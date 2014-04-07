基本的应用程序模板
==========================

基本的Yii应用模板是一个非常适合小型项目或当你刚学这个框架。

基本的应用模板包括四个页面：首页，关于页面，联系我们页面和一个登录页面。
The contact page displays a contact form that users can fill in to submit their inquiries to the webmaster. Assuming the site has access to a mail server and that the administrator's email address is entered in the configuration file, the contact form will work. The same goes for the login page, which allows users to be authenticated before accessing privileged content.

安装
------------

Installation of the framework requires [Composer](http://getcomposer.org/). If you do not have Composer on your system yet, you may download it from
[http://getcomposer.org/](http://getcomposer.org/), or run the following command on Linux/Unix/MacOS:

~~~
curl -s http://getcomposer.org/installer | php
~~~

接下来你可以使用下面的命令创建一个基本Yii应用：

~~~
php composer.phar create-project --prefer-dist --stability=dev yiisoft/yii2-app-basic /path/to/yii-application
~~~

Now set document root directory of your Web server to /path/to/yii-application/web and you should be able to access the application using the URL `http://localhost/`.

目录结构
-------------------

基本的应用程序不划分应用程序目录了。这里是的基本结构：

- `assets` - 应用程序asset文件。
  - `AppAsset.php` - definition of application assets such as CSS, JavaScript etc. Check [Managing assets](assets.md) for
    details.
- `commands` - 控制台控制器。
- `config` - 配置。
- `controllers` - web控制器。
- `models` - 应用模型
- `runtime` - 日志，状态，文件缓存。
- `views` - 视图模板。
- `web` - webroot.

根目录中包含的一系列文件。

- `.gitignore` contains a list of directories ignored by git version system. If you need something never get to your source
code repository, add it there.
- `codeception.yml` - Codeception配置。
- `composer.json` - Composer config described in detail below.
- `LICENSE.md` - 许可信息。Put your project license there. Especially when opensourcing.
- `README.md` - 有关安装模板的基本信息。Consider replacing it with information about your project and its
  installation.
- `requirements.php` - Yii的要求检查器。
- `yii` - console application bootstrap.
- `yii.bat` - 同样适用于Windows。


### 配置

该目录包含配置文件：

- `console.php` - 控制台应用程序的配置。
- `params.php` - 常见的应用程序的参数。
- `web.php` - Web应用程序配置。
- `web-test.php` - 运行功能测试时，Web应用程序配置中使用。

All these files are returning arrays used to configure corresponding application properties. Check
[Configuration](configuration.md) guide section for details.

### 视图

视图目录包含应用程序正在使用的模板。基本的模板有：

```
layouts
	main.php
site
	about.php
	contact.php
	error.php
	index.php
	login.php
```

`layouts` contains HTML layouts i.e. page markup except content: doctype, head section, main menu, footer etc.
The rest are typically controller views. By convention these are located in subdirectories matching controller id. For
`SiteController` views are under `site`. Names of the views themselves are typically match controller action names.
Partials are often named starting with underscore.

### web

此目录是一个webroot。通常情况下，Web服务器指向到它。

```
assets
css
index.php
index-test.php
```

`assets` contains published asset files such as CSS, JavaScript etc. Publishing process is automatic so you don't need
to do anything with this directory other than making sure Yii has enough permissions to write to it.

`css` contains plain CSS files and is useful for global CSS that isn't going to be compressed or merged by assets manager.

`index.php` is the main web application bootstrap and is the central entry point for it. `index-test.php` is the entry
point for functional testing.

配置Composer
--------------------

After application template is installed it's a good idea to adjust default `composer.json` that can be found in the root
directory:

```json
{
	"name": "yiisoft/yii2-app-basic",
	"description": "Yii 2 Basic Application Template",
	"keywords": ["yii", "framework", "basic", "application template"],
	"homepage": "http://www.yiiframework.com/",
	"type": "project",
	"license": "BSD-3-Clause",
	"support": {
		"issues": "https://github.com/yiisoft/yii2/issues?state=open",
		"forum": "http://www.yiiframework.com/forum/",
		"wiki": "http://www.yiiframework.com/wiki/",
		"irc": "irc://irc.freenode.net/yii",
		"source": "https://github.com/yiisoft/yii2"
	},
	"minimum-stability": "dev",
	"require": {
		"php": ">=5.4.0",
		"yiisoft/yii2": "*",
		"yiisoft/yii2-swiftmailer": "*",
		"yiisoft/yii2-bootstrap": "*",
		"yiisoft/yii2-debug": "*",
		"yiisoft/yii2-gii": "*"
	},
	"scripts": {
		"post-create-project-cmd": [
			"yii\\composer\\Installer::setPermission"
		]
	},
	"extra": {
		"writable": [
			"runtime",
			"web/assets"
		],
		"executable": [
			"yii"
		]
	}
}
```

First we're updating basic information. Change `name`, `description`, `keywords`, `homepage` and `support` to match
your project.

Now the interesting part. You can add more packages your application needs to `require` section.
All these packages are coming from [packagist.org](https://packagist.org/) so feel free to browse the website for useful code.

After your `composer.json` is changed you can run `php composer.phar update --prefer-dist`, wait till packages are downloaded and
installed and then just use them. Autoloading of classes will be handled automatically.
