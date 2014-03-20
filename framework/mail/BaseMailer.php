<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\mail;

use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\base\ViewContextInterface;
use yii\web\View;
use yii\base\MailEvent;

/**
 * BaseMailer作为一个基类,它实现了 [[MailerInterface]] 所要求的基本功能。
 *
 * 子类可以实现 [[sendMessage()]] 方法。
 *
 * @see BaseMessage
 *
 * @property View $view 视图实例。请注意,该属性的类型不同的getter和setter。参见 [[getView()]] 和 [[setView()]] 了解详情。
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 2.0
 */
abstract class BaseMailer extends Component implements MailerInterface, ViewContextInterface
{
	/**
	 * @event \yii\base\MailEvent 一个事件之前发送。
	 * 设置 [[\yii\base\MailEvent::isValid]] 为 false 取消发送。
	 */
	const EVENT_BEFORE_SEND = 'beforeSend';
	/**
	 * @event \yii\base\MailEvent 一个事件后发送。
	 */
	const EVENT_AFTER_SEND = 'afterSend';
	/**
	 * @var string 这个邮件包含视图目录文件。
	 * 这可以作为一个指定绝对路径或路径别名。
	 */
	public $viewPath = '@app/mail';
	/**
	 * @var string|boolean HTML布局视图名称。这是布局用于呈现HTML邮件的内容。
	 * 该属性可以取以下值：
	 *
	 * - 相对视图名称: 一个视图文件相对于 [[viewPath]], e.g., 'layouts/html'.
	 * - 一个路径别名: 一个绝对的视图文件路径指定为路径别名, e.g., '@app/mail/html'.
	 * - 布尔值 false: 布局被禁用。
	 */
	public $htmlLayout = 'layouts/html';
	/**
	 * @var string|boolean 文本布局视图名称。用于呈现文本邮件的内容体。
	 * 请参阅 [[htmlLayout]] 对于这个属性可以使用的值。
	 */
	public $textLayout = 'layouts/text';
	/**
	 * @var array 配置应该适用于任何新创建的电子邮件消息实例的 [[createMessage()]] 或 [[compose()]]。
	 * 由 [[MessageInterface]] 定义的任何有效的属性可以被配置，例如 `from`, `to`, `subject`, `textBody`, `htmlBody`, 等等
	 *
	 * For example:
	 *
	 * ~~~
	 * [
	 *     'charset' => 'UTF-8',
	 *     'from' => 'noreply@mydomain.com',
	 *     'bcc' => 'developer@mydomain.com',
	 * ]
	 * ~~~
	 */
	public $messageConfig = [];
	/**
	 * @var string 新消息实例的默认类名由 [[createMessage()]] 提供
	 */
	public $messageClass = 'yii\mail\BaseMessage';
	/**
	 * @var boolean 是否将邮件以文件形式保存在 [[fileTransportPath]],而不是发送实际的接受者。这通常
	 * 是在开发过程中用于调试目的。
	 * @see fileTransportPath
	 */
	public $useFileTransport = false;
	/**
	 * @var string the directory where the email messages are saved when [[useFileTransport]] is true.
	 * @var string 当 [[useFileTransport]] 为真，电子邮件保存在目录中。
	 */
	public $fileTransportPath = '@runtime/mail';
	/**
	 * @var callable 当 [[useFileTransport]] 为真, [[send()]] 将被PHP回调。
	 * 回调函数应该返回一个文件名称将用于保存电子邮件消息。
	 * 如果没有设置, 文件名会生成基于当前时间戳。
	 *
	 * The signature of the callback is:
	 *
	 * ~~~
	 * function ($mailer, $message)
	 * ~~~
	 */
	public $fileTransportCallback;

	/**
	 * @var \yii\base\View|array 视图实例或其数组配置。
	 */
	private $_view = [];

	/**
	 * @param array|View $view 视图实例或其数组配置,用于呈现消息正文。
	 * @throws InvalidConfigException 无效的参数。
	 *
	 * @param array|View $view 
	 *
	 *
	 */
	public function setView($view)
	{
		if (!is_array($view) && !is_object($view)) {
			throw new InvalidConfigException('"' . get_class($this) . '::view" should be either object or configuration array, "' . gettype($view) . '" given.');
		}
		$this->_view = $view;
	}

	/**
	 * @return View 视图实例。
	 */
	public function getView()
	{
		if (!is_object($this->_view)) {
			$this->_view = $this->createView($this->_view);
		}
		return $this->_view;
	}

	/**
	 * 从给定的配置创建视图实例。
	 * @param array $config 视图配置。
	 * @return View 视图实例。
	 */
	protected function createView(array $config)
	{
		if (!array_key_exists('class', $config)) {
			$config['class'] = View::className();
		}
		return Yii::createObject($config);
	}

	/**
	 * 创建一个新的消息实例,并通过视图呈现组成它的身体内容。
	 * 
	 * @param string|array $view 视图用于呈现消息体。 这可以是:
	 *
	 * - 一个字符串, 代表视图的名称或路径别名呈现HTML的电子邮件。
	 *   在这种情况下, 将通过应用在HTML内生成文本主体的 `strip_tags()` 。
	 * - 一个数组的 'html' 和/或 'text' 元素。'html' 元素指的是视图名称或路径别名
	 *	 呈现html的身体,而 'text' 元素是呈现文本内容。例如,
	 *   `['html' => 'contact-html', 'text' => 'contact-text']`.
	 * - 空, 这意味着消息实例将返回没有正文内容。
	 *
	 * 可以在一个指定的视图呈现下列格式:
	 *
	 * - 路径别名 (e.g. "@app/mail/contact");
	 * - 一个相对视图名称 (e.g. "contact"): 将解决 [[findViewFile()]] 的实际视图文件
	 *
	 * @param array $params 参数 (name-value pairs) 将在视图中提取并提供文件。
	 * @return MessageInterface 消息实例。
	 */
	public function compose($view = null, array $params = [])
	{
		$message = $this->createMessage();
		if ($view !== null) {
			$params['message'] = $message;
			if (is_array($view)) {
				if (isset($view['html'])) {
					$html = $this->render($view['html'], $params, $this->htmlLayout);
				}
				if (isset($view['text'])) {
					$text = $this->render($view['text'], $params, $this->textLayout);
				}
			} else {
				$html = $this->render($view, $params, $this->htmlLayout);
			}
			if (isset($html)) {
				$message->setHtmlBody($html);
			}
			if (isset($text)) {
				$message->setTextBody($text);
			} elseif (isset($html)) {
				$message->setTextBody(strip_tags($html));
			}
		}
		return $message;
	}

	/**
	 * 创建一个新的消息实例。
	 * 由 [[messageConfig]] 新创建的实例将被初始化配置。
	 * 如果配置不指定一个 'class', [[messageClass]] 将被用作类
	 * 新消息的实例。
	 * @return MessageInterface 消息实例。
	 */
	protected function createMessage()
	{
		$config = $this->messageConfig;
		if (!array_key_exists('class', $config)) {
			$config['class'] = $this->messageClass;
		}
		return Yii::createObject($config);
	}

	/**
	 * 发送电子邮件消息。
	 * 该方法将日志消息发送的电子邮件。
	 * 如果 [[useFileTransport]] 为真, 它会将邮件保存为一个文件在  [[fileTransportPath]]。
	 * 否则, 它将调用 [[sendMessage()]] 发送电子邮件收件人(s)。
	 * 子类应该实现 [[sendMessage()]] 与实际的电子邮件发送逻辑。
	 * @param MessageInterface $message 要发送电子邮件消息实例
	 * @return boolean 是否已成功发送的消息
	 */
	public function send($message)
	{
		if (!$this->beforeSend($message)) {
			return false;
		}

		$address = $message->getTo();
		if (is_array($address)) {
			$address = implode(', ', array_keys($address));
		}
		Yii::info('Sending email "' . $message->getSubject() . '" to "' . $address . '"', __METHOD__);

		if ($this->useFileTransport) {
			$isSuccessful = $this->saveMessage($message);
		} else {
			$isSuccessful = $this->sendMessage($message);
		}
		$this->afterSend($message, $isSuccessful);
		return $isSuccessful;
	}

	/**
	 * 一次发送多条消息。
	 *
	 * 默认实现简单地调用多次 [[send()]]。
	 * 子类可以重写这个方法来实现更有效的方式发送多条消息。
	 *
	 * @param array $messages 应该发送的邮件列表。
	 * @return integer 成功发送的消息数量。
	 */
	public function sendMultiple(array $messages)
	{
		$successCount = 0;
		foreach ($messages as $message) {
			if ($this->send($message)) {
				$successCount++;
			}
		}
		return $successCount;
	}

	/**
	 * 显示指定的视图与可选参数和布局。
	 * 视图将使用 [[view]] 组件呈现。
	 * @param string $view 视图的视图名称或路径别名文件。
	 * @param array $params 参数 (name-value pairs) 将在视图中提取并提供文件。
	 * @param string|boolean $layout 布局视图名称或路径别名。如果错误,没有布局将被应用。
	 * @return string 呈现的结果。
	 */
	public function render($view, $params = [], $layout = false)
	{
		$output = $this->getView()->render($view, $params, $this);
		if ($layout !== false) {
			return $this->getView()->render($layout, ['content' => $output], $this);
		} else {
			return $output;
		}
	}

	/**
	 * 指定发送消息。
	 * 这种方法应该由子类实现实际的电子邮件发送逻辑。
	 * @param MessageInterface $message 要发送的消息
	 * @return boolean 消息是否发送成功
	 */
	abstract protected function sendMessage($message);

	/**
	 * Saves the message as a file under [[fileTransportPath]].
	 * @param MessageInterface $message
	 * @return boolean 是否保存成功的消息
	 */
	protected function saveMessage($message)
	{
		$path = Yii::getAlias($this->fileTransportPath);
		if (!is_dir(($path))) {
			mkdir($path, 0777, true);
		}
		if ($this->fileTransportCallback !== null) {
			$file = $path . '/' . call_user_func($this->fileTransportCallback, $this, $message);
		} else {
			$file = $path . '/' . $this->generateMessageFileName();
		}
		file_put_contents($file, $message->toString());
		return true;
	}

	/**
	 * @return string 当 [[useFileTransport]] 为真,文件名保存消息。
	 */
	public function generateMessageFileName()
	{
		$time = microtime(true);
		return date('Ymd-His-', $time) . sprintf('%04d', (int)(($time - (int)$time) * 10000)) . '-' . sprintf('%04d', mt_rand(0, 10000)) . '.eml';
	}

	/**
	 * 找到对应的视图文件指定的相对视图名称。
	 * 此方法返回视图文件,在视图名称前面加上 [[viewPath]]。
	 * @param string $view 一个相对的视图名称。
	 * @return string 视图文件路径。请注意,文件可能不存在。
	 */
	public function findViewFile($view)
	{
		return Yii::getAlias($this->viewPath) . DIRECTORY_SEPARATOR . $view;
	}

	/**
	 * 这个方法被调用之前邮件发送。
	 * 你可以重写此方法做最后的准备。
	 * 如果你重写此方法, 请先确保你调用父实现。
	 * @param MessageInterface $message
	 * @return boolean 是否继续发送电子邮件。
	 */
	public function beforeSend($message)
	{
		$event = new MailEvent(['message' => $message]);
		$this->trigger(self::EVENT_BEFORE_SEND, $event);
		return $event->isValid;
	}

	/**
	 * 调用该方法后邮件发送。
	 * 你可以覆盖这个方法,基于邮件发送状态做一些后处理或日志。
	 * 如果你重写这个方法, 请先确保你调用父实现。
	 * @param MessageInterface $message
	 * @param boolean $isSuccessful
	 */
	public function afterSend($message, $isSuccessful)
	{
		$event = new MailEvent(['message' => $message, 'isSuccessful' => $isSuccessful]);
		$this->trigger(self::EVENT_AFTER_SEND, $event);
	}
}
