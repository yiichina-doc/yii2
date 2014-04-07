<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\validators;

use Yii;
use yii\base\Component;
use yii\base\NotSupportedException;

/**
 * Validator 是所有验证程序的基类。
 *
 * 子类应该重写[[validateValue()]]和/或[[validateAttribute()]]的方法
 * 来提供执行数据验证的实际逻辑。子类还可以重写[[clientValidateAttribute()]]
 * 以提供客户端验证支持。
 *
 * Validator 声明一组可以使用短名称的
 * [[builtInValidators|built-in validators]。 列表如下：
 *
 * - `boolean`: [[BooleanValidator]]
 * - `captcha`: [[\yii\captcha\CaptchaValidator]]
 * - `compare`: [[CompareValidator]]
 * - `date`: [[DateValidator]]
 * - `default`: [[DefaultValueValidator]]
 * - `double`: [[NumberValidator]]
 * - `email`: [[EmailValidator]]
 * - `exist`: [[ExistValidator]]
 * - `file`: [[FileValidator]]
 * - `filter`: [[FilterValidator]]
 * - `image`: [[ImageValidator]]
 * - `in`: [[RangeValidator]]
 * - `integer`: [[NumberValidator]]
 * - `match`: [[RegularExpressionValidator]]
 * - `required`: [[RequiredValidator]]
 * - `safe`: [[SafeValidator]]
 * - `string`: [[StringValidator]]
 * - `trim`: [[FilterValidator]]
 * - `unique`: [[UniqueValidator]]
 * - `url`: [[UrlValidator]]
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class Validator extends Component
{
	/**
	 * @var array 内置的validators列表 (name => class or configuration)
	 */
	public static $builtInValidators = [
		'boolean' => 'yii\validators\BooleanValidator',
		'captcha' => 'yii\captcha\CaptchaValidator',
		'compare' => 'yii\validators\CompareValidator',
		'date' => 'yii\validators\DateValidator',
		'default' => 'yii\validators\DefaultValueValidator',
		'double' => 'yii\validators\NumberValidator',
		'email' => 'yii\validators\EmailValidator',
		'exist' => 'yii\validators\ExistValidator',
		'file' => 'yii\validators\FileValidator',
		'filter' => 'yii\validators\FilterValidator',
		'image' => 'yii\validators\ImageValidator',
		'in' => 'yii\validators\RangeValidator',
		'integer' => [
			'class' => 'yii\validators\NumberValidator',
			'integerOnly' => true,
		],
		'match' => 'yii\validators\RegularExpressionValidator',
		'number' => 'yii\validators\NumberValidator',
		'required' => 'yii\validators\RequiredValidator',
		'safe' => 'yii\validators\SafeValidator',
		'string' => 'yii\validators\StringValidator',
		'trim' => [
			'class' => 'yii\validators\FilterValidator',
			'filter' => 'trim',
		],
		'unique' => 'yii\validators\UniqueValidator',
		'url' => 'yii\validators\UrlValidator',
	];

	/**
	 * @var array|string 通过validator验证此属性。对于多个属性，
	 * 请指定它们为一个数组; 对于单个属性，你可以使用一个字符串或一个数组。
	 */
	public $attributes = [];
	/**
	 * @var string 用户定义的错误消息。它可能包含以下占位符
	 * 将相应的validator来代替：
	 *
	 * - `{attribute}`: 被验证的属性标签
	 * - `{value}`: 被验证的属性的值
	 */
	public $message;
	/**
	 * @var array|string 此validator可以应用到脚本。例如脚本，
	 * 请指定它们为一个数组; 对于单个脚本，你可以使用一个字符串或一个数组.
	 */
	public $on = [];
	/**
	 * @var array|string 该验证程序不应该被应用到的脚本。对于多个，
	 * 请指定它们为一个数组; 对于单个脚本，你可以使用一个字符串或一个数组。
	 */
	public $except = [];
	/**
	 * @var boolean 当这个被校验的特性已经在之前的规则（判断）中发生了错误
	 * 是否跳过这个验证规则。默认设置为true。
	 */
	public $skipOnError = true;
	/**
	 * @var boolean 如果属性值为null或empty
	 * 这个验证规则要跳过。
	 */
	public $skipOnEmpty = true;
	/**
	 * @var boolean whether 是否启用客户端验证这个validator。
	 * 实际的客户端验证是通过[[clientValidateAttribute()]]返回的
	 * JavaScript代码。如果该方法返回null，即使
	 * 此属性为true，无客户端验证将通过此验证程序来完成。
	 */
	public $enableClientValidation = true;


	/**
	 * 创建一个validator对象。
	 * @param mixed $type validator类型。这可以是一个内置的validator名称，
	 * 模型类的方法名，一个匿名函数，或一个validator类名称。
	 * @param \yii\base\Model $object 数据对象进行验证。
	 * @param array|string $attributes 属性列表进行验证。可以是属性名数组
	 * 或用逗号分隔的属性名称的字符串。
	 * @param array $params 初始值被应用到validator属性中
	 * @return Validator the validator
	 */
	public static function createValidator($type, $object, $attributes, $params = [])
	{
		$params['attributes'] = $attributes;

		if ($type instanceof \Closure || $object->hasMethod($type)) {
			// method-based validator
			$params['class'] = __NAMESPACE__ . '\InlineValidator';
			$params['method'] = $type;
		} else {
			if (isset(static::$builtInValidators[$type])) {
				$type = static::$builtInValidators[$type];
			}
			if (is_array($type)) {
				foreach ($type as $name => $value) {
					$params[$name] = $value;
				}
			} else {
				$params['class'] = $type;
			}
		}

		return Yii::createObject($params);
	}

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();
		$this->attributes = (array)$this->attributes;
		$this->on = (array)$this->on;
		$this->except = (array)$this->except;
	}

	/**
	 * 验证指定的对象。
	 * @param \yii\base\Model $object 数据对象被验证
	 * @param array|null $attributes 被验证的属性列表。
	 * 请注意如果一个属性与validator无关，
	 * 它将被忽略。
	 * 如果此参数为null，在[[attributes]]中列出的每个属性将被验证。
	 */
	public function validateAttributes($object, $attributes = null)
	{
		if (is_array($attributes)) {
			$attributes = array_intersect($this->attributes, $attributes);
		} else {
			$attributes = $this->attributes;
		}
		foreach ($attributes as $attribute) {
			$skip = $this->skipOnError && $object->hasErrors($attribute)
				|| $this->skipOnEmpty && $this->isEmpty($object->$attribute);
			if (!$skip) {
				$this->validateAttribute($object, $attribute);
			}
		}
	}

	/**
	 * 验证一个属性。
	 * 子类必须实现这个方法来提供实际的验证逻辑。
	 * @param \yii\base\Model $object 数据对象进行验证
	 * @param string $attribute 要验证的属性的名称。
	 */
	public function validateAttribute($object, $attribute)
	{
		$result = $this->validateValue($object->$attribute);
		if (!empty($result)) {
			$this->addError($object, $attribute, $result[0], $result[1]);
		}
	}

	/**
	 * 验证给定值。
	 * 你可以用这个方法来验证脱离一个数据模型的上下文的值。
	 * @param mixed $value 对数据值进行验证。
	 * @param string $error 要返回的错误消息，如果验证失败。
	 * @return boolean 数据是否有效。
	 */
	public function validate($value, &$error = null)
	{
		$result = $this->validateValue($value);
		if (empty($result)) {
			return true;
		} else {
			list($message, $params) = $result;
			$params['attribute'] = Yii::t('yii', 'the input value');
			$params['value'] = is_array($value) ? 'array()' : $value;
			$error = Yii::$app->getI18n()->format($message, $params, Yii::$app->language);
			return false;
		}
	}

	/**
	 * 验证值。
	 * 一个validator类可以实现此方法来支持脱离数据模型的数据验证。
	 * @param mixed $value 对数据值进行验证。
	 * @return array|null 参数被插入时的错误消息。
	 * 如果该数据是有效的则应返回Null。
	 * @throws NotSupportedException 若此validator不支持无模型的数据验证
	 */
	protected function validateValue($value)
	{
		throw new NotSupportedException(get_class($this) . ' does not support validateValue().');
	}

	/**
	 * 返回JavaScript执行的客户端验证。
	 *
	 * 如果validation支持客户端验证
	 * 你可以重写此方法来返回JavaScript验证码。
	 *
	 * 下面的JavaScript变量是预定义的，并且可以被用作验证码：
	 *
	 * - `attribute`: 被验证的属性名称。
	 * - `value`: 被验证的值。
	 * - `messages`: 用于保存该验证错误信息属性的数组。
	 *
	 * @param \yii\base\Model $object 被验证的数据对象。
	 * @param string $attribute 被验证的属性的名称。
	 * @param \yii\web\View $view 视图对象它被用来显示视图或者包含了
	 * 一个应用了这个验证器的表单的视图文件。
	 * @return string 在客户端验证脚本。如果不支持证
	 * 客户端验。
	 * @see \yii\widgets\ActiveForm::enableClientValidation
	 */
	public function clientValidateAttribute($object, $attribute, $view)
	{
		return null;
	}

	/**
	 * 返回一个值，表明 这个验证器在给定的场景和特性下 是否有效。
	 *
	 * A validator is active if
	 *
	 * - the validator's `on` property is empty, or
	 * - the validator's `on` property contains the specified scenario
	 *
	 * @param string $scenario 脚本名
	 * @return boolean validator是否应用到指定的脚本。
	 */
	public function isActive($scenario)
	{
		return !in_array($scenario, $this->except, true) && (empty($this->on) || in_array($scenario, $this->on, true));
	}

	/**
	 * 关于增加指定的属性到模型对象的错误。
	 * 这是执行消息选择和国际化的辅助方法。
	 * @param \yii\base\Model $object 被验证的数据对象
	 * @param string $attribute 正在被验证的属性
	 * @param string $message 错误消息
	 * @param array $params 在错误消息中的占位符的值
	 */
	public function addError($object, $attribute, $message, $params = [])
	{
		$value = $object->$attribute;
		$params['attribute'] = $object->getAttributeLabel($attribute);
		$params['value'] = is_array($value) ? 'array()' : $value;
		$object->addError($attribute, Yii::$app->getI18n()->format($message, $params, Yii::$app->language));
	}

	/**
	 * 检查给定的值是否为empty。
	 * 若该值为null被认为是empty，一个空数组，或修整的结果是一个空字符串。
	 * 请注意，此方法不同于PHP的empty()函数。当值为0时返回false。
	 * @param mixed $value 要检查的值
	 * @param boolean $trim 如果字符串为empty是否要执行此操作。默认为false。
	 * @return boolean 值是否为empty
	 */
	public function isEmpty($value, $trim = false)
	{
		return $value === null || $value === [] || $value === ''
			|| $trim && is_scalar($value) && trim($value) === '';
	}
}
