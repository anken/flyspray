CREATE TABLE `flyspray_reminders` (
  `reminder_id` mediumint(10) NOT NULL auto_increment,
  `task_id` mediumint(10) NOT NULL default '0',
  `to_user_id` mediumint(3) NOT NULL default '0',
  `from_user_id` mediumint(3) NOT NULL default '0',
  `start_time` varchar(12) NOT NULL default '0',
  `how_often` mediumint(12) NOT NULL default '0',
  `last_sent` varchar(12) NOT NULL default '0',
  `reminder_message` longtext NOT NULL,
  PRIMARY KEY  (`reminder_id`)
) TYPE=MyISAM COMMENT='Scheduled reminders about tasks' AUTO_INCREMENT=19 ;


ALTER TABLE `flyspray_tasks` ADD `is_closed` MEDIUMINT( 1 ) NOT NULL AFTER `opened_by` ;

update flyspray_tasks set is_closed = '1' where item_status = '8';