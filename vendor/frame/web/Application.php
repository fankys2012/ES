<?php
/**
 * Created by IntelliJ IDEA.
 * User: fankys
 * Date: 2018/11/26
 * Time: 10:11
 */

namespace frame\web;
use frame\base\InvalidCallException;


/**
 * Class Application
 * @package frame\web
 * @property \frame\web\Request $request
 */
class Application extends \frame\base\Application
{
    public $requestedRoute;

    /**
     * @inheritdoc
     */
    protected function bootstrap()
    {
//        $request = $this->getRequest();

        parent::bootstrap();
    }

    public function getRequest()
    {
        return $this->get('request');
    }

    /**
     * Returns the response component.
     * @return Response the response component.
     */
    public function getResponse()
    {
        return $this->get('response');
    }

    /**
     * Handles the specified request.
     * @param Request $request the request to be handled
     * @return Response the resulting response
     */
    public function handleRequest($request)
    {
        if (empty($this->catchAll)) {
            list ($route, $params) = $request->resolve();
        } else {
            $route = $this->catchAll[0];
            $params = $this->catchAll;
            unset($params[0]);
        }
        try {
            $this->requestedRoute = $route;
            $result = $this->runAction($route, $params);
            if ($result instanceof Response) {
                return $result;
            } else {
                $response = $this->getResponse();
                if ($result !== null) {
                    $response->data = $result;
                }

                return $response;
            }

        } catch ( InvalidCallException $e) {
            throw new NotFoundHttpException('Page not found', $e->getCode(), $e);
        }
    }


    /**
     * @inheritdoc
     */
    public function coreComponents()
    {
        return array_merge(parent::coreComponents(), [
            'request' => ['class' => 'frame\web\Request'],
            'response' => ['class' => 'frame\web\Response'],
        ]);
    }

    /**
     * get value from $params
     * @param array $params
     * @param string $name
     * @param null $defaultValue
     * @return null|string
     */
    public function getParam(&$params,$name,$defaultValue=null)
    {
        if(isset($params[$name])) {
            if(is_string($params[$name]) && strlen($params[$name]) >0) {
                return trim($params[$name]);
            }
            elseif (is_array($params[$name]) || is_integer($params[$name])) {
                return $params[$name];
            }
        }
        return $defaultValue;
    }
}