<table class="mainTable">
	<thead>
		<tr><th colspan="2"><?=lang('cf:location:settings')?></th></tr>
	</thead>
	<tbody>
		<tr>
			<td><?=lang('cf:location:type')?></td>
			<td>
				<?php if (isset($type) == FALSE) $type = 'local';?>
				<input type="radio" value="local" class="cf_location_type" name="cf_locations[type]" <?php if ($type == 'local') echo 'checked';?> /> &nbsp;<?=lang('cf:location:local')?> &nbsp;&nbsp;&nbsp;
				<input type="radio" value="s3" class="cf_location_type" name="cf_locations[type]" <?php if ($type == 's3') echo 'checked';?> /> &nbsp;<?=lang('cf:location:s3')?> &nbsp;&nbsp;&nbsp;
				<input type="radio" value="cloudfiles" class="cf_location_type" name="cf_locations[type]" <?php if ($type == 'cloudfiles') echo 'checked';?> /> &nbsp;<?=lang('cf:location:cloudfiles')?>
			</td>
		</tr>
	</tbody>
</table>


<table class="mainTable cf_locations_local">
	<thead>
		<tr><th colspan="2"><?=lang('cf:location:local')?></th></tr>
	</thead>
	<tbody>
		<tr>
			<td><?=lang('cf:location:locallocation')?></td>
			<td>
				<?=form_dropdown('cf_locations[local][location_id]', $file_upload_prefs, ((isset($local['location_id']) != FALSE) ? $local['location_id'] : '') ); ?> <br />
				<?=lang('cf:location:locallocation_exp')?>
			</td>
		</tr>
	</tbody>
</table>

<table class="mainTable cf_locations_s3">
	<thead>
		<tr><th colspan="2"><?=lang('cf:location:s3')?></th></tr>
	</thead>
	<tbody>
		<tr>
			<td><?=lang('cf:location:s3:key')?></td>
			<td>
				<input type="text" name="cf_locations[s3][key]" value="<?php if (isset($s3['key']) == FALSE) echo ''; else echo $s3['key'];?>"  /> <br />
				<?=lang('cf:location:s3:key_exp')?>
			</td>
		</tr>
		<tr>
			<td><?=lang('cf:location:s3:secret_key')?></td>
			<td>
				<input type="text" name="cf_locations[s3][secret_key]" value="<?php if (isset($s3['secret_key']) == FALSE) echo ''; else echo $s3['secret_key'];?>"  /> <br />
				<?=lang('cf:location:s3:secret_key_exp')?>
			</td>
		</tr>
		<tr>
			<td><?=lang('cf:location:s3:bucket')?></td>
			<td>
				<input type="text" name="cf_locations[s3][bucket]" value="<?php if (isset($s3['bucket']) == FALSE) echo ''; else echo $s3['bucket'];?>"  /> <br />
				<?=lang('cf:location:s3:bucket_exp')?>
			</td>
		</tr>
		<tr>
			<td><?=lang('cf:location:s3:region')?></td>
			<td>
				<?php if (isset($s3['region']) == FALSE) $s3['region'] = '';?>
				<?php $options = array('us-east-1' => lang('cf:location:s3:region:us_east'), 'us-west-1' => lang('cf:location:s3:region:us_west'), 'EU' => lang('cf:location:s3:region:eu'), 'ap-southeast-1' => lang('cf:location:s3:region:asia'));?>
				<?=form_dropdown('cf_locations[s3][region]', $options, $s3['region'] ); ?> <br />
			</td>
		</tr>
		<tr>
			<td><?=lang('cf:location:s3:acl')?></td>
			<td>
				<?php if (isset($s3['acl']) == FALSE) $s3['acl'] = 'public-read';?>
				<?php $options = array('public-read' => lang('cf:location:s3:acl:public-read'), 'authenticated-read' => lang('cf:location:s3:acl:authenticated-read'), 'private' => lang('cf:location:s3:acl:private'));?>
				<?=form_dropdown('cf_locations[s3][acl]', $options, $s3['acl'] ); ?> <br />
				<?=lang('cf:location:s3:acl_exp')?>
			</td>
		</tr>
	</tbody>
</table>

<table class="mainTable cf_locations_cloudfiles">
	<thead>
		<tr><th colspan="2"><?=lang('cf:location:cloudfiles')?></th></tr>
	</thead>
	<tbody>
		<tr>
			<td><?=lang('cf:location:cloudfiles:username')?></td>
			<td>
				<input type="text" name="cf_locations[cloudfiles][username]" value="<?php if (isset($cloudfiles['username']) == FALSE) echo ''; else echo $cloudfiles['username'];?>"  /> <br />
			</td>
		</tr>
		<tr>
			<td><?=lang('cf:location:cloudfiles:api')?></td>
			<td>
				<input type="text" name="cf_locations[cloudfiles][api]" value="<?php if (isset($cloudfiles['api']) == FALSE) echo ''; else echo $cloudfiles['api'];?>"  /> <br />
			</td>
		</tr>
		<tr>
			<td><?=lang('cf:location:cloudfiles:container')?></td>
			<td>
				<input type="text" name="cf_locations[cloudfiles][container]" value="<?php if (isset($cloudfiles['container']) == FALSE) echo ''; else echo $cloudfiles['container'];?>"  /> <br />
			</td>
		</tr>
		<tr>
			<td><?=lang('cf:location:cloudfiles:region')?></td>
			<td>
				<?php if (isset($cloudfiles['region']) == FALSE) $cloudfiles['region'] = 'us';?>
				<?php $options = array('us' => lang('cf:location:cloudfiles:region:us'), 'uk' => lang('cf:location:cloudfiles:region:uk'));?>
				<?=form_dropdown('cf_locations[cloudfiles][region]', $options, $cloudfiles['region'] ); ?> <br />
			</td>
		</tr>
	</tbody>
</table>

<a href="#" class="cf_test_location"><?=lang('cf:test_location')?></a>