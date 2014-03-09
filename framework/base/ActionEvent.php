<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\base;

/**
 * ActionEvent 表示用于一个动作事件的事件参数。
 *
 * 通过设置 [[isValid]] 属性，可以控制是否继续执行该操作。
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class ActionEvent extends Event
{
	/**
	 * @var Action 当前正在执行的动作
	 */
	public $action;
	/**
	 * @var mixed 操作结果。事件处理程序可以修改此属性去更改操作结果。
	 */
	public $result;
	/**
	 * @var boolean 是否继续执行该操作。
	 * 事件处理程序 [[Controller::EVENT_BEFORE_ACTION]] 可以设置这个属性来决定是否继续执行当前操作。
	 */
	public $isValid = true;

	/**
	 * 构造方法。
	 * @param Action $action 与此动作事件相关的操作。
	 * @param array $config name-value 对将用于初始化该对象属性
	 */
	public function __construct($action, $config = [])
	{
		$this->action = $action;
		parent::__construct($config);
	}
}
