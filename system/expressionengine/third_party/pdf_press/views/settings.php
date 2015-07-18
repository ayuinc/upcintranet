<ul class="tab_menu" id="tab_menu_tabs">
<li class="content_tab"> <a href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=pdf_press'.AMP.'method=index'?>"><?=lang('index')?></a>  </li> 
<li class="content_tab current"> <a href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=pdf_press'.AMP.'method=settings'?>"><?=lang('settings') ?></a></li>
<li class="content_tab "> <a href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=pdf_press'.AMP.'method=fonts'?>"><?=lang('fonts') ?></a></li>
<li class="content_tab "> <a href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=pdf_press'.AMP.'method=preview'?>"><?=lang('preview')?></a>  </li> 
</ul>
<div class="clear_left shun"></div>
<div style="padding: 10px;">
	<p>Setting presets allow you to quickly configure multiple PDF settings and retrieve them in your templates. You can also configure Encryption & Security settings 
		for your PDF files as well.</p>
	<p><a class="submit" href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=pdf_press'.AMP.'method=new_setting'?>">New Setting Preset</a></p>
	<?php
	    $this->table->set_template($cp_table_template);
	    $this->table->set_heading(
			lang('id'),
	        lang('key'),
			lang('data'),
			lang('delete')
		);

	    foreach($settings as $setting)
	    {
	        $this->table->add_row(
	            $setting['id'],
				'<a href='.$setting['setting_link'].'>'.$setting['key'].'</a>',
				$setting['data'],
				anchor($setting['delete_link'], 'Delete', array('onClick' => "return confirm('Are you sure you want to delete this setting?')"))
	        );
	    }

		echo $this->table->generate();

	?>
	<div class="tableFooter">
		<div class="tableSubmit">
	
		</div>
		<span class=""><?=$pagination?></span>
		<span class="pagination" id="filter_pagination"></span>
	</div>
</div>