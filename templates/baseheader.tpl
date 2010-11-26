<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="{L('locale')}" xml:lang="{L('locale')}">
  <head>
    <title>{$this->_title}</title>

    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta http-equiv="Content-Script-Type" content="text/javascript" />
    <meta http-equiv="Content-Style-Type" content="text/css" />

    <link rel="icon" type="image/png" href="{$this->get_image('favicon')}" />
    <link rel="index" id="indexlink" type="text/html" href="{$this->relativeUrl($baseurl)}" />
    <?php foreach ($fs->projects as $project): ?>
    <link rel="section" type="text/html" href="{$this->relativeUrl($baseurl)}?project={$project['project_id']}" />
    <?php endforeach; ?>
    <link media="screen" href="{$this->themeUrl()}theme.css" rel="stylesheet" type="text/css" />
    <link media="print"  href="{$this->themeUrl()}theme_print.css" rel="stylesheet" type="text/css" />
    <link rel="alternate" type="application/rss+xml" title="Flyspray Feed - {L('taskopened')}" href="{$this->relativeUrl($baseurl)}feed.php?feed_type=rss1&amp;project={$proj->id}" />
    <link rel="alternate" type="application/rss+xml" title="Flyspray Feed - {L('taskclosed')}" href="{$this->relativeUrl($baseurl)}feed.php?feed_type=rss1&amp;project={$proj->id}" />
    <link rel="alternate" type="application/rss+xml" title="Flyspray Feed - {L('taskedited')}" href="{$this->relativeUrl($baseurl)}feed.php?feed_type=rss1&amp;project={$proj->id}" />
    <link title="{$proj->prefs['project_title']} - Flyspray" type="application/opensearchdescription+xml" rel="search" href="{$this->relativeUrl($baseurl)}index.php?opensearch=1&amp;project_id={$proj->id}" />
    <link rel="start" href="{$this->relativeUrl($baseurl)}" id="baseurl" />

    <style type="text/css">
    <?php
    if ($fs->prefs['color_field']):
        $colors = array('#fff5dd' => '#ffe9b4', '#ecdbb7' => '#efca80', '#f5d5c6' => '#f7b390',
                        '#ffd5d1' => '#ffb2ac', '#f3a29b' => '#f3867e');
        end($colors);
        if (isset($proj->fields['field' . $fs->prefs['color_field']]->values)):
            foreach ($proj->fields['field' . $fs->prefs['color_field']]->values as $key => $value):
                if (!isset($value['list_item_id']) || !$value['list_item_id']) continue; ?>
                .colorfield{$value['list_item_id']} { background-color:{key($colors)} !important; }
                .colorfield{$value['list_item_id']}:hover { background-color:{current($colors)} !important; }
            <?php prev($colors); 
            endforeach; 
        endif;
    endif; ?>
    </style>

    <script type="text/javascript" src="{$this->relativeUrl($baseurl)}javascript/prototype/prototype.js"></script>
    <?php if ('index' == $do || 'details' == $do): ?>
        <script type="text/javascript" src="{$this->relativeUrl($baseurl)}javascript/{$do}.js"></script>
    <?php endif; ?>
    <?php if ( $do == 'pm' || $do == 'admin'): ?>
        <script type="text/javascript" src="{$this->relativeUrl($baseurl)}javascript/tablecontrol.js"></script>
    <?php endif; ?>
    <script type="text/javascript" src="{$this->relativeUrl($baseurl)}javascript/bsn.AutoSuggest_2.1.js"></script>
    <script type="text/javascript" src="{$this->relativeUrl($baseurl)}javascript/tabs.js"></script>
    <script type="text/javascript" src="{$this->relativeUrl($baseurl)}javascript/functions.js"></script>
    <script type="text/javascript" src="{$this->relativeUrl($baseurl)}javascript/jscalendar/calendar_stripped.js"></script>
    <script type="text/javascript" src="{$this->relativeUrl($baseurl)}javascript/jscalendar/lang/calendar-{substr(L('locale'), 0, 2)}.js"></script>
    <!--[if IE]>
    <link media="screen" href="{$this->themeUrl()}ie.css" rel="stylesheet" type="text/css" />
    <![endif]-->
  </head>
  <body onload="perms = new Perms('permissions');<?php
        if (isset($_SESSION['SUCCESS'])):
        ?>setTimeout('var fade = new _bsn.Fader($(&quot;successbar&quot;),1,0,500)', 6000);<?php
        elseif (isset($_SESSION['ERROR'])):
        ?>setTimeout('var fade = new _bsn.Fader($(&quot;errorbar&quot;),1,0,500)', 15000);<?php endif ?>">

  <div id="container">
