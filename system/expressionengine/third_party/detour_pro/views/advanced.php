<?php   if ( ! defined('BASEPATH')) exit('No direct script access allowed');
    $this->EE =& get_instance();
?>

<?php foreach ($cp_messages as $cp_message_type => $cp_message) : ?>
	<p class="notice <?=$cp_message_type?>"><?=$cp_message?></p>
<?php endforeach; ?>

<?php	

	echo form_open($action_url);

	if($id)
	{
		echo form_hidden('id', $id);
	}

	$this->table->set_template($cp_pad_table_template);
	$this->table->set_heading(
		array(
			'data' => $this->EE->lang->line('title_setting'),
			'style' => 'width:50%;'
		),
		array(
			'data' => $this->EE->lang->line('title_value'),
			'style' => 'width:50%;'
		)
	);
	
	// Original URL	
	$this->table->add_row(
		form_label($this->EE->lang->line('label_original_url'), '') . 
		'<div class="subtext">' . $this->EE->lang->line('subtext_original_url') . '</div>', 
		form_input(
			array(
				'name' => 'original_url',
				'id' => 'original_url',
				'value' => $original_url,  
				'size' => '50'
			)
		) 
	);
	
	// Redirect
	$this->table->add_row(
		form_label($this->EE->lang->line('label_new_url'), '') . 
		'<div class="subtext">' . $this->EE->lang->line('subtext_new_url') . '</div>', 
		form_input(
			array(
				'name' => 'new_url',
				'id' => 'new_url',
				'value' => $new_url, 
				'size' => '50'
			)
		) 
	);
	
	// Type of redirect
	$this->table->add_row(
		form_label($this->EE->lang->line('label_detour_method'), '') . 
		'<div class="subtext">' . $this->EE->lang->line('subtext_detour_method') . '</div>', 
		form_dropdown('detour_method', $detour_methods, $detour_method)
	);

	$this->table->add_row(
		'<strong>' . $this->EE->lang->line('title_hits') . '</strong>', 
		$detour_hits
	);	
	
	// Start date
	$this->table->add_row(
		form_label($this->EE->lang->line('label_start_date'), '') . 
		'<div class="subtext">' . $this->EE->lang->line('subtext_start_date') . '</div>', 
		form_input(
			array(
				'name' => 'start_date',
				'id' => 'start_date',
				'value' => $start_date, 
				'size' => '30', 
				'class' => 'datepicker'
			)
		) 
	);	
	
	if($start_date)
	{
		$this->table->add_row(
			form_label($this->EE->lang->line('label_clear_start_date'), '') . 
			'<div class="subtext">' . $this->EE->lang->line('subtext_clear_start_date') . '</div>', 
			form_checkbox('clear_start_date', '1')
		);		
	}
	
	// End Date
	$this->table->add_row(
		form_label($this->EE->lang->line('label_end_date'), '') . 
		'<div class="subtext">' . $this->EE->lang->line('subtext_end_date') . '</div>', 
		form_input(
			array(
				'name' => 'end_date',
				'id' => 'end_date',
				'value' => $end_date, 
				'size' => '30', 
				'class' => 'datepicker'
			)
		)
	);	
	
	if($end_date)
	{
		$this->table->add_row(
			form_label($this->EE->lang->line('label_clear_end_date'), '') . 
			'<div class="subtext">' . $this->EE->lang->line('subtext_clear_end_date') . '</div>', 
			form_checkbox('clear_end_date', '1')
		);		
	}
	
	/*
	// Owner
	$this->table->add_row(
		form_label($this->EE->lang->line('label_detour_owner'), '') . 
		'<div class="subtext">' . $this->EE->lang->line('subtext_detour_owner') . '</div>', 
		form_input(
			array(
				'name' => 'owner',
				'id' => 'owner',
				// 'value' => $this->get_value( $values, "filename" ),
				'size' => '50'
			)
		) 
	);
		
	// Category
	$categories = array(
      'cat_1'  => 'Category 1',
      'cat_2'    => 'Category 2'
    );

	$this->table->add_row(
		form_label($this->EE->lang->line('label_detour_category'), '') . 
		'<div class="subtext">' . $this->EE->lang->line('subtext_detour_category') . '</div>', 
		form_dropdown('detour_category', $categories)
	);	
	*/
	
	echo $this->table->generate();							
	
	echo form_submit(array('name' => 'submit', 'value' => $this->EE->lang->line('btn_save_detour'), 'class' => 'submit'));
	echo form_close();
	
?>