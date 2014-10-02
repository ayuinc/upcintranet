<?php

/*
=====================================================
 This ExpressionEngine plugin was created by Laisvunas
 - http://devot-ee.com/developers/ee/laisvunas/
=====================================================
 Copyright (c) Laisvunas
=====================================================
 This is commercial Software.
 One purchased license permits the use this Software on the SINGLE website.
 Unless you have been granted prior, written consent from Laisvunas, you may not:
 * Reproduce, distribute, or transfer the Software, or portions thereof, to any third party
 * Sell, rent, lease, assign, or sublet the Software or portions thereof
 * Grant rights to any other person
=====================================================
 Purpose: Outputs variables from GET and POST arrays.
=====================================================
*/

$plugin_info = array(
						'pi_name'			=> 'GET and POST Variables',
						'pi_version'		=> '1.1.1',
						'pi_author'			=> 'Laisvunas',
						'pi_author_url'		=> 'http://devot-ee.com/developers/ee/laisvunas/',
						'pi_description'	=> 'Outputs variables from GET and POST arrays.',
						'pi_usage'			=> Get_post_vars::usage()
					);

class Get_post_vars {

  var $return_data = '';
  
  function Get_post_vars()
  {
    $this->EE =& get_instance();
    
    // Fetch the tagdata
    $tagdata = $this->EE->TMPL->tagdata;
    //echo '$tagdata: '.$tagdata.PHP_EOL;
    
    // Fetch params
    $if_array = $this->EE->TMPL->fetch_param('if_array') ? $this->EE->TMPL->fetch_param('if_array') : '';
    $on_failure = $this->EE->TMPL->fetch_param('on_failure') ? $this->EE->TMPL->fetch_param('on_failure') : '';
    
    // Define variables
    $conds = array();
    $vars_in_conds = array();
    
    // Find variables inside conditionals
    $regex = '/\spost_[a-zA-Z0-9_-]+/';
    preg_match_all($regex, $tagdata, $matches);
    if (isset($matches[0]) AND count($matches[0]) > 0)
    {
      foreach ($matches[0] as $var)
      {
        if (!in_array(trim($var), $vars_in_conds))
        {
          array_push($vars_in_conds, trim($var));
        }
      }
    } 
    //print_r($vars_in_conds);
    
    $regex = '/\sget_[a-zA-Z0-9_-]+/';
    preg_match_all($regex, $tagdata, $matches);
    if (isset($matches[0]) AND count($matches[0]) > 0)
    {
      foreach ($matches[0] as $var)
      {
        if (!in_array(trim($var), $vars_in_conds))
        {
          array_push($vars_in_conds, trim($var));
        }
      }
    } 
    //print_r($vars_in_conds);
    
    $regex = '/\sget_post_[a-zA-Z0-9_-]+/';
    preg_match_all($regex, $tagdata, $matches);
    if (isset($matches[0]) AND count($matches[0]) > 0)
    {
      foreach ($matches[0] as $var)
      {
        if (!in_array(trim($var), $vars_in_conds))
        {
          array_push($vars_in_conds, trim($var));
        }
      }
    } 
    //print_r($vars_in_conds);
    
    // Push variables found inside conditionals into $TMPL->var_single array
    foreach ($vars_in_conds as $var)
    {
      if (!isset($this->EE->TMPL->var_single[$var]))
      {
        $this->EE->TMPL->var_single[$var] = $var;
      }
    }
    
    foreach ($this->EE->TMPL->var_single as $key => $val) 
    {
      //echo '$key: ['.$key.'] $val: ['.$val.']<br><br>';
      if (substr($key, 0, 5) == 'post_')
      {
        $var = substr($key, 5);
        //echo '$var: ['.$var.']<br><br>';
        //print_r($_POST);
        if (!isset($_POST[$var]))
        {
          $conds[$key] = $on_failure;
        }
        elseif (!is_array($this->EE->input->post($var)))
        {
          $conds[$key] = $this->EE->security->xss_clean($this->EE->input->post($var));
        }
        elseif (is_array($this->EE->input->post($var)))
        {
          $conds[$key] = $if_array;
        }
      }
      elseif (substr($key, 0, 4) == 'get_')
      {
        $var = substr($key, 4);
        if (!isset($_GET[$var]))
        {
          $conds[$key] = $on_failure;
        }
        elseif (!is_array($this->EE->input->get($var)))
        {
          $conds[$key] = $this->EE->security->xss_clean($this->EE->input->get($var));
        }
        elseif (is_array($this->EE->input->get($var)))
        {
          $conds[$key] = $if_array;
        }
      }
      elseif (substr($key, 0, 9) == 'get_post_')
      {
        $var = substr($key, 9);
        if (!isset($_GET[$var]) AND !isset($_POST[$var]))
        {
          $conds[$key] = $on_failure;
        }
        elseif (!is_array($this->EE->input->get_post($var)))
        {
          $conds[$key] = $this->EE->input->get_post($var);
        }
        elseif (is_array($this->EE->input->get_post($var)))
        {
          $conds[$key] = $if_array;
        }
      }
    }
    
    foreach ($conds as $key => $val)
    {
      $tagdata = $this->EE->TMPL->swap_var_single($key, $val, $tagdata);
    }
    $tagdata = $this->EE->functions->prep_conditionals($tagdata, $conds);

    return $this->return_data = $tagdata;
  }
  // END FUNCTION
  
  // ----------------------------------------
  //  Plugin Usage
  // ----------------------------------------
  
  // This function describes how the plugin is used.
  //  Make sure and use output buffering

  function usage()
  {
  ob_start(); 
?>
This plugin outputs variables from GET and POST arrays. 

PARAMETERS

1) if_array - Optional. Allows you to specify what will be outputted in case the value of variable is array.
By default an empty string will be outputted.

2) on_failure - Optional. Allows you to specify what will be outputted in case variable was not found.
By default an empty string will be outputted.
 
USAGE

To output variable "my_variable" from GET array use the code as this:

{exp:get_post_vars parse="inward"}
{get_my_variable}
{/exp:get_post_vars}

To output variable "my_variable" from POST array use the code as this:

{exp:get_post_vars parse="inward"}
{post_my_variable}
{/exp:get_post_vars}

To output variable "my_variable" from POST or GET array use the code as this:

{exp:get_post_vars parse="inward"}
{get_post_my_variable}
{/exp:get_post_vars}
  
<?php
  $buffer = ob_get_contents();
  	
  ob_end_clean(); 
  
  return $buffer;
  }
  // END FUNCTION
}
// END CLASS
?>