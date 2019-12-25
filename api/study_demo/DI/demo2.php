<?php
/**
 * 容器顾名思义是用来装东西的，DI容器里面的东西是什么呢？Yii使用 yii\di\Instance 来表示容器中的东西
 * Date: 2019/10/25
 * Time: 14:29
 */

class Instance
{
    //私有属性，用于保存类名、接口名或别名
    private $_id;

    protected function __construct($id)
    {
        $this->_id = $id;
    }

    //静态方法创建一个Instance 实例
    public static function of($id)
    {
        return new static($id);
    }

    //将引用解析成实际对象，并确保这个对象的类型
    public static function ensure($reference, $type = null, $container = null)
    {

    }

    //获取实例所引用的实际对象，实际上它调用的是yii\di\Container::get
    public function get($container=null)
    {

    }
}
/**
 * 对于 yii\di\Instance ，我们要了解：
 * 1、表示的是容器中的内容，代表的是对于实际对象的引用。
 * 2、DI容器可以通过他获取所引用的实际对象
 * 3、类仅有的一个属性 id 一般表示的是实例的类型。
 */

class Container
{
    // 用于保存单例Singleton对象，以对象类型为键
    private $_singletons = [];

    // 用于保存依赖的定义，以对象类型为键
    private $_definitions = [];

    // 用于保存构造函数的参数，以对象类型为键
    private $_params = [];

    // 用于缓存ReflectionClass对象，以类名或接口名为键
    private $_reflections = [];

    // 用于缓存依赖信息，以类名或接口名为键
    private $_dependencies = [];

    /**
     * 使用DI容器，首先要告诉容器，类型及类型之间的依赖关系，声明一这关系的过程称为注册依赖。
     * 使用 yii\di\Container::set() 和 yii\di\Container::setSinglton() 可以注册依赖。
     */
    public function set($class, $definition = [], array $params = [])
    {
        // 规范化 $definition 并写入 $_definitions[$class]
        $this->_definitions[$class] = $this->normalizeDefinition($class, $definition);

        // 将构造函数参数写入 $_params[$class]
        $this->_params[$class] = $params;

        // 删除$_singletons[$class]
        unset($this->_singletons[$class]);
        return $this;
    }

    public function setSingleton($class, $definition = [], array $params = [])
    {
        // 规范化 $definition 并写入 $_definitions[$class]
        $this->_definitions[$class] = $this->normalizeDefinition($class, $definition);

        // 将构造函数参数写入 $_params[$class]
        $this->_params[$class] = $params;

        // 将$_singleton[$class]置为null，表示还未实例化
        $this->_singletons[$class] = null;
        return $this;
    }

    /**
     * 这两个函数功能类似没有太大区别，只是 set() 用于在每次请求时构造新的实例返回，
     * 而 setSingleton() 只维护一个单例，每次请求时都返回同一对象。

     * 表现在数据结构上，就是 set() 在注册依赖时，会把使用 setSingleton() 注册的依赖删除。
     * 否则，在解析依赖时，你让Yii究竟是依赖续弦还是原配？因此，在DI容器中，依赖关系的定义是唯一的。
     * 后定义的同名依赖，会覆盖前面定义好的依赖。

     * 从形参来看，这两个函数的 $class 参数接受一个类名、接口名或一个别名，作为依赖的名称。
     * $definition` 表示依赖的定义，可以是一个类名、配置数组或一个PHP callable。

     * 这两个函数，本质上只是将依赖的有关信息写入到容器的相应数组中去。
     * 在 set() 和 setSingleton() 中，首先调用 yii\di\Container::normalizeDefinition()
     * 对依赖的定义进行规范化处理，其代码如下::
     */

    protected function normalizeDefinition($class,$definition)
    {
        // $definition 是空的转换成 ['class' => $class] 形式
        if (empty($definition)) {
            return ['class' => $class];

            // $definition 是字符串，转换成 ['class' => $definition] 形式
        } elseif (is_string($definition)) {
            return ['class' => $definition];

            // $definition 是PHP callable 或对象，则直接将其作为依赖的定义
        } elseif (is_callable($definition, true) || is_object($definition)) {
            return $definition;

            // $definition 是数组则确保该数组定义了 class 元素
        } elseif (is_array($definition)) {
            if (!isset($definition['class'])) {
                if (strpos($class, '\\') !== false) {
                    $definition['class'] = $class;
                } else {
                    throw new InvalidConfigException("A class definition requires a \"class\" member.");
                }
            }
            return $definition;
            // 这也不是，那也不是，那就抛出异常算了
        } else {
            throw new InvalidConfigException("Unsupported definition type for \"$class\": " . gettype($definition));
        }
    }
}

//举例说明
$container = new Container;

// 直接以类名注册一个依赖，虽然这么做没什么意义。
// $_definition['yii\db\Connection'] = 'yii\db\Connetcion'
$container->set('yii\db\Connection');

// 注册一个接口，当一个类依赖于该接口时，定义中的类会自动被实例化，并供有依赖需要的类使用。
// $_definition['yii\mail\MailInterface', 'yii\swiftmailer\Mailer']
$container->set('yii\mail\MailInterface', 'yii\swiftmailer\Mailer');

// 注册一个别名，当调用$container->get('foo')时，可以得到一个 yii\db\Connection 实例。
// $_definition['foo', 'yii\db\Connection']
$container->set('foo', 'yii\db\Connection');

// 用一个配置数组来注册一个类，需要这个类的实例时，这个配置数组会发生作用。
// $_definition['yii\db\Connection'] = [...]
$container->set('yii\db\Connection', [
    'dsn' => 'mysql:host=127.0.0.1;dbname=demo',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8',
]);

// 用一个配置数组来注册一个别名，由于别名的类型不详，因此配置数组中需要有 class 元素
// $_definition['db'] = [...]
$container->set('db', [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host=127.0.0.1;dbname=demo',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8',
]);

// 用一个PHP callable来注册一个别名，每次引用这个别名时，这个callable都会被调用。
// $_definition['db'] = function(...){...}
$container->set('db', function ($container, $params, $config) {
    return new \yii\db\Connection($config);
});

// 用一个对象来注册一个别名，每次引用这个别名时，这个对象都会被引用。
// $_definition['pageCache'] = anInstanceOfFileCache
$container->set('pageCache', new FileCache);