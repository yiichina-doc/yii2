<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\validators;

/**
 * InlineValidator 表示一个验证器 它被定义为待验证的对象中的一个方法。
 *
 * 验证方法必须按照以下规则:
 *
 * ~~~
 * function foo($attribute, $params)
 * ~~~
 *
 * `$attribute` 表示需要验证的属性名，`$params`
 * 是一个表示验证规则支持的附加参数的数组。
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class InlineValidator extends Validator
{
	/**
	 * @var string|\Closure an anonymous function or the name of a model class method that will be
	 * called to perform the actual validation. The signature of the method should be like the following:
	 *
	 * ~~~
	 * function foo($attribute, $params)
	 * ~~~
	 */
	public $method;
	/**
	 * @var array 该方法的其他参数被传递给验证方法
	 */
	public $params;
	/**
	 * @var string|\Closure an anonymous function or the name of a model class method that returns the client validation code.
	 * The signature of the method should be like the following:
	 *
	 * ~~~
	 * function foo($attribute, $params)
	 * {
	 *     return "javascript";
	 * }
	 * ~~~
	 *
	 * `$attribute`指的是验证过的属性名称。
	 *
	 * 请参阅[[clientValidateAttribute()]]了解如何返回客户端验证代码的详细信息。
	 */
	public $clientValidate;

	/**
	 * @inheritdoc
	 */
	public function validateAttribute($object, $attribute)
	{
		$method = $this->method;
		if (is_string($method)) {
			$method = [$object, $method];
		}
		call_user_func($method, $attribute, $this->params);
	}

	/**
	 * @inheritdoc
	 */
	public function clientValidateAttribute($object, $attribute, $view)
	{
		if ($this->clientValidate !== null) {
			$method = $this->clientValidate;
			if (is_string($method)) {
				$method = [$object, $method];
			}
			return call_user_func($method, $attribute, $this->params);
		} else {
			return null;
		}
	}
}
