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
        Component::__construct($config);
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

    }

    public function run()
    {
        try{
            $response = $this->handleRequest($this->getRequest());

        } catch (Exception $e) {

        }

    }

    public function coreComponents()
    {
        return [

        ];
    }


}