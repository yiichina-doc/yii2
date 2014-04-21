<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\base;

/**
 * ViewRenderer 视图渲染器类的基类.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
abstract class ViewRenderer extends Component
{
	/**
	 * 渲染一个视图文件.
	 *
	 * 每当渲染一个视图时,调用 [[View]] 方法.
	 * 子类必须实现这个方法来呈现给定的视图文件.
	 *
	 * @param View $view 用于渲染的视图对象文件.
	 * @param string $file 视图文件.
	 * @param array $params 参数被传递给视图文件.
	 * @return string 呈现的结果
	 */
	abstract public function render($view, $file, $params);
}
