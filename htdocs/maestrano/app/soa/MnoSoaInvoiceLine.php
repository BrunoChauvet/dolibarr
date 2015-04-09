<?php

/**
 * Mno InvoiceLine Class
 */
class MnoSoaInvoiceLine extends MnoSoaBaseInvoiceLine
{
  protected $_local_entity_name = "INVOICE_LINE";

  public function saveLocalEntity($mno_invoice, $invoice_local_id, $invoice_mno_id, $invoice_lines, $push_to_maestrano) {
    MnoSoaLogger::debug("Saving invoice lines for invoice $invoice_mno_id => " . json_encode($invoice_lines));

    if(!empty($invoice_lines)) {
      $processed_lines_local_ids = array();
      foreach($invoice_lines as $line_id => $line) {
        $unique_line_id = $invoice_mno_id . "#" . $line_id;
        $local_line_id = $this->getLocalIdByMnoId($unique_line_id);

        if($this->isDeletedIdentifier($local_line_id)) {
          continue;
        }

        $invoice_line = new FactureLigne($this->_db);
        $new_record = true;
        if ($this->isValidIdentifier($local_line_id)) {
          $new_record = false;
          $invoice_line->fetch($local_line_id->_id);
        }

        // Apply invoice level discount to lines
        $invoice_discount_percent = $mno_invoice->_discount_percent;
        if(!isset($invoice_discount_percent)) {
          $invoice_discount_percent = 0;
        }

        $invoice_line->fk_facture = $invoice_local_id;
        $invoice_line->rang = $line->lineNumber;
        $invoice_line->desc = $line->description;
        $invoice_line->tva_tx = $line->totalPrice->taxRate;
        $invoice_line->qty = isset($line->quantity) ? $line->quantity : null;
        $invoice_line->subprice = $line->unitPrice->netAmount;

        if($invoice_discount_percent > 0) {
          $invoice_line->total_ht = $line->totalPrice->netAmount * (1 - ($invoice_discount_percent / 100));
          $invoice_line->total_tva = $line->totalPrice->taxAmount * (1 - ($invoice_discount_percent / 100));
          $invoice_line->total_ttc = $line->totalPrice->price * (1 - ($invoice_discount_percent / 100));
          $invoice_line->remise_percent = $invoice_discount_percent;
        } else {
          $invoice_line->total_ht = $line->totalPrice->netAmount;
          $invoice_line->total_tva = $line->totalPrice->taxAmount;
          $invoice_line->total_ttc = $line->totalPrice->price;
          $invoice_line->remise_percent = $line->reductionPercent;
        }
        
        // Map item
        if(!empty($line->item)) {
          $local_item_id = $this->getLocalIdByMnoIdName($line->item->id, "ITEMS");
          $invoice_line->fk_product = $local_item_id->_id;
        }

        if($new_record) {
          MnoSoaLogger::debug("Creating new invoice line " . json_encode($unique_line_id));
          $local_id = $invoice_line->insert(0, $push_to_maestrano);
          if ($local_id > 0) {
            $this->addIdMapEntry($local_id, $unique_line_id);
          }
        } else {
          MnoSoaLogger::debug("Updating invoice line " . json_encode($unique_line_id));
          $local_id = $local_line_id->_id;
          $invoice_line->update('', 0, $push_to_maestrano);
        }

        // Keep track of received line IDs to remove missing ones
        array_push($processed_lines_local_ids, $local_id);
      }

      // Delete local invoice lines that have been removed
      $local_invoice_lines = $mno_invoice->_local_entity->lines;
      foreach ($local_invoice_lines as $local_invoice_line) {
        if(!in_array($local_invoice_line->rowid, $processed_lines_local_ids)) {
          MnoSoaLogger::debug("Deleting invoice line " . json_encode($unique_line_id));
          $local_invoice_line->delete(false);
        }
      }
    }

    MnoSoaLogger::debug("Finished saving invoice lines");
  }

  protected function getMainCurrency() {
    global $conf;
    return $conf->currency;
  }
}

?>