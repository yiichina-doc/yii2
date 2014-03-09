<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\base;

use Yii;

/**
 * Action 是所有控制器动作类的基类。
 *
 * Action 提供了一种方法将一个复杂的控制器分割成更小的单独类文件的动作。
 *
 * 派生类必须实现一个名为 `run()` 方法。当动作被请求时该方法会由控制器调用。
 * `run()` 方法可以有参数，将会根据他们的名字自动填充了用户输入的值。
 * 例如，如果 `run()` 方法声明如下：
 *
 * ~~~
 * public function run($id, $type = 'book') { ... }
 * ~~~
 *
 * 并提供了该动作的参数: `['id' => 1]`。
 * 那么 `run()` 方法将作为 `run(1)` 被自动调用。
 *
 * @property string $uniqueId 整个应用程序中这个动作的唯一ID。
 * 此属性为只读。
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class Action extends Component
{
	/**
	 * @var string 动作的ID
	 */
	public $id;
	/**
	 * @var Controller 拥有这个动作的控制器
	 */
	public $controller;

	/**
	 * 构造方法。
	 * @param string $id 此动作的ID
	 * @param Controller $controller 拥有这个动作的控制器
	 * @param array $config 将用于初始化该对象属性的name-value对
	 */
	public function __construct($id, $controller, $config = [])
	{
		$this->id = $id;
		$this->controller = $controller;
		parent::__construct($config);
	}

	/**
	 * 返回整个应用程序中这个动作的唯一ID。
	 * @return string 这个动作在整个应用程序中的唯一ID。
	 */
	public function getUniqueId()
	{
		return $this->controller->getUniqueId() . '/' . $this->id;
	}

	/**
	 * 使用指定的参数执行此动作。
	 * 此方法是主要由控制器调用的。
	 * @param array $params 该参数被绑定到动作的 run() 方法。
	 * @return mixed 动作的结果
	 * @throws InvalidConfigException 如果动作类没有 run() 方法
	 */
	public function runWithParams($params)
	{
		if (!method_exists($this, 'run')) {
			throw new InvalidConfigException(get_class($this) . ' must define a "run()" method.');
		}
		$args = $this->controller->bindActionParams($this, $params);
		Yii::trace('Running action: ' . get_class($this) . '::run()', __METHOD__);
		if (Yii::$app->requestedParams === null) {
			Yii::$app->requestedParams = $args;
		}
		if ($this->beforeRun()) {
			$result = call_user_func_array([$this, 'run'], $args);
			$this->afterRun();
			return $result;
		} else {
			return null;
		}
	}

	/**
	 * 这个方法是 `run()` 被执行前调用的。
	 * 你可以为动作执行重写此方法做准备工作。
	 * 如果此方法返回 false, 将会取消这个动作。
	 * @return boolean 是否执行这个动作。
	 */
	protected function beforeRun()
	{
		return true;
	}

	/**
	 * 这个方法是 `run()` 被执行后调用的。
	 * 你可以为动作执行重写此方法做 post-processing 工作。
	 */
	protected function afterRun()
	{
	}
}
