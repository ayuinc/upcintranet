<?php   if ( ! defined('BASEPATH')) exit('No direct script access allowed');
    $this->EE =& get_instance();
?>

<?php foreach ($cp_messages as $cp_message_type => $cp_message) : ?>
	<p class="notice <?=$cp_message_type?>"><?=$cp_message?></p>
<?php endforeach; ?>

<?php	
	echo form_open($action_url);

	$cp_table_template['table_open'] = '<table class="mainTable addDetour" border="0" cellspacing="0" cellpadding="0">';
	$this->table->set_template($cp_table_template);
	$this->table->set_heading(
		array(
			'data' => $this->EE->lang->line('title_url'),
			'style' => 'width:26%;'
		),
		array(
			'data' => $this->EE->lang->line('title_redirect'),
			'style' => 'width:26%;'
		),	
		array(
			'data' => $this->EE->lang->line('title_method'),
			'style' => 'width:6%;'
		),
		array(
			'data' => $this->EE->lang->line('title_start'),
			'style' => 'width:21%;'
		),	
		array(
			'data' => $this->EE->lang->line('title_end'),
			'style' => 'width:21%;'
		)
	);
	
	$this->table->add_row(
		form_input(
			array(
				'name' => 'original_url',
				'id' => 'original_url'
			)
		), 
		form_input(
			array(
				'name' => 'new_url',
				'id' => 'new_url'
			)
		), 
		form_dropdown('detour_method', $detour_methods), 
		form_input(
			array(
				'name' => 'start_date',
				'id' => 'start_date',
				'class' => 'datepicker'
			)
		), 
		form_input(
			array(
				'name' => 'end_date',
				'id' => 'end_date',
				'class' => 'datepicker'
			)
		)
	);
	
	$this->table->add_row(
		$this->EE->lang->line('dir_uri'), 
		$this->EE->lang->line('dir_detour'), 
		array('data' => '&nbsp', 'colspan' => 3)
	);

	echo $this->table->generate();	
	$this->table->clear();	

	echo form_submit(array('name' => 'submit', 'value' => $this->EE->lang->line('btn_save_detour'), 'class' => 'submit'));

	echo '<br /><br />';

	$this->table->set_template($cp_pad_table_template);
	$this->table->set_heading(
		array(
			'data' => $this->EE->lang->line('title_url'),
			'style' => 'width:35%;'
		),
		array(
			'data' => $this->EE->lang->line('title_redirect'),
			'style' => 'width:35%;'
		),	
		array(
			'data' => $this->EE->lang->line('title_method'),
			'style' => 'width:5%;'
		),
		array(
			'data' => $this->EE->lang->line('title_start'),
			'style' => 'width:10%;'
		),	
		array(
			'data' => $this->EE->lang->line('title_end'),
			'style' => 'width:10%;'
		),		
		array(
			'data' => 'Delete'
		)
	);

	foreach($current_detours as $detour)
	{
		$this->table->add_row(
			'<a href="' . $detour['advanced_link'] . '">' . $detour['original_url'] . '</a>',
			$detour['new_url'],
			'<strong>' . $detour['detour_method'] . '</strong>',
			$detour['start_date'], 
			$detour['end_date'],  
			'<input type="checkbox" name="detour_delete[]" value="' . $detour['detour_id'] . '" />'
		);
	
	}
	
	/*
	$this->table->add_row(
		form_input('original_url', ''),
		form_input('new_url', ''),
		form_dropdown('detour_method', $detour_methods), 
		'&nbsp;', 
		'&nbsp;', 
		'', 
		''
	);

	$this->table->add_row(
		$this->EE->lang->line('dir_uri'), 
		$this->EE->lang->line('dir_detour'), 
		'&nbsp;', 
		'&nbsp;', 
		'&nbsp;', 
		'', 
		''
	);
	*/

	echo $this->table->generate();
	$this->table->clear();			

	echo form_submit(array('name' => 'submit', 'value' => $this->EE->lang->line('btn_delete_detours'), 'class' => 'submit'));
	
	echo form_close();

	
?>