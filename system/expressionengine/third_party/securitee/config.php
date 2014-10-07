<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

 /**
 * mithra62 - Securit:ee
 *
 * @package		mithra62:Securitee
 * @author		Eric Lamb
 * @copyright	Copyright (c) 2012, mithra62, Eric Lamb.
 * @link		http://blah.com
 * @version		1.0
 * @filesource 	./system/expressionengine/third_party/securitee/
 */

$config['name'] = 'Securit:ee';
$config['class_name'] = 'Securitee';
$config['version'] = '1.3.7';
//$config['nsm_addon_updater']['versions_xml'] = 'http://mithra62.com/securitee.xml';

$config['settings_table'] = 'securitee_settings';
$config['hashes_table'] = 'securitee_hashes';
$config['description'] = 'Securit:ee is a security suite for ExpressionEngine.';

$config['mod_url_name'] = strtolower($config['class_name']);
$config['ext_class_name'] = $config['class_name'].'_ext';

$config['docs_url'] = 'http://mithra62.com/docs/securit-ee';