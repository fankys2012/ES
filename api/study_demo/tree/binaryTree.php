<?php
/**
 * https://www.cnblogs.com/zxqc/p/10653924.html
 * https://blog.csdn.net/s3frh3jyn6yymhmt11/article/details/89325071
 * Created by IntelliJ IDEA.
 * User: fankys
 * Date: 2019/11/21
 * Time: 17:52
 */
class Node
{
    public $data;
    public $left = null;
    public $right = null;
    public $parent = null;

    public function __construct($data,$parent = null)
    {
        $this->data =  $data;
        $this->parent = $parent;
    }
}

class BinaryTree
{
    private $tree;

    public function insert($data)
    {
        //空树
        if(!$this->tree)
        {
            $this->tree = new Node($data);
            return;
        }
        /**
         * @var $p Node
         */
        $p = $this->tree;
        while ($p)
        {
            if($data < $p->data)
            {
                if(!$p->left)
                {
                    $p->left = new Node($data,$p);
                    return ;
                }
                $p = $p->left;

            }
            elseif ($data > $p->data)
            {
                if(!$p->right)
                {
                    $p->right = new Node($data,$p);
                    return;
                }
                $p = $p->right;
            }
            else
            {
                return;
            }
        }
    }

    public function find($data)
    {
        $p = $this->tree;
        while ($p)
        {
            if($data < $p->data)
            {
                $p = $p->left;
            }
            elseif($data > $p-> data)
            {
                $p = $p->right;
            }
            else
            {
                return $p;
            }
        }
        return null;
    }


    /**
     * 获取最大节点 最大节点一定在右子树或根节点（无右子树时）
     * @param $node
     * @return Node
     */
    public function findMaxNode($node)
    {
        while ($node && $node->right)
        {
            $node = $node->right;
        }
        return $node;
    }

    /**
     * 获取最小节点
     * @param $node
     */
    public function findMinNode($node)
    {
        while ($node && $node->left)
        {
            $node = $node->left;
        }
        return $node;
    }

    /**
     * 获取中序遍历的前驱节点(查找小于该节点的最大节点)
     * @param $node
     */
    public function getPredecessor($node)
    {
        /**
         * 场景一：
         * 如果存在左子节点，那么左子树下最大的key值节点即是前驱节点
         */
        if($node->left) {
            return $this->findMaxNode($node->left);
        }
        /**
         * 场景二：
         * 如果该节点没有左子节点，且该节点是其父节点的右子节点，那么该节点的父节点即为该节点的前驱
         */
        $parentNode = $node->parent;
        if($parentNode->right == $node){
            return $parentNode;
        }

        /**
         * 场景三：
         * 如果该节点没有左子节点，且该节点为其父节点的左子节点，那么就往顶端寻找，直到找到一个节点是其父节点的右子节点，该父节点就是要找的前驱
         */
        while ($parentNode && $node == $parentNode->left) {
            $node = $parentNode;
            $parentNode = $parentNode->parent;
        }
        return $parentNode;
    }

    /**
     * 获取中序遍历的后继节点(查找大于某个节点的最小节点)
     * @param $node
     */
    public function getSuccessor($node)
    {
        /**
         * 场景一：
         * 如果存在右子节点，那么右子节点中最小key 值节点即为后继
         */
        if($node->right){
            return $this->findMinNode($node->right);
        }
        /**
         * 场景二：
         * 如果没有右子节点，且该节点为其父节点的左子节点，那么该节点的父节点即为该节点的后继
         */
        $parendNode = $node->parent;
        if($parendNode->left = $node) {
            return $parendNode;
        }

        /**
         * 场景三：
         * 如果没有右子节点，且该节点为其父节点的右子节点，那么就往顶端寻找，直到找到一个节点是其父节点的左子节点，那么该父节点即为需要查找的后继
         */
        while ($parendNode && $node == $parendNode->right) {
            $node = $parendNode;
            $parendNode = $parendNode->parent;
        }
        return $parendNode;
    }

    /**
     * @param $srcNode  源节点
     * @param $dscNode  目标节点（删除节点）
     */
    public function transplantNode($srcNode,$dscNode)
    {
        if($dscNode->parent == null) { //删除的是根节点
            $this->tree = $srcNode;
        }
        elseif($dscNode == $dscNode->parent->left) {
            //删除左节点
            $dscNode->parent->left = $srcNode;
        }
        else {
            $dscNode->parent->right = $srcNode;
        }

        //源节点不空,则把源节点父节点指向目标节点的父节点
        if($srcNode)
        {
            $srcNode->parent = $dscNode->parent;
        }
    }

    public function delete($node)
    {
        if($node->left == null) {//无左节点
            $this->transplantNode($node->right,$node);
        }
        elseif($node->right == null) {//无右节点
            $this->transplantNode($node->left,$node);
        }
        else {
            //获取删除节点的后继节点
            $successorNode = $this->getSuccessor($node);
            //删除节点的右孩子不是后继节点，则转化成后继节点结构
            if($node->right != $successorNode) {
                //后继节点的右孩子替换后继节点
                $this->transplantNode($successorNode->right,$successorNode);
                //设置删除节点的右孩子为后继节点的右孩子
                $successorNode->right = $node->right;
                //删除节点的右孩子的父节点改为后继节点
                $successorNode->right->parent=$successorNode;
            }

            //后继节点替换删除节点
            $this->transplantNode($successorNode,$node);
            //设置删除节点的左孩子为后继节点的左孩子
            $successorNode->left = $node->left;
            //删除节点的左孩子的父节点改为后继节点
            $successorNode->left->parent = $successorNode;

        }
    }

    public function getTree()
    {
        return $this->tree;
    }
}

$object = new BinaryTree();

$object->insert(53);
$object->insert(17);
$object->insert(78);
$object->insert(9);
$object->insert(45);
$object->insert(87);

echo "<pre>";

$node = $object->find(17);
//print_r($node);
print_r($object->findMinNode($node));
//print_r($object->getTree());

