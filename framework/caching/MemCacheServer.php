<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\caching;

/**
 * MemCacheServer作为memcache或者memcached server的配置.
 *
 * See [PHP manual](http://www.php.net/manual/en/function.Memcache-addServer.php) for detailed explanation
 * of each configuration property.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class MemCacheServer extends \yii\base\Object
{
	/**
	 * @var string 缓存服务器的主机名或IP地址
	 */
	public $host;
	/**
	 * @var integer 缓存服务器的端口
	 */
	public $port = 11211;
	/**
	 * @var integer 在所有服务器中使用这台服务器概率.
	 */
	public $weight = 1;
	/**
	 * @var boolean 是否使用长连接. 只在memcache使用.
	 */
	public $persistent = true;
	/**
	 * @var integer 服务连接以毫秒为单位.
	 * 如果使用memcache,这个参数只对持指定的旧版本的memcache有效
	 * 以秒为单位时将参数将被进位成秒
	 */
	public $timeout = 1000;
	/**
	 * @var integer 重连失败服务器的频率（以秒为单位）. 只在memcache使用.
	 */
	public $retryInterval = 15;
	/**
	 * @var boolean 线上服务发生错误时进行标记. 只在memcache使用.
	 */
	public $status = true;
	/**
	 * @var \Closure this callback function will run upon encountering an error.
	 * 这个回调方法执行前会进行故障处理的尝试. 这个方法接收两个参数,
	 * host[[host]]和端口[[port]]配置错误.
	 * 只在memcache使用.
	 */
	public $failureCallback;
}
