<?php

/**
 * Mno Payment Class
 */
class MnoSoaPayment extends MnoSoaBasePayment
{
    protected $_local_entity_name = "PAYMENTS";
    
    protected function pushPayment() {
      MnoSoaLogger::debug(__FUNCTION__ . " start");

      $id = $this->getLocalEntityIdentifier();
      if (empty($id)) { return; }

      $mno_id = $this->getMnoIdByLocalIdName($id, $this->_local_entity_name);
      $this->_id = ($this->isValidIdentifier($mno_id)) ? $mno_id->_id : null;
      MnoSoaLogger::debug("mapped to mno payment " . json_encode($mno_id));

      // Map payment attributes
      $this->_payment_reference = $this->push_set_or_delete_value($this->_local_entity->num_paiement);
      $this->_transaction_date = $this->push_set_or_delete_value($this->_local_entity->datepaye);
      $this->_total_amount = floatval($this->push_set_or_delete_value($this->_local_entity->amount));
      $this->_currency = $this->getMainCurrency();
      $this->_public_note = $this->push_set_or_delete_value($this->_local_entity->note);

      // Map Account ID
      $mno_account_id = $this->getMnoIdByLocalIdName($this->_local_entity->fk_account, "ACCOUNT");
      $this->_deposit_account_id = $mno_account_id->_id;

      // Map Payment Method ID
      $mno_payment_method_id = $this->getMnoIdByLocalIdName($this->_local_entity->paiementid, "PAYMENT_METHOD");
      $this->_payment_method_id = $mno_payment_method_id->_id;

      // Map payment lines ID
      $local_payment_lines = $this->getLocalPaymentLines();
      foreach ($local_payment_lines as $key => $local_payment_line) {
        // Find or create a payment line entry
        $local_payment_line_id = $local_payment_line->rowid;
        $mno_payment_line_id = $this->getMnoIdByLocalIdName($local_payment_line_id, "PAYMENT_LINE");
        if($this->isDeletedIdentifier($mno_payment_line_id)) {
          $payment_line_mno_id = $mno_payment_line_id->_id;
        } else if (!$this->isValidIdentifier($mno_payment_line_id)) {
          // Generate and save ID
          $payment_line_mno_id = uniqid();
          $this->_mno_soa_db_interface->addIdMapEntry($local_payment_line_id, "PAYMENT_LINE", $payment_line_mno_id, "PAYMENT_LINE");
        } else {
          // Use existing payment line
          $payment_line_mno_id = $mno_payment_line_id->_id;
        }
        $payment_line = array();
        $payment_line['id'] = $payment_line_mno_id;
        $payment_line['amount'] = $local_payment_line->amount;

        // Map single Invoice to Payment line
        $mno_invoice_id = $this->getMnoIdByLocalIdName($local_payment_line->fk_facture, "INVOICE");
        $payment_line['linkedTransactions'] = array($mno_invoice_id->_id => array('id' => $mno_invoice_id->_id));

        $this->_payment_lines[$payment_line_mno_id] = $payment_line;
      }

      MnoSoaLogger::debug(__FUNCTION__ . " end");
    }
    
    protected function pullPayment() {
      MnoSoaLogger::debug(__FUNCTION__ . " start");
      
      $return_status = null;
      if (empty($this->_id)) { return constant('MnoSoaBaseEntity::STATUS_ERROR'); }

      // Create new payments only, no updates
      $local_id = $this->getLocalIdByMnoIdName($this->_id, $this->_mno_entity_name);
      if ($this->isDeletedIdentifier($local_id)) { return constant('MnoSoaBaseEntity::STATUS_DELETED_ID'); }
      if ($this->isValidIdentifier(($local_id))) { return constant('MnoSoaBaseEntity::STATUS_EXISTING_ID'); }
      // Payments without invoice references are not supported
      if (empty($this->_payment_lines)) { return constant('MnoSoaBaseEntity::STATUS_ERROR'); }

      $return_status = constant('MnoSoaBaseEntity::STATUS_NEW_ID');

      $paiement = new Paiement($this->_db);
      $this->_local_entity = $paiement;
      $paiement->datepaye = date('Y-m-d H:i:s', $this->_transaction_date);
      $paiement->amounts = array();
      $paiement->num_paiement = $this->_payment_reference;

      // Map Account
      if(isset($this->_deposit_account_id)) {
        $mno_account_id = $this->getLocalIdByMnoIdName($this->_deposit_account_id, "ACCOUNTS");
        $paiement->bank_account = $mno_account_id->_id;
      }
      
      // Payment type
      $paiement->paiementid =  6;
      if(isset($this->_payment_method_id)) {
        $local_payment_method_id = $this->getLocalIdByMnoIdName($this->_payment_method_id, "PAYMENT_METHODS");
        if($this->isValidIdentifier($local_payment_method_id)) {
          $paiement->paiementid = $local_payment_method_id->_id;
        }
      }

      // Note
      if(isset($this->_public_note)) {
        $paiement->note = $this->_public_note;
      } else if(isset($this->_private_note)) {
        $paiement->note = $this->_private_note;
      }

      // Map each linked invoice of each payment line to a local payment
      foreach($this->_payment_lines as $line_id => $line) {
        $payment_line_amount = floatval($line->amount);
        foreach($line->linkedTransactions as $invoice_id => $linked_transaction) {
          // Map invoice
          $invoice_local_id = $this->getLocalIdByMnoIdName($invoice_id, "INVOICES");
          if ($this->isValidIdentifier($invoice_local_id)) {
            $paiement->ac_inv_id = $invoice_local_id->_id;
          } else {
            // Fetch remote invoice if missing
            $notification->entity = "invoices";
            $notification->id = $invoice_id;
            $mno_invoice = new MnoSoaInvoice($this->_db, $this->_log);   
            $status = $mno_invoice->receiveNotification($notification);
            if ($status) {
              $paiement->ac_inv_id = $invoice_local_id->_id;
            }
          }

          $paiement->amounts[$invoice_local_id->_id] = $payment_line_amount;
        }
      }

      MnoSoaLogger::debug(__FUNCTION__ . " end");

      return $return_status;
    }
    
    protected function saveLocalEntity($push_to_maestrano, $status) {
      MnoSoaLogger::debug("saving _local_entity=" . json_encode($this->_local_entity));
      if ($status == constant('MnoSoaBaseEntity::STATUS_NEW_ID')) {
        // Save the payment
        $user = (object) array();
        $user->id = "1";
        $user->rights->facture->valider = true;
        $local_payment_id = $this->_local_entity->create($user, 0, false);
        if($local_payment_id > 0) {
          $this->_local_entity->id = $local_payment_id;

          // Add payment to account
          if(isset($this->_deposit_account_id)) {
            $mno_account_id = $this->getLocalIdByMnoIdName($this->_deposit_account_id, "ACCOUNTS");
            $label='(CustomerInvoicePayment)';
            $this->_local_entity->addPaymentToBank($user, 'payment', $label, $mno_account_id->_id, null, null, 0, false);
          }
        } else {
          $this->_local_entity->id = null;
        }
      }
    }
    
    public function getLocalEntityIdentifier() {
        return $this->_local_entity->id;
    }

    protected function getMainCurrency() {
      global $conf;
      return $conf->currency;
    }

    protected function getLocalPaymentLines() {
      $sql = 'SELECT *';
      $sql.= ' FROM '.MAIN_DB_PREFIX.'paiement_facture ';
      $sql.= ' WHERE fk_paiement = '.$this->getLocalEntityIdentifier();
      $resql = $this->_db->query($sql);
      $i=0;
      $num=$this->_db->num_rows($resql);
      $payment_lines=array();
      while ($i < $num) {
        $obj = $this->_db->fetch_object($resql);
        $payment_lines[$i]=$obj;
        $i++;
      }

      return $payment_lines;
    }
}

?>