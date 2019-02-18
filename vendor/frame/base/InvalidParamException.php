<?php
/**
 * Created by IntelliJ IDEA.
 * User: fankys
 * Date: 2019/2/18
 * Time: 13:30
 */

namespace frame\base;


class InvalidParamException extends \BadMethodCallException
{
    /**
     * @return string the user-friendly name of this exception
     */
    public function getName()
    {
        return 'Invalid Parameter';
    }
}