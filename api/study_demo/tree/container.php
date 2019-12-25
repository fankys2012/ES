<?php
/**
 * Created by IntelliJ IDEA.
 * User: fankys
 * Date: 2019/10/31
 * Time: 10:36
 */
class container
{
    private $_definitions = [];

    private $_params = [];

    private $_reflections = [];

    private $_dependencies = [];


    public function set($class,$definitions=[],$params=[])
    {
        $this->_definitions[$class] = $this->normalizeDefinition($class,$definitions);
        $this->_params[$class] = $params;
        return $this;
    }


    /**
     * 规范流程处理
     * @param $class
     * @param array $definitions
     */
    protected function normalizeDefinition($class,$definitions=[])
    {
        if(empty($definitions)) {
            //$container->set('yii\db\Connection');
            return ['class'=>$class];
        } elseif (is_string($definitions)) {
            //注册别名
            //$container->set('foo', 'yii\db\Connection');
            return ['class'=>$definitions];
        } elseif (is_callable($definitions)) {
            //callable来注册一个别名
            return $definitions;
        } elseif (is_array($definitions)) {
            if(!isset($definitions['class'])) {
                /*
                 *$container->set('yii\db\Connection', [
                    'dsn' => 'mysql:host=127.0.0.1;dbname=demo',
                    'username' => 'root',
                    'password' => '',
                    'charset' => 'utf8',
                ]);
                 */
                if(strpos($class,'\\') !== false) {
                    $definitions['class'] = $class;
                } else {
                    throw new Exception('A class definition requires class');
                }
            }
            return $definitions;
        }
    }

    public function get($class,$params=[],$config=[])
    {

        $definitions = $this->_definitions[$class];

        if(is_callable($definitions,true)) {
            $params = $this->resolveDependencies($params);
            $object = call_user_func($definitions,$this,$params,$config);
        } else {
            $concreate = $definitions['class'];
            unset($definitions['class']);
        }
    }

    /**
     * 获取依赖信息
     * @param $class
     */
    protected function getDependencies($class)
    {
        //已经解析依赖信息，直接从缓存中返回
        if(isset($this->_reflections[$class])) {
            return [$this->_reflections[$class],$this->_dependencies[$class]];
        }

        $dependencies = [];

        $reflection = new ReflectionClass($class);
        $constructor = $reflection->getConstructor();
        if($constructor !== null ){
            foreach ($constructor->getParameters() as $params){
                if($params->isDefaultValueAvailable()) {
                    $dependencies[] = $params->getDefaultValue();
                } else{
                    $c = $params->getClass();
                    if($c !== null) {
                        $dependencies = instance::of($c);
                    }
                }
            }
        }
        $this->_reflections[$class] = $reflection;
        $this->_dependencies[$class] = $dependencies;
        return [$reflection,$dependencies];
    }

    /**
     * 解析依赖信息
     * @param $dependencies
     * @param null $reflection
     */
    protected function resolveDependencies($dependencies,$reflection=null)
    {
        foreach ($dependencies as $index=> $dependency) {
            if($dependency instanceof instance) {
                if ($dependency->id !== null ){
                    //递归获取依赖
                    $dependencies[$index] = $this->get($dependency->id);
                } elseif($reflection !== null) {
                    throw new Exception("resolveDependencies failed");
//                    $name = $reflection->getConstructor()->getParameters()[$index]->getName();
                }
            }
        }
        return $dependencies;
    }

    protected function build($class,$params,$config)
    {
        list($reflection,$dependencies) = $this->getDependencies($class);

        //用传入的参数覆盖默认参数
        foreach ($params as $index=>$param){
            $dependencies[$index] = $param;
        }

        $dependencies = $this->resolveDependencies($dependencies);
        if(!$reflection->isInstantiable()) {
            throw new Exception("not instantiable ".$reflection->name);
        }
        $object = $reflection->newInstanceArgs($dependencies);
        foreach ($config as $name => $value) {
            $object->$name = $value;
        }
        return $object;
    }
}