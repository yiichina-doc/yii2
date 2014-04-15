<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\base;

use Yii;

/**
 * Request 代表一个来自 [[Application]] 的请求处理.
 *
 * @property boolean $isConsoleRequest 该值指示当前请求是否通过控制台.
 * @property string $scriptFile 输入脚本文件路径 (processed w/ realpath()).
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
abstract class Request extends Component
{
	private $_scriptFile;
	private $_isConsoleRequest;

	/**
	 * 解决当前请求到一个路线和相关的参数.
	 * @return array 第一个元素是路线, 第二个是相关的参数.
	 */
	abstract public function resolve();

	/**
	 * 返回一个值,该值指示当前请求是否通过命令行
	 * @return boolean 该值指示当前请求是否通过控制台
	 */
	public function getIsConsoleRequest()
	{
		return $this->_isConsoleRequest !== null ? $this->_isConsoleRequest : PHP_SAPI === 'cli';
	}

	/**
	 * 设置值,该值指示当前请求是否通过命令行
	 * @param boolean $value 该值指示当前请求是否通过命令行
	 */
	public function setIsConsoleRequest($value)
	{
		$this->_isConsoleRequest = $value;
	}

	/**
	 * 返回输入脚本文件路径.
	 * @return string 输入脚本文件路径 (processed w/ realpath())
	 * @throws InvalidConfigException 如果入口文件路径不能自动确定.
	 */
	public function getScriptFile()
	{
		if ($this->_scriptFile === null) {
			if (isset($_SERVER['SCRIPT_FILENAME'])) {
				$this->setScriptFile($_SERVER['SCRIPT_FILENAME']);
			} else {
				throw new InvalidConfigException('Unable to determine the entry script file path.');
			}
		}
		return $this->_scriptFile;
	}

	/**
	 * 设置输入脚本文件路径.
	 * 输入脚本文件路径通常可以基于 `SCRIPT_FILENAME` 服务器变量决定的.
	 * 然而, 对于某些服务器配置, 这可能不是正确的或可行的.
	 * 这样的入口脚本文件的路径，可以手动指定.
	 * @param string $value 输入脚本文件路径. 这可以是一个文件路径或路径别名.
	 * @throws InvalidConfigException 如果提供的输入脚本文件路径无效.
	 */
	public function setScriptFile($value)
	{
		$scriptFile = realpath(Yii::getAlias($value));
		if ($scriptFile !== false && is_file($scriptFile)) {
			$this->_scriptFile = $scriptFile;
		} else {
			throw new InvalidConfigException('Unable to determine the entry script file path.');
		}
	}
}
