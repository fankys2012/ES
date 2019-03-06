<?php
/**
 * Created by IntelliJ IDEA.
 * User: fankys
 * Date: 2019/2/27
 * Time: 10:08
 */

namespace api\util;


class DoubleLink
{
    /**
     * @var Node 头节点
     */
    private $head;

    public function __construct()
    {
        $this->head = null;
    }

    public function add($item)
    {
        $node = new Node($item);
        if($this->head == null) {
            $this->head = $node;
        } else {
            //待插入节点后继节点为原本头节点
            $node->next = $this->head;
            //待插入节点为原本头节点的前驱节点
            $this->head->prev = $node;
            //待插入节点变为头节点
            $this->head = $node;
        }
    }

    public function append($item)
    {
        $node = new Node($item);
        if($this->head == null) {
            $this->head = $node;
        } else {
            //移动到尾节点
            $curr = $this->head;
            while ($curr->next != null) {
                $curr = $curr->next;
            }
            //原本尾节点next指向待插入节点
            $curr->next = $node;
            //待插入节点prev指向原本尾节点
            $node->prev = $curr;
        }

    }
}