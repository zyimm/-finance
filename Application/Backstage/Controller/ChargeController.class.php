<?php
namespace Backstage\Controller;

use Backstage\Model\ChargeModel;

class ChargeController extends BaseController
{
    
    
    public function online()
    { 
        $model = new ChargeModel();
       
        $where = [
            
            'p.way'=>['not in','off']
        ];
        $field = 'p.*,m.user_name';
        $page_now = I('get.p',1);
        $row = $model->getChargeList($where,$field,$page_now);
        $this->assign('way',$model::$config);
        $this->assign('status',$model::$payStatus);
        $this->assign('rows',$row['rows']);
        $this->assign('search',$row['search']);
        $this->assign('page',$row['page']);
        $this->display('index');
    }
    
    public function offline()
    {
        $model = new ChargeModel();
         
        $where = [
        
            'p.way'=>'off'
        ];
        $field = 'p.*,m.user_name';
        $page_now = I('get.p',1);
        $row = $model->getChargeList($where,$field,$page_now);
        $this->assign('way',$model::$config);
        $this->assign('status',$model::$payStatus);
        $this->assign('rows',$row['rows']);
        $this->assign('search',$row['search']);
        $this->assign('page',$row['page']);
        $this->display('index');
    }
    
    public function log()
    {
        $model = new ChargeModel();
 
        $field = 'p.*,m.user_name';
        $page_now = I('get.p',1);
        $row = $model->getChargeList($where,$field,$page_now);
        $this->assign('way',$model::$config);
        $this->assign('status',$model::$payStatus);
        $this->assign('rows',$row['rows']);
        $this->assign('search',$row['search']);
        $this->assign('page',$row['page']);
        $this->display('index');
    }
    
    public function deal()
    {
        if(IS_POST){
            $id = I('post.id',0,'intval');
            $status = I('post.status',3,'intval');
            $model = new ChargeModel();
            $model->deal($id,$status);
            
        }else{
            $this->success($this->fetch());
        }
        
    }
    
}