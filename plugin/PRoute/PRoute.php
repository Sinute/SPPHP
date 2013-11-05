<?php
/**
* 路由转发, 抽取自Yii
* http://www.yiiframework.com/doc/api/1.1/CUrlManager/
*/
class PRoute extends Plugin
{
	private $__urlFormat, $__rules, $__urlSuffix = false;
	const PATH_FORMAT = 'path';

	function onAfterRequest()
	{
		$this->processRequest();
	}

	/**
	 * 根据配置初始化route实例
	 * @param array $config route配置
	 */
	function __construct($config)
	{
		if ($config['urlFormat'] != self::PATH_FORMAT || !is_array($config['rules']) || empty($config['rules']))
			return;
		$this->__urlFormat = self::PATH_FORMAT;
		if (isset($config['urlSuffix']))
			$this->__urlSuffix = $config['urlSuffix'];
		if (is_array($config['rules'])) {
			if(($this->__rules = SP::PCache()->get(serialize($config['rules']))) != null) {
				$this->__rules = unserialize($this->__rules);
				return;
			}
			foreach ($config['rules'] as $pattern => $route)
				$this->__rules[] = $this->__createUrlRule($pattern, $route);
			SP::PCache()->set(serialize($config['rules']), serialize($this->__rules));
		}
	}

	/**
	 * 处理请求
	 */
	public function processRequest()
	{
		if ($this->__urlFormat !== self::PATH_FORMAT) return;
		$rawPathInfo = $this->__getPathInfo();
		$pathInfo = $this->__removeUrlSuffix( $rawPathInfo, $this->__urlSuffix);
		foreach ($this->__rules as $i => $rule) if (($r=$this->__parseUrl($rule, $pathInfo, $rawPathInfo))!==false)
		{
			$req = explode('/', $r);
			$_REQUEST['c'] = $_GET['c'] = $req[0];
			$_REQUEST['a'] = $_GET['a'] = $req[1];
			return;
		}
		throw new HttpException("Error Processing Request {$rawPathInfo}", 0, 404);
		
	}

	/**
	 * 创建url规则
	 * @param  string $pattern 路由正则
	 * @param  string $route   路由转发规则
	 * @return array           路由规则的数组
	 */
	private function __createUrlRule($pattern, $route)
	{
		$params = $references = $defaultParams = array();
		$routePattern = $urlSuffix = null;

		// 载入配置参数
		if (is_array($route)) foreach(array('urlSuffix', 'defaultParams', 'pattern', 'route') as $name) if(isset($route[$name]))
			$$name = $route[$name];

		$route = trim($route, '/');

		$tr2['/'] = $tr['/'] = '\\/';

		// 获取绑定变量
		if (strpos($route,'<')!==false && preg_match_all('/<(\w+)>/',$route,$matches2)) foreach($matches2[1] as $name)
			$references[$name]="<$name>";

		if (preg_match_all('/<(\w+):?(.*?)?>/', $pattern, $matches))
		{
			$tokens = array_combine($matches[1],$matches[2]);
			foreach($tokens as $name => $value)
			{
				// 没有设定类型则匹配除'/'外所有字符
				if($value === '')
					$value = '[^\/]+';
				$tr["<$name>"] = "(?P<$name>$value)";
				// 如果该绑定变量在路由中被使用则标记准备替换生成路由正则
				if( isset($references[$name]) )
					$tr2["<$name>"] = $tr["<$name>"];
				else // 未使用的绑定变量则作为普通参数
					$params[$name] = $value;
			}
		}
		$p = rtrim($pattern, '*');
		$append = $p !== $pattern;
		$p = trim($p,'/');
		$template = preg_replace('/<(\w+):?.*?>/', '<$1>', $p);
		$pattern = '/^' . strtr($template, $tr) . '\/';
		if($append)
			$pattern.='/u';
		else
			$pattern.='$/u';

		// 如果使用了绑定变量则生成路由正则
		if($references !== array())
			$routePattern='/^' . strtr($route, $tr2) . '$/u';

		return compact('urlSuffix', 'params', 'pattern', 'routePattern', 'references', 'defaultParams', 'route');
	}

	/**
	 * 获取请求的uri
	 * @return string uri
	 */
	private function __getRequestUri()
	{
		$requestUri = null;
		if(isset($_SERVER['REQUEST_URI']))
		{
			$requestUri=$_SERVER['REQUEST_URI'];
			if(!empty($_SERVER['HTTP_HOST']))
			{
				if(strpos($requestUri,$_SERVER['HTTP_HOST'])!==false)
					$requestUri=preg_replace('/^\w+:\/\/[^\/]+/','',$requestUri);
			}
			else
				$requestUri=preg_replace('/^(http|https):\/\/[^\/]+/i','',$requestUri);
		}
		return $requestUri;
	}

	/**
	 * 获取pathInfo
	 * @return string pathInfo
	 */
	private function __getPathInfo()
	{
		$pathInfo = $this->__getRequestUri();

		// 去除参数
		if(($pos=strpos($pathInfo, '?')) !== false)
		   $pathInfo = substr($pathInfo, 0, $pos);

		$pathInfo = $this->__decodePathInfo($pathInfo);

		$scriptUrl = SP::getScriptUrl();
		$baseUrl = SP::getBaseUrl();
		if(strpos($pathInfo, $scriptUrl) === 0)
			$pathInfo = substr($pathInfo,strlen($scriptUrl));
		elseif($baseUrl==='' || strpos($pathInfo, $baseUrl) === 0)
			$pathInfo = substr($pathInfo, strlen($baseUrl));
		elseif(strpos($_SERVER['PHP_SELF'], $scriptUrl) === 0)
			$pathInfo = substr($_SERVER['PHP_SELF'], strlen($scriptUrl));
		else
			$pathInfo = null;

		return trim($pathInfo, '/');
	}

	/**
	 * 解码pathInfo, 非utf8字符则转换为utf8
	 * @return string pathInfo
	 */
	private function __decodePathInfo($pathInfo)
	{
		$pathInfo = urldecode($pathInfo);

		// is it UTF-8?
		// http://w3.org/International/questions/qa-forms-utf-8.html
		if(preg_match('%^(?:
		   [\x09\x0A\x0D\x20-\x7E]            # ASCII
		 | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
		 | \xE0[\xA0-\xBF][\x80-\xBF]         # excluding overlongs
		 | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
		 | \xED[\x80-\x9F][\x80-\xBF]         # excluding surrogates
		 | \xF0[\x90-\xBF][\x80-\xBF]{2}      # planes 1-3
		 | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
		 | \xF4[\x80-\x8F][\x80-\xBF]{2}      # plane 16
		)*$%xs', $pathInfo))
		{
			return $pathInfo;
		}
		else
		{
			return utf8_encode($pathInfo);
		}
	}

	/**
	 * 解析url
	 * @param  array $rule        路由规则
	 * @param  string $pathInfo    pathInfo
	 * @param  string $rawPathInfo 没有去除后缀的rawPathInfo
	 * @return string              标准格式的路由, 解析失败返回false
	 */
	private function __parseUrl($rule, $pathInfo, $rawPathInfo)
	{
		if($rule['urlSuffix'] !== null)
			$pathInfo = $this->__removeUrlSuffix($rawPathInfo, $rule['urlSuffix']);

		$pathInfo .= '/';

		// 匹配规则
		if(preg_match($rule['pattern'], $pathInfo, $matches))
		{
			// 载入默认参数
			foreach($rule['defaultParams'] as $name => $value)
			{
				if(!isset($_GET[$name]))
					$_REQUEST[$name] = $_GET[$name] = $value;
			}
			$tr = array();
			foreach($matches as $key => $value)
			{
				// 注册绑定变量
				if(isset($rule['references'][$key])) {
					$_REQUEST[$key] = $_GET[$key] = $value;
					// 记录变量, 准备通过路由正则替换
					$tr[$rule['references'][$key]] = $value;
				}
				elseif(isset($rule['params'][$key])) // 注册未绑定变量
					$_REQUEST[$key] = $_GET[$key] = $value;
			}
			if($pathInfo !== $matches[0]) // 还有其他其他参数
				$this->__parsePathInfo(ltrim(substr($pathInfo, strlen($matches[0])), '/'));
			if($rule['routePattern']!==null) // 如果绑定了路由变量则替换
				return strtr($rule['route'],$tr);
			else // 否则直接返回路由字符串
				return $rule['route'];
		}
		else
			return false;
	}

	/**
	 * 解析pathInfo并将变量注册到本次请求
	 * @param  string $pathInfo pathInfo
	 */
	private function __parsePathInfo($pathInfo)
	{
		if($pathInfo==='')
			return;
		$segs=explode('/',$pathInfo.'/');
		$n=count($segs);
		for($i=0;$i<$n-1;$i+=2)
		{
			$key=$segs[$i];
			if($key==='') continue;
			$value=$segs[$i+1];
			// 参数为数组的情况
			if(($pos=strpos($key,'['))!==false && ($m=preg_match_all('/\[(.*?)\]/',$key,$matches))>0)
			{
				$name=substr($key,0,$pos);
				for($j=$m-1;$j>=0;--$j)
				{
					if($matches[1][$j]==='')
						$value=array($value);
					else
						$value=array($matches[1][$j]=>$value);
				}
				if(isset($_GET[$name]) && is_array($_GET[$name]))
					$value=$this->__mergeArray($_GET[$name],$value);
				$_REQUEST[$name]=$_GET[$name]=$value;
			}
			else
				$_REQUEST[$key]=$_GET[$key]=$value;
		}
	}

	/**
	 * 合并数组
	 * @param  array $a 数组1
	 * @param  array $b 数组2
	 * ...
	 * @param  array $n 数组n
	 * @return array    合并后的数组
	 */
	private function __mergeArray($a,$b)
	{
		$args=func_get_args();
		$res=array_shift($args);
		while(!empty($args))
		{
			$next=array_shift($args);
			foreach($next as $k => $v)
			{
				// 索引数组有则使用新索引, 没有则按旧索引直接添加
				if(is_integer($k))
					isset($res[$k]) ? $res[]=$v : $res[$k]=$v;
				// 索引数组且键重复且值都为数组, 递归合并数组
				elseif(is_array($v) && isset($res[$k]) && is_array($res[$k]))
					$res[$k]=$this->mergeArray($res[$k],$v);
				// 其他情况后面的值直接覆盖之前的值
				else
					$res[$k]=$v;
			}
		}
		return $res;
	}

	/**
	 * 移除后缀
	 * @param  string $pathInfo  pathInfo
	 * @param  string $urlSuffix 后缀
	 * @return string            移除后缀后的pathInfo
	 */
	private function __removeUrlSuffix($pathInfo,$urlSuffix)
	{
		if($urlSuffix!=='' && substr($pathInfo,-strlen($urlSuffix))===$urlSuffix)
			return substr($pathInfo,0,-strlen($urlSuffix));
		else
			return $pathInfo;
	}
}

