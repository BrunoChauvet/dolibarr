<?php

/**
 * Mno PaymentMethod Interface
 */
class MnoSoaBasePaymentMethod extends MnoSoaBaseEntity
{
  protected $_mno_entity_name = "payment_methods";
  protected $_create_rest_entity_name = "payment_methods";
  protected $_create_http_operation = "POST";
  protected $_update_rest_entity_name = "payment_methods";
  protected $_update_http_operation = "POST";
  protected $_receive_rest_entity_name = "payment_methods";
  protected $_receive_http_operation = "GET";
  protected $_delete_rest_entity_name = "payment_methods";
  protected $_delete_http_operation = "DELETE";
  
  protected $_id;
  protected $_code;
  protected $_name;

  protected function pushPaymentMethod() {
    throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoPayment class!');
  }
  
  protected function pullPaymentMethod() {
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
    $this->pushPaymentMethod();
    if ($this->_code != null) { $msg['paymentMethod']->code = $this->_code; }
    if ($this->_name != null) { $msg['paymentMethod']->name = $this->_name; }

    $result = json_encode($msg['paymentMethod']);

    MnoSoaLogger::debug("result = $result");

    return $result;
  }
  
  protected function persist($mno_entity) {
    MnoSoaLogger::debug("start");
    
    if (!empty($mno_entity->paymentMethod)) {
      $mno_entity = $mno_entity->paymentMethod;
    }
    
    if (!empty($mno_entity->id)) {
      $this->_id = $mno_entity->id;
      $this->set_if_array_key_has_value($this->_code, 'code', $mno_entity);
      $this->set_if_array_key_has_value($this->_name, 'name', $mno_entity);

      MnoSoaLogger::debug("id = " . $this->_id);

      $status = $this->pullPaymentMethod();
      MnoSoaLogger::debug("after pullPaymentMethod");
      
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