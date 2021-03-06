<?php
   /**********************************************************\
   | This script moves data from {dbprefix)tasks.assigned_to   |
   | to {dbprefix}assigned.  This is to implement multiple     |
   | assignees per task as described in FS#329.  It only needs |
   | to be run once to do the conversion.                      |
   \***********************************************************/

$check_sql = $db->query("SELECT task_id, assigned_to
                           FROM {tasks}
                          WHERE assigned_to > '0'");

while ($row = $check_sql->FetchRow())
{
   $check = $db->x->GetOne('SELECT assigned_id FROM {assigned} WHERE task_id = ? AND user_id = ?',
                          null, array($row['task_id'], $row['assigned_to']));
   if ($check) {
       continue;
   }

   $db->x->execParam('INSERT INTO {assigned}
                           (task_id, user_id)
                    VALUES (?,?)',
                           array($row['task_id'], $row['assigned_to']));

   $db->x->execParam('UPDATE {tasks}
                  SET assigned_to = 0
                WHERE task_id = ?',
                      array($row['task_id']));
}
?>
