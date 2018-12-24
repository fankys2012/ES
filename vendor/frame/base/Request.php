<?php
/**
 * Created by IntelliJ IDEA.
 * User: fankys
 * Date: 2018/11/26
 * Time: 10:16
 */

namespace frame\base;

use frame;
abstract class Request extends Component
{
    abstract public function resolve();


}