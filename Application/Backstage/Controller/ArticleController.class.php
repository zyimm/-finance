<?php
namespace Backstage\Controller;

use Front\Model\ArticleModel;

class ArticleController extends BaseController
{
    public $model = null;
    
    public function index()
    {
       $this->model = new ArticleModel();
       $where = I('post.');
       $field = 'a.*,c.type_name';
       $data = $this->model->article($where,$field,I('get.p',1,'intval'));
       $this->assign('rows',$data['rows']);
       $this->assign('search',$data['search']);
       $this->assign('page',$data['page']);
       $this->assign('status',C('WITHDRAW_STATUS'));
       $this->display('article');
    }
    
    public function category()
    {
        $this->model = new ArticleModel();
        $where = I('post.');
        $field = 'id,type_name as classname,parent_id as pid,type_set,sort_order,add_time,type_nid';
        $data = $this->model->category($where,$field,I('get.p',1,'intval'));
        $this->assign('rows',$data['rows']);
        $this->assign('search',$data['search']);
        $this->assign('page',$data['page']);
        $this->assign('type_name',['单页','列表']);
        $this->assign('tree',$data['tree']);
        $this->display('category');
    }
    
    public function addCategory()
    {
        if(IS_POST){
            
        }else{
            $this->display('addCategory');
        }
    }
    
    public function addArticle()
    {
        if(IS_POST){
            
        }else{
            $this->display('addArticle');
        }
        
    }
    
    public function ad()
    {
        $this->model = new ArticleModel();
        $where = I('post.'); 
        $field = '*';
        $data = $this->model->ad($where,$field,I('get.p',1,'intval'));
        $this->assign('rows',$data['rows']);
        $this->assign('search',$data['search']);
        $this->assign('page',$data['page']);
        $this->display('ad');
    }
    
}