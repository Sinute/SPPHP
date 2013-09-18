<?php
/**
* Http异常
*/
class HttpException extends Exception
{
	public static $statusCode = array(
		200	=> 'OK',
		201	=> 'Created',
		202	=> 'Accepted',
		203	=> 'Non-Authoritative Information',
		204	=> 'No Content',
		205	=> 'Reset Content',
		206	=> 'Partial Content',

		300	=> 'Multiple Choices',
		301	=> 'Moved Permanently',
		302	=> 'Found',
		304	=> 'Not Modified',
		305	=> 'Use Proxy',
		307	=> 'Temporary Redirect',

		400	=> 'Bad Request',
		401	=> 'Unauthorized',
		403	=> 'Forbidden',
		404	=> 'Not Found',
		405	=> 'Method Not Allowed',
		406	=> 'Not Acceptable',
		407	=> 'Proxy Authentication Required',
		408	=> 'Request Timeout',
		409	=> 'Conflict',
		410	=> 'Gone',
		411	=> 'Length Required',
		412	=> 'Precondition Failed',
		413	=> 'Request Entity Too Large',
		414	=> 'Request-URI Too Long',
		415	=> 'Unsupported Media Type',
		416	=> 'Requested Range Not Satisfiable',
		417	=> 'Expectation Failed',

		500	=> 'Internal Server Error',
		501	=> 'Not Implemented',
		502	=> 'Bad Gateway',
		503	=> 'Service Unavailable',
		504	=> 'Gateway Timeout',
		505	=> 'HTTP Version Not Supported'
	);
	private $__status;

	/**
	 * 构造函数
	 * @param string  $message 异常信息
	 * @param integer $code    错误码
	 * @param integer $status  状态码
	 */
	public function __construct($message=null, $code=0, $status=400)
	{
		$this->__status = $status;
		$statusText = self::$statusCode[$status];
		header( "HTTP/1.1 {$status} {$statusText}" );
		if((isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH']==='XMLHttpRequest') || $_GET['ajax'])
		{
			header('Content-Type: application/json');
			parent::__construct($message,$code);
			$request = rtrim($_SERVER['QUERY_STRING'] ? substr($_SERVER['REQUEST_URI'], 0, strpos($_SERVER['REQUEST_URI'], "?{$_SERVER['QUERY_STRING']}")) : $_SERVER['REQUEST_URI'], '/');
			echo json_encode(array('msg' => $message, 'code' => $code, 'method' => $_SERVER['REQUEST_METHOD'], 'request' => $request));
		}
		else
		{
			$message = htmlspecialchars($message);
			parent::__construct($message,$code);
			if (file_exists(SP_ROOT.DS.'view'.DS.'httpStatus'.DS."$status.php"))
				include SP_ROOT.DS.'view'.DS.'httpStatus'.DS."$status.php";
			elseif (file_exists(APP_ROOT.DS.'protected'.DS.'view'.DS.'httpStatus'.DS."$status.php"))
				include APP_ROOT.DS.'protected'.DS.'view'.DS.'httpStatus'.DS."$status.php";
			else
				echo $message;
			// TODO default HttpException page
		}
	}

	/**
	 * 获取状态码
	 * @return integer 状态码
	 */
	public function getStatus()
	{
		return $this->__status;
	}
}
