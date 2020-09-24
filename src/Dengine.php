<?php

namespace Chikolokoy08\Dengine;

use Illuminate\Support\Facades\Cache;

class Dengine {

	protected static $cache_name = null;
	protected static $extra_columns = array();
	protected static $fetched_data;
	protected static $server_cols;
	protected static $custom_data_collection = array();
	public static $data = array();
	protected static $searchable_cols = array();

	public static $collection;
	public static $skip;
	public static $limit;
	public static $keyword;
	public static $order;
	public static $column;
	public static $filtered;
	public static $serverside_cols;

	public static $draw;
	public static $total;
	public static $custom_data;
	public static $init_custom_data;
  	public static $filters;

    /**
     * Return boolean if collection / input data from datatable Input::get() are properly set
     * @param array
     * @return boolean
     */
	private static function isset_collection()
	{
		try {
			return ((isset(static::$collection) && static::$collection != '') ? TRUE : FALSE);	
		} catch (\Exception $e) {
			\Log::error($e);
		}
	}

    /**
     * Set Input::get('draw') passed by datatable client side
     * @return int static::$draw
     */
	public static function draw()
	{
		try {
			static::$draw = 0;
			if (static::isset_collection()) {
				static::$draw = static::$collection['draw'];
			}
			return static::$draw;			
		} catch (\Exception $e) {
			\Log::error($e);
		}
	}

    /**
     * Set collection / Input::get() passed
     * @param array $dtinputs, int $uid
     * @return array static::$collection
     */
	public static function parse($dtinputs=array(), $uid=0)
	{
		try {
			static::$collection 	= $dtinputs;
			//For caching
			if (!empty($unique_id)) {
				//For unique user identifier
				$for_cache_name 		= array_merge($dtinputs, ['uid'=>$uid]);
				$trace 					= debug_backtrace();
				if (isset($trace[1])) {
					//For unique function caller identifier
					$for_cache_name = array_merge($for_cache_name, ['function_caller'=>$trace[1]['function']]);
				}
				static::$cache_name = md5(static::encdecData('en', $for_cache_name));
			}
			
			static::searchable();
			return static::$collection;			
		} catch (\Exception $e) {
			\Log::error($e);
		}
	}

	protected static function searchable()
	{
		try {
			if (static::$collection) {
				$collection = static::$collection;
				foreach ($collection['columns'] as $s_col) {
					if ($s_col['searchable'] == true && $s_col['data'] != 'action') {
						static::$searchable_cols[] = $s_col['data'];
					}
				}
			}			
		} catch (\Exception $e) {
			\Log::error($e);
		}
	}

    /**
     * Set Input::get('start') passed by datatable client side
     * @param array $dtinputs
     * @return int static::$skip
     */
	public static function skip()
	{
		try {
			static::$skip = 0;
			if (static::isset_collection()) {
				static::$skip = static::$collection['start'];
			}
			return static::$skip;
		} catch (\Exception $e) {
			\Log::error($e);
		}
	}

    /**
     * Set Input::get('length') passed by datatable client side
     * @return int static::$limit
     */
	public static function limit()
	{
		try {
			static::$limit = 0;
			if (static::isset_collection()) {
				static::$limit = static::$collection['length'];
			}
			return static::$limit;			
		} catch (\Exception $e) {
			\Log::error($e);
		}
	}

    /**
     * Set Input::get('search')['value'] passed by datatable client side
     * @return string static::$keyword
     */
	public static function search_keyword($disableDTPSearch=true)
	{
		try {
			static::$keyword = '';
			if (static::isset_collection()) {
				static::$keyword = $disableDTPSearch == true ? static::$collection['search']['value'] : '';
			}
			return static::$keyword;			
		} catch (\Exception $e) {
			\Log::error($e);
		}
	}

    /**
     * Set Input::get('order')[0]['value'] passed by datatable client side
     * @return string static::$order (asc / desc)
     */
	public static function order_type()
	{
		try {
			static::$order = '';
			if (static::isset_collection()) {
				static::$order = static::$collection['order'][0]['dir'];
			}
			return static::$order;			
		} catch (\Exception $e) {
			\Log::error($e);
		}
	}

    /**
     * Set Input::get('order')[0]['column'] passed by datatable client side
     * @return int static::$column (index)
     */
	public static function column()
	{
		try {
			static::$column = '';
			if (static::isset_collection()) {
				static::$column = static::$collection['order'][0]['column'];
			}
			return static::$column;			
		} catch (\Exception $e) {
			\Log::error($e);
		}
	}

    /**
     * Set total count of passed data
     * @param any $obj
     * @return int static::$total
     */
	public static function recordsTotal($obj)
	{
		try {
			$num = $obj;
			if (!is_int($obj)) {
				if (is_string($obj)) {
					$num = intval($obj);
				} else {
					$convert = is_object($obj) ? (array) $obj : $obj;
					$num = count($convert);					
				}
			}
			static::$total = is_null($num) || empty($num) ? 0 : $num;
			return static::$total;			
		} catch (\Exception $e) {
			\Log::error($e);
		}
	}

    /**
     * Set key-value pairs to be used by various function in this and outside the Dengine
     * @param array $fetched_array
     * @return mixed
     */
	public static function prepare($fetched_array=array())
	{
		try {
		    static::$fetched_data = !is_array($fetched_array) ? (array) reset($fetched_array) : $fetched_array;

		    if (!is_null(static::$cache_name)) {
		    	Cache::put(static::$cache_name, ['total' => static::$total, 'data' => static::$fetched_data], 5);
		    }
			static::$filtered = count(static::$fetched_data);
			static::$data['draw'] = static::draw();
			static::$data['recordsTotal'] = static::$total;
			static::$data['recordsFiltered'] = (isset(static::$keyword) && static::$keyword != '' ? static::$filtered : static::$total);			
		} catch (\Exception $e) {
			\Log::error($e);
		}
	}

	public static function prepareCache()
	{
		try {
			$cached_data = [];
			if (!is_null(static::$cache_name) && !is_null(static::fetchCachedData(static::$cache_name)) ) {
				$cached_data = static::fetchCachedData(static::$cache_name);
			}
			return $cached_data;			
		} catch (\Exception $e) {
			\Log::error($e);
		}
	}


	public static function setParameters($arrays=array())
    {
    	try {
    		$full_parameters = [];
    		$default_params = [
				'skip' => static::skip(), 
				'take' => static::limit(), 
				'column_text' => '', 
				'order_type' => static::order_type(), 
				'keyword' => static::search_keyword(),
				'get_total' => true,
				'column' => static::column(),
			];
			if (!empty($arrays) && count($arrays) > 0) {
				$full_parameters = array_merge($default_params, $arrays);
			} else {
				$full_parameters = $default_params;
			}

			return $full_parameters;

    	} catch (\Exception $e) {
    		\Log::error($e);
    	}
    }

    /**
     * Set dynamic server side column set for datatable rendering on front-end
     * @param array $obj
     * @return mixed
     */
	public static function serverside_cols($obj=array())
	{
		try {
			for ($i=0; $i < count($obj); $i++) {
				if (isset($obj[$i]['data'])) {
					$dval = $obj[$i]['data'];
					$obj[$i] += ['mData' => $dval];
					// /array_merge($obj[$i], array('mData'=>$dval));
				}
				if (isset($obj[$i]['title'])) {
					$dval = $obj[$i]['title'];
					$obj[$i] += ['sTitle' => $dval];
				}
				if (isset($obj[$i]['orderable'])) {
					$dval = $obj[$i]['orderable'];
					$obj[$i] += ['bSortable' => $dval];
				}
				if (isset($obj[$i]['searchable'])) {
					$dval = $obj[$i]['searchable'];
					$obj[$i] += ['bSearchable' => $dval];
				}
			}
			static::$server_cols = $obj;
			return static::$server_cols;
		} catch (\Exception $e) {
			\Log::error($e);
		}
	}

    /**
     * Returns completed data structure for datatables plugin query
     * @param boolean $json
     * @return mixed (true: json_encoded, false: raw)
     */
	public static function make($json=true, $extra=[])
	{
		try {
			static::$data['draw'] = static::draw();
			static::$data['server_cols'] = (isset(static::$server_cols) && static::$server_cols != '' ? static::$server_cols : '' );
			static::$data['data'] = static::init_columns();
			static::$data['recordsTotal'] = static::$total;
			static::$data['recordsFiltered'] = (isset(static::$keyword) && static::$keyword != '' ? static::$filtered : static::$total);
			static::init_custom_data(static::$data);
			if (isset($extra['skim_rows']) && !empty($extra['skim_rows'])) {
				$skey = $extra['skim_rows']['key'];
				static::$data[$skey] = static::skim_rows($skey);
			}
			return ( $json ? json_encode(static::$data) : static::$data );			
		} catch (\Exception $e) {
			\Log::error($e);
		}
	}

    /**
     * Set custom column based on row values of the list
     * @param string $name
     * @param mixed $content
     */
	public static function add_column($name, $content)
	{
		try {
			static::$extra_columns[] = array('name' => $name, 'content' => $content);	
		} catch (\Exception $e) {
			\Log::error($e);
		}
	}

    public static function add_filters($name, $callback)
    {
    	try {
	        if (!isset(static::$filters[$name])) {
	            static::$filters[$name] = [];
	        }
	        if (is_callable($callback)) {
	            static::$filters[$name][] = $callback;
	        }    		
    	} catch (\Exception $e) {
    		\Log::error($e);
    	}
    }

    /**
     * Re-organized columns
     * @param mixed
     */
	private static function init_columns()
	{
		try {
			$new_data = array();

			if (static::isset_collection()) {

				$new_data = static::collection_to_array(static::$fetched_data);
				for ($i=0; $i < count($new_data); $i++) {

		            foreach (static::$extra_columns as $key => $value) {

		                if (is_string($value['content'])) {
							$value['content'] = $value['content'];
		                } else if(is_callable($value['content'])) {
		                    $value['content'] = $value['content'](static::$fetched_data[$i]);
		                }

		                $new_data[$i] = static::include_in_array($value,$new_data[$i]);
		            }
				}

				if (static::$keyword != '') {
					$new_data = static::filter_array($new_data, static::$keyword);
					static::$filtered = count($new_data);
					$new_data = static::skip_take($new_data);
				}
			}

			return $new_data;
		} catch (\Exception $e) {
			\Log::error($e);
		}
	}

	private static function filter_array($new_data, $keyword)
	{
		try {
			$array_data = array();
			$item_array_ids = array();
			if (!empty($new_data)) {
				$id 				= 1;
				foreach ($new_data as $item) {
					foreach($item as $field => $ritem) {
						if (in_array($field, static::$searchable_cols)) {
							if (!isset($item['temp_id'])) {
								// var_dump(static::get_keyword_score($ritem, $keyword));
								if (static::get_keyword_score($ritem, $keyword)) {
									$item_temp_id = !isset($item['temp_id']) ? $item['temp_id'] = $id : $item['temp_id'];
									if (!in_array($item_temp_id, $item_array_ids)) {
										array_push($item_array_ids, $id);
										array_push($array_data, $item);
										$id++;
									}
								}
							}
						}
					}
				}
			}

			return $array_data;
		} catch (\Exception $e) {
			\Log::error($e);
		}
	}

	private static function get_keyword_score($str_ritem='', $keyword='')
	{
		try {
			return is_string($str_ritem) && strpos(trim(strtolower($str_ritem)), trim(strtolower($keyword)))!==FALSE;	
		} catch (\Exception $e) {
			\Log::error($e);
		}
	    
	}

	private static function pre_filter($new_data, $keyword)
	{
		try {
			$array_data = array();
			if (!empty($new_data) && count($new_data) > 0) {
				$keywords = array_filter(array_unique(explode(" ", trim(strtolower($keyword)))));
				foreach ($new_data as $item) {
					$weight = 0;
					$percent = 0;
					$percentage = 0;
					foreach ($item as $field => $ritem) {
						//Add bonus as per whole keyword
						$str_ritem = (string) $ritem;
						$weight += similar_text(trim(strtolower($str_ritem)), trim(strtolower($keyword)), $percent);
						$percentage += $percent;
		                foreach($keywords as $active_keyword) {
		                	$weight += similar_text(trim(strtolower($str_ritem)), $active_keyword, $percent);
		                	$percentage += $percent;
		                	$item['weight'] = $weight;
		                	$item['percentage'] = $percentage;
		                }
					}
					array_push($array_data, $item);
				}
			}
			return $array_data;
		} catch (\Exception $e) {
			\Log::error($e);
		}
	}

	private static function skip_take($new_data)
	{
		try {
			$array_data = array();
			$limit = static::$skip + static::$limit;
			if ($limit > static::$filtered) {
				$limit = static::$filtered;
			}
			for ($i=static::$skip; $i < $limit; $i++) {
				array_push($array_data, $new_data[$i]);
			}
			return $array_data;
		} catch (\Exception $e) {
			\Log::error($e);
		}
	}

    /**
     * Converts fetch data object into readable and customizable array
     * @param object $obj_collection
     * @return array (converted array)
     */
	private static function collection_to_array($obj_collection)
	{
		try {
			$collection_array = array();
			foreach($obj_collection as $value) {
				$collection_array[] = (array) $value;
			}
			return $collection_array;			
		} catch (\Exception $e) {
			\Log::error($e);
		}
	}

    /**
     * Parses and compiles strings by using Blade Template System
     * @return string
     */
    private static function blader($str,$data = array())
    {
    	try {
	        $empty_filesystem_instance = new Filesystem;
	        $blade = new BladeCompiler($empty_filesystem_instance,'datatables');
	        $parsed_string = $blade->compileString($str);

	        ob_start() and extract($data, EXTR_SKIP);

	        try
	        {
	            eval('?>'.$parsed_string);
	        }

	        catch (\Exception $e)
	        {
	            ob_end_clean(); throw $e;
	        }

	        $str = ob_get_contents();
	        ob_end_clean();

	        return $str;    		
    	} catch (\Exception $e) {
    		\Log::error($e);
    	}

    }

    private static function stringer($str, $data = array()) 
    {
    	
    }

    /**
     * Includes new array to existing array
     * @return string
     */
    private static function include_in_array($item,$array)
    {
    	try {
	    	$row = $array;
	    	if (is_object($array)) {
	    		$row = (array) $array;
	    	}
	    	return array_merge($row,array($item['name']=>$item['content']));
    	} catch (\Exception $e) {
    		\Log::error($e);
    	}
    }

    private static function init_custom_data($data)
    {
    	try {
	    	if (isset(static::$custom_data_collection) && static::$custom_data_collection != '') {
	    		foreach (static::$custom_data_collection as $key=>$value) {
	    			static::$data[$key] = $value;
	    		}
	    	}
	    	return static::$data;    		
    	} catch (\Exception $e) {
    		\Log::error($e);
    	}
    }

    public static function custom_data($name, $content)
    {
    	try {
	    	static::$custom_data_collection[$name] = $content;
	    	return static::$custom_data_collection;    		
    	} catch (\Exception $e) {
    		\Log::error($e);
    	}
    }

    public function __call($name, $arguments)
    {
    	try {
	        $name = strtolower(preg_replace('/([^A-Z])([A-Z])/', "$1_$2", $name));
	        if (method_exists($this, $name)) {
	            return call_user_func_array(array($this, $name),$arguments);
	        } else {
	            trigger_error('Call to undefined method '.__CLASS__.'::'.$name.'()', E_USER_ERROR);
	        }
    	} catch (\Exception $e) {
    		\Log::error($e);
    	}
    }

    /**
     * Row checker if a specific key value pair is in the collection
     * @param mixed $content
     */
	public static function skim_rows($passed_key)
	{
		try {
			$has_one = false;
	    	if (isset(static::$data['data']) && static::$data['data'] != '') {
	    		foreach (static::$data['data'] as $key=>$item) {
	    			if ($has_one == false && !is_null($item[$passed_key])) {
	    				$has_one = true;
	    			}
	    		}
	    	}
	    	return $has_one;			
		} catch (\Exception $e) {
			\Log::error($e);
		}
	}

	private static function encdecData($type='en', $data) {
		try {
			return $type==='de' ? unserialize(urldecode(base64_decode($data))) : base64_encode(urlencode(serialize($data)));	
		} catch (\Exception $e) {
			\Log::error($e);
		}
    }

    private static function fetchCachedData($cache_name='', $refresh=false)
    {
        try {
            return $refresh == true ? NULL : Cache::get($cache_name);
        } catch (\Exception $e) {
            \Log::error($e);
        }
    }

}
