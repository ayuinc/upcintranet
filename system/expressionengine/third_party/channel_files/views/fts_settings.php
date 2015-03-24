<div class="ChannelFilesField cfix">

<ul class="ChannelFilesTabs">
	<li><a href="#CFLocSettings"><?=lang('cf:loc_settings')?></a></li>
	<li><a href="#CFFieldUI"><?=lang('cf:fieldtype_settings')?></a></li>
</ul>

<div class="ChannelFilesTabsHolder">

<div class="CFLocSettings" id="CFLocSettings">
<table cellspacing="0" cellpadding="0" border="0" class="ChannelFilesTable" style="width:80%">
	<thead>
		<tr>
			<th style="width:180px"><?=lang('cf:pref')?></th>
			<th><?=lang('cf:value')?></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td><?=lang('cf:upload_location')?></td>
			<td><?=form_dropdown('channel_files[upload_location]', $upload_locations, $upload_location, ' class="cf_upload_type" ')?></td>
		</tr>
		<tr>
			<td colspan="2"><a href="#" class="TestLocation"><?=lang('cf:test_location')?></a></td>
		</tr>
	</tbody>
</table>


<table cellspacing="0" cellpadding="0" border="0" class="ChannelFilesTable CFUpload_local" style="width:80%">
	<thead>
		<tr>
			<th colspan="2">
				<h4>
					<?=lang('cf:local')?>
					<small><?=lang('cf:specify_pref_cred')?></small>
				</h4>
			</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td><?=lang('cf:upload_location')?></td>
			<td><?=form_dropdown('channel_files[locations][local][location]', $local['locations'], $locations['local']['location'] ); ?></td>
		</tr>
	</tbody>
</table>

<table cellspacing="0" cellpadding="0" border="0" class="ChannelFilesTable CFUpload_s3" style="width:80%">
	<thead>
		<tr>
			<th colspan="2">
				<h4>
					<?=lang('cf:s3')?>
					<small><?=lang('cf:specify_pref_cred')?></small>
				</h4>
			</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td><?=lang('cf:s3:key')?> <small><?=lang('cf:s3:key_exp')?></small></td>
			<td><?=form_input('channel_files[locations][s3][key]', $locations['s3']['key'])?></td>
		</tr>
		<tr>
			<td><?=lang('cf:s3:secret_key')?> <small><?=lang('cf:s3:secret_key_exp')?></small></td>
			<td><?=form_input('channel_files[locations][s3][secret_key]', $locations['s3']['secret_key'])?></td>
		</tr>
		<tr>
			<td><?=lang('cf:s3:bucket')?> <small><?=lang('cf:s3:bucket_exp')?></small></td>
			<td><?=form_input('channel_files[locations][s3][bucket]', $locations['s3']['bucket'])?></td>
		</tr>
		<tr>
			<td><?=lang('cf:s3:region')?></td>
			<td><?=form_dropdown('channel_files[locations][s3][region]', $s3['regions'], $locations['s3']['region']); ?></td>
		</tr>
		<tr>
			<td><?=lang('cf:s3:acl')?> <small><?=lang('cf:s3:acl_exp')?></small></td>
			<td><?=form_dropdown('channel_files[locations][s3][acl]', $s3['acl'], $locations['s3']['acl']); ?></td>
		</tr>
		<tr>
			<td><?=lang('cf:s3:storage')?></td>
			<td><?=form_dropdown('channel_files[locations][s3][storage]', $s3['storage'], $locations['s3']['storage']); ?></td>
		</tr>
		<tr>
			<td><?=lang('cf:s3:force_download')?> <small><?=lang('cf:s3:force_download_exp')?></small></td>
			<td><?=form_dropdown('channel_files[locations][s3][force_download]', array('no' => lang('cf:no'), 'yes' => lang('cf:yes')), $locations['s3']['force_download']); ?></td>
		</tr>
		<tr>
			<td><?=lang('cf:s3:directory')?></td>
			<td><?=form_input('channel_files[locations][s3][directory]', $locations['s3']['directory'])?></td>
		</tr>
				<tr>
			<td><?=lang('cf:s3:cloudfrontd')?></td>
			<td><?=form_input('channel_files[locations][s3][cloudfront_domain]', $locations['s3']['cloudfront_domain'])?></td>
		</tr>
	</tbody>
</table>

<table cellspacing="0" cellpadding="0" border="0" class="ChannelFilesTable CFUpload_cloudfiles" style="width:80%">
	<thead>
		<tr>
			<th colspan="2">
				<h4>
					<?=lang('cf:cloudfiles')?>
					<small><?=lang('cf:specify_pref_cred')?></small>
				</h4>
			</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td><?=lang('cf:cloudfiles:username')?></td>
			<td><?=form_input('channel_files[locations][cloudfiles][username]', $locations['cloudfiles']['username'])?></td>
		</tr>
		<tr>
			<td><?=lang('cf:cloudfiles:api')?></td>
			<td><?=form_input('channel_files[locations][cloudfiles][api]', $locations['cloudfiles']['api'])?></td>
		</tr>
		<tr>
			<td><?=lang('cf:cloudfiles:container')?></td>
			<td><?=form_input('channel_files[locations][cloudfiles][container]', $locations['cloudfiles']['container'])?></td>
		</tr>
		<tr>
			<td><?=lang('cf:cloudfiles:region')?></td>
			<td><?=form_dropdown('channel_files[locations][cloudfiles][region]', $cloudfiles['regions'], $locations['cloudfiles']['region']); ?></td>
		</tr>
		<tr>
			<td><?=lang('cf:cloudfiles:cdn_uri')?></td>
			<td><?=form_input('channel_files[locations][cloudfiles][cdn_uri]', $locations['cloudfiles']['cdn_uri'])?></td>
		</tr>
	</tbody>
</table>


<table cellspacing="0" cellpadding="0" border="0" class="ChannelFilesTable CFUpload_ftp" style="width:80%">
	<thead>
		<tr>
			<th colspan="2">
				<h4>
					<?=lang('cf:ftp')?>
					<small><?=lang('cf:specify_pref_cred')?></small>
				</h4>
			</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td><?=lang('cf:host_ip')?></td>
			<td><?=form_input('channel_files[locations][ftp][hostname]', $locations['ftp']['hostname']);?></td>
		</tr>
		<tr>
			<td><?=lang('cf:port')?></td>
			<td><?=form_input('channel_files[locations][ftp][port]', $locations['ftp']['port']);?></td>
		</tr>
		<tr>
			<td><?=lang('cf:username')?></td>
			<td><?=form_input('channel_files[locations][ftp][username]', $locations['ftp']['username']);?></td>
		</tr>
		<tr>
			<td><?=lang('cf:password')?></td>
			<td><?=form_password('channel_files[locations][ftp][password]', $locations['ftp']['password']);?></td>
		</tr>
		<tr>
			<td><?=lang('cf:path')?></td>
			<td><?=form_input('channel_files[locations][ftp][path]', $locations['ftp']['path']);?></td>
		</tr>
		<tr>
			<td><?=lang('cf:url')?></td>
			<td><?=form_input('channel_files[locations][ftp][url]', $locations['ftp']['url']);?></td>
		</tr>
		<tr>
			<td><?=lang('cf:ftp:passive')?></td>
			<td><?=form_dropdown('channel_files[locations][ftp][passive]', array('yes' => lang('cf:yes'), 'no' => lang('cf:no')), $locations['ftp']['passive']); ?></td>
		</tr>
		<tr>
			<td><?=lang('cf:ftp:ssl')?></td>
			<td><?=form_dropdown('channel_files[locations][ftp][ssl]', array( 'no' => lang('cf:no'), 'yes' => lang('cf:yes')), $locations['ftp']['ssl']); ?></td>
		</tr>
	</tbody>
</table>

<table cellspacing="0" cellpadding="0" border="0" class="ChannelFilesTable CFUpload_sftp" style="width:80%">
	<thead>
		<tr>
			<th colspan="2">
				<h4>
					<?=lang('cf:sftp')?>
					<small><?=lang('cf:specify_pref_cred')?></small>
				</h4>
			</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td><?=lang('cf:host_ip')?></td>
			<td><?=form_input('channel_files[locations][sftp][hostname]', $locations['sftp']['hostname']);?></td>
		</tr>
		<tr>
			<td><?=lang('cf:port')?></td>
			<td><?=form_input('channel_files[locations][sftp][port]', $locations['sftp']['port']);?></td>
		</tr>
		<tr>
			<td><?=lang('cf:username')?></td>
			<td><?=form_input('channel_files[locations][sftp][username]', $locations['sftp']['username']);?></td>
		</tr>
		<tr>
			<td><?=lang('cf:password')?></td>
			<td><?=form_password('channel_files[locations][sftp][password]', $locations['sftp']['password']);?></td>
		</tr>
		<tr>
			<td><?=lang('cf:path')?></td>
			<td><?=form_input('channel_files[locations][sftp][path]', $locations['sftp']['path']);?></td>
		</tr>
		<tr>
			<td><?=lang('cf:url')?></td>
			<td><?=form_input('channel_files[locations][sftp][url]', $locations['sftp']['url']);?></td>
		</tr>
	</tbody>
</table>

</div>

<div class="CFFieldUI" id="CFFieldUI">

<table cellspacing="0" cellpadding="0" border="0" class="ChannelFilesTable" style="width:80%">
	<thead>
		<tr>
			<th style="width:180px"><?=lang('cf:pref')?></th>
			<th><?=lang('cf:value')?></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td><?=lang('cf:categories')?></td>
			<td>
				<?=form_input('channel_files[categories]', implode(',', $categories), 'style="border:1px solid #ccc; width:80%;"')?>
				<small><?=lang('cf:categories_explain')?></small>
			</td>
		</tr>
		<tr>
			<td><?=lang('cf:show_stored_files')?></td>
			<td><?=form_dropdown('channel_files[show_stored_files]', array('yes' => lang('cf:yes'), 'no' => lang('cf:no')), $show_stored_files)?></td>
		</tr>
		<tr>
			<td><?=lang('cf:limt_stored_files_author')?></td>
			<td>
				<?=form_dropdown('channel_files[stored_files_by_author]', array('no' => lang('cf:no'), 'yes' => lang('cf:yes')), $stored_files_by_author)?>
				<small><?=lang('cf:limt_stored_files_author_exp')?></small>
			</td>
		</tr>
		<tr>
			<td><?=lang('cf:show_import_files')?></td>
			<td>
				<?=form_dropdown('channel_files[show_import_files]', array('no' => lang('cf:no'), 'yes' => lang('cf:yes')), $show_import_files)?>
				<small><?=lang('cf:show_import_files_exp')?></small>
				<?=lang('cf:import_path')?> <?=form_input('channel_files[import_path]', $import_path, 'style="border:1px solid #ccc; width:80%;"')?>
				<small><?=lang('cf:import_path_exp')?></small>
			</td>
		</tr>
		<tr>
			<td><?=lang('cf:stored_files_search_type')?></td>
			<td>
				<?=form_dropdown('channel_files[stored_files_search_type]', array('entry' => lang('cf:entry_based'), 'file' => lang('cf:file_based')), $stored_files_search_type)?>
			</td>
		</tr>
		<tr>
			<td><?=lang('cf:jeditable_event')?></td>
			<td>
				<?=form_dropdown('channel_files[jeditable_event]', array('click' => lang('cf:click'), 'mouseenter' => lang('cf:hover')), $jeditable_event)?>
			</td>
		</tr>
		<tr>
			<td><?=lang('cf:file_limit')?></td>
			<td>
				<?=form_input('channel_files[file_limit]', $file_limit, 'style="border:1px solid #ccc; width:50px;"')?>
				<small><?=lang('cf:file_limit_exp')?></small>
			</td>
		</tr>
		<tr>
			<td><?=lang('cf:file_extensions')?></td>
			<td>
				<?=form_input('channel_files[file_extensions]', $file_extensions, 'style="border:1px solid #ccc; width:50px;"')?>
				<small><?=lang('cf:file_extensions_exp')?></small>
			</td>
		</tr>
		<tr class="cf_entry_id_folder">
			<td><?=lang('cf:store_entry_id_folder')?></td>
			<td>
				<?=form_dropdown('channel_files[entry_id_folder]', array('yes' => lang('cf:yes'), 'no' => lang('cf:no')), $entry_id_folder)?>
				<small><?=lang('cf:store_entry_id_folder_exp')?></small>
			</td>
		</tr>
		<tr class="cf_prefix_entry_id" style="<?php if ($entry_id_folder == 'yes') echo 'display:none'; ?>">
			<td><?=lang('cf:prefix_entry_id')?></td>
			<td>
				<?=form_dropdown('channel_files[prefix_entry_id]', array('yes' => lang('cf:yes'), 'no' => lang('cf:no')), $prefix_entry_id)?>
				<small><?=lang('cf:prefix_entry_id_exp')?></small>
			</td>
		</tr>
		<tr>
			<td><?=lang('cf:show_download_btn')?></td>
			<td>
				<?=form_dropdown('channel_files[show_download_btn]', array('no' => lang('cf:no'), 'yes' => lang('cf:yes')), $show_download_btn)?>
			</td>
		</tr>
		<tr>
			<td><?=lang('cf:show_file_replace')?></td>
			<td>
				<input name="channel_files[show_file_replace]" <?php if (isset($override['show_file_replace'])):?>disabled="disabled"<?php endif;?> type="radio" value="yes" <?php if ($show_file_replace == 'yes') echo 'checked'?>> <?=lang('cf:yes')?>&nbsp;&nbsp;
				<input name="channel_files[show_file_replace]" <?php if (isset($override['show_file_replace'])):?>disabled="disabled"<?php endif;?> type="radio" value="no" <?php if ($show_file_replace == 'no') echo 'checked'?>> <?=lang('cf:no')?>
			</td>
		</tr>
		<tr>
			<td><?=lang('cf:hybrid_upload')?></td>
			<td>
				<?=form_dropdown('channel_files[hybrid_upload]', array('yes' => lang('cf:yes'), 'no' => lang('cf:no')), $hybrid_upload)?>
				<small><?=lang('cf:hybrid_upload_exp')?></small>
			</td>
		</tr>
		<tr>
			<td><?=lang('cf:locked_url_fieldtype')?></td>
			<td>
				<?=form_dropdown('channel_files[locked_url_fieldtype]', array('no' => lang('cf:no'), 'yes' => lang('cf:yes')), $locked_url_fieldtype)?>
				<small><?=lang('cf:locked_url_fieldtype_exp')?></small>
			</td>
		</tr>
		<tr>
			<td><?=lang('cf:act_url')?></td>
			<td>
				<strong><a href="<?=$act_url?>" target="_blank"><?=$act_url?></a></strong>
				<small><?=lang('cf:act_url:exp')?></small>
			</td>
		</tr>
	</tbody>
</table>

<table cellspacing="0" cellpadding="0" border="0" class="ChannelFilesTable" style="width:80%">
	<thead>
		<tr>
			<th colspan="2">
				<h4>
					<?=lang('cf:field_columns')?>
					<small><?=lang('cf:field_columns_exp')?></small>
				</h4>
			</th>
		</tr>
	</thead>
	<tbody>
	<?php foreach($columns as $name => $val):?>
		<tr>
			<td><?=lang('cf:'.$name)?></td>
			<td><?=form_input('channel_files[columns]['.$name.']', $val)?></td>
		</tr>
	<?php endforeach;?>
	</tbody>
</table>
</div>

</div>
</div>
