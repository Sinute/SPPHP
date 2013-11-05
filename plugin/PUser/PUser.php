<?php
/**
* PUser
*/
class PUser extends Plugin
{
	private $__config;
	private $__algo;
	private $__isGuest;
	private $__cookiePath;
	private $__cookieDomain;
	private $__cookieSecure;
	private $__cookieHttpOnly;

	/**
	 * 构造函数, 读取配置初始化
	 * @param array $config 配置数组
	 */
	function __construct($config)
	{
		$this->__algo = 'sha1';
		$this->__isGuest = true;
		$this->__config = $config;
		$this->__config['authKey'] = $this->__config['authKey'] ? : (SP::PCache()->get(SP::getAppName().'authKey') ? : $this->__generateAuthKey(32));
		$this->__cookiePath = $this->__config['cookiePath'] ? : '/';
		$this->__cookieDomain = $this->__config['cookieDomain'] ? : '';
		$this->__cookieSecure = $this->__config['cookieSecure'] ? : false;
		$this->__cookieHttpOnly = $this->__config['cookieHttpOnly'] ? : true;
		if(isset($_SESSION['PUSER']))
		{
			$this->__isGuest = false;
			return;
		}
		if(isset($_COOKIE['PUSER']))
			$this->__validateUser($_COOKIE['PUSER']);
	}

	/**
	 * 登入
	 * @param  array  $userInfo 用户信息数组
	 * @param  integer $expire   过期时间
	 */
	public function signin($userInfo, $expire = 0)
	{
		$_SESSION['PUSER'] = $userInfo;
		if($expire > 0)
		{
			$sUserInfo = serialize($userInfo);
			setcookie('PUSER', hash_hmac($this->__algo, $sUserInfo, $this->__config['authKey']).$sUserInfo, time() + $expire, $this->__cookiePath, $this->__cookieDomain, $this->__cookieSecure, $this->__cookieHttpOnly);
		}
		$this->__isGuest = false;
	}

	/**
	 * 修改登入信息
	 * @param  array $userInfo 用户信息数组
	 */
	public function changeInfo($userInfo)
	{
		$_SESSION['PUSER'] = array_merge($_SESSION['PUSER'], $userInfo);
		$this->__unsetCookie();
	}

	/**
	 * 登出
	 */
	public function signout()
	{
		$this->__unsetCookie();
		$this->__unsetSession();
		if(isset($this->__config['returnUrl'])) SP::redirect($this->__config['returnUrl']);
	}

	/**
	 * 删除cookie
	 */
	private function __unsetCookie()
	{
		setcookie('PUSER', false, 0, $this->__cookiePath, $this->__cookieDomain, $this->__cookieSecure, $this->__cookieHttpOnly);
	}

	/**
	 * 删除session
	 */
	private function __unsetSession()
	{
		session_unset();
		session_destroy();
	}

	/**
	 * 是否为游客
	 * @return boolean 未登入用户为true/其他为false
	 */
	public function isGuest()
	{
		return $this->__isGuest;
	}

	/**
	 * 验证用户
	 * @param  string $info 用户信息
	 * @return boolean      验证有效返回true/无效返回false
	 */
	private function __validateUser($info)
	{
		$pos = strlen(hash_hmac($this->__algo, 'test', 'test'));
		$str = substr($info, 0, $pos);
		$data = substr($info, $pos);
		if($str == hash_hmac($this->__algo, $data, $this->__config['authKey']))
		{
			$this->signin(unserialize($data));
			return true;
		}
		return false;
	}

	/**
	 * 生成认证密钥
	 * @param  integer $len 密钥长度
	 * @return string      	密钥
	 */
	private function __generateAuthKey($len)
	{
		$authKey = '';
		for ($i=0; $i < $len; $i++) {
			$authKey .= chr(rand(32, 126));
		}
		SP::PCache()->set(SP::getAppName().'authKey', $authKey);
		return $authKey;
	}

	public function onBeforeRunController($controller, $action)
	{
		// 方法不存在直接跳过
		if(!method_exists($controller, 'accessRules')) return;
		$accessRules = $controller->accessRules();
		foreach ($accessRules as $accessRule)
		{
			// 不是当前action, 跳过
			if(isset($accessRule['action']) && $action != $accessRule['action']) continue;
			if(isset($accessRule['method']) && strtolower($_SERVER['REQUEST_METHOD']) != strtolower($accessRule['method'])) break;
			$callBack = $accessRule['rule'];
			if($callBack === true) return;
			if($callBack !== false && call_user_func($callBack)) return;
			break;
		}
		if(isset($this->__config['returnUrl'])) SP::redirect($this->__config['returnUrl']);
		// 默认为权限不足
		throw new HttpException("Unauthorized", 0, 401);
	}

	/**
	 * 获取用户属性
	 * @param  string $name 属性名
	 * @return mix       	属性值
	 */
	public function __get($name)
	{
		return $_SESSION['PUSER'][$name];
	}

	/**
	 * 保持属性只读, 需要修改用户属性请使用signin重新登入
	 * @param string $name  属性名
	 * @param mix $value 属性值
	 */
	public function __set($name, $value)
	{
		throw new Exception("Use SP::PUser()->signin() to set value");
	}
}
