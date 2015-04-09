<?php

/**
 * Maestrano map table functions
 *
 * @author root
 */

class MnoSoaEntity extends MnoSoaBaseEntity {    
    public function getUpdates($timestamp) {
        MnoSoaLogger::info("start (timestamp=$timestamp)");
        $msg = $this->callMaestrano("GET", "updates" . '/' . $timestamp);
        if (empty($msg)) { return false; }
        
        $this->updateEntity($msg, "MnoSoaCompany", "companys");
        $this->updateEntity($msg, "MnoSoaTax", "taxCodes");
        $this->updateEntity($msg, "MnoSoaPaymentMethod", "paymentMethods");
        $this->updateEntity($msg, "MnoSoaOrganization", "organizations");
        $this->updateEntity($msg, "MnoSoaPersonContact", "persons");
        $this->updateEntity($msg, "MnoSoaItem", "items");
        $this->updateEntity($msg, "MnoSoaAccount", "accounts");
        $this->updateEntity($msg, "MnoSoaInvoice", "invoices");
        $this->updateEntity($msg, "MnoSoaPayment", "payments");
        
        MnoSoaLogger::info( "successfully completed (timestamp=$timestamp)");
    }
    
    public function updateEntity($msg, $class_name, $mno_element_name) {
        if (empty($msg->{$mno_element_name}) || !class_exists($class_name)) { return; }
        MnoSoaLogger::debug("processing entities $class_name");
        foreach ($msg->{$mno_element_name} as $x) {
            MnoSoaLogger::debug($mno_element_name . " updating id=" . $x->id);
            try {
                $mno_x = new $class_name($this->_db);
                $mno_x->receive($x);
            } catch (Exception $ex) {
                MnoSoaLogger::error("id={$x->id} exception={$ex->getMessage()}");
            }
        }
    }
}
