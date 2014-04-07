<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\base;

/**
 * ModelEvent 类。
 *
 * ModelEvent 表需要通过模型事件的参数。
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class ModelEvent extends Event
{
	/**
	 * @var boolean 是否该模型是有效的状态。默认为true。
	 * 模型是在有效的状态，如果它通过验证或某些检查。
	 */
	public $isValid = true;
}
