<div class="CFField">
<table cellspacing="0" cellpadding="0" border="0" class="CFTable" rel="<?=$field_id?>">
	<thead>
		<tr>
			<th></th>
			<th><?=lang('cf:filename')?></th>
			<th><?=lang('cf:filesize')?></th>
		</tr>
	</thead>
	<tbody>
	<?php foreach ($files as $filename => $size):?>
		<tr>
			<td><input type="checkbox" value="<?=$filename?>" checked></td>
			<td><?=$filename?></td>
			<td><?=$size?></td>
		</tr>
	<?php endforeach;?>
	<?php if (count($files) < 1):?><tr><td colspan="99"><?=lang('cf:import:no_files')?></td></tr><?php endif;?>
	</tbody>
	<tfoot>
		<tr>
			<?php if (count($files) > 0):?><td colspan="99" style="text-align:left"><button class="ImportFilesBtn"><?=lang('cf:import_files')?> <span class="loading"></span></button></td><?php endif;?>
		</tr>
	</tfoot>
</table>
</div>