<?php

/**
 * Mno Invoice Interface
 */
class MnoSoaBaseInvoice extends MnoSoaBaseEntity
{
  protected $_mno_entity_name = "invoices";
  protected $_create_rest_entity_name = "invoices";
  protected $_create_http_operation = "POST";
  protected $_update_rest_entity_name = "invoices";
  protected $_update_http_operation = "POST";
  protected $_receive_rest_entity_name = "invoices";
  protected $_receive_http_operation = "GET";
  protected $_delete_rest_entity_name = "invoices";
  protected $_delete_http_operation = "DELETE";

  protected $_enable_delete_notifications=true;
  
  public $_id;
  public $_transaction_number;
  public $_transaction_date;
  public $_amount;
  public $_currency;
  public $_due_date;
  public $_status;
  public $_type;
  public $_public_note;
  public $_private_note;
  public $_balance;
  public $_deposit;
  public $_discount_percent;
  public $_organization_id;
  public $_invoice_lines;

  protected function pushInvoice() {
    throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoInvoice class!');
  }
  
  protected function pullInvoice() {
    throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoInvoice class!');
  }

  protected function saveLocalEntity($push_to_maestrano, $status) {
    throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoInvoice class!');
  }
  
  public function getLocalEntityIdentifier() {
    throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoInvoice class!');
  }
  
  protected function build() {
    MnoSoaLogger::debug("start");
    $this->pushInvoice();
    if ($this->_transaction_number != null) { $msg['invoice']->transactionNumber = $this->_transaction_number; }
    if ($this->_transaction_date != null) { $msg['invoice']->transactionDate = $this->_transaction_date; }
    if ($this->_amount != null) { $msg['invoice']->amount = $this->_amount; }
    if ($this->_currency != null) { $msg['invoice']->currency = $this->_currency; }
    if ($this->_due_date != null) { $msg['invoice']->dueDate = $this->_due_date; }
    if ($this->_status != null) { $msg['invoice']->status = $this->_status; }
    if ($this->_type != null) { $msg['invoice']->type = $this->_type; }
    if ($this->_public_note != null) { $msg['invoice']->publicNote = $this->_public_note; }
    if ($this->_private_note != null) { $msg['invoice']->privateNote = $this->_private_note; }
    if ($this->_balance != null) { $msg['invoice']->balance = $this->_balance; }
    if ($this->_deposit != null) { $msg['invoice']->deposit = $this->_deposit; }
    if ($this->_discount_percent != null) { $msg['invoice']->discountPercent = $this->_discount_percent; }
    if ($this->_organization_id != null) { $msg['invoice']->organization->id = $this->_organization_id; }
    if ($this->_invoice_lines != null) { $msg['invoice']->invoiceLines = $this->_invoice_lines; }

    $result = json_encode($msg['invoice']);

    MnoSoaLogger::debug("result = $result");

    return $result;
  }
  
  protected function persist($mno_entity) {
    MnoSoaLogger::debug("start");
    
    if (!empty($mno_entity->invoice)) {
      $mno_entity = $mno_entity->invoice;
    }
    
    if (!empty($mno_entity->id)) {
      $this->_id = $mno_entity->id;
      $this->set_if_array_key_has_value($this->_transaction_number, 'transactionNumber', $mno_entity);
      $this->set_if_array_key_has_value($this->_transaction_date, 'transactionDate', $mno_entity);
      $this->set_if_array_key_has_value($this->_amount, 'amount', $mno_entity);
      $this->set_if_array_key_has_value($this->_currency, 'currency', $mno_entity);
      $this->set_if_array_key_has_value($this->_due_date, 'dueDate', $mno_entity);
      $this->set_if_array_key_has_value($this->_status, 'status', $mno_entity);
      $this->set_if_array_key_has_value($this->_type, 'type', $mno_entity);
      $this->set_if_array_key_has_value($this->_public_note, 'publicNote', $mno_entity);
      $this->set_if_array_key_has_value($this->_private_note, 'privateNote', $mno_entity);
      $this->set_if_array_key_has_value($this->_balance, 'balance', $mno_entity);
      $this->set_if_array_key_has_value($this->_deposit, 'deposit', $mno_entity);
      $this->set_if_array_key_has_value($this->_discount_percent, 'discountPercent', $mno_entity);

      if (!empty($mno_entity->organization)) {
        $this->set_if_array_key_has_value($this->_organization_id, 'id', $mno_entity->organization);
      }

      if (!empty($mno_entity->invoiceLines)) {
        $this->set_if_array_key_has_value($this->_invoice_lines, 'invoiceLines', $mno_entity);
      }

      MnoSoaLogger::debug("id = " . $this->_id);

      $status = $this->pullInvoice();
      MnoSoaLogger::debug("after pullInvoice");
      
      if ($status == constant('MnoSoaBaseEntity::STATUS_NEW_ID') || $status == constant('MnoSoaBaseEntity::STATUS_EXISTING_ID')) {
        $this->saveLocalEntity(false, $status);
      }
    }
    MnoSoaLogger::debug("end");
  }
}

?>