<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\base;

/**
 * NotSupportedException 表示由访问不支持的功能异常。
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class NotSupportedException extends Exception
{
	/**
	 * @return string 这个异常友好的名称
	 */
	public function getName()
	{
		return 'Not Supported';
	}
}
