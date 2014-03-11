<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\rbac;

use Yii;
use yii\base\Exception;
use yii\base\InvalidCallException;
use yii\base\InvalidParamException;

/**
 * PhpManager 代表一个授权管理器,存储授权
 * 在一个PHP脚本文件信息.
 *
 * 授权数据将被保存并从文件加载
 * 指定 [[authFile]],默认为 'protected/data/rbac.php'.
 *
 * PhpManager 主要适用于授权数据不太大
 * (例如，一个人博客系统的授权数据).
 * 用 [[DbManager]] 处理更复杂的授权数据.
 *
 * @property Item[] $items 特定类型的授权项目。此属性为只读.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Alexander Kochetov <creocoder@gmail.com>
 * @since 2.0
 */
class PhpManager extends Manager
{
	/**
	 * @var string 包含授权项目数据的PHP脚本的路径.
	 * 这可以是一个文件路径或文件路径别名.
	 * 确保文件可写，授权需要修改该文件.
	 * @见 loadFromFile()
	 * @见 saveToFile()
	 */
	public $authFile = '@app/data/rbac.php';

	private $_items = []; // itemName => item
	private $_children = []; // itemName, childName => child
	private $_assignments = []; // userId, itemName => assignment

	/**
	 * 初始化应用程序组件。
	 * 这个方法覆盖父类
	 * 实现从PHP脚本加载授权项目数据.
	 */
	public function init()
	{
		parent::init();
		$this->authFile = Yii::getAlias($this->authFile);
		$this->load();
	}

	/**
	 * 执行指定的用户访问检查.
	 * @param mixed $userId 用户id. 这可以是一个整数或字符串
	 * @param string $itemName 需要权限检查的授权项目名
	 * 一个用户的唯一标识符. 见 [[\yii\web\User::id]].
	 * @param array $params 键(名称)-值对，可以通过业务规则关联
	 * 将任务和角色分配给用户. 参数'userId'被添加到数组里
	 * 这个数组将包含`$userId`的值
	 * @return boolean whether 用户是否有权使用该操作.
	 */
	public function checkAccess($userId, $itemName, $params = [])
	{
		if (!isset($this->_items[$itemName])) {
			return false;
		}
		/** @var Item $item */
		$item = $this->_items[$itemName];
		Yii::trace('Checking permission: ' . $item->getName(), __METHOD__);
		if (!isset($params['userId'])) {
			$params['userId'] = $userId;
		}
		if ($this->executeBizRule($item->bizRule, $params, $item->data)) {
			if (in_array($itemName, $this->defaultRoles)) {
				return true;
			}
			if (isset($this->_assignments[$userId][$itemName])) {
				/** @var Assignment $assignment */
				$assignment = $this->_assignments[$userId][$itemName];
				if ($this->executeBizRule($assignment->bizRule, $params, $assignment->data)) {
					return true;
				}
			}
			foreach ($this->_children as $parentName => $children) {
				if (isset($children[$itemName]) && $this->checkAccess($userId, $parentName, $params)) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * 增加一个项(授权项)的子项.
	 * @param string $itemName 父项名
	 * @param string $childName 子项名
	 * @return boolean 项目是否添加成功
	 * @如果父项或子项不存在则抛出异常.
	 * @如果该子项已经存在或者是一个循环，则抛出 InvalidCallException 异常.
	 */
	public function addItemChild($itemName, $childName)
	{
		if (!isset($this->_items[$childName], $this->_items[$itemName])) {
			throw new Exception("Either '$itemName' or '$childName' does not exist.");
		}
		/** @var Item $child */
		$child = $this->_items[$childName];
		/** @var Item $item */
		$item = $this->_items[$itemName];
		$this->checkItemChildType($item->type, $child->type);
		if ($this->detectLoop($itemName, $childName)) {
			throw new InvalidCallException("Cannot add '$childName' as a child of '$itemName'. A loop has been detected.");
		}
		if (isset($this->_children[$itemName][$childName])) {
			throw new InvalidCallException("The item '$itemName' already has a child '$childName'.");
		}
		$this->_children[$itemName][$childName] = $this->_items[$childName];
		return true;
	}

	/**
	 * 删除一个子项.
	 * 注意，子项目不被删除。只有父子关系被删除.
	 * @param string $itemName 父项名
	 * @param string $childName 子项名
	 * @return boolean 项目是否删除成功
	 */
	public function removeItemChild($itemName, $childName)
	{
		if (isset($this->_children[$itemName][$childName])) {
			unset($this->_children[$itemName][$childName]);
			return true;
		} else {
			return false;
		}
	}

	/**
	 * 判断是否存在某一子项.
	 * @param string $itemName 父项名
	 * @param string $childName 子项名
	 * @return boolean 子项是否存在
	 */
	public function hasItemChild($itemName, $childName)
	{
		return isset($this->_children[$itemName][$childName]);
	}

	/**
	 * 返回指定项目的子项.
	 * @param mixed $names 父项名. 可以是一个字符串或一个数组.
	 * 后者是一个列表中的项目名称.
	 * @return Item[] 所有的子项目
	 */
	public function getItemChildren($names)
	{
		if (is_string($names)) {
			return isset($this->_children[$names]) ? $this->_children[$names] : [];
		}

		$children = [];
		foreach ($names as $name) {
			if (isset($this->_children[$name])) {
				$children = array_merge($children, $this->_children[$name]);
			}
		}
		return $children;
	}

	/**
	 * 给用户授权
	 * @param mixed $userId 用户id (见 [[\yii\web\User::id]])
	 * @param string $itemName 项目名
	 * @param string $bizRule 业务规则当要执行 [[checkAccess()]] 时被调用
	 * 针对这个特定的授权项目。
	 * @param mixed $data 这项任务相关的附加数据
	 * @return Assignment 授权信息.
	 * @如果这个项不存在或者这个项已经分配给这个用户，则抛出 InvalidParamException 异常
	 */
	public function assign($userId, $itemName, $bizRule = null, $data = null)
	{
		if (!isset($this->_items[$itemName])) {
			throw new InvalidParamException("Unknown authorization item '$itemName'.");
		} elseif (isset($this->_assignments[$userId][$itemName])) {
			throw new InvalidParamException("Authorization item '$itemName' has already been assigned to user '$userId'.");
		} else {
			return $this->_assignments[$userId][$itemName] = new Assignment([
				'manager' => $this,
				'userId' => $userId,
				'itemName' => $itemName,
				'bizRule' => $bizRule,
				'data' => $data,
			]);
		}
	}

	/**
	 * 解除用户授权.
	 * @param mixed $userId 用户 ID (见 [[\yii\web\User::id]])
	 * @param string $itemName 权限名
	 * @return boolean 是否解除成功
	 */
	public function revoke($userId, $itemName)
	{
		if (isset($this->_assignments[$userId][$itemName])) {
			unset($this->_assignments[$userId][$itemName]);
			return true;
		} else {
			return false;
		}
	}

	/**
	 * 解除一个用户的所以权限.
	 * @param mixed $userId 用户 ID (见 [[\yii\web\User::id]])
	 * @return boolean 是否解除成功
	 */
	public function revokeAll($userId)
	{
		if (isset($this->_assignments[$userId]) && is_array($this->_assignments[$userId])) {
			foreach ($this->_assignments[$userId] as $itemName => $value)
				unset($this->_assignments[$userId][$itemName]);
			return true;
		} else {
			return false;
		}
	}

	/**
	 * 检查用户是否被授予某个权限.
	 * @param mixed $userId 用户 ID (见 [[\yii\web\User::id]])
	 * @param string $itemName 权限名
	 * @return boolean 某权限是否授予用户.
	 */
	public function isAssigned($userId, $itemName)
	{
		return isset($this->_assignments[$userId][$itemName]);
	}

	/**
	 * 获取用户指定授权项目信息.
	 * @param mixed $userId 用户 ID (见 [[\yii\web\User::id]])
	 * @param string $itemName 项目名
	 * @return Assignment 项目信息. 如果返回null
	 * 表示没有授权给用户.
	 */
	public function getAssignment($userId, $itemName)
	{
		return isset($this->_assignments[$userId][$itemName]) ? $this->_assignments[$userId][$itemName] : null;
	}

	/**
	 * 返回指定的用户的项目信息.
	 * @param mixed $userId 用户 ID (见 [[\yii\web\User::id]])
	 * @return Assignment[] 为用户分配的项目信息. 如果返回空
	 * 说明用户没有项目.
	 */
	public function getAssignments($userId)
	{
		return isset($this->_assignments[$userId]) ? $this->_assignments[$userId] : [];
	}

	/**
	 * 返回指定的用户和类型的授权项目表.
	 * @param mixed $userId 用户 ID. 默认为 null，表示返回所以权限项，
	 * 包括没有授予用户的.
	 * @param integer $type 权限项类型 (0: 操作, 1: 任务, 2: 角色). 默认为 null,
	 * 返回所以项，无论什么类型.
	 * @return Item[] 特定用户特定类型的授权项目.
	 */
	public function getItems($userId = null, $type = null)
	{
		if ($userId === null && $type === null) {
			return $this->_items;
		}
		$items = [];
		if ($userId === null) {
			foreach ($this->_items as $name => $item) {
				/** @var Item $item */
				if ($item->type == $type) {
					$items[$name] = $item;
				}
			}
		} elseif (isset($this->_assignments[$userId])) {
			foreach ($this->_assignments[$userId] as $assignment) {
				/** @var Assignment $assignment */
				$name = $assignment->itemName;
				if (isset($this->_items[$name]) && ($type === null || $this->_items[$name]->type == $type)) {
					$items[$name] = $this->_items[$name];
				}
			}
		}
		return $items;
	}

	/**
	 * 创建一个授权项目.
	 * 一个授权项目表示一个动作的权限(e.g. creating a post).
	 * 它有三种类型: 操作, 任务 and 角色.
	 * 授权项目分级别，高级别的授权项目会代表低级别的
	 * 
	 * @param string $name 项目名. 必须是唯一的标示符.
	 * @param integer $type 项目类型 (0: 操作, 1: 任务, 2: 角色).
	 * @param string $description 项目描述
	 * @param string $bizRule 与项目相关的业务规则. 这是一段php代码，
	 * 当调用[[checkAccess()]]时被执行.
	 * @param mixed $data 与项目相关的附加数据.
	 * @return Item 授权项目
	 * @如果项目名已经存在，抛出一个异常(Exception)
	 */
	public function createItem($name, $type, $description = '', $bizRule = null, $data = null)
	{
		if (isset($this->_items[$name])) {
			throw new Exception('Unable to add an item whose name is the same as an existing item.');
		}
		return $this->_items[$name] = new Item([
			'manager' => $this,
			'name' => $name,
			'type' => $type,
			'description' => $description,
			'bizRule' => $bizRule,
			'data' => $data,
		]);
	}

	/**
	 * 移除指定的授权项目.
	 * @param string $name 被移除的授权项目名
	 * @return boolean 授权项目存在并且被删除
	 * (译者注：不存在返回false，其他都是true)
	 */
	public function removeItem($name)
	{
		if (isset($this->_items[$name])) {
			foreach ($this->_children as &$children) {
				unset($children[$name]);
			}
			foreach ($this->_assignments as &$assignments) {
				unset($assignments[$name]);
			}
			unset($this->_items[$name]);
			return true;
		} else {
			return false;
		}
	}

	/**
	 * 返回指定名称的授权项目.
	 * @param string $name 授权项目名
	 * @return Item 授权项目，如果是null表示没有找到.
	 */
	public function getItem($name)
	{
		return isset($this->_items[$name]) ? $this->_items[$name] : null;
	}

	/**
	 * 保存一个授权项.
	 * @param Item $item 被保存的授权项.
	 * @param string $oldName 旧的授权项名. 如果为 null, 这意味着授权项目名称不改变.
	 * @如果已经有了相同的授权项目名，抛出一个 InvalidParamException 异常
	 */
	public function saveItem($item, $oldName = null)
	{
		if ($oldName !== null && ($newName = $item->getName()) !== $oldName) { // name changed
			if (isset($this->_items[$newName])) {
				throw new InvalidParamException("Unable to change the item name. The name '$newName' is already used by another item.");
			}
			if (isset($this->_items[$oldName]) && $this->_items[$oldName] === $item) {
				unset($this->_items[$oldName]);
				$this->_items[$newName] = $item;
				if (isset($this->_children[$oldName])) {
					$this->_children[$newName] = $this->_children[$oldName];
					unset($this->_children[$oldName]);
				}
				foreach ($this->_children as &$children) {
					if (isset($children[$oldName])) {
						$children[$newName] = $children[$oldName];
						unset($children[$oldName]);
					}
				}
				foreach ($this->_assignments as &$assignments) {
					if (isset($assignments[$oldName])) {
						$assignments[$newName] = $assignments[$oldName];
						unset($assignments[$oldName]);
					}
				}
			}
		}
	}

	/**
	 * 保存更改的权限分配.
	 * @param Assignment $assignment 已经改变的权限分配.
	 */
	public function saveAssignment($assignment)
	{
	}

	/**
	 * 持久化保存授权配置.
	 * If any change is made to the authorization data, please make
	 * 如果有任何更改授权数据，请确保此方法调用你保存更改的数据写入持久存储.
	 */
	public function save()
	{
		$items = [];
		foreach ($this->_items as $name => $item) {
			/** @var Item $item */
			$items[$name] = [
				'type' => $item->type,
				'description' => $item->description,
				'bizRule' => $item->bizRule,
				'data' => $item->data,
			];
			if (isset($this->_children[$name])) {
				foreach ($this->_children[$name] as $child) {
					/** @var Item $child */
					$items[$name]['children'][] = $child->getName();
				}
			}
		}

		foreach ($this->_assignments as $userId => $assignments) {
			foreach ($assignments as $name => $assignment) {
				/** @var Assignment $assignment */
				if (isset($items[$name])) {
					$items[$name]['assignments'][$userId] = [
						'bizRule' => $assignment->bizRule,
						'data' => $assignment->data,
					];
				}
			}
		}

		$this->saveToFile($items, $this->authFile);
	}

	/**
	 * 加载授权数据.
	 */
	public function load()
	{
		$this->clearAll();

		$items = $this->loadFromFile($this->authFile);

		foreach ($items as $name => $item) {
			$this->_items[$name] = new Item([
				'manager' => $this,
				'name' => $name,
				'type' => $item['type'],
				'description' => $item['description'],
				'bizRule' => $item['bizRule'],
				'data' => $item['data'],
			]);
		}

		foreach ($items as $name => $item) {
			if (isset($item['children'])) {
				foreach ($item['children'] as $childName) {
					if (isset($this->_items[$childName])) {
						$this->_children[$name][$childName] = $this->_items[$childName];
					}
				}
			}
			if (isset($item['assignments'])) {
				foreach ($item['assignments'] as $userId => $assignment) {
					$this->_assignments[$userId][$name] = new Assignment([
						'manager' => $this,
						'userId' => $userId,
						'itemName' => $name,
						'bizRule' => $assignment['bizRule'],
						'data' => $assignment['data'],
					]);
				}
			}
		}
	}

	/**
	 * 清除所有授权数据.
	 */
	public function clearAll()
	{
		$this->clearAssignments();
		$this->_children = [];
		$this->_items = [];
	}

	/**
	 * 删除所有授权分配.
	 */
	public function clearAssignments()
	{
		$this->_assignments = [];
	}

	/**
	 * 检查是否有授权项目层次循环.
	 * @param string $itemName 父项目名
	 * @param string $childName 添加到层次结构的子项目名称
	 * @return boolean 是否存在循环
	 */
	protected function detectLoop($itemName, $childName)
	{
		if ($childName === $itemName) {
			return true;
		}
		if (!isset($this->_children[$childName], $this->_items[$itemName])) {
			return false;
		}
		foreach ($this->_children[$childName] as $child) {
			/** @var Item $child */
			if ($this->detectLoop($itemName, $child->getName())) {
				return true;
			}
		}
		return false;
	}

	/**
	 * 加载项目授权数据从php脚本文件.
	 * @param string $file 文件路径.
	 * @return array 授权项目数据
	 * @见 saveToFile()
	 */
	protected function loadFromFile($file)
	{
		if (is_file($file)) {
			return require($file);
		} else {
			return [];
		}
	}

	/**
	 * 保存项目授权数据到php脚本文件.
	 * @param array $data 授权项目数据
	 * @param string $file 文件路径.
	 * @见 loadFromFile()
	 */
	protected function saveToFile($data, $file)
	{
		file_put_contents($file, "<?php\nreturn " . var_export($data, true) . ";\n", LOCK_EX);
	}
}
