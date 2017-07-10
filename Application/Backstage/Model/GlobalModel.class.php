<?php
namespace Backstage\Model;

use Think\Model;

class GlobalModel extends Model{
    
    public function getList()
    {
        return $this->field('*')->select();
    }
    
    public function saveGlobal($data)
    {
        //@TODO 这边要验证
        $where = array();
        $result = true;
        $this->startTrans();
        foreach ($data as $k=>$v){
           $where['code'] = $k;
    
           if(!$this->where($where)->save(array('text'=>$v,'update_time'=>time()))){ 
               $result = false;
               $this->rollback();
               break;
           }
           
        }
        if($result === true){
            //@TODO 日志
            //(new LogModel())->logs()
            return true;
        }else{
            return '插入数据失败';
        }
      
    }
    
}