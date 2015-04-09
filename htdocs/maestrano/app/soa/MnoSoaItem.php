<?php

/**
 * Mno Item Class
 */
class MnoSoaItem extends MnoSoaBaseItem {

    protected $_local_entity_name = "ITEMS";
    // PRODUCT OR SERVICE
    public $_local_element_type;
    public $_is_delete;
    public $_is_new;
    
    protected function pushItem() {
        $id = $this->getLocalEntityIdentifier();
        if (empty($id)) { return; }

        $mno_id = $this->getMnoIdByLocalIdName($id, $this->_local_entity_name);
        $this->_id = ($this->isValidIdentifier($mno_id)) ? $mno_id->_id : null;
        $this->_is_new = (empty($this->_id)) ? true : false;

        $this->_code = $this->push_set_or_delete_value($this->_local_entity->ref);
        $this->_name = $this->push_set_or_delete_value($this->_local_entity->libelle);
        $this->_description = $this->push_set_or_delete_value($this->_local_entity->description);
        $this->_status = ($this->_is_delete) ? "INACTIVE" : "ACTIVE";
        
        switch ($this->_local_element_type) {
          case "PRODUCT":
            $this->_type = $this->mapLocalProductNatureToMnoType($this->_local_entity->finished);
            break;
          case "SERVICE":
            $this->_type = "SERVICE";
            break;
        }
        
        // Sale price
        if(!empty($this->_local_entity->price)) {
          $this->_sale->price = $this->push_set_or_delete_value($this->_local_entity->price_ttc);
          $this->_sale->netAmount = $this->push_set_or_delete_value($this->_local_entity->price);
          $this->_sale->taxRate = $this->push_set_or_delete_value($this->_local_entity->tva_tx);
          $this->_sale->taxAmount = $this->_sale->price - $this->_sale->netAmount;
          $this->_sale->currency = $this->push_set_or_delete_value($this->getMainCurrency());
        }

        // Minimum purchase price
        $product_fourn = new ProductFournisseur($this->_db);
        $price_found = $product_fourn->find_min_price_product_fournisseur($id);
        if(isset($product_fourn->fourn_unitprice)) {
          $this->_purchase->netAmount = $product_fourn->fourn_unitprice;
          $this->_purchase->taxRate = $product_fourn->fourn_tva_tx;
        }

        $this->pushTaxes();
    }
    
    protected function pullItem() {
        $return_status = null;
        if (empty($this->_id)) { return constant('MnoSoaBaseEntity::STATUS_ERROR'); }
        
        $mno_type_format = strtoupper($this->pull_set_or_delete_value($this->_type));
        
        switch ($mno_type_format) {
            case "PURCHASED": $this->_local_element_type = "PRODUCT"; break;
            case "MANUFACTURED": $this->_local_element_type = "PRODUCT"; break;
            case "SERVICE": $this->_local_element_type = "SERVICE"; break;
            default: break;
        }
        
        $local_id = $this->getLocalIdByMnoIdName($this->_id, $this->_mno_entity_name);
        if ($this->isDeletedIdentifier($local_id)) { return constant('MnoSoaBaseEntity::STATUS_DELETED_ID'); }
        $mno_status_format = strtoupper($this->pull_set_or_delete_value($this->_status));        
        
        MnoSoaLogger::debug("before local entity");
        $this->_local_entity = new Product($this->_db);
        MnoSoaLogger::debug("after local entity");
        if ($this->isValidIdentifier(($local_id))) {
            MnoSoaLogger::debug("ID is valid");
            $return_status = constant('MnoSoaBaseEntity::STATUS_EXISTING_ID'); 
            $this->_local_entity->fetch($local_id->_id);
            
            if ($mno_status_format == 'INACTIVE') { 
                $this->_local_entity->delete($local_id->_id, false);
                $this->deleteIdMapEntryName($local_id, $this->_local_entity_name);
                return constant('MnoSoaBaseEntity::STATUS_DELETED_ID'); 
            }
        } else {
            MnoSoaLogger::debug("ID is invalid");
            $return_status = constant('MnoSoaBaseEntity::STATUS_NEW_ID');
            if ($mno_status_format == 'INACTIVE') { return constant('MnoSoaBaseEntity::STATUS_DELETED_ID'); }
            $this->_local_entity->note = '';
        }
        
        if (empty($this->_code)) {
            # Generate a random item reference if missing
            $this->_local_entity->ref = 'ITE-' . rand();
        } else {
            $this->_local_entity->ref = $this->pull_set_or_delete_value($this->_code);
        }
        $this->_local_entity->label = $this->_local_entity->libelle = $this->pull_set_or_delete_value($this->_name);
        $this->_local_entity->description = $this->pull_set_or_delete_value($this->_description);
        $this->_local_entity->type = '0';
        if ($this->_local_element_type == "PRODUCT") {
            $this->_local_entity->finished = $this->mapMnoTypeToLocalProductNature($this->_type);
        } else if ($this->_local_element_type == "SERVICE") {
            MnoSoaLogger::debug("is service");
            $this->_local_entity->type = '1';
        }
        
        MnoSoaLogger::debug("main currency=".$this->getMainCurrency());
        if (!empty($this->_sale->currency)) {
            $sale_currency = strtoupper($this->pull_set_or_delete_value($this->_sale->currency));
            MnoSoaLogger::debug("sale currency=".$sale_currency);
            if ($this->getMainCurrency() == $sale_currency) {
                if(isset($this->_sale->netAmount)) {
                  MnoSoaLogger::debug("saving product price excluding tax");
                  $this->_local_entity->price = $this->pull_set_or_delete_value($this->_sale->netAmount, "0");
                  $this->_local_entity->tva_tx = $this->pull_set_or_delete_value($this->_sale->taxRate, "0");
                  $this->_local_entity->price_base_type = 'HT';
                } else {
                  MnoSoaLogger::debug("saving product price including tax");
                  $this->_local_entity->price = $this->pull_set_or_delete_value($this->_sale->price, "0");
                  $this->_local_entity->tva_tx = $this->pull_set_or_delete_value($this->_sale->taxRate, "0");
                  $this->_local_entity->price_base_type = 'TTC';
                }
            } else {
              MnoSoaLogger::info("Product currency does not match main currency, product price will not be saved");
            }
        }
        
        $this->_local_entity->status = (empty($this->_sale)) ? 0 : 1;
        $this->_local_entity->status_buy = (empty($this->_purchase)) ? 0 : 1;
        
        return $return_status;
    }
    
    protected function pushParent()
    {
        // DO NOTHING
    }
    
    protected function pullParent()
    {
        // DO NOTHING
    }
    
    protected function pushSale()
    {   
        // DO NOTHING
    }
    
    protected function pullSale()
    {
        // DO NOTHING
    }
    
    protected function pushPurchase()
    {
        // DO NOTHING
    }
    
    protected function pullPurchase()
    {   
        // DO NOTHING
    }
    
    protected function saveLocalEntity($push_to_maestrano, $status) {
        MnoSoaLogger::debug("start");
        $user = (object) array();
        $user->id = "1";
        $temp = json_encode($this->_local_entity);
        MnoSoaLogger::debug("this->_local_entity={$temp}");
        $id = $this->getLocalEntityIdentifier();
        $newprice = $this->_local_entity->price;
        $newpricebase = $this->_local_entity->price_base_type;
        $newvat = $this->_local_entity->tva_tx;
        
        if ($status == constant('MnoSoaBaseEntity::STATUS_NEW_ID')) {
            $local_id = $this->_local_entity->create($user,true,false);
            $this->_local_entity->updatePrice($local_id, $newprice, $newpricebase, $user, $newvat,'', 0, 0, 0, false);
            if ($local_id > 0) {
                $this->addIdMapEntryName($local_id, $this->_local_entity_name, $this->_id, $this->_mno_entity_name);
            }
        } else if ($status == constant('MnoSoaBaseEntity::STATUS_EXISTING_ID')) {
            $this->_local_entity->update($id, $user, true, 'update', $push_to_maestrano);
            $this->_local_entity->updatePrice($id, $newprice, $newpricebase, $user, $newvat,'', 0, 0, 0, false);
        }
    }
    
    public function getLocalEntityIdentifier() {
        return $this->_local_entity->id;
    }
    
    protected function getMainCurrency() {
        global $conf;
        return $conf->currency;
    }
    
    protected function mapLocalProductNatureToMnoType($local_product_nature) {
        switch ($local_product_nature) {
            case "0": return "PURCHASED";
            case "1": return "MANUFACTURED";
        }
        
        return "PURCHASED";
    }
    
    protected function mapMnoTypeToLocalProductNature($mno_item_type) {
        $mno_item_type_format = $this->pull_set_or_delete_value($mno_item_type);
        
        switch ($mno_item_type_format) {
            case "PURCHASED": return "0";
            case "MANUFACTURED": return "1";
        }
        
        return "0";
    }

    // Dolibarr stores only the tax rate against product, find first tax matching rate
    protected function pushTaxes() {
      global $mysoc;

      if($this->_local_entity->tva_tx != null && $this->_local_entity->tva_tx != 0) {
        $country_taxes = $this->fetchTaxes();

        $this->_taxes = array();
        foreach ($country_taxes as $country_tax) {
          // Find first tax matching rate
          if($country_tax['taux'] == $this->_local_entity->tva_tx) {
            $mno_id = $this->getMnoIdByLocalIdName($country_tax['rowid'], 'TAX');
            if(isset($mno_id)) {
              if(!empty($this->_sale)) { $this->_sale_tax_code = $mno_id->_id; }
              if(!empty($this->_purchase)) { $this->_purchase_tax_code = $mno_id->_id; }
            }
          }
        }
      }
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