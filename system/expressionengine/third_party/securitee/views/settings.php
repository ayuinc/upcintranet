<?php $this->load->view('errors', array('disable_accordions' => $disable_accordions)); ?>
<?php 

$tmpl = array (
	'table_open'          => '<table class="mainTable" border="0" cellspacing="0" cellpadding="0">',

	'row_start'           => '<tr class="even">',
	'row_end'             => '</tr>',
	'cell_start'          => '<td style="width:50%;">',
	'cell_end'            => '</td>',

	'row_alt_start'       => '<tr class="odd">',
	'row_alt_end'         => '</tr>',
	'cell_alt_start'      => '<td>',
	'cell_alt_end'        => '</td>',

	'table_close'         => '</table>'
);

$this->table->set_template($tmpl); 
$this->table->set_empty("&nbsp;");
?>
<div class="clear_left shun"></div>

<?php echo form_open($query_base.'settings', array('id'=>'my_accordion'))?>
<input type="hidden" value="yes" name="go_settings" />
<h3  class="accordion"><?php echo lang('configure_securitee')?></h3>
<div>
	<?php 
	
	$this->table->set_heading('Setting','Value');		
	$this->table->add_row('<label for="enable_file_monitor">'.lang('enable_file_monitor').'</label><div class="subtext">'.lang('enable_file_monitor_instructions').'</div>', form_checkbox('enable_file_monitor', '1', $settings['enable_file_monitor'], 'id="enable_file_monitor"' . $settings_disable));
	//$this->table->add_row('<label for="enable_exploit_scanner">'.lang('enable_exploit_scanner').'</label><div class="subtext">'.lang('enable_exploit_scanner_instructions').'</div>', form_checkbox('enable_exploit_scanner', '1', $settings['enable_exploit_scanner'], 'id="enable_exploit_scanner"'));
	$this->table->add_row('<label for="enable_cp_login_alert">'.lang('enable_cp_login_alert').'</label><div class="subtext">'.lang('enable_cp_login_alert_instructions').'</div>', form_checkbox('enable_cp_login_alert', '1', $settings['enable_cp_login_alert'], 'id="enable_cp_login_alert"' . $settings_disable));
	$this->table->add_row('<label for="enable_cp_ip_locker">'.lang('enable_cp_ip_locker').'</label><div class="subtext">'.lang('enable_cp_ip_locker_instructions').'</div>', form_checkbox('enable_cp_ip_locker', '1', $settings['enable_cp_ip_locker'], 'id="enable_cp_ip_locker"' . $settings_disable));
	$this->table->add_row('<label for="enable_quick_deny_cp_login">'.lang('enable_quick_deny_cp_login').'</label><div class="subtext">'.lang('enable_quick_deny_cp_login_instructions').'</div>', form_checkbox('enable_quick_deny_cp_login', '1', $settings['enable_quick_deny_cp_login'], 'id="enable_quick_deny_cp_login"' . $settings_disable));
	$this->table->add_row('<label for="enable_client_ip_locker">'.lang('enable_client_ip_locker').'</label><div class="subtext">'.lang('enable_client_ip_locker_instructions').'</div>', form_checkbox('enable_client_ip_locker', '1', $settings['enable_client_ip_locker'], 'id="enable_client_ip_locker"' . $settings_disable));
	$this->table->add_row('<label for="enable_expiring_passwords">'.lang('enable_expiring_passwords').'</label><div class="subtext">'.lang('enable_expiring_passwords_instructions').'</div>', form_checkbox('enable_expiring_passwords', '1', $settings['enable_expiring_passwords'], 'id="enable_expiring_passwords"'. $settings_disable));
	$this->table->add_row('<label for="enable_expiring_members">'.lang('enable_expiring_members').'</label><div class="subtext">'.lang('enable_expiring_members_instructions').'</div>', form_checkbox('enable_expiring_members', '1', $settings['enable_expiring_members'], 'id="enable_expiring_members"'. $settings_disable));
	$this->table->add_row('<label for="enable_cp_member_reg_email">'.lang('enable_cp_member_reg_email').'</label><div class="subtext">'.lang('enable_cp_member_reg_email_instructions').'</div>', form_checkbox('enable_cp_member_reg_email', '1', $settings['enable_cp_member_reg_email'], 'id="enable_cp_member_reg_email"'.$settings_disable));
	$this->table->add_row('<label for="enable_cp_email_activate">'.lang('enable_cp_reg_email_on_activate').'</label><div class="subtext">'.str_replace('##BASE##', BASE, lang('enable_cp_reg_email_on_activate_instructions')).'</div>', form_checkbox('enable_cp_email_activate', '1', $settings['enable_cp_email_activate'], 'id="enable_cp_email_activate"'.$settings_disable));
	
	echo $this->table->generate();
	$this->table->clear();	
	?>
</div>

<h3  class="accordion" id="configure_member_expire"><?php echo lang('configure_member_expire')?></h3>
<div>
	<?php 
	$this->table->set_heading('Setting','Value');
	
	$settings['member_expire_ttl_custom'] = $settings['allow_ip_ttl_custom'] = $settings['pw_expire_ttl_custom'] = $settings['cp_reg_email_expire_ttl_custom'] = $settings['pw_ttl_custom'] = '';
	if(!isset($ttl_options[$settings['member_expire_ttl']]))
	{
		$settings['member_expire_ttl_custom'] = $settings['member_expire_ttl'];
		$settings['member_expire_ttl'] = 'custom';
	}
	
	$this->table->add_row('<label for="member_expire_ttl">'.lang('member_expire_ttl').'</label><div class="subtext">'.lang('member_expire_ttl_instructions').'</div>', form_dropdown('member_expire_ttl', $ttl_options, $settings['member_expire_ttl'], 'id="member_expire_ttl"'). form_error('member_expire_ttl'). form_input('member_expire_ttl_custom', $settings['member_expire_ttl_custom'], 'id="member_expire_ttl_custom" style="display:none; width:40%; margin-left:10px;"'. $settings_disable));
	$this->table->add_row('<label for="member_expire_member_groups">'.lang('member_expire_member_groups').'</label><div class="subtext">'.lang('member_expire_member_groups_instructions').'</div>', form_multiselect('member_expire_member_groups[]', $pw_member_groups, $settings['member_expire_member_groups'], 'id="member_expire_member_groups"'. $settings_disable));
	echo $this->table->generate();
	$this->table->clear();	
	?>
</div>

<h3  class="accordion" id="configure_pw_expire"><?php echo lang('configure_pw_expire')?></h3>
<div>
	<?php 
	$this->table->set_heading('Setting','Value');
	//$settings['pw_expire_ttl_custom'] = $settings['cp_reg_email_expire_ttl_custom'] = $settings['pw_ttl_custom'] = '';
	if(!isset($ttl_options[$settings['pw_expire_ttl']]))
	{
		$settings['pw_expire_ttl_custom'] = $settings['pw_expire_ttl'];
		$settings['pw_expire_ttl'] = 'custom';
	}
	
	$this->table->add_row('<label for="pw_expire_ttl">'.lang('pw_expire_ttl').'</label><div class="subtext">'.lang('pw_expire_ttl_instructions').'</div>', form_dropdown('pw_expire_ttl', $ttl_options, $settings['pw_expire_ttl'], 'id="pw_expire_ttl"'). form_error('pw_expire_ttl'). form_input('pw_expire_ttl_custom', $settings['pw_expire_ttl_custom'], 'id="pw_expire_ttl_custom" style="display:none; width:40%; margin-left:10px;"'. $settings_disable));
	
	$this->table->add_row('<label for="pw_expire_member_groups">'.lang('pw_expire_member_groups').'</label><div class="subtext">'.lang('pw_expire_member_groups_instructions').'</div>', form_multiselect('pw_expire_member_groups[]', $pw_member_groups, $settings['pw_expire_member_groups'], 'id="pw_expire_member_groups"'. $settings_disable));
	$this->table->add_row('<label for="pw_change_template">'.lang('pw_change_template').'</label><div class="subtext">'.lang('pw_change_template_instructions').'</div>', form_dropdown('pw_change_template', $template_options, $settings['pw_change_template'], 'id="pw_change_template"' . $settings_disable));
	
	echo $this->table->generate();
	$this->table->clear();	
	?>
</div>

<h3  class="accordion" id="configure_cp_reg_email"><?php echo lang('configure_cp_reg_email')?></h3>
<div>
	<?php 
	$this->table->set_heading('Setting','Value');
	if(!isset($ttl_options[$settings['cp_reg_email_expire_ttl']]))
	{
		$settings['cp_reg_email_expire_ttl_custom'] = $settings['cp_reg_email_expire_ttl'];
		$settings['cp_reg_email_expire_ttl'] = 'custom';
	}	

		
	//$this->table->add_row('<label for="cp_reg_email_on_activate">'.lang('cp_reg_email_on_activate').'</label><div class="subtext">'.lang('cp_reg_email_on_activate_instructions').'</div>', form_checkbox('cp_reg_email_on_activate', 1, $settings['cp_reg_email_on_activate'], 'id="cp_reg_email_on_activate"'). $settings_disable);
		
	$this->table->add_row('<label for="cp_reg_email_expire_ttl">'.lang('cp_reg_email_expire_ttl').'</label><div class="subtext">'.lang('cp_reg_email_expire_ttl_instructions').'</div>', form_dropdown('cp_reg_email_expire_ttl', $ttl_options, $settings['cp_reg_email_expire_ttl'], 'id="cp_reg_email_expire_ttl"'). form_error('schedule'). form_input('cp_reg_email_expire_ttl_custom', $settings['cp_reg_email_expire_ttl_custom'], 'id="cp_reg_email_expire_ttl_custom" style="display:none; width:40%; margin-left:10px;"'. $settings_disable));
	
	$this->table->add_row('<label for="cp_reg_email_subject">'.lang('cp_reg_email_subject').'</label><div class="subtext">'.lang('cp_reg_email_subject_instructions').'</div>', form_input('cp_reg_email_subject', $settings['cp_reg_email_subject'], 'id="cp_reg_email_subject"'. $settings_disable));
	$this->table->add_row('<label for="cp_reg_email_message_body">'.lang('cp_reg_email_message').'</label><div class="subtext">'.lang('cp_reg_email_message_instructions').'</div>', form_textarea('cp_reg_email_message_body', $settings['cp_reg_email_message_body'], 'cols="90" rows="6"' . $settings_disable));
	$this->table->add_row('<label for="cp_reg_email_mailtype">'.lang('cp_reg_email_mailtype').'</label><div class="subtext">'.lang('cp_reg_email_mailtype_instructions').'</div>', form_dropdown('cp_reg_email_mailtype', $email_format_options, $settings['cp_reg_email_mailtype'], 'id="cp_reg_email_mailtype"'));
		
	echo $this->table->generate();
	$this->table->clear();	
	?>
</div>

<h3  class="accordion" id="configure_forgot_pw"><?php echo lang('configure_forgot_pw')?></h3>
<div>
	<?php 
	$this->table->set_heading('Setting','Value');
	if(!isset($ttl_options[$settings['pw_ttl']]))
	{
		$settings['pw_ttl_custom'] = $settings['pw_ttl'];
		$settings['pw_ttl'] = 'custom';
	}	
	
	$this->table->add_row('<label for="pw_ttl">'.lang('pw_ttl').'</label><div class="subtext">'.lang('pw_ttl_instructions').'</div>', form_dropdown('pw_ttl', $ttl_options, $settings['pw_ttl'], 'id="pw_ttl"'). form_error('pw_ttl'). form_input('pw_ttl_custom', $settings['pw_ttl_custom'], 'id="pw_ttl_custom" style="display:none; width:40%; margin-left:10px;"'. $settings_disable));	
	$this->table->add_row('<label for="pw_email_subject">'.lang('pw_email_subject').'</label><div class="subtext">'.lang('pw_email_subject_instructions').'</div>', form_input('forgot_password_email_subject', $settings['forgot_password_email_subject'], 'id="pw_email_subject"'. $settings_disable));
	$this->table->add_row('<label for="pw_email_message">'.lang('pw_email_message').'</label><div class="subtext">'.lang('pw_email_message_instructions').'</div>', form_textarea('pw_email_message', $settings['pw_email_message'], 'cols="90" rows="6"'. $settings_disable));
	$this->table->add_row('<label for="pw_email_mailtype">'.lang('pw_email_mailtype').'</label><div class="subtext">'.lang('pw_email_mailtype_instructions').'</div>', form_dropdown('pw_email_mailtype', $email_format_options, $settings['pw_email_mailtype'], 'id="pw_email_mailtype"'));
	
	echo $this->table->generate();
	$this->table->clear();	
	?>
</div>

<h3  class="accordion" id="configure_file_monitor"><?php echo lang('configure_file_monitor')?></h3>
<div>
	<?php 
	$this->table->set_heading('Setting','Value');

	//$this->table->add_row('<label for="modification_detection">'.lang('modification_detection').'</label><div class="subtext">'.lang('modification_detection_instructions').'</div>', form_input('modification_detection', $settings['modification_detection'], 'id="modification_detection"'));
	$this->table->add_row('<label for="file_scan_path">'.lang('file_scan_path').'</label><div class="subtext">'.lang('file_scan_path_instructions').'</div>', form_input('file_scan_path', $settings['file_scan_path'], 'id="file_scan_path"'. $settings_disable));
	$this->table->add_row('<label for="file_scan_exclude_paths">'.lang('file_scan_exclude_paths').'</label><div class="subtext">'.lang('file_scan_exclude_paths_instructions').'</div>', form_textarea('file_scan_exclude_paths', implode("\n", $settings['file_scan_exclude_paths']), 'cols="90" rows="6"'. $settings_disable));
	$this->table->add_row('<label for="file_monitor_notify_emails">'.lang('file_monitor_notify_emails').'</label><div class="subtext">'.lang('file_monitor_notify_emails_instructions').'</div>', form_textarea('file_monitor_notify_emails', implode("\n", $settings['file_monitor_notify_emails']), 'cols="90" rows="6"'. $settings_disable));
	$this->table->add_row('<label>'.lang('file_monitor_cron_command').'</label><div class="subtext">'.lang('file_monitor_cron_command_instructions').'</div>', 'wget '.$cron_url.' >/dev/null 2>&1');
	
	echo $this->table->generate();
	$this->table->clear();	
	?>
</div>

<!-- 
<h3  class="accordion" id="configure_exploit_scan"><?php echo lang('configure_exploit_scan')?></h3>
<div>
	<?php 
	$this->table->set_heading('Setting','Value');

	//$this->table->add_row('<label for="modification_detection">'.lang('modification_detection').'</label><div class="subtext">'.lang('modification_detection_instructions').'</div>', form_input('modification_detection', $settings['modification_detection'], 'id="modification_detection"'));
	$this->table->add_row('<label for="exploit_scan_path">'.lang('exploit_scan_path').'</label><div class="subtext">'.lang('exploit_scan_path_instructions').'</div>', form_input('exploit_scan_path', $settings['exploit_scan_path'], 'id="exploit_scan_path"' . $settings_disable));
	$this->table->add_row('<label for="exploit_scan_exclude_paths">'.lang('exploit_scan_exclude_paths').'</label><div class="subtext">'.lang('exploit_scan_exclude_paths_instructions').'</div>', form_textarea('exploit_scan_exclude_paths', implode("\n", $settings['exploit_scan_exclude_paths']), 'cols="90" rows="6"'. $settings_disable));
	$this->table->add_row('<label for="exploit_scan_notify_emails">'.lang('exploit_scan_notify_emails').'</label><div class="subtext">'.lang('exploit_scan_notify_emails_instructions').'</div>', form_textarea('file_monitor_notify_emails', implode("\n", $settings['exploit_scan_notify_emails']), 'cols="90" rows="6"'. $settings_disable));
	$this->table->add_row('<label>'.lang('exploit_scan_cron_command').'</label><div class="subtext">'.lang('exploit_scan_cron_command_instructions').'</div>', 'wget '.$cron_url.' >/dev/null 2>&1');
	
	echo $this->table->generate();
	$this->table->clear();	
	?>
</div>
 -->
 
<h3  class="accordion" id="configure_ip_locker"><?php echo lang('configure_ip_locker')?></h3>
<div>
	<?php	
	if(!isset($ttl_options[$settings['allow_ip_ttl']]))
	{
		$settings['allow_ip_ttl_custom'] = $settings['allow_ip_ttl'];
		$settings['allow_ip_ttl'] = 'custom';
	}
	
	$this->table->set_heading('Setting','Value');
	$this->table->add_row('<label for="allowed_ips">'.lang('allowed_ips').'</label><div class="subtext">'.lang('allowed_ips_instructions').'</div>', form_textarea('allowed_ips', implode("\n", $settings['allowed_ips']), 'cols="90" rows="6" id="allowed_ips"'. $settings_disable));
	$this->table->add_row('<label for="allow_ip_ttl">'.lang('allow_ip_ttl').'</label><div class="subtext">'.lang('allow_ip_ttl_instructions').'</div>', form_dropdown('allow_ip_ttl', $ttl_options, $settings['allow_ip_ttl'], 'id="allow_ip_ttl"'). form_error('allow_ip_ttl'). form_input('allow_ip_ttl_custom', $settings['allow_ip_ttl_custom'], 'id="allow_ip_ttl_custom" style="display:none; width:40%; margin-left:10px;"'. $settings_disable));	
	$this->table->add_row('<label for="allow_ip_email_subject">'.lang('allow_ip_email_subject').'</label><div class="subtext">'.lang('allow_ip_email_subject_instructions').'</div>', form_input('allow_ip_email_subject', $settings['allow_ip_email_subject'], 'id="allow_ip_email_subject"'. $settings_disable));
	$this->table->add_row('<label for="allow_ip_email_message">'.lang('allow_ip_email_message').'</label><div class="subtext">'.lang('allow_ip_email_message_instructions').'</div>', form_textarea('allow_ip_email_message', $settings['allow_ip_email_message'], 'cols="90" rows="6"'. $settings_disable));
	$this->table->add_row('<label for="allow_ip_email_mailtype">'.lang('allow_ip_email_mailtype').'</label><div class="subtext">'.lang('allow_ip_email_mailtype_instructions').'</div>', form_dropdown('allow_ip_email_mailtype', $email_format_options, $settings['allow_ip_email_mailtype'], 'id="allow_ip_email_mailtype"'));
	$this->table->add_row('<label for="allow_ip_template">'.lang('allow_ip_template').'</label><div class="subtext">'.lang('allow_ip_template_instructions').'</div>', form_dropdown('allow_ip_template', $template_options, $settings['allow_ip_template'], 'id="allow_ip_template"' . $settings_disable));
	$this->table->add_row('<label for="allow_ip_add_member_groups">'.lang('allow_ip_add_member_groups').'</label><div class="subtext">'.lang('allow_ip_add_member_groups_instructions').'</div>', form_multiselect('allow_ip_add_member_groups[]', $pw_member_groups, $settings['allow_ip_add_member_groups'], 'id="allow_ip_add_member_groups"'. $settings_disable));
	
	echo $this->table->generate();
	$this->table->clear();	
	?>
</div>

<h3  class="accordion" id="configure_login_alert"><?php echo lang('configure_login_alert')?></h3>
<div>
	<?php 
	$this->table->set_heading('Setting','Value');
	$this->table->add_row('<label for="login_alert_emails">'.lang('login_alert_emails').'</label><div class="subtext">'.lang('login_alert_emails_instructions').'</div>', form_textarea('login_alert_emails', implode("\n", $settings['login_alert_emails']), 'cols="90" rows="6" id="login_alert_emails"'. $settings_disable));
	echo $this->table->generate();
	$this->table->clear();	
	?>
</div>

<h3  class="accordion" id="configure_cp_quick_deny"><?php echo lang('configure_cp_quick_deny')?></h3>
<div>
	<?php 
	$this->table->set_heading('Setting','Value');
	$this->table->add_row('<label for="cp_quick_deny_exclude_groups">'.lang('cp_quick_deny_exclude_groups').'</label><div class="subtext">'.lang('cp_quick_deny_exclude_groups_instructions').'</div>', form_multiselect('cp_quick_deny_exclude_groups[]', $member_groups, $settings['cp_quick_deny_exclude_groups'], 'id="cp_quick_deny_exclude_groups"'. $settings_disable));
	echo $this->table->generate();
	$this->table->clear();	
	?>
</div>

<h3  class="accordion"><?php echo lang('license_number')?></h3>
<div>
	<?php 
	$this->table->set_heading('Setting','Value');
	$this->table->add_row('<label for="license_number">'.lang('license_number').'</label>', form_input('license_number', $settings['license_number'], 'id="license_number"'. $settings_disable));
	echo $this->table->generate();
	$this->table->clear();	
	?>
</div>
<br />
<div class="tableFooter">
	<div class="tableSubmit">
		<?php echo form_submit(array('name' => 'submit', 'value' => lang('submit'), 'class' => 'submit'));?>
	</div>
</div>	
<?php echo form_close()?>