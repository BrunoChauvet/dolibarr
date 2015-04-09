<?php
//-----------------------------------------------
// Define root folder and load base
//-----------------------------------------------
if (!defined('MAESTRANO_ROOT')) {
  define("MAESTRANO_ROOT", realpath(dirname(__FILE__) . '/../../'));
}

error_reporting(0);

require_once MAESTRANO_ROOT . '/app/init/base.php';

//-----------------------------------------------
// Require your app specific files here
//-----------------------------------------------
define('DOL_DOCUMENT_ROOT', realpath(MAESTRANO_ROOT . '/../'));
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/conf/conf.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/conf.class.php';
require_once DOL_DOCUMENT_ROOT . '/includes/adodbtime/adodb-time.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/hookmanager.class.php';
include_once DOL_DOCUMENT_ROOT . '/core/class/translate.class.php';
$langs=new Translate("",$conf);
include_once DOL_DOCUMENT_ROOT . '/filefunc.inc.php';
include_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
include_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/modules/facture/mod_facture_terre.php';
require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.product.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/cpays.class.php';

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
$conf->db->character_set                = $dolibarr_main_db_character_set;
$conf->db->dolibarr_main_db_collation   = $dolibarr_main_db_collation;

// Configure Dolibarr Database
$db=getDoliDBInstance($dolibarr_main_db_type,$dolibarr_main_db_host,$dolibarr_main_db_user,$dolibarr_main_db_pass,$dolibarr_main_db_name,$dolibarr_main_db_port);
$opts['db_connection'] = $db;

$conf->setValues($db);

// Dolibar root document
$conf->file->dol_document_root=array(DOL_DOCUMENT_ROOT);

// Dolibarr hookmanager (global)
$hookmanager=new HookManager($db);

//-----------------------------------------------
// Perform your custom preparation code
//-----------------------------------------------
// If you define the $opts variable then it will
// automatically be passed to the MnoSsoUser object
// for construction
