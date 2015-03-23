<?php if (!defined('BASEPATH')) die('No direct script access allowed');

/**
 * Channel Files Module Tags
 *
 * @package			DevDemon_ChannelFiles
 * @author			DevDemon <http://www.devdemon.com> - Lead Developer @ Parscale Media
 * @copyright 		Copyright (c) 2007-2011 Parscale Media <http://www.parscale.com>
 * @license 		http://www.devdemon.com/license/
 * @link			http://www.devdemon.com
 * @see				http://expressionengine.com/user_guide/development/module_tutorial.html#core_module_file
 */
class Channel_files
{

	/**
	 * Constructor
	 *
	 * @access public
	 *
	 * Calls the parent constructor
	 */
	public function __construct()
	{
		$this->EE =& get_instance();
		$this->site_id = $this->EE->config->item('site_id');
		$this->EE->load->library('channel_files_helper');
		$this->EE->load->model('channel_files_model');

		$this->EE->config->load('cf_config');
	}

	// ********************************************************************************* //

	public function files()
	{
		// Entry ID
		$entry_id = $this->EE->TMPL->fetch_param('entry_id');

		// URL Title
		if ($this->EE->TMPL->fetch_param('url_title') != FALSE)
		{
			$entry_id = 9999999;
			$query = $this->EE->db->query("SELECT entry_id FROM exp_channel_titles WHERE url_title = '".$this->EE->TMPL->fetch_param('url_title')."' LIMIT 1");
			if ($query->num_rows() > 0) $entry_id = $query->row('entry_id');
		}

		// Which Fields?
		$fields = $this->EE->channel_files_model->get_fields_from_params($this->EE->TMPL->tagparams);

		return $this->EE->channel_files_model->parse_template($entry_id, $fields, $this->EE->TMPL->tagparams, $this->EE->TMPL->tagdata);
	}

	// ********************************************************************************* //

	public function grouped_files($legacy=FALSE)
	{
		// Variable prefix
		$this->prefix = $this->EE->TMPL->fetch_param('prefix', 'file') . ':';

		$params = $this->EE->TMPL->tagparams;

		// Which Fields?
		$fields = $this->EE->channel_files_model->get_fields_from_params($params);

		// Shoot the query
		$this->EE->db->select('category');
		$this->EE->db->from('exp_channel_files');

		// Field ID
		if ($fields !== FALSE)
		{
			if (is_array($fields) === TRUE)
			{
				$this->EE->db->where_in('field_id', $fields);
			}
			else
			{
				$this->EE->db->where('field_id', $fields);
			}
		}

		// Channel?
		$channels = FALSE;
		if (isset($params['channel']) === TRUE)
		{
			$channels = $this->EE->channel_files_model->get_channel_id($params['channel']);
			if (is_array($channels) === TRUE) $this->EE->db->where_in('channel_id', $channels);
			else $this->EE->db->where('channel_id', $channels);
		}

		// Category
		if (isset($params['category']) === TRUE)
		{
			$cat = $params['category'];

			// Multiple Categories?
			if (strpos($cat, '|') !== FALSE)
			{
				$cats = explode('|', $cat);
				$this->EE->db->where_in('category', $cats);
			}
			else
			{
				$this->EE->db->where('category', $cat);
			}
		}

		// Entry ID
		$entry_id = $this->EE->TMPL->fetch_param('entry_id');

		// URL Title
		if ($this->EE->TMPL->fetch_param('url_title') != FALSE)
		{
			$entry_id = 9999999;
			$query = $this->EE->db->query("SELECT entry_id FROM exp_channel_titles WHERE url_title = '".$this->EE->TMPL->fetch_param('url_title')."' LIMIT 1");
			if ($query->num_rows() > 0) $entry_id = $query->row('entry_id');
		}

		// Entry ID
		if ($entry_id != FALSE)
		{
			$this->EE->db->where('entry_id', $entry_id);
		}

		$this->EE->db->group_by('category');
		$query = $this->EE->db->get();

		// Get categories!
		$categories = array();

		foreach ($query->result() as $key => $value)
		{
			if ($value->category == FALSE) continue;
			$categories[] = $value->category;
		}

		// Empty category?
		if (empty($categories) == TRUE)
		{
			$this->EE->TMPL->log_item("CHANNEL FILES: Found files but no categories.");
			return $this->EE->channel_files_helper->custom_no_results_conditional($this->prefix.'no_files', $this->EE->TMPL->tagdata);
		}

		// Sort by category
		if (strtolower($this->EE->TMPL->fetch_param('category_sort')) != 'desc')
			sort($categories);
		else rsort($categories);

		// -----------------------------------------
		// Find our pair data!
		// -----------------------------------------
		$pair_data = FALSE;
		foreach ($this->EE->TMPL->var_pair as $key => $val)
		{
			if (substr($key, 0, 5) == 'files') $pair_data = $key;
		}

		// Grab the {files} var pair
		if ($pair_data == FALSE)
		{
			$this->EE->TMPL->log_item("CHANNEL FILES: No {files} var pair found.");
			return $this->EE->channel_files_helper->custom_no_results_conditional($this->prefix.'no_files', $this->EE->TMPL->tagdata);
		}

		// Has parameters?
		$pair_params = array();
		$var_pair = $pair_data;
		if (is_array($this->EE->TMPL->var_pair[$var_pair]) == TRUE)
		{
			$pair_params = $this->EE->TMPL->var_pair[$var_pair];
			$pair_data = $this->EE->channel_files_helper->fetch_data_between_var_pairs_params($var_pair, 'files', $this->EE->TMPL->tagdata);
		}
		else
		{
			$pair_data = $this->EE->channel_files_helper->fetch_data_between_var_pairs('files', $this->EE->TMPL->tagdata);
		}

		/** ----------------------------------------
		/**  Switch? / Normal URL
		/** ----------------------------------------*/

		// Switch=""
		$parse_switch = FALSE;
		if ( preg_match( "/".LD."({$this->prefix}switch\s*=.+?)".RD."/is", $pair_data, $switch_matches ) > 0 )
		{
			$parse_switch = TRUE;
			$switch_param = $this->EE->functions->assign_parameters($switch_matches['1']);
		}

		// {file:url}
		$parse_full_url = FALSE;
		if ( strpos($this->EE->TMPL->tagdata, LD.$this->prefix.'url'.RD) !== FALSE)
		{
			$parse_full_url = TRUE;
		}

		// {file:locked_url}
		$parse_locked_url = FALSE;
		if ( strpos($this->EE->TMPL->tagdata, LD.$this->prefix.'locked_url'.RD) !== FALSE)
		{
			$parse_locked_url = TRUE;

			// Grab Router URL
			$locked_router_url = $this->EE->channel_files_helper->get_router_url('url', 'locked_file_url');
		}

		// -----------------------------------------
		// Loop over all categories
		// -----------------------------------------
		$OUT = '';
		foreach ($categories as $category)
		{
			// Shoot the query
			$this->EE->db->select('*');
			$this->EE->db->from('exp_channel_files');
			$this->EE->db->where('category', $category);
			if (isset($pair_params['limit']) == TRUE) $this->EE->db->limit($pair_params['limit']);
			if (isset($params['channel']) === TRUE)
			{
				$channels = $this->EE->channel_files_model->get_channel_id($params['channel']);
				if (is_array($channels) === TRUE) $this->EE->db->where_in('channel_id', $channels);
				else $this->EE->db->where('channel_id', $channels);
			}
			if ($entry_id != FALSE) $this->EE->db->where('entry_id', $entry_id);

			$sort = (isset($pair_params['sort']) === TRUE && $pair_params['sort'] == 'desc') ? 'DESC': 'ASC';

			// Order by? (only if primary_only is false, since this would override our orderby)
			if (isset($pair_params['primary_only']) === FALSE)
			{
				if (isset($pair_params['orderby']) === FALSE) $pair_params['orderby'] = 'file_order';
				switch ($pair_params['orderby'])
				{
					case 'title':
						$this->EE->db->order_by('title', $sort);
						break;
					case 'upload_date':
						$this->EE->db->order_by('date', $sort);
						break;
					case 'filename':
						$this->EE->db->order_by('filename', $sort);
						break;
					case 'filesize':
						$this->EE->db->order_by('filesize', $sort);
						break;
					case 'file_id':
						$this->EE->db->order_by('file_id', $sort);
						break;
					case 'random':
						$this->EE->db->order_by('RAND()', FALSE);
						break;
					default:
						$this->EE->db->order_by('file_order', $sort);
					break;
				}
			}
			$query = $this->EE->db->get();


			$CATOUT = str_replace(LD.$this->prefix.'category'.RD, $category, $this->EE->TMPL->tagdata);
			$CATIMG = '';

			$total_files = $query->num_rows();

			foreach ($query->result() as $count => $file)
			{
				$temp = '';

				// Check for linked file!
				if ($file->link_entry_id > 1){
					$file->entry_id = $file->link_entry_id;
					$file->field_id = $file->link_field_id;
				}

				$vars = array();
				$vars[$this->prefix.'count'] = $count + 1;
				$vars[$this->prefix.'total'] = $total_files;
				$vars[$this->prefix.'title'] = $file->title;
				$vars[$this->prefix.'url_title'] = $file->url_title;
				$vars[$this->prefix.'description'] = $file->description;
				$vars[$this->prefix.'category'] = $file->category;
				$vars[$this->prefix.'filename'] = $file->filename;
				$vars[$this->prefix.'id'] = $file->file_id;
				$vars[$this->prefix.'mimetype'] = $file->mime;
				$vars[$this->prefix.'extension'] = $file->extension;
				$vars[$this->prefix.'md5'] = $file->md5;
				$vars[$this->prefix.'date'] = $file->date; // Deprecated since version 5
				$vars[$this->prefix.'upload_date'] = $file->date;
				$vars[$this->prefix.'downloads'] = $file->downloads;
				$vars[$this->prefix.'filesize'] = $this->EE->channel_files_helper->format_bytes($file->filesize);
				$vars[$this->prefix.'filesize_bytes'] = $file->filesize;
				$vars[$this->prefix.'field:1'] = $file->cffield_1;
				$vars[$this->prefix.'field:2'] = $file->cffield_2;
				$vars[$this->prefix.'field:3'] = $file->cffield_3;
				$vars[$this->prefix.'field:4'] = $file->cffield_4;
				$vars[$this->prefix.'field:5'] = $file->cffield_5;

				//----------------------------------------
				// Load Location
				//----------------------------------------
				if ($parse_full_url == TRUE)
				{
					// Get Field Settings!
					$settings = $this->EE->channel_files_helper->grab_field_settings($file->field_id);

					$dir = $file->entry_id;
					if (isset($settings['entry_id_folder']) && $settings['entry_id_folder'] == 'no')
					{
						$dir = FALSE;
					}

					if (isset($this->LOCS[$file->field_id]) == FALSE)
					{
						$location_type = $settings['upload_location'];
						$location_class = 'CF_Location_'.$location_type;
						$location_settings = $settings['locations'][$location_type];

						// Load Main Class
						if (class_exists('Cfile_Location') == FALSE) require PATH_THIRD.'channel_files/locations/cfile_location.php';

						// Try to load Location Class
						if (class_exists($location_class) == FALSE)
						{
							$location_file = PATH_THIRD.'channel_files/locations/'.$location_type.'/'.$location_type.'.php';

							require $location_file;
						}

						// Init!
						$this->LOCS[$file->field_id] = new $location_class($location_settings);
					}

					$fileurl = $this->LOCS[$file->field_id]->parse_file_url($dir, $file->filename);
					$vars[$this->prefix.'url'] = $fileurl;
					$vars[$this->prefix.'secure_url'] = str_replace('http://', 'https://', $fileurl);
				}


				// -----------------------------------------
				// Parse Locked URL's
				// -----------------------------------------
				if ($parse_locked_url == TRUE)
				{
					$locked = array('time' => $this->EE->localize->now + 3600, 'file_id' => $file->file_id, 'ip' => $this->EE->input->ip_address());
					$vars[$this->prefix.'locked_url'] = $locked_router_url . '&amp;key=' . base64_encode($this->EE->channel_files_helper->encrypt_string(serialize($locked)));
				}

				$temp = $this->EE->TMPL->parse_variables_row($pair_data, $vars);

				// -----------------------------------------
				// Parse Switch {switch="one|twoo"}
				// -----------------------------------------
				if ($parse_switch)
				{
					$sw = '';

					if ( isset( $switch_param[$this->prefix.'switch'] ) !== FALSE )
					{
						$sopt = explode("|", $switch_param[$this->prefix.'switch']);

						$sw = $sopt[($count + count($sopt)) % count($sopt)];
					}

					$temp = $this->EE->TMPL->swap_var_single($switch_matches['1'], $sw, $temp);
				}

				$CATIMG .= $temp;
			}

			if (empty($pair_params) == TRUE) $CATOUT = $this->EE->channel_files_helper->swap_var_pairs('files', $CATIMG, $CATOUT);
			else $CATOUT = $this->EE->channel_files_helper->swap_var_pairs_params($var_pair, 'files', $CATIMG, $CATOUT);

			$OUT .= $CATOUT;
		}


		return $OUT;
	}

	// ********************************************************************************* //

	public function zip()
	{
		// -----------------------------------------
		// Increase all types of limits!
		// -----------------------------------------
		@set_time_limit(0);
		@ini_set('memory_limit', '64M');
		@ini_set('memory_limit', '96M');
		@ini_set('memory_limit', '128M');
		@ini_set('memory_limit', '160M');
		@ini_set('memory_limit', '192M');

		error_reporting(E_ALL);
		@ini_set('display_errors', 1);

		// What Entry?
		$entry_id = $this->EE->channel_files_helper->get_entry_id_from_param();

		// Filename
		if ($this->EE->TMPL->fetch_param('filename') != FALSE)
		{
			$filename = strtolower($this->EE->security->sanitize_filename(str_replace(' ', '_', $this->EE->TMPL->fetch_param('filename'))));
		}
		else
		{
			$query = $this->EE->db->select('url_title')->from('exp_channel_titles')->where('entry_id', $entry_id)->get();
			$filename = substr($query->row('url_title'), 0 , 50);
		}

		// We need an entry_id
		if ($entry_id == FALSE)
		{
			show_error('No entry found! Unable to generate ZIP');
		}

		$this->EE->db->select('*');
		$this->EE->db->from('exp_channel_files');
		$this->EE->db->where('entry_id', $entry_id);

		//----------------------------------------
		// Field ID
		//----------------------------------------
		if ($this->EE->TMPL->fetch_param('field_id') != FALSE)
		{
			$this->EE->db->where('field_id', $this->EE->TMPL->fetch_param('field_id'));
		}

		//----------------------------------------
		// Field
		//----------------------------------------
		if ($this->EE->TMPL->fetch_param('field') != FALSE)
		{
			$group = $this->EE->TMPL->fetch_param('field');

			// Multiple Fields
			if (strpos($group, '|') !== FALSE)
			{
				$group = explode('|', $group);
				$groups = array();

				foreach ($group as $name)
				{
					$groups[] = $name;
				}
			}
			else
			{
				$groups = $this->EE->TMPL->fetch_param('field');
			}

			$this->EE->db->join('exp_channel_fields cf', 'cf.field_id = exp_channel_files.field_id', 'left');
			$this->EE->db->where_in('cf.field_name', $groups);
		}

		$query = $this->EE->db->get();

		//----------------------------------------
		// Shoot the query
		//----------------------------------------
		if ($query->num_rows() == 0)
		{
			show_error('No Files found! Unable to generate ZIP');
		}

		$files = $query->result();

		//----------------------------------------
		// Harvest Field ID!
		//----------------------------------------
		$tfields = array();
		foreach ($files as $file)
		{
			if ($file->link_file_id > 0) $tfields[] = $file->link_field_id;
			$tfields[] = $file->field_id;
		}

		$tfields = array_unique($tfields);

		//----------------------------------------
		// Load Location
		//----------------------------------------
		if (class_exists('Cfile_Location') == FALSE) require PATH_THIRD.'channel_files/locations/cfile_location.php';
		if (class_exists('CF_Location_local') == FALSE) require PATH_THIRD.'channel_files/locations/local/local.php';
		$LOCAL = new CF_Location_local();

		//----------------------------------------
		// Check Each Field
		//----------------------------------------
		$fields = array();
		foreach ($tfields as $field_id)
		{
			// Get Field Settings!
			$settings = $this->EE->channel_files_helper->grab_field_settings($field_id);

			if ($settings['upload_location'] != 'local') continue;

			$settings['locprefs'] = $LOCAL->get_location_prefs($settings['locations']['local']['location']);
			$fields[$field_id] = $settings;
		}

		//print_r($fields);

		if (empty($fields) == TRUE)
		{
			show_error('No suitable fields found! Unable to generate ZIP');
		}

		//----------------------------------------
		// Create .ZIP
		//----------------------------------------
		$zip = new ZipArchive();
		$zip_path = APPPATH."cache/channel_files/{$filename}.zip";
		if ($zip->open($zip_path, ZIPARCHIVE::OVERWRITE) !== true)
		{
			show_error('Unable to Create ZIP. ZIP Open ERROR');
		}

		//----------------------------------------
		// Add Files!
		//----------------------------------------
		foreach ($files as $file)
		{
			$entry_id = $file->entry_id;
			$field_id = $file->field_id;

			if ($file->link_file_id > 0)
			{
				$field_id = $file->link_field_id;
				$entry_id = $file->link_entry_id;
			}

			// Good Field?
			if (isset($fields[$field_id]) == FALSE) continue;

			$dir = $entry_id.'/';
			if (isset($settings['entry_id_folder']) && $settings['entry_id_folder'] == 'no')
			{
				$dir = '';
			}

			$zip->addFile($fields[$field_id]['locprefs']['server_path'] . $dir . $file->filename, $file->filename);
		}

		$zip->close();

		//----------------------------------------
		// Output to browser!
		//----------------------------------------
		header('Pragma: public');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Cache-Control: public', FALSE);
		header('Content-Description: File Transfer');
		header('Content-Type: application/zip');
		header('Accept-Ranges: bytes');
		header('Content-Disposition: attachment; filename="' . $filename . '.zip";');
		header('Content-Transfer-Encoding: binary');
		header('Content-Length: ' . @filesize($zip_path));

		if (! $fh = fopen($zip_path, 'rb'))
		{
			exit('COULD NOT OPEN FILE.');
		}

		while (!feof($fh))
		{
			@set_time_limit(0);
		  	print(fread($fh, 8192));
		  	flush();
		}
		fclose($fh);

		@unlink($zip_path);

	}

	// ********************************************************************************* //

	function channel_files_router()
	{
		@header('Access-Control-Allow-Origin: *');
		@header('Access-Control-Allow-Credentials: true');
        @header('Access-Control-Max-Age: 86400');
        @header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        @header('Access-Control-Allow-Headers: Keep-Alive, Content-Type, User-Agent, Cache-Control, X-Requested-With, X-File-Name, X-File-Size');

		// -----------------------------------------
		// Ajax Request?
		// -----------------------------------------
		if ( $this->EE->input->get_post('ajax_method') != FALSE OR (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') )
		{
			// Load Library
			if (class_exists('Channel_Files_AJAX') != TRUE) include 'ajax.channel_files.php';

			$AJAX = new Channel_Files_AJAX();

			$this->EE->lang->loadfile('channel_files');

			// Shoot the requested method
			$method = $this->EE->input->get_post('ajax_method');
			echo $AJAX->$method();
			exit();
		}

		// If nothing of the above is true...
		exit('This is the ACT URL for Channel Images');
	}

	// ********************************************************************************* //

	public function simple_file_url()
	{
		error_reporting(E_ALL);
		@ini_set('display_errors', 1);

		// Encrypted??
		if ($this->EE->input->get('key') != FALSE)
		{
			$data = @unserialize($this->EE->channel_files_helper->decode_string(base64_decode($this->EE->input->get_post('key'))));
			if (is_array($data) == FALSE) exit('FAILED TO DECRYPT');
			$file_id = $data['file_id'];
			$field_id = $data['fid'];
			$dir = $data['d'];
			$file = $data['f'];
			$temp_dir = isset($data['temp_dir']) ? $data['temp_dir'] : FALSE;
			$force_stream = TRUE;
		}
		else
		{
			$file_id = FALSE;
			$field_id = $this->EE->input->get('fid');
			$dir = $this->EE->input->get('d');
			$file = $this->EE->input->get('f');
			$temp_dir = $this->EE->input->get('temp_dir');
			$force_stream = FALSE;
		}

		$file = $file = $this->EE->security->sanitize_filename($file);

		// Must be a number!
		if ($this->EE->channel_files_helper->is_natural_number($dir) == FALSE || $this->EE->channel_files_helper->is_natural_number($field_id) == FALSE)
        {
			$this->EE->output->set_status_header(404);
			echo '<html><head><title>404 Page Not Found</title></head><body><h1>Status: 404 Page Not Found</h1></body></html>';
			exit();
        }

		// -----------------------------------------
		// Temp DIR?
		// -----------------------------------------
		if ($temp_dir == 'yes')
		{
			$os = $this->EE->config->item('host_os'); // Custom in config file
			if ($os == 'windows' OR ( isset($_SERVER['SERVER_SOFTWARE']) == TRUE && strpos($_SERVER['SERVER_SOFTWARE'], 'IIS') !== FALSE) )
			{
				$dir_path = APPPATH.'\\cache\\channel_files\\field_'.$field_id.'\\'.$dir.'\\';
			}
			else
			{
				$dir_path = APPPATH.'cache/channel_files/field_'.$field_id.'/'.$dir.'/';
			}

			$this->stream_file($dir_path, $file);
		}

		// Get Field Settings!
		$settings = $this->EE->channel_files_helper->grab_field_settings($field_id);

		$location_type = $settings['upload_location'];
		$location_class = 'CF_Location_'.$location_type;
		$location_settings = $settings['locations'][$location_type];

		// Entry_id FOLDER?
		if (isset($settings['entry_id_folder']) && $settings['entry_id_folder'] == 'no')
		{
			$dir = FALSE;
		}

		// Load Main Class
		if (class_exists('Cfile_Location') == FALSE) require PATH_THIRD.'channel_files/locations/cfile_location.php';

		// Try to load Location Class
		if (class_exists($location_class) == FALSE)
		{
			$location_file = PATH_THIRD.'channel_files/locations/'.$location_type.'/'.$location_type.'.php';

			require $location_file;
		}

		// Init!
		$LOC = new $location_class($location_settings);

		// Force to stream?
		if ($force_stream == TRUE && $location_type == 'local')
		{
			$filedb = $this->EE->db->select('*')->from('exp_channel_files')->where('file_id', $file_id)->limit(1)->get();
			$filedb = $filedb->row();

			if ($dir != FALSE) $dir .= '/';
			$path = $LOC->get_location_prefs($location_settings['location']);
			$this->stream_file($path['server_path'] . $dir, $file, $filedb);
		}


		header('Location: '.$LOC->parse_file_url($dir, $file));
		exit();
	}

	// ********************************************************************************* //

	public function locked_file_url()
	{
		try
		{
			// Encryption Disabled?
			if ($this->EE->input->get_post('e') == 'no')
			{
				$data = @unserialize(base64_decode($this->EE->input->get_post('key')));
			}
			else
			{
				$data = @unserialize($this->EE->channel_files_helper->decode_string(base64_decode($this->EE->input->get_post('key'))));
			}

			if (is_array($data) == FALSE) throw new Exception();
		}
		catch (Exception $e)
		{
			exit( $this->EE->output->show_user_error('general', $this->EE->lang->line('cf:bad_data') ) );
		}

		// -----------------------------------------
		// Get File
		// -----------------------------------------
		$file = $this->EE->db->select('*')->from('exp_channel_files')->where('file_id', $data['file_id'])->limit(1)->get();

		if ($file->num_rows() == 0)
		{
			exit( $this->EE->output->show_user_error('general', 'NO SUCH FILE' ) );
		}

		// IP
		$IP = (isset($_SERVER['HTTP_X_FORWARDED_FOR']) == TRUE) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $this->EE->input->ip_address();

		// Simple Locked URL?
		if (isset($data['simple']) == TRUE && $data['simple'] == 'yes') $simple = TRUE;
		else $simple = FALSE;

		if ($simple == FALSE)
		{
			// -----------------------------------------
			// Within Time?
			// -----------------------------------------
			if ($data['time'] < $this->EE->localize->now)
			{
				exit( $this->EE->output->show_user_error('general', $this->EE->lang->line('cf:time_limit_passed') ) );
			}

			// -----------------------------------------
			// Same IP?
			// -----------------------------------------
			if ($data['ip'] != $IP)
			{
				exit( $this->EE->output->show_user_error('general', $this->EE->lang->line('cf:invalid_ip') ) );
			}
		}

		$file = $file->row();

		// Is it a linked file?
		// Then we need to "fake" the channel_id/field_id
		if ($file->link_file_id > 1)
		{
			$file->entry_id = $file->link_entry_id;
			$file->field_id = $file->link_field_id;
		}

		// Get Field Settings!
		$settings = $this->EE->channel_files_helper->grab_field_settings($file->field_id);

		// -----------------------------------------
		// Load Location
		// -----------------------------------------
		$location_type = $settings['upload_location'];
		$location_class = 'CF_Location_'.$location_type;
		$location_settings = $settings['locations'][$location_type];

		// Load Main Class
		if (class_exists('Cfile_Location') == FALSE) require PATH_THIRD.'channel_files/locations/cfile_location.php';

		// Try to load Location Class
		if (class_exists($location_class) == FALSE)
		{
			$location_file = PATH_THIRD.'channel_files/locations/'.$location_type.'/'.$location_type.'.php';

			require $location_file;
		}

		// Init!
		$LOC = new $location_class($location_settings);


		// Robots normally have empty referrer
		$is_robot = FALSE;
		if (trim($this->EE->input->server('HTTP_REFERER')) != TRUE)
		{
			$agent = strtolower($this->EE->input->server('HTTP_USER_AGENT'));
			$robots = array('google', 'yahoo', 'msnbot', 'bing', 'baidu', 'exabot', 'twiceler', 'teoma', 'gigabot');
			foreach ($robots as $robot)
			{
				if (strpos($agent, $robot) !== FALSE) $is_robot = TRUE;
			}
		}

		if ($is_robot == FALSE)
		{
			// -----------------------------------------
			// Log Download!
			// -----------------------------------------
			$data = array(	'site_id'	=>	$this->site_id,
							'file_id'	=>	$file->file_id,
							'entry_id'	=>	$file->entry_id,
							'member_id'	=>	$this->EE->session->userdata['member_id'],
							'ip_address'=>	sprintf("%u", ip2long($IP)),
							'date'		=>	$this->EE->localize->now,
					);

			$this->EE->db->insert('exp_channel_files_download_log', $data);

			// Update file stats
			$this->EE->db->query("UPDATE exp_channel_files SET `downloads` = (`downloads` + 1) WHERE file_id = {$file->file_id} LIMIT 1");
		}

		// -------------------------------------------
		// 'channel_files_download_init' hook.
		//  - Executes before the download is going to begin
		//
			if ($this->EE->extensions->active_hook('channel_files_download_init') === TRUE)
			{
				$edata = $this->EE->extensions->universal_call('channel_files_download_init', $file->file_id, $file->entry_id, $this->EE->session->userdata['member_id'], $data);
				if ($this->EE->extensions->end_script === TRUE) return;
			}
		//
		// -------------------------------------------

		// Entry_id FOLDER?
		$dir = $file->entry_id;
		if (isset($settings['entry_id_folder']) && $settings['entry_id_folder'] == 'no')
		{
			$dir = FALSE;
		}

		// -----------------------------------------
		// For Local Files we Stream
		// -----------------------------------------
		if ($file->upload_service == 'local')
		{
			if ($dir != FALSE) $dir .= '/';
			$path = $LOC->get_location_prefs($settings['locations']['local']['location']);
			$this->stream_file($path['server_path'] . $dir, $file->filename, $file);
		}
		else
		{
			header('Location: '.$LOC->parse_file_url($dir, $file->filename));
			exit();
		}

		exit();
	}

	// ********************************************************************************* //

	public function upload_file()
	{
		@header('Access-Control-Allow-Origin: *');
		@header('Access-Control-Allow-Credentials: true');
        @header('Access-Control-Max-Age: 86400');
        @header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        @header('Access-Control-Allow-Headers: Keep-Alive, Content-Type, User-Agent, Cache-Control, Origin, X-Requested-With, X-File-Name, X-File-Size, X-EEXID');

		$this->EE->lang->loadfile('channel_files');

		// Load Library
		if (class_exists('Channel_Files_AJAX') != TRUE) include 'ajax.channel_files.php';

		$AJAX = new Channel_Files_AJAX();
		$AJAX->upload_file();

	}

	// ********************************************************************************* //

	private function stream_file($dir='', $filename='', $file=FALSE)
	{
		if (file_exists($dir.$filename) == FALSE)
		{
			exit('FILE NOT FOUND!');
		}

		if (empty($file) == TRUE)
		{
			$file = new StdClass();
			$file->filename = $filename;
			$file->extension = substr( strrchr($file->filename, '.'), 1);

			// Mime type
			if (class_exists('CFMimeTypes') == FALSE) include PATH_THIRD.'channel_files/libraries/mimetypes.class.php';
			$MIME = new CFMimeTypes();

			$file->mime = $MIME->get_mimetype($file->extension);
			$file->filesize = @filesize($dir.$filename);
		}

		/** ----------------------------------------
		/**  For Local Files we STREAM
		/** ----------------------------------------*/
		header('Pragma: public');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Cache-Control: public', FALSE);
		header('Content-Description: File Transfer');
		header('Content-Type: ' . $file->mime);
		header('Accept-Ranges: bytes');
		header('Content-Disposition: attachment; filename="' . $filename . '";');
		header('Content-Transfer-Encoding: binary');
		header('Content-Length: ' . $file->filesize);

		@ob_clean();
		@flush();
		@readfile($dir.$filename);
		exit();
	}

	// ********************************************************************************* //

} // END CLASS

/* End of file mod.channel_files.php */
/* Location: ./system/expressionengine/third_party/channel_files/mod.channel_files.php */
