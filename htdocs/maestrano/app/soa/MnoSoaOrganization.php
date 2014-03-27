<?php

/**
 * Mno Organization Class
 */
class MnoSoaOrganization extends MnoSoaBaseOrganization
{
    protected $_local_entity_name = "societe";
    
    // DONE
    protected function pushId() 
    {
        $this->_log->debug(__FUNCTION__ . " start");
	$id = $this->_local_entity->id;
	
	if (!empty($id)) {
	    $this->_log->debug(__FUNCTION__ . " this->_local_entity->id = " . json_encode($id));
	    $mno_id = $this->getMnoIdByLocalId($id);
            
	    if ($this->isValidIdentifier($mno_id)) {
                $this->_log->debug(__FUNCTION__ . " this->getMnoIdByLocalId(id) = " . json_encode($mno_id));
		$this->_id = $mno_id->_id;
	    }
	}
        
        $this->_log->debug(__FUNCTION__ . " end");
    }
    
    // DONE
    protected function pullId() 
    {
        $this->_log->debug(__FUNCTION__ . " start " . $this->_id);
        
	if (!empty($this->_id)) {            
	    $local_id = $this->getLocalIdByMnoId($this->_id);
            $this->_log->debug(__FUNCTION__ . " this->getLocalIdByMnoId(this->_id) = " . json_encode($local_id));
            
	    if ($this->isValidIdentifier($local_id)) {
                $this->_log->debug(__FUNCTION__ . " is STATUS_EXISTING_ID");
                $this->_local_entity = new Societe($this->_db);
                $this->_local_entity->fetch($local_id->_id);
		return constant('MnoSoaBaseEntity::STATUS_EXISTING_ID');
	    } else if ($this->isDeletedIdentifier($local_id)) {
                $this->_log->debug(__FUNCTION__ . " is STATUS_DELETED_ID");
                return constant('MnoSoaBaseEntity::STATUS_DELETED_ID');
            } else {
                $this->_log->debug(__FUNCTION__ . " is STATUS_NEW_ID");
		$this->_local_entity = new Societe($this->_db);
		return constant('MnoSoaBaseEntity::STATUS_NEW_ID');
	    }
	}
        
        $this->_log->debug(__FUNCTION__ . " return STATUS_ERROR");
        return constant('MnoSoaBaseEntity::STATUS_ERROR');
    }
    
    // DONE
    protected function pushName() 
    {
        $this->_log->debug(__FUNCTION__ . " local_entity = " . json_encode($this->_local_entity));
        $this->_log->debug(__FUNCTION__ . " start " . $this->_local_entity->name);
        $this->_name = $this->push_set_or_delete_value($this->_local_entity->name);
	$this->_log->debug(__FUNCTION__ . " end " . $this->_name);
    }
    
    // DONE
    protected function pullName() 
    {
        $this->_log->debug(__FUNCTION__ . " start " . $this->_name);
        $this->_local_entity->name = $this->pull_set_or_delete_value($this->_name);        
        $this->_log->debug(__FUNCTION__ . " end " . $this->_local_entity->name);
    }
    
    // DONE
    protected function pushIndustry() {
	// DO NOTHING
    }
    
    // DONE
    protected function pullIndustry() {
	// DO NOTHING
    }
    
    // DONE
    protected function pushAnnualRevenue() {
	// DO NOTHING
    }
    
    // DONE
    protected function pullAnnualRevenue() {
	// DO NOTHING
    }
    
    // DONE
    protected function pushCapital() {
        $this->_log->debug(__FUNCTION__ . " start ");
        $capital = $this->getNumeric((string) $this->_local_entity->capital);
        $this->_capital = $this->push_set_or_delete_value($capital, 0);
        $this->_log->debug(__FUNCTION__ . " end ");
    }
    
    // DONE
    protected function pullCapital() {
        $this->_log->debug(__FUNCTION__ . " start ");
        $this->_local_entity->capital = $this->pull_set_or_delete_value($this->_capital, "");
        $this->_log->debug(__FUNCTION__ . " end ");
    }
    
    // DONE
    protected function pushNumberOfEmployees() {
	// DO NOTHING
    }
    
    // DONE
    protected function pullNumberOfEmployees() {
        $this->_log->debug(__FUNCTION__ . " start ");
        
        $no = $this->pull_set_or_delete_value($this->_number_of_employees, 0);
        $this->_local_entity->effectif_id = $this->pull_set_or_delete_value($this->mapNumberOfEmployeesToEnumeration($no), 0);
        
        $this->_log->debug(__FUNCTION__ . " end ");
    }
    
    // DONE
    protected function pushAddresses() {
        $this->_log->debug(__FUNCTION__ . " start ");
        // POSTAL ADDRESS
        $this->_address->postalAddress->postalCode = $this->push_set_or_delete_value($this->_local_entity->zip, "");
        $this->_address->postalAddress->locality = $this->push_set_or_delete_value($this->_local_entity->town, "");
        if (!empty($this->_local_entity->state_id)) {
            $state = getState($this->_local_entity->state_id,0,$this->_db);
            if (!empty($state) && array_key_exists('label', $state)) {
                $this->_address->postalAddress->region = $this->push_set_or_delete_value($state['label'], "");
            } else {
                $this->log->error(__FUNCTION__ . " failed to lookup state " . $this->_local_entity->state_id);
            }
        }
        
        if (empty($this->_local_entity->country_id)) {
            $this->_address->postalAddress->country = "";
        } else {
            $country_code = getCountry($this->_local_entity->country_id,2,$this->_db);
            if (!empty($country_code) && $country_code != 'NotDefined' && $country_code != 'Error' && strlen($country_code) == 2) {
                $this->_address->postalAddress->country = $this->push_set_or_delete_value(strtoupper($country_code));
            } else {
                $this->log->error(__FUNCTION__ . " failed to lookup country " . $this->_address->postalAddress->country);
            }
            
        }
        $this->_log->debug(__FUNCTION__ . " end ");
    }
    
    // DONE
    protected function pullAddresses() {
        $this->_log->debug(__FUNCTION__ . " start ");
	// POSTAL ADDRESS
        $this->_local_entity->address = $this->pull_set_or_delete_value($this->_address->postalAddress->streetAddress, "");
        $this->_local_entity->town = $this->pull_set_or_delete_value($this->_address->postalAddress->locality, "");
        $this->_local_entity->zip = $this->pull_set_or_delete_value($this->_address->postalAddress->postalCode, "");
        if ($this->_address->postalAddress->country != null) {
            if (empty($this->_address->postalAddress->country)) {
                $this->_local_entity->country_id = null;
            } else {
                $country_code = getCountry($this->_address->postalAddress->country,3,$this->_db);
                if (!empty($country_code) && $country_code != 'NotDefined' && $country_code != 'Error') {
                    $this->_local_entity->country_id = $country_code;
                } else {
                    $this->log->error(__FUNCTION__ . " failed to lookup country " . $this->_address->postalAddress->country);
                }
            }
        } else {
            $this->_local_entity->country_id = null;
        }
        
        $this->_log->debug(__FUNCTION__ . " end ");
    }
    
    // DONE
    protected function pushEmails() {
        $this->_log->debug(__FUNCTION__ . " start ");
        $this->_email->emailAddress = $this->push_set_or_delete_value($this->_local_entity->email, "");
        $this->_log->debug(__FUNCTION__ . " end ");
    }
    
    // DONE
    protected function pullEmails() {
        $this->_log->debug(__FUNCTION__ . " start ");
        $this->_local_entity->email = $this->pull_set_or_delete_value($this->_email->emailAddress, "");
        $this->_log->debug(__FUNCTION__ . " end ");
    }
    
    // DONE
    protected function pushTelephones() {
        $this->_log->debug(__FUNCTION__ . " start ");
        $this->_telephone->voice = $this->push_set_or_delete_value($this->_local_entity->phone, "");
        $this->_telephone->fax = $this->push_set_or_delete_value($this->_local_entity->fax, "");
        $this->_log->debug(__FUNCTION__ . " end ");
    }
    
    // DONE
    protected function pullTelephones() {
        $this->_log->debug(__FUNCTION__ . " start ");
        $this->_local_entity->phone = $this->pull_set_or_delete_value($this->_telephone->voice, "");
        $this->_local_entity->fax = $this->pull_set_or_delete_value($this->_telephone->fax, "");
        $this->_log->debug(__FUNCTION__ . " end ");
    }
    
    // DONE
    protected function pushWebsites() {
        $this->_log->debug(__FUNCTION__ . " start ");
        $this->_website->url = $this->push_set_or_delete_value($this->_local_entity->url, "");
        $this->_log->debug(__FUNCTION__ . " end ");
    }
    
    // DONE
    protected function pullWebsites() {
        $this->_log->debug(__FUNCTION__ . " start ");
        $this->_local_entity->url = $this->pull_set_or_delete_value($this->_website->url, "");
        $this->_log->debug(__FUNCTION__ . " end ");
    }
    
    // DONE
    protected function pushEntity() {
        $this->_log->debug(__FUNCTION__ . " start ");
        if (!empty($this->_local_entity->client)) {
            switch($this->_local_entity->client) {
                case 0: $this->_entity->customer = false; $this->_entity->lead = false; break;
                case 1: $this->_entity->customer = true; break;
                case 2: $this->_entity->lead = true; break;
                case 3: $this->_entity->customer = true; $this->_entity->lead = true; break;
            }
	} else {
            $this->_entity->customer = false; 
            $this->_entity->lead = false;
        }
        
        if (!empty($this->_local_entity->fournisseur)) {
            switch($this->_local_entity->fournisseur) {
                case 0: $this->_entity->supplier = false; break;
                case 1: $this->_entity->supplier = true; break;
            }
        } else {
            $this->_entity->supplier = false;
        }
        
        $this->_log->debug(__FUNCTION__ . " end ");
    }
    
    // DONE
    protected function pullEntity() {
        $this->_log->debug(__FUNCTION__ . " start ");
        if (!empty($this->_entity->customer) && !empty($this->_entity->lead)) {
            $this->_local_entity->client = 3;
        } else if (!empty($this->_entity->customer)) {
            $this->_local_entity->client = 1;
        } else if (!empty($this->_entity->lead)) {
            $this->_local_entity->client = 2;
        } else {
            $this->_local_entity->client = 0;
        }
        
        if (!empty($this->_entity->supplier)) {
            $this->_local_entity->fournisseur = 1;
        } else {
            $this->_local_entity->fournisseur = 0;
        }
        $this->_log->debug(__FUNCTION__ . " end ");
    }
    
    // DONE
    protected function saveLocalEntity($push_to_maestrano, $status) {
        $this->_log->debug(__FUNCTION__ . " start ");
        // status = 2 update status = 1 new
        if ($status == constant('MnoSoaBaseEntity::STATUS_NEW_ID')) {
            $this->_log->debug(json_encode($this->_local_entity));
            $this->_local_entity->create('', $push_to_maestrano);
            $this->_log->debug(__FUNCTION__ . " create ");
        } else if ($status == constant('MnoSoaBaseEntity::STATUS_EXISTING_ID')) {
            $this->_log->debug(__FUNCTION__ . " local entity = " . json_encode($this->_local_entity));
            $this->_local_entity->update($this->_local_entity->id, '', 1, 0, 0, 'update', 1, $push_to_maestrano);
            $this->_log->debug(__FUNCTION__ . " update ");
        }
        $this->_log->debug(__FUNCTION__ . " end ");
    }
    
    // DONE
    public function getLocalEntityIdentifier() {
        return $this->_local_entity->id;
    }

    // DONE
    protected function mapNumberOfEmployeesToEnumeration($no) {
        $no = intval($no);
        
        if ($no >= 1 && $no <= 5) { return 1; }
        if ($no >= 6 && $no <= 10) { return 2; }
        if ($no >= 11 && $no <= 50) { return 3; }
        if ($no >= 51 && $no <= 100) { return 4; }
        if ($no >= 100 && $no <= 500) { return 5; }
        if ($no > 500) { return 6; }
        return null;
    }
}

?>