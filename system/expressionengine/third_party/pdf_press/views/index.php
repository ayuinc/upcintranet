<ul class="tab_menu" id="tab_menu_tabs">
<li class="content_tab current"> <a href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=pdf_press'.AMP.'method=index'?>"><?=lang('index')?></a>  </li> 
<li class="content_tab "> <a href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=pdf_press'.AMP.'method=settings'?>"><?=lang('settings') ?></a></li>
<li class="content_tab "> <a href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=pdf_press'.AMP.'method=fonts'?>"><?=lang('fonts') ?></a></li>
<li class="content_tab"> <a href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=pdf_press'.AMP.'method=preview'?>"><?=lang('preview')?></a>  </li> 
</ul>
<div class="clear_left shun"></div>
<style>
	.ok {color:green;}
	.warning {color:orange;}
	.failed {color:red;}
</style>
<div style="padding: 10px;">
	<h3 id="system"><?= lang("System Configuration") ?></h3>
	<?php
		$this->table->set_template($cp_table_template);
		$this->table->set_heading(
			lang('requirement'),
			lang('required'),
			lang('present')
		);
	
		foreach($server_configs as $key => $server_config) {
			$val_extra = "";
			$val_format = "";
			$val_class = "";
			
			
			if ($server_config["result"] && !$server_config["value"]) {
				$val_extra = "Yes";
				$val_class = "ok";
			}
        	if (!$server_config["result"]) {
          		if (isset($server_config["fallback"])) {
            		$val_extra = "<div>No. ".$server_config["fallback"]."</div>";
					$val_class = "warning";
          		}
          		if (isset($server_config["failure"])) {
            		$val_extra = "<div>".$server_config["failure"]."</div>";
					$val_class = "failed";
          		}
			}
			
			if($server_config['required'] == 1)
				$val_format = lang('yes');
			elseif($server_config['required'] == 0)
				$val_format = lang('no');
			else
				$val_format = $server_config['required'];
			
			$this->table->add_row(
	            $key,
	            $val_format,
				"<div class='$val_class'>".$server_config['value'].' '.$val_extra."</div>"
	        );
		}
		echo $this->table->generate();
	?>

	<h3 id="dompdf-config">DOMPDF Configuration</h3>
	<p><strong><?= lang('setting_override') ?></strong</p>
	<?php
		$this->table->set_template($cp_table_template);
		$this->table->set_heading(
			lang('config_name'),
			lang('value'),
			lang('desc'),
			lang('status')
		);
		
		foreach($dompdf_configs as $key => $dompdf_config) {
			$val_format = "";
			$val_format = $dompdf_config['value'];
			$val_class = ($dompdf_config['success'] ? "ok" : "failed");
						
			$this->table->add_row(
	            $key,
	            $val_format,
				$dompdf_config['desc'],
				"<div class='$val_class'>".$dompdf_config['message']."</div>"
	        );
		}
		echo $this->table->generate();
	?>
</div>