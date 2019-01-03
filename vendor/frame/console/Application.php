<?php
/**
 * Created by IntelliJ IDEA.
 * User: fankys
 * Date: 2019/1/3
 * Time: 14:37
 */

namespace frame\console;


use frame\Base;

/**
 * Class Application
 * @package frame\console
 * @property \frame\console\Request $request
 */
class Application extends \frame\base\Application
{
    const OPTION_APPCONFIG = 'appconfig';

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        $config = $this->loadConfig($config);
        parent::__construct($config);
    }

    protected function loadConfig($config)
    {
        if (!empty($_SERVER['argv'])) {
            $option = '--' . self::OPTION_APPCONFIG . '=';
            foreach ($_SERVER['argv'] as $param) {
                if (strpos($param, $option) !== false) {
                    $path = substr($param, strlen($option));
                    if (!empty($path) && is_file($file = Base::getAlias($path))) {
                        return require($file);
                    } else {
                        exit("The configuration file does not exist: $path\n");
                    }
                }
            }
        }

        return $config;
    }

    /**
     * Handles the specified request.
     * @param Request $request the request to be handled
     * @return Response the resulting response
     */
    public function handleRequest($request)
    {
        list ($route, $params) = $request->resolve();
        $this->requestedRoute = $route;
        $result = $this->runAction($route, $params);
//        if ($result instanceof Response) {
//            return $result;
//        } else {
//            $response = $this->getResponse();
//            $response->exitStatus = $result;
//
//            return $response;
//        }
    }

    public function getRequest()
    {
        return $this->get('request');
    }

    /**
     * @inheritdoc
     */
    public function coreComponents()
    {
        return array_merge(parent::coreComponents(), [
            'request' => ['class' => 'frame\console\Request'],
        ]);
    }
}