<?php

  /*************************************************************\
  | Edits or closes multiple tasks at once                      |
  | ~~~~~~~~~~~~~~~~~~~~~~~~~~~~                                |
  \*************************************************************/

if (!defined('IN_FS')) {
    die('Do not access this file directly.');
}

require_once(BASEDIR . '/includes/events.inc.php');

class FlysprayDoEdit extends FlysprayDo
{
    // **********************
    // Begin all action_ functions
    // **********************

    function action_edit()
    {
        foreach (Get::val('ids') as $task_id) {
            // Edit or close? If we have a resolution_reason, then close! otherwise, edit.
            if (Post::val('resolution_reason')) {
                Backend::close_task($task_id, Post::val('resolution_reason'), Post::val('closure_comment'), Post::val('mark100'));
            } elseif (count(Post::val('changes'))) {
                $task = Flyspray::GetTaskDetails($task_id);
                $args = $task; // import previous values
                foreach (Post::val('changes') as $change) {
                    $args[$change] = Post::val($change);
                }

                if (is_array($args['assigned_to'])) {
                    $args['assigned_to'] = implode(';', $task['assigned_to_uname']);
                }

                Backend::edit_task($task, $args);
            }
        }

        return array(SUBMIT_OK, L('masseditsuccessful'));
    }

    // **********************
    // End of all action_ functions
    // **********************

    function is_accessible()
    {
        global $user;
        return !$user->isAnon();
    }

	function _onsubmit()
	{
        global $proj;
        // only meant for global fields...
        if (!count(Get::val('ids', array()))) {
            return array(ERROR_RECOVER, L('notasksselected'), CreateUrl('index'));
        }
        $proj = new Project(0);
        $return = $this->handle('action', Req::val('action'));
        $proj = new Project(0);
        return $return;
	}

    function show()
    {
        global $page, $user, $fs, $proj, $db;
        $page->setTitle($fs->prefs['page_title'] . $proj->prefs['project_title'] . ': ' . L('massedit'));
        $page->assign('userlist', array());
        $page->pushTpl('massedit.tpl');
    }
}

?>
