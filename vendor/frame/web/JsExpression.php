<?php
/**
 * Created by IntelliJ IDEA.
 * User: fankys
 * Date: 2019/2/18
 * Time: 11:49
 */

namespace frame\web;


use frame\base\Object;

class JsExpression extends Object
{
    /**
     * @var string the JavaScript expression represented by this object
     */
    public $expression;


    /**
     * Constructor.
     * @param string $expression the JavaScript expression represented by this object
     * @param array $config additional configurations for this object
     */
    public function __construct($expression, $config = [])
    {
        $this->expression = $expression;
        parent::__construct($config);
    }

    /**
     * The PHP magic function converting an object into a string.
     * @return string the JavaScript expression.
     */
    public function __toString()
    {
        return $this->expression;
    }
}