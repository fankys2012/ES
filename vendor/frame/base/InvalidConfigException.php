<?php
/**
 * Created by IntelliJ IDEA.
 * User: fankys
 * Date: 2018/11/30
 * Time: 18:06
 */

namespace frame\base;


class InvalidConfigException extends \Exception
{
    /**
     * @return string the user-friendly name of this exception
     */
    public function getName()
    {
        return 'Invalid Configuration';
    }
}
