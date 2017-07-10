<?php
/**
 * 
 * @Author ZYIMM <799783009@qq.com>
 * @version 1.0
 * Create By 2017年4月18日 下午10:33:26  版权所有@copyright 2013~2017 www.zyimm.com
 */
namespace Member\Model;

use Think\Model;
use Think\Page;

class MessageModel extends Model
{
    protected $tableName = 'insite_msg';
    
    public function getMessageList($where = array(),$field='*',$page_now = 1,$order = 'status desc,uid desc')
    {
        $row = array();
        
        $list = $this->field($field)->where($where)->order($order)->limit($page_now,C('PAGE_SIZE'))->select();
        $count = $this->field($field)->where($where)->order($order)->count('id');
        $page = new Page($count,C('PAGE_SIZE'),$where);
        $row = array(
            'list'=>$list,
            'page'=>$page->ajaxShow()
            
        );
        
        return $row;
    }
}