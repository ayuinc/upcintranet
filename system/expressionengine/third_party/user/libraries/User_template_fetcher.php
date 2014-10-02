<?php

require_once rtrim(dirname(dirname(__FILE__)), '/') . '/addon_builder/addon_builder.php';

class User_template_fetcher extends Addon_builder_user
{
	// --------------------------------------------------------------------

	/**
	 * Fetch Template
	 *
	 * @access	public
	 * @param	string $group		template group or slash separated group/tempalte
	 * @param	string $template	optional name of template
	 * @return	string				template or empty string
	 */

	public function fetch_template($group, $template = '')
	{
		//	----------------------------------------
		//  Retrieve Template
		//	----------------------------------------

		$grp = $group;
		$tpl = $template;

		if ( ! $tpl)
		{
			$x = explode('/', $group);

			if ( ! isset($x[1]))
			{
				$tpl = 'index';
			}
			else
			{
				$tpl = $x[1];
			}

			$grp = $x[0];
		}

		//	----------------------------------------
		//  Template as File?
		//	----------------------------------------

		$template_data = '';

		if ($template_data == '')
		{
			$query =	ee()->db->select('template_data, group_name, template_name, template_type')
								->from('exp_templates as t')
								->from('exp_template_groups as tg')
								->where('t.site_id', ee()->config->item('site_id'))
								->where('t.group_id = tg.group_id')
								->where('t.template_name', $tpl)
								->where('tg.group_name', $grp)
								->limit(1)
								->get();

			if ($query->num_rows() > 0)
			{
				if (ee()->config->item('save_tmpl_files') == 'y' AND
					ee()->config->item('tmpl_file_basepath') != '')
				{
					ee()->load->library('api');
					ee()->api->instantiate('template_structure');

					$row = $query->row_array();

					$template_data = $this->find_template_file(
						$row['group_name'],
						$row['template_name'],
						ee()->api_template_structure->file_extensions(
							$row['template_type']
						)
					);
				}

				//no file? query it is
				if ($template_data == '')
				{
					$template_data = stripslashes($query->row('template_data'));
				}

			}
		}

		// -------------------------------------
		//	query didn't work but save templates
		//	as files is enabled? Lets see if its there
		//	as an html file anyway
		// -------------------------------------

		if ($template_data == '' AND
			ee()->config->item('save_tmpl_files') == 'y' AND
			ee()->config->item('tmpl_file_basepath') != '')
		{
			$template_data = $this->find_template_file($grp, $tpl);
		}

		return $template_data;
	}
	//END fetch_template


	// --------------------------------------------------------------------

	/**
	 * Find the template
	 *
	 * @access	public
	 * @param	string	$group		template group
	 * @param	string	$template	template name
	 * @param	string	$extension	file extension
	 * @return	string				template data or empty string
	 */

	public function find_template_file($group, $template, $extention = '.html')
	{
		$template_data = '';

		$extention = '.' . ltrim($extention, '.');

		$filepath = rtrim(ee()->config->item('tmpl_file_basepath'), '/') . '/';
		$filepath .= ee()->config->item('site_short_name') . '/';
		$filepath .= $group . '.group/';
		$filepath .= $template;
		$filepath .= $extention;

		ee()->security->sanitize_filename($filepath);

		if (file_exists($filepath))
		{
			$template_data = file_get_contents($filepath);
		}

		return $template_data;
	}
	//END find_template_file
}
//END class User_template_fetcher