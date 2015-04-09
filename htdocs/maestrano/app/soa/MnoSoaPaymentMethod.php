<?php

/**
 * Mno PaymentMethod Class
 */
class MnoSoaPaymentMethod extends MnoSoaBasePaymentMethod {
  protected $_local_entity_name = "PAYMENT_METHOD";

  protected function pushPaymentMethod() {
    // Not applicable
  }

  protected function pullPaymentMethod() {
    MnoSoaLogger::debug("start " . __FUNCTION__ . " for " . json_encode($this->_id));
        
    if (!empty($this->_id)) {
      $local_id = $this->getLocalIdByMnoId($this->_id);
      MnoSoaLogger::debug(__FUNCTION__ . " this->getLocalIdByMnoId(this->_id) = " . json_encode($local_id));
      
      if ($this->isValidIdentifier($local_id)) {
        MnoSoaLogger::debug(__FUNCTION__ . " updating payment method " . json_encode($local_id));
        $status = constant('MnoSoaBaseEntity::STATUS_EXISTING_ID');
      } else if ($this->isDeletedIdentifier($local_id)) {
        MnoSoaLogger::debug(__FUNCTION__ . " is STATUS_DELETED_ID");
        $status = constant('MnoSoaBaseEntity::STATUS_DELETED_ID');
      } else {
        MnoSoaLogger::debug(__FUNCTION__ . " creating payment method rate " . json_encode($local_id));
        $status = constant('MnoSoaBaseEntity::STATUS_NEW_ID');
      }
    } else {
      $status = constant('MnoSoaBaseEntity::STATUS_ERROR');
    }

    return $status;
  }

  protected function saveLocalEntity($push_to_maestrano, $status) {
    MnoSoaLogger::debug("start saveLocalEntity status=$status");

    $local_id = $this->getLocalIdByMnoId($this->_id);
    $payment_method_code = $this->mapLocalPaymentMethodCode($this->pull_set_or_delete_value($this->_code));
    $payment_method_name = $this->pull_set_or_delete_value($this->_name);
    MnoSoaLogger::debug("creating or updating payment method $payment_method_code $payment_method_name with id " . json_encode($local_id));

    if($status == constant('MnoSoaBaseEntity::STATUS_NEW_ID')) {
      // Only insert new payment methods as we do not want to overwritte predefined ones
      $local_payment_method = $this->findPaymentMethodByCodeOrName($payment_method_code, $payment_method_name);
      if(!isset($local_payment_method)) {
        $this->insertPaymentMethod($payment_method_code, $payment_method_name);
        $local_payment_method = $this->findPaymentMethodByCodeOrName($payment_method_code, $payment_method_name);
        $payment_method_id = $local_payment_method['id'];
      } else {
        $payment_method_id = $local_payment_method['id'];
      }

      // Map PaymentMethod ID
      $this->addIdMapEntry($payment_method_id, $this->_id);
    }
  }

  public function getLocalEntityIdentifier() {
    return $this->_local_entity['id'];
  }

  private function findPaymentMethodByCodeOrName($payment_method_code, $payment_method_name) {
    $payment_methods = $this->fetchPaymentMethods();
    foreach ($payment_methods as $payment_method) {
      if(($payment_method['code'] == $payment_method_code && $payment_method_code != '') || ($payment_method['libelle'] == $payment_method_name && $payment_method_name != '')) {
        return $payment_method;
      }
    }

    return null;
  }

  private function insertPaymentMethod($payment_method_code, $payment_method_name) {
    $newid=0;
    $sql = "SELECT max(id) + 1 as id FROM ".MAIN_DB_PREFIX."c_paiement";
    $result = $this->_db->query($sql);
    if ($result) {
      $obj = $this->_db->fetch_object($result);
      $newid = $obj->id;
    }

    if($newid > 0) {
      $sql = "INSERT INTO ".MAIN_DB_PREFIX."c_paiement (id, code, libelle) ";
      $sql.= "VALUES ($newid, '$payment_method_code', '$payment_method_name')";
      MnoSoaLogger::debug("insert new payment method " . $sql);
      $this->_db->query($sql);
    }
  }

  private function updatePaymentMethod($payment_method_id, $payment_method_code, $payment_method_name) {
    $sql = "UPDATE ".MAIN_DB_PREFIX."c_paiement ";
    $sql.= " SET code='$payment_method_code', libelle='$payment_method_name' ";
    $sql.= " WHERE id=$payment_method_id";
    MnoSoaLogger::debug("update payment method " . $sql);
    $this->_db->query($sql);
  }

  private function fetchPaymentMethods() {
    global $langs;

    $sql = "SELECT pm.id, pm.code, pm.libelle FROM ".MAIN_DB_PREFIX."c_paiement as pm";
    $resql = $this->_db->query($sql);
    
    $payment_methods = null;
    for($i=0;$payment_method = $this->_db->fetch_array();$i++) {
      $payment_methods[$i] = $payment_method;
    }
    return $payment_methods;
  }

  private function mapMnoPaymentMethodCode($payment_method_code) {
    $mapping = array (
      'TIP' => 'TIP',
      'VIR' => 'DEP',
      'PRE' => 'ORD',
      'LIQ' => 'CASH',
      'CB'  => 'CC',
      'CHQ' => 'CHECK',
      'VAD' => 'VAD',
      'TRA' => 'TRA',
      'LCR' => 'LCR',
      'FAC' => 'FAC',
      'PRO' => 'PRO'
    );

    $code = $mapping[$payment_method_code];
    if(isset($code)) {
      return $code;
    } else {
      return $payment_method_code;
    }
  }

  private function mapLocalPaymentMethodCode($payment_method_code) {
    $mapping = array (
      'TIP'   => 'TIP',
      'DEP'   => 'VIR',
      'ORD'   => 'PRE',
      'CASH'  => 'LIQ',
      'CC'    => 'CB',
      'CHECK' => 'CHQ',
      'VAD'   => 'VAD',
      'TRA'   => 'TRA',
      'LCR'   => 'LCR',
      'FAC'   => 'FAC',
      'PRO'   => 'PRO'
    );

    $code = $mapping[$payment_method_code];
    if(isset($code)) {
      return $code;
    } else {
      return $payment_method_code;
    }
  }
}

?>
