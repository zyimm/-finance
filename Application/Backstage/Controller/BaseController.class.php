<?php
namespace Backstage\Controller;

use Think\Controller;
use Backstage\Model\AdminModel;
use Think\Crypt;
use Backstage\Model\MenuModel;
use Backstage\Model\LogModel;

class BaseController extends Controller
{
    public $adminId = NULL;
    
    public $adminName = NULL;
    
    public static $logModel = NULL;
    public  function __construct()
    {
        parent::__construct();
        if(session('admin_id')){
            $this->adminId = Crypt::decrypt(session('admin_id'),C('CRYPT_KEY'));
            $this->adminName = Crypt::decrypt(session('admin_name'),C('CRYPT_KEY'));
            if((int)$this->adminId>0){
                //判断权限
                $_url = strtolower(__ACTION__);
                $_url =  explode('/',$_url);
                $this->assign('menu_id',MenuModel::getMenuId(join('/',[$_url[2],$_url[3]])));
                $this->assign('admin_info',(new AdminModel())->getAdminInfo($this->adminId));
                $this->assign('empty',"<tr><td colspan=20 class='text-main' ><i class='iconfont text-large text-dot padding-right'>&#xe606;</i>暂无数据!!!</td></tr>");
                //log
                self::$logModel = new LogModel();
            }else{
                session_destroy();
                $this->redirect('/Backstage/Sign');
            }
           
        }else{
            session_destroy();
            $this->redirect('/Backstage/Sign');
        }
    }
    
}