<?php 
$this->load->view('errors'); 
$tmpl = array (
	'table_open'          => '<table class="mainTable" border="0" cellspacing="0" cellpadding="0">',

	'row_start'           => '<tr class="even">',
	'row_end'             => '</tr>',
	'cell_start'          => '<td style="width:80%;">',
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
<?php echo lang('security_scan_instructions'); ?><br /><br />

<div id="progressbar"></div>
<?php echo $passed_tests." Passed / ".$total_tests." Total"; ?> <br /><br />

<?php echo form_open($query_base.'index', array('id'=>'my_accordion'))?>
<input type="hidden" value="yes" name="go_securitee_scan" />

<?php 
ksort($security_scan);
foreach($security_scan AS $cat => $scan): ?>
<h3  class="accordion"><?php echo lang($cat)?></h3>
<div>
	<?php 
	$this->table->set_heading('Setting','Grade');	
	foreach($scan AS $key => $value)
	{
		$value = ($value == 1 ? '<span class="go_notice">Pass</span>' : '<span class="notice">Warning</span>');
		$this->table->add_row('<label for="'.$key.'">'.lang($key).'</label><div class="subtext">'.str_replace('##BASE##', BASE, lang($key.'_instructions')).'</div>', $value);
	}
	
	if($cat == 'version_control_scan')
	{
		if(count($cvs_file_locations) >= '1')
		{
			$cell_data = array('colspan' => '2', 'data' => '<label for="'.$key.'">'.lang($cat.'_found_locations').':</label><div class="subtext">'.str_replace('##BASE##', BASE, lang($cat.'_found_locations_instructions')).'</div>');
			$this->table->add_row($cell_data);
		}
		
		foreach($cvs_file_locations AS $vcs)
		{
			$this->table->add_row($vcs, $value);
		}
	}
	
	
	echo $this->table->generate();
	$this->table->clear();	
	?>
</div>
<?php endforeach; ?>
<br />
<div class="tableFooter">
	<div class="tableSubmit">
		<?php //echo form_submit(array('name' => 'submit', 'value' => lang('fix_all_possible'), 'class' => 'submit'));?>
	</div>
</div>	
<?php echo form_close()?>