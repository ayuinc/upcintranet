<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

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
 * Securit:ee - Javascript Library
 *
 * Library class for the Javaccript
 *
 * @package 	mithra62:Securitee
 * @author		Eric Lamb
 * @filesource 	./system/expressionengine/third_party/securitee/libraries/Securitee_js.php
 */
class Securitee_js
{
	
	public function __construct()
	{
		$this->EE =& get_instance();
	}
	
	public function get_accordian_css()
	{
		if (version_compare(APP_VER, '2.2', '<') || version_compare(APP_VER, '2.2', '>'))
		{
			return ' $("#my_accordion").accordion({autoHeight: false,header: "h3"}); ';
		}
		else
		{
			return '';
		}
	}
	
	public function get_security_scan_progressbar($progress)
	{
		$progress = (int)$progress;
		return "$('#progressbar').progressbar('option', 'value', ".$progress.");";
	}	
	
	public function get_scan_progressbar($proc_url, $url_base)
	{
		$js = "
			var kill_progress = false;
			
			$.ajax({
				url: '".html_entity_decode($proc_url)."',
				cache: false,
				dataType: 'json',
				success: function(data) {
					$('#progressbar').progressbar('option', 'value', 100);
					kill_progress = true;
					$.ajax({
						url: '".html_entity_decode($url_base)."progress',
						cache: false,
						dataType: 'json',
						success: function(data) {
							$('#active_item').html('');
							$('#total_items').html(data['total_items']);	
							$('#active_item').html(data['msg']);
							$('#item_number').html(data['item_number']);
							$('div.heading h2.edit').html('".lang('backup_progress_bar_stop')."');
							document.title = '".lang('backup_progress_bar_stop')."';
							$('#breadCrumb li:last').html('".lang('backup_progress_bar_stop')."');
							$('#backup_instructions').hide();
						}
					});			
				}
			});
				
			function updateProgress() {
				var progress;
				progress = $('#progressbar').progressbar('option','value');
				if (progress < 100 && !kill_progress) {
		
					$.ajax({
						url: '".html_entity_decode($url_base)."progress',
						cache: false,
						dataType: 'json',
						success: function(data) {
							progress = Math.floor(data['item_number']/data['total_items']*100);
							$('#progressbar').progressbar('option', 'value', progress);
							$('#total_items').html(data['total_items']);	
							$('#active_item').html(data['msg']);
							$('#item_number').html(data['item_number']);
							
						}
					});
					setTimeout(updateProgress, 2000);
				}
				else
				{
		
				}
			}	  
			setTimeout(updateProgress, 2000);		
		";
		return $js;
	}
	
	public function get_check_toggle()
	{
		return array(
						'$(".toggle_all_cron").toggle(
							function(){
								$("input.toggle_cron").each(function() {
									this.checked = true;
								});
							}, function (){
								var checked_status = this.checked;
								$("input.toggle_cron").each(function() {
									this.checked = false;
								});
							}
						);'		
					);		
	}
	
	public function get_acc_scripts()
	{
		return array(
			'
			$("#securitee").find("a.entryLink").click(function() {
				$(".fullEntry").hide();
				$(this).siblings(".fullEntry").toggle();
				return false;
			});	

		$("#clear_file_monitor").click(function(e){
			
			e.preventDefault();
			var get_url = $(this).attr("href");
			$.ajax({  
				type: "GET",  
				url: get_url, 
				success: function(){
					$("#file_monitor_clear_success").show();
					$("#securitee_file_monitor_results").hide();
				},
				error: function(jqXHR, textStatus){
					var obj = jQuery.parseJSON(jqXHR.responseText);
				}	
			});  
			return false;
		});
				
			'				
		);
	}
	
	public function get_settings_form()
	{
		return array('
	
			if($("#pw_expire_ttl").val() == "custom")
			{
				$("#pw_expire_ttl_custom").show();
			}
		
			var def_assign = "0";
			$("#pw_expire_ttl").change(function(){
				var new_assign = $("#pw_expire_ttl").val();
				if(new_assign == def_assign || new_assign != "custom")
				{
					$("#pw_expire_ttl_custom").hide();
					$("#pw_expire_ttl_custom").val(new_assign);
				}
				else
				{
					$("#pw_expire_ttl_custom").show();
				}
			});
				
			if($("#pw_expire_ttl").val() == "custom")
			{
				$("#pw_expire_ttl_custom").show();
			}
		
			var def_assign = "0";
			$("#cp_reg_email_expire_ttl").change(function(){
				var new_assign = $("#cp_reg_email_expire_ttl").val();
				if(new_assign == def_assign || new_assign != "custom")
				{
					$("#cp_reg_email_expire_ttl_custom").hide();
					$("#cp_reg_email_expire_ttl_custom").val(new_assign);
				}
				else
				{
					$("#cp_reg_email_expire_ttl_custom").show();
				}
			});	
				
			if($("#cp_reg_email_expire_ttl").val() == "custom")
			{
				$("#cp_reg_email_expire_ttl_custom").show();
			}				

			var def_assign = "0";
			$("#pw_ttl").change(function(){
				var new_assign = $("#pw_ttl").val();
				if(new_assign == def_assign || new_assign != "custom")
				{
					$("#pw_ttl_custom").hide();
					$("#pw_ttl_custom").val(new_assign);
				}
				else
				{
					$("#pw_ttl_custom").show();
				}
			});	

			if($("#pw_ttl").val() == "custom")
			{
				$("#pw_ttl_custom").show();
			}	

			var def_assign = "0";
			$("#member_expire_ttl").change(function(){
				var new_assign = $("#member_expire_ttl").val();
				if(new_assign == def_assign || new_assign != "custom")
				{
					$("#member_expire_ttl_custom").hide();
					$("#member_expire_ttl_custom").val(new_assign);
				}
				else
				{
					$("#member_expire_ttl_custom").show();
				}
			});	

			if($("#member_expire_ttl").val() == "custom")
			{
				$("#member_expire_ttl_custom").show();
			}
				
			var def_assign = "0";
			$("#allow_ip_ttl").change(function(){
				var new_assign = $("#allow_ip_ttl").val();
				if(new_assign == def_assign || new_assign != "custom")
				{
					$("#allow_ip_ttl_custom").hide();
					$("#allow_ip_ttl_custom").val(new_assign);
				}
				else
				{
					$("#allow_ip_ttl_custom").show();
				}
			});	

			if($("#allow_ip_ttl").val() == "custom")
			{
				$("#allow_ip_ttl_custom").show();
			}				

				
		');
	}	
}