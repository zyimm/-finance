<?php
namespace Backstage\Controller;

use Backstage\Model\MenuModel;

class MenuController extends BaseController
{
    public $model = null;
    
    public function navList(){
        $this->model = new MenuModel();
        $where = I('post.');
        $field = '*';
        $data = $this->model->navigation($where,$field,I('get.p',1,'intval'));
        $this->assign('rows',$data['rows']);
        $this->assign('search',$data['search']);
        $this->assign('page',$data['page']);
       
        $this->display('navList');
    }
    
    public function navAdd()
    {
        
    }
    
    public function navEdit()
    {
        
    }
    
    public function delete()
    {
        
    }
}