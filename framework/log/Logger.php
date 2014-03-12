<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\log;

use Yii;
use yii\base\Component;

/**
 * 将信息记录在存储器中并根据需要将它们发送到不同的目标.
 *
 * Logger is registered as a core application component，可以使用`Yii::$app->log`调用.
 * 通过[[log()]]方法记录一个日志消息. 为了方便,
 * [[Yii]] 类 提供的一系列有关于 错误日志的方法:
 *
 * - [[Yii::trace()]]
 * - [[Yii::error()]]
 * - [[Yii::warning()]]
 * - [[Yii::info()]]
 * - [[Yii::beginProfile()]]
 * - [[Yii::endProfile()]]
 *
 * 当足够的消息被累积在记录器, 或当当前请求完成,
 * 记录的消息将被发送到不同的[[targets]], 例如日志文件, 电子邮件.
 *
 * 您可以通过应用程序配置配置目标, 类似以下:
 *
 * ~~~
 * [
 *     'components' => [
 *         'log' => [
 *             'targets' => [
 *                 'file' => [
 *                     'class' => 'yii\log\FileTarget',
 *                     'levels' => ['trace', 'info'],
 *                     'categories' => ['yii\*'],
 *                 ],
 *                 'email' => [
 *                     'class' => 'yii\log\EmailTarget',
 *                     'levels' => ['error', 'warning'],
 *                     'message' => [
 *                         'to' => 'admin@example.com',
 *                     ],
 *                 ],
 *             ],
 *         ],
 *     ],
 * ]
 * ~~~
 *
 * 每个日志对象可以有一个名称，可以通过[[targets]]属性引用
 * 如下:
 *
 * ~~~
 * Yii::$app->log->targets['file']->enabled = false;
 * ~~~
 *
 * 当应用程序结束或[[flushInterval]]到达, 记录器将调用[[flush()]]
 * 发送记录的消息到不同的日志目标, 例如文件, email, Web.
 *
 * @property array $dbProfiling 第一个元素指示执行的SQL语句的数量, 
 * 第二个元素的总时间花费在SQL执行. 这个属性是只读的.
 * @property float $elapsedTime 总时间，当前请求以秒为单位. 
 * 这个属性是只读的.
 * @property array $profiling 分析该结果. Each element is an array consisting of these elements:
 * `info`, `category`, `timestamp`, `trace`, `level`, `duration`. 此属性是只读的.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class Logger extends Component
{
	/**
	 * 错误消息级别. 应用程序异常终止的错误消息
	 * 并且可能需要开发者的处理.
	 */
	const LEVEL_ERROR = 0x01;
	/**
	 * 警告消息级别. 不正常的情况发生的警告消息
	 * 但应用程序能够继续运行. 开发人员应该注意这消息.
	 */
	const LEVEL_WARNING = 0x02;
	/**
	 * 参考消息级别. An informational message is one that includes certain information
	 * for developers to review.
	 */
	const LEVEL_INFO = 0x04;
	/**
	 * 跟踪消息级别. 踪消息揭示了代码执行流程.
	 */
	const LEVEL_TRACE = 0x08;
	/**
	 * 分析消息级别. This indicates the message is for profiling purpose.
	 */
	const LEVEL_PROFILE = 0x40;
	/**
	 * 分析消息级别. This indicates the message is for profiling purpose. 它标志着
	 * 一个剖析块的开始.
	 */
	const LEVEL_PROFILE_BEGIN = 0x50;
	/**
	 * 分析消息级别. This indicates the message is for profiling purpose. 它标志着
	 * 一个剖析块的结束.
	 */
	const LEVEL_PROFILE_END = 0x60;


	/**
	 * @var array 记录的消息. 此属性通过[[log()]] 和 [[flush()]]管理.
	 * 每个日志信息按照以下结构:
	 *
	 * ~~~
	 * [
	 *   [0] => message (mixed, can be a string or some complex data, such as an exception object)
	 *   [1] => level (integer)
	 *   [2] => category (string)
	 *   [3] => timestamp (float, obtained by microtime(true))
	 *   [4] => traces (array, debug backtrace, contains the application code call stacks)
	 * ]
	 * ~~~
	 */
	public $messages = [];
	/**
	 * @var array 调试数据. 此属性用于存储各种类型的调试数据,报告在
	 * 不同的地方.
	 */
	public $data = [];
	/**
	 * @var array|Target[] 日志目标. Each array element represents a single [[Target|log target]] instance
	 * or the configuration for creating the log target instance.
	 */
	public $targets = [];
	/**
	 * @var integer how many messages should be logged before they are flushed from memory and sent to targets.
	 * Defaults to 1000, meaning the [[flush]] method will be invoked once every 1000 messages logged.
	 * Set this property to be 0 if you don't want to flush messages until the application terminates.
	 * This property mainly affects how much memory will be taken by the logged messages.
	 * A smaller value means less memory, but will increase the execution time due to the overhead of [[flush()]].
	 */
	public $flushInterval = 1000;
	/**
	 * @var integer how much call stack information (file name and line number) should be logged for each message.
	 * If it is greater than 0, at most that number of call stacks will be logged. Note that only application
	 * call stacks are counted.
	 *
	 * If not set, it will default to 3 when `YII_ENV` is set as "dev", and 0 otherwise.
	 */
	public $traceLevel;

	/**
	 * Initializes the logger by registering [[flush()]] as a shutdown function.
	 */
	public function init()
	{
		parent::init();
		if ($this->traceLevel === null) {
			$this->traceLevel = YII_ENV_DEV ? 3 : 0;
		}
		foreach ($this->targets as $name => $target) {
			if (!$target instanceof Target) {
				$this->targets[$name] = Yii::createObject($target);
			}
		}
		register_shutdown_function([$this, 'flush'], true);
	}

	/**
	 * 记录具有给定类型和类别的消息.
	 * 如果[[traceLevel]]大于0, additional call stack information about
	 * the application code will be logged as well.
	 * @param string $message the message to be logged.
	 * @param integer $level the level of the message. This must be one of the following:
	 * `Logger::LEVEL_ERROR`, `Logger::LEVEL_WARNING`, `Logger::LEVEL_INFO`, `Logger::LEVEL_TRACE`,
	 * `Logger::LEVEL_PROFILE_BEGIN`, `Logger::LEVEL_PROFILE_END`.
	 * @param string $category 该消息的类.
	 */
	public function log($message, $level, $category = 'application')
	{
		$time = microtime(true);
		$traces = [];
		if ($this->traceLevel > 0) {
			$count = 0;
			$ts = debug_backtrace();
			array_pop($ts); // remove the last trace since it would be the entry script, not very useful
			foreach ($ts as $trace) {
				if (isset($trace['file'], $trace['line']) && strpos($trace['file'], YII_PATH) !== 0) {
					unset($trace['object'], $trace['args']);
					$traces[] = $trace;
					if (++$count >= $this->traceLevel) {
						break;
					}
				}
			}
		}
		$this->messages[] = [$message, $level, $category, $time, $traces];
		if ($this->flushInterval > 0 && count($this->messages) >= $this->flushInterval) {
			$this->flush();
		}
	}

	/**
	 * 从内存到目标刷新日志信息.
	 * @param boolean $final 是否是一个请求期间的最后调用.
	 */
	public function flush($final = false)
	{
		/** @var Target $target */
		foreach ($this->targets as $target) {
			if ($target->enabled) {
				$target->collect($this->messages, $final);
			}
		}
		$this->messages = [];
	}

	/**
	 * 返回自当前请求的开始的总运行时间.
	 * 这种方法计算，现在和在文件[[\yii\BaseYii]]开始处
	 * 被`YII_BEGIN_TIME`定义的时间戳 
	 * 之间的不同.
	 * @return float 总的运行时间，以秒为单位.
	 */
	public function getElapsedTime()
	{
		return microtime(true) - YII_BEGIN_TIME;
	}

	/**
	 * 返回的分析结果.
	 *
	 * 默认所有的分析结果将被返回. 可以使用
	 * `$categories` and `$excludeCategories` 作为参数来检索
	 * 所感兴趣的结果.
	 *
	 * @param array $categories 你有兴趣的类别列表.
	 * You can use an asterisk at the end of a category to do a prefix match.
	 * For example, 'yii\db\*' will match categories starting with 'yii\db\',
	 * such as 'yii\db\Connection'.
	 * @param array $excludeCategories list of categories that you want to exclude
	 * @return array the profiling results. Each element is an array consisting of these elements:
	 * `info`, `category`, `timestamp`, `trace`, `level`, `duration`.
	 */
	public function getProfiling($categories = [], $excludeCategories = [])
	{
		$timings = $this->calculateTimings($this->messages);
		if (empty($categories) && empty($excludeCategories)) {
			return $timings;
		}

		foreach ($timings as $i => $timing) {
			$matched = empty($categories);
			foreach ($categories as $category) {
				$prefix = rtrim($category, '*');
				if (strpos($timing['category'], $prefix) === 0 && ($timing['category'] === $category || $prefix !== $category)) {
					$matched = true;
					break;
				}
			}

			if ($matched) {
				foreach ($excludeCategories as $category) {
					$prefix = rtrim($category, '*');
					foreach ($timings as $i => $timing) {
						if (strpos($timing['category'], $prefix) === 0 && ($timing['category'] === $category || $prefix !== $category)) {
							$matched = false;
							break;
						}
					}
				}
			}

			if (!$matched) {
				unset($timings[$i]);
			}
		}
		return array_values($timings);
	}

	/**
	 * 返回数据库查询的统计结果.
	 * 返回的结果包括执行的SQL语句的数量和
	 * 花费的总时间.
	 * @return array 第一个元素表示执行的SQL语句的数量,
	 * 第二个元素是SQL执行花费的总时间.
	 */
	public function getDbProfiling()
	{
		$timings = $this->getProfiling(['yii\db\Command::query', 'yii\db\Command::execute']);
		$count = count($timings);
		$time = 0;
		foreach ($timings as $timing) {
			$time += $timing['duration'];
		}
		return [$count, $time];
	}

	/**
	 * 计算给定的日志消息所用的时间.
	 * @param array $messages 从分析中获得的日志信息
	 * @return array timings. Each element is an array consisting of these elements:
	 * `info`, `category`, `timestamp`, `trace`, `level`, `duration`.
	 */
	public function calculateTimings($messages)
	{
		$timings = [];
		$stack = [];

		foreach ($messages as $i => $log) {
			list($token, $level, $category, $timestamp, $traces) = $log;
			$log[5] = $i;
			if ($level == Logger::LEVEL_PROFILE_BEGIN) {
				$stack[] = $log;
			} elseif ($level == Logger::LEVEL_PROFILE_END) {
				if (($last = array_pop($stack)) !== null && $last[0] === $token) {
					$timings[$last[5]] = [
						'info' => $last[0],
						'category' => $last[2],
						'timestamp' => $last[3],
						'trace' => $last[4],
						'level' => count($stack),
						'duration' => $timestamp - $last[3],
					];
				}
			}
		}

		ksort($timings);

		return array_values($timings);
	}


	/**
	 * 返回指定级别以文本显示.
	 * @param integer $level 消息级别, 例如. [[LEVEL_ERROR]], [[LEVEL_WARNING]].
	 * @return string 该级别的文本显示
	 */
	public static function getLevelName($level)
	{
		static $levels = [
			self::LEVEL_ERROR => 'error',
			self::LEVEL_WARNING => 'warning',
			self::LEVEL_INFO => 'info',
			self::LEVEL_TRACE => 'trace',
			self::LEVEL_PROFILE_BEGIN => 'profile begin',
			self::LEVEL_PROFILE_END => 'profile end',
		];
		return isset($levels[$level]) ? $levels[$level] : 'unknown';
	}
}
