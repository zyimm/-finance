<?php
namespace Member\Model;

use Think\Model;
use Think\Page;
use Front\Model\ToolModel;
use Front\Model\GlobalModel;
use Front\Model\MessageModel;

class WithDrawModel extends Model
{

    protected $tableName = 'member_withdraw';

    public function getWithDrawLog($where, $page_now = 1, $page_size = 10)
    {
        $rows = $this->where($where)
            ->page($page_now, $page_size)
            ->order('id DESC')
            ->select();
        
        $count = $this->where($where)->count();
        
        $page = new Page($count, $page_size, I('post.'));
        $page_show = $page->show();
        $map = [];
        $map['status'] = 1;
        $row['success_money'] = M('member_payonline')->where($map)->sum('money');
        $map['status'] = array(
            'neq',
            '1'
        );
        $row['fail_money'] = M('member_payonline')->where($map)->sum('money');
        return array(
            'rows' => $rows,
            'page' => $page_show,
            'search' => ''
        );
    }

    public function checkDraw($data = [], $uid = 0)
    {
        $withdraw_money = floatval($data['amount']);
        $pwd = md5($data['pwd']);
        $where = [
            'm.id' => $uid,
            'm.pay_pass' => $pwd
        
        ];
        $field = 'mm.account_money,mm.back_money,m.user_leve,m.time_limit';
        $vo = [];
        $vo = $this->table($this->tablePrefix . 'members m')
            ->field($field)
            ->join($this->tablePrefix . "member_money mm on mm.uid = m.id")
            ->where($where)
            ->find();
        $where = [
            'borrow_uid' => $uid,
            'borrow_type' => 4,
            'borrow_status' => [
                'in',
                [
                    0,
                    2,
                    4,
                    6,
                    8,
                    9,
                    10
                ]
            ]
        
        ];
        $field = "sum(borrow_money+borrow_interest+borrow_fee) as borrow, sum(repayment_money+repayment_interest) as also";
        $borrow_info = $this->table($this->tablePrefix . 'borrow_info')
            ->field($field)
            ->where($where)
            ->find();
        if (empty($vo) || empty($borrow_info)) {
            
            return '基础信息不存在!';
        }
        
        $borrow_money = $vo['account_money'] + $vo['back_money'] - ($borrow_info['borrow'] + $borrow_info['also']);
        if (($vo['account_money'] + $vo['back_money']) < $withdraw_money) {
            return '提现额大于帐户余额';
        }
        
        if ($borrow_money < $withdraw_money) {
            return "存在借款" . ($borrow_info['borrow'] + $borrow_info['also']) . "元未还，账户余额提现不足";
        }
        
        $start = strtotime(date("Y-m-d", time()) . " 00:00:00");
        $end = strtotime(date("Y-m-d", time()) . " 23:59:59");
        $map = [];
        $map['uid'] = $uid;
        $map['withdraw_status'] = array(
            "neq",
            3
        );
        $map['add_time'] = array(
            "between",
            [
                $start,
                $end
            ]
        );
        $today_money = $this->where($map)->sum('withdraw_money');
        $today_time = $this->where($map)->count('id');
        
        $tqfee = explode("|", $this->glo['fee_tqtx']);
        $fee[0] = explode("-", $tqfee[0]);
        $fee[1] = explode("-", $tqfee[1]);
        $fee[2] = explode("-", $tqfee[2]);
        
        $one_limit = $fee[2][0] * 10000;
        
        if ($withdraw_money < 100 || $withdraw_money > $one_limit){
            return "单笔提现金额限制为100-{$one_limit}元";
        }
            
        // 今天的限额
        $today_limit = $fee[2][1] / $fee[2][0];
        
        if ($today_time > $today_limit) {
            return "一天最多只能提现{$today_limit}次";
        }
        
        if (1 == 1 || $vo['user_leve'] > 0 && $vo['time_limit'] > time()) {
            $itime = strtotime(date("Y-m", time()) . "-01 00:00:00") . "," . strtotime(date("Y-m-", time()) . date("t", time()) . " 23:59:59");
            $map = [];
            $map['uid'] = $uid;
            $map['withdraw_status'] = array(
                "neq",
                3
            );
            $wmapx['add_time'] = array(
                "between",
                "{$itime}"
            );
            $times_month = $this->where($map)->count("id");
            $global = GlobalModel::getGlobalSetting();
            $tqfee1 = explode("|", $global['fee_tqtx']);
            $fee1[0] = explode("-", $tqfee1[0]);
            $fee1[1] = explode("-", $tqfee1[1]);
            $fee1[0] = empty($fee1[0])?0:$fee1[0];
            $fee1[1] = empty($fee1[1])?0:$fee1[1];
            if (($withdraw_money - $vo['back_money']) >= 0) {
                $maxfee1 = ($withdraw_money - $vo['back_money']) * $fee1[0][0] / 1000;
                if ($maxfee1 >= $fee1[0][1]) {
                    $maxfee1 = $fee1[0][1];
                }
                
                $maxfee2 = $vo['back_money'] * $fee1[1][0] / 1000;
                if ($maxfee2 >= $fee1[1][1]) {
                    $maxfee2 = $fee1[1][1];
                }
                $fee = 0;
                $fee = $maxfee1 + $maxfee2;
                $money = $withdraw_money - $vo['back_money'];
            } else {
                $fee = $vo['back_money'] * $fee1[1][0] / 1000;
            }
            $message = [];
            if ($withdraw_money <= $vo['back_money']) {
                if ($fee > 0) {
                    $message[] = "您好，您申请提现{$withdraw_money}元，小于目前的回款总额{$vo['back_money']}元，依然需要支付{$fee}手续费，确认要提现吗？";
                } else {
                    $message[] = "您好，您申请提现{$withdraw_money}元，小于目前的回款总额{$vo['back_money']}元，因此无需手续费，确认要提现吗？";
                }
            } else {
                $message[] = "您好，您申请提现{$withdraw_money}元，其中有{$vo['back_money']}元在回款之内，无需提现手续费，另有{$money}元需收取提现手续费{$fee}元，确认要提现吗？";
            }
            MessageModel::smsTip("payonline", $vo['user_phone'],[
                "#USERANEM#",
                "#MONEY#"
            ],[
                $vo['user_name'],
                $vo['money']
            ]);
           
            if (($today_money + $withdraw_money) > $fee[2][1] * 10000) {
                $message[]= "单日提现上限为{$fee[2][1]}万元。您今日已经申请提现金额：{$today_money}元,当前申请金额为:{$withdraw_money}元,已超出单日上限，请您修改申请金额或改日再申请提现";
               
            }
        } else { // 普通会员暂未使用
            if (($today_money + $withdraw_money) > 300000) {
                $message[] = "您是普通会员，单日提现上限为30万元。您今日已经申请提现金额：$today_money元,当前申请金额为:$withdraw_money元,已超出单日上限，请您修改申请金额或改日再申请提现";
                
            }
            $tqfee = $global['fee_pttx'];
            $fee = ToolModel::getFloatValue($tqfee * $withdraw_money / 100, 2);
            
            if (($vo['account_money'] - $withdraw_money - $fee) < 0) {
                $message[] = "您好，您申请提现{$withdraw_money}元，提现手续费{$fee}元将从您的提现金额中扣除，确认要提现吗？";
            } else {
                $message[] = "您好，您申请提现{$withdraw_money}元，提现手续费{$fee}元将从您的帐户余额中扣除，确认要提现吗？";
            }
            $message['withdraw_money'] = $withdraw_money;
            $message['fee'] = $fee;
            return $message;
        }
    }

    public function doWithDraw($data, $uid)
    {
        $check_info =$this->checkDraw($data, $uid);
        $withdraw_money = $check_info['withdraw_money'];
        $fee = $check_info['fee'];
        
        $this->startTrans();
        $money_data = [];
        $money_data['withdraw_money'] = $withdraw_money;
        $money_data['withdraw_fee'] = $fee;
        $money_data['second_fee'] = $fee;
        $money_data['withdraw_status'] = 0;
        $money_data['uid'] = $this->uid;
        $money_data['add_time'] = time();
        $money_data['add_ip'] = get_client_ip();
        if ($this->add($money_data)) {
            $status = false;
            $status = MoneyModel::memberMoneyLog($uid, 4, -$withdraw_money, "提现,默认自动扣减手续费" . $fee . "元", '0', '@网站管理员@', 0);
            if($status === true){
                MessageModel::MTip('3', $uid);
                $this->commit();
                return '恭喜，提现申请提交成功!';
            }else{ 
                $this->rollback();
                return '账户日志记录失败！';
            }
         
        }else{
            $this->rollback();
            return '对不起，提现出错，请重试';
        }
        
    }
}