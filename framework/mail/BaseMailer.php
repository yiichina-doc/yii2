<?php
/**
 * @link http://www.yiiframework.com/
 * @yiiframework官方网址
 * @copyright Copyright (c) 2008 Yii Software LLC
 *@版权所有版权所有（c）2008乙软件有限责任公司
 * @license http://www.yiiframework.com/license/
 *@许可证http://www.yiiframework.com/license/
 */


namespace yii\mail;

use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\base\ViewContextInterface;
use yii\web\View;
use yii\base\MailEvent;

/**
 * BaseMailer serves as a base class that implements the basic functions required by [[MailerInterface]].
 * BaseMailer作为实现由[[MailerInterface]所需的基本功能的基类。
 * Concrete child classes should may focus on implementing the [[sendMessage()]] method.
 * 子类可以单独用于实现[[sendMessage（）]方法
 * @see BaseMessage
 * 可以参考 BaseMessage 类
 * @property View $view View instance. Note that the type of this property differs in getter and setter. See
 * [[getView()]] and [[setView()]] for details.
 * $view 可以用于属性视图实例化。请注意，此属性的类型与getter和setter不同。看[[getView（）]]和[[的setView（）]]了解详情。
 * @author Paul Klimov <klimov.paul@gmail.com>
 * 作者保罗·克里莫夫<klimov.paul@gmail.com>
 * @since 2.0
 * 2.0版本起
 */
abstract class BaseMailer extends Component implements MailerInterface, ViewContextInterface
{
	/**
	 * @event \yii\base\MailEvent an event raised right before send.
	 * 事件 \yii\base\MailEvent 之前发送
	 * You may set [[\yii\base\MailEvent::isValid]] to be false to cancel the send.
	 * 你可以设置 [[\yii\base\MailEvent::isValid]] 为 false 取消发送
	 */
	const EVENT_BEFORE_SEND = 'beforeSend';
	/**
	 * @event \yii\base\MailEvent an event raised right after send.
	 * 事件 \yii\base\MailEvent 之后发送
	 */
	const EVENT_AFTER_SEND = 'afterSend';
	/**
	 * @var string directory containing view files for this email messages.
	 * @在这个电子邮件信息中 字符串目录 包含视图文件
	 * This can be specified as an absolute path or path alias.
	 * 可以被指定为绝对路径或路径别名。
	 */
	public $viewPath = '@app/mail';
	/**
	 * @var string|boolean HTML layout view name. This is the layout used to render HTML mail body.
	 * The property can take the following values:
	 * 这是 HTML 布局视图的名称 这是一个用来显示HTML邮件正文的布局。
	 * 该属性可以取以下值：
	 * - a relative view name: a view file relative to [[viewPath]], e.g., 'layouts/html'.
	 * 相对视图名称：视图文件相对于[[viewPath]，比如，'layouts/ html'也是一个。
	 * - a path alias: an absolute view file path specified as a path alias, e.g., '@app/mail/html'.
	 * 一个路径别名：指定一个路径别名，比如，'@app/mail/html'也是一个绝对的视图文件的路径。
	 * - a boolean false: the layout is disabled.
	 * 一个布尔值false：布局被禁用。
	 */
	public $htmlLayout = 'layouts/html';
	/**
	 * @var string|boolean text layout view name. This is the layout used to render TEXT mail body.
	 * Please refer to [[htmlLayout]] for possible values that this property can take.
	 * @字符串|布尔 文本布局视图名称。这是用于呈现文本邮件正文的布局。
	 *请参阅[[htmlLayout]对于这个属性可以采取可能的值。
	 */
	public $textLayout = 'layouts/text';
	/**
	 * @var array the configuration that should be applied to any newly created
	 * email message instance by [[createMessage()]] or [[compose()]]. Any valid property defined
	 * by [[MessageInterface]] can be configured, such as `from`, `to`, `subject`, `textBody`, `htmlBody`, etc.
	 * @array配置，应适用于该配置任何新创建
	 * 由[[CreateMessage（）]]或[[compose（）]]电子邮件消息实例。定义的任何有效的属性
	 * 由[[MessageInterface]可配置，如`from`, `to`, `subject`, `textBody`, `htmlBody`等
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
	 * @var string the default class name of the new message instances created by [[createMessage()]]
	 */
	public $messageClass = 'yii\mail\BaseMessage';
	/**
	 * @var boolean whether to save email messages as files under [[fileTransportPath]] instead of sending them
	 * to the actual recipients. This is usually used during development for debugging purpose.
	 * @see fileTransportPath
	 */
	public $useFileTransport = false;
	/**
	 * @var string the directory where the email messages are saved when [[useFileTransport]] is true.
	 */
	public $fileTransportPath = '@runtime/mail';
	/**
	 * @var callable a PHP callback that will be called by [[send()]] when [[useFileTransport]] is true.
	 * The callback should return a file name which will be used to save the email message.
	 * If not set, the file name will be generated based on the current timestamp.
	 *
	 * The signature of the callback is:
	 *
	 * ~~~
	 * function ($mailer, $message)
	 * ~~~
	 */
	public $fileTransportCallback;

	/**
	 * @var \yii\base\View|array view instance or its array configuration.
	 */
	private $_view = [];

	/**
	 * @param array|View $view view instance or its array configuration that will be used to
	 * render message bodies.
	 * @throws InvalidConfigException on invalid argument.
	 */
	public function setView($view)
	{
		if (!is_array($view) && !is_object($view)) {
			throw new InvalidConfigException('"' . get_class($this) . '::view" should be either object or configuration array, "' . gettype($view) . '" given.');
		}
		$this->_view = $view;
	}

	/**
	 * @return View view instance.
	 */
	public function getView()
	{
		if (!is_object($this->_view)) {
			$this->_view = $this->createView($this->_view);
		}
		return $this->_view;
	}

	/**
	 * Creates view instance from given configuration.
	 * @param array $config view configuration.
	 * @return View view instance.
	 */
	protected function createView(array $config)
	{
		if (!array_key_exists('class', $config)) {
			$config['class'] = View::className();
		}
		return Yii::createObject($config);
	}

	/**
	 * Creates a new message instance and optionally composes its body content via view rendering.
	 *
	 * @param string|array $view the view to be used for rendering the message body. This can be:
	 *
	 * - a string, which represents the view name or path alias for rendering the HTML body of the email.
	 *   In this case, the text body will be generated by applying `strip_tags()` to the HTML body.
	 * - an array with 'html' and/or 'text' elements. The 'html' element refers to the view name or path alias
	 *   for rendering the HTML body, while 'text' element is for rendering the text body. For example,
	 *   `['html' => 'contact-html', 'text' => 'contact-text']`.
	 * - null, meaning the message instance will be returned without body content.
	 *
	 * The view to be rendered can be specified in one of the following formats:
	 *
	 * - path alias (e.g. "@app/mail/contact");
	 * - a relative view name (e.g. "contact"): the actual view file will be resolved by [[findViewFile()]]
	 *
	 * @param array $params the parameters (name-value pairs) that will be extracted and made available in the view file.
	 * @return MessageInterface message instance.
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
	 * Creates a new message instance.
	 * The newly created instance will be initialized with the configuration specified by [[messageConfig]].
	 * If the configuration does not specify a 'class', the [[messageClass]] will be used as the class
	 * of the new message instance.
	 * @return MessageInterface message instance.
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
	 * Sends the given email message.
	 * This method will log a message about the email being sent.
	 * If [[useFileTransport]] is true, it will save the email as a file under [[fileTransportPath]].
	 * Otherwise, it will call [[sendMessage()]] to send the email to its recipient(s).
	 * Child classes should implement [[sendMessage()]] with the actual email sending logic.
	 * @param MessageInterface $message email message instance to be sent
	 * @return boolean whether the message has been sent successfully
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
	 * Sends multiple messages at once.
	 *
	 * The default implementation simply calls [[send()]] multiple times.
	 * Child classes may override this method to implement more efficient way of
	 * sending multiple messages.
	 *
	 * @param array $messages list of email messages, which should be sent.
	 * @return integer number of messages that are successfully sent.
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
	 * Renders the specified view with optional parameters and layout.
	 * The view will be rendered using the [[view]] component.
	 * @param string $view the view name or the path alias of the view file.
	 * @param array $params the parameters (name-value pairs) that will be extracted and made available in the view file.
	 * @param string|boolean $layout layout view name or path alias. If false, no layout will be applied.
	 * @return string the rendering result.
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
	 * Sends the specified message.
	 * This method should be implemented by child classes with the actual email sending logic.
	 * @param MessageInterface $message the message to be sent
	 * @return boolean whether the message is sent successfully
	 */
	abstract protected function sendMessage($message);

	/**
	 * Saves the message as a file under [[fileTransportPath]].
	 * @param MessageInterface $message
	 * @return boolean whether the message is saved successfully
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
	 * @return string the file name for saving the message when [[useFileTransport]] is true.
	 */
	public function generateMessageFileName()
	{
		$time = microtime(true);
		return date('Ymd-His-', $time) . sprintf('%04d', (int)(($time - (int)$time) * 10000)) . '-' . sprintf('%04d', mt_rand(0, 10000)) . '.eml';
	}

	/**
	 * Finds the view file corresponding to the specified relative view name.
	 * This method will return the view file by prefixing the view name with [[viewPath]].
	 * @param string $view a relative view name. The name does NOT start with a slash.
	 * @return string the view file path. Note that the file may not exist.
	 */
	public function findViewFile($view)
	{
		return Yii::getAlias($this->viewPath) . DIRECTORY_SEPARATOR . $view;
	}

	/**
	 * This method is invoked right before mail send.
	 * You may override this method to do last-minute preparation for the message.
	 * If you override this method, please make sure you call the parent implementation first.
	 * @param MessageInterface $message
	 * @return boolean whether to continue sending an email.
	 */
	public function beforeSend($message)
	{
		$event = new MailEvent(['message' => $message]);
		$this->trigger(self::EVENT_BEFORE_SEND, $event);
		return $event->isValid;
	}

	/**
	 * This method is invoked right after mail was send.
	 * You may override this method to do some postprocessing or logging based on mail send status.
	 * If you override this method, please make sure you call the parent implementation first.
	 * @param MessageInterface $message
	 * @param boolean $isSuccessful
	 */
	public function afterSend($message, $isSuccessful)
	{
		$event = new MailEvent(['message' => $message, 'isSuccessful' => $isSuccessful]);
		$this->trigger(self::EVENT_AFTER_SEND, $event);
	}
}
