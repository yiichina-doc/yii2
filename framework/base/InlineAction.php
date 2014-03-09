<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\base;

use Yii;

/**
 * InlineAction 表示被定义为控制器的方法的动作。
 *
 * 控制器的方法名通过 [[actionMethod]] 是由 [[controller]] 生成的这个动作设置是可用的。
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class InlineAction extends Action
{
	/**
	 * @var string 内联动作相关联的控制器的方法
	 */
	public $actionMethod;

	/**
	 * @param string $id 此动作的ID
	 * @param Controller $controller 拥有此动作的控制器
	 * @param string $actionMethod 内联动作相关联的控制器的方法
	 * @param array $config name-value 对将用于初始化对象属性
	 */
	public function __construct($id, $controller, $actionMethod, $config = [])
	{
		$this->actionMethod = $actionMethod;
		parent::__construct($id, $controller, $config);
	}

	/**
	 * 使用指定的参数执行此动作。
	 * 这个方法主要是由控制器调用的。
	 * @param array $params 动作参数
	 * @return mixed 该动作的结果
	 */
	public function runWithParams($params)
	{
		$args = $this->controller->bindActionParams($this, $params);
		Yii::trace('Running action: ' . get_class($this->controller) . '::' . $this->actionMethod . '()', __METHOD__);
		if (Yii::$app->requestedParams === null) {
			Yii::$app->requestedParams = $args;
		}
		return call_user_func_array([$this->controller, $this->actionMethod], $args);
	}
}
