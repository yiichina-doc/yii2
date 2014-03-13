<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\caching;

/**
 * ApcCache 为应用程序组件提供APC缓存.
 *
 * 要使用这个应用程序组件，PHP的APC扩展必须开启 [APC PHP extension](http://www.php.net/apc).
 * 使用APC 的 CLI 需要在php.ini中添加 "apc.enable_cli = 1".
 *
 * 查看 [[Cache]]操作手册以了解CApcCache支持的常用缓存操作.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class ApcCache extends Cache
{
	/**
	 * 检查一个键在缓存中是否已经存在.
	 * 当缓存中数据特别在时此操作要快与获取.
	 * 此方法不会检查依赖关系 
	 * 缓存的数据发生改变时，get返回false，exits返回true.
	 * @param mixed $key 缓存中值的键. 可以是一个简单的字符串也可以是一个键值的数据结构
	 * @return boolean 存在于缓存中时返回true，如果该值不存在或者已过期则返回false.
	 */
	public function exists($key)
	{
		$key = $this->buildKey($key);
		return apc_exists($key);
	}

	/**
	 * 从缓存中检索一个特定键的值.
	 * T这是在父类中定义方法的具体实现.
	 * @param string $key 要检索的键
	 * @return string|boolean 缓存中存储的值，如果该值不存在或者已过期则返回false.
	 */
	protected function getValue($key)
	{
		return apc_fetch($key);
	}

	/**
	 * 从缓存中检索一组特定键的值.
	 * @param array $keys 要检索的键值列表
	 * @return array 检索到的值
	 */
	protected function getValues($keys)
	{
		return apc_fetch($keys);
	}

	/**
	 * 往缓存中存储一个用键名区分的值.
	 * 这是在父类中定义方法的具体实现.
	 *
	 * @param string $key 用以甄别缓存值的键名
	 * @param string $value 要缓存的值.
	 * @param integer $expire 缓存过期时间，以秒为单位. 0 代表永不过期.
	 * @return boolean 缓存成功返回true，失败返回false
	 */
	protected function setValue($key, $value, $expire)
	{
		return apc_store($key, $value, $expire);
	}

	/**
	 * 往缓存中存储一组用键名区分的值.
	 * @param array $data 数组中的键值与缓存中对应
	 * @param integer $expire 缓存过期时间，以秒为单位. 0 代表永不过期.
	 * @return array 返回缓存失败的键
	 */
	protected function setValues($data, $expire)
	{
		return array_keys(apc_store($data, null, $expire));
	}

	/**
	 * 当添加的值的键在缓存中不存在时，进行缓存.
	 * 这是在父类中定义方法的具体实现.
	 * @param string $key 当键通过检查时进行缓存
	 * @param string $value 要缓存的值
	 * @param integer $expire 缓存过期时间，以秒为单位. 0 代表永不过期.
	 * @return boolean 缓存成功返回true，失败返回false
	 */
	protected function addValue($key, $value, $expire)
	{
		return apc_add($key, $value, $expire);
	}

	/**
	 * 添加多个键值到缓存.
	 * @param array $data 数组中的键值也将是其值在缓存中对应的键值
	 * @param integer $expire 缓存过期时间，以秒为单位. 0 代表永不过期.
	 * @return array 返回缓存失败的键
	 */
	protected function addValues($data, $expire)
	{
		return array_keys(apc_add($data, null, $expire));
	}

	/**
	 * 从缓存中删除指定的键名的值
	 * 这是在父类中定义方法的具体实现.
	 * @param string $key 要删除值的键名
	 * @return boolean 当删除期间没有错误发生
	 */
	protected function deleteValue($key)
	{
		return apc_delete($key);
	}

	/**
	 * 删除所有缓存值.
	 * 这是在父类中定义方法的具体实现.
	 * @return boolean 如果清空操作成功执行.
	 */
	protected function flushValues()
	{
		if (extension_loaded('apcu')) {
			return apc_clear_cache();
		} else {
			return apc_clear_cache('user');
		}
	}
}
