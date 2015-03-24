<?php if ($missing_settings == TRUE): ?>
<span style="font-weight:bold; color:red;"> <?=lang('cf:missing_settings')?> </span>
<input name="field_id_<?=$field_id?>[skip]" type="hidden" value="y" />
<?php else: ?>

<div class="CFField" id="ChannelFiles_<?=$field_id?>" rel="<?=$field_id?>">
<div class="CFDragDrop" id="CFDragDrop_<?=$field_id?>"><p><?=lang('cf:drophere')?></p></div>
<table cellspacing="0" cellpadding="0" border="0" class="CFTable">
	<thead>
		<tr>
			<th colspan="99" class="top_actions">
				<div class="block UploadFiles"><div style="position:relative; overflow:hidden;"><?=lang('cf:upload_files')?><em id="ChannelFilesSelect_<?=$field_id?>"></em></div></div>
				<?php if ($settings['show_stored_files'] == 'yes'):?><div class="block StoredFiles"><?=lang('cf:stored_files')?></div><?php endif;?>
				<?php if ($settings['show_import_files'] == 'yes'):?><div class="block ImportFiles"><?=lang('cf:import_files')?></div>
				<?php else:?><div class="block">&nbsp;</div><?php endif;?>
				<div class="block_long">
					<div class="UploadProgress cfhidden">
						<div class="progress">
							<div class="inner">
								<span class="percent"></span>&nbsp;&nbsp;&nbsp;
								<span class="speed"></span>&nbsp;&nbsp;&nbsp;
								<span class="bytes"> <span class="uploadedBytes"></span> <span class="totalBytes"></span> </span>&nbsp;&nbsp;&nbsp;
							</div>
						</div>
					</div>
				</div>
				<div class="block"><a href="#" class="StopUpload"><?=lang('cf:stop_upload')?></a></div>
			</th>
		</tr>
		<tr class="SearchFiles" style="display:none">
			<th colspan="99">
				<table>
					<tbody>
						<tr>
							<td class="filefilter">
								<div class="filter">
									<div class="left">
										<input type="text" value="<?=lang('cf:keywords')?>" maxlength="256" onblur="if (value == '') {value='<?=lang('cf:keywords')?>'}" onfocus="if (value == '<?=lang('cf:keywords')?>') {value =''}" rel="keywords">
									</div>
									<div class="right">
										<label><?=lang('cf:limit')?></label>
										<input type="text" value="20" rel="limit"/>
									</div>
								</div>
								<div class="results_wrapper">
									<p class="Loading"><?=lang('cf:loading_files')?></p>
									<div class="results"></div>
								</div>
							</td>
						</tr>
					</tbody>
				</table>
			</th>
		</tr>
		<tr class="FilesQueue cfhidden"><th colspan="99"></th></tr>
		<tr>
			<?php foreach ($settings['columns'] as $type => $val):?>
			<?php if ($val == FALSE) continue;?>
			<?php $size=''; if ($type == 'row_num') $size = '10'; elseif ($type == 'id') $size = '20'; elseif ($type == 'image') $size = '50';?>
			<th style="width:<?=$size?>px"><?=$val?></th>
			<?php endforeach;?>

			<?php if (isset($settings['show_download_btn']) == TRUE && $settings['show_download_btn'] == 'yes'):?><th style="width:80px"><?=lang('cf:actions')?></th>
			<?php else:?><th style="width:85px"><?=lang('cf:actions')?></th><?php endif;?>
		</tr>
	</thead>
	<tbody class="AssignedFiles">
	<?php if ($total_files < 1):?><tr class="NoFiles"><td colspan="99"><?=lang('cf:no_files')?></td></tr><?php endif;?>
	</tbody>
	<tfoot>
		<tr>
			<td <?php if ($settings['file_limit'] == '999999') echo 'style="display:none"';?> colspan="99" class="FileLimit"><?=lang('cf:file_remain')?> <span class="remain"><?=$settings['file_limit']?></span></td>
		</tr>
	</tfoot>
</table>
<input name="<?=$field_name?>[key]" type="hidden" value="<?=$temp_key?>"/>

</div>

<?php endif; // Missing Settings ?>
