<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\widgets;

use yii\base\InvalidConfigException;
use yii\base\Widget;

/**
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class ContentDecorator extends Widget
{
	/**
	 * @var string the view file that will be used to decorate the content enclosed by this widget.
	 * 这可以被指定为视图文件路径或路径别名。
	 */
	public $viewFile;
	/**
	 * @var array the parameters (name => value) to be extracted and made available in the decorative view.
	 */
	public $params = [];

	/**
	 * 开始记录剪辑。
	 */
	public function init()
	{
		if ($this->viewFile === null) {
			throw new InvalidConfigException('ContentDecorator::viewFile must be set.');
		}
		ob_start();
		ob_implicit_flush(false);
	}

	/**
	 * 结束记录剪辑。
	 * 这个方法结束输出缓冲，保存渲染结果到（一个可在）控制器中（使用）的命名剪辑中。
	 */
	public function run()
	{
		$params = $this->params;
		$params['content'] = ob_get_clean();
		// render under the existing context
		echo $this->view->renderFile($this->viewFile, $params);
	}
}
