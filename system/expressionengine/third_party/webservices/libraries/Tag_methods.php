<?php

/**
 * Created by PhpStorm.
 * User: eportillaf
 * Date: 4/17/16
 * Time: 3:06 PM
 */
class Tag_methods
{
    public function __construct()
    {
        $this->CI =& get_instance();
        $this->EE =& get_instance();
    }

    /**
     * Get subtag data
     *
     * @access  public
     * @param $tag_name - tag to look for
     * @param $tag_data - Template tag data to look in
     * @return string
     */
    public function get_subtag_data($tag_name, $tag_data)
    {
        $pattern  = '#'.LD.$tag_name.RD.'(.*?)'.LD.'/'.$tag_name.RD.'#s';

        if (is_string($tag_data) && is_string($tag_name) && preg_match($pattern, $tag_data, $matches))
        {
            return $matches[1];
        }
        return '';
    }

    /**
     * Replace lonenly tag data
     *
     * @access  public
     * @param $tag_name - Tag to look for
     * @param $tag_data - Template tag data to look in
     * @param $replacement
     * @return string
     */
    public function replace_subtag_data($tag_name, $tag_data, $replacement)
    {
        if (is_string($tag_data) && is_string($tag_name) && is_string($replacement))
        {
            // var_dump('by this '.$replacement.' replace'.$tag_name.' on '.$tagdata);
            $pattern  =  LD.$tag_name.RD ;
            return str_replace($pattern, $replacement, $tag_data);
        }
        return $tag_data;
    }
    /**
     * String starts with
     *
     * @access  public
     * @param $string - Tag to look for
     * @param $start - Template tag data to look in
     * @return boolean
     */
    public function startsWith($string, $start) {
        // search backwards starting from haystack length characters from the end
        return $start === "" || strrpos($string, $start, -strlen($string)) !== false;
    }
    /**
     * String ends with
     *
     * @access  public
     * @param $string - Complete String
     * @param $start - with...
     * @return boolean
     */
    public function endsWith($string, $start) {
        // search forward starting from end minus needle length characters
        return $start === "" || (($temp = strlen($string) - strlen($start)) >= 0 && strpos($string, $start, $temp) !== false);
    }
    /**
     * Replace pair tag data
     *
     * @access  public
     * @param $tag_name - Tag to look for
     * @param $tag_data - Template tag data to look in
     * @param $replacement
     * @return string
     */
    public function replace_pair_subtag_data($tag_name, $tag_data, $replacement)
    {
        $pattern  = '#'.LD.$tag_name.RD.'(.*?)'.LD.'/'.$tag_name.RD.'#s';
        if (is_string($tag_data) && is_string($tag_name) && preg_match($pattern, $tag_data, $matches))
        {
            return str_replace($matches[0], $replacement, $tag_data);
        }
        return '';
    }

}
/* End of file Tag_methods.php */
/* Location: ./system/expressionengine/third_party/webservices/libraries/Tag_methods.php */