<?php
// +----------------------------------------------------------------------
// | PHP400 [ tree V1.0 beta]
// +----------------------------------------------------------------------
// | Copyright (c) 2016 http://www.zyimmc.om All rights reserved.
// +----------------------------------------------------------------------
// | Author: zyimm <799783009@qq.com.com>
// +----------------------------------------------------------------------
namespace Common\Model;

class TreeModel
{

    private $categorys = array();

    private $parentArray = array();

    public function __construct($arr)
    {
        foreach ($arr as $v) {
            $this->setOption($v['id'], $v['pid'], $v['classname']);
        }
    }

    public function setOption($id, $pid, $val)
    {
        $this->categorys[$id] = $val;
        $this->parentArray[$id] = $pid;
    }

    public function getAllChilds($id = 0)
    {
        $childArray = array();
        $childs = $this->getChilds($id);
        foreach ($childs as $child) {
            $childArray[] = $child;
            $childArray = array_merge($childArray, $this->getAllChilds($child));
        }
        return $childArray;
    }
   
    private function getChilds($id)
    {
        //   
        $childs = array();
        foreach ($this->parentArray as $child => $parent) {
            if ($parent == $id)
                $childs[] = $child;
        }
        return $childs;
    }

    public function getValue($id)
    {
        return $this->categorys[$id];
    }
    private function getLever($id)
    {
        $parents = array();
        if (array_key_exists($this->parentArray[$id], $this->parentArray)) {
            $parents[] = $this->parentArray[$id];
            $parents = array_merge($parents, $this->getLever($this->parentArray[$id]));
        }
        return $parents;
    }

    public function setMark($id)
    {
        $num = count($this->getLever($id));
        $mark = str_repeat('&nbsp;', $num);
        return $mark . '|--';
    }

    public function getTrees()
    {
        $row = array();
        foreach ($this->getAllChilds() as $id) {
            $row[$id] = $this->setMark($id) . $this->getValue($id);
        }

        return $row;
    }
}
?>