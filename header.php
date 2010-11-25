<?php

require dirname(__FILE__) . '/includes/fix.inc.php';
require 'class.flyspray.php';
require 'constants.inc.php';
require 'i18n.inc.php';

// Get the translation for the wrapper page (this page)
setlocale(LC_ALL, str_replace('-', '_', L('locale')) . '.utf8');

// If it is empty,take the user to the setup page
if (!$conf) {
    Flyspray::Redirect('setup/index.php');
}

require 'class.gpc.php';
require 'utf8.inc.php';
require 'class.database.php';
require 'class.backend.php';
require 'class.project.php';
require 'class.user.php';
require 'class.tpl.php';
require 'class.do.php';
require_once 'authplugins/class.flysprayauth.php';

$db = NewDatabase($conf['database']);
$fs = new Flyspray;

// If version number of database and files do not match, run upgrader
if (Flyspray::base_version($fs->version) != Flyspray::base_version($fs->prefs['fs_ver'])) {
    Flyspray::Redirect('setup/upgrade.php');
}

if (is_readable(BASEDIR . '/setup/index.php') && strpos($fs->version, 'dev') === false) {
    die('Please empty the folder "' . BASEDIR . DIRECTORY_SEPARATOR . "setup\"  before you start using Flyspray.\n".
        "If you are upgrading, please go to the setup directory and launch upgrade.php");
}

// Get available do-modes and include the classes
$modes = str_replace('.php', '', array_map('basename', glob_compat(BASEDIR ."/scripts/*.php")));
// yes, we need all of them for now
foreach ($modes as $mode) {
    require_once(BASEDIR . '/scripts/' . $mode . '.php');
}
$do = Req::val('do');

// Any "do" mode that accepts a task_id or id field should be added here.
if (Req::num('task_id')) {
    $project_id = $db->x->GetOne('SELECT  project_id
                                 FROM  {tasks}
                                WHERE task_id = ?', null,
                               Req::num('task_id'));
    $do = Filters::enum($do, array('details', 'depends', 'editcomment'));
} else {
    if ($do == 'admin' && Get::has('switch') && Get::val('project') != '0') {
        $do = 'pm';
    } elseif ($do == 'pm' && Get::has('switch') && Get::val('project') == '0') {
        $do = 'admin';
    } elseif (Get::has('switch') && ($do == 'details')) {
        $do = 'index';
    }

    if ($do && class_exists('FlysprayDo' . ucfirst($do)) &&
        !call_user_func(array('FlysprayDo' . ucfirst($do), 'is_projectlevel'))) {
        $project_id = 0;
    }
}

if (!isset($project_id)) {
    // Determine which project we want to see
    if (($project_id = Cookie::val('flyspray_project')) == '') {
        $project_id = $fs->prefs['default_project'];
    }
    $project_id = Req::val('project', Req::val('project_id', $project_id));
}

$proj = new Project($project_id);
// reset do for default project level entry page
if (!in_array($do, $modes)) {
    $do = ($do) ? Req::enum('do', $modes, $proj->prefs['default_entry']) : $proj->prefs['default_entry'];
}

$proj->setCookie();
$user = new User($uid = 0);

// verify and initiate user
$auth = new FlysprayAuth();

if (Post::val('user_name') && Post::has('password')) {
    $uid = $auth->checkLogin(Post::val('user_name'), Post::val('password'));
    if (is_array($uid)) {
        FlysprayDo::error($uid);
    }
} else if (Cookie::val('flyspray_userid') && $auth->checkCookie(Cookie::val('flyspray_userid'), Cookie::val('flyspray_passhash'))) {
    $uid = Cookie::val('flyspray_userid');
}
$user = new User($uid);

// Load translations
load_translations();

function debuglog($str)
{
    $file = fopen("debug.log", "a+");
    fwrite($file, $str."\n");
    fflush($file);
    fclose($file);
}

?>
