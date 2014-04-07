<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\base;

use Yii;
use ArrayAccess;
use ArrayObject;
use ArrayIterator;
use ReflectionClass;
use IteratorAggregate;
use yii\helpers\Inflector;
use yii\validators\RequiredValidator;
use yii\validators\Validator;

/**
 * 模型类是数据模型的基类.
 *
 * 模型实现以下常用功能:
 *
 * - 属性声明: 默认情况下, 每一个公共类成员被认为是一个模型属性
 * - 属性标签: 每个属性可以被关联到一个标签显示的目的
 * - 大量的属性赋值
 * - 基于场景的验证
 *
 * 当执行数据验证模型还提出了以下事件:
 *
 * - [[EVENT_BEFORE_VALIDATE]]: 在开始时引发的事件 [[validate()]]
 * - [[EVENT_AFTER_VALIDATE]]: 一个事件引起的 [[validate()]]
 *
 * 您可以直接使用模型来存储模型数据, 或自定义扩展它.
 * 你也可以通过附加自定义模型 [[ModelBehavior|model behaviors]].
 *
 * @property \yii\validators\Validator[] $activeValidators 验证器适用于当前
 * [[scenario]]. 这个属性是只读的.
 * @property array $attributes 属性值 (name => value).
 * @property array $errors 一个数组所有属性的错误. 如果没有错误则返回空数组. 
 * 结果是一个二维数组. 看 [[getErrors()]] 的详细描述. 这个属性是只读的.
 * @property array $firstErrors 第一个错误. 如果没有错误,将返回一个空数组. 这个属性是只读的.
 * @property ArrayIterator $iterator 一个迭代器遍历列表中的项目. 这个属性是只读的.
 * @property string $scenario 这个模型的场景. 默认为 [[SCENARIO_DEFAULT]].
 * @property ArrayObject|\yii\validators\Validator[] $validators 模型中声明的所有验证器.
 * 这个属性是只读的.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class Model extends Component implements IteratorAggregate, ArrayAccess, Arrayable
{
	use ArrayableTrait;

	/**
	 * 默认场景的名称.
	 */
	const SCENARIO_DEFAULT = 'default';
	/**
	 * @event ModelEvent 在开始时引发的事件 [[validate()]]. 您可以设定
	 * [[ModelEvent::isValid]] 为假来停止验证.
	 */
	const EVENT_BEFORE_VALIDATE = 'beforeValidate';
	/**
	 * @event Event 一个事件引起的 [[validate()]]
	 */
	const EVENT_AFTER_VALIDATE = 'afterValidate';

	/**
	 * @var array 验证错误 (属性名称 => 错误)
	 */
	private $_errors;
	/**
	 * @var ArrayObject 验证器列表
	 */
	private $_validators;
	/**
	 * @var string 当前场景
	 */
	private $_scenario = self::SCENARIO_DEFAULT;

	/**
	 * 返回属性的验证规则.
	 *
	 * 如果属性值是有效的验证规则使用 [[validate()]].
	 * 子类可以重写此方法声明不同的验证规则.
	 *
	 * 每个规则是一个数组使用以下结构:
	 *
	 * ~~~
	 * [
	 *     ['attribute1', 'attribute2'],
	 *     'validator type',
	 *     'on' => ['scenario1', 'scenario2'],
	 *     ...other parameters...
	 * ]
	 * ~~~
	 *
	 * where
	 *
	 *  - 属性列表: required, 指定的属性数组来进行验证, 对于单属性可以通过字符串;
	 *  - 验证器类型: required, 指定使用的验证器. 它可以是一个内置验证器的名字,
	 *    一个方法模型类的名称, 一个匿名函数, 或验证器类名称.
	 *  - 在: optional, 指定了 [[scenario|scenarios]] 数组时验证规则可以应用. 
	 *    如果没有设置这个选项, 规则将适用于所有场景.
	 *  - 可以指定额外的 名称-值 初始化相应的验证器属性.
	 *    请参考个人为可能的属性确认器类的API.
	 *
	 * 一个验证器可以是一个对象类的扩展 [[Validator]], 或者一个模型类的方法
	 * (被称为 *内联验证器*) 如下:
	 *
	 * ~~~
	 * // $params refers 验证参数的规则
	 * function validatorName($attribute, $params)
	 * ~~~
	 *
	 * 在上面的 `$attribute` 是指目前验证属性名称而 `$params` 包含一组验证器配置选项
	 * 如 `max` 的 `string` 验证器. 目前可以访问验证属性值为 `$this->[$attribute]`.
	 *
	 * Yii还提供了一组 [[Validator::builtInValidators|built-in validators]].
	 * 他们每个人都有一个别名,可以指定一个验证规则时使用.
	 *
	 * 下面是一些例子:
	 *
	 * ~~~
	 * [
	 *     // 内置验证器 "required"
	 *     [['username', 'password'], 'required'],
	 *     // 内置的 "string" 定制验证器 "min" 和 "max" 的特性
	 *     ['username', 'string', 'min' => 3, 'max' => 12],
	 *     // 内置验证器 "compare" 只在 "register" 场景中使用
	 *     ['password', 'compare', 'compareAttribute' => 'password2', 'on' => 'register'],
	 *     // 通过 "authenticate()" 方法在模型类定义内联验证器
	 *     ['password', 'authenticate', 'on' => 'login'],
	 *     // 一个验证器的类 "DateRangeValidator"
	 *     ['dateRange', 'DateRangeValidator'],
	 * ];
	 * ~~~
	 *
	 * 请注意, 为了继承父类中定义的规则, 子类需要和父类规则合并使用功能
	 * 如 `array_merge()`.
	 *
	 * @return array 验证规则
	 * @see scenarios()
	 */
	public function rules()
	{
		return [];
	}

	/**
	 * 返回一个列表的场景和相应的活动属性.
	 * 当前场景中验证一个活跃的属性.
	 * 返回的数组应该按以下格式:
	 *
	 * ~~~
	 * [
	 *     'scenario1' => ['attribute11', 'attribute12', ...],
	 *     'scenario2' => ['attribute21', 'attribute22', ...],
	 *     ...
	 * ]
	 * ~~~
	 *
	 * 默认情况下, 一个积极的属性被认为是安全的可以被大量分配.
	 * 如果属性不应该被大量分配 (因此认为不安全),
	 * 请用一个感叹字符描述属性 (e.g. '!rank').
	 *
	 * 该方法默认实现将返回所有场景中发现的 [[rules()]]
	 * 声明. 一个特殊的场景 [[SCENARIO_DEFAULT]] 将包含所有属性在 [[rules()]].
	 * 适用于每个场景将与属性相关的验证规则.
	 *
	 * @return array 一个场景和相应的积极的属性列表.
	 */
	public function scenarios()
	{
		$scenarios = [self::SCENARIO_DEFAULT => []];
		foreach ($this->getValidators() as $validator) {
			foreach ($validator->on as $scenario) {
				$scenarios[$scenario] = [];
			}
			foreach ($validator->except as $scenario) {
				$scenarios[$scenario] = [];
			}
		}
		$names = array_keys($scenarios);

		foreach ($this->getValidators() as $validator) {
			if (empty($validator->on) && empty($validator->except)) {
				foreach ($names as $name) {
					foreach ($validator->attributes as $attribute) {
						$scenarios[$name][$attribute] = true;
					}
				}
			} elseif (empty($validator->on)) {
				foreach ($names as $name) {
					if (!in_array($name, $validator->except, true)) {
						foreach ($validator->attributes as $attribute) {
							$scenarios[$name][$attribute] = true;
						}
					}
				}
			} else {
				foreach ($validator->on as $name) {
					foreach ($validator->attributes as $attribute) {
						$scenarios[$name][$attribute] = true;
					}
				}
			}
		}

		foreach ($scenarios as $scenario => $attributes) {
			if (empty($attributes) && $scenario !== self::SCENARIO_DEFAULT) {
				unset($scenarios[$scenario]);
			} else {
				$scenarios[$scenario] = array_keys($attributes);
			}
		}

		return $scenarios;
	}

	/**
	 * 使用这个模型类返回表单名称.
	 *
	 * 所使用的表单名称主要是 [[\yii\web\ActiveForm]] 来决定如何命名属性模型的输入字段.
	 * 如果表单的名字是 "A" 和一个属性的名字是 "b",
	 * 然后相应的输入名称将是 "A[b]". 如果表单的名字是一个空字符串, 然后输入名称将是 "b".
	 *
	 * 默认情况下, 该方法返回模型类名称 (没有名称空间的一部分)
	 * 表单名称. 当模型中使用不同的形式你可以覆盖它.
	 *
	 * @return string 该模型的类名.
	 */
	public function formName()
	{
		$reflector = new ReflectionClass($this);
		return $reflector->getShortName();
	}

	/**
	 * 返回属性名称的列表.
	 * 默认情况下, 该方法将返回所有的公共类的非静态属性.
	 * 你可以覆盖这个方法来改变默认的行为.
	 * @return array 属性名称列表.
	 */
	public function attributes()
	{
		$class = new ReflectionClass($this);
		$names = [];
		foreach ($class->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
			if (!$property->isStatic()) {
				$names[] = $property->getName();
			}
		}
		return $names;
	}

	/**
	 * 返回属性的标签.
	 *
	 * 属性标签主要用于显示目的. 例如, 给定一个属性
	 * `firstName`, 我们可以声明一个标签 `First Name` 可以更友好的显示给最终用户.
	 *
	 * 在默认情况下 [[generateAttributeLabel()]] 生成一个属性标签使用.
	 * 这种方法允许您显式地指定属性标签.
	 *
	 * 请注意, 为了继承父类中定义的标签, 子类需要合并父与子标签
	 * 标签使用功能,如 `array_merge()`.
	 *
	 * @return array 属性标签 (名字 => 标签)
	 * @see generateAttributeLabel()
	 */
	public function attributeLabels()
	{
		return [];
	}

	/**
	 * 执行数据验证.
	 *
	 * 该方法适用于当前执行验证规则 [[scenario]].
	 * 以下标准用来确定当前是否适用的规则:
	 *
	 * - 规则必须与属性相关,并且关联到当前场景;
	 * - 当前场景的规则必须是有效的.
	 *
	 * 这个方法将调用 [[beforeValidate()]] 和 [[afterValidate()]] 之前和之
	 * 后的实际验证, 分别. 如果 [[beforeValidate()]] 返回为假,
	 * 验证将会取消 [[afterValidate()]] 将不会被调用.
	 *
	 * 可以通过检索在验证过程中发现错误 [[getErrors()]],
	 * [[getFirstErrors()]] 和 [[getFirstError()]].
	 *
	 * @param array $attributes 应验证的属性列表.
	 * 如果这个参数是空的, 它是指在适用的验证规则中列出的所有应验证的属性.
	 * @param boolean $clearErrors 是否调用 [[clearErrors()]] 前执行验证
	 * @return boolean 验证是否成功没有任何错误.
	 * @throws InvalidParamException 如果当前场景是未知的.
	 */
	public function validate($attributes = null, $clearErrors = true)
	{
		$scenarios = $this->scenarios();
		$scenario = $this->getScenario();
		if (!isset($scenarios[$scenario])) {
			throw new InvalidParamException("Unknown scenario: $scenario");
		}

		if ($clearErrors) {
			$this->clearErrors();
		}
		if ($attributes === null) {
			$attributes = $this->activeAttributes();
		}
		if ($this->beforeValidate()) {
			foreach ($this->getActiveValidators() as $validator) {
				$validator->validateAttributes($this, $attributes);
			}
			$this->afterValidate();
			return !$this->hasErrors();
		}
		return false;
	}

	/**
	 * 验证开始前调用该方法.
	 * 默认引发一个 `beforeValidate` 事件.
	 * 你可以覆盖这个方法做初步检查前验证.
	 * 确保调用父类实现,这样可以更高效.
	 * @return boolean 验证是否应该被执行. 默认为执行.
	 * 如果返回错误, 验证将停止模型被认为是无效的.
	 */
	public function beforeValidate()
	{
		$event = new ModelEvent;
		$this->trigger(self::EVENT_BEFORE_VALIDATE, $event);
		return $event->isValid;
	}

	/**
	 * 验证结束后调用该方法.
	 * 默认引发一个 `afterValidate` 事件.
	 * 你可以重写此验证方法后进行后处理.
	 * 确保调用父类实现,这样可以更高效.
	 */
	public function afterValidate()
	{
		$this->trigger(self::EVENT_AFTER_VALIDATE);
	}

	/**
	 * 返回所有声明的验证器 [[rules()]].
	 *
	 * 这种方法不同于 [[getActiveValidators()]] 后者只返回适用于当前的
	 * 验证器 [[scenario]].
	 *
	 * 因为这个方法返回一个 ArrayObject 对象, 你可以通过插入或删除验证器来
	 * 操纵它 (用于模型的行为).
	 * 例如,
	 *
	 * ~~~
	 * $model->validators[] = $newValidator;
	 * ~~~
	 *
	 * @return ArrayObject|\yii\validators\Validator[] 模型中声明的所有验证器.
	 */
	public function getValidators()
	{
		if ($this->_validators === null) {
			$this->_validators = $this->createValidators();
		}
		return $this->_validators;
	}

	/**
	 * 返回适用于当前的验证器 [[scenario]].
	 * @param string $attribute 应该返回其适用的验证器属性的名称.
	 * 如果是空的, 将返回模型中所有属性的验证器.
	 * @return \yii\validators\Validator[] 验证器适用于当前的 [[scenario]].
	 */
	public function getActiveValidators($attribute = null)
	{
		$validators = [];
		$scenario = $this->getScenario();
		foreach ($this->getValidators() as $validator) {
			if ($validator->isActive($scenario) && ($attribute === null || in_array($attribute, $validator->attributes, true))) {
				$validators[] = $validator;
			}
		}
		return $validators;
	}

	/**
	 * 创建验证器对象中指定的验证规则 [[rules()]].
	 * 不像 [[getValidators()]], 每次调用此方法, 新确认器会返回的列表.
	 * @return ArrayObject 验证器
	 * @throws InvalidConfigException 如果任何验证规则配置是无效的
	 */
	public function createValidators()
	{
		$validators = new ArrayObject;
		foreach ($this->rules() as $rule) {
			if ($rule instanceof Validator) {
				$validators->append($rule);
			} elseif (is_array($rule) && isset($rule[0], $rule[1])) { // attributes, validator type
				$validator = Validator::createValidator($rule[1], $this, (array) $rule[0], array_slice($rule, 2));
				$validators->append($validator);
			} else {
				throw new InvalidConfigException('Invalid validation rule: a rule must specify both attribute names and validator type.');
			}
		}
		return $validators;
	}

	/**
	 * 返回一个值,提示属性是否是必需的.
	 * 如果属性是用 [[\yii\validators\RequiredValidator|required]] 相关检查来确定,
	 * 验证当前的 [[scenario]].
	 * @param string $attribute 属性名称
	 * @return boolean 是否需要的属性
	 */
	public function isAttributeRequired($attribute)
	{
		foreach ($this->getActiveValidators($attribute) as $validator) {
			if ($validator instanceof RequiredValidator) {
				return true;
			}
		}
		return false;
	}

	/**
	 * 返回一个值,该值指示该属性是否安全的大规模作业.
	 * @param string $attribute 属性名称
	 * @return boolean 属性是否安全的大规模作业
	 * @see safeAttributes()
	 */
	public function isAttributeSafe($attribute)
	{
		return in_array($attribute, $this->safeAttributes(), true);
	}

	/**
	 * 返回一个值,该值指示该属性是否活跃在当前场景.
	 * @param string $attribute 属性名称
	 * @return boolean 在当前的场景属性是否活跃
	 * @see activeAttributes()
	 */
	public function isAttributeActive($attribute)
	{
		return in_array($attribute, $this->activeAttributes(), true);
	}

	/**
	 * 返回指定的属性的文本标签.
	 * @param string $attribute 属性名称
	 * @return string 属性标签
	 * @see generateAttributeLabel()
	 * @see attributeLabels()
	 */
	public function getAttributeLabel($attribute)
	{
		$labels = $this->attributeLabels();
		return isset($labels[$attribute]) ? $labels[$attribute] : $this->generateAttributeLabel($attribute);
	}

	/**
	 * 返回一个指示是否有任何验证错误的值.
	 * @param string|null $attribute 属性名字. 使用 null 检查所有属性.
	 * @return boolean 是否有任何错误.
	 */
	public function hasErrors($attribute = null)
	{
		return $attribute === null ? !empty($this->_errors) : isset($this->_errors[$attribute]);
	}

	/**
	 * 返回所有属性或一个属性的错误.
	 * @param string $attribute 属性名称. 使用 null 来检索所有属性的错误.
	 * @property array 一个数组所有属性的错误. 如果没有错误则返回空数组.
	 * 结果是一个二维数组. 参见 [[getErrors()]] 的详细描述.
	 * @return array 返回所有属性或指定的属性错误. 如果没有错误则返回空数组.
	 * 注意,当所有属性返回错误, 结果是一个二维数组, 像下面的:
	 *
	 * ~~~
	 * [
	 *     'username' => [
	 *         'Username is required.',
	 *         'Username must contain only word characters.',
	 *     ],
	 *     'email' => [
	 *         'Email address is invalid.',
	 *     ]
	 * ]
	 * ~~~
	 *
	 * @see getFirstErrors()
	 * @see getFirstError()
	 */
	public function getErrors($attribute = null)
	{
		if ($attribute === null) {
			return $this->_errors === null ? [] : $this->_errors;
		} else {
			return isset($this->_errors[$attribute]) ? $this->_errors[$attribute] : [];
		}
	}

	/**
	 * 返回第一个错误的每个属性模型.
	 * @return array 第一个错误. 数组的键是属性名称, 和数组值相应的
	 * 错误消息. 如果没有错误将返回一个空数组.
	 * @see getErrors()
	 * @see getFirstError()
	 */
	public function getFirstErrors()
	{
		if (empty($this->_errors)) {
			return [];
		} else {
			$errors = [];
			foreach ($this->_errors as $name => $es) {
				if (!empty($es)) {
					$errors[$name] = reset($es);
				}
			}
			return $errors;
		}
	}

	/**
	 * 返回指定属性的第一个错误.
	 * @param string $attribute 属性名称.
	 * @return string 错误信息. 如果没有错误则返回 Null.
	 * @see getErrors()
	 * @see getFirstErrors()
	 */
	public function getFirstError($attribute)
	{
		return isset($this->_errors[$attribute]) ? reset($this->_errors[$attribute]) : null;
	}

	/**
	 * 给指定的属性添加错误信息.
	 * @param string $attribute 属性名称
	 * @param string $error 新的错误信息
	 */
	public function addError($attribute, $error = '')
	{
		$this->_errors[$attribute][] = $error;
	}

	/**
	 * 为所有属性或一个属性删除错误.
	 * @param string $attribute 属性名称. 使用 null 来删除所有属性的错误.
	 */
	public function clearErrors($attribute = null)
	{
		if ($attribute === null) {
			$this->_errors = [];
		} else {
			unset($this->_errors[$attribute]);
		}
	}

	/**
	 * 生成一个用户友好的属性标签,基于给予属性名称.
	 * 这是通过替换下划线, 破折号,点,空白和改变每个单词的
	 * 第一个字母大写.
	 * 例如, 'department_name' 或 'DepartmentName' 将生成 'Department Name'.
	 * @param string $name 列名
	 * @return string the 属性标签
	 */
	public function generateAttributeLabel($name)
	{
		return Inflector::camel2words($name, true);
	}

	/**
	 * 返回属性值.
	 * @param array $names 需要返回的属性值列表.
	 * 默认为空, 列出的所有属性 [[attributes()]] 将被归回.
	 * 如果它是一个数组, 只返回数组中的属性.
	 * @param array $except 不返回属性的值的列表.
	 * @return array 属性值 (名称 => 值).
	 */
	public function getAttributes($names = null, $except = [])
	{
		$values = [];
		if ($names === null) {
			$names = $this->attributes();
		}
		foreach ($names as $name) {
			$values[$name] = $this->$name;
		}
		foreach ($except as $name) {
			unset($values[$name]);
		}

		return $values;
	}

	/**
	 * 用一个巨大的方式设置属性值.
	 * @param array $values 属性值 (名称 => 值) 分配到模型中.
	 * @param boolean $safeOnly 是否只应在安全属性分配.
	 * 在当前 [[scenario]] 安全属性是与一个验证规则.
	 * @see safeAttributes()
	 * @see attributes()
	 */
	public function setAttributes($values, $safeOnly = true)
	{
		if (is_array($values)) {
			$attributes = array_flip($safeOnly ? $this->safeAttributes() : $this->attributes());
			foreach ($values as $name => $value) {
				if (isset($attributes[$name])) {
					$this->$name = $value;
				} elseif ($safeOnly) {
					$this->onUnsafeAttribute($name, $value);
				}
			}
		}
	}

	/**
	 * 调用该方法时,一个不安全的属性被分配.
	 * 如果 YII_DEBUG 是一条警告消息,默认记录此消息.
	 * 否则什么也不做.
	 * @param string $name 不安全的属性名称
	 * @param mixed $value 属性值
	 */
	public function onUnsafeAttribute($name, $value)
	{
		if (YII_DEBUG) {
			Yii::trace("Failed to set unsafe attribute '$name' in '" . get_class($this) . "'.", __METHOD__);
		}
	}

	/**
	 * 返回用于该场景的模型.
	 *
	 * 场景会影响执行验证和哪些属性可以被大量分配.
	 *
	 * @return string 该模型所处默认为 [[SCENARIO_DEFAULT]].
	 */
	public function getScenario()
	{
		return $this->_scenario;
	}

	/**
	 * 设置场景模式.
	 * 请注意,此方法不检查的情况存在与否.
	 * 方法 [[validate()]] 将执行此检查.
	 * @param string $value 这个模型的场景.
	 */
	public function setScenario($value)
	{
		$this->_scenario = $value;
	}

	/**
	 * 返回属性名称,在当前场景安全的大规模分配.
	 * @return string[] 安全属性名称
	 */
	public function safeAttributes()
	{
		$scenario = $this->getScenario();
		$scenarios = $this->scenarios();
		if (!isset($scenarios[$scenario])) {
			return [];
		}
		$attributes = [];
		foreach ($scenarios[$scenario] as $attribute) {
			if ($attribute[0] !== '!') {
				$attributes[] = $attribute;
			}
		}
		return $attributes;
	}

	/**
	 * 返回属性名称,来验证在当前场景.
	 * @return string[] 安全属性名称
	 */
	public function activeAttributes()
	{
		$scenario = $this->getScenario();
		$scenarios = $this->scenarios();
		if (!isset($scenarios[$scenario])) {
			return [];
		}
		$attributes = $scenarios[$scenario];
		foreach ($attributes as $i => $attribute) {
			if ($attribute[0] === '!') {
				$attributes[$i] = substr($attribute, 1);
			}
		}
		return $attributes;
	}

	/**
	 * 从最终用户的数据填充模型.
	 * 要加载的数据是 `$data[formName]`, 其中 `formName` 指的值是 [[formName()]].
	 * 如果 [[formName()]] 是空的, 整个的 `$data` 数组将被用于填充模型.
	 * 填充的数据由 [[setAttributes()]] 安全检查.
	 * @param array $data 数据数组. 通常是 `$_POST` or `$_GET`, 但也可以是任何有效的数组提
	 * 供的最终用户.
	 * @param string $formName 表单名称用于加载数据到模型中.
	 * 如果没有设置, [[formName()]] 将使用.
	 * @return boolean 模型是否成功地填充了一些数据.
	 */
	public function load($data, $formName = null)
	{
		$scope = $formName === null ? $this->formName() : $formName;
		if ($scope == '' && !empty($data)) {
			$this->setAttributes($data);
			return true;
		} elseif (isset($data[$scope])) {
			$this->setAttributes($data[$scope]);
			return true;
		} else {
			return false;
		}
	}

	/**
	 * 填充一组模型与最终用户的数据.
	 * 这种方法主要用于收集表格数据输入.
	 * 要为每个模型加载的数据是 `$data[formName][index]`, `formName`
	 * 指的值是 [[formName()]], 和 `index` 模型指数数组 `$models`.
	 * 如果 [[formName()]] 是空的, `$data[index]` 将用于填充每个模型.
	 * 每个模型的数据填充由 [[setAttributes()]] 安全检查.
	 * @param array $models 填充模型. 请注意,所有的模型都应该有相同的类.
	 * @param array $data 数据数组. 数据来自 `$_POST` 或者 `$_GET`, 但也可以是任何有效的数组提
	 * 供的最终用户.
	 * @return boolean 模型是否成功地填充了一些数据.
	 */
	public static function loadMultiple($models, $data)
	{
		/** @var Model $model */
		$model = reset($models);
		if ($model === false) {
			return false;
		}
		$success = false;
		$scope = $model->formName();
		foreach ($models as $i => $model) {
			if ($scope == '') {
				if (isset($data[$i])) {
					$model->setAttributes($data[$i]);
					$success = true;
				}
			} elseif (isset($data[$scope][$i])) {
				$model->setAttributes($data[$scope][$i]);
				$success = true;
			}
		}
		return $success;
	}

	/**
	 * 验证多个模型.
	 * 该方法将验证每一个模型. 正在验证的模型可能是相同的或
	 * 不同的类型.
	 * @param array $models 模型进行验证
	 * @param array $attributes 应验证的属性列表.
	 * 如果这个参数是空的, 这意味着应该验证任何属
	 * 性中列出适用的验证规则.
	 * @return boolean 所有的模型是否是有效的. 如果一个或多个模型验证错误
	 * 错误将被返回.
	 */
	public static function validateMultiple($models, $attributes = null)
	{
		$valid = true;
		/** @var Model $model */
		foreach ($models as $model) {
			$valid = $model->validate($attributes) && $valid;
		}
		return $valid;
	}

	/**
	 * 当没有指定特定的字段,返回的字段列表应该是默认的 [[toArray()]].
	 *
	 * 一个字段是一个属性名称,在返回的数组元素 [[toArray()]].
	 *
	 * 此方法应该返回一个数组字段名和字段定义.
	 * 如果是前者, 字段名称将被视为一个对象属性名称的值
	 * 被用作字段值. 如果是后者, 数组的键应该是字段名,而数组的值应该是对应的字段定义,可以是一
	 * 个对象属性名或一个PHP调用返回相应的字段值. 可调用的签名:
	 *
	 * ```php
	 * function ($field, $model) {
	 *     // return field value
	 * }
	 * ```
	 *
	 * 例如, 下面的代码声明四个字段:
	 *
	 * - `email`: 字段名相同的属性名 `email`;
	 * - `firstName` and `lastName`: 字段名称是 `firstName` 和 `lastName`, 它们的值
	 *   是从 `first_name` 和 `last_name` 性能得到;
	 * - `fullName`: 字段名是 `fullName`. 它的值是通过连接 `first_name`
	 *   和 `last_name`.
	 *
	 * ```php
	 * return [
	 *     'email',
	 *     'firstName' => 'first_name',
	 *     'lastName' => 'last_name',
	 *     'fullName' => function () {
	 *         return $this->first_name . ' ' . $this->last_name;
	 *     },
	 * ];
	 * ```
	 *
	 * 在这个方法中, 您可能还想基于一些上下文信息返回不同的字 
	 * 段列表.例如, 根据 [[scenario]] 或当前应用程序用户的特权,
	 * 你可以返回不同的字段或过滤掉一些字段可见.
	 *
	 * 此方法默认实现返回 [[attributes()]] 由相同的属性名索引.
	 *
	 * @return array 字段名称的列表或字段定义.
	 * @see toArray()
	 */
	public function fields()
	{
		$fields = $this->attributes();
		return array_combine($fields, $fields);
	}

	/**
	 * [[toArray()]] 决定哪些字段可以返回.
	 * 该方法将检查那些声明的请求字段 [[fields()]] 和 [[extraFields()]]
	 * 来决定哪些字段是可以恢复的.
	 * @param array $fields 被请求的字段导出
	 * @param array $expand 被请求出口的附加字段
	 * @return array 导出字段的列表. 数组的键是字段名称, 数组值是对应的对象属性名
	 * 称或PHP callables返回的字段值.
	 */
	protected function resolveFields(array $fields, array $expand)
	{
		$result = [];

		foreach ($this->fields() as $field => $definition) {
			if (is_integer($field)) {
				$field = $definition;
			}
			if (empty($fields) || in_array($field, $fields, true)) {
				$result[$field] = $definition;
			}
		}

		if (empty($expand)) {
			return $result;
		}

		foreach ($this->extraFields() as $field => $definition) {
			if (is_integer($field)) {
				$field = $definition;
			}
			if (in_array($field, $expand, true)) {
				$result[$field] = $definition;
			}
		}

		return $result;
	}

	/**
	 * 返回一个迭代器遍历属性的模型.
	 * 这种方法是 IteratorAggregate 所需的接口.
	 * @return ArrayIterator 一个迭代器遍历列表中的项目.
	 */
	public function getIterator()
	{
		$attributes = $this->getAttributes();
		return new ArrayIterator($attributes);
	}

	/**
	 * 返回是否有指定偏移位置的一个元素.
	 * 此方法要求 SPL 接口 `ArrayAccess`.
	 * 当你使用类似 `isset($model[$offset])` 这是隐式调用.
	 * @param mixed $offset 检查的抵消
	 * @return boolean
	 */
	public function offsetExists($offset)
	{
		return $this->$offset !== null;
	}

	/**
	 * 返回指定偏移位置的元素.
	 * 此方法要求 SPL 接口 `ArrayAccess`.
	 * 当你使用类似 `$value = $model[$offset];` 是隐式调用.
	 * @param mixed $offset 抵消检索元素.
	 * @return mixed 元素的偏移量, null 如果没有找到元素的偏移量
	 */
	public function offsetGet($offset)
	{
		return $this->$offset;
	}

	/**
	 * 设置指定偏移位置的元素.
	 * 此方法要求 SPL 接口 `ArrayAccess`.
	 * 当你使用类似 `$model[$offset] = $item;` 是隐式调用.
	 * @param integer $offset 抵消设置元素
	 * @param mixed $item 元素的值
	 */
	public function offsetSet($offset, $item)
	{
		$this->$offset = $item;
	}

	/**
	 * 设置指定偏移位置的元素值为 null.
	 * 该方法需要 SPL 的接口 `ArrayAccess`.
	 * 当你使用类似 `unset($model[$offset])` 是隐式调用.
	 * @param mixed $offset 设置元素的偏移量
	 */
	public function offsetUnset($offset)
	{
		$this->$offset = null;
	}
}
