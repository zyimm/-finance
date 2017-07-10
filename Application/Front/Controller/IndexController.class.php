<?php
/**
 * 首页放标展示的控制器
 * @Author ZYIMM <799783009@qq.com>
 * @version 1.0
 * Create By 2017年3月13日 下午9:03:02  版权所有@copyright 2013~2017 www.zyimm.com
 */
namespace Front\Controller;

use Front\Model\BorrowInfoModel;
use Front\Model\MessageModel;
use Common\Service\Mailer;
use Front\Model\InvestModel;

class IndexController extends BaseController
{

    public static $brrowInfo = NULL;

    public static $brrowConfig = NULL;

    public function index()
    {
        self::$brrowInfo = new BorrowInfoModel();
        $per = C('DB_PREFIX');
        self::$brrowConfig = \Front\Model\ConfigModel::read('borrow_config');
        // 正在进行的贷款
        $searchMap = array();
        $searchMap['b.borrow_status'] = array(
            "in",
            '2,4,6,7'
        );
     
        $parm = array();
        $parm['map'] = $searchMap;
        $parm['pagesize'] = 4;
        $parm['orderby'] = "b.borrow_status ASC,b.id DESC";
        $listBorrow = self::$brrowInfo->getBorrowList($parm);

        $this->assign("listBorrow", $listBorrow);
        // 成功放款笔数
        if (S('mborrowOutNumData')) {
            $this->assign("mborrowOutNum", S('mborrowOutNumData'));
        } else {
            S('mborrowOutNumData', self::$brrowInfo->where("borrow_status in(6,7,8,9)")->count("id"), 60);
            $this->assign("mborrowOutNum", S('mborrowOutNumData'));
        }
        // 成功放款总额
        if (S('mborrowLimitData')) {
            $this->assign("mborrowLimit", S('mborrowLimitData'));
        } else {
            S('mborrowLimitData', self::$brrowInfo->where("borrow_status in(6,7,8,9)")->sum(" borrow_money"));
            $this->assign("mborrowLimit", S('mborrowLimitData'));
        }
        // 待回收本金总额
        if (S('mborrowOutData')) {
            $this->assign("mborrowOut", S('mborrowOutData'));
        } else {
            S('mborrowOutData', self::$brrowInfo->where("borrow_status in(6)")->sum("borrow_money"), 60);
            $this->assign("mborrowOut", S('mborrowOutData'));
        }
        
        // yesterday
        $yesterdays = strtotime('-1 day', strtotime(date("Y-m-d", time())));
        $yesterday = $yesterdays + 3600 * 24;
        $map = null;
        $map = array(
            'borrow_status' => array(
                'in',
                '6,7,8,9'
            ),
            'add_time' => array(
                'between',
                "{$yesterdays},{$yesterday}"
            )
        );
        
        $this->assign("mborrowOutYesterday", self::$brrowInfo->where($map)->sum("borrow_money"));
        // 投资排行
        // 日
        $timeType = strtotime('-24 hours') . ',' . time();
        $lately = self::$brrowInfo->investorList($timeType);
        $this->assign("tenddataToday", $lately);
        // 周
        $timeType = strtotime('-1 week') . ',' . time();
        $lately = self::$brrowInfo->investorList($timeType);
        $this->assign("tenddataWeek", $lately);
        // 月
        $timeType = strtotime('-1 month') . ',' . time();
        $lately = self::$brrowInfo->investorList($timeType);
        $this->assign("tenddataMon", $lately);
        
        // 总
        $timeType = strtotime('1980-01-01') . ',' . time();
        $lately = self::$brrowInfo->investorList($timeType);
        $this->assign("tenddataYear", $lately);
       
        $this->assign("Bconfig", self::$brrowConfig);
        $this->assign("memberNum", M('members')->count('id'));
        dump(self::$brrowInfo->autoFaild());
        //$this->display();
    }
    
    public function sms()
    {
        dump(MessageModel::sendSms(15358461826,'你好'));
    }
    
    public function page()
    {
        $page = new \Think\Page(100,5);
       echo  $page->ajaxShow();
    }
    
    
    
    
    public function mail()
    {
        $mailer = new Mailer();
        $result = $mailer->setServer('smtp.189.cn',25)
        ->setAuth('15358461826@189.cn', 'zyimm12')
        ->setFrom('zyimm', '15358461826@189.cn')
        ->setSubject('Hello')
        ->setBody('php mail')
        ->addTo('zhouyangyang','799783009@qq.com')
        ->send();
        dump($result);exit;
    }
    
    public function test()
    {
        $invest_model  = new \Front\Model\InvestModel();
        $result = (new BorrowInfoModel())->borrowApproved('2882');
        
        dump($result);
        
    }
    
    
    public function test4()
    {
        $result = (new BorrowInfoModel())->borrowRepayment(2882,1);
        
        dump($result);
    }
    public function test3()
    {
        
   
        $mmoney['money_freeze'] = -10;
        $mmoney['money_collect'] = 1500.06;
        $mmoney['account_money'] = 180695.00;
        $mmoney['back_money'] = 18592.50;
        dump(M('member_money')->where("uid=1304")->save($mmoney));
    }
    
    public function test2()
    {
        
 
        $invest_model =new InvestModel();
        $_POST['pay_pass'] =  '';
        $result = $invest_model->investMoney(4,'Rr茹茹',2882,1200);
        if($result === true){
            echo ('投标成功');
        }else{
            $result = is_bool($result)?'error':$result;
            dump($result);
        }
    }
}