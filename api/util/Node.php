<?php
/**
 * Created by IntelliJ IDEA.
 * User: fankys
 * Date: 2019/2/27
 * Time: 10:06
 */

namespace api\util;


class Node
{
    public $item;

    public $prev;

    public $next;

    public function __construct($item)
    {
        $this->item = $item;
        $this->next = null;
        $this->prev = null;
    }
}