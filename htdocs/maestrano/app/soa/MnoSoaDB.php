<?php

/**
 * Maestrano map table functions
 *
 * @author root
 */

class MnoSoaDB extends MnoSoaBaseDB {    
    /**
    * Update identifier map table
    * @param  	string 	local_id                Local entity identifier
    * @param    string  local_entity_name       Local entity name
    * @param	string	mno_id                  Maestrano entity identifier
    * @param	string	mno_entity_name         Maestrano entity name
    *
    * @return 	boolean Record inserted
    */
            
    public function addIdMapEntry($local_id, $local_entity_name, $mno_id, $mno_entity_name) {	
	// Fetch record
	$query = "INSERT INTO mno_id_map (mno_entity_guid, mno_entity_name, app_entity_id, app_entity_name, db_timestamp) VALUES ('".$this->_db->escape($mno_id)."','".$this->_db->escape(strtoupper($mno_entity_name))."','".$this->_db->escape($local_id)."','".$this->_db->escape(strtoupper($local_entity_name))."',UTC_TIMESTAMP)";	
        $this->_log->debug("addIdMapEntry query = ".$query);
	$result = $this->_db->query($query);
	$this->_log->debug("after insert");
	
	if ($result) {
	    return true;
	}
	
	return false;
    }
    
    /**
    * Get Maestrano GUID when provided with a local identifier
    * @param  	string 	local_id                Local entity identifier
    * @param    string  local_entity_name       Local entity name
    *
    * @return 	boolean Record found	
    */
    
    public function getMnoIdByLocalIdName($localId, $localEntityName)
    {
        $mno_entity = null;
        
	// Fetch record
	$query = "SELECT mno_entity_guid, mno_entity_name, deleted_flag from mno_id_map where app_entity_id='" . $this->_db->escape($localId) . "' and app_entity_name='" . $this->_db->escape(strtoupper($localEntityName)) . "'";
        $this->_log->debug("getMnoIdByLocalIdName query = ".$query);
	$result = $this->_db->query($query);
        $this->_log->debug("after fetch");
	
	// Return id value
	if (!empty($result->num_rows)) {
            $obj = $this->_db->fetch_object($result);
            
            $mno_entity_guid = trim($obj->mno_entity_guid);
            $mno_entity_name = trim($obj->mno_entity_name);
            $deleted_flag = trim($obj->deleted_flag);
            
            if (!empty($mno_entity_guid) && !empty($mno_entity_name)) {
                $mno_entity = (object) array (
                    "_id" => $mno_entity_guid,
                    "_entity" => $mno_entity_name,
                    "_deleted_flag" => $deleted_flag
                );
            }
	}
        
        $this->_log->debug("returning mno_entity = ".json_encode($mno_entity));
	
	return $mno_entity;
    }
    
    public function getLocalIdByMnoIdName($mnoId, $mnoEntityName)
    {
	$local_entity = null;
        
	// Fetch record
	$query = "SELECT app_entity_id, app_entity_name, deleted_flag from mno_id_map where mno_entity_guid='". $this->_db->escape($mnoId) ."' and mno_entity_name='". $this->_db->escape(strtoupper($mnoEntityName)) . "'";
        $this->_log->debug("getLocalIdByMnoIdName query = ".$query);
	$result = $this->_db->query($query);
        $this->_log->debug("after fetch");
	
	// Return id value
	if (!empty($result->num_rows)) {           
            $obj = $this->_db->fetch_object($result);
            
            $app_entity_id = trim($obj->app_entity_id);
            $app_entity_name = trim($obj->app_entity_name);
            $deleted_flag = trim($obj->deleted_flag);
            
            if (!empty($app_entity_id) && !empty($app_entity_name)) {
            
                $local_entity = (object) array (
                    "_id" => $app_entity_id,
                    "_entity" => $app_entity_name,
                    "_deleted_flag" => $deleted_flag
                );
            }
	}
	
        $this->_log->debug("returning local_entity = ".json_encode($local_entity));
        
	return $local_entity;
    }
    
    public function deleteIdMapEntry($localId, $localEntityName) 
    {
        // Logically delete record
        $query = "UPDATE mno_id_map SET deleted_flag=1 WHERE app_entity_id='".$this->_db->escape($localId)."' and app_entity_name='".$this->_db->escape(strtoupper($localEntityName)) . "'";
        $this->_log->debug("deleteIdMapEntry query = ".$query);
        $result = $this->_db->query($query);
        $this->_log->debug("result = ".json_encode($result));
        
        if ($result) {
            return true;
        }
        
        return false;
    }
}
