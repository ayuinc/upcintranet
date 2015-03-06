<?php
 /**
 * mithra62 - Securit:ee
 *
 * @package		mithra62:Securitee
 * @author		Eric Lamb
 * @copyright	Copyright (c) 2011, mithra62, Eric Lamb.
 * @link		http://mithra62.com/projects/view/securit-ee/
 * @version		1.2.1
 * @filesource 	./system/expressionengine/third_party/securitee/
 */

/**
 * The add-ons language stuff
 * Used to switch between the various system languages
 * @var array
 */
$lang = array(

// Required for MODULES page

'securitee_module_name'		=> 'Securit:ee',
'securitee_module_description'	=> 'Securit:ee is a security suite for ExpressionEngine to add additional layers of protection for your site.',

//----------------------------------------

// Additional Key => Value pairs go here

// END
'security_scan_instructions' => 'Keep in mind; security is a cumulative effort. While it\'s recommended that you implement as many of the below settings as possible NO site would be very much fun locked down to such extremes. Do as many as you feel won\'t make your site suck.',
'modification_detection' => 'Modification Detection',
'scan_path' => 'Scan Path',
'configure_file_monitor' => 'Configure File Monitor',
'changed_files' => 'Changed Files',
'added_files' => 'Added Files',
'removed_files' => 'Removed Files',
'settings' => 'Settings',
'configure_securitee' => 'Configure Securit:ee',
'configure_ip_locker' => 'Configure Ip Lockers',
'configure_login_alert' => 'Configure CP Login Alert',
'configure_cp_quick_deny' => 'Configure CP Quick Deny',
'configure_cp_reg_email' => 'Configure CP Registration Email',
'enable_file_monitor' => 'Enable File Monitor',
'enable_file_monitor_instructions' => 'The File Monitor will watch after your file system and alert you when changes are made so you can visually inspect that your site hasn\'t been hacked. ',
'enable_cp_login_alert' => 'Enable CP Login Alert',
'enable_cp_login_alert_instructions' => 'The CP Login Alert will send an email every time someone logs into the Control Panel. This is useful for knowing immediately if your site has any unauthorized accesses so you can take counter measures.',
'enable_cp_ip_locker' => 'Enable CP IP Locker',
'enable_cp_ip_locker_instructions' => 'For even more security you can require logins to your Control Panel only be from certain IP addresses (your office perhaps). ',
'enable_client_ip_locker' => 'Enable Client IP Locker',
'enable_client_ip_locker_instructions' => 'Works just like the CP IP Locker except for the front side. This is especially helpful for allowing clients access to review things without letting the wider Internet in.',
'enable_quick_deny_cp_login_instructions' => 'Enabling this will deny all requests to the Control Panel by all selected member groups. Note, that you can not deny Super User.',
'enable_quick_deny_cp_login' => 'Enable CP Quick Deny',
'enable_expiring_passwords' => 'Enable Expiring Passwords',
'enable_expiring_passwords_instructions' => 'The Expiring Password extension will require members to change their password based on how long since the last time it was updated. ',
'enable_cp_member_reg_email' => 'Enable CP Member Registration Email',
'enable_cp_member_reg_email_instructions' => 'By default, ExpressionEngine won\'t send an email to the members created in the Control Panel. With this enabled Securit:ee will send a confirmation link simliar to Forgot Password so the member can create a password immediately.',
'enable_expiring_members' => 'Enable Member Expire',
'enable_expiring_members_instructions' => 'Members assigned to the Member Groups you assign will have accounts that expire after the set time limit based on the members creation date/time. ',

'unauthorized_ip_access' => 'Your IP is not allowed for accessing this site. Please speak to your site administrator to add you to the white list.',
'settings_updated' => 'Settings Updated',
'file_scan_path' => 'File Scan Path',
'file_scan_path_instructions' => 'Enter the full system path you want to scan for changes. If left blank will use your sites document root.',
'file_scan_exclude_paths' => 'Scan Exclude Paths',
'file_scan_exclude_paths_instructions' => 'Enter the full system path to the directories or files you want to exclude. This can useful for directories that change a lot like those for cache or uploaded media.',
'file_monitor_notify_emails' => 'Notify Email Addresses',
'file_monitor_notify_emails_instructions' => 'Who should be notified when changes to your system are detected. One email address per line; bad emails will be stripped.',
'security_scan' => 'Security Scan',
'fix_all_possible' => 'Update Config(s)',

'login_alert' => 'CP Login Alert',
'login_alert_emails' => 'Login Alert Notification Emails',
'login_alert_emails_instructions' => 'Who do you want to be notified when someone logs into your Control Panel? Put one email address per line; bad emails will be stripped.',

'cp_quick_deny_exclude_groups' => 'Exclude Groups',
'cp_quick_deny_exclude_groups_instructions' => 'Select the groups that you would like to deny access to the Control Panel to. Note that Super Admin and the current users group is not allowed (to prevent self lockouts).',
'cp_quick_deny_message_header' => 'Control Panel Login Disabled',
'cp_quick_deny_message_body' => 'Access to the control panel has temporarily been disabled by a system administrator. This is usually done during development or while troubleshooting site issues. Contact your system administrator for more information.',

'php_scan' => 'PHP Scan Results',
'check_config' => 'Config Scan Results',
'cp_scan' => 'CP Scan Results',
'version_control_scan' => 'Version Control Scan Results',

'encrypt_key' => 'Encryption Key Present?',
'encrypt_key_instructions' => 'Some third party add-ons may require an encryption key to function properly. See the CodeIgniter docs for the <a href="http://codeigniter.com/user_guide/libraries/encryption.html">Encryption</a> library for more details.',
'encrypt_key_length' => 'Encryption Key Minimum Length Met?',
'encrypt_key_length_instructions' => 'The Encryption Key should be at least 32 characters in length. ',
'deny_duplicate_data' => 'Deny Duplicate Data Enabled?',
'deny_duplicate_data_instructions' => 'Useful for keeping spammers and other nonsense at bay. This setting ensures there\'s no data being submitted that exactly matches another. <a href="##BASE##&C=admin_system&M=security_session_preferences">Change</a>',
'config_not_writable' => 'Is Config File Not Writable?',
'config_not_writable_instructions' => 'To add an extra layer of reliability it\'d be a good idea to make sure your config.php file isn\'t writable. Note that this will make changing certain settings through the admin impossible.',
'allow_username_change' => 'Allow Users to Change Username?',
'allow_username_change_instructions' => 'It\'s not a very good idea to allow your users to change their username because then accountability becomes tough and misleading when it comes to user activity. <a href="##BASE##&C=admin_system&M=security_session_preferences">Change</a>',
'allow_multi_logins' => 'Allow Multiple Logins?',
'allow_multi_logins_instructions' => 'This setting makes it easy for attackers to spoof attacks on your system. Setting it to No is recommended. <a href="##BASE##&C=admin_system&M=security_session_preferences">Change</a>',
'require_ip_for_login' => 'Require IP and User Agent For Login?',
'require_ip_for_login_instructions' => 'This setting requires that all requests to your ExpressionEngine site provide a valid IP address and User Agent. <a href="##BASE##&C=admin_system&M=security_session_preferences">Change</a>',
'require_ip_for_posting' => 'Require IP and User Agent For Posting?',
'require_ip_for_posting_instructions' => 'Just like the sister setting for logging in, this setting requires there be a valid IP address and User Agent sent when posting comments. <a href="##BASE##&C=admin_system&M=security_session_preferences">Change</a>',
'xss_clean_uploads' => 'XSS Clean Uploads?',
'xss_clean_uploads_instructions' => 'Enabling this setting will have ExpressionEngine parse uploaded files looking for anything malicous or evil. <a href="##BASE##&C=admin_system&M=security_session_preferences">Change</a>',
'password_lockout' => 'Password Lockout?',
'password_lockout_instructions' => 'This setting makes it so if a user doesn\'t login successfully after attempting 4 times they are locked out for a TBD amount of time. <a href="##BASE##&C=admin_system&M=security_session_preferences">Change</a>',
'password_lockout_interval' => 'Password Lockout Interval?',
'password_lockout_interval_instructions' => 'The Password Lockout Interval is the amount of time to require users to wait once their account has been locked. Setting this to more than 15 is recommended. <a href="##BASE##&C=admin_system&M=security_session_preferences">Change</a>',
'require_secure_passwords' => 'Require Secure Password?',
'require_secure_passwords_instructions' => 'Enabling this is just a good idea for every site. It requires every password created be made of at least 1 lowercase letter, 1 uppercase letter and 1 number. <a href="##BASE##&C=admin_system&M=security_session_preferences">Change</a>',
'allow_dictionary_pw' => 'Allow Dictionary Password?',
'allow_dictionary_pw_instructions' => 'Setting this to No will prevent users from choosing passwords that are easily hacked using what are called <a href="http://en.wikipedia.org/wiki/Dictionary_attack">Dictionary Attacks</a>. If Secure Passwords are required this setting is essentially moot. <a href="##BASE##&C=admin_system&M=security_session_preferences">Change</a>',
'pw_min_len'  => 'Minimum Password Length?',
'pw_min_len_instructions' => 'The more characters in a password the more secure it is; a minimum of 8 characters is recommended. <a href="##BASE##&C=admin_system&M=security_session_preferences">Change</a>',
'enable_throttling' => 'Enable Throttling?',
'enable_throttling_instructions' => 'Enabling the throttling controls is an extremely easy way to help protect your site from automated scripts and attacks. <a href="##BASE##&C=admin_system&M=throttling_configuration">Change</a>',
'max_page_loads' => 'Max Page Loads?',
'max_page_loads_instructions' => 'Used in conjunction with Time Interval this setting sets how many requests a user can have for any ExpressionEngine page. It\'s recommended that this setting be at least 20. <a href="##BASE##&C=admin_system&M=throttling_configuration">Change</a>',
'time_interval' => 'Time Interval?',
'time_interval_instructions' => 'The amount of time, in seconds, that the throttling checks are applied. It\'s recommended that this setting be set to at least 5. <a href="##BASE##&C=admin_system&M=throttling_configuration">Change</a>',
'lockout_time' => 'Lockout Time?',
'lockout_time_instructions' => 'How long should the lockout last when it\'s activated? Ideally, this should be set to more than 1800 seconds. <a href="##BASE##&C=admin_system&M=throttling_configuration">Change</a>',
'debug' => 'Debug?',
'debug_instructions' => 'Unless your site is under development the Debug settings should be turned off. <a href="##BASE##&C=admin_system&M=output_debugging_preferences">Change</a>',
'profile_trigger' => 'Profile Trigger',
'profile_trigger_instructions' => 'If you leave this in the defaul of "member", visitors to your site can access member lists and possibly reveal details about your members. <a href="##BASE##&C=members&M=member_config">Change</a>',
'admin_session_type' => 'Admin Session Type',
'admin_session_type_instructions' => 'This setting tells ExpressionEngine how it should authenticate users who visit the CP. For extra security it\'s recommended that you require both Cookie and Session. <a href="##BASE##&C=admin_system&M=security_session_preferences">Change</a>',
'user_session_type' => 'User Session Type',
'user_session_type_instructions' => 'Simliar to Admin Session Type, this setting tells ExpressionEngine how it should authenticate regular users of your site. For users it\'s generally accepted that Cookie only is sufficient. ',
'secure_forms' => 'Secure Forms',
'secure_forms_instructions' => 'Enabling Secure Forms will force ExpressionEngine to process the form using what it calls <a href="http://expressionengine.com/user_guide/general/spam_protection.html">Secure Mode</a> which helps to curtail spam bots and accidental submissions. ',
'cp_https' => 'Access CP under HTTPS',
'cp_https_instructions' => 'Accessing anything on the Internet without a secure connection is rarely a good idea much less your site\'s control panel. Be sure to install an SSL certificate and access your site using the https protocol.',
'cp_outside_root' => 'CP Outside Root',
'cp_outside_root_instructions' => 'One of the easiest things you can do to add a nice buffer of security around your site is move the "system" directory above the webroot. See <a href="http://expressionengine.com/user_guide/installation/best_practices.html">ExpressionEngine Best Practices</a> for more details.',
'cp_not_named_system' => 'CP Directory Name',
'cp_not_named_system_instructions' => 'It\'s a good idea to rename your "system" directory to something a little unique. This will keep automated requests from being able to find your Control Panel and other sensitive information. See <a href="http://expressionengine.com/user_guide/installation/best_practices.html">ExpressionEngine Best Practices</a> for more details. ',
'admin_not_named_admin' => 'CP File Name',
'admin_not_named_admin_instructions' => 'If you\'ve moved your system directory above your document root then you should rename admin.php to something else. See <a href="http://expressionengine.com/user_guide/installation/best_practices.html">ExpressionEngine Best Practices</a> for more details. ',
'cookie_prefix' => 'Cookie Prefix',
'cookie_prefix_instructions' => 'The cookie prefix is one of the only vectors within ExpressionEngine that give any hint that your site is built on top of ExpressionEngine. Setting a custom Cookie Prefix can help attackers from even knowing what your site is made with. <a href="##BASE##&C=admin_system&M=cookie_settings">Change</a>',
'db_prefix' => 'Database Prefix',
'db_prefix_instructions' => 'Your database prefix is still at the default setting within your database config file (database.php). For optimal results you should change it. Note that this may cause some add-ons to not function properly.',
'vz_bb_installed' => 'VZ Bad Behavior Installed',
'vz_bb_installed_instructions' => '<a href="http://devot-ee.com/add-ons/vz-bad-behavior">VZ Bad Behavior</a> is a free add-on that prevents automated bots from visiting your site. ',
'devotee_monitor_installed' => 'Devot:ee Monitor Installed',
'devotee_monitor_installed_instructions' => 'Add-ons are always getting new updates, usually with security improvements. The <a href="http://devot-ee.com/add-ons/devotee-monitor">Devot:ee Monitor</a> is a free add-on that will let you know when updates are available.',
'cp_ip_locker' => 'Control Panel IP Locker Enabled',
'cp_ip_locker_instructions' => 'For added security of accessing your ExpressionEngine control panel you should enable the IP locker. ',
'cp_login_alert' => 'Control Panel Login Alerts Enabled',
'cp_login_alert_instructions' => 'Securit:ee will let you know anytime anyone logs into your ExpressionEngine Control Panel. If it\'s enabled to that is. ',
'file_monitor' => 'File Monitor Enabled',
'file_monitor_instructions' => 'The Securit:ee File Monitor keeps a watch on your sites file system and will let you know when/if any changes are detected.',
'register_globals' => 'Register Globals',
'register_globals_instructions' => 'Register Global is an old setting in PHP that can open up some pretty serious security issues if enabled. Unless you need it, and with ExpressionEngine you don\'t, disable it in your php.ini.',
'disable_url_fopen' => 'Disable URL fopen()',
'disable_url_fopen_instructions' => 'This option enables the URL-aware fopen wrappers that are used for accessing URL object like files. Unless you need it, and with ExpressionEngine you don\'t, disable it in your php.ini.',
'php_version' => 'PHP Version Up To Date',
'php_version_instructions' => 'This checks to make sure that your PHP version is up to the latest, stable, version of 5.3.8. Your installed version is: '.PHP_VERSION,
'disable_passthru' => 'Passthru Disabled',
'disable_passthru_instructions' => 'The <code>passthru</code> function allows system commands to be executed with PHP. Unless you need this function, and ExpressionEngine does not, you should disable it with the <code>disable_functions</code> within your php.ini. See the <a href="http://www.php.net/manual/en/function.passthru.php">PHP manual</a> for more details.',
'disable_shell_exec' => 'Shell Exec Disabled',
'disable_shell_exec_instructions' => 'The <code>shell_exec</code> function allows system commands to be executed with PHP. Unless you need this function, and ExpressionEngine does not, you should disable it with the <code>disable_functions</code> within your php.ini. See the <a href="http://www.php.net/manual/en/function.shell-exec.php">PHP manual</a> for more details.',
'disable_system' => 'System Disabled',
'disable_system_instructions' => 'The <code>system</code> function allows system commands to be executed with PHP. Unless you need this function, and ExpressionEngine does not, you should disable it with the <code>disable_functions</code> within your php.ini. See the <a href="http://php.net/manual/en/function.system.php">PHP manual</a> for more details.',
'disable_proc_open' => 'Proc Open Disabled',
'disable_proc_open_instructions' => 'The <code>proc_open</code> function allows system commands to be executed with PHP. Unless you need this function, and ExpressionEngine does not, you should disable it with the <code>disable_functions</code> within your php.ini. See the <a href="http://php.net/manual/en/function.proc-open.php">PHP manual</a> for more details.',
'disable_exec' => 'Exec Disabled',
'disable_exec_instructions' => 'The <code>exec</code> function allows system commands to be executed with PHP. ExpressionEngine does use this function for a couple things like file management and image manipulation but it\'s not required. See the <a href="http://php.net/manual/en/function.exec.php">PHP manual</a> for more details.',
'disable_popen' => 'Popen Disabled',
'disable_popen_instructions' => 'The <code>popen</code> function allows system commands to be executed with PHP. ExpressionEngine uses this function to send email when configured to use the sendmail protocol. If your site isn\'t configured to use sendmail for sending email it\'d be best to disable this function. See the <a href="http://php.net/manual/en/function.popen.php">PHP manual</a> for more details.',
'disable_show_source' => 'Show Source Disabled',
'disable_show_source_instructions' => 'The <code>show_source</code> function allows system commands to be executed with PHP. Unless you need this function, and ExpressionEngine does not, you should disable it with the <code>disable_functions</code> within your php.ini. See the <a href="http://php.net/manual/en/function.exec.php">PHP manual</a> for more details.',
'disable_dl' => 'Disable DL',
'disable_dl_instructions' => 'Disabling the dl() function prevents scripts from loading PHP modules from within a script. ExpressionEngine does make use of it if the GD Image library isn\'t installed but, considering since PHP has GD enabled by default, disabling dl() is recommended. See the <a href="http://php.net/manual/en/function.dl.php">PHP manual</a> for more details.',

'disable_apache_note' => 'Disable Apache Note',
'disable_apache_note_instructions' => 'The <code>apache_note</code> function allows system commands to be executed with PHP and the underlying system. Unless you need this function, and ExpressionEngine does not, you should disable it with the <code>disable_functions</code> within your php.ini. See the <a href="http://php.net/manual/en/function.apache-note.php">PHP manual</a> for more details.',
'disable_apache_setenv' => 'Disable Apache SetEnv',
'disable_apache_setenv_instructions' => 'The <code>apache_setenv</code> function allows system commands to be executed with PHP and the underlying system. Unless you need this function, and ExpressionEngine does not, you should disable it with the <code>disable_functions</code> within your php.ini. See the <a href="http://php.net/manual/en/function.apache-setenv.php">PHP manual</a> for more details.',
'disable_pcntl_exec' => 'Disable Pcntl Exec',
'disable_pcntl_exec_instructions' => 'The <code>pcntl_exec</code> function allows system commands to be executed with PHP and the underlying system. Unless you need this function, and ExpressionEngine does not, you should disable it with the <code>disable_functions</code> within your php.ini. See the <a href="http://php.net/manual/en/function.pcntl-exec.php">PHP manual</a> for more details.',
'disable_proc_close' => 'Disable Proc Close',
'disable_proc_close_instructions' => 'The <code>proc_close</code> function will close a process opened by <code>proc_open()</code> and return the exit code of that process. Unless you need this function, and ExpressionEngine does not, you should disable it with the <code>disable_functions</code> within your php.ini. See the <a href="http://php.net/manual/en/function.proc-close.php">PHP manual</a> for more details.',
'disable_proc_get_status' => 'Disable Proc Get Status',
'disable_proc_get_status_instructions' => 'The <code>proc_get_status</code> function will get information about a process opened by <code>proc_open()</code>. Unless you need this function, and ExpressionEngine does not, you should disable it with the <code>disable_functions</code> within your php.ini. See the <a href="http://php.net/manual/en/function.proc-get-status.php">PHP manual</a> for more details.',
'disable_proc_terminate' => 'Proc Terminate Disabled',
'disable_proc_terminate_instructions' => 'The <code>proc_terminate</code> function kills a process opened by <code>proc_open</code>. Unless you need this function, and ExpressionEngine does not, you should disable it with the <code>disable_functions</code> within your php.ini. See the <a href="http://php.net/manual/en/function.apache-note.php">PHP manual</a> for more details.',
'disable_putenv' => 'Putenv Disabled',
'disable_putenv_instructions' => 'The <code>putenv</code> function allows environment settings to be set. Unless you need this function, and ExpressionEngine does not, you should disable it with the <code>disable_functions</code> within your php.ini. See the <a href="http://php.net/manual/en/function.putenv.php">PHP manual</a> for more details.',
'disable_virtual' => 'Disable Virtual',
'disable_virtual_instructions' => 'The <code>virtual</code> function allows modification of the underlying system. Unless you need this function, and ExpressionEngine does not, you should disable it with the <code>disable_functions</code> within your php.ini. See the <a href="http://php.net/manual/en/function.virtual.php">PHP manual</a> for more details.',
'disable_openlog' => 'Disable Openlog',
'disable_openlog_instructions' => 'The <code>openlog</code> function allows modification to the system logs which an attacker could use to cover their tracks. Unless you need this function, and ExpressionEngine does not, you should disable it with the <code>disable_functions</code> within your php.ini. See the <a href="http://php.net/manual/en/function.openlog.php">PHP manual</a> for more details.',
'disable_proc_nice' => 'Disable Proc Nice',
'disable_proc_nice_instructions' => 'The <code>proc_nice</code> function allows the priority of current process to be changed. Unless you need this function, and ExpressionEngine does not, you should disable it with the <code>disable_functions</code> within your php.ini. See the <a href="http://php.net/manual/en/function.proc-nice.php">PHP manual</a> for more details.',
'disable_expose_php' => 'Disable Expose PHP',
'disable_expose_php_instructions' => 'Your server is set to tell the world that it\'s running PHP. An attacker can use this to figure out what security vulnerabilities are available to take over your system. Unless you need this set, and ExpressionEngine does not, you should disable it within your php.ini. See the <a href="http://www.php.net/manual/en/ini.core.php#ini.expose-php">PHP manual</a> for more details.',
'disable_syslog' => 'Disable Syslog',
'disable_syslog_instructions' => 'The <code>syslog</code> function allows modification of the system logs. Unless you need this function, and ExpressionEngine does not, you should disable it with the <code>disable_functions</code> within your php.ini. See the <a href="http://php.net/manual/en/function.syslog.php">PHP manual</a> for more details.',
'disable_splFileObject' => 'Disable splFileObject',
'disable_splFileObject_instructions' => 'The <code>splFileObject</code> class can be used to execute system commands to the underlying system. Unless you need this class, and ExpressionEngine does not, you should disable it with the <code>disable_functions</code> within your php.ini. See the <a href="http://www.php.net/manual/en/class.splfileobject.php">PHP manual</a> for more details.',
'disable_phpinfo' => 'Disable PHP Info',
'disable_phpinfo_instructions' => 'The <code>phpinfo</code> function displays information about your PHP configuration. Unless you need this function, and ExpressionEngine does not, you should disable it with the <code>disable_functions</code> within your php.ini. See the <a href="http://php.net/manual/en/function.phpinfo.php">PHP manual</a> for more details.',
'disable_get_loaded_extensions' => 'Disable Get Loaded Extensions',
'disable_phpinfo_instructions' => 'The <code>get_loaded_extensions</code> function displays information about your PHP configuration. Unless you need this function, and ExpressionEngine does not, you should disable it with the <code>disable_functions</code> within your php.ini. See the <a href="http://php.net/manual/en/function.get-loaded-extensions.php">PHP manual</a> for more details.',

'enable_open_basedir' => 'Disable Get Loaded Extensions',
'enable_open_basedir_instructions' => 'Your server is set to tell the world that it\'s running PHP. An attacker can use this to figure out what security vulnerabilities are available to take over your system. Unless you need this set, and ExpressionEngine does not, you should disable it within your php.ini. See the <a href="http://www.php.net/manual/en/ini.core.php#ini.expose-php">PHP manual</a> for more details',

'version_control_free' => 'Version Control Crudge',
'version_control_free_instructions' => 'Leaving your version control repository files can make it easier for potential attackers to gain insight into your site and code. It\'s best to never allow them in a production environment.',
'template_debugging' => 'Template Debugging Disabled?',

'file_monitor_notify' => 'File Monitor Alert',
'file_monitor_notify_message' => 'There have been changes to files on your site: ',
'file_monitor_notify_message_footer' => 'Securit:ee :)',
'file_monitor_cron_command' => 'File Monitor Cron Command',
'file_monitor_cron_command_instructions' => 'This is the command to use with your systems internal Cron to execute the file monitor. ',
'login_alert_notify_message' => '##username## has logged into your control panel at ##date## from ##ip##! You might want to investigate if this is an issue.',

'license_number' => 'License Number',
'missing_license_number' => 'Please enter your license number. <a href="#config_url#">Enter License</a> or <a href="http://devot-ee.com/add-ons/securitee/">Buy A License</a>',
'allowed_ips' => 'Allowed Ip Addresses',
'allowed_ips_instructions' => 'Enter one IP Address per line. Any bad IP addresses will be stripped.',
'log_file_monitor_cleared' => 'File Monitor Cleared',
'log_file_monitor_clear_fail' => 'File Monitor Clear Faild...',

'decrypt_access' => 'Decrypt Access',
'decrypt_access_instructions' => 'By default the value will only be decrypted for Super Admins but if you need other groups to view the value select them here.',
'display_type' => 'Display Type',
'display_type_instructions' => 'What field type do you want to use on the form. ',
'hidden_text' => 'Hidden Text',
'hidden_text_instructions' => 'What\'s the placeholder to use if the value shouldn\'t be decrypted? ',

'exploit_scan' => 'Exploit Scan',
'configure_exploit_scan' => 'Configure Exploit Scan',
'exploit_scan_in_progress' => 'Exploit Scan In Progress...',
'exploit_scan_in_progress_instructions' => '',
'exploit_scan_path' => 'Exploit Scan Path',
'exploit_scan_path_instructions' => 'The path you want to use for the file exploit scan. Put the full system path. ',
'enable_exploit_scanner' => 'Enable Exploit Scan',
'exploit_scan_exclude_paths' => 'Exploit Scan Exclude Paths',
'exploit_scan_exclude_paths_instructions' => 'Enter the full system path to the directories or files you want to exclude. One path or file per line.',
'enable_exploit_scanner_instructions' => 'The Exploit Scanner will actively scan your file system and database looking for suspicious patterns and will inform you if any are found.',
'cant_send' => 'Couldn\'t send email...',
'email_not_exist' => 'The email doesn\'t exist...',
'email_not_allowed' => 'The account can\'t do this...',
'username_not_exist' => 'The username doesn\'t exist',
'bad_hash' => 'The hash is invalid',
'forgot_password_email_subject' => 'Password Recovery',
'forgot_password_email_message' => '{username},

To reset your password for your account, click the link below:

{change_url}
		
Copy and paste the URL in a new browser window if you can\'t click on it.

Please keep in mind that the link will only work for 24 hours; after that it will be inactive.

If you didn\'t request to reset your password you don\'t need to take any further action and can safely disregard this email.

{site_name}
{site_url}	

Please don\'t respond to this email; all emails are automatically deleted. 		
',
'pw_ttl' => 'Forgot Password Expire Time',
'pw_ttl_instructions' => 'Set how long the link within the Securit:ee forgot password email should last. The custom time should be in seconds. If you change this be sure to update the email.',
'pw_email_message' => 'Forgot Password Email Message',
'pw_email_message_instructions' => 'The email copy that gets sent with a forgot password request. You can use global template tags but be sure to include {change_url}.',
'pw_expire_ttl' => 'Member Password Expire Time',
'pw_expire_ttl_instructions' => 'How long should a password be active before it must be changed. The custom time should be in seconds.',
'pw_expire_member_groups' => 'Member Groups To Expire',
'pw_expire_member_groups_instructions' => 'What groups, if any, that will have expiring passwords.',
'pw_email_subject' => 'Forgot Password Email Subject',
'pw_email_subject_instructions' => 'The subject you want the Forgot Password email to have. You can use global template tags but nothing fancy.',
'pw_email_mailtype_instructions' => 'Type of mail email message the Forgot Password email should be sent in. If you send HTML email you must send it as a complete web page. Make sure you don\'t have any relative links or relative image paths otherwise they will not work.',
'pw_email_mailtype' => 'Email Format',

'missing_change_template' => 'You must provide a change_template parameter :(',

'cp_reg_email_message' => 'CP Member Registration Email Message',
'cp_reg_email_expire_ttl' => 'Registration Email Expire Time',
'cp_reg_email_expire_ttl_instructions' => 'Set how long the link within the CP member registration email should last. The custom time should be in seconds. If you change this be sure to update the email.',
'cp_reg_email_message_instructions' => 'The email copy that gets sent when a member gets created in the Control Panel. You can use global template tags but be sure to include {change_url}. If you use HTML in your message be sure to change the mailtype.',
'cp_reg_email_mailtype_instructions' => 'Type of mail email message the registration email should be sent in. If you send HTML email you must send it as a complete web page. Make sure you don\'t have any relative links or relative image paths otherwise they will not work.',
'cp_reg_email_mailtype' => 'Email Format',
'cp_reg_email_message_body' => 'Hello,

A new account was created on {site_url} for you. To begin using your account click the below link and create a password. 

{change_url}
		
Copy and paste the URL in a new browser window if you can\'t click on it.

Please keep in mind that the link will only work for 24 hours; after that it will be inactive.

{site_name}
{site_url}	

Please don\'t respond to this email; all emails are automatically deleted. 	',
'cp_reg_member_email_subject' => '{site_name} - Account Created',
'cp_reg_email_subject' => 'CP Member Registration Email Subject',
'cp_reg_email_subject_instructions' => 'The subject you want the registration email to have. You can use global template tags but nothing fancy.',
'configure_members' => 'Configure Members',
'configure_forgot_pw' => 'Configure Forgot Password',
'configure_pw_expire' => 'Configure Password Expire',
'pw_change_template' => 'Change Password Template',
'pw_change_template_instructions' => 'In the CP the user is directed to the Username/Password page but on the front site you have to set where to change the password. Choose the template here.',
'password_expired' => 'Your password has expired. You must set a new one to proceed.',

'configure_member_expire' => 'Configure Member Expire',
'member_expire_ttl' => 'Member Account Expire Time',
'member_expire_ttl_instructions' => 'Set how long the user group should be active before expiring. The custom time should be in seconds.',
'member_expire_member_groups' => 'Member Accounts To Expire',
'member_expire_member_groups_instructions' => 'What groups, if any, you want to expire.',
'member_account_expired_error' => 'This account has expired and you are no longer able to continue using this site.',
'validate_must_change_password' => 'Your new password must be different from your current password.',
'unauthorized_ip_access_allow_instructions' => 'Your IP is not allowed for accessing this site. Please enter your email address to confirm access.',

'allow_ip_email_message_body' => 'Hello,

To confirm your IP please click the below URL:

{allow_url}

Copy and paste the URL in a new browser window if you can\'t click on it.

Please keep in mind that the link will only work for 24 hours; after that it will be inactive.

{site_name}
{site_url}

Please don\'t respond to this email; all emails are automatically deleted. 	',
'allow_ip_email_subject_copy' => '{site_name} - IP Allow Request',
'allow_ip_email_subject' => 'IP Allow Email Subject',
'allow_ip_email_subject_instructions' => 'The subject you want the Allow IP email to have. You can use global template tags but nothing fancy.',
'allow_ip_email_message' => 'Allow IP Email Message',
'allow_ip_email_message_instructions' => 'The email copy that gets sent with an allow IP request. You can use global template tags but be sure to include {allow_url}.',
'allow_ip_email_mailtype' => 'Email Format',
'allow_ip_email_mailtype_instructions' => 'Type of mail email message the Allow IP email should be sent in. If you send HTML email you must send it as a complete web page. Make sure you don\'t have any relative links or relative image paths otherwise they will not work.',
'allow_ip_template' => 'Allow IP Template',
'allow_ip_template_instructions' => 'The template on the front site the Allow IP form tags are used. The user will be forced to see only this template if set. ',
'allow_ip_add_member_groups' => 'Add IP Member Groups',
'allow_ip_add_member_groups_instructions' => 'What member groups have the ability to add themselves to the white list.',
'allow_ip_ttl' => 'Allow IP Expire Time',
'allow_ip_ttl_instructions' => 'Set how long the link within the Allow IP email should last. The custom time should be in seconds. If you change this be sure to update the email.',
'version_control_scan_found_locations' => 'Found Locations',
'version_control_scan_found_locations_instructions' => 'Below are the found Version Control locations within your file system. Be aware; Securit:ee only searches within the value set for the File Monitor. You should ensure that the below files aren\'t pushed to production.',

'field_max_length' => 'Field Max Length',
'clear_file_monitor_history' => 'Clear File Monitor History',
'clear_file_monitor_history' => 'Clear File Monitor History',
'clear_file_monitor_history' => 'Clear File Monitor History',

'enable_cp_reg_email_on_activate' => 'Enable CP Member Account Activation Email',
'enable_cp_reg_email_on_activate_instructions' => 'Should the email be sent on <a href="##BASE##&C=members&M=member_validation">Activate Pending</a> module from within the Control Panel? If checked, Securit:ee will send the email to all members when activated. Uses the CP Registration email.',
''=>''
);