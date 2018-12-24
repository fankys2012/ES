<?php
/**
 * Created by IntelliJ IDEA.
 * User: fankys
 * Date: 2018/11/30
 * Time: 18:01
 */

namespace frame\base;

use frame\Base;
use frame\base\InvalidConfigException;

class Controller extends Component
{
    /**
     * @var string the ID of this controller.
     */
    public $id;
    /**
     * @var Module the module that this controller belongs to.
     */
    public $module;
    /**
     * @var string the ID of the action that is used when the action ID is not specified
     * in the request. Defaults to 'index'.
     */
    public $defaultAction = 'index';


    public $action;



    /**
     * Controller constructor.
     * @param string $id
     * @param string $module
     * @param array $config
     */
    public function __construct($id, $module,array $config = [])
    {
        parent::__construct($config);
    }

    public function runAction($id, $params = [])
    {
        $action = $id."Action";
        $this->$action();
        exit;
        $action = $this->createSysAction($id);
        var_dump($action);exit;
        if ($action === null) {
            throw new InvalidConfigException('Unable to resolve the request: ' .  $id);
        }



        $this->action = $action;

        $modules = [];
        $runAction = true;

        // call beforeAction on modules
        foreach ($this->getModules() as $module) {
            if ($module->beforeAction($action)) {
                array_unshift($modules, $module);
            } else {
                $runAction = false;
                break;
            }
        }

        $result = null;

        if ($runAction && $this->beforeAction($action)) {
            // run the action
            $result = $action->runWithParams($params);

            $result = $this->afterAction($action, $result);

            // call afterAction on modules
            foreach ($modules as $module) {
                /* @var $module Module */
                $result = $module->afterAction($action, $result);
            }
        }



        return $result;
    }

    public function createSysAction($id)
    {
        if ($id === '') {
            $id = $this->defaultAction;
        }
        $actionMap = $this->actions();
        if (isset($actionMap[$id])) {
            return Base::createObject($actionMap[$id], [$id, $this]);
        } elseif (preg_match('/^[(a-z|A-Z)0-9\\-_]+$/', $id) && strpos($id, '--') === false && trim($id, '-') === $id) {
            $methodName = str_replace(' ', '', ucwords(implode(' ', explode('-', $id)))).'Action';
            if (method_exists($this, $methodName)) {
                $method = new \ReflectionMethod($this, $methodName);
                if ($method->isPublic() && $method->getName() === $methodName) {
                    return new InlineAction($id, $this, $methodName);
                }
            }
        }

        return null;
    }

    public function actions()
    {
        return [];
    }

    /**
     * Returns all ancestor modules of this controller.
     * The first module in the array is the outermost one (i.e., the application instance),
     * while the last is the innermost one.
     * @return Module[] all ancestor modules that this controller is located within.
     */
    public function getModules()
    {
        $modules = [$this->module];
        $module = $this->module;
        while ($module->module !== null) {
            array_unshift($modules, $module->module);
            $module = $module->module;
        }
        return $modules;
    }

}