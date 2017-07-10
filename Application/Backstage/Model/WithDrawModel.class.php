<?php
namespace Backstage\Model;

use Think\Model;
use Think\Page;
use Front\Model\ToolModel;

class WithDrawModel extends Model
{
    protected $tableName = 'member_withdraw';

    public function getWithDrawList($where = array(), $field = '*', $page_now = 1, $page_size = 0)
    {
        $page_size = empty($page_size) ? C('PAGE_SIZE') : $page_size;
        $page_now = empty($page_now) ? 1 : $page_now;
        
        
        $rows = $this->alias('w')
            ->field($field)
            ->join("{$this->tablePrefix}members m ON w.uid=m.id",'left')
            ->join("{$this->tablePrefix}member_info mi ON w.uid=mi.uid",'left')
            ->join("{$this->tablePrefix}member_money mm on w.uid = mm.uid",'left')
            ->where($where)
            ->order(' w.id DESC ')
            ->page($page_now,$page_size)
            ->select();
        $count = $this->alias('w')
            ->field($field)
            ->join("{$this->tablePrefix}members m ON w.uid=m.id", 'left')
            ->join("{$this->tablePrefix}member_info mi ON w.uid=mi.uid", 'left')
            ->join("{$this->tablePrefix}member_money mm on w.uid = mm.uid", 'left')
            ->where($where)
            ->order(' w.id DESC ')
            ->count();
        $page = new Page($count, $page_size, I('post.'));
        $page_show = $page->show();
        return array(
            'rows' => $rows,
            'page' => $page_show,
            'search'=>''
        ); 
    }
    
    
}