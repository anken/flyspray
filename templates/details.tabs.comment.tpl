<div id="comments" class="tab">
  <?php foreach($comments as $comment): ?>
  <em>
    <a name="comment{$comment['comment_id']}" id="comment{$comment['comment_id']}"
      href="{CreateURL('details', $task_details['task_id'])}#comment{$comment['comment_id']}">
      <img src="{$this->get_image('comment')}"
        title="{L('commentlink')}" alt="" />
    </a>
    {L('commentby')} {!tpl_userlink($comment['user_id'])} -
    {formatDate($comment['date_added'], true)}
  </em>

  <span class="DoNotPrint">
    <?php if ($user->perms['edit_comments'] || ($user->perms['edit_own_comments'] && $comment['user_id'] == $user->id)): ?>
    &mdash;
    <a href="{$baseurl}?do=editcomment&amp;task_id={Get::val('id')}&amp;id={$comment['comment_id']}">
      {L('edit')}</a>
    <?php endif; ?>

    <?php if ($user->perms['delete_comments']): ?>
    &mdash;
    <a href="{$baseurl}?do=modify&amp;action=deletecomment&amp;task_id={Get::val('id')}&amp;comment_id={$comment['comment_id']}"
      onclick="return confirm('{L('confirmdeletecomment')}');">
      {L('delete')}</a>
    <?php endif ?>
  </span>
  <div class="comment">
  <?php if(isset($comment_changes[$comment['date_added']])): ?>
  <ul class="comment_changes">
  <?php foreach($comment_changes[$comment['date_added']] as $change): ?>
    <li>{!event_description($change)}</li>
  <?php endforeach; ?>
  </ul>
  <?php endif; ?>
  <div class="commenttext">{!tpl_formatText($comment['comment_text'], false, 'comm', $comment['comment_id'], $comment['content'])}</div></div>

  <?php if (isset($comment_attachments[$comment['comment_id']])) {
            $this->display('common.attachments.tpl', 'attachments', $comment_attachments[$comment['comment_id']]);
        }
  ?>

  <?php endforeach; ?>

  <?php if ($user->perms['add_comments'] && (!$task_details['is_closed'] || $proj->prefs['comment_closed'])): ?>
  <fieldset><legend>{L('addcomment')}</legend>
  <form enctype="multipart/form-data" action="{$baseurl}" method="post">
    <div>
      <div class="hide preview" id="preview">{L('loading')}</div>
      <input type="hidden" name="do" value="modify" />
      <input type="hidden" name="action" value="addcomment" />
      <input type="hidden" name="task_id" value="{$task_details['task_id']}" />
      <?php if ($user->perms['create_attachments']): ?>
      <div id="uploadfilebox">
        <span style="display: none;"><?php // this span is shown/copied in javascript when adding files ?>
          <input tabindex="5" class="file" type="file" size="55" name="userfile[]" />
            <a href="javascript://" tabindex="6" onclick="removeUploadField(this);">{L('remove')}</a><br />
        </span>    
      </div>
      <button id="uploadfilebox_attachafile" tabindex="7" type="button" onclick="addUploadFields()">
        {L('uploadafile')}
      </button>
      <button id="uploadfilebox_attachanotherfile" tabindex="7" style="display: none" type="button" onclick="addUploadFields()">
         {L('attachanotherfile')}
      </button>
      <?php endif; ?>
      <textarea accesskey="r" tabindex="8" id="comment_text" name="comment_text" cols="72" rows="10"></textarea>


      <button tabindex="9" type="submit">{L('addcomment')}</button>
      <button tabindex="9" type="button" onclick="showPreview('comment_text', '{$baseurl}', 'preview')">{L('preview')}</button>
      <?php if (!$watched): ?>
      {!tpl_checkbox('notifyme', true, 'notifyme')} <label class="left" for="notifyme">{L('notifyme')}</label>
      <?php endif; ?>
    </div>
  </form>
  </fieldset>
  <?php endif; ?>
</div>
