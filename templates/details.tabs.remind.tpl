<div id="remind" class="tab">
  <?php foreach ($reminders as $row): ?>
  <?php if (!$task_details['is_closed']): ?>
  <form action="{$baseurl}" method="post">
    <div>
      <input type="hidden" name="do" value="modify" />
      <input type="hidden" name="action" value="deletereminder" />
      <input type="hidden" name="task_id" value="{Get::val('id')}" />
      <input type="hidden" name="reminder_id" value="{$row['reminder_id']}" />
      <input class="adminbutton" type="submit" value="{$details_text['remove']}" />
    </div>
  </form>
  <?php endif; ?>
  <em>{$details_text['remindthisuser']}:</em>
  <a href="?do=admin&amp;area=users&amp;id={$row['to_user_id']}">
    {$row['real_name']} ({$row['user_name']})</a>
  <br />
  <?php
      // Work out the unit of time to display
      if ($row['how_often'] < 86400) {
          $how_often = $row['how_often'] / 3600 . " " . $details_text['hours'];
      } elseif ($row['how_often'] < 604800) {
          $how_often = $row['how_often'] / 86400 . " " . $details_text['days'];
      } else {
          $how_often = $row['how_often'] / 604800 . " " . $details_text['weeks'];
      }
  ?>

  <em>{$details_text['thisoften']}:</em> {$how_often}
  <br />
  <em>{$details_text['message']}:</em> {!tpl_formatText($row['reminder_message'])}
  <br /><br />
  <?php endforeach; ?>

  <?php if (!$task_details['is_closed']): ?>
  <form action="{$baseurl}" method="post" id="formaddreminder">
    <div class="admin">
      <input type="hidden" name="do" value="modify" />
      <input type="hidden" name="action" value="addreminder" />
      <input type="hidden" name="task_id" value="{Get::val('id')}" />

      <em>{$details_text['remindthisuser']}</em>
      <select class="adminlist" name="to_user_id">
        <?php $fs->listUsers($proj->id); ?>
      </select>

      <br />

      <em>{$details_text['thisoften']}</em>
      <input type="text" name="timeamount1" size="3" maxlength="3" />
      <select class="adminlist" name="timetype1">
        <option value="3600">{$details_text['hours']}</option>
        <option value="86400">{$details_text['days']}</option>
        <option value="604800">{$details_text['weeks']}</option>
      </select>

      <br />

      <em>{$details_text['startafter']}</em>
      <input type="text" name="timeamount2" size="3" maxlength="3" />
      <select class="adminlist" name="timetype2">
        <option value="3600">{$details_text['hours']}</option>
        <option value="86400">{$details_text['days']}</option>
        <option value="604800">{$details_text['weeks']}</option>
      </select>

      <br />
      <textarea class="admintext" name="reminder_message"
        rows="10" cols="72">{$details_text['defaultreminder']}

{$fs->CreateURL('details', Get::val('id'))}</textarea>
      <br />
      <input class="adminbutton" type="submit" value="{$details_text['addreminder']}" />
    </div>
  </form>
  <?php endif; ?>
</div>