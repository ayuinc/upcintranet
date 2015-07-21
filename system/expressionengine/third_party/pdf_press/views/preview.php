<ul class="tab_menu" id="tab_menu_tabs">
<li class="content_tab"> <a href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=pdf_press'.AMP.'method=index'?>"><?=lang('index')?></a>  </li> 
<li class="content_tab"> <a href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=pdf_press'.AMP.'method=settings'?>"><?=lang('settings') ?></a></li>
<li class="content_tab "> <a href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=pdf_press'.AMP.'method=fonts'?>"><?=lang('fonts') ?></a></li>
<li class="content_tab current"> <a href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=pdf_press'.AMP.'method=preview'?>"><?=lang('preview')?></a>  </li> 
</ul>
<div class="clear_left shun"></div>
<style>
	.ok {color:green;}
	.warning {color:orange;}
	.failed {color:red;}
</style>
<div style="padding: 10px;">
	<table class="mainTable">
		<thead>
			<tr>
				<th colspan="2" class="even">
					<?=lang('preview')?>
				</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td class="odd" colspan="2">
					<p><?=lang('preview_desc')?></p>
				</td>
			</tr>
			<tr>
				<td class="odd">
					<label for="size">Size:</label><br/>
					<select id="size">
						<?php foreach($paper_sizes as $paper) : ?>
								<option value="<?=$paper?>" <?= ($paper == DOMPDF_DEFAULT_PAPER_SIZE ? 'selected' : '')?> ><?=$paper?></option>
						<?php endforeach; ?>	
					</select>
				</td>
				<td class="even">
					<label for="orientation">Orientation:</label><br/>
					<select id="orientation">
						<option value="portrait">Portrait (default)</option>
						<option value="landscape">Landscape</option>
					</select>
				</td>
			</tr>
			<tr>
				<td colspan="2" class="odd">
					<label for="url">EE Template Path (ex. site/test):</label>
					<input type="text" id="url" name="url" style="width:500px;" class="search" />
				</td>
			</tr>
		</tbody>
	</table>
	<button id="get_pdf" class="submit"><?=lang('submit')?></button>
	<!--<div id="loadingDiv"><img src="<?= URL_THIRD_THEMES ?>pdf_press/img/ajax-loader.gif" style="width:20px;"></div>-->
	<p>&nbsp;</p>
	<iframe id="preview" width="100%" height="600" name="preview" src="about:blank" frameborder="0" marginheight="0" marginwidth="0"></iframe>
	
	<script type="text/javascript">
	function resizePreview(){
	  var preview = $("#preview");
	  preview.height($(window).height() - preview.offset().top - 2);
	}

	function setHash(hash) {
	  location.hash = "#"+hash;
	}
	
	$(document).ready(function() {
		$('#get_pdf').on("click", function() {
			var url = $('#url').val();
			var preview = $("#preview");
			var size = $("#size").val();
			var orientation = $('#orientation').val();
			
			$("#preview").attr("src", "<?= URL_THIRD_THEMES ?>pdf_press/img/ajax-loader.gif");
			
			var dom_src = "<?=$dom_path?>" + encodeURI(url) + '&size='+ encodeURI(size) + '&orientation=' + orientation + '#toolbar=0&view=FitH&statusbar=0&messages=0&navpanes=0';
			$("#preview").attr("src", dom_src);
			

		});
	});
	
	</script>
</div>