<?php

// Convert lists
$sql = $db->query('SELECT * FROM {list_items}');
if ($sql && !$sql->FetchRow()) {
    foreach (array('os' => 'operating_system', 'resolution' => 'resolution_reason', 'status' => 'item_status',
                           'tasktype' => 'task_type', 'version' => array('product_version', 'closedby_version')) as $list => $cols) {
        $sql = $db->query('SELECT DISTINCT project_id FROM {list_'.$list.'}');
		
		# continue if we have an error or the required query returns no result
		if (PEAR::isError($sql)) continue;
		
        while ($row = $sql->FetchRow()) {
            // Create new list
            if ($list == 'version') {
                $db->x->execParam('INSERT INTO {lists} (list_name, list_type, project_id) VALUES(?,?,?)', array($list, 2, $row['project_id']));
            } else {
                $db->x->execParam('INSERT INTO {lists} (list_name, list_type, project_id) VALUES(?,?,?)', array($list, 1, $row['project_id']));
            }
            $list_id = $db->x->GetOne('SELECT list_id FROM {lists} WHERE project_id = ? ORDER BY list_id DESC', null, $row['project_id']);

            // Transfer list items
            $res = $db->x->getAll('SELECT * FROM {list_' . $list . '} WHERE project_id = ?', null, $row['project_id']);
            foreach ($res as $item) {
                if ($list == 'version') {
                    $db->x->execParam('INSERT INTO {list_items} (item_name, list_position, show_in_list, list_id, version_tense)
                                     VALUES (?, ?, ?, ?, ?)',
                               array($item[$list . '_name'], $item['list_position'], $item['show_in_list'], $list_id, $item['version_tense']));
                } else {
                    $db->x->execParam('INSERT INTO {list_items} (item_name, list_position, show_in_list, list_id)
                                     VALUES (?, ?, ?, ?)',
                               array($item[$list . '_name'], $item['list_position'], $item['show_in_list'], $list_id));
                }

                $tt = $db->x->GetOne('SELECT list_item_id FROM {list_items} ORDER BY list_item_id DESC');
                // Update existing tasks
                settype($cols, 'array');
                foreach ($cols as $col) {
                    $db->x->execParam('UPDATE {tasks} SET ' . $col .' = ? WHERE '. $col . '= ?', array($tt, $item[$list . '_id']));
                    // and history tables
                    $db->x->execParam('UPDATE {history} SET new_value = ? WHERE new_value = ? AND field_changed = ?', array($tt, $item[$list . '_id'], $col));
                    $db->x->execParam('UPDATE {history} SET old_value = ? WHERE old_value = ? AND field_changed = ?', array($tt, $item[$list . '_id'], $col));
                }
                if ($list == 'resolution') {
                    $db->x->execParam('UPDATE {history} SET new_value = ? WHERE new_value = ? AND event_type = 2', array($tt, $item[$list . '_id']));
                }
            }
        }
    }

    // Categories
    $sql = $db->query('SELECT DISTINCT project_id FROM {list_category}');

    while ($row = $sql->FetchRow()) {
        // Create new list
        $db->x->execParam('INSERT INTO {lists} (list_name, list_type, project_id) VALUES(?,?,?)', array('category', 3, $row['project_id']));
        $list_id = $db->x->GetOne('SELECT list_id FROM {lists} WHERE project_id = ? ORDER BY list_id DESC', null, $row['project_id']);
        $db->x->execParam('UPDATE {list_category} SET list_id = ? WHERE project_id = ?', array($list_id, $row['project_id']));
    }
}

// Convert fields
$sql = $db->query('SELECT * FROM {field_values}');
if ($sql && !$sql->FetchRow()) {
    // Add priorities
    $db->x->execParam('INSERT INTO {lists} (list_name, list_type, project_id) VALUES(?,?,?)', array('Priorities', 1, 0));
    $pri_id = $db->x->GetOne('SELECT list_id FROM {lists} ORDER BY list_id DESC');
    $pri_ids = array();
    $li = 0;
    foreach (array(6 => 'Flash', 5 => 'Immediate', 4 => 'Urgent', 3 => 'High', 2 => 'Normal', 1 => 'Low') as $key => $priority) {
        $db->x->execParam('INSERT INTO {list_items} (item_name, list_position, show_in_list, list_id) VALUES(?,?,1,?)', array($priority, $li++, $pri_id));
        $pri_ids[$key] = $db->x->GetOne('SELECT list_item_id FROM {list_items} ORDER BY list_item_id DESC');

        $db->x->execParam('UPDATE {history} SET new_value = ? WHERE new_value = ? AND field_changed = ?', array($pri_ids[$key], $key, 'task_priority'));
        $db->x->execParam('UPDATE {history} SET old_value = ? WHERE old_value = ? AND field_changed = ?', array($pri_ids[$key], $key, 'task_priority'));
    }

	$all_project_id_sql = $db->query('SELECT DISTINCT project_id FROM {projects} ');

	while ($my_project_id = $all_project_id_sql->FetchRow()) {
	    foreach (array('task_type' => 'Task Type',
			   'item_status' => 'Status',
			   'due_date' => 'Due date',
			   'task_priority' => 'Priority',

			   'operating_system' => 'Operating System',
			   'closedby_version' => 'Due in Version',
			   'product_version' => 'Reported version',
			   'product_category' => 'Category') as $field => $name) {

		if(($field != 'task_priority') && ($field != 'due_date'))
		{
			if($field == "task_type")
				$my_name = "tasktype";
			else if($field == "item_status")
				$my_name = "status";
			else if($field == "operating_system")
				$my_name = "os";
			else if($field == "closedby_version")
				$my_name = "version";
			else if($field == "product_version")
				$my_name = "version";
			else if($field == "product_category")
				$my_name = "category";

			$list_id =$db->x->getOne("select list_id from {lists} where list_name =? and (project_id = ? OR  project_id= 0 ) ORDER BY project_id DESC",
                                      null, array($my_name,$my_project_id['project_id']));
			if(!isset($list_id))
			{
				 $list_id = 1;
			}

		}

		$db->x->execParam('INSERT INTO {fields} (field_name, field_type, list_id, project_id) VALUES(?,?,?,?)',
			   array($name, ($field == 'due_date' ? 2 : 1), ($field == 'task_priority' ? $pri_id : $list_id), ($field == 'due_date' ? 0 : $my_project_id['project_id'])));
		$field_id = $db->x->getRow("SELECT field_id FROM {fields} WHERE project_id=? AND field_name = ? ORDER BY field_id DESC", null, array($my_project_id['project_id'], $name));

		$db->x->execParam('UPDATE {history} SET field_changed = ? WHERE field_changed = ? AND event_type = 3', array($field_id['field_id'], $field));
		if ($field == 'closedby_version') {
		    $db->x->execParam('UPDATE {projects} SET roadmap_field = ?', array($field_id['field_id']));
		}

		// Add values
		$tasks = $db->x->getAll("SELECT {$field}, task_id FROM {tasks} WHERE project_id=? ", null, $my_project_id['project_id']);
		if ($field != 'task_priority') {
		    foreach ($tasks as $row) {
			$db->x->execParam('INSERT INTO {field_values} (task_id, field_value, field_id) VALUES (?,?,?)', array($row['task_id'], $row[$field], $field_id['field_id']));
		    }
		} else {
		    foreach ($tasks as $row) {
			$db->x->execParam('INSERT INTO {field_values} (task_id, field_value, field_id) VALUES (?,?,?)', array($row['task_id'], @intval($pri_ids[$row[$field]]), $field_id['field_id']));
		    }
		}
	    }
	}


    // Clean columns
    $db->x->execParam('UPDATE {projects} SET visible_columns = ?', array('id severity summary progress'));
    $db->x->execParam('UPDATE {prefs} SET pref_value = ? WHERE pref_name = ?', array('id severity summary progress', 'visible_columns'));
}

// Try to guess resolution list
$sql = $db->x->GetOne('SELECT pref_value FROM {prefs} WHERE pref_name = ?', null, 'resolution_list');
if ($sql == 0) {
    // find list
    $list = $db->x->GetOne('SELECT list_id FROM {lists} WHERE list_name = ? AND project_id = 0', null, 'resolution');
    $db->x->execParam('UPDATE {prefs} SET pref_value = ? WHERE pref_name = ?', array($list, 'resolution_list'));
}
?>
