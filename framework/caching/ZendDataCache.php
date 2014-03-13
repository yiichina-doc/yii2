<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\caching;

/**
 * ZendDataCache 提供Zend Data Cache的 应用组件
 *
 * 使用这个应用组件, 必须加载 [Zend Data Cache PHP extension](http://www.zend.com/en/products/server/)
 *
 * See [[Cache]] for common cache operations that ZendDataCache supports.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class ZendDataCache extends Cache
{
	/**
	 * 从缓存中获取指定的键的值.
	 * 这是在父类中定义的方法的具体实现.
	 * @param string $key 一个缓存中唯一的键名
	 * @return string|boolean 缓存中存储的值，如果该值不存在或者已过期则返回false.	
	 */
	protected function getValue($key)
	{
		$result = zend_shm_cache_fetch($key);
		return $result === null ? false : $result;
	}

	/**
	 * 更新缓存中已存在键的值.
	 * 这是在父类中定义的方法的具体实现.
	 *
	 * @param string $key 要更新的键，会检查缓存中是否已有此键
	 * @param string $value 要缓存的值
	 * @param integer $expire 缓存过期时间，以秒为单位. 0 代表永不过期.
	 * @return boolean 设置成功返回true,失败返回false
	 */
	protected function setValue($key, $value, $expire)
	{
		return zend_shm_cache_store($key, $value, $expire);
	}

	/**
	 * 往缓存中新添加一对键值，当新添加的键不存在于缓存中执行.
	 * 这是在父类中定义的方法的具体实现.
	 *
	 * @param string $key 要添加的键，会检查缓存中是否已有此键
	 * @param string $value 要缓存的值
	 * @param integer $expire 缓存过期时间，以秒为单位. 0 代表永不过期.
	 * @return boolean 添加成功返回true,失败返回false
	 */
	protected function addValue($key, $value, $expire)
	{
		return zend_shm_cache_fetch($key) === null ? $this->setValue($key, $value, $expire) : false;
	}

	/**
	 * 从缓存中删除指定键的值
	 * 这是父类中定义的方法的具体实现.
	 * @param string $key 要删除值的键值
	 * @return boolean 如果没有错误产生就会执行删除
	 */
	protected function deleteValue($key)
	{
		return zend_shm_cache_delete($key);
	}

	/**
	 * 清空缓存.
	 * 这是父类中定义的方法的具体实现.
	 * @return boolean 清空是否成功.
	 */
	protected function flushValues()
	{
		return zend_shm_cache_clear();
	}
}
