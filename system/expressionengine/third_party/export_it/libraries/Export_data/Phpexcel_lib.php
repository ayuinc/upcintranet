<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

 /**
 * mithra62 - Export It
 *
 * @package		mithra62:Export_it
 * @author		Eric Lamb
 * @copyright	Copyright (c) 2011, mithra62, Eric Lamb.
 * @link		http://mithra62.com/projects/view/export-it/
 * @version		1.1.2
 * @filesource 	./system/expressionengine/third_party/export_it/
 */
 
 /**
 * Export It - PhpExcel Creation Class
 *
 * Uses PHPExcel to create the XLSX Export
 *
 * @package 	mithra62:Export_it
 * @author		Eric Lamb
 * @filesource 	./system/expressionengine/third_party/export_it/libraries/Export_data/Phpexcel_lib.php
 */
 class Phpexcel_lib
{
	/**
	 * The keys used for the columsn.
	 * Since some data won't have matching keys we have to keep track of things
	 * @var array
	 */
	public $keys = array();
	
	/**
	 * Contains the expected structure to use for the export data
	 * @var array
	 */
	public $arr_structure = array();
	
	public function __construct()
	{
		$this->EE =& get_instance();
		$this->objPHPExcel = new PHPExcel();
	}
	
	/**
	 * Wrapper to handle creation
	 * @param array $arr
	 * @param bool $keys_as_headers
	 * @param string $file_name
	 * @return string
	 */
	public function create(array $arr, $keys_as_headers = TRUE, $file_name = 'download.txt')
	{	
		$arr = $this->EE->export_xls->make_non_nested($arr);
		if(is_array($arr) && count($arr) >= 1)
		{
			$rows = array();
			$row = 1;
			foreach($arr AS $key => $value)
			{
				foreach($value AS $k => $v)
				{
					foreach($this->EE->export_xls->keys AS $master)
					{
						if($k == $master)
						{
							$value[$k] = $v;
							break;
						}
						else
						{
							$value[$k] = '';
						}
					}
				}
				
				$rows[] =$value;
			}

			$cols = $this->EE->export_xls->keys;
			$rows = array_merge(array($cols), $rows);

			foreach($rows AS $_row)
			{
				$col = 0;
				foreach($_row as $key=>$value) 
				{
					$this->objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($col, $row, $value);
					$col++;
				}
				$row++;	
			}
		}
	}		
}