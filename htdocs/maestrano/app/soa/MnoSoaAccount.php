<?php

/**
 * Mno Account Class
 */
class MnoSoaAccount extends MnoSoaBaseAccount
{
    protected $_local_entity_name = "ACCOUNT";
    public $_is_delete;
    
    protected function pushAccount()
    {
        // PUSH ID
        $id = $this->getLocalEntityIdentifier();
        if (empty($id)) { return; }
        $mno_id = $this->getMnoIdByLocalIdName($id, $this->_local_entity_name);
        $this->_id = ($this->isValidIdentifier($mno_id)) ? $mno_id->_id : null;
        // PUSH CODE
        $this->_code = $this->push_set_or_delete_value($this->_local_entity->ref);
        // PUSH NAME
        $this->_name = $this->push_set_or_delete_value($this->_local_entity->label);
        // PUSH DESCRIPTION
        $this->_description = $this->push_set_or_delete_value($this->_local_entity->comment);
        switch ($this->_local_entity->courant) {
            case "0": $this->_classification="ASSET_BANK_SAVINGS"; break;
            case "1": $this->_classification="LIABILITY_CREDITCARD_CREDITCARD"; break;
            case "2": $this->_classification="ASSET_BANK_CASHONHAND"; break;
            default: break;
        }

        $this->_currency = $this->push_set_or_delete_value($this->_local_entity->currency_code);
        $this->_status = ($this->_is_delete) ? "INACTIVE" : "ACTIVE";

        $this->_currency = $this->push_set_or_delete_value($this->_local_entity->currency_code);

        // Push account details
        $this->_bank_name = $this->push_set_or_delete_value($this->_local_entity->bank);
        $this->_bank_code = $this->push_set_or_delete_value($this->_local_entity->code_banque);
        $this->_bank_account = $this->push_set_or_delete_value($this->_local_entity->number);
        $this->_iban = $this->push_set_or_delete_value($this->_local_entity->iban_prefix);
        $this->_bic = $this->push_set_or_delete_value($this->_local_entity->bic);
    }
    
    protected function pullAccount()
    {
        // PULL SALE
        if (empty($this->_id)) { return constant('MnoSoaBaseEntity::STATUS_ERROR'); }
        
        $local_id = $this->getLocalIdByMnoIdName($this->_id, $this->_mno_entity_name);
        if ($this->isDeletedIdentifier($local_id)) { return constant('MnoSoaBaseEntity::STATUS_DELETED_ID'); }
        $mno_status_format = strtoupper($this->pull_set_or_delete_value($this->_status));
        
        $this->_local_entity = new Account($this->_db);
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
            $this->_local_entity->country_id = '11';
            $this->_local_entity->date_solde = date("y-m-d");
        }
        
        if (empty($this->_code)) {
            # Generate a random account reference if missing
            $this->_local_entity->ref = 'ACC-' . rand();
        } else {
            $this->_local_entity->ref = $this->pull_set_or_delete_value($this->_code);
        }

        $this->_local_entity->label = $this->pull_set_or_delete_value($this->_name);
        $this->_local_entity->comment = $this->pull_set_or_delete_value($this->_description);
        $this->_local_entity->solde = $this->pull_set_or_delete_value($this->_opening_balance);
        $this->_local_entity->currency_code = $this->pull_set_or_delete_value($this->_currency);
        
        $mno_classification = strtoupper($this->pull_set_or_delete_value($this->_classification));
        
        if ($mno_classification == "ASSET_BANK_SAVINGS") { $local_courant = "0"; }
        else if ($mno_classification == "LIABILITY_CREDITCARD_CREDITCARD") { $local_courant = "1"; }
        else if ($mno_classification == "ASSET_BANK_CASHONHAND") { $local_courant = "2"; }
        else { $local_courant = "0"; }
        // Default to SAVING account otherwise account sare ignored
        $this->_local_entity->courant = $local_courant;

        $this->_local_entity->currency_code = $this->pull_set_or_delete_value($this->_currency);

        // Account details
        $this->_local_entity->bank = $this->pull_set_or_delete_value($this->_bank_name);
        $this->_local_entity->code_banque = $this->pull_set_or_delete_value($this->_bank_code);
        $this->_local_entity->number = $this->pull_set_or_delete_value($this->_bank_account);
        $this->_local_entity->iban = $this->pull_set_or_delete_value($this->_iban);
        $this->_local_entity->bic = $this->pull_set_or_delete_value($this->_bic);

        return $return_status;
    }
    
    protected function saveLocalEntity($push_to_maestrano, $status) {
        MnoSoaLogger::debug("start status=$status");
        
        $user = (object) array();
        $user->id = "1";
        
        if ($status == constant('MnoSoaBaseEntity::STATUS_NEW_ID')) {
            $local_id = $this->_local_entity->create(false);
            if ($local_id > 0) {
                $this->addIdMapEntryName($local_id, $this->_local_entity_name, $this->_id, $this->_mno_entity_name);
            }
            $this->_local_entity->update_bban($user, false);
        } else if ($status == constant('MnoSoaBaseEntity::STATUS_EXISTING_ID')) {
            $this->_local_entity->update($user, false);
            $this->_local_entity->update_bban($user, false);
        }
    }
    
    public function getLocalEntityIdentifier() {
        return $this->_local_entity->id;
    }
}

?>