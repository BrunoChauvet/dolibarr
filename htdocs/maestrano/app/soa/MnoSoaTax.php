<?php

/**
 * Mno Tax Class
 */
class MnoSoaTax extends MnoSoaBaseTax {
  protected $_local_entity_name = "TAX";

  protected function pushTax() {
    // Not applicable
  }

  protected function pullTax() {
    MnoSoaLogger::debug("start " . __FUNCTION__ . " for " . json_encode($this->_id));
        
    if (!empty($this->_id)) {
      $local_id = $this->getLocalIdByMnoId($this->_id);
      MnoSoaLogger::debug(__FUNCTION__ . " this->getLocalIdByMnoId(this->_id) = " . json_encode($local_id));
      
      if ($this->isValidIdentifier($local_id)) {
        MnoSoaLogger::debug(__FUNCTION__ . " updating tax rate " . json_encode($local_id));
        $status = constant('MnoSoaBaseEntity::STATUS_EXISTING_ID');
      } else if ($this->isDeletedIdentifier($local_id)) {
        MnoSoaLogger::debug(__FUNCTION__ . " is STATUS_DELETED_ID");
        $status = constant('MnoSoaBaseEntity::STATUS_DELETED_ID');
      } else {
        MnoSoaLogger::debug(__FUNCTION__ . " creating new tax rate " . json_encode($local_id));
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
    $tax_name = $this->pull_set_or_delete_value($this->_name);
    $tax_rate = $this->pull_set_or_delete_value($this->_rate);

    MnoSoaLogger::debug("creating or updating tax $tax_name => $tax_rate with id " . json_encode($local_id));

    if(!isset($tax_rate) || $tax_rate == 0.0) { return null; }

    if($status == constant('MnoSoaBaseEntity::STATUS_NEW_ID')) {
      // Try to find any existing tax rate with same name
      $local_tax = $this->findTaxByLabel($tax_name);
      if(isset($local_tax)) {
        $tax_id = $local_tax['rowid'];
        $this->updateTax($tax_id, $tax_name, $tax_rate);
      } else {
        $this->insertTax($tax_name, $tax_rate);
        $local_tax = $this->findTaxByLabel($tax_name);
        $tax_id = $local_tax['rowid'];
      }

      // Map Tax ID
      $this->addIdMapEntry($tax_id, $this->_id);
    }

    if($status == constant('MnoSoaBaseEntity::STATUS_EXISTING_ID')) {
      // Update tax rate
      if(isset($tax_id)) {
        $this->updateTax($local_id->_id, $tax_name, $tax_rate);
      }
    }
  }

  public function getLocalEntityIdentifier() {
    return $this->_local_entity['taxid'];
  }

  private function findTaxByLabel($tax_label) {
    $tax_details = $this->fetchTaxes();
    foreach ($tax_details as $tax_detail) {
      if($tax_detail['note'] == $tax_label) {
        return $tax_detail;
      }
    }

    return null;
  }

  private function insertTax($tax_name, $tax_rate) {
    $country_id = $this->getCompanyCountryId();

    $sql = "INSERT INTO ".MAIN_DB_PREFIX."c_tva (taux, note, fk_pays, active, recuperableonly, localtax1, localtax1_type, localtax2, localtax2_type) ";
    $sql.= "VALUES ($tax_rate, '$tax_name', $country_id, 1, 0, 0, 0, 0, 0)";

    $this->_db->query($sql);
  }

  private function updateTax($tax_id, $tax_name, $tax_rate) {
    $sql = "UPDATE ".MAIN_DB_PREFIX."c_tva ";
    $sql.= " SET taux=$tax_rate, note='$tax_name' ";
    $sql.= " WHERE rowid=$tax_id";
    $this->_db->query($sql);
  }

  private function fetchTaxes() {
    $sql = "SELECT t.rowid, t.taux, t.note";
    $sql.= " FROM ".MAIN_DB_PREFIX."c_tva as t";
    $sql.= " WHERE t.fk_pays = ".$this->getCompanyCountryId();
    $sql.= " AND t.active = 1";

    $taxes = null;
    $resql = $this->_db->query($sql);
    for($i=0;$tax = $this->_db->fetch_array();$i++) {
      $taxes[$i] = $tax;
    }

    return $taxes;
  }

  private function getCompanyCountryId() {
    // String format: 28:AU:Australia
    $country_string = dolibarr_get_const($this->_db, "MAIN_INFO_SOCIETE_COUNTRY");
    $str_bits = explode(":", $country_string);
    return $str_bits[0];
  }
}

?>