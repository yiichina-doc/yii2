<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\base;

/**
 * InvalidRouteException 表示由一个无效的路径导致的异常
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class InvalidRouteException extends UserException
{
	/**
	 * @return string 这个异常友好的名称
	 */
	public function getName()
	{
		return 'Invalid Route';
	}
}
