<?php
//-----------------------------------------------
// Define root folder and load base
//-----------------------------------------------
if (!defined('MAESTRANO_ROOT')) {
  define("MAESTRANO_ROOT", realpath(dirname(__FILE__) . '/../../'));
}
require_once MAESTRANO_ROOT . '/app/init/base.php';

//-----------------------------------------------
// Require your app specific files here
//-----------------------------------------------
define('DOL_DOCUMENT_ROOT', realpath(MAESTRANO_ROOT . '/../'));
require_once DOL_DOCUMENT_ROOT . '/conf/conf.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/conf.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/security.lib.php';
require_once DOL_DOCUMENT_ROOT . '/includes/adodbtime/adodb-time.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/hookmanager.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';

//-----------------------------------------------
// Perform your custom preparation code
//-----------------------------------------------
// If you define the $opts variable then it will
// automatically be passed to the MnoSsoUser object
// for construction
// e.g:
$opts = array();

// Define DOL_URL_ROOT (no url prefix on mno)
define('DOL_URL_ROOT', '');
define('MAIN_DB_PREFIX', $dolibarr_main_db_prefix);


// Dolibarr configuration (global)
$conf = new Conf();

// DB configuration
$conf->db->host							= $dolibarr_main_db_host;
$conf->db->port							= $dolibarr_main_db_port;
$conf->db->name							= $dolibarr_main_db_name;
$conf->db->user							= $dolibarr_main_db_user;
$conf->db->pass							= $dolibarr_main_db_pass;
$conf->db->type							= $dolibarr_main_db_type;
$conf->db->prefix						= $dolibarr_main_db_prefix;
$conf->db->character_set				= $dolibarr_main_db_character_set;
$conf->db->dolibarr_main_db_collation	= $dolibarr_main_db_collation;

// Set properties specific to conf file
$conf->file->mailing_limit_sendbyweb	= $dolibarr_mailing_limit_sendbyweb;
$conf->file->main_authentication		= empty($dolibarr_main_authentication)?'':$dolibarr_main_authentication;	// Identification mode
$conf->file->main_force_https			= empty($dolibarr_main_force_https)?'':$dolibarr_main_force_https;			// Force https
$conf->file->strict_mode 				= empty($dolibarr_strict_mode)?'':$dolibarr_strict_mode;					// Force php strict mode (for debug)
$conf->file->cookie_cryptkey			= empty($dolibarr_main_cookie_cryptkey)?'':$dolibarr_main_cookie_cryptkey;	// Cookie cryptkey
$conf->file->dol_document_root			= array('main' => DOL_DOCUMENT_ROOT);										// Define array of document root directories
$opts['app_conf'] = $conf;

// Configure Dolibarr Database
$db=getDoliDBInstance($dolibarr_main_db_type,$dolibarr_main_db_host,$dolibarr_main_db_user,$dolibarr_main_db_pass,$dolibarr_main_db_name,$dolibarr_main_db_port);
$opts['db_connection'] = $db;
//echo var_dump($db);

// Define the database prefix
if (empty($dolibarr_main_db_prefix)) $dolibarr_main_db_prefix='llx_';
$opts['db_table_prefix'] = $dolibarr_main_db_prefix;

// Dolibarr hookmanager (global)
$hookmanager=new HookManager($db);

// Set the dolibarr session name
$session_name='DOLSESSID_' . dol_getprefix();
session_name($session_name);


