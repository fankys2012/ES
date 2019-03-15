<?php
/**
 * Created by IntelliJ IDEA.
 * User: fankys
 * Date: 2018/11/26
 * Time: 9:40
 */

namespace frame\base;

use frame\Base;

/**
 * Class Application
 * @package frame\base
 * @property \frame\web\Request|\frame\console\Request $request
 */
abstract class Application extends Module
{
    public $bootstrap = [];
    public function __construct($config = [])
    {
        Base::$app = $this;
        $this->preinit($config);
        //注册错误方法

        Component::__construct($config);
//        $this->getErrorHandler()->register();
    }
    abstract public function handleRequest($request);

    public function init()
    {
        $this->bootstrap();
    }

    public function preinit(&$config)
    {
        //merge core components with custom components
        foreach ($this->coreComponents() as $id=>$component)
        {
            if(!isset($config['components'][$id]))
            {
                $config['components'][$id] = $component;
            }
            elseif (is_array($config['components'][$id]) && !isset($config['components'][$id]['class']))
            {
                $config['components'][$id]['class'] = $component['class'];
            }
        }
    }


    protected function bootstrap()
    {
        foreach ($this->bootstrap as $class) {
            $component = null;
            if (is_string($class)) {
                if ($this->has($class)) {
                    $component = $this->get($class);
                } elseif ($this->hasModule($class)) {
                    $component = $this->getModule($class);
                } elseif (strpos($class, '\\') === false) {
                    throw new InvalidConfigException("Unknown bootstrapping component ID: $class");
                }
            }
            if (!isset($component)) {
                $component = Base::createObject($class);
            }

            if ($component instanceof BootstrapInterface) {
                $component->bootstrap($this);
            }
        }
    }

    public function run()
    {
        try{
            $response = $this->handleRequest($this->getRequest());
            $response->send();
            return $response->exitStatus;

        } catch (Exception $e) {
            return 0;
        }

    }

    public function coreComponents()
    {
        return [
            'log' => ['class' => 'frame\log\Dispatcher'],
            'errorHandler' => ['class' => 'frame\base\ErrorHandler'],
        ];
    }

    /**
     * Returns the log dispatcher component.
     * @return \frame\log\Dispatcher the log dispatcher application component.
     */
    public function getLog()
    {
        return $this->get('log');
    }

    public function getErrorHandler()
    {
        return $this->get('errorHandler');
    }


}