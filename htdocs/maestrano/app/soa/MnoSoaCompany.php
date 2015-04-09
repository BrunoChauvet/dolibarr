<?php

/**
 * Mno Company Class
 */
class MnoSoaCompany extends MnoSoaBaseCompany
{
  protected $_local_entity_name = "company";

  protected function pushCompany() {
    MnoSoaLogger::debug(__FUNCTION__ . " start");

    $this->_name = $this->_local_entity["nom"];
    $this->_currency = $this->_local_entity["currency"];
    $this->_email = $this->_local_entity["mail"];
    $this->_address = $this->_local_entity["address"];
    $this->_postcode = $this->_local_entity["zipcode"];
    $this->_city = $this->_local_entity["town"];
    $this->_website = $this->_local_entity["web"];
    $this->_phone = $this->_local_entity["tel"];

    // Map country
    if(isset($this->_local_entity["country_id"])) {
      $sql = "SELECT libelle as country";
      $sql.= " FROM ".MAIN_DB_PREFIX."c_pays as cou";
      $sql.= " WHERE cou.rowid = ".$this->_local_entity["country_id"];

      $result = $this->_db->query($sql);
      if ($result) {
        $obj = $this->_db->fetch_object($result);
        $this->_country = $obj->country;
      }
    }

    // Map state
    if(isset($this->_local_entity["state_id"])) {
      $sql = "SELECT nom as state";
      $sql.= " FROM ".MAIN_DB_PREFIX."c_departements as dep";
      $sql.= " WHERE dep.rowid = ".$this->_local_entity["state_id"];

      $result = $this->_db->query($sql);
      if ($result) {
        $obj = $this->_db->fetch_object($result);
        $this->_state = $obj->state;
      }
    }

    MnoSoaLogger::debug(__FUNCTION__ . " end");
  }

  protected function pullCompany() {
    MnoSoaLogger::debug(__FUNCTION__ . " start " . $this->_id);

    $this->_local_entity = (object) array();
    $this->_local_entity->name = $this->_name;
    $this->_local_entity->currency = $this->_currency;
    $this->_local_entity->logo = $this->_logo;
    $this->_local_entity->email = $this->_email;
    $this->_local_entity->address = $this->_address;
    $this->_local_entity->postcode = $this->_postcode;
    $this->_local_entity->state = $this->_state;
    $this->_local_entity->city = $this->_city;
    $this->_local_entity->country = $this->_country;
    $this->_local_entity->website = $this->_website;
    $this->_local_entity->phone = $this->_phone;

    MnoSoaLogger::debug(__FUNCTION__ . " end " . $this->_id);
  }

  protected function saveLocalEntity($push_to_maestrano) {
    MnoSoaLogger::debug(__FUNCTION__ . " start " . json_encode($this->_local_entity));

    if(isset($this->_local_entity->name)) { dolibarr_set_const($this->_db, "MAIN_INFO_SOCIETE_NOM", $this->_local_entity->name); }
    if(isset($this->_local_entity->currency)) { dolibarr_set_const($this->_db, "MAIN_MONNAIE", $this->_local_entity->currency); }

    // Save logo
    $this->saveLogo();

    // Map country
    if(isset($this->_local_entity->country)) {
      // Map country name and code
      $country_code = $this->mapCountryToISO3166($this->_local_entity->country);
      $country_name = $this->mapISO3166ToCountry($this->_local_entity->country);
      if(!isset($country_code)) {
        $country_code = $this->_local_entity->country;
      }
      if(!isset($country_name)) {
        $country_name = $this->_local_entity->country;
      }
      
      $cpays = new Cpays($this->_db);
      $cpays->fetch(false, $country_code);
      $s = $cpays->id.':'.$country_code.':'.$country_name;
      dolibarr_set_const($this->_db, "MAIN_INFO_SOCIETE_COUNTRY", $s);

      // Map state
      if (isset($this->_local_entity->state)) {
        $sql = "SELECT rowid as state_id";
        $sql.= " FROM ".MAIN_DB_PREFIX."c_departements as dep";
        $sql.= " JOIN ".MAIN_DB_PREFIX."llx_c_regions as reg ON (reg.rowid = dep.fk_region)";
        $sql.= " WHERE reg.fk_pays = ".$cpays->id;
        $sql.= " AND (dep.code_departement = '" .$this->_local_entity->state."' OR dep.nom = '" .$this->_local_entity->state."')";

        $result = $this->_db->query($sql);
        if ($result) {
          $obj = $this->_db->fetch_object($result);
          dolibarr_set_const($this->_db, "MAIN_INFO_SOCIETE_STATE", $obj->rowid);
        }
      }
    }
    
    // Map address
    if(isset($this->_local_entity->address)) { dolibarr_set_const($this->_db, "MAIN_INFO_SOCIETE_ADDRESS", $this->_local_entity->address); }
    if(isset($this->_local_entity->city)) { dolibarr_set_const($this->_db, "MAIN_INFO_SOCIETE_TOWN", $this->_local_entity->city); }
    if(isset($this->_local_entity->postcode)) { dolibarr_set_const($this->_db, "MAIN_INFO_SOCIETE_ZIP", $this->_local_entity->postcode); }
    if(isset($this->_local_entity->phone)) { dolibarr_set_const($this->_db, "MAIN_INFO_SOCIETE_TEL", $this->_local_entity->phone); }
    if(isset($this->_local_entity->email)) { dolibarr_set_const($this->_db, "MAIN_INFO_SOCIETE_MAIL", $this->_local_entity->email); }
    if(isset($this->_local_entity->website)) { dolibarr_set_const($this->_db, "MAIN_INFO_SOCIETE_WEB", $this->_local_entity->website); }

    MnoSoaLogger::debug(__FUNCTION__ . " end ");
  }

  protected function saveLogo() {
    global $maxwidthsmall;
    global $maxheightsmall;
    global $maxwidthmini;
    global $maxheightmini;
    global $quality;
    global $conf;

    if(isset($this->_local_entity->logo->logo)) {
      // Save logo file locally
      $filename = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 10) . '.jpg';
      $dir = $conf->mycompany->dir_output . '/logos/';
      if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
      }
      $tmpLogoFilePath = $dir . $filename;
      MnoSoaLogger::debug("saving company logo " . $tmpLogoFilePath);
      file_put_contents($tmpLogoFilePath, file_get_contents($this->_local_entity->logo->logo));

      dolibarr_set_const($this->_db, "MAIN_INFO_SOCIETE_LOGO", $filename);
      $imgThumbSmall = vignette($tmpLogoFilePath, $maxwidthsmall, $maxheightsmall, '_small', $quality);
      if (preg_match('/([^\\/:]+)$/i',$imgThumbSmall,$reg)) {
          $imgThumbSmall = $reg[1];
          dolibarr_set_const($this->_db, "MAIN_INFO_SOCIETE_LOGO_SMALL",$imgThumbSmall);
      } else dol_syslog($imgThumbSmall);

      $imgThumbMini = vignette($tmpLogoFilePath, $maxwidthmini, $maxheightmini, '_mini', $quality);
      if (preg_match('/([^\\/:]+)$/i',$imgThumbMini,$reg)) {
          $imgThumbMini = $reg[1];
          dolibarr_set_const($this->_db, "MAIN_INFO_SOCIETE_LOGO_MINI",$imgThumbMini);
      } else dol_syslog($imgThumbMini);
    }
  }

  public function getLocalEntityIdentifier() {
    return 0;
  }

}

?>
