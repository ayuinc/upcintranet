<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

if (isset($this->EE) == FALSE) $this->EE =& get_instance(); // For EE 2.2.0+

// Default Field Columns
$config['cf_columns']['row_num']	= $this->EE->lang->line('cf:row_num');
$config['cf_columns']['id']			= $this->EE->lang->line('cf:id');
$config['cf_columns']['filename']	= $this->EE->lang->line('cf:filename');
$config['cf_columns']['title']		= $this->EE->lang->line('cf:title');
$config['cf_columns']['url_title']	= '';
$config['cf_columns']['desc']		= $this->EE->lang->line('cf:desc');
$config['cf_columns']['category']	= '';
$config['cf_columns']['cffield_1']	= '';
$config['cf_columns']['cffield_2']	= '';
$config['cf_columns']['cffield_3']	= '';
$config['cf_columns']['cffield_4']	= '';
$config['cf_columns']['cffield_5']	= '';

// Defaults
$config['cf_defaults']['upload_location'] = 'local';
$config['cf_defaults']['categories'] = array();
$config['cf_defaults']['show_stored_files'] = 'yes';
$config['cf_defaults']['stored_files_by_author'] = 'no';
$config['cf_defaults']['stored_files_search_type'] = 'entry';
$config['cf_defaults']['show_import_files'] = 'no';
$config['cf_defaults']['import_path'] = '';
$config['cf_defaults']['jeditable_event'] = 'click';
$config['cf_defaults']['file_limit'] = '';
$config['cf_defaults']['file_extensions'] = '*.*';
$config['cf_defaults']['entry_id_folder'] = 'yes';
$config['cf_defaults']['prefix_entry_id'] = 'yes';
$config['cf_defaults']['hybrid_upload'] = 'yes';
$config['cf_defaults']['locked_url_fieldtype'] = 'no';
$config['cf_defaults']['show_download_btn'] = 'no';
$config['cf_defaults']['show_file_replace'] = 'no';
$config['cf_defaults']['locations']['local']['location'] = 0;
$config['cf_defaults']['locations']['s3']['key'] = '';
$config['cf_defaults']['locations']['s3']['secret_key'] = '';
$config['cf_defaults']['locations']['s3']['bucket'] = '';
$config['cf_defaults']['locations']['s3']['region'] = 'us-east-1';
$config['cf_defaults']['locations']['s3']['acl'] = 'public-read';
$config['cf_defaults']['locations']['s3']['storage'] = 'standard';
$config['cf_defaults']['locations']['s3']['force_download'] = 'no';
$config['cf_defaults']['locations']['s3']['directory'] = '';
$config['cf_defaults']['locations']['s3']['cloudfront_domain'] = '';
$config['cf_defaults']['locations']['cloudfiles']['username'] = '';
$config['cf_defaults']['locations']['cloudfiles']['api'] = '';
$config['cf_defaults']['locations']['cloudfiles']['container'] = '';
$config['cf_defaults']['locations']['cloudfiles']['region'] = 'us';
$config['cf_defaults']['locations']['cloudfiles']['cdn_uri'] = '';
$config['cf_defaults']['locations']['ftp']['hostname'] = '';
$config['cf_defaults']['locations']['ftp']['port'] = '21';
$config['cf_defaults']['locations']['ftp']['username'] = '';
$config['cf_defaults']['locations']['ftp']['password'] = '';
$config['cf_defaults']['locations']['ftp']['passive'] = 'yes';
$config['cf_defaults']['locations']['ftp']['path'] = '/';
$config['cf_defaults']['locations']['ftp']['url'] = '';
$config['cf_defaults']['locations']['ftp']['ssl'] = 'no';
$config['cf_defaults']['locations']['ftp']['debug'] = TRUE;
$config['cf_defaults']['locations']['sftp']['hostname'] = '';
$config['cf_defaults']['locations']['sftp']['port'] = '22';
$config['cf_defaults']['locations']['sftp']['username'] = '';
$config['cf_defaults']['locations']['sftp']['password'] = '';
$config['cf_defaults']['locations']['sftp']['passive'] = 'yes';
$config['cf_defaults']['locations']['sftp']['path'] = '/';
$config['cf_defaults']['locations']['sftp']['url'] = '';
$config['cf_defaults']['columns'] = $config['cf_columns'];


// Upload Locations
$config['cf_upload_locs']['local']	= $this->EE->lang->line('cf:local');
$config['cf_upload_locs']['s3']		= $this->EE->lang->line('cf:s3');
$config['cf_upload_locs']['cloudfiles'] = $this->EE->lang->line('cf:cloudfiles');
$config['cf_upload_locs']['ftp'] = $this->EE->lang->line('cf:ftp');
$config['cf_upload_locs']['sftp'] = $this->EE->lang->line('cf:sftp');

// S3
$config['cf_s3_regions']['us-east-1']	= 'REGION_US_E1';
$config['cf_s3_regions']['us-west-1']	= 'REGION_US_W1';
$config['cf_s3_regions']['us-west-2']   = 'REGION_US_W2';
$config['cf_s3_regions']['eu']          = 'REGION_EU_W1';
$config['cf_s3_regions']['ap-southeast-1']	= 'REGION_APAC_SE1';
$config['cf_s3_regions']['ap-southeast-2']  = 'REGION_APAC_SE2';
$config['cf_s3_regions']['ap-northeast-1']	= 'REGION_APAC_NE1';
$config['cf_s3_regions']['sa-east-1']  = 'REGION_SA_E1';
$config['cf_s3_acl']['private']	= 'ACL_PRIVATE';
$config['cf_s3_acl']['public-read']	= 'ACL_PUBLIC';
$config['cf_s3_acl']['authenticated-read']	= 'ACL_AUTH_READ';
$config['cf_s3_storage']['standard']= 'STORAGE_STANDARD';
$config['cf_s3_storage']['reduced']	= 'STORAGE_REDUCED';

// Cloudfiles
$config['cf_cloudfiles_regions']['us']	= 'US_AUTHURL';
$config['cf_cloudfiles_regions']['uk']	= 'UK_AUTHURL';



// Custom
//$config['channel_files']['fields'][51]['upload_location'] = 's3';
//$config['channel_files']['fields'][51]['locations']['s3']['key'] = 'AAA';
//$config['channel_files']['fields'][51]['locations']['s3']['secret_key'] = 'BBB';
//$config['channel_files']['fields'][51]['locations']['s3']['bucket'] = 'AAAAA';
//$config['channel_files']['fields'][51]['locations']['s3']['region'] = 'us-east-1';
//$config['channel_files']['fields'][51]['locations']['s3']['acl'] = 'public-read';
//$config['channel_files']['fields'][51]['locations']['s3']['storage'] = 'standard';
//$config['channel_files']['fields'][51]['locations']['s3']['force_download'] = 'no';
//$config['channel_files']['fields'][51]['locations']['s3']['directory'] = '';
//$config['channel_files']['fields'][51]['locations']['s3']['cloudfront_domain'] = '';
//$config['channel_files']['fields'][51]['columns']['title'] = '123 test';