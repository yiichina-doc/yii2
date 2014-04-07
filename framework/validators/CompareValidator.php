<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\validators;

use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\Html;

/**
 * CompareValidator 指定的属性值与另一个值进行比较，并验证它们是否相等。
 *
 * 两个属性值进行比较
 * (通过指定[[compareAttribute]])或一个常量(通过指定
 * [[compareValue]]。当两个都被指定， 后者
 * 优先。 如果不指定，此属性将与另一个以
 * "_repeat"结尾的
 * 源属性名进行比较。
 *
 * CompareValidator 支持不同的比较操作符， 通过
 * 指定[[operator]]属性。
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class CompareValidator extends Validator
{
	/**
	 * @var string 要比较的属性的名称。当已经设置此属性
	 * 和[[compareValue]]，后者优先。 如果都未设置，
	 * 假设要比较的两个属性字前半部分相同
	 * 后半部分以'_repeat'结尾的属性。例如，如果'password'
	 * 正在验证，然后要比较的属性将是'password_repeat'。
	 * @see compareValue
	 */
	public $compareAttribute;
	/**
	 * @var mixed 要比较的常数值. 当已经设置此属性和
	 * [[compareAttribute]]， 此属性优先。
	 * @see compareAttribute
	 */
	public $compareValue;
	/**
	 * @var string 比较操作。支持以下操作：
	 *
	 * - '==': 验证两个值相等。非严格的比较模式.
	 * - '===': 验证两个值相等。比较严格的比较模式.
	 * - '!=': 验证两个值不相等. 非严格的比较模式。
	 * - '!==': 验证两个值不相等。 比较严格的比较模式。
	 * - `>`: 被验证的值是否大于所比较的值。
	 * - `>=`: 被验证的值是否大于或等于所比较的值。
	 * - `<`: 被验证的值是否小于所比较的值。
	 * - `<=`: 被验证的值是否小于或等于所比较的值。
	 */
	public $operator = '==';
	/**
	 * @var string 用户定义的错误消息。它可以包含下列的占位符
	 * 验证器会替换这些占位符:
	 *
	 * - `{attribute}`: 被验证的属性标签
	 * - `{value}`: 该属性的值被验证
	 * - `{compareValue}`: 要比较的值或属性标签
	 * - `{compareAttribute}`: 被比较的属性标签
	 */
	public $message;


	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();
		if ($this->message === null) {
			switch ($this->operator) {
				case '==':
					$this->message = Yii::t('yii', '{attribute} must be repeated exactly.');
					break;
				case '===':
					$this->message = Yii::t('yii', '{attribute} must be repeated exactly.');
					break;
				case '!=':
					$this->message = Yii::t('yii', '{attribute} must not be equal to "{compareValue}".');
					break;
				case '!==':
					$this->message = Yii::t('yii', '{attribute} must not be equal to "{compareValue}".');
					break;
				case '>':
					$this->message = Yii::t('yii', '{attribute} must be greater than "{compareValue}".');
					break;
				case '>=':
					$this->message = Yii::t('yii', '{attribute} must be greater than or equal to "{compareValue}".');
					break;
				case '<':
					$this->message = Yii::t('yii', '{attribute} must be less than "{compareValue}".');
					break;
				case '<=':
					$this->message = Yii::t('yii', '{attribute} must be less than or equal to "{compareValue}".');
					break;
				default:
					throw new InvalidConfigException("Unknown operator: {$this->operator}");
			}
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
		if ($this->compareValue !== null) {
			$compareLabel = $compareValue = $this->compareValue;
		} else {
			$compareAttribute = $this->compareAttribute === null ? $attribute . '_repeat' : $this->compareAttribute;
			$compareValue = $object->$compareAttribute;
			$compareLabel = $object->getAttributeLabel($compareAttribute);
		}

		if (!$this->compareValues($this->operator, $value, $compareValue)) {
			$this->addError($object, $attribute, $this->message, [
				'compareAttribute' => $compareLabel,
				'compareValue' => $compareValue,
			]);
		}
	}

	/**
	 * @inheritdoc
	 */
	protected function validateValue($value)
	{
		if ($this->compareValue === null) {
			throw new InvalidConfigException('CompareValidator::compareValue must be set.');
		}
		if (!$this->compareValues($this->operator, $value, $this->compareValue)) {
			return [$this->message, [
				'compareAttribute' => $this->compareValue,
				'compareValue' => $this->compareValue,
			]];
		} else {
			return null;
		}
	}

	/**
	 * 通过指定的操作比较两个值。
	 * @param string $operator 比较运算符
	 * @param mixed $value 被比较的值
	 * @param mixed $compareValue 被比较的另一个值
	 * @return boolean 通过指定的操作比较是否为真。
	 */
	protected function compareValues($operator, $value, $compareValue)
	{
		switch ($operator) {
			case '==': return $value == $compareValue;
			case '===': return $value === $compareValue;
			case '!=': return $value != $compareValue;
			case '!==': return $value !== $compareValue;
			case '>': return $value > $compareValue;
			case '>=': return $value >= $compareValue;
			case '<': return $value < $compareValue;
			case '<=': return $value <= $compareValue;
			default: return false;
		}
	}

	/**
	 * @inheritdoc
	 */
	public function clientValidateAttribute($object, $attribute, $view)
	{
		$options = ['operator' => $this->operator];

		if ($this->compareValue !== null) {
			$options['compareValue'] = $this->compareValue;
			$compareValue = $this->compareValue;
		} else {
			$compareAttribute = $this->compareAttribute === null ? $attribute . '_repeat' : $this->compareAttribute;
			$compareValue = $object->getAttributeLabel($compareAttribute);
			$options['compareAttribute'] = Html::getInputId($object, $compareAttribute);
		}

		if ($this->skipOnEmpty) {
			$options['skipOnEmpty'] = 1;
		}

		$options['message'] = Yii::$app->getI18n()->format($this->message, [
			'attribute' => $object->getAttributeLabel($attribute),
			'compareAttribute' => $compareValue,
			'compareValue' => $compareValue,
		], Yii::$app->language);

		ValidationAsset::register($view);
		return 'yii.validation.compare(value, messages, ' . json_encode($options) . ');';
	}
}
