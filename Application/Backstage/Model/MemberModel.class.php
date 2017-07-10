<?php
namespace Backstage\Model;

use Think\Model;
use Think\Page;
use Common\Service\WhereBuilder;
use Member\Model\MoneyModel as MemModel;
use Member\Model\VerifyModel;

class MemberModel extends  Model
{
    protected $tableName = 'members';
    
    public static $vipType = array(
        "<b class='text-main'>[个人]</b>",
        "<b class='text-dot'>[内部发标专员]</b>"
    );

    public static $userType = array(
        1 => '普通借款者',
        2 => '优良借款者',
        3 => '风险借款者',
        4 => '黑名单'
    );
    
    public function layerInfo($uid  = 0){
        $rows = [];      
        if(!empty($uid)){
            $rows['info'] = $this->getInfo($uid);
            //帐户资金
            $rows['capital'] = $this->getCapital($uid);
            $rows['loan'] = $this->getLoan($uid);
            return $rows;
        }else{
            
            return false;
        }
    }
    
    public function getInfo($uid)
    {
        $field = 'm.user_type,m.user_name,m.is_deny,mi.real_name,m.customer_name,m.email,m.mobile,
                  mi.sex,mi.address,mi.profession,mi.marry,mi.id_card,mi.education,mi.income,
                  mi.age,mb.bank_num,mb.bank_province,mb.bank_city,mb.bank_name,mi.idcard_images';
        
        $rows = $this->alias('m')
        ->field($field)
        ->join($this->tablePrefix . 'member_info as mi on m.id = mi.uid', 'left')
        ->join($this->tablePrefix . 'member_banks as mb on m.id = mb.uid', 'left')
        ->where(['m.id'=>$uid])
        ->page($page_now, $page_size)
        ->order($order)
        ->find();
        $rows['user_type_name'] = self::$userType[$rows['user_type']];
        $rows['verify'] = VerifyModel::getMemberStatus($uid);
        $rows['idcard_images'] = explode('@',$rows['idcard_images']);
        $rows['idcard_images'] = empty($rows['idcard_images'][0])?'no image':$rows['idcard_images'][0];
        return $rows;
    }
    
    public function getCapital($uid)
    {
        return (new MemModel())->getPersonalCount($uid);
    }
    
    public function getLoan($uid)
    {
        return (new MemModel())->loanTotalInfo($uid);
    }
    
    public function getMemberList($where = array(),$field = '*',$page_now = 1,$order='m.id desc',$page_size = 0)
    {
        $search = array(
            'user_name'=>array(
                'name'=>'会员名',
                'type'=>'input',
                'tip'=>'不填则不限制',
                'value'=>empty($where['deal_user'])?'':$where['deal_user']
            ),
            'real_name'=>array(
                'name'=>'真实姓名',
                'type'=>'input',
                'tip'=>'不填则不限制',
                'value'=>empty($where['deal_user'])?'':$where['deal_user']
            ),
            'customer_name'=>array(
                'name'=>'所属客服',
                'type'=>'input',
                'tip'=>'不填则不限制',
                'value'=>empty($where['deal_user'])?'':$where['deal_user']
            ),
            'star'=>array(
                'name'=>'注册时间(开始)',
                'type'=>'date',
                'tip'=>'只选开始时间则查询从开始时间往后所有',
                'value'=>empty($where['star'])?'':$where['star']
            ),
            'end'=>array(
                'name'=>'注册时间(结束)',
                'type'=>'date',
                'tip'=>'只选开始时间则查询从开始时间往后所有',
                'value'=>empty($where['end'])?'':$where['end']
            ),
        
        );
       
        $page_size = empty($page_size) ? C('PAGE_SIZE') : $page_size;
        $page_now = empty($page_now) ? 1 : $page_now;
        $rows = $this->alias('m')
            ->field($field)
            ->join($this->tablePrefix . 'member_money as mm on m.id = mm.uid', 'left')
            ->join($this->tablePrefix . 'member_info as mi on m.id = mi.uid', 'left')
            ->where($where)
            ->page($page_now, $page_size)
            ->order($order)
            ->select();
        foreach ($rows as $k=>$v){
            if($v['recommend_id']>0){
                $rows[$k]['recommend_name'] = $this->where(['id'=>$v['recommend_id']])->cache(true)->getField('user_name');
            }
            if(!empty($v['user_type'])){
                $rows[$k]['user_type_name'] = self::$userType[$v['user_type']];  
            }
        }
        $count = $this->alias('m')
            ->field($field)
            ->join($this->tablePrefix . 'member_money as mm on m.id = mm.uid', 'left')
            ->join($this->tablePrefix . 'member_info as mi on m.id = mi.uid', 'left')
            ->where($where)
            ->order($order)
            ->count();
        $page = new Page($count, $page_size, I('post.'));
        $page_show = $page->show();
        $row = array(
            'rows'=>$rows,
            'page'=>$page_show,
            'search'=>WhereBuilder::buidFrom($search),
        );
        return $row;
    }
    
    
    public function getRecommendList($where = array(),$field = '*',$page_now = 1,$order='m.id desc',$page_size = 0)
    {
        $search = array(
            'user_name'=>array(
                'name'=>'会员名',
                'type'=>'input',
                'tip'=>'不填则不限制',
                'value'=>empty($where['deal_user'])?'':$where['deal_user']
            ),
            'real_name'=>array(
                'name'=>'推广人',
                'type'=>'input',
                'tip'=>'不填则不限制',
                'value'=>empty($where['deal_user'])?'':$where['deal_user']
            ),   
            'star'=>array(
                'name'=>'交易时间(开始)',
                'type'=>'date',
                'tip'=>'只选开始时间则查询从开始时间往后所有',
                'value'=>empty($where['star'])?'':$where['star']
            ),
            'end'=>array(
                'name'=>'交易时间(结束)',
                'type'=>'date',
                'tip'=>'只选开始时间则查询从开始时间往后所有',
                'value'=>empty($where['end'])?'':$where['end']
            ),
        
        );
        $page_size = empty($page_size) ? C('PAGE_SIZE') : $page_size;
        $page_now = empty($page_now) ? 1 : $page_now;
        $rows = '';
        $page = new Page($count, $page_size, I('post.'));
        $page_show = $page->show();
        $row = array(
            'rows' => $rows,
            'page' => $page_show,
            'search' => WhereBuilder::buidFrom($search)
        );
        return $row;
    }
    
    
    
}