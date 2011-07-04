<?php
// This is just a developer's tool. Needs tables with flyspray_ prefix.

error_reporting(E_ALL);
define('IN_FS', true);
$file = 'exportdb.xml';

require_once dirname(__FILE__) . '/../includes/fix.inc.php';
require_once dirname(__FILE__) . '/../includes/class.database.php';
 
$conf    = @parse_ini_file('../flyspray.conf.php', true) or die('Cannot open config file.');
 
define('DEBUG_SQL', true);
$db = NewDatabase($conf['database']);

require_once dirname(__FILE__) . '/../includes/external/MDB2/Schema.php';
 
// Now build schema object based on existing connection
$db->setOption('idxname_format', '%s');
$schema =& MDB2_Schema::factory($db);
$def = $schema->getDefinitionFromDatabase();
$schema->dumpDatabase($def, array('output_mode' => 'file', 'output' => $file), MDB2_SCHEMA_DUMP_ALL);

// Now make prefix a variable, so that it can be replaced during setup or upgrade
$xml = file_get_contents($file);
$xml = str_replace('flyspray_', '<variable>db_prefix</variable>', $xml);
// workaround for quotes bug
$xml = str_replace('&quot;', '"', $xml);
// empty default values might cause problems
$xml = str_replace("<notnull>true</notnull>\n    <default></default>", '<notnull>true</notnull>', $xml);
// also make database name variable
$xml = str_replace('<name>'.$conf['database']['dbname'].'</name>', '<name><variable>db_name</variable></name>', $xml);
file_put_contents($file, $xml);

?>