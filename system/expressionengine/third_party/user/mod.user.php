<?php if ( ! defined('BASEPATH') ) exit('No direct script access allowed');

/**
 * User - User Side
 *
 * @package		Solspace:User
 * @author		Solspace, Inc.
 * @copyright	Copyright (c) 2008-2014, Solspace, Inc.
 * @link		http://solspace.com/docs/user
 * @license		http://www.solspace.com/license_agreement
 * @version		3.5.0
 * @filesource	user/mod.user.php
 */

require_once 'addon_builder/module_builder.php';

class User extends Module_builder_user
{
	public static $trigger			= 'user';
	public static $key_trigger		= 'key';

	protected 	$TYPE;
	protected 	$UP;
	protected 	$query;
	protected 	$email_obj			= FALSE;

	protected 	$dynamic			= TRUE;
	protected 	$member_only		= FALSE;
	protected 	$multipart			= FALSE;
	protected 	$search				= FALSE;
	protected 	$selected			= FALSE;

	private 	$entry_id			= 0;
	private 	$member_id			= 0;
	private 	$params_id			= 0;

	public 		$refresh			= 1440;	//	Cache refresh in minutes

	public		$cur_page			= 0;
	public		$current_page		= 0;
	public		$limit				= 100;
	public		$total_pages		= 0;
	public		$absolute_count		= 0;
	public		$absolute_results	= 0;
	public		$page_count			= '';
	public		$pager				= '';
	public		$paginate			= FALSE;
	public		$paginate_data		= '';
	public		$res_page			= '';
	public		$completed_override	= FALSE;
	public		$screen_name_dummy	= '4f99fa19c1d3b11c9ad517b0c073e450';
	public		$lang_dir			= '';

	private 	$group_id			= '';
	private 	$str				= '';

	protected 	$assigned_cats		= array();
	protected 	$cat_params			= array();
	protected 	$cat_parents		= array();
	protected 	$categories			= array();
	public    	$form_data			= array();
	public	  	$insert_data		= array();
	protected 	$img				= array();
	public 		$mfields			= array();
	protected 	$params				= array();
	protected 	$used_cat			= array();
	protected 	$catfields			= array();
	protected 	$cat_chunk			= array();

	protected 	$cat_formatting		= array(
		'category_tagdata' 		=> '',
		'category_formatting' 	=> '',
		'category_header' 		=> '',
		'category_indent' 		=> '',
		'category_body' 		=> '',
		'category_footer' 		=> '',
		'category_selected' 	=> '',
		'category_group_header' => '',
		'category_group_footer' => ''
	);

	/**
	 * Standard member fields
	 * @var array
	 */
	protected 	$standard			= array(
		'url',
		'location',
		'occupation',
		'interests',
		'language',
		'last_activity',
		'bday_d',
		'bday_m',
		'bday_y',
		'aol_im',
		'yahoo_im',
		'msn_im',
		'icq',
		'bio',
		'profile_views',
		'time_format',
		'timezone',
		'signature'
	);

	protected 	$check_boxes		= array(
		'accept_admin_email',
		'accept_user_email',
		'notify_by_default',
		'notify_of_pm',
		'smart_notifications'
	);

	protected 	$photo				= array(
		'photo_filename',
		'photo_width',
		'photo_height'
	);

	protected 	$avatar				= array(
		'avatar_filename',
		'avatar_width',
		'avatar_height'
	);

	protected 	$signature			= array(
		'signature',
		'sig_img_filename',
		'sig_img_width',
		'sig_img_height'
	);

	protected 	$images				= array(
		'avatar' 	=> 'avatar_filename',
		'photo' 	=> 'photo_filename',
		'sig' 		=> 'sig_img_filename'
	);

	/**
	 * Fields that are integers in the table
	 *
	 * We dont want to send this as blank strings
	 * otherwise MySQL strict errors occur.
	 *
	 * @var array
	 */
	protected	$int_fields			= array(
		'member_id',
		'group_id',
		'bday_d',
		'bday_m',
		'bday_y',
		'avatar_filename',
		'avatar_width',
		'avatar_height',
		'photo_width',
		'photo_height',
		'sig_img_width',
		'sig_img_height',
		'private_messages',
		'last_view_bulletins',
		'last_bulletin_date',
		'join_date',
		'last_visit',
		'last_activity',
		'total_entries',
		'total_comments',
		'total_forum_topics',
		'total_forum_posts',
		'last_entry_date',
		'last_comment_date',
		'last_forum_post_date',
		'last_email_date',
		'profile_views'
	);

	protected 	$preferences		= array();

	private 	$uploads			= array();


	// --------------------------------------------------------------------

	/**
	 *	Constructor
	 *
	 *	@access		public
	 *	@return		null
	 */

	public function __construct()
	{
		parent::__construct();

		if (version_compare($this->ee_version, '2.6.0', '<'))
		{
			$this->check_boxes[]	= 'daylight_savings';
			$this->standard[]		= 'daylight_savings';
		}

		// --------------------------------------------
		//  Language Files of Translating Might!
		// --------------------------------------------

		ee()->lang->loadfile('myaccount');
		ee()->lang->loadfile('member');
		// Goes last as User overrides a few Member language variables
		ee()->lang->loadfile('user');

		// --------------------------------------------
		//  Welcome Email
		// --------------------------------------------

		if (ee()->config->item('req_mbr_activation') == 'manual' AND
			ee()->db->table_exists('exp_user_welcome_email_list'))
		{
			$query = ee()->db->query(
				"SELECT m.screen_name, m.email, m.username, m.member_id
				 FROM 	exp_members AS m,
						exp_user_welcome_email_list AS el
				 WHERE 	m.member_id = el.member_id
				 AND 	el.email_sent = 'n'
				 AND 	el.group_id != m.group_id
				 LIMIT 	2"
			);

			foreach($query->result_array() as $row)
			{
				$this->welcome_email($row);
			}
		}

		//--------------------------------------------
		//	System lang directory
		//--------------------------------------------

		$this->lang_dir =  APPPATH . 'language/';

		//--------------------------------------------
		//	force https?
		//--------------------------------------------

		$this->_force_https();
	}
	// END constructor


	// --------------------------------------------------------------------

	/**
	 * Theme Folder URL
	 *
	 * Mainly used for codepack
	 *
	 * @access	public
	 * @return	string	theme folder url with ending slash
	 */

	public function theme_folder_url()
	{
		return $this->sc->addon_theme_url;
	}
	//END theme_folder_url


	// --------------------------------------------------------------------

	/**
	 *	Statistics for a Specific User
	 *
	 *
	 *	@access		public
	 *	@return		string
	 */

	public function stats()
	{
		//	----------------------------------------
		//	Member id only?
		//	----------------------------------------

		if ( ! $this->_member_id() )
		{
			return $this->no_results('user');
		}

		//	----------------------------------------
		//	Set exclude
		//	----------------------------------------

		$exclude	= array(
			'channel_id',			'weblog_id',			'tmpl_group_id',
			'upload_id',			'password',				'unique_id',
			'authcode',				'avatar_filename',		'avatar_width',
			'avatar_height',		'photo_width',			'photo_height',
			'sig_img_width',		'sig_img_height',		'notepad',
			'in_authorlist',		'accept_admin_email',	'accept_user_email',
			'notify_by_default',	'notify_of_pm',			'display_avatars',
			'display_signatures',	'smart_notifications',
			'cp_theme',				'profile_theme',		'forum_theme',
			'tracker',				'notepad_size',			'quick_links',
			'quick_tabs',			'pmember_id'
		);

		if (version_compare($this->ee_version, '2.7.0', '<'))
		{
			$exclude[] = 'localization_is_site_default';
		}

		if ( ee()->TMPL->fetch_param('exclude') )
		{
			$exclude	= array_merge( $exclude, explode( "|", ee()->TMPL->fetch_param('exclude') ) );
		}

		//	----------------------------------------
		//	Set include
		//	----------------------------------------

		$include		= array();

		if ( ee()->TMPL->fetch_param('include') )
		{
			$include	= array_merge( $include, explode( "|", ee()->TMPL->fetch_param('include') ) );
		}

		/**	----------------------------------------
		/**	Set dates
		/**	----------------------------------------*/

		$dates	= array(
			'join_date', 			'last_bulletin_date', 	'last_visit',
			'last_activity', 		'last_entry_date', 		'last_rating_date',
			'last_comment_date', 	'last_forum_post_date', 'last_email_date'
		);

		/**	----------------------------------------
		/**	Update profile views
		/**	----------------------------------------*/

		$this->_update_profile_views();

		/**	----------------------------------------
		/**	Fetch stats
		/**	----------------------------------------*/

		$query	= ee()->db->query(
			"SELECT m.*, mg.group_title,
					m.screen_name AS user_screen_name,
					( m.total_entries + m.total_comments ) AS total_combined_posts
			 FROM 	exp_members AS m, exp_member_groups AS mg
			 WHERE 	member_id = '".ee()->db->escape_str( $this->member_id )."'
			 AND 	m.group_id = mg.group_id
			 AND 	mg.site_id = '".ee()->db->escape_str(ee()->config->slash_item('site_id'))."'"
		);

		if ( $query->num_rows() == 0 )
		{
			return $this->no_results('user');
		}

		$query_row = $query->row_array();

		/**	----------------------------------------
		/**	Add additional values to $query->row
		/**	----------------------------------------*/

		$query_row['photo_url']				= ee()->config->item('photo_url');
		$query_row['avatar_url']			= ee()->config->slash_item('avatar_url');
		$query_row['sig_img_url']			= ee()->config->slash_item('sig_img_url');

		/** --------------------------------------------
		/**  Tweak Forum Variables to Match Meaning
		/** --------------------------------------------*/

		$query_row['total_forum_replies']	= $query->row('total_forum_posts');
		$query_row['total_forum_posts']		= $query->row('total_forum_topics') + $query->row('total_forum_posts');

		// --------------------------------------------
		//  Total Ratings
		//	- Removed in Rating 3.0 from exp_members, so we autodetect and add back in
		// --------------------------------------------

		if ( ! isset($query_row['total_ratings']))
		{
			$query_row['total_ratings'] = 0;

			if (stristr(ee()->TMPL->tagdata, 'total_ratings') && ee()->db->table_exists('exp_rating_stats'))
			{
				$rquery = ee()->db->query("SELECT count FROM exp_rating_stats
										   WHERE member_id = '".ee()->db->escape_str( $this->member_id )."'");

				if ($rquery->num_rows() > 0)
				{
					$query_row['total_ratings'] = $rquery->row('count');
				}
			}
		}

		/**	----------------------------------------
		/**	Handle categories
		/**	----------------------------------------*/

		$tagdata							= ee()->TMPL->tagdata;

		$this->member_only					= ( ee()->TMPL->fetch_param('member_only') !== FALSE AND
												ee()->TMPL->fetch_param('member_only') == 'no' ) ? FALSE: TRUE;

		$tagdata							= $this->_categories( $tagdata, 'yes' );

		/**	----------------------------------------
		/**	Conditional Prep
		/**	----------------------------------------*/

		$cond		= $query->row_array();

		$custom	= ee()->db->query(
			"SELECT *
			 FROM 	exp_member_data
			 WHERE 	member_id = '".ee()->db->escape_str( $this->member_id )."'"
		);

		$custom_row = $custom->row_array();

		foreach ( $this->_mfields() as $key => $val )
		{
			$cond[$key] = (isset($custom_row['m_field_id_'.$val['id']])) ?
								$custom_row['m_field_id_'.$val['id']] : '';
		}

		/** --------------------------------------------
		/**  Typography
		/** --------------------------------------------*/

		ee()->load->library('typography');


		ee()->typography->initialize();

		ee()->typography->convert_curly = FALSE;

		if ($query->row('bio') != '')
		{
			$query_row['bio'] = ee()->typography->parse_type(
				$query->row('bio'),
				array(
					'text_format'   => 'xhtml',
					'html_format'   => 'safe',
					'auto_links'    => 'y',
					'allow_img_url' => 'n'
				)
			);
		}

		//signature also needs love
		if ($query->row('signature') != '')
		{
			$query_row['signature'] = ee()->typography->parse_type(
				$query->row('signature'),
				array(
					'text_format'   => 'xhtml',
					'html_format'   => 'safe',
					'auto_links'    => 'y',
					'allow_img_url' => (ee()->config->item('sig_allow_img_hotlink') == 'y') ? 'y' : 'n'
				)
			);
		}

		/** --------------------------------------------
		/**  Conditionals
		/** --------------------------------------------*/

		$tagdata	= ee()->functions->prep_conditionals( $tagdata, $cond );

		/**	----------------------------------------
		/**	Parse all
		/**	----------------------------------------*/

		if ( preg_match( "/".LD.'all'.RD."(.*?)".LD.preg_quote($this->t_slash, '/').'all'.RD."/s", $tagdata, $match ) )
		{
			$str	= '';

			foreach ( $query->row_array() as $key => $val )
			{
				if ( count($include) > 0 )
				{
					if ( ! in_array( $key, $include ) )
					{
						continue;
					}
				}
				elseif ( in_array( $key, $exclude ) )
				{
					continue;
				}

				$all	= $match['1'];

				$all	= str_replace( LD.'label'.RD, $key, $all );

				if ( in_array( $key, $dates ) AND $val != 0 )
				{
					$all	= str_replace( LD.'value'.RD, $this->human_time($val), $all );
				}
				else
				{
					$all	= str_replace( LD.'value'.RD, $val, $all );
				}

				$str	.= $all;
			}

			$tagdata	= str_replace( $match[0], $str, $tagdata );
		}

		/**	----------------------------------------
		/**	Parse dates
		/**	----------------------------------------*/

		foreach ($dates as $val)
		{
			if (preg_match_all("/".LD.$val."\s+format=([\"'])([^\\1]*?)\\1".RD."/s", $tagdata, $matches))
			{
				for($i=0, $s=count($matches[2]); $i < $s; ++$i)
				{
					$str	= $matches[2][$i];

					$codes	= $this->fetch_date_params( $matches[2][$i] );

					foreach ( $codes as $code )
					{
						$str	= str_replace(
							$code,
							$this->convert_timestamp(
								$code,
								$query_row[$val],
								TRUE
							),
							$str
						);
					}

					$tagdata	= str_replace( $matches[0][$i], $str, $tagdata );
				}
			}
		}

		/**	----------------------------------------
		/**	Parse remaining standards
		/**	----------------------------------------*/

		foreach ( $query_row as $key => $val )
		{
			if ( in_array( $key, $dates ) )
			{
				$tagdata	= str_replace( LD.$key.RD, $val, $tagdata );
			}
			else
			{
				$tagdata	= str_replace( LD.$key.RD, $val, $tagdata );
			}
		}

		/**	----------------------------------------
		/**	Parse custom variables
		/**	----------------------------------------*/

		if ( $custom->num_rows() > 0 )
		{
			foreach ( $this->_mfields() as $key => $val )
			{
				/**	----------------------------------------
				/**	Conditionals
				/**	----------------------------------------*/

				$cond[ $val['name'] ]	= $custom_row['m_field_id_'.$val['id']];
				$tagdata				= ee()->functions->prep_conditionals( $tagdata, $cond );

				/**	----------------------------------------
				/**	Parse select
				/**	----------------------------------------*/

				foreach ( ee()->TMPL->var_pair as $k => $v )
				{
					if ( $k == "select_".$key )
					{
						$data		= ee()->TMPL->fetch_data_between_var_pairs( $tagdata, $k );

						$tagdata	= preg_replace(
							"/".LD.preg_quote($k, '/').RD."(.*?)".
								LD.preg_quote($this->t_slash, '/').preg_quote($k, '/').RD."/s",
							str_replace(
								'$',
								'\$',
								$this->_parse_select(
									$key,
									$custom_row,
									$data
								)
							),
							$tagdata
						);
					}
				}

				/**	----------------------------------------
				/**	Parse singles
				/**	----------------------------------------*/

				if (empty($custom_row['m_field_id_'.$val['id']]))
				{
					$tagdata = ee()->TMPL->swap_var_single($key, '', $tagdata);
				}
				else
				{
					$tagdata = ee()->TMPL->swap_var_single(
						$key,
						ee()->typography->parse_type(
							$custom_row['m_field_id_'.$val['id']],
							array(
								'text_format'   => $this->mfields[$key]['format'],
								'html_format'   => 'safe',
								'auto_links'    => 'n',
								'allow_img_url' => 'n'
							)
						),
						$tagdata
					);
				}
			}
		}

		/**	----------------------------------------
		/**	Return
		/**	----------------------------------------*/

		return $tagdata;
	}

	/* END stats */


	// --------------------------------------------------------------------

	/**
	 *	Users Tag
	 *
	 *	Displays a list of Users, what else?
	 *
	 *	@access		public
	 *	@return		string
	 */

	public function users()
	{
		/**	----------------------------------------
		/**	Assemble query
		/**	----------------------------------------*/

		if ( ee()->TMPL->fetch_param('disable') !== FALSE AND stristr('member_data', ee()->TMPL->fetch_param('disable')))
		{
			$sql	 = "SELECT DISTINCT m.*, ( m.total_entries + m.total_comments ) AS total_combined_posts";
		}
		else
		{
			$sql	 = "SELECT DISTINCT m.*, md.*, ( m.total_entries + m.total_comments ) AS total_combined_posts";
		}

		if (stristr(ee()->TMPL->tagdata, LD.'group_title'.RD) OR stristr(ee()->TMPL->tagdata, LD.'group_description'.RD))
		{
			$sql .= ", mg.group_title, mg.group_description ";
		}

		if ( ee()->TMPL->fetch_param('disable') !== FALSE AND stristr('member_data', ee()->TMPL->fetch_param('disable')))
		{
			$sql 	.= " FROM exp_members m ";
		}
		else
		{
			$sql 	.= " FROM exp_members m
						 LEFT JOIN exp_member_data md ON md.member_id = m.member_id";
		}

		if (stristr(ee()->TMPL->tagdata, LD.'group_title'.RD) OR stristr(ee()->TMPL->tagdata, LD.'group_description'.RD))
		{
			$sql .= " LEFT JOIN exp_member_groups AS mg ON mg.group_id = m.group_id";
		}

		/**	----------------------------------------
		/**	Dynamic?
		/**	----------------------------------------*/

		$dynamic	= TRUE;

		if ( ee()->TMPL->fetch_param('dynamic') !== FALSE AND $this->check_no(ee()->TMPL->fetch_param('dynamic')))
		{
			$dynamic	= FALSE;
		}

		/**	----------------------------------------
		/**	Fetch category
		/**	----------------------------------------*/

		if ( ee()->TMPL->fetch_param('category') !== FALSE AND ee()->TMPL->fetch_param('category') != '' )
		{
			$category	= str_replace( "C", "", ee()->TMPL->fetch_param('category') );
		}
		elseif ( preg_match("/\/C(\d+)/s", ee()->uri->uri_string, $match ) AND $dynamic === TRUE )
		{
			$category	= $match['1'];
		}

		/** --------------------------------------
		/**  Parse category indicator
		/** --------------------------------------*/

		// Text version of the category

		if (ee()->uri->uri_string != '' AND ee()->config->item("reserved_category_word") != '' AND in_array(ee()->config->item("reserved_category_word"), explode("/", ee()->uri->uri_string)) AND $dynamic)
		{
			if (preg_match("/(^|\/)".preg_quote(ee()->config->item("reserved_category_word"))."\/(.+?)($|\/)/i", ee()->uri->uri_string, $cmatch))
			{
				$cquery = ee()->db->query("SELECT cat_id
											FROM exp_categories
											WHERE site_id IN ('".implode("','", ee()->db->escape_str(ee()->TMPL->site_ids))."') AND
											cat_url_title = '".ee()->db->escape_str($cmatch[2])."'");

				if ($cquery->num_rows() > 0)
				{
					$category = $cquery->row('cat_id');
				}
			}
		}

		/**	----------------------------------------
		/**	Filter by category
		/**	----------------------------------------*/

		if ( isset( $category ) === TRUE )
		{
			$sql .= " LEFT JOIN exp_user_category_posts ucp ON ucp.member_id = m.member_id";
		}

		/**	----------------------------------------
		/**	Continue sql
		/**	----------------------------------------*/

		$sql	.= " WHERE m.member_id != ''";

		if (stristr(ee()->TMPL->tagdata, LD.'group_title'.RD) OR stristr(ee()->TMPL->tagdata, LD.'group_description'.RD))
		{
			$sql .= " AND mg.site_id = '".ee()->db->escape_str(ee()->config->item('site_id'))."'";
		}

		/**	----------------------------------------
		/**	Filter by category
		/**	----------------------------------------*/

		if ( isset( $category ) === TRUE )
		{
			$sql .= " AND ucp.cat_id = '".ee()->db->escape_str( $category )."'";
		}

		/**	----------------------------------------
		/**	Filter by member id
		/**	----------------------------------------*/

		if ( $member_ids = ee()->TMPL->fetch_param('member_id') )
		{
			$sql .= ee()->functions->sql_andor_string( $member_ids, 'm.member_id' );
		}

		/**	----------------------------------------
		/**	Filter by group id
		/**	----------------------------------------*/

		if ( $group_id = ee()->TMPL->fetch_param('group_id') )
		{
			$sql .= ee()->functions->sql_andor_string( $group_id, 'm.group_id' );
		}

		/**	----------------------------------------
		/**	Filter by alpha
		/**	----------------------------------------*/

		if ( ee()->TMPL->fetch_param('alpha') !== FALSE AND ee()->TMPL->fetch_param('alpha') != '' )
		{
			$alpha	= ee()->TMPL->fetch_param('alpha');

			$sql   .= " AND m.screen_name LIKE '".ee()->db->escape_like_str( $alpha )."%'";
		}

		/**	----------------------------------------
		/**	Filter method
		/**	----------------------------------------*/

		$possible_filters = array('exact', 'any');

		$filter_method = (in_array(ee()->TMPL->fetch_param('filter_method'), $possible_filters)) ? ee()->TMPL->fetch_param('filter_method') : 'any';

		/**	----------------------------------------
		/**	Filter by screen name
		/**	----------------------------------------*/

		if ( $letter = ee()->TMPL->fetch_param('screen_name') )
		{
			if ( $filter_method == 'exact' )
			{
				$sql .= " AND m.screen_name = '".ee()->db->escape_str($letter)."'";
			}
			elseif($filter_method == 'any')
			{
				$sql .= " AND m.screen_name LIKE '%".ee()->db->escape_like_str($letter)."%'";
			}
		}

		/**	----------------------------------------
		/**	Filter by standard field
		/**	----------------------------------------*/

		if ( $standard_member_field = ee()->TMPL->fetch_param('standard_member_field') )
		{
			$standard_field	= explode( "|", $standard_member_field );

			if ( isset( $standard_field[0] ) AND isset( $standard_field[1] ) AND in_array( $standard_field[0], $this->standard))
			{
				if ( $standard_field[1] == 'IS_EMPTY' )
				{
					$sql .= " AND `m`.`".$standard_field[0]."` = ''";
				}
				elseif ( $standard_field[1] == 'IS_NOT_EMPTY' )
				{
					$sql .= " AND `m`.`".$standard_field[0]."` != ''";
				}
				elseif ( $filter_method == 'exact' )
				{
					$sql .= " AND `m`.`".$standard_field[0]."` = '".ee()->db->escape_str($standard_field[1])."'";
				}
				else
				{
					$sql .= " AND `m`.`".$standard_field[0]."` LIKE '%".ee()->db->escape_like_str($standard_field[1])."%'";
				}
			}
		}

		/**	----------------------------------------
		/**	Filter by custom field
		/**	----------------------------------------*/

		if ( $custom_member_field = ee()->TMPL->fetch_param('custom_member_field') )
		{
			$this->_mfields();

			$custom_field	= explode( "|", $custom_member_field );

			if ( isset( $custom_field[0] ) AND isset( $custom_field[1] ) AND isset( $this->mfields[ $custom_field[0] ] ) )
			{
				if ($custom_field[1] == 'IS_EMPTY')
				{
					$sql .= " AND `md`.`m_field_id_".$this->mfields[ $custom_field[0] ]['id']."` = ''";
				}
				elseif($custom_field[1] == 'IS_NOT_EMPTY')
				{
					$sql .= " AND `md`.`m_field_id_".$this->mfields[ $custom_field[0] ]['id']."` != ''";
				}
				elseif ( $filter_method == 'exact' )
				{
					$sql .= " AND `md`.`m_field_id_".$this->mfields[ $custom_field[0] ]['id']."` = '".ee()->db->escape_str($custom_field[1])."'";
				}
				else
				{
					$sql .= " AND `md`.`m_field_id_".$this->mfields[ $custom_field[0] ]['id']."` LIKE '%".ee()->db->escape_like_str($custom_field[1])."%'";
				}
			}
		}

		/** --------------------------------------------
		/**  Magical Lookup Parameter Prefix
		/** --------------------------------------------*/

		$search_fields = array();

		if ( is_array(ee()->TMPL->tagparams))
		{
			foreach(ee()->TMPL->tagparams as $key => $value)
			{
				if (strncmp($key, 'search:', 7) == 0)
				{
					$this->_mfields();
					$search_fields[substr($key, strlen('search:'))] = $value;
				}
			}

			if (count($search_fields) > 0)
			{
				$sql .= $this->_search_fields($search_fields);
			}

		} // End is_array(ee()->TMPL->tagparams)

		/** ----------------------------------------
		/**	Order
		/** ----------------------------------------*/

		$sql	= $this->_order_sort( $sql );

		/** ----------------------------------------
		/**  Prep pagination
		/** ----------------------------------------*/

		$use_prefix = stristr(ee()->TMPL->tagdata, LD . 'user_paginate' . RD);

		$sql	= $this->_prep_pagination( $sql );

		/**	----------------------------------------
		/**	Empty
		/**	----------------------------------------*/

		if ( $sql == '' )
		{
			return $this->no_results('user');
		}

		/** ----------------------------------------
		/**	Run query
		/** ----------------------------------------*/

		$this->query	= ee()->db->query( $sql );

		if ($this->query->num_rows() == 0)
		{
			return $this->no_results('user');
		}
		else
		{
			ee()->TMPL->tagdata = preg_replace(
				"/".LD."if " .preg_quote($this->lower_name)."_no_results" .
					RD."(.*?)".LD.preg_quote($this->t_slash, '/')."if".RD."/s",
				'',
				ee()->TMPL->tagdata);
		}

		ee()->TMPL->log_item('Query Processed and Done.');

		/**	----------------------------------------
		/**	Return
		/**	----------------------------------------*/

		//support legacy
		if ($use_prefix)
		{
			$return	= $this->parse_pagination(array(
				'prefix' 	=> 'user',
				'tagdata' 	=> $this->_users()
			));
		}
		else
		{
			$return	= $this->parse_pagination(array(
				'tagdata' 	=> $this->_users()
			));
		}

		return $return;
	}

	/* END users */


	// --------------------------------------------------------------------

	/**
	 *	Subprocessing Users
	 *
	 *	Method that is used both by users() and results() to output a list of users
	 *
	 *	@access		public
	 *	@param		array
	 *	@return		string
	 */

	public function _users( $inject = array() )
	{
		/**	----------------------------------------
		/**	Set dates
		/**	----------------------------------------*/

		$dates	= array( 'join_date', 'last_bulletin_date', 'last_visit', 'last_activity',
						 'last_entry_date', 'last_rating_date', 'last_comment_date',
						 'last_forum_post_date', 'last_email_date' );

		/**	----------------------------------------
		/**	Set fixed vars
		/**	----------------------------------------*/

		$photo_url		= ee()->config->slash_item('photo_url');
		$avatar_url		= ee()->config->slash_item('avatar_url');
		$sig_img_url	= ee()->config->slash_item('sig_img_url');

		/**	----------------------------------------
		/**	Set classes
		/**	----------------------------------------*/

		ee()->load->library('typography');

		ee()->typography->initialize();

		ee()->typography->convert_curly = FALSE;

		/**	----------------------------------------
		/**	Switch $this->query to something friendly to manipulation
		/**	----------------------------------------*/

		$query_result	= $this->query->result_array();

		//--------------------------------------------
		// 'user_users_start' hook.
		//  - Execute something before we start looping through results
		//--------------------------------------------

		if (ee()->extensions->active_hook('user_users_start') === TRUE)
		{
			$inject = ee()->extensions->universal_call( 'user_users_start', $query_result, $inject );
			if (ee()->extensions->end_script === TRUE) return;
		}

		/**	----------------------------------------
		/**	Inject
		/**	----------------------------------------*/

		//this doesn't appear to work
		foreach ( $query_result as $id => $row )
		{
			if ( count( $inject ) > 0 AND isset( $inject[$row['member_id']] ) )
			{
				$query_result[$id]	= array_merge( $row, $inject[$row['member_id']] );
			}
		}

		/** ------------------------------------------
		/** Sort by principal if needed
		/** ------------------------------------------*/

		if ( ee()->TMPL->fetch_param('orderby') !== FALSE AND
			 in_array(ee()->TMPL->fetch_param('orderby') ,array("primary", "principal")) )
		{
			usort($query_result, array(&$this, "principal_sort"));

			if ( ee()->TMPL->fetch_param('sort') !== FALSE AND
				ee()->TMPL->fetch_param('sort') == "desc" )
			{
				$query_result = array_reverse($query_result);
			}
		}

		/**	----------------------------------------
		/**	Parse dates
		/**	----------------------------------------*/

		$date_variables_exist	= FALSE;
		$date_vars				= array();

		foreach ($dates as $val)
		{
			if (strpos(ee()->TMPL->tagdata, LD.$val) === FALSE) continue;

			//added escaped quotes for EE 2.9 variable conditionals
			if (preg_match_all("/".LD.$val."\s+format=(\\\'|\\\"|\"|\')([^\\1]*?)\\1".RD."/s", ee()->TMPL->tagdata, $matches))
			{
				$date_variables_exist = TRUE;

				for ($j = 0; $j < count($matches[0]); $j++)
				{
					$matches[0][$j] = str_replace(array(LD,RD), '', $matches[0][$j]);

					$date_vars[$val][$matches[0][$j]] = $this->fetch_date_params($matches[2][$j]);
				}
			}
		}

		/**	----------------------------------------
		/**	Loop
		/**	----------------------------------------*/

		$this->_mfields();

		$return			= '';
		$position		= 0;
		$total_results	= $this->query->num_rows();

		ee()->load->library('typography');

		ee()->typography->initialize();

		ee()->TMPL->log_item('Beginning Query Result Loop');
		foreach ( $query_result as $count => $row )
		{
			$position++;

			if ( isset( $inject[$row['member_id']] ) )
			{
				$row = array_merge( $row, $inject[$row['member_id']] );
			}

			$row['count']				= $count+1;
			$row['total_results']		= $total_results;
			$this->absolute_count++;
			$row['absolute_count']		= $this->absolute_count;
			$row['absolute_results']	= $this->absolute_results;

			/**	----------------------------------------
			/**	Hardcode some row vars
			/**	----------------------------------------*/

			$row['photo_url']	= $photo_url;
			$row['avatar_url']	= $avatar_url;
			$row['sig_img_url']	= $sig_img_url;

			$row['total_combined_posts'] = $row['total_forum_topics'] + $row['total_forum_posts'] +
											$row['total_entries'] + $row['total_comments'];

			/**	----------------------------------------
			/**	Conditionals
			/**	----------------------------------------*/

			$tagdata	= ee()->TMPL->tagdata;

			$cond		= $row;

			foreach($this->mfields as $key => $value)
			{
				$cond[$key] = ( ! array_key_exists('m_field_id_'.$value['id'], $row)) ? '' : $row['m_field_id_'.$value['id']];
			}

			$tagdata	= ee()->functions->prep_conditionals( $tagdata, $cond );

			/**	----------------------------------------
			/**	Set member id for categories
			/**	----------------------------------------*/

			$this->member_id		= $row['member_id'];
			$this->assigned_cats	= array();

			/**	----------------------------------------
			/**	Handle categories
			/**	----------------------------------------*/

			$tagdata	= $this->_categories( $tagdata, 'yes' );

			/**	----------------------------------------
			/**	Parse Switch
			/**	----------------------------------------*/

			if ( preg_match( "/".LD."(switch\s*=.+?)".RD."/is", $tagdata, $match ) > 0 )
			{
				$sparam = ee()->functions->assign_parameters($match['1']);

				$sw = '';

				if ( isset( $sparam['switch'] ) !== FALSE )
				{
					$sopt = explode("|", $sparam['switch']);

					$sw = $sopt[($position + count($sopt) -1) % count($sopt)];
				}

				$tagdata = ee()->TMPL->swap_var_single($match['1'], $sw, $tagdata);
			}

			/**	----------------------------------------
			/**	Parse remaining standards and injected vals
			/**	----------------------------------------*/

			foreach (ee()->TMPL->var_single as $key => $val)
			{
				/** --------------------------------------------
				/**  Bio Needs Special Typography Parsing
				/** --------------------------------------------*/

				if ($key == 'bio' && is_string($row['bio']) && $row['bio'] != '')
				{
					$row['bio'] = ee()->typography->parse_type(
						$row['bio'],
						array(
							'text_format'   => 'xhtml',
							'html_format'   => 'safe',
							'auto_links'    => 'y',
							'allow_img_url' => 'n'
						)
					);
				}

				//signature also needs love
				if ($key == 'signature' && is_string($row['signature']) && $row['signature'] != '')
				{
					$row['signature'] = ee()->typography->parse_type(
						$row['signature'],
						array(
							'text_format'   => 'xhtml',
							'html_format'   => 'safe',
							'auto_links'    => 'y',
							'allow_img_url' => (ee()->config->item('sig_allow_img_hotlink') == 'y') ? 'y' : 'n'
						)
					);
				}

				// --------------------------------------------
				//  Parse Dates!
				// --------------------------------------------

				foreach($date_vars as $type => $matches)
				{
					if ( ! isset($matches[$key])) continue;

						$val = str_replace($matches[$key],
										   $this->convert_timestamp(
										   $matches[$key],
										   $row[$type], TRUE),
										   $val);

					$tagdata = ee()->TMPL->swap_var_single($key, $val, $tagdata);

					continue(2);
				}

				// --------------------------------------------
				//  Parse Row Variables, if Exist
				// --------------------------------------------

				if ( isset($row[$key]))
				{
					$tagdata = str_replace( LD.$key.RD, $row[$key], $tagdata );
					continue;
				}
			}

			/**	----------------------------------------
			/**	Parse custom variables
			/**	----------------------------------------*/

			foreach ( $this->mfields as $key => $val )
			{
				/**	----------------------------------------
				/**	Parse select
				/**	----------------------------------------*/

				if (isset(ee()->TMPL->var_pair["select_".$key]))
				{
					// I added caching to the _parse_select() method as some people were doing
					// crazy things like using the Users tag to display dozens of people and their
					// custom fields. Originally, we were sending the entire $row array, which
					// prevented the caching from working. So, now we send a much more narrower
					// $field_data array, which allows the caching to work.

					$k 			= "select_".$key;
					$field_data = array();

					if (isset($row['m_field_id_'.$this->mfields[$key]['id']]))
					{
						$field_data['m_field_id_'.$this->mfields[$key]['id']] = $row['m_field_id_'.$this->mfields[$key]['id']];
					}

					$data	= ee()->TMPL->fetch_data_between_var_pairs( $tagdata, $k );

					$tagdata	= preg_replace(
						"/".LD.preg_quote($k, '/').RD."(.*?)".LD.preg_quote($this->t_slash, '/').preg_quote($k, '/').RD."/s",
						str_replace('$', '\$', $this->_parse_select( $key, $field_data, $data )),
						$tagdata
					);
				}

				/**	----------------------------------------
				/**	Parse abbreviated
				/**	----------------------------------------*/

				if (isset(ee()->TMPL->var_single['abbr_'.$key]))
				{
					if (empty($row['m_field_id_'.$val['id']]))
					{
						$tagdata = ee()->TMPL->swap_var_single('abbr_'.$key, '', $tagdata);
						continue;
					}

					// What the hell is this?
					$tagdata = ee()->TMPL->swap_var_single(
						'abbr_'.$key,
						ee()->typography->parse_type(
							substr($row['m_field_id_'.$val['id']], 0, 1 ).'.',
							array(
								'text_format'   => $this->mfields[$key]['format'],
								'html_format'   => 'safe',
								'auto_links'    => 'n',
								'allow_img_url' => 'n'
							)
						),
						$tagdata
					);
				}

				/**	----------------------------------------
				/**	Parse singles
				/**	----------------------------------------*/

				if (isset(ee()->TMPL->var_single[$key]))
				{
					if (empty($row['m_field_id_'.$val['id']]))
					{
						$tagdata = ee()->TMPL->swap_var_single($key, '', $tagdata);
						continue;
					}

					$tagdata = ee()->TMPL->swap_var_single(
						$key,
						ee()->typography->parse_type(
							$row['m_field_id_'.$val['id']],
							array(
								'text_format'   => $this->mfields[$key]['format'],
								'html_format'   => 'safe',
								'auto_links'    => 'n',
								'allow_img_url' => 'n'
							)
						 ),
						$tagdata
					);
				}
			}

			$return	.= $tagdata;
		}

		/**	----------------------------------------
		/**	Backspace
		/**	----------------------------------------*/

		$backspace = 0;

		if ( isset(ee()->TMPL) AND is_object(ee()->TMPL) AND ctype_digit( ee()->TMPL->fetch_param('backspace') ) )
		{
			$backspace = ee()->TMPL->fetch_param('backspace');
		}

		$return	= ( $backspace > 0 ) ? substr( $return, 0, - $backspace ): $return;

		/**	----------------------------------------
		/**	Return
		/**	----------------------------------------*/

		return $return;
	}

	/* END sub users */

	// --------------------------------------------------------------------

	/**
	 *	Sorts a Query Result by A Principal Value
	 *
	 *	@access		public
	 *	@param		string
	 *	@param		string
	 *	@return		integer
	 */

	public function principal_sort($a, $b)
	{
		return strnatcmp($a['principal'], $b['principal']);
	}
	/* END principal sort */

	// --------------------------------------------------------------------

	/**
	 *	Authors
	 *
	 *	Returns the info of the most recently cached array depending on cache type.
	 *
	 *	@access		public
	 *	@return		string
	 */

	public function authors()
	{
		/**	----------------------------------------
		/**	Is this feature enabled?
		/**	----------------------------------------*/

		if ( ee()->db->table_exists( 'exp_user_authors' ) === FALSE )
		{
			return $this->no_results('user');
		}

		/**	----------------------------------------
		/**	Grab entry id
		/**	----------------------------------------*/

		if ( $this->_entry_id() === FALSE )
		{
			return $this->no_results('user');
		}

		/**	----------------------------------------
		/**	Grab authors
		/**	----------------------------------------*/

		$sql	= "SELECT ua.author_id, ua.principal
				   FROM exp_user_authors ua
				   WHERE ua.entry_id = '".ee()->db->escape_str( $this->entry_id )."'";

		$query	= ee()->db->query( $sql );

		/**	----------------------------------------
		/**	Results?
		/**	----------------------------------------*/

		if ( $query->num_rows() == 0 )
		{
			return $this->no_results('user');
		}

		/**	----------------------------------------
		/**	Prepare injection array
		/**	----------------------------------------*/

		$inject	= array();
		$ids	= array();

		foreach ( $query->result_array() as $row )
		{
			$inject[$row['author_id']]['principal']	= $row['principal'];
			$inject[$row['author_id']]['primary']	= $row['principal'];
			$ids[]	= ee()->db->escape_str( $row['author_id'] );
		}

		//why do you hate me kelsey?
		if (is_object(ee()->TMPL) AND
			isset(ee()->TMPL->tagparams['orderby']) AND
			ee()->TMPL->tagparams['orderby'] === 'primary')
		{
			ee()->TMPL->tagparams['orderby'] = 'principal';
		}

		/**	----------------------------------------
		/**	Run full query
		/**	----------------------------------------*/

		$sql	 = "SELECT DISTINCT m.*, md.*";

		if (stristr(ee()->TMPL->tagdata, LD.'group_title'.RD) OR stristr(ee()->TMPL->tagdata, LD.'group_description'.RD))
		{
			$sql .= ", mg.group_title, mg.group_description ";
		}

		$sql 	.= " FROM exp_members m
					 LEFT JOIN exp_member_data md ON md.member_id = m.member_id
					 LEFT JOIN exp_user_authors ua ON ua.author_id = m.member_id";

		if (stristr(ee()->TMPL->tagdata, LD.'group_title'.RD) OR stristr(ee()->TMPL->tagdata, LD.'group_description'.RD))
		{
			$sql .= " LEFT JOIN exp_member_groups AS mg ON mg.group_id = m.group_id";
		}


		$sql	.= " WHERE ua.author_id IN ('".implode( "','", $ids )."')
					 AND ua.entry_id = '".ee()->db->escape_str( $this->entry_id )."'";

		if (stristr(ee()->TMPL->tagdata, LD.'group_title'.RD) OR stristr(ee()->TMPL->tagdata, LD.'group_description'.RD))
		{
			$sql .= " AND mg.site_id = '".ee()->db->escape_str(ee()->config->item('site_id'))."'";
		}

		/** ----------------------------------------
		/**	Order
		/** ----------------------------------------*/

		$sql	= $this->_order_sort( $sql, array('principal' => 'ua'));

		$this->query	= ee()->db->query( $sql );

		/**	----------------------------------------
		/**	Results?
		/**	----------------------------------------*/

		if ( $this->query->num_rows() == 0 )
		{
			return $this->no_results('user');
		}

		/**	----------------------------------------
		/**	Return
		/**	----------------------------------------*/

		return $this->_users( $inject );
	}

	/* END authors */


	// --------------------------------------------------------------------

	/**
	 *	Entries
	 *
	 *	List of Entries for an Author
	 *
	 *	@access		public
	 *	@return		string
	 */

	public function entries()
	{
		/**	----------------------------------------
		/**	Is this feature enabled?
		/**	----------------------------------------*/

		if ( ee()->db->table_exists( 'exp_user_authors' ) === FALSE )
		{
			return $this->no_results('user');
		}

		/**	----------------------------------------
		/**	Grab member id
		/**	----------------------------------------*/

		if ( $this->_member_id() === FALSE )
		{
			return $this->no_results('user');
		}

		/**	----------------------------------------
		/**	Fetch entries
		/**	----------------------------------------*/

		$query	= ee()->db->query(
			"SELECT DISTINCT 	entry_id
			 FROM 				exp_user_authors
			 WHERE 				author_id != 0
			 ".ee()->functions->sql_andor_string( $this->member_id, 'author_id'));

		if ( $query->num_rows() == 0 )
		{
			return $this->no_results('user');
		}

		/**	----------------------------------------
		/**	Prep
		/**	----------------------------------------*/

		$this->entry_id	= '';

		foreach ( $query->result_array() as $row )
		{
			$this->entry_id	.= $row['entry_id'].'|';
		}

		/**	----------------------------------------
		/**	Parse entries
		/**	----------------------------------------*/

		if ( ! $tagdata = $this->_entries( array('dynamic' => 'off') ) )
		{
			return $this->no_results('user');
		}

		return $tagdata;
	}

	/* END entries */


	// --------------------------------------------------------------------

	/**
	 *	List of Entires for an Author, Sub-Processing for entries() method
	 *
	 *	@access		private
	 *	@param		array
	 *	@return		string
	 */

	private function _entries ( $params = array() )
	{

		/**	----------------------------------------
		/**	Execute?
		/**	----------------------------------------*/

		if ( $this->entry_id == '' ) return FALSE;

		/**	----------------------------------------
		/**	Invoke Channel/Weblog class
		/**	----------------------------------------*/

		if (! class_exists('Channel'))
		{
			require PATH_MOD.'channel/mod.channel.php';
		}

		$channel = new Channel();


		// --------------------------------------------
		//  Invoke Pagination for EE 2.4 and Above
		// --------------------------------------------

		$channel = $this->add_pag_to_channel($channel);

		/**	----------------------------------------
		/**	Pass params
		/**	----------------------------------------*/

		ee()->TMPL->tagparams['entry_id']	= $this->entry_id;

		ee()->TMPL->tagparams['inclusive']	= '';

		if ( isset( $params['dynamic'] ) AND $params['dynamic'] == "off" )
		{

				ee()->TMPL->tagparams['dynamic'] = 'no';
		}

		/**	----------------------------------------
		/**	Pre-process related data
		/**	----------------------------------------*/

		if (version_compare($this->ee_version, '2.6.0', '<'))
		{
			ee()->TMPL->tagdata	= ee()->TMPL->assign_relationship_data( ee()->TMPL->tagdata );

		}


		ee()->TMPL->var_single	= array_merge( ee()->TMPL->var_single, ee()->TMPL->related_markers );

		/**	----------------------------------------
		/**	Execute needed methods
		/**	----------------------------------------*/


		$channel->fetch_custom_channel_fields();

		$channel->fetch_custom_member_fields();

		// --------------------------------------------
		//  Pagination Tags Parsed Out
		// --------------------------------------------

		$channel = $this->fetch_pagination_data($channel);

		/**	----------------------------------------
		/**	Grab entry data
		/**	----------------------------------------*/

		//$channel->create_pagination();

		$channel->build_sql_query();

		if ($channel->sql == '')
		{
			return $this->no_results();
		}

		$channel->query = ee()->db->query($channel->sql);

		if ( ! isset( $channel->query ) OR
			 $channel->query->num_rows() == 0 )
		{
			return $this->no_results();
		}

		ee()->load->library('typography');
		ee()->typography->initialize();
		ee()->typography->convert_curly = FALSE;


		$channel->fetch_categories();

		/**	----------------------------------------
		/**	Parse and return entry data
		/**	----------------------------------------*/

		$channel->parse_channel_entries();

		$channel = $this->add_pagination_data($channel);

		/**	----------------------------------------
		/**	Count tag
		/**	----------------------------------------*/

		if (version_compare($this->ee_version, '2.6.0', '<'))
		{
			if (count(ee()->TMPL->related_data) > 0 AND
			count($channel->related_entries) > 0)
			{
				$channel->parse_related_entries();
			}

			if (count(ee()->TMPL->reverse_related_data) > 0 AND
				count($channel->reverse_related_entries) > 0)
			{
				$channel->parse_reverse_related_entries();
			}
		}


		// ----------------------------------------
		//  Handle problem with pagination segments in the url
		// ----------------------------------------

		if ( preg_match("#(/P\d+)#", ee()->uri->uri_string, $match) )
		{
			$channel->return_data	= str_replace( $match['1'], "", $channel->return_data );
		}
		elseif ( preg_match("#(P\d+)#", ee()->uri->uri_string, $match) )
		{
			$channel->return_data	= str_replace( $match['1'], "", $channel->return_data );
		}

		$tagdata = $channel->return_data;

		return $tagdata;
	}
	// END _entries()


	// --------------------------------------------------------------------

	/**
	 *	Edit Profile Form
	 *
	 *	@access		public
	 *	@return		string
	 */

	public function edit()
	{
		$this->form_data = array();

		//	----------------------------------------
		//	Member id only?
		//	----------------------------------------

		if ( ! $this->_member_id() )
		{
			return $this->no_results('user');
		}

		//	----------------------------------------
		//	If an admin is editing a member,
		//	make sure they are allowed
		//	----------------------------------------

		if (ee()->session->userdata['member_id'] != $this->member_id AND
			ee()->session->userdata['group_id'] != 1
			AND
			(
				ee()->TMPL->fetch_param('group_id') === FALSE OR
				(
					ee()->TMPL->fetch_param('group_id') !== FALSE AND
					! in_array(
						ee()->session->userdata['group_id'],
						preg_split(
							"/,|\|/",
							ee()->TMPL->fetch_param('group_id')
						)
					)
				)
			)
		)
		{
			return $this->no_results('user');
		}

		//--------------------------------------------
		// 'user_edit_start' hook.
		//  - Execute something before we show the profile edit form
		//--------------------------------------------

		if (ee()->extensions->active_hook('user_edit_start') === TRUE)
		{
			$edata = ee()->extensions->universal_call(
				'user_edit_start',
				$this->member_id
			);
			if (ee()->extensions->end_script === TRUE) return;
		}

		//	----------------------------------------
		//	Grab member data
		//	----------------------------------------

		$select	= "email, group_id, member_id, screen_name, username";

		$arr	= array_merge(
			$this->standard,
			$this->check_boxes,
			$this->photo,
			$this->avatar,
			$this->signature
		);

		foreach ( $arr as $a )
		{
			$select	.= ", " . $a;
		}

		$query = ee()->db
					->select($select)
					->where('member_id', $this->member_id)
					->get('members');

		if ( $query->num_rows() == 0 )
		{
			return $this->no_results('user');
		}

		$query_row = $query->row_array();

		//	----------------------------------------
		//	Userdata
		//	----------------------------------------

		$tagdata	= ee()->TMPL->tagdata;

		//	----------------------------------------
		//	Sniff for checkboxes
		//	----------------------------------------

		$checks			= '';
		$custom_checks	= '';

		if ( preg_match_all( "/name=(['|\"])?([\w\-\[\]]+)(\1)?/s", $tagdata, $match ) )
		{
			$this->_mfields();

			foreach ( $match['2'] as $m )
			{
				$m	= str_replace( '[]', '', $m );

				if ( in_array( $m, $this->check_boxes ) )
				{
					$checks	.= $m."|";
				}

				if ( isset( $this->mfields[ $m ] ) AND
					$this->mfields[ $m ]['type'] == 'select' )
				{
					$custom_checks	.= $m."|";
				}
			}
		}

		//	----------------------------------------
		//	Sniff for fields of type 'file'
		//	----------------------------------------

		if ( preg_match( "/type=['|\"]?file['|\"]?/", $tagdata, $match ) )
		{
			$this->multipart	= TRUE;
		}

		//	----------------------------------------
		//	Add additional values to $query->row
		//	----------------------------------------

		$query_row['photo_url']		= ee()->config->slash_item('photo_url');
		$query_row['avatar_url']	= ee()->config->slash_item('avatar_url');
		$query_row['sig_img_url']	= ee()->config->slash_item('sig_img_url');

		//	----------------------------------------
		//	Handle categories
		//	----------------------------------------

		$tagdata = $this->_categories($tagdata);

		//	----------------------------------------
		//	Conditional Prep
		//	----------------------------------------

		$cond		= $query_row;

		$custom		= ee()->db
						->where('member_id', $this->member_id)
						->get('member_data');

		$custom_row = $custom->row_array();

		foreach ( $this->_mfields() as $key => $val )
		{
			$cond[$key] = (isset($custom_row['m_field_id_'.$val['id']])) ?
							$custom_row['m_field_id_'.$val['id']] :
							'';
		}

		// --------------------------------------------
		//  Conditionals
		// --------------------------------------------

		$tagdata = ee()->functions->prep_conditionals($tagdata, $cond);

		//	----------------------------------------
		//	Parse var pairs
		//	----------------------------------------

		foreach ( ee()->TMPL->var_pair as $key => $val )
		{
			// --------------------------------------------
			//  Member Groups Select List
			// --------------------------------------------

			if ($key == 'select_member_groups')
			{
				if (ee()->TMPL->fetch_param('allowed_groups') !== FALSE)
				{
					$data = ee()->TMPL->fetch_data_between_var_pairs(
						$tagdata,
						$key
					);

					$tagdata	= preg_replace(
						"/" . LD . $key . RD .
							"(.*?)" .
						LD . preg_quote($this->t_slash, '/') . $key . RD."/s",
						str_replace(
							'$',
							'\$',
							$this->_parse_select_member_groups(
								$data,
								$query_row['group_id']
							)
						),
						$tagdata
					);
				}
				else
				{
					$tagdata = preg_replace(
						"/" . LD . $key . RD .
							"(.*?)" .
						LD . preg_quote($this->t_slash, '/') . $key . RD . "/s",
						'',
						$tagdata
					);
				}
			}

			//	----------------------------------------
			//	Timezones
			//	----------------------------------------

			/**
			 * @deprecated
			 * This shouldn't be used in EE 2.6
			 * but we need this as a fallback for people
			 * who don't want to change.
			 * People should be using the new {user:timezone_menu}
			 */
			if ( $key == 'timezones' )
			{
				preg_match(
					"/" . LD . $key . RD .
						"(.*?)" .
					LD . preg_quote($this->t_slash, '/') . $key . RD . "/s",
					$tagdata,
					$match
				);

				$r	= '';

				foreach ( $this->timezones() as $key => $val )
				{
					$out		= $match['1'];

					$checked	= (
						isset($query_row['timezone']) AND
						$query_row['timezone'] == $key
					) ? 'checked="checked"': '';

					$selected	= (
						isset($query_row['timezone']) AND
						$query_row['timezone'] == $key
					) ? 'selected="selected"': '';

					$out = str_replace(LD."zone_name".RD, $key, $out);
					$out = str_replace(LD."zone_label".RD, lang($key), $out);
					$out = str_replace(LD."checked".RD, $checked, $out);
					$out = str_replace(LD."selected".RD, $selected, $out);

					$r	.= $out;
				}

				$tagdata	= str_replace( $match[0], $r, $tagdata );
			}

			//	----------------------------------------
			//	Time format
			//	----------------------------------------

			if ( $key == 'time_formats' )
			{
				preg_match(
					"/" . LD . $key . RD .
						"(.*?)" .
					LD . preg_quote($this->t_slash, '/') . $key . RD . "/s",
					$tagdata,
					$match
				);

				$r	= '';

				foreach ( array( 'us', 'eu' ) as $key )
				{
					$out		= $match['1'];

					$checked	= (
						isset($query_row['time_format'] ) AND
						$query_row['time_format'] == $key
					) ? 'checked="checked"' : '';

					$selected	= (
						isset($query_row['time_format']) AND
						$query_row['time_format'] == $key
					) ? 'selected="selected"': '';

					$out = str_replace(LD."time_format_name".RD, $key, $out);
					$out = str_replace(LD."time_format_label".RD, lang($key), $out);
					$out = str_replace(LD."checked".RD, $checked, $out);
					$out = str_replace(LD."selected".RD, $selected, $out);

					$r	.= $out;
				}

				$tagdata	= str_replace( $match[0], $r, $tagdata );
			}

			//	----------------------------------------
			//	Languages
			//	----------------------------------------

			if ( $key == 'languages' )
			{
				$dirs = array();

				if (is_dir($this->lang_dir) AND
					$fp = @opendir($this->lang_dir))
				{
					while (FALSE !== ($file = readdir($fp)))
					{
						if (is_dir($this->lang_dir.$file) AND
							substr($file, 0, 1) != ".")
						{
							$dirs[] = $file;
						}
					}
					closedir($fp);
				}

				sort($dirs);

				preg_match(
					"/" . LD . $key . RD .
						"(.*?)" .
					LD . preg_quote($this->t_slash, '/') . $key . RD . "/s",
					$tagdata,
					$match
				);

				$r	= '';

				foreach ( $dirs as $key )
				{
					$out		= $match['1'];

					$checked	= (
						isset($query_row['language']) AND
						$query_row['language'] == $key
					) ? 'checked="checked"': '';

					$selected	= (
						isset($query_row['language']) AND
						$query_row['language'] == $key
					) ? 'selected="selected"': '';

					$out = str_replace(LD."language_name".RD, $key, $out);
					$out = str_replace(LD."language_label".RD, ucfirst($key), $out);
					$out = str_replace(LD."checked".RD, $checked, $out);
					$out = str_replace(LD."selected".RD, $selected, $out);

					$r	.= $out;
				}

				$tagdata	= str_replace($match[0], $r, $tagdata );
			}
		}

		// -------------------------------------
		//	new timezone type
		// -------------------------------------

		$tagdata = $this->parse_timezone_menu_tag($tagdata, $query_row['timezone']);

		//	----------------------------------------
		//	Parse primary variables
		//	----------------------------------------

		foreach ( $query_row AS $key => $val )
		{
			$tagdata = ee()->TMPL->swap_var_single(
				$key,
				$val,
				$tagdata
			);
		}

		//	----------------------------------------
		//	Parse custom variables
		//	----------------------------------------

		if ( $custom->num_rows() > 0 )
		{
			foreach ($this->_mfields() as $key => $val)
			{
				//	----------------------------------------
				//	Parse select
				//	----------------------------------------

				foreach (ee()->TMPL->var_pair as $k => $v )
				{
					if ( $k == "select_".$key )
					{
						$data = ee()->TMPL->fetch_data_between_var_pairs(
							$tagdata,
							$k
						);

						$tagdata = preg_replace(
							"/" . LD . $k . RD .
								"(.*?)" .
							LD . preg_quote($this->t_slash, '/') . $k . RD . "/s",
							str_replace(
								'$',
								'\$',
								$this->_parse_select($key, $custom_row, $data)
							),
							$tagdata
						);
					}
				}

				//	----------------------------------------
				//	Parse singles
				//	----------------------------------------

				$tagdata	= ee()->TMPL->swap_var_single(
					$key,
					$custom_row['m_field_id_'.$val['id']],
					$tagdata
				);

				//	----------------------------------------
				//	Parse Language
				//	----------------------------------------

				$tagdata = ee()->TMPL->swap_var_single(
					'lang:' . $key . ':label',
					$val['label'],
					$tagdata
				);

				$tagdata = ee()->TMPL->swap_var_single(
					'lang:' . $key . ':description',
					$val['description'],
					$tagdata
				);
			}
		}

		//	----------------------------------------
		//	Prep data
		//	----------------------------------------

		$this->form_data['tagdata']	= $tagdata;

		$this->form_data['ACT']		= ee()->functions->fetch_action_id(
			'User',
			'edit_profile'
		);

		$this->form_data['RET']		= (
			isset($_POST['RET'])
		) ? $_POST['RET'] : ee()->functions->fetch_current_uri();

		if ( ee()->TMPL->fetch_param('form_name') !== FALSE AND
			ee()->TMPL->fetch_param('form_name') != '' )
		{
			$this->form_data['name'] = ee()->TMPL->fetch_param('form_name');
		}

		$this->form_data['id']		= ee()->TMPL->fetch_param(
			'form_id',
			'member_form'
		);

		$this->form_data['return']	= ee()->TMPL->fetch_param(
			'return',
			''
		);

		$params = array(
			'member_id'				=> $this->member_id,
			'checks'				=> $checks,
			'custom_checks'			=> $custom_checks,
			'username'				=> $query_row['username'],
			'username_override'		=> ee()->TMPL->fetch_param(
				'username_override',
				''
			),
			'exclude_username'		=> ee()->TMPL->fetch_param(
				'exclude_username',
				''
			),
			'required'				=> ee()->TMPL->fetch_param(
				'required',
				''
			),
			'group_id'				=> ee()->TMPL->fetch_param(
				'group_id',
				''
			),
			'password_required'		=> ee()->TMPL->fetch_param(
				'password_required',
				''
			),
			'secure_action'			=> ee()->TMPL->fetch_param(
				'secure_action',
				'no'
			),
			'screen_name_password_required' => ee()->TMPL->fetch_param(
				'screen_name_password_required',
				'y'
			),
		);

		$this->params = array_merge($this->params, $params);

		if (ee()->TMPL->fetch_param('allowed_groups') !== FALSE AND
			ee()->TMPL->fetch_param('allowed_groups') != '')
		{
			$this->params['allowed_groups']	= ee()->TMPL->fetch_param(
				'allowed_groups'
			);
		}

		//	----------------------------------------
		//	Return
		//	----------------------------------------

		return $this->_form();
	}
	// END edit


	// --------------------------------------------------------------------

	/**
	 *	Edit Profile Processing Method
	 *
	 *	@access		public
	 *	@return		string
	 */

	public function edit_profile()
	{
		$this->insert_data = array();

		//	----------------------------------------
		//	Logged in?
		//	----------------------------------------

		if ( ee()->session->userdata['member_id'] == 0 )
		{
			return $this->_output_error(
				'general',
				array(lang('not_authorized'))
			);
		}

		//	----------------------------------------
		//	Missing member_id?
		//	----------------------------------------

		if ( ! $member_id = $this->_param('member_id') )
		{
			return $this->_output_error(
				'general',
				array(lang('not_authorized'))
			);
		}

		//	----------------------------------------
		//	We'll use the $admin variable to handle
		//	occasions where one member is allowed
		//	to edit another member's profile.
		//	----------------------------------------

		if ( ee()->session->userdata['group_id'] == 1 OR
			(
				$this->_param('group_id') AND
				in_array(
					ee()->session->userdata['group_id'],
					preg_split( "/,|\|/", $this->_param('group_id'))
				)
			)
		 )
		{
			$admin	= TRUE;
		}
		else
		{
			$admin	= FALSE;
		}

		//	----------------------------------------
		//	If an admin is editing a member,
		//	make sure they are allowed
		//	----------------------------------------

		if ( ee()->session->userdata['member_id'] != $member_id AND
			$admin === FALSE )
		{
			return $this->_output_error(
				'general',
				array(lang('not_authorized'))
			);
		}

		//--------------------------------------------
		//	load auth for 2.2.x+
		//--------------------------------------------

		ee()->load->library('auth');

		// -------------------------------------------
		// 'user_edit_start' hook.
		//  - Do something when a user edits their profile
		//  - Added $cfields for User 3.3.9
		// -------------------------------------------

		if (ee()->extensions->active_hook('user_edit_start') === TRUE)
		{
			$edata = ee()->extensions->universal_call(
				'user_edit_start',
				$member_id,
				$admin,
				$this
			);
			if (ee()->extensions->end_script === TRUE) return;
		}

		// --------------------------------------------
		//  Prepare for Update Email of Peace and Love
		// --------------------------------------------

		$update_admin_email = FALSE;

		$wquery = ee()->db
						->select('preference_value, preference_name')
						->where_in('preference_name', array(
							'member_update_admin_notification_template',
							'member_update_admin_notification_emails'
						))
						->get('user_preferences');

		if ($wquery->num_rows() >= 2)
		{
			$update_admin_email = TRUE;

			foreach($wquery->result_array() as $row)
			{
				${$row['preference_name']} = stripslashes($row['preference_value']);
			}

			$oquery   = ee()->db->query(
				"SELECT m.*, md.*
				 FROM 	exp_members AS m,
						exp_member_data AS md
				 WHERE 	md.member_id = m.member_id
				 AND 	m.member_id = '".ee()->db->escape_str($member_id)."'"
			);

			$old_data = ($oquery->num_rows() == 0) ? array() : $oquery->row_array();
		}

		//	----------------------------------------
		//	Clean the post
		//	----------------------------------------

		//passwords should not be xss cleaned because they get hashed
		//and xss clean removes special characters
		//You say: 'but later, we use get_post??',
		//and i say: 'yeah, this still affects it'.
		$temp_pass 		= isset($_POST['password']) 		? $_POST['password'] : '';
		$temp_confirm 	= isset($_POST['password_confirm']) ? $_POST['password_confirm'] : '';
		$temp_current 	= isset($_POST['current_password']) ? $_POST['current_password'] : '';

		$_POST	= ee()->security->xss_clean( $_POST );

		if ($temp_pass != '')
		{
			$_POST['password'] = $temp_pass;
		}

		if ($temp_confirm != '')
		{
			$_POST['password_confirm'] = $temp_confirm;
		}

		if ($temp_current != '')
		{
			$_POST['current_password'] = $temp_current;
		}

		ee()->load->helper('string');

		if (isset($_POST['username']))
		{
			$_POST['username']		= trim_nbs($_POST['username']);
		}

		if (isset($_POST['screen_name']))
		{
			$_POST['screen_name']	= trim_nbs($_POST['screen_name']);
		}

		//	----------------------------------------
		//	 Remove an Image?
		//	----------------------------------------

		foreach(array('avatar', 'signature', 'photo') AS $type)
		{
			if (isset($_POST['remove_'.$type]))
			{
				$this->_remove_image($type, $member_id, $admin);
				return;
			}
		}

		//	----------------------------------------
		//	Check screen name override
		//	----------------------------------------

		$this->_screen_name_override($member_id);

		// --------------------------------------------
		//  Email as Username Preference
		// --------------------------------------------

		$wquery = ee()->db
						->select('preference_value')
						->where('preference_name', 'email_is_username')
						->get('user_preferences');

		$this->preferences['email_is_username'] = (
			$wquery->num_rows() == 0
		) ? 'n' : $wquery->row('preference_value');

		//	----------------------------------------
		//	Check email is username
		//	----------------------------------------

		$this->_email_is_username($member_id);

		//	----------------------------------------
		//	Is username banned?
		//	----------------------------------------

		if (ee()->input->post('username') AND
			ee()->session->ban_check(
				'username',
				ee()->input->post('username')
			)
		)
		{
			return $this->_output_error('general', array(lang('prohibited_username')));
		}

		if ($this->_param('exclude_username') != '' AND
			in_array(
				ee()->input->post('username'),
				explode('|', $this->_param('exclude_username'))
			)
		)
		{
			return $this->_output_error(
				'general',
				array(lang('prohibited_username'))
			);
		}

		//	----------------------------------------
		//	If the screen name field is absent,
		//	we'll stick to the current screen name
		//	----------------------------------------

		if (ee()->input->get_post('screen_name') === FALSE AND
			$member_id == ee()->session->userdata['member_id'])
		{
			$_POST['screen_name']	= ee()->session->userdata['screen_name'];
		}

		//	----------------------------------------
		//	If the screen name field is empty, we'll assign it from the username field.
		//	----------------------------------------

		elseif ( trim(ee()->input->get_post('screen_name')) == '' )
		{
			$_POST['screen_name']	= ee()->input->post('username');
		}

		//	----------------------------------------
		//	Prepare validation array.
		//	----------------------------------------

		$query = ee()->db
						->where('member_id', $member_id)
						->get('members');

		if ( $query->num_rows() == 0 )
		{
			return $this->_output_error('general', array(lang('cant_find_member')));
		}

		$validate	= array(
			'member_id'			=> $member_id,
			'val_type'			=> 'update', // new or update
			'fetch_lang' 		=> FALSE,
			'require_cpw' 		=> FALSE,
			'enable_log'		=> FALSE,
			'cur_username'		=> $query->row('username'),
			'screen_name'		=> stripslashes(ee()->input->post('screen_name')),
			'cur_screen_name'	=> $query->row('screen_name'),
			'cur_email'			=> $query->row('email')
		);

		if ( ee()->input->post('username') )
		{
			$validate['username']	= ee()->input->post('username');
		}

		if ( ee()->input->get_post('email') )
		{
			$validate['email']	= ee()->input->get_post('email');
		}

		if ( ee()->input->get_post('password') )
		{
			$validate['password']	= ee()->input->get_post('password');
		}

		if ( ee()->input->get_post('password_confirm') )
		{
			$validate['password_confirm']	= ee()->input->get_post('password_confirm');
		}

		if ( ee()->input->get_post('current_password') )
		{
			$validate['cur_password']	= ee()->input->get_post('current_password');
		}

		$old_username		= $query->row('username');

		// --------------------------------------------
		//  Brute Force Password Attack - Denied!
		// --------------------------------------------

		if (ee()->session->check_password_lockout() === TRUE)
		{
			$line = str_replace(
				"%x",
				ee()->config->item('password_lockout_interval'),
				lang('password_lockout_in_effect')
			);

			return $this->_output_error('submission', $line);
		}

		//	----------------------------------------
		//	Are we changing a password?
		//	----------------------------------------

		$changing_password	= FALSE;

		if (ee()->input->get_post('password') !== FALSE AND
			ee()->input->get_post('password') != '' AND
			ee()->input->get_post('current_password') !=
				ee()->input->get_post('password') AND
			$member_id == ee()->session->userdata['member_id'])
		{
			$changing_password	= TRUE;
		}

		//	----------------------------------------
		//	 Password Required for Form Submission?!
		//	----------------------------------------

		if ($admin === FALSE &&
			$this->check_yes($this->_param('password_required')))
		{
			if (ee()->session->check_password_lockout() === TRUE)
			{
				$line = str_replace(
					"%x",
					ee()->config->item('password_lockout_interval'),
					lang('password_lockout_in_effect')
				);
				return $this->_output_error('submission', $line);
			}

			if (ee()->input->get_post('current_password') === FALSE OR
				ee()->input->get_post('current_password') == '')
			{
				return $this->_output_error('general', array(lang('invalid_password')));
			}

			$passwd = ee()->auth->hash_password(
				ee()->input->get_post('current_password'),
				$query->row('salt')
			);

			if ( ! isset($passwd['salt']) OR ($passwd['password'] != $query->row('password')))
			{
				return $this->_output_error( 'general', array( lang( 'invalid_password' ) ) );
			}
		}

		// --------------------------------------------
		//	Password Check for when Username,
		//	Screen Name, Email, and Password are changed
		// --------------------------------------------

		if ($admin === FALSE)
		{
			$check = array('username', 'email');

			//allows the override of screen_name password protection
			if (! $this->check_no($this->_param('screen_name_password_required')))
			{
				$check[] = 'screen_name';
			}

			if (ee()->input->get_post('password') != '')
			{
				$check[] = 'password';
			}

			foreach($check as $val)
			{
				if (ee()->input->get_post($val) !== FALSE AND
					ee()->input->get_post($val) != $query->row($val))
				{
					if (ee()->input->get_post('current_password') === FALSE OR
						ee()->input->get_post('current_password') == '')
					{
						return $this->_output_error( 'general', array( lang( 'invalid_password' ) ) );
					}

					$passwd = ee()->auth->hash_password(
						ee()->input->get_post('current_password'),
						$query->row('salt')
					);

					if ( ! isset($passwd['salt']) OR
						($passwd['password'] != $query->row('password')))
					{
						ee()->session->save_password_lockout();

						if ($check == 'email')
						{
							return $this->_output_error(
								'general',
								array( lang( 'current_password_required_email' ) )
							);
						}
						else
						{
							return $this->_output_error(
								'general',
								array( lang( 'invalid_password' ) )
							);
						}
					}
				}
			}
		}

		//	----------------------------------------
		//	If we are changing the language
		//	preference, use caution
		//	----------------------------------------

		if ( ee()->input->post('language') !== FALSE AND
			 ee()->input->post('language') != '' )
		{
			$language = ee()->security->sanitize_filename(
				ee()->input->get_post('language')
			);

			if ( ! is_dir( $this->lang_dir.$language ) )
			{
				return $this->_output_error(
					'general',
					array(lang('incorrect_language'))
				);
			}
		}

		//	----------------------------------------
		//   Required Fields
		//	----------------------------------------

		if ( $this->_param('required') !== FALSE)
		{
			$this->_mfields();

			$missing	= array();

			$required	= preg_split( "/,|\|/", $this->_param('required') );

			foreach ( $required as $req )
			{
				if ( $req == 'all_required')
				{
					foreach ( $this->mfields as $key => $val )
					{
						if ( ! ee()->input->get_post($key) AND $val['required'] == 'y' )
						{
							$missing[]	= $this->mfields[$key]['label'];
						}
					}
				}
				elseif ( ! ee()->input->get_post($req) )
				{
					if (isset( $this->mfields[$req] ) )
					{
						$missing[]	= $this->mfields[$req]['label'];
					}
					elseif (in_array($req, $this->standard))
					{
						if (in_array($req, array('bday_d', 'bday_m', 'bday_y')))
						{
							$missing[]	= lang('mbr_birthday');
						}
						else if ($req == 'daylight_savings' AND
								version_compare($this->ee_version, '2.6.0', '<'))
						{
							$missing[] = lang('daylight_savings_time');
						}
						else if(in_array($req, array(
							'aol_im',
							'yahoo_im',
							'msn_im',
							'icq',
							'signature'
						)))
						{
							$missing[]	= lang($req);
						}
						else
						{
							$missing[]	= lang('mbr_'.$req);
						}
					}
				}
			}

			//	----------------------------------------
			//	Anything missing?
			//	----------------------------------------

			if ( count( $missing ) > 0 )
			{
				$missing	= implode( "</li><li>", $missing );

				$str		= str_replace( "%fields%", $missing, lang('missing_fields') );

				return $this->_output_error('general', $str);
			}
		}

		// --------------------------------------------
		//  Required Custom Member Fields?
		// --------------------------------------------

		$no_fields		 = '';

		foreach ( $this->_mfields() as $key => $val )
		{
			if ( $val['required'] == 'y' AND ! ee()->input->get_post($key) )
			{
				$no_fields	.=	"<li>".$val['label']."</li>";
			}
		}

		if ( $no_fields != '' )
		{
			return $this->_output_error(
				'general',
				str_replace("%s", $no_fields, lang('user_field_required'))
			);
		}

		//	----------------------------------------
		//	Validate submitted data
		//	----------------------------------------

		ee()->load->library('validate', $validate, 'validate');

		if ( ee()->input->post('screen_name') )
		{
			ee()->validate->validate_screen_name();
		}

		//	----------------------------------------
		//	Username
		//	----------------------------------------

		if ( isset( $_POST['username'] ) )
		{
			if ( ee()->input->post('username') != '' )
			{
				if ( ee()->input->post('username') != $query->row('username') )
				{
					if ( ee()->config->item('allow_username_change') == 'y' )
					{
						ee()->validate->validate_username();

						if ($this->preferences['email_is_username'] != 'n' AND
							($key = array_search(
								lang('username_password_too_long'),
								ee()->validate->errors
							)) !== FALSE)
						{
							if (strlen(ee()->validate->username) <= 50)
							{
								unset(ee()->validate->errors[$key]);
							}
							else
							{
								ee()->validate->errors[$key] = str_replace(
									'32',
									'50',
									ee()->validate->errors[$key]
								);
							}
						}
					}
					else
					{
						return $this->_output_error(
							'general',
							array(lang('username_change_not_allowed'))
						);
					}
				}
			}
			else
			{
				ee()->validate->errors[] = lang('missing_username');
			}
		}

		//	----------------------------------------
		//	Password
		//	----------------------------------------

		if (ee()->input->get_post('password') AND
			ee()->input->get_post('password') != '')
		{
			ee()->validate->validate_password();
		}

		//	----------------------------------------
		//	Email
		//	----------------------------------------

		if ( ee()->input->get_post('email') )
		{
			ee()->validate->validate_email();
		}

		// -------------------------------------------
		// 'user_edit_validate' hook.
		//  - Do something when a user edits their profile
		//  - Added $cfields for User 3.3.9
		// -------------------------------------------

		if (ee()->extensions->active_hook('user_edit_validate') === TRUE)
		{
			$errors = ee()->extensions->universal_call(
				'user_edit_insert_data',
				$member_id,
				$this->insert_data,
				$this
			);
			if (ee()->extensions->end_script === TRUE) return;

			if (is_array($errors) AND ! empty($errors))
			{
				ee()->validate->errors = array_merge(
					ee()->validate->errors,
					$errors
				);
			}
		}

		//	----------------------------------------
		//	Display errors if there are any
		//	----------------------------------------

		if (count(ee()->validate->errors) > 0)
		{
			return $this->_output_error(
				'submission',
				ee()->validate->errors
			);
		}

		// --------------------------------------------
		//  Test Image Uploads
		// --------------------------------------------

		$this->_upload_images(0, TRUE);

		//	----------------------------------------
		//	Check Form Hash
		//	----------------------------------------

		if ( ! $this->check_secure_forms() )
		{
			return $this->_output_error(
				'general',
				array(lang('not_authorized'))
			);
		}

		// ---------------------------------
		//	Fetch categories
		// ---------------------------------

		if (isset($_POST['category']) AND is_array($_POST['category']))
		{
			foreach ( $_POST['category'] as $cat_id )
			{
				$this->cat_parents[] = $cat_id;
			}

			if ( ee()->config->item('auto_assign_cat_parents') == 'y' )
			{
				$this->_fetch_category_parents( $_POST['category'] );
			}
		}

		unset($_POST['category']);

		ee()->db
				->where('member_id', $member_id)
				->delete('user_category_posts');

		foreach ( $this->cat_parents as $cat_id )
		{
			ee()->db->insert(
				'exp_user_category_posts',
				array(
					'member_id'	=> $member_id,
					'cat_id'	=> $cat_id
				)
			);
		}

		//	----------------------------------------
		//	Update "last post" forum info if needed
		//	----------------------------------------

		if ($query->row('screen_name') != $_POST['screen_name'] AND
			ee()->config->item('forum_is_installed') == "y" )
		{
			ee()->db->update(
				'forums',
				array('forum_last_post_author'		=> $_POST['screen_name']),
				array('forum_last_post_author_id'	=> $member_id)
			);
		}

		//	----------------------------------------
		//	Assign the query data
		//	----------------------------------------

		if ( ee()->input->post('screen_name') )
		{
			$this->insert_data['screen_name'] = ee()->input->post('screen_name');
		}

		if ( ee()->input->post('username') AND
			 ee()->config->item('allow_username_change') == 'y')
		{
			$this->insert_data['username'] = ee()->input->get_post('username');
		}

		//	----------------------------------------
		//	Was a password submitted?
		//	----------------------------------------

		if (ee()->input->get_post('password'))
		{
			ee()->auth->update_password(
				$member_id,
				stripslashes(ee()->input->get_post('password'))
			);
		}

		//	----------------------------------------
		//	Was an email submitted?
		//	----------------------------------------

		if ( ee()->input->get_post('email') )
		{
			$this->insert_data['email'] = ee()->input->get_post('email');
		}

		//	----------------------------------------
		//	Assemble standard fields
		//	----------------------------------------

		foreach ($this->standard as $field)
		{
			if (isset($_POST[ $field ]))
			{
				//we dont want to send blank strings to INT fields
				if (trim($_POST[$field]) === '' AND
					in_array($field, $this->int_fields))
				{
					continue;
				}

				$this->insert_data[$field]	= $_POST[ $field ];
			}
		}

		//	----------------------------------------
		//	Assemble checkbox fields
		//	----------------------------------------

		if ( $this->_param('checks') != '' )
		{
			foreach ( explode( "|", $this->_param('checks') )  as $c )
			{
				if ( in_array( $c, $this->check_boxes ) )
				{
					if ( ee()->input->post($c) !== FALSE )
					{
						if ( stristr( ee()->input->post($c), 'n' ) )
						{
							$this->insert_data[$c]	= 'n';
						}
						else
						{
							$this->insert_data[$c]	= 'y';
						}
					}
					else
					{
						$this->insert_data[$c]	= 'n';
					}
				}
			}
		}

		//	----------------------------------------
		//	If a super admin is editing, we will
		//	allow changes to member group.
		//	Otherwise we will unset group id
		//	right before updating just to be
		//	damn sure we don't get hacked.
		//	----------------------------------------*/

		if ( ee()->input->post('group_id') AND
			 ee()->input->post('group_id') != $query->row('group_id') AND
			 ee()->session->userdata('group_id') == '1' )
		{
			if ( ee()->session->userdata('member_id') == $member_id )
			{
				return $this->_output_error(
					'general',
					array(lang('super_admin_group_id'))
				);
			}

			$this->insert_data['group_id']	= ee()->db->escape_str(
				ee()->input->post('group_id')
			);
		}
		elseif(ee()->input->post('group_id') !== FALSE AND
			ctype_digit(ee()->input->post('group_id')) AND
			$this->_param('allowed_groups') !== FALSE)
		{
			$sql = "SELECT DISTINCT group_id
					FROM exp_member_groups
					WHERE group_id NOT IN (1,2,3,4)
					AND group_id = '".ee()->db->escape_str(
						ee()->input->post('group_id')
					)."' ".
					ee()->functions->sql_andor_string(
						$this->_param('allowed_groups'),
						'group_id'
					);

			$gquery = ee()->db->query($sql);

			if ($query->num_rows() > 0)
			{
				$this->insert_data['group_id'] = $gquery->row('group_id');
			}
			else
			{
				unset( $this->insert_data['group_id'] );
			}
		}
		//	HACK! This allows those with Admin permissions to change a member's group id.
		//  		Change this at your own risk...
		/*
		elseif (
			ee()->input->post('group_id') !== FALSE AND
			$this->_param('group_id') !== FALSE AND
			ee()->input->post('group_id') != $query->row('group_id') AND
			ee()->session->userdata('group_id') != '1' AND
			in_array(
				ee()->session->userdata('group_id'),
				preg_split(
					"/,|\|/",
					$this->_param('group_id'),
					-1,
					PREG_SPLIT_NO_EMPTY
				)
			) !== FALSE AND
			ctype_digit( ee()->input->post('group_id') ) )
		{
			$this->insert_data['group_id']	= ee()->input->post('group_id');
		}
		*/
		else
		{
			unset( $this->insert_data['group_id'] );
		}

		//	----------------------------------------
		//	Last activity
		//	----------------------------------------

		if ($member_id == ee()->session->userdata['member_id'])
		{
			$this->insert_data['last_activity'] = ee()->localize->now;
		}

		//	----------------------------------------
		//	Run standard insert
		//	----------------------------------------

		// -------------------------------------------
		// 'user_edit_insert_data' hook.
		//  - Do something when a user edits their profile
		//  - Added $cfields for User 3.3.9
		// -------------------------------------------

		if (ee()->extensions->active_hook('user_edit_insert_data') === TRUE)
		{
			$this->insert_data = ee()->extensions->universal_call(
				'user_edit_insert_data',
				$member_id,
				$this->insert_data,
				$this
			);
			if (ee()->extensions->end_script === TRUE) return;
		}

		if ( count( $this->insert_data ) > 0 )
		{
			ee()->db->update(
				'exp_members',
				$this->insert_data,
				array('member_id' => $member_id)
			);
		}

		//	----------------------------------------
		//	Assemble custom fields
		//	----------------------------------------

		$cfields		= array();
		$custom_checks	= array();

		if ( $this->_param( 'custom_checks' ) )
		{
			$custom_checks	= preg_split(
				"/,|\|/",
				$this->_param('custom_checks'),
				-1,
				PREG_SPLIT_NO_EMPTY
			);
		}

		foreach ($this->_mfields() as $key => $val )
		{
			//	----------------------------------------
			//	Handle fields
			//	----------------------------------------

			if (isset($_POST[$key]))
			{
				//	----------------------------------------
				//	Handle arrays
				//	----------------------------------------

				if (is_array( $_POST[ $key ] ) )
				{
					$cfields['m_field_id_'.$val['id']]	= implode("\n", $_POST[$key]);
				}
				else
				{
					$cfields['m_field_id_'.$val['id']]	= $_POST[ $key ];
				}
			}
			else
			{
				unset( $cfields['m_field_id_'.$val['id']] );
			}

			//	----------------------------------------
			//	Handle empty checkbox fields
			//	----------------------------------------

			foreach ( $custom_checks as $check )
			{
				if ( isset( $_POST[$check] ) OR $check != $key ) continue;

				$cfields['m_field_id_'.$val['id']]	= "";
			}
		}

		//	----------------------------------------
		//	Run custom fields insert
		//	----------------------------------------

		if ( count( $cfields ) > 0 )
		{
			ee()->db->update(
				'exp_member_data',
				$cfields,
				array('member_id' => $member_id)
			);
		}

		//	----------------------------------------
		//	Handle image uploads
		//	----------------------------------------

		$this->_upload_images($member_id);

		//	----------------------------------------
		//	Update comments if screen name has
		//	changed.
		//	----------------------------------------

		if ($query->row('screen_name') != $_POST['screen_name'] &&
			ee()->db->table_exists('exp_comments'))
		{
			ee()->db->update(
				'exp_comments',
				array('name' => $_POST['screen_name']),
				array('author_id' => $member_id)
			);
		}

		// --------------------------------------------
		//  Send Update Email of Peace and Love
		// --------------------------------------------

		if ($update_admin_email === TRUE AND
			trim($member_update_admin_notification_emails) != '')
		{
			$this->_member_update_email(
				$old_data,
				array_merge($query->row_array(), $this->insert_data, $cfields),
				$member_update_admin_notification_emails,
				$member_update_admin_notification_template
			);
		}

		// -------------------------------------------
		// 'user_edit_end' hook.
		//  - Do something when a user edits their profile
		//  - Added $cfields for User 2.1
		// -------------------------------------------

		if (ee()->extensions->active_hook('user_edit_end') === TRUE)
		{
			$edata = ee()->extensions->universal_call(
				'user_edit_end',
				$query->row('member_id'),
				$this->insert_data,
				$cfields
			);
			if (ee()->extensions->end_script === TRUE) return;
		}

		//	----------------------------------------
		//	 Override Return
		//	----------------------------------------

		if ( $this->_param('override_return') !== FALSE AND
			 $this->_param('override_return') != '' AND
			 $this->is_ajax_request() === FALSE)
		{
			ee()->functions->redirect($this->_param('override_return'));
			exit();
		}

		//	----------------------------------------
		//	Set return
		//	----------------------------------------

		if ( ee()->input->get_post('return') !== FALSE AND
			 ee()->input->get_post('return') != '' )
		{
			$return	= ee()->input->get_post('return');
		}
		elseif ( ee()->input->get_post('RET') !== FALSE AND
				 ee()->input->get_post('RET') != '' )
		{
			$return	= ee()->input->get_post('RET');
		}
		else
		{
			$return = ee()->config->item('site_url');
		}

		// -------------------------------------
		//	fix paths
		// -------------------------------------

		if (preg_match( "/".LD."\s*path=(.*?)".RD."/", $return, $match ) > 0 )
		{
			$return	= ee()->functions->create_url( $match['1'] );
		}
		elseif ( stristr( $return, "http://" ) === FALSE AND
				 stristr( $return, "https://" ) === FALSE )
		{
			$return	= ee()->functions->create_url($return);
		}

		//	----------------------------------------
		//	Prep for username change on return
		//	To keep it loose, but keep it tight.
		//	----------------------------------------

		if ( ee()->input->post('username') !== FALSE AND
			 $old_username != ee()->input->post('username') )
		{
			if (stristr( $return, "/user/" . $old_username))
			{
				$return	= str_replace(
					"/user/" . $old_username,
					"/user/" . ee()->input->get_post('username'),
					$return
				);
			}
		}
		else
		{
			$return	= str_replace(LD.'username'.RD, $old_username, $return);
		}

		//	----------------------------------------
		//	Password stuff
		//	----------------------------------------

		if ($changing_password)
		{
			// --------------------------------------------
			//  AJAX Response
			// --------------------------------------------

			if ($this->is_ajax_request())
			{
				$this->send_ajax_response(array(
					'success' => TRUE,
					'heading' => lang('user_successful_submission'),
					'message' => lang('password_changed'),
					'content' => lang('password_changed')
				));
				exit();
			}

			return ee()->output->show_message(array(
				'title'		=> lang('success'),
				'heading'	=> lang('success'),
				'link'		=> array( $return, lang('return') ),
				'content'	=> lang('password_changed')
			));
		}

		// --------------------------------------------
		//  AJAX Response
		// --------------------------------------------

		if ($this->is_ajax_request())
		{
			$this->send_ajax_response(array(
				'success' => TRUE,
				'heading' => lang('user_successful_submission'),
				'message' => lang('mbr_profile_has_been_updated'),
				'content' => lang('mbr_profile_has_been_updated')
			));
		}

		//	----------------------------------------
		//	Return
		//	----------------------------------------

		ee()->functions->redirect($this->_chars_decode($return));
		exit();
	}
	// END edit profile


	// --------------------------------------------------------------------

	/**
	 *	Category Parsing for a Tag
	 *
	 *	@access		private
	 *	@param		string
	 *	@param		string
	 *	@return		string
	 */

	private function _categories( $tagdata, $only_show_selected = '' )
	{
		/**	----------------------------------------
		/**	Parent id
		/**	----------------------------------------*/

		$parent_id	= '';

		if ( isset(ee()->TMPL)  AND
			 ee()->TMPL->fetch_param('parent_id') !== FALSE AND
			 ctype_digit( ee()->TMPL->fetch_param('parent_id') ) )
		{
			$parent_id	= ee()->TMPL->fetch_param('parent_id');
		}

		/**	----------------------------------------
		/**	 Parse the {category} User Pair
		/**	----------------------------------------*/

		if (count($this->cat_chunk) == 0 AND
			preg_match_all(
				"/".LD."categories(.*?)".RD."(.*?)".
					LD.preg_quote($this->t_slash, '/')."categories".RD."/s",
				$tagdata,
				$matches
			) )
		{
			for ($j = 0; $j < count($matches['0']); $j++)
			{
				$this->cat_chunk[$j] = array(
					'category_tagdata'	=> $matches['2'][$j],
					'params'			=> ee()->functions->assign_parameters($matches['1'][$j]),
					'category_block'	=> $matches['0'][$j]
				);

				foreach ( array(
					'category_header',
					'category_footer',
					'category_indent',
					'category_body',
					'category_selected',
					'category_group_header',
					'category_group_footer'
				) as $val )
				{
					if ( preg_match(
							"/".LD.$val.RD."(.*?)".
							LD.preg_quote($this->t_slash, '/').$val.RD."/s",
							$this->cat_chunk[$j]['category_tagdata'],
							$match
					) )
					{
						$this->cat_chunk[$j][$val]	= $match['1'];
					}
					else
					{
						$this->cat_chunk[$j][$val] = '';
					}
				}
			}


			$query = ee()->db->query(
				"SELECT field_id, field_name
				 FROM 	exp_category_fields"
			);

			foreach ($query->result_array() as $row)
			{
				$this->catfields[$row['field_name']] = $row['field_id'];
			}
		}

		/** --------------------------------------------
		/**  Process Categories
		/** --------------------------------------------*/

		if (count($this->cat_chunk) > 0)
		{
			foreach($this->cat_chunk as $cat_chunk)
			{
				// Reset
				$this->categories	= array();
				$this->used_cat		= array();
				$this->cat_params = array();

				foreach($cat_chunk as $var => $value)
				{
					if ( $var == 'params' AND is_array( $value ) === TRUE )
					{
						$this->cat_params	= $value;
					}

					$this->cat_formatting[$var] = $value;
				}

				if ( ! isset($this->cat_params['group_id']))
				{
					$this->cat_params['group_id'] = '';
				}

				/**	----------------------------------------
				/**	Prepare the tree
				/**	----------------------------------------*/

				$query = ee()->db->query(
					"SELECT preference_value
					 FROM 	exp_user_preferences
					 WHERE 	preference_name = 'category_groups'
					 LIMIT  1"
				);

				$category_groups = ($query->num_rows() == 0) ? '' : $query->row('preference_value');

				//--------------------------------------------
				//	adjust cat groups to site param
				//--------------------------------------------

				if ( ! empty($category_groups))
				{
					$site_groups = array();

					if (isset($cat_chunk['params']['site_id']))
					{
						$query = ee()->db->query(
							"SELECT group_id
							 FROM	exp_category_groups
							 WHERE	site_id
							 IN 	(".implode(",", explode("|", $cat_chunk['params']['site_id'])).")");
					}
					else
					{
						$query = ee()->db->query(
							"SELECT group_id
							 FROM	exp_category_groups
							 WHERE	site_id = '".ee()->db->escape_str(ee()->config->item('site_id'))."'");
					}

					foreach ($query->result_array() as $row)
					{
						$site_groups[] = $row['group_id'];
					}

					$category_groups = implode('|', array_intersect($site_groups, explode( "|", $category_groups )));
				}

				if ( ! empty($category_groups) )
				{

					foreach ( explode( "|", $category_groups ) as $group_id )
					{
						if ( $this->cat_params['group_id'] != '')
						{
							if (substr($this->cat_params['group_id'], 0, 4) != 'not ' AND
								! in_array( $group_id, explode( "|", $this->cat_params['group_id'] ) ))
							{
								continue;
							}
							elseif (substr($this->cat_params['group_id'], 0, 4) == 'not ' AND
									in_array( $group_id, explode( "|", substr($this->cat_params['group_id'], 4))))
							{
								continue;
							}
						}

						$this->categories[]	= $this->_category_group_vars(
							$group_id,
							$this->cat_formatting['category_group_header']
						);

						$this->category_tree( 'text', $group_id, $parent_id, $only_show_selected );

						$this->categories[]	= $this->_category_group_vars(
							$group_id,
							$this->cat_formatting['category_group_footer']
						);
					}
				}

				$r	= implode( "", $this->categories );

				if ( isset( $this->cat_params['backspace'] ) === TRUE AND
					 ctype_digit( $this->cat_params['backspace'] ) AND
					 $this->cat_params['backspace'] > 0
				 )
				{
					$r	= substr( $r, 0, -($this->cat_params['backspace']) );
				}

				$tagdata	= str_replace( $this->cat_formatting['category_block'], $r, $tagdata );
			}
		}

		return $tagdata;
	}

	/* END _categories() */


	// --------------------------------------------------------------------

	/**
	 *	Fetch Parents for an Array of Categories
	 *
	 *	@access		private
	 *	@param		array
	 *	@return		null	Puts the categories into the global category array
	 */

	private function _fetch_category_parents(array $cat_array = array())
	{
		if ( ! is_array($cat_array) OR count($cat_array) == 0)
		{
			return;
		}

		$sql = "SELECT parent_id FROM exp_categories WHERE cat_id != ''";

		if ( ee()->config->item('site_id') !== FALSE )
		{
			$sql	.= " AND site_id = '".ee()->db->escape_str(ee()->config->item('site_id'))."'";
		}

		$sql	.= " AND cat_id IN ('".implode("','", ee()->db->escape_str($cat_array))."')";

		$query = ee()->db->query($sql);

		if ($query->num_rows() == 0)
		{
			return;
		}

		$temp = array();

		foreach ($query->result_array() as $row)
		{
			if ($row['parent_id'] != 0)
			{
				$this->cat_parents[] = $row['parent_id'];

				$temp[] = $row['parent_id'];
			}
		}

		$this->_fetch_category_parents($temp);
	}

	/* END fetch parent category id */


	// --------------------------------------------------------------------

	/**
	 *	Process Category Tree
	 *
	 *	@access		public
	 *	@param		string
	 *	@param 		integer
	 *	@param		integer
	 *	@param		string
	 *	@return		null
	 */

	public function category_tree( $type = 'text', $group_id = '', $parent_id = '', $only_show_selected = '' )
	{
		/**	----------------------------------------
		/**  Fetch member's categories
		/**	----------------------------------------*/

		if ( $this->member_id != 0 AND count( $this->assigned_cats ) == 0 )
		{
			$catq	= ee()->db->query( "SELECT cat_id FROM exp_user_category_posts WHERE member_id = '".ee()->db->escape_str( $this->member_id )."'" );

			foreach ( $catq->result_array() as $row )
			{
				$this->assigned_cats[]	= $row['cat_id'];
			}
		}

		/**	----------------------------------------
		/**  Fetch categories
		/**	----------------------------------------*/

		$sql = "SELECT c.cat_name	 AS category_name,
					   c.cat_id		 AS category_id,
					   c.parent_id	 AS parent_id,
					   c.cat_image	 AS category_image,
					   c.cat_description AS category_description,
					   c.cat_url_title	 AS category_url_title,
					   p.cat_name	 AS parent_name,
					   cg.group_name AS category_group_name, cg.group_id AS category_group_id,
					   fd.*
				FROM exp_categories c
				LEFT JOIN exp_categories p ON p.cat_id = c.parent_id
				LEFT JOIN exp_category_groups cg ON cg.group_id = c.group_id
				LEFT JOIN exp_category_field_data AS fd ON fd.cat_id = c.cat_id
				WHERE c.cat_id != 0 ";

		if ( $group_id != '' AND ctype_digit( $group_id ) )
		{
			$sql	.= " AND c.group_id = '".ee()->db->escape_str( $group_id )."'";
		}

		if ( $parent_id != '' AND ctype_digit( $parent_id ) )
		{
			$sql	.= " AND c.parent_id = '".ee()->db->escape_str( $parent_id )."'";
		}

		if ( $only_show_selected == 'yes' )
		{
			$sql	.= " AND c.cat_id IN ('".implode( "','", $this->assigned_cats )."')";
		}

		/**	----------------------------------------
		/**  Establish sort order
		/**	----------------------------------------*/

		if ( count( $this->cat_params ) > 0 AND isset( $this->cat_params['orderby'] ) === TRUE AND $this->cat_params['orderby'] == 'category_order' )
		{
			$sql .= " ORDER BY c.parent_id, c.cat_order";
		}
		else
		{
			$sql .= " ORDER BY c.parent_id, c.cat_name";
		}

		if ( ! isset ($this->cache[md5($sql)]))
		{
			$query = ee()->db->query($sql);
			$this->cache[md5($sql)] = $query;
		}
		else
		{
			$query = $this->cache[md5($sql)];
		}

		if ($query->num_rows() == 0)
		{
			return FALSE;
		}

		/**	----------------------------------------
		/**  Assign cats to array
		/**	----------------------------------------*/

		foreach($query->result_array() as $row)
		{
			$cat_array[$row['category_id']]  = $row;
		}

		$this->categories[]	= $this->cat_formatting['category_header'];

		/**	----------------------------------------
		/**	Loop for each category
		/**	----------------------------------------
		/*	Listen, we try to construct a family
		/*	when we can, but if we're not
		/*	auto-assigning parent cats, forget it.
		/*	Just show a flat list.
		/**	----------------------------------------*/

		foreach($cat_array as $key => $val)
		{
			if ( in_array( $key, $this->used_cat ) === TRUE ) continue;

			$selected	= ( in_array( $key, $this->assigned_cats ) === TRUE ) ? $this->cat_formatting['category_selected']: '';

			$checked	= ( in_array( $key, $this->assigned_cats ) === TRUE ) ? $this->cat_formatting['category_selected']: '';

			$parent		= ( $this->search === TRUE ) ? $val['parent_name']: '';

			$cat_body	= $this->cat_formatting['category_body'];

			$cat_body	= str_replace( LD."selected".RD, $selected, $cat_body );

			$cat_body	= str_replace( LD."checked".RD, $checked, $cat_body );

			$data					= $val;
			$data['depth']			= 0;
			$data['indent']			= '';
			$data['category_id']	= $key;

			foreach($this->catfields as $name => $id)
			{
				if (isset($val['field_id_'.$id]))
				{
					$data[$name] = $val['field_id_'.$id];
				}
			}

			$cat_body = ee()->functions->prep_conditionals($cat_body, $data);

			foreach($data as $var_name => $var_value)
			{
				$cat_body = str_replace(LD.$var_name.RD, $var_value, $cat_body);
			}

			$this->categories[] = $cat_body;

			$this->used_cat[]	= $key;

			$this->category_subtree($key, $cat_array, $group_id, $depth=0, $type, $parent_id);
		}

		$this->categories[]	= $this->cat_formatting['category_footer'];
	}

	/* END category tree */

	// --------------------------------------------------------------------

	/**
	 *	Process the Subcategories for Our Category Tree
	 *
	 *	@access		public
	 *	@param		integer
	 *	@param		array
	 *	@param		integer
	 *	@param		integer
	 *	@param		string
	 *	@param		integer
	 *	@return		string
	 */

	public function category_subtree( $cat_id, $cat_array, $group_id, $depth = 0, $type, $parent_id = '' )
	{
		$depth	= ($depth == 0) ? 1: $depth + 1;

		$indent	= 15;

		$this->categories[]	= $this->cat_formatting['category_header'];

		$checked	= ( $this->selected === TRUE ) ? 'checked="checked"': '';

		$arr		= array();

		foreach ($cat_array as $key => $val)
		{
			if ( in_array( $key, $this->used_cat ) === TRUE ) continue;

			$selected	= ( in_array( $key, $this->assigned_cats ) === TRUE ) ? $this->cat_formatting['category_selected']: '';

			$checked	= ( in_array( $key, $this->assigned_cats ) === TRUE ) ? $this->cat_formatting['category_selected']: '';

			if ($cat_id == $val['parent_id'])
			{
				$cat_body	= $this->cat_formatting['category_body'];

				$cat_body	= str_replace( LD."selected".RD, $selected, $cat_body );

				$cat_body	= str_replace( LD."checked".RD, $checked, $cat_body );

				$data					= $val;
				$data['depth']			= $depth;
				$data['indent']			= str_repeat( $this->cat_formatting['category_indent'], $depth);
				$data['categoriy_id']	= $key;
				foreach($this->catfields as $name => $id)
				{
					if (isset($val['field_id_'.$id]))
					{
						$data[$name] = $val['field_id_'.$id];
					}
				}

				$cat_body = ee()->functions->prep_conditionals($cat_body, $data);

				foreach($data as $var_name => $var_value)
				{
					$cat_body = str_replace(LD.$var_name.RD, $var_value, $cat_body);
				}

				$this->categories[] = $cat_body;

				$this->used_cat[]	= $key;

				$this->category_subtree($key, $cat_array, $group_id, $depth, $type, $parent_id);
			}
		}

		$this->categories[]	= $this->cat_formatting['category_footer'];
	}

	/* END category subtree */


	// --------------------------------------------------------------------

	/**
	 *	Return Category Group Variables
	 *
	 *	@access		public
	 *	@param		integer
	 *	@param		string
	 *	@return		string
	 */

	public function _category_group_vars( $group_id = 0, $data = '' )
	{

		if ( $group_id == 0 OR $data == '' ) return FALSE;

		if ( isset( $this->cat_group_vars[$group_id] ) === TRUE )
		{
			$data   = ee()->functions->prep_conditionals(
				$data,
				array(
					'category_group_id' 	=> $group_id,
					'category_group_name' 	=> $this->cat_group_vars[$group_id]
				)
			);

			return str_replace(
				array(
					LD.'category_group_id'.RD,
					LD.'category_group_name'.RD
				),
				array(
					$group_id,
					$this->cat_group_vars[$group_id]
				),
				$data
			);
		}

		$sql	= "SELECT 	group_id, group_name
				   FROM 	exp_category_groups
				   WHERE 	group_id != 0 ";

		if ( $this->cat_params['group_id'] != '' )
		{
			$sql	.= ee()->functions->sql_andor_string( $this->cat_params['group_id'], 'group_id' );
		}

		$query	= ee()->db->query( $sql );

		if ( $query->num_rows() > 0 )
		{
			foreach ( $query->result_array() as $row )
			{
				$this->cat_group_vars[ $row['group_id'] ]	= $row['group_name'];
			}
		}

		if ( isset( $this->cat_group_vars[$group_id] ) )
		{
			$data   = ee()->functions->prep_conditionals(
				$data,
				array(
					'category_group_id' 	=> $group_id,
					'category_group_name' 	=> $this->cat_group_vars[$group_id]
				)
			);

			$data	= str_replace(
				array(
					LD.'category_group_id'.RD,
					LD.'category_group_name'.RD
				),
				array(
					$group_id,
					$this->cat_group_vars[$group_id]
				),
				$data
			);
		}

		return $data;
	}

	/* END category group vars */

	// --------------------------------------------------------------------

	/**
	 *	Groups
	 *
	 *	Allows Authorized Members to Edit Other Members in Batches
	 *
	 *	@access		public
	 *	@return		string
	 */

	public function groups()
	{
		/**	----------------------------------------
		/**	Validate the admin
		/**	----------------------------------------
		/*	We can authorize member groups to use
		/*	this form. Let's check to see if this
		/*	person can.
		/**	----------------------------------------*/

		if (
			ee()->session->userdata['group_id'] == 1 OR
			(
				ee()->TMPL->fetch_param('authorized_group') !== FALSE AND
				ctype_digit( ee()->TMPL->fetch_param('authorized_group') ) AND
				ee()->session->userdata['group_id'] == ee()->TMPL->fetch_param('authorized_group')
			 ) OR
			(
				ee()->TMPL->fetch_param('authorized_group') !== FALSE AND
				preg_split( "/,|\|/", ee()->TMPL->fetch_param('authorized_group'), -1, PREG_SPLIT_NO_EMPTY ) !== FALSE AND
				in_array( ee()->session->userdata['group_id'], preg_split( "/,|\|/", ee()->TMPL->fetch_param('authorized_group'), -1, PREG_SPLIT_NO_EMPTY ))
			)
		)
		{
		}
		else
		{
			return $this->no_results('user');
		}

		/**	----------------------------------------
		/**	Editable groups
		/**	----------------------------------------
		/*	We have a safeguard that requires that
		/*	the editable groups be supplied by the
		/*	creator of the template using this
		/*	function. So we need to validate that.
		/**	----------------------------------------*/

		$all			= FALSE;
		$editable_group	= array();

		if ( ee()->TMPL->fetch_param('editable_group') !== FALSE AND ee()->TMPL->fetch_param('editable_group') != '' )
		{
			if ( ee()->TMPL->fetch_param('editable_group') == 'all' )
			{
				$all	= TRUE;
			}
			else
			{
				$editable_group		= preg_split( "/,|\|/", ee()->TMPL->fetch_param('editable_group'), -1, PREG_SPLIT_NO_EMPTY );
			}

			if ( $all === FALSE AND ( count( $editable_group ) == 0 ) )
			{
				return $this->no_results('user');
			}
		}
		else
		{
			return $this->no_results('user');
		}

		/**	----------------------------------------
		/**	Grab member data
		/**	----------------------------------------*/

		$sql	= "SELECT md.*, m.email, m.group_id, m.member_id, m.screen_name, m.username";

		$arr	= array_merge( $this->standard, $this->check_boxes, $this->photo, $this->avatar, $this->signature );

		foreach ( $arr as $a )
		{
			$sql	.= ", m.".$a;
		}

		$sql	.= " FROM exp_members m LEFT JOIN exp_member_data md ON m.member_id = md.member_id WHERE m.group_id != '1'";

		if ( $all === FALSE )
		{
			$sql	.= " AND m.group_id IN (";

			foreach ( $editable_group as $group )
			{
				$sql	.= "'".$group."',";
			}

			$sql	= rtrim( $sql, "," );

			$sql	.= ")";
		}

		$query	= ee()->db->query( $sql );

		if ( $query->num_rows() == 0 )
		{
			return $this->no_results('user');
		}

		/**	----------------------------------------
		/**	Userdata
		/**	----------------------------------------*/

		$tagdata	= ee()->TMPL->tagdata;

		/**	----------------------------------------
		/**	Sniff for checkboxes
		/**	----------------------------------------*/

		$checks			= '';
		$custom_checks	= '';

		if ( preg_match_all( "/name=['|\"]?(\w+)['|\"]?/", $tagdata, $match ) )
		{
			$this->_mfields();

			foreach ( $match['1'] as $m )
			{
				if ( in_array( $m, $this->check_boxes ) )
				{
					$checks	.= $m."|";
				}

				if ( isset( $this->mfields[ $m ] ) AND $this->mfields[ $m ]['type'] == 'select' )
				{
					$custom_checks	.= $m."|";
				}
			}
		}

		/**	----------------------------------------
		/**	Sniff for fields of type 'file'
		/**	----------------------------------------*/

		if ( preg_match( "/type=['|\"]?file['|\"]?/", $tagdata, $match ) )
		{
			$this->multipart	= TRUE;
		}

		/**	----------------------------------------
		/**	Prep additional values
		/**	----------------------------------------*/

		$photo_url		= ee()->config->slash_item('photo_url');
		$avatar_url		= ee()->config->slash_item('avatar_url');
		$sig_img_url	= ee()->config->slash_item('sig_img_url');

		/**	----------------------------------------
		/**	Are we in 'all' mode?
		/**	----------------------------------------
		/*	This function can loop through all members or it can loop through members
		/*	per group. If we are doing all, then that's it, no group level looping.
		/**	----------------------------------------*/

		if ( $all === TRUE AND preg_match( "/".LD."group".RD."(.*?)".LD.preg_quote($this->t_slash, '/')."group".RD."/s", $tagdata, $match ) > 0 )
		{
			$output	= '';

			foreach ( $query->result_array() as $row )
			{
				$tdata	= $match['1'];

				/**	----------------------------------------
				/**	Additionals
				/**	----------------------------------------*/

				$row['photo_url']	= $photo_url;
				$row['avatar_url']	= $avatar_url;
				$row['sig_img_url']	= $sig_img_url;

				/**	----------------------------------------
				/**	Conditionals
				/**	----------------------------------------*/

				$cond	= $row;

				$tdata	= ee()->functions->prep_conditionals( $tdata, $cond );

				$tzone = isset($row['timezone']) ? $row['timezone'] : 'UTC';

				$tdata = $this->parse_timezone_menu_tag($tdata, $tzone);

				/**	----------------------------------------
				/**	Parse var pairs
				/**	----------------------------------------*/

				foreach ( ee()->TMPL->var_pair as $key => $val )
				{
					/**	----------------------------------------
					/**	Timezones
					/**	----------------------------------------*/

					if ( $key == 'timezones' )
					{
						preg_match( "/".LD.$key.RD."(.*?)".LD.preg_quote($this->t_slash, '/').$key.RD."/s", $tdata, $m );
						$r	= '';



						foreach ( $this->timezones() as $key => $val )
						{
							$out		= $m['1'];

							$checked	= ( isset( $row['timezone'] ) AND $row['timezone'] == $key ) ? 'checked="checked"': '';

							$selected	= ( isset( $row['timezone'] ) AND $row['timezone'] == $key ) ? 'selected="selected"': '';

							$out		= str_replace( LD."zone_name".RD, $key, $out );
							$out		= str_replace( LD."zone_label".RD, lang( $key ), $out );
							$out		= str_replace( LD."checked".RD, $checked, $out );
							$out		= str_replace( LD."selected".RD, $selected, $out );

							$r	.= $out;
						}

						$tdata	= str_replace( $m['0'], $r, $tdata );
					}

					/**	----------------------------------------
					/**	Time format
					/**	----------------------------------------*/

					if ( $key == 'time_formats' )
					{
						preg_match( "/".LD.$key.RD."(.*?)".LD.preg_quote($this->t_slash, '/').$key.RD."/s", $tdata, $m );
						$r	= '';

						foreach ( array( 'us', 'eu' ) as $key )
						{
							$out		= $m['1'];

							$checked	= ( isset( $row['time_format'] ) AND $row['time_format'] == $key ) ? 'checked="checked"': '';

							$selected	= ( isset( $row['time_format'] ) AND $row['time_format'] == $key ) ? 'selected="selected"': '';

							$out		= str_replace( LD."time_format_name".RD, $key, $out );
							$out		= str_replace( LD."time_format_label".RD, lang( $key ), $out );
							$out		= str_replace( LD."checked".RD, $checked, $out );
							$out		= str_replace( LD."selected".RD, $selected, $out );

							$r	.= $out;
						}

						$tdata	= str_replace( $m['0'], $r, $tdata );
					}

					/**	----------------------------------------
					/**	Languages
					/**	----------------------------------------*/

					if ( $key == 'languages' )
					{
						$dirs = array();

						if ($fp = @opendir($this->lang_dir))
						{
							while (FALSE !== ($file = readdir($fp)))
							{
								if (is_dir($this->lang_dir.$file) AND substr($file, 0, 1) != ".")
								{
									$dirs[] = $file;
								}
							}
							closedir($fp);
						}

						sort($dirs);

						preg_match( "/".LD.$key.RD."(.*?)".LD.preg_quote($this->t_slash, '/').$key.RD."/s", $tdata, $m );
						$r	= '';

						foreach ( $dirs as $key )
						{
							$out		= $m['1'];

							$checked	= ( isset( $row['language'] ) AND $row['language'] == $key ) ? 'checked="checked"': '';

							$selected	= ( isset( $row['language'] ) AND $row['language'] == $key ) ? 'selected="selected"': '';

							$out		= str_replace( LD."language_name".RD, $key, $out );
							$out		= str_replace( LD."language_label".RD, ucfirst( $key ), $out );
							$out		= str_replace( LD."checked".RD, $checked, $out );
							$out		= str_replace( LD."selected".RD, $selected, $out );

							$r	.= $out;
						}

						$tdata	= str_replace( $m['0'], $r, $tdata );
					}
				}

				/**	----------------------------------------
				/**	Parse primary variables
				/**	----------------------------------------*/

				foreach ( $row as $key => $val )
				{
					$tdata	= ee()->TMPL->swap_var_single( $key, $val, $tdata );
				}

				/**	----------------------------------------
				/**	Parse custom variables
				/**	----------------------------------------*/

				foreach ( $this->_mfields() as $key => $val )
				{
					/**	----------------------------------------
					/**	Parse select
					/**	----------------------------------------*/

					foreach ( ee()->TMPL->var_pair as $k => $v )
					{
						if ( $k == "select_".$key )
						{
							$data		= ee()->TMPL->fetch_data_between_var_pairs( $tdata, $k );

							$tdata	= preg_replace( "/".LD.preg_quote($k,'/').RD."(.*?)".LD.preg_quote($this->t_slash, '/').preg_quote($k, '/').RD."/s",
													str_replace('$', '\$', $this->_parse_select( $key, $row, $data )),
													$tdata );
						}
					}

					/**	----------------------------------------
					/**	Parse singles
					/**	----------------------------------------*/

					$tdata	= ee()->TMPL->swap_var_single( $key, $row['m_field_id_'.$val['id']], $tdata );
				}

				$output	.= $tdata;
			}

			$tagdata	= str_replace( $match[0], $output, $tagdata );
		}

		/**	----------------------------------------
		/**	We're in group mode
		/**	----------------------------------------*/

		else
		{
			/**	----------------------------------------
			/**	Let's create an array of members by
			/**	group
			/**	----------------------------------------*/

			$members	= array();

			foreach ( $query->result_array() as $row )
			{
				$members[$row['group_id']][$row['member_id']]	= $row;
			}

			/**	----------------------------------------
			/**	Let's loop for each group and parse
			/**	----------------------------------------*/

			foreach ( $members as $group => $member )
			{
				if ( preg_match( "/".LD."group_".$group.RD."(.*?)".LD.preg_quote($this->t_slash, '/')."group_".$group.RD."/s", $tagdata, $match ) > 0 )
				{
					$output	= '';

					foreach ( $member as $row )
					{
						$tdata	= $match['1'];

						/**	----------------------------------------
						/**	Additionals
						/**	----------------------------------------*/

						$row['photo_url']	= $photo_url;
						$row['avatar_url']	= $avatar_url;
						$row['sig_img_url']	= $sig_img_url;

						/**	----------------------------------------
						/**	Conditionals
						/**	----------------------------------------*/

						$cond		= $row;

						$tdata	= ee()->functions->prep_conditionals( $tdata, $cond );

						/**	----------------------------------------
						/**	Parse var pairs
						/**	----------------------------------------*/

						ee()->load->helper('date');

						foreach ( ee()->TMPL->var_pair as $key => $val )
						{
							/**	----------------------------------------
							/**	Timezones
							/**	----------------------------------------*/

							if ( $key == 'timezones' )
							{
								preg_match( "/".LD.$key.RD."(.*?)".LD.preg_quote($this->t_slash, '/').$key.RD."/s", $tdata, $m );
								$r	= '';

								foreach ( $this->timezones() as $key => $val )
								{
									$out		= $m['1'];

									$checked	= ( isset( $row['timezone'] ) AND $row['timezone'] == $key ) ? 'checked="checked"': '';

									$selected	= ( isset( $row['timezone'] ) AND $row['timezone'] == $key ) ? 'selected="selected"': '';

									$out		= str_replace( LD."zone_name".RD, $key, $out );
									$out		= str_replace( LD."zone_label".RD, lang( $key ), $out );
									$out		= str_replace( LD."checked".RD, $checked, $out );
									$out		= str_replace( LD."selected".RD, $selected, $out );

									$r	.= $out;
								}

								$tdata	= str_replace( $m['0'], $r, $tdata );
							}

							/**	----------------------------------------
							/**	Time format
							/**	----------------------------------------*/

							if ( $key == 'time_formats' )
							{
								preg_match( "/".LD.$key.RD."(.*?)".LD.preg_quote($this->t_slash, '/').$key.RD."/s", $tdata, $m );
								$r	= '';

								foreach ( array( 'us', 'eu' ) as $key )
								{
									$out		= $m['1'];

									$checked	= ( isset( $row['time_format'] ) AND $row['time_format'] == $key ) ? 'checked="checked"': '';

									$selected	= ( isset( $row['time_format'] ) AND $row['time_format'] == $key ) ? 'selected="selected"': '';

									$out		= str_replace( LD."time_format_name".RD, $key, $out );
									$out		= str_replace( LD."time_format_label".RD, lang( $key ), $out );
									$out		= str_replace( LD."checked".RD, $checked, $out );
									$out		= str_replace( LD."selected".RD, $selected, $out );

									$r	.= $out;
								}

								$tdata	= str_replace( $m['0'], $r, $tdata );
							}

							/**	----------------------------------------
							/**	Languages
							/**	----------------------------------------*/

							if ( $key == 'languages' )
							{
								$dirs = array();

								if ($fp = @opendir($this->lang_dir))
								{
									while (FALSE !== ($file = readdir($fp)))
									{
										if (is_dir($this->lang_dir.$file) AND substr($file, 0, 1) != ".")
										{
											$dirs[] = $file;
										}
									}
									closedir($fp);
								}

								sort($dirs);

								preg_match( "/".LD.$key.RD."(.*?)".LD.preg_quote($this->t_slash, '/').$key.RD."/s", $tdata, $m );
								$r	= '';

								foreach ( $dirs as $key )
								{
									$out		= $m['1'];

									$checked	= ( isset( $row['language'] ) AND $row['language'] == $key ) ? 'checked="checked"': '';

									$selected	= ( isset( $row['language'] ) AND $row['language'] == $key ) ? 'selected="selected"': '';

									$out		= str_replace( LD."language_name".RD, $key, $out );
									$out		= str_replace( LD."language_label".RD, ucfirst( $key ), $out );
									$out		= str_replace( LD."checked".RD, $checked, $out );
									$out		= str_replace( LD."selected".RD, $selected, $out );

									$r	.= $out;
								}

								$tdata	= str_replace( $m['0'], $r, $tdata );
							}
						}

						/**	----------------------------------------
						/**	Parse primary variables
						/**	----------------------------------------*/

						foreach ( $row as $key => $val )
						{
							$tdata	= ee()->TMPL->swap_var_single( $key, $val, $tdata );
						}

						/**	----------------------------------------
						/**	Parse custom variables
						/**	----------------------------------------*/

						foreach ( $this->_mfields() as $key => $val )
						{
							/**	----------------------------------------
							/**	Parse select
							/**	----------------------------------------*/

							foreach ( ee()->TMPL->var_pair as $k => $v )
							{
								if ( $k == "select_".$key )
								{
									$data		= ee()->TMPL->fetch_data_between_var_pairs( $tdata, $k );

									$tdata	= preg_replace( "/".LD.preg_quote($k, '/').RD."(.*?)".LD.preg_quote($this->t_slash, '/').preg_quote($k, '/').RD."/s",
															str_replace('$', '\$', $this->_parse_select( $key, $row, $data )),
															$tdata );
								}
							}

							/**	----------------------------------------
							/**	Parse singles
							/**	----------------------------------------*/

							$tdata	= ee()->TMPL->swap_var_single( $key, $row['m_field_id_'.$val['id']], $tdata );
						}

						$output	.= $tdata;
					}

					$tagdata	= str_replace( $match[0], $output, $tagdata );
				}
			}
		}


		/**	----------------------------------------
		/**	Prep data
		/**	----------------------------------------*/

		$this->form_data['tagdata']					= $tagdata;

		$this->form_data['ACT']						= ee()->functions->fetch_action_id('User', 'group_edit');

		$this->form_data['RET']						= (isset($_POST['RET'])) ? $_POST['RET'] : ee()->functions->fetch_current_uri();

		if ( ee()->TMPL->fetch_param('form_name') !== FALSE AND ee()->TMPL->fetch_param('form_name') != '' )
		{
			$this->form_data['name']	= ee()->TMPL->fetch_param('form_name');
		}

		$this->form_data['id']						= ( ee()->TMPL->fetch_param('form_id') !== FALSE ) ? ee()->TMPL->fetch_param('form_id'): 'member_form';

		$this->form_data['return']					= ( ee()->TMPL->fetch_param('return') !== FALSE ) ? ee()->TMPL->fetch_param('return'): '';

		$this->params['checks']					= $checks;

		$this->params['custom_checks']			= $custom_checks;

		$this->params['required']				= ( ee()->TMPL->fetch_param('required') !== FALSE ) ? ee()->TMPL->fetch_param('required'): '';

		$this->params['authorized_group']		= ( ee()->TMPL->fetch_param('authorized_group') !== FALSE ) ? ee()->TMPL->fetch_param('authorized_group'): '';

		$this->params['editable_group']			= ( ee()->TMPL->fetch_param('editable_group') !== FALSE ) ? ee()->TMPL->fetch_param('editable_group'): '';

		$this->params['secure_action']			= ( ee()->TMPL->fetch_param('secure_action') !== FALSE) ? ee()->TMPL->fetch_param('secure_action'): 'no';

		/**	----------------------------------------
		/**	Return
		/**	----------------------------------------*/

		return $this->_form();
	}

	/* END groups */

	// --------------------------------------------------------------------

	/**
	 *	Group Edit
	 *
	 *	Edit members in a batch
	 *
	 *	@access		public
	 *	@return		string
	 */

	public function group_edit()
	{
		/**	----------------------------------------
		/**	Logged in?
		/**	----------------------------------------*/

		if ( ee()->session->userdata('member_id') == 0 )
		{
			return $this->_output_error('general', array(lang('not_authorized')));
		}

		/** ----------------------------------------
		/**  Is the user banned?
		/** ----------------------------------------*/

		if (ee()->session->userdata['is_banned'] == TRUE)
		{
			return $this->_output_error('general', array(lang('not_authorized')));
		}

		/** ----------------------------------------
		/**  Is the IP address and User Agent required?
		/** ----------------------------------------*/

		if (ee()->config->item('require_ip_for_posting') == 'y')
		{
			if (ee()->input->ip_address() == '0.0.0.0' OR ee()->session->userdata['user_agent'] == "")
			{
				return $this->_output_error('general', array(lang('not_authorized')));
			}
		}

		/** ----------------------------------------
		/**  Is the nation of the user banned?
		/** ----------------------------------------*/

		ee()->session->nation_ban_check();

		/** ----------------------------------------
		/**  Blacklist/Whitelist Check
		/** ----------------------------------------*/

		if (ee()->blacklist->blacklisted == 'y' AND ee()->blacklist->whitelisted == 'n')
		{
			return $this->_output_error('general', array(lang('not_authorized')));
		}

		/**	----------------------------------------
		/**	Validate the admin
		/**	----------------------------------------
		/*	We can authorize member groups to use
		/*	this function. Let's check to see if
		/*	this person can.
		/**	----------------------------------------*/

		if (
			ee()->session->userdata['group_id'] == 1 OR
			(
				$this->_param('authorized_group') !== FALSE AND
				ctype_digit( $this->_param('authorized_group') ) AND
				ee()->session->userdata['group_id'] == $this->_param('authorized_group')
			 ) OR
			(
				$this->_param('authorized_group') !== FALSE AND
				preg_split( "/,|\|/", $this->_param('authorized_group'), -1, PREG_SPLIT_NO_EMPTY ) !== FALSE AND
				in_array( ee()->session->userdata['group_id'], preg_split( "/,|\|/", $this->_param('authorized_group'), -1, PREG_SPLIT_NO_EMPTY ))
			)
		)
		{
		}
		else
		{
			return $this->_output_error('general', array(lang('not_authorized')));
		}

		/**	----------------------------------------
		/**	Editable groups
		/**	----------------------------------------
		/*	We have a safeguard that requires that
		/*	the editable groups be supplied by the
		/*	creator of the template using this
		/*	function. So we need to validate that.
		/**	----------------------------------------*/

		$all			= FALSE;
		$editable_group	= array();

		if ( $this->_param('editable_group') !== FALSE AND $this->_param('editable_group') != '' )
		{
			if ( $this->_param('editable_group') == 'all' )
			{
				$all	= TRUE;
			}
			else
			{
				$editable_group		= preg_split( "/,|\|/", $this->_param('editable_group'), -1, PREG_SPLIT_NO_EMPTY );
			}

			if ( $all === FALSE AND ( count( $editable_group ) == 0 OR ctype_digit( $editable_group ) === FALSE ) )
			{
				return $this->_output_error('general', array(lang('incorrect_editable_groups')));
			}
		}
		else
		{
			return $this->_output_error('general', array(lang('incorrect_editable_groups')));
		}

		/**	----------------------------------------
		/**	Assemble array from post
		/**	----------------------------------------
		/*	We're doing a group thing, so we only
		/*	care about info coming through a POST
		/*	array
		/**	----------------------------------------*/

		if ( isset( $_POST ) === FALSE )
		{
			return $this->_output_error('general', array(lang('no_data')));
		}

		$members	= array();

		$this->_mfields();

		foreach ( $_POST as $key => $val )
		{
			/**	----------------------------------------
			/**	If we're not dealing with an array we
			/**	skip
			/**	----------------------------------------*/

			if ( is_array( $val ) === TRUE )
			{
				/**	----------------------------------------
				/**	Let's only allow things we care about
				/**	into the process
				/**	----------------------------------------*/

				//	Standard fields

				if ( in_array( $key, $this->standard ) === TRUE OR in_array( $key, array('group_id') ) === TRUE OR isset( $this->mfields[$key] ) === TRUE )
				{
					/**	----------------------------------------
					/**	Load members array
					/**	----------------------------------------
					/*	We're going to check later, but right
					/*	now we assume that for each of our
					/*	arrays, the key is the member id and
					/*	the value is some value we're going to]
					/*	set.
					/**	----------------------------------------*/

					foreach ( $val as $k => $v )
					{
						if ( ctype_digit( $k ) )
						{
							$members[$k][$key]	= ee()->security->xss_clean( $v );
						}
					}
				}
			}
		}

		/**	----------------------------------------
		/**	Any members?
		/**	----------------------------------------*/

		if ( count( $members ) == 0 )
		{
			return $this->_output_error('general', array(lang('member_list_error')));
		}

		/**	----------------------------------------
		/**	Check the members against the DB
		/**	----------------------------------------*/

		$sql	= "SELECT member_id, group_id FROM exp_members WHERE group_id != '1'";

		if ( $all === FALSE )
		{
			$sql	.= " AND group_id IN (";

			foreach ( $editable_group as $group )
			{
				$sql	.= "'".ee()->db->escape_str( $group )."',";
			}

			$sql	= rtrim( $sql, "," );

			$sql	.= ")";
		}

		$sql	.= " AND member_id IN (";

		foreach ( array_keys( $members ) as $member )
		{
			$sql	.= "'".ee()->db->escape_str( $member )."',";
		}

		$sql	= rtrim( $sql, "," );

		$sql	.= ")";

		$query	= ee()->db->query( $sql );

		/**	----------------------------------------
		/**	Validate
		/**	----------------------------------------*/

		if ( $query->num_rows() == 0 OR $query->num_rows() != count( $members ) )
		{
			return $this->_output_error('general', array(lang('member_list_error')));
		}

		//	----------------------------------------
		//	Check Form Hash
		//	----------------------------------------

		if ( ! $this->check_secure_forms())
		{
			return $this->_output_error(
				'general',
				array(lang('not_authorized'))
			);
		}

		//	----------------------------------------
		//	Loop and update
		//	----------------------------------------*/

		foreach ( $query->result_array() as $row )
		{
			$data	= array();

			/**	----------------------------------------
			/**	Modify group id?
			/**	----------------------------------------*/

			if ( isset( $members[$row['member_id']]['group_id'] ) === TRUE AND $members[$row['member_id']]['group_id'] != $row['group_id'] AND in_array( $members[$row['member_id']]['group_id'], $editable_group ) === TRUE )
			{
				$data['group_id']	= $members[$row['member_id']]['group_id'];
			}

			/**	----------------------------------------
			/**	Modify standard field?
			/**	----------------------------------------*/

			foreach ( $members[$row['member_id']] as $key => $val )
			{
				if ( in_array( $key, $this->standard ) )
				{
					$data[$key]	= $val;
				}
			}

			/**	----------------------------------------
			/**	Update DB
			/**	----------------------------------------*/

			if ( ! empty( $data ) )
			{
				ee()->db->query( ee()->db->update_string( 'exp_members', $data, array( 'member_id' => $row['member_id'] ) ) );
			}

			/**	----------------------------------------
			/**	Modify custom field?
			/**	----------------------------------------*/

			$cfields	= array();

			foreach ( $members[$row['member_id']] as $key => $val )
			{
				if ( isset( $this->mfields[$key] ) === TRUE )
				{
					$cfields['m_field_id_' . $this->mfields[$key]['id']]	= $val;
				}
			}

			/**	----------------------------------------
			/**	Update DB
			/**	----------------------------------------*/

			if ( ! empty( $cfields ) )
			{
				ee()->db->query( ee()->db->update_string( 'exp_member_data', $cfields, array( 'member_id' => $row['member_id'] ) ) );
			}

			/* -------------------------------------------
			/* 'user_group_edit_end' hook.
			/*  - Do something when a user edits other user's profiles in batch
			*/
			if (ee()->extensions->active_hook('user_group_edit_end') === TRUE)
			{
				$edata = ee()->extensions->universal_call(
					'user_group_edit_end',
					$row['member_id'],
					$data,
					$cfields,
					$query->result_array()
				);
				if (ee()->extensions->end_script === TRUE) return;
			}
			/*
			/* -------------------------------------------*/
		}

		/**	----------------------------------------
		/**	 Override Return
		/**	----------------------------------------*/

		if ( $this->_param('override_return') !== FALSE AND $this->_param('override_return') != '' &&
			$this->is_ajax_request() === FALSE)
		{
			ee()->functions->redirect( $this->_param('override_return') );
			exit;
		}

		/**	----------------------------------------
		/**	Set return
		/**	----------------------------------------*/

		if ( ee()->input->get_post('return') !== FALSE AND ee()->input->get_post('return') != '' )
		{
			$return	= ee()->input->get_post('return');
		}
		elseif ( ee()->input->get_post('RET') !== FALSE AND ee()->input->get_post('RET') != '' )
		{
			$return	= ee()->input->get_post('RET');
		}
		else
		{
			$return = ee()->config->item('site_url');
		}

		if ( preg_match( "/".LD."\s*path=(.*?)".RD."/", $return, $match ) )
		{
			$return	= ee()->functions->create_url( $match['1'] );
		}
		elseif ( stristr( $return, 'http://' ) === FALSE AND
				 stristr( $return, 'https://' ) === FALSE )
		{
			$return	= ee()->functions->create_url( $return );
		}

		// --------------------------------------------
		//  AJAX Response
		// --------------------------------------------

		if ($this->is_ajax_request())
		{
			$this->send_ajax_response(array('success' => TRUE,
											'heading' => lang('user_successful_submission'),
											'message' => lang('member_group_updated'),
											'content' => lang('member_group_updated')));
		}

		/**	----------------------------------------
		/**	Return
		/**	----------------------------------------*/

		$return	= $this->_chars_decode( $return );

		ee()->functions->redirect( $return );
	}

	/* END group edit */

	// --------------------------------------------------------------------

	/**
	 *	The Register Form
	 *
	 *	@access		public
	 *	@return		string
	 */

	public function register()
	{
		/**	----------------------------------------
		/**	Allow registration?
		/**	----------------------------------------*/

		if ( ee()->config->item('allow_member_registration') != 'y' )
		{
			return $this->_output_error('general', array(lang('registration_not_enabled')));
		}

		/**	----------------------------------------
		/**	Is the current user logged in?
		/**	----------------------------------------*/

		if ( ee()->session->userdata('member_id') != 0 AND
			 ee()->TMPL->fetch_param('admin_register') !== 'yes')
		{
			if (ee()->TMPL->fetch_param('admin_register') !== 'yes' OR
			   (ee()->session->userdata['group_id'] != 1 AND
				ee()->session->userdata['can_admin_members'] !== 'y'))
			{
				// In case the registration form is on a page with other content, we don't want to
				// seize control and output an error.
				return $this->no_results('user');
				//return $this->_output_error('general', array(lang('mbr_you_are_registered')));
			}
		}

		/**	----------------------------------------
		/**	Userdata
		/**	----------------------------------------*/

		$tagdata			= ee()->TMPL->tagdata;

		/**	----------------------------------------
		/**	Grab key from url
		/**	----------------------------------------*/

		if ( preg_match( "#/".self::$key_trigger."/(\w+)/?#", ee()->uri->uri_string, $match ) )
		{
			$tagdata		= ee()->TMPL->swap_var_single( 'key', $match['1'], $tagdata );
		}
		else
		{
			$tagdata		= ee()->TMPL->swap_var_single( 'key', '', $tagdata );
		}

		/**	----------------------------------------
		/**	Handle categories
		/**	----------------------------------------*/

		$tagdata	= $this->_categories( $tagdata );

		/**	----------------------------------------
		/**	 Parse conditional pairs
		/**	----------------------------------------*/

		$cond['captcha'] = FALSE;

		if (ee()->config->item('use_membership_captcha') == 'y')
		{
			$cond['captcha'] =  (ee()->config->item('captcha_require_members') == 'y'  OR
								(ee()->config->item('captcha_require_members') == 'n' AND ee()->session->userdata('member_id') == 0)) ? 'TRUE' : 'FALSE';
		}

		$tagdata			= ee()->functions->prep_conditionals( $tagdata, $cond );

		/**	----------------------------------------
		/**	Parse var pairs
		/**	----------------------------------------*/

		foreach ( ee()->TMPL->var_pair as $key => $val )
		{
			/** --------------------------------------------
			/**  Member Groups Select List
			/** --------------------------------------------*/

			if ($key == 'select_member_groups')
			{
				if (ee()->TMPL->fetch_param('allowed_groups') !== FALSE)
				{
					$data		= ee()->TMPL->fetch_data_between_var_pairs( $tagdata, $key );

					$tagdata	= preg_replace( "/".LD.$key.RD."(.*?)".LD.preg_quote($this->t_slash, '/').$key.RD."/s",
												str_replace('$', '\$', $this->_parse_select_member_groups( $data )),
												$tagdata );
				}
				else
				{
					$tagdata	= preg_replace( "/".LD.$key.RD."(.*?)".LD.preg_quote($this->t_slash, '/').$key.RD."/s", '', $tagdata);
				}
			}

			/** --------------------------------------------
			/**  Mailing Lists Select List
			/** --------------------------------------------*/

			if ($key == 'select_mailing_lists')
			{
				$data		= ee()->TMPL->fetch_data_between_var_pairs( $tagdata, $key );

				$tagdata	= preg_replace( "/".LD.$key.RD."(.*?)".LD.preg_quote($this->t_slash, '/').$key.RD."/s",
											str_replace('$', '\$', $this->_parse_select_mailing_lists( $data, array() )),
											$tagdata );
			}

			/**	----------------------------------------
			/**	Languages
			/**	----------------------------------------*/

			if ( $key == 'languages' )
			{
				$dirs = array();

				if ($fp = @opendir($this->lang_dir))
				{
					while (FALSE !== ($file = readdir($fp)))
					{
						if (is_dir($this->lang_dir.$file) AND substr($file, 0, 1) != ".")
						{
							$dirs[] = $file;
						}
					}
					closedir($fp);
				}

				sort($dirs);

				preg_match( "/".LD.$key.RD."(.*?)".LD.preg_quote($this->t_slash, '/').$key.RD."/s", $tagdata, $match );
				$r	= '';

				foreach ( $dirs as $key )
				{
					$out		= $match['1'];

					$checked	= ( isset($query_row['language'] ) AND $query_row['language'] == $key ) ? 'checked="checked"': '';

					$selected	= ( isset($query_row['language'] ) AND $query_row['language'] == $key ) ? 'selected="selected"': '';

					$out		= str_replace( LD."language_name".RD, $key, $out );
					$out		= str_replace( LD."language_label".RD, ucfirst( $key ), $out );
					$out		= str_replace( LD."checked".RD, $checked, $out );
					$out		= str_replace( LD."selected".RD, $selected, $out );

					$r	.= $out;
				}

				$tagdata	= str_replace( $match[0], $r, $tagdata );
			}
		}

		/**	----------------------------------------
		/**	Parse selects
		/**	----------------------------------------*/

		foreach ( $this->_mfields() as $key => $val )
		{
			/**	----------------------------------------
			/**	Parse select
			/**	----------------------------------------*/

			foreach ( ee()->TMPL->var_pair as $k => $v )
			{
				if ( $k == "select_".$key )
				{
					$data		= ee()->TMPL->fetch_data_between_var_pairs( $tagdata, $k );

					$tagdata	= preg_replace( "/".LD.preg_quote($k, '/').RD."(.*?)".LD.preg_quote($this->t_slash, '/').preg_quote($k, '/').RD."/s",
												str_replace('$', '\$', $this->_parse_select( $key, array(), $data )),
												$tagdata );
				}
			}
		}

		/**	----------------------------------------
		/**	Sniff for fields of type 'file'
		/**	----------------------------------------*/

		if ( preg_match( "/type=['|\"]?file['|\"]?/", $tagdata, $match ) )
		{
			$this->multipart	= TRUE;
		}

		/**	----------------------------------------
		/**	Do we just want the parsing and no
		/**	form?
		/**	----------------------------------------*/

		if ( ee()->TMPL->fetch_param( 'no_form' ) == "yes" )
		{
			return $tagdata;
		}

		/**	----------------------------------------
		/**	Prep data
		/**	----------------------------------------*/

		$this->form_data['tagdata']	= $tagdata;

		$this->form_data['ACT']		= ee()->functions->fetch_action_id('User', 'reg');

		if (isset($_POST['RET']))
		{
			 $this->form_data['RET'] = $_POST['RET'];
		}
		elseif(ee()->TMPL->fetch_param('return') !== FALSE)
		{
			$this->form_data['RET'] = ee()->TMPL->fetch_param('return');
		}
		else
		{
			$this->form_data['RET'] = ee()->functions->fetch_current_uri();
		}

		if ( ee()->TMPL->fetch_param('form_name') !== FALSE AND ee()->TMPL->fetch_param('form_name') != '' )
		{
			$this->form_data['name']	= ee()->TMPL->fetch_param('form_name');
		}

		$this->form_data['id']						= ( ee()->TMPL->fetch_param('form_id') !== FALSE ) ? ee()->TMPL->fetch_param('form_id'): 'member_form';

		$this->params['group_id']				= ( ee()->TMPL->fetch_param('group_id') !== FALSE ) ? ee()->TMPL->fetch_param('group_id'): '';

		$this->params['notify']					= ( ee()->TMPL->fetch_param('notify') !== FALSE ) ? ee()->TMPL->fetch_param('notify'): '';

		$this->params['screen_name_override']	= ( ee()->TMPL->fetch_param('screen_name') !== FALSE ) ? ee()->TMPL->fetch_param('screen_name'): '';

		$this->params['exclude_username']		= ( ee()->TMPL->fetch_param('exclude_username') ) ? ee()->TMPL->fetch_param('exclude_username'): '';

		$this->params['require_key']			= ( ee()->TMPL->fetch_param('require_key') ) ? ee()->TMPL->fetch_param('require_key'): '';

		$this->params['key_email_match']		= ( ee()->TMPL->fetch_param('key_email_match') ) ? ee()->TMPL->fetch_param('key_email_match'): '';

		$this->params['key']					= ( ee()->TMPL->fetch_param('key') != '' ) ? ee()->TMPL->fetch_param('key'): '';

		$this->params['secure_action']			= ( ee()->TMPL->fetch_param('secure_action') !== FALSE) ? ee()->TMPL->fetch_param('secure_action'): 'no';

		$this->params['admin_register']			= ( ee()->TMPL->fetch_param('admin_register') !== FALSE) ? ee()->TMPL->fetch_param('admin_register'): 'no';

		$this->params['required']				= ( ee()->TMPL->fetch_param('required') ) ? ee()->TMPL->fetch_param('required'): '';

		if (ee()->TMPL->fetch_param('allowed_groups') !== FALSE AND ee()->TMPL->fetch_param('allowed_groups') != '')
		{
			$this->params['allowed_groups']		=  ee()->TMPL->fetch_param('allowed_groups');
		}

		/**	----------------------------------------
		/**	Return
		/**	----------------------------------------*/

		return $this->_form();
	}

	/* END register */


	// --------------------------------------------------------------------

	/**
	 *	Registration Form Processing
	 *
	 *	@access		public
	 *	@param		bool
	 *	@return		string
	 */

	public function reg ( $remote = FALSE )
	{
		ee()->load->helper('url'); // For prep_url();

		$key_id	= '';

		//----------------------------------------
		//	Do we allow new member registrations?
		//----------------------------------------

		if (ee()->config->item('allow_member_registration') == 'n')
		{
			return $this->_output_error(
				'general',
				array(lang('registration_not_enabled'))
			);
		}

		//--------------------------------------------
		//  Allowed to Register
		//--------------------------------------------

		if ( ee()->session->userdata('member_id') != 0)
		{
			if ($this->_param('admin_register') !== 'yes' OR
				(ee()->session->userdata['group_id'] != 1 AND
				 ee()->session->userdata['can_admin_members'] !== 'y'))
			{
				return $this->_output_error(
					'general',
					array(lang('mbr_you_are_registered'))
				);
			}
		}

		//--------------------------------------------
		//	2.2.0 Auth lib
		//--------------------------------------------

		ee()->load->library('auth');

		// This should go in the auth lib.
		if ( ! ee()->auth->check_require_ip())
		{
			return $this->_output_error(
				'general',
				array(lang('not_authorized'))
			);
		}


		//--------------------------------------------
		//	Is user banned?
		//--------------------------------------------

		if (ee()->session->userdata('is_banned') == TRUE)
		{
			return $this->_output_error(
				'general',
				array(lang('not_authorized'))
			);
		}

		//--------------------------------------------
		//	Blacklist/Whitelist Check
		//--------------------------------------------

		if (isset(ee()->blacklist))
		{
			if (ee()->blacklist->blacklisted == 'y' && ee()->blacklist->whitelisted == 'n')
			{
				return $this->_output_error(
					'general',
					array(lang('not_authorized'))
				);
			}
		}

		//--------------------------------------------
		//	Clean the post
		//--------------------------------------------

		//need to protect passwords from this because they get hashed anyway
		$temp_pass 	= isset($_POST['password']) 		? $_POST['password'] 		 : FALSE;
		$temp_pass2 = isset($_POST['password_confirm']) ? $_POST['password_confirm'] : $temp_pass;

		$_POST	= ee()->security->xss_clean( $_POST );

		//make sure the password is actually set
		if ( ! in_array($temp_pass, array(FALSE, ''), TRUE))
		{
			$_POST['password'] = $temp_pass;
		}

		//make sure the password is actually set
		if ( ! in_array($temp_pass2, array(FALSE, ''), TRUE))
		{
			$_POST['password_confirm'] = $temp_pass2;
		}

		//--------------------------------------------
		//  Email as Username Preference
		//--------------------------------------------

		$wquery = ee()->db->query(
			"SELECT preference_value
			 FROM 	exp_user_preferences
			 WHERE 	preference_name = 'email_is_username'"
		);

		$this->preferences['email_is_username'] = (
			$wquery->num_rows() == 0
		) ? 'n' : $wquery->row('preference_value');

		//--------------------------------------------
		//	Check email is username
		//--------------------------------------------

		$this->_email_is_username( '0', 'new' );

		//--------------------------------------------
		//	Empty email?
		//--------------------------------------------

		if ( ! ee()->input->get_post('email') )
		{
			return $this->_output_error('general', array(lang('email_required')));
		}

		//--------------------------------------------
		// 'user_register_start' hook.
		//  - Take control of member registration routine
		//--------------------------------------------

		if (ee()->extensions->active_hook('user_register_start') === TRUE)
		{
			$edata = ee()->extensions->universal_call('user_register_start', $this);
			if (ee()->extensions->end_script === TRUE) return;
		}

		//--------------------------------------------
		//	Set the default globals
		//--------------------------------------------

		$default = array_merge(
			array('username', 'password', 'password_confirm', 'email', 'screen_name' ),
			$this->standard
		);

		foreach ($default as $val)
		{
			if ( ! isset($_POST[$val]))
			{
				$_POST[$val] = '';
			}
		}

		/**	----------------------------------------
		/**	Check screen name override
		/**	----------------------------------------*/

		$this->_screen_name_override();

		/**	----------------------------------------
		/**	Handle alternate username / screen name
		/**	----------------------------------------*/

		if ( ee()->input->post('username') == '' AND
			 $this->preferences['email_is_username'] == 'y' )
		{
			$_POST['username']	= ee()->input->get_post('email');
		}

		if ( ! ee()->input->get_post('screen_name') OR
			 ee()->input->get_post('screen_name') == '' )
		{
			$_POST['screen_name']	= $_POST['username'];
		}

		// -------------------------------------
		//	EE 2.3+ trims username and screenname
		// -------------------------------------

		ee()->load->helper('string');

		if (isset($_POST['username']))
		{
			$_POST['username']		= trim_nbs($_POST['username']);
		}

		if (isset($_POST['screen_name']))
		{
			$_POST['screen_name']	= trim_nbs($_POST['screen_name']);
		}


		/**	----------------------------------------
		/**	Check prohibited usernames
		/**	----------------------------------------*/

		if (ee()->session->ban_check('username', $_POST['username']))
		{
			return $this->_output_error(
				'general',
				array(lang('prohibited_username'))
			);
		}

		if ($this->_param('exclude_username') != '' AND
			in_array($_POST['username'], explode('|', $this->_param('exclude_username'))))
		{
			return $this->_output_error(
				'general',
				array(lang('prohibited_username'))
			);
		}

		/**	----------------------------------------
		/**   Required Fields
		/**	----------------------------------------*/

		if ( $this->_param('required') !== FALSE)
		{
			$this->_mfields();

			$missing	= array();

			$required	= preg_split( "/,|\|/", $this->_param('required') );

			foreach ( $required as $req )
			{
				if ( $req == 'all_required')
				{
					foreach ( $this->mfields as $key => $val )
					{
						if ( ! ee()->input->get_post($key) AND $val['required'] == 'y' )
						{
							$missing[]	= $this->mfields[$key]['label'];
						}
					}
				}
				elseif ( ! ee()->input->get_post($req) )
				{
					if (isset( $this->mfields[$req] ) )
					{
						$missing[]	= $this->mfields[$req]['label'];
					}
					elseif (in_array($req, $this->standard))
					{
						if (in_array($req, array('bday_d', 'bday_m', 'bday_y')))
						{
							$missing[]	= lang('mbr_birthday');
						}
						else if ($req == 'daylight_savings' AND
							version_compare($this->ee_version, '2.6.0', '<'))
						{
							$missing[] = lang('daylight_savings_time');
						}
						elseif(in_array($req, array('aol_im', 'yahoo_im', 'msn_im', 'icq', 'signature' )))
						{
							$missing[]	= lang($req);
						}
						else
						{
							$missing[]	= lang('mbr_'.$req);
						}
					}
				}
			}

			/**	----------------------------------------
			/**	Anything missing?
			/**	----------------------------------------*/

			if ( count( $missing ) > 0 )
			{
				$missing	= implode( "</li><li>", $missing );

				$str		= str_replace( "%fields%", $missing, lang('missing_fields') );

				return $this->_output_error('general', $str);
			}
		}

		/**	----------------------------------------
		/**	Instantiate validation class
		/**	----------------------------------------*/

		$validate_config = array(
			'member_id'			=> '',
			'val_type'			=> 'new', // new or update
			'fetch_lang' 		=> TRUE,
			'require_cpw' 		=> FALSE,
			'enable_log'		=> FALSE,
			'username'			=> $_POST['username'],
			'cur_username'		=> '',
			'screen_name'		=> stripslashes($_POST['screen_name']),
			'cur_screen_name'	=> '',
			'password'			=> $_POST['password'],
			'password_confirm'	=> $_POST['password_confirm'],
			'cur_password'		=> '',
			'email'				=> $_POST['email'],
			'cur_email'			=> ''
		);

		ee()->load->library('validate', $validate_config, 'validate');

		ee()->validate->validate_username();
		ee()->validate->validate_screen_name();
		ee()->validate->validate_password();
		ee()->validate->validate_email();

		if ($this->preferences['email_is_username'] != 'n' AND
			($key = array_search(
				lang('username_password_too_long'),
				ee()->validate->errors)
			) !== FALSE)
		{
			if (strlen(ee()->validate->username) <= 50)
			{
				unset(ee()->validate->errors[$key]);
			}
			else
			{
				ee()->validate->errors[$key] = str_replace('32', '50', ee()->validate->errors[$key]);
			}
		}

		/**	----------------------------------------
		/**	Do we have any custom fields?
		/**	----------------------------------------*/

		$cust_errors = array();
		$cust_fields = array();
		$fields		 = '';

		if ( count( $this->_mfields() ) > 0 )
		{
			foreach ( $this->mfields as $key => $val )
			{
				if ( $val['required'] == 'y' AND ! ee()->input->get_post($key) )
				{
					$fields	.=	"<li>".$val['label']."</li>";
				}

				if ( isset( $_POST[ $key ] ) )
				{
					/**	----------------------------------------
					/**	Handle arrays
					/**	----------------------------------------*/

					if ( is_array( $_POST[ $key ] ) )
					{
						$cust_fields['m_field_id_'.$val['id']] =  implode( "\n", $_POST[ $key ] );
					}
					else
					{
						$cust_fields['m_field_id_'.$val['id']] = $_POST[ $key ];
					}
				}
			}

			if ( $fields != '' )
			{
				$cust_errors[] = str_replace( "%s", $fields, lang('user_field_required') );
			}
		}

		/**	----------------------------------------
		/**	Assemble custom fields
		/**	----------------------------------------*/

		$cfields	= array();

		foreach ( $this->_mfields() as $key => $val )
		{
			if ( isset( $_POST[ $key ] ) )
			{
				/**	----------------------------------------
				/**	Handle arrays
				/**	----------------------------------------*/

				if ( is_array( $_POST[ $key ] ) )
				{
					$cfields['m_field_id_'.$val['id']]	= implode( "\n", $_POST[ $key ] );
				}
				else
				{
					$cfields['m_field_id_'.$val['id']]	= $_POST[ $key ];
				}
			}
		}


		if (ee()->config->item('use_membership_captcha') == 'y')
		{
			if (ee()->config->item('captcha_require_members') == 'y'  OR
				(ee()->config->item('captcha_require_members') == 'n' AND
				 ee()->session->userdata('member_id') == 0)
			 )
			{
				// Hidden configuration!  Disables CAPTCHA on Remote Registrations.
				if ($remote === TRUE && ee()->config->item('user_disable_remote_captcha') == 'y')
				{
					// Nothing...
				}
				elseif ( ! isset($_POST['captcha']) OR $_POST['captcha'] == '')
				{
					$cust_errors[] = lang('captcha_required');
				}
			}
		}

		if (ee()->config->item('require_terms_of_service') == 'y')
		{
			if ( ! isset($_POST['accept_terms']))
			{
				$cust_errors[] = lang('mbr_terms_of_service_required');
			}
		}

		$errors = array_merge(ee()->validate->errors, $cust_errors);


		/** --------------------------------------------
		/**	 'user_register_error_checking' Extension Hook
		/**		- Error checking
		/**		- Added User 2.0.9
	   /** --------------------------------------------*/

		if (ee()->extensions->active_hook('user_register_error_checking') === TRUE)
		{
			$errors = ee()->extensions->universal_call('user_register_error_checking', $this, $errors);
			if (ee()->extensions->end_script === TRUE) return;
		}

		/**	----------------------------------------
		/**	 Output Errors
		/**	----------------------------------------*/

		 if (count($errors) > 0)
		 {
			return $this->_output_error('submission', $errors);
		 }

		/**	----------------------------------------
		/**	Do we require a key?
		/**	----------------------------------------*/

		if ( $this->_param('require_key') == 'yes' OR
			 $this->_param('key_email_match') == 'yes' )
		{
			/**	----------------------------------------
			/**	No key?
			/**	----------------------------------------*/

			if ( ! ee()->input->post('key') )
			{
				return $this->_output_error(
					'submission',
					array(lang('key_required'))
				);
			}

			/**	----------------------------------------
			/**	Key and email match required?
			/**	----------------------------------------*/

			if ( $this->_param('key_email_match') == 'yes' AND
				! ee()->input->get_post('email') )
			{
				return $this->_output_error(
					'submission',
					array(lang('key_email_match_required'))
				);
			}

			/**	----------------------------------------
			/**	Query
			/**	----------------------------------------*/

			$sql = "SELECT 	key_id
					FROM 	exp_user_keys
					WHERE 	member_id = '0'
					AND 	hash = '" .
					ee()->db->escape_str( ee()->input->get_post('key') ) . "'";

			if ( $this->_param('key_email_match') == 'yes' )
			{
				$sql	.= " AND email = '" .
							ee()->db->escape_str( ee()->input->get_post('email') )."'";
			}

			$query	= ee()->db->query( $sql );

			if ( $query->num_rows() == 0 )
			{
				$query = ee()->db->query(
					"SELECT preference_value
					 FROM 	exp_user_preferences
					 WHERE 	preference_name = 'key_expiration'
					 LIMIT  1"
				);

				$exp = ( $query->num_rows() > 0 ) ?
					$query->row('preference_value') :
					$exp;

				return $this->_output_error(
					'submission',
					array( str_replace( "%s", $exp, lang('key_incorrect')))
				);
			}

			$key_id	= $query->row('key_id');
		}

		/**	----------------------------------------
		/**	Set member group
		/**	----------------------------------------*/

		if (ee()->config->item('req_mbr_activation') == 'manual' OR
			ee()->config->item('req_mbr_activation') == 'email')
		{
			$this->insert_data['group_id'] = 4;  // Pending
		}
		else
		{
			if (ee()->config->item('default_member_group') == '')
			{
				$this->insert_data['group_id'] = 4;  // Pending
			}
			else
			{
				$this->insert_data['group_id'] = ee()->config->item('default_member_group');
			}
		}

		/**	----------------------------------------
		/**	Override member group if hard coded
		/**	----------------------------------------*/

		if ( $this->_param('group_id') AND
			 is_numeric( $this->_param('group_id') ) AND
			 $this->_param('group_id') != '1' )
		{
			// Email and Manual Activation will use the exp_user_activation_group table to change group.

			if (ee()->config->item('req_mbr_activation') != 'email' AND
				ee()->config->item('req_mbr_activation') != 'manual')
			{
				$this->insert_data['group_id']	= $this->_param('group_id');
			}
		}

		/**	----------------------------------------
		/**	Override member group if invitation
		/**	code provided and valid.
		/**	----------------------------------------*/

		if ( $key_id != '' AND $key_id != '1' )
		{
			$key = ee()->db->query(
				"SELECT k.group_id
				 FROM 	exp_user_keys AS k
				 JOIN 	exp_member_groups AS g
				 ON 	g.group_id = k.group_id
				 WHERE 	k.key_id = '" . ee()->db->escape_str($key_id) . "'
				 AND 	k.group_id NOT IN (0, 1)" );

			if ( $key->num_rows() > 0 )
			{
				if (ee()->config->item('req_mbr_activation') == 'email' OR
					ee()->config->item('req_mbr_activation') == 'manual')
				{
					$this->params['group_id'] = $key->row('group_id');
				}
				else
				{
					$this->insert_data['group_id'] = $key->row('group_id');
				}
			}
		}

		/** --------------------------------------------
		/**  Submitted Group ID, Restricted by allowed_groups=""
		/** --------------------------------------------*/

		if(ee()->input->post('group_id') !== FALSE AND
		   ctype_digit(ee()->input->post('group_id')) AND
		   $this->_param('allowed_groups') )
		{
			$sql = "SELECT DISTINCT group_id
					FROM 	exp_member_groups
					WHERE 	group_id
					NOT IN 	(1,2,3,4)
					AND 	group_id = '" .
						ee()->db->escape_str(ee()->input->post('group_id')) . "' " .
						ee()->functions->sql_andor_string(
							$this->_param('allowed_groups'),
							'group_id'
						);

			$mquery = ee()->db->query($sql);

			if ($mquery->num_rows() > 0)
			{
				if (ee()->config->item('req_mbr_activation') == 'email' OR
					ee()->config->item('req_mbr_activation') == 'manual')
				{
					$this->params['group_id'] = $mquery->row('group_id');
				}
				else
				{
					$this->insert_data['group_id'] = $mquery->row('group_id');
				}
			}
		}

		/**	----------------------------------------
		/**	Double check that member group is real
		/**	----------------------------------------*/

		$query = ee()->db->query(
			"SELECT COUNT(*) AS count
			 FROM 	exp_member_groups
			 WHERE  group_id != '1'
			 AND 	group_id = '" .
				ee()->db->escape_str($this->insert_data['group_id']) . "'"
		);

		if ( $query->row('count') == 0 )
		{
			return $this->_output_error(
				'submission',
				array(lang('invalid_member_group'))
			);
		}

		/** --------------------------------------------
		/**  Test Image Uploads
		/** --------------------------------------------*/

		$this->_upload_images( 0, TRUE );

		/**	----------------------------------------
		/**	Do we require captcha?
		/**	----------------------------------------*/

		if (ee()->config->item('use_membership_captcha') == 'y')
		{
			if (ee()->config->item('captcha_require_members') == 'y'  OR
				(ee()->config->item('captcha_require_members') == 'n' AND
				 ee()->session->userdata('member_id') == 0))
			{
				// Hidden configuration!  Disables CAPTCHA on Remote Registrations.
				if ($remote === TRUE && ee()->config->item('user_disable_remote_captcha') == 'y')
				{
					// Nothing...
				}
				else
				{
					$query = ee()->db->query(
						"SELECT COUNT(*) AS count
						 FROM 	exp_captcha
						 WHERE  word='" . ee()->db->escape_str($_POST['captcha'])."'
						 AND 	ip_address = '".ee()->input->ip_address()."'
						 AND 	date > UNIX_TIMESTAMP()-7200"
					);

					if ($query->row('count') == 0)
					{
						return $this->_output_error(
							'submission',
							array(lang('captcha_incorrect'))
						);
					}

					ee()->db->query(
						"DELETE FROM exp_captcha
						 WHERE 	(word='".ee()->db->escape_str($_POST['captcha'])."'
								AND 	ip_address = '".ee()->input->ip_address()."')
						 OR date < UNIX_TIMESTAMP()-7200"
					);
				}
			}
		}

		//	----------------------------------------
		//	Secure Mode Forms?
		//	----------------------------------------

		//EE 2.8 takes all of this out of our hands
		if (version_compare($this->ee_version, '2.8.0', '<') && $this->csrf_enabled())
		{
			$csrf_key = 'secure_forms';

			$good = ee()->input->get_post($this->sc->csrf_name);

			$good = ee()->security->check_xid($good);

			//EE 2.7 does this 'for us'
			if (version_compare($this->ee_version, '2.7', '<') && ! $good)
			{
				return $this->_output_error(
					'general',
					array(lang('not_authorized'))
				);
			}

			//----------------------------------------
			//	Delete secure hash?
			//----------------------------------------
			//	The reg() function is also assisting the
			//	remote_registration routine. That
			//	routine receives form submissions from
			//	comment and rating forms. If we delete
			//	the secure hash now, those forms will fail
			//	when they do their security check.
			//	So we don't delete in the case of remote reg.
			//----------------------------------------

			if ( $remote === FALSE && version_compare($this->ee_version, '2.7', '<'))
			{
				ee()->security->delete_xid(ee()->input->get_post($this->sc->csrf_name));
			}
			//EE 2.7 auto deletes csrf_token. The jerk.
			else
			{
				$this->restore_xid();
			}
		}

		//	----------------------------------------
		//	Assign the base query data
		//	----------------------------------------

		$this->insert_data['username']    = $_POST['username'];

		$pass_data = ee()->auth->hash_password( stripslashes($_POST['password']));

		$this->insert_data['password']    	= $pass_data['password'];
		$this->insert_data['salt']    		= $pass_data['salt'];


		$this->insert_data['ip_address']  = ee()->input->ip_address();
		$this->insert_data['unique_id']   = ee()->functions->random('encrypt');
		$this->insert_data['join_date']   = ee()->localize->now;
		$this->insert_data['email']       = $_POST['email'];
		$this->insert_data['screen_name'] = $_POST['screen_name'];

		/**	----------------------------------------
		/**	Optional Fields
		/**	----------------------------------------*/

		$optional	= array('language'			=> 'deft_lang',
							'timezone'			=> 'server_timezone',
							'time_format'		=> 'time_format');

		foreach($optional as $key => $value)
		{
			if (isset($_POST[$value]))
			{
				$this->insert_data[$key] = $_POST[$value];
			}
		}

		foreach($this->standard as $key)
		{
			if (isset($_POST[$key]))
			{
				$this->insert_data[$key] = $_POST[$key];
			}
		}

		$this->insert_data['url']				= prep_url($_POST['url']);

		if (version_compare($this->ee_version, '2.6.0', '<'))
		{
			$this->insert_data['daylight_savings']	= (
				ee()->input->post('daylight_savings') == 'y'
			) ? 'y' : 'n';
		}

		// We generate an authorization code if the member needs to self-activate

		if (ee()->config->item('req_mbr_activation') == 'email')
		{
			$this->insert_data['authcode'] = ee()->functions->random('alpha', 10);
		}

		// Default timezone
		if ( ! isset($this->insert_data['timezone']))
		{
			$this->insert_data['timezone'] = 'UTC';
		}

		// -------------------------------------
		//	fix ints
		//	NOTE: Do not remove this unless a new
		//	solution has been found.
		//	Needed to fix errors with MySQL strict.
		// -------------------------------------

		foreach ($this->int_fields as $int_field)
		{
			if (isset($this->insert_data[$int_field]) &&
				empty($this->insert_data[$int_field]))
			{
				$this->insert_data[$int_field] = 0;
			}
		}

		/**	----------------------------------------
		/**	Insert basic member data
		/**	----------------------------------------*/

		ee()->db->insert('exp_members', $this->insert_data);

		$member_id = ee()->db->insert_id();

		//running a second time to get the member_id correct
		$this->_screen_name_override($member_id);

		/**	----------------------------------------
		/**	Insert custom fields
		/**	----------------------------------------*/

		$cust_fields['member_id'] = $member_id;

		ee()->db->insert('exp_member_data', $cust_fields);

		/**	----------------------------------------
		/**	Member Group Override on Activation
		/**	----------------------------------------*/

		if ( $this->_param('group_id') AND
			 is_numeric( $this->_param('group_id') ) AND
			 $this->_param('group_id') != '1' )
		{
			if (ee()->config->item('req_mbr_activation') == 'email' OR
				ee()->config->item('req_mbr_activation') == 'manual')
			{
				ee()->db->query(
					ee()->db->insert_string(
						'exp_user_activation_group',
						array(
							'member_id' => $member_id,
							'group_id' 	=> $this->_param('group_id')
						)
					)
				);
			}
		}

		/** ---------------------------------
		/**	Fetch categories
		/** ---------------------------------*/

		if ( isset( $_POST['category']))
		{
			if (is_array( $_POST['category'] ))
			{
				foreach ( $_POST['category'] as $cat_id )
				{
					$this->cat_parents[] = $cat_id;
				}
			}
			elseif (is_numeric($_POST['category']))
			{
				$this->cat_parents = $_POST['category'];
			}
		}

		if (count($this->cat_parents) > 0)
		{
			if ( ee()->config->item('auto_assign_cat_parents') == 'y' )
			{
				$this->_fetch_category_parents( $this->cat_parents );
			}
		}

		unset( $_POST['category'] );

		ee()->db->query(
			"DELETE FROM exp_user_category_posts
			 WHERE 	member_id = '" . $member_id . "'"
		);

		foreach ( $this->cat_parents as $cat_id )
		{
			ee()->db->query(
				ee()->db->insert_string(
					'exp_user_category_posts',
					array(
						'member_id' => $member_id,
						'cat_id' 	=> $cat_id
					)
				)
			);
		}

		/**	----------------------------------------
		/**	Handle image uploads
		/**	----------------------------------------*/

		$this->_upload_images( $member_id );

		/**	----------------------------------------
		/**	Update key table
		/**	----------------------------------------*/

		if ( $key_id != '' )
		{
			ee()->db->query(
				ee()->db->update_string(
					'exp_user_keys',
					array( 'member_id' 	=> $member_id ),
					array( 'key_id' 	=> $key_id )
				)
			);
		}

		//----------------------------------------
		//	Create a record in the member
		//	homepage table
		//----------------------------------------

		// This is only necessary if the user gains
		// CP access, but we'll add the record anyway.

		ee()->db->query(
			ee()->db->insert_string(
				'exp_member_homepage',
				array('member_id' => $member_id)
			)
		);

		//--------------------------------------------
		//  Set Language Variable
		//--------------------------------------------

		if ( isset($_POST['language']) AND
			 preg_match("/^[a-z]+$/", $_POST['language']))
		{
			ee()->session->userdata['language'] = $_POST['language'];
		}

		//----------------------------------------
		//	Mailinglist Subscribe
		//----------------------------------------

		$mailinglist_subscribe = FALSE;

		if (isset($_POST['mailinglist_subscribe']) AND
			(is_array($_POST['mailinglist_subscribe']) OR
			 is_numeric($_POST['mailinglist_subscribe'])))
		{
			// Kill duplicate emails from authorizatin queue.
			ee()->db->query(
				"DELETE FROM 	exp_mailing_list_queue
				 WHERE 			email = '" . ee()->db->escape_str($_POST['email']) . "'"
			);

			$lists = (is_array($_POST['mailinglist_subscribe'])) ?
						$_POST['mailinglist_subscribe'] :
						 array($_POST['mailinglist_subscribe']);

			foreach($lists as $list_id)
			{
				// Validate Mailing List ID
				$query = ee()->db->query(
					"SELECT list_title
					 FROM 	exp_mailing_lists
					 WHERE 	list_id = '" . ee()->db->escape_str($list_id) . "'"
				);

				// Email Not Already in Mailing List
				$results = ee()->db->query(
					"SELECT COUNT(*) AS count
					 FROM 	exp_mailing_list
					 WHERE 	email = '" . ee()->db->escape_str($_POST['email']) . "'
					 AND 	list_id = '" . ee()->db->escape_str($list_id) . "'"
				);

				//----------------------------------------
				//	INSERT Email
				//----------------------------------------

				if ($query->num_rows() > 0 AND $results->row('count') == 0)
				{
					$code = ee()->functions->random('alpha', 10);

					//----------------------------------------
					// 	The User module still does member
					//	activation through the Member module,
					// 	which does not allow one to activate
					//	MORE THAN ONE Mailing List subscription
					// 	per registration.  So, what we do is if
					//	member activation is not automatic
					// 	AND there is more than one mailing list
					//	being subscribed to, then we require
					// 	activation of mailing list subscription
					//	on an individual basis through the
					// 	Mailing List module.
					//----------------------------------------

					if (ee()->config->item('req_mbr_activation') == 'email' AND
						count($lists) == 1)
					{
						$mailinglist_subscribe = TRUE;

						// Activated When Membership Activated
						ee()->db->query(
							"INSERT INTO exp_mailing_list_queue (email, list_id, authcode, date)
							 VALUES 	('".ee()->db->escape_str($_POST['email'])."', '" .
											ee()->db->escape_str($list_id)."', '" .
											$code."', '".time()."')"
						);
						//we will notify the admin of the mailing list join as soon as the member
						//hits thier activation key email2
					}
					elseif (ee()->config->item('req_mbr_activation') == 'manual' OR
							ee()->config->item('req_mbr_activation') == 'email')
					{
						// Mailing List Subscribe Email
						ee()->db->query(
							"INSERT INTO exp_mailing_list_queue (email, list_id, authcode, date)
							 VALUES ('".ee()->db->escape_str($_POST['email'])."', '" .
										ee()->db->escape_str($list_id)."', '" .
										$code."', '".time()."')"
						);

						$this->mailing_list_email(
							$query->row('list_title'),
							ee()->input->post('email', TRUE),
							$code
						);
					}
					else
					{
						// Automatically Accepted
						ee()->db->query(
							"INSERT INTO exp_mailing_list (user_id, list_id, authcode, email)
							 VALUES 	('', '".ee()->db->escape_str($list_id)."', '" .
										$code."', '" .
										ee()->db->escape_str($_POST['email'])."')"
						);

						$this->notify_mailinglist_admin(
							$query->row('list_title'),
							ee()->input->post('email', TRUE)
						);
					}
				}
			}
		}
		// End Mailing Lists inserts...

		//----------------------------------------
		//	Send admin notifications
		//----------------------------------------

		$notify	= ( $this->_param('notify') ) ? $this->_param('notify'): '';

		if ( ( ee()->config->item('new_member_notification') == 'y' AND
			   ee()->config->item('mbr_notification_emails') != '' ) OR
			   $notify != '' )
		{
			$name = ($this->insert_data['screen_name'] != '') ?
				$this->insert_data['screen_name'] :
				$this->insert_data['username'];

			$swap = array(
				'name'					=> $name,
				'site_name'				=> stripslashes(ee()->config->item('site_name')),
				'control_panel_url'		=> ee()->config->item('cp_url'),
				'username'				=> $this->insert_data['username'],
				'email'					=> $this->insert_data['email']
			);

			$template = ee()->functions->fetch_email_template('admin_notify_reg');
			$email_tit = $this->_var_swap($template['title'], $swap);
			$email_msg = $this->_var_swap($template['data'], $swap);

			$notify_address = ( $notify != '' ) ?
								$notify :
								ee()->config->item('mbr_notification_emails');

			ee()->load->helper('string');

			$notify_address	= reduce_multiples( $notify_address, ',', TRUE);

			/**	----------------------------------------
			/**	Send email
			/**	----------------------------------------*/

			ee()->load->library('email');
			ee()->load->helper('text');

			ee()->email->initialize();
			ee()->email->wordwrap = true;
			ee()->email->from(
				ee()->config->item('webmaster_email'),
				ee()->config->item('webmaster_name')
			);
			ee()->email->to($notify_address);
			ee()->email->subject($email_tit);
			ee()->email->message(entities_to_ascii($email_msg));
			ee()->email->Send();
		}

		/**	----------------------------------------
		/*	'user_register_end' hook.
		/*	- Additional processing when a member is created through the User Side
		/**	----------------------------------------*/

		if (ee()->extensions->active_hook('user_register_end') === TRUE)
		{
			$edata = ee()->extensions->universal_call('user_register_end', $this, $member_id);
			if (ee()->extensions->end_script === TRUE) return;
		}

		//false is put here so we can eventually add a preference for this
		//but still allow people who want it now to remove the false and
		//get the functionality. This is sort of tricky, though so no
		//"official" support on it just yet.
		if (FALSE AND ee()->extensions->active_hook('member_member_register') === TRUE)
		{
			$this->EE->extensions->call('member_member_register', $this->insert_data, $member_id);
			if ($this->EE->extensions->end_script === TRUE) return;
		}

		/**	----------------------------------------*/

		/**	----------------------------------------
		/**	Send user notifications
		/**	----------------------------------------*/

		$message = '';

		if ( ee()->config->item('req_mbr_activation') == 'email' )
		{
			$qs = (ee()->config->item('force_query_string') == 'y') ? '' : '?';

			$action_id  = ee()->functions->fetch_action_id('User', 'activate_member');

			$name = ($this->insert_data['screen_name'] != '') ?
						$this->insert_data['screen_name'] :
						$this->insert_data['username'];

			$forum_id = (ee()->input->get_post('FROM') == 'forum') ? '&r=f' : '';

			$add = ($mailinglist_subscribe !== TRUE) ? '' : '&mailinglist='.$list_id;

			$swap = array(
				'name'				=> $name,
				'activation_url'	=> ee()->functions->fetch_site_index(0, 0) .
										$qs . 'ACT='.$action_id .
										'&id=' . $this->insert_data['authcode'] .
										$forum_id.$add,
				'site_name'			=> stripslashes(ee()->config->item('site_name')),
				'site_url'			=> ee()->config->item('site_url'),
				'username'			=> $this->insert_data['username'],
				'email'				=> $this->insert_data['email']
			 );

			$template = ee()->functions->fetch_email_template('mbr_activation_instructions');
			$email_tit = $this->_var_swap($template['title'], $swap);
			$email_msg = $this->_var_swap($template['data'], $swap);

			/**	----------------------------------------
			/**	Send email
			/**	----------------------------------------*/

			ee()->load->library('email');
			ee()->load->helper('text');

			ee()->email->initialize();
			ee()->email->wordwrap = true;
			ee()->email->from(
				ee()->config->item('webmaster_email'),
				ee()->config->item('webmaster_name')
			);
			ee()->email->to($this->insert_data['email']);
			ee()->email->subject($email_tit);
			ee()->email->message(entities_to_ascii($email_msg));
			ee()->email->Send();

			$message = lang('mbr_membership_instructions_email');
		}
		elseif (ee()->config->item('req_mbr_activation') == 'manual')
		{
			$message = lang('mbr_admin_will_activate');
		}
		elseif($this->_param('admin_register') != 'yes')
		{
			// Kill old sessions
			ee()->session->delete_old_sessions();

			ee()->session->gc_probability = 100;

			/**	----------------------------------------
			/**	Log user in
			/**	----------------------------------------*/

			$expire = 60*60*24*182;

			//--------------------------------------------
			//	As of 2.2.0, auth is different
			//--------------------------------------------

			$member 	= ee()->db->get_where('members', array('member_id' => $member_id));

			$session 	= new Auth_result($member->row());
			if ($this->ee_version >= '2.4.0') { $session->remember_me(60*60*24*182); }
			$session->start_session();

			// Update system stats
			ee()->load->library('stats');

			if ( ! $this->check_no(ee()->config->item('enable_online_user_tracking')))
			{
				ee()->stats->update_stats();
			}


			$message = lang('mbr_your_are_logged_in');
		}

		/** --------------------------------------------
		/**  Welcome Email!
		/** --------------------------------------------*/

		if (ee()->config->item('req_mbr_activation') == 'manual')
		{
			// Put in a Table and Send Later!

			ee()->db->query(
				ee()->db->insert_string(
					'exp_user_welcome_email_list',
					array(
						'member_id' => $member_id,
						'group_id' => $this->insert_data['group_id']
					)
				)
			);
		}
		elseif ( ee()->config->item('req_mbr_activation') != 'email')
		{
			$this->insert_data['member_id'] = $member_id;
			$this->welcome_email($this->insert_data);
		}

		/**	----------------------------------------
		/**	 Override Return
		/**	----------------------------------------*/

		if ( $this->_param('override_return') !== FALSE AND
			 $this->_param('override_return') != '' AND
			 $this->is_ajax_request() === FALSE)
		{
			ee()->functions->redirect( $this->_param('override_return') );
			exit;
		}

		/**	----------------------------------------
		/**	Set return
		/**	----------------------------------------*/

		if ( ee()->input->get_post('return') !== FALSE AND
			 ee()->input->get_post('return') != '' )
		{
			$return	= ee()->input->get_post('return');
		}
		elseif ( ee()->input->get_post('RET') !== FALSE AND
				 ee()->input->get_post('RET') != '' )
		{
			$return	= ee()->input->get_post('RET');
		}
		else
		{
			$return = ee()->config->item('site_url');
		}

		if ( preg_match( "/".LD."\s*path=(.*?)".RD."/", $return, $match ) )
		{
			$return	= ee()->functions->create_url( $match['1'] );
		}

		// --------------------------------------------
		//  AJAX Response
		// --------------------------------------------

		if ($this->is_ajax_request())
		{
			$this->send_ajax_response(array(
				'success' => TRUE,
				'heading' => lang('user_successful_submission'),
				'message' => lang('mbr_registration_completed')."\n\n".$message,
				'content' => lang('mbr_registration_completed')."\n\n".$message
			));
		}

		/**	----------------------------------------
		/**	Return
		/**	----------------------------------------*/

		$return	= $this->_chars_decode( $return );

		if ( $remote === FALSE)
		{
			ee()->functions->redirect( $return );
		}
	}
	// END reg

	// --------------------------------------------------------------------

	/**
	 *	Logout
	 *
	 *	@access		public
	 *	@param		bool
	 *	@return		string
	 */

	public function logout ()
	{
		$qs			= (ee()->config->item('force_query_string') == 'y') ? '' : '?';
		$action_id	= ee()->functions->fetch_action_id('User', 'do_logout');

		//----------------------------------------
		//	Do we have a return?
		//----------------------------------------

		$return	= ( ee()->TMPL->fetch_param('return') !== FALSE AND ee()->TMPL->fetch_param('return') != '' ) ? ee()->TMPL->fetch_param('return'): ee()->uri->uri_string;

		$return	= str_replace( '&#47;', '/', $return );

		//----------------------------------------
		//	Prepare data
		//----------------------------------------

		$swap = array(
			'url'	=> ee()->functions->fetch_site_index(0, 0) .
				$qs.'ACT='.$action_id.'&return='.urlencode( $return ),
		);

		//----------------------------------------
		//	Parse
		//----------------------------------------

		$tagdata	= ee()->functions->prep_conditionals( ee()->TMPL->tagdata, $swap );

		foreach ( $swap as $key => $val )
		{
			$tagdata	= str_replace( LD.$key.RD, $val, $tagdata );
		}

		return $tagdata;
	}

	//	End logout

	// --------------------------------------------------------------------

	/**
	 *	Do Logout
	 *
	 *	@access		public
	 *	@param		bool
	 *	@return		string
	 */

	public function do_logout ()
	{
		// Kill the session and cookies
		ee()->db->where('site_id', ee()->config->item('site_id'));
		ee()->db->where('ip_address', ee()->input->ip_address());
		ee()->db->where('member_id', ee()->session->userdata('member_id'));
		ee()->db->delete('online_users');

		ee()->session->destroy();
		$this->set_cookie('read_topics');

		/* -------------------------------------------
		/* 'user_logout' hook.
		/*  - Perform additional actions after logout
		*/
			$edata = ee()->extensions->call('user_logout');
			if (ee()->extensions->end_script === TRUE) return;
		/*
		/* -------------------------------------------*/

		//----------------------------------------
		//	Do we have a return?
		//----------------------------------------

		$return	= ee()->functions->create_url('');

		if ( ee()->input->get_post('return') !== FALSE )
		{
			$return	= urldecode( ee()->input->get_post('return') );
		}

		if ( strpos( $return, 'http' ) === FALSE )
		{
			$return 	= ee()->functions->create_url( $return );
		}

		//----------------------------------------
		//	Prep data
		//----------------------------------------

		$data = array(	'title' 	=> lang('mbr_login'),
						'heading'	=> lang('thank_you'),
						'content'	=> lang('mbr_you_are_logged_out'),
						'redirect'	=> $return,
						'link'		=> array($return, stripslashes(ee()->config->item('site_name')))
					 );

		ee()->output->show_message($data);
	}

	//	End do logout

	// --------------------------------------------------------------------

	/**
	 *	send email for mailing list
	 *
	 *	@access		private
	 *	@param		string 	list title for mailing list
	 *	@param		string 	email to send it to
	 */

	private function mailing_list_email($list_title, $email, $code)
	{
		ee()->lang->loadfile('mailinglist');

		$qs 		= (ee()->config->item('force_query_string') == 'y') ? '' : '?';
		$action_id  = ee()->functions->fetch_action_id('Mailinglist', 'authorize_email');

		$swap = array(
			'activation_url'	=> ee()->functions->fetch_site_index(0, 0) .
									$qs.'ACT='.$action_id.'&id='.$code,
			'site_name'			=> stripslashes(ee()->config->item('site_name')),
			'site_url'			=> ee()->config->item('site_url'),
			'mailing_list'		=> $list_title
		);

		$template = ee()->functions->fetch_email_template(
			'mailinglist_activation_instructions'
		);

		$email_tit = ee()->functions->var_swap($template['title'], 	$swap);
		$email_msg = ee()->functions->var_swap($template['data'], 	$swap);

		//----------------------------------------
		//	Send email
		//----------------------------------------

		ee()->load->library('email');

		ee()->email->initialize();
		ee()->email->wordwrap = true;
		ee()->email->mailtype = 'plain';
		ee()->email->priority = '3';

		ee()->email->from(
			ee()->config->item('webmaster_email'),
			ee()->config->item('webmaster_name')
		);

		ee()->email->to($email);
		ee()->email->subject($email_tit);
		ee()->email->message($email_msg);
		ee()->email->Send();
	}
	//END mailing_list_email


	// --------------------------------------------------------------------

	/**
	 *	notify admin of mailing list joining
	 *
	 *	@access		private
	 *	@param		string 	list title for mailing list
	 *	@param		string 	email to send it to
	 */

	private function notify_mailinglist_admin($list_title, $email)
	{
		if (ee()->config->item('mailinglist_notify') == 'y' AND
			ee()->config->item('mailinglist_notify_emails') != '')
		{
			$swap = array(
				'email'			=> $email,
				'mailing_list' 	=> $list_title
			);

			$template  = ee()->functions->fetch_email_template('admin_notify_mailinglist');
			$email_tit = ee()->functions->var_swap($template['title'], $swap);
			$email_msg = ee()->functions->var_swap($template['data'], $swap);

			/** ----------------------------
			/**  Send email
			/** ----------------------------*/

			ee()->load->helper('string');
			// Remove multiple commas
			$notify_address = reduce_multiples(
				ee()->config->item('mailinglist_notify_emails'), ',', TRUE
			);

			if ($notify_address != '')
			{
				//----------------------------
				// Send email
				//----------------------------

				ee()->load->library('email');

				// Load the text helper
				ee()->load->helper('text');

				foreach (explode(',', $notify_address) as $addy)
				{
					ee()->email->EE_initialize();
					ee()->email->wordwrap = true;
					ee()->email->from(ee()->config->item('webmaster_email'), ee()->config->item('webmaster_name'));
					ee()->email->to($addy);
					ee()->email->reply_to(ee()->config->item('webmaster_email'));
					ee()->email->subject($email_tit);
					ee()->email->message(entities_to_ascii($email_msg));
					ee()->email->send();
				}
			}
		}
	}
	//END notify_mailinglist_admin


	// --------------------------------------------------------------------

	/**
	 *	Automatic Welcome Message for New Users!
	 *
	 *	@access		public
	 *	@param		array
	 *	@param		string
	 *	@return		bool
	 */

	public function welcome_email($row)
	{
		$wquery = ee()->db->query("SELECT preference_name, preference_value FROM exp_user_preferences
								   WHERE preference_name IN ('welcome_email_subject','welcome_email_content')");

		if ($wquery->num_rows() == 0)
		{
			return FALSE;
		}

		$subject = lang('welcome_email_content');
		$message = '';

		foreach($wquery->result_array() as $wrow)
		{
			if ($wrow['preference_name'] == 'welcome_email_subject')
			{
				$subject = stripslashes($wrow['preference_value']);
			}
			else
			{
				$message = stripslashes($wrow['preference_value']);
			}
		}

		if ($message == '')
		{
			return FALSE;
		}

		/**	----------------------------------------
		/**	Send email
		/**	----------------------------------------*/

		ee()->load->library('email');

		$swap = array(LD.'site_name'.RD		=> ee()->config->item('site_name'),
					  LD.'site_url'.RD		=> ee()->config->item('site_url'),
					  LD.'screen_name'.RD	=> $row['screen_name'],
					  LD.'email'.RD			=> $row['email'],
					  LD.'username'.RD		=> $row['username'],
					  LD.'member_id'.RD		=> $row['member_id']);

		$message = str_replace(array_keys($swap), array_values($swap), $message);

		ee()->load->helper('text');

		ee()->email->initialize();
		ee()->email->wordwrap = true;
		ee()->email->from(ee()->config->item('webmaster_email'), ee()->config->item('webmaster_name'));
		ee()->email->to($row['email']);
		ee()->email->subject($subject);
		ee()->email->message(entities_to_ascii($message));
		ee()->email->Send();

		ee()->db->query("DELETE FROM exp_user_welcome_email_list WHERE member_id = '".ee()->db->escape_str($row['member_id'])."'");

		return TRUE;
	}
	/* END welcome_message() */


	// --------------------------------------------------------------------

	/**
	 *	Member's Profile has Been Updated Email Routine
	 *
	 *	@access		private
	 *	@param		array
	 *	@param		array
	 *	@param		string
	 *	@param		string
	 *	@return		bool
	 */

	private function _member_update_email($old_data, $new_data, $emails, $message)
	{
		if (trim($message) == '' OR trim(str_replace(',', '', $emails)) == '') return FALSE;

		$swap = array(LD.'site_name'.RD		=> ee()->config->item('site_name'),
					  LD.'site_url'.RD		=> ee()->config->item('site_url'));

		$message = str_replace(array_keys($swap), array_values($swap), $message);

		/** --------------------------------------------
		/**  Fields that are Disallowed
		/** --------------------------------------------*/

		unset($old_data['password'], 		$new_data['password']);
		unset($old_data['last_activity'], 	$new_data['last_activity']);

		unset($old_data['salt'], $new_data['salt']);

		/** --------------------------------------------
		/**  Changed Values?
		/** --------------------------------------------*/

		$this->_mfields();

		if (preg_match_all("/".LD."changed(.*?)".RD."(.*?)".LD.'\/changed'.RD."/s", $message, $matches))
		{
			$changed = array();

			foreach($old_data as $key => $value)
			{
				if (isset($new_data[$key]) AND stripslashes($new_data[$key]) != $value)
				{
					$changed[$key] = $new_data[$key];
				}
			}

			/** --------------------------------------------
			/**  Convert Dates to User Friendly Version
			/** --------------------------------------------*/

			$dates = array('last_activity');

			foreach($dates as $date)
			{
				if (isset($new_data[$date]))
				{
					$new_data[$date] = $this->human_time($new_data[$date]);

					if (isset($changed[$date]))
					{
						$changed[$date] = $new_data[$date];
					}
				}
			}

			/** --------------------------------------------
			/**  Replace!
			/** --------------------------------------------*/

			for ($j = 0; $j < count($matches['0']); $j++)
			{
				$result  = '';

				foreach($changed as $key => $value)
				{
					$content = $matches['2'][$j];

					if (stristr($key, 'm_field_id_') !== FALSE)
					{
						foreach($this->mfields AS $info)
						{
							if (str_replace('m_field_id_', '', $key) == $info['id'])
							{
								$new_data[$info['name']] = $value;

								$name = $info['label'];
							}
						}
					}
					else
					{
						if (lang($key) != FALSE AND lang($key) != '')
						{
							$name = lang($key);
						}
						else
						{
							// This will eventually have to be replaced with an array...
							$name = ucwords(str_replace('_', ' ', $key));
						}
					}

					$content = str_replace(LD.'name'.RD, $name, $content);
					$content = str_replace(LD.'value'.RD, $value, $content);

					$result .= $content;
				}

				$message = str_replace($matches[0][$j], $result, $message);
			}
		}

		/** --------------------------------------------
		/**  New Data Replace
		/** --------------------------------------------*/

		if (stristr($message, '{') !== FALSE)
		{
			foreach($new_data as $key => $value)
			{
				if (stristr($key, 'm_field_id_') !== FALSE)
				{
					foreach($this->mfields as $name => $info)
					{
						if (str_replace('m_field_id_', '', $key) == $info['id'])
						{
							$key = $info['label'];
						}
					}
				}

				$message = str_replace(LD.$key.RD, $value, $message);
			}
		}

		/** --------------------------------------------
		/**  Date of Email and Change
		/** --------------------------------------------*/

		$message = str_replace(LD.'update_date'.RD, $this->human_time(ee()->localize->now), $message);

		/**	----------------------------------------
		/**	Parse dates
		/**	----------------------------------------*/

		foreach (array('update_date' => ee()->localize->now) as $key => $val)
		{
			if (preg_match("/".LD.$key."\s+format=[\"'](.*?)[\"']".RD."/s", $message, $match))
			{
				$str	= $match['1'];

				$codes	= $this->fetch_date_params( $match['1'] );

				foreach ( $codes as $code )
				{
					$str	= str_replace( $code, $this->convert_timestamp( $code, $val, TRUE ), $str );
				}

				$message	= str_replace( $match[0], $str, $message );
			}
		}

		/**	----------------------------------------
		/**	Send email
		/**	----------------------------------------*/

		ee()->load->library('email');

		ee()->load->helper('text');
		ee()->load->helper('string');

		ee()->email->initialize();
		ee()->email->wordwrap = true;
		ee()->email->from(ee()->config->item('webmaster_email'), ee()->config->item('webmaster_name'));
		ee()->email->to(reduce_multiples($emails, ',', TRUE));
		ee()->email->subject(lang('member_update'));
		ee()->email->message(entities_to_ascii($message));
		ee()->email->Send();

		return TRUE;
	}
	/* END welcome_message() */

	// --------------------------------------------------------------------

	/**
	 *	The Validate Members functionality for the CP Hook
	 *
	 *	@access		public
	 *	@param		array
	 *	@return		string
	 */

	public function cp_validate_members($member_ids = array())
	{
		if (count($member_ids) == 0)
		{
			return;
		}

		/** --------------------------------------------
		/**  Retrieve Member Data
		/** --------------------------------------------*/

		$query = ee()->db->query("SELECT member_id, group_id, email, screen_name, username
							 FROM exp_members
							 WHERE member_id IN ('".implode("','", ee()->db->escape_str($member_ids))."')");

		if ($query->num_rows() == 0)
		{
			return;
		}

		/** --------------------------------------------
		/**  Find Activation Groups
		/** --------------------------------------------*/

		if (ee()->db->table_exists('exp_user_activation_group'))
		{
			$aquery = ee()->db->query("SELECT group_id, member_id FROM exp_user_activation_group
								  WHERE member_id IN ('".implode("','", ee()->db->escape_str($member_ids))."')
								  AND group_id != 0");

			foreach($aquery->result_array() as $row)
			{
				ee()->db->query("UPDATE exp_members
							SET group_id = '".ee()->db->escape_str($row['group_id'])."'
							WHERE member_id = '".ee()->db->escape_str($row['member_id'])."'");
			}

			ee()->db->query("DELETE FROM exp_user_activation_group
						WHERE member_id IN ('".implode("','", ee()->db->escape_str($member_ids))."')");
		}

		ee()->stats->update_member_stats();

		return TRUE;
	}
	/* END cp_validate_members() */

	// --------------------------------------------------------------------

	/**
	 *	Member Self Activation Processing
	 *
	 *	ACTION Method
	 *
	 *	@access		public
	 *	@return		string
	 */

	public function activate_member()
	{
		/** ----------------------------------------
		/**  Fetch the site name and URL
		/** ----------------------------------------*/

		if (ee()->input->get_post('r') == 'f')
		{
			if (ee()->input->get_post('board_id') !== FALSE AND
				is_numeric(ee()->input->get_post('board_id')))
			{
				$query	= ee()->db->query(
					"SELECT board_forum_url, board_id, board_label
					 FROM 	exp_forum_boards
					 WHERE 	board_id = '" . ee()->db->escape_str(
						 ee()->input->get_post('board_id')
					 ) . "'"
				);
			}
			else
			{
				$query	= ee()->db->query(
					"SELECT board_forum_url, board_id, board_label
					 FROM 	exp_forum_boards
					 WHERE 	board_id = '1'"
				);
			}

			$site_name	= $query->row('board_label');
			$return		= $query->row('board_forum_url');
		}
		else
		{
			$return 	= ee()->functions->fetch_site_index();
			$site_name 	= (ee()->config->item('site_name') == '') ?
							lang('back') :
							stripslashes(ee()->config->item('site_name'));
		}

		//----------------------------------------
		// No ID?  Tisk tisk...
		//----------------------------------------

		$id  = ee()->input->get_post('id');

		if ($id == FALSE)
		{
			ee()->output->show_message(array(
				'title' 	=> lang('mbr_activation'),
				'heading'	=> lang('error'),
				'content'	=> lang('invalid_url'),
				'link'		=> array($return, $site_name)
			));
		}

		//----------------------------------------
		// Set the member group
		//----------------------------------------

		$group_id = ee()->config->item('default_member_group');

		// Is there even an account for this particular user?
		$query = ee()->db->query(
			"SELECT member_id, group_id, email, screen_name, username
			 FROM 	exp_members
			 WHERE 	authcode = '".ee()->db->escape_str($id)."'"
		);

		if ($query->num_rows() == 0)
		{
			ee()->output->show_message(array(
				'title' 	=> lang('mbr_activation'),
				'heading'	=> lang('error'),
				'content'	=> lang('mbr_problem_activating'),
				'link'		=> array($return, $site_name)
			));
		}

		$member_id = $query->row('member_id');

		if (ee()->input->get_post('mailinglist') !== FALSE AND
			is_numeric(ee()->input->get_post('mailinglist')))
		{
			$expire = time() - (60*60*48);

			ee()->db->query(
				"DELETE FROM 	exp_mailing_list_queue
				 WHERE 			date < '$expire' "
			);

			$results = ee()->db->query(
				"SELECT authcode
				 FROM 	exp_mailing_list_queue
				 WHERE 	email = '".ee()->db->escape_str($query->row('email'))."'
				 AND 	list_id = '".ee()->db->escape_str(
					 ee()->input->get_post('mailinglist')
				 ) . "'"
			);

			if ($results->num_rows() > 0)
			{
				//put into db
				ee()->db->query(
					"INSERT INTO exp_mailing_list (user_id, list_id, authcode, email)
					 VALUES 	 ('', '" .
						ee()->db->escape_str(ee()->input->get_post('mailinglist')) . "', '" .
						ee()->db->escape_str($results->row('authcode')) . "', '" .
						ee()->db->escape_str($query->row('email')) .
					 "')"
				);

				//remove queues
				ee()->db->query(
					"DELETE FROM exp_mailing_list_queue
					 WHERE 		 authcode = '" .
						ee()->db->escape_str($results->row('authcode')) . "'"
				);

				//get title
				$title_query = ee()->db->query(
					"SELECT list_title
					 FROM 	exp_mailing_lists
					 WHERE 	list_id = '" . ee()->db->escape_str(
						 ee()->input->get_post('mailinglist')
					) . "'"
				);

				//notify admin of mebmer joining
				$this->notify_mailinglist_admin(
					$title_query->row('list_title'),
					$query->row('email')
				);
			}
		}

		/** --------------------------------------------
		/**  User Specific for Email Activation!
		/** --------------------------------------------*/

		if (ee()->db->table_exists('exp_user_activation_group'))
		{
			$aquery = ee()->db->query(
				"SELECT group_id
				 FROM 	exp_user_activation_group
				 WHERE 	member_id = '" . ee()->db->escape_str($member_id) . "'"
			);

			if ($aquery->num_rows() > 0 AND $aquery->row('group_id') != 0)
			{
				$group_id = $aquery->row('group_id');
			}
		}

		// If the member group hasn't been switched we'll do it.

		if ($query->row('group_id') != $group_id)
		{
			ee()->db->query("UPDATE exp_members SET group_id = '".ee()->db->escape_str($group_id)."' WHERE authcode = '".ee()->db->escape_str($id)."'");
		}

		ee()->db->query("UPDATE exp_members SET authcode = '' WHERE authcode = '$id'");

		/** --------------------------------------------
		/**  Welcome Email of Doom and Despair
		/** --------------------------------------------*/

		$this->welcome_email($query->row_array());

		// -------------------------------------------
		// 'member_register_validate_members' hook.
		//  - Additional processing when member(s) are self validated
		//  - Added 1.5.2, 2006-12-28
		//  - $member_id added 1.6.1
		//
		//  - We leave this in here for the User module, just in case other extensions exist!
		//
		if (ee()->extensions->active_hook('member_register_validate_members') === TRUE)
		{
			$edata = ee()->extensions->universal_call('member_register_validate_members', $member_id);
			if (ee()->extensions->end_script === TRUE) return;
		}
		//
		// -------------------------------------------

	   // Upate Stats

		ee()->stats->update_member_stats();

		/** ----------------------------------------
		/**  Show success message
		/** ----------------------------------------*/

		$data = array(	'title' 	=> lang('mbr_activation'),
						'heading'	=> lang('thank_you'),
						'content'	=> lang('mbr_activation_success')."\n\n".lang('mbr_may_now_log_in'),
						'link'		=> array($return, $site_name)
					 );

		ee()->output->show_message($data);
	}
	/* END activate_member() */


	// --------------------------------------------------------------------

	/**
	 *	Remote Login
	 *
	 *	Allows One to Login Someone During a Form Submission
	 *	- In EE 2.4, they abstracted the login methods a bit.  We will not modify this method
	 *	too much until any problems are known as it is not widely used.
	 *
	 *	@access		public
	 *	@return		string
	 */

	public function _remote_login()
	{
		/** ----------------------------------------
		/**  Is user already logged in?
		/** ----------------------------------------*/

		if ( ee()->session->userdata['member_id'] != 0 )
		{
			return;
		}

		/** ----------------------------------------
		/**  Is user banned?
		/** ----------------------------------------*/

		if (ee()->session->userdata['is_banned'] == TRUE)
		{
			return $this->_output_error('general', array(lang('not_authorized')));
		}

		ee()->lang->loadfile('login');

		/** ----------------------------------------
		/**  Error trapping
		/** ----------------------------------------*/

		$errors = array();

		/** ----------------------------------------
		/**  No username/password?  Bounce them...
		/** ----------------------------------------*/

		if ( ! ee()->input->get('multi') AND
			 ( ! ee()->input->post('username') OR ! ee()->input->post('password')))
		{
			return $this->_output_error('submission', array(lang('mbr_form_empty')));
		}

		//--------------------------------------------
		//	2.2.x+ needs auth lib
		//--------------------------------------------

		ee()->load->library('auth');

		/** ----------------------------------------
		/**  Is IP and User Agent required for login?
		/** ----------------------------------------*/

		if (ee()->config->item('require_ip_for_login') == 'y')
		{
			if (ee()->session->userdata['ip_address'] == '' OR
				ee()->session->userdata['user_agent'] == '')
			{
				return $this->_output_error('general', array(lang('unauthorized_request')));
			}
		}

		/** ----------------------------------------
		/**  Check password lockout status
		/** ----------------------------------------*/

		if (ee()->session->check_password_lockout() === TRUE)
		{
			$line = lang('password_lockout_in_effect');

			$line = str_replace("%x", ee()->config->item('password_lockout_interval'), $line);

			return $this->_output_error('general', array($line));
		}

		/** ----------------------------------------
		/**  Fetch member data
		/** ----------------------------------------*/

		if ( ee()->input->get('multi') === FALSE )
		{
			$sql = "SELECT 	exp_members.*
					FROM   	exp_members, exp_member_groups
					WHERE  	username = '".ee()->db->escape_str(ee()->input->post('username'))."'
					AND    	exp_members.group_id = exp_member_groups.group_id";

			if ( ee()->config->item('site_id') !== FALSE )
			{
				$sql	.= " AND exp_member_groups.site_id = '".ee()->db->escape_str(ee()->config->item('site_id'))."'";
			}

			$query = ee()->db->query($sql);

		}
		else
		{
			if (ee()->config->item('allow_multi_logins') == 'n' OR
				! ee()->config->item('multi_login_sites') OR
				ee()->config->item('multi_login_sites') == '')
			{
				return $this->_output_error('general', array(lang('not_authorized')));
			}

			// Current site in list.  Original login site.
			if (ee()->input->get('cur') === FALSE OR ee()->input->get('orig') === FALSE)
			{
				return $this->_output_error('general', array(lang('not_authorized')));
			}

			// Kill old sessions first

			ee()->session->gc_probability = 100;

			ee()->session->delete_old_sessions();

			// Set cookie expiration to one year if the "remember me" button is clicked

			$expire = ( ! isset($_POST['auto_login'])) ? '0' : 60*60*24*365;

			// Check Session ID

			$sql = "SELECT 	exp_members.*
					FROM   	exp_sessions,
							exp_members
					WHERE  	exp_sessions.session_id  = '".ee()->db->escape_str(ee()->input->get('multi'))."'
					AND		exp_sessions.member_id = exp_members.member_id
					AND    	exp_sessions.last_activity > $expire";

			if ( ee()->config->item('site_id') !== FALSE )
			{
				$sql	.= " AND exp_member_groups.site_id = '".ee()->db->escape_str(ee()->config->item('site_id'))."'";
			}

			$query	= ee()->db->query( $sql );

			if ($query->num_rows() == 0)
				return;

			// -------------------------------------------
			// 'member_member_login_multi' hook.
			//  - Additional processing when a member is logging into multiple sites
			//
			if (ee()->extensions->active_hook('member_member_login_multi') === TRUE)
			{
				$edata = ee()->extensions->universal_call('member_member_login_multi', $query->row);
				if (ee()->extensions->end_script === TRUE) return;
			}
			//
			// -------------------------------------------

			// Check if there are any more sites to log into

			$sites	= explode('|',ee()->config->item('multi_login_sites'));
			$next	= (ee()->input->get('cur') + 1 != ee()->input->get('orig')) ?
						ee()->input->get('cur') + 1 :
						ee()->input->get('cur') + 2;

			if ( ! isset($sites[$next]))
			{
				// We're done.
				$data = array(
					'title' 	=> lang('mbr_login'),
					'heading'	=> lang('thank_you'),
					'content'	=> lang('mbr_you_are_logged_in'),
					'redirect'	=> $sites[ee()->input->get('orig')],
					'link'		=> array($sites[ee()->input->get('orig')], lang('back'))
				);

				ee()->output->show_message($data);
			}
			else
			{
				// Next Site

				$next_url = $sites[$next].'?ACT='.ee()->functions->fetch_action_id('Member', 'member_login').
							'&multi='.ee()->input->get('multi').'&cur='.$next.'&orig='.ee()->input->get_post('orig');

				return ee()->functions->redirect($next_url);
			}
		}

		/** ----------------------------------------
		/**  Invalid Username
		/** ----------------------------------------*/

		if ($query->num_rows() == 0)
		{
			ee()->session->save_password_lockout();

			return $this->_output_error('submission', array(lang('no_username')));
		}

		/** ----------------------------------------
		/**  Is the member account pending?
		/** ----------------------------------------*/

		if ($query->row('group_id') == 4)
		{
			return $this->_output_error('general', array(lang('mbr_account_not_active')));
		}

		// ----------------------------------------
		//  Check password
		// ----------------------------------------

		$passwd = ee()->auth->hash_password(stripslashes(ee()->input->post('password')), $query->row('salt'));

		if ( ! isset($passwd['salt']) OR ($passwd['password'] != $query->row('password')))
		{
			ee()->session->save_password_lockout();

			$errors[] = lang('no_password');
		}

		/** --------------------------------------------------
		/**  Do we allow multiple logins on the same account?
		/** --------------------------------------------------*/

		if (ee()->config->item('allow_multi_logins') == 'n')
		{
			// Kill old sessions first

			ee()->session->gc_probability = 100;

			ee()->session->delete_old_sessions();

			$expire = time() - ee()->session->session_length;

			// See if there is a current session

			$sql = "SELECT ip_address, user_agent FROM exp_sessions
					WHERE  member_id  = '".$query->row('member_id')."'
					AND    last_activity > $expire";

			if ( ee()->config->item('site_id') !== FALSE )
			{
				$sql	.= " AND site_id = '".ee()->db->escape_str(ee()->config->item('site_id'))."'";
			}

			$result	= ee()->db->query( $sql );

			// If a session exists, trigger the error message

			if ($result->num_rows() == 1)
			{
				if (ee()->session->userdata['ip_address'] != $result->row('ip_address') OR
					ee()->session->userdata['user_agent'] != $result->row('user_agent') )
				{
					$errors[] = lang('multi_login_warning');
				}
			}
		}

		/** ----------------------------------------
		/**  Are there errors to display?
		/** ----------------------------------------*/

		if (count($errors) > 0)
		{
			return $this->_output_error('submission', $errors);
		}

		/** ----------------------------------------
		/**  Set cookies
		/** ----------------------------------------*/

		// Set cookie expiration to one year if the "remember me" button is clicked

		$expire = ( ! isset($_POST['auto_login'])) ? '0' : 60*60*24*365;

		if (version_compare($this->ee_version, '2.6.0', '<'))
		{
			$this->set_cookie(ee()->session->c_expire , time()+$expire, $expire);
			$this->set_cookie(ee()->session->c_uniqueid , $query->row('unique_id'), $expire);
		}

		$member 	= ee()->db->get_where('members', array('member_id' => $query->row('member_id')));

		$session 	= new Auth_result($member->row());
		if ($this->ee_version >= '2.4.0') { $session->remember_me(60*60*24*182); }
		$session->start_session();

		// Update system stats
		ee()->load->library('stats');

		if ( ! $this->check_no(ee()->config->item('enable_online_user_tracking')))
		{
			ee()->stats->update_stats();
		}


		// Does the user want to remain anonymous?

		$anon = (ee()->input->post('anon') == 1) ? '' : 'y';

		if ( $anon == 'y')
		{
			$this->set_cookie(ee()->session->c_anon , 1,  $expire);
		}
		else
		{
			$this->set_cookie(ee()->session->c_anon);
		}

		/** ----------------------------------------
		/**  Create a new session
		/** ----------------------------------------*/

		ee()->session->create_new_session($query->row('member_id'));

		/** ----------------------------------------
		/**  Populate session
		/** ----------------------------------------*/

		ee()->session->userdata['username']		= $query->row('username');
		ee()->session->userdata['screen_name']	= $query->row('screen_name');
		ee()->session->userdata['email']		= $query->row('email');
		ee()->session->userdata['url']			= $query->row('url');
		ee()->session->userdata['location']		= $query->row('location');

		/** ----------------------------------------
		/**  Update stats
		/** ----------------------------------------*/

		$cutoff		= ee()->localize->now - (15 * 60);

		$sql		= "DELETE FROM 	exp_online_users
					   WHERE 		(ip_address = 'ee()->input->ip_address()'
									AND	member_id = '0')
					   OR 			date < $cutoff";

		if ( ee()->config->item('site_id') !== FALSE )
		{
			$sql	.= " AND site_id = '".ee()->db->escape_str(ee()->config->item('site_id'))."'";
		}

		ee()->db->query( $sql );

		$data = array(
			'member_id'		=> ee()->session->userdata('member_id'),
			'name'			=> (ee()->session->userdata['screen_name'] == '') ?
								ee()->session->userdata['username'] :
								ee()->session->userdata['screen_name'],
			'ip_address'	=> ee()->input->ip_address(),
			'date'			=> ee()->localize->now,
			'anon'			=> $anon
		);

		if ( ee()->config->item('site_id') !== FALSE )
		{
			$data['site_id']	= ee()->config->item('site_id');
		}

		/** ----------------------------------------
		/**  Delete old password lockouts
		/** ----------------------------------------*/

		ee()->session->delete_password_lockout();
	}

	/* END remote login */

	// --------------------------------------------------------------------

	/**
	 *	Remote Register
	 *
	 *	Allows Person to Be Registered During a Form Submission
	 *
	 *	@access		public
	 *	@return		null
	 */

	public function _remote_register()
	{

		/** ----------------------------------------
		/**	Is user already logged in?
		/** ----------------------------------------*/

		if ( ee()->session->userdata['member_id'] != 0 )
		{
			return;
		}

		/** ----------------------------------------
		/**	Is user banned?
		/** ----------------------------------------*/

		if (ee()->session->userdata['is_banned'] == TRUE)
		{
			return $this->_output_error('general', array(lang('not_authorized')));
		}

		/**	----------------------------------------
		/**	Is immediate registration enabled?
		/**	----------------------------------------
		/*	There can be many many permutations on
		/*	this because of the different
		/*	registration types and approval processes
		/*	and such.
		/*	If immediate registration is not enabled,
		/*	we're going to not allow this process.
		/**	----------------------------------------*/

		if ( ee()->config->item('req_mbr_activation') == 'manual' OR
			 ee()->config->item('req_mbr_activation') == 'email' )
		{
			return $this->_output_error('general', array(lang('wrong_reg_mode')));
		}

		/** ----------------------------------------
		/**	Invoke the reg function and pray
		/** ----------------------------------------*/

		$this->reg( TRUE );
	}

	/* END remote register */


	// --------------------------------------------------------------------

	/**
	 *	Search Form
	 *
	 *	@access		public
	 *	@return		string
	 */

	public function search()
	{
		/** ----------------------------------------
		/**  Fetch ID number
		/** ----------------------------------------
		/*	We want to repopulate our search field with the pervious searched data in case
		/*	the search form and results occupy the same page.
		/** ----------------------------------------*/

		$search_id	= '';

		foreach ( ee()->uri->segments as $seg )
		{
			if ( strlen($seg) >= 32 )
			{
				$search_id	= $seg;
			}
		}

		if ( strlen( $search_id ) > 32 )
		{
			$search_id = substr( $search_id, 0, 32 );
			$this->cur_page  = substr( $search_id, 32 );
		}

		/** ----------------------------------------
		/**	Check DB
		/** ----------------------------------------*/

		$fields		= array();
		$cfields	= array();
		$keywords	= '';

		if ( $search_id != '' )
		{
			$query	= ee()->db->query( "SELECT `keywords`, `categories`, `fields`, `cfields` FROM exp_user_search WHERE search_id = '".ee()->db->escape_str( $search_id )."'" );

			if ( $query->num_rows() > 0 )
			{
				$this->assigned_cats	= unserialize( $query->row('categories') );
				$fields					= unserialize( $query->row('fields') );
				$cfields				= unserialize( $query->row('cfields') );
				$keywords				= $query->row('keywords');
			}
		}

		/**	----------------------------------------
		/**	Userdata
		/**	----------------------------------------*/

		$tagdata	= ee()->TMPL->tagdata;

		/**	----------------------------------------
		/**	Handle categories
		/**	----------------------------------------*/

		$user_parent_category	= '';
		$user_category			= '';

		if ( count( $this->assigned_cats ) > 0 )
		{
			$catq	= ee()->db->query( "SELECT cat_id, parent_id FROM exp_categories WHERE cat_id IN ('".implode( "','", $this->assigned_cats )."')" );

			foreach ( $catq->result_array() as $row )
			{
				if ( $row['parent_id'] == 0 )
				{
					$user_parent_category	= $row['cat_id'];
				}
				else
				{
					$user_category			= $row['cat_id'];
				}
			}
		}

		$tagdata	= str_replace( LD.'user_parent_category'.RD, $user_parent_category, $tagdata );
		$tagdata	= str_replace( LD.'user_category'.RD, $user_category, $tagdata );

		/**	----------------------------------------
		/**	Sniff for checkboxes
		/**	----------------------------------------*/

		$checks			= '';
		$custom_checks	= '';

		if ( preg_match_all( "/name=['|\"]?(\w+)['|\"]?/", $tagdata, $match ) )
		{
			$this->_mfields();

			foreach ( $match['1'] as $m )
			{
				if ( in_array( $m, $this->check_boxes ) )
				{
					$checks	.= $m."|";
				}

				if ( isset( $this->mfields[ $m ] ) AND $this->mfields[ $m ]['type'] == 'select' )
				{
					$custom_checks	.= $m."|";
				}
			}
		}

		/**	----------------------------------------
		/**	Handle categories
		/**	----------------------------------------*/

		$tagdata	= $this->_categories( $tagdata );

		/**	----------------------------------------
		/**	Handle standard fields
		/**	----------------------------------------*/

		$standard	= array_merge( array('username', 'email', 'screen_name' ), $this->standard );

		foreach ( $standard as $key )
		{
			if ( isset( $fields[$key] ) === TRUE )
			{
				$tagdata	= str_replace( LD.$key.RD, $fields[$key], $tagdata );
			}
			else
			{
				$tagdata	= str_replace( LD.$key.RD, "", $tagdata );
			}
		}

		/**	----------------------------------------
		/**	Handle custom fields
		/**	----------------------------------------*/

		foreach ( $this->_mfields() as $key => $val )
		{
			if ( isset( $cfields['m_field_id_'.$val['id']] ) === TRUE )
			{
				$tagdata	= str_replace( LD.$key.RD, $cfields['m_field_id_'.$val['id']], $tagdata );
			}
			else
			{
				$tagdata	= str_replace( LD.$key.RD, "", $tagdata );
			}
		}

		/**	----------------------------------------
		/**	Keywords
		/**	----------------------------------------*/

		$tagdata	= str_replace( LD."keywords".RD, $keywords, $tagdata );

		/**	----------------------------------------
		/**	Prep data
		/**	----------------------------------------*/

		$this->form_data['tagdata']					= $tagdata;

		$this->form_data['ACT']						= ee()->functions->fetch_action_id('User', 'do_search');

		$this->form_data['RET']						= (isset($_POST['RET'])) ? $_POST['RET'] : ee()->functions->fetch_current_uri();

		$this->form_data['id']						= ( ee()->TMPL->fetch_param('form_id') ) ? ee()->TMPL->fetch_param('form_id'): '';

		$this->form_data['class']					= ( ee()->TMPL->fetch_param('form_class') ) ? ee()->TMPL->fetch_param('form_class'): '';

		$this->form_data['skip_field']				= ( ee()->TMPL->fetch_param('skip_field') ) ? ee()->TMPL->fetch_param('skip_field'): '';

		$this->form_data['group_id']					= ( ee()->TMPL->fetch_param('group_id') ) ? ee()->TMPL->fetch_param('group_id'): '';

		$this->form_data['return']					= ( ee()->TMPL->fetch_param('return') ) ? ee()->TMPL->fetch_param('return') : '';

		$this->form_data['inclusive_categories']		= ( ee()->TMPL->fetch_param('inclusive_categories') ) ? ee()->TMPL->fetch_param('inclusive_categories'): '';

		$this->params['checks']					= $checks;

		$this->params['custom_checks']			= $custom_checks;

		$this->params['search_id']				= $search_id;

		if ( ee()->TMPL->fetch_param('form_name') !== FALSE AND ee()->TMPL->fetch_param('form_name') != '' )
		{
			$this->form_data['name']					= ee()->TMPL->fetch_param('form_name');
		}

		$this->params['secure_action']			= ( ee()->TMPL->fetch_param('secure_action') !== FALSE) ? ee()->TMPL->fetch_param('secure_action'): 'no';

		/**	----------------------------------------
		/**	Return
		/**	----------------------------------------*/

		return $this->_form();
	}

	/* END search */


	// --------------------------------------------------------------------

	/**
	 *	Perform Search Processing
	 *
	 *	@access		public
	 *	@return		redirect
	 */

	public function do_search()
	{
		/**	----------------------------------------
		/**	Is user banned?
		/**	----------------------------------------*/

		if (ee()->session->userdata['is_banned'] == TRUE)
		{
			return $this->_output_error('general', array(lang('not_authorized')));
		}

		/**	----------------------------------------
		/**	Blacklist/Whitelist Check
		/**	----------------------------------------*/

		if (ee()->blacklist->blacklisted == 'y' AND ee()->blacklist->whitelisted == 'n')
		{
			return $this->_output_error('general', array(lang('not_authorized')));
		}

		/** ----------------------------------------
		/**  Update last activity
		/** ----------------------------------------*/

		$this->_update_last_activity();

		/** ----------------------------------------
		/**  Do we have a search results page?
		/** ----------------------------------------*/

		if ( ee()->input->post('return') === FALSE OR ee()->input->post('return') == '' )
		{
			if ( ee()->input->post('RET') !== FALSE AND ee()->input->post('RET') != '' )
			{
				$return	= ee()->input->post('RET');
			}
			else
			{
				return $this->_output_error('general', array(lang('search_path_error')));
			}
		}
		else
		{
			$return	= ee()->input->post('return');
		}

		/** ----------------------------------------
		/**  Is the current user allowed to search?
		/** ----------------------------------------*/

		if (ee()->session->userdata['can_search'] == 'n' AND ee()->session->userdata['group_id'] != 1)
		{
			return $this->_output_error('general', array(lang('search_not_allowed')));
		}

		/** ----------------------------------------
		/**  Flood control
		/** ----------------------------------------*/

		if (ee()->session->userdata['search_flood_control'] > 0 AND ee()->session->userdata['group_id'] != 1)
		{
			$cutoff = time() - ee()->session->userdata['search_flood_control'];

			$sql = "SELECT search_id FROM exp_user_search WHERE search_id != '' AND search_date > '{$cutoff}' AND ";

			if ( ee()->config->item('site_id') !== FALSE )
			{
				$sql .= "site_id = '".ee()->db->escape_str(ee()->config->item('site_id'))."' AND ";
			}

			if (ee()->session->userdata['member_id'] != 0)
			{
				$sql .= "(member_id='".ee()->db->escape_str(ee()->session->userdata('member_id'))."' OR ip_address='".ee()->db->escape_str(ee()->input->ip_address())."')";
			}
			else
			{
				$sql .= "ip_address='".ee()->db->escape_str(ee()->input->ip_address())."'";
			}

			$query = ee()->db->query($sql);

			$text = str_replace("%x", ee()->session->userdata['search_flood_control'], lang('search_time_not_expired'));

			if ($query->num_rows() > 0)
			{
				return $this->_output_error('general', array($text));
			}
		}

		/** ----------------------------------------
		/**	Prep group ids if needed
		/** ----------------------------------------*/

		$group_id	= '';

		if ( ee()->input->post('group_id') !== FALSE AND ee()->input->post('group_id') != '' )
		{
			$group_id	= ee()->input->post('group_id');
		}

		/** ----------------------------------------
		/**	Prep member ids if needed
		/** ----------------------------------------*/

		$member_ids	= array();

		if ( ee()->input->post('search_within_results') !== FALSE AND ee()->input->post('search_within_results') == 'yes' AND $this->_param('search_id') !== FALSE AND $this->_param('search_id') != '' )
		{
			$memq	= ee()->db->query("SELECT member_ids FROM exp_user_search WHERE search_id = '".ee()->db->escape_str( $this->_param('search_id') )."'");

			if ( $memq->row('member_ids') !== FALSE )
			{
				$member_ids	= unserialize( $memq->row('member_ids') );
			}
		}

		/** ----------------------------------------
		/**	Prep categories
		/** ----------------------------------------*/

		$categories	= array();

		if ( isset( $_POST['category'] ) !== FALSE )
		{
			if ( is_array( $_POST['category'] ) === TRUE )
			{
				$_POST['category']	= ee()->security->xss_clean( $_POST['category'] );

				foreach ( $_POST['category'] as $cat )
				{
					if (ctype_digit($cat))
					{
						$categories[] = $cat;
					}
				}
			}
			elseif ( ctype_digit( $_POST['category'] ) )
			{
				$categories[]	= ee()->security->xss_clean( $_POST['category'] );
			}
		}

		/** ----------------------------------------
		/**	Turn keywords into an array
		/** ----------------------------------------*/

		$exclude	= array();
		$terms		= array();

		if ( ( $keywords = ee()->input->post('keywords') ) !== FALSE )
		{
			$keywords = stripslashes($keywords);

			if ( preg_match_all( "/\-*\"(.*?)\"/", $keywords, $matches ) )
			{
				for( $m=0; $m < count( $matches['1'] ); $m++ )
				{
					$terms[]	= trim( str_replace( '"', '', $matches['0'][$m] ) );
					$keywords	= str_replace( $matches['0'][$m], '', $keywords );
				}
			}

			if ( trim( $keywords ) != '' )
			{
				$terms = array_merge( $terms, preg_split( "/\s+/", trim( $keywords ) ) );
			}

			$keywords	= array();

			foreach ( $terms as $val )
			{
				if ( substr( $val, 0, 1 ) == "-" )
				{
					$exclude[]	= substr( $val, 1 );
				}
				else
				{
					$keywords[]	= $val;
				}
			}
		}
		else
		{
			$keywords	= array();
		}

		/**	----------------------------------------
		/**	Set skip fields
		/**	----------------------------------------*/

		$skip	= array();

		if ( ee()->input->post('skip_field') !== FALSE AND ee()->input->post('skip_field') != '' )
		{
			$skip	= explode( "|", ee()->input->post('skip_field') );
		}

		/**	----------------------------------------
		/**	Assemble standard fields
		/**	----------------------------------------*/

		$this->standard	= array_merge( $this->standard, array( 'username', 'screen_name', 'email' ) );

		foreach ( $this->standard as $field )
		{
			if ( isset( $_POST[ $field ]) AND ! in_array( $field, $skip ) AND trim($_POST[ $field ]) !== '')
			{
				$this->insert_data[$field]	= trim($_POST[ $field ]);
			}
		}

		/**	----------------------------------------
		/**	Assemble checkbox fields
		/**	----------------------------------------*/

		if ( $this->_param('checks') != '' )
		{
			foreach ( explode( "|", $this->_param('checks') )  as $c )
			{
				if ( in_array( $c, $this->check_boxes ) )
				{
					if ( ee()->input->post($c) !== FALSE )
					{
						if ( stristr( ee()->input->post($c), 'n' ) )
						{
							$this->insert_data[$c]	= 'n';
						}
						else
						{
							$this->insert_data[$c]	= 'y';
						}
					}
					else
					{
						$this->insert_data[$c]	= 'n';
					}
				}
			}
		}

		$this->insert_data	= ee()->security->xss_clean( $this->insert_data );

		/**	----------------------------------------
		/**	Assemble custom fields
		/**	----------------------------------------*/

		$cfields	= array();

		foreach ( $this->_mfields() as $key => $val )
		{
			/**	----------------------------------------
			/**	Field named 'keywords'? Skip it.
			/**	----------------------------------------*/

			if ( $key == 'keywords' ) continue;

			/**	----------------------------------------
			/**	Handle empty checkbox fields
			/**	----------------------------------------*/

			if ( $this->_param( 'custom_checks' ) !== FALSE )
			{
				$arr	= explode( "|", $this->_param( 'custom_checks' ) );

				foreach ( $arr as $check )
				{
					// No idea what this is doing.  -Paul
					// $cfields['m_field_id_'.$val['id']]	= "";
				}
			}

			/**	----------------------------------------
			/**	Handle fields
			/**	----------------------------------------*/

			if ( isset( $_POST[ $key ] ) AND ! in_array( $key, $skip ) )
			{
				/**	----------------------------------------
				/**	Handle arrays
				/**	----------------------------------------*/

				if ( is_array( $_POST[ $key ] ) )
				{
					$cfields['m_field_id_'.$val['id']]	= implode( "\n", $_POST[ $key ] );
				}
				else
				{
					$cfields['m_field_id_'.$val['id']]	= $_POST[ $key ];
				}
			}
		}

		$cfields	= ee()->security->xss_clean( $cfields );

		/**	----------------------------------------
		/**	Start query
		/**	----------------------------------------*/

		$globalandor	= " AND";

		$sql			= "SELECT DISTINCT(m.member_id) FROM exp_members m";

		/**	----------------------------------------
		/**	Join for custom fields?
		/**	----------------------------------------*/

		if ( count( $cfields ) > 0 OR count( $keywords ) > 0 )
		{
			$sql	.= " LEFT JOIN exp_member_data md ON md.member_id = m.member_id";
		}

		/**	----------------------------------------
		/**	Join for categories
		/**	----------------------------------------*/

		if ( count( $categories ) > 0 )
		{
			$sql	.= " LEFT JOIN exp_user_category_posts ucp ON ucp.member_id = m.member_id";
		}

		/**	----------------------------------------
		/**	Where
		/**	----------------------------------------*/

		$sql	.= " WHERE m.member_id != 0";

		/**	----------------------------------------
		/**	Categories
		/**	----------------------------------------*/

		if ( count( $categories ) > 0 )
		{
			$sql	.= " AND (";

			foreach ( $categories as $cat )
			{
				$sql	.= " ucp.cat_id = '".ee()->db->escape_str( $cat )."' OR";
			}

			$sql	= substr( $sql, 0, -2 );

			$sql	.= ")";
		}

		/**	----------------------------------------
		/**	Group ids
		/**	----------------------------------------*/

		if ( $group_id != '' )
		{
			$sql	.= " ".ee()->functions->sql_andor_string( $group_id, 'm.group_id' );
		}

		/**	----------------------------------------
		/**	Standard fields
		/**	----------------------------------------*/

		if ( count( $this->insert_data ) > 0 )
		{
			$compare	= 'like';

			//allready doing this above
			//$this->insert_data	= ee()->security->xss_clean( $this->insert_data );

			$andor	= " AND";

			$sql	.= $globalandor." (";

			foreach ( $this->insert_data as $key => $val )
			{
				if ( $compare == 'like' )
				{
					$sql	.= " m.".$key." LIKE '%".ee()->db->escape_str( $val )."%'".$andor;
				}
				else
				{
					$sql	.= " m.".$key." = '".ee()->db->escape_str( $val )."'".$andor;
				}
			}

			$sql	= substr( $sql, 0, -( strlen( $andor ) ) );

			$sql	.= ")";
		}


		/**	----------------------------------------
		/**	Custom fields
		/**	----------------------------------------*/

		if ( count( $cfields ) > 0 )
		{
			$compare	= 'like';

			$cfields	= ee()->security->xss_clean( $cfields );

			$andor		= " AND";

			$sql		.= $globalandor." (";

			foreach ( $cfields as $key => $val )
			{
				if ( $compare == 'like' )
				{
					$sql	.= " md.".$key." LIKE '%".ee()->db->escape_str( $val )."%'".$andor;
				}
				else
				{
					$sql	.= " md.".$key." = '".ee()->db->escape_str( $val )."'".$andor;
				}
			}

			$sql		= substr( $sql, 0, -( strlen( $andor ) ) );

			$sql		.= ")";
		}

		/**	----------------------------------------
		/**	Keywords
		/**	----------------------------------------
		/*	This is where all the action is. It's
		/*	convoluted, but we'll try to make it more
		/*	simple than EE's regular search query.
		/**	----------------------------------------*/

		if ( count( $keywords ) > 0 )
		{
			/**	----------------------------------------
			/**	Clean
			/**	----------------------------------------*/

			$keywords	= ee()->security->xss_clean( $keywords );

			$andor		= " OR";

			/**	----------------------------------------
			/**	Start the wrapper
			/**	----------------------------------------*/

			$sql		.= $globalandor." (";

			/**	----------------------------------------
			/**	Check standard fields
			/**	----------------------------------------*/

			foreach ( $keywords as $keyword )
			{
				if (trim($keyword) == '') continue;

				foreach ( $this->standard as $val )
				{
					if (in_array($val, $skip)) continue;

					$sql	.= " m.".$val." LIKE '%".ee()->db->escape_str( $keyword )."%'".$andor."\n";
				}

				foreach ( $this->_mfields() as $key => $val )
				{
					if (in_array($key, $skip)) continue;

					$sql	.= " md.m_field_id_".$val['id']." LIKE '%".ee()->db->escape_str( $keyword )."%'".$andor."\n";
				}
			}

			/**	----------------------------------------
			/* END the wrapper
			/**	----------------------------------------*/

			$sql		= substr( $sql, 0, -( strlen( $andor ) ) );

			$sql		.= ")";
		}

		/**	----------------------------------------
		/**	Exclude
		/**	----------------------------------------*/

		if ( count( $exclude ) > 0 )
		{
			/**	----------------------------------------
			/**	Clean
			/**	----------------------------------------*/

			$exclude	= ee()->security->xss_clean( $exclude );

			$andor		= " OR";

			/**	----------------------------------------
			/**	Start the wrapper
			/**	----------------------------------------*/

			$sql		.= " AND (";

			/**	----------------------------------------
			/**	Check standard fields
			/**	----------------------------------------*/

			foreach ( $exclude as $ex )
			{
				if (trim($ex) == '') continue;

				foreach ( $this->standard as $val )
				{
					if (in_array($val, $skip)) continue;

					$sql	.= " m.".$val." NOT LIKE '%".ee()->db->escape_str( $ex )."%'".$andor."\n";
				}

				foreach ( $this->mfields as $key => $val )
				{
					if (in_array($key, $skip)) continue;

					$sql	.= " md.m_field_id_".$val['id']." NOT LIKE '%".ee()->db->escape_str( $keyword )."%'".$andor."\n";
				}
			}

			/**	----------------------------------------
			/* END the wrapper
			/**	----------------------------------------*/

			$sql		= substr( $sql, 0, -( strlen( $andor ) ) );

			$sql		.= ")";
		}

		/**	----------------------------------------
		/**	Member ids if we're within results
		/**	----------------------------------------*/

		if ( count( $member_ids ) > 0 )
		{
			$sql	.= " AND m.member_id IN ('".implode( "','", $member_ids )."')";
		}

		/**	----------------------------------------
		/**	Inclusive categories?
		/**	----------------------------------------*/

		$fail	= FALSE;

		if ( count( $categories ) > 1 AND ee()->input->post('inclusive_categories') !== FALSE AND ee()->input->post('inclusive_categories') == 'yes' )
		{
			$chosen	= array();

			$catq	= ee()->db->query( "SELECT member_id, cat_id FROM exp_user_category_posts WHERE cat_id IN ('".implode( "','", $categories )."')" );

			$mem_array	= array();

			foreach ( $catq->result_array() as $row )
			{
				$mem_array[ $row['cat_id'] ][]	= $row['member_id'];
			}

			if ( count( $mem_array) < 2 OR count( array_diff( $categories, array_keys( $mem_array ) ) ) > 0)
			{
				$fail	= TRUE;
			}
			else
			{
				$chosen = call_user_func_array('array_intersect', $mem_array);
			}

			if ( count( $chosen ) == 0)
			{
				$fail	= TRUE;
			}

			if ( count( $chosen ) > 0 )
			{
				$sql	.= " AND ucp.member_id IN ('".implode( "','", $chosen )."')";
			}
		}

		/**	----------------------------------------
		/**	Run?
		/**	----------------------------------------*/

		if ( $fail === FALSE )
		{
			/**	----------------------------------------
			/**	Run the poor query
			/**	----------------------------------------*/

			$this->query	= ee()->db->query( $sql );

			$ids	= array();

			foreach ( $this->query->result_array() as $row )
			{
				$ids[]	= $row['member_id'];
			}

			/**	----------------------------------------
			/**	Prep final query
			/**	----------------------------------------*/

			$sql	= "SELECT m.*, md.*, ( m.total_entries + m.total_comments ) AS total_combined_posts, mg.group_title, mg.group_description
					   FROM exp_members m
					   LEFT JOIN exp_member_data md ON md.member_id = m.member_id
					   LEFT JOIN exp_member_groups AS mg ON mg.group_id = m.group_id
					   WHERE m.member_id IN ('".implode( "','", $ids )."')
					   AND mg.site_id = '".ee()->db->escape_str(ee()->config->item('site_id'))."'";

			// This fixes a bug that occurs when a different table prefix is used

			$sql	= str_replace('exp_', 'MDBMPREFIX', $sql);
		}
		else
		{
			$sql	= "";
			$ids	= array();
		}

		/**	----------------------------------------
		/**	Prep insert
		/**	----------------------------------------*/

		$hash = ee()->functions->random('md5');

		$data = array(
				'search_id'		=> $hash,
				'search_date'	=> time(),
				'member_id'		=> ee()->session->userdata('member_id'),
				'keywords'		=> ee()->security->xss_clean( ee()->input->post('keywords') ),
				'ip_address'	=> ee()->input->ip_address(),
				'total_results'	=> ( $sql != '' ) ? $this->query->num_rows() : 0,
				'query'			=> $sql,
				'categories'	=> serialize( $categories ),
				'member_ids'	=> serialize( $ids ),
				'fields'		=> serialize( $this->insert_data ),
				'cfields'		=> serialize( $cfields )
				);

		if ( ee()->config->item('site_id') !== FALSE )
		{
			$data['site_id']	= ee()->config->item('site_id');
		}

		ee()->db->query( ee()->db->insert_string('exp_user_search', $data) );

		$return	= $this->_chars_decode( $return );
		$return = rtrim($return, '/')."/".$hash;

		// --------------------------------------------
		//  AJAX Response
		// --------------------------------------------

		if ($this->is_ajax_request())
		{
			$this->send_ajax_response(array('success' => TRUE,
											'heading' => lang('user_successful_submission'),
											'message' => $return,
											'content' => $return));
		}

		/**	----------------------------------------
		/**	Return
		/**	----------------------------------------*/

		return ee()->functions->redirect( $return );
	}

	/* END do_search() */


	// --------------------------------------------------------------------

	/**
	 *	Output Results of Search
	 *
	 *	@access		public
	 *	@return		string
	 */

	public function results()
	{
		$cache_expire	= 24;

		// ----------------------------------------
		//  Clear old search results
		// ----------------------------------------

		$expire = time() - ($cache_expire * 3600);

		ee()->db->query(
			"DELETE FROM 	exp_user_search
			 WHERE 			search_date < '" . ee()->db->escape_str($expire) . "'"
		);

		// ----------------------------------------
		//  Fetch ID number and page number
		// ----------------------------------------
		//	We cleverly disguise the page number in the ID hash string.
		// ----------------------------------------

		$search_id	= '';

		foreach ( ee()->uri->segments as $seg )
		{
			if ( strlen($seg) >= 32 )
			{
				$search_id	= $seg;
			}
		}

		if ( $search_id == '' )
		{
			ee()->TMPL->template = str_replace(
				LD . 'keywords' . RD,
				'',
				ee()->TMPL->template
			);

			ee()->TMPL->template = str_replace(
				LD . 'total_results' . RD,
				'',
				ee()->TMPL->template
			);

			return $this->no_results('user');
		}
		elseif ( strlen( $search_id ) == 32 )
		{
		}
		else
		{
			$this->cur_page	= substr( $search_id, 32 );
			$search_id		= substr( $search_id, 0, 32 );
		}

		$this->res_page	= str_replace(
			$search_id . $this->cur_page,
			"",
			ee()->uri->uri_string
		);

		$this->res_page	= str_replace(
			$search_id,
			"",
			$this->res_page
		);

		// ----------------------------------------
		//	Check DB
		// ----------------------------------------

		$this->query = ee()->db->query(
			"SELECT `search_id`,
					`keywords`,
					`total_results`,
					`categories`,
					`fields`,
					`cfields`,
					`query`
			 FROM 	exp_user_search
			 WHERE 	search_id = '" . ee()->db->escape_str( $search_id ) . "'"
		);

		if ( $this->query->num_rows() == 0 )
		{
			return $this->no_results('user');
		}

		// ----------------------------------------
		//	Parse some vars
		// ----------------------------------------

		ee()->TMPL->template = str_replace(
			LD . 'keywords' . RD,
			str_replace(
				array('{', '}', '/', '<', '>'),
				array('&#123;', '&#125;', '&#47;', '&lt;', '&gt;'),
				$this->query->row('keywords')
			),
			ee()->TMPL->template
		);

		ee()->TMPL->template = str_replace(
			LD . 'total_results' . RD,
			$this->query->row('total_results'),
			ee()->TMPL->template
		);

		if ( $this->query->row('total_results') == 0 )
		{
			return $this->no_results('user');
		}

		/** ----------------------------------------
		/**	Start SQL
		/** ----------------------------------------*/

		$sql	= str_replace( 'MDBMPREFIX', 'exp_', $this->query->row('query') );

		/** ----------------------------------------
		/**	Order
		/** ----------------------------------------*/

		$sql	= $this->_order_sort( $sql );

		/** ----------------------------------------
		/**  Prep pagination
		/** ----------------------------------------*/

		$use_prefix = stristr(ee()->TMPL->tagdata, LD . 'user_paginate' . RD);

		$sql	= $this->_prep_pagination( $sql, $this->query->row('search_id'), FALSE );

		/** ----------------------------------------
		/**	Run query
		/** ----------------------------------------*/

		$this->query	= ee()->db->query( $sql );

		/**	----------------------------------------
		/**	Return
		/**	----------------------------------------*/

		//support legacy
		if ($use_prefix)
		{
			$return	= $this->parse_pagination(array(
				'prefix' 	=> 'user',
				'tagdata' 	=> $this->_users()
			));
		}
		else
		{
			$return	= $this->parse_pagination(array(
				'tagdata' 	=> $this->_users()
			));
		}

		return $return;
	}

	/* END search results */

	// --------------------------------------------------------------------

	/**
	 *	Forgot Username Form
	 *
	 *	@access		public
	 *	@return		string
	 */

	public function forgot_username()
	{
		if ( ee()->TMPL->fetch_param('form_name') !== FALSE AND ee()->TMPL->fetch_param('form_name') != '' )
		{
			$this->form_data['name']	= ee()->TMPL->fetch_param('form_name');
		}

		$this->form_data['id']	= ( ee()->TMPL->fetch_param('form_id') ) ? ee()->TMPL->fetch_param('form_id'): 'forgot_username_form';

		$this->form_data['ACT']	= ee()->functions->fetch_action_id('User', 'retrieve_username');

		$this->form_data['RET']	= ( ee()->TMPL->fetch_param('return') != '' ) ? ee()->TMPL->fetch_param('return'): '';

		$this->params['secure_action'] = ( ee()->TMPL->fetch_param('secure_action') !== FALSE) ? ee()->TMPL->fetch_param('secure_action'): 'no';

		return $this->_form();
	}

	/* END forgot username form */


	// --------------------------------------------------------------------

	/**
	 *	Forgot Username Form Processing
	 *
	 *	@access		public
	 *	@return		string
	 */

	public function retrieve_username()
	{
		/** ----------------------------------------
		/**  Is user banned?
		/** ----------------------------------------*/

		if (ee()->session->userdata['is_banned'] == TRUE)
		{
			return $this->_output_error('general', array(lang('not_authorized')));
		}

		/** ----------------------------------------
		/**  Error trapping
		/** ----------------------------------------*/

		if ( ! $address = ee()->input->post('email'))
		{
			return $this->_output_error('submission', array(lang('invalid_email_address')));
		}

		ee()->load->helper('email');

		if ( ! valid_email($address))
		{
			return $this->_output_error('submission', array(lang('invalid_email_address')));
		}

		$address = strip_tags($address);

		// Fetch user data

		$sql = "SELECT member_id, username, screen_name, email, language FROM exp_members
				WHERE email ='".ee()->db->escape_str($address)."'";

		$query = ee()->db->query($sql);

		if ($query->num_rows() == 0)
		{
			return $this->_output_error('submission', array(lang('no_email_found')));
		}

		$member_id		= $query->row('member_id');
		$username		= $query->row('username');
		$screen_name	= $query->row('screen_name');
		$address		= $query->row('email'); // Just incase there were tags in it...

		ee()->session->userdata['language'] = $query->row('language');

		/** --------------------------------------------
		/**  Where are we returning them to while they wait for the email?
		/** --------------------------------------------*/

		if (ee()->input->get_post('FROM') == 'forum')
		{
			if (ee()->input->get_post('board_id') !== FALSE AND is_numeric(ee()->input->get_post('board_id')))
			{
				$query	= ee()->db->query("SELECT board_forum_url, board_id, board_label
												FROM exp_forum_boards
												WHERE board_id = '".ee()->db->escape_str(ee()->input->get_post('board_id'))."'");
			}
			else
			{
				$query	= ee()->db->query("SELECT board_forum_url, board_id, board_label
												FROM exp_forum_boards WHERE board_id = '1'");
			}

			$return		= $query->row('board_forum_url');
			$site_name	= $query->row('board_label');
			$board_id	= $query->row('board_id');
		}
		else
		{
			$site_name	= stripslashes(ee()->config->item('site_name'));
			$return 	= ee()->config->item('site_url');
		}

		$forum_id = (ee()->input->get_post('FROM') == 'forum') ? '&r=f&board_id='.$board_id : '';

		$swap = array(
						'username'		=> $username,
						'screen_name'	=> $screen_name,
						'site_name'		=> $site_name,
						'site_url'		=> $return,
						'email'			=> $address,
						'member_id'		=> $member_id
					 );

		$wquery = ee()->db->query("SELECT preference_value FROM exp_user_preferences
								   WHERE preference_name IN ('user_forgot_username_message')");

		if ($wquery->num_rows() == 0)
		{
			return $this->_output_error('submission', array(lang('error_sending_email')));
		}

		// Instantiate the email class

		ee()->load->library('email');
		ee()->email->initialize();
		ee()->email->wordwrap = true;
		ee()->email->from(ee()->config->item('webmaster_email'), ee()->config->item('webmaster_name'));
		ee()->email->to($address);
		ee()->email->subject(lang('forgotten_username_subject'));
		ee()->email->message($this->_var_swap(stripslashes($wquery->row('preference_value')), $swap));

		if ( ! ee()->email->Send())
		{
			return $this->_output_error('submission', array(lang('error_sending_email')));
		}

		/**	----------------------------------------
		/**	 Override Return
		/**	----------------------------------------*/

		if ( $this->_param('override_return') !== FALSE AND $this->_param('override_return') != '' &&
			$this->is_ajax_request() === FALSE)
		{
			ee()->functions->redirect( $this->_param('override_return') );
			exit;
		}

		/**	----------------------------------------
		/**	 Set return
		/**	----------------------------------------*/

		if ( ee()->input->get_post('return') !== FALSE AND ee()->input->get_post('return') != '' )
		{
			$return	= ee()->input->get_post('return');
		}
		elseif ( ee()->input->get_post('RET') !== FALSE AND ee()->input->get_post('RET') != '' )
		{
			$return	= ee()->input->get_post('RET');
		}
		else
		{
			$return = ee()->config->item('site_url');
		}

		if ( preg_match( "/".LD."\s*path=(.*?)".RD."/", $return, $match ) )
		{
			$return	= ee()->functions->create_url( $match['1'] );
		}

		/**	----------------------------------------
		/**	Return
		/**	----------------------------------------*/

		$return	= $this->_chars_decode( $return );

		// --------------------------------------------
		//  AJAX Response
		// --------------------------------------------

		if ($this->is_ajax_request())
		{
			$this->send_ajax_response(array('success' => TRUE,
											'heading' => lang('user_successful_submission'),
											'message' => lang('forgotten_username_sent'),
											'content' => lang('forgotten_username_sent')));
		}

		/** ----------------------------------------
		/**  Build success message
		/** ----------------------------------------*/

		$data = array(	'title' 	=> lang('forgotten_username_subject'),
						'heading'	=> lang('thank_you'),
						'content'	=> lang('forgotten_username_sent'),
						'link'		=> array($return, $site_name)
					 );

		ee()->output->show_message($data);
	}
	/* END retrieve username */


	// --------------------------------------------------------------------

	/**
	 *	Forgot Password Form -> And Alias
	 *
	 *	@access		public
	 *	@return		string
	 */

	public function forgot_password()
	{
		return $this->forgot();
	}

	public function forgot()
	{
		if ( ee()->TMPL->fetch_param('form_name') !== FALSE AND
			ee()->TMPL->fetch_param('form_name') != '' )
		{
			$this->form_data['name']	= ee()->TMPL->fetch_param('form_name');
		}

		$this->form_data['id']			= ee()->TMPL->fetch_param('form_id', 'forgot_password_form');

		$this->form_data['ACT']			= $this->get_retrieve_password_aid();

		$this->form_data['RET']			= ee()->TMPL->fetch_param('return', '');

		$this->params['secure_action']	= ee()->TMPL->fetch_param('secure_action', 'no');

		//password reset template for EE 2.6+
		if (version_compare($this->ee_version, '2.6', '>=') &&
			ee()->TMPL->fetch_param('password_reset_template'))
		{
			$this->params['secure_reset_link']				=
				ee()->TMPL->fetch_param('secure_reset_link', 'no');

			$this->params['password_reset_template']		=
				ee()->TMPL->fetch_param('password_reset_template');

			$this->params['password_reset_email_template']	=
				ee()->TMPL->fetch_param('password_reset_email_template', '');

			$this->params['password_reset_email_subject']	=
				ee()->TMPL->fetch_param('password_reset_email_subject', '');
		}

		return $this->_form();
	}
	// END forgot_password


	// --------------------------------------------------------------------

	/**
	 * https://support.ellislab.com/bugs/detail/19586
	 *
	 * Ee 2.6-2.6.1 has a bug where the updater blanket updates
	 * any method in the actions table named 'retrive_password'
	 * which breaks User. We need to move this back for forgotpassword
	 * to work correctly.
	 *
	 * Get retreive password
	 *
	 * @access public
	 * @return int	action id
	 */

	private function get_retrieve_password_aid()
	{
		$action_q = ee()->db->where(
			array(
				'class'		=> $this->class_name,
				'method'	=> 'retrieve_password'
			)
		)->get('actions');

		//if present send on
		//this query would run anyway if we were using
		//the template tag so no performance loss here
		if ($action_q->num_rows() > 0 )
		{
			return $action_q->row('action_id');
		}

		// -------------------------------------
		//	update or add action
		// -------------------------------------

		$update_q = ee()->db
					->where('class', 'User')
					->where('method', 'send_reset_token')
					->get('actions');

		//update?
		if ($update_q->num_rows() > 0)
		{
			ee()->db->update(
				'actions',
				array('method'	=> 'retrieve_password'),
				array(
					'class'		=> 'User',
					'method'	=> 'send_reset_token'
				)
			);

			//we can use the old ID as its still accurate
			return $update_q->row('action_id');
		}
		//missing?
		else
		{
			ee()->db->insert(
				'actions',
				array(
					'method'	=> 'retrieve_password',
					'class'		=> 'User'
				)
			);

			return ee()->db->insert_id();
		}
	}
	//END get_retrieve_password_aid


	// --------------------------------------------------------------------

	/**
	 * Reset Password Form
	 *
	 * @access	public
	 * @return	string	tagdata wrapped in form setup
	 */

	public function reset_password()
	{
		return $this->lib('reset_password')->reset_password_form();
	}
	//END reset_password


	// --------------------------------------------------------------------

	/**
	 * Process Reset Password
	 *
	 * Uses intercepting objects to capture how EE would do it but using
	 * our own output and redirecting.
	 *
	 * @access	public
	 * @return	void	redirects
	 */

	public function process_reset_password()
	{
		return $this->lib('reset_password')->process_reset_password();
	}
	//END


	// --------------------------------------------------------------------

	/**
	 *	Forgot Password Processing
	 *
	 *	Let's come together everybody and just use EE!
	 *
	 *	@access		public
	 *	@return		null
	 */

	public function retrieve_password()
	{
		//EE 2.6+
		if (version_compare($this->ee_version, '2.6.0', '>='))
		{
			$this->_param();

			if ($this->lib('reset_password')->check_params($this->params))
			{
				return $this->lib('reset_password')->send_reset_token($this->params);
			}

			if ( ! class_exists('Member'))
			{
				require PATH_MOD.'member/mod.member.php';
			}

			$M = new Member();

			if ( ! class_exists('Member_auth'))
			{
				require PATH_MOD.'member/mod.member_auth.php';
			}

			$MA = new Member_auth();

			foreach(get_object_vars($this) as $key => $value)
			{
				$MA->{$key} = $value;
			}

			return $MA->send_reset_token();
		}
		//EE 2.5.5 and below
		else
		{
			if ( ! class_exists('Member'))
			{
				require PATH_MOD.'member/mod.member.php';
			}

			$M = new Member();

			foreach(get_object_vars($this) as $key => $value)
			{
				$M->{$key} = $value;
			}

			// --------------------------------------------
			//  Set Language for Location, If Email Found.
			//  Do NOT Return Error!
			// --------------------------------------------

			if ( isset($_POST['email']))
			{
				$query = ee()->db
							->select('language')
							->where('email', $_POST['email'])
							->get('members');

				if ($query->num_rows() > 0)
				{
					ee()->session->userdata['language'] = $query->row('language');
				}
			}

			$M->retrieve_password();
		}
	}
	/* END retrieve_password() */


	// --------------------------------------------------------------------

	/**
	 *	Key Form
	 *
	 *	Allows the sending of a registration key to people, allowing them to Register on the site.
	 *
	 *	@access		public
	 *	@return		string
	 */

	public function key()
	{
		/**	----------------------------------------
		/**	Allow registration?
		/**	----------------------------------------*/

		if ( ee()->config->item('allow_member_registration') != 'y' )
		{
			return $this->_output_error('general', array(lang('registration_not_enabled')));
		}

		/**	----------------------------------------
		/**	Is the current user logged in?
		//	are they an admin? must be admin
		/**	----------------------------------------*/

		if ( ee()->session->userdata('member_id') == 0 OR ee()->session->userdata('group_id') != 1)
		{
			return $this->_output_error('general', array(lang('not_authorized')));
		}

		/**	----------------------------------------
		/**	Userdata
		/**	----------------------------------------*/

		$tagdata			= ee()->TMPL->tagdata;

		/**	----------------------------------------
		/**	Member groups
		/**	----------------------------------------*/

		if ( preg_match( "/" . LD . 'member_groups' . RD . "(.*?)" .
							   LD . preg_quote($this->t_slash, '/').'member_groups'.RD."/s", $tagdata, $match ) )
		{
			$query	= ee()->db->query(
				"SELECT DISTINCT group_id, group_title
				 FROM 			 exp_member_groups
				 WHERE 			 group_id
				 NOT IN 		 (1,2,3,4)"
			);

			$groups	= '';

			if ( $query->num_rows() > 0 )
			{
				foreach ( $query->result_array() as $row )
				{
					$out	= $match['1'];
					$out	= str_replace( LD.'group_id'.RD, $row['group_id'], $out );
					$out	= str_replace( LD.'group_title'.RD, $row['group_title'], $out );
					$groups	.= $out;
				}

				$tagdata	= str_replace( $match[0], $groups, $tagdata );
			}
			else
			{
				$tagdata	= str_replace( $match[0], '', $tagdata );
			}
		}

		/**	----------------------------------------
		/**	Prep data
		/**	----------------------------------------*/

		$this->form_data['tagdata']			= $tagdata;

		$this->form_data['ACT']				= ee()->functions->fetch_action_id('User', 'create_key');

		$this->form_data['RET']				= (isset($_POST['RET'])) ?
													$_POST['RET'] :
													ee()->functions->fetch_current_uri();

		if ( ee()->TMPL->fetch_param('form_name') !== FALSE AND
			 ee()->TMPL->fetch_param('form_name') != '' )
		{
			$this->form_data['name']		= ee()->TMPL->fetch_param('form_name');
		}

		$this->form_data['id']				= ( ee()->TMPL->fetch_param('form_id') ) ?
													ee()->TMPL->fetch_param('form_id') :
													'member_form';

		$this->params['template']			= ( ee()->TMPL->fetch_param('template') ) ? ee()->TMPL->fetch_param('template'): '';

		$this->params['html']				= $this->check_yes(ee()->TMPL->fetch_param('html')) ? 'yes': 'no';

		$this->params['word_wrap']			= ( ! $this->check_yes(ee()->TMPL->fetch_param('word_wrap'))) ? 'no': 'yes';

		$this->params['parse']				= ( in_array(ee()->TMPL->fetch_param('parse'), array('br', 'none', 'xhtml'))) ?
												ee()->TMPL->fetch_param('parse') : 'none';

		$this->params['return']				= ( ee()->TMPL->fetch_param('return') ) ?
													ee()->TMPL->fetch_param('return') : '';

		$this->params['secure_action']		= ( ! $this->check_yes(ee()->TMPL->fetch_param('secure_action'))) ? 'no': 'yes';

		/**	----------------------------------------
		/**	Return
		/**	----------------------------------------*/

		return $this->_form();
	}

	/* END key */


	// --------------------------------------------------------------------

	/**
	 *	Create Key
	 *
	 *	Processing the Key Form and sends out the Key
	 *
	 *	@access		public
	 *	@return		string
	 */

	public function create_key()
	{
		$email	= '';
		$hashes	= array();

		//	----------------------------------------
		//	Allow registration?
		//	----------------------------------------

		if ( ee()->config->item('allow_member_registration') != 'y' )
		{
			return $this->_output_error('general', array(lang('registration_not_enabled')));
		}

		//	----------------------------------------
		//	Is the current user logged in?
		//	----------------------------------------

		if ( ee()->session->userdata('member_id') == 0 )
		{
			return $this->_output_error('general', array(lang('not_authorized')));
		}

		// --------------------------------------------
		//  Validate Email Addresses
		// --------------------------------------------

		$possible = array('sender_email', 'from', 'to', 'recipient_email');

		foreach($possible AS $var)
		{
			if (ee()->input->get_post($var) !== FALSE)
			{
				ee()->load->helper('email');

				$email	= explode(",", ee()->input->get_post($var));

				foreach ( $email as $e )
				{
					if ( ! valid_email($e))
					{
						return $this->_output_error('general', array(lang('invalid_email_address')));
					}
				}
			}
		}

		// ----------------------------------------
		//  Update last activity
		// ----------------------------------------

		$this->_update_last_activity();

		//	----------------------------------------
		//	Clear old hashes
		//	----------------------------------------

		$this->_clear_old_hashes();

		//	----------------------------------------
		//	Set base vars
		//	----------------------------------------

		$this->insert_data['author_id']		= ee()->session->userdata['member_id'];
		$this->insert_data['date']			= ee()->localize->now;
		$this->insert_data['group_id']		= ( ee()->input->get_post('group_id') ) ?
												ee()->input->get_post('group_id'): '';

		// Invalid Member Group...
		if (in_array($this->insert_data['group_id'], array(1,2,3,4)))
		{
			return $this->_output_error('general', array(lang('not_authorized')));
		}

		$vars						= array();

		$vars['from_email']			= ( ee()->input->get_post('sender_email') !== FALSE ) ?
											ee()->input->get_post('sender_email') :
											(( ee()->input->get_post('from') !== FALSE ) ?
												ee()->input->get_post('from') :
												ee()->config->item('webmaster_email')
											);
		$vars['from_name']			= ( ee()->input->get_post('sender_name') !== FALSE ) ?
											ee()->input->get_post('sender_name') :
											(( ee()->input->get_post('name') !== FALSE ) ?
												ee()->input->get_post('name') :
												ee()->config->item('webmaster_name')
											);
		$vars['subject']			= ( ee()->input->get_post('subject') !== FALSE ) ?
											ee()->input->get_post('subject') :
											lang('you_are_invited_to_join') . ' ' .
												ee()->config->item('site_name');
		$vars['message']			= ( ee()->input->get_post('message') !== FALSE ) ?
											ee()->input->get_post('message') : '';

		$vars['from_name']			= stripslashes( $vars['from_name'] );
		$vars['subject']			= stripslashes( $vars['subject'] );

		$vars['selected_group_id'] 	= $this->insert_data['group_id'];

		/** --------------------------------------------
		/**  Parse Template?
		/** --------------------------------------------*/

		if ( $tmp	= $this->_param('template') )
		{
			$template = explode( "/", $tmp );

			$query = ee()->db->query(
				"SELECT t.template_type, t.template_data
				 FROM 	exp_templates AS t
				 JOIN 	exp_template_groups AS tg
				 ON 	tg.group_id = t.group_id
				 WHERE 	tg.group_name = '" . ee()->db->escape_str($template['0']) . "'
				 AND 	t.template_name = '" . ee()->db->escape_str($template['1']) . "'
				 LIMIT 	1"
			);

			if ( $query->num_rows() == 0 )
			{
				return $this->_output_error('general', array(lang('template_not_found')));
			}

			// ----------------------------------------
			//  Instantiate template class
			// ----------------------------------------

			ee()->load->library('template');
			ee()->load->helper('text');

			/**	----------------------------------------
			/**	Set some values
			/**	----------------------------------------*/

			ee()->template->encode_email	= FALSE;

			ee()->template->global_vars	= array_merge( ee()->template->global_vars, $vars );


			ee()->config->_global_vars 	= array_merge( ee()->config->_global_vars, $vars );


			ee()->template->run_template_engine( $template['0'], $template['1'] );

			/**	----------------------------------------
			/**	Parse typography
			/**	----------------------------------------*/

			ee()->load->library('typography');

			ee()->typography->initialize();

			ee()->typography->smileys			= FALSE;
			ee()->typography->highlight_code	= TRUE;
			ee()->typography->convert_curly		= FALSE;

			$formatting['html_format']			= 'all';
			$formatting['auto_links']			= 'n';
			$formatting['allow_img_url']		= 'y';
			$formatting['text_format']			= 'none';

			if ( in_array($this->_param('parse'), array('br', 'none', 'xhtml')))
			{
				$formatting['text_format'] = $this->_param('parse');
			}

			$body = ee()->typography->parse_type(
				stripslashes(
					//ee()->security->xss_clean(
						ee()->template->final_template
					//)
				),
				$formatting
			);
		}

		/**	----------------------------------------
		/**	Are we sending email?
		/**	----------------------------------------*/

		$to = ee()->input->get_post('to') ?
				ee()->input->get_post('to') :
				(ee()->input->get_post('recipient_email') ?
					ee()->input->get_post('recipient_email') :
					FALSE
				);

		if ( $to )
		{
			$email	= explode( ",", $to );

			$email	= array_unique( $email );

			foreach ( $email as $e )
			{
				/**	----------------------------------------
				/**	Insert
				/**	----------------------------------------*/

				$this->insert_data['email']	= trim( $e );
				$this->insert_data['hash']	= ee()->functions->random( 'alpha' );
				$hashes[]					= $this->insert_data['hash'];

				/**	----------------------------------------
				/**	Prep vars
				/**	----------------------------------------*/

				$key		= $this->insert_data['hash'];

				ee()->db->query( ee()->db->insert_string( 'exp_user_keys', $this->insert_data ) );

				/**	----------------------------------------
				/**	Are we sending invitations?
				/**	----------------------------------------*/

				if ( $tmp	= $this->_param('template') )
				{
					$message = str_replace(LD.'key'.RD, $key, $body);
					$message = str_replace(LD.'to_email'.RD, trim( $e ), $message);

					/**	----------------------------------------
					/**	Send email
					/**	----------------------------------------*/

					ee()->load->library('email');

					ee()->load->helper('text');

					ee()->email->initialize();
					ee()->email->wordwrap	= ( ee()->input->get_post('word_wrap') == 'yes' ) ? true: false;
					ee()->email->mailtype	= ( $this->_param('html') == 'yes' ) ? 'html': 'text';
					ee()->email->from( $vars['from_email'], $vars['from_name'] );
					ee()->email->to( trim( $e ) );
					ee()->email->subject( $vars['subject'] );
					ee()->email->message( entities_to_ascii( $message ) );
					ee()->email->Send();
				}
			}
		}
		else
		{
			/**	----------------------------------------
			/**	Insert
			/**	----------------------------------------*/

			$this->insert_data['hash']	= ee()->functions->random( 'alpha' );
			$hashes[]					= $this->insert_data['hash'];

			ee()->db->query( ee()->db->insert_string( 'exp_user_keys', $this->insert_data ) );
		}

		/**	----------------------------------------
		/**	Return
		/**	----------------------------------------*/

		if ( $this->_param('return') !== FALSE AND $this->_param('return') != '' )
		{
			$return	= $this->_chars_decode( $this->_param('return') );

			ee()->functions->redirect( ee()->functions->create_url( str_replace( "%%key%%", implode( ",", $hashes ), $return ) ) );
		}
		else
		{
			// --------------------------------------------
			//  AJAX Response
			// --------------------------------------------

			if ($this->is_ajax_request())
			{
				$this->send_ajax_response(array('success' => TRUE,
												'heading' => lang('user_successful_submission'),
												'message' => lang('key_success'),
												'content' => lang('key_success')));
			}

			$return	= ( ee()->input->get_post('return') ) ? ee()->input->get_post('return'): ee()->input->get_post('RET');

			$return	= $this->_chars_decode( $return );

			$data	= array(
							'title'		=> lang('key_created'),
							'heading'	=> lang('key_created'),
							'link'		=> array(
											$return,
											lang('back')
											),
							'content'	=> lang('key_success')
							);

			return ee()->output->show_message( $data, TRUE );
		}
	}

	/* END create key */

	// --------------------------------------------------------------------

	/**
	 *	Rank
	 *
	 *	Returns a ranked list of users based on their site participation. ::rolls eyes::
	 *
	 *	@access		public
	 *	@return		string
	 */

	public function rank()
	{
		$users	= array();

		/**	----------------------------------------
		/**	Begin blog entries query
		/**	----------------------------------------*/


		$sql = "SELECT author_id AS member_id, entry_date AS date FROM exp_channel_titles t WHERE author_id != '0'";

		/**	----------------------------------------
		/**	Filter by member
		/**	----------------------------------------*/

		if ( ee()->TMPL->fetch_param('member_id') == 'CURRENT_USER' AND ee()->session->userdata['member_id'] != '0' )
		{
			$sql	.= " AND author_id = '".ee()->session->userdata['member_id']."'";
		}

		/**	----------------------------------------
		/**	Exclude members
		/**	----------------------------------------*/

		if ( ee()->TMPL->fetch_param('exclude') )
		{
			$exclude	= explode( ",", ee()->TMPL->fetch_param('exclude') );

			if ( count( $exclude ) > 0 )
			{
				$sql	.= " AND author_id NOT IN (".implode( ",", $exclude ).")";
			}

		}

		/**	----------------------------------------
		/**	Add status declaration
		/**	----------------------------------------*/

		if ($status = ee()->TMPL->fetch_param('status'))
		{
			$status = str_replace('Open',   'open',   $status);
			$status = str_replace('Closed', 'closed', $status);

			$sstr = ee()->functions->sql_andor_string($status, 't.status');

			if ( strpos($sstr, "'closed'") === FALSE)
			{
				$sstr .= " AND t.status != 'closed' ";
			}

			$sql .= $sstr;
		}
		else
		{
			$sql .= "AND t.status = 'open' ";
		}

		/**	----------------------------------------
		/**	Days limit
		/**	----------------------------------------*/

		if ( $days = ee()->TMPL->fetch_param('days') )
		{
			$time	= ee()->localize->now - ( $days * 60 * 60 * 24);
			$sql	.= " AND t.entry_date > $time";
		}

		/**	----------------------------------------
		/**	Group
		/**	----------------------------------------*/

		// $sql	.= " GROUP BY member_id";

		/**	----------------------------------------
		/**	Order
		/**	----------------------------------------*/

		$sql	.= " ORDER BY member_id DESC, date DESC";

		/**	----------------------------------------
		/**	Execute
		/**	----------------------------------------*/

		$query	= ee()->db->query( $sql );

		/**	----------------------------------------
		/**	Assemble
		/**	----------------------------------------*/

		if ( $query->num_rows() > 0 )
		{
			$i		= ( ee()->TMPL->fetch_param('entries_per_day') ) ? ee()->TMPL->fetch_param('entries_per_day'): 3;

			$iarr	= range( 0, $i );

			foreach ( $query->result_array() as $row )
			{
				/**	----------------------------------------
				/**	Add value
				/**	----------------------------------------*/

				if ( $i = next($iarr) )
				{
					$users[ $row['member_id'] ]['entry'.date( "ymd", $row['date'] ).$i]	= 1;
				}
				else
				{
					reset($iarr);
				}
			}
		}

		/**	----------------------------------------
		/**	Begin comments query
		/**	----------------------------------------*/

		$sql = "SELECT c.author_id AS member_id, c.comment_date AS date FROM exp_comments c
					LEFT JOIN exp_channel_titles t ON t.entry_id = c.entry_id
					WHERE c.author_id != '0'";

		/**	----------------------------------------
		/**	Filter by member
		/**	----------------------------------------*/

		if ( ee()->TMPL->fetch_param('member_id') == 'CURRENT_USER' AND ee()->session->userdata['member_id'] != '0' )
		{
			$sql	.= " AND c.author_id = '".ee()->session->userdata['member_id']."'";
		}

		/**	----------------------------------------
		/**	Exclude members
		/**	----------------------------------------*/

		if ( ee()->TMPL->fetch_param('exclude') )
		{
			$sql	.= " AND c.author_id NOT IN (".ee()->TMPL->fetch_param('exclude').")";
		}

		/**	----------------------------------------
		/**	Add status declaration
		/**	----------------------------------------*/

		if ($status = ee()->TMPL->fetch_param('status'))
		{
			$status = str_replace('Open',   'open',   $status);
			$status = str_replace('Closed', 'closed', $status);

			$sstr = ee()->functions->sql_andor_string($status, 't.status');

			if ( strpos($sstr, "'closed'") === FALSE)
			{
				$sstr .= " AND t.status != 'closed' ";
			}

			$sql .= $sstr;
		}
		else
		{
			$sql .= "AND t.status = 'open' ";
		}

		/**	----------------------------------------
		/**	Days limit
		/**	----------------------------------------*/

		if ( $days = ee()->TMPL->fetch_param('days') )
		{
			$time	= ee()->localize->now - ( $days * 60 * 60 * 24);
			$sql	.= " AND c.comment_date > $time";
		}

		/**	----------------------------------------
		/**	Order
		/**	----------------------------------------*/

		$sql	.= " ORDER BY member_id DESC, date DESC";

		/**	----------------------------------------
		/**	Execute
		/**	----------------------------------------*/

		$query	= ee()->db->query( $sql );

		/**	----------------------------------------
		/**	Assemble
		/**	----------------------------------------*/

		if ( $query->num_rows() > 0 )
		{
			$i		= ( ee()->TMPL->fetch_param('comments_per_day') ) ? ee()->TMPL->fetch_param('comments_per_day'): 3;

			$iarr	= range( 0, $i );

			foreach ( $query->result_array() as $row )
			{
				/**	----------------------------------------
				/**	Add value
				/**	----------------------------------------*/

				if ( $i = next($iarr) )
				{
					$users[ $row['member_id'] ]['comment'.date( "ymd", $row['date'] ).$i]	= 1;
				}
				else
				{
					reset($iarr);
				}
			}
		}

		/**	----------------------------------------
		/**	Begin favorites
		/**	----------------------------------------*/

		if ( ee()->db->table_exists('exp_favorites') )
		{
			/**	----------------------------------------
			/**	Begin favorites query
			/**	----------------------------------------*/

				$sql	= "SELECT f.member_id AS member_id, f.entry_date AS date FROM exp_favorites f
						   LEFT JOIN exp_channel_titles t ON t.entry_id = f.entry_id
						   WHERE f.member_id != '0'";


			/**	----------------------------------------
			/**	Filter by member
			/**	----------------------------------------*/

			if ( ee()->TMPL->fetch_param('member_id') == 'CURRENT_USER' AND ee()->session->userdata['member_id'] != '0' )
			{
				$sql	.= " AND f.member_id = '".ee()->session->userdata['member_id']."'";
			}

			/**	----------------------------------------
			/**	Exclude members
			/**	----------------------------------------*/

			if ( ee()->TMPL->fetch_param('exclude') )
			{
				$sql	.= " AND f.member_id NOT IN (".ee()->TMPL->fetch_param('exclude').")";
			}

			/**	----------------------------------------
			/**	Add status declaration
			/**	----------------------------------------*/

			if ($status = ee()->TMPL->fetch_param('status'))
			{
				$status = str_replace('Open',   'open',   $status);
				$status = str_replace('Closed', 'closed', $status);

				$sstr = ee()->functions->sql_andor_string($status, 't.status');

				if ( strpos($sstr, "'closed'") === FALSE)
				{
					$sstr .= " AND t.status != 'closed' ";
				}

				$sql .= $sstr;
			}
			else
			{
				$sql .= "AND t.status = 'open' ";
			}

			/**	----------------------------------------
			/**	Days limit
			/**	----------------------------------------*/

			if ( $days = ee()->TMPL->fetch_param('days') )
			{
				$time	= ee()->localize->now - ( $days * 60 * 60 * 24);
				$sql	.= " AND t.entry_date > $time";
			}

			/**	----------------------------------------
			/**	Order
			/**	----------------------------------------*/

			$sql	.= " ORDER BY member_id DESC, date DESC";

			/**	----------------------------------------
			/**	Execute
			/**	----------------------------------------*/

			$query	= ee()->db->query( $sql );

			/**	----------------------------------------
			/**	Assemble
			/**	----------------------------------------*/

			if ( $query->num_rows() > 0 )
			{
				$i		= ( ee()->TMPL->fetch_param('favorites_per_day') ) ? ee()->TMPL->fetch_param('favorites_per_day'): 3;

				$iarr	= range( 0, $i );

				foreach ( $query->result_array() as $row )
				{
					/**	----------------------------------------
					/**	Add value
					/**	----------------------------------------*/

					if ( $i = next($iarr) )
					{
						$users[ $row['member_id'] ]['favorite'.date( "ymd", $row['date'] ).$i]	= 1;
					}
					else
					{
						reset($iarr);
					}
				}
			}
		}


		/**	----------------------------------------
		/**	Wrap up users array
		/**	----------------------------------------*/

		$total	= 0;

		foreach ( $users as $key => $val )
		{
			$tot			= array_sum($val);
			$users[$key]	= $tot;
			$total			= $total + $tot;
		}

		/**	----------------------------------------
		/**	Parse total
		/**	----------------------------------------
		/*	If we are getting totals for only one
		/*	member then we obviously don't care
		/*	about getting a ranked list, so we are
		/*	going to return once the total stuff is
		/*	done.
		/**	----------------------------------------*/

		if ( ee()->TMPL->fetch_param('member_id') )
		{
			$cond['total']			= $total;
			ee()->TMPL->tagdata			= ee()->functions->prep_conditionals( ee()->TMPL->tagdata, $cond );

			return ee()->TMPL->tagdata	= str_replace( LD.'total'.RD, $total, ee()->TMPL->tagdata );
		}

		/**	----------------------------------------
		/**	Empty total?
		/**	----------------------------------------*/

		if ( $total == 0 )
		{
			return $this->no_results('user');
		}

		/**	----------------------------------------
		/**	Sort users array
		/**	----------------------------------------*/

		arsort( $users );

		/**	----------------------------------------
		/**	Limit
		/**	----------------------------------------*/

		if ( ! $this->limit = ee()->TMPL->fetch_param('limit') )
		{
			$this->limit	= 50;
		}

		$users	= array_chunk( $users, $this->limit, TRUE );

		$users	= $users[0];

		/**	----------------------------------------
		/**	Grab member data
		/**	----------------------------------------*/

		$mems	= implode( ",", array_keys( $users ) );

		$mems	= ( stristr( ',', $mems ) ) ? substr( $mems, 0, -1 ): $mems;

		$query	= ee()->db->query( "SELECT member_id, screen_name FROM exp_members
							   WHERE member_id IN (".ee()->db->escape_str( $mems ).")" );

		/**	----------------------------------------
		/**	Empty?
		/**	----------------------------------------*/

		if ( $query->num_rows() == 0 )
		{
			return $this->no_results('user');
		}

		/**	----------------------------------------
		/**	Reorder
		/**	----------------------------------------*/

		$new	= array();

		foreach ( $users as $key => $val )
		{
			foreach ( $query->result_array() as $k => $row )
			{
				if ( $row['member_id'] == $key )
				{
					$new[]	= $row;
				}
			}
		}

		/**	----------------------------------------
		/**	Loop
		/**	----------------------------------------*/

		$r	= '';
		$i	= 1;

		foreach ( $new as $row )
		{
			$row['order']	= $i++;
			$row['count']	= $users[ $row['member_id'] ];
			$row['total']	= $users[ $row['member_id'] ];

			$tagdata	= ee()->TMPL->tagdata;

			foreach ( ee()->TMPL->var_single as $val )
			{
				if ( isset( $row[$val] ) )
				{
					$tagdata	= ee()->TMPL->swap_var_single( $val, $row[$val], $tagdata );
				}
			}

			$r	.= $tagdata;
		}

		return $r;
	}

	/* END rank */

	// --------------------------------------------------------------------

	/**
	 *	Inbox Count
	 *
	 *	Returns the number of unread Message in one's Private Message InBox
	 *
	 *	@access		public
	 *	@return		string
	 */

	public function inbox_count()
	{
		$tagdata	= ee()->TMPL->tagdata;

		/**	----------------------------------------
		/**	Logged in?
		/**	----------------------------------------*/

		if ( ee()->session->userdata('member_id') == 0 )
		{
			return str_replace( LD."count".RD, "0", $tagdata );
		}

		/**	----------------------------------------
		/**	Grab count
		/**	----------------------------------------*/

		$query	= ee()->db->query( "SELECT COUNT(*) AS count FROM exp_message_copies WHERE recipient_id = '".ee()->db->escape_str( ee()->session->userdata('member_id') )."' AND message_read = 'n' AND message_deleted = 'n' AND message_folder = '1'" );

		/**	----------------------------------------
		/**	Conditionals
		/**	----------------------------------------*/

		$cond['count']	= $query->row('count');

		$tagdata		= ee()->functions->prep_conditionals( $tagdata, $cond );

		/**	----------------------------------------
		/**	Return
		/**	----------------------------------------*/

		$tagdata		= str_replace( LD."count".RD, $query->row('count'), $tagdata );

		return $tagdata;
	}

	/* END inbox count */

	// --------------------------------------------------------------------

	/**
	 *	Self Delete Confirmation Form
	 *
	 *	Do you really want to destroy your member account on this installation of EE?  I mean, isn't
	 *	that pretty darn severe?  Can't we at least be Friends?
	 *
	 *	@access		public
	 *	@return		string
	 */

	public function delete_form()
	{
		/**	----------------------------------------
		/**	 Member ID
		/**	----------------------------------------*/

		if ( ! $this->_member_id() )
		{
			return $this->no_results('user');
		}

		$this->params['member_id'] = $this->member_id;

		/**	----------------------------------------
		/**	 Authorized?
		/**   - Remember that the 'can_delete_self' preference is on a per-Site basis
		/**	----------------------------------------*/

		if ($this->member_id == ee()->session->userdata['member_id'] AND (ee()->session->userdata['can_delete_self'] !== 'y' OR ee()->session->userdata['group_id'] == 1))
		{
			return $this->_output_error('general', lang('cannot_delete_self'));
		}
		elseif (ee()->session->userdata['member_id'] == 0)
		{
			return '';
		}
		elseif($this->member_id != ee()->session->userdata['member_id'] AND ee()->session->userdata['group_id'] != 1 AND ee()->session->userdata['can_delete_members'] != 'y')
		{
			return '';
		}
		else
		{

			/**	----------------------------------------
			/**	Grab member data
			/**	----------------------------------------*/

			$query = ee()->db->query("SELECT email, group_id, member_id, screen_name, username
								 FROM exp_members WHERE member_id = '".ee()->db->escape_str( $this->member_id )."'");

			if ( $query->num_rows() == 0 )
			{
				return $this->no_results('user');
			}

			$query_row = $query->row_array();

			/**	----------------------------------------
			/**	Parse Variables
			/**	----------------------------------------*/

			$tagdata 	= ee()->TMPL->tagdata;

			foreach($query->row() as $name => $value)
			{
				$query_row['user:'.$name] = $value; // Prefixed variables
			}

			ee()->functions->prep_conditionals($tagdata, $query_row);

			foreach($query_row as $name => $value)
			{
				$tagdata	= str_replace( LD.$name.RD, $value, $tagdata );
			}

			/**	----------------------------------------
			/**	 Create Form
			/**	----------------------------------------*/

			$this->form_data['tagdata']	= $tagdata;

			$this->form_data['onsubmit']	= "if(!confirm('".lang('user_delete_confirm')."')) return false;";

			if ( ee()->TMPL->fetch_param('form_name') !== FALSE AND ee()->TMPL->fetch_param('form_name') != '' )
			{
				$this->form_data['name']	= ee()->TMPL->fetch_param('form_name');
			}

			$this->form_data['id']	= ( ee()->TMPL->fetch_param('form_id') ) ? ee()->TMPL->fetch_param('form_id'): 'member_delete_form';

			$this->form_data['ACT']	= ee()->functions->fetch_action_id('User', 'delete_account');

			$this->form_data['RET']	= ( ee()->TMPL->fetch_param('return') != '' ) ? ee()->TMPL->fetch_param('return'): '';

			$this->params['secure_action'] = ( ee()->TMPL->fetch_param('secure_action') !== FALSE) ? ee()->TMPL->fetch_param('secure_action'): 'no';

			return $this->_form();
		}
	}
	/* END */


	// --------------------------------------------------------------------

	/**
	 *	Delete Member Account Processing
	 *
	 *	@access		public
	 *	@return		string
	 */

	public function delete_account()
	{
		/**	----------------------------------------
		/**  Authorization Check
		/**	----------------------------------------*/

		if ( $this->_param('member_id') == FALSE OR ! ctype_digit($this->_param('member_id')) OR ! isset($_POST['ACT']))
		{
			return $this->_output_error('general', array(lang('not_authorized')));
		}

		if (ee()->session->userdata['member_id'] == 0)
		{
			return $this->_output_error('general', lang('not_authorized'));
		}

		// If not deleting yourself, you must be a SuperAdmin or have Delete Member permissions
		// If deleting yourself, you must have permission to do so.

		if ($this->_param('member_id') != ee()->session->userdata['member_id'])
		{
			if (ee()->session->userdata['group_id'] != 1 AND ee()->session->userdata['can_delete_members'] != 'y')
			{
				return $this->_output_error('general', lang('not_authorized'));
			}
		}
		elseif (ee()->session->userdata['can_delete_self'] !== 'y')
		{
			return $this->_output_error('general', lang('not_authorized'));
		}

		$admin = (ee()->session->userdata['member_id'] != $this->_param('member_id')) ? TRUE : FALSE;

		/** --------------------------------------------
		/**  Member Data
		/** --------------------------------------------*/

		$query = ee()->db->query(
			"SELECT m.*,
					mg.mbr_delete_notify_emails
			 FROM 	exp_members AS m,
					exp_member_groups AS mg
			 WHERE 	m.member_id = '".ee()->db->escape_str($this->_param('member_id'))."'
			 AND 	m.group_id = mg.group_id"
		);

		if ($query->num_rows() == 0)
		{
			return $this->_output_error('general', lang('not_authorized'));
		}

		/** -------------------------------------
		/**  One cannot delete a SuperAdmin from the User side.  Sorry...
		/** -------------------------------------*/

		if($query->row('group_id') == 1)
		{
			return $this->_output_error('general', lang('cannot_delete_super_admin'));
		}

		/** --------------------------------------------
		/**  Variables!
		/** --------------------------------------------*/

		$id							= $query->row('member_id');
		$check_password				= $query->row('password');
		$mbr_delete_notify_emails	= $query->row('mbr_delete_notify_emails');
		$screen_name				= $query->row('screen_name');
		$email						= $query->row('email');

		/** ----------------------------------------
		/**  Is IP and User Agent required for login?  Then, same here.
		/** ----------------------------------------*/

		if (ee()->config->item('require_ip_for_login') == 'y')
		{
			if (ee()->session->userdata['ip_address'] == '' OR ee()->session->userdata['user_agent'] == '')
			{
				return $this->_output_error('general', lang('unauthorized_request'));
			}
		}

		/** ----------------------------------------
		/**  Check password lockout status
		/** ----------------------------------------*/

		if (ee()->session->check_password_lockout() === TRUE)
		{
			return $this->_output_error(
				'general',
				str_replace(
					"%x",
					ee()->config->item('password_lockout_interval'),
					lang('password_lockout_in_effect')
				)
			);
		}

		/* -------------------------------------
		/*  If deleting self, you must submit your password.
		/*  If SuperAdmin deleting another, must submit your password
		/* -------------------------------------*/


			$check_salt = $query->row('salt');

		// Fetch the SAs password instead as they are the one doing the deleting
		if (ee()->session->userdata['member_id'] != $this->_param('member_id'))
		{
			$squery = ee()->db->query(
				"SELECT password, salt
				 FROM 	exp_members
				 WHERE 	member_id = '".ee()->db->escape_str(ee()->session->userdata['member_id'])."'"
			);

			$check_password = $squery->row('password');

			$check_salt = $squery->row('salt');


			unset($squery);
		}


		ee()->load->library('auth');

		$passwd = ee()->auth->hash_password(stripslashes(ee()->input->post('password')), $check_salt);

		if ( ! isset($passwd['salt']) OR ($passwd['password'] != $check_password))
		{
			ee()->session->save_password_lockout();

			return $this->_output_error('general', lang('invalid_pw'));
		}


		// --------------------------------------------
		//  EE 2.4 Added a Member Model for Deleting That Works Rather Well
		// --------------------------------------------

		ee()->load->model('member_model');
		ee()->member_model->delete_member($id);


		/** -------------------------------------
		/**  Email notification recipients
		/** -------------------------------------*/

		if ($mbr_delete_notify_emails != '')
		{
			$notify_address = $mbr_delete_notify_emails;

			$swap = array(
							'name'				=> $screen_name,
							'email'				=> $email,
							'site_name'			=> stripslashes(ee()->config->item('site_name'))
						 );

			$email_tit = ee()->functions->var_swap(lang('mbr_delete_notify_title'), $swap);
			$email_msg = ee()->functions->var_swap(lang('mbr_delete_notify_message'), $swap);

			// No notification for the user themselves, if they're in the list
			if (stristr($notify_address, $email))
			{
				$notify_address = str_replace($email, "", $notify_address);
			}

			ee()->load->helper('string');

			$notify_address = reduce_multiples($notify_address, ',', TRUE);

			if ($notify_address != '')
			{
				/** ----------------------------
				/**  Send email
				/** ----------------------------*/

				ee()->load->library('email');

				ee()->load->helper('text');

				foreach (explode(',', $notify_address) as $addy)
				{
					ee()->email->initialize();
					ee()->email->wordwrap = false;
					ee()->email->from(ee()->config->item('webmaster_email'), ee()->config->item('webmaster_name'));
					ee()->email->to($addy);
					ee()->email->reply_to(ee()->config->item('webmaster_email'));
					ee()->email->subject($email_tit);
					ee()->email->message(entities_to_ascii($email_msg));
					ee()->email->Send();
				}
			}
		}

		/** -------------------------------------
		/**  Trash the Session and cookies
		/** -------------------------------------*/

		ee()->db->query("DELETE FROM exp_online_users
						  WHERE site_id = '".ee()->db->escape_str(ee()->config->item('site_id'))."'
						  AND ip_address = '{ee()->input->ip_address()}'
						  AND member_id = '{$id}'");

		ee()->db->query("DELETE FROM exp_sessions WHERE member_id = '".$id."'");

		if ($admin === FALSE)
		{
			$this->set_cookie(ee()->session->c_session);
			$this->set_cookie(ee()->session->c_anon);
			$this->set_cookie('read_topics');
			$this->set_cookie('tracker');
		}

		if (ee()->extensions->active_hook('user_delete_account_end') === TRUE)
		{
			$edata = ee()->extensions->universal_call('user_delete_account_end', $this);
			if (ee()->extensions->end_script === TRUE) return;
		}

		/**	----------------------------------------
		/**	 Override Return
		/**	----------------------------------------*/

		if ( $this->_param('override_return') !== FALSE AND $this->_param('override_return') != '' &&
			$this->is_ajax_request() === FALSE)
		{
			ee()->functions->redirect( $this->_param('override_return') );
			exit;
		}

		/**	----------------------------------------
		/**	 Set return
		/**	----------------------------------------*/

		if ( ee()->input->get_post('return') !== FALSE AND ee()->input->get_post('return') != '' )
		{
			$return	= ee()->input->get_post('return');
		}
		elseif ( ee()->input->get_post('RET') !== FALSE AND ee()->input->get_post('RET') != '' )
		{
			$return	= ee()->input->get_post('RET');
		}
		else
		{
			$return = ee()->config->item('site_url');
		}

		if ( preg_match( "/".LD."\s*path=(.*?)".RD."/", $return, $match ) )
		{
			$return	= ee()->functions->create_url( $match['1'] );
		}

		/**	----------------------------------------
		/**	Return
		/**	----------------------------------------*/

		$return	= $this->_chars_decode( $return );

		// --------------------------------------------
		//  AJAX Response
		// --------------------------------------------

		if ($this->is_ajax_request())
		{
			$this->send_ajax_response(array(
				'success' => TRUE,
				'heading' => lang('user_successful_submission'),
				'message' => lang('mbr_account_deleted'),
				'content' => lang('mbr_account_deleted'))
			);
		}

		/** -------------------------------------
		/**  Build Success Message
		/** -------------------------------------*/

		$name	= stripslashes(ee()->config->item('site_name'));

		$data = array(
			'title' 	=> lang('mbr_delete'),
			'heading'	=> lang('thank_you'),
			'content'	=> lang('mbr_account_deleted'),
			'redirect'	=> $return,
		);

		ee()->output->show_message($data);
	}
	//END delete_account


	// --------------------------------------------------------------------

	/**
	 *	Private Messaging
	 *
	 *	This returns the contebnts of a message box
	 *
	 *	@access		public
	 *	@return		string
	 */

	public function messages()
	{
		/**	----------------------------------------
		/**	Logged in?
		/**	----------------------------------------*/

		if ( ee()->session->userdata('member_id') == 0 )
		{
			return $this->no_results('user');
		}

		/**	----------------------------------------
		/**	Which box?
		/**	----------------------------------------*/

		$folder	= '1';

		if ( ee()->TMPL->fetch_param( 'folder' ) !== FALSE AND ctype_digit( ee()->TMPL->fetch_param( 'folder' ) ) )
		{
			$folder	= ee()->TMPL->fetch_param( 'folder' );
		}

		/**	----------------------------------------
		/**	Grab messages
		/**	----------------------------------------*/

		$sql	= "SELECT mc.copy_id AS message_id, md.message_subject, md.message_date FROM exp_message_copies mc LEFT JOIN exp_message_data md ON md.message_id = mc.message_id WHERE mc.recipient_id = '".ee()->db->escape_str( ee()->session->userdata('member_id') )."' AND mc.message_deleted = 'n' AND mc.message_folder = '".ee()->db->escape_str( $folder )."'";

		$sql	.= " ORDER BY md.message_date DESC";

		/**	----------------------------------------
		/**	Limit?
		/**	----------------------------------------*/

		if ( ee()->TMPL->fetch_param( 'limit' ) !== FALSE AND ctype_digit( ee()->TMPL->fetch_param( 'limit' ) ) )
		{
			$sql	.= " LIMIT ".ee()->TMPL->fetch_param( 'limit' );
		}

		$query	= ee()->db->query( $sql );

		/**	----------------------------------------
		/**	Empty?
		/**	----------------------------------------*/

		if ( $query->num_rows() == 0 )
		{
			return $this->no_results('user');
		}

		/**	----------------------------------------
		/**	Loop
		/**	----------------------------------------*/

		$r	= "";

		foreach ( $query->result_array() as $row )
		{
			$tagdata	= ee()->TMPL->tagdata;

			/**	----------------------------------------
			/**	Conditionals
			/**	----------------------------------------*/

			$cond			= $row;

			$tagdata		= ee()->functions->prep_conditionals( $tagdata, $cond );

			/**	----------------------------------------
			/**	Vars
			/**	----------------------------------------*/

			foreach ( ee()->TMPL->var_single as $key )
			{
				if ( isset( $row[$key] ) === TRUE )
				{
					$tagdata	= str_replace( LD.$key.RD, $row[$key], $tagdata );
				}
			}

			/**	----------------------------------------
			/**	Concat
			/**	----------------------------------------*/

			$r	.= $tagdata;
		}

		/**	----------------------------------------
		/**	Return
		/**	----------------------------------------*/

		return $r;
	}

	/* END messages */

	// --------------------------------------------------------------------

	/**
	 *	Is Mine
	 *
	 *	Evaluates Wheter the Logged in Member is the Same as the Member ID sent
	 *
	 *	@access		public
	 *	@return		string
	 */

	public function is_mine()
	{
		$cond['mine']		= FALSE;
		$cond['not_mine']	= TRUE;

		if ( $this->_member_id() === TRUE )
		{
			ee()->TMPL->log_item('Member ID: '.$this->member_id);

			if ( $this->member_id == ee()->session->userdata('member_id'))
			{
				$cond['mine']		= TRUE;
				$cond['not_mine']	= FALSE;
			}
		}

		ee()->TMPL->tagdata	= ee()->functions->prep_conditionals( ee()->TMPL->tagdata, $cond );

		return ee()->TMPL->tagdata;
	}

	/* END is mine */

	// --------------------------------------------------------------------

	/**
	 *	Reassign Jump
	 *
	 *	@access		public
	 *	@return		string
	 */

	public function reassign_jump()
	{
		return ee()->functions->redirect( ee()->config->item('cp_url')."?C=modules&M=user&P=reassign_ownership_confirm&member_id=".ee()->input->get_post('member_id')."&entry_id=".ee()->input->get_post('entry_id') );
	}

	/* END reassign jump */


	// --------------------------------------------------------------------

	/**
	 * User Authors Placeholder?
	 *
	 * @access	public
	 * @return	null
	 */

	public function user_authors()
	{
		if (! class_exists('Channel'))
		{
			require PATH_MOD.'channel/mod.channel.php';
		}

		//AND THEEEEN???
	}
	 /* END user_authors() */

	// --------------------------------------------------------------------

	/**
	 *	Screen Name Override
	 *
	 *	Used when assembling a screen name out of a set of Custom Fields
	 *
	 *	@access		public
	 *	@param		integer
	 *	@return		string
	 */

	public function _screen_name_override( $member_id = '0' )
	{
		//we dont want to do this work twice
		if ($this->completed_override) return;

		//do we need to run the sql?
		$update_member_data	=	(isset($_POST['screen_name']) AND $_POST['screen_name'] == $this->screen_name_dummy);

		$query = ee()->db->query(
			"SELECT preference_value
			 FROM 	exp_user_preferences
			 WHERE 	preference_name = 'screen_name_override'
			 LIMIT 	1"
		);

		$screen_name_override = ($query->num_rows() == 0) ? '' : $query->row('preference_value');

		if ( ! empty($screen_name_override) )
		{
			$this->_mfields();

			$fields			= '';
			$screen_name	= '';
			$update			= FALSE;

			/**	----------------------------------------
			/**	Check required fields
			/**	----------------------------------------*/

			$name = explode( "|", $screen_name_override );

			//if we need to parse member_id, we need to do a little magic.
			if (in_array('member_id', $name))
			{
				//if we have a member_id available, lets put it in the normal loop
				if ($member_id != 0)
				{
					$this->mfields['member_id'] = TRUE;
					$_POST['member_id']			= $member_id;
				}
				//if not, this is probably creation time and we are going to call it again
				//so we give it a hash and we will replace it later.
				else
				{
					$this->completed_override 	= FALSE;
					$_POST['screen_name']		= $this->screen_name_dummy;
					return;
				}
			}

			foreach ( $name as $n )
			{
				$n	= trim( $n );

				if ( isset( $this->mfields[$n] ) )
				{
					if ( isset( $_POST[$n] ) )
					{
						$update	= TRUE;

						if ( $_POST[$n] == '' )
						{
							$fields			.= "<li>".$this->mfields[$n]['label']."</li>";
						}
						else
						{
							$screen_name	.= $_POST[$n]." ";
						}
					}
				}
			}

			$screen_name	= trim( $screen_name );

			//remove these if we added them in
			if (isset($this->mfields['member_id']))
			{
				unset($this->mfields['member_id']);
				unset($_POST['member_id']);
			}

			/**	----------------------------------------
			/*	If screen name is empty at this
			/*	point, we are not updating it and
			/*	can get out.
			/**	----------------------------------------*/

			if ( $screen_name == '' AND ! $update )
			{
				return TRUE;
			}

			if ( $fields != '' )
			{
				return $this->_output_error(
					'general',
					array(
						str_replace( "%s", $fields, lang('user_field_required') )
					)
				);
			}

			/**	----------------------------------------
			/**	Is screen name banned?
			/**	----------------------------------------*/

			if (ee()->session->ban_check('screen_name', $screen_name))
			{
				return $this->_output_error(
					'general',
					array(
						str_replace( "%s", $screen_name, lang('banned_screen_name') )
					)
				);
			}

			/**	----------------------------------------
			/**	Is screen name taken?
			/**	----------------------------------------*/
			/*

			$sql	= "SELECT COUNT(*) AS count FROM exp_members WHERE screen_name = '".ee()->db->escape_str($screen_name)."'";

			if ( $member_id != '0' )
			{
				$sql	.= " AND member_id != '".$member_id."'";
			}

			$query = ee()->db->query( $sql );

			if ($query->row('count') > 0)
			{
				return $this->_output_error( 'general', array( str_replace( "%s", $screen_name, lang('bad_screen_name') ) ) );
			}
			*/

			/**	----------------------------------------
			/**	Re-assign
			/**	----------------------------------------*/

			$_POST['screen_name']	= $screen_name;

			//we need to update this if it is being sent a second time
			if ($update_member_data)
			{
				ee()->db->query(
					ee()->db->update_string(
						'exp_members',
						array(
							'screen_name' => $_POST['screen_name']
						),
						'member_id = "' . ee()->db->escape_str($member_id) . '"'
					)
				);
			}
		}

		$this->completed_override = TRUE;

		return;
	}

	/* END screen name override */


	// --------------------------------------------------------------------

	/**
	 *	Email is Username Error Checkin
	 *
	 *	One's Email Address can be used as one's Username.  This does synching and checking to allow that.
	 *
	 *	@access		public
	 *	@param		integer
	 *	@param		string
	 *	@return		string
	 */

	public function _email_is_username( $member_id = '0', $type = 'update' )
	{
		/**	----------------------------------------
		/**	No member id? Fail out
		/**	----------------------------------------*/

		if ( $member_id == '0' AND $type == 'update' ) return FALSE;

		/**	----------------------------------------
		/**	No username change allowed?
		/**	----------------------------------------*/

		if ( ee()->config->item('allow_username_change') != 'y' AND $type == 'update' ) return FALSE;

		/**	----------------------------------------
		/**	Should we execute?
		/**	----------------------------------------*/

		if ( $this->preferences['email_is_username'] == 'n')
		{
			return TRUE;
		}

		/**	----------------------------------------
		/**	Get current data
		/**	----------------------------------------*/

		$cur_username	= '';
		$cur_email		= '';

		$query	= ee()->db->query( "SELECT username, email FROM exp_members
									WHERE member_id = '".ee()->db->escape_str($member_id)."'" );

		if ( $query->num_rows() > 0 )
		{
			$cur_username	= $query->row('username');
			$cur_email		= $query->row('email');
		}

		/**	----------------------------------------
		/**	Is username empty or not changed?
		/**	----------------------------------------*/

		if ( $type == 'update' AND
			 ( ! ee()->input->get_post('username') OR
			  ee()->input->get_post('username') == $cur_username ) )
		{
			return TRUE;
		}

		/**	----------------------------------------
		/**	Is username banned?
		/**	----------------------------------------*/

		if ( ee()->session->ban_check( 'username', ee()->input->get_post('username') ) OR
			 ee()->session->ban_check( 'email', ee()->input->get_post('username') ) )
		{
			return $this->_output_error( 'general', lang('banned_username') );
		}

		$validate	= array(
			'member_id'			=> $member_id,
			'val_type'			=> $type, // new or update
			'fetch_lang' 		=> FALSE,
			'require_cpw' 		=> FALSE,
			'enable_log'		=> FALSE,
			'username'			=> ee()->input->get_post('username'),
			'cur_username'		=> $cur_username,
			'email'				=> ee()->input->get_post('username'),
			'cur_email'			=> $cur_email,
		);

		/**	----------------------------------------
		/**	Validate submitted data
		/**	----------------------------------------*/

		ee()->load->library('validate', $validate, 'email_validate');

		ee()->email_validate->validate_username();

		if ($this->preferences['email_is_username'] != 'n' AND
			($key = array_search(
				lang('username_password_too_long'),
				ee()->email_validate->errors
			)) !== FALSE)
		{
			if (strlen(ee()->email_validate->username) <= 50)
			{
				unset(ee()->email_validate->errors[$key]);
			}
			else
			{
				ee()->email_validate->errors[$key] = str_replace('32', '50', ee()->email_validate->errors[$key]);
			}
		}

		// If email is username, remove username error message
		if( $this->preferences['email_is_username'] == 'y')
		{
			foreach(array('username_taken', 'missing_username') as $go_away)
			{
				if (($key = array_search(lang($go_away), ee()->email_validate->errors)) !== FALSE)
				{
					unset(ee()->email_validate->errors[$key]);
				}
			}
		}

		ee()->email_validate->validate_email();

		/**	----------------------------------------
		/**	Display errors if there are any
		/**	----------------------------------------*/

		if (count(ee()->email_validate->errors) > 0)
		{
			return $this->_output_error('submission', ee()->email_validate->errors);
		}

		/**	----------------------------------------
		/**	Re-assign
		/**	----------------------------------------*/

		$_POST['email']	= ee()->input->get_post('username');
	}

	/* END _email_is_username() */

	// --------------------------------------------------------------------

	/**
	 *	Remove old User Keys
	 *
	 *	@access		public
	 *	@param		integer		$exp - Number of days ago to start deleting
	 *	@return		string
	 */

	public function _clear_old_hashes( $exp = '7' )
	{
		$query = ee()->db->query("SELECT preference_value FROM exp_user_preferences WHERE preference_name = 'key_expiration' LIMIT 1");

		$exp	= ( $query->num_rows() > 0 ) ? $query->row('preference_value') : $exp;

		$now	= ee()->localize->now - ( $exp * 60 * 60 * 24 );

		ee()->db->query( "DELETE FROM exp_user_keys WHERE member_id = '0' AND date < '".$now."'" );

		return TRUE;
	}

	/* END _clean_old_hashes() */

	// --------------------------------------------------------------------

	/**
	 *	Characters Decoding
	 *
	 *	Converted entities back into characters
	 *
	 *	@access		public
	 *	@param		string
	 *	@return		string
	 */

	public function _chars_decode( $str = '' )
	{
		if ( $str == '' ) return;

		if ( function_exists( 'htmlspecialchars_decode' ) )
		{
			$str	= htmlspecialchars_decode( $str );
		}

		if ( function_exists( 'html_entity_decode' ) )
		{
			$str	= html_entity_decode( $str );
		}

		$str	= str_replace( array( '&amp;', '&#47;', '&#39;', '\'' ), array( '&', '/', '', '' ), $str );

		$str	= stripslashes( $str );

		return $str;
	}

	/* END chars decode */


	// --------------------------------------------------------------------

	/**
	 *	Update User's Last Activity in Database
	 *
	 *	@access		private
	 *	@return		bool
	 */

	private function _update_last_activity()
	{
		if ( ee()->session->userdata('member_id') == 0 )
		{
			return FALSE;
		}
		else
		{
			$member_id	= ee()->session->userdata('member_id');
		}

		ee()->db->query( ee()->db->update_string( 'exp_members', array( 'last_activity' => ee()->localize->now ), array( 'member_id' => $member_id ) ) );

		return TRUE;
	}

	/* END _update_last_activity() */


	// --------------------------------------------------------------------

	/**
	 *	Upload Images for Member
	 *
	 *	@access		private
	 *	@param		integer
	 *	@param		bool		$test_mode - When FALSE, it just does error checking
	 *	@return		string
	 */

	private function _upload_images( $member_id = 0, $test_mode = FALSE )
	{
		/**	----------------------------------------
		/**	Should we execute?
		/**	----------------------------------------*/

		if ( $member_id == 0 AND $test_mode === FALSE )
		{
			return FALSE;
		}

		foreach ( $this->images as $key => $val )
		{
			if ( isset( $_FILES[$val] ) AND $_FILES[$val]['name'] != '' )
			{
				$this->uploads[$key]	= $val;
			}
		}

		if ( count( $this->uploads ) == 0 )
		{
			return FALSE;
		}

		/**	----------------------------------------
		/**	Let's loop
		/**	----------------------------------------*/

		foreach ( $this->uploads as $key => $val )
		{
			$this->_upload_image( $key, $member_id, $test_mode );
		}

		/* END loop */
	}

	/* END upload image */

	// --------------------------------------------------------------------

	/**
	 *	Remove an Image for Member
	 *
	 *	@access		private
	 *	@param		string
	 *	@param		integer
	 * 	@param		bool
	 *	@return		string
	 */

	private function _remove_image($type, $member_id, $admin)
	{

		ee()->load->library('auth');

		if ( ! isset($_POST['remove_'.$type]) OR
			! in_array($type, array('avatar', 'photo', 'signature')))
		{
			return FALSE;
		}

		/**	----------------------------------------
		/**	 Password Required for Form Submission?!
		/**	----------------------------------------*/

		if ($admin === FALSE AND ($this->check_yes($this->_param('password_required')) OR $this->_param('password_required') == 'yes'))
		{
			if (ee()->session->check_password_lockout() === TRUE)
			{
				$line = str_replace("%x", ee()->config->item('password_lockout_interval'), lang('password_lockout_in_effect'));
				return $this->_output_error('submission', $line);
			}

			$query = ee()->db->query(
				"SELECT password
				 FROM 	exp_members
				 WHERE  member_id = '" . ee()->db->escape_str( $member_id ) . "'"
			);

			if ( $query->num_rows() == 0 )
			{
				return $this->_output_error('general', array(lang('cant_find_member')));
			}

			if (ee()->input->get_post('current_password') === FALSE OR
				ee()->input->get_post('current_password') == '')
			{
				return $this->_output_error( 'general', array( lang( 'invalid_password' ) ) );
			}

			if (! ee()->auth->authenticate_id($member_id, $_POST['current_password']))
			{
				return $this->_output_error( 'general', array( lang( 'invalid_password' ) ) );
			}

		}

		/**	----------------------------------------
		/**	Check Form Hash
		/**	----------------------------------------*/

		if ( ! $this->check_secure_forms() )
		{
			return $this->_output_error('general', array(lang('not_authorized')));
		}

		/**	----------------------------------------
		/**	Set return
		/**	----------------------------------------*/

		if ( ee()->input->get_post('return') !== FALSE AND
			 ee()->input->get_post('return') != '' )
		{
			$return	= ee()->input->get_post('return');
		}
		elseif ( ee()->input->get_post('RET') !== FALSE AND
				 ee()->input->get_post('RET') != '' )
		{
			$return	= ee()->input->get_post('RET');
		}
		else
		{
			$return = ee()->config->item('site_url');
		}

		if ( preg_match( "/".LD."\s*path=(.*?)".RD."/", $return, $match ) > 0 )
		{
			$return	= ee()->functions->create_url( $match['1'] );
		}
		elseif ( stristr( $return, "http://" ) === FALSE AND
				 stristr( $return, "https://" ) === FALSE )
		{
			$return	= ee()->functions->create_url( $return );
		}

		/** --------------------------------------------
		/**  Let's Delete An Image!
		/** --------------------------------------------*/

		$_POST['remove'] = 'Woot!';

		if ($type == 'signature') $type = 'sig';

		if (FALSE == $this->_upload_image($type, $member_id))
		{
			// 	If FALSE, there was no image to delete...
			//	BUT YOU CANT GET YE FLASK!
		}

		/** --------------------------------------------
		/**  Success Message?
		/** --------------------------------------------*/

		switch ($type)
		{
			case 'avatar'	:
				$remove		= 'remove_avatar';
				$removed	= 'avatar_removed';
			break;
			case 'photo'	:
				$remove		= 'remove_photo';
				$removed	= 'photo_removed';

			break;
			case 'sig'		:
				$remove		= 'remove_sig_image';
				$removed	= 'sig_img_removed';
			break;
		}

		// --------------------------------------------
		//  AJAX Response
		// --------------------------------------------

		if ($this->is_ajax_request())
		{
			$this->send_ajax_response(array(
					'success' => TRUE,
					'heading' => lang('user_successful_submission'),
					'message' => $removed,
					'content' => $removed
			));
			exit();
		}

		/**	----------------------------------------
		/**	 Override Return
		/**	----------------------------------------*/

		if ( $this->_param('override_return') !== FALSE AND
			 $this->_param('override_return') != '' )
		{
			ee()->functions->redirect( $this->_param('override_return') );
			exit();
		}

		return ee()->output->show_message(array(
			'title'		=> lang($remove),
			'heading'	=> lang($remove),
			'link'		=> array( $return, lang('return') ),
			'content'	=> lang($removed)
		));
	}
	// END remove_image()


	// --------------------------------------------------------------------

	/**
	 *	Upload an Image
	 *
	 *	Uploads one of the three types of images for Member Accounts
	 *
	 *	@access		public
	 *	@param		string
	 *	@param		integer
	 *	@param		bool
	 *	@return		string|bool
	 */

	private function _upload_image( $type = 'avatar', $member_id = 0, $test_mode = TRUE )
	{
		$member_id	= ee()->db->escape_str( $member_id );

		switch ($type)
		{
			case 'avatar'	:
								$this->img['edit_image']	= 'edit_avatar';
								$this->img['enable_pref']	= 'allow_avatar_uploads';
								$this->img['not_enabled']	= 'avatars_not_enabled';
								$this->img['remove']		= 'remove_avatar';
								$this->img['removed']		= 'avatar_removed';
								$this->img['updated']		= 'avatar_updated';
				break;
			case 'photo'	:
								$this->img['edit_image'] 	= 'edit_photo';
								$this->img['enable_pref']	= 'enable_photos';
								$this->img['not_enabled']	= 'photos_not_enabled';
								$this->img['remove']		= 'remove_photo';
								$this->img['removed']		= 'photo_removed';
								$this->img['updated']		= 'photo_updated';

				break;
			case 'sig'		:
								$this->img['edit_image'] 	= 'edit_signature';
								$this->img['enable_pref']	= 'sig_allow_img_upload';
								$this->img['not_enabled']	= 'sig_img_not_enabled';
								$this->img['remove']		= 'remove_sig_img';
								$this->img['removed']		= 'sig_img_removed';
								$this->img['updated']		= 'signature_updated';
				break;
		}

		/**	----------------------------------------
		/**	Is this a remove request?
		/**	----------------------------------------*/

		if ( ! isset($_POST['remove']))
		{
			//  Is image uploading enabled?
			if (ee()->config->item( $this->img['enable_pref'] ) == 'n')
			{
				return $this->_output_error('general', array(lang($type.'_uploads_not_enabled')));
			}
		}
		else
		{
			if ($type == 'avatar')
			{
				$query = ee()->db->query("SELECT avatar_filename FROM exp_members WHERE member_id = '".$member_id."'");

				if ($query->row('avatar_filename') == '')
				{
					return FALSE;
				}

				ee()->db->update(
					'exp_members',
					array(
						'avatar_filename'	=> '',
						'avatar_width'		=> 0,
						'avatar_height'		=> 0
					),
					array('member_id'		=> $member_id)
				);

				if ( strpos($query->row('avatar_filename'), '/') !== FALSE)
				{
					@unlink(ee()->config->slash_item('avatar_path').$query->row('avatar_filename'));
				}
			}
			elseif ($type == 'photo')
			{
				$query = ee()->db->query("SELECT photo_filename FROM exp_members WHERE member_id = '".$member_id."'");

				if ($query->row('photo_filename') == '')
				{
					return FALSE;
				}

				ee()->db->update(
					'exp_members',
					array(
						'photo_filename'	=> '',
						'photo_width'		=> 0,
						'photo_height'		=> 0
					),
					array('member_id'		=> $member_id)
				);

				@unlink(ee()->config->slash_item('photo_path').$query->row('photo_filename'));
			}
			else
			{
				$query = ee()->db->query("SELECT sig_img_filename FROM exp_members WHERE member_id = '".$member_id."'");

				if ($query->row('sig_img_filename') == '')
				{
					return FALSE;
				}

				ee()->db->update(
					'exp_members',
					array(
						'sig_img_filename'	=> '',
						'sig_img_width'		=> 0,
						'sig_img_height'	=> 0
					),
					array('member_id'		=> $member_id)
				);

				@unlink(ee()->config->slash_item('sig_img_path').$query->row('sig_img_filename'));
			}

			return TRUE;
		}

		/**	----------------------------------------
		/**	Do the have the GD library?
		/**	----------------------------------------*/

		if ( ! function_exists('getimagesize'))
		{
			return $this->_output_error('general', array(lang('gd_required')));
		}

		/**	----------------------------------------
		/**	Check the image size
		/**	----------------------------------------*/

		$size = ceil(($_FILES[$this->uploads[$type]]['size']/1024));

		if ($type == 'avatar')
		{
			$max_size = (ee()->config->item('avatar_max_kb') == '' OR ee()->config->item('avatar_max_kb') == 0) ? 50 : ee()->config->item('avatar_max_kb');
		}
		elseif ($type == 'photo')
		{
			$max_size = (ee()->config->item('photo_max_kb') == '' OR ee()->config->item('photo_max_kb') == 0) ? 50 : ee()->config->item('photo_max_kb');
		}
		else
		{
			$max_size = (ee()->config->item('sig_img_max_kb') == '' OR ee()->config->item('sig_img_max_kb') == 0) ? 50 : ee()->config->item('sig_img_max_kb');
		}

		$max_size = preg_replace("/(\D+)/", "", $max_size);

		if ($size > $max_size)
		{
			return $this->_output_error('submission', str_replace('%s', $max_size, lang('image_max_size_exceeded')));
		}

		/**	----------------------------------------
		/**	Is the upload path valid and writable?
		/**	----------------------------------------*/

		if ($type == 'avatar')
		{
			$upload_path = ee()->config->slash_item('avatar_path').'uploads/';
		}
		elseif ($type == 'photo')
		{
			$upload_path = ee()->config->slash_item('photo_path');
		}
		else
		{
			$upload_path = ee()->config->slash_item('sig_img_path');
		}

		if ( ! @is_dir($upload_path) OR ! is_writable($upload_path))
		{
			return $this->_output_error('general', array(lang('missing_upload_path')));
		}

		/**	----------------------------------------
		/**	Set some defaults
		/**	----------------------------------------*/

		$filename = $_FILES[$this->uploads[$type]]['name'];

		if ($type == 'avatar')
		{
			$max_width	= (ee()->config->item('avatar_max_width') == '' OR ee()->config->item('avatar_max_width') == 0) ? 100 : ee()->config->item('avatar_max_width');
			$max_height	= (ee()->config->item('avatar_max_height') == '' OR ee()->config->item('avatar_max_height') == 0) ? 100 : ee()->config->item('avatar_max_height');
			$max_kb		= (ee()->config->item('avatar_max_kb') == '' OR ee()->config->item('avatar_max_kb') == 0) ? 50 : ee()->config->item('avatar_max_kb');
		}
		elseif ($type == 'photo')
		{
			$max_width	= (ee()->config->item('photo_max_width') == '' OR ee()->config->item('photo_max_width') == 0) ? 100 : ee()->config->item('photo_max_width');
			$max_height	= (ee()->config->item('photo_max_height') == '' OR ee()->config->item('photo_max_height') == 0) ? 100 : ee()->config->item('photo_max_height');
			$max_kb		= (ee()->config->item('photo_max_kb') == '' OR ee()->config->item('photo_max_kb') == 0) ? 50 : ee()->config->item('photo_max_kb');
		}
		else
		{
			$max_width	= (ee()->config->item('sig_img_max_width') == '' OR ee()->config->item('sig_img_max_width') == 0) ? 100 : ee()->config->item('sig_img_max_width');
			$max_height	= (ee()->config->item('sig_img_max_height') == '' OR ee()->config->item('sig_img_max_height') == 0) ? 100 : ee()->config->item('sig_img_max_height');
			$max_kb		= (ee()->config->item('sig_img_max_kb') == '' OR ee()->config->item('sig_img_max_kb') == 0) ? 50 : ee()->config->item('sig_img_max_kb');
		}

		/**	----------------------------------------
		/**	Does the image have a file extension?
		/**	----------------------------------------*/

		if ( ! stristr($filename, '.'))
		{
			return $this->_output_error('submission', lang('invalid_image_type'));
		}

		/**	----------------------------------------
		/**	Is it an allowed image type?
		/**	----------------------------------------*/

		$xy = explode('.', $filename);
		$extension = '.'.end($xy);

		// We'll do a simple extension check now.
		// The file upload class will do a more thorough check later

		$types = array('.jpg', '.jpeg', '.gif', '.png');

		if ( ! in_array(strtolower($extension), $types))
		{
			return $this->_output_error('submission', lang('invalid_image_type'));
		}

		/** --------------------------------------------
		/**  END Test Mode
		/** --------------------------------------------*/

		if ($test_mode === TRUE)
		{
			return TRUE;
		}

		/**	----------------------------------------
		/**	Assign the name of the image
		/**	----------------------------------------*/

		$new_filename = $type.'_'.$member_id . strtolower($extension);

		/**	----------------------------------------
		/**	Do they currently have an avatar or photo?
		/**	----------------------------------------*/

		if ($type == 'avatar')
		{
			$query = ee()->db->query("SELECT avatar_filename FROM exp_members WHERE member_id = '".$member_id."'");
			$old_filename = ($query->row('avatar_filename') == '') ? '' : $query->row('avatar_filename');

			if ( strpos($old_filename, '/') !== FALSE)
			{
				$xy = explode('/', $old_filename);
				$old_filename =  end($xy);
			}
		}
		elseif ($type == 'photo')
		{
			$query = ee()->db->query("SELECT photo_filename FROM exp_members WHERE member_id = '".$member_id."'");
			$old_filename = ($query->row('photo_filename') == '') ? '' : $query->row('photo_filename');
		}
		else
		{
			$query = ee()->db->query("SELECT sig_img_filename FROM exp_members WHERE member_id = '".$member_id."'");
			$old_filename = ($query->row('sig_img_filename') == '') ? '' : $query->row('sig_img_filename');
		}


		/**	----------------------------------------
		/**	Instantiate upload class
		/**	----------------------------------------*/

		// Upload the image
		//1.6.x doesnt like an extension, but 2.x does?
		$config['file_name']		= $new_filename;
		$config['upload_path']		= $upload_path;
		$config['allowed_types']	= 'gif|jpg|jpeg|png';
		$config['max_size']			= $max_kb;
		//$config['max_width']		= $max_width;
		//$config['max_height']		= $max_height;
		$config['overwrite']		= TRUE;

		if (ee()->config->item('xss_clean_uploads') == 'n')
		{
			$config['xss_clean'] = FALSE;
		}
		else
		{
			$config['xss_clean'] = (ee()->session->userdata('group_id') == 1) ? FALSE : TRUE;
		}

		ee()->load->library('upload');

		ee()->upload->initialize($config);

		if (ee()->upload->do_upload($this->uploads[$type]) === FALSE)
		{
			// Upload Failed.  Make sure that file is gone!
			@unlink($upload_path.$filename.$extension);

			if (REQ == 'CP')
			{
				ee()->session->set_flashdata('message_failure', ee()->upload->display_errors());
				ee()->functions->redirect(BASE.AMP.'C=myaccount'.AMP.'M='.$edit_image.AMP.'id='.$id);
			}
			else
			{
				return $this->_output_error('submission', ee()->upload->display_errors());
			}
		}

		$file_info = ee()->upload->data();

		// Do we need to resize?
		$width	= $file_info['image_width'];
		$height = $file_info['image_height'];

		if ($max_width < $width OR $max_height < $height)
		{
			$axis = ((($width - $max_width) > ($height - $max_height)) ? 'width' : 'height');

			$resize_result = $this->_image_resize(
				$file_info['file_name'],
				$type,
				$axis
			);

			//reset sizes
			if ($resize_result)
			{
				if ($axis == 'height')
				{
					$width 		= ceil($width * ($max_height / $height));
					$height		= $max_height;
				}
				else
				{
					$height 	= ceil($height * ($max_width / $width));
					$width		= $max_width;
				}
			}
			//error on image lib errors
			else if (isset(ee()->image_lib->error_msg) AND
					 count(ee()->image_lib->error_msg) > 0)
			{
				return $this->_output_error('submission', ee()->image_lib->display_errors());
			}
		}

		// Delete the old file if necessary
		if ($old_filename != $new_filename)
		{
			@unlink($upload_path.$old_filename);
		}


		/**	----------------------------------------
		/**	Update DB
		/**	----------------------------------------*/

		if ($type == 'avatar')
		{
			$avatar = 'uploads/'.$new_filename;
			ee()->db->query("UPDATE exp_members SET avatar_filename = '{$avatar}', avatar_width='{$width}', avatar_height='{$height}' WHERE member_id = '".$member_id."' ");
		}
		elseif ($type == 'photo')
		{
			ee()->db->query("UPDATE exp_members SET photo_filename = '{$new_filename}', photo_width='{$width}', photo_height='{$height}' WHERE member_id = '".$member_id."' ");
		}
		else
		{
			ee()->db->query("UPDATE exp_members SET sig_img_filename = '{$new_filename}', sig_img_width='{$width}', sig_img_height='{$height}' WHERE member_id = '".$member_id."' ");
		}

		/**	----------------------------------------
		/**	Return
		/**	----------------------------------------*/

		return TRUE;
	}

	/* END _upload_image() */


	// --------------------------------------------------------------------

	/**
	 *	Image Resizing for Three Types of Uploadable Member Images
	 *
	 *	@access		private
	 *	@param		string
	 *	@param		string
	 *	@param		string
	 *	@return		string
	 */

	private function _image_resize($filename, $type = 'avatar', $axis = 'width')
	{
		if ($type == 'avatar')
		{
			$max_width	= (	ee()->config->slash_item('avatar_max_width') == '' OR
							ee()->config->item('avatar_max_width') == 0) ?
								100 : ee()->config->item('avatar_max_width');
			$max_height	= (	ee()->config->item('avatar_max_height') == '' OR
							ee()->config->item('avatar_max_height') == 0) ?
								100 : ee()->config->item('avatar_max_height');
			$image_path = rtrim( ee()->config->item('avatar_path'), '/' ) . '/' . 'uploads/';
		}
		elseif ($type == 'photo')
		{
			$max_width	= (	ee()->config->slash_item('photo_max_width') == '' OR
							ee()->config->item('photo_max_width') == 0) ?
								100 : ee()->config->item('photo_max_width');
			$max_height	= (	ee()->config->item('photo_max_height') == '' OR
							ee()->config->item('photo_max_height') == 0) ?
								100 : ee()->config->item('photo_max_height');
			$image_path = ee()->config->item('photo_path');
		}
		else
		{
			$max_width	= (	ee()->config->slash_item('sig_img_max_width') == '' OR
							ee()->config->item('sig_img_max_width') == 0) ?
								100 : ee()->config->item('sig_img_max_width');
			$max_height	= (	ee()->config->item('sig_img_max_height') == '' OR
							ee()->config->item('sig_img_max_height') == 0) ?
								100 : ee()->config->item('sig_img_max_height');
			$image_path = ee()->config->item('sig_img_path');
		}

		ee()->load->helper('text');

		$config = array(
			'image_library'		=> ee()->config->item('image_resize_protocol'),
			'library_path'		=> ee()->config->item('image_library_path'),
			'maintain_ratio'	=> TRUE,
			'master_dim'		=> $axis,
			'source_image'		=> reduce_double_slashes($image_path . '/' . $filename),
			'quality'			=> '75%',
			'width'				=> $max_width,
			'height'			=> $max_height
		);

		ee()->load->library('image_lib');

		ee()->image_lib->initialize($config);

		if ( ! ee()->image_lib->resize())
		{
			return FALSE;
		}

		return TRUE;
	}
	/* END image resize */


	// --------------------------------------------------------------------

	/**
	 *	Variable Swapping
	 *
	 *	Available even when $TMPL is not
	 *
	 *	@access		public
	 *	@param		string
	 *	@param		array
	 *	@return		string
	 */

	public function _var_swap($str, $data)
	{
		if ( ! is_array($data))
		{
			return false;
		}

		foreach ($data as $key => $val)
		{
			$str = str_replace('{'.$key.'}', $val, $str);
		}

		return $str;
	}
	/* END _var_swap() */


	// --------------------------------------------------------------------

	/**
	 *	Update Profile Views
	 *
	 *	@access		private
	 *	@return		bool
	 */

	private function _update_profile_views()
	{
		if (is_object(ee()->TMPL) AND $this->check_no(ee()->TMPL->fetch_param('log_views'))) return FALSE;

		if ( $this->member_id == 0 OR $this->member_id == ee()->session->userdata('member_id') ) return FALSE;

		// Only update once per page load
		if ( isset($this->cache[__FUNCTION__][$this->member_id])) return FALSE;

		ee()->db->set('profile_views', 'profile_views + 1', FALSE);
		ee()->db->where('member_id', $this->member_id);
		ee()->db->update('exp_members');

		$this->cache[__FUNCTION__][$this->member_id] = TRUE;

		return TRUE;
	}

	/* END _update_profile_views() */


	// --------------------------------------------------------------------

	/**
	 *	Fetch IDs for Member Fields
	 *
	 *	@access		public
	 *	@return		array
	 */

	public function _mfields()
	{
		if ( isset(ee()->TMPL) AND is_object(ee()->TMPL) AND ee()->TMPL->fetch_param('disable') !== FALSE AND stristr('member_data', ee()->TMPL->fetch_param('disable')))
		{
			return array();
		}

		if ( count( $this->mfields ) > 0 ) return $this->mfields;

		$query = ee()->db->query("SELECT m_field_id, m_field_name, m_field_label, m_field_type,
											  m_field_list_items, m_field_required, m_field_public, m_field_fmt, m_field_description
									   FROM exp_member_fields");

		foreach ($query->result_array() as $row)
		{
			$this->mfields[$row['m_field_name']] = array(
											'id'			=> $row['m_field_id'],
											'name'			=> $row['m_field_name'],
											'label'			=> $row['m_field_label'],
											'type'			=> $row['m_field_type'],
											'list'			=> $row['m_field_list_items'],
											'required'		=> $row['m_field_required'],
											'public'		=> $row['m_field_public'],
											'format'		=> $row['m_field_fmt'],
											'description'	=> $row['m_field_description']
			);
		}

		return $this->mfields;
	}

	/* END _mfields() */


	// --------------------------------------------------------------------

	/**
	 *	Determine Member ID for Page
	 *
	 *	@access		public
	 *	@return		bool
	 */

	public function _member_id()
	{
		/** --------------------------------------------
		/**  Requisite Helpers
		/** --------------------------------------------*/

		ee()->load->helper('string');

		/** --------------------------------------------
		/**  Default Variables
		/** --------------------------------------------*/

		$cat_segment	= ee()->config->item("reserved_category_word");

		$dynamic = TRUE;

		if ( ee()->TMPL->fetch_param('dynamic') !== FALSE AND $this->check_no(ee()->TMPL->fetch_param('dynamic')))
		{
			$dynamic = FALSE;
		}

		/**	----------------------------------------
		/**	Have we already set the member id?
		/**	----------------------------------------*/

		if ( $this->member_id != 0 ) return TRUE;

		/**	----------------------------------------
		/**	Track down the member id?
		/**	----------------------------------------*/

		if ( ($member_id = ee()->TMPL->fetch_param('member_id')) !== FALSE)
		{
			if (ctype_digit($member_id))
			{
				$this->member_id = $member_id;

				return TRUE;
			}
			elseif($member_id == 'CURRENT_USER' AND ee()->session->userdata['member_id'] != 0)
			{
				$this->member_id = ee()->session->userdata['member_id'];

				return TRUE;
			}
			elseif ($member_id != '')
			{
				return FALSE;
			}
		}

		if ( ($member_id = ee()->TMPL->fetch_param('user_author_id')) !== FALSE)
		{
			if (ctype_digit(trim(str_replace(array('not ', '|'), '', $member_id)))) // Allow for multiples or not
			{
				$this->member_id = $member_id;

				return TRUE;
			}
			elseif($member_id == 'CURRENT_USER' AND ee()->session->userdata['member_id'] != 0)
			{
				$this->member_id = ee()->session->userdata['member_id'];

				return TRUE;
			}
			elseif($member_id != '')
			{
				return FALSE;
			}
		}

		if ( ee()->TMPL->fetch_param('username') !== FALSE )
		{
			if (ee()->TMPL->fetch_param('username') == 'CURRENT_USER' AND ee()->session->userdata['member_id'] != 0)
			{
				$this->member_id = ee()->session->userdata['member_id'];

				return TRUE;
			}

			$query	= ee()->db->query("SELECT member_id FROM exp_members
								  WHERE username = '".ee()->db->escape_str( ee()->TMPL->fetch_param('username') )."'");

			if ( $query->num_rows() == 1 )
			{
				$this->member_id	= $query->row('member_id');

				return TRUE;
			}
			else
			{
				return FALSE;
			}
		}

		/** --------------------------------------------
		/**  Magical Lookup Parameter Prefix
		/** --------------------------------------------*/

		$search_fields = array();

		if ( is_array(ee()->TMPL->tagparams))
		{
			foreach(ee()->TMPL->tagparams as $key => $value)
			{
				if (strncmp($key, 'search:', 7) == 0)
				{
					$this->_mfields();
					$search_fields[substr($key, strlen('search:'))] = $value;
				}
			}

			if (count($search_fields) > 0)
			{
				$sql = $this->_search_fields($search_fields);

				if ($sql != '')
				{
					$query = ee()->db->query("SELECT m.member_id
											  FROM exp_members AS m, exp_member_data AS md
											  WHERE m.member_id = md.member_id ".$sql);

					if ($query->num_rows() == 1)
					{
						$this->member_id = $query->row('member_id');

						return TRUE;
					}
					else
					{
						return FALSE;
					}
				}
			}
		}

		/** --------------------------------------------
		/**  User ID or Name in the URL?
		/** --------------------------------------------*/

		if ($dynamic === TRUE AND preg_match( "#/".self::$trigger."/(\w+)/?#", ee()->uri->uri_string, $match ) )
		{
			$sql	= "SELECT member_id FROM exp_members";

			if ( is_numeric( $match['1'] ) )
			{
				$sql	.= " WHERE member_id = '".ee()->db->escape_str( $match['1'] )."'";
			}
			else
			{
				$sql	.= " WHERE username = '".ee()->db->escape_str( $match['1'] )."'";
			}

			$sql	.= " LIMIT 1";

			$query	= ee()->db->query( $sql );

			if ( $query->num_rows() == 1 )
			{
				$this->member_id	= $query->row('member_id');

				return TRUE;
			}
		}

		/**	----------------------------------------
		/**	No luck so far? Let's try query string
		/**	----------------------------------------*/

		if ( ee()->uri->uri_string != '' AND $dynamic === TRUE)
		{
			$qstring	= ee()->uri->uri_string;

			/**	----------------------------------------
			/**	Do we have a pure ID number?
			/**	----------------------------------------*/

			if ( is_numeric( $qstring) )
			{
				$this->member_id	= $qstring;
			}
			else
			{
				/**	----------------------------------------
				/**	Parse day
				/**	----------------------------------------*/

				if (preg_match("#\d{4}/\d{2}/(\d{2})#", $qstring, $match))
				{
					$partial	= substr($match[0], 0, -3);

					$qstring	= trim_slashes(str_replace($match[0], $partial, $qstring));
				}

				/**	----------------------------------------
				/**	Parse /year/month/
				/**	----------------------------------------*/

				if (preg_match("#(\d{4}/\d{2})#", $qstring, $match))
				{
					$qstring	= trim_slashes(str_replace($match['1'], '', $qstring));
				}

				/**	----------------------------------------
				/**	Parse page number
				/**	----------------------------------------*/

				if (preg_match("#^P(\d+)|/P(\d+)#", $qstring, $match))
				{
					$qstring	= trim_slashes(str_replace($match[0], '', $qstring));
				}

				/**	----------------------------------------
				/**	Parse category indicator
				/**	----------------------------------------*/

				// Text version of the category

				if (preg_match("#^".$cat_segment."/#", $qstring, $match) AND (ee()->TMPL->fetch_param('weblog') OR ee()->TMPL->fetch_param('channel')))
				{
					$qstring	= str_replace($cat_segment.'/', '', $qstring);

					$sql		= "SELECT DISTINCT cat_group FROM exp_channels WHERE ";

					$xsql	= ee()->functions->sql_andor_string(ee()->TMPL->fetch_param('channel'), 'channel_name');


					if (substr($xsql, 0, 3) == 'AND') $xsql = substr($xsql, 3);

					$sql	.= ' '.$xsql;

					$query	= ee()->db->query($sql);

					if ($query->num_rows() == 1)
					{
						$result	= ee()->db->query("SELECT cat_id FROM exp_categories
														WHERE cat_name='".ee()->db->escape_str($qstring)."'
														AND group_id='{$query->row('cat_group')}'");

						if ($result->num_rows() == 1)
						{
							$qstring	= 'C'.$result->row('cat_id');
						}
					}
				}

				// Numeric version of the category

				if (preg_match("#^C(\d+)#", $qstring, $match))
				{
					$qstring	= trim_slashes(str_replace($match[0], '', $qstring));
				}

				/**	----------------------------------------
				/**	Remove "N"
				/**	----------------------------------------*/

				// The recent comments feature uses "N" as the URL indicator
				// It needs to be removed if presenst

				if (preg_match("#^N(\d+)|/N(\d+)#", $qstring, $match))
				{
					$qstring	= trim_slashes(str_replace($match[0], '', $qstring));
				}

				/**	----------------------------------------
				/**	Numeric?
				/**	----------------------------------------*/

				if ( is_numeric( str_replace( "/", "", $qstring) ) )
				{
					$this->member_id	= $qstring;
				}
				elseif ( preg_match( "/(^|\/)(\d+)(\/|$)/s", $qstring, $match ) )
				{
					$this->member_id = $match[2];
				}
			}

			/**	----------------------------------------
			/**	Let's check the number against the DB
			/**	----------------------------------------*/

			if ( $this->member_id != '' )
			{
				$query	= ee()->db->query( "SELECT member_id FROM exp_members WHERE member_id = '".ee()->db->escape_str( $this->member_id )."' LIMIT 1" );

				if ( $query->num_rows() > 0 )
				{
					$this->member_id	= $query->row('member_id');

					return TRUE;
				}
			}
		}

		/**	----------------------------------------
		/**	When all else fails, show current user
		/**	----------------------------------------*/

		if ( ee()->session->userdata('member_id') != '0' )
		{
			$this->member_id	= ee()->session->userdata('member_id');

			return TRUE;
		}

		return FALSE;
	}

	/* END member id */

	// --------------------------------------------------------------------

	/**
	 *	Search Member Fields
	 *
	 *	Searches within the exp_members and exp_member_data fields for the user() and _member_id() methods
	 *
	 *	@access		public
	 *	@param		array
	 *	@return		string
	 */

	function _search_fields($search_fields = array())
	{
		$sql = '';

		foreach($search_fields as $field => $values)
		{
			$field = preg_replace("/^([a-z\_]+)\[[0-9]+\]$/i", '\1', $field);

			// Remove 'not ' and do a little switch
			if (strncmp($values, 'not ', 4) == 0)
			{
				$values = substr($values, 4);
				$comparision		= '!=';
				$like_comparision	= 'NOT LIKE';
			}
			else
			{
				$comparision		= '=';
				$like_comparision	= 'LIKE';
			}

			if (in_array( $field, $this->standard) OR in_array($field, array('username', 'screen_name')))
			{
				$field = "`m`.`".$field."`";
			}
			elseif (isset($this->mfields[$field]))
			{
				$field = "`md`.`m_field_id_".$this->mfields[$field]['id']."`";
			}
			else
			{
				continue;
			}

			if (strpos($values, '&&') !== FALSE)
			{
				$values = explode('&&', $values);
				$andor = (strncmp($like_comparision, 'NOT', 3) == 0) ? ' OR ' : ' AND ';
			}
			else
			{
				$values = explode('|', $values);
				$andor = (strncmp($like_comparision, 'NOT', 3) == 0) ? ' AND ' : ' OR ';
			}

			$parts = array();

			foreach($values as $value)
			{
				if ($value == '') continue;

				if ($value == 'IS_EMPTY')
				{
					$parts[] = " ".$field." ".$comparision." ''";
				}
				elseif($value == 'IS_NOT_EMPTY')
				{
					if ($comparision == '!=')
					{
						// search:field="not IS_NOT_EMPTY" - Very screwy
						$parts[] = " ".$field." = ''";
					}
					else
					{
						$parts[] = " ".$field." != ''";
					}
				}
				elseif ( substr($value, 0, 1) == '%' OR substr($value, -1) == '%')
				{
					if ( substr($value, 0, 1) == '%' AND substr($value, -1) == '%')
					{
						$parts[] = " ".$field." ".$like_comparision." '%".ee()->db->escape_like_str(substr($value, 1, -1))."%'";
					}
					elseif ( substr($value, 0, 1) == '%')
					{
						$parts[] = " ".$field." ".$like_comparision." '%".ee()->db->escape_like_str(substr($value, 1))."'";
					}
					else
					{
						$parts[] = " ".$field." ".$like_comparision." '".ee()->db->escape_like_str(substr($value, 0, -1))."%'";
					}
				}
				else
				{
					$parts[] = " ".$field." ".$comparision." '".ee()->db->escape_str($value)."'";
				}
			}

			if (count($parts) > 0)
			{
				$sql .= ' AND ( '.implode($andor, $parts).' ) ';
			}

		} // End $search_fields loop

		//echo $sql;

		return $sql;
	}
	/* END _search_fields() */




	// --------------------------------------------------------------------

	/**
	 *	Determine Entry ID for Page
	 *
	 *	@access		public
	 *	@return		bool
	 */

	public function _entry_id()
	{
		/** --------------------------------------------
		/**  Required Helpers
		/** --------------------------------------------*/

		ee()->load->helper('string');

		/** --------------------------------------------
		/**  Work to Determine Entry Id
		/** --------------------------------------------*/

		$cat_segment	= ee()->config->item("reserved_category_word");

		if ( ctype_digit( ee()->TMPL->fetch_param('entry_id') ) )
		{

			$query	= ee()->db->query( "SELECT entry_id FROM exp_channel_titles
											 WHERE entry_id = '".ee()->db->escape_str( ee()->TMPL->fetch_param('entry_id') )."'" );


			if ( $query->num_rows() > 0 )
			{
				$this->entry_id	= $query->row('entry_id');

				return TRUE;
			}
		}
		elseif ( ee()->uri->query_string != '' )
		{
			$qstring	= ee()->uri->query_string;

			/**	----------------------------------------
			/**	Do we have a pure ID number?
			/**	----------------------------------------*/

			if ( ctype_digit( $qstring ) )
			{

				$query	= ee()->db->query("SELECT entry_id FROM exp_channel_titles
											WHERE entry_id = '".ee()->db->escape_str( $qstring )."'" );


				if ( $query->num_rows() > 0 )
				{
					$this->entry_id	= $query->row('entry_id');

					return TRUE;
				}
			}
			else
			{
				/**	----------------------------------------
				/**	Parse day
				/**	----------------------------------------*/

				if (preg_match("#\d{4}/\d{2}/(\d{2})#", $qstring, $match))
				{
					$partial	= substr($match[0], 0, -3);

					$qstring	= trim_slashes(str_replace($match[0], $partial, $qstring));
				}

				/**	----------------------------------------
				/**	Parse /year/month/
				/**	----------------------------------------*/

				if (preg_match("#(\d{4}/\d{2})#", $qstring, $match))
				{
					$qstring	= trim_slashes(str_replace($match['1'], '', $qstring));
				}

				/**	----------------------------------------
				/**	Parse page number
				/**	----------------------------------------*/

				if (preg_match("#^P(\d+)|/P(\d+)#", $qstring, $match))
				{
					$qstring	= trim_slashes(str_replace($match[0], '', $qstring));
				}

				/**	----------------------------------------
				/**	Parse category indicator
				/**	----------------------------------------*/

				// Text version of the category

				if (preg_match("#^".$cat_segment."/#", $qstring, $match) AND (ee()->TMPL->fetch_param('weblog') OR ee()->TMPL->fetch_param('channel')))
				{
					$qstring	= str_replace($cat_segment.'/', '', $qstring);


					$sql = "SELECT DISTINCT cat_group FROM exp_channels WHERE cat_group != ''";


					if ( isset( ee()->TMPL->site_ids ) === TRUE )
					{
						$sql	.= " AND site_id IN ('".implode("','", ee()->TMPL->site_ids)."')";
					}


					$sql .= ee()->functions->sql_andor_string(ee()->TMPL->fetch_param('channel'), 'channel_name');



					$query	= ee()->db->query($sql);

					if ($query->num_rows() == 1)
					{
						$sql	= "SELECT cat_id FROM exp_categories WHERE cat_name='".ee()->db->escape_str($qstring)."' AND group_id='{$query->row('cat_group')}'";

						if ( isset( ee()->TMPL->site_ids ) === TRUE )
						{
							$sql	.= " site_id IN ('".implode("','", ee()->TMPL->site_ids)."')";
						}

						$result	= ee()->db->query( $sql );

						if ($result->num_rows() == 1)
						{
							$qstring	= 'C'.$result->row('cat_id');
						}
					}
				}

				/**	----------------------------------------
				/**	Numeric version of the category
				/**	----------------------------------------*/

				if (preg_match("#^C(\d+)#", $qstring, $match))
				{
					$qstring	= trim_slashes(str_replace($match[0], '', $qstring));
				}

				/**	----------------------------------------
				/**	Remove "N"
				/**	----------------------------------------*/

				// The recent comments feature uses "N" as the URL indicator
				// It needs to be removed if presenst

				if (preg_match("#^N(\d+)|/N(\d+)#", $qstring, $match))
				{
					$qstring	= trim_slashes(str_replace($match[0], '', $qstring));
				}

				/**	----------------------------------------
				/**	Parse URL title
				/**	----------------------------------------*/

				if (strstr($qstring, '/'))
				{
					$xe			= explode('/', $qstring);
					$qstring	= current($xe);
				}


				$sql	= "SELECT exp_channel_titles.entry_id
							FROM exp_channel_titles, exp_channels
							WHERE exp_channel_titles.channel_id = exp_channels.channel_id
							AND exp_channel_titles.url_title = '".ee()->db->escape_str($qstring)."'";

				if ( isset( ee()->TMPL->site_ids ) === TRUE )
				{
					$sql	.= " AND exp_channel_titles.site_id IN ('".implode("','", ee()->TMPL->site_ids)."') ";
				}


				$query	= ee()->db->query($sql);

				if ( $query->num_rows() > 0 )
				{
					$this->entry_id = $query->row('entry_id');

					return TRUE;
				}
			}
		}

		return FALSE;
	}

	/* END entry id */

	// --------------------------------------------------------------------

	/**
	 *	Create Select Field for Mailing Lists
	 *
	 *	Takes a string of tag data and parses it for each and every possible mailing list
	 *
	 *	@access		public
	 *	@param		string
	 *	@param		arrray		List of Mailing List IDs to Parse
	 *	@return		string
	 */

	public function _parse_select_mailing_lists( $data, $row = array())
	{
		/**	----------------------------------------
		/**	Fail?
		/**	----------------------------------------*/

		if ( $data == '' OR ee()->db->table_exists( 'exp_mailing_lists' ) === FALSE)
		{
			return '';
		}

		/**	----------------------------------------
		/**	Are there list items present?
		/**	----------------------------------------*/

		$sql = "SELECT DISTINCT list_id, list_title FROM exp_mailing_lists";

		$query = ee()->db->query($sql);

		if ($query->num_rows() == 0) return '';

		/**	----------------------------------------
		/**	Do we have a value?
		/**	----------------------------------------*/

		if ( isset( $row['list_id']))
		{
			$value	= $row['list_id'];
		}
		else
		{
			$value	= '';
		}

		/**	----------------------------------------
		/**	Loop
		/**	----------------------------------------*/

		$return	= '';

		foreach ( $query->result_array() as $row )
		{
			$out		= $data;
			$out		= ee()->functions->prep_conditionals($out,$row);
			$selected	= ($value == $row['list_id']) ? 'selected="selected"': '';
			$checked	= ($value == $row['list_id']) ? 'checked="checked"': '';
			$out		= str_replace( LD."selected".RD, $selected, $out );
			$out		= str_replace( LD."checked".RD, $checked, $out );
			$out		= str_replace( LD."list_id".RD, $row['list_id'], $out );
			$out		= str_replace( LD."list_title".RD, $row['list_title'], $out );
			$return		.= trim( $out )."\n";
		}

		/**	----------------------------------------
		/**	Return
		/**	----------------------------------------*/

		return $return;
	}

	/* END parse select */

	// --------------------------------------------------------------------

	/**
	 *	Parse Select Field for Member Groups
	 *
	 *	@access		public
	 *	@param		string
	 *	@param		integer
	 *	@return		string
	 */

	public function _parse_select_member_groups( $data, $selected_group_id = 0)
	{
		/**	----------------------------------------
		/**	Fail?
		/**	----------------------------------------*/

		if ( $data == '' )
		{
			return '';
		}

		/**	----------------------------------------
		/**	Are there list items present?
		/**	----------------------------------------*/

		if ( ee()->TMPL->fetch_param('allowed_groups') === FALSE OR
			 ee()->TMPL->fetch_param('allowed_groups') == '')
		{
			return '';
		}

		$sql = "SELECT DISTINCT group_id, group_title
				FROM 	exp_member_groups
				WHERE 	group_id
				NOT IN (1,2,3,4) " .
				ee()->functions->sql_andor_string(
					ee()->TMPL->fetch_param('allowed_groups'),
					'group_id'
				);

		$query = ee()->db->query($sql);

		if ($query->num_rows() == 0) return '';

		/**	----------------------------------------
		/**	Loop
		/**	----------------------------------------*/

		$return	= '';

		foreach ( $query->result_array() as $row )
		{
			$out		= $data;
			$out		= ee()->functions->prep_conditionals($out,$row);
			$selected	= ($selected_group_id == $row['group_id']) ? 'selected="selected"': '';
			$checked	= ($selected_group_id == $row['group_id']) ? 'checked="checked"': '';
			$out		= str_replace( LD."selected".RD, $selected, $out );
			$out		= str_replace( LD."checked".RD, $checked, $out );
			$out		= str_replace( LD."group_id".RD, $row['group_id'], $out );
			$out		= str_replace( LD."group_title".RD, $row['group_title'], $out );
			$return		.= trim( $out )."\n";
		}

		/**	----------------------------------------
		/**	Return
		/**	----------------------------------------*/

		return $return;
	}

	/* END parse select */


	// --------------------------------------------------------------------

	/**
	 *	Parse Select Field for Member Custom Fields
	 *
	 *	@access		public
	 *	@param		string
	 *	@param		array
	 *	@param		string
	 *	@return		string
	 */

	public function _parse_select( $key = '', $row = array(), $data = '' )
	{
		/**	----------------------------------------
		/**	Fail?
		/**	----------------------------------------*/

		if ( $key == '' OR $data == '' )
		{
			return '';
		}

		/**	----------------------------------------
		/**	Are there list items present?
		/**	----------------------------------------*/

		if ( ! isset( $this->mfields[$key]['list'] ) OR $this->mfields[$key]['list'] == '' )
		{
			return '';
		}

		// --------------------------------------------
		//  Cache?
		// --------------------------------------------

		$cache_name = __FUNCTION__;
		$cache_hash = $this->_imploder(func_get_args());

		if (isset($this->cached[$cache_name][$cache_hash]))
		{
			return $this->cached[$cache_name][$cache_hash];
		}

		/**	----------------------------------------
		/**	Do we have a value?
		/**	----------------------------------------*/

		if ( isset( $row['m_field_id_'.$this->mfields[$key]['id']] ) )
		{
			$value	= $row['m_field_id_'.$this->mfields[$key]['id']];
		}
		else
		{
			$value	= '';
		}

		/**	----------------------------------------
		/**	Create an array from value
		/**	----------------------------------------*/

		$arr	= preg_split( "/\r|\n/", $value );

		/**	----------------------------------------
		/**	Loop
		/**	----------------------------------------*/

		$return	= '';
		$count  = 0;

		foreach ( preg_split( "/\r|\n/", $this->mfields[$key]['list'] ) as $val )
		{
			$out		= $data;
			$selected	= ( in_array( $val, $arr ) ) ? 'selected="selected"': '';
			$checked	= ( in_array( $val, $arr ) ) ? 'checked="checked"': '';
			$out		= str_replace( LD."selected".RD, $selected, $out );
			$out		= str_replace( LD."checked".RD, $checked, $out );
			$out		= str_replace( LD."value".RD, $val, $out );
			$out		= str_replace( LD."option_index".RD, ++$count, $out );
			$out		= ee()->functions->prep_conditionals($out, array('value' => $val));
			$return		.= trim( $out )."\n";
		}

		///exit($return);

		/**	----------------------------------------
		/**	Return
		/**	----------------------------------------*/

		$this->cached[$cache_name][$cache_hash] = $return;

		return $this->cached[$cache_name][$cache_hash];
	}

	/* END parse select */


	// --------------------------------------------------------------------

	/**
	 *	Creates a Form
	 *
	 *	Takes an Array of Form Data and Automatically Builds Our Form Output
	 *
	 *	@access		public
	 *	@param		array
	 *	@return		string
	 */

	public function _form( $form_data = array() )
	{
		if ( count( $form_data ) == 0 AND ! isset( $this->form_data ) ) return '';

		if ( ! isset( $this->form_data['tagdata'] ) OR $this->form_data['tagdata'] == '' )
		{
			$tagdata	=	ee()->TMPL->tagdata;
		}
		else
		{
			$tagdata	= $this->form_data['tagdata'];
			unset( $this->form_data['tagdata'] );
		}

		if (ee()->TMPL->fetch_param('override_return') !== FALSE AND ee()->TMPL->fetch_param('override_return') != '')
		{
			$override_return = ee()->TMPL->fetch_param('override_return');

			if ( preg_match( "/".LD."\s*path=(.*?)".RD."/", $override_return, $match ) > 0 )
			{
				$override_return = ee()->functions->create_url( $match['1'] );
			}
			elseif ( stristr( $override_return, "http://" ) === FALSE AND
					 stristr( $override_return, "https://" ) === FALSE )
			{
				$override_return = ee()->functions->create_url( $override_return );
			}

			$this->params['override_return'] = $override_return;
		}

		/**	----------------------------------------
		/**	Insert params
		/**	----------------------------------------*/

		if ( ($this->params_id = $this->_insert_params()) === FALSE )
		{
			$this->params_id	= 0;
		}

		$this->form_data['params_id']	= $this->params_id;

		/** --------------------------------------------
		/**  Special Handling for return="" parameter
		/** --------------------------------------------*/

		foreach(array('return', 'RET') as $val)
		{
			if (isset($this->form_data[$val]) AND $this->form_data[$val] !== FALSE AND $this->form_data[$val] != '')
			{
				$this->form_data[$val] = str_replace($this->t_slash, '/', $this->form_data[$val]);

				if ( preg_match( "/".LD."\s*path=(.*?)".RD."/", $this->form_data[$val], $match ))
				{
					$this->form_data[$val] = ee()->functions->create_url( $match['1'] );
				}
				elseif ( stristr( $this->form_data[$val], "http://" ) === FALSE AND
						 stristr( $this->form_data[$val], "https://" ) === FALSE )
				{
					$this->form_data[$val] = ee()->functions->create_url( $this->form_data[$val] );
				}
			}
		}

		/**	----------------------------------------
		/**	Generate form
		/**	----------------------------------------*/

		$arr	=	array(
			'action'		=> ee()->functions->fetch_site_index(),
			'id'			=> $this->form_data['id'],
			'enctype'		=> ( $this->multipart ) ? 'multi': '',
			'onsubmit'		=> ( isset($this->form_data['onsubmit'])) ? $this->form_data['onsubmit'] : ''
		);

		$arr['onsubmit'] = ( ee()->TMPL->fetch_param('onsubmit') ) ? ee()->TMPL->fetch_param('onsubmit') : $arr['onsubmit'];

		if ( isset( $this->form_data['name'] ) !== FALSE )
		{
			$arr['name']	= $this->form_data['name'];
			unset( $this->form_data['name'] );
		}

		unset( $this->form_data['id'] );
		unset( $this->form_data['onsubmit'] );

		$arr['hidden_fields']	= $this->form_data;

		/** --------------------------------------------
		/**  HTTPS URLs?
		/** --------------------------------------------*/

		if (ee()->TMPL->fetch_param('secure_action') == 'yes')
		{
			if (isset($arr['action']))
			{
				$arr['action'] = str_replace('http://', 'https://', $arr['action']);
			}
		}

		if (ee()->TMPL->fetch_param('secure_return') == 'yes')
		{
			foreach(array('return', 'RET') as $return_field)
			{
				if (isset($arr['hidden_fields'][$return_field]))
				{
					if ( preg_match( "/".LD."\s*path=(.*?)".RD."/", $arr['hidden_fields'][$return_field], $match ) > 0 )
					{
						$arr['hidden_fields'][$return_field] = ee()->functions->create_url( $match['1'] );
					}
					elseif ( stristr( $arr['hidden_fields'][$return_field], "http://" ) === FALSE )
					{
						$arr['hidden_fields'][$return_field] = ee()->functions->create_url( $arr['hidden_fields'][$return_field] );
					}

					$arr['hidden_fields'][$return_field] = str_replace('http://', 'https://', $arr['hidden_fields'][$return_field]);
				}
			}
		}

		/** --------------------------------------------
		/**  Custom Error Page
		/** --------------------------------------------*/

		if (ee()->TMPL->fetch_param('error_page') !== 'FALSE' AND
			ee()->TMPL->fetch_param('error_page') != '')
		{
			$arr['hidden_fields']['error_page'] = str_replace($this->t_slash, '/', ee()->TMPL->fetch_param('error_page'));
		}

		/** --------------------------------------------
		/**  Override Form Attributes with form:xxx="" parameters
		/** --------------------------------------------*/

		$extra_attributes = array();

		if (is_object(ee()->TMPL) AND ! empty(ee()->TMPL->tagparams))
		{
			foreach(ee()->TMPL->tagparams as $key => $value)
			{
				if (strncmp($key, 'form:', 5) == 0)
				{
					if (isset($arr[substr($key, 5)]))
					{
						$arr[substr($key, 5)] = $value;
					}
					else
					{
						$extra_attributes[substr($key, 5)] = $value;
					}
				}
			}
		}

		/** --------------------------------------------
		/**  Create Form
		/** --------------------------------------------*/

		$r	= ee()->functions->form_declaration( $arr );

		$r	.= stripslashes($tagdata);

		$r	.= "</form>";

		/**	----------------------------------------
		/**	 Add <form> attributes from
		/**	----------------------------------------*/

		$allowed = array('accept', 'accept-charset', 'enctype', 'method', 'action',
						 'name', 'target', 'class', 'dir', 'id', 'lang', 'style',
						 'title', 'onclick', 'ondblclick', 'onmousedown', 'onmousemove',
						 'onmouseout', 'onmouseover', 'onmouseup', 'onkeydown',
						 'onkeyup', 'onkeypress', 'onreset', 'onsubmit');

		foreach($extra_attributes as $key => $value)
		{
			if ( in_array($key, $allowed) == FALSE AND strncmp($key, 'data-', 5) != 0) continue;

			$r = str_replace( "<form", '<form '.$key.'="'.htmlspecialchars($value).'"', $r );
		}

		/**	----------------------------------------
		/**	Return
		/**	----------------------------------------*/

		return str_replace('{/exp:', LD.$this->t_slash.'exp:', str_replace($this->t_slash, '/', $r));
	}

	/* END form */

	// --------------------------------------------------------------------

	/**
	 *	Return a Parameter for the Submitted Form
	 *
	 *	@access		public
	 *	@param		string
	 *	@param		string
	 *	@return		string
	 */

	public function _param( $which = '', $type = 'all' )
	{
		/**	----------------------------------------
		/**	Which?
		/**	----------------------------------------*/

		if ( $which == '' ) return FALSE;

		/**	----------------------------------------
		/**	Params set?
		/**	----------------------------------------*/

		if ( count( $this->params ) == 0 )
		{
			/**	----------------------------------------
			/**	Empty id?
			/**	----------------------------------------*/

			if ( ! $this->params_id = ee()->input->get_post('params_id') )
			{
				return FALSE;
			}

			/**	----------------------------------------
			/**	Select from DB
			/**	----------------------------------------*/

			$query	= ee()->db->query( "SELECT data FROM exp_user_params
								   WHERE hash = '".ee()->db->escape_str( $this->params_id )."'" );

			/**	----------------------------------------
			/**	Empty?
			/**	----------------------------------------*/

			if ( $query->num_rows() == 0 ) return FALSE;

			/**	----------------------------------------
			/**	Unserialize
			/**	----------------------------------------*/

			$this->params			= unserialize( $query->row('data') );
			$this->params['set']	= TRUE;

			/**	----------------------------------------
			/**	Delete
			/**	----------------------------------------*/

			ee()->db->query( "DELETE FROM exp_user_params WHERE entry_date < ". (ee()->localize->now-7200) );
		}

		/**	----------------------------------------
		/**	Fetch from params array
		/**	----------------------------------------*/

		if ( isset( $this->params[$which] ) )
		{
			$return	= str_replace( "&#47;", "/", $this->params[$which] );

			return $return;
		}

		/**	----------------------------------------
		/**	Fetch TMPL
		/**	----------------------------------------*/

		if ( isset(ee()->TMPL) AND is_object(ee()->TMPL) AND ee()->TMPL->fetch_param($which) )
		{
			return ee()->TMPL->fetch_param($which);
		}

		/**	----------------------------------------
		/**	Return
		/**	----------------------------------------*/

		return FALSE;
	}
	// END params


	// --------------------------------------------------------------------

	/**
	 * Insert Parameters for a Form
	 *
	 * @access	private
	 * @param	array   $params				array of params to hash
	 * @return	string						hash key for param insert
	 */
	private function _insert_params($params = array())
	{
		//	----------------------------------------
		//	Empty?
		//	----------------------------------------

		if ( count( $params ) > 0 )
		{
			$this->params	= $params;
		}
		elseif ( ! isset($this->params) OR count($this->params) == 0)
		{
			return FALSE;
		}

		//	----------------------------------------
		//	Delete excess when older than 2 hours
		//	----------------------------------------

		ee()->db->where("entry_date < ", (ee()->localize->now - 7200))
				->delete('user_params');

		//	----------------------------------------
		//	Insert
		//	----------------------------------------

		$hash = ee()->functions->random('alpha', 25);

		ee()->db->insert(
			'exp_user_params',
			array(
				'hash'			=> $hash,
				'entry_date'	=> ee()->localize->now,
				'data'			=> serialize($this->params)
			)
		);

		//	----------------------------------------
		//	Return
		//	----------------------------------------

		return $hash;
	}
	// END insert params


	// --------------------------------------------------------------------

	/**
	 *	Automatic way of determining ORDER BY clause and adding to SQL string
	 *
	 *	@access		private
	 *	@param		string
	 *	@param		array
	 *	@return		string
	 */

	private function _order_sort( $sql , $additional = array())
	{
		/** ----------------------------------------
		/**	Order
		/** ----------------------------------------*/

		$others = array( 'username', 'screen_name', 'email', 'group_id', 'join_date', 'total_entries' );

		$this->standard	= array_merge( $this->standard, $others );

		/** ----------------------------------------
		/**	Control ordering from POST / COOKIE, or
		/** ----------------------------------------*/

		$sub_sql	= '';

		if ( ee()->TMPL->fetch_param('dynamic_parameters') !== FALSE &&
			 ee()->TMPL->fetch_param('dynamic_parameters') != 'no' &&
			 (
				( ee()->input->post('user_orderby') !== FALSE AND ee()->input->post('user_orderby') != '' )
				OR
				( ee()->input->cookie('user_orderby') !== FALSE AND ee()->input->cookie('user_orderby') != '' )
			 )
		   )
		{
			$this->_mfields();

			/** ----------------------------------------
			/**	Prepare multiple orders
			/** ----------------------------------------*/

			if ( isset( $_POST['user_orderby'] ) === TRUE AND is_array( $_POST['user_orderby'] ) === TRUE AND count( $_POST['user_orderby'] ) > 0 )
			{
				$orderby	= $_POST['user_orderby'];
			}
			elseif ( stristr( ",", ee()->input->post('user_orderby') ) )
			{
				$orderby	= explode( ee()->input->post('user_orderby') );
			}
			elseif ( stristr( ",", ee()->input->cookie('user_orderby') ) )
			{
				$orderby	= explode( ee()->input->cookie('user_orderby') );
			}
			else
			{
				$orderby	= ( ee()->input->post('user_orderby') !== FALSE ) ? array( ee()->input->post('user_orderby') ): array( ee()->input->cookie('user_orderby') );
			}

			/** ----------------------------------------
			/**	Prepare multiple sorts
			/** ----------------------------------------*/

			$sort	= array();

			foreach ( $orderby as $key => $order )
			{
				if ( stristr( $order, "|" ) )
				{
					$temp	= explode( "|", $order );

					$orderby[$key]	= $temp[0];
					$sort[$key]		= $temp[1];
				}
			}

			if ( count( $sort ) == 0 )
			{
				if ( ee()->input->post('user_sort') !== FALSE AND ee()->input->post('user_sort') != '' )
				{
					$sort	= ( ee()->input->post('user_sort') != 'asc' ) ? array('DESC'): array('ASC');
				}
				elseif ( ee()->input->cookie('user_sort') !== FALSE AND ee()->input->cookie('user_sort') != '' )
				{
					$sort	= ( ee()->input->cookie('user_sort') != 'asc' ) ? array('DESC'): array('ASC');
				}
				else
				{
					$sort	= array('ASC');
				}
			}

			/** ----------------------------------------
			/**	Set cookies
			/** ----------------------------------------*/

			if ( ee()->input->post('user_orderby') !== FALSE )
			{
				$this->set_cookie( 'user_orderby', strtolower( implode( ",", $orderby ) ), 0 );
				$this->set_cookie( 'user_sort', strtolower( implode( ",", $sort ) ), 0 );
			}

			/** ----------------------------------------
			/**	Loop and order
			/** ----------------------------------------*/

			foreach ( $orderby as $key => $order )
			{
				$s	= "";

				if ( isset( $sort[$key] ) === TRUE )
				{
					$s	= " ".$sort[$key].",";
				}
				else
				{
					$s	= " ".$sort[0].",";
				}

				if ( $order == 'random' )
				{
					$sub_sql	.= "random";
				}
				elseif ( isset($additional[$order]))
				{
					$sub_sql	.= " ".$additional[$order].".".$order.$s;
				}
				elseif ( in_array( $order, $this->standard ) )
				{
					$sub_sql	.= " m.".$order.$s;
				}
				elseif ( isset( $this->mfields[ $order ] ) !== FALSE )
				{
					$sub_sql	.= " md.m_field_id_".$this->mfields[ $order ]['id'].$s;
				}
			}

			if ( $sub_sql != '' )
			{
				if ( stristr( $sub_sql, 'random' ) )
				{
					$sql	.= " ORDER BY rand()";
				}
				else
				{
					$sql	.= " ORDER BY ".substr( $sub_sql, 0, -1 );
				}
			}
		}

		/** ----------------------------------------
		/**	Control ordering from TMPL
		/** ----------------------------------------*/

		elseif ( ee()->TMPL->fetch_param('orderby') !== FALSE AND ee()->TMPL->fetch_param('orderby') != '' )
		{
			$this->_mfields();

			/** ----------------------------------------
			/**	Prepare multiple orders
			/** ----------------------------------------*/

			if ( stristr( ee()->TMPL->fetch_param('orderby'), "|" ) )
			{
				$orderby	= explode( "|", ee()->TMPL->fetch_param('orderby') );
			}
			else
			{
				$orderby	= array( ee()->TMPL->fetch_param('orderby') );
			}

			/** ----------------------------------------
			/**	Prepare multiple sorts
			/** ----------------------------------------*/

			if ( ee()->TMPL->fetch_param('sort') !== FALSE AND ee()->TMPL->fetch_param('sort') != '' )
			{
				if ( stristr( ee()->TMPL->fetch_param('sort'), "|" ) )
				{
					$sort	= explode( "|", strtoupper( ee()->TMPL->fetch_param('sort') ) );
				}
				else
				{
					$sort	= array( strtoupper( ee()->TMPL->fetch_param('sort') ) );
				}
			}
			else
			{
				$sort	= array('DESC');
			}

			/** ----------------------------------------
			/**	Loop and order
			/** ----------------------------------------*/

			foreach ( $orderby as $key => $order )
			{
				$s	= "";

				if ( isset( $sort[$key] ) === TRUE )
				{
					$s	= " ".$sort[$key].",";
				}
				else
				{
					$s	= " ".$sort[0].",";
				}

				if ( $order == 'random' )
				{
					$sub_sql	= "random";
				}
				elseif ( isset($additional[$order]))
				{
					$sub_sql	.= " ".$additional[$order].".".$order.$s;
				}
				elseif ( in_array( $order, $this->standard ) )
				{
					$sub_sql	.= " m.".$order.$s;
				}
				elseif ( isset( $this->mfields[ $order ] ) !== FALSE )
				{
					$sub_sql	.= " md.m_field_id_".$this->mfields[ $order ]['id'].$s;
				}
			}

			if ( $sub_sql != '' )
			{
				if ( stristr( $sub_sql, 'random' ) )
				{
					$sql	.= " ORDER BY rand()";
				}
				else
				{
					$sql	.= " ORDER BY ".substr( $sub_sql, 0, -1 );
				}
			}
		}

		/** ----------------------------------------
		/**	Limit
		/** ----------------------------------------*/

		if ( ee()->TMPL->fetch_param('limit') !== FALSE AND ctype_digit( ee()->TMPL->fetch_param('limit') ) )
		{
			$this->limit	= ee()->TMPL->fetch_param('limit');
		}

		if ( ee()->TMPL->fetch_param('dynamic_parameters') !== FALSE AND ee()->TMPL->fetch_param('dynamic_parameters') != 'no' )
		{
			if ( ee()->input->post('user_limit') !== FALSE AND ee()->input->post('user_limit') != '' )
			{
				$this->limit	= ee()->input->post('user_limit');
				$this->set_cookie( 'limit', ee()->input->post('user_limit'), 0 );
			}
			elseif ( ee()->input->cookie('user_limit') !== FALSE AND ee()->input->cookie('user_limit') != '' )
			{
				$this->limit	= ee()->input->cookie('user_limit');
			}
		}

		/** ----------------------------------------
		/**	Return
		/** ----------------------------------------*/

		return $sql;
	}

	/* END order sort */


	// --------------------------------------------------------------------

	/**
	 *	Prepare Pagination
	 *
	 *	@access		public
	 *	@param		string
	 *	@param		string
	 *	@return		string
	 */

	public function _prep_pagination( $sql, $url_suffix = '', $prefix = TRUE )
	{
		$tsql	= $sql;

		$offset 		= ( ee()->TMPL->fetch_param('offset') OR ctype_digit(ee()->TMPL->fetch_param('offset'))) ? ee()->TMPL->fetch_param('offset'): 0;

		$tsql	= preg_replace("/SELECT (DISTINCT)?(.*?)\s+FROM\s+/is", 'SELECT \\1 COUNT(*) AS count FROM ', $tsql);

		$query = ee()->db->query( $tsql );

		if ($query->row('count') == 0)
		{
			return '';
		}

		if ($offset >= $query->row('count'))
		{
			return '';
		}

		//get pagination info
		$pagination_data = $this->universal_pagination(array(
			'sql'					=> $sql,
			//'url_suffix' 			=> $url_suffix,
			'total_results'			=> $this->absolute_results,
			'tagdata'				=> ee()->TMPL->tagdata,
			'limit'					=> $this->limit,
			'uri_string'			=> ee()->uri->uri_string,
			//'current_page'		=> $this->p_page,
			'offset'				=> $offset,
			'auto_paginate'			=> TRUE,
			'paginate_prefix'		=> 'user_'
		));

		$this->absolute_results	= $query->row('count') - $offset;
		$this->absolute_count	= ( ! empty( $pagination_data['pagination_page'] ) ) ? $pagination_data['pagination_page']: 0;

		//if we paginated, sort the data
		if ($pagination_data['paginate'] === TRUE)
		{
			ee()->TMPL->tagdata		= $pagination_data['tagdata'];

			return $pagination_data['sql'];
		}
		else
		{
			return $sql . " LIMIT ".ceil($offset).", ".ceil($this->limit);
		}
	}
	// END prep pagination


	// --------------------------------------------------------------------

	/**
	 *	Force HTTPS/SSL on Form Submission
	 *
	 *	@access		public
	 *	@return		redirect
	 */

	private function _force_https()
	{
		if ( ! isset($_POST['ACT']) OR $this->_param('secure_action') != 'yes') return;

		if ( ! isset($_SERVER['HTTPS']) OR strtolower($_SERVER['HTTPS']) != 'on')
		{
			header("Location: https://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']);
			exit;
		}
	}
	/* END force_https() */


	// --------------------------------------------------------------------

	/**
	 *	Output Custom Error Template
	 *
	 *	@access		public
	 *	@param		string
	 *	@param		array
	 *	@return		string
	 */

	private function _output_error($type, $errors)
	{
		// -------------------------------------
		//	auto restore csrf_token? (ee 2.7 only)
		// -------------------------------------

		if (version_compare($this->ee_version, '2.7', '>='))
		{
			$errors = is_array($errors) ? $errors : array($errors);

			$restore = true;

			foreach($errors as $error)
			{
				foreach (array(
						lang('not_authorized'),
						lang('unauthorized_access'),
						lang('invalid_action')
					) as $exception
				)
				{
					if (strpos($error, $exception) !== FALSE)
					{
						$restore = false;
					}
				}
			}

			if ($restore)
			{
				$this->restore_xid();
			}
		}

		// -------------------------------------
		//	error page
		// -------------------------------------

		if ( REQ == 'PAGE' AND is_object(ee()->TMPL) AND ee()->TMPL->fetch_param('error_page') !== FALSE)
		{
			$_POST['error_page'] = str_replace($this->t_slash, '/', ee()->TMPL->fetch_param('error_page'));
		}

		if ( ! isset($_POST['error_page']) OR $_POST['error_page'] == '' OR ! stristr($_POST['error_page'], '/'))
		{
			return $this->show_error($errors);
		}

		/** --------------------------------------------
		/**  Retrieve Template
		/** --------------------------------------------*/

		$x = explode('/', $_POST['error_page']);

		if ( ! isset($x[1])) $x[1] = 'index';

		$query = ee()->db->query(  "SELECT template_data, group_name, template_name, template_type
									FROM exp_templates AS t, exp_template_groups AS tg
									WHERE t.site_id = '".ee()->db->escape_str(ee()->config->item('site_id'))."'
									AND t.group_id = tg.group_id
									AND t.template_name = '".ee()->db->escape_str($x[1])."'
									AND tg.group_name = '".ee()->db->escape_str($x[0])."'
									LIMIT 1");

		if ($query->num_rows() == 0)
		{
			return $this->show_error($errors);
		}

		$template_data = stripslashes($query->row('template_data'));

		/** --------------------------------------------
		/**  Template as File?
		/** --------------------------------------------*/

		if (ee()->config->item('save_tmpl_files') == 'y' AND ee()->config->item('tmpl_file_basepath') != '')
		{
			$basepath = rtrim(ee()->config->item('tmpl_file_basepath'), '/').'/';


			ee()->load->library('api');
			ee()->api->instantiate('template_structure');
			$basepath .= ee()->config->item('site_short_name').'/'.
						 $query->row('group_name').'.group/'.
						 $query->row('template_name').
						 ee()->api_template_structure->file_extensions($query->row('template_type'));


			if (file_exists($basepath))
			{
				$template_data = file_get_contents($basepath);
			}
		}

		switch($type)
		{
			case 'submission' : $heading = lang('submission_error');
				break;
			case 'general'    : $heading = lang('general_error');
				break;
			default           : $heading = lang('submission_error');
				break;
		}

		/** --------------------------------------------
		/**  Create List of Errors for Content
		/** --------------------------------------------*/

		$content  = '<ul>';

		if ( ! is_array($errors))
		{
			$content.= "<li>".$errors."</li>\n";
		}
		else
		{
			foreach ($errors as $val)
			{
				$content.= "<li>".$val."</li>\n";
			}
		}

		$content .= "</ul>";

		/** --------------------------------------------
		/**  Data Array
		/** --------------------------------------------*/

		$data = array(	'title' 		=> lang('error'),
						'heading'		=> $heading,
						'content'		=> $content,
						'redirect'		=> '',
						'meta_refresh'	=> '',
						'link'			=> array('javascript:history.go(-1)', lang('return_to_previous')),
						'charset'		=> ee()->config->item('charset')
					 );

		if (is_array($data['link']) AND count($data['link']) > 0)
		{
			$refresh_msg = ($data['redirect'] != '' AND $this->refresh_msg == TRUE) ? lang('click_if_no_redirect') : '';

			$ltitle = ($refresh_msg == '') ? $data['link']['1'] : $refresh_msg;

			$url = (strtolower($data['link']['0']) == 'javascript:history.go(-1)') ? $data['link']['0'] : ee()->security->xss_clean($data['link']['0']);

			$data['link'] = "<a href='".$url."'>".$ltitle."</a>";
		}

		/** --------------------------------------------
		/**  For a Page Request, we parse variables and return
		/**  to let the Template Parser do the rest of the work
		/** --------------------------------------------*/

		if (REQ == 'PAGE')
		{
			foreach ($data as $key => $val)
			{
				$template_data = str_replace('{'.$key.'}', $val, $template_data);
			}

			return str_replace('/', $this->t_slash, $template_data);
		}

		// --------------------------------------------
		//	Parse as Template
		// --------------------------------------------

		require_once 'addon_builder/parser.addon_builder.php';

		ee()->TMPL = $GLOBALS['TMPL'] = new Addon_builder_parser_user();
		ee()->TMPL->global_vars	= array_merge(ee()->TMPL->global_vars, $data);
		$out = $GLOBALS['TMPL']->process_string_as_template($template_data);

		exit($out);
	}
	/* END _output_error() */


	// --------------------------------------------------------------------

	/**
	 * Timezones
	 *
	 * This array is used to render the localization pull-down menu
	 *
	 * @access	public
	 * @return	array
	 */

	public function timezones()
	{
		// Note: Don't change the order of these even though
		// some items appear to be in the wrong order
		return array(
			'UM12'		=> -12,
			'UM11'		=> -11,
			'UM10'		=> -10,
			'UM95'		=> -9.5,
			'UM9'		=> -9,
			'UM8'		=> -8,
			'UM7'		=> -7,
			'UM6'		=> -6,
			'UM5'		=> -5,
			'UM45'		=> -4.5,
			'UM4'		=> -4,
			'UM35'		=> -3.5,
			'UM3'		=> -3,
			'UM2'		=> -2,
			'UM1'		=> -1,
			'UTC'		=> 0,
			'UP1'		=> +1,
			'UP2'		=> +2,
			'UP3'		=> +3,
			'UP35'		=> +3.5,
			'UP4'		=> +4,
			'UP45'		=> +4.5,
			'UP5'		=> +5,
			'UP55'		=> +5.5,
			'UP575'		=> +5.75,
			'UP6'		=> +6,
			'UP65'		=> +6.5,
			'UP7'		=> +7,
			'UP8'		=> +8,
			'UP875'		=> +8.75,
			'UP9'		=> +9,
			'UP95'		=> +9.5,
			'UP10'		=> +10,
			'UP105'		=> +10.5,
			'UP11'		=> +11,
			'UP115'		=> +11.5,
			'UP12'		=> +12,
			'UP1275'	=> +12.75,
			'UP13'		=> +13,
			'UP14'		=> +14
		);
	}
	//END timezones

	// --------------------------------------------------------------------

	/**
	 * Parses {user:timezone_menu} tag
	 *
	 * @access	public
	 * @param	string $tagdata	tagdata to parse tag from
	 * @return	[type]          [description]
	 */

	public function parse_timezone_menu_tag($tagdata = '', $timezone = 'UTC')
	{
		if (version_compare($this->ee_version, '2.6.0', '>='))
		{
			preg_match_all(
				"/" . LD . "user:timezone_menu(.*?)" . RD . "/ims",
				$tagdata,
				$matches,
				PREG_SET_ORDER
			);

			if ($matches)
			{
				//is it old? lets make it new again <3
				if (array_key_exists($timezone, $this->timezones()))
				{
					$timezone = ee()->localize->get_php_timezone($timezone);
				}

				foreach ($matches as $match_set)
				{
					// Checking for variables/tags embedded within tags
					// {exp:channel:entries channel="{master_channel_name}"}
					if (stristr(substr($match_set[0], 1), LD) !== FALSE)
					{
						$match_set[0] = ee()->functions->full_tag(
							$match_set[0],
							$tagdata
						);
					}

					$params = ee()->functions->assign_parameters($match_set[0]);

					$wrapper_open		= '<div ';
					$wrapper_class_set	= FALSE;
					$country_params		= array();
					$timezone_params	= array();

					// -------------------------------------
					//	get params for items
					// -------------------------------------

					foreach ($params as $param_name => $param_value)
					{
						//country
						if (substr($param_name, 0, 8) == 'country:')
						{
							$country_params[substr($param_name,8)] = $param_value;
						}
						//timezone
						else if (substr($param_name, 0, 9) == 'timezone:')
						{
							$timezone_params[substr($param_name,9)] = $param_value;
						}
						//wrapper
						else if ($param_name !== 'name')
						{
							if ($param_name == 'class')
							{
								$wrapper_class_set = TRUE;
							}

							$wrapper_open .= $param_name . '="' . $param_value . '" ';
						}
					}

					//basic class?
					if ( ! $wrapper_class_set)
					{
						$wrapper_open .= 'class="timezone_menu_wrapper" ';
					}

					$wrapper_open .= ">";

					$menu = ee()->localize->timezone_menu($timezone, 'timezone');

					// -------------------------------------
					//	parse country items
					// -------------------------------------

					//if you need this custom, better to do it with
					//JS listeners and leave this one alone.
					unset($country_params['onchange']);

					$country_add = '';

					foreach ($country_params as $cp_name => $cp_value)
					{
						$country_add .= $cp_name . '="' . $cp_value . '" ';
					}

					//add it in
					$menu = str_replace(
						'<select name="tz_country"',
						'<select name="tz_country" ' . $country_add,
						$menu
					);

					// -------------------------------------
					//	parse timezone items
					// -------------------------------------

					//this cannot be changed or else it wont work
					//with user
					unset($timezone_params['name']);

					$timezone_add = '';

					foreach ($timezone_params as $tp_name => $tp_value)
					{
						//ID has a special case because of the JS
						if ($tp_name = 'id')
						{
							$menu = str_replace('timezone_select', $tp_value, $menu);
						}

						$timezone_add .= $tp_name . '="' . $tp_value . '" ';
					}

					//add it in
					$menu = str_replace(
						'<select name="server_timezone"',
						'<select name="server_timezone" ' . $timezone_add,
						$menu
					);

					// -------------------------------------
					//	finish outer wrapper
					// -------------------------------------

					$menu = $wrapper_open . $menu . '</div>';

					$tagdata = str_replace($match_set[0], $menu, $tagdata);
				}
			}
			//END if ($matches)
		}
		//END version_compare

		return $tagdata;
	}
	//END parse_timezone_menu_tag


	// --------------------------------------------------------------------

	/**
	 * EE 2.7+ restore xid with version check
	 *
	 * @access	public
	 * @return	void
	 */

	public function restore_xid()
	{
		//deprecated in 2.8. Weeee!
		if (version_compare($this->ee_version, '2.7', '>=') &&
			version_compare($this->ee_version, '2.8', '<'))
		{
			ee()->security->restore_xid();
		}
	}
	//END restore_xid
}
// END CLASS User
