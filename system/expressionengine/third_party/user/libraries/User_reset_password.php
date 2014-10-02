<?php

require_once rtrim(dirname(dirname(__FILE__)), '/') . '/mod.user.php';

class User_reset_password extends User
{
	// --------------------------------------------------------------------

	/**
	 * Check User $this->_params() for template for resetpassword.
	 *
	 * @access	public
	 * @param	array	$params	saved params from posted form
	 * @return	boolean			password_reset_template is in params
	 */

	public function check_params($params)
	{
		return isset($params['password_reset_template']);
	}
	//END check_params


	// --------------------------------------------------------------------

	/**
	 * Reset Password Form
	 *
	 * @access	public
	 * @return	string	tagdata wrapped in form setup
	 */

	public function reset_password_form()
	{
		// if the user is logged in, then send them away
		if (ee()->session->userdata('member_id') !== 0)
		{
			return ee()->output->show_user_error('general', array(lang('mbr_you_are_registered')));
		}

		// If the user is banned, send them away.
		if (ee()->session->userdata('is_banned') === TRUE)
		{
			return ee()->output->show_user_error('general', array(lang('not_authorized')));
		}

		// They didn't include their token.  Give em an error.
		if ( ! ($resetcode = ee()->TMPL->fetch_param('reset_code')))
		{
			if (stristr(ee()->TMPL->tagdata, LD . 'user_no_results'))
			{
				return $this->no_results();
			}

			return ee()->output->show_user_error('submission', array(lang('mbr_no_reset_id')));
		}

		$this->form_data = array_merge($this->form_data, array(
			'id'		=> ee()->TMPL->fetch_param('form_id', 'reset_password_form'),
			'ACT'		=> ee()->functions->fetch_action_id('User', 'process_reset_password'),
			'resetcode'	=> $resetcode,
			'RET'		=> ee()->TMPL->fetch_param('return', '')
		));

		if (ee()->TMPL->fetch_param('form_name') !== FALSE AND
			ee()->TMPL->fetch_param('form_name') != '')
		{
			$this->form_data['name']	= ee()->TMPL->fetch_param('form_name');
		}

		$this->params['secure_action']	= ee()->TMPL->fetch_param('secure_action', 'no');

		return $this->_form();
	}
	//END reset_password_form


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
		if (REQ == 'PAGE')
		{
			return ee()->functions->redirect(ee()->functions->fetch_site_index());
		}

		// if the user is logged in, then send them away
		if (ee()->session->userdata('member_id') !== 0)
		{
			return ee()->output->show_user_error('general', array(lang('mbr_you_are_registered')));
		}

		// If the user is banned, send them away.
		if (ee()->session->userdata('is_banned') === TRUE)
		{
			return ee()->output->show_user_error('general', array(lang('not_authorized')));
		}

		// -------------------------------------
		//	we need to override output class here
		//	in order to capture the result of our
		//	actions
		// -------------------------------------

		$MA = $this->member_auth_instance();

		$old_output = ee()->output;

		ee()->output = new User_output_intercept();

		$MA->process_reset_password();

		$process_data	= ee()->output->user_get_output_capture();
		$errors			= ee()->output->user_had_errors();

		//reset output to make sure everything is back in order
		//before we continue.
		ee()->output = $old_output;

		if ( ! empty($errors))
		{
			return call_user_func_array(
				array(ee()->output, 'show_user_error'),
				$errors
			);
		}

		$this->_param();

		// -------------------------------------
		//	redirect to return template with success
		// -------------------------------------

		$return = $this->_param('return');

		if (empty($return))
		{
			$return = $this->_param('RET');
		}

		if (empty($return))
		{
			$return = ee()->input->get_post('return');
		}

		if (empty($return))
		{
			$return = ee()->input->get_post('RET');
		}

		if (empty($return))
		{
			$return = ee()->functions->fetch_site_index(0, 0);
		}

		//return is pre-processed by _form()
		ee()->functions->redirect($return);
	}
	//END process_reset_password



	// --------------------------------------------------------------------

	/**
	 * Process the reset email, etc for our custom password and URL.
	 *
	 * This is nutty, but EE ties the entire work of the reset into
	 * a single function with no way to undo it but to toy around
	 * with replacing objects. This is usually a POST request so doing
	 * so _shouldn't_ hose things but we are playing it as safe as we can.
	 *
	 * @access	public
	 * @param 	array	$params	array of incoming params from posted form
	 * @return	void
	 */

	public function send_reset_token($params)
	{
		// if the user is logged in, then send them away
		if (ee()->session->userdata('member_id') !== 0)
		{
			return ee()->output->show_user_error('general', array(lang('mbr_you_are_registered')));
		}

		// If the user is banned, send them away.
		if (ee()->session->userdata('is_banned') === TRUE)
		{
			return ee()->output->show_user_error('general', array(lang('not_authorized')));
		}

		if (REQ == 'PAGE' ||
			! isset($params['password_reset_template'])
		)
		{
			return ee()->functions->redirect(ee()->functions->fetch_site_index());
		}

		$MA = $this->member_auth_instance();

		// -------------------------------------
		//	we need to override output class here
		//	in order to capture the result of our
		//	actions
		// -------------------------------------

		// -------------------------------------
		//	always loaded
		// -------------------------------------
		$old_output = ee()->output;

		ee()->output = new User_output_intercept();

		// -------------------------------------
		//	unless they go to forcing the email lib to reload
		//	this should prevent their call of load->library('email')
		//	from replacing our stand-in class.
		// -------------------------------------

		ee()->load->library('email');

		$old_email = ee()->email;

		ee()->email = new User_email_intercept();

		// -------------------------------------
		//	functions lib is autoloaded now
		// -------------------------------------

		$old_functions = ee()->functions;

		ee()->functions = new User_functions_intercept();

		// -------------------------------------
		//	capture result
		// -------------------------------------

		$MA->send_reset_token();

		$process_data	= ee()->output->user_get_output_capture();
		$errors			= ee()->output->user_had_errors();
		$email_data		= ee()->email->user_get_email_capture();

		// -------------------------------------
		//	cleanup
		// -------------------------------------

		ee()->output	= $old_output;
		ee()->email		= $old_email;
		ee()->functions	= $old_functions;

		// -------------------------------------
		//	errors?
		// -------------------------------------

		if ( ! empty($errors))
		{
			return call_user_func_array(
				array(ee()->output, 'show_user_error'),
				$errors
			);
		}

		// -------------------------------------
		//	get token
		// -------------------------------------

		//xml->array
		$xml			= simplexml_load_string($email_data['message'], null, LIBXML_NOCDATA);
		$json			= json_encode($xml);
		$message_data	= json_decode($json, TRUE);

		$token			= false;

		//token is wrapped up in a url we've captured from email attempting
		//to send.
		if (preg_match('/\&id\=(\w+)(?:\b|$|\&)/i', $message_data['reset_url'], $matches))
		{
			$token = $matches[1];
		}

		//can't do a thing without the token, though it should error before
		//this.
		if (empty($token))
		{
			$this->show_error(lang('could_not_send_reset_email'));
			exit();
		}

		// -------------------------------------
		//	build our reset url
		// -------------------------------------

		$url = $params['password_reset_template'];

		//create url for template.
		if ( preg_match("/" . LD . "\s*path=(.*?)" . RD . "/", $url, $match))
		{
			$url = $match['1'];
		}

		//replace ID placeholder or append to end.
		if (stristr($url, '%id%'))
		{
			$url = str_replace('%id%', $token, $url);
		}
		else
		{
			$url = rtrim($url, '/') . '/' . $token;
		}

		if ( ! stristr($url, "http://") &&
			 ! stristr($url, "https://")
		)
		{
			$url = ee()->functions->create_url($url);
		}

		// -------------------------------------
		//	secure action?
		// -------------------------------------

		if (isset($params['secure_reset_link']) &&
			$this->check_yes($params['secure_reset_link'])
		)
		{
			$url = str_replace('http://', 'https://', $url);
		}

		// -------------------------------------
		//	process email template or use default
		//	template with our new url
		// -------------------------------------

		$default_template = ee()->functions->fetch_email_template(
				'forgot_password_instructions'
			);

		$email_template	= '';
		$email_subject	= $default_template['title'];

		//custom template?
		if (isset($params['password_reset_email_template']))
		{
			$email_template = $this->lib('template_fetcher')->fetch_template(
				$params['password_reset_email_template']
			);
		}

		if ($email_template == '')
		{
			$email_template = $default_template['data'];
		}

		//custom subject?
		if (isset($params['password_reset_email_subject']))
		{
			$email_subject = $params['password_reset_email_subject'];
		}

		// -------------------------------------
		//	swap vars out of data
		// -------------------------------------

		$email_swap_data = array(
			'name'		=> $message_data['name'],
			'reset_url'	=> $url,
			'site_name'	=> $message_data['site_name'],
			'site_url'	=> $message_data['site_url']
		);

		$email_subject	= ee()->functions->var_swap($email_subject, $email_swap_data);
		$email_template	= ee()->functions->var_swap($email_template, $email_swap_data);

		// -------------------------------------
		//	email user
		// -------------------------------------

		ee()->email->wordwrap = true;
		ee()->email->from(
			ee()->config->item('webmaster_email'),
			ee()->config->item('webmaster_name')
		);
		ee()->email->to($email_data['to']);
		ee()->email->subject($email_subject);
		ee()->email->message($email_template);
		ee()->email->send();

		// -------------------------------------
		//	redirect to return template with success
		// -------------------------------------

		$return = ee()->input->get_post('return');

		if (empty($return))
		{
			$return = ee()->input->get_post('RET');
		}

		if (empty($return))
		{
			$return = ee()->functions->fetch_site_index(0, 0);
		}

		//return is pre-processed by _form()
		ee()->functions->redirect($return);
	}
	//END send_reset_token


	// --------------------------------------------------------------------

	/**
	 * Returns a new instance of the Member_auth library
	 *
	 * @access	public
	 * @return	object	new member auth object
	 */

	public function member_auth_instance()
	{
		if ( ! class_exists('Member'))
		{
			require PATH_MOD.'member/mod.member.php';
		}

		if ( ! class_exists('Member_auth'))
		{
			require PATH_MOD.'member/mod.member_auth.php';
		}

		return new Member_auth();
	}
	//END member_auth_instance

}
//END Class User_reset_password

// -------------------------------------
//	Wrapper class for Output to capture
//	result of some functions that don't
//	allow the override of their return
//	functions.
// -------------------------------------

require_once APPPATH . 'core/EE_Output.php';

class User_output_intercept Extends EE_Output
{
	protected $__user_output_capture = array();
	protected $__user_errors_sent = false;

	public function user_had_errors()
	{
		return $this->__user_errors_sent;
	}

	public function user_get_output_capture()
	{
		return $this->__user_output_capture;
	}

	public function show_message($data, $xhtml = TRUE)
	{
		return $this->__user_output_capture = $data;
	}

	public function show_user_error($type, $lang_items)
	{
		$this->__user_errors_sent = func_get_args();
	}
}
//END User_output_intercept

// -------------------------------------
//	Wrapper class for Functions to capture
//	result of some functions that don't
//	allow the override of their return
//	functions.
// -------------------------------------

require_once APPPATH . 'libraries/Functions.php';

class User_functions_intercept Extends EE_Functions
{
	public function fetch_email_template($template)
	{
		if ($template == 'forgot_password_instructions')
		{
			$data	=	'<email>' .
						'<name><![CDATA[{name}]]></name>' .
						'<reset_url><![CDATA[{reset_url}]]></reset_url>' .
						'<site_name><![CDATA[{site_name}]]></site_name>' .
						'<site_url><![CDATA[{site_url}]]></site_url>' .
						'</email>';
			$title	=	'Capture Title';

			return array('data' => $data, 'title' => $title);
		}
		else
		{
			return parent::fetch_email_template($template);
		}
	}
}
//END User_functions_intercept

// -------------------------------------
//	Wrapper class for Email to capture
//	result of some functions that don't
//	allow the override of their return
//	functions.
// -------------------------------------

require_once BASEPATH . 'libraries/Email.php';
require_once APPPATH . 'libraries/EE_Email.php';

class User_email_intercept Extends EE_Email
{
	protected $__user_from_capture = '';
	protected $__user_to_capture = '';
	protected $__user_subject_capture = '';
	protected $__user_message_capture = '';

	public function send()
	{
		return true;
	}

	public function from($from, $name = '', $return_path = NULL)
	{
		$this->__user_from_capture = $from;
	}

	public function to($to)
	{
		$this->__user_to_capture = $to;
	}

	public function subject($subject)
	{
		$this->__user_subject_capture = $subject;
	}

	public function message($body)
	{
		$this->__user_message_capture = $body;
	}

	public function user_get_email_capture()
	{
		return array(
			'from'		=> $this->__user_from_capture,
			'to'		=> $this->__user_to_capture,
			'subject'	=> $this->__user_subject_capture,
			'message'	=> $this->__user_message_capture,
		);
	}
}
//END User_email_intercept