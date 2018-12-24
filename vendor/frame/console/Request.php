<?php
/**
 * Created by IntelliJ IDEA.
 * User: fankys
 * Date: 2018/11/26
 * Time: 11:06
 */

namespace frame\console;


class Request extends \frame\base\Request
{
    private $_params;

    /**
     * Returns the command line arguments
     * @return array|null
     */
    public function getParams()
    {
        if($this->_params === null)
        {
            if(isset($_SERVER['argv']))
            {
                $this->_params = $_SERVER['argv'];
                array_shift($this->_params);
            }
            else
            {
                $this->_params = [];
            }
        }
        return $this->_params;
    }

    public function setParams($params)
    {
        $this->_params = $params;
    }

    public function resolve()
    {
        // TODO: Implement resolve() method.
        $rawParams = $this->getParams();
        $endOfOptionsFound = false;

        if (isset($rawParams[0])) {
            $route = array_shift($rawParams);

            if ($route === '--') {
                $endOfOptionsFound = true;
                $route = array_shift($rawParams);
            }
        } else {
            $route = '';
        }
        $params = [];
        foreach ($rawParams as $param)
        {
            if($endOfOptionsFound){
                $params = $param;
            }
            else {
                $params = $params;
            }
        }
        return [$route,$params];

    }

}