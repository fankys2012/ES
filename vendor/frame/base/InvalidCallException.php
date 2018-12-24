<?php
/**
 * Created by IntelliJ IDEA.
 * User: fankys
 * Date: 2018/12/18
 * Time: 10:12
 */

namespace frame\base;

/**
 * InvalidCallException represents an exception caused by calling a method in a wrong way.
 *
 */
class InvalidCallException extends \BadMethodCallException
{
    public function getName()
    {
        return 'Invalid Call';
    }
}