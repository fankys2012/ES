<?php
/**
 * Created by IntelliJ IDEA.
 * User: fankys
 * Date: 2019/1/25
 * Time: 10:33
 */

namespace frame\base;


use frame\Log;

class InlineAction extends Action
{
    public $actionMethod;

    public function __construct($id, $controller, $actionMethod, array $config = [])
    {
        $this->actionMethod = $actionMethod;
        parent::__construct($id, $controller, $config);
    }

    public function runWithParams($params)
    {
        $args = $this->controller->bindActionParams($this, $params);
        if(FRAME_DEBUG) {
            Log::trace('Running action: ' . get_class($this->controller) . '::' . $this->actionMethod . '()', __METHOD__);
        }
        return call_user_func_array([$this->controller, $this->actionMethod], $args);
    }
}