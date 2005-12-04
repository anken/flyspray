<?php foreach($groups as $group): ?>
<a class="grouptitle" href="{$fs->CreateURL('editgroup', $group['group_id'])}">{$group['group_name']}</a>
<p>{$group['group_desc']}</p>
<form action="{$baseurl}" method="post">
  <div>
    <input type="hidden" name="do" value="modify" />
    <input type="hidden" name="action" value="movetogroup" />
    <input type="hidden" name="old_group" value="{$group['group_id']}" />
    <input type="hidden" name="project_id" value="{$proj->id}" />
    <input type="hidden" name="prev_page" value="{$_SERVER['REQUEST_URI']}" />
  </div>

  <table class="userlist">
    <tr>
      <th></th>
      <th>{$admin_text['username']}</th>
      <th>{$admin_text['realname']}</th>
      <th>{$admin_text['accountenabled']}</th>
    </tr>
    <?php foreach($proj->listUsersIn($group['group_id']) as $usr): ?>
    <tr>
      <td>{!tpl_checkbox('users['.$usr['user_id'].']')}</td>
      <td><a href="{$fs->CreateURL('user', $usr['user_id'])}">{$usr['user_name']}</a></td>
      <td>{$usr['real_name']}</td>
      <?php if ($user->infos['account_enabled']): ?>
      <td>{$admin_text['yes']}</td>
      <?php else: ?>
      <td>{$admin_text['no']}</td>
      <?php endif; ?>
    </tr>
    <?php endforeach; ?>

    <tr>
      <td colspan="4">
        <input class="adminbutton" type="submit" value="{$admin_text['moveuserstogroup']}" />
        <select class="adminlist" name="switch_to_group">
          <?php if (!$is_admin): ?>
          <option value="0">{$admin_text['nogroup']}</option>
          <?php endif; ?>
          {!tpl_options($proj->listGroups($is_admin))}
        </select>
      </td>
    </tr>
  </table>
</form>

<?php endforeach; ?>

<?php if (!$is_admin): ?>
<form action="{$baseurl}" method="post">
  <div>
    <input type="hidden" name="do" value="modify" />
    <input type="hidden" name="action" value="addtogroup" />
    <input type="hidden" name="project_id" value="{$proj->id}" />
    <input type="hidden" name="prev_page" value="{$_SERVER['REQUEST_URI']}" />
    <select class="adminlist" name="user_list[]" multiple="multiple" size="15">
      {!tpl_options($proj->UserList($is_admin))}
    </select>
    <br />
    <input class="adminbutton" type="submit" value="{$admin_text['addtogroup']}" />
    <select class="adminbutton" name="add_to_group">
      {!tpl_options($proj->listGroups($is_admin))}
    </select>
  </div>
</form>
<?php endif; ?>