<?php
/**
 * Created by IntelliJ IDEA.
 * User: fankys
 * Date: 2019/3/27
 * Time: 9:13
 */

namespace tests;


use PHPUnit\Framework\TestCase;

class StackTest extends TestCase
{
    public function testPush()
    {
        $stack = [];
        $this->assertEquals(0,count($stack));
        array_push($stack, 'foo');
        $this->assertEquals(1, count($stack));
        $this->assertEquals('faoo', array_pop($stack));
    }

    public function testPush2()
    {
        $stack = [];
        $this->assertEquals(1,count($stack));
        array_push($stack, 'foo');
        $this->assertEquals(1, count($stack));
        $this->assertEquals('foo', array_pop($stack));
    }
}