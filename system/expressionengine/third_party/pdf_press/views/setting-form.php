<ul class="tab_menu" id="tab_menu_tabs">
<li class="content_tab"> <a href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=pdf_press'.AMP.'method=index'?>"><?=lang('index')?></a>  </li> 
<li class="content_tab current"> <a href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=pdf_press'.AMP.'method=settings'?>"><?=lang('settings') ?></a></li>
<li class="content_tab "> <a href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=pdf_press'.AMP.'method=fonts'?>"><?=lang('fonts') ?></a></li>
<li class="content_tab "> <a href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=pdf_press'.AMP.'method=preview'?>"><?=lang('preview')?></a>  </li> 
</ul>
<div class="clear_left shun"></div>
<div style="padding: 10px;">
	<h3 id="system"><?= $preset_page_title ?></h3>
	<?=form_open('C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=pdf_press'.AMP.'method=save_setting');?>
	<?php 
	$this->table->set_template($cp_pad_table_template);

	foreach ($data as $key => $val)
	{
		$this->table->add_row(lang($key, $key), $val);
	}

	echo $this->table->generate();

	?>
	<?php $this->table->clear()?>

	<p><?=form_submit('submit', lang('submit'), 'class="submit"')?></p>

	<?php
	form_close();
	?>
</div>