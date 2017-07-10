<?php
namespace Backstage\Controller;

use Backstage\Model\VerifyModel;

class VerifyController extends BaseController
{
    
    public function mobile()
    {
        $this->_model = new VerifyModel();
        $page_now = I('get.p',1);
        $map = I('post.');
        $map['ms.mobile_status'] =  1;
        $field = 'ms.uid,ms.mobile_status,m.user_name,mi.real_name,
                  m.customer_name,m.reg_time,mm.account_money,
                  mm.money_freeze,mm.money_collect,m.mobile';
        $row = $this->_model->getVerifyList($map,$field,$page_now);
        $this->assign('rows',$row['rows']);
        $this->assign('search',$row['search']);
        $this->assign('page',$row['page']);
        $this->display();
    }
    
    public function email()
    {
        $this->_model = new VerifyModel();
        $page_now = I('get.p',1);
        $map = I('post.');
        $map['ms.email_status'] =  1;
        $field = 'ms.uid,ms.email_status,m.user_name,mi.real_name,
                  m.customer_name,m.reg_time,mm.account_money,
                  mm.money_freeze,mm.money_collect,m.email';
        $row = $this->_model->getVerifyList($map,$field,$page_now);
        $this->assign('rows',$row['rows']);
        $this->assign('search',$row['search']);
        $this->assign('page',$row['page']);
        $this->display();
    }
    
    public function id()
    {
        $this->_model = new VerifyModel();
        $page_now = I('get.p',1);
        $map = I('post.');
        $map['ms.id_status'] =  2;
        $field = 'ms.uid,ms.id_status,m.user_name,mi.real_name,
                  m.customer_name,m.reg_time,mm.account_money,mi.id_card,
                  mm.money_freeze,mm.money_collect,mi.idcard_images';
        $row = $this->_model->getVerifyList($map,$field,$page_now);

        $this->assign('rows',$row['rows']);
        $this->assign('search',$row['search']);
        $this->assign('page',$row['page']);
        $this->display();
    }
    
    public function vip()
    {
        $this->_model = new VerifyModel();
        $page_now = I('get.p',1);
        $row = $this->_model->getVipList($page_now);
        $this->assign("status", array('待审核','已通过审核','未通过审核'));
        $this->assign('rows',$row['rows']);
        $this->assign('search',$row['search']);
        $this->assign('page',$row['page']);
        $this->display();
    }
    
    public function apply()
    {
        $this->_model = new VerifyModel();
        $page_now = I('get.p',1);
        $row = $this->_model->getApplyList($page_now);
        $this->assign("status", array('待审核','已通过审核','未通过审核'));
        $this->assign('rows',$row['rows']);
        $this->assign('search',$row['search']);
        $this->assign('page',$row['page']);
        $this->display();
    }
    
    public function doApply()
    {
        $id  = I('post.id',0,'intval');
        if(!empty($id)){
            layout(false);
            $this->assign('id',$id);
            $this->assign('val',M('member_apply')->where(['id'=>$id])->getField('apply_money'));
            $html = $this->fetch('doApply');
            $this->success($html);
    
        }else{
            $this->error('id错误');
        }
    }
    
    public function saveApply()
    {
        $this->_model = new VerifyModel();
        $result = $this->_model->saveApply(I('post.'),$this->adminName);
        if($result === true){
            $this->success('操作成功!');
            
        }else{
            $this->error($result);
        }
    }
    
    public function doVip()
    {
        $id  = I('get.id',0,'intval');
        if(!empty($id)){
            
            
        }else{
            $this->error('id错误');
        }
    }
    
    
}