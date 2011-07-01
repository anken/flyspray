<?php
/*
 *  Run me once for every project that uses SVN
 */

# set the timezone
date_default_timezone_set('Europe/Berlin');

set_time_limit(0);

define('IN_FS', true);

require_once '../../header.php';

if (!Get::val('project_id')) {
    die('No project ID specified (use ?project_id=X).');
}

if (!$proj->prefs['svn_url']) {
    die('No URL to SVN repository entered in PM area.');
}

$project_prefixes = $db->x->GetCol('SELECT project_prefix FROM {projects}');
$look = array('FS#', 'bug ');
foreach ($project_prefixes as $prefix) {
    $look[] = preg_quote($prefix . '#', '/');
}
$look = implode('|', $look);

echo '<h2>'. $proj->prefs['project_title'] .'</h2>';

// use backward-compatible column name
$cols = $db->x->getRow('SELECT * FROM {related}');
$col = isset($cols['is_duplicate']) ? 'is_duplicate' : 'related_type';

$revisions = $db->x->GetCol('SELECT topic FROM {cache} WHERE project_id = ? AND type = ?', null, $proj->id, 'svn');

$svninfo = new SVNinfo();
$svninfo->setRepository($proj->prefs['svn_url'], $proj->prefs['svn_user'], $proj->prefs['svn_password']);

$currentRevision = $svninfo->getCurrentRevision();

// retrieve stuff in small portions

$stmt = $db->x->autoPrepare('{cache}', array('type', 'content', 'topic', 'project_id', 'last_updated'));

for ($i = 1; $i <= $currentRevision; $i += 50) {
    echo sprintf('<p>Importing revisions %d to %d...', $i, $i + 49); flush();

    $logsvn = $svninfo->getLog($i, $i + 49);
    foreach ($logsvn as $log) {
        if (in_array($log['version-name'], $revisions)) {
            continue;
        }
        // fill related revisions
        preg_replace_callback("/\b(" . $look . ")(\d+)\b/", 'add_related', $log['comment']);

        $stmt->execute(array('svn', serialize($log), $log['version-name'], $proj->id, strtotime($log['date'])));
    }
}

$stmt->free();

function add_related($arr)
{
    global $log, $col, $db;
    static $imported = array();

    $task = $arr[1] . $arr[2];
    list($prefix, $task) = explode( (strpos($task, '#') !== false) ? '#' : ' ', $task);
    $task = Flyspray::GetTaskDetails($task, true, $prefix);

    if (!$task['task_id'] || isset($imported[$task['task_id'] . '-' . $log['version-name']])) {
        return;
    }

    echo sprintf('<p>&nbsp;&nbsp;&nbsp;Adding task %d for revision %d</p>', $task['task_id'], $log['version-name']); flush();

    $imported[$task['task_id'] . '-' . $log['version-name']] = true;
    $db->x->execParam("INSERT INTO {related} (this_task, related_task, {$col}) VALUES (?,?,?)",
                  array($task['task_id'], $log['version-name'], RELATED_SVN));
}
?>
