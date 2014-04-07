<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\log;

use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;

/**
 * Target是所有日志目标类的基类。
 *
 * Log target对象将根据其[[levels]] and [[categories]] 属性筛选记录的[[Logger]]
 * 消息。 它也可以导出过滤后的信息到
 * target定义的指定目标， 例如 emails, files。
 *
 * 级别过滤和类别过滤是组合起来的，即，消息满足
 * 过滤条件将被处理。 此外， 你可以
 * 指定[[except]]来排除某些类别的信息。
 *
 * @property integer $levels 感兴趣的消息级别。这是级别值
 * 位图。默认设置为0，所有可用级别。请注意此属性在getter
 * 和setter中不同。详见[[getLevels()]] and [[setLevels()]]。
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
abstract class Target extends Component
{
	/**
	 * @var boolean 是否启用此log target， 默认true。
	 */
	public $enabled = true;
	/**
	 * @var array 关心的消息类别列表。 默认空，这意味着所有类别。
	 * 你可以在类别的结尾处用*作为通配符来
	 * 匹配有相同的公共前缀的类别. 例如，'yii\db\*' 将匹配以
	 * 'yii\db\'开头的分类，比如'yii\db\Connection'。
	 */
	public $categories = [];
	/**
	 * @var array 不关心的消息类别列表。默认设置为空，意味着不关心此消息。
	 * 若此属性不为空，在这里列出的任何类别将从[[categories]]中被排除。
	 * 你可以在类别的结尾处用*作为通配符来
	 * 匹配前缀匹配的所有类别。 例如，'yii\db\*'将匹配以
	 * 'yii\db\'开头的分类，比如'yii\db\Connection'。
	 * @see categories
	 */
	public $except = [];
	/**
	 * @var boolean 是否记录包含当前用户名和ID的消息。 默认设置为false.
	 * @see \yii\web\User
	 */
	public $logUser = false;
	/**
	 * @var array PHP的预定义变量应该记录在消息列表中。
	 * 请注意变量必须通过`$GLOBALS`访问。 否则将不会被记录。
	 * 默认设置为`['_GET', '_POST', '_FILES', '_COOKIE', '_SESSION', '_SERVER']`。
	 */
	public $logVars = ['_GET', '_POST', '_FILES', '_COOKIE', '_SESSION', '_SERVER'];
	/**
	 * @var integer 在导出之前应累积多少消息。
	 * 默认设置为1000。请注意在应用程序终止时消息将被导出。
	 * 如果直到应用程序终止你也不想输出消息，则将此属性设置为0。
	 */
	public $exportInterval = 1000;
	/**
	 * @var array 到目前为止 这个log target从logger中接收到的消息。
	 * 请参阅[[Logger::messages]]关于消息结构的详细信息。
	 */
	public $messages = [];

	private $_levels = 0;

	/**
	 * 输出日志[[messages]]到特定目的地.
	 * 子类必须实现此方法.
	 */
	abstract public function export();

	/**
	 * 处理给定的日志消息。
	 * 此方法将使用[[levels]]和[[categories]]筛选给定的消息。
	 * 如果要求, 它会将过滤结果导出到特定的介质(例如email)。
	 * @param array $messages 处理日志消息。参见[[Logger::messages]]
	 * 每个消息的结构。
	 * @param boolean $final 前应用程序结束后这个方法是否被调用。
	 */
	public function collect($messages, $final)
	{
		$this->messages = array_merge($this->messages, $this->filterMessages($messages, $this->getLevels(), $this->categories, $this->except));
		$count = count($this->messages);
		if ($count > 0 && ($final || $this->exportInterval > 0 && $count >= $this->exportInterval)) {
			if (($context = $this->getContextMessage()) !== '') {
				$this->messages[] = [$context, Logger::LEVEL_INFO, 'application', YII_BEGIN_TIME];
			}
			$this->export();
			$this->messages = [];
		}
	}

	/**
	 * 记录生成上下文信息。
	 * 这个默认的实现将转储用户信息， 系统变量等。
	 * @return string 上下文信息. 如果是一个空字符串, 这意味着没有上下文信息。
	 */
	protected function getContextMessage()
	{
		$context = [];
		if ($this->logUser && ($user = Yii::$app->getComponent('user', false)) !== null) {
			/** @var \yii\web\User $user */
			$context[] = 'User: ' . $user->getId();
		}

		foreach ($this->logVars as $name) {
			if (!empty($GLOBALS[$name])) {
				$context[] = "\${$name} = " . var_export($GLOBALS[$name], true);
			}
		}

		return implode("\n\n", $context);
	}

	/**
	 * @return integer 你感兴趣的消息级别。 这是一个级别值
	 * 的位图。 默认为0,意味着所有可用级别。
	 */
	public function getLevels()
	{
		return $this->_levels;
	}

	/**
	 * 设置你感兴趣的消息级别。
	 *
	 * 这个参数既可以是一个关心的日志级别的名称的数组也可以是一个 位映射
	 * the bitmap of the interested level values。 有效级别值包括: 'error'，
	 * 'warning', 'info', 'trace' and 'profile'; 有效级别值包括:
	 * [[Logger::LEVEL_ERROR]], [[Logger::LEVEL_WARNING]], [[Logger::LEVEL_INFO]],
	 * [[Logger::LEVEL_TRACE]] and [[Logger::LEVEL_PROFILE]]。
	 *
	 * 例如，
	 *
	 * ~~~
	 * ['error', 'warning']
	 * // which is equivalent to:
	 * Logger::LEVEL_ERROR | Logger::LEVEL_WARNING
	 * ~~~
	 *
	 * @param array|integer $levels 这个目标是感兴趣的消息级别。
	 * @throws InvalidConfigException 如果给定一个未知的级别名称。
	 */
	public function setLevels($levels)
	{
		static $levelMap = [
			'error' => Logger::LEVEL_ERROR,
			'warning' => Logger::LEVEL_WARNING,
			'info' => Logger::LEVEL_INFO,
			'trace' => Logger::LEVEL_TRACE,
			'profile' => Logger::LEVEL_PROFILE,
		];
		if (is_array($levels)) {
			$this->_levels = 0;
			foreach ($levels as $level) {
				if (isset($levelMap[$level])) {
					$this->_levels |= $levelMap[$level];
				} else {
					throw new InvalidConfigException("Unrecognized level: $level");
				}
			}
		} else {
			$this->_levels = $levels;
		}
	}

	/**
	 * 根据自己的类别和级别过滤给定的消息。
	 * @param array $messages 消息将被过滤。
	 * @param integer $levels 需要过滤的信息级别，这是一个级别值的位映射
	 * 0表示允许各级。
	 * @param array $categories 过滤消息类别，如果为空， 这意味着所有的类别都允许。
	 * @param array $except 排除消息类别。 如果为空, 这意味着所有的类别都允许。
	 * @return array 过滤后的消息。
	 */
	public static function filterMessages($messages, $levels = 0, $categories = [], $except = [])
	{
		foreach ($messages as $i => $message) {
			if ($levels && !($levels & $message[1])) {
				unset($messages[$i]);
				continue;
			}

			$matched = empty($categories);
			foreach ($categories as $category) {
				if ($message[2] === $category || substr($category, -1) === '*' && strpos($message[2], rtrim($category, '*')) === 0) {
					$matched = true;
					break;
				}
			}

			if ($matched) {
				foreach ($except as $category) {
					$prefix = rtrim($category, '*');
					if (strpos($message[2], $prefix) === 0 && ($message[2] === $category || $prefix !== $category)) {
						$matched = false;
						break;
					}
				}
			}

			if (!$matched) {
				unset($messages[$i]);
			}
		}
		return $messages;
	}

	/**
	 * 格式化日志消息。
	 * 该消息结构在[[Logger::messages]]中。
	 * @param array $message 日志消息进行格式化。
	 * @return string 格式化的消息
	 */
	public function formatMessage($message)
	{
		list($text, $level, $category, $timestamp) = $message;
		$level = Logger::getLevelName($level);
		if (!is_string($text)) {
			$text = var_export($text, true);
		}
		$ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
		return date('Y/m/d H:i:s', $timestamp) . " [$ip] [$level] [$category] $text";
	}
}
