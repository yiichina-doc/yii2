<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\web;

use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;

/**
 * User is the class for the "user" application component that manages the user authentication status.
 *
 * You may use [[isGuest]] to determine whether the current user is a guest or not.
 * If the user is a guest, the [[identity]] property would return null. Otherwise, it would
 * be an instance of [[IdentityInterface]].
 *
 * You may call various methods to change the user authentication status:
 *
 * - [[login()]]: sets the specified identity and remembers the authentication status in session and cookie.
 * - [[logout()]]: marks the user as a guest and clears the relevant information from session and cookie.
 * - [[setIdentity()]]: changes the user identity without touching session or cookie.
 *   This is best used in stateless RESTful API implementation.
 *
 * Note that User only maintains the user authentication status. It does NOT handle how to authenticate
 * a user. The logic of how to authenticate a user should be done in the class implementing [[IdentityInterface]].
 * You are also required to set [[identityClass]] with the name of this class.
 *
 * User is configured as an application component in [[\yii\web\Application]] by default.
 * You can access that instance via `Yii::$app->user`.
 *
 * You can modify its configuration by adding an array to your application config under `components`
 * as it is shown in the following example:
 *
 * ~~~
 * 'user' => [
 *     'identityClass' => 'app\models\User', // User must implement the IdentityInterface
 *     'enableAutoLogin' => true,
 *     // 'loginUrl' => ['user/login'],
 *     // ...
 * ]
 * ~~~
 *
 * @property string|integer $id The unique identifier for the user. If null, it means the user is a guest.
 * This property is read-only.
 * @property IdentityInterface $identity The identity object associated with the currently logged user. Null
 * is returned if the user is not logged in (not authenticated).
 * @property boolean $isGuest Whether the current user is a guest. This property is read-only.
 * @property string $returnUrl The URL that the user should be redirected to after login. Note that the type
 * of this property differs in getter and setter. See [[getReturnUrl()]] and [[setReturnUrl()]] for details.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class User extends Component
{
	const EVENT_BEFORE_LOGIN = 'beforeLogin';
	const EVENT_AFTER_LOGIN = 'afterLogin';
	const EVENT_BEFORE_LOGOUT = 'beforeLogout';
	const EVENT_AFTER_LOGOUT = 'afterLogout';

	/**
	 * @var string the class name of the [[identity]] object.
	 */
	public $identityClass;
	/**
	 * @var boolean whether to enable cookie-based login. Defaults to false.
	 */
	public $enableAutoLogin = false;
	/**
	 * @var string|array the URL for login when [[loginRequired()]] is called.
	 * If an array is given, [[UrlManager::createUrl()]] will be called to create the corresponding URL.
	 * The first element of the array should be the route to the login action, and the rest of
	 * the name-value pairs are GET parameters used to construct the login URL. For example,
	 *
	 * ~~~
	 * ['site/login', 'ref' => 1]
	 * ~~~
	 *
	 * If this property is null, a 403 HTTP exception will be raised when [[loginRequired()]] is called.
	 */
	public $loginUrl = ['site/login'];
	/**
	 * @var array the configuration of the identity cookie. This property is used only when [[enableAutoLogin]] is true.
	 * @see Cookie
	 */
	public $identityCookie = ['name' => '_identity', 'httpOnly' => true];
	/**
	 * @var integer the number of seconds in which the user will be logged out automatically if he
	 * remains inactive. If this property is not set, the user will be logged out after
	 * the current session expires (c.f. [[Session::timeout]]).
	 */
	public $authTimeout;
	/**
	 * @var boolean whether to automatically renew the identity cookie each time a page is requested.
	 * This property is effective only when [[enableAutoLogin]] is true.
	 * When this is false, the identity cookie will expire after the specified duration since the user
	 * is initially logged in. When this is true, the identity cookie will expire after the specified duration
	 * since the user visits the site the last time.
	 * @see enableAutoLogin
	 */
	public $autoRenewCookie = true;
	/**
	 * @var string the session variable name used to store the value of [[id]].
	 */
	public $idParam = '__id';
	/**
	 * @var string the session variable name used to store the value of expiration timestamp of the authenticated state.
	 * This is used when [[authTimeout]] is set.
	 */
	public $authTimeoutParam = '__expire';
	/**
	 * @var string the session variable name used to store the value of [[returnUrl]].
	 */
	public $returnUrlParam = '__returnUrl';

	private $_access = [];


	/**
	 * Initializes the application component.
	 */
	public function init()
	{
		parent::init();

		if ($this->identityClass === null) {
			throw new InvalidConfigException('User::identityClass must be set.');
		}
		if ($this->enableAutoLogin && !isset($this->identityCookie['name'])) {
			throw new InvalidConfigException('User::identityCookie must contain the "name" element.');
		}
	}

	private $_identity = false;

	/**
	 * Returns the identity object associated with the currently logged-in user.
	 * @param boolean $checkSession whether to check the session if the identity has never been determined before.
	 * If the identity is already determined (e.g., by calling [[setIdentity()]] or [[login()]]),
	 * then this parameter has no effect.
	 * @return IdentityInterface the identity object associated with the currently logged-in user.
	 * Null is returned if the user is not logged in (not authenticated).
	 * @see login()
	 * @see logout()
	 */
	public function getIdentity($checkSession = true)
	{
		if ($this->_identity === false) {
			if ($checkSession) {
				$this->renewAuthStatus();
			} else {
				return null;
			}
		}
		return $this->_identity;
	}

	/**
	 * Sets the user identity object.
	 *
	 * This method does nothing else except storing the specified identity object in the internal variable.
	 * For this reason, this method is best used when the user authentication status should not be maintained
	 * by session.
	 *
	 * This method is also called by other more sophisticated methods, such as [[login()]], [[logout()]],
	 * [[switchIdentity()]]. Those methods will try to use session and cookie to maintain the user authentication
	 * status.
	 *
	 * @param IdentityInterface $identity the identity object associated with the currently logged user.
	 */
	public function setIdentity($identity)
	{
		$this->_identity = $identity;
		$this->_access = [];
	}

	/**
	 * Logs in a user.
	 *
	 * By logging in a user, you may obtain the user identity information each time through [[identity]].
	 *
	 * The login status is maintained according to the `$duration` parameter:
	 *
	 * - `$duration == 0`: the identity information will be stored in session and will be available
	 *   via [[identity]] as long as the session remains active.
	 * - `$duration > 0`: the identity information will be stored in session. If [[enableAutoLogin]] is true,
	 *   it will also be stored in a cookie which will expire in `$duration` seconds. As long as
	 *   the cookie remains valid or the session is active, you may obtain the user identity information
	 *   via [[identity]].
	 *
	 * @param IdentityInterface $identity the user identity (which should already be authenticated)
	 * @param integer $duration number of seconds that the user can remain in logged-in status.
	 * Defaults to 0, meaning login till the user closes the browser or the session is manually destroyed.
	 * If greater than 0 and [[enableAutoLogin]] is true, cookie-based login will be supported.
	 * @return boolean whether the user is logged in
	 */
	public function login($identity, $duration = 0)
	{
		if ($this->beforeLogin($identity, false, $duration)) {
			$this->switchIdentity($identity, $duration);
			$id = $identity->getId();
			$ip = Yii::$app->getRequest()->getUserIP();
			Yii::info("User '$id' logged in from $ip with duration $duration.", __METHOD__);
			$this->afterLogin($identity, false, $duration);
		}
		return !$this->getIsGuest();
	}

	/**
	 * Logs in a user by the given access token.
	 * Note that unlike [[login()]], this method will NOT start a session to remember the user authentication status.
	 * Also if the access token is invalid, the user will remain as a guest.
	 * @param string $token the access token
	 * @return IdentityInterface the identity associated with the given access token. Null is returned if
	 * the access token is invalid.
	 */
	public function loginByAccessToken($token)
	{
		/** @var IdentityInterface $class */
		$class = $this->identityClass;
		$identity = $class::findIdentityByAccessToken($token);
		$this->setIdentity($identity);
		return $identity;
	}

	/**
	 * Logs in a user by cookie.
	 *
	 * This method attempts to log in a user using the ID and authKey information
	 * provided by the given cookie.
	 */
	protected function loginByCookie()
	{
		$name = $this->identityCookie['name'];
		$value = Yii::$app->getRequest()->getCookies()->getValue($name);
		if ($value !== null) {
			$data = json_decode($value, true);
			if (count($data) === 3 && isset($data[0], $data[1], $data[2])) {
				list ($id, $authKey, $duration) = $data;
				/** @var IdentityInterface $class */
				$class = $this->identityClass;
				$identity = $class::findIdentity($id);
				if ($identity !== null && $identity->validateAuthKey($authKey)) {
					if ($this->beforeLogin($identity, true, $duration)) {
						$this->switchIdentity($identity, $this->autoRenewCookie ? $duration : 0);
						$ip = Yii::$app->getRequest()->getUserIP();
						Yii::info("User '$id' logged in from $ip via cookie.", __METHOD__);
						$this->afterLogin($identity, true, $duration);
					}
				} elseif ($identity !== null) {
					Yii::warning("Invalid auth key attempted for user '$id': $authKey", __METHOD__);
				}
			}
		}
	}

	/**
	 * Logs out the current user.
	 * This will remove authentication-related session data.
	 * If `$destroySession` is true, all session data will be removed.
	 * @param boolean $destroySession whether to destroy the whole session. Defaults to true.
	 */
	public function logout($destroySession = true)
	{
		$identity = $this->getIdentity();
		if ($identity !== null && $this->beforeLogout($identity)) {
			$this->switchIdentity(null);
			$id = $identity->getId();
			$ip = Yii::$app->getRequest()->getUserIP();
			Yii::info("User '$id' logged out from $ip.", __METHOD__);
			if ($destroySession) {
				Yii::$app->getSession()->destroy();
			}
			$this->afterLogout($identity);
		}
	}

	/**
	 * Returns a value indicating whether the user is a guest (not authenticated).
	 * @return boolean whether the current user is a guest.
	 */
	public function getIsGuest()
	{
		return $this->getIdentity() === null;
	}

	/**
	 * Returns a value that uniquely represents the user.
	 * @return string|integer the unique identifier for the user. If null, it means the user is a guest.
	 */
	public function getId()
	{
		$identity = $this->getIdentity();
		return $identity !== null ? $identity->getId() : null;
	}

	/**
	 * Returns the URL that the user should be redirected to after successful login.
	 * This property is usually used by the login action. If the login is successful,
	 * the action should read this property and use it to redirect the user browser.
	 * @param string|array $defaultUrl the default return URL in case it was not set previously.
	 * If this is null and the return URL was not set previously, [[Application::homeUrl]] will be redirected to.
	 * Please refer to [[setReturnUrl()]] on accepted format of the URL.
	 * @return string the URL that the user should be redirected to after login.
	 * @see loginRequired()
	 */
	public function getReturnUrl($defaultUrl = null)
	{
		$url = Yii::$app->getSession()->get($this->returnUrlParam, $defaultUrl);
		if (is_array($url)) {
			if (isset($url[0])) {
				$route = array_shift($url);
				return Yii::$app->getUrlManager()->createUrl($route, $url);
			} else {
				$url = null;
			}
		}
		return $url === null ? Yii::$app->getHomeUrl() : $url;
	}

	/**
	 * @param string|array $url the URL that the user should be redirected to after login.
	 * If an array is given, [[UrlManager::createUrl()]] will be called to create the corresponding URL.
	 * The first element of the array should be the route, and the rest of
	 * the name-value pairs are GET parameters used to construct the URL. For example,
	 *
	 * ~~~
	 * ['admin/index', 'ref' => 1]
	 * ~~~
	 */
	public function setReturnUrl($url)
	{
		Yii::$app->getSession()->set($this->returnUrlParam, $url);
	}

	/**
	 * Redirects the user browser to the login page.
	 * Before the redirection, the current URL (if it's not an AJAX url) will be
	 * kept as [[returnUrl]] so that the user browser may be redirected back
	 * to the current page after successful login. Make sure you set [[loginUrl]]
	 * so that the user browser can be redirected to the specified login URL after
	 * calling this method.
	 *
	 * Note that when [[loginUrl]] is set, calling this method will NOT terminate the application execution.
	 *
	 * @return Response the redirection response if [[loginUrl]] is set
	 * @throws ForbiddenHttpException the "Access Denied" HTTP exception if [[loginUrl]] is not set
	 */
	public function loginRequired()
	{
		$request = Yii::$app->getRequest();
		if (!$request->getIsAjax()) {
			$this->setReturnUrl($request->getUrl());
		}
		if ($this->loginUrl !== null) {
			return Yii::$app->getResponse()->redirect($this->loginUrl);
		} else {
			throw new ForbiddenHttpException(Yii::t('yii', 'Login Required'));
		}
	}

	/**
	 * This method is called before logging in a user.
	 * The default implementation will trigger the [[EVENT_BEFORE_LOGIN]] event.
	 * If you override this method, make sure you call the parent implementation
	 * so that the event is triggered.
	 * @param IdentityInterface $identity the user identity information
	 * @param boolean $cookieBased whether the login is cookie-based
	 * @param integer $duration number of seconds that the user can remain in logged-in status.
	 * If 0, it means login till the user closes the browser or the session is manually destroyed.
	 * @return boolean whether the user should continue to be logged in
	 */
	protected function beforeLogin($identity, $cookieBased, $duration)
	{
		$event = new UserEvent([
			'identity' => $identity,
			'cookieBased' => $cookieBased,
			'duration' => $duration,
		]);
		$this->trigger(self::EVENT_BEFORE_LOGIN, $event);
		return $event->isValid;
	}

	/**
	 * This method is called after the user is successfully logged in.
	 * The default implementation will trigger the [[EVENT_AFTER_LOGIN]] event.
	 * If you override this method, make sure you call the parent implementation
	 * so that the event is triggered.
	 * @param IdentityInterface $identity the user identity information
	 * @param boolean $cookieBased whether the login is cookie-based
	 * @param integer $duration number of seconds that the user can remain in logged-in status.
	 * If 0, it means login till the user closes the browser or the session is manually destroyed.
	 */
	protected function afterLogin($identity, $cookieBased, $duration)
	{
		$this->trigger(self::EVENT_AFTER_LOGIN, new UserEvent([
			'identity' => $identity,
			'cookieBased' => $cookieBased,
			'duration' => $duration,
		]));
	}

	/**
	 * This method is invoked when calling [[logout()]] to log out a user.
	 * The default implementation will trigger the [[EVENT_BEFORE_LOGOUT]] event.
	 * If you override this method, make sure you call the parent implementation
	 * so that the event is triggered.
	 * @param IdentityInterface $identity the user identity information
	 * @return boolean whether the user should continue to be logged out
	 */
	protected function beforeLogout($identity)
	{
		$event = new UserEvent([
			'identity' => $identity,
		]);
		$this->trigger(self::EVENT_BEFORE_LOGOUT, $event);
		return $event->isValid;
	}

	/**
	 * This method is invoked right after a user is logged out via [[logout()]].
	 * The default implementation will trigger the [[EVENT_AFTER_LOGOUT]] event.
	 * If you override this method, make sure you call the parent implementation
	 * so that the event is triggered.
	 * @param IdentityInterface $identity the user identity information
	 */
	protected function afterLogout($identity)
	{
		$this->trigger(self::EVENT_AFTER_LOGOUT, new UserEvent([
			'identity' => $identity,
		]));
	}

	/**
	 * Renews the identity cookie.
	 * This method will set the expiration time of the identity cookie to be the current time
	 * plus the originally specified cookie duration.
	 */
	protected function renewIdentityCookie()
	{
		$name = $this->identityCookie['name'];
		$value = Yii::$app->getRequest()->getCookies()->getValue($name);
		if ($value !== null) {
			$data = json_decode($value, true);
			if (is_array($data) && isset($data[2])) {
				$cookie = new Cookie($this->identityCookie);
				$cookie->value = $value;
				$cookie->expire = time() + (int)$data[2];
				Yii::$app->getResponse()->getCookies()->add($cookie);
			}
		}
	}

	/**
	 * Sends an identity cookie.
	 * This method is used when [[enableAutoLogin]] is true.
	 * It saves [[id]], [[IdentityInterface::getAuthKey()|auth key]], and the duration of cookie-based login
	 * information in the cookie.
	 * @param IdentityInterface $identity
	 * @param integer $duration number of seconds that the user can remain in logged-in status.
	 * @see loginByCookie()
	 */
	protected function sendIdentityCookie($identity, $duration)
	{
		$cookie = new Cookie($this->identityCookie);
		$cookie->value = json_encode([
			$identity->getId(),
			$identity->getAuthKey(),
			$duration,
		]);
		$cookie->expire = time() + $duration;
		Yii::$app->getResponse()->getCookies()->add($cookie);
	}

	/**
	 * Switches to a new identity for the current user.
	 *
	 * This method may use session and/or cookie to store the user identity information,
	 * according to the value of `$duration`. Please refer to [[login()]] for more details.
	 *
	 * This method is mainly called by [[login()]], [[logout()]] and [[loginByCookie()]]
	 * when the current user needs to be associated with the corresponding identity information.
	 *
	 * @param IdentityInterface $identity the identity information to be associated with the current user.
	 * If null, it means switching the current user to be a guest.
	 * @param integer $duration number of seconds that the user can remain in logged-in status.
	 * This parameter is used only when `$identity` is not null.
	 */
	public function switchIdentity($identity, $duration = 0)
	{
		$session = Yii::$app->getSession();
		if (!YII_ENV_TEST) {
			$session->regenerateID(true);
		}
		$this->setIdentity($identity);
		$session->remove($this->idParam);
		$session->remove($this->authTimeoutParam);
		if ($identity instanceof IdentityInterface) {
			$session->set($this->idParam, $identity->getId());
			if ($this->authTimeout !== null) {
				$session->set($this->authTimeoutParam, time() + $this->authTimeout);
			}
			if ($duration > 0 && $this->enableAutoLogin) {
				$this->sendIdentityCookie($identity, $duration);
			}
		} elseif ($this->enableAutoLogin) {
			Yii::$app->getResponse()->getCookies()->remove(new Cookie($this->identityCookie));
		}
	}

	/**
	 * Updates the authentication status using the information from session and cookie.
	 *
	 * This method will try to determine the user identity using the [[idParam]] session variable.
	 *
	 * If [[authTimeout]] is set, this method will refresh the timer.
	 *
	 * If the user identity cannot be determined by session, this method will try to [[loginByCookie()|login by cookie]]
	 * if [[enableAutoLogin]] is true.
	 */
	protected function renewAuthStatus()
	{
		$session = Yii::$app->getSession();
		$id = $session->getHasSessionId() || $session->getIsActive() ? $session->get($this->idParam) : null;

		if ($id === null) {
			$identity = null;
		} else {
			/** @var IdentityInterface $class */
			$class = $this->identityClass;
			$identity = $class::findIdentity($id);
		}

		$this->setIdentity($identity);

		if ($this->authTimeout !== null && $identity !== null) {
			$expire = $session->get($this->authTimeoutParam);
			if ($expire !== null && $expire < time()) {
				$this->logout(false);
			} else {
				$session->set($this->authTimeoutParam, time() + $this->authTimeout);
			}
		}

		if ($this->enableAutoLogin) {
			if ($this->getIsGuest()) {
				$this->loginByCookie();
			} elseif ($this->autoRenewCookie) {
				$this->renewIdentityCookie();
			}
		}
	}

	/**
	 * Performs access check for this user.
	 * @param string $operation the name of the operation that need access check.
	 * @param array $params name-value pairs that would be passed to business rules associated
	 * with the tasks and roles assigned to the user. A param with name 'userId' is added to
	 * this array, which holds the value of [[id]] when [[DbAuthManager]] or
	 * [[PhpAuthManager]] is used.
	 * @param boolean $allowCaching whether to allow caching the result of access check.
	 * When this parameter is true (default), if the access check of an operation was performed
	 * before, its result will be directly returned when calling this method to check the same
	 * operation. If this parameter is false, this method will always call
	 * [[AuthManager::checkAccess()]] to obtain the up-to-date access result. Note that this
	 * caching is effective only within the same request and only works when `$params = []`.
	 * @return boolean whether the operations can be performed by this user.
	 */
	public function checkAccess($operation, $params = [], $allowCaching = true)
	{
		$auth = Yii::$app->getAuthManager();
		if ($auth === null) {
			return false;
		}
		if ($allowCaching && empty($params) && isset($this->_access[$operation])) {
			return $this->_access[$operation];
		}
		$access = $auth->checkAccess($this->getId(), $operation, $params);
		if ($allowCaching && empty($params)) {
			$this->_access[$operation] = $access;
		}
		return $access;
	}
}
