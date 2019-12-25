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
        $parentPos = intval($this->count / 2) -1;
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

    /**
     * 使$arr[$start]、$arr[$start+1]、、、$arr[$end]成为一个大根堆
     * @param $start
     * @param $end
     */
    public function adjust($start,$end)
    {
        for (;$start < intval($end/2);)
        {
            //左孩子2 * $start + 1
            $leftChild = $start * 2 +1;
            //左孩子比右孩子小
            if($leftChild+1 < $end && $this->heap[$leftChild] < $this->heap[$leftChild+1])
            {
                //转化为右孩子
                $leftChild ++;
            }
            if($this->heap[$start] < $this->heap[$leftChild])
            {
                $this->swap($start,$leftChild);
                $start = $leftChild;
            }
            else
            {
                //满足大堆特性
                break;
            }
        }
    }

    /**
     * 初始时把要排序的数的序列看作是一棵顺序存储的二叉树，调整它们的存储序，使之成为一个 堆，这时堆的根节点的数最大
     * 然后将根节点与堆的最后一个节点交换。然后对前面(n-1)个数重新调整使之成为堆
     * 依此类推，直到只有两个节点的堆，并对 它们作交换，最后得到有n个节点的有序序列
     */
    public function heapSort()
    {
        for ($i = $this->count-1;$i>1;$i--)
        {
            //将堆顶元素与最后一个元素交换,获取到最大元素（交换后的最后一个元素），将最大元素放到数组末尾
            $this->swap(0,$i);
            //经过交换，将最后一个元素（最大元素）脱离大根堆，并将未经排序的新树($arr[0...$i-1])重新调整为大根堆
            $this->adjust(0,$i-1);
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

$heap->heapSort();
var_dump($heap->heap);