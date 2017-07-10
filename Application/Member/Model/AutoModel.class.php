<?php 
namespace Member\Model;


use Think\Model;

class AutoModel extends Model
{
    
    protected $tableName = 'auto_borrow';
    public function autoList()
    {
        $map = [
            'is_use'=>1
        ];
        $auto_list = array();
        $auto_list = $this->alias('a')->join($this->tablePrefix."member_money m ON a.uid=m.uid",'left')
        ->field("a.*, m.account_money+m.back_money as money")
        ->where($map)
        ->order("a.invest_time asc")
        ->select();
        return $auto_list;
    }
}