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
        MnoSoaLogger::debug("addIdMapEntry query = ".$query);
	$result = $this->_db->query($query);
	MnoSoaLogger::debug("after insert");
	
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
        MnoSoaLogger::debug("getMnoIdByLocalIdName query = ".$query);
  $result = $this->_db->query($query);
  // Return id value
  $row = $this->_db->fetch_object($result);
  if ($row) {
      MnoSoaLogger::debug("mno_id_map ".json_encode($row));
            $mno_entity_guid = trim($row->mno_entity_guid);
            $mno_entity_name = trim($row->mno_entity_name);
            $deleted_flag = trim($row->deleted_flag);
            
            if (!empty($mno_entity_guid) && !empty($mno_entity_name)) {
                $mno_entity = (object) array (
                    "_id" => $mno_entity_guid,
                    "_entity" => $mno_entity_name,
                    "_deleted_flag" => $deleted_flag
                );
            }
	}
        
        MnoSoaLogger::debug("returning mno_entity = ".json_encode($mno_entity));
	
	return $mno_entity;
    }
    
    public function getLocalIdByMnoIdName($mnoId, $mnoEntityName)
    {
      	$local_entity = null;
              
      	// Fetch record
      	$query = "SELECT app_entity_id, app_entity_name, deleted_flag from mno_id_map where mno_entity_guid='". $this->_db->escape($mnoId) ."' and mno_entity_name='". $this->_db->escape(strtoupper($mnoEntityName)) . "'";
        MnoSoaLogger::debug("getLocalIdByMnoIdName query = ".$query);
      	$result = $this->_db->query($query);
        if (!$result) {
            die('Invalid query: ' . mysql_error());
        }

        $row = $this->_db->fetch_object($result);

      	// Return id value
      	if ($row) {
            MnoSoaLogger::debug("fetched object " . json_encode($row));
            
            $app_entity_id = trim($row->app_entity_id);
            $app_entity_name = trim($row->app_entity_name);
            $deleted_flag = trim($row->deleted_flag);
            
            if (!empty($app_entity_id) && !empty($app_entity_name)) {
            
                $local_entity = (object) array (
                    "_id" => $app_entity_id,
                    "_entity" => $app_entity_name,
                    "_deleted_flag" => $deleted_flag
                );
            }
      	}
	      mysql_free_result($result);
        MnoSoaLogger::debug("returning local_entity = ".json_encode($local_entity));
        
	      return $local_entity;
    }
    
    public function deleteIdMapEntry($localId, $localEntityName) {
        // Logically delete record
        $query = "UPDATE mno_id_map SET deleted_flag=1 WHERE app_entity_id='".$this->_db->escape($localId)."' and app_entity_name='".$this->_db->escape(strtoupper($localEntityName)) . "'";
        MnoSoaLogger::debug("deleteIdMapEntry query = ".$query);
        $result = $this->_db->query($query);
        MnoSoaLogger::debug("result = ".json_encode($result));
        
        if ($result) {
            return true;
        }
        
        return false;
    }

    public function hardDeleteIdMapEntry($localId, $localEntityName) {
      MnoSoaLogger::debug(__CLASS__ . ' ' . __FUNCTION__ . " start");
        // Hard delete record
      $query = "DELETE FROM mno_id_map WHERE app_entity_id='" . $localId . "' and app_entity_name='" . strtoupper($localEntityName) . "'";
      $result = $this->_db->query($query);

      if ($result) {
        MnoSoaLogger::debug(__CLASS__ . ' ' . __FUNCTION__ . " return true");
        return true;
      } else {
        MnoSoaLogger::debug(__CLASS__ . ' ' . __FUNCTION__ . " return false");
        return false;
      }
    }
}
