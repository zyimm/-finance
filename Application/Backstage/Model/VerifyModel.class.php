<?php
namespace Backstage\Model;

use Think\Model;
use Think\Page;
use Front\Model\MessageModel;

class VerifyModel extends Model
{
    protected $tableName = 'member_status';
    
    public function getVerifyList($where = [],$field='*',$page_now = 1,$page_size=0)
    {

        $page_size = empty($page_size) ? C('PAGE_SIZE') : $page_size;
        $page_now = empty($page_now) ? 1 : $page_now;
        $rows = $this->alias('ms')
            ->field($field)
            ->join($this->tablePrefix . 'members as m on ms.uid = m.id')
            ->join($this->tablePrefix . 'member_info as mi on ms.uid = mi.uid', 'left')
            ->join($this->tablePrefix . 'member_money as mm on ms.uid = mm.uid', 'left')
            ->where($where)
            ->order('ms.uid desc')
            ->page($page_now, $page_size)
            ->select();
        $count = $this->alias('ms')
            ->field($field)
            ->join($this->tablePrefix . 'members as m on ms.uid = m.id')
            ->join($this->tablePrefix . 'member_info as mi on ms.uid = mi.uid', 'left')
            ->join($this->tablePrefix . 'member_money as mm on ms.uid = mm.uid', 'left')
            ->where($where)->count();
        $page = new Page($count, $page_size, I('post.'));
        $page_show = $page->show();
        $row = array(
            'rows' => $rows,
            'page' => $page_show,
           /*  'search' => WhereBuilder::buidFrom($search) */
        );
        return $row;
    }
    
    public function getVipList($page_now = 1,$page_size = 0)
    {
        $page_size = empty($page_size) ? C('PAGE_SIZE') : $page_size;
        $page_now = empty($page_now) ? 1 : $page_now;
        
        $where = array();
        $field = 'v.*,m.user_name,mi.real_name,v.customer_id';
        $rows = $this->table($this->tablePrefix . 'vip_apply as v')
            ->field($field)
            ->join($this->tablePrefix . 'members as m on v.uid = m.id')
            ->join($this->tablePrefix . 'member_info as mi on mi.uid = v.uid', 'left')
            ->where($where)
            ->order('v.id desc')
            ->page($page_now, $page_size)
            ->select();
        $count = $this->table($this->tablePrefix . 'vip_apply as v')
            ->field($field)
            ->join($this->tablePrefix . 'members as m on v.uid = m.id')
            ->join($this->tablePrefix . 'member_info as mi on mi.uid = v.uid', 'left')
            ->where($where)
            ->count();
        foreach ($rows as $k => $v) {
            $rows[$k]['customer_name'] = $this->table($this->tablePrefix . 'backstage_users')
                ->where([
                'id' => $v['customer_id']
                ])->getField('user_name');
            $rows[$k]['des'] = mb_strcut($v['des'],0,12,'utf8');
        }
        $page = new Page($count, $page_size, I('post.'));
        $page_show = $page->show();
        $row = array(
            'rows' => $rows,
            'page' => $page_show,
        );
        return $row;
    }
    
    public function getApplyList($page_now = 1,$page_size = 0)
    {
        $page_size = empty($page_size) ? C('PAGE_SIZE') : $page_size;
        $page_now = empty($page_now) ? 1 : $page_now;
        $where = array();
        $field = 'v.*,m.user_name,mi.real_name,m.customer_id';
        $rows = $this->table($this->tablePrefix . 'member_apply as v')
            ->field($field)
            ->join($this->tablePrefix . 'members as m on v.uid = m.id')
            ->join($this->tablePrefix . 'member_info as mi on mi.uid = v.uid', 'left')
            ->where($where)
            ->order('v.apply_status asc')
            ->page($page_now, $page_size)
            ->select();
        $count = $this->table($this->tablePrefix . 'member_apply as v')
            ->field($field)
            ->join($this->tablePrefix . 'members as m on v.uid = m.id')
            ->join($this->tablePrefix . 'member_info as mi on mi.uid = v.uid', 'left')
            ->where($where)
            ->count();
        foreach ($rows as $k => $v) {
            $rows[$k]['customer_name'] = $this->table($this->tablePrefix . 'backstage_users')
                ->where([
                'id' => $v['customer_id']
            ])->getField('user_name');
            $rows[$k]['apply_info_desc'] = mb_strcut($v['apply_info'], 0, 12, 'utf8');

        }
        $page = new Page($count, $page_size, I('post.'));
        $page_show = $page->show();
        $row = array(
            'rows' => $rows,
            'page' => $page_show
        );
        return $row;
    }
    
    public function saveApply($data=[],$admin_name)
    {
        if(!empty($data['id'])){
            $is_pass =  (int)$data['is_pass'];
            if((int)$data['money']<1){
                return '金额不对!';
            }
            $this->startTrans();
            $where = [
                'id'=>$data['id'],
                
            ];
           $_data = [
              'credit_money'=>$data['money'], 
              'apply_status'=>($is_pass == 1)?$is_pass:2,
              'deal_user'=>$admin_name,
              'deal_time'=>time(),
              'deal_info'=>(string)$data['deal_info']
           ];
            $result = M('member_apply')->where($where)->save($_data);
            if($result){
                //日志
                (new LogModel())->logs($data['id'],0,'额度修改为'.$data['money']);
                //通过
                if($is_pass == 1){
                    $uid = M('member_apply')->where($where)->getField('uid');
                    $where = [
                        'uid'=>$uid
                    ];
                    $member_result = M('member_money')->where($where)->setField('credit_cuse', $data['money']);
                    if($member_result){
                        MessageModel::addInsideMsg($uid,'额度申请提示','额度申请为'.$data['money'].'申请成功');
                        $this->commit();
                       
                        return true;
                    }else{
                        $this->rollback();
                        return '额度操作失败';
                    }
                }else{
                    $this->commit();
                    MessageModel::addInsideMsg($uid,'额度申请提示','额度申请为'.$data['money'].'申请失败,原因：'.(string)$data['deal_info']);
                    return true;
                }
                
            }else{
                $this->rollback();
                return '操作失败';
            }
            
           
        }else{
            
            return 'id 不存在!';
        }
    }
    
    
    public function doApply($id = 0)
    {
        
    }
}