<?php

/**
 * Mno InvoiceLine Interface
 */
class MnoSoaBaseInvoiceLine extends MnoSoaBaseEntity
{
  protected $_mno_entity_name = "invoice_line";

  // Overwrite method to hard delete mapping of invoice lines
  public function sendDeleteNotification($local_id) {
    MnoSoaLogger::debug(__FUNCTION__ .  " (MnoSoaBaseInvoiceLine) start local_id = " . $local_id);
    $mno_id =  $this->getMnoIdByLocalId($local_id);

    if ($this->isValidIdentifier($mno_id)) {
        MnoSoaLogger::debug(__FUNCTION__ . " corresponding mno_id = " . $mno_id->_id);
        
        if ($this->_enable_delete_notifications) {
            $response = $this->callMaestrano($this->_delete_http_operation, $this->_delete_rest_entity_name . '/' . $mno_id->_id);
            if (empty($response)) { 
                return false; 
            }
        }
        
        $this->_mno_soa_db_interface->hardDeleteIdMapEntry($local_id, 'INVOICE_LINE');
        MnoSoaLogger::debug(__FUNCTION__ .  " after hard deleting ID entry");
    }
    
    return true;
  }
}

?>