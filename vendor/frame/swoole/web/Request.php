<?php
/**
 * Created by IntelliJ IDEA.
 * User: fankys
 * Date: 2019/2/14
 * Time: 15:45
 */

namespace frame\swoole\web;


use frame\Base;

class Request extends \frame\web\Request
{
    private $_queryParams;

    private $_bodyParams;


    public $parsers = [];

    private $_rawBody;

    /**
     * @var \Swoole\Http\Request
     */
    public $swooleRequest;

    public function setSwooleReques($request)
    {
        $this->swooleRequest = $request;
        $this->clear();
    }

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
            return $this->swooleRequest->get;
        }

        return $this->_queryParams;
    }

    public function getQueryParam($name, $defaultValue = null)
    {
        $params = $this->getQueryParams();

        return isset($params[$name]) ? $params[$name] : $defaultValue;
    }

    public function getBodyParams()
    {
        if ($this->_bodyParams === null) {
            if (isset($this->swooleRequest->post[$this->methodParam])) {
                $this->_bodyParams = $this->swooleRequest->post;
                unset($this->_bodyParams[$this->methodParam]);
                return $this->_bodyParams;
            }
            $contentType = $this->getContentType();
            if (($pos = strpos($contentType, ';')) !== false) {
                // e.g. application/json; charset=UTF-8
                $contentType = substr($contentType, 0, $pos);
            }
            if ($this->getMethod() === 'POST') {
                // PHP has already parsed the body so we have all params in $this->swoole->post
                $this->_bodyParams = $this->swooleRequest->post;
            } else {
                $this->_bodyParams = [];
                mb_parse_str($this->getRawBody(), $this->_bodyParams);
            }
        }
        return $this->_bodyParams;
    }

    public function getRawBody()
    {
        if ($this->_rawBody === null) {
            $this->_rawBody = $this->swooleRequest->rawContent();
        }
        return $this->_rawBody;
    }

    public function getMethod()
    {
        if (isset($this->swooleRequest->post[$this->methodParam])) {
            return strtoupper($this->swooleRequest->post[$this->methodParam]);
        }

        if (isset($this->swooleRequest->server['REQUEST_METHOD'])) {
            return strtoupper($this->swooleRequest->server['REQUEST_METHOD']);
        }

        return 'GET';
    }

    /**
     * 清理变量
     */
    public function clear()
    {
        $this->_bodyParams = null;
        $this->_queryParams = null;
        $this->_rawBody = null;
    }
}