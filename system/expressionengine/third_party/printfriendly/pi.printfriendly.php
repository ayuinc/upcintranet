<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Default Module file
 *
 * @package		Default module
 * @category	Modules
 * @author		Rein de Vries <info@reinos.nl>
 * @link		http://reinos.nl
 * @copyright 	Copyright (c) 2013 Reinos.nl Internet Media
 */
 
/**
 * Include the config file
 */
require_once PATH_THIRD.'printfriendly/config.php';

$plugin_info = array(
  'pi_name' => PF_NAME,
  'pi_version' => PF_VERSION,
  'pi_author' => PF_AUTHOR,
  'pi_author_url' => PF_DOCS,
  'pi_description' => PF_DESCRIPTION,
  'pi_usage' => Printfriendly::usage()
);

class Printfriendly
{
	private $EE; 
	private $site_id;
	public $return_data = '';

	// ----------------------------------------------------------------------
	
	/**
	 * Constructor
	 */
	public function __construct()
	{		
		//load default helper
		ee()->load->library(PF_MAP.'_lib');

		//require the default settings
		require PATH_THIRD.PF_MAP.'/settings.php';
	}	

	// ----------------------------------------------------------------------
	
	/**
	 * Constructor
	 */
	public function button()
	{	
		//set the cache
        //Printfriendly_helper::set_cache(PF_MAP.'_caller', '1');	

		//EDT Benchmark
		Printfriendly_helper::benchmark(__FUNCTION__, true);

		//page header
		$this->header_img_url = ee()->TMPL->fetch_param('header_img_url', '');
		$this->header_tagline = ee()->TMPL->fetch_param('header_tagline', '');
		//click-to-delete
		$this->disable_click_to_del = Printfriendly_helper::check_yes(ee()->TMPL->fetch_param('disable_click_to_del', 'no'), true);
		//Images
		$this->hide_images = Printfriendly_helper::check_yes(ee()->TMPL->fetch_param('hide_images', 'no'), true);
		//Image style
		$this->image_display_style = ee()->TMPL->fetch_param('image_display_style', 'right');
		//pdf
		$this->disable_pdf = Printfriendly_helper::check_yes(ee()->TMPL->fetch_param('disable_pdf', 'no'), true);
		//email
		$this->disable_email = Printfriendly_helper::check_yes(ee()->TMPL->fetch_param('disable_email', 'no'), true);
		//Print
		$this->disable_print = Printfriendly_helper::check_yes(ee()->TMPL->fetch_param('disable_print', 'no'), true);
		//Custom css url
		$this->custom_css = ee()->TMPL->fetch_param('custom_css', '');

		//icon
		$this->button = ee()->TMPL->fetch_param('button', 'pf-button-both.gif');
		$this->custom_button = ee()->TMPL->fetch_param('custom_button', '');
		if($this->custom_button != '')
		{
			$button = $this->custom_button;
		}
		else
		{
			$button = ee()->printfriendly_settings->item('theme_url').'images/'.$this->button;
		}
		//icon title
		$this->title = ee()->TMPL->fetch_param('title', 'Print Friendly and PDF');
		//icon style
		$this->style = ee()->TMPL->fetch_param('style', 'color:#6D9F00;text-decoration:none;');
		//class
		$this->class = ee()->TMPL->fetch_param('class', 'printfriendly');
		//id
		$this->id = ee()->TMPL->fetch_param('id', 'printfriendly');

		//return data
		$this->return_data = "
		<script>
			var pfHeaderImgUrl = '".$this->header_img_url."';
			var pfHeaderTagline = '".$this->header_tagline."';
			var pfdisableClickToDel = ".$this->disable_click_to_del.";
			var pfHideImages = ".$this->hide_images.";
			var pfImageDisplayStyle = '".$this->image_display_style."';
			var pfDisablePDF = ".$this->disable_pdf.";
			var pfDisableEmail = ".$this->disable_email .";
			var pfDisablePrint = ".$this->disable_print.";
			var pfCustomCSS = '".$this->custom_css."';
			var pfBtVersion='1';
			(function(){
				var js, pf;
				pf = document.createElement('script');
				pf.type = 'text/javascript';
				if('https:' == document.location.protocol){
					js='https://pf-cdn.printfriendly.com/ssl/main.js'
				}else{
					js='http://cdn.printfriendly.com/printfriendly.js'
				}
				pf.src=js;document.getElementsByTagName('head')[0].appendChild(pf)
			})();
			</script>
			<a 
				href='http://www.printfriendly.com' 
				style='".$this->style."'
				onclick='window.print();return false;' 
				title='".$this->title."'
				class='".$this->class."'
				id='".$this->id."'
			>
				<img 
					style='border:none;' 
					src='".$button."' 
					alt='".$this->title."'
					
				/>
			</a>
		";

		//EDT Benchmark
		Printfriendly_helper::benchmark(__FUNCTION__, false);

		return $this->return_data;
	}	

	//  Plugin Usage
	// ----------------------------------------
	function usage()
	{
		ob_start();
		?>
		
		See reinos.nl/add-ons/printfriendly/docs
		
		{exp:printfriendly:button}

		<?php
		$buffer = ob_get_contents();

		ob_end_clean();

		return $buffer;
	}
	// END
}