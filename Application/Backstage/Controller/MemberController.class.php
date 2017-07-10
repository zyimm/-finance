<?php
namespace Backstage\Controller;

use Backstage\Model\MemberModel;

class MemberController extends BaseController
{
    public function index()
    {
        $this->_model = new MemberModel();
        $page_now = I('get.p',1);
        $map = I('post.');
        $field = 'm.id,m.is_vip,m.user_name,mi.real_name,m.recommend_id,
                  m.customer_name,m.user_type,m.reg_time,mm.account_money,
                  mm.money_freeze,mm.money_collect';
        $row = $this->_model->getMemberList($map,$field,$page_now);
        $this->assign('rows',$row['rows']);
        $this->assign('search',$row['search']);
        $this->assign('page',$row['page']);
        $this->assign('vip_name',MemberModel::$vipType);
        $this->display();
    }
    
    public function info()
    {
        $this->_model = new MemberModel();
        $field = 'm.id,m.user_name,mi.*,m.recommend_id,
                  m.user_type,m.reg_time';
        $page_now = I('get.p',1);
        $map = I('post.');
        $row = $this->_model->getMemberList($map,$field,$page_now);
        $this->assign('rows',$row['rows']);
        $this->assign('search',$row['search']);
        $this->assign('page',$row['page']);
        $this->display();
    }
    
    public function recommend()
    {
        $this->_model = new MemberModel();
        $field = 'm.id,m.user_name,mi.*,m.recommend_id,
                  m.user_type,m.reg_time';
        $page_now = I('get.p',1);
        $map = I('post.');
        $row = $this->_model->getRecommendList($map,$field,$page_now);
        $this->assign('rows',$row['rows']);
        $this->assign('search',$row['search']);
        $this->assign('page',$row['page']);
        $this->display();
    }
    
    public function  layerInfo()
    {
        $uid = I('get.id',0,'intval');

        $this->assign('data',(new MemberModel())->layerInfo($uid));
       
        $this->display('layerInfo');
    }
}