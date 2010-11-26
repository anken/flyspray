<fieldset>
<legend>{L('massedit')}</legend>
  <form action="{$this->url('edit', array('ids' => Get::val('ids')))}" method="post">
    <input type="hidden" name="ids" value="{implode(' ', Get::val('ids', array()))}" />
    <input type="hidden" name="action" value="edit" />
    <p>{L('editnote')}</p>
    <p><label>{L('selectedtasks')}:
    <select name="showselected">
    <?php foreach (Get::val('ids', array()) as $id): ?>
    <option>{strip_tags(tpl_tasklink($id))}</option>
    <?php endforeach; ?>
    </select></label>
    </p>

    <hr />

    <table><tr><td id="taskfieldscell"><?php // small layout table ?>

    <div id="taskfields">
      <table>
        <tr>
          <td><input type="checkbox" name="changes[]" value="project_id"/></td>
          <th id="project_id">{L('attachedtoproject')}</th>
          <td headers="project_id">
            <select name="project_id">
			{!tpl_options($fs->projects, Post::val('project_id', $proj->id))}
		    </select>
          </td>
        </tr>
        <?php foreach ($proj->fields as $field): ?>
        <tr>
          <td><input type="checkbox" name="changes[]" value="field{$field->id}"/></td>
          <th id="fh{$field->id}">{$field->prefs['field_name']}</th>
          <td headers="fh{$field->id}">
            {!$field->edit(USE_DEFAULT, !LOCK_FIELD)}
          </td>
        </tr>
        <?php endforeach; ?>
        <tr>
          <td><input type="checkbox" name="changes[]" value="assigned_to" /></td>
          <td>
            <label>{L('assignedto')}</label>
          </td>
          <td>
            <?php $this->display('common.multiuserselect.tpl'); ?>
          </td>
        </tr>
        <tr>
          <td><input type="checkbox" name="changes[]" value="mark_private" /></td>
          <td><label for="private">{L('private')}</label></td>
          <td>
            {!tpl_checkbox('mark_private', Req::val('mark_private', 0), 'private')}
          </td>
        </tr>
      </table>
    </div>

    </td><td width="350">
      <div>
         <select class="adminlist" name="resolution_reason" onmouseup="Event.stop(event);">
            <option value="0">{L('selectareason')}</option>
            {!tpl_options($proj->get_list(array('list_id' => $fs->prefs['resolution_list'])), Post::val('resolution_reason'))}
         </select>
         <label class="inline" for="closure_comment">{L('closurecomment')}</label>
         <textarea class="text" id="closure_comment" name="closure_comment" rows="3" cols="10">{Post::val('closure_comment')}</textarea>
         <label class="inline">{!tpl_checkbox('mark100', Post::val('mark100', !(Req::val('action') == 'edit')))}&nbsp;&nbsp;{L('mark100')}</label>
      </div>
    </td></tr></table>

    <button type="submit">{L('applychanges')}</button>
  </form>

  <div class="clear"></div>
</fieldset>