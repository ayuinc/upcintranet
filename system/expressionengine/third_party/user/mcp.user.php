<?php if ( ! defined('BASEPATH') ) exit('No direct script access allowed');

/**
 * User - Control Panel
 *
 * @package		Solspace:User
 * @author		Solspace, Inc.
 * @copyright	Copyright (c) 2008-2014, Solspace, Inc.
 * @link		http://solspace.com/docs/user
 * @license		http://www.solspace.com/license_agreement
 * @version		3.5.0
 * @filesource	user/mcp.user.php
 */

require_once 'addon_builder/module_builder.php';

class User_mcp extends Module_builder_user
{
	// --------------------------------------------------------------------

	/**
	 * Constructor
	 *
	 * @access	public
	 * @return	null
	 */

	public function __construct( $switch = TRUE )
	{
		parent::__construct();

		if ((bool) $switch === FALSE) return; // Install or Uninstall Request

		// --------------------------------------------
		//  Module Menu Items
		// --------------------------------------------

		$menu	= array(

			'module_preferences'		=> array(
				'link'  => $this->base.'&method=preferences',
				'title' => lang('preferences')
			),

			'module_reassign_ownership'	=> array(
				'link'  => $this->base.'&method=reassign_ownership',
				'title' => lang('reassign_ownership')
			),
			'module_demo_templates'		=> array(
				'link'			=> $this->base.'&method=code_pack',
				'title'			=> lang('demo_templates'),
			),
			'module_documentation'		=> array(
				'link'  => USER_DOCS_URL,
				'title' => lang('online_documentation'),
				'new_window' => TRUE
			),
		);

		$this->cached_vars['lang_module_version'] 	= lang('user_module_version');
		$this->cached_vars['module_version'] 		= USER_VERSION;
		$this->cached_vars['module_menu_highlight'] = 'module_preferences';
		$this->cached_vars['module_menu'] 			= $menu;

		// --------------------------------------------
		//  Sites
		// --------------------------------------------

		$this->cached_vars['sites']	= array();

		ee()->load->model('user_model');

		foreach(ee()->user_model->get_sites() as $site_id => $site_label)
		{
			$this->cached_vars['sites'][$site_id] = $site_label;
		}
	}
	// END __construct()


	// --------------------------------------------------------------------

	/**
	 * Homepage
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */

	public function index( $message = '' )
	{
		return $this->preferences($message);
	}
	// End home


	// --------------------------------------------------------------------

	/**
	 * Code pack installer page
	 *
	 * @access public
	 * @param	string	$message	lang line for update message
	 * @return	string				html output
	 */

	public function code_pack($message = '')
	{
		//--------------------------------------------
		//	message
		//--------------------------------------------

		if ($message == '' AND ee()->input->get_post('msg') !== FALSE)
		{
			$message = lang(ee()->input->get_post('msg'));
		}

		$this->cached_vars['message'] = $message;

		// -------------------------------------
		//	load vars from code pack lib
		// -------------------------------------

		$lib_name = str_replace('_', '', $this->lower_name) . 'codepack';
		$load_name = str_replace(' ', '', ucwords(str_replace('_', ' ', $this->lower_name))) . 'CodePack';

		ee()->load->library($load_name, $lib_name);

		ee()->$lib_name->autoSetLang = true;

		$cpt = ee()->$lib_name->getTemplateDirectoryArray(
			$this->addon_path . 'code_pack/'
		);

		$screenshot = ee()->$lib_name->getCodePackImage(
			$this->sc->addon_theme_path . 'code_pack/',
			$this->sc->addon_theme_url . 'code_pack/'
		);

		$this->cached_vars['screenshot'] = $screenshot;

		$this->cached_vars['prefix'] = $this->lower_name . '_';

		$this->cached_vars['code_pack_templates'] = $cpt;

		$this->cached_vars['form_url'] = $this->base . '&method=code_pack_install';

		//--------------------------------------
		//  menus and page content
		//--------------------------------------

		$this->cached_vars['module_menu_highlight'] = 'module_demo_templates';

		$this->add_crumb(lang('demo_templates'));

		$this->cached_vars['current_page'] = $this->view('code_pack.html', NULL, TRUE);

		//---------------------------------------------
		//  Load Homepage
		//---------------------------------------------

		return $this->ee_cp_view('index.html');
	}
	//END code_pack


	// --------------------------------------------------------------------

	/**
	 * Code Pack Install
	 *
	 * @access public
	 * @param	string	$message	lang line for update message
	 * @return	string				html output
	 */

	public function code_pack_install()
	{
		$prefix = trim((string) ee()->input->get_post('prefix'));

		if ($prefix === '')
		{
			ee()->functions->redirect($this->base . '&method=code_pack');
		}

		// -------------------------------------
		//	load lib
		// -------------------------------------

		$lib_name = str_replace('_', '', $this->lower_name) . 'codepack';
		$load_name = str_replace(' ', '', ucwords(str_replace('_', ' ', $this->lower_name))) . 'CodePack';

		ee()->load->library($load_name, $lib_name);
		ee()->$lib_name->autoSetLang = true;

		// -------------------------------------
		//	¡Las Variables en vivo! ¡Que divertido!
		// -------------------------------------

		$variables = array();

		$variables['code_pack_name']	= $this->lower_name . '_code_pack';
		$variables['code_pack_path']	= $this->addon_path . 'code_pack/';
		$variables['prefix']			= $prefix;

		// -------------------------------------
		//	install
		// -------------------------------------

		$details = ee()->$lib_name->getCodePackDetails($this->addon_path . 'code_pack/');

		$this->cached_vars['code_pack_name'] = $details['code_pack_name'];
		$this->cached_vars['code_pack_label'] = $details['code_pack_label'];

		$return = ee()->$lib_name->installCodePack($variables);

		$this->cached_vars = array_merge($this->cached_vars, $return);

		//--------------------------------------
		//  menus and page content
		//--------------------------------------

		$this->cached_vars['module_menu_highlight'] = 'module_demo_templates';

		$this->add_crumb(lang('demo_templates'), $this->base . '&method=code_pack');
		$this->add_crumb(lang('install_demo_templates'));

		$this->cached_vars['current_page'] = $this->view('code_pack_install.html', NULL, TRUE);

		//---------------------------------------------
		//  Load Homepage
		//---------------------------------------------

		return $this->ee_cp_view('index.html');
	}
	//END code_pack_install


	// --------------------------------------------------------------------

	/**
	 * Find Member Form
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */

	public function reassign_ownership( $message = '' )
	{
		// --------------------------------------------
		//  Page Pre-Launch Variables
		// --------------------------------------------

		$this->cached_vars['message'] = $message;

		$this->cached_vars['module_menu_highlight'] = 'module_reassign_ownership';
		$this->add_crumb(lang('reassign_ownership'));

		// --------------------------------------------
		//	Channels to Search?
		//	- Since fetch_assigned_channels() does not
		//	take MSM enabled into account,
		//	we have to do it ourselves.
		//	--------------------------------------------

		if (ee()->config->item('multiple_sites_enabled') != 'y')
		{
			$allowed_blogs = ee()->functions->fetch_assigned_channels(FALSE);
		}
		else
		{
			$allowed_blogs = ee()->functions->fetch_assigned_channels(TRUE);
		}

		if (count($allowed_blogs) == 0)
		{
			return $this->show_error(lang('missing_member_id'));
		}

		// --------------------------------------------
		//  Channels
		// --------------------------------------------

		ee()->load->model('user_model');

		foreach(ee()->user_model->get_channel_data_by_channel_array($allowed_blogs) as $data)
		{
			if (ee()->config->item('multiple_sites_enabled') != 'y')
			{
				$this->cached_vars['channels'][ $data['channel_id'] ] = $data['channel_title'];
			}
			else
			{
				$this->cached_vars['channels'][ $data['channel_id'] ] = $this->cached_vars['sites'][$data['site_id']]." :: ". $data['channel_title'];
			}
		}

		//	----------------------------------------
		//	 Build page
		//	----------------------------------------

		$this->cached_vars['ajax_find_member']	= $this->base.'&method=ajax_member_search';
		$this->cached_vars['ajax_find_entries']	= $this->base.'&method=ajax_entry_search';

		return $this->ee_cp_view('reassign_ownership_form.html');
	}
	//	End find member form


	// --------------------------------------------------------------------

	/**
	 * Reassign ownership confirm
	 *
	 * @access	public
	 * @return	string
	 */

	public function reassign_ownership_confirm()
	{
		if ( ! $member_id = ee()->input->get_post('member_id'))
		{
			return $this->show_error(lang('missing_member_id'));
		}

		if ( ! ee()->input->get_post('entry_ids') OR
			! is_array(ee()->input->get_post('entry_ids')))
		{
			return $this->show_error(lang('no_entries_selected'));
		}

		// --------------------------------------------
		//  Hidden Form Fields
		// --------------------------------------------*/

		$this->cached_vars['hidden']['member_id'] = $member_id;

		foreach ( $_POST['entry_ids'] as $key => $val )
		{
			$this->cached_vars['hidden']['entry_ids'][] = $val;
		}

		$query = ee()->db
						->select('title')
						->where_in('entry_id', ee()->input->get_post('entry_ids'))
						->get('channel_titles');

		$replace[] = $query->num_rows();

		$query	= ee()->db
						->select('screen_name')
						->where('member_id', $member_id)
						->get('members');

		$replace[]	= $query->row('screen_name');

		$search	= array( '%i%', '%name%' );

		if ($query->num_rows() == 1)
		{
			$this->cached_vars['reassign_question'] = str_replace(
				$search,
				$replace,
				lang('reassign_ownership_question_entry')
			);
		}
		else
		{
			$this->cached_vars['reassign_question'] = str_replace(
				$search,
				$replace,
				lang('reassign_ownership_question_entries')
			);
		}

		//	----------------------------------------
		//	 Build page
		//	----------------------------------------

		$this->add_crumb(lang('reassign_ownership_confirm'));

		return $this->ee_cp_view('reassign_ownership_confirm_form.html');

	}
	//	End reassign ownership confirm


	// --------------------------------------------------------------------

	/**
	 * Reassign Ownership
	 *
	 * @access	public
	 * @return	string
	 */

	public function process_reassign_ownership()
	{
		$sql	= array();

		if ( ! $member_id = ee()->input->get_post('member_id'))
		{
			return $this->show_error(lang('missing_member_id'));
		}

		if ( ! ee()->input->post('entry_ids') OR
			! is_array(ee()->input->post('entry_ids')))
		{
			return $this->show_error(lang('no_entries_selected'));
		}

		$query = ee()->db
					->select('entry_id, author_id')
					->where_in('entry_id', ee()->input->post('entry_ids'))
					->get('channel_titles');

		//	----------------------------------------
		//	Loop
		//	----------------------------------------

		$sql	= array();

		$tag	= ee()->db->table_exists('exp_tag_entries');

		foreach ( $query->result_array() as $row )
		{
			//	----------------------------------------
			//	Count old authors
			//	----------------------------------------

			$authors[ $row['author_id'] ][]	= $row['entry_id'];

			//	----------------------------------------
			//	Update entry versioning
			//	----------------------------------------

			$sql[]	= ee()->db->update_string(
				'exp_entry_versioning',
				array( 'author_id' => $member_id ),
				array( 'entry_id' => $row['entry_id'])
			);

			//	----------------------------------------
			//	Update tag ownership
			//	----------------------------------------

			if ( $tag )
			{
				$sql[]	= ee()->db->update_string(
					'exp_tag_entries',
					array('author_id' => $member_id),
					array(
						'entry_id' => $row['entry_id'],
						'remote' => 'n'
					)
				);
			}
		}

		//	----------------------------------------
		//	Update Channel titles
		//	----------------------------------------

			$sql[]	= ee()->db->update_string(
				'exp_channel_titles',
				array( 'author_id' => $member_id ),
				"entry_id IN ('" .
					implode(
						"','",
						ee()->db->escape_str(
							ee()->input->post('entry_ids')
						)
					).
				"')"
			);

		//	----------------------------------------
		//	Update author stats
		//	----------------------------------------

		foreach( $authors as $author => $ents )
		{
			$squery = ee()->db
							->select('total_entries')
							->where('member_id', $author)
							->get('members');

			if ($squery->num_rows() > 0)
			{
				$total_entries = $squery->row('total_entries') - count( $ents );

				if ($total_entries < 0) $total_entries = 0;

				$sql[]	= ee()->db->update_string(
					'exp_members',
					array('total_entries' => $total_entries),
					array('member_id' => $author)
				);
			}
		}

		//	----------------------------------------
		//	Update member stats
		//	----------------------------------------

		$sql[]	= "UPDATE exp_members
				   SET total_entries = total_entries + ".$query->num_rows().",
				   last_entry_date = '".ee()->db->escape_str(ee()->localize->now)."'
				   WHERE member_id = '".ee()->db->escape_str($member_id)."'";

		// --------------------------------------------
		//  Perform Queries
		// --------------------------------------------

		foreach ( $sql as $q )
		{
			ee()->db->query($q);
		}

		//	----------------------------------------
		//	 Prepare message
		//	----------------------------------------

		$message = (
			$query->num_rows() == 1) ?
				str_replace( '%i%', $query->num_rows(), lang('entry_reassigned') ) :
				str_replace( '%i%', $query->num_rows(), lang('entries_reassigned')
		);

		return $this->reassign_ownership($message);
	}
	// END reassign_ownership()


	// --------------------------------------------------------------------

	/**
	 * Preferences Form
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */

	public function preferences( $message = '' )
	{
		// --------------------------------------------
		//  crumbs, messages, and highlights
		// --------------------------------------------

		$this->cached_vars['message'] 				= $message;
		$this->cached_vars['module_menu_highlight'] = 'module_preferences';
		$this->add_crumb(lang('preferences'));

		//	----------------------------------------
		//	Get prefs
		//	----------------------------------------

		$default_prefs = array(
			'email_is_username' 						=> 'n',
			'screen_name_override'						=> '',
			'category_groups'							=> '',
			'welcome_email_subject'						=> lang('welcome_email_content'),
			'welcome_email_content'						=> '',
			'user_forgot_username_message'				=> '',
			'member_update_admin_notification_template'	=> '',
			'member_update_admin_notification_emails'	=> '',
			'key_expiration'							=> '7'
		);

		$prefs = array();

		$query = ee()->db->get('user_preferences');

		foreach($query->result_array() as $row)
		{
			$prefs[$row['preference_name']] = stripslashes($row['preference_value']);
		}

		$prefs = array_merge($default_prefs, $prefs);

		//--------------------------------------------
		//	output vars
		//--------------------------------------------

		//set pref values and lang available
		foreach ($prefs as $key => $value)
		{
			$this->cached_vars['pref_' . $key] = $value;
			$this->cached_vars['lang_' . $key] = lang($key);

			//do we have subtext?
			if (lang($key . '_subtext') !== $key . '_subtext' )
			{
				$this->cached_vars['lang_' . $key. '_subtext'] = lang($key. '_subtext');
			}
		}

		//--------------------------------------------
		//	other language items like titles
		//--------------------------------------------

		$extra_lang = array(
			'general_preferences',
			'multiple_authors',
			'email_notifications',
			'user_preference',
			'user_setting'
		);

		foreach ($extra_lang as $lang_item)
		{
			$this->cached_vars['lang_' . $lang_item] = lang($lang_item);
		}

		//--------------------------------------------
		//	email is username checkbox bool
		//--------------------------------------------

		$this->cached_vars['emiun'] 	= $this->check_yes($prefs['email_is_username']);


		// --------------------------------------------
		//  Sites
		// --------------------------------------------
		ee()->load->model('user_model');

		$this->cached_vars['sites'] = ee()->user_model->get_sites();

		//--------------------------------------------
		//	Category groups
		//--------------------------------------------

		$cg		= explode( "|", $prefs['category_groups']);

		$extra = '';

		if (ee()->config->item('multiple_sites_enabled') != 'y')
		{
			$extra = " AND exp_category_groups.site_id = '" .
						ee()->db->escape_str(
							ee()->config->item('site_id')
						)."'";
		}

		$cgq	= ee()->db->query(
			"SELECT 	group_id, group_name, exp_sites.site_id, site_label
			 FROM 		exp_category_groups, exp_sites
			 WHERE		exp_category_groups.site_id = exp_sites.site_id
			 {$extra}
			 ORDER BY 	site_label, group_name ASC"
		);

		$category_groups = array();

		foreach($cgq->result_array() as $row)
		{
			$row['selected'] 	= in_array($row['group_id'], $cg);

			$category_groups[] 	= $row;
		}

		$this->cached_vars['category_groups'] = $category_groups;

		//----------------------------------------------------------------------
		// start user author stuff
		//----------------------------------------------------------------------

		$current = isset($prefs['channel_ids']) ? unserialize($prefs['channel_ids']) : array();

		// --------------------------------------------
		//  List of Channels
		// --------------------------------------------

		$query	= ee()->db->query(
			"SELECT 	{$this->sc->db->channel_id}, {$this->sc->db->channel_title}, site_label
			 FROM 		{$this->sc->db->channels}, exp_sites
			 WHERE 		exp_sites.site_id = {$this->sc->db->channels}.site_id
			 ORDER BY 	site_label, {$this->sc->db->channel_title}"
		);

		$this->settings		= $current;
		$channels 			= array();

		foreach ( $query->result_array() as $row )
		{
			$channels[$row[$this->sc->db->channel_id]] = $row;
			$channels[$row[$this->sc->db->channel_id]]['channel_title']	= $row[$this->sc->db->channel_title];
			$channels[$row[$this->sc->db->channel_id]]['tab_label']		= '';

			if (isset( $this->settings[ $row[$this->sc->db->channel_id] ]))
			{
				$channels[$row[$this->sc->db->channel_id]]['tab_label'] =
					$this->settings[ $row[$this->sc->db->channel_id] ];
			}
		}

		$this->cached_vars['channels'] = $channels;

		//	----------------------------------------
		//	 Build page
		//	----------------------------------------

		return $this->ee_cp_view('preferences_form.html');
	}
	// END preferences


	// --------------------------------------------------------------------

	/**
	 * Update Preferences
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */

	public function update_preferences( $message = '' )
	{
		//	----------------------------------------
		//	Empty?
		//	----------------------------------------

		if ( ! ee()->input->post('email_is_username') )
		{
			return $this->index();
		}

		$data = array();

		//	----------------------------------------
		//	Username changes allowed?
		//	----------------------------------------

		if ( ee()->config->item('allow_username_change') != 'y' AND
			 ee()->input->post('email_is_username') == 'y' )
		{
			return $this->show_error(lang('username_change_not_allowed'));
		}

		if ( in_array(ee()->input->post('email_is_username'), array('y', 'n')))
		{
			$data[] = 'email_is_username';
		}

		//	----------------------------------------
		//	Add / update category group
		//	----------------------------------------

		if ( ! empty($_POST['category_groups']) &&
			is_array($_POST['category_groups']))
		{
			$_POST['category_groups'] = implode( "|", $_POST['category_groups'] );
		}
		else
		{
			$_POST['category_groups'] = '';
		}

		// --------------------------------------------
		//  Preferences Table
		// --------------------------------------------

		$prefs = array(
			'email_is_username',
			'screen_name_override',
			'category_groups',
			'welcome_email_subject',
			'welcome_email_content',
			'user_forgot_username_message',
			'member_update_admin_notification_template',
			'member_update_admin_notification_emails',
			'key_expiration',
			'channel_ids'
		);

		$prefs = array_merge($data, $prefs);

		foreach($prefs as $pref)
		{
			if ( isset( $_POST[$pref] ) )
			{
				ee()->db->query(
					"DELETE FROM exp_user_preferences
					 WHERE preference_name = '" .
					 	ee()->db->escape_str($pref)."'"
				);

				ee()->db->insert(
					'exp_user_preferences',
					array(
						'preference_value' => ee()->input->post($pref, TRUE),
						'preference_name'  => $pref
					)
				);
			}
		}

		$channel_ids = array();

		if (  isset( $_POST['channel_id'] ) AND
				is_array( $_POST['channel_id'] ) )
		{

			foreach ( $_POST['channel_id'] as $key => $val )
			{
				$new_val = trim(ee()->security->xss_clean( $val ));

				if ( $new_val != '' )
				{
					$channel_ids[$key] = $new_val;
				}
			}

			ee()->load->model('user_model');

			$channel_id_pref 	= ee()->user_model->get_channel_ids(FALSE);

			$change = FALSE;

			//first instance of channel prefs?
			//one is empty?
			if ($channel_id_pref == FALSE OR
				count($channel_ids) != count($channel_id_pref))
			{
				$change = TRUE;
			}
			else
			{
				foreach ($channel_ids as $k => $v)
				{
					if ( $v != $channel_id_pref[$k])
					{
						$change = TRUE;
						break;
					}
				}
			}

			if ($change)
			{
				//need to load this here because the below might
				//not be true if there are no tabs yet

				ee()->load->library('layout');

				if ( ! class_exists('User_upd'))
				{
					require_once $this->addon_path.'upd.user.php';
				}

				$U = new User_upd();

				//first remove all from layouts
				//we do the first check cacheless,
				//and let the second one hit the previous cache
				if ($channel_id_pref !== FALSE)
				{
					ee()->layout->delete_layout_tabs($U->tabs());
					ee()->layout->delete_layout_fields($U->tabs());
				}

				//remove if present
				ee()->db->query(
					"DELETE FROM exp_user_preferences
					 WHERE 		 preference_name = 'channel_ids'"
				);

				//save
				ee()->db->insert(
					'exp_user_preferences',
					array(
						'preference_value' => serialize($channel_ids),
						'preference_name'  => 'channel_ids'
					)
				);

				ee()->load->model('user_model');

				//needs to be false again in case some get deleted
				$channel_id_data = ee()->user_model->get_channel_ids(FALSE);

				if ( $channel_id_data !== FALSE AND
					is_array($channel_id_data) 	AND
					! empty($channel_id_data))
				{
					ee()->layout->add_layout_tabs(
						$U->tabs(),
						'',
						array_keys($channel_id_data)
					);
				}
			}
		}

		//	----------------------------------------
		//	 Success
		//	----------------------------------------

		return ee()->functions->redirect(
			$this->base . AMP . 'msg=user_preferences_updated'
		);
	}
	// END update_preferences


	// --------------------------------------------------------------------

	/**
	 * AJAX Author Search
	 *
	 * @access	public
	 * @return	string
	 */

	public function ajax_member_search()
	{
		$str = $this->_clean_str( ee()->input->get_post('member_keywords') );

		$extra = '';

		if (trim($str) == '')
		{
			$this->cached_vars['members'] = array();
			exit($this->view('reassign_ownership_members.html', array(), TRUE));
		}

		if ($str != '*')
		{
			$extra = "	AND LOWER( exp_members.username ) LIKE '%" .
							ee()->db->escape_str(strtolower($str)) ."%'
						OR LOWER( exp_members.screen_name ) LIKE '%" .
							ee()->db->escape_str(strtolower($str)) . "%'
						OR LOWER( exp_members.email ) LIKE '%" .
							ee()->db->escape_str(strtolower($str))."%' ";
		}

		$sql = "SELECT		exp_members.member_id, exp_members.screen_name
				FROM		exp_members
				LEFT JOIN	exp_member_groups
				ON			exp_member_groups.group_id = exp_members.group_id
				WHERE		exp_member_groups.site_id = '" .
								ee()->db->escape_str(ee()->config->item('site_id'))."'
				AND (
					 exp_members.group_id = 1 OR
					 exp_members.in_authorlist = 'y' OR
					 exp_member_groups.include_in_authorlist = 'y'
					 )
				{$extra}
				ORDER BY screen_name ASC, username ASC";

		$query	= ee()->db->query($sql);

		$this->cached_vars['members'] = array();

		foreach($query->result_array() as $row)
		{
			$this->cached_vars['members'][$row['member_id']] = $row['screen_name'];
		}

		exit($this->view('reassign_ownership_members.html', array(), TRUE));
	}
	// END ajax_member_search()


	// --------------------------------------------------------------------

	/**
	 * AJAX Entry Search
	 *
	 * @access	public
	 * @return	string
	 */

	public function ajax_entry_search()
	{
		// member_id, entry_title_keywords, channels
		$str = $this->_clean_str( ee()->input->get_post('entry_title_keywords') );

		if (trim($str) == '')
		{
			$this->cached_vars['entries'] = array();
			exit($this->view('reassign_ownership_entries.html', array(), TRUE));
		}

		$extra = ($str == '*') ? '' : " AND t.title LIKE '%".ee()->db->escape_str($str)."%'";


		$sql = "SELECT		t.entry_id, t.title
				FROM		exp_channel_titles t
				LEFT JOIN	exp_members m
				ON			t.author_id = m.member_id
				WHERE		t.author_id != '" .
					ee()->db->escape_str(ee()->input->get_post('member_id'))."'
				AND			t.channel_id = '" .
					ee()->db->escape_str(ee()->input->get_post('channel_id')) . "'
				{$extra}";

		$query = ee()->db->query($sql);

		$this->cached_vars['entries'] = array();

		foreach($query->result_array() as $row)
		{
			$this->cached_vars['entries'][$row['entry_id']] = $row['title'];
		}

		exit($this->view('reassign_ownership_entries.html', array(), TRUE));
	}
	// END ajax_entry_search


	// --------------------------------------------------------------------

	/**
	 * AJAX Author Search
	 *
	 * @access	public
	 * @return	string
	 */

	public function user_authors_search()
	{
		ee()->lang->loadfile( 'user' );

		//	----------------------------------------
		//	Handle existing
		//	----------------------------------------

		$existing	= array();

		if ( ee()->input->get_post('existing') !== FALSE )
		{
			$existing	= explode( "||", ee()->input->get_post('existing', TRUE) );
		}

		//	----------------------------------------
		//	Query and construct
		//	----------------------------------------

		$select	= '<li class="message">'.lang('no_matching_authors').'</li>';

		$str 	= $this->_clean_str( ee()->input->get_post('author') );

		if ( $str == '' )
		{
			echo $select;
			exit();
		}

		$extra = ($str == '*') ? '' : " AND exp_members.screen_name LIKE '%" .
										ee()->db->escape_str( $str )."%' ";

		$sql = "SELECT	exp_members.member_id AS id,
						exp_members.screen_name AS name
				FROM	exp_members
				LEFT JOIN exp_member_groups
				ON		exp_member_groups.group_id = exp_members.group_id
				WHERE	exp_member_groups.site_id = '" .
							ee()->db->escape_str(ee()->config->item('site_id'))."'
				AND (
					 exp_members.group_id = 1 OR
					 exp_members.in_authorlist = 'y' OR
					 exp_member_groups.include_in_authorlist = 'y'
					 )
				AND exp_members.member_id NOT IN ('" .
					implode( "','", ee()->db->escape_str( $existing ) )."')
				{$extra}
				ORDER BY screen_name ASC, username ASC";

		$query	= ee()->db->query($sql);

		$select	= '';

		if ( $query->num_rows() == 0 )
		{
			$select .= '<li class="message">'.lang('no_matching_authors').'</li>';
		}
		else
		{
			foreach ( $query->result_array() as $row )
			{
				$select	.= '<li><input type="radio" name="user_authors_principal" value="'.$row['id'].'" style="display:none;" />'.$row['name'].' (<a href="'.$row['id'].'" alt="'.$row['id'].'">'.lang('add').'</a>)</li>';
			}
		}

		@header("Cache-Control: no-cache, must-revalidate");

		echo $select;

		exit();
	}
	// END user_authors_search()



	// --------------------------------------------------------------------

	/**
	 * AJAX Author Search json
	 *
	 * @access	public
	 * @return	string
	 */

	public function user_authors_search_json()
	{
		//	----------------------------------------
		//	Handle existing
		//	----------------------------------------

		$existing		= array();

		$return_data	= array('found' => FALSE, 'users' => array());

		if ( ee()->input->get_post('existing') !== FALSE )
		{
			$existing	= explode( "||", ee()->input->get_post('existing', TRUE) );
		}

		//	----------------------------------------
		//	Query and construct
		//	----------------------------------------

		$str 	= $this->_clean_str( ee()->input->get_post('author') );

		if ( $str == '' )
		{
			echo $this->json_encode($return_data);
			exit();
		}

		$extra = ($str == '*') ? '' : " AND exp_members.screen_name LIKE '%" .
										ee()->db->escape_str( $str )."%' ";

		$sql = "SELECT 		exp_members.member_id 	AS id,
							exp_members.screen_name AS name
				FROM 		exp_members
				LEFT JOIN 	exp_member_groups
				ON 			exp_member_groups.group_id = exp_members.group_id
				WHERE 		exp_member_groups.site_id = '" .
								ee()->db->escape_str(ee()->config->item('site_id')) . "'
				AND 		(
								exp_members.group_id = 1 OR
								exp_members.in_authorlist = 'y' OR
								exp_member_groups.include_in_authorlist = 'y'
				)
				AND 		exp_members.member_id
				NOT IN 		('".implode( "','", ee()->db->escape_str( $existing ) )."')
				{$extra}
				ORDER BY 	screen_name ASC, username ASC";

		$query	= ee()->db->query($sql);

		if ( $query->num_rows() > 0 )
		{
			$return_data['found'] = TRUE;

			foreach ( $query->result_array() as $row )
			{
				$return_data['users'][] = $row;
			}
		}

		@header("Cache-Control: no-cache, must-revalidate");
		@header("Content-type: application/json");

		echo $this->json_encode($return_data);
		exit();
	}
	// END user_authors_search


	// --------------------------------------------------------------------

	/**
	 * AJAX Author Add
	 *
	 * @access	public
	 * @return	string
	 */

	public function user_authors_add()
	{
		ee()->lang->loadfile( 'user' );

		$entry_id	= '';
		$hash		= '';

		if ( ee()->input->post('entry_id') !== FALSE AND
			ee()->input->post('entry_id') != '' )
		{
			$entry_id	= ee()->input->post('entry_id');
		}

		if ( ee()->input->post('hash') !== FALSE AND
			ee()->input->post('hash') != '' )
		{
			$hash		= ee()->input->post('hash');
		}

		//	----------------------------------------
		//	Author id?
		//	----------------------------------------

		if ( ee()->input->post('author_id') === FALSE OR
			ee()->input->post('author_id') == '' )
		{
			echo "!".lang('no_author_id');
			exit();
		}
		else
		{
			$author_id	= ee()->input->post('author_id');
		}

		//	----------------------------------------
		//	Has this already been saved?
		//	----------------------------------------

		$sql = "SELECT	id, author_id, entry_id, hash
				FROM	exp_user_authors
				WHERE	author_id = '".ee()->db->escape_str( $author_id )."'";

		if ( $entry_id != '' )
		{
			$sql	.= " AND entry_id = '".ee()->db->escape_str( $entry_id )."'";
		}
		elseif ( $hash != '' )
		{
			$sql	.= " AND hash = '".ee()->db->escape_str( $hash )."'";
		}

		$query	= ee()->db->query( $sql );

		if ( $query->num_rows() > 0 AND
			 $query->row('entry_id') == '0' )
		{
			ee()->db->update(
				'exp_user_authors',
				array( 'entry_id' => $entry_id ),
				array( 'id' => $query->row('id'))
			);
		}

		if ( $query->num_rows() == 0 )
		{
			$data['author_id']	= $author_id;
			$data['hash']		= $hash;
			$data['entry_date']	= ee()->localize->now;

			if ( $entry_id != '' )
			{
				$data['entry_id']	= $entry_id;
			}
		}

		ee()->db->insert('exp_user_authors', $data);

		//	----------------------------------------
		//	Return
		//	----------------------------------------

		echo lang('successful_add');


		exit();
	}
	//END user_authors_add


	// --------------------------------------------------------------------

	/**
	 * AJAX Author Delete
	 *
	 * @access	public
	 * @return	string
	 */

	public function user_authors_delete()
	{
		ee()->lang->loadfile( 'user' );

		$entry_id	= '';
		$hash		= '';

		if ( ee()->input->post('entry_id') !== FALSE AND
			ee()->input->post('entry_id') != '' )
		{
			$entry_id	= ee()->input->post('entry_id');
		}

		if ( ee()->input->post('hash') !== FALSE AND
			ee()->input->post('hash') != '' )
		{
			$hash		= ee()->input->post('hash');
		}

		//	----------------------------------------
		//	Author id?
		//	----------------------------------------

		if ( ee()->input->post('author_id') === FALSE OR
			ee()->input->post('author_id') == '' )
		{
			echo "!".lang('no_author_id');
			exit();
		}
		else
		{
			$author_id	= ee()->input->post('author_id');
		}

		//	----------------------------------------
		//	Has this already been saved?
		//	----------------------------------------

		$sql = "SELECT	id, author_id, entry_id, hash
				FROM	exp_user_authors
				WHERE	author_id = '".ee()->db->escape_str( $author_id )."'";

		if ( $entry_id != '' )
		{
			$sql	.= " AND entry_id = '".ee()->db->escape_str( $entry_id )."'";
		}
		elseif ( $hash != '' )
		{
			$sql	.= " AND hash = '".ee()->db->escape_str( $hash )."'";
		}

		$query	= ee()->db->query( $sql );

		if ( $query->num_rows() == 0 )
		{
			echo "!".lang('author_not_assigned');
			exit();
		}
		else
		{
			$sql = "DELETE FROM exp_user_authors
					WHERE author_id = '".ee()->db->escape_str( $author_id )."'";

			if ( $entry_id != '' )
			{
				$sql	.= " AND entry_id = '".ee()->db->escape_str( $entry_id )."'";
			}
			else
			{
				$sql	.= " AND hash = '".ee()->db->escape_str( $hash )."'";
			}
		}

		ee()->db->query( $sql );

		//	----------------------------------------
		//	Return
		//	----------------------------------------

		echo lang('successful_add');

		exit();
	}
	// END user_authors_delete


	// --------------------------------------------------------------------

	/**
	 * Clean Tag String
	 *
	 * @access	private
	 * @param	string
	 * @return	string
	 */

	private function _clean_str( $str = '' )
	{
		ee()->load->helper('text');

		if (ee()->config->item('auto_convert_high_ascii') == 'y')
		{
			$str =  ascii_to_entities( $str );
		}

		return ee()->security->xss_clean( $str );
	}
	// END _clean_str


	// --------------------------------------------------------------------

	/**
	 *	Publish Tab JS
	 *
	 *	Used, currently, for just the User Authors Tab,
	 *	since EE 2.x does not allow us to give
	 *	Publish Tabs to extensions.
	 *
	 *	@access		public
	 *	@return		string
	 */

	function publish_tab_javascript()
	{
		if ( ee()->input->get('channel_id') == FALSE)
		{
			exit('');
		}

		// --------------------------------------------
		//  Default View variables
		// --------------------------------------------

		$this->cached_vars['tag_name']	 = '';
		$this->cached_vars['channel_id'] = ee()->input->get('channel_id');

		// --------------------------------------------
		//  Publish Tab Name
		// --------------------------------------------

		// Load the string helper
		ee()->load->helper('string');
		ee()->load->model('user_model');

		$extension_settings = ee()->user_model->get_channel_ids();

		// --------------------------------------------
		//  Do we have a Publish Tab for this Channel?
		// --------------------------------------------

		if ( ! empty($extension_settings[$this->cached_vars['channel_id']]))
		{
			$this->cached_vars['tag_name'] = $extension_settings[$this->cached_vars['channel_id']];
		}

		//json url for members
		$this->cached_vars['template_uri']				= $this->base .
														'&method=ajax' .
														'&solspace_user_ajax=user_authors_template' .
														((ee()->input->get('entry_id') !== FALSE) ?
															'&entry_id=' . ee()->input->get('entry_id') : '');

		$this->cached_vars['user_search_uri']			= $this->base .
															'&method=ajax' .
															'&solspace_user_ajax=user_authors_search_json';

		$this->cached_vars['loading_img_uri'] 			= PATH_CP_GBL_IMG . 'indicator.gif';

		$this->cached_vars['lang_loading_users'] 		= lang('loading_users');

		// --------------------------------------------
		//  Output Our JS File
		// --------------------------------------------

		exit($this->view('publish_tab.js', null, TRUE));
	}
	// END publish_tab_javascript


	// --------------------------------------------------------------------

	/**
	 *	Auto Complete for User Authors Publish Tab
	 *
	 *	@access		public
	 *	@return		string
	 */

	function browse_authors_autocomplete()
	{
		// --------------------------------------------
		//  Existing
		// --------------------------------------------

		$existing = array();

		if ( ee()->input->get('current_authors') !== FALSE )
		{
			$existing = array_unique(
				preg_split(
					"/\s*,\s*/",
					trim(ee()->input->get('current_authors', TRUE)),
					', '
				)
			);
		}

		//	----------------------------------------
		//	Query DB
		//	----------------------------------------

		$sql = "SELECT	screen_name
				FROM	exp_members
				WHERE	group_id
				NOT IN	(2,3,4) ";

		if (count($existing) > 0)
		{
			$sql .= "AND screen_name NOT IN ('" .
					implode( "','", ee()->db->escape_str( $existing ) )."') ";
		}

		if (ee()->input->get('q') != '*')
		{
			$sql .= "AND screen_name LIKE '" .
					ee()->db->escape_like_str(ee()->input->get('q'))."%' ";
		}

		$sql .= "ORDER BY screen_name DESC LIMIT 100";

		$query = ee()->db->query($sql);

		$return_users = array();

		foreach($query->result_array() as $row)
		{
			$return_users[] = $row['screen_name'];
		}

		$output = implode("\n", array_unique($return_users));

		// --------------------------------------------
		//  Headers
		// --------------------------------------------

		ee()->output->set_status_header(200);
		@header("Cache-Control: max-age=5184000, must-revalidate");
		@header('Last-Modified: '.gmdate('D, d M Y H:i:s', gmmktime()).' GMT');
		@header('Expires: '.gmdate('D, d M Y H:i:s', gmmktime() + 1).' GMT');
		@header('Content-Length: '.strlen($output));

		//	----------------------------------------
		//	 Send JavaScript/CSS Header and Output
		//	----------------------------------------

		@header("Content-type: text/plain");

		exit($output);
	}
	// END browse_authors_autocomplete


	// --------------------------------------------------------------------

	/**
	 * Module Upgrading
	 *
	 * @access	public
	 * @return	bool
	 */

	public function user_module_update()
	{
		if ( ! isset($_POST['run_update']) OR $_POST['run_update'] != 'y')
		{
			$this->add_crumb(lang('update_user_module'));
			$this->cached_vars['form_url'] = $this->base.'&method=user_module_update';
			return $this->ee_cp_view('update_module.html');
		}

		require_once $this->addon_path.'upd.user.php';

		$U = new User_upd();

		if ($U->update() !== TRUE)
		{
			return $this->index(lang('update_failure'));
		}
		else
		{
			return $this->index(lang('update_successful'));
		}
	}
	// END user_module_update


	// --------------------------------------------------------------------

	/**
	 * user_authors_template
	 *
	 *
	 * @access	public
	 * @return	null
	 */

	public function user_authors_template()
	{
		$entry_id 			= ee()->input->get('entry_id');
		$in_primary_author	= ee()->input->get('primary_author');
		$in_user_authors 	= ee()->input->get('user_authors');

		$current_authors 	= array();

		$member_id_sql 		= '0';

		//is the entry_id useable?
		if ($entry_id !== 'FALSE' AND is_numeric($entry_id))
		{
			//data please
			$query	= ee()->db->query(
				"SELECT 	ua.author_id, ua.principal, m.screen_name
				 FROM 		exp_user_authors ua, exp_members m
				 WHERE 		ua.author_id != '0'
				 AND 		ua.entry_id = '".ee()->db->escape_str($entry_id)."'
				 AND 		ua.author_id = m.member_id
				 ORDER BY 	m.screen_name"
			);

			//if we have users, fill arrays and store primary
			if ($query->num_rows() > 0)
			{
				$current_authors = $query->result_array();

				foreach($current_authors as $row)
				{
					//to weed out current authors
					$member_id_sql .= ', ' . $row['author_id'];
				}
			}
		}

		//because EE 2 saves data on exit, no submit, we have to do some footwork
		//this is not an else statement because sometimes there is an entry_id
		//when there shouldn't be, but there might still be stored data
		//damned stored data
		if ( empty($current_authors) AND
			! in_array($in_user_authors, array(FALSE, ''), TRUE) )
		{
			$primary_author = ( ! in_array($in_primary_author, array(FALSE, ''), TRUE) AND
								is_numeric($in_primary_author) ) ? $in_primary_author : 0;

			$temp_authors = preg_split(
				"/[\s]*,[\s]*/is",
				$in_user_authors,
				-1,
				PREG_SPLIT_NO_EMPTY
			);

			//clean
			$search_authors = array();

			foreach($temp_authors AS $author_id)
			{
				if ( ! is_numeric($author_id)) {continue;}

				$search_authors[]	= trim($author_id);
			}

			$search_authors = implode(',', $search_authors);

			//data from members because this could be unsaved data
			$query	= ee()->db->query(
				"SELECT 	screen_name, member_id AS author_id
				 FROM 		exp_members
				 WHERE 		member_id != '0'
				 AND 		member_id
				 IN			($search_authors)
				 ORDER BY 	screen_name"
			);

			//if we have users, fill arrays and store principal correctly
			//different set of data, but needs to match for template
			//cannot rely on entry_id because there might not always be one.
			if ($query->num_rows() > 0)
			{
				foreach($query->result_array() AS $row)
				{
					$row['principal'] 	= ($row['author_id'] === $primary_author) ? 'y' : 'n';

					$current_authors[]  = $row;
				}
			}

		}

		//$this->cached_vars['users']				= $query->result_array();
		$this->cached_vars['current_authors']	= $current_authors;

		//words n stuff
		$lang_items = array(
			'assigned_authors',
			'choose_author_instructions',
			'browse_authors',
			'assigned_authors_instructions',
			'search',
			'no_matching_authors'
		);

		foreach($lang_items AS $item)
		{
			$this->cached_vars['lang_' . $item]	= lang($item);
		}

		echo $this->view('tab_template.html', null, TRUE);
	}
	// END user_author_template


	// --------------------------------------------------------------------

	function ajax()
	{
		if ( ee()->input->get('solspace_user_ajax') === FALSE)
		{
			exit('');
		}

		$method = ee()->input->get('solspace_user_ajax');

		//kill out if we find what we need
		if (is_callable(array($this, $method)))
		{
			$this->$method();
			exit();
		}

		exit('');
	}

}
// END User_cp_base CLASS
