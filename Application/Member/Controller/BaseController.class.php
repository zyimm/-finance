<?php
namespace Member\Controller;

use Think\Controller;
use Think\Crypt;
use Member\Model\MenuModel;
use Front\Model\GlobalModel;

class BaseController extends Controller
{


    
    public $uid = NULL;
    
    public $userName = NULL;
    
    public $title;
       
    public static $denyLayout = array('index');
    
    public  function __construct()
    {
        parent::__construct();
        $action = strtolower(__ACTION__); 
        $_action = explode('/',$action);
        $_action = array_pop($_action);
        if(!in_array($_action,self::$denyLayout)){
            C('LAYOUT_ON',false);
           
        }
        $this->uid = Crypt::decrypt(session('user_id'),C('CRYPT_KEY'));
   
        if(!empty($this->uid)){
            $this->userName = Crypt::decrypt(session('user_name'),C('CRYPT_KEY'));session('user_name');
            //加载菜单 
            $_node = explode('/',strtolower(__CONTROLLER__));
            $node = array_pop($_node);
            $this->assign('menu',MenuModel::init());
            $this->assign('children_menu',MenuModel::getChildren($node));
            $this->assign('now_controller',$node);
            $this->assign('now_action',strtolower(__ACTION__));
            $this->assign('global',GlobalModel::getGlobalSetting());
            $this->assign('is_ajax',IS_AJAX?1:0);
        }else{
            $this->redirect('/Sign');
        }
    }
    
    protected function outHtml($html = '')
    {
        $result = array(
            'html'=>$html  
        );
        $this->ajaxReturn($result);
    }
    
    
}