<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\rbac;

use Yii;
use yii\base\Object;

/**
 * Assignment 用户角色分配（授权）.
 * 它包括额外的分配信息[[bizRule]] 和 [[data]] 等.
 * 不需要用new来创建一个Assignment实例.
 * 而是调用 [[Manager::assign()]].
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Alexander Kochetov <creocoder@gmail.com>
 * @since 2.0
 */
class Assignment extends Object
{
	/**
	 * @var Manager 授权项的管理者
	 */
	public $manager;
	/**
	 * @var string 授权的相关业务规则
	 */
	public $bizRule;
	/**
	 * @var mixed 此授权的附加数据
	 */
	public $data;
	/**
	 * @var mixed 用户ID (see [[\yii\web\User::id]]). 不要修改这个属性，他被自动填充.
	 * 要修改授权指定的用户id，你必须先删除授权，然后创建一个新的.
	 */
	public $userId;
	/**
	 * @return string 授权项目名称。不要修改这个属性，他被自动填充.
	 * 要修改授权指定的授权项目，你必须先删除授权，然后创建一个新的.
	 */
	public $itemName;

	/**
	 * 保存更改的权限分配.
	 */
	public function save()
	{
		$this->manager->saveAssignment($this);
	}
}
