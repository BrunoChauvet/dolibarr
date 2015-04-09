<?php

/**
 * Mno Invoice Class
 */
class MnoSoaInvoice extends MnoSoaBaseInvoice {
  protected $_local_entity_name = "INVOICE";

  protected function pushInvoice() {
    $id = $this->getLocalEntityIdentifier();
    MnoSoaLogger::debug("start pushInvoice for " . json_encode($id));

    if (empty($id)) { return false; }

    $mno_id = $this->getMnoIdByLocalIdName($id, $this->_local_entity_name);
    $this->_id = ($this->isValidIdentifier($mno_id)) ? $mno_id->_id : null;
    MnoSoaLogger::debug("mapped to mno invoice " . json_encode($mno_id));

    $this->_transaction_number = $this->push_set_or_delete_value($this->_local_entity->ref_client);
    $this->_transaction_date = $this->push_set_or_delete_value($this->_local_entity->date);
    $this->_due_date = $this->push_set_or_delete_value($this->_local_entity->date_lim_reglement);
    $this->_public_note = $this->push_set_or_delete_value($this->_local_entity->note_public);
    $this->_private_note = $this->push_set_or_delete_value($this->_local_entity->note_private);

    $this->_amount->price = floatval($this->push_set_or_delete_value($this->_local_entity->total_ttc));
    $this->_amount->netAmount = floatval($this->push_set_or_delete_value($this->_local_entity->total_ht));
    $this->_amount->taxAmount = floatval($this->push_set_or_delete_value($this->_local_entity->total_tva));
    $this->_amount->netAmount = floatval($this->push_set_or_delete_value($this->_local_entity->total_ht));
    $this->_amount->currency = $this->getMainCurrency();

    // Map status
    $paid = $this->push_set_or_delete_value($this->_local_entity->paye);
    if($paid == 1) {
      $this->_status = 'PAID';
    } else {
      $status_code = $this->push_set_or_delete_value($this->_local_entity->statut);
      if($status_code == 0) {
        $this->_status = 'DRAFT';
      } else if($status_code == 1) {
        $this->_status = 'AUTHORISED';
      } else if($status_code == 2) {
        $this->_status = 'AUTHORISED';
      } else if($status_code == 3) {
        $this->_status = 'VOIDED';
      } 
    }

    // Map type
    $type = $this->push_set_or_delete_value($this->_local_entity->type);
    if($type == 0) {
      $this->_type = 'CUSTOMER';
    } else if($type == 3) {
      $this->_type = 'SUPPLIER';
    }

    // Pull Organization ID
    $mno_customer_id = $this->getMnoIdByLocalIdName($this->_local_entity->socid, "SOCIETE");
    $this->_organization_id = $mno_customer_id->_id;

    // Pull Invoice lines
    $this->_invoice_lines = array();
    if(!empty($this->_local_entity->lines)) {
      foreach($this->_local_entity->lines as $line) {
        $invoice_line = array();
        
        // Find mno id if already exists
        $active = true;
        $mno_invoice_line_id = $this->getMnoIdByLocalIdName($line->rowid, "INVOICE_LINE");
        if($this->isDeletedIdentifier($mno_invoice_line_id)) {
          $invoice_line_mno_id = $mno_invoice_line_id->_id;
          $active = false;
        } else if (!$this->isValidIdentifier($mno_invoice_line_id)) {
          // Generate and save ID
          $invoice_line_mno_id = $this->_id . "#" . uniqid();
          $this->_mno_soa_db_interface->addIdMapEntry($line->rowid, "INVOICE_LINE", $invoice_line_mno_id, "INVOICE_LINE");
        } else {
          $invoice_line_mno_id = $mno_invoice_line_id->_id;
        }

        $invoice_line_id_parts = explode("#", $invoice_line_mno_id);
        $invoice_line_mno_id = $invoice_line_id_parts[1];

        // Map Product
        $local_product_id = $this->push_set_or_delete_value($line->fk_product);
        $mno_item_id = $this->getMnoIdByLocalIdName($line->fk_product, "ITEMS");
        $invoice_line['item']->id = $mno_item_id->_id;

        // Push line price
        $invoice_line['id'] = $invoice_line_mno_id;
        $invoice_line['lineNumber'] = intval($line->rang);
        $invoice_line['description'] = $line->desc;
        $invoice_line['quantity'] = intval($line->qty);

        $invoice_line['unitPrice'] = array();
        $invoice_line['unitPrice']['netAmount'] = floatval($line->subprice);
        $invoice_line['unitPrice']['taxRate'] = floatval($line->tva_tx);

        $invoice_line['totalPrice'] = array();
        $invoice_line['totalPrice']['price'] = floatval($line->total_ttc);
        $invoice_line['totalPrice']['taxRate'] = floatval($line->tva_tx);
        $invoice_line['totalPrice']['taxAmount'] = floatval($line->total_tva);
        $invoice_line['totalPrice']['netAmount'] = floatval($line->total_ht);

        $invoice_line['reductionPercent'] = floatval($line->remise_percent);
        $invoice_line['status'] = $active ? 'ACTIVE' : 'INACTIVE';

        $invoice_line['taxCode'] = array();
        $invoice_line['taxCode']['id'] = $this->mapTaxCode($line);

        $this->_invoice_lines[$invoice_line_mno_id] = $invoice_line;
      }
    }

    return true;
  }

  protected function pullInvoice() {
    MnoSoaLogger::debug("start pullInvoice for " . json_encode($this->_id));

    if (empty($this->_id)) {
      MnoSoaLogger::debug("Invoice does not have an ID, skipping");
      return constant('MnoSoaBaseEntity::STATUS_ERROR');
    }
    if (empty($this->_organization_id)) {
      MnoSoaLogger::debug("Invoice does not have an Organization ID, skipping");
      return constant('MnoSoaBaseEntity::STATUS_ERROR');
    }

    $local_id = $this->getLocalIdByMnoIdName($this->_id, $this->_mno_entity_name);
    if ($this->isDeletedIdentifier($local_id)) { return constant('MnoSoaBaseEntity::STATUS_DELETED_ID'); }
    $mno_status_format = strtoupper($this->pull_set_or_delete_value($this->_status));

    $this->_local_entity = new Facture($this->_db);
    if ($this->isValidIdentifier(($local_id))) { 
      $return_status = constant('MnoSoaBaseEntity::STATUS_EXISTING_ID'); 
      $this->_local_entity->fetch($local_id->_id);

      if ($mno_status_format == "INACTIVE") { 
        $this->_local_entity->delete(false);
        $this->deleteIdMapEntryName($local_id, $this->_local_entity_name);
        return constant('MnoSoaBaseEntity::STATUS_DELETED_ID');
      }
    } else {
      if ($mno_status_format == "INACTIVE") { return constant('MnoSoaBaseEntity::STATUS_DELETED_ID'); }
      $return_status = constant('MnoSoaBaseEntity::STATUS_NEW_ID');
    }

    $this->_local_entity->ref_client = $this->pull_set_or_delete_value($this->_transaction_number);
    $this->_local_entity->note_public = $this->pull_set_or_delete_value($this->_public_note);
    $this->_local_entity->note_private = $this->pull_set_or_delete_value($this->_private_note);

    $this->_local_entity->date = $this->pull_set_or_delete_value($this->_transaction_date);
    $this->_local_entity->date_lim_reglement = $this->pull_set_or_delete_value($this->_due_date);
    $this->_local_entity->total = $this->pull_set_or_delete_value($this->_amount->netAmount);
    $this->_local_entity->total_ttc = $this->pull_set_or_delete_value($this->_amount->price);
    $this->_local_entity->total_ht = $this->pull_set_or_delete_value($this->_amount->netAmount);
    $this->_local_entity->total_tva = $this->pull_set_or_delete_value($this->_amount->taxAmount);

    // Map type
    if($this->_type == 'SUPPLIER') {
      $this->_local_entity->type = 3;
    } else {
      $this->_local_entity->type = 0;
    }

    // Map local organization
    $local_id = $this->getLocalIdByMnoIdName($this->_organization_id, "organizations");
    if ($this->isValidIdentifier($local_id)) {
      MnoSoaLogger::debug(__FUNCTION__ . "organization local_id = " . json_encode($local_id));
      $this->_local_entity->socid = $local_id->_id;
    } else if ($this->isDeletedIdentifier($local_id)) {
      // do not update
      return;
    } else {
      // Fetch remote Organization if missing
      $notification->entity = "organizations";
      $notification->id = $this->organization->id;
      $organization = new MnoSoaOrganization($this->_db);   
      $status = $organization->receiveNotification($notification);
      if ($status) {
        $this->_local_entity->socid = $organization->_local_entity->id;
        $this->_local_entity->poste = $this->pull_set_or_delete_value($this->_role->title, "");
      }
    }

    MnoSoaLogger::debug("Returning entity " . json_encode($this->_local_entity));

    return $return_status;
  }

  protected function saveLocalEntity($push_to_maestrano, $status) {
    MnoSoaLogger::debug("start saveLocalEntity status=$status " . json_encode($this->_local_entity));

    $user = (object) array();
    $user->id = "1";
    $user->rights->facture->valider = true;

    if ($status == constant('MnoSoaBaseEntity::STATUS_NEW_ID')) {
      $invoice_local_id = $this->_local_entity->create($user, 0, 0, $push_to_maestrano);
      if ($invoice_local_id > 0) {
        $this->addIdMapEntryName($invoice_local_id, $this->_local_entity_name, $this->_id, $this->_mno_entity_name);
      }
    } else if ($status == constant('MnoSoaBaseEntity::STATUS_EXISTING_ID')) {
      $this->_local_entity->update($user, 0, $push_to_maestrano);
      $invoice_local_id = $this->getLocalEntityIdentifier();
    }

    $mno_invoice_line = new MnoSoaInvoiceLine($this->_db, $this->_log);
    $mno_invoice_line->saveLocalEntity($this, $invoice_local_id, $this->_id, $this->_invoice_lines, $push_to_maestrano);

    // Calculate invoice amounts
    $this->_local_entity->update_price(1);

    // Update invoice status
    if($this->_status == 'PAID') {
      $this->_local_entity->set_paid($user, '', '', false);
    } else if($this->_status == 'SUBMITTED' || $this->_status == 'AUTHORISED') {
      $this->_local_entity->validate($user, '', 0, false);
    } else if($this->_status == 'VOIDED' || $this->_status == 'INACTIVE') {
      $this->_local_entity->set_canceled($user, '', '', false);
    } else  {
      $this->_local_entity->set_draft($user, -1, false);
    }

  }

  public function getLocalEntityIdentifier() {
    return $this->_local_entity->id;
  }

  protected function getMainCurrency() {
    global $conf;
    return $conf->currency;
  }

  protected function mapTaxCode($line) {
    $line_tax_rate = floatval($line->tva_tx);
    if($line_tax_rate > 0) {
      $country_taxes = $this->fetchTaxes();

      foreach ($country_taxes as $country_tax) {
        if($country_tax['taux'] == $line_tax_rate) {
          $mno_id = $this->getMnoIdByLocalIdName($country_tax['rowid'], 'TAX');
          if(isset($mno_id)) {
            return $mno_id->_id;
          }
        }
      }
    }
    return null;
  }

  private function fetchTaxes() {
    global $mysoc;

    $sql = "SELECT t.rowid, t.taux, t.note";
    $sql.= " FROM ".MAIN_DB_PREFIX."c_tva as t";
    $sql.= ", ".MAIN_DB_PREFIX."c_pays as p";
    $sql.= " WHERE t.fk_pays = p.rowid";
    $sql.= " AND t.active = 1";
    $sql.= " AND p.code = '".$mysoc->country_code."'";
    $sql.= " ORDER BY t.rowid DESC";

    $taxes = null;
    $resql = $this->_db->query($sql);
    for($i=0;$tax = $this->_db->fetch_array();$i++) {
      $taxes[$i] = $tax;
    }

    return $taxes;
  }
}

?>