<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\widgets;

use yii\web\AssetBundle;

/**
 * 该资源包提供了[[Pjax]]widget基类所需要的javascript文件。
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class PjaxAsset extends AssetBundle
{
	public $sourcePath = '@vendor/yiisoft/jquery-pjax';
	public $js = [
		'jquery.pjax.js',
	];
	public $depends = [
		'yii\web\YiiAsset',
	];
}
