<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\validators;

use Yii;
use yii\web\JsExpression;
use yii\helpers\Json;

/**
 * NumberValidator 验证该属性值是一个数字。
 *
 * 这个数字的格式必须和[[integerPattern]] 或者 [[numberPattern]]指定的正则表达式匹配。
 * 可选项，您可以配置[[max]] 和 [[min]]属性
 * 来确保数在一定范围内。
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class NumberValidator extends Validator
{
	/**
	 * @var boolean 属性值是否只可以是整数。默认为false。
	 */
	public $integerOnly = false;
	/**
	 * @var integer|float 上限数。 默认为null，意味着没有上限。
	 */
	public $max;
	/**
	 * @var integer|float 下限数。 默认为null，意味着没有下限。
	 */
	public $min;
	/**
	 * @var string 当用户定义的值大于[[max]]时的错误信息。
	 */
	public $tooBig;
	/**
	 * @var string 当用户定义的值小于[[min]]时的错误信息。
	 */
	public $tooSmall;
	/**
	 * @var string 正则表达式匹配整数。
	 */
	public $integerPattern = '/^\s*[+-]?\d+\s*$/';
	/**
	 * @var string 正则表达式匹配的数字。默认是一个
	 * 与可选的指数部分匹配浮点数的模式(例如 -1.23e-10)。
	 */
	public $numberPattern = '/^\s*[-+]?[0-9]*\.?[0-9]+([eE][-+]?[0-9]+)?\s*$/';


	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();
		if ($this->message === null) {
			$this->message = $this->integerOnly ? Yii::t('yii', '{attribute} must be an integer.')
				: Yii::t('yii', '{attribute} must be a number.');
		}
		if ($this->min !== null && $this->tooSmall === null) {
			$this->tooSmall = Yii::t('yii', '{attribute} must be no less than {min}.');
		}
		if ($this->max !== null && $this->tooBig === null) {
			$this->tooBig = Yii::t('yii', '{attribute} must be no greater than {max}.');
		}
	}

	/**
	 * @inheritdoc
	 */
	public function validateAttribute($object, $attribute)
	{
		$value = $object->$attribute;
		if (is_array($value)) {
			$this->addError($object, $attribute, Yii::t('yii', '{attribute} is invalid.'));
			return;
		}
		$pattern = $this->integerOnly ? $this->integerPattern : $this->numberPattern;
		if (!preg_match($pattern, "$value")) {
			$this->addError($object, $attribute, $this->message);
		}
		if ($this->min !== null && $value < $this->min) {
			$this->addError($object, $attribute, $this->tooSmall, ['min' => $this->min]);
		}
		if ($this->max !== null && $value > $this->max) {
			$this->addError($object, $attribute, $this->tooBig, ['max' => $this->max]);
		}
	}

	/**
	 * @inheritdoc
	 */
	protected function validateValue($value)
	{
		if (is_array($value)) {
			return [Yii::t('yii', '{attribute} is invalid.'), []];
		}
		$pattern = $this->integerOnly ? $this->integerPattern : $this->numberPattern;
		if (!preg_match($pattern, "$value")) {
			return [$this->message, []];
		} elseif ($this->min !== null && $value < $this->min) {
			return [$this->tooSmall, ['min' => $this->min]];
		} elseif ($this->max !== null && $value > $this->max) {
			return [$this->tooBig, ['max' => $this->max]];
		} else {
			return null;
		}
	}

	/**
	 * @inheritdoc
	 */
	public function clientValidateAttribute($object, $attribute, $view)
	{
		$label = $object->getAttributeLabel($attribute);

		$options = [
			'pattern' => new JsExpression($this->integerOnly ? $this->integerPattern : $this->numberPattern),
			'message' => Yii::$app->getI18n()->format($this->message, [
				'attribute' => $label,
			], Yii::$app->language),
		];

		if ($this->min !== null) {
			$options['min'] = $this->min;
			$options['tooSmall'] = Yii::$app->getI18n()->format($this->tooSmall, [
				'attribute' => $label,
				'min' => $this->min,
			], Yii::$app->language);
		}
		if ($this->max !== null) {
			$options['max'] = $this->max;
			$options['tooBig'] = Yii::$app->getI18n()->format($this->tooBig, [
				'attribute' => $label,
				'max' => $this->max,
			], Yii::$app->language);
		}
		if ($this->skipOnEmpty) {
			$options['skipOnEmpty'] = 1;
		}

		ValidationAsset::register($view);
		return 'yii.validation.number(value, messages, ' . Json::encode($options) . ');';
	}
}
