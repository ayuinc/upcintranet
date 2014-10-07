CREATE TABLE IF NOT EXISTS `exp_calendar_calendars` (
	`calendar_id` 		int(10) unsigned 		NOT NULL DEFAULT '0',
	`site_id` 			int(10) unsigned 		NOT NULL DEFAULT '1',
	`tz_offset` 		char(5) 				NOT NULL DEFAULT '+0000',
	`timezone` 			varchar(100) 			NOT NULL DEFAULT 'Europe/London',
	`time_format` 		varchar(10) 			NOT NULL DEFAULT 'g:i a',
	`ics_url` 			text,
	`ics_updated` 		datetime 				DEFAULT '0000-00-00',
	PRIMARY KEY 		(`calendar_id`),
	KEY 				(`site_id`)
) CHARACTER SET utf8 COLLATE utf8_general_ci ;;

CREATE TABLE IF NOT EXISTS `exp_calendar_events` (
	`event_id` 			int(10) unsigned 		NOT NULL AUTO_INCREMENT,
	`site_id` 			int(10) unsigned 		NOT NULL DEFAULT '1',
	`calendar_id` 		int(10) unsigned 		NOT NULL DEFAULT '0',
	`entry_id` 			int(10) unsigned 		NOT NULL DEFAULT '0',
	`start_date` 		int(8) 					NOT NULL DEFAULT '0',
	`start_year` 		smallint(4) 			NOT NULL DEFAULT '0',
	`start_month` 		tinyint(2) 				NOT NULL DEFAULT '0',
	`start_day` 		tinyint(2) 				NOT NULL DEFAULT '0',
	`all_day` 			char(1) 				NOT NULL DEFAULT 'n',
	`start_time` 		smallint unsigned 		NOT NULL DEFAULT '0',
	`end_date` 			int(8) 					NOT NULL DEFAULT '0',
	`end_year` 			smallint(4) 			NOT NULL DEFAULT '0',
	`end_month` 		tinyint(2) 				NOT NULL DEFAULT '0',
	`end_day` 			tinyint(2) 				NOT NULL DEFAULT '0',
	`end_time` 			smallint unsigned 		NOT NULL DEFAULT '0',
	`recurs` 			char(1) 				NOT NULL DEFAULT 'n',
	`last_date` 		int(8) 					NOT NULL DEFAULT '0',
	PRIMARY KEY 		(`event_id`),
	KEY 				(`site_id`),
	KEY 				(`calendar_id`),
	KEY 				(`start_date`),
	KEY 				(`end_date`),
	KEY 				(`last_date`)
) CHARACTER SET utf8 COLLATE utf8_general_ci ;;

CREATE TABLE IF NOT EXISTS `exp_calendar_events_rules` (
	`rule_id` 			int(10) unsigned 		NOT NULL AUTO_INCREMENT,
	`event_id` 			int(10) unsigned 		NOT NULL DEFAULT '0',
	`calendar_id` 		int(10) unsigned 		NOT NULL DEFAULT '0',
	`entry_id` 			int(10) unsigned 		NOT NULL DEFAULT '0',
	`rule_type` 		char(1) 				DEFAULT '+',
	`start_date` 		int(8) 					NOT NULL DEFAULT '0',
	`all_day` 			char(1) 				NOT NULL DEFAULT 'n',
	`start_time` 		smallint unsigned 		NOT NULL DEFAULT '0',
	`end_date` 			int(8) 					NOT NULL DEFAULT '0',
	`end_time` 			smallint unsigned 		NOT NULL DEFAULT '0',
	`repeat_years` 		smallint(5) unsigned 	NOT NULL DEFAULT '0',
	`repeat_months` 	smallint(5) unsigned 	NOT NULL DEFAULT '0',
	`repeat_days` 		smallint(5) unsigned 	NOT NULL DEFAULT '0',
	`repeat_weeks` 		smallint(5) unsigned 	NOT NULL DEFAULT '0',
	`days_of_week` 		varchar(7) 				DEFAULT '',
	`relative_dow` 		varchar(6) 				NOT NULL DEFAULT '',
	`days_of_month` 	varchar(31) 			DEFAULT '',
	`months_of_year` 	varchar(12) 			DEFAULT '',
	`stop_by` 			int(8) 					NOT NULL DEFAULT '0',
	`stop_after` 		smallint(5) unsigned 	NOT NULL DEFAULT '0',
	`last_date` 		int(8) 					NOT NULL DEFAULT '0',
	PRIMARY KEY 		(`rule_id`),
	KEY 				(`event_id`),
	KEY 				(`start_date`),
	KEY 				(`end_date`)
) CHARACTER SET utf8 COLLATE utf8_general_ci ;;

CREATE TABLE IF NOT EXISTS `exp_calendar_events_occurrences` (
	`occurrence_id` 	int(10) unsigned 		NOT NULL AUTO_INCREMENT,
	`event_id` 			int(10) unsigned 		NOT NULL DEFAULT '0',
	`calendar_id` 		int(10) unsigned 		NOT NULL DEFAULT '0',
	`site_id` 			int(10) unsigned 		NOT NULL DEFAULT '0',
	`entry_id` 			int(10) unsigned 		NOT NULL DEFAULT '0',
	`start_date` 		int(8) 					NOT NULL DEFAULT '0',
	`start_year` 		smallint(4) 			NOT NULL DEFAULT '0',
	`start_month` 		tinyint(2) 				NOT NULL DEFAULT '0',
	`start_day` 		tinyint(2) 				NOT NULL DEFAULT '0',
	`all_day` 			char(1) 				NOT NULL DEFAULT 'n',
	`start_time` 		smallint unsigned 		NOT NULL DEFAULT '0',
	`end_date` 			int(8) 					NOT NULL DEFAULT '0',
	`end_year` 			smallint(4) 			NOT NULL DEFAULT '0',
	`end_month` 		tinyint(2) 				NOT NULL DEFAULT '0',
	`end_day` 			tinyint(2) 				NOT NULL DEFAULT '0',
	`end_time` 			smallint unsigned 		NOT NULL DEFAULT '0',
	PRIMARY KEY 		(`occurrence_id`),
	KEY 				(`event_id`),
	KEY 				(`entry_id`),
	KEY 				(`calendar_id`),
	KEY 				(`site_id`),
	KEY 				(`start_date`),
	KEY 				(`end_date`)
) CHARACTER SET utf8 COLLATE utf8_general_ci ;;

CREATE TABLE IF NOT EXISTS `exp_calendar_events_exceptions` (
	`exception_id` 		int(10) unsigned 		NOT NULL AUTO_INCREMENT,
	`event_id` 			int(10) unsigned 		NOT NULL DEFAULT '0',
	`calendar_id` 		int(10) unsigned 		NOT NULL DEFAULT '0',
	`site_id` 			int(10) unsigned 		NOT NULL DEFAULT '0',
	`entry_id` 			int(10) unsigned 		NOT NULL DEFAULT '0',
	`start_date` 		int(8) 					NOT NULL DEFAULT '0',
	`start_year` 		smallint(4) 			NOT NULL DEFAULT '0',
	`start_month` 		tinyint(2) 				NOT NULL DEFAULT '0',
	`start_day` 		tinyint(2) 				NOT NULL DEFAULT '0',
	`start_time` 		smallint unsigned 		NOT NULL DEFAULT '0',
	PRIMARY KEY 		(`exception_id`),
	KEY 				(`event_id`),
	KEY 				(`entry_id`),
	KEY 				(`calendar_id`),
	KEY 				(`site_id`),
	KEY 				(`start_date`)
) CHARACTER SET utf8 COLLATE utf8_general_ci ;;

CREATE TABLE IF NOT EXISTS `exp_calendar_reminders` (
	`reminder_id` 		int(10) unsigned		NOT NULL AUTO_INCREMENT,
	`member_id` 		int(10) unsigned 		NOT NULL DEFAULT '0',
	`event_id` 			int(10) unsigned 		NOT NULL DEFAULT '0',
	`occurrence_id` 	int(10) unsigned 		NOT NULL DEFAULT '0',
	`template_id` 		int(10) unsigned 		NOT NULL DEFAULT '0',
	`time_interval` 	smallint(5) unsigned 	NOT NULL DEFAULT '1',
	`time_unit` 		char(1) 				NOT NULL DEFAULT 'd',
	PRIMARY KEY 		(`reminder_id`),
	KEY 				(`member_id`),
	KEY 				(`event_id`),
	KEY 				(`occurrence_id`)
) CHARACTER SET utf8 COLLATE utf8_general_ci ;;

CREATE TABLE IF NOT EXISTS `exp_calendar_reminders_templates` (
	`template_id` 		int(10) unsigned 		NOT NULL AUTO_INCREMENT,
	`wordwrap` 			char(1) 				NOT NULL DEFAULT 'y',
	`html` 				char(1) 				NOT NULL DEFAULT 'n',
	`template_name` 	varchar(150) 			NOT NULL DEFAULT '',
	`template_label` 	varchar(150) 			NOT NULL DEFAULT '',
	`from_name` 		varchar(150) 			NOT NULL DEFAULT '',
	`from_email` 		varchar(200) 			NOT NULL DEFAULT '',
	`subject` 			varchar(80) 			NOT NULL DEFAULT '',
	`template_data` 	text,
	PRIMARY KEY 		(`template_id`),
	KEY 				(`template_name`)
) CHARACTER SET utf8 COLLATE utf8_general_ci ;;

CREATE TABLE IF NOT EXISTS `exp_calendar_permissions_users` (
	`permission_id` 	int(10) unsigned 		NOT NULL AUTO_INCREMENT,
	`user_id` 			int(10) unsigned 		NOT NULL DEFAULT '0',
	`calendar_id` 		int(10) unsigned 		NOT NULL DEFAULT '0',
	`calendar_admin` 	char(1) 				NOT NULL DEFAULT 'n',
	`calendar_edit` 	char(1) 				NOT NULL DEFAULT 'n',
	`calendar_view` 	char(1) 				NOT NULL DEFAULT 'n',
	`events_admin` 		char(1) 				NOT NULL DEFAULT 'n',
	`events_edit` 		char(1) 				NOT NULL DEFAULT 'n',
	`events_view` 		char(1) 				NOT NULL DEFAULT 'n',
	PRIMARY KEY 		(`permission_id`),
	KEY 				(`user_id`),
	KEY 				(`calendar_id`)
) CHARACTER SET utf8 COLLATE utf8_general_ci ;;

CREATE TABLE IF NOT EXISTS `exp_calendar_permissions_groups` (
	`permission_id` 	int(10) unsigned 		NOT NULL AUTO_INCREMENT,
	`group_id` 			int(10) unsigned 		NOT NULL DEFAULT '0',
	`calendar_id` 		int(10) unsigned 		NOT NULL DEFAULT '0',
	`calendar_admin` 	char(1) 				NOT NULL DEFAULT 'n',
	`calendar_edit` 	char(1) 				NOT NULL DEFAULT 'n',
	`calendar_view` 	char(1) 				NOT NULL DEFAULT 'n',
	`events_admin` 		char(1) 				NOT NULL DEFAULT 'n',
	`events_edit` 		char(1) 				NOT NULL DEFAULT 'n',
	`events_view` 		char(1) 				NOT NULL DEFAULT 'n',
	PRIMARY KEY 		(`permission_id`),
	KEY					(`group_id`),
	KEY 				(`calendar_id`)
) CHARACTER SET utf8 COLLATE utf8_general_ci ;;

CREATE TABLE IF NOT EXISTS `exp_calendar_preferences` (
	`site_id` 			int(10) unsigned 		NOT NULL DEFAULT '1',
	`preferences` 		text,
	KEY (`site_id`)
) CHARACTER SET utf8 COLLATE utf8_general_ci ;;

CREATE TABLE IF NOT EXISTS `exp_calendar_permissions_preferences` (
	`site_id` 			int(10) unsigned 		NOT NULL DEFAULT '1',
	`preferences` 		text,
	KEY (`site_id`)
) CHARACTER SET utf8 COLLATE utf8_general_ci ;;

CREATE TABLE IF NOT EXISTS `exp_calendar_events_imports` (
	`import_id` 		int(10) unsigned 		NOT NULL AUTO_INCREMENT,
	`calendar_id` 		int(10) unsigned 		NOT NULL DEFAULT '0',
	`event_id` 			int(10) unsigned 		NOT NULL DEFAULT '0',
	`entry_id` 			int(10) unsigned 		NOT NULL DEFAULT '0',
	`uid` 				varchar(255) 			NOT NULL DEFAULT '',
	`last_mod` 			char(12) 				NOT NULL DEFAULT '',
	PRIMARY KEY 		(`import_id`),
	KEY 				(`calendar_id`),
	KEY 				(`event_id`),
	KEY 				(`entry_id`),
	KEY 				(`uid`)
) CHARACTER SET utf8 COLLATE utf8_general_ci ;;