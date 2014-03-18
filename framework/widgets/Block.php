<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\widgets;

use yii\base\Widget;

/**
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class Block extends Widget
{
	/**
	 * @var boolean whether to render the block content in place. 默认设置为false，
	 * 意味着所捕获的内容块将不被显示.
	 */
	public $renderInPlace = false;

	/**
	 * 开始记录块。
	 */
	public function init()
	{
		ob_start();
		ob_implicit_flush(false);
	}

	/**
	 * 结束记录块。
	 * 这个方法结束输出缓冲，保存渲染结果到（一个可在）视图中（使用）的命名区块中。
	 */
	public function run()
	{
		$block = ob_get_clean();
		if ($this->renderInPlace) {
			echo $block;
		}
		$this->view->blocks[$this->getId()] = $block;
	}
}
