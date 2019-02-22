<?php
/**
 * Created by IntelliJ IDEA.
 * User: fankys
 * Date: 2019/2/22
 * Time: 10:00
 */

//namespace frame;

require(__DIR__ . '/Base.php');
require(__DIR__ . '/Log.php');

class SwooleInit extends \frame\Base
{

}
spl_autoload_register(['SwooleInit', 'autoload'], true, true);
SwooleInit::$classMap = require(__DIR__ . '/classes.php');
SwooleInit::$curr_date_time = date('Y-m-d H:i:s',time());