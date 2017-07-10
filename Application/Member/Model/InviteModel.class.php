<?php
namespace Member\Model;

use Think\Model;

class InviteModel extends Model
{
    public function log($uid = 0)
    {
        
        $where = array(
            'uid'=>$uid,
            'type'=>array('in',array(1,13))
        );
        $member_model = new MemberModel();
        $list = $member_model->getMoneyLog($map, C('PAGE_SIZE'));
        
        $total = $this->table($this->tablePrefix.'member_moneylog')->where($where)->sum('affect_money');
        $data = array(
            'total'=>$total,
            'list'=>$list
        );
        return $data;
    }
    public function friends($uid = 0)
    {
        $pre = C('DB_PREFIX');
        $field = " m.id,m.user_name,m.reg_time,sum(ml.affect_money) jiangli ";
        $data = array();
        $_vm = $this->table($this->tablePrefix . "members")
            ->alias('m')
            ->field($field)
            ->join("{$pre}member_moneylog ml ON m.id = ml.target_uid ", 'left')
            ->where(" m.recommend_id ={$uid} AND ml.type =13")
            ->group("ml.target_uid")
            ->select();
        $field = " m.user_name,m.reg_time";
        $vm_ = M("members m")->field($field1)
            ->where(" m.recommend_id ={$uid}")
            ->group("m.id")
            ->select();
        $data = array(
            'vm' => $_vm,
            'vi' => $vm_
        );
        return $data;
    }   
}