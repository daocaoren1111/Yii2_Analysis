<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii;

use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;
use yii\base\UnknownClassException;
use yii\di\Container;
use yii\log\Logger;

/**
 * Gets the application start timestamp.
 */
defined('YII_BEGIN_TIME') or define('YII_BEGIN_TIME', microtime(true));
/**
 * 此常量定义框架安装目录。
 */
defined('YII2_PATH') or define('YII2_PATH', __DIR__);
/**
 * 此常量定义应用程序是否应处于调试模式。 默认为false。
 */
defined('YII_DEBUG') or define('YII_DEBUG', false);
/**
 * This constant defines in which environment the application is running. Defaults to 'prod', meaning production environment.
 * You may define this constant in the bootstrap script. The value could be 'prod' (production), 'dev' (development), 'test', 'staging', etc.
 */
defined('YII_ENV') or define('YII_ENV', 'prod');
/**
 * Whether the the application is running in 生产环境.
 */
defined('YII_ENV_PROD') or define('YII_ENV_PROD', YII_ENV === 'prod');
/**
 * Whether the the application is running in 开发环境.
 */
defined('YII_ENV_DEV') or define('YII_ENV_DEV', YII_ENV === 'dev');
/**
 * Whether the the application is running in 测试环境.
 */
defined('YII_ENV_TEST') or define('YII_ENV_TEST', YII_ENV === 'test');

/**
 * 此常量定义是否应启用错误处理。 默认为true。
 */
defined('YII_ENABLE_ERROR_HANDLER') or define('YII_ENABLE_ERROR_HANDLER', true);

/**
 * BaseYii is the core helper class for the Yii framework.
 *
 * Do not use BaseYii directly. Instead, use its child class [[\Yii]] which you can replace to
 * customize methods of BaseYii.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class BaseYii
{
    /**
     * @var Yii自动加载机制使用的数组类映射。
     * 数组键是类名（没有前导反斜杠）， 数组值是相应的类文件路径（或路径别名），
     * 此属性主要影响 autoload() 的工作方式。
     * @see autoload()
     */
    public static $classMap = [];
    /**
     * @var \yii\console\Application|\yii\web\Application 应用程序实例
     */
    public static $app;
    /**
     * @var 数组注册的路径别名
     * @see getAlias()
     * @see setAlias()
     */
    public static $aliases = ['@yii' => __DIR__];
    /**
     * 容器[[createObject（）]]使用的依赖注入（DI）容器。您可以使用[[Container :: set（）]]来设置所需的类依赖关系及其初始属性值。
     * @var Container the dependency injection (DI) container used by [[createObject()]].
     * You may use [[Container::set()]] to set up the needed dependencies of classes and
     * their initial property values.
     * @see createObject()
     * @see Container
     */
    public static $container;


    /**
     * Returns a string representing the current version of the Yii framework.
     * @return string the version of Yii framework
     */
    public static function getVersion()
    {
        return '2.0.15.1';
    }

    /**
     * 将路径别名转换为实际路径。
     * Translates a path alias into an actual path.
     *
     * 转换按照以下步骤完成：
     * The translation is done according to the following procedure:
     *
     * 如果给定的别名不以'@'开头，则返回时不做更改;
     * 1. If the given alias does not start with '@', it is returned back without change; 
     * 否则，查找与给定别名的开头部分匹配的最长注册别名。如果存在，请将给定别名的匹配部分替换为相应的注册路径。
     * 2. Otherwise, look for the longest registered alias that matches the beginning part 否则，查找与给定别名的开头部分匹配的最长注册别名。
     *    of the given alias. If it exists, replace the matching part of the given alias with 如果存在，请将给定别名的匹配部分替换为相应的注册路径。
     *    the corresponding registered path.
     * 3. Throw an exception or return false, depending on the `$throwException` parameter. 抛出异常或返回false，具体取决于`$throwException`参数。
     *
     * For example, by default '@yii' is registered as the alias to the Yii framework directory, 例如，默认情况下，'@yii'被注册为Yii框架目录的别名，
     * say '/path/to/yii'. The alias '@yii/web' would then be translated into '/path/to/yii/web'.  别名'@yii/web'将被翻译成'/path/to/yii/web'。
     *
     * If you have registered two aliases '@foo' and '@foo/bar'. Then translating '@foo/bar/config'  如果您已经注册了两个别名'@foo'和'@foo/bar'。
     * would replace the part '@foo/bar' (instead of '@foo') with the corresponding registered path.  然后转换'@foo/bar/config'会将部分'@foo/bar'（而不是'@foo'）替换为相应的注册路径。
     * This is because the longest alias takes precedence.    这是因为最长的别名优先。
     *
     * However, if the alias to be translated is '@foo/barbar/config', then '@foo' will be replaced
     * instead of '@foo/bar', because '/' serves as the boundary character.  如果要转换的别名是'@foo/barbar/config'，那么'@foo'将被替换而不是'@foo/bar'，因为'/'用作边界字符。
     *
     * Note, this method does not check if the returned path exists or not.  注意，此方法不检查返回的路径是否存在。
     *
     * See the [guide article on aliases](guide:concept-aliases) for more information.
     *
     * @param string $alias the alias to be translated.
     * @param bool $throwException whether to throw an exception if the given alias is invalid.
     * If this is false and an invalid alias is given, false will be returned by this method.
     * @return string|bool the path corresponding to the alias, false if the root alias is not previously registered.
     * @throws InvalidArgumentException if the alias is invalid while $throwException is true.
     * @see setAlias()
     */
    public static function getAlias($alias, $throwException = true)
    {
        if (strncmp($alias, '@', 1)) {
            // not an alias
            return $alias;
        }

        $pos = strpos($alias, '/');
        $root = $pos === false ? $alias : substr($alias, 0, $pos);

        if (isset(static::$aliases[$root])) {
            if (is_string(static::$aliases[$root])) {
                return $pos === false ? static::$aliases[$root] : static::$aliases[$root] . substr($alias, $pos);
            }

            //如果是别名数组，匹配最长别名
            foreach (static::$aliases[$root] as $name => $path) {
                if (strpos($alias . '/', $name . '/') === 0) {
                    return $path . substr($alias, strlen($name));
                }
            }
        }

        if ($throwException) {
            throw new InvalidArgumentException("Invalid path alias: $alias");
        }

        return false;
    }

    /**
     * Returns the root alias part of a given alias.  返回给定别名的根别名部分。
     * A root alias is an alias that has been registered via [[setAlias()]] previously.  根别名是先前通过[[setAlias（）]]注册的别名。
     * If a given alias matches multiple root aliases, the longest one will be returned.  如果给定的别名与多个根别名匹配，则将返回最长的别名。
     * @param string $alias the alias
     * @return string|bool the root alias, or false if no root alias is found
     */
    public static function getRootAlias($alias)
    {
        $pos = strpos($alias, '/');
        $root = $pos === false ? $alias : substr($alias, 0, $pos);

        if (isset(static::$aliases[$root])) {
            if (is_string(static::$aliases[$root])) {
                return $root;
            }

            foreach (static::$aliases[$root] as $name => $path) {
                if (strpos($alias . '/', $name . '/') === 0) {
                    return $name;
                }
            }
        }

        return false;
    }

    /**
     * Registers a path alias.  注册路径别名
     *
     * A path alias is a short name representing a long path (a file path, a URL, etc.)  路径别名是表示长路径（文件路径，URL等）的短名称。
     * For example, we use '@yii' as the alias of the path to the Yii framework directory. 例如，我们使用'@yii'作为Yii框架目录路径的别名。
     *
     * A path alias must start with the character '@' so that it can be easily differentiated
     * from non-alias paths.   路径别名必须以字符“@”开头，以便可以轻松区分非别名路径。
     *
     * Note that this method does not check if the given path exists or not. All it does is
     * to associate the alias with the path. 请注意，此方法不检查给定路径是否存在。 它所做的只是将别名与路径相关联。
     *
     * Any trailing '/' and '\' characters in the given path will be trimmed. 将修剪给定路径中的任何尾随'/'和'\'字符。
     *
     * See the [guide article on aliases](guide:concept-aliases) for more information.
     *
     * @param string $alias the alias name (e.g. "@yii"). It must start with a '@' character.
     * It may contain the forward slash '/' which serves as boundary character when performing
     * alias translation by [[getAlias()]].
     * @param string $path the path corresponding to the alias. If this is null, the alias will
     * be removed. Trailing '/' and '\' characters will be trimmed. This can be
     *
     * - a directory or a file path (e.g. `/tmp`, `/tmp/main.txt`)
     * - a URL (e.g. `http://www.yiiframework.com`)
     * - a path alias (e.g. `@yii/base`). In this case, the path alias will be converted into the
     *   actual path first by calling [[getAlias()]].
     *
     * @throws InvalidArgumentException if $path is an invalid alias.
     * @see getAlias()
     */
    public static function setAlias($alias, $path)
    {
        //如果$alias不是@开头，则将@拼接在开头
        if (strncmp($alias, '@', 1)) {
            $alias = '@' . $alias;
        }
        //获取'/'在 $alias 的位置
        $pos = strpos($alias, '/');
        //如果 $alias 中没有 '/' 那么 $root 等于 alias，否则等于 $alias 中 '/' 前面的部分
        $root = $pos === false ? $alias : substr($alias, 0, $pos);
        if ($path !== null) {
            //如果 $path 的开头有@ ，$path 为已注册的别名的路径，否则去除右边的左右斜线
            $path = strncmp($path, '@', 1) ? rtrim($path, '\\/') : static::getAlias($path);
            //当 $aliases 或者 $aliases的斜线前部分，没有被注册过
            if (!isset(static::$aliases[$root])) {
                //如果 $alias 不存在斜线 , 注册 $alias 等于 $path，否则 就将 $alias 等于 $path 注册为 $alias斜线前部分 下的路径
                if ($pos === false) {
                    static::$aliases[$root] = $path;
                } else {
                    static::$aliases[$root] = [$alias => $path];
                }
            //如果$aliases 或者 $aliases的斜线前部分，之前被注册过，并且是个字符串的话
            } elseif (is_string(static::$aliases[$root])) {
                //如果$aliases没有斜线，直接覆盖。
                if ($pos === false) {
                    static::$aliases[$root] = $path;
                //如果$aliases有斜线，把 $alias=$path 添加到原来斜线前部分的路径数组中
                } else {
                    static::$aliases[$root] = [
                        $alias => $path,
                        $root => static::$aliases[$root],
                    ];
                }
            //如果$aliases 或者 $aliases的斜线前部分，之前被注册过，并且是数组
            } else {
                //直接添加进去，并对数组按照键降序排序
                static::$aliases[$root][$alias] = $path;
                krsort(static::$aliases[$root]);
            }
        //$path为空
        } elseif (isset(static::$aliases[$root])) {
            //如果$aliases或者$aliases斜线前的部分已经注册过还是个数组，删除对应的$alias别名
            if (is_array(static::$aliases[$root])) {
                unset(static::$aliases[$root][$alias]);

             //如果$aliases没有斜线,删除整个别名
            } elseif ($pos === false) {
                unset(static::$aliases[$root]);
            }
        }
    }

    /**
     * Class autoload loader.   类自动加载器。
     *
     * This method is invoked automatically when PHP sees an unknown class.  当PHP看到一个未知类时，会自动调用此方法。
     * The method will attempt to include the class file according to the following procedure:  该方法将尝试根据以下过程包含类文件：
     *
     * 1. Search in [[classMap]];
     * 2. If the class is namespaced (e.g. `yii\base\Component`), it will attempt
     *    to include the file associated with the corresponding path alias
     *    (e.g. `@yii/base/Component.php`);
     *
     * This autoloader allows loading classes that follow the [PSR-4 standard](http://www.php-fig.org/psr/psr-4/)
     * and have its top-level namespace or sub-namespaces defined as path aliases. 此自动装载器允许加载遵循[PSR-4标准]（http://www.php-fig.org/psr/psr-4/）的类，并将其顶级命名空间或子命名空间定义为路径别名。
     *
     *
     * 示例：当定义别名`@yii`和`@yii/bootstrap`时，将使用`@yii/bootstrap`别名加载`yii\bootstrap`命名空间中的类，
     * 该别名指向安装引导程序扩展文件的目录 并且将从yii框架目录加载来自其他`yii`命名空间的所有类。
     *
     * Example: When aliases `@yii` and `@yii/bootstrap` are defined, classes in the `yii\bootstrap` namespace
     * will be loaded using the `@yii/bootstrap` alias which points to the directory where bootstrap extension
     * files are installed and all classes from other `yii` namespaces will be loaded from the yii framework directory.
     *
     * Also the [guide section on autoloading](guide:concept-autoloading).
     *
     * @param string $className the fully qualified class name without a leading backslash "\"
     * @throws UnknownClassException if the class does not exist in the class file
     */
    public static function autoload($className)
    {
        if (isset(static::$classMap[$className])) {
            $classFile = static::$classMap[$className];
            if ($classFile[0] === '@') {
                $classFile = static::getAlias($classFile);
            }
        } elseif (strpos($className, '\\') !== false) {
            $classFile = static::getAlias('@' . str_replace('\\', '/', $className) . '.php', false);
            if ($classFile === false || !is_file($classFile)) {
                return;
            }
        } else {
            return;
        }

        include $classFile;

        //如果开启了debug模式，并且$className不是一个类或者接口或者trait，抛出异常
        if (YII_DEBUG && !class_exists($className, false) && !interface_exists($className, false) && !trait_exists($className, false)) {
            throw new UnknownClassException("Unable to find '$className' in file: $classFile. Namespace missing?");
        }
    }

    /**
     * 使用给定配置创建新对象。
     * Creates a new object using the given configuration.
     *
     * 您可以将此方法视为`new`运算符的增强版本。 该方法支持基于类名，配置数组或匿名函数创建对象。
     *
     * You may view this method as an enhanced version of the `new` operator.
     * The method supports creating an object based on a class name, a configuration array or
     * an anonymous function.
     *
     * 以下是一些使用示例：
     * Below are some usage examples:
     *
     * ```php
     * // create an object using a class name  使用类名创建一个对象
     * $object = Yii::createObject('yii\db\Connection');
     *
     * // create an object using a configuration array  使用配置数组创建对象
     * $object = Yii::createObject([
     *     'class' => 'yii\db\Connection',
     *     'dsn' => 'mysql:host=127.0.0.1;dbname=demo',
     *     'username' => 'root',
     *     'password' => '',
     *     'charset' => 'utf8',
     * ]);
     *
     * // create an object with two constructor parameters  使用两个构造函数参数创建一个对象
     * $object = \Yii::createObject('MyClass', [$param1, $param2]);
     * ```
     *
     * 使用[[\yii\di\Container|依赖注入容器]]，此方法也可以识别依赖对象，实例化它们并将它们注入新创建的对象。
     * Using [[\yii\di\Container|dependency injection container]], this method can also identify
     * dependent objects, instantiate them and inject them into the newly created object.
     *
     * @param string|array|callable $type the object type. This can be specified in one of the following forms:
     *
     * - a string: representing the class name of the object to be created
     * - a configuration array: the array must contain a `class` element which is treated as the object class,
     *   and the rest of the name-value pairs will be used to initialize the corresponding object properties
     * - a PHP callable: either an anonymous function or an array representing a class method (`[$class or $object, $method]`).
     *   The callable should return a new instance of the object being created.
     *
     * @param array $params the constructor parameters
     * @return object the created object
     * @throws InvalidConfigException if the configuration is invalid.
     * @see \yii\di\Container
     */
    public static function createObject($type, array $params = [])
    {
        if (is_string($type)) {
            return static::$container->get($type, $params);
        } elseif (is_array($type) && isset($type['class'])) {
            $class = $type['class'];
            unset($type['class']);
            return static::$container->get($class, $params, $type);
        } elseif (is_callable($type, true)) {
            return static::$container->invoke($type, $params);
        } elseif (is_array($type)) {
            throw new InvalidConfigException('Object configuration must be an array containing a "class" element.');
        }

        throw new InvalidConfigException('Unsupported configuration type: ' . gettype($type));
    }

    private static $_logger;

    /**
     * @return Logger message logger
     */
    public static function getLogger()
    {
        if (self::$_logger !== null) {
            return self::$_logger;
        }

        return self::$_logger = static::createObject('yii\log\Logger');
    }

    /**
     * Sets the logger object.
     * @param Logger $logger the logger object.
     */
    public static function setLogger($logger)
    {
        self::$_logger = $logger;
    }

    /**
     * 记录调试消息。
     * 跟踪消息主要用于开发目的，以查看某些代码的执行工作流程。 此方法仅在应用程序处于调试模式时记录消息。
     * Logs a debug message.
     * Trace messages are logged mainly for development purpose to see
     * the execution work flow of some code. This method will only log
     * a message when the application is in debug mode.
     * @param string|array $message the message to be logged. This can be a simple string or a more
     * complex data structure, such as array.
     * @param string $category the category of the message.
     * @since 2.0.14
     */
    public static function debug($message, $category = 'application')
    {
        if (YII_DEBUG) {
            static::getLogger()->log($message, Logger::LEVEL_TRACE, $category);
        }
    }

    /**
     * Alias of [[debug()]].
     * @param string|array $message the message to be logged. This can be a simple string or a more
     * complex data structure, such as array.
     * @param string $category the category of the message.
     * @deprecated since 2.0.14. Use [[debug()]] instead.
     */
    public static function trace($message, $category = 'application')
    {
        static::debug($message, $category);
    }

    /**
     *
     * 记录错误消息。 在执行应用程序时发生不可恢复的错误时，通常会记录错误消息。
     * Logs an error message.
     * An error message is typically logged when an unrecoverable error occurs
     * during the execution of an application.
     * @param string|array $message the message to be logged. This can be a simple string or a more
     * complex data structure, such as array.
     * @param string $category the category of the message.
     */
    public static function error($message, $category = 'application')
    {
        static::getLogger()->log($message, Logger::LEVEL_ERROR, $category);
    }

    /**
     * Logs a warning message.
     * A warning message is typically logged when an error occurs while the execution
     * can still continue.
     * @param string|array $message the message to be logged. This can be a simple string or a more
     * complex data structure, such as array.
     * @param string $category the category of the message.
     */
    public static function warning($message, $category = 'application')
    {
        static::getLogger()->log($message, Logger::LEVEL_WARNING, $category);
    }

    /**
     * 记录信息性消息。
     * Logs an informative message.
     * An informative message is typically logged by an application to keep record of
     * something important (e.g. an administrator logs in).
     * @param string|array $message the message to be logged. This can be a simple string or a more
     * complex data structure, such as array.
     * @param string $category the category of the message.
     */
    public static function info($message, $category = 'application')
    {
        static::getLogger()->log($message, Logger::LEVEL_INFO, $category);
    }

    /**
     * 标记代码块的开头以进行性能分析。
     * Marks the beginning of a code block for profiling.
     *
     * 这必须与具有相同类别名称的[[endProfile]]调用相匹配。 开始和结束调用也必须正确嵌套。
     * This has to be matched with a call to [[endProfile]] with the same category name.
     * The begin- and end- calls must also be properly nested. For example,
     *
     * ```php
     * \Yii::beginProfile('block1');
     * // some code to be profiled
     *     \Yii::beginProfile('block2');
     *     // some other code to be profiled
     *     \Yii::endProfile('block2');
     * \Yii::endProfile('block1');
     * ```
     * @param string $token token for the code block
     * @param string $category the category of this log message
     * @see endProfile()
     */
    public static function beginProfile($token, $category = 'application')
    {
        static::getLogger()->log($token, Logger::LEVEL_PROFILE_BEGIN, $category);
    }

    /**
     * 标记代码块的结尾以进行性能分析。
     * Marks the end of a code block for profiling.
     * This has to be matched with a previous call to [[beginProfile]] with the same category name.
     * @param string $token token for the code block
     * @param string $category the category of this log message
     * @see beginProfile()
     */
    public static function endProfile($token, $category = 'application')
    {
        static::getLogger()->log($token, Logger::LEVEL_PROFILE_END, $category);
    }

    /**
     * 返回一个HTML超链接，该超链接可以显示在显示“Powered by Yii Framework”信息的网页上。
     * Returns an HTML hyperlink that can be displayed on your Web page showing "Powered by Yii Framework" information.
     * @return string an HTML hyperlink that can be displayed on your Web page showing "Powered by Yii Framework" information
     * @deprecated since 2.0.14, this method will be removed in 2.1.0.
     */
    public static function powered()
    {
        return \Yii::t('yii', 'Powered by {yii}', [
            'yii' => '<a href="http://www.yiiframework.com/" rel="external">' . \Yii::t('yii',
                    'Yii Framework') . '</a>',
        ]);
    }

    /**
     * 将消息转换为指定的语言。
     * Translates a message to the specified language.
     *
     * 这是[[\yii\i18n\I18N::translate()]]的快捷方法。
     * This is a shortcut method of [[\yii\i18n\I18N::translate()]].
     *
     * 翻译将根据消息类别进行，并将使用目标语言。
     * The translation will be conducted according to the message category and the target language will be used.
     *
     * 您可以将参数添加到翻译消息中，该翻译消息将在翻译后替换为相应的值。 这种格式是在参数名称周围使用大括号，如下例所示：
     * You can add parameters to a translation message that will be substituted with the corresponding value after
     * translation. The format for this is to use curly brackets around the parameter name as you can see in the following example:
     *
     * ```php
     * $username = 'Alexander';
     * echo \Yii::t('app', 'Hello, {username}!', ['username' => $username]);
     * ```
     *
     * Further formatting of message parameters is supported using the [PHP intl extensions](http://www.php.net/manual/en/intro.intl.php)
     * message formatter. See [[\yii\i18n\I18N::translate()]] for more details.
     *
     * @param string $category the message category.
     * @param string $message the message to be translated.
     * @param array $params the parameters that will be used to replace the corresponding placeholders in the message.
     * @param string $language the language code (e.g. `en-US`, `en`). If this is null, the current
     * [[\yii\base\Application::language|application language]] will be used.
     * @return string the translated message.
     */
    public static function t($category, $message, $params = [], $language = null)
    {
        if (static::$app !== null) {
            return static::$app->getI18n()->translate($category, $message, $params, $language ?: static::$app->language);
        }

        $placeholders = [];
        foreach ((array) $params as $name => $value) {
            $placeholders['{' . $name . '}'] = $value;
        }

        return ($placeholders === []) ? $message : strtr($message, $placeholders);
    }

    /**
     * 使用初始属性值配置对象。
     * Configures an object with the initial property values.
     * @param object $object the object to be configured
     * @param array $properties the property initial values given in terms of name-value pairs.
     * @return object the object itself
     */
    public static function configure($object, $properties)
    {
        foreach ($properties as $name => $value) {
            $object->$name = $value;
        }

        return $object;
    }

    /**
     * 返回对象的公共成员变量。
     * Returns the public member variables of an object.
     * This method is provided such that we can get the public member variables of an object.
     * It is different from "get_object_vars()" because the latter will return private
     * and protected variables if it is called within the object itself.
     * @param object $object the object to be handled
     * @return array the public member variables of the object
     */
    public static function getObjectVars($object)
    {
        return get_object_vars($object);
    }
    
}
