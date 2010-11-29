<?php if (!count($projects)): ?>
<div class="box">
<h2>{L('allprivate')}</h2>
</div>
<?php endif; ?>

<?php foreach ($projects as $project): ?>
<div class="box">
<h2><a href="{$this->url(array('', 'proj' . $project['project_id']))}">{$project['project_title']}</a></h2>
<table class="toplevel">
  <?php if ($stats[$project['project_id']]['project_managers']): ?>
  <tr>
    <th><strong>{L('projectmanagers')}</strong></th>
    <td>
      {!$stats[$project['project_id']]['project_managers']}
    </td>
  </tr>
  <?php endif; ?>
  <tr>
    <th><strong>{L('viewtasks')}</strong></th>
    <td>
        <a href="{$_SERVER['SCRIPT_NAME']}?do=index&amp;project={$project['project_id']}&amp;status[]=">{L('All')}</a> |
        <a href="{$_SERVER['SCRIPT_NAME']}?do=index&amp;project={$project['project_id']}&amp;field3to={date("Y-m-d")}">{L('Overdue')} |
        <a href="{$_SERVER['SCRIPT_NAME']}?do=index&amp;project={$project['project_id']}&amp;status[]=open">{L('open')}</a> |
        <?php if (!$user->isAnon()): ?>
          <a href="{$_SERVER['SCRIPT_NAME']}?do=index&amp;project={$project['project_id']}&amp;dev={$user->id}">{L('assignedtome')}</a> |
          <a href="{$_SERVER['SCRIPT_NAME']}?do=index&amp;project={$project['project_id']}&amp;only_watched=1">{L('taskswatched')}</a> |
          <a href="{$_SERVER['SCRIPT_NAME']}?do=index&amp;project={$project['project_id']}&amp;opened={$user->id}">{L('tasksireported')}</a> |
        <?php endif; ?>
        <a href="{$_SERVER['SCRIPT_NAME']}?do=index&amp;project={$project['project_id']}&amp;openedfrom=-1+week">{L('recentlyopened')}</a> |
        <a href="{$_SERVER['SCRIPT_NAME']}?do=index&amp;project={$project['project_id']}&amp;closedfrom=-1+week">{L('recentlyclosed')}</a>
    </td>
  </tr>
  <tr>
    <th><strong>{L('stats')}</strong></th>
    <td>{$stats[$project['project_id']]['open']} {L('opentasks')}, {$stats[$project['project_id']]['all']} {L('totaltasks')}.</td>
  </tr>
  <tr>
    <th><strong>{L('progress')}</strong></th>
    <td>
        <div class="taskpercent" style="height:1.2em;"><div style="width:{round($stats[$project['project_id']]['average_done'])}%">
          {$stats[$project['project_id']]['average_done']}%&nbsp;{L('done')}
        </div></div>
    </td>
  </tr>
  <?php if (isset($most_wanted[$project['project_id']])): ?>
  <tr>
    <th><strong>{L('mostwanted')}</strong></th>
    <td>
        <?php if (count($most_wanted[$project['project_id']])): ?>
        <ul>
            <?php foreach($most_wanted[$project['project_id']] as $task): ?>
            <li>{!tpl_tasklink($task['task_id'])}, {$task['num_votes']} {L('vote(s)')}</li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </td>
  </tr>
  <?php endif; ?>
  <tr>
    <th><strong>{L('feeds')}</strong></th>
    <td>
        <b>{L('rss')} 1.0</b> <a href="{$this->relativeUrl($baseurl)}feed.php?feed_type=rss1&amp;project={$project['project_id']}{$feed_auth}">{L('opened')}</a> -
        <a href="{$this->relativeUrl($baseurl)}feed.php?feed_type=rss1&amp;topic=edit&amp;project={$project['project_id']}{$feed_auth}">{L('edited')}</a> -
        <a href="{$this->relativeUrl($baseurl)}feed.php?feed_type=rss1&amp;topic=clo&amp;project={$project['project_id']}{$feed_auth}">{L('closed')}</a> |
        <b>{L('rss')} 2.0</b> <a href="{$this->relativeUrl($baseurl)}feed.php?feed_type=rss2&amp;project={$project['project_id']}{$feed_auth}">{L('opened')}</a> -
        <a href="{$this->relativeUrl($baseurl)}feed.php?feed_type=rss2&amp;topic=edit&amp;project={$project['project_id']}{$feed_auth}">{L('edited')}</a> -
        <a href="{$this->relativeUrl($baseurl)}feed.php?feed_type=rss2&amp;topic=clo&amp;project={$project['project_id']}{$feed_auth}">{L('closed')}</a> |
        <b>{L('atom')}</b> <a href="{$this->relativeUrl($baseurl)}feed.php?feed_type=atom&amp;project={$project['project_id']}{$feed_auth}">{L('opened')}</a> -
        <a href="{$this->relativeUrl($baseurl)}feed.php?feed_type=atom&amp;topic=edit&amp;project={$project['project_id']}{$feed_auth}">{L('edited')}</a> -
        <a href="{$this->relativeUrl($baseurl)}feed.php?feed_type=atom&amp;topic=clo&amp;project={$project['project_id']}{$feed_auth}">{L('closed')}</a>
    </td>
  </tr>
</table>
</div>
<?php endforeach; ?>