<?php
namespace Member\Model;

use Think\Model;
use Front\Model\SafeModel;

class MoneyModel extends Model
{

    protected $tableName = 'member_money';

    public static $type = [];

    public function _initialize()
    { 
        if(empty(self::$type)){
            self::$type = C('MONEY_LOG');
        }
    }
    public function apply($data = [],$uid = 0)
    {
        $xtime = strtotime("-1 month");
        $model = M('member_apply');
        $vo = $model->field('apply_status')
            ->where("uid={$uid}")
            ->order("id DESC")
            ->find();
        $xcount = $model->field('add_time')
            ->where("uid={$uid} AND add_time>{$xtime}")
            ->order("id DESC")
            ->find();
        if((int)$data['money'] < 1){
            return  "金额格式不对";
        }
        if (is_array($vo) && $vo['apply_status'] == 0) {
            return  "是您的申请正在审核，请等待此次审核结束再提交新的申请";
           
        } elseif (is_array($xcount)) {
            $timex = date("Y-m-d", $xcount['add_time']);
            return "一个月内只能进行一次额度申请，您已在{$timex}申请过了，如急需额度，请直接联系客服";
        
        } else {
            $apply = [];
            $apply['uid'] = $uid;
            $apply['apply_type'] = 1;
            $apply['apply_money'] = floatval($data['money']);
            $apply['apply_info'] = SafeModel::text($data['info']);
            $apply['add_time'] = time();
            $apply['apply_status'] = 0;
            $apply['add_ip'] = get_client_ip();
            if(!$model->add($apply)){
                return '申请提交失败，请重试';
            }else{
                return true;
            }
        }
       
    }
    
    /**
     * 记录会员资金变化
     * 
     * @param number $uid       用户的ID
     * @param number $type      变化类型
     * @param number $affect_money    影响的金钱
     * @param string $info      备注信息
     * @param string $target_uid            
     * @param string $target_uname            
     * @param number $fee            
     * @return boolean
     * @author 周阳阳 2017年3月27日 下午3:40:26
     */
    public static function memberMoneyLog($uid = 0, $type = 0, $affect_money = 0, $info = "", $target_uid = "", $target_uname = "", $fee = 0)
    {
        $affect_money = floatval($affect_money);
     
        $model = M('member_money');
        
        $MM = $model->field("money_freeze,money_collect,account_money,back_money")->find($uid);
        if (! is_array($MM) || empty($MM)) {
            $model->add(array(
                'uid' => $uid,
                'money_freeze'=>0,
                'money_collect'=>0,
                'account_money'=>0,
                'back_money'=>0
            ));
            $MM = $model->field("money_freeze,money_collect,account_money,back_money")->find($uid);
        }
        $money_log = M('member_moneylog');
        if (in_array($type, array(
            "71",
            "72",
            "73"
        ))) {
            $type_save = 7;
        } else {
            $type_save = $type;
        }
        
        if ($target_uname == "" && $target_uid > 0) {
            $tname = M('members')->getFieldById($target_uid, 'user_name');
        } else {
            $tname = $target_uname;
        }
        if ($target_uid == "" && $target_uname == "") {
            $target_uid = 0;
            $tname = '@网站管理员@';
        }
        $model->startTrans();
        $data = [];
        $data['uid'] = $uid;
        $data['type'] = $type_save;
        $data['info'] = $info;
        $data['target_uid'] = $target_uid;
        $data['target_uname'] = $tname;
        $data['add_time'] = time();
        $data['add_ip'] = get_client_ip();
        switch ($type) {
            case 5: // 撤消提现
                $data['affect_money'] = $affect_money;
                if (($MM['back_money'] + $affect_money + $fee) < 0) { // 提现手续费先从回款余额资金池里扣，不够再去充值资金池里减少
                    $data['back_money'] = 0;
                    $data['account_money'] = $MM['account_money'] + $MM['back_money'] + $affect_money + $fee;
                } else {
                    $data['back_money'] = $MM['back_money'];
                    $data['account_money'] = $MM['account_money'] + $affect_money + $fee;
                }
                
                $data['collect_money'] = $MM['money_collect'];
                $data['freeze_money'] = $MM['money_freeze'] - $affect_money;
                break;
            case 4: // 提现冻结
            case 6: // 投标冻结
            case 37: // 投流转标冻结
                $data['affect_money'] = $affect_money;
                if (($MM['back_money'] + $affect_money + $fee) < 0) { // 提现手续费先从回款余额资金池里扣，不够再去充值资金池里减少
                    $data['back_money'] = 0;
                    $data['account_money'] = $MM['account_money'] + $MM['back_money'] + $affect_money + $fee;
                } else {
                    $data['back_money'] = $MM['back_money'] + $affect_money + $fee;
                    $data['account_money'] = $MM['account_money'];
                }
                
                $data['collect_money'] = $MM['money_collect'];
                $data['freeze_money'] = $MM['money_freeze'] - $affect_money;
                break;
            case 12: // 提现失败
                $data['affect_money'] = $affect_money;
                
                if (($MM['account_money'] + $MM['back_money']) > abs($fee)) {
                    if (($MM['back_money'] + $affect_money + $fee) < 0) { // 提现手续费先从回款余额资金池里扣，不够再去充值资金池里减少
                        $data['back_money'] = 0;
                        $data['account_money'] = $MM['account_money'] + $MM['back_money'] + $affect_money + $fee;
                    } else {
                        $data['back_money'] = $MM['back_money'] + $affect_money + $fee;
                        $data['account_money'] = $MM['account_money'];
                    }
                    $data['collect_money'] = $MM['money_collect'];
                    $data['freeze_money'] = $MM['money_freeze'] - $affect_money;
                } else {
                    if (($MM['back_money'] + $affect_money + $fee) < 0) { // 提现手续费先从回款余额资金池里扣，不够再去充值资金池里减少
                        $data['back_money'] = 0;
                        $data['account_money'] = $MM['account_money'] + $MM['back_money'] + $affect_money;
                    } else {
                        $data['back_money'] = $MM['back_money'] + $affect_money;
                        $data['account_money'] = $MM['account_money'];
                    }
                    $data['collect_money'] = $MM['money_collect'];
                    $data['freeze_money'] = $MM['money_freeze'] - $affect_money + $fee;
                }
                break;
            
            case 29: // 提现成功
                $data['affect_money'] = $affect_money; // 影响
                $data['account_money'] = $MM['account_money']; // 充值
                $data['back_money'] = $MM['back_money']; // 回款
                $data['collect_money'] = $MM['money_collect']; // 待收
                $data['freeze_money'] = $MM['money_freeze'] + $affect_money + $fee; // 冻结金额
                break;
            case 36: // 提现通过，处理中
                $data['affect_money'] = $affect_money;
                if (($MM['account_money'] + $MM['back_money']) > abs($fee)) {
                    if (($MM['back_money'] + $fee) < 0) { // 提现手续费先从回款余额资金池里扣，不够再去充值资金池里减少
                        $data['account_money'] = $MM['account_money'] + $MM['back_money'] + $fee;
                        $data['back_money'] = 0;
                    } else {
                        $data['account_money'] = $MM['account_money'];
                        $data['back_money'] = $MM['back_money'] + $fee;
                    }
                    $data['collect_money'] = $MM['money_collect'];
                    $data['freeze_money'] = $MM['money_freeze'];
                } else {
                    $data['account_money'] = $MM['account_money'];
                    $data['back_money'] = $MM['back_money'];
                    $data['collect_money'] = $MM['money_collect'];
                    $data['freeze_money'] = $MM['money_freeze'] + $fee;
                }
                break;
            case 8: // 流标解冻
            case 19: // 借款保证金
            case 24: // 还款完成解冻
            case 34: // 预投标奖励撤销
                $data['affect_money'] = $affect_money;
                if (($MM['account_money'] + $affect_money) < 0) {
                    $data['account_money'] = 0;
                    $data['back_money'] = $MM['account_money'] + $MM['back_money'] + $affect_money;
                } else {
                    $data['account_money'] = $MM['account_money'] + $affect_money;
                    $data['back_money'] = $MM['back_money'];
                }
                $data['collect_money'] = $MM['money_collect'];
                $data['freeze_money'] = $MM['money_freeze'] - $affect_money;
                break;
            case 3: // 会员充值
            case 17: // 借款金额入帐
            case 18: // 借款管理费
            case 20: // 投标奖励
            case 21: // 支付投标奖励
            case 40: // 流转标续投奖励
            case 41: // 流转标投标奖励
            case 42: // 支付流转标投标奖励
                $data['affect_money'] = $affect_money;
                if (($MM['account_money'] + $affect_money) < 0) {
                    $data['account_money'] = 0;
                    $data['back_money'] = $MM['account_money'] + $MM['back_money'] + $affect_money;
                } else {
                    $data['account_money'] = $MM['account_money'] + $affect_money;
                    $data['back_money'] = $MM['back_money'];
                }
                $data['collect_money'] = $MM['money_collect'];
                $data['freeze_money'] = $MM['money_freeze'];
                break;
            case 9: // 会员还款
            case 10: // 网站代还
                $data['affect_money'] = $affect_money;
                $data['account_money'] = $MM['account_money'];
                $data['collect_money'] = $MM['money_collect'] - $affect_money;
                $data['freeze_money'] = $MM['money_freeze'];
                $data['back_money'] = $MM['back_money'] + $affect_money;
                break;
            case 15: // 投标成功冻结资金转为待收资金
            case 39: // 流转标投标成功冻结资金转为待收资金
                $data['affect_money'] = $affect_money;
                $data['account_money'] = $MM['account_money'];
                $data['collect_money'] = $MM['money_collect'] + $affect_money;
                $data['freeze_money'] = $MM['money_freeze'] - $affect_money;
                $data['back_money'] = $MM['back_money'];
                break;
            case 28: // 投标成功利息待收
            case 38: // 流转标投标成功利息待收
            case 73: // 单独操作待收金额
                $data['affect_money'] = $affect_money;
                $data['account_money'] = $MM['account_money'];
                $data['collect_money'] = $MM['money_collect'] + $affect_money;
                $data['freeze_money'] = $MM['money_freeze'];
                $data['back_money'] = $MM['back_money'];
                break;
            case 72: // 单独操作冻结金额
            case 33: // 续投奖励(预奖励)
            case 35: // 续投奖励(取消)
                $data['affect_money'] = $affect_money;
                $data['account_money'] = $MM['account_money'];
                $data['collect_money'] = $MM['money_collect'];
                $data['freeze_money'] = $MM['money_freeze'] + $affect_money;
                $data['back_money'] = $MM['back_money'];
                break;
            case 71: // 单独操作可用余额
            default:
                $data['affect_money'] = $affect_money;
                if (($MM['account_money'] + $affect_money) <= 0) {
                    $data['account_money'] = 0;
                    $data['back_money'] = $MM['account_money'] + $MM['back_money'] + $affect_money;
                } else {
                    $data['account_money'] = $MM['account_money'] + $affect_money;
                    $data['back_money'] = $MM['back_money'];
                }
                $data['collect_money'] = $MM['money_collect'];
                $data['freeze_money'] = $MM['money_freeze'];
                break;
        }
        
        // 帐户更新
        $mmoney['money_freeze'] = $data['freeze_money'];
        $mmoney['money_collect'] = $data['collect_money'];
        $mmoney['account_money'] = $data['account_money'];
        $mmoney['back_money'] = $data['back_money'];
        $where = [
            'uid'=>$uid
        ];
        if (M('member_moneylog')->add($data)) {
            if($model->where($where)->save($mmoney)){
                $model->commit();
                return true;
            }else{     
                trace('member_money-fail');
               //echo M('member_money')->_sql();
                $model->rollback();
                return false;
               
            }
        }else{
            trace('member_moneylog-fail');
            $model->rollback();
            return false;
        }
       
    }

    /**
     * 累计投资金额 \累计款金额\累计充值金额\累计提现金额\累计支付佣金
     *
     * @param mixed $uid            
     */
    public function getPersonalCount($uid = 0)
    {
        $uid = intval($uid);
        $count = array();
        // 累计投资金额
        $where = array(
            'investor_uid' => $uid,
            'status' => array(
                'in',
                array(
                    4,
                    5,
                    6,
                    7
                )
            )
        );
        $all_invest = $this->table($this->tablePrefix . 'borrow_investor')
            ->where($where)
            ->sum('investor_capital');
        // 流转标
        $all_invest_transfer = $this->table($this->tablePrefix . 'transfer_borrow_investor')
            ->where("investor_uid={$uid}")
            ->sum('investor_capital');
        $count['all_invest_sum'] = $all_invest + $all_invest_transfer;
        // 累计借入金额
        $where = array(
            'borrow_uid' => $uid,
            'borrow_status' => array(
                'in',
                array(
                    6,
                    7,
                    8,
                    9,
                    10
                )
            )
        );
        $all_borrow = $this->table($this->tablePrefix . 'borrow_info')
            ->where($where)
            ->sum('borrow_money');
        $count['all_borrow'] = $all_borrow;
        // 累计充值金额
        $pay_online = $this->table($this->tablePrefix . 'member_payonline')
            ->where("uid={$uid} AND status=1")
            ->sum('money'); // 累计充值金额
        $count['pay_online'] = $pay_online;
        // 累计提现金额
        $where = array(
            'uid' => $uid,
            'withdraw_status' => 2
        );
        $withdraw = $this->table($this->tablePrefix . 'member_withdraw')
            ->where($where)
            ->sum('withdraw_money');
        $count['withdraw'] = $withdraw;
        // 累计支付佣金 包括借款管理费、投资手续费
        $interest_fee = $this->table($this->tablePrefix.'investor_detail')
            ->where('investor_uid=' . $uid . ' and status in (1,2,3,4,5)')
            ->sum('interest_fee'); // 普通标投资管理费（统计还款后的管理费）
        $transfer_interest_fee = $this->table($this->tablePrefix . 'transfer_investor_detail')
            ->where('investor_uid=' . $uid . ' and status =1 ')
            ->sum('interest_fee'); // 流转标投资管理费（统计还款后的管理费）
        $borrow_fee = $this->table($this->tablePrefix . 'borrow_info')
            ->where("borrow_uid={$uid} AND borrow_status in(6,7,8,9,10)")
            ->sum('borrow_fee'); // 借款管理费 （统计复审通过后的管理费）
        $count['commission'] = $interest_fee + $transfer_interest_fee + $borrow_fee; // 累积支付佣金
        return $count;
    }
    

    /**
     * 获取用户投资收益汇总
     * 净赚利息、投标奖励、推广奖励、续投奖励、线下充值奖励、收入总和、代收利息
     * 
     * @param number $uid
     * @return []
     * @author 周阳阳 2017年5月5日 下午2:26:20
     */
    public function getPersonalBenefit($uid = 0)
    {
        $uid = intval($uid);
        $total = array();
        // 统计回款利息interest、回款总额capital、利息手续费fee
        $model = $this->table($this->tablePrefix . "investor_detail");
        $field = "sum(receive_capital) as capital, sum(receive_interest) as interest, sum('interest_fee') as fee";
        $investor = $model->field($field)
            ->where([
            'investor_uid' => $uid
        ])
            ->find();
        $investor['interest'] -= $investor['fee'];
        // 投资奖励 推广奖励 续投奖励 线下充值奖励
        $log = $this->getMoneyLog($uid);
        // 待收利息
        $where = [
            'investor_uid' => $uid,
            'status' => [
                'in',
                [
                    6,
                    7
                ]
            ]
        ];
        $field = 'sum(interest) as interest, sum(capital) as capital,sum(interest_fee) as fee';
        $interest_collection = $model->field($field)
            ->where($where)
            ->find();
        
        $benefit = [];
        
        $benefit['ireward'] = $log['20']['money'] + $log['41']['money']; // 投标奖励
        $benefit['spread_reward'] = $log['13']['money']; // 推广奖励
        $benefit['con_reward'] = $log['34']['money'] + $log['40']['money']; // 续投奖励
        $benefit['re_reward'] = $log['32']['money']; // 线下充值
        $benefit['interest'] = $investor['interest']; // 净赚利息
        $benefit['capital'] = $investor['capital']; // 回款总额
        $benefit['total'] = $benefit['ireward'] + $benefit['spread_reward'] + $benefit['con_reward'] + $benefit['re_reward'] + $benefit['interest'];
        $benefit['interest_collection'] = $interest_collection['interest'] - $interest_collection['fee']; // dai shou ben xi
        $benefit['capital_collection'] = $interest_collection['capital']; // 待收本金
        
        return $benefit;
    }
    
    
    /**
     * 获取的资金流水记录
     * 
     * @param number $uid
     * @return array|unknown
     * @author 周阳阳 2017年5月5日 下午2:26:11
     */
    public  function getMoneyLog($uid = 0)
    {
        $uid = intval($uid);
        $log = [];
        if($uid){
            $list = M("member_moneylog")->field('type,sum(affect_money) as money')->where("uid={$uid}")->group('type')->select();
        }else{
            $list = M("member_moneylog")->field('type,sum(affect_money) as money')->group('type')->select();
        }
        
        foreach($list as $v){
            $log[$v['type']]['money']= ($v['money']>0)?$v['money']:$v['money']*(-1);
            $log[$v['type']]['name']= self::$type[$v['type']];
        }
        return $log;
    }


    /**
     * 用户借款支出汇总
     * 支付投标奖励、支付利息、提现手续费、借款管理费、会员及认证费用、逾期及催收费用 、 支出总和、待付利息总额
     * 
     * @param number $uid
     * @return []
     * @author 周阳阳 2017年5月5日 下午2:30:59
     */
    public function getPersonalOut($uid = 0)
    {
        $log = $this->getMoneyLog($uid);
        $out = [];
        $out['borrow_manage'] = $log['18']['money']; // 借款管理费
        $out['pay_tender'] = $log['21']['money'] + $log['42']['money']; // 支付投标奖励
        $out['overdue'] = $log['30']['money'] + $log['31']['money']; // 逾期催收
        $out['authenticate'] = $log['14']['money'] + $log['22']['money'] + $log['25']['money'] + $log['26']['money']; // 认证费用
        $investor_model = M("investor_detail");
        $interest = $investor_model->field('sum(receive_capital) as capital, sum(receive_interest) as interest')
            ->where("borrow_uid={$uid} and status in (1,2,3,4,5)")
            ->find();
        
        $out['interest'] = $interest['interest']; // 支付利息
        $out['capital'] = $interest['capital']; // 已还本金
                                                
        // 待付利息\本金
        $interest_pay = $investor_model->field('sum(interest) as interest, sum(capital) as capital')
            ->where("borrow_uid={$uid} and status in (6,7)")
            ->find();
        $out['interest_pay'] = $interest_pay['interest']; // 待还利息
        $out['capital_pay'] = $interest_pay['capital']; // 待还金额
        
        $czfee = M('member_payonline')->where("uid={$uid} AND status=1")->sum('fee'); // 在线充值手续费
        $out['czfee'] = $czfee;
        $withdraw = M('member_withdraw')->field('sum(second_fee) as fee, sum(withdraw_money) as withdraw_money')
            ->where("uid={$uid} and withdraw_status=2")
            ->find();
        $out['withdraw_fee'] = $withdraw['fee']; // 提现手续费
        $out['withdraw_money'] = $withdraw['withdraw_money']; // 提现金额
        
        $out['total'] = $out['borrow_manage'] + $out['pay_tender'] + $out['overdue'] + $out['authenticate'] + $out['interest'] + $out['withdraw_fee'];
        return $out;
    }
    
    
    /**
     * 统计借款信息（借款总额、放款笔数、已还总额、待还总额）
     * 
     * @param number $uid
     * @return []
     * @author 周阳阳 2017年5月5日 下午2:45:42
     */
    public function loanTotalInfo($uid= 0){
        $info = [];
        $where = [];
        $model = M("borrow_info");
        if ((int) $uid > 0) {
            $where['uid'] = (int) $uid;
        }
        $where['borrow_status'] = [
            'in',
            [
                6,
                7,
                8,
                9
            ]
        ];
        $info['ordinary_total'] = $model->where($where)->sum("borrow_money"); // 普通标借款总额
        $where['borrow_status'] = [
            'in',
            [
                6,
                7,
                8,
                9
            ]
        ];
        $info['num_total'] = $model->where($where)->count("id"); // 普通标总笔数
        $where['borrow_status'] = [
            'in',
            [
                7,
                8,
                9
            ]
        ];
        $info['has_also'] = $model->where($where)->count("borrow_money"); // 已还款总额
        $where['borrow_status'] = 6;
        $info['arrears'] = $model->where($where)->count("borrow_money"); // 未还款总额
        
        return $info;
    }
}