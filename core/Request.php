<?php
/**
* Request
*/
class Request
{
	function __construct()
	{
		if(IS_CLI){
			$_SERVER['REQUEST_URI'] = $GLOBALS['argv'][1];
			$pos = strpos($_SERVER['REQUEST_URI'], '?');
			if($pos > 0) $_SERVER['QUERY_STRING'] = substr($_SERVER['REQUEST_URI'], $pos+1);
			parse_str($_SERVER['QUERY_STRING'], $_GET);
			parse_str($GLOBALS['argv'][2], $_POST);
		}else{
			if(function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc())
			{
				if(isset($_GET))
					$_GET=$this->__stripSlashes($_GET);
				if(isset($_POST))
					$_POST=$this->__stripSlashes($_POST);
				if(isset($_REQUEST))
					$_REQUEST=$this->__stripSlashes($_REQUEST);
				if(isset($_COOKIE))
					$_COOKIE=$this->__stripSlashes($_COOKIE);
			}
		}
	}

	private function __stripSlashes($data)
	{
		if(is_array($data))
		{
			if(count($data) == 0)
				return $data;
			$keys=array_map('stripslashes',array_keys($data));
			$data=array_combine($keys,array_values($data));
			return array_map(array($this,'__stripSlashes'),$data);
		}
		else
			return stripslashes($data);
	}

	/**
	 * 获取当前请求的action
	 * @return string action
	 */
	public function getAction()
	{
		return strtolower($_GET['a']);
	}

	/**
	 * 获取当前请求的controller
	 * @return string controller
	 */
	public function getController()
	{
		return strtolower($_GET['c']);
	}
}