<?php

/**
* Map Connec Customer Invoice Line representation to/from Dolibarr FactureLigne
*/
class CustomerInvoiceLineMapper extends BaseMapper {
  private $invoice = null;
  private $invoice_hash = null;

  public function __construct($invoice=null, $invoice_hash=null) {
    parent::__construct();

    $this->connec_entity_name = 'InvoiceLine';
    $this->local_entity_name = 'FactureLigne';
    $this->connec_resource_name = 'invoices/lines';
    $this->connec_resource_endpoint = 'invoices/lines';

    $this->invoice = $invoice;
    $this->invoice_hash = $invoice_hash;
  }

  // Invoice Line ID
  protected function getId($invoice_line) {
    return $invoice_line->rowid;
  }

  // Prefix the Invoice Line ID with the Invoice ID to ensure unicity
  protected function getConnecResourceId($invoice_line_hash) {
    return $this->invoice_hash['id'] . "#" . $invoice_line_hash['id'];
  }

  // Return a local FactureLigne by id
  protected function loadModelById($local_id) {
    global $db;

    $invoice_line = new $this->local_entity_name($db);
    $invoice_line->fetch($local_id);
    return $invoice_line;
  }

  // Load by Invoice and Line number
  protected function matchLocalModel($invoice_line_hash) {
    foreach($this->invoice->lines as $invoice_line) {
      if(intval($invoice_line->rang) == intval($invoice_line_hash['line_number'])) { return $invoice_line; }
    }
    return null;
  }

  // Map the Connec resource attributes onto the Dolibarr FactureLigne
  protected function mapConnecResourceToModel($invoice_line_hash, $invoice_line) {
    // Line attributes
    $invoice_line->fk_facture = $this->invoice->id;
    $invoice_line->rang = $invoice_line_hash['line_number'];
    $invoice_line->desc = $invoice_line_hash['description'];
    $invoice_line->tva_tx = $invoice_line_hash['total_price']['tax_rate'];
    if($this->is_set($invoice_line_hash['quantity'])) { $invoice_line->qty = $invoice_line_hash['quantity']; }
    
    // Line amounts
    $invoice_line->total_ht = $invoice_line_hash['total_price']['net_amount'] ? $invoice_line_hash['total_price']['net_amount'] : 0;
    $invoice_line->total_tva = $invoice_line_hash['total_price']['tax_amount'] ? $invoice_line_hash['total_price']['tax_amount'] : 0;
    $invoice_line->total_ttc = $invoice_line_hash['total_price']['total_amount'] ? $invoice_line_hash['total_price']['total_amount'] : 0;
    $invoice_line->remise_percent = $invoice_line_hash['reduction_percent'];
    $invoice_line->subprice = $invoice_line_hash['unit_price']['net_amount'];

    // Map item
    if(!empty($invoice_line_hash['item_id'])) {
      $mno_id_map = MnoIdMap::findMnoIdMapByMnoIdAndEntityName($invoice_line_hash['item_id'], 'ITEM');
      $invoice_line->fk_product = $mno_id_map['app_entity_id'];
    }
  }

  // Map the Dolibarr Invoice to a Connec resource hash
  protected function mapModelToConnecResource($invoice_line) {
    $invoice_line_hash = array();

    $productid = intval($invoice_line->fk_product);
    $line_number = intval($invoice_line->rang);
    $quantity = floatval($invoice_line->qty);
    $unit_price = floatval($invoice_line->subprice);
    $tax_rate = floatval($invoice_line->tva_tx);
    $total_amount = floatval($invoice_line->total_ht);
    $total_tax_amount = floatval($invoice_line->total_tva);
    $total_net_amount = floatval($invoice_line->total_ttc);
    $reduction_percent = floatval($invoice_line->remise_percent);
    $description = $invoice_line->desc;

    // Map Invoice Line ID
    $invoice_line_local_id = $invoice_line->rowid;
    $invoice_line_mno_id = MnoIdMap::findMnoIdMapByLocalIdAndEntityName($invoice_line_local_id, $this->local_entity_name);
    if($invoice_line_mno_id) {
      $invoice_line_id_parts = explode("#", $invoice_line_mno_id['mno_entity_guid']);
      $invoice_line_hash['id'] = $invoice_line_id_parts[1];
    }

    $invoice_line_hash['status'] = 'ACTIVE';
    $invoice_line_hash['line_number'] = $line_number;
    $invoice_line_hash['description'] = $description;
    $invoice_line_hash['quantity'] = $quantity;
    $invoice_line_hash['reduction_percent'] = $reduction_percent;
    $invoice_line_hash['unit_price'] = array('net_amount' => $unit_price, 'tax_rate' => $tax_rate);
    $invoice_line_hash['total_price'] = array('total_amount' => $total_amount, 'tax_amount' => $total_tax_amount, 'net_amount' => $total_net_amount, 'tax_rate' => $tax_rate);

    // Map tax code by tax rate (best match)
    if($tax_rate > 0) {
      $country_taxes = ConnecUtils::fetchTaxes();
      if($country_taxes) {
        foreach ($country_taxes as $country_tax) {
          if($country_tax['taux'] == $tax_rate) {
            $mno_id_map = MnoIdMap::findMnoIdMapByLocalIdAndEntityName($country_tax['rowid'], 'TAXCODE');
            if($mno_id_map) { $invoice_line_hash['tax_code_id'] = $mno_id_map['mno_entity_guid']; }
            break;
          }
        }
      }
    }

    // Map item id
    $mno_id_map = MnoIdMap::findMnoIdMapByLocalIdAndEntityName($productid, 'PRODUCT');
    if($mno_id_map) { $invoice_line_hash['item_id'] = $mno_id_map['mno_entity_guid']; }

    return $invoice_line_hash;
  }

  // Persist the Dolibarr InvoiceLine
  protected function persistLocalModel($invoice_line, $invoice_line_hash) {
    if(!$this->is_set($invoice_line->rowid)) {
      $invoice_line->rowid = $invoice_line->insert(0, false);
    } else {
      $user = ConnecUtils::defaultUser();
      $invoice_line->update($user, 0, false);
    }
  }
}