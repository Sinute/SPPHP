<?php
// TODO local
// TODO default error page
// TODO autoload js/css
// TODO verification code
defined('APP_ROOT') or die('No APP_ROOT');
defined('SP_DEBUG') or define('SP_DEBUG', false);
defined('DS') or define('DS', DIRECTORY_SEPARATOR);
defined('SP_ROOT') or define('SP_ROOT', dirname(__FILE__));
defined('IS_CLI') or define('IS_CLI', PHP_SAPI === 'cli');

session_name('spauth');
$sessionParams = session_get_cookie_params();
$sessionParams['httponly'] = true;
call_user_func_array('session_set_cookie_params', $sessionParams);
session_start();

require SP_ROOT.DS.'core'.DS.'Hook.php';
require SP_ROOT.DS.'core'.DS.'Plugin.php';
require SP_ROOT.DS.'core'.DS.'Request.php';
require SP_ROOT.DS.'core'.DS.'BaseModel.php';
require SP_ROOT.DS.'core'.DS.'BaseController.php';
require SP_ROOT.DS.'core'.DS.'HttpException.php';

if(SP_DEBUG)
{
	error_reporting(E_ALL ^ E_NOTICE | E_STRICT);
	ini_set( 'display_errors' , true );
	if(!IS_CLI)
	{
		set_exception_handler(function($exception){
			ob_end_clean();
			require SP_ROOT.DS.'view'.DS.'error.php';
			die();
		});
		set_error_handler(function($errno, $errstr, $errfile, $errline, $errcontext){
			if (!(error_reporting() & $errno)) return;
			ob_end_clean();
			require SP_ROOT.DS.'view'.DS.'error.php';
			die();
		}, error_reporting());
	}
}else{
	error_reporting(E_ALL & ~E_DEPRECATED);
	ini_set( 'display_errors' , false );
}

/**
* SP
*/
class SP
{
	private $__hook; // hook
	static private $__config; // config
	static private $__controller; // controller
	static private $__action; // action
	static private $__appName; // 应用名
	static private $__scriptUrl; // 站点url
	static private $__baseUrl; // 站点路径url
	static private $__plugins; // 插件类

	/**
	 * 应用初始化
	 * @param array $config 配置
	 */
	function __construct($config)
	{
		spl_autoload_register(array($this, 'autoLoad'));
		self::$__config = $config;
		$this->__hook = new Hook();
		self::$__scriptUrl = null;
		self::$__baseUrl = null;
		self::$__appName = self::$__config['appName'];
	}

	/**
	 * 获取当前路径
	 * @return string 路径
	 */
	static public function getScriptUrl()
	{
		if(IS_CLI) return '/';
		if(self::$__scriptUrl !== null) return self::$__scriptUrl;
		$scriptName=basename($_SERVER['SCRIPT_FILENAME']);
		if(basename($_SERVER['SCRIPT_NAME'])===$scriptName)
			self::$__scriptUrl=$_SERVER['SCRIPT_NAME'];
		elseif(basename($_SERVER['PHP_SELF'])===$scriptName)
			self::$__scriptUrl=$_SERVER['PHP_SELF'];
		elseif(isset($_SERVER['ORIG_SCRIPT_NAME']) && basename($_SERVER['ORIG_SCRIPT_NAME'])===$scriptName)
			self::$__scriptUrl=$_SERVER['ORIG_SCRIPT_NAME'];
		elseif(($pos=strpos($_SERVER['PHP_SELF'],'/'.$scriptName))!==false)
			self::$__scriptUrl=substr($_SERVER['SCRIPT_NAME'],0,$pos).'/'.$scriptName;
		elseif(isset($_SERVER['DOCUMENT_ROOT']) && strpos($_SERVER['SCRIPT_FILENAME'],$_SERVER['DOCUMENT_ROOT'])===0)
			self::$__scriptUrl=str_replace('\\','/',str_replace($_SERVER['DOCUMENT_ROOT'],'',$_SERVER['SCRIPT_FILENAME']));
		else
			self::$__scriptUrl = '';
		return self::$__scriptUrl;
	}

	/**
	 * 获取站点路径
	 * @return string 站点路径
	 */
	static public function getBaseUrl()
	{
		if(self::$__baseUrl !== null) return self::$__baseUrl;
		self::$__baseUrl = rtrim(dirname(SP::getScriptUrl()),'\\/');
		return self::$__baseUrl;
	}

	/**
	 * 初始化方法
	 * @return SP 自身实例
	 */
	static public function init()
	{
		if(IS_CLI)
			$c = require APP_ROOT.DS.'protected'.DS.'config'.DS.'cli.php';
		else
			$c = require APP_ROOT.DS.'protected'.DS.'config'.DS.'web.php';
		return new SP($c);
	}

	/**
	 * 启动器
	 */
	function run()
	{
		try {
			if(!IS_CLI)
				ob_start();
			$this->__hook->onBeforeRequest();
			// 处理请求
			$request = new Request();
			$this->__hook->onAfterRequest();

			defined('IS_AJAX') or define('IS_AJAX', (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH']==='XMLHttpRequest') || isset($_GET['ajax']));
			self::$__controller = $request->getController() ? : self::$__config['defaultController'];
			self::$__action = $request->getAction() ? : self::$__config['defaultAction'];
			$controllerName = ucfirst(self::$__controller).'Controller';
			$actionName = ucfirst(self::$__action).'Action';
			require APP_ROOT.DS.'protected'.DS.'controller'.DS.'Controller.php';
			if(!@include APP_ROOT.DS.'protected'.DS.'controller'.DS.$controllerName.'.php')
				throw new HttpException("Controller {$controllerName} not found", 0, 404);

			// 创建控制器
			$controller = new $controllerName;
			// 自定义异常处理
			if(method_exists($controller, 'ExceptionHandler'))
				set_exception_handler(array($controller, 'ExceptionHandler'));
			if(method_exists($controller, 'ErrorHandler'))
				set_error_handler(array($controller, 'ErrorHandler'), error_reporting());

			if(!method_exists($controller, $actionName))
				throw new HttpException("Action {$actionName} not found", 0, 404);
			$this->__hook->onBeforeRunController($controller, $actionName);

			// 绑定变量启动控制器
			$method = new ReflectionMethod($controllerName, $actionName);
			$method->invokeArgs($controller, $this->__parseParams($method->getParameters()));

			$this->__hook->onAfterRunController();
			if(!IS_CLI)
				ob_end_flush();
		} catch (HttpException $e) {
			die();
		}
	}

	/**
	 * 获取当前控制器
	 * @return string 控制器
	 */
	static public function getController()
	{
		return self::$__controller;
	}

	/**
	 * 获取当前action
	 * @return string action
	 */
	static public function getAction()
	{
		return self::$__action;
	}

	/**
	 * 获取配置
	 * @param  string $name 配置名
	 * @return mix       配置
	 */
	static public function getConfig($name)
	{
		return self::$__config[$name];
	}

	/**
	 * 解析参数
	 * @param  array $methodParams 参数反射类数组
	 * @return array               参数数组
	 */
	private function __parseParams($methodParams)
	{
		$params = array();
		foreach ($methodParams as $methodParam)
		{
			if(isset($_POST[$methodParam->name]))
				$params[] = $_POST[$methodParam->name];
			elseif(isset($_GET[$methodParam->name]))
				$params[] = $_GET[$methodParam->name];
			elseif($methodParam->isOptional())
				$params[] = $methodParam->getDefaultValue();
			else
				$params[] = null;
		}
		return $params;
	}

	/**
	 * 插件调用魔术方法
	 * @param string 	$pluginName 	插件名
	 * @param array 	$arguments 		参数
	 * @return Object 	返回插件对象
	 */
	static public function __callStatic($pluginName, $arguments = array())
	{
		if(!$pluginName)
			return self::$__plugins;
		if(!isset(self::$__plugins[$pluginName]))
			self::$__plugins[$pluginName] = new $pluginName(@include APP_ROOT.DS.'protected'.DS.'plugin'.DS.$pluginName.DS."$pluginName.config.php");

		if(method_exists(self::$__plugins[$pluginName], 'invoke'))
			call_user_func_array(array(self::$__plugins[$pluginName], 'invoke'), $arguments);

		return self::$__plugins[$pluginName];
	}

	/**
	 * autoLoad方法
	 * @param  string $className 插件名
	 */
	static public function autoLoad($className)
	{
		if(file_exists(SP_ROOT.DS.'plugin'.DS.$className.DS."$className.php"))
			require SP_ROOT.DS.'plugin'.DS.$className.DS."$className.php";
		else if(file_exists(APP_ROOT.DS.'protected'.DS.'plugin'.DS.$className.DS."$className.php"))
			require APP_ROOT.DS.'protected'.DS.'plugin'.DS.$className.DS."$className.php";
		elseif(file_exists(APP_ROOT.DS.'protected'.DS.'model'.DS."$className.php"))
			require APP_ROOT.DS.'protected'.DS.'model'.DS."$className.php";
		else
			throw new Exception("'{$className}' not found");
	}

	/**
	 * 获取应用名
	 * @return string 应用名
	 */
	static public function getAppName()
	{
		return self::$__appName;
	}

	/**
	 * 跳转
	 * @param string $url 地址
	 */
	static public function redirect($url)
	{
		if(!IS_AJAX)
			header('Location: '.$url);
		die();
	}

	/**
	 * 获取静态文件url
	 * @param  string $path 路径
	 * @return string       url
	 */
	static public function staticFile($path = '')
	{
		return self::getBaseUrl()."/{$path}";
	}
}
