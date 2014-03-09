<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\base;

/**
 * Extension 是可以由单个扩展延伸的基类。
 *
 * Extension 提供了 bootstrap 类的扩展。
 * 当一个扩展通过composer安装后，此扩展类的 [[init()]] 方法 (如果有的话) 将在应用程序初始化阶段被调用。
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class Extension
{
	/**
	 * 初始化扩展。
	 * 这个方法在 [[Application::init()]] 结束时调用。
	 */
	public static function init()
	{
	}
}
