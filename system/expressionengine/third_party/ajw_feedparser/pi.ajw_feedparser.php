<?php

$plugin_info = array(
	'pi_name'           => 'AJW Feed Parser',
	'pi_version'        => '0.9.3',
	'pi_author'         => 'Andrew Weaver',
	'pi_author_url'     => 'http://brandnewbox.co.uk/',
	'pi_description'    => 'Allows you to extract data from an XML feed and display it in a template',
	'pi_usage'          => Ajw_feedparser::usage()
);

/**
 * Ajw_feedparser Class
 *
 * @package			ExpressionEngine
 * @category		Plugin
 * @author			Andrew Weaver
 * @copyright		Copyright (c) 2004 - 2011, Andrew Weaver
 */


class Ajw_feedparser {

	var $debug = TRUE;

	var $page_url = '';
	var $itempath = '/rss/channel/item';
	//var $itempath = '/feed/entry';

	var $cache_name = "ajw_feedparser";
	var $cache_refresh = 60; // minutes

	var $limit = 999;
	var $offset = 0;
	var $orderby = FALSE;
	var $prefix = "";
	var $dates = array();

	var $return_data = '';

	function Ajw_feedparser() {

		// error_reporting(E_ALL); 
		// ini_set("display_errors", 1);

		$this->EE =& get_instance();

		$this->EE->load->helper('url_helper');

		// Read template parameters
		
		$this->page_url = ( $this->EE->TMPL->fetch_param('url') === FALSE ) ? '' : str_replace('&#47;', '/',trim($this->EE->TMPL->fetch_param('url')));
		$this->itempath = ( $this->EE->TMPL->fetch_param('itempath') === FALSE ) ? $this->itempath : str_replace('&#47;', '/',trim($this->EE->TMPL->fetch_param('itempath')));
		$this->cache_refresh = ( $this->EE->TMPL->fetch_param('cache_refresh') === FALSE ) ? $this->cache_refresh : $this->EE->TMPL->fetch_param('cache_refresh');
		$this->limit = ( $this->EE->TMPL->fetch_param('limit') === FALSE ) ? $this->limit : $this->EE->TMPL->fetch_param('limit');
		$this->offset = ( $this->EE->TMPL->fetch_param('offset') === FALSE ) ? $this->offset : $this->EE->TMPL->fetch_param('offset');
		$this->orderby = ( $this->EE->TMPL->fetch_param('orderby') === FALSE ) ? $this->orderby : $this->EE->TMPL->fetch_param('orderby');
		$this->debug = ( $this->EE->TMPL->fetch_param('debug') === FALSE ) ? FALSE : TRUE;
		
		$this->prefix = $this->EE->TMPL->fetch_param('prefix', '');
		
		if( $this->EE->TMPL->fetch_param('date') !== FALSE ) {
			$this->dates = explode(",", $this->EE->TMPL->fetch_param('date') );
		}
		
		if ($this->page_url == '') {
			$this->return_data = $this->debug ? '<p>No feed url supplied</p>' : '';
			return $this->return_data;
		}

		// Parse {site_url} variable
		$this->page_url = @str_replace(LD."site_url".RD, $this->EE->config->item('site_url'), $this->page_url);

		if ( $this->debug ) { $this->return_data .= "<p>Fetching: " . $this->page_url . "</p>\n"; }

		$xml = $this->_fetch_feed( $this->page_url );

		//$xml = $this->_convert_smart_quotes($xml); 

		// $xml = filter_var($xml, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);

		// print $xml;

		if( $xml === FALSE ) {
			$this->return_data .= $this->EE->TMPL->no_results();
			return;
		}

		$this->EE->load->library('xmlparser'); 
		$xml_obj = $this->EE->xmlparser->parse_xml( $xml );

		if( $xml_obj === FALSE ) {
			$this->return_data .= $this->EE->TMPL->no_results();
			return;
		}
		
		// EE replaces slashes with entities earlier in process, convert back here
		//$this->itempath = str_replace(SLASH, '/', $this->itempath);

		$items = null; // initialise array to store output

		// Auto-detect feed type if itempath not set
		if( $this->EE->TMPL->fetch_param('itempath') === FALSE ) {
			if( $xml_obj->tag == "feed") {
				// ATOM feed
				$this->itempath = '/feed/entry';
			} elseif( $xml_obj->tag == "rss") {
				// RSS feed
				$this->itempath = '/rss/channel/item';
			}
		}
		
		// Recurse through XML structure looking for nodes that match $this->itempath
		$this->_fetch_xml( $xml_obj, $this->itempath, $items );

		// Find all potential tags by looping through the whole array
		$available_tags = array();
		if( count( $items ) > 0 ) {
			foreach ( $items as $item ) {
				foreach ( $item as $key => $value ) {
					if( !isset( $available_tags[ $key ] ) && substr( $key, -1, 1) != "#" ) {
						$available_tags[ $this->prefix . $key ] = $value;
					}
				}
			}
		}

		if( $this->debug ) {
			if( count( $available_tags ) ) {
				$this->return_data .= '<div style="background: #efeeec; color: #63594f; padding: 10px; border: 1px solid #a9a39d; margin: 1em 0;"><p><strong>Available tags:</strong></p>';
				foreach( $available_tags as $key => $value ) {
					$this->return_data .= "<p><strong>" . $key . "</strong>: " . ( strlen( $value ) > 32 ? substr( strip_tags($value), 0, 32 ) . "..." : $value ) . "</p>";
				}
				$this->return_data .= "</div>";
			} else {
				$this->return_data .= "<p>No tags available.</p>";
			}
		}
		
		// Loop over all feed items
		if( count( $items ) > 0 ) {

			// Check for search parameters
			foreach( $this->EE->TMPL->tagparams as $param => $value  ) {
				if( strncmp($param, 'search:', 7) == 0 ) {
					// This is a search: paramter, find which field
					$field = substr( $param, 7 );
					if( isset( $available_tags[ $field ] )) {
						// Filter based on the field $field using the value $value
						$value = strtolower($value);
						if( strpos($value, "not ") === 0 ) {
							// NOT this value
							$value = substr($value, 4);
							$fn = create_function('$var', '
								return preg_match("('. $value .')", strtolower( $var["'.$field.'"] )) == 0;
							');
							$items = array_filter( $items, $fn );
						} else {
							/*
							$fn = create_function('$var', '
								return $var["' . $field . '"] == "' . $value . '";
							');
							*/
							$fn = create_function('$var', '
								return preg_match("('. $value .')", strtolower( $var["'.$field.'"] )) > 0;
							');
							$items = array_filter( $items, $fn );
						}
					}
				}
			}

			// Check for orderby parameter
			if( $this->orderby == "random" ) {
				// Randomise order
				shuffle( $items );
			} elseif( $this->orderby !== FALSE ) {
				// Set order by tag
				if( isset( $available_tags[ $this->orderby ] )) {
					$fn = create_function('$a, $b', '
						$orderby = "' . $this->orderby . '";
						$al = strtolower($a[$orderby]);
						$bl = strtolower($b[$orderby]);
						if ($al == $bl) {
							return 0;
						}
						return ($al > $bl) ? +1 : -1;
					');
					usort( $items, $fn );
					// Reverse order?
					// todo: more efficient to include this in above
					if( $this->EE->TMPL->fetch_param('sort') == "desc" ) {
						$items = array_reverse($items, TRUE);
					}
				}
			} else {
				// Option to reverse order when orderby= not being used
				if( $this->EE->TMPL->fetch_param('sort') == "reverse" ) {
					$items = array_reverse($items, TRUE);
				}
			}
			
			// Loop over all field of all entries looking for fields with
			// multiple entries (ie, tags# > 1)
			foreach ( $items as $idx => $item ) {
				foreach( $item as $key => $value ) {
					if( isset( $item[ $key . "#" ] ) && ( $item[ $key . "#" ] > 1 ) ) {
						$no_elements = $item[ $key . "#" ];
						$sub = array();
						$sub[] = array( $key."_item" => $item[ $key ] );
						for( $i=2; $i<=$no_elements; $i++ ) {
							$sub[] = array( $key."_item" => $item[ $key . "#" . $i ] );
						}
						$items[ $idx ][ $key . "_list" ] = $sub;
					}
				}
			}

			/*
			$count = 0;
			$total_results = count( $items );
			if( $total_results > $this->limit ) {
				$total_results = $this->limit;
			}
			
			foreach ( $items as $item ) {

				$count++;
				
				if( ( $count - $this->offset ) > $this->limit ) break;
				if( $count <= $this->offset ) continue;

				$tagdata = $this->EE->TMPL->tagdata;

				// Parse conditionals (currently just count and total_results)
				$cond = array();
				$cond["count"] = $count - $this->offset;
				$cond["total_results"] = $total_results;
				foreach( $available_tags as $tag => $tagvalue ) {
					if( isset( $item[$tag] ) ) {
				 		$cond[$this->prefix . $tag] = $item[$tag];
					}
				}
				$tagdata = $this->EE->functions->prep_conditionals( $tagdata, $cond );
				
				// Parse {switch} tag
				if (preg_match("/".LD."switch=[\"'](.*?)[\"']".RD."/", $tagdata, $match)) {
					$sopt = explode("|", $match[1]);
					$sw = $sopt[($count-1 + count($sopt)) % count($sopt)];
					$tagdata = preg_replace("/".LD."switch=[\"'](.*?)[\"']".RD."/", $sw, $tagdata);
				}
				
				// Loop over tags, replacing where required
				foreach( $item as $key => $value ) {

					$key = $this->prefix . $key;

					// Clean up value
					$value = $this->EE->security->xss_clean($value);
					$replace = array("{","}");
					$with = array("&123;","&125;");
					$value = str_replace($replace, $with, $value);
					
					// Parse dates
					if (preg_match("!".LD.$key."\s+format=[\"'](.*?)[\"']".RD."!s", $tagdata, $match)) {
						$str = $match['1'];
						$codes = $this->EE->localize->fetch_date_params( $str );
						foreach ( $codes as $code ) {
							$date = $this->_parse_date( $value ); 
							$str = str_replace( $code, $this->EE->localize->convert_timestamp( $code, $date, TRUE ), $str );
						}
						$tagdata = str_replace( $match['0'], $str, $tagdata );
					}

					// Replace remaining tags
					$tagdata = str_replace(LD.$key.RD, $value, $tagdata);
					
				}

				$tagdata = str_replace(LD."count".RD, $count - $this->offset, $tagdata);
				$tagdata = str_replace(LD."total_results".RD, $total_results, $tagdata);

				// Remove empty tags.
				foreach( $available_tags as $key => $value ) {
					$tagdata = str_replace(LD.$key.RD, "", $tagdata);
				}

				$this->return_data .= $tagdata;
			}
			
			*/
		
			if( count($items) === 0 ) {
				$this->return_data .= $this->EE->TMPL->no_results();
				return;
			}
		
			// Needs to have index starting from zero, so bodge for now...
			$variables = array();
			$count = 0;
			foreach( $items as $item ) {
				
				$count++;
				if( ( $count - $this->offset ) > $this->limit ) break;
				if( $count <= $this->offset ) continue;
				
				$new_item = array();
				foreach( $item as $key => $value ) {
					// Parse any date fields
					if( in_array( $key, $this->dates ) ) {
						$value = $this->_parse_date( $value );
					}
					// Add prefix to variable
					$new_item[ $this->prefix. $key ] = $value;
				}
				$variables[] = $new_item;
			}
			
			$tagdata = $this->EE->TMPL->tagdata;
			$this->return_data .= $this->EE->TMPL->parse_variables( $tagdata, $variables );
		}

		return $this->return_data;
	}

	/**
	 * Fetch file from cache or from URL
	 *
	 * @param string $url 
	 * @return string $xml
	 * @author Andrew Weaver
	 */
	function _fetch_feed( $url ) {
		
		$xml = FALSE;
		
		// Check cache
		
		$cache_path = APPPATH . 'cache/' . $this->cache_name . '/';
		$cache_refresh = $this->cache_refresh * 60; // seconds
		
		// Check cache folder exists
		if( ! @is_dir( $cache_path ) ) {
			@mkdir( $cache_path );
			@chmod( $cache_path, 0777 );
		}
		
		// Create hash of the URL
		$file_hash = md5( $url );
		
		$cache_status = "Not found";
		
		// Is file in the cache?
		if ( file_exists( $cache_path . $file_hash ) ) {

			if ( $this->debug ) { $this->return_data .= "<p>Cache file exists</p>\n"; }

			// Is cache file new enough?
			$mtime = filemtime( $cache_path . $file_hash );
			$age = time() - $mtime;

			if ( $cache_refresh > $age ) {

				if ( $this->debug ) { 
					$this->return_data .= "<p>Cache file is fresh (" . ($cache_refresh - $age) . " seconds remaining)</p>\n"; 
					$cache_status = "OK";		
				}

				// Cache found and still valid
				$xml = @file_get_contents( $cache_path . $file_hash );
				
			} else {
				if ( $this->debug ) { 
					$this->return_data .= "<p>Cache file is stale (" . ($cache_refresh - $age) . " seconds)</p>\n"; 
					$cache_status = "Stale";
				}
			}
		} else {
			if ( $this->debug ) { $this->return_data .= "<p>Cache file does not exist</p>\n"; }
		}
		
		if( $xml === FALSE ) {
			
			// Fetch file
			if( $this->EE->TMPL->fetch_param('method') == "curl" ) {
				$xml = $this->_curl_fetch( $url );
			} else {
				$xml = @file_get_contents( $url );
			}

			// $xml = mb_convert_encoding($xml, "UTF-8");

			// Write to cache
			if( $xml !== FALSE ) {
				$f_h = fopen( $cache_path . $file_hash, "w" );
				fwrite( $f_h, $xml );
				fclose( $f_h );
				if ( $this->debug ) { $this->return_data .= "<p>Cache file $file_hash written</p>\n"; }
			}
			
			if( $xml === FALSE ) {
				// Fetch old cache file or return nothing?
				if( $cache_status == "Stale" ) {
					// Use old data
					$xml = @file_get_contents( $cache_path . $file_hash );
					$this->return_data .= "<p>Cannot fetch new feed. Using stale cache file</p>\n";
				}
			}

		}
		
		return $xml;
	}
	
	/**
	 * Convert xml object created by $this->EE->xmlparser->parse_xml into a flat
	 * array of items. Array contains elements based on the XPath in $search. 
	 * Recursive function.
	 *
	 * @param string $x xml array object
	 * @param string $search the Xpath 
	 * @param array $items items found
	 * @param string $path current path
	 * @param string $element current element id
	 * @param boolean $in_element are we in an element?
	 * @param string $subpath 
	 * @return array items
	 * @author Andrew Weaver
	 */
	function _fetch_xml( $x, $search, &$items, $path="", $element=0, $in_element=false, $subpath="" ) {

		$path = $path . "/" . $x->tag ;

		// print "<p>$path = $search</p>";

		if ( $path == $search ) {

			// Path matches exactly our search element - we are in a new item
			$element++;
			$items[ $element ] = array();
			$subpath = "";
			$in_element = true;

		} elseif ( $str = strstr( $path, $search ) ) {
			
			// We are within an existing item  - get xpath of subcomponent
			$subpath = substr( $str, strlen( $search )+1 );
			if ( ! isset( $items[ $element ][ $subpath . "#" ] ) ) {
				$items[ $element ][ $subpath . "#" ] = 0;
			}
			$count = $items[ $element ][ $subpath . "#" ]++;
			if ( isset( $items[ $element ][ $subpath ] ) ) {
				$subpath .= "#" . ( $count + 1);
			}
		} else {
			$in_element = false;
		}

		if ( count( $x->children ) == 0 ) {

			// Element has children ie, is not a parent element
			if ( $in_element ) {
				// If within an item, add to its array
				$items[ $element ][ $subpath ] = $x->value;
			}
			
		} else {

			// Loop over all child elements...        
			foreach ( $x->children as $key => $value ) {
				// ...and recurse through xml structure
				$element = $this->_fetch_xml( $value, $search, $items, $path, $element, $in_element, $subpath );
			}

		}

		// Add attributes
		if( $in_element ) {
			if ( is_array( $x->attributes ) ) {
				foreach ( $x->attributes as $attr_key => $attr_value ) {
					$items[ $element ][ $subpath . "@" . $attr_key ] = $attr_value;
				}
			}
		}

		return $element;
	}
	
	/**
	 * Read file using curl
	 *
	 */
	function _curl_fetch($url) {

		if ( $this->debug ) { $this->return_data .= "<p>Using curl to fetch file</p>"; }

		$ch = curl_init(); 
		curl_setopt($ch, CURLOPT_URL, $url); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_USERAGENT, "EE/Ajw_feedparser PHP/" . phpversion());
		
		$connect_timeout = $this->EE->TMPL->fetch_param('curl_connect_timeout'); 
		if( $connect_timeout !== FALSE ) {
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $connect_timeout);
		}

		$timeout = $this->EE->TMPL->fetch_param('curl_timeout'); 
		if( $timeout !== FALSE ) {
			curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		}
		
		$data = curl_exec($ch);

		if(curl_exec($ch) === false) {
			if ( $this->debug ) { $this->return_data .= "<p>curl: " . curl_error($ch) . "</p>\n"; }
			return FALSE;
		}
		
		return $data;
	}

	/**
	 * Read file using fsockopen
	 *
	 * @param string $url 
	 * @return void
	 * @author Andrew Weaver
	 */
	function _fsockopen_fetch($url) {
		$target = parse_url($url);

		$data = '';

		$fp = fsockopen($target['host'], 80, $error_num, $error_str, 8); 

		if (is_resource($fp)) {
			fputs($fp, "GET {$url} HTTP/1.0\r\n");
			fputs($fp, "Host: {$target['host']}\r\n");
			fputs($fp, "User-Agent: EE/Ajw_feedparser PHP/" . phpversion() . "\r\n\r\n");

			$headers = TRUE;

			while( ! feof($fp)) {
				$line = fgets($fp, 4096);

				if ($headers === FALSE) {
					$data .= $line;
				} elseif (trim($line) == '') {
					$headers = FALSE;
				}
			}

			fclose($fp); 
		}

		return $data;
	}

	/**
	 * Try to read the date and return as timestamp
	 *
	 * @param string $datestr 
	 * @return int the date
	 */
	function _parse_date( $datestr ) {
		$date = strtotime( $datestr );
		if ( $date == -1 || $date == "" ) {
			$date = time();
		}
		return( $date );
	}

	function _convert_smart_quotes($string) 
	{ 
		$search = array(chr(145), 
		                    chr(146), 
		                    chr(147), 
		                    chr(148), 
		                    chr(151),
												chr(133)); 

		    $replace = array("'", 
		                     "'", 
		                     '"', 
		                     '"', 
		                     '-',
												'...'); 

		    return str_replace($search, $replace, $string);
	}

	/**
	 * Usage instructions with EE Control Panel
	 *
	 * @return void
	 * @author Andrew Weaver
	 */
	function usage() {
		ob_start(); 
		?>

EXAMPLE USAGE:

{exp:ajw_feedparser 
  url="http://search.twitter.com/search.atom?q=eecms"
  itempath="/feed/entry"
  cache_refresh="10"
  limit="5"
}
  <h1>Tweet from {published}</h3>
  {if count == 1}<p>FIRST Entry</p>{/if}
  <p class="{switch="odd|even"}">{content}</p>
  <p><a href="{link@href}">Link</a></p>
  {if count == total_results}<p>LAST Entry</p>{/if}
  <div class="posted">Posted from <a href="{author/uri}">{author/name}</a> 
    on {published format="%D, %F %d, %Y - %g:%i:%s %a"} 
    ({count} of {total_results})</div>
{/exp:ajw_feedparser}

PLUGIN PARAMETERS:

url - the url of the XML feed to fetch and parse
itempath - the XPath of the feed's entries (if not set, then the plugin will guess)
limit - the maximum number of entries to display
offset - the number of entries to skip at the start of the feed
cache_refresh - the time in minutes before fetching the file again
debug - adding this parameter will display debugging information (see below)

DEBUG MODE

By adding the debug="true" parameter, the plugin will display information as it 
fetches and parses the feed.

It will also display a list of available variables that you can use within the plugin.

DATES

If you add a format parameter to a variable the plugin will try to parse it as a date, 
using the default EE formatting, eg, {published format="%D, %F %d, %Y - %g:%i:%s %a"}

SEARCH/FILTER

You can filter the entries by using a search:field parameter, eg:

  search:summary="rhubarb"
  
will only return items that contain the word rhubarb in the summary field.

You can use the '|' character to specify additional terms, eg:

  "rhubarb|custard" will find entries containing rhubarb OR custard

You can also preceed the keyword with 'not' to find items that don't contain 
the word(s), eg:

  "not rhubarb|custard" = entries that contain neither rhubarb or custard.


		<?php
		$buffer = ob_get_contents();	
		ob_end_clean(); 
		return $buffer;
	}

} // end class Ajw_feedparser
