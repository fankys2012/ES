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

    /**
     * @var array the parsers for converting the raw HTTP request body into [[bodyParams]].
     * The array keys are the request `Content-Types`, and the array values are the
     * corresponding configurations for [[Base::createObject|creating the parser objects]].
     * A parser must implement the [[RequestParserInterface]].
     *
     * To enable parsing for JSON requests you can use the [[JsonParser]] class like in the following example:
     *
     * ```
     * [
     *     'application/json' => 'Base\web\JsonParser',
     * ]
     * ```
     *
     * To register a parser for parsing all request types you can use `'*'` as the array key.
     * This one will be used as a fallback in case no other types match.
     *
     * @see getBodyParams()
     */
    public $parsers = [];

    private $_rawBody;

    /**
     * @var \Swoole\Http\Request
     */
    public $swooleRequest;

    public function setSwooleReques($request)
    {
        $this->swooleRequest = $request;
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
            if (isset($this->parsers[$contentType])) {
                $parser = Base::createObject($this->parsers[$contentType]);
                if (!($parser instanceof RequestParserInterface)) {
                    throw new InvalidConfigException("The '$contentType' request parser is invalid. It must implement the yii\\web\\RequestParserInterface.");
                }
                $this->_bodyParams = $parser->parse($this->getRawBody(), $contentType);
            } elseif (isset($this->parsers['*'])) {
                $parser = Base::createObject($this->parsers['*']);
                if (!($parser instanceof RequestParserInterface)) {
                    throw new InvalidConfigException("The fallback request parser is invalid. It must implement the yii\\web\\RequestParserInterface.");
                }
                $this->_bodyParams = $parser->parse($this->getRawBody(), $contentType);
            } elseif ($this->getMethod() === 'POST') {
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
}