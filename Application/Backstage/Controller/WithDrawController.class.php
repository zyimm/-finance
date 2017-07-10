<?php
namespace Backstage\Controller;

use Backstage\Model\WithDrawModel;

class WithDrawController extends BaseController
{

    public $model = NULL; 
    
    
    
    public function index()
    {
        
        $this->model = new WithDrawModel();
        $where = I('post.');
        $field = 'w.*,m.user_name,mi.real_name,w.id,w.uid,(mm.account_money+mm.back_money) all_money';
        $data = $this->model->getWithDrawList(I('post.'),$field,I('get.p',1,'intval'));
        $this->assign('rows',$data['rows']);
        $this->assign('search',$data['search']);
        $this->assign('page',$data['page']);
        $this->assign('status',C('WITHDRAW_STATUS'));
        $this->display('index');
    }

    public function wait()
    {
        $this->model = new WithDrawModel();
        $where = I('post.');
        $where['w.withdraw_status'] = 0;
        $field = 'w.*,m.user_name,mi.real_name,w.id,w.uid,(mm.account_money+mm.back_money) all_money';
        $data = $this->model->getWithDrawList($where,$field,I('get.p',1,'intval'));
        $this->assign('rows',$data['rows']);
        $this->assign('search',$data['search']);
        $this->assign('page',$data['page']);
        $this->assign('status',C('WITHDRAW_STATUS'));
        $this->assign('jsMethod','firstDeal');
        $this->display('index');
    }

    public function doing()
    {
        $this->model = new WithDrawModel();
        $where = I('post.');
        $where['w.withdraw_status'] = 1;
        $field = 'w.*,m.user_name,mi.real_name,w.id,w.uid,(mm.account_money+mm.back_money) all_money';
        $data = $this->model->getWithDrawList($where,$field,I('get.p',1,'intval'));
        $this->assign('rows',$data['rows']);
        $this->assign('search',$data['search']);
        $this->assign('page',$data['page']);
        $this->assign('status',C('WITHDRAW_STATUS'));
        $this->assign('jsMethod','secondDeal');
        $this->display('index');
        
    }

    public function done()
    {
        $this->model = new WithDrawModel();
        $where = I('post.');
        $where['w.withdraw_status'] = 2;
        $field = 'w.*,m.user_name,mi.real_name,w.id,w.uid,(mm.account_money+mm.back_money) all_money';
        $data = $this->model->getWithDrawList($where,$field,I('get.p',1,'intval'));
        $this->assign('rows',$data['rows']);
        $this->assign('search',$data['search']);
        $this->assign('page',$data['page']);
        $this->assign('status',C('WITHDRAW_STATUS'));
        $this->display('index');
        
    }

    public function no()
    {
        $this->model = new WithDrawModel();
        $where = I('post.');
        $where['w.withdraw_status'] = 3;
        $field = 'w.*,m.user_name,mi.real_name,w.id,w.uid,(mm.account_money+mm.back_money) all_money';
        $data = $this->model->getWithDrawList($where,$field,I('get.p',1,'intval'));
        $this->assign('rows',$data['rows']);
        $this->assign('search',$data['search']);
        $this->assign('page',$data['page']);
        $this->assign('status',C('WITHDRAW_STATUS'));
        $this->display('index');
        
    }
    
    public function firstDeal()
    {
        $this->display();
    }
    
    public function secondDeal()
    {
        $this->display();
    }
}