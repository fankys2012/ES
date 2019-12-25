<?php
/**
 * Created by IntelliJ IDEA.
 * User: fankys
 * Date: 2019/11/20
 * Time: 17:52
 */
class maxHeap
{
    public $heap;

    public $count;

    public function __construct($size)
    {
        $this->heap = array_fill(0,$size,0);
        $this->count = 0;
    }

    public function insert($data)
    {
        if($this->count == 0)
        {
            $this->heap[0] = $data;
            $this->count =1;
        }
        else
        {
            $this->heap[$this->count++] = $data;
            $this->_siftUP();
        }
    }

    private function _siftUP()
    {
        //待上浮元素的临时位置
        $tempPos = $this->count - 1;
        //根据完全二叉树性质找到副节点的位置
        $parentPos = intval($tempPos / 2);
        while ($tempPos > 0 && $this->heap[$parentPos] < $this->heap[$tempPos])
        {
            //当不是根节点并且副节点的值小于临时节点的值，就交换两个节点的值
            $this->swap($parentPos, $tempPos);
            //重置上浮元素的位置
            $tempPos = $parentPos;
            //重置父节点的位置
            $parentPos = intval($tempPos / 2);
        }
    }

    public function swap($a, $b)
    {
        $temp = $this->heap[$a];
        $this->heap[$a] = $this->heap[$b];
        $this->heap[$b] = $temp;
    }

    public function extractMax()
    {
        //最大值就是大跟堆的第一个值
        $max = $this->heap[0];
        //把堆的最后一个元素作为临时的根节点
        $this->heap[0] = $this->heap[$this->count - 1];
        //把最后一个节点重置为0
        $this->heap[--$this->count] = 0;
        //下沉根节点到合适的位置
        $this->siftDown(0);

        return $max;
    }

    public function siftDown($k)
    {
        //最大值的位置
        $largest = $k;
        //左孩子的位置
        $left = 2 * $k + 1;
        //右孩子的位置
        $right = 2 * $k + 2;


        if ($left < $this->count && $this->heap[$largest] < $this->heap[$left])
        {
            //如果左孩子大于最大值，重置最大值的位置为左孩子
            $largest = $left;
        }

        if ($right < $this->count && $this->heap[$largest] < $this->heap[$right])
        {
            //如果右孩子大于最大值，重置最大值的位置为左孩子
            $largest = $right;
        }


        //如果最大值的位置发生改变
        if ($largest != $k)
        {
            //交换位置
            $this->swap($largest, $k);
            //继续下沉直到初始位置不发生改变
            $this->siftDown($largest);
        }
    }
}

$heap = new maxHeap(10);
echo "<pre>";
//45，36，18，53，72，30，48，93，15，35
$heap->insert(45);
$heap->insert(36);
$heap->insert(18);
$heap->insert(53);
$heap->insert(72);
$heap->insert(30);
$heap->insert(48);
$heap->insert(93);
$heap->insert(15);
$heap->insert(35);
//$heap->extractMax();
var_dump($heap->heap);