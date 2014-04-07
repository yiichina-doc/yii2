<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\widgets;

use Yii;
use yii\base\Widget;
use yii\base\Model;
use yii\base\InvalidConfigException;
use yii\helpers\Html;

/**
 * InputWidget 收集用户输入的widgets的基类。
 *
 * 输入widget基类可以与一个数据模型和属性，
 * 或一个名称一个值相关联。若和属性相关联，名称和值
 * 将会自动产生。
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class InputWidget extends Widget
{
	/**
	 * @var Model 此widget基类相关联的数据模型。
	 */
	public $model;
	/**
	 * @var string 此widget基类相关联的模型属性。
	 */
	public $attribute;
	/**
	 * @var string 输入名称。若[[model]] 和 [[attribute]] 未设置，必须设置此选项。
	 */
	public $name;
	/**
	 * @var string 输入值。
	 */
	public $value;
	/**
	 * @var array HTML的input标签属性。
	 */
	public $options = [];


	/**
	 * 初始化widget基类。
	 * 如果重写此方法，确保首先调用父类实现。
	 */
	public function init()
	{
		if (!$this->hasModel() && $this->name === null) {
			throw new InvalidConfigException("Either 'name', or 'model' and 'attribute' properties must be specified.");
		}
		if (!isset($this->options['id'])) {
			$this->options['id'] = $this->hasModel() ? Html::getInputId($this->model, $this->attribute) : $this->getId();
		}
		parent::init();
	}

	/**
	 * @return boolean widget基类是否和数据模型有关。
	 */
	protected function hasModel()
	{
		return $this->model instanceof Model && $this->attribute !== null;
	}
}
