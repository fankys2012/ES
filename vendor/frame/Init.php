<?php
/**
 * Created by IntelliJ IDEA.
 * User: fankys
 * Date: 2018/11/30
 * Time: 14:27
 */

require(__DIR__ . '/Base.php');


class Init extends \frame\Base
{

}
spl_autoload_register(['Init', 'autoload'], true, true);
Init::$classMap = require(__DIR__ . '/classes.php');
Init::$curr_date_time = date('Y-m-d H:i:s',time());
\frame\Base::$container = new frame\di\Container();
