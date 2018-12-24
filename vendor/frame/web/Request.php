<?php
/**
 * Created by IntelliJ IDEA.
 * User: fankys
 * Date: 2018/11/30
 * Time: 16:04
 */

namespace frame\web;


class Request extends \frame\base\Request
{
    private $_queryParams;

    public function resolve()
    {
        $c = $this->getQueryParam('c','Index');
        $a = $this->getQueryParam('a','Index');
        $route = $c .'/'.$a;
        return [$route, $this->getQueryParams()];
    }

    public function getQueryParams()
    {
        if ($this->_queryParams === null) {
            return $_GET;
        }

        return $this->_queryParams;
    }

    public function getQueryParam($name, $defaultValue = null)
    {
        $params = $this->getQueryParams();

        return isset($params[$name]) ? $params[$name] : $defaultValue;
    }

    public function getParam($name,$defaultValue=null)
    {
        $params = $_REQUEST;
        if(isset($params[$name])) {
            if(is_string($params[$name]) && strlen($params[$name]) >0) {
                return trim($params[$name]);
            }
            elseif (is_array($params[$name])) {
                return $params[$name];
            }
        }
        return $defaultValue;
    }

}