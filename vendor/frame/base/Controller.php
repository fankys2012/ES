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

        $action = $this->createSysAction($id);
        if ($action === null) {
            throw new \Exception('Unable to resolve the request: ' .  $id);
        }

        $this->action = $action;
        $runAction = true;

        $result = null;

        if ($runAction && $this->beforeAction($action)) {
            // run the action
            $result = $action->runWithParams($params);

            $result = $this->afterAction($action, $result);

        }

        return $result;
    }

    public function createSysAction($id)
    {
        if ($id === '') {
            $id = $this->defaultAction;
        }
        if (preg_match('/^[(a-z|A-Z)0-9\\-_]+$/', $id) && strpos($id, '--') === false && trim($id, '-') === $id) {
            $methodName = str_replace(' ', '', $id).'Action';
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

    public function beforeAction($action)
    {
        //暂未实现
        return true;
    }

    public function afterAction($action,$result)
    {
        //暂未实现
        return $result;
    }

    public function bindActionParams($action, $params)
    {
        return [];
    }

}