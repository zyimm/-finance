<?php
namespace Backstage\Controller;

use Think\Controller;
use Backstage\Model\MenuModel;
use Backstage\Model\InvestModel;
use Backstage\Model\ChargeModel;

class IndexController extends BaseController
{
    
    public function index()
    {
        layout(false);
        //载入菜单
        $menu = MenuModel::init();
        $this->assign('category_level_second',$menu['category_level']);
        $this->assign('category_level_first',$menu['category_level_first']);
        $this->display();
    }
    
    public function welcome()
    {
        // 最近一周等待还款统计
       
        $this->assign('week_json',(new InvestModel())->getInvestorListForChart());
        $this->assign('week_charge_json',(new ChargeModel())->getChargeListForChart());
        $system = array(
            'os'=>PHP_OS,
            'app_ver'=>'2.1.2',
            'run_time'=>php_sapi_name(),
            'php_ver'=>phpversion(),
            'mysql_ver'=>current(M()->query('select version()'))['version()'],
            'max_post'=>ini_get('post_max_size'),
            'max_execution_time'=>ini_get('max_execution_time'),
            'server_name'=>$_SERVER['SERVER_NAME'],
            'disk_total'=>round((disk_total_space('./')/(1024*1000000)),3),
            'disk_has'=>round(((disk_total_space('./')/(1024*1000000))-disk_free_space('./')/(1024*1000000)),3)
        );

        $this->assign('system',$system);
        $this->display();
    }
}