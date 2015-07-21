<?php

/**
 * Pdf_press Module Class
 *
 * @package     pdf_press
 * @author      Patrick Pohler ppohler@anecka.com
 * @copyright   Copyright (c) 2014, Patrick Pohler
 * @link        http://www.anecka.com/pdf_press
 * @license		See LICENSE.txt
 */

if ( ! defined('PDF_PRESS_NAME'))
{
	define('PDF_PRESS_NAME',    'Pdf_press');
	define('PDF_PRESS_PACKAGE', 'pdf_press');
	define('PDF_PRESS_FULL_NAME', 'PDF Press');
	define('PDF_PRESS_VERSION', '2.3');
	define('PDF_PRESS_DOCS',    '');
}

/**
 * < EE 2.6.0 backward compatiblity
*/

if ( ! function_exists('ee'))
{
	function ee()
	{
		static $EE;
		if ( ! $EE) $EE = get_instance();
		return $EE;
	}
}
