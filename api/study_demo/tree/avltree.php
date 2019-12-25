<?php
/**
 * https://www.cnblogs.com/Lynn-Zhang/p/5643797.html
 * Created by IntelliJ IDEA.
 * User: fankys
 * Date: 2019/11/29
 * Time: 15:18
 */
class Node{
    public $key;
    public $value;
    public $left = null;
    public $right = null;
    public $parent = null;
    public $balancef = 0;

    public function __construct($key,$value)
    {
        $this->key = $key;
        $this->value = $value;
    }
}

class AVLTree{
    public $tree;

    public function __construct()
    {
    }

    public function Insert($key,$value)
    {
        if($this->tree == null) {
            $this->tree = new Node($key,$value);
            return;
        }
        $currNode = $this->tree;
        while ($currNode) {
            if($key < $currNode->key) {
                $parentNode = $currNode;
                $currNode = $currNode->left;
            } elseif ($currNode->key < $key) {
                $parentNode = $currNode;
                $currNode = $currNode->right;
            } else {
                return false;
            }
        }
        $node = new Node($key,$value);
        $node->parent = $parentNode;
        if ($key < $parentNode->key) {
            $parentNode->left = $node;
        }
        else {
            $parentNode->right = $node;
        }

        //更新平衡因子
        while ($parentNode) {
            if ($node == $parentNode->left) {
                $parentNode->balancef --;
            }
            elseif ($node == $parentNode->right) {
                $parentNode->balancef ++;
            }
            if ($parentNode->balancef == 0) {
                break;
            }
            elseif ($parentNode->balancef == -1 || $parentNode->balancef == 1)
            {
                $node = $parentNode;
                $parentNode = $node->parent;
            }
            else{
                if($parentNode->balancef == 2) {
                    if($node->balancef == 1) {
                        //左旋
                        $this->_RotateL($parentNode);
                    } else {
                        //右左双旋
                        $this->_RotateRL($parentNode);
                    }
                }
                elseif ($parentNode->balancef == -2) {
                    if($node->balancef == -1) {
                        //右旋
                        $this->_RotateR($parentNode);
                    } else {
                        //左右双旋
                        $this->_RotateLR($parentNode);
                    }
                }
                break;
            }
        }
    }

    /**
     * 左旋
     * @param $parentNode
     */
    private function _RotateL(&$parentNode)
    {
        $subR = $parentNode->right;
        $subRL = $subR->left;
        $ppNode = $parentNode->parent;//祖先节点

        //1.构建parent子树 链接parent和subRL
        $parentNode->right = $subRL;
        if ($subRL) {
            $subRL->parent = $parentNode;
        }
        //2.构建subR子树 链接parent和subR
        $subR->left = $parentNode;
        $parentNode->parent = $subR;

        //3.链接祖先节点和subR节点
        $subR->parent = $ppNode;

        if($ppNode == null) {
            //如果祖先节点为NULL，说明目前的根节点为subR
            $this->tree = $subR;
        }
        else {
            //将祖先节点和subR节点链接起来
            if($parentNode == $ppNode->left) {
                $ppNode->left = $subR;
            }
            else {
                $ppNode->right = $subR;
            }
        }
        //4.重置平衡因子
        $parentNode->balancef = 0;
        $subR ->balancef = 0;
        //5.更新subR为当前父节点
        $parentNode = $subR;
    }


    /**
     * 右旋
     * @param $parendNode
     */
    private function _RotateR(&$parendNode)
    {
        $subL = $parendNode->left;
        $subLR = $subL->right;
        $ppNode = $parendNode->parent;

        //1.构建parent子树 将parent和subLR链接起来
        $parendNode->left = $subLR;
        if($subLR != null ){
            $subLR->parent = $parendNode;
        }
        //2.构建subL子树 将subL与parent链接起来
        $subL->right = $parendNode;
        $parendNode->parent = $subL;

        //3.将祖先节点与sunL链接起来
        if($ppNode == null){
            $this->tree = $subL;
        }
        else {
            $subL->parent = $ppNode;
            if($ppNode->left == $parendNode) {
                $ppNode->left = $subL;
            } elseif($ppNode->right == $parendNode) {
                $ppNode->right = $subL;
            }
        }
        //4.重置平衡因子
        $parendNode->balancef = 0;
        $subL->balancef = 0;
        $parendNode = $subL;
    }

    /**
     * 左右双悬
     * @param $parentNode
     */
    private function _RotateLR($parentNode)
    {
        $pnode = $parentNode;
        $subL = $parentNode->left;
        $subLR = $subL->right;
        $balancef = $subLR ->balancef;

        //对左孩子进行左旋
        $this->_RotateL($subL);
        //对parentNode 右旋
        $this->_RotateR($parentNode);

        if($balancef == 1) {
            $pnode->balancef = 0;
            $subL->balancef = -1;
        }elseif($balancef == -1) {
            $pnode->balancef = 1;
            $subL->balancef = 0;
        } else{
            $pnode->balancef = 0;
            $subL->balancef = 0;
        }
    }

    /**
     * 右左双旋
     * @param $parendNode
     */
    private function _RotateRL($parendNode)
    {
        $pNode = $parendNode;
        $subR = $parendNode->right;
        $subRL = $subR->left;

        $balancef = $subRL->balancef;

        $this->_RotateR($subR);
        $this->_RotateL($parendNode);

        if($balancef == 1) {
            $pNode->balancef = 0;
            $subR->balancef = -1;
        } elseif ($balancef == -1) {
            $pNode->balancef = -1;
            $subR->balancef = 0;
        } else{
            $pNode->balancef = 0;
            $subR->balancef = 0;
        }

    }
}

$avl = new AVLTree();
$avl->Insert("A","张三");
$avl->Insert("B","李四");
$avl->Insert("C","王五");
$avl->Insert("D","小明");
$avl->Insert("E","小明E");
$avl->Insert("F","小明F");
$avl->Insert("G","小明G");
$avl->Insert("H","小明H");

echo "<pre>";
print_r($avl->tree);