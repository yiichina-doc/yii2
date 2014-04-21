<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\base;

/**
 * ViewEvent 代表事件引发的 [[View]] 组件.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class ViewEvent extends Event
{
	/**
	 * @var string 渲染结果 [[View::renderFile()]].
	 * 事件处理程序可以修改这个属性,修改后的输出将返回 [[View::renderFile()]].
	 * 这个属性只使用 [[View::EVENT_AFTER_RENDER]] 事件.	
	 */
	public $output;
	/**
	 * @var boolean 是否继续呈现视图文件. 事件处理程序
	 * [[View::EVENT_BEFORE_RENDER]] 可以设置这个属性决定是否继
	 * 续呈现当前视图文件.
	 */
	public $isValid = true;
}
