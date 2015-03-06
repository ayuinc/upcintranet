<?php if ( ! defined('BASEPATH') ) exit('No direct script access allowed');

/**
 * User - Extension Class
 *
 * @package		Solspace:User
 * @author		Solspace, Inc.
 * @copyright	Copyright (c) 2008-2014, Solspace, Inc.
 * @link		http://solspace.com/docs/user
 * @license		http://www.solspace.com/license_agreement
 * @version		3.5.0
 * @filesource	user/ext.user.php
 */

require_once 'addon_builder/extension_builder.php';
require_once 'constants.user.php';

class User_ext extends Extension_builder_user
{
	public $name				= "User";
	public $version				= "";
	public $description			= "";
	public $settings_exist		= "n";
	public $docs_url			= USER_DOCS_URL;
	public $settings			= array();
	public $user_base			= '';
	public $required_by			= array('module');

	/**
	 * Shim for removed extension calls
	 * that will get hit before upgrade
	 *
	 * @var	array
	 * @see	__call
	 */
	protected $removed_functions = array('ajax');

	// --------------------------------------------------------------------

	/**
	 *	Constructor
	 *
	 *	@access		public
	 *	@param		array
	 *	@return		null
	 */

	public function __construct( $settings = '' )
	{
		// --------------------------------------------
		//  Load Parent Constructor
		// --------------------------------------------

		parent::__construct();

		$this->settings = $settings;

		if ( ! isset($this->base) )
		{
			//BASE is not set until AFTER sessions_end,
			//and we don't want to clobber it.
			$base_const = defined('BASE') ? BASE :  SELF . '?S=0';

			if (substr($base_const, -4) != 'D=cp')
			{
				$base_const .= '&amp;D=cp';
			}

			$this->base	= str_replace('&amp;', '&', $base_const) .
							'&C=addons_modules&M=show_module_cp&module=' .
							$this->lower_name;
		}

		$this->user_base = $this->base;
	}
	//	End __construct


	// --------------------------------------------------------------------

	/**
	 * Magic Call Method
	 *
	 * Used here to shim out removed hook calls so no errors show
	 * when we are needing to upgrade to a new version that doesn't
	 * have the removed function.
	 *
	 * @access	public
	 * @param	string	$method	desired method
	 * @param	array	$args	method ards
	 * @return	mixed			last call, or FALSE, or null if method not removed
	 */

	public function __call($method = '', $args = array())
	{
		if (in_array($method, $this->removed_functions))
		{
			return $this->get_last_call(
				( ! empty($args)) ? array_shift($args) : FALSE
			);
		}
	}
	//END __call


	// --------------------------------------------------------------------

	/**
	 * Activate Extension
	 *
	 * @access	public
	 * @return	null
	 */

	public function activate_extension(){}

	// --------------------------------------------------------------------

	/**
	 * Disable Extension
	 * @access	public
	 * @return	null
	 */

	public function disable_extension(){}
	// END disable_extension()

	// --------------------------------------------------------------------

	/**
	 * Update Extension
	 *
	 * @access	public
	 * @return	null
	 */

	function update_extension(){}
	// END update_extension


	// --------------------------------------------------------------------

	/**
	 *	Insert Rating Start Submission
	 *
	 *	@access		public
	 *	@param		array
	 *	@return		array
	 */

	public function insert_rating_start( $data = array() )
	{
		return $this->loginreg($data);
	}
	// END insert_rating_start()

	// --------------------------------------------------------------------

	/**
	 *	Insert Comment Start Submission
	 *
	 *	@access		public
	 *	@param		array
	 *	@return		array
	 */

	public function insert_comment_start( $data = array() )
	{
		return $this->loginreg($data);
	}
	// END insert_comment_start()


	// --------------------------------------------------------------------

	/**
	 *	PayPal Pro Payment Submission
	 *
	 *	@access		public
	 *	@param		array
	 *	@return		array
	 */

	public function paypalpro_payment_start( $data = array() )
	{
		return $this->loginreg($data);
	}
	// END paypalpro_payment_start()


	// --------------------------------------------------------------------

	/**
	 *	Freeform Module Submission
	 *
	 *	@access		public
	 *	@param		array
	 *	@return		array
	 */

	public function freeform_module_insert_begin( $data = array() )
	{
		if ( is_array( ee()->extensions->last_call ) &&
			count( ee()->extensions->last_call ) > 0 )
		{
			$data = ee()->extensions->last_call;
		}

		return $data;
		//return $this->loginreg($data);
	}
	// END freeform_module_insert_begin()


	// --------------------------------------------------------------------

	/**
	 *	Login/Registration During Form Submission
	 *
	 *	@access		public
	 *	@param		array
	 *	@return		array
	 */

	public function loginreg( $data = array() )
	{
		if ( is_array( ee()->extensions->last_call ) &&
			count( ee()->extensions->last_call ) > 0 )
		{
			$data = ee()->extensions->last_call;
		}

		if ( ee()->input->post('user_login_type') === FALSE OR
			ee()->input->post('user_login_type') == '')
		{
			return $data;
		}

		ee()->extensions->end_script = FALSE;

		// ----------------------------------------
		//	Instantiate class
		// ----------------------------------------

		if ( class_exists('User') === FALSE )
		{
			require 'mod.user.php';
		}

		$User = new User();

		if ( ee()->input->post('user_login_type') != 'register' )
		{
			$User->_remote_login();
		}
		else
		{
			$User->_remote_register();
		}

		return $data;
	}
	// END loginreg


	// --------------------------------------------------------------------

	/**
	 *	Validate Members
	 *
	 *	@access		public
	 *	@return		null
	 */

	public function cp_members_validate_members()
	{
		if ( ! ee()->input->post('toggle') OR
			$_POST['action'] != 'activate')
		{
			return;
		}

		$member_ids = array();

		foreach ($_POST['toggle'] as $key => $val)
		{
			if ( ! is_array($val))
			{
				$member_ids[] = $val;
			}
		}

		if (count($member_ids) == 0)
		{
			return;
		}

		// ----------------------------------------
		//	Instantiate class
		// ----------------------------------------

		if ( class_exists('User') === FALSE )
		{
			require 'mod.user.php';
		}

		$User = new User();

		$User->cp_validate_members($member_ids);
	}
	// END cp_members_validate_members()


	// --------------------------------------------------------------------

	/**
	 *	User Authors - Delete
	 *
	 *	@access		public
	 *	@param		array
	 *	@return		string
	 */

	public function delete_entries_start()
	{
		if ( empty($_POST['delete']) OR
			! is_array($_POST['delete']) )
		{
			return;
		}

		//	----------------------------------------
		//	 Delete Query
		//	----------------------------------------

		ee()->db
				->where_in('entry_id', $_POST['delete'])
				->delete('user_authors');

		return;
	}
	// END delete_entries_start()
}
//	END User_ext