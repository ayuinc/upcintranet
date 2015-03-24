<div class="left">
<?php foreach ($files[0] as $file):?>
	<div class="rFile"><em class="CFFileExt <?=$file->extension?>"></em><?=$file->title?> <small><?=$file->filename?> (<?=$this->channel_files_helper->format_bytes($file->filesize)?>)</small> <a href="#" rel="<?=$file->file_id?>">&nbsp;</a></div>
<?php endforeach;?>
</div>

<div class="right">
<?php foreach ($files[1] as $file):?>
	<div class="rFile"><em class="CFFileExt <?=$file->extension?>"></em><?=$file->title?> <small><?=$file->filename?> (<?=$this->channel_files_helper->format_bytes($file->filesize)?>)</small> <a href="#" rel="<?=$file->file_id?>">&nbsp;</a></div>
<?php endforeach;?>
</div>

<br clear="all">