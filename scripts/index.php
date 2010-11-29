<?php

/*
   This script sets up and shows the front page with
   the list of all available tasks that the user is
   allowed to view.
*/

if (!defined('IN_FS')) {
    die('Do not access this file directly.');
}

class FlysprayDoIndex extends FlysprayDo
{
    function is_projectlevel() {
        return true;
    }

    function action_remove_notification()
    {
        Backend::remove_notification(Req::val('user_id'), Req::val('ids'));
        return array(SUBMIT_OK, L('notifyremoved'));
    }

    function action_takeownership()
    {
        global $user;

        Backend::assign_to_me($user->id, Req::val('ids'));
        return array(SUBMIT_OK, L('takenownershipmsg'));
    }

    function action_add_notification()
    {
        return FlysprayDoDetails::action_add_notification();
    }

    function action_mass_edit()
    {
        Flyspray::Redirect(CreateUrl('edit', array('ids' => Req::val('ids'))));
    }

    function show($area = null)
    {
	global $page, $fs, $db, $proj, $user, $conf;

        $perpage = '20';
        if (isset($user->infos['tasks_perpage'])) {
            $perpage = $user->infos['tasks_perpage'];
        }

        $pagenum = max(1, Get::num('pagenum', 1));

        $offset = $perpage * ($pagenum - 1);

        // Get the visibility state of all columns
        $visible = explode(' ', trim($proj->id ? $proj->prefs['visible_columns'] : $fs->prefs['visible_columns']));
        if (!is_array($visible) || !count($visible) || !$visible[0]) {
            $visible = array('id');
        }

        list($tasks, $id_list) = Backend::get_task_list($_GET, $visible, $offset, $perpage);

        $page->assign('tasks', $tasks);
        $page->assign('offset', $offset);
        $page->assign('perpage', $perpage);
        $page->assign('pagenum', $pagenum);
        $page->assign('visible', $visible);

        // List of task IDs for next/previous links
        $_SESSION['tasklist'] = $id_list;
        $page->assign('total', count($id_list));

        // Javascript replacement
        if (Get::val('toggleadvanced')) {
            $advanced_search = intval(!Req::val('advancedsearch'));
            Flyspray::setCookie('advancedsearch', $advanced_search, time()+60*60*24*30);
            $_COOKIE['advancedsearch'] = $advanced_search;
        }

        // Update check {{{
        if (Get::has('hideupdatemsg')) {
            unset($_SESSION['latest_version']);
        } else if ($conf['general']['update_check'] && $user->perms('is_admin')
                   && $fs->prefs['last_update_check'] < time()-60*60*24*3) {
            if (!isset($_SESSION['latest_version'])) {
                $latest = Flyspray::remote_request('http://flyspray.org/version.txt', GET_CONTENTS);
                //if for some silly reason we get and empty response, we use the actual version
                $_SESSION['latest_version'] = empty($latest) ? $fs->version : $latest ;
                $db->x->execParam('UPDATE {prefs} SET pref_value = ? WHERE pref_name = ?', array(time(), 'last_update_check'));
            }
        }
        if (isset($_SESSION['latest_version']) && version_compare($fs->version, $_SESSION['latest_version'] , '<') ) {
            $page->assign('updatemsg', true);
        }
        $page->setTitle($fs->prefs['page_title'] . $proj->prefs['project_title'] . ': ' . L('tasklist'));
        $page->pushTpl('index.tpl');
    }

    function _onsubmit()
    {
        $area = Post::val('action');
        return $this->handle('action', $area);
    }

    function is_accessible()
    {
	global $user, $proj;
	if (!$user->can_view_project($proj->id)) {
	    $proj = new Project(0);
	}
	return true;
    }
}


// tpl function that  draws a cell {{{

function tpl_draw_cell($task, $colname, $format = "<td class='%s %s'>%s</td>") {
    global $fs, $proj, $page;

    $indexes = array (
            'id'         => 'task_id',
            'project'    => 'project_title',
            'summary'    => 'item_summary',
            'dateopened' => 'date_opened',
            'openedby'   => 'opened_by_real_name',
            'closedby'   => 'closed_by_real_name',
            'changedby'   => 'last_changed_by_real_name',
            'assignedto' => 'assigned_to_name',
            'lastedit'   => 'last_changed_time',
            'comments'   => 'comment_count',
            'votes'      => 'vote_count',
            'attachments'=> 'attachment_count',
            'dateclosed' => 'date_closed',
            'projectlevelid' => 'prefix_id',
            'progress'   => '',
            'state'      => '',
            'private'    => 'mark_private',
        );

    //must be an array , must contain elements and be alphanumeric (permitted  "_")
    if(!is_array($task) || empty($task) || preg_match('![^A-Za-z0-9_]!', $colname)) {
        //run away..
        return '';
    }

    switch ($colname) {
        case 'id':
            $value = tpl_tasklink($task, $task['task_id']);
            break;
        case 'projectlevelid':
            $value = tpl_tasklink($task, $task['project_prefix'] . '#' . $task['prefix_id']);
            break;
        case 'summary':
            $value = tpl_tasklink($task, utf8_substr($task['item_summary'], 0, 55), false, array(), array('state','age','percent_complete'));
            if (utf8_strlen($task['item_summary']) > 55) {
                $value .= '...';
            }
            break;

        case 'lastedit':
        case 'dateopened':
        case 'dateclosed':
            $value = formatDate($task[$indexes[$colname]]);
            break;

        case 'progress':
            $value = '<div class="taskpercent"><div style="width:'.$task['percent_complete'].'%"> </div></div>';
            break;

        case 'assignedto':
            $value = Filters::noXSS($task[$indexes[$colname]]);
            if ($task['num_assigned'] > 1) {
                $value .= ', +' . ($task['num_assigned'] - 1);
            }
            break;

        case 'state':
            if ($task['is_closed']) {
                $value = L('closed');
            } elseif ($task['closed_by']) {
                $value = L('reopened');
            } else {
                $value = L('open');
            }
            break;
            
        case 'private':
            $value = $task[$indexes[$colname]] ? L('yes') : L('no');
            break;
            
        default:
            if (isset($indexes[$colname])) {
                $value = $task[$indexes[$colname]];
            } elseif (isset($task[$colname . '_name'])) {
                $value = $task[$colname . '_name'];
            } else if (isset($proj->fields[$colname])) {
                $value = $proj->fields[$colname]->view($task, array(), true);
            } else {
                $value = $task[$colname];
            }
            $value = Filters::noXSS($value);
            break;
    }
    $wsvalue = str_replace(' ', '', $value);
    return sprintf($format, 'task_'.$colname, (Filters::isAlnum($wsvalue) ? $colname.'_'.$wsvalue : ''), $value);
}
?>
