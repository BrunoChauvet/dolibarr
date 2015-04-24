<?php

class MnoIdMap {
  public static function addMnoIdMap($local_id, $local_entity_name, $mno_id, $mno_entity_name) {
    global $db;
    $query = "INSERT INTO mno_id_map (mno_entity_guid, mno_entity_name, app_entity_id, app_entity_name, db_timestamp) VALUES ('".$mno_id."','".strtoupper($mno_entity_name)."','".$local_id."','".strtoupper($local_entity_name)."', LOCALTIMESTAMP(0))";
    $result = $db->query($query);
    return $result;
  }

  public static function findMnoIdMapByMnoIdAndEntityName($mno_id, $mno_entity_name, $local_entity_name=null) {
    global $db;

    $query = '';
    if(is_null($local_entity_name)) {
      $query = "SELECT * from mno_id_map WHERE mno_entity_guid = '$mno_id' AND mno_entity_name = '".strtoupper($mno_entity_name)."'";
    } else {
      $query = "SELECT * from mno_id_map WHERE mno_entity_guid = '$mno_id' AND mno_entity_name = '".strtoupper($mno_entity_name)."' AND app_entity_name = '".strtoupper($local_entity_name)."'";
    }
    
    $result = $db->query($query);
    if($result->num_rows > 0) {
      return $result->fetch_assoc();
    }
    return null;
  }

  public static function findMnoIdMapByLocalIdAndEntityName($local_id, $local_entity_name) {
    global $db;
    $result = $db->query("SELECT * from mno_id_map WHERE app_entity_id = '".$local_id."' AND app_entity_name = '".strtoupper($local_entity_name)."'");
    if($result->num_rows > 0) {
      return $result->fetch_assoc();
    }
    return null;
  }

  public static function deleteMnoIdMap($local_id, $local_entity_name) {
    global $db;
    $query = "UPDATE mno_id_map SET deleted_flag = 1 WHERE app_entity_id = '".$local_id."' AND app_entity_name = '".strtoupper($local_entity_name)."'";
    $db->query($query);
  }

  public static function hardDeleteMnoIdMap($local_id, $local_entity_name) {
    global $db;
    $query = "DELETE FROM mno_id_map WHERE app_entity_id = '".$local_id."' AND app_entity_name = '".strtoupper($local_entity_name)."'";
    $db->query($query);
  }

  public static function updateIdMapEntry($current_mno_id, $new_mno_id, $mno_entity_name) {
    global $db;
    $query = "UPDATE mno_id_map SET mno_entity_guid = '".$new_mno_id."' WHERE mno_entity_guid = '".$current_mno_id."' AND mno_entity_name = '".strtoupper($mno_entity_name)."'";
    return $db->query($query);
  }
}