<script type="text/javascript">
ChannelFiles.LANG = <?=$langjson?>;
</script>

<script id="ChannelFilesSingleField" type="text/x-jquery-tmpl">
<tr class="File {{#is_primary}}PrimaryFile{{/is_primary}}">
	{{#show_row_num}}<td class="num"></td>{{/show_row_num}}
	{{#show_id}}<td>{{{file_id}}}</td>{{/show_id}}
	{{#show_filename}}<td class="cfilename">
			{{#fileurl}}<a href="{{{fileurl}}}" target="_blank" class="CFFileExt {{{extension}}}">{{{filename}}}</a>{{/fileurl}}
			{{^fileurl}}<span href="{{{fileurl}}}" target="_blank" class="CFFileExt {{{extension}}}">{{{filename}}}{{/fileurl}}
	</td>{{/show_filename}}
	{{#show_title}}<td rel="title">{{{file_title}}}</td>{{/show_title}}
	{{#show_url_title}}<td rel="url_title">{{{file_url_title}}}</td>{{/show_url_title}}
	{{#show_desc}}<td rel="description">{{{description}}}</td>{{/show_desc}}
	{{#show_category}}<td rel="category">{{{category}}}</td>{{/show_category}}
	{{#show_cffield_1}}<td rel="cffield_1">{{{cffield_1}}}</td>{{/show_cffield_1}}
	{{#show_cffield_2}}<td rel="cffield_2">{{{cffield_2}}}</td>{{/show_cffield_2}}
	{{#show_cffield_3}}<td rel="cffield_3">{{{cffield_3}}}</td>{{/show_cffield_3}}
	{{#show_cffield_4}}<td rel="cffield_4">{{{cffield_4}}}</td>{{/show_cffield_4}}
	{{#show_cffield_5}}<td rel="cffield_5">{{{cffield_5}}}</td>{{/show_cffield_5}}
	<td>
		<a href="javascript:void(0)" class="FileMove gIcon" title="Move"></a>
		{{#show_download_btn}} {{#fileurl}} <a href="{{{fileurl}}}" target="_blank" class="FileDownload gIcon"></a> {{/fileurl}} {{/show_download_btn}}
		{{#show_file_replace}}<a href='#' class='gIcon FileReplace'></a>{{/show_file_replace}}
		<a href="#" class="gIcon FilePrimary {{#is_primary}}StarIcon{{/is_primary}} {{^is_primary}}StarGreyIcon{{/is_primary}}" title="Cover"></a>
		<a href="#" {{#is_linked}}class="gIcon FileDel FileLinked" title="Unlink"{{/is_linked}} {{^is_linked}}class="gIcon FileDel" title="Delete"{{/is_linked}}></a>
		<textarea name="{{{field_name}}}[files][][data]" class="FileData cfhidden">{{{json_data}}}</textarea>
	</td>
</tr>
</script>

