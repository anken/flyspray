<?php

class Project
{
    var $id = 0;
    var $prefs = array();
    var $fields = array();

    /**
     * List of all columns, needed in templates
     * @access public
     * @var array
     */
    var $columns = array('id', 'project', 'summary', 'dateopened', 'openedby', 'closedby', 'changedby',
                         'assignedto', 'lastedit', 'comments', 'attachments', 'progress',
                         'dateclosed', 'votes', 'state', 'projectlevelid', 'private');

    function Project($id)
    {
        global $db, $fs;

        // Get custom fields
        $fields = $db->x->getAll('SELECT f.*, l.list_type
                                           FROM {fields} f
                                      LEFT JOIN {lists} l ON f.list_id = l.list_id
                                          WHERE f.project_id IN (0, ?) ORDER BY field_name',
                                          null, array($id));
        foreach ($fields as $field) {
            $f = new Field($field);
            if ($f->id == $fs->prefs['color_field']) {
                $f->values = $this->get_list($f->prefs, $f->id);
            }
            $this->fields['field' . $field['field_id']] = $f;
        }

        $this->columns = array_combine($this->columns, array_map('L', $this->columns));
        foreach ($this->fields as $field) {
            $this->columns['field' . $field->id] = $field->prefs['field_name'];
        }

        if (is_numeric($id) && $id > 0) {
            $this->prefs = $db->x->getRow(
                              "SELECT p.*, c.content AS pm_instructions, c.last_updated AS cache_update
                                 FROM {projects} p
                            LEFT JOIN {cache} c ON c.topic = p.project_id AND c.type = 'msg'
                                WHERE p.project_id = ?", null, array($id));
            if (is_array($this->prefs)) {
                $this->id    = (int) $this->prefs['project_id'];
                $this->prefs['visible_columns'] = implode(' ', array_intersect(explode(' ', $this->prefs['visible_columns']), array_keys($this->columns)));
                $this->prefs['theme_style'] = Filters::enum($this->prefs['theme_style'], Flyspray::listThemes());
                return;
            }
        }

        $this->id = 0;
        $this->prefs = array();
        $this->prefs['project_title'] = L('allprojects');
        $this->prefs['feed_description']  = L('feedforall');
        $this->prefs['theme_style']   = $fs->prefs['global_theme'];
        $this->prefs['theme_style'] = Filters::enum($this->prefs['theme_style'], Flyspray::listThemes());
        $this->prefs['lang_code']   = $fs->prefs['lang_code'];
        $this->prefs['others_view'] = 1;
        $this->prefs['intro_message'] = '';
        $this->prefs['anon_open'] = $this->prefs['override_user_lang'] = 0;
        $this->prefs['feed_img_url'] = '';
        $this->prefs['default_entry'] = $fs->prefs['default_entry'];
        $this->prefs['notify_reply'] = '';
        $fs->prefs['visible_columns'] = implode(' ', array_intersect(explode(' ', $fs->prefs['visible_columns']), array_keys($this->columns)));

        return;
    }

    function setCookie()
    {
        Flyspray::setCookie('flyspray_project', $this->id);
    }

    /* cached list functions {{{ */

    // helpers {{{

    function get_edit_list($list_id)
    {
        global $db;

        //Get the column names of list tables for the group by statement
        $groupby = GetColumnNames('{list_items}',  'lb.list_item_id', 'lb');

        return $db->x->getAll('SELECT lb.*, count(fv.task_id) AS used_in_tasks
                             FROM {list_items} lb
                        LEFT JOIN {fields} f ON f.list_id = lb.list_id
                        LEFT JOIN {field_values} fv ON (fv.field_id = f.field_id AND field_value = lb.list_item_id)
                            WHERE lb.list_id = ?
                         GROUP BY ' . $groupby . '
                         ORDER BY list_position',
                          null, $list_id);
    }

    /**
     * _list_sql
     *
     * @param mixed $type
     * @param mixed $where
     * @access protected
     * @return string
     * @notes The $where parameter is dangerous, think twice what you pass there..
     */

    function _list_sql($type, $where = null)
    {
        // sanity check.
        if (!is_numeric($type)) {
            return '';
        }

        return "SELECT  list_item_id, item_name
                  FROM  {list_items}
                 WHERE  show_in_list = 1 AND list_id = '{$type}'
                        $where
              ORDER BY  list_position";
    }

    // }}}
    // PM dependant functions {{{

    function get_list($field_prefs, $selected = null)
    {
        global $db;
        $default = array('list_type' => LIST_BASIC, 'version_tense' => null, 'value_required' => 1);
        $field = array_merge($default, $field_prefs);
        $params =  array();
        $where = '';
        $required = (!$field['value_required']) ? array(array('list_item_id' => '0', 'item_name' => L('notspecified'))) : array();

        if ($field['list_type'] == LIST_CATEGORY) {
            return array_merge($required, $this->listCategories($field['list_id']));
        } else if ($field['list_type'] == LIST_VERSION) {
            if ($field['version_tense'] > 0) {
                if ($field['version_tense'] <= 3) {
                    $tense = intval($field['version_tense']);
                } else {
                    $tenses[4] = '1,2';
                    $tenses[5] = '2,3';
                    $tenses[6] = '1,3';
                    $tense = $tenses[$field['version_tense']];
                }
                $where = 'AND version_tense IN (' . $tense . ')';
            }

            if (!is_null($selected)) {
                $where .= ' OR list_item_id = ?';
                $params[] = $selected;
            }
        }

        $result = $db->x->getAll($this->_list_sql($field['list_id'], $where), null, $params);
        return array_merge($required, $result);
    }
    
    function createRootCategory($listid)
    {
        global $db;
        
        $db->x->execParam('INSERT INTO {list_category} (lft, rgt, category_name, list_id) VALUES (1,2,"root",?)', array($listid));
    }

    function listCategories($id, $hide_hidden = true, $remove_root = true, $depth = true)
    {
        global $db;

        // start with a empty arrays
        $right = array();
        $cats = array();

        // retrieve the left and right value of the root node
        //debuglog("SELECT lft, rgt FROM {list_category} WHERE category_name='root' AND lft=1 AND list_id=".$id);
        $rootsql = "SELECT lft, rgt FROM {list_category} WHERE category_name='root' AND lft=1 AND list_id=?";
        
        $row = $db->x->getRow($rootsql, null, array($id));
        
        // IF we FAILED :( create a new one, because something bad has happened.
        if($row == 0)
        {
            debuglog("Something bad has happened to list number $id, and it's root entry has gone missing! Attempting to recover.");
            $this->createRootCategory($id);
            $row = $db->x->getRow($rootsql, null, array($id)); // try again!
            if($row == 0)
            {
                trigger_error("class.project.php: listCategories: unable to find root category node! Unable to create a new one! BAD!");
            }
        }

        $groupby = GetColumnNames('{list_category}', 'c.category_id', 'c');

        //debuglog("SELECT c.category_id, c.category_name, c.* FROM {list_category} c WHERE list_id=$id AND lft BETWEEN $row[lft] AND $row[rgt] GROUP BY $groupby ORDER BY lft ASC");
        // now, retrieve all descendants of the root node
        $result = $db->x->getAll('SELECT c.category_id, c.category_name, c.*
                                FROM {list_category} c
                               WHERE list_id = ? AND lft BETWEEN ? AND ?
                            GROUP BY ' . $groupby . '
                            ORDER BY lft ASC', null,
                             array($id, intval($row['lft']), intval($row['rgt'])));

        foreach ($result as $row) {
            if ($hide_hidden && !$row['show_in_list'] && $row['lft'] != 1) {
                continue;
            }

           // check if we should remove a node from the stack
           while (count($right) > 0 && $right[count($right)-1] < $row['rgt']) {
               array_pop($right);
           }
           $cats[] = $row + array('depth' => count($right)-1);

           // add this node to the stack
           $right[] = $row['rgt'];
        }

        // Adjust output for select boxes
        if ($depth) {
            foreach ($cats as $key => $cat) {
                if ($cat['depth'] > 0) {
                    $cats[$key]['category_name'] = str_repeat('...', $cat['depth']) . $cat['category_name'];
                }
            }
        }

        if ($remove_root) {
            unset($cats[0]);
        }

        return $cats;
    }

    // }}}

    function listAttachments($cid)
    {
        global $db;
        return  $db->x->getAll(
                'SELECT  *
                   FROM  {attachments}
                  WHERE  comment_id = ?
               ORDER BY  attachment_id ASC',
               null, $cid);
    }

    function listTaskAttachments($tid)
    {
        global $db;
        return $db->x->getAll(
                'SELECT  *
                   FROM  {attachments}
                  WHERE  task_id = ? AND comment_id = 0
               ORDER BY  attachment_id ASC',
               null, $tid);
    }
    /* }}} */
}

?>
