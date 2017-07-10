<?php
namespace Front\Controller;

use Front\Model\ConfigModel;
use Front\Model\SafeModel;
use Member\Model\MemberModel;
use Front\Model\ToolModel;
use Front\Model\BorrowInfoModel;

class BorrowController extends BaseController
{

    public function index()
    {
        $this->display();
    }

    public function apply()
    {
        if (! $this->uid) {
            $this->error(L('must_sign'), U('/sign'));
        }
        
        $vminfo = M('members')->field("user_leve,time_limit,is_borrow,is_vip")->find($this->uid);
        
        if ($vminfo['is_vip'] == 0) {
            $_vo = M('borrow_info')->where("borrow_uid={$this->uid} AND borrow_status in(0,2,4)")->count('id');
            if ($_vo > 0) {
                $this->error(L('has_borrow'));
            }
            
            if (! ($vminfo['user_leve'] > 0 && $vminfo['time_limit'] > time())) {
                $this->error(L('must_is_vip'));
            }
            
            if ($vminfo['is_borrow'] == 0) {
                $this->error(L('forbid_borrow'));
                $this->assign("waitSecond", 3);
            }
            
            $vo = (new MemberModel())->getMemberDetail($this->uid);
            if ($vo['province'] == 0 && $vo['province_now '] == 0 && $vo['province_now '] == 0 && $vo['city'] == 0 && $vo['city_now'] == 0) {
                $this->error(L('before_update_member_info'));
            }
        }
        $type = explode('/',$_SERVER['PATH_INFO']);
        $type = array_pop($type);
        $gtype = SafeModel::text($type);
        $vkey = md5(time() . $gtype);
        switch ($gtype) {
            case "normal": // 普通标
                $borrow_type = 1;
                break;
            case "vouch": // 新担保标
                $borrow_type = 2;
                break;
            case "second": // 秒还标
                $this->assign("second", 'yes');
                $borrow_type = 3;
                break;
            case "net": // 净值标
                $borrow_type = 4;
                break;
            case "mortgage": // 抵押标
                $borrow_type = 5;
                break;
        }
        cookie($vkey, $borrow_type, 3600);
        $borrow_duration_day = explode("|", $this->glo['borrow_duration_day']);
        $day = range($borrow_duration_day[0], $borrow_duration_day[1]);
        $day_time = array();
        foreach ($day as $v) {
            $day_time[$v] = $v . "天";
        }
        $borrow_duration = explode("|", $this->glo['borrow_duration']);
        $month = range($borrow_duration[0], $borrow_duration[1]);
        $month_time = array();
        foreach ($month as $v) {
            $month_time[$v] = $v . "个月";
        }
        $rate_lixt = explode("|", $this->glo['rate_lixi']);
        $borrow_config = ConfigModel::read('borrow_config');
        $this->assign("borrow_use", $borrow_config['BORROW_USE']);
        $this->assign("borrow_min", $borrow_config['BORROW_MIN']);
        $this->assign("borrow_max", $borrow_config['BORROW_MAX']);
        $this->assign("borrow_time", $borrow_config['BORROW_TIME']);
        $this->assign("BORROW_TYPE", $borrow_config['BORROW_TYPE']);
        $this->assign("borrow_type", $borrow_type);
        $this->assign("borrow_day_time", $day_time);
        $this->assign("borrow_month_time", $month_time);
        array_shift($borrow_config['REPAYMENT_TYPE']);
        $this->assign("repayment_type",$borrow_config['REPAYMENT_TYPE']);
        $this->assign("vkey", $vkey);
        $this->assign("rate_lixt", $rate_lixt);
        $this->display();
    }

    public function save()
    {
        if (!$this->uid) {
            $this->error(L('must_sign'), U('/member/sign'));
        }
        $pre = C('DB_PREFIX');
        // 相关的判断参数
        $rate_lixt = explode("|", $this->glo['rate_lixi']);
        
        $borrow_duration = explode("|", $this->glo['borrow_duration']);
        
        $borrow_duration_day = explode("|", $this->glo['borrow_duration_day']);
        
        $fee_borrow_manage = explode("|", $this->glo['fee_borrow_manage']);
        
        $vminfo = M('members m')->join("{$pre}member_info mf ON m.id=mf.uid")
            ->field("m.user_leve,m.time_limit,mf.province_now,mf.city_now,mf.area_now")
            ->where("m.id={$this->uid}")
            ->find();
            
        // 相关的判断参数
        $borrow = [];
        
        $borrow['borrow_type'] = intval(cookie(SafeModel::text(I('post.vkey'))));
        
        if ($borrow['borrow_type'] == 0) {
            $this->error("校验数据有误，请重新发布");
        }
        
        if (floatval(I('post.borrow_interest_rate')) > $rate_lixt[1] || floatval(I('post.borrow_interest_rate')) < $rate_lixt[0]) {
            $this->error("提交的借款利率超出允许范围，请重试", 0);
        }
        
        $borrow['borrow_money'] = intval(I('post.borrow_money'));
        
        $_minfo = (new MemberModel())->getMinfo($this->uid, "m.pay_pass,mm.account_money,mm.back_money,mm.credit_cuse,mm.money_collect");
        $where = [
            'borrow_uid' =>$this->uid,
            'borrow_status'=>6
        ];
        $borrowNum = M('borrow_info')->field("borrow_type,count(id) as num,sum(borrow_money) as money,sum(repayment_money) as repayment_money")
            ->where($where)
            ->group("borrow_type")
            ->select();
        $borrowDe = array();
        foreach ($borrowNum as $k => $v) {
            $borrowDe[$v['borrow_type']] = $v['money'] - $v['repayment_money'];
        }
        
        switch ($borrow['borrow_type']) {
            case 1: // 普通标
                if ($_minfo['credit_cuse'] < $borrow['borrow_money']){
                    $this->error("您的可用信用额度为{$_minfo['credit_cuse']}元，小于您准备借款的金额，不能发标");
                }
                    
                break;
            case 2: // 新担保标
            case 3: // 秒还标
                break;
            case 4: // 净值标
                $_netMoney = ToolModel::getFloatValue(0.9 * $_minfo['money_collect'] - $borrowDe[4], 2);
                if ($_netMoney < $borrow['borrow_money']){
                    $this->error("您的净值额度{$_netMoney}元，小于您准备借款的金额，不能发标");
                }
                   
                break;
            case 5: // 抵押标
                $borrow_type = 5;
                break;
        }
        
        $borrow['borrow_uid'] = $this->uid;
        $borrow['borrow_name'] = SafeModel::text(I('post.borrow_name'));
        // 秒标固定为一月
        $borrow['borrow_duration'] = ($borrow['borrow_type'] == 3) ? 1 : intval(I('post.borrow_duration'));
        $borrow['borrow_interest_rate'] = floatval(I('post.borrow_interest_rate'));
        if (strtolower(I('post.is_day')) == 'yes') {
            $borrow['repayment_type'] = 1;
        } elseif ($borrow['borrow_type'] == 3) {
            $borrow['repayment_type'] = 2; // 秒标按月还
        } else {
            $borrow['repayment_type'] = intval(I('post.repayment_type',1));
        }
        
        if ($borrow['repayment_type'] == '1' || $borrow['repayment_type'] == '5') {
            $borrow['total'] = 1;
        } else {
            $borrow['total'] = $borrow['borrow_duration']; // 分几期还款
        }
        $borrow['borrow_status'] = 0;
        $borrow['borrow_use'] = intval(I('post.borrow_use'));
        $borrow['add_time'] = time();
        $borrow['collect_day'] = intval(I('post.borrow_time'));
        $borrow['add_ip'] = get_client_ip();
        $borrow['borrow_info'] = SafeModel::text(I('post.borrow_info'));
        $borrow['reward_type'] = intval(I('post.reward_type'));
        $borrow['reward_num'] = floatval(I('post.borrow_reward'));
        $borrow['borrow_min'] = intval(I('post.borrow_min'));
        $borrow['borrow_max'] = intval(I('post.borrow_max'));
        if (I('post.is_pass',false) && intval(I('post.is_pass')) == 1) {
            $borrow['password'] = md5(I('post.password'));
        }
        
        $borrow['money_collect'] = floatval(I('post.moneycollect',0)); // 代收金额限制设置
        // 借款费和利息
        $borrow['borrow_interest'] = BorrowInfoModel::getBorrowInterest($borrow['repayment_type'], $borrow['borrow_money'], $borrow['borrow_duration'], $borrow['borrow_interest_rate']);
        if ($borrow['repayment_type'] == 1) { // 按天还
            $fee_rate = (is_numeric($fee_borrow_manage[0])) ? ($fee_borrow_manage[0] / 100) : 0.001;
    
            $borrow['borrow_fee'] = ToolModel::getFloatValue($fee_rate * $borrow['borrow_money'] * $borrow['borrow_duration'], 2);
        } else {
            $fee_rate_1 = (is_numeric($fee_borrow_manage[1])) ? ($fee_borrow_manage[1] / 100) : 0.02;
            $fee_rate_2 = (is_numeric($fee_borrow_manage[2])) ? ($fee_borrow_manage[2] / 100) : 0.002;
            if ($borrow['borrow_duration'] > $fee_borrow_manage[3] && is_numeric($fee_borrow_manage[3])) {
                $borrow['borrow_fee'] = ToolModel::getFloatValue($fee_rate_1 * $borrow['borrow_money'], 2);
                $borrow['borrow_fee'] += ToolModel::getFloatValue($fee_rate_2 * $borrow['borrow_money'] * ($borrow['borrow_duration'] - $fee_borrow_manage[3]), 2);
            } else {
                $borrow['borrow_fee'] = ToolModel::getFloatValue($fee_rate_1 * $borrow['borrow_money'], 2);
            }
        }
        
        if ($borrow['borrow_type'] == 3) { // 秒还标
            if ($borrow['reward_type'] > 0) {
                $_reward_money = ToolModel::getFloatValue($borrow['borrow_money'] * $borrow['reward_num'] / 100, 2);
            }
            $_reward_money = floatval($_reward_money);
            if (($_minfo['account_money'] + $_minfo['back_money']) < ($borrow['borrow_fee'] + $_reward_money)){
                $this->error("发布此标您最少需保证您的帐户余额大于等于" . ($borrow['borrow_fee'] + $_reward_money) . "元，以确保可以支付借款管理费和投标奖励费用");
            }
               
        }
        
        $newid = M("borrow_info")->add($borrow);
        
        if ($newid) {
            $this->success("借款发布成功，网站会尽快初审");
        } else {
            $this->error(L('before_update_member_info'));
        }
    }
}