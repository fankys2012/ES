<?php
/**
 * Created by IntelliJ IDEA.
 * User: fankys
 * Date: 2019/1/25
 * Time: 10:34
 */

namespace frame\base;


class Action extends Component
{
    public $id;

    public $controller;

    public function __construct($id,$controller,$config = [])
    {
        $this->id = $id;
        $this->controller = $controller;
        parent::__construct($config);
    }

    protected function beforeRun()
    {

    }

    protected function afterRun()
    {

    }
}