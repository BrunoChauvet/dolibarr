<?php

/**
 * Mno Payment Interface
 */
class MnoSoaBasePayment extends MnoSoaBaseEntity
{
  protected $_mno_entity_name = "payments";
  protected $_create_rest_entity_name = "payments";
  protected $_create_http_operation = "POST";
  protected $_update_rest_entity_name = "payments";
  protected $_update_http_operation = "POST";
  protected $_receive_rest_entity_name = "payments";
  protected $_receive_http_operation = "GET";
  protected $_delete_rest_entity_name = "payments";
  protected $_delete_http_operation = "DELETE";

  protected $_enable_delete_notifications=true;
  
  protected $_id;
  protected $_payment_reference;
  protected $_total_amount;
  protected $_transaction_date;
  protected $_currency;
  protected $_status;
  protected $_private_note;
  protected $_public_note;
  protected $_payment_method_id;
  protected $_organization_id;
  protected $_person_id;
  protected $_deposit_account_id;
  protected $_payment_lines = array();

  protected function pushPayment() {
    throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoPayment class!');
  }
  
  protected function pullPayment() {
    throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoPayment class!');
  }

  protected function saveLocalEntity($push_to_maestrano, $status) {
    throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoPayment class!');
  }
  
  public function getLocalEntityIdentifier() {
    throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoPayment class!');
  }
  
  protected function build() {
    MnoSoaLogger::debug("start");
    $this->pushPayment();
    if ($this->_payment_reference != null) { $msg['payment']->paymentReference = $this->_payment_reference; }
    if ($this->_total_amount != null) { $msg['payment']->totalAmount = $this->_total_amount; }
    if ($this->_transaction_date != null) { $msg['payment']->transactionDate = $this->_transaction_date; }
    if ($this->_currency != null) { $msg['payment']->currency = $this->_currency; }
    if ($this->_status != null) { $msg['payment']->status = $this->_status; }
    if ($this->_private_note != null) { $msg['payment']->privateNote = $this->_private_note; }
    if ($this->_public_note != null) { $msg['payment']->publicNote = $this->_public_note; }
    if ($this->_payment_method_id != null) { $msg['payment']->paymentMethod->id = $this->_payment_method_id; }
    if ($this->_organization_id != null) { $msg['payment']->organization->id = $this->_organization_id; }
    if ($this->_person_id != null) { $msg['payment']->person->id = $this->_person_id; }
    if ($this->_deposit_account_id != null) { $msg['payment']->depositAccount->id = $this->_deposit_account_id; }
    if ($this->_payment_lines != null) { $msg['payment']->paymentLines = $this->_payment_lines; }

    $result = json_encode($msg['payment']);

    MnoSoaLogger::debug("result = $result");

    return $result;
  }
  
  protected function persist($mno_entity) {
    MnoSoaLogger::debug("start");
    
    if (!empty($mno_entity->payment)) {
      $mno_entity = $mno_entity->payment;
    }
    
    if (!empty($mno_entity->id)) {
      $this->_id = $mno_entity->id;
      $this->set_if_array_key_has_value($this->_payment_reference, 'paymentReference', $mno_entity);
      $this->set_if_array_key_has_value($this->_transaction_date, 'transactionDate', $mno_entity);
      $this->set_if_array_key_has_value($this->_total_amount, 'totalAmount', $mno_entity);
      $this->set_if_array_key_has_value($this->_currency, 'currency', $mno_entity);
      $this->set_if_array_key_has_value($this->_status, 'status', $mno_entity);
      $this->set_if_array_key_has_value($this->_private_note, 'privateNote', $mno_entity);
      $this->set_if_array_key_has_value($this->_public_note, 'publicNote', $mno_entity);
      
      if (!empty($mno_entity->paymentMethod)) {
        $this->set_if_array_key_has_value($this->_payment_method_id, 'id', $mno_entity->paymentMethod);
      }
      if (!empty($mno_entity->organization)) {
        $this->set_if_array_key_has_value($this->_organization_id, 'id', $mno_entity->organization);
      }
      if (!empty($mno_entity->person)) {
        $this->set_if_array_key_has_value($this->_person_id, 'id', $mno_entity->person);
      }
      if (!empty($mno_entity->depositAccount)) {
        $this->set_if_array_key_has_value($this->_deposit_account_id, 'id', $mno_entity->depositAccount);
      }
      if (!empty($mno_entity->paymentLines)) {
        $this->set_if_array_key_has_value($this->_payment_lines, 'paymentLines', $mno_entity);
      }

      MnoSoaLogger::debug("id = " . $this->_id);

      $status = $this->pullPayment();
      MnoSoaLogger::debug("after pullPayment");
      
      if ($status == constant('MnoSoaBaseEntity::STATUS_NEW_ID') || $status == constant('MnoSoaBaseEntity::STATUS_EXISTING_ID')) {
        $this->saveLocalEntity(false, $status);

        $local_entity_id = $this->getLocalEntityIdentifier();
        $mno_entity_id = $this->_id;
        if ($status == constant('MnoSoaBaseEntity::STATUS_NEW_ID') && !empty($local_entity_id) && !empty($mno_entity_id)) {
          $this->addIdMapEntry($local_entity_id, $mno_entity_id);
        }
      }
    }
    MnoSoaLogger::debug("end");
  }
}

?>