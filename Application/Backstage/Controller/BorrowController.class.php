<?php
namespace Backstage\Controller;

use Backstage\Model\BorrowModel;
use Front\Model\ConfigModel;

class BorrowController extends BaseController
{

    public $model = null;

    public function first()
    {
        $this->model = new BorrowModel();
        
        $field = 'b.id,b.borrow_name,b.borrow_uid,
                  b.borrow_duration,b.borrow_type,
                  b.updata,b.borrow_money,b.borrow_fee,
                  b.borrow_interest_rate,b.repayment_type,
                  b.add_time,m.user_name,m.id as mid,
                  b.is_recommend,b.money_collect';
        $where = I('post.');
        $where['b.borrow_status'] = 0;
        $data = $this->model->getBorrowList($where,$field,I('get.p',1,'intval'));
        $this->assign('rows',$data['rows']);
        $this->assign('search',$data['search']);
        $this->assign('page',$data['page']);
        $config = ConfigModel::read('borrow_config');
        $this->assign('repayment_type',$config['REPAYMENT_TYPE']);
        $this->display();
    }

    public function second()
    {
        $this->model = new BorrowModel();
        
        $field = 'b.id,b.borrow_name,b.borrow_uid,b.borrow_status,
                  b.borrow_duration,b.borrow_type,
                  b.updata,b.borrow_money,b.borrow_fee,
                  b.borrow_interest_rate,b.repayment_type,
                  b.full_time,m.user_name,m.id as mid,
                  b.is_recommend,b.money_collect';
        $where = I('post.');
        $where['b.borrow_status'] = 4;
        $data = $this->model->getBorrowList($where,$field,I('get.p',1,'intval'));
        $this->assign('rows',$data['rows']);
        $this->assign('search',$data['search']);
        $this->assign('page',$data['page']);
        $config = ConfigModel::read('borrow_config');
        $this->assign('repayment_type',$config['REPAYMENT_TYPE']);
        $this->display();
        
    }

    public function ing()
    {
        $this->model = new BorrowModel();
        
        $field = 'b.id,b.borrow_name,b.borrow_uid,
                  b.borrow_duration,b.borrow_type,
                  b.updata,b.borrow_money,b.borrow_fee,
                  b.borrow_interest_rate,b.repayment_type,
                  b.add_time,m.user_name,m.id as mid,
                  b.is_recommend,b.money_collect';
        $where = I('post.');
        $where['b.borrow_status'] = 2;
        $data = $this->model->getBorrowList($where,$field,I('get.p',1,'intval'));
        $this->assign('rows',$data['rows']);
        $this->assign('search',$data['search']);
        $this->assign('page',$data['page']);
        $config = ConfigModel::read('borrow_config');
        $this->assign('repayment_type',$config['REPAYMENT_TYPE']);
        $this->display();
        
    }

    public function repay()
    {
        $this->model = new BorrowModel();
        
        $field = 'b.id,b.borrow_name,b.borrow_uid,
                  b.borrow_duration,b.borrow_type,
                  b.updata,b.borrow_money,b.borrow_fee,
                  b.borrow_interest_rate,b.repayment_type,
                  b.repayment_money,b.repayment_interest,
                  b.add_time,m.user_name,m.id as mid,
                  b.is_recommend,b.money_collect';
        $where = I('post.');
        $where['b.borrow_status'] = 6;
        $data = $this->model->getBorrowList($where,$field,I('get.p',1,'intval'));
        $this->assign('rows',$data['rows']);
        $this->assign('search',$data['search']);
        $this->assign('page',$data['page']);
        $config = ConfigModel::read('borrow_config');
        $this->assign('repayment_type',$config['REPAYMENT_TYPE']);
        $this->display();
        
    }

    public function done()
    { 
        $this->model = new BorrowModel();
        
        $field = 'b.id,b.borrow_name,b.borrow_uid,b.deadline,
                      b.borrow_duration,b.borrow_type,
                      b.updata,b.borrow_money,b.borrow_fee,
                      b.borrow_interest_rate,b.repayment_type,
                      b.repayment_money,b.repayment_interest,
                      b.add_time,m.user_name,m.id as mid,
                      b.is_recommend,b.money_collect';
        $where = I('post.');
        $where['b.borrow_status'] = array("in","7,9");
        $data = $this->model->getBorrowList($where,$field,I('get.p',1,'intval'));
        $this->assign('rows',$data['rows']);
        $this->assign('search',$data['search']);
        $this->assign('page',$data['page']);
        $config = ConfigModel::read('borrow_config');
        $this->assign('repayment_type',$config['REPAYMENT_TYPE']);
        $this->display();
        
    }
    
    public function fail()
    {
        $this->model = new BorrowModel();
        
        $field = 'b.id,b.borrow_name,b.borrow_uid,
                  b.borrow_duration,b.borrow_type,
                  b.updata,b.borrow_money,b.borrow_fee,
                  b.borrow_interest_rate,b.repayment_type,
                  b.add_time,m.user_name,m.id as mid,
                  b.is_recommend,b.money_collect,v.first_deal_info as deal_info,
                  v.first_deal_time as deal_time,v.first_deal_user as deal_user';
        $where = I('post.');
        $where['b.borrow_status'] = 3;
        $data = $this->model->getBorrowList($where,$field,I('get.p',1,'intval'));
        $this->assign('rows',$data['rows']);
        $this->assign('search',$data['search']);
        $this->assign('page',$data['page']);
        $config = ConfigModel::read('borrow_config');
        $this->assign('repayment_type',$config['REPAYMENT_TYPE']);
        $this->display();
    }

    public function firstFail()
    {
        
    }
    
    public function secondFail()
    {
        
    }
    
    public function expired()
    {
        
    }
    public function expiredMember()
    {
        
    }
    
    public function verify()
    {
        
    }
    
    public function doVerify()
    {
    
    }
}