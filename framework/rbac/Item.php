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
 * Item 代表一个授权项目.
 * 一个授权项目可以是一个操作、一个任务或角色
 * 他们形成了一个授权等级. 较高等级的授权项目
 * 继承较低等级的项目权限.
 * 用户可以指定一个或多个授权项目 (调用 [[Assignment]] 指定).
 * 他只可以操作已经指定的授权项目.
 *
 * @property Item[] $children 所有这些项目的子项目。此属性为只读.
 * @property string $name 项目名称.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Alexander Kochetov <creocoder@gmail.com>
 * @since 2.0
 */
class Item extends Object
{
	const TYPE_OPERATION = 0;
	const TYPE_TASK = 1;
	const TYPE_ROLE = 2;

	/**
	 * @var Manager 项目管理者
	 */
	public $manager;
	/**
	 * @var string 项目描述
	 */
	public $description;
	/**
	 * @var string 与此项关联的业务规则
	 */
	public $bizRule;
	/**
	 * @var mixed 与此项关联的附加数据
	 */
	public $data;
	/**
	 * @var integer 授权项目类型。这可能是0（操作），1（任务）或2（角色）.
	 */
	public $type;

	private $_name;
	private $_oldName;


	/**
	 * 验证一个项目是否存在这个权限结构中
     * （译者注 原文Checks to see if the specified item is within 
     * the hierarchy starting from this item）.
	 * 这是一个内部使用的方法，是通过[[Manager::checkAccess()]]方法实现的
     * （译者注 原文This method is expected to be internally used 
     * by the actual implementations of the [[Manager::checkAccess()]]）
	 * @param string $itemName 被检查的项目名
	 * @param array $params 判断规则
     * （译者注 原文the parameters to be passed to business rule evaluation）
	 * @return boolean 指定的项是否在指定的权限结构中.
	 */
	public function checkAccess($itemName, $params = [])
	{
		Yii::trace('Checking permission: ' . $this->_name, __METHOD__);
		if ($this->manager->executeBizRule($this->bizRule, $params, $this->data)) {
			if ($this->_name == $itemName) {
				return true;
			}
			foreach ($this->manager->getItemChildren($this->_name) as $item) {
				if ($item->checkAccess($itemName, $params)) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * @return string 项目名
	 */
	public function getName()
	{
		return $this->_name;
	}

	/**
	 * @param string $value 项目名
	 */
	public function setName($value)
	{
		if ($this->_name !== $value) {
			$this->_oldName = $this->_name;
			$this->_name = $value;
		}
	}

	/**
	 * 添加一个子项.
	 * @param string $name 子项名
	 * @return boolean 添加是否成功
	 * @throws \yii\base\Exception 如果子项或父项不存在或存在循环，则抛出\yii\base\Exception异常.
	 * @see Manager::addItemChild
	 */
	public function addChild($name)
	{
		return $this->manager->addItemChild($this->_name, $name);
	}

	/**
	 * 删除一个子项的父子关系.
	 * 注意，子项目不被删除。只有父子关系被删除.
	 * @param string $name 子项名
	 * @return boolean 删除是否成功
	 * @see Manager::removeItemChild
	 */
	public function removeChild($name)
	{
		return $this->manager->removeItemChild($this->_name, $name);
	}

	/**
	 * 检查授权项是否存在指定的子项
	 * @param string $name 子项名
	 * @return boolean 是否存在
	 * @see Manager::hasItemChild
	 */
	public function hasChild($name)
	{
		return $this->manager->hasItemChild($this->_name, $name);
	}

	/**
	 * 返回授权项的子项.
	 * @return Item[] 授权项的所以子项.
	 * @see Manager::getItemChildren
	 */
	public function getChildren()
	{
		return $this->manager->getItemChildren($this->_name);
	}

	/**
	 * 授权一个授权项给用户.
	 * @param mixed $userId 用户ID (see [[\yii\web\User::id]])
	 * @param string $bizRule 业务规则，当调用 [[checkAccess()]] 时被使用
	 * 针对这个特定项目.
	 * @param mixed $data 这项任务相关的附加数据
	 * @return Assignment 授权信息.
	 * @throws \yii\base\Exception 如果这个授权项已经分配给用户抛出一个\yii\base\Exception
	 * @see Manager::assign
	 */
	public function assign($userId, $bizRule = null, $data = null)
	{
		return $this->manager->assign($userId, $this->_name, $bizRule, $data);
	}

	/**
	 * 解除用户授权.
	 * @param mixed $userId 用户 ID (see [[\yii\web\User::id]])
	 * @return boolean 是否成功
	 * @see Manager::revoke
	 */
	public function revoke($userId)
	{
		return $this->manager->revoke($userId, $this->_name);
	}

	/**
	 * 返回是否将该项授权给该用户.
	 * @param mixed $userId 用户ID (see [[\yii\web\User::id]])
	 * @return boolean 项目是否被授权给用户.
	 * @see Manager::isAssigned
	 */
	public function isAssigned($userId)
	{
		return $this->manager->isAssigned($userId, $this->_name);
	}

	/**
	 * 返回项目的授权信息.
	 * @param mixed $userId 用户id (see [[\yii\web\User::id]])
	 * @return Assignment 授权信息. 如果返回 Null
	 * 则此项没有授权给用户.
	 * @see Manager::getAssignment
	 */
	public function getAssignment($userId)
	{
		return $this->manager->getAssignment($userId, $this->_name);
	}

	/**
	 * 保存一个授权项.
	 */
	public function save()
	{
		$this->manager->saveItem($this, $this->_oldName);
		$this->_oldName = null;
	}
}
