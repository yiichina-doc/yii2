高级应用程序模板
=============================

This template is for large projects developed in teams where backend is divided from frontend, application is deployed
to multiple servers etc. This application template also goes a bit further regarding features and provides essential
database, signup and password restore out of the box.

安装
------------

### 通过Composer安装

如果你没有安装 [Composer](http://getcomposer.org/)， 你可以从
[http://getcomposer.org/](http://getcomposer.org/) 下载或者在 Linux/Unix/MacOS 上运行如下命令：

~~~
curl -s http://getcomposer.org/installer | php
~~~

然后，您可以使用下面的命令来安装该应用程序：

~~~
php composer.phar create-project --prefer-dist --stability=dev yiisoft/yii2-app-advanced /path/to/yii-application
~~~

入门
---------------

在安装应用程序时，您必须进行以下步骤来初始化安装的应用程序。所有这些你只需要做一次。

1. 执行 `init` 命令并选择 `dev` 做为环境。
   ```
   php /path/to/yii-application/init
   ```
2. Create a new database and adjust the `components.db` configuration in `common/config/main-local.php` accordingly.
3. 适用迁移的控制台命令 `yii migrate`。
4. 设置你的Web服务器文档根目录：

- for frontend `/path/to/yii-application/frontend/web/` and using the URL `http://frontend/`
- for backend `/path/to/yii-application/backend/web/` and using the URL `http://backend/`

目录结构
-------------------

根目录包含以下子目录：

- `backend` - 后端Web应用程序。
- `common` - 适用于所有应用程序文件。
- `console` - 控制台应用程序。
- `environments` - 环境的配置。
- `frontend` - 前端Web应用程序。

根目录中包含的一系列文件。

- `.gitignore` contains a list of directories ignored by git version system. If you need something never get to your source
  code repository, add it there.
- `composer.json` - Composer config described in detail below.
- `init` - initialization script described in "Composer config described in detail below".
- `init.bat` - 同样适用于Windows。
- `LICENSE.md` - license info. Put your project license there. Especially when opensourcing.
- `README.md` - basic info about installing template. Consider replacing it with information about your project and its
  installation.
- `requirements.php` - Yii requirements checker.
- `yii` - console application bootstrap.
- `yii.bat` - 同样适用于Windows。

应用
------------

There are three applications in advanced template: frontend, backend and console. Frontend is typically what is presented
to end user, the project itself. Backend is admin panel, analytics and such functionality. Console is typically used for
cron jobs and low-level server management. Also it's used during application deployment and handles migrations and assets.

There's also a `common` directory that contains files used by more than one application. For example, `User` model.

frontend and backend are both web applications and both contain `web` directory. That's the webroot you should point your
webserver to.

Each application has its own namespace and alias corresponding to its name. Same applies to common directory.

配置和环境
------------------------------

There are multiple problems with straightforward approach to configuration:

- Each team member has its own configuration options. Committing such config will affect other team members.
- Production database password and API keys should not end up in repository.
- There are multiple servers: development, testing, production. Each should have its own configuration.
- Defining all configuration options for each case is very repetitive and takes too much time to maintain.

In order to solve these issues Yii introduces environments concept that is very simple. Each environment is represented
by a set of files under `environments` directory. `init` command is used to switch between these. What it really does is
just copying everything from environment directory over the root directory where all applications are.

Typically environment contains application bootstrap files such as `index.php` and config files suffixed with
`-local.php`. These are added to `.gitignore` and never added to source code repository.

In order to avoid duplication configurations are overriding each other. For example, frontend reads configuration in the
following order:

- `common/config/main.php`
- `common/config/main-local.php`
- `frontend/config/main.php`
- `frontend/config/main-local.php`

Parameters are read in the following order:

- `common/config/params.php`
- `common/config/params-local.php`
- `frontend/config/params.php`
- `frontend/config/params-local.php`

后者配置文件覆盖了前者。

下面是完整的方案：

![高级应用程序的配置](images/advanced-app-configs.png)

配置Composer
--------------------

After application template is installed it's a good idea to adjust default `composer.json` that can be found in the root
directory:

```json
{
	"name": "yiisoft/yii2-app-advanced",
	"description": "Yii 2 Advanced Application Template",
	"keywords": ["yii", "framework", "advanced", "application template"],
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
			"backend/runtime",
			"backend/web/assets",

			"console/runtime",
			"console/migrations",

			"frontend/runtime",
			"frontend/web/assets"
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

从后端创建前端的链接
---------------------------------------

Often it's required to create links from backend application to frontend application. Since frontend application may
contain its own URL manager rules you need to duplicate that for backend application naming it differently:

```php
return [
	'components' => [
		'urlManager' => [
			// here is your normal backend url manager config
		],
		'urlManagerFrontend' => [
			// here is your frontend URL manager config
		],

	],
];
```

After it is done you can get URL poiting to frontend like the following:

```php
echo Yii::$app->urlManagerFrontend->createUrl(...);
```
