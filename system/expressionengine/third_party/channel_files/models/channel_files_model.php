<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Channel Files Model File
 *
 * @package			DevDemon_ChannelFiles
 * @author			DevDemon <http://www.devdemon.com> - Lead Developer @ Parscale Media
 * @copyright 		Copyright (c) 2007-2010 Parscale Media <http://www.parscale.com>
 * @license 		http://www.devdemon.com/license/
 * @link			http://www.devdemon.com
 */
class Channel_files_model
{
	public $LOCS = array();
	/**
	 * Constructor
	 *
	 * @access public
	 * @return void
	 */
	public function __construct()
	{
		// Creat EE Instance
		$this->EE =& get_instance();
		$this->site_id = $this->EE->config->item('site_id');
	}

	// ********************************************************************************* //

	public function get_files($entry_id=FALSE, $field_id=FALSE, $params=array(), $tagdata='')
	{
		// Limit
		$limit = isset($params['limit']) ? $params['limit'] : 30;
		if (strpos($tagdata, LD.'/'."{$this->prefix}paginate".RD) === FALSE) $this->EE->db->limit($limit);

		// Sort
		$sort = (isset($params['sort']) === TRUE && $params['sort'] == 'desc') ? 'DESC': 'ASC';

		// Order by? (only if primary_only is false, since this would override our orderby)
		if (isset($params['primary_only']) === FALSE)
		{
			if (isset($params['orderby']) === FALSE) $params['orderby'] = 'file_order';
			switch ($params['orderby'])
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

		// Field ID
		if ($field_id !== FALSE)
		{
			if (is_array($field_id) === TRUE)
			{
				$this->EE->db->where_in('field_id', $field_id);
			}
			else
			{
				$this->EE->db->where('field_id', $field_id);
			}
		}

		// Offset
		if (isset($params['offset']) === TRUE)
		{
			$this->EE->db->limit($limit, $params['offset']);
		}

		// Do we need to skip the cover image?
        if (isset($params['skip_primary']) === TRUE)
        {
        	$this->EE->db->where('file_primary', 0);
        }

		// Primary Image
		if (isset($params['primary_only']) == TRUE && (isset($params['force_primary']) === FALSE OR $params['force_primary'] != 'yes'))
		{
			$this->EE->db->limit(1);
			$this->EE->db->order_by('file_primary DESC, file_order ASC');
		}
		elseif ( (isset($params['force_primary']) === TRUE && $params['force_primary'] == 'yes') )
		{
			$this->EE->db->where('file_primary', 1);
		}

		// File ID?
		if (isset($params['file_id']) === TRUE)
		{
			$file_id = $params['file_id'];

			// Multiple File ID?
			if (strpos($file_id, '|') !== FALSE)
			{
				$ids = explode('|', $file_id);
				$this->EE->db->where_in('file_id', $ids);
			}
			else
			{
				$this->EE->db->limit(1);
				$this->EE->db->where('file_id', $file_id);
			}
		}

		// Entry ID
		if ($entry_id != FALSE)
		{
			$this->EE->db->where('entry_id', $entry_id);
		}

		// Channel?
		if (isset($params['channel']) === TRUE)
		{
			$cid = $this->get_channel_id($params['channel']);
			if (is_array($cid) === TRUE) $this->EE->db->where_in('channel_id', $cid);
			else $this->EE->db->where('channel_id', $cid);
		}

		// Channel ID?
		if (isset($params['channel_id']) === TRUE)
		{
			$channel_id = $params['channel_id'];

			// Multiple Channel ID?
			if (strpos($channel_id, '|') !== FALSE)
			{
				$ids = explode('|', $channel_id);
				$this->EE->db->where_in('channel_id', $ids);
			}
			else
			{
				$this->EE->db->where('channel_id', $channel_id);
			}
		}

		// Member ID?
		if (isset($params['member_id']) === TRUE)
		{
			$member_id = $params['member_id'];

			if ($member_id == 'CURRENT_USER')
			{
				$this->EE->db->where('member_id', $this->EE->session->userdata['member_id']);
			}
			elseif ($member_id != FALSE)
			{
				// Multiple Authors?
				if (strpos($member_id, '|') !== FALSE)
				{
					$cols = explode('|', $member_id);
					$this->EE->db->where_in('member_id', $cols);
				}
				else
				{
					$this->EE->db->where('member_id', $member_id);
				}
			}
		}

		// Better Workflow Draft?
		if (isset($this->EE->session->cache['ep_better_workflow']['is_draft']) && $this->EE->session->cache['ep_better_workflow']['is_draft'])
		{
			$this->EE->db->where('is_draft', 1);
		}
		else
		{
			$this->EE->db->where('is_draft', 0);
		}

		//----------------------------------------
		// Shoot the Query
		//----------------------------------------
		$this->EE->db->select('*');
		$this->EE->db->from('exp_channel_files');
		$query = $this->EE->db->get();

		$result = $query->result();
		$query->free_result();

		return $result;
	}

	// ********************************************************************************* //

	public function parse_template($entry_id=FALSE, $field_id=FALSE, $params=array(), $tagdata)
	{
		// Variable prefix
		$this->prefix = (isset($params['prefix']) === FALSE) ? 'file:' : $params['prefix'].':';

		if ($this->EE->TMPL->fetch_param('encrypt_locked_url') == 'no') $this->EE->session->cache['channel_files']['encrypt_locked_url'] = 'no';
		if ($this->EE->TMPL->fetch_param('simple_locked_url') == 'yes') $this->EE->session->cache['channel_files']['simple_locked_url'] = 'yes';

		// Set a default value of false for the is_draft flag
		$is_draft = 0;

		// If we are loading a draft into the publish page update the flag to true
		if (isset($this->session->cache['ep_better_workflow']['is_draft']) && $this->session->cache['ep_better_workflow']['is_draft'])
		{
			$is_draft = 1;
		}

		$temp_params = $params;

		// Lets remove all unwanted params
		unset($temp_params['entry_id'], $temp_params['url_title']);

		// Make our hash
		$hash = crc32(serialize($temp_params));

		$files = $this->get_files($entry_id, $field_id, $params, $tagdata);

		// Any Images?
		if (count($files) === 0)
		{
			$this->EE->TMPL->log_item("CHANNEL FILES: No files found.");
			return $this->EE->channel_files_helper->custom_no_results_conditional($this->prefix.'no_files', $this->EE->TMPL->tagdata);
		}

		$this->total_files = count($files);
		$limit = isset($params['limit']) ? $params['limit'] : 30;
		$paginate = FALSE;


		//----------------------------------------
		// Pagination
		//----------------------------------------
		if (preg_match('/'.LD."{$this->prefix}paginate(.*?)".RD."(.+?)".LD.'\/'."{$this->prefix}paginate".RD."/s", $tagdata, $match))
		{
			// Pagination variables
			$paginate		= TRUE;
			$paginate_data	= $match['2'];
			$current_page	= 0;
			$total_pages	= 1;
			$qstring		= $this->EE->uri->query_string;
			$uristr			= $this->EE->uri->uri_string;
			$pagination_links = '';
			$page_previous = '';
			$page_next = '';

			// We need to strip the page number from the URL for two reasons:
			// 1. So we can create pagination links
			// 2. So it won't confuse the query with an improper proper ID

			if (preg_match("#(^|/)CF(\d+)(/|$)#", $qstring, $match))
			{
				$current_page = $match['2'];
				$uristr  = $this->EE->functions->remove_double_slashes(str_replace($match['0'], '/', $uristr));
				$qstring = trim($this->EE->functions->remove_double_slashes(str_replace($match['0'], '/', $qstring)), '/');
			}

			// Remove the {paginate}
			$tagdata = preg_replace("/".LD."{$this->prefix}paginate.*?".RD.".+?".LD.'\/'."{$this->prefix}paginate".RD."/s", "", $tagdata);

			// What is the current page?
			$current_page = ($current_page == '' OR ($limit > 1 AND $current_page == 1)) ? 0 : $current_page;

			if ($current_page > $this->total_files)
			{
				$current_page = 0;
			}

			$t_current_page = floor(($current_page / $limit) + 1);
			$total_pages	= intval(floor($this->total_files / $limit));

			if ($this->total_files % $limit) $total_pages++;

			if ($this->total_files > $limit)
			{
				$this->EE->load->library('pagination');

				$deft_tmpl = '';

				if ($uristr == '')
				{
					if ($this->EE->config->item('template_group') == '')
					{
						$this->EE->db->select('group_name');
						$query = $this->EE->db->get_where('template_groups', array('is_site_default' => 'y'));

						$deft_tmpl = $query->row('group_name') .'/index';
					}
					else
					{
						$deft_tmpl  = $this->EE->config->item('template_group').'/';
						$deft_tmpl .= ($this->EE->config->item('template') == '') ? 'index' : $this->EE->config->item('template');
					}
				}

				$basepath = $this->EE->functions->remove_double_slashes($this->EE->functions->create_url($uristr, FALSE).'/'.$deft_tmpl);

				if (isset($params['paginate_base']) === TRUE)
				{
					// Load the string helper
					$this->EE->load->helper('string');

					$pbase = trim_slashes($params['paginate_base']);

					$pbase = str_replace("/index", "/", $pbase);

					if ( ! strstr($basepath, $pbase))
					{
						$basepath = $this->EE->functions->remove_double_slashes($basepath.'/'.$pbase);
					}
				}

				// Load Language
				$this->EE->lang->loadfile('channel_files');

				$config['first_url'] 	= rtrim($basepath, '/');
				$config['base_url']		= $basepath;
				$config['prefix']		= 'CF';
				$config['total_rows'] 	= $this->total_files;
				$config['per_page']		= $limit;
				$config['cur_page']		= $current_page;
				$config['suffix']		= '';
				$config['first_link'] 	= $this->EE->lang->line('cf:pag_first_link');
				$config['last_link'] 	= $this->EE->lang->line('cf:pag_last_link');
				$config['full_tag_open']		= '<span class="ci_paginate_links">';
				$config['full_tag_close']		= '</span>';
				$config['first_tag_open']		= '<span class="ci_paginate_first">';
				$config['first_tag_close']		= '</span>&nbsp;';
				$config['last_tag_open']		= '&nbsp;<span class="ci_paginate_last">';
				$config['last_tag_close']		= '</span>';
				$config['cur_tag_open']			= '&nbsp;<strong class="ci_paginate_current">';
				$config['cur_tag_close']		= '</strong>';
				$config['next_tag_open']		= '&nbsp;<span class="ci_paginate_next">';
				$config['next_tag_close']		= '</span>';
				$config['prev_tag_open']		= '&nbsp;<span class="ci_paginate_prev">';
				$config['prev_tag_close']		= '</span>';
				$config['num_tag_open']			= '&nbsp;<span class="ci_paginate_num">';
				$config['num_tag_close']		= '</span>';

				// Allows $config['cur_page'] to override
				$config['uri_segment'] = 0;

				$this->EE->pagination->initialize($config);
				$pagination_links = $this->EE->pagination->create_links();

				if ((($total_pages * $limit) - $limit) > $current_page)
				{
					$page_next = $basepath.$config['prefix'].($current_page + $limit).'/';
				}

				if (($current_page - $limit ) >= 0)
				{
					$page_previous = $basepath.$config['prefix'].($current_page - $limit).'/';
				}
			}
			else
			{
				$current_page = 0;
			}

			$files = array_slice($files, $current_page, $limit);
		}

		//----------------------------------------
		// Switch=""
		//----------------------------------------
		$this->parse_switch = FALSE;
		$this->switch_matches = array();
		if ( preg_match_all( "/".LD."({$this->prefix}switch\s*=.+?)".RD."/is", $tagdata, $this->switch_matches ) > 0 )
		{
			$this->parse_switch = TRUE;

			// Loop over all matches
			foreach($this->switch_matches[0] as $key => $match)
			{
				$this->switch_vars[$key] = $this->EE->functions->assign_parameters($this->switch_matches[1][$key]);
				$this->switch_vars[$key]['original'] = $this->switch_matches[0][$key];
			}
		}

		//----------------------------------------
		// Locked URL?
		//----------------------------------------
		$this->locked_url = FALSE;
		if ( strpos($tagdata, $this->prefix.'locked_url') !== FALSE)
		{
			$this->locked_url = TRUE;

			// IP
			$this->IP = (isset($_SERVER['HTTP_X_FORWARDED_FOR']) == TRUE) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $this->EE->input->ip_address();

			// Grab Router URL
			$this->locked_act_url = $this->EE->channel_files_helper->get_router_url('url', 'locked_file_url');
		}

		// Parse Full URL
		$this->parse_full_url = FALSE;
		if ( strpos($tagdata, LD.$this->prefix.'url'.RD) !== FALSE OR strpos($tagdata, LD.$this->prefix.'secure_url'.RD) !== FALSE )
		{
			$this->parse_full_url = TRUE;
		}

		// Force Download URL
		$this->parse_force_download = FALSE;
		if ( strpos($tagdata, LD.$this->prefix.'download_url'.RD) !== FALSE )
		{
			$this->parse_full_url = TRUE;
			$this->parse_force_download = TRUE;
		}

		//----------------------------------------
		// Performance :)
		//----------------------------------------
		if (isset($this->session->cache['channel_files']['locations']) == FALSE)
		{
			$this->session->cache['channel_files']['locations'] = array();
		}

		$this->LOCS =& $this->session->cache['channel_files']['locations'];

		// Another Check, just to be sure
		if (is_array($this->LOCS) == FALSE) $this->LOCS = array();

		$OUT = '';

		//----------------------------------------
		// Loop over all Images
		//----------------------------------------
		foreach ($files as $count => $file)
		{
			$OUT .= $this->parse_single_file_row($count, $file, $tagdata);
		}

		//----------------------------------------
		// Add pagination to result
		//----------------------------------------
		if ($paginate == TRUE)
		{
			$paginate_data = str_replace(LD.$this->prefix.'current_page'.RD, 	$t_current_page, 	$paginate_data);
			$paginate_data = str_replace(LD.$this->prefix.'total_pages'.RD,		$total_pages,  		$paginate_data);
			$paginate_data = str_replace(LD.$this->prefix.'pagination_links'.RD,	$pagination_links,	$paginate_data);

			if (preg_match("/".LD."if {$this->prefix}previous_page".RD."(.+?)".LD.'\/'."if".RD."/s", $paginate_data, $match))
			{
				if ($page_previous == '')
				{
					 $paginate_data = preg_replace("/".LD."if {$this->prefix}previous_page".RD.".+?".LD.'\/'."if".RD."/s", '', $paginate_data);
				}
				else
				{
					$match['1'] = str_replace(array(LD."{$this->prefix}path".RD, LD."{$this->prefix}auto_path".RD), $page_previous, $match['1']);

					$paginate_data = str_replace($match['0'], $match['1'], $paginate_data);
				}
			}

			if (preg_match("/".LD."if {$this->prefix}next_page".RD."(.+?)".LD.'\/'."if".RD."/s", $paginate_data, $match))
			{
				if ($page_next == '')
				{
					 $paginate_data = preg_replace("/".LD."if {$this->prefix}next_page".RD.".+?".LD.'\/'."if".RD."/s", '', $paginate_data);
				}
				else
				{
					$match['1'] = str_replace(array(LD."{$this->prefix}path".RD, LD."{$this->prefix}auto_path".RD), $page_next, $match['1']);

					$paginate_data = str_replace($match['0'], $match['1'], $paginate_data);
				}
			}

			$position = (isset($params['paginate']) === TRUE) ? $params['paginate'] : '';

			switch ($position)
			{
				case "top"	: $OUT  = $paginate_data.$OUT;
					break;
				case "both"	: $OUT  = $paginate_data.$OUT.$paginate_data;
					break;
				default		: $OUT .= $paginate_data;
					break;
			}
		}

		// Apply Backspace
		$backspace = (isset($params['backspace']) === TRUE) ? $params['backspace'] : 0;
		$OUT = ($backspace > 0) ? substr($OUT, 0, - $backspace): $OUT;

		return $OUT;
	}

	// ********************************************************************************* //

	public function parse_single_file_row($count, $file, $tagdata)
	{
		$out = '';

		// Check for linked file!
		if ($file->link_entry_id > 1)
		{
			$file->entry_id = $file->link_entry_id;
			$file->field_id = $file->link_field_id;
		}

		$vars = array();
		$vars[$this->prefix.'count'] = $count + 1;
		$vars[$this->prefix.'total'] = $this->total_files;
		$vars[$this->prefix.'title'] = $file->title;
		$vars[$this->prefix.'url_title'] = $file->url_title;
		$vars[$this->prefix.'description'] = $file->description;
		$vars[$this->prefix.'category'] = $file->category;
		$vars[$this->prefix.'filename'] = $file->filename;
		$vars[$this->prefix.'basename'] = basename($file->filename, '.'.$file->extension);
		$vars[$this->prefix.'id'] = $file->file_id;
		$vars[$this->prefix.'entry_id'] = $file->entry_id;
		$vars[$this->prefix.'channel_id'] = $file->channel_id;
		$vars[$this->prefix.'member_id'] = $file->member_id;
		$vars[$this->prefix.'mimetype'] = $file->mime;
		$vars[$this->prefix.'extension'] = $file->extension;
		$vars[$this->prefix.'md5'] = $file->md5;
		$vars[$this->prefix.'date'] = $file->date; // Deprecated since version 5
		$vars[$this->prefix.'upload_date'] = $file->date;
		$vars[$this->prefix.'downloads'] = $file->downloads;
		$vars[$this->prefix.'primary'] = $file->file_primary;
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
		if ($this->parse_full_url == TRUE)
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
			$vars[$this->prefix.'download_url'] = $fileurl;
			$vars[$this->prefix.'secure_url'] = str_replace('http://', 'https://', $fileurl);
		}

		if ($this->parse_force_download)
		{
			$vars[$this->prefix.'download_url'] = $this->LOCS[$file->field_id]->parse_file_url($dir, $file->filename, true);
		}

		// -----------------------------------------
		// Parse Locked URL's
		// -----------------------------------------
		if ($this->locked_url == TRUE)
		{
			$IP = (isset($_SERVER['HTTP_X_FORWARDED_FOR']) == TRUE) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $this->EE->input->ip_address();
			$locked = array('time' => $this->EE->localize->now + 3600, 'file_id' => $file->file_id, 'ip' => $IP);
			if (isset($this->EE->session->cache['channel_files']['simple_locked_url']) === TRUE && $this->EE->session->cache['channel_files']['simple_locked_url'] == 'yes')
			{
				$locked['simple'] = 'yes';
				unset($locked['time'], $locked['ip']);
			}

			if (isset($this->EE->session->cache['channel_files']['encrypt_locked_url']) == TRUE && $this->EE->session->cache['channel_files']['encrypt_locked_url'] == 'no')
			{
				$vars[$this->prefix.'locked_url'] = $this->locked_act_url . '&amp;e=no&amp;key=' . base64_encode(serialize($locked));
			}
			else
			{
				$vars[$this->prefix.'locked_url'] = $this->locked_act_url . '&amp;key=' . base64_encode($this->EE->channel_files_helper->encrypt_string(serialize($locked)));
			}
		}


		$temp = $this->EE->TMPL->parse_variables_row($tagdata, $vars);

		// -----------------------------------------
		// Parse Switch {switch="one|twoo"}
		// -----------------------------------------
		if ($this->parse_switch)
		{
			// Loop over all switch variables
			foreach($this->switch_vars as $switch)
			{
				$sw = '';

				// Does it exist? Just to be sure
				if ( isset( $switch[$this->prefix.'switch'] ) !== FALSE )
				{
					$sopt = explode("|", $switch[$this->prefix.'switch']);
					$sw = $sopt[(($count) + count($sopt)) % count($sopt)];
				}

				$temp = str_replace($switch['original'], $sw, $temp);
			}
		}

		return $temp;
	}

	// ********************************************************************************* //

	/**
	 * Get Upload Prefs
	 *
	 * @param int $location_id
	 * @access public
	 * @return array - Location settings
	 */
	public function get_location_prefs($location_id)
	{
		$location = array();

		if (isset($this->EE->session->cache['Channel_Files']['Locations'][$location_id]) == FALSE)
		{
			$query = $this->EE->db->select('server_path, url')->from('exp_upload_prefs')->where('id', $location_id)->get();

			if ($query->num_rows() == 0)
			{
				$query->free_result();
				return FALSE;
			}

			$location = array('path' => $query->row('server_path'), 'url' => $query->row('url'));

			$this->EE->session->cache['Channel_Files']['Locations'][$location_id] = $location;

			$query->free_result();
		}
		else
		{
			$location = $this->EE->session->cache['Channel_Files']['Locations'][$location_id];
		}


		return $location;
	}

	// ********************************************************************************* //

	/**
	 * Get Field ID
	 * Since we moved to Field Based Settings, our legacy versions where not storing field_id's
	 * so we need to somehow get it from the channel_id
	 *
	 * @param object $image
	 * @access public
	 * @return int - The FieldID
	 */
	public function get_field_id($file)
	{
		if ($file->link_file_id > 1)
		{
			$file->field_id = $file->link_file_id;
		}

		// Easy way..
		if ($file->field_id > 1)
		{
			return $file->field_id;
		}

		// Hard way
		if (isset($this->EE->session->cache['Channel_Files']['Channel2Field'][$file->channel_id]) == FALSE)
		{
			// Then we need to use the Channel ID :(
			$query = $this->EE->db->query("SELECT cf.field_id FROM exp_channel_fields AS cf
											LEFT JOIN exp_channels AS c ON c.field_group = cf.group_id
											WHERE c.channel_id = {$file->channel_id} AND cf.field_type = 'channel_files'");
			if ($query->num_rows() == 0)
			{
				$query->free_result();
				return 0;
			}

			$this->EE->session->cache['Channel_Files']['Channel2Field'][$file->channel_id] = $query->row('field_id');
			$field_id = $query->row('field_id');

			$query->free_result();
		}
		else
		{
			$field_id = $this->EE->session->cache['Channel_Files']['Channel2Field'][$file->channel_id];
		}

		return $field_id;
	}

	// ********************************************************************************* //

	public function get_channel_id($channels)
	{
		if ($channels == FALSE) return FALSE;

		// Multiple Channels?
		if (strpos($channels, '|') !== FALSE)
		{
			$channels = explode('|', $channels);
			$lookup = array();
			$return = array();

			foreach ($channels as $key => $value)
			{
				// Did we Cache this already?
				if (isset($this->EE->session->cache['devdemon']['channel_to_id'][$value]) === TRUE)
				{
					$return[] = $this->EE->session->cache['devdemon']['channel_to_id'][$value];
					continue;
				}

				$lookup[] = "'".$value."'";
			}

			if (empty($lookup) === FALSE)
			{
				$query = $this->EE->db->query("SELECT channel_id, channel_name FROM exp_channels WHERE channel_name IN (".implode(',', $lookup).") ");
				if ($query->num_rows() == 0) return FALSE;

				foreach ($query->result() as $row)
				{
					$this->EE->session->cache['devdemon']['channel_to_id'][$row->channel_name] = $row->channel_id;
					$return[] = $row->channel_id;
				}
			}

			if (empty($return) === TRUE) return FALSE;
			return $return;
		}
		else
		{
			// Did we Cache this already?
			if (isset($this->EE->session->cache['devdemon']['channel_to_id'][$channels]) === FALSE)
			{
				$query = $this->EE->db->query("SELECT channel_id FROM exp_channels WHERE channel_name = '{$channels}' ");
				if ($query->num_rows() == 0) return FALSE;

				$this->EE->session->cache['devdemon']['channel_to_id'][$channels] = $query->row('channel_id');
			}

			return $this->EE->session->cache['devdemon']['channel_to_id'][$channels];
		}
	}

	// ********************************************************************************* //

	public function get_fields_from_params($params)
	{
		$fields = array();

		if (isset($params['field_id']) === TRUE)
		{
			// Multiple fields?
			if (strpos($params['field_id'], '|') !== FALSE)
			{
				return explode('|', $params['field_id']);
			}
			else
			{
				return $params['field_id'];
			}
		}

		if (isset($params['field']) === TRUE)
		{
			// Multiple fields?
			if (strpos($params['field'], '|') !== FALSE)
			{
				$pfields = explode('|', $params['field']);

				foreach($pfields as $field)
				{
					if (isset($this->EE->session->cache['channel']['custom_channel_fields'][$this->site_id][ $field ]) === FALSE)
					{
						// Grab the field id
						$query = $this->EE->db->query("SELECT field_id FROM exp_channel_fields WHERE field_name = '{$field}' AND site_id = {$this->site_id} ");
						if ($query->num_rows() == 0)
						{
							if (isset($this->EE->TMPL) === TRUE) $this->EE->TMPL->log_item('CHANNEL_FILES: Could not find field : ' . $field);
							return FALSE;
						}
						else
						{
							$this->EE->session->cache['channel']['custom_channel_fields'][$this->site_id][ $field ] = $query->row('field_id');
						}
					}

					$fields[] = $this->EE->session->cache['channel']['custom_channel_fields'][$this->site_id][ $field ];
				}
			}
			else
			{
				if (isset($this->EE->session->cache['channel']['custom_channel_fields'][$this->site_id][ $params['field'] ]) === FALSE)
				{
					// Grab the field id
					$query = $this->EE->db->query("SELECT field_id FROM exp_channel_fields WHERE field_name = '{$params['field']}' AND site_id = {$this->site_id} ");
					if ($query->num_rows() == 0)
					{
						if (isset($this->EE->TMPL) === TRUE) $this->EE->TMPL->log_item('CHANNEL_FILES: Could not find field : ' . $params['field']);
						return FALSE;
					}
					else
					{
						$this->EE->session->cache['channel']['custom_channel_fields'][$this->site_id][ $params['field'] ] = $query->row('field_id');
					}
				}

				return $this->EE->session->cache['channel']['custom_channel_fields'][$this->site_id][ $params['field'] ];
			}
		}

		if (empty($fields) === TRUE) return FALSE;

		return $fields;
	}

	// ********************************************************************************* //

} // END CLASS

/* End of file Channel_images_model.php  */
/* Location: ./system/expressionengine/third_party/channel_images/models/Channel_images_model.php */
