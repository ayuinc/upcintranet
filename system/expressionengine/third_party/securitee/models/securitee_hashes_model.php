<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

 /**
 * mithra62 - Securit:ee
 *
 * @package		mithra62:Securitee
 * @author		Eric Lamb
 * @copyright	Copyright (c) 2012, mithra62, Eric Lamb.
 * @link		http://mithra62.com/projects/view/securitee/
 * @version		1.2
 * @filesource 	./system/expressionengine/third_party/securitee/
 */
 
 /**
 * Securit:ee - Hash Model
 *
 * @package 	mithra62:Securitee
 * @author		Eric Lamb
 * @filesource 	./system/expressionengine/third_party/securitee/models/securitee_hashes_model.php
 */
class Securitee_hashes_model extends CI_Model
{
	/**
	 * Name of the hashes table
	 * @var string
	 */	
	private $_table = '';

	public function __construct()
	{
		parent::__construct();
		$path = dirname(realpath(__FILE__));
		include $path.'/../config'.EXT;
		$this->_table = $config['hashes_table'];
	}
	
	private function get_sql(array $hash)
	{
		return $data = array(
		'hash' => $hash['hash'],
		'member_id' => $hash['member_id'],				
		'forgotten_stamp' => date('Y-m-d H:i:s')
		);
	}
	
	public function get_table()
	{
		return $this->_table;
	}
	
	public function _set_lang($arr)
	{
		foreach($arr AS $key => $value)
		{
			$arr[$key] = lang($value);
		}
		return $arr;
	}
	
	/**
	 * Adds a hash to the databse
	 * @param string $cron
	 */
	public function add_hash(array $profile)
	{
		$data = $this->get_sql($profile);
		$data['last_changed'] = date('Y-m-d H:i:s');
		if($this->db->insert($this->_table, $data))
		{
			return $this->db->insert_id();
		}
	}	
	
	public function get_hashes(array $where = array())
	{
		foreach($where AS $key => $value)
		{
			$this->db->where($key, $value);
		}
		
		$query = $this->db->get($this->_table);
		$data = $query->result_array();
		return $data;
	}
	
	/**
	 * Returns the value straigt from the database
	 * @param string $setting
	 */
	public function get_hash(array $where)
	{
		$data = $this->db->get_where($this->_table, $where)->result_array();
		if($data)
		{
			return $data['0'];
		}
	}	
	
	public function update_hashes(array $data, $where)
	{
		foreach($data AS $key => $value)
		{	
			$this->update_profile($data, $where);
		}
		
		return TRUE;
	}
	
	/**
	 * Updates a hash
	 * @param string $key
	 * @param string $value
	 */
	public function update_hash($data, $where, $complete = TRUE)
	{
		if($complete)
		{
			$data = $this->get_sql($data);
		}
		
		return $this->db->update($this->_table, $data, $where);
	}
	
	public function delete_hash(array $where)
	{
		return $this->db->delete($this->_table, $where);	
	}
}