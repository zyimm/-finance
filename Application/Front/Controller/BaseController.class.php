<?php
/**
 * 基础控制器
 * @Author ZYIMM <799783009@qq.com>
 * @version 1.0
 * Create By 2017年3月13日 下午9:37:19  版权所有@copyright 2013~2017 www.zyimm.com
 */
namespace Front\Controller;

use Think\Controller;
use Think\Crypt;
use Backstage\Model\GlobalModel;

class BaseController extends Controller
{
    public $uid = NULL;
    
    public $userName = NULL;
    
    public  $glo = array();
    
    public function __construct()
    {
        parent::__construct();
        $this->uid = Crypt::decrypt(session('user_id'),C('CRYPT_KEY'));
        $this->glo = \Front\Model\GlobalModel::getGlobalSetting();
        if(!empty($this->uid)){
            $this->userName = Crypt::decrypt(session('user_name'),C('CRYPT_KEY'));session('user_name');
        }
    }
}