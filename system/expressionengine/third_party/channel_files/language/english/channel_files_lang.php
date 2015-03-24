<?php if (!defined('BASEPATH')) die('No direct script access allowed');

$lang = array(

// Required for MODULES page
'channel_files'					=>	'Channel Files',
'channel_files_module_name'		=>	'Channel Files',
'channel_files_module_description'	=>	'Enables Files to be associated with an entry.',

//----------------------------------------
'cf:home'			=>	'Home',
'cf:docs' 			=>	'Documentation',
'cf:yes'			=>	'Yes',
'cf:no'				=>	'No',
'cf:pref'			=>	'Preference',
'cf:value'			=>	'Value',
'cf:username'		=>	'Username',
'cf:password'		=>	'Password',
'cf:host_ip'		=>	'Hostname/IP',
'cf:port'			=>	'Port',
'cf:path'			=>	'Remote Path',
'cf:url'			=>	'Remote URL',

'cf:download_log'		=>	'Download Log',
'cf:file'				=>	'File',
'cf:entry'				=>	'Entry',
'cf:member'				=>	'Member',
'cf:ip'					=>	'IP Address',
'cf:date'				=>	'Date',
'cf:no_logs'			=>	'No downloads have yet been recorded',
'cf:search_files'		=>	'Search Files',
'cf:search_entries'		=>	'Search Entries',
'cf:search_members'		=>	'Search Members',
'cf:search:date_from'	=>	'Date From',
'cf:search:date_to'		=>	'Date To',

// Fieldtype Settings
'cf:specify_pref_cred' =>	'Specify Credential & Settings',
'cf:local'             =>	'Local Server',
'cf:test_location'     =>	 'Test Location',

// S3
'cf:s3'                        =>	'Amazon S3',
'cf:s3:key'                    =>	'AWS KEY',
'cf:s3:key_exp'                =>	'Amazon Web Services Key. Found in the AWS Security Credentials.',
'cf:s3:secret_key'             =>	'AWS SECRET KEY',
'cf:s3:secret_key_exp'         =>	'Amazon Web Services Secret Key. Found in the AWS Security Credentials.',
'cf:s3:bucket'                 =>	'Bucket',
'cf:s3:bucket_exp'             =>	'Every object stored in Amazon S3 is contained in a bucket. Must be unique.',
'cf:s3:region'                 =>	'Bucket Region',
'cf:s3:region:us-east-1'       => 'USA-East (Northern Virginia)',
'cf:s3:region:us-west-1'       => 'USA-West (Northern California)',
'cf:s3:region:us-west-2'       => 'USA-West 2 (Oregon)',
'cf:s3:region:eu'              => 'Europe (Ireland)',
'cf:s3:region:ap-southeast-1'  => 'Asia Pacific (Singapore)',
'cf:s3:region:ap-southeast-2'  => 'Asia Pacific (Sydney, Australia)',
'cf:s3:region:ap-northeast-1'  => 'Asia Pacific (Tokyo, Japan)',
'cf:s3:region:sa-east-1'       => 'South America - (Sao Paulo, Brazil)',
'cf:s3:acl'                    =>	'ACL',
'cf:s3:acl_exp'                =>	'ACL is a mechanism which decides who can access an object.',
'cf:s3:acl:public-read'        =>	'Public READ',
'cf:s3:acl:authenticated-read' =>	'Public Authenticated Read',
'cf:s3:acl:private'            =>	'Owner-only read',
'cf:s3:storage'                =>	'Storage Redundancy',
'cf:s3:storage:standard'       =>	'Standard storage redundancy',
'cf:s3:storage:reduced'        =>	'Reduced storage redundancy (cheaper)',
'cf:s3:force_download'			=>	'Force Download',
'cf:s3:force_download_exp'		=>	'Setting this option to yes will mark all uploaded files to be downloaded instead of viewed inline by the browser.',
'cf:s3:directory'				=>	'Subdirectory (optional)',
'cf:s3:cloudfrontd'             =>  'Cloudfront Domain (optional)',

// CloudFiles
'cf:cloudfiles'=>'Rackspace Cloud Files',
'cf:cloudfiles:username'	=>	'Username',
'cf:cloudfiles:api'			=>	'API Key',
'cf:cloudfiles:container'	=>	'Container',
'cf:cloudfiles:region'		=>	'Region',
'cf:cloudfiles:region:us'	=>	'United States',
'cf:cloudfiles:region:uk'	=>	'United Kingdom (London)',
'cf:cloudfiles:cdn_uri'		=>	'CDN URI Override',

// FTP
'cf:ftp'					=>	'Remote FTP Server',
'cf:ftp:passive'			=>	'Passive Mode',
'cf:ftp:ssl'				=>	'SSL Mode',

// SFTP
'cf:sftp'					=>	'Remote SFTP Server (SSH)',


'cf:loc_settings'	=>	'Location Settings',
'cf:fieldtype_settings'=>	'Fieldtype Settings',

'cf:upload_location'              =>	'Upload Location',
'cf:categories'                   =>	'Categories',
'cf:categories_explain'           =>	'Seperate each category with a comma.',
'cf:show_stored_files'            =>	'Show Stored Files',
'cf:limt_stored_files_author'     =>	'Limit Stored Files by Author?',
'cf:limt_stored_files_author_exp' =>	'When using the Stored Files feature, all files uploaded by everyone will be searched. <br />Select YES to limit the searching to files uploaded by the current member.',
'cf:stored_files_search_type'     =>	'Stored Files Search Type',
'cf:entry_based'           =>	'Entry Based',
'cf:file_based'            =>	'File Based',
'cf:show_import_files'     =>	'Show Import Files',
'cf:show_import_files_exp' =>	'The Import Files feature allows you to add files from the local filesystem',
'cf:show_file_replace'     =>  'Show File Replace Button',
'cf:import_path'           =>	'Import Path',
'cf:import_path_exp'       =>	'Path where the files will be located',
'cf:jeditable_event'       =>	'Edit Field Event',
'cf:click'                 =>	'Click',
'cf:hover'                 =>	'Hover',
'cf:file_limit'            =>	'File Limit',
'cf:file_limit_exp'        =>	'Limit the amount of files a user can upload to this field. Leave empty to allow unlimited files.',
'cf:file_extensions'       =>	'File Extensions',
'cf:file_extensions_exp'   =>	'Limit the selection to certain file extensions only. Example: *.jpg;*.gif',
'cf:store_entry_id_folder' =>	'Store files in entry_id folders.',
'cf:store_entry_id_folder_exp'    =>	'This option changes the default behaviour of storing all files uploaded in a seperate directory for each entry. <br /> <strong style="color:red">THIS SHOULD ONLY BE CHANGED ONCE! (At the very beginning)</strong>',
'cf:prefix_entry_id'		=>	'Prefix files with Entry ID',
'cf:prefix_entry_id_exp'	=>	'To avoid files with the same name of being overwritten we prefix the files with the entry_id. If this option is set to \'no\' we will add a number to the end of the file if one with the same name exists.',
'cf:hybrid_upload'                => 'Hybrid Upload',
'cf:hybrid_upload_exp'            =>'Enabling this option will turn on HTML 5 uploading, otherwise Flash uploading will occur.',
'cf:locked_url_fieldtype'	=>	'Obfuscate file URL\'s in the fieldtype',
'cf:locked_url_fieldtype_exp'=>	'Normally the File URL\'s are direct links to the files. But in some cases you want the file location to be secret. Enable this option to encrypt the File URL.<br>NOTE: This will prevent any WYSIWYG Channel Files plugin to work.',
'cf:act_url'                      =>	'ACT URL',
'cf:act_url:exp'                  =>	'This URL is going to be used for all AJAX calls and file uploads',
'cf:show_download_btn'	=>	'Show Download Button',

'cf:actions:replace'   =>  'Replace File',

// Field Columns
'cf:field_columns'		=>	'Field Columns',
'cf:field_columns_exp'	=>	'Specify a label for each column, leave the field blank to disable the column.',
'cf:row_num'		=>	'#',
'cf:id'				=>	'ID',
'cf:title'			=>	'Title',
'cf:url_title'		=>	'URL Title',
'cf:desc'			=>	'Description',
'cf:category'		=>	'Category',
'cf:filename'		=>	'Filename',
'cf:actions:edit'	=>	'Edit',
'cf:actions:cover'	=>	'Cover',
'cf:actions:move'	=>	'Move',
'cf:actions:del'	=>	'Delete',
'cf:cffield_1'		=>	'Field 1',
'cf:cffield_2'		=>	'Field 2',
'cf:cffield_3'		=>	'Field 3',
'cf:cffield_4'		=>	'Field 4',
'cf:cffield_5'		=>	'Field 5',

// PBF
'cf:drophere'           =>	'Drop Your Files Here....',
'cf:file_remain'        =>	'Files Remaining:',
'cf:missing_settings'   =>	'Missing Channel Files settings for this field.',
'cf:no_files'           =>	'No files have yet been uploaded.',
'cf:crossdomain_detect' =>	'CROSSDOMAIN: The current domain does not mach the ACT URL domain. Upload may fail due crossdomain restrictions.',

'cf:upload_files'	=>	'Upload Files',
'cf:stored_files'	=>	'Stored Files',
'cf:stop_upload'	=>	'Stop Upload',
'cf:row_num'		=>	'#',
'cf:id'				=>	'ID',
'cf:title'			=>	'Title',
'cf:desc'			=>	'Description',
'cf:category'		=>	'Category',
'cf:filename'		=>	'Filename',
'cf:filesize'		=>	'Filesize',
'cf:actions'		=>	'Actions',
'cf:actions:edit'	=>	'Edit',
'cf:actions:cover'	=>	'Cover',
'cf:actions:move'	=>	'Move',
'cf:actions:del'	=>	'Delete',

'cf:json:click2edit'		=> '<span>Click to edit..</span>',
'cf:json:hover2edit'		=> '<span>Hover to edit..</span>',
'cf:json:file_limit_reached'=> 'ERROR: File Limit Reached',
'cf:json:xhr_reponse_error'	=> "Server response was not as expected, probably a PHP error. <a href='#' class='OpenError'>OPEN ERROR</a>",
'cf:json:xhr_status_error'	=> "Upload request failed, no HTTP 200 Return Code! <a href='#' class='OpenError'>OPEN ERROR</a>",
'cf:json:del_file'			=> 'Are you sure you want to delete this file?',
'cf:json:unlink_file'		=> 'Are you sure you want to unlink this file?',
'cf:json:linked_force_del'	=> "This file is linked with other entries, are you sure you want to delete it? \n The other references will also be deleted!",
'cf:json:submitwait'		=> 'Your uploaded files are being stored. This may take a while, depending on the size of the files.',

// Import Files
'cf:import_files'		=> 'Import Files',
'cf:import:bad_path'	=> 'The supplied import path does not exist (or is inaccessible).',
'cf:import:no_files'	=> 'No files..',
'cf:import:remain_limit'=> 'You cannot add more files, you have already reached the limit set',

// Stored Files
'cf:limit'         =>	'Limit',
'cf:keywords'      =>	'Keywords',
'cf:loading_files' => 'Loading Files, please wait...',
'cf:no_keywords'   =>	'No Keywords!',
'cf:no_results'    =>	'No files found matching your criteria..',

// Pagination
'cf:pag_first_link' => '&lsaquo; First',
'cf:pag_last_link' => 'Last &rsaquo;',

// Errors
'cf:file_arr_empty'            => 'No file was uploaded or file is not allowed by EE.(See EE Mime-type settings).',
'cf:tempkey_missing'           => 'The temp key was not found',
'cf:file_upload_error'         => 'No file was uploaded. (Maybe filesize was too big)',
'cf:no_settings'               => 'No settings exist for this fieldtype',
'cf:location_settings_failure' =>	'Upload Location Settings Missing',
'cf:location_load_failure'     =>	'Failure to load Upload Location Class',
'cf:tempdir_error'             =>	'The Local Temp dir is either not writable or does not exist',
'cf:temp_dir_failure'          =>	'Failed to create the temp dir, through Upload Location Class',
'cf:file_upload_error'         =>	'Failed to upload the file, through Upload Location Class',
'cf:no_upload_location_found'  => 'Upload Location has not been found!.',
'cf:file_to_big'               => 'The file is too big. (See module settings for max file size).',
'cf:extension_not_allow'       => 'The file extension is not allowed. (See module settings for file extensions)',
'cf:targetdir_error'           => 'The target directory is either not writable or does not exist',
'cf:file_move_error'           => 'Failed to move uploaded file to the temp directory, please check upload path permissions etc.',












// MOD
'cf:bad_data'		=>	'BAD DATA',
'cf:time_limit_passed'=>	'This download url is not longer valid',
'cf:invalid_ip'		=>	'Invalid IP',




// END
''=>''
);

/* End of file lang.channel_files.php */
/* Location: ./system/expressionengine/third_party/channel_files/language/english/lang.channel_files.php */
