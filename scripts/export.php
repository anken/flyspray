<?php
  /*********************************************************\
  | Export the tasklist                                     |
  | ~~~~~~~~~~~~~~~~~~~                                     |
  \*********************************************************/

if (!defined('IN_FS')) {
    die('Do not access this file directly.');
}

// Get the visibility state of all columns
$visible = explode(' ', trim($proj->id ? $proj->prefs['visible_columns'] : $fs->prefs['visible_columns']));

list($tasks, $id_list) = Backend::get_task_list($_GET, $visible, 0);

// Data for a cell
function tpl_csv_cell($task, $colname) {
    global $fs, $proj;

    $indexes = array (
            'id'         => 'task_id',
            'project'    => 'project_title',
            'tasktype'   => 'task_type',
            'category'   => 'category_name',
            'severity'   => '',
            'priority'   => '',
            'summary'    => 'item_summary',
            'dateopened' => 'date_opened',
            'status'     => 'status_name',
            'openedby'   => 'opened_by_name',
            'assignedto' => 'assigned_to_name',
            'lastedit'   => 'event_date',
            'reportedin' => 'product_version',
            'dueversion' => 'closedby_version',
            'duedate'    => 'due_date',
            'comments'   => 'num_comments',
            'votes'      => 'num_votes',
            'attachments'=> 'num_attachments',
            'dateclosed' => 'date_closed',
            'progress'   => '',
            'os'         => 'os_name',
    );

    switch ($colname) {
        case 'id':
            $value = $task['task_id'];
            break;
        case 'summary':
            $value = $task['item_summary'];
            break;

        case 'severity':
            $value = $fs->severities[$task['task_severity']];
            break;

        case 'priority':
            $value = $fs->priorities[$task['task_priority']];
            break;

        case 'duedate':
        case 'dateopened':
        case 'dateclosed':
        case 'lastedit':
            $value = formatDate($task[$indexes[$colname]]);
            break;

        case 'status':
            if ($task['is_closed']) {
                $value = L('closed');
            } else {
                $value = $task[$indexes[$colname]];
            }
            break;

        case 'progress':
            $value = $task['percent_complete'] . '%';
            break;

        case 'assignedto':
            $value = $task[$indexes[$colname]];
            if ($task['num_assigned'] > 1) {
                $value .= ', +' . ($task['num_assigned'] - 1);
            }
            break;
            
        default:
            $value = $task[$indexes[$colname]];
            break;
    }

    return str_replace(array(';', '"'), array('\;', '\"'), $value);
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: filename="export.csv"');
$page = new FSTpl;
$page->uses('tasks', 'visible');
$page->display('csvexport.tpl');
exit(); // no footer please
?>