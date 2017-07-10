<?php
namespace Backstage\Controller;

use Backstage\Model\LinkModel;

class LinkController extends BaseController
{
    public $model = null;
    
    public function index()
    {
        $this->model = new LinkModel();
        $where = I('post.');
        $field = '*';
        $data = $this->model->getLinkList($where,$field,I('get.p',1,'intval'));
        $this->assign('rows',$data['rows']);
        $this->assign('search',$data['search']);
        $this->assign('page',$data['page']);
        $this->assign('link_type',['已经交换','未交换']);
        $this->assign('is_show',['不显示','显示']);
        $this->display();
    }
    
    public  function add()
    {
        
    }
    
    public function edit()
    {
        
    }
    
    public function del()
    {
        
    }
}