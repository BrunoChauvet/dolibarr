<?php

/**
 * Configure App specific behavior for 
 * Maestrano SSO
 */
class MnoSsoUser extends MnoSsoBaseUser
{
  /**
   * Database connection
   * @var PDO
   */
  public $connection = null;
  
  /**
   * The dolibarr configuration object
   * @var string
   */
	//private $_app_conf = null;
	
  /**
   * Internal Dolibarr user object
   * @var PDO
   */
	private $_user = null;
	
  /**
   * The dolibarr table prefix
   * @var string
   */
	private $_db_tbl_prefix = 'llx_';
	
  /**
   * Extend constructor to inialize app specific objects
   *
   * @param OneLogin_Saml_Response $saml_response
   *   A SamlResponse object from Maestrano containing details
   *   about the user being authenticated
   */
  public function __construct(OneLogin_Saml_Response $saml_response, &$session = array(), $opts = array())
  {
    // Call Parent
    parent::__construct($saml_response,$session);
    
    // Assign new attributes
    $this->connection = $opts['db_connection'];
		$this->_app_conf = $opts['app_conf'];
		
		if ($opts['db_table_prefix']) {
			$this->_db_tbl_prefix = $opts['db_table_prefix'];
		}
		
		// Assign global variable $conf (used internally by dolibarr)
		$conf = $opts['app_conf'];
  }
  
  
  /**
   * Sign the user in the application. 
   * Parent method deals with putting the mno_uid, 
   * mno_session and mno_session_recheck in session.
   *
   * @return boolean whether the user was successfully set in session or not
   */
  protected function setInSession()
  {
    // First set $conn variable (need global variable?)
    $this->session["dol_login"] = $this->uid;
    $this->session["dol_authmode"]='';
    $this->session["dol_tz"]='';
    $this->session["dol_tz_string"]='';
    $this->session["dol_dst"]='';
    $this->session["dol_dst_observed"]='';
    $this->session["dol_dst_first"]='';
    $this->session["dol_dst_second"]='';
    $this->session["dol_screenwidth"]='';
    $this->session["dol_screenheight"]='';
    $this->session["dol_company"]='';
    $this->session["dol_entity"]=1;
		
		// Used by extensions like cashdesk
		$this->session['uid'] = $this->local_id;
		$this->session['uname'] = $this->local_id;
		$this->session['lastname'] = $this->surname;
		$this->session['firstname'] = $this->name;
		
		return true;
  }
  
  
  /**
   * Used by createLocalUserOrDenyAccess to create a local user 
   * based on the sso user.
   * If the method returns null then access is denied
   *
   * @return the ID of the user created, null otherwise
   */
  protected function createLocalUser()
  {
    $lid = null;
    
    if ($this->accessScope() == 'private') {
			// Build the local user first
			$this->buildLocalUser();
			
			// Save the user and capture the id
			$this->connection->begin();
      $lid = $this->_user->create(null);
			if ($lid > 0) {
        $this->_user->setPassword(null,$this->generatePassword());
        if($this->_user->admin) { $this->assignAllRights(); }
      }
			$this->connection->commit();
    }
    
    return $lid;
  }
  
  /**
   * Build the _user object (Dolibarr user)
   *
   * @return User Dolibarr user object
   */
  protected function buildLocalUser()
  {
		 $u = new User($this->connection);
     $u->lastname		  = $this->surname;
     $u->firstname	  = $this->name;
     $u->login		    = $this->uid;
     $u->admin		    = $this->isAdmin();
     $u->office_phone	= "";
     $u->office_fax	  = "";
     $u->user_mobile	= "";
     $u->email		    = $this->email;
     $u->job			    = "";
     $u->signature	  = "";
     $u->note			    = "";
     $u->ldap_sid		  = "";
		 $u->entity       = 1;
		 
		 $this->_user = $u;
		 
		 return $this->_user;
  }

  protected function assignAllRights() {
    // Delete all assigned rights
    $sql = "DELETE FROM ".MAIN_DB_PREFIX."user_rights WHERE fk_user = " . $this->_user->id;
    $result = $this->connection->query($sql);

    // Assign all available rights
    $sql = "SELECT id FROM ".MAIN_DB_PREFIX."rights_def";
    $resql = $this->connection->query($sql);
    if($resql) {
      $num = $this->connection->num_rows($resql);
      $i = 0;
      while ($i < $num) {
        $row = $this->connection->fetch_row($resql);
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."user_rights (fk_user, fk_id) VALUES (".$this->_user->id.", ".$row[0].")";
        $result = $this->connection->query($sql);

        $i++;
      }
      $this->connection->free($resql);
    }
  }
  
  /**
   * Check wether a user should be set admin in
	 * dolibarr or not
   *
   * @return boolean true if admin false otherwise
   */
	protected function isAdmin()
	{
    $admin = false;
    
    if ($this->app_owner) {
      $admin = true;
    } else {
      foreach ($this->organizations as $organization) {
        if ($organization['role'] == 'Admin' || $organization['role'] == 'Super Admin') {
          $admin = true;
        } else {
          $admin = false;
        }
      }
    }
    
    return $admin;
	}
	
  /**
   * Get the ID of a local user via Maestrano UID lookup
   *
   * @return a user ID if found, null otherwise
   */
  protected function getLocalIdByUid()
  {
    $sql = "SELECT rowid FROM " . $this->_db_tbl_prefix . "user WHERE mno_uid = '{$this->connection->escape($this->uid)}' LIMIT 1";
		$result = $this->connection->query($sql);
		if ($result) $result = $this->connection->fetch_object($result);
		
    if ($result && $result->rowid) {
      return $result->rowid;
    }
    
    return null;
  }
  
  /**
   * Get the ID of a local user via email lookup
   *
   * @return a user ID if found, null otherwise
   */
  protected function getLocalIdByEmail()
  {
    $sql = "SELECT rowid FROM " . $this->_db_tbl_prefix . "user WHERE email = '{$this->connection->escape($this->email)}' LIMIT 1";
		$result = $this->connection->query($sql);
		if ($result) $result = $this->connection->fetch_object($result);
		
    if ($result && $result->rowid) {
      return $result->rowid;
    }
    
    return null;
  }
  
  /**
   * Set all 'soft' details on the user (like name, surname, email)
   * Implementing this method is optional.
   *
   * @return boolean whether the user was synced or not
   */
   protected function syncLocalDetails()
   {
		 if($this->local_id) {
			 
			 // Prepare sql statement
			 $sql = "UPDATE " . $this->_db_tbl_prefix . "user SET
			 	 login = '{$this->connection->escape($this->uid)}',
				 firstname = '{$this->connection->escape($this->name)}',
			 	 lastname = '{$this->connection->escape($this->surname)}',
		     email = '{$this->connection->escape($this->email)}'
				 WHERE rowid = $this->local_id";
       
			 // Execute
			 $this->connection->begin();
			 $upd = $this->connection->query($sql);
			 $this->connection->commit();
			 
			 return $upd;
     }
     
     return false;
   }
  
  /**
   * Set the Maestrano UID on a local user via id lookup
   *
   * @return a user ID if found, null otherwise
   */
  protected function setLocalUid()
  {
    if($this->local_id) {
			// Prepare statement
			$sql = "UPDATE " . $this->_db_tbl_prefix . "user SET mno_uid = '{$this->connection->escape($this->uid)}' WHERE rowid = $this->local_id";
      
			// Execute
			$this->connection->begin();
			$upd = $this->connection->query($sql);
			$this->connection->commit();
			
      return $upd;
    }
    
    return false;
  }
}