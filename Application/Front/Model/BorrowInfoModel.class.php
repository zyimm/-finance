<?php
/**
 * 借款标处理
 * 
 * @Author ZYIMM <799783009@qq.com>
 * @version 1.0
 * Create By 2017年3月13日 下午9:26:12  版权所有@copyright 2013~2017 www.zyimm.com
 */
namespace Front\Model;

use Think\Model;
use Think\Page;
use Member\Model\MemberModel;
use Member\Model\MoneyModel;

class BorrowInfoModel extends Model
{
    public $investorDetailStatus = 0;

    /**
     * 获取标列表
     *
     * @param array $parm            
     * @return boolean|string[]|number[]
     */
    public function getBorrowList($parm = array())
    {
        if (empty($parm['map'])) {
            return false;
        }
        
        $map = $parm['map'];
        $orderby = $parm['orderby'];
        $page_now = I('get.p', 0, 'intval');
        $parm['pagesize'] = empty($parm['pagesize']) ? 10 : $parm['pagesize'];
        // 分页处理
        $count = $this->alias('b')
            ->where($map)
            ->count();
        $p = new Page($count, $parm['pagesize']);
        $page = $p->show();
        $pre = $this->tablePrefix;
        $suffix = C("URL_HTML_SUFFIX");
        $field = "b.id,b.borrow_name,b.borrow_type,b.reward_type,b.borrow_times,
                  b.borrow_status,b.borrow_money,b.borrow_use,b.repayment_type,
                  b.borrow_interest_rate,b.borrow_duration,b.collect_time,b.add_time,
                  b.province,b.has_borrow,b.has_vouch,b.city,b.area,b.reward_type,
                  b.reward_num,b.password,m.user_name,m.id as uid,m.credits,
                  m.customer_name,b.is_recommend,b.deadline,b.danbao,b.borrow_info,b.risk_control";
        $list = $this->alias('b')
            ->field($field)
            ->join("{$pre}members m ON m.id=b.borrow_uid", 'left')
            ->where($map)
            ->order($orderby)
            ->page($page_now, $parm['pagesize'])
            ->select();
        $area_list = ConfigModel::read('area');
        foreach ($list as $key => $v) {
            if (empty($v['province']) || empty($v['city'])) {
                $list[$key]['location'] = '未填写地址';
            } else {
                $list[$key]['location'] = $area_list[$v['province']] . $area_list[$v['city']];
            }
            $list[$key]['biao'] = $v['borrow_times'];
            $list[$key]['need'] = $v['borrow_money'] - $v['has_borrow'];
            $list[$key]['leftdays'] = ToolModel::getLeftTime($v['collect_time']);
            $list[$key]['progress'] = ToolModel::getFloatValue($v['has_borrow'] / $v['borrow_money'] * 100, 2);
            $list[$key]['vouch_progress'] = ToolModel::getFloatValue($v['has_vouch'] / $v['borrow_money'] * 100, 2);
            $list[$key]['burl'] = '1';
            // 新加
            $list[$key]['lefttime'] = $v['collect_time'] - time();
            if ($v['deadline'] == 0) {
                $endTime = strtotime(date("Y-m-d", time()));
                if ($v['repayment_type'] == 1) {
                    $list[$key]['repaytime'] = strtotime("+{$v['borrow_duration']} day", $endTime);
                } else {
                    $list[$key]['repaytime'] = strtotime("+{$v['borrow_duration']} month", $endTime);
                }
            } else {
                $list[$key]['repaytime'] = $v['deadline']; // 还款时间
            }
            $list[$key]['publishtime'] = $v['add_time'] + 60 * 60 * 24 * 3; // 预计发标时间=添加时间+1天
        }
        $row = array();
        $row['list'] = $list;
        $row['page'] = $page;
        return $row;
    }

    /**
     * 投资排行
     *
     * @param string $time_type     时间
     * @return mixed|object
     * @author 周阳阳 2017年3月31日 上午9:59:34
     */
    public function investorList($time_type = '')
    {
        if (S('investorList-' . $time_type) && ! empty($time_type)) {
            return S('investorList-' . $time_type);
        }
        $pre = C('DB_PREFIX');
        $map = array(
            'b.status' => array(
                'in',
                '4,5'
            ),
            'add_time' => array(
                'between',
                $time_type
            )
        );
        $field = "b.investor_uid,sum(investor_capital) as money_all,b.investor_uid,m.user_name";
        $lately = M('borrow_investor')->alias('b')
            ->field($field)
            ->join("{$pre}members m ON m.id=b.investor_uid", 'left')
            ->where($map)
            ->group('b.investor_uid')
            ->order("money_all desc")
            ->limit(10)
            ->select();
        foreach ($lately as $k => $v) {
            $lately[$k]['money_all'] = number_format($v['money_all'], 2);
        }
        S('investorList-' . $time_type, $lately, 30); // 30s更新一次缓存
        return $lately;
    }

    public function autoFaild()
    {
        // 流标返回
        $map = [];
        $map['collect_time'] = array(
            "lt",
            time()
        );
        $map['borrow_status'] = 2;
        $field = "id,borrow_uid,borrow_type,borrow_money,first_verify_time,borrow_interest_rate,borrow_duration,repayment_type,collect_day,collect_time";
        $list = M("borrow_info")->field($field)
            ->where($map)
            ->select();
        
        // 如果没有流标，则退出
        if (empty($list)) {
            return false;
        }
        
        foreach ($list as $key => $val) {
            $this->startTrans();
            $borrow_id = $val['id'];
            // 流标
            $result = true;
            $borrow_investor = M('borrow_investor');
            $field = "borrow_type,borrow_money,borrow_uid,borrow_duration,repayment_type";
            $borrow_info = $this->field($field)->find($borrow_id);
            $field = 'id,investor_uid,investor_capital';
            $where = [
                'borrow_id' => $borrow_id
            ];
            $investor_list = $borrow_investor->field($field)
                ->where($where)
                ->select();
            
            if ($borrow_info['borrow_type'] == 1) {
                // TODO 这边函数要处理
                $limit_credit = MemberModel::memberLimitLog($borrow_info['borrow_uid'], 12, ($borrow_info['borrow_money']), $info = "{$borrow_info['id']}号标流标"); // 返回额度
                if ($limit_credit !== true) {
                    $result = false;
                }
            } 
           
            // 处理借款概要
            $borrow_user_name = M('members')->getFieldById($borrow_info['borrow_uid'], 'user_name');
            // 处理借款概要
            if (! empty($investor_list)) {
                
                $update_borrow_investor = M('borrow_investor')->where("borrow_id={$borrow_id}")->setField("status", $type);
                foreach ($investor_list as $v) {
                    
                    $member_money_log = [];
                    MessageModel::MTip('11', $v['investor_uid']); // 流标通知
                    $accountMoney_investor = M("member_money")->field(true)->find($v['investor_uid']);
                    $member_money_log['uid'] = $v['investor_uid'];
                    $member_money_log['type'] = ($type == 3) ? 16 : 8;
                    $member_money_log['affect_money'] = $v['investor_capital'];
                    $member_money_log['account_money'] = ($accountMoney_investor['account_money'] + $member_money_log['affect_money']); // 投标不成功返回充值资金池
                    $member_money_log['collect_money'] = $accountMoney_investor['money_collect'];
                    $member_money_log['freeze_money'] = $accountMoney_investor['money_freeze'] - $member_money_log['affect_money'];
                    $member_money_log['back_money'] = $accountMoney_investor['back_money'];
                    // 会员帐户
                    $member_money = [];
                    $member_money['money_freeze'] = $member_money_log['freeze_money'];
                    $member_money['money_collect'] = $member_money_log['collect_money'];
                    $member_money['account_money'] = $member_money_log['account_money'];
                    $member_money['back_money'] = $member_money_log['back_money'];
                    // 会员帐户
                    $_xstr = ($type == 3) ? "复审未通过" : "募集期内标未满,流标";
                    $member_money_log['info'] = "第{$borrow_id}号标" . $_xstr . "，返回冻结资金";
                    $member_money_log['add_time'] = time();
                    $member_money_log['add_ip'] = get_client_ip();
                    $member_money_log['target_uid'] = $borrow_info['borrow_uid'];
                    $member_money_log['target_uname'] = $borrow_user_name;
                    
                    if (M('member_moneylog')->add($member_money_log)) {
                        if (! M('member_money')->where("uid={$member_money_log['uid']}")->save($member_money)) {
                            trace(__LINE__);
                            $result = false;
                            break;
                        }
                    } else {
                        trace(__LINE__);
                        $result = false;
                        break;
                    }
                }
                $del_investor_detail = M('investor_detail')->where("borrow_id={$borrow_id}")->delete();
            }else {
                $del_investor_detail = true;
            }
  
            $update_borrow_info = M('borrow_info')->where("id={$borrow_id}")->setField("borrow_status", 3);
         
            if ($result && $del_investor_detail && $update_borrow_info) {
               
                $verify_info = [];
                $verify_info['borrow_id'] = $borrow_id;
                $verify_info['second_deal_info'] = '系统自动流标';
                $verify_info['second_deal_user'] = 0;
                $verify_info['second_deal_time'] = time();
                $verify_info['second_deal_status'] = 3;
                if ($val['first_verify_time'] > 0) {
                    M('borrow_verify')->save($verify_info);
                } else {
                    M('borrow_verify')->add($verify_info);
                }
                $member_info = M("members")->field("mobile,user_name")
                ->where("id = {$val['borrow_uid']}")
                ->find();
                MessageModel::smsTip("refuse", $member_info['mobile'], array(
                    "#USERANEM#",
                    "ID"
                ), array(
                    $member_info['user_name'],
                    $verify_info['borrow_id']
                ));
                $this->commit();
            } else {
                $this->rollback();
                $result = false;
                trace(__LINE__);
                continue;
            }

        }
        return $result;
    }

    /**
     * 满标处理
     *
     * @param number $borrow_id            
     * @param number $type            
     * @author 周阳阳 2017年3月27日 下午3:56:54
     */
    public function borrowFull($borrow_id = 0, $type = 0)
    {
        $save_borrow = [];
        if ($type == 3) { // 秒还标
            $approved_result = $this->borrowApproved($borrow_id);
            if ($approved_result !== true) {
                return false;
            }
            // sleep(2);
            $repatment_result = $this->borrowRepayment($borrow_id, 1);
            if ($repatment_result !== true) {
                return false;
            }
        } else {
            $save_borrow = [];
            $save_borrow['borrow_status'] = 4;
            $save_borrow['full_time'] = time();
            $where = [
                'borrow_id'=>$borrow_id
            ];
            $result = $this->where($where)->save($save_borrow);
            if (empty($result)) {
                return false;
            }
            return true;
        }
    }

    /**
     * 借款成功，进入复审处理
     *
     * @param number $borrow_id            
     * @return boolean
     * @author 周阳阳 2017年3月27日 下午3:57:06
     */
    public function borrowApproved($borrow_id = 0)
    {
        $pre = $this->tablePrefix;
        $result = false;
        $global = GlobalModel::getGlobalSetting();
        $invest_integral = $global['invest_integral']; // 投资积分
        $member_model = new MemberModel();
        // borrow_info 借款信息管理表
        $where = [
            'borrow_status'=>4,   //复审
            'id'=>$borrow_id
        ];
        $borrow_info = $this->where($where)->find();
        if(empty($borrow_info)){
            trace('borrow_status is not 4');
            return false;
        }
        // 借款天数、还款时间
        $endTime = strtotime(date("Y-m-d", time()) . " " . $global['back_time']);
        if ($borrow_info['borrow_type'] == 3 || $borrow_info['repayment_type'] == 1) { // 天标或秒标
            $deadline_last = strtotime("+{$borrow_info['borrow_duration']} day", $endTime);
        } else { // 月标
            $deadline_last = strtotime("+{$borrow_info['borrow_duration']} month", $endTime);
        }
        $get_integral_days = intval(($deadline_last - $endTime) / 3600 / 24); // 借款天数
        $this->startTrans();
        //
        $field = 'id,borrow_id,investor_uid,investor_capital,investor_interest,reward_money';
        $where = [
            'borrow_id' => $borrow_id,
        ];
        $investor_list = M('borrow_investor')->field($field)->where($where) ->select();
        if(empty($investor_list)){
            trace(__LINE__ . 'fail');
            $this->rollback();
            return false;
        }
        $_investor_num = count($investor_list);
        // 更新投资概要
        $borrow_investor = false;
        foreach ($investor_list as $key => $v) {
            $_reward_money = 0;
            if ($borrow_info['reward_type'] > 0) {
                $investor_list[$key]['reward_money'] = ToolModel::getFloatValue($v['investor_capital'] * $borrow_info['reward_num'] / 100, 4);
            } else {
                $investor_list[$key]['reward_money'] = 0;
            }
            
            MessageModel::MTip(10, $v['investor_uid'], $borrow_id);
            $borrow_investor = M('borrow_investor')->where([
                'id' => $v['id']
            ])->save([
                'deadline' => $deadline_last,
                'status' => 4,
                'reward_money' => $investor_list[$key]['reward_money']
            ]);
            if (empty($borrow_investor)) {
                
                trace(__LINE__ . 'fail');
                $this->rollback();
                return false;
                break;
            }
        }
        // 更新投资概要，更新借款信息  
        $save_investor = [
            'deadline' => $deadline_last,
            'status' => 7
        ];
        $where = [
            'borrow_id' => $borrow_id
        ];
        $save_investor_result = false;
        // 更新借款信息 ，更新投资详细
        switch ($borrow_info['repayment_type']) {
            case 2: // 每月还款
            case 3: // 每季还本
            case 4: // 期未还本
                for ($i = 1; $i <= $borrow_info['borrow_duration']; $i ++) {
                    $deadline = strtotime("+{$i} month", $endTime);
                    $save_investor['sort_order'] = $i;
                    $save_investor['deadline'] = $deadline;
                    $save_investor_result = M('investor_detail')->where($where)->save($save_investor);
                }
                break;
            case 1: // 按天一次性还款
            case 5: // 一次性还款
                $deadline = $deadline_last;
                $save_investor['sort_order'] = 1;
                $save_investor['deadline'] = $deadline;
                $save_investor_result = M('investor_detail')->where($where)->save($save_investor);
                break;
        }
        
        if (empty($save_investor_result)) {
            trace(__LINE__ . 'fail');
            $this->rollback();
            return false;
        }
        
        // 借款者帐户
        $_borraccount = MoneyModel::memberMoneyLog($borrow_info['borrow_uid'], 17, $borrow_info['borrow_money'], "第{$borrow_id}号标复审通过，借款金额入帐"); // 借款入帐
                                                                                                                                                // 借款者帐户处理出错
        if (! $_borraccount) {
            trace(__LINE__ . 'fail');
            $this->rollback();
            return false;
        }
        $_borrfee = MoneyModel::memberMoneyLog($borrow_info['borrow_uid'], 18, - $borrow_info['borrow_fee'], "第{$borrow_id}号标借款成功，扣除借款管理费"); // 借款
                                                                                                                                             // 借款者帐户处理出错
        if (! $_borrfee) {
            trace(__LINE__ . 'fail');
            $this->rollback();
            return false;
        }
        $_freezefee = MoneyModel::memberMoneyLog($borrow_info['borrow_uid'], 19, - $borrow_info['borrow_money'] * $global['money_deposit'] / 100, "第{$borrow_id}号标借款成功，冻结{$global['money_deposit']}%的保证金"); // 冻结保证金
                                                                                                                                                                                                            // 借款者帐户处理出错
        if (! $_freezefee) {
            trace(__LINE__ . 'fail');
            $this->rollback();
            return false;
        }
        // 借款者帐户& 投资者帐户
        $_investor_num = count($investor_list);
        $_remoney_do = true;
        foreach ($investor_list as $v) {
            // 增加投资者的投资积分
            $integ = intval($v['investor_capital'] * $get_integral_days * $invest_integral / 1000);
            $reintegral = $member_model->memberIntegralLog($v['investor_uid'], 2, $integ, "第{$borrow_id}号标复审通过，应获积分：" . $integ . "分,投资金额：" . $v['investor_capital'] . "元,投资天数：" . $get_integral_days . "天");
            if (ToolModel::isBirth($v['investor_uid'])) {
                $reintegral = $member_model->memberIntegralLog($v['investor_uid'], 2, $integ, "亲，祝您生日快乐，本站特赠送您{$integ}积分作为礼物，以表祝福。");
            }
            if (empty($reintegral)) {
                trace(__LINE__ . 'fail');
                $this->rollback();
                return false;
                break;
            }
            // 处理待收金额为负的问题
            
            $where = [
                'investor_uid' => $v['investor_uid'],
                'borrow_id' => $v['borrow_id'],
                'invest_id' => $v['id']
            ]
            ;
            $collect = M('investor_detail')->field('interest')
                ->where($where)
                ->sum('interest');
            // 待收金额 &投标奖励
            if ($v['reward_money'] > 0) {
                $_remoney_do = false;
                $_reward_m = MoneyModel::memberMoneyLog($v['investor_uid'], 20, $v['reward_money'], "第{$borrow_id}号标复审通过，获取投标奖励", $borrow_info['borrow_uid']);
                $_reward_m_give = MoneyModel::memberMoneyLog($borrow_info['borrow_uid'], 21, - $v['reward_money'], "第{$borrow_id}号标复审通过，支付投标奖励", $v['investor_uid']);
                if (empty($_reward_m) || empty($_reward_m_give)) {
                    trace(__LINE__ . 'fail');
                    $this->rollback();
                    return false;
                    break;
                }
            }
            // 投标奖励
            $remcollect = MoneyModel::memberMoneyLog($v['investor_uid'], 15, $v['investor_capital'], "第{$borrow_id}号标复审通过，冻结本金成为待收金额", $borrow_info['borrow_uid']);
            $reinterestcollect = MoneyModel::memberMoneyLog($v['investor_uid'], 28, $collect, "第{$borrow_id}号标复审通过，应收利息成为待收利息", $borrow_info['borrow_uid']);
            if (empty($remcollect) || empty($reinterestcollect)) {
               
                trace(__LINE__ . 'fail');
                $this->rollback();
                return false;
                break;
            }
            
            // 邀请奖励开始
            $investor_detail_list = M('members')->field('user_name,recommend_id')->find($v['investor_uid']);
            $_rate = $global['award_invest'] / 1000; // 推广奖励
            $reward = ToolModel::getFloatValue($_rate * $v['investor_capital'], 2);
            if ($investor_detail_list['recommend_id'] != 0) {
                if (($borrow_info['borrow_type'] == '1' || $borrow_info['borrow_type'] == '2' || $borrow_info['borrow_type'] == '5') && $borrow_info['repayment_type'] != '1') {
                    $result = MoneyModel::memberMoneyLog($investor_detail_list['recommend_id'], 13, $reward, $investor_detail_list['user_name'] . "对{$borrow_id}号标投资成功，你获得推广奖励" . $reward . "元。", $v['investor_uid']);
                    if (empty($result)) {
                        trace(__LINE__ . 'fail');
                        $this->rollback();
                        return false;
                        break;
                    }
                }
            }
        }
        
        // 续投奖励预奖励取消开始 ,这是建立在回款续投上
        $list_reward = $this->table($pre . 'today_reward')
            ->field("reward_uid,reward_money")
            ->where("borrow_id={$borrow_id} AND reward_status=0")
            ->select();
        if (! empty($list_reward)) {
            foreach ($list_reward as $v) {
                MoneyModel::memberMoneylog($v['reward_uid'], 34, $v['reward_money'], "续投奖励({$borrow_id}号标)预奖励[{$v['reward_money']}]到账", 0, "@网站管理员@");
            }
            $update_data = [
                'deal_time' => time(),
                'reward_status' => 1
            ];
            $where = array(
                'borrow_id' => (int) $borrow_id,
                'reward_status' => 0
            );
            $result = M('today_reward')->where($where)->save($update_data);
            // 回款续投奖励预奖励取消结束
            if (!empty($result)) {
                $this->commit();
                return true;
            } else {
                trace('回款续投奖励预奖励取消结束 fail');
                $this->rollback();
                return false;
            }
        }
        //
        $where = [
            'id' => $borrow_id
        ];
        $borrow_info_result = M('borrow_info')->where($where)->save([
            'deadline' => $deadline_last,
            'borrow_status' => 6
        ]);
        if (empty($borrow_info_result)) {
            trace(__LINE__ . 'fail');
            $this->rollback();
            return false;
        } else {
            $this->commit();
            return true;
        }
        
    }

    /**
     * 还款处理
     * 
     * @param number $borrow_id
     * @param number $sort_order
     * @param number $type
     * @return string|boolean|string|boolean
     * @author 周阳阳 2017年4月7日 下午2:32:42
     */
    public function borrowRepayment($borrow_id = 0, $sort_order = 0, $type = 1)
    {
        $pre = $this->tablePrefix;
        $result = false;
        $investor_detail_model = M('investor_detail');
        $where = [
            'borrow_status'=>6,   //复审
            'id'=>$borrow_id
        ];
        $borrow_info = $this->where($where)->find();
        if(empty($borrow_info)){
            trace('borrow_status is not 6');
            return false;
        }
        $borrow_info = $this->where($where)->find($borrow_id);
        if ($borrow_info['total'] == 0) {
            $where = [
                'id' => $borrow_id
            ];
            $this->where($where)->setField("total", $borrow_info['borrow_duration']);
        }
        $member_info = M('members')->field("user_name")->find($borrow_info['borrow_uid']);
        // 检测
        $check_repay_result = $this->checkRepay($borrow_info, $sort_order, $type);
        if ($check_repay_result !== true) {
            return $check_repay_result;
        }
        // 判断还款期数不一样
        $field = 'sort_order,sum(capital) as capital,
                 sum(interest) as interest,sum(interest_fee) as interest_fee,
                 deadline,substitute_time';
        $where = [
            'borrow_id' => $borrow_id,
            'sort_order' => (int) $sort_order
        ];
        // 当前要还款信息
        $investor_detail_list = $investor_detail_model->field($field)
            ->where($where)
            ->find();
        // 判断是否逾期了
        if ($investor_detail_list['deadline'] < time()) { // 此标已逾期
            $is_expired = true;
            // 是否代还
            if ($type == 2) {
                // 已代还
                $is_substitute = true;
            } else {
                $is_substitute = false;
            }
            // 逾期的相关计算
            $expired_days = ToolModel::getExpiredDays($investor_detail_list['deadline']);
            $expired_money = ToolModel::getExpiredMoney($expired_days, $investor_detail_list['capital'], $investor_detail_list['interest']);
            $call_fee = ToolModel::getExpiredCallFee($expired_days, $investor_detail_list['capital'], $investor_detail_list['interest']);
        } else {
            $is_expired = false;
            $expired_days = 0;
            $expired_money = 0;
            $call_fee = 0;
        }
        
        // 普通标,判断还款期数不一样
        $borrower_money = $this->getBorrowerAccount($borrow_info['borrow_uid']);
        if ($type == 1 && $borrow_info['borrow_type'] != 3 && ($borrower_money['account_money'] + $borrower_money['back_money']) < ($investor_detail_list['capital'] + $investor_detail_list['interest'] + $expired_money + $call_fee)) {
            return "帐户可用余额不足，本期还款共需" . ($investor_detail_list['capital'] + $investor_detail_list['interest'] + $expired_money + $call_fee) . "元，请先充值";
        }
        // 网站代还且逾期
        if ($is_substitute && $is_expired) {
            return $this->repayExpiredAndSubstitute($borrow_id, $borrow_info, $borrower_money);
            /* 逾期还款积分与还款状态处理结束 */
        } else {
            // 开启事务
            $this->startTrans();
            // 逾期还款积分与还款状态处理开始
            $global = GlobalModel::getGlobalSetting();
            
            if ($type == 1) {
                // 客户自己还款才需要记录这些操作
                $integral_result = $this->writeIntegralLog($borrow_id, $investor_detail_list, $borrow_info, $global);
                if ($integral_result !== true) {
                    $this->rollback();
                    return $integral_result;
                }
            }
            $member_money_log = [];
            // 对借款者帐户进行减少
            if ($type == 1) {
                $borrower_money = $this->getBorrowerAccount($borrow_info['borrow_uid']);
                $member_money_log['uid'] = $borrow_info['borrow_uid'];
                $member_money_log['type'] = 11;
                $member_money_log['affect_money'] = - ($investor_detail_list['capital'] + $investor_detail_list['interest']);
                if (($member_money_log['affect_money'] + $borrower_money['back_money']) < 0) { // 如果需要还款的金额大于回款资金池资金总额
                    $member_money_log['account_money'] = floatval($borrower_money['account_money'] + $borrower_money['back_money'] + $member_money_log['affect_money']);
                    $member_money_log['back_money'] = 0;
                } else {
                    $member_money_log['account_money'] = $borrower_money['account_money'];
                    $member_money_log['back_money'] = floatval($borrower_money['back_money']) + $member_money_log['affect_money']; // 回款资金注入回款资金池
                }
                $member_money_log['collect_money'] = $borrower_money['money_collect'];
                $member_money_log['freeze_money'] = $borrower_money['money_freeze'];
                
                // 会员帐户
                $member_money['money_freeze'] = $member_money_log['freeze_money'];
                $member_money['money_collect'] = $member_money_log['collect_money'];
                $member_money['account_money'] = $member_money_log['account_money'];
                $member_money['back_money'] = $member_money_log['back_money'];
                
                // 会员帐户
                $member_money_log['info'] = "对{$borrow_id}号标第{$sort_order}期还款";
                $member_money_log['add_time'] = time();
                $member_money_log['add_ip'] = get_client_ip();
                $member_money_log['target_uid'] = 0;
                $member_money_log['target_uname'] = '@网站管理员@';
                
                if (M('member_moneylog')->add($member_money_log)) {
                    $where = [
                        'uid' => $member_money_log['uid']
                    ];
                    if (! M('member_money')->where($where)->save($member_money)) {
                        $this->rollback();
                        trace(__LINE__);
                        return false;
                    }
                } else {
                    $this->rollback();
                    trace(__LINE__);
                    return false;
                }
                // 逾期了
                if ($is_expired) {
                    // 逾期罚息
                    $repay_for_expired = $this->repayForExpired($expired_money, $call_fee, $borrow_id, $borrow_info);
                    if ($repay_for_expired !== true) {
                        $this->rollback();
                        trace(__LINE__);
                        return false;
                    }
                }
            }
            /* 对借款者帐户进行减少更新借款信息 */
            $borrow_info_save = [
                'substitute_money' => 0,
                'borrow_status' => 10,
                'repayment_money' => "repayment_money+{$investor_detail_list['capital']}",
                'repayment_interest' => "`repayment_interest`+ {$investor_detail_list['interest']}",
                'has_pay' => $sort_order
            ];
            /* 如果是网站代还的，则记录代还金额 */
            if ($type == 2) {
                $total_money = ($investor_detail_list['capital'] + $investor_detail_list['interest']);
                $borrow_info_save['substitute_money'] = "`substitute_money`+ {$total_money}";
                if ($borrow_info['has_pay'] + 1 == $borrow_info['total']) {
                    $borrow_info_save['borrow_status'] = 9; // 网站代还款完成
                }
            }
            // 自己手动还
            if ($type == 1) {
                if ($sort_order == $borrow_info['total']) {
                    $borrow_info_save['borrow_status'] = 7;
                }
            }
            // 逾期
            if ($is_expired) {
                $borrow_info_save['expired_money'] = "`expired_money`+{$expired_money}";
            }
            $where = [
                'id' => $borrow_id
            ];
            // 更新标的状态，更新借款信息
            $borrow_info_result = $this->where($where)->save($borrow_info_save);
            if (! empty($borrow_info_result)) {
                // @TODO 更新还款详情表
                if ($type == 2) { // 网站代还
                    $where = [
                        'borrow_id' => $borrow_id,
                        'sort_order' => $sort_order
                    ];
                    M('investor_detail')->where($where)->save([
                        'receive_capital' => '`capital`',
                        'substitute_time' => time(),
                        'substitute_money' => "substitute_money+{$total_subs}",
                        'status' => 4
                    ]);
                } else {
                    if ($is_expired) {
                        $this->execute("update `{$pre}investor_detail` set `receive_capital`=`capital` ,`receive_interest`=(`interest`-`interest_fee`),`repayment_time`=" . time() . ",`call_fee`={$call_fee},`expired_money`={$expired_money},`expired_days`={$expired_days},`status`={$this->investorDetailStatus} WHERE `borrow_id`={$borrow_id} AND `sort_order`={$sort_order}");
                    } else {
                        $this->execute("update `{$pre}investor_detail` set `receive_capital`=`capital` ,`receive_interest`=(`interest`-`interest_fee`),`repayment_time`=" . time() . ", `status`={$this->investorDetailStatus} WHERE `borrow_id`={$borrow_id} AND `sort_order`={$sort_order}");
                    }
                    $field = 'invest_id,investor_uid,capital,interest,interest_fee,borrow_id,total';
                    $where = array(
                        'borrow_id' => (int) $borrow_id,
                        'sort_order' => (int) $sort_order
                    );
                    $detailList = $investor_detail_model->field($field) ->where($where)->select();
                    // 更新还款概要表
                    $update_investor_list = $this->updateInvestorList($type,$detailList,$borrow_info,$member_info,$sort_order);   
                   
                    if($update_investor_list !== true){
                        $this->rollback();
                        trace(__LINE__);
                        return false;
                    }
                    $this->commit();
                    // @TODO 这边应该加入队列 发送信息
                    $mobile = [];
                    $where = [
                        'id' => [
                            'in',
                            array_column($detailList, 'investor_uid')
                        ],
                        'is_deny' => 0
                    ];
                    $mobile = $this->table($pre . 'members')
                        ->field("mobile")
                        ->where($where)
                        ->select();
                    $mobile = array_column($mobile, 'mobile');
                    MessageModel::smsTip("payback", $mobile, [
                        "#ID#",
                        "#ORDER#"
                    ], [
                        $borrow_id,
                        $sort_order
                    ]);
                    
                    return  true;
                }
            }
        }
    }
    /**
     * 
     * 
     * @param array $borrow_info
     * @return boolean
     * @author 周阳阳 2017年7月7日 下午2:47:46
     */
    public function lastRepayment($borrow_info = [])
    {  
        $pre = $this->tablePrefix; 
    
        if ($borrow_info['borrow_type'] == 2) {
            // 返回借款人的借款担保额度
            $_result = MemberModel::memberLimitLog($borrow_info['borrow_uid'], 8, ($borrow_info['borrow_money']), $info = "{$borrow_info['id']}号标还款完成");
            if (! $_result) {
                trace(__LINE__);
                return false;
            }
            // 返回投资人的投资担保额度
            $investor_detail_listcuhlist = $this->table($pre . 'borrow_vouch')
                ->field("uid,vouch_money")
                ->where("borrow_id={$borrow_info['id']}")
                ->select();
            foreach ($investor_detail_listcuhlist as $vv) {
                $_result = MemberModel::memberLimitLog($vv['uid'], 10, ($vv['vouch_money']), $info = "您担保的{$borrow_info['id']}号标还款完成");
                if (empty($_result)) {
                    trace(__LINE__);
                    return false;
                }
            }
        } elseif ($borrow_info['borrow_type'] == 1) {
           
            $_result = MemberModel::memberLimitLog($borrow_info['borrow_uid'], 7, ($borrow_info['borrow_money']), $info = "{$borrow_info['id']}号标还款完成");
            if (empty($_result)) {
                trace(__LINE__);
                return false;
            }
        }
        
        // 解冻保证金
        $global = GlobalModel::getGlobalSetting();
        $field = 'account_money,money_collect,money_freeze,back_money';
        $borrower_money = $this->table($pre.'member_money')->field($field)->find($borrow_info['borrow_uid']);
        
        $member_money_log = [];
        $member_money_log['uid'] = $borrow_info['borrow_uid'];
        $member_money_log['type'] = 24;
        $member_money_log['affect_money'] = ($borrow_info['borrow_money'] * $global['money_deposit'] / 100);
        $member_money_log['account_money'] = ($borrower_money['account_money'] + $member_money_log['affect_money']);
        $member_money_log['collect_money'] = $borrower_money['money_collect'];
        $member_money_log['freeze_money'] = ($borrower_money['money_freeze'] - $member_money_log['affect_money']);
        $member_money_log['back_money'] = $borrower_money['back_money'];
        $member_money_log['info'] = "网站对{$borrow_info['id']}号标还款完成的解冻保证金";
        $member_money_log['add_time'] = time();
        $member_money_log['add_ip'] = get_client_ip();
        $member_money_log['target_uid'] = 0;
        $member_money_log['target_uname'] = '@网站管理员@';
        
        // 会员帐户
        $member_money = [];
        $member_money['money_freeze'] = $member_money_log['freeze_money'];
        $member_money['money_collect'] = $member_money_log['collect_money'];
        $member_money['account_money'] = $member_money_log['account_money'];
        $member_money['back_money'] = $member_money_log['back_money'];
        $where = [
            'uid' => $member_money_log['uid']
        ];
        if (M('member_moneylog')->add($member_money_log)) {
            if (! M('member_money')->where($where)->save($member_money)) {
                trace(__LINE__);
                return false;
            }
        }
        return true;
    }

    /**
     * 流标处理
     *
     * @param number $borrow_id            
     * @param number $type $type=2 代表流标返还; $type=3代表复审未通过，返还
     * @return boolean
     * @author 周阳阳 2017年4月11日 下午4:12:42
     */
    public function borrowRefuse($borrow_id = 0, $type = 0)
    {
        $pre = $this->tablePrefix;
        $result = false;
        $field = "id,borrow_type,borrow_money,borrow_uid,borrow_duration,repayment_type";
        $borrow_info = $this->field($field)->find($borrow_id);
        // 开启事务
        $borrow_investor = M('borrow_investor');
        $where = [
            'borrow_id' => (int) $borrow_id
        ];
        $field = 'id,investor_uid,investor_capital';
        $investorList = $borrow_investor->field($field)->where($where)->select();
        
        if(empty($investorList) || empty($borrow_info)){
            return false;
        }
        $this->startTrans();
        if ($borrow_info['borrow_type'] == 1) { // 如果是普通标
            $limit_credit = MemberModel::memberLimitLog($borrow_info['borrow_uid'], 12, ($borrow_info['borrow_money']), $info = "{$borrow_id}号标流标,返还借款信用额度"); // 返回借款额度
        }
       
        // 流标将删除其对应的还款记录表
        M('investor_detail')->where($where)->delete();
        $borrow_status = ($type == 2) ? 3 : 5; // 3:标未满，结束，流标 5:复审未通过，结束
        $update_borrow_info = $this->where("id={$borrow_id}")->setField("borrow_status", $borrow_status);
        // 处理借款概要
        $borrow_user_name= M('members')->getFieldById($borrow_info['borrow_uid'], 'user_name');
        // 处理借款概要
        $where = [
            'borrow_id' => $borrow_id
        ];
        $update_borrow_investor= M('borrow_investor')->where($where)->setField("status", $type);
       
        foreach ($investorList as $v) {
            
            MessageModel::MTip('11', $v['investor_uid'], $borrow_id); // sss
            $accountMoney_investor = M("member_money")->find($v['investor_uid']);
            $member_money_log = [];
            $member_money_log['uid'] = $v['investor_uid'];
            $member_money_log['type'] = ($type == 3) ? 16 : 8;
            $member_money_log['affect_money'] = $v['investor_capital'];
            $member_money_log['account_money'] = ($accountMoney_investor['account_money'] + $member_money_log['affect_money']); // 投标不成功返回充值资金池
            $member_money_log['collect_money'] = $accountMoney_investor['money_collect'];
            $member_money_log['freeze_money'] = $accountMoney_investor['money_freeze'] - $member_money_log['affect_money'];
            $member_money_log['back_money'] = $accountMoney_investor['back_money'];
            
            // 会员帐户
            $member_money = [];
            $member_money['money_freeze'] = $member_money_log['freeze_money'];
            $member_money['money_collect'] = $member_money_log['collect_money'];
            $member_money['account_money'] = $member_money_log['account_money'];
            $member_money['back_money'] = $member_money_log['back_money'];
            
            // 会员帐户
            $_xstr = ($type == 3) ? "复审未通过" : "募集期内标未满,流标";
            $member_money_log['info'] = "第{$borrow_id}号标" . $_xstr . "，返回冻结资金";
            $member_money_log['add_time'] = time();
            $member_money_log['add_ip'] = get_client_ip();
            $member_money_log['target_uid'] = $borrow_info['borrow_uid'];
            $member_money_log['target_uname'] = $borrow_user_name;
            if (M('member_moneylog')->add($member_money_log)) {
                $where = [
                    'uid'=>$member_money_log['uid']
                ];
                if(!M('member_money')->where($where)->save($member_money)){
                    return false;
                }
            }else{
                return false;
            }        
        }
        if ($update_borrow_investor && $update_borrow_info && $limit_credit) {
            // 回款续投奖励预奖励取消开始  
            $field = "reward_uid,reward_money";
            $where = [
                'borrow_id' => $borrow_id,
                'reward_status' => 0
            ];
            $list_reward = M('today_reward')->field($field)->where($where)->select();
            if (! empty($list_reward)) {
                foreach ($list_reward as $v) {
                    MoneyModel::memberMoneyLog($v['reward_uid'], 35, 0 - $v['reward_money'], "续投奖励({$borrow_id}号标)预奖励[{$v['reward_money']}]取消", 0, "@网站管理员@");
                }
                $updata = [];
                $updata['deal_time'] = time();
                $updata['reward_status'] = 2;
                M('today_reward')->where($where)->save($updata);
            }
            $result = true;
            $borrow_investor->commit();
        } else {
            $borrow_investor->rollback();
        }
        return $result;
    }

    /**
     * 获取利息
     *
     * @param int $type    类型
     * @param float $money    金额
     * @param string $duration  期限
     * @param string $rate  年利率
     */
    public function getBorrowInterest($type, $money, $duration, $rate)
    {
        switch ($type) {
            case 1: // 按天到期还款
                $day_rate = $rate / 36500; // 计算出天标的天利率
                $interest = ToolModel::getFloatValue($money * $day_rate * $duration, 4);
                break;
            case 2: // 按月分期还款
                $parm['duration'] = $duration;
                $parm['money'] = $money;
                $parm['year_apr'] = $rate;
                $parm['type'] = "all";
                $intre = ToolModel::EqualMonth($parm);
                $interest = ($intre['repayment_money'] - $money);
                break;
            case 3: // 按季分期还款
                $parm['month_times'] = $duration;
                $parm['account'] = $money;
                $parm['year_apr'] = $rate;
                $parm['type'] = "all";
                $intre = ToolModel::EqualSeason($parm);
                $interest = $intre['interest'];
                break;
            case 4: // 每月还息到期还本
                $parm['month_times'] = $duration;
                $parm['account'] = $money;
                $parm['year_apr'] = $rate;
                $parm['type'] = "all";
                $intre = ToolModel::EqualEndMonth($parm);
                $interest = $intre['interest'];
                break;
            case 5: // 一次性到期还款
                $parm['month_times'] = $duration;
                $parm['account'] = $money;
                $parm['year_apr'] = $rate;
                $parm['type'] = "all";
                $intre = ToolModel::EqualEndMonthOnly($parm);
                $interest = $intre['interest'];
                break;
        }
        return $interest;
    }
    
    public function getBorrowerAccount($uid = 0)
    {
        $field = 'money_freeze,money_collect,account_money,back_money';
        
        return M('member_money')->field($field)->find($uid);
        
    }

    public function repayExpiredAndSubstitute($borrow_id = 0,$borrow_info = [],$borrower_money = [])
    {
        $this->startTrans();
        
        $member_money_log = [];
        $member_money = [];
        $where = [
            'uid'=>$member_money_log['uid']
        ];
        // 已代还后的会员还款，则只需要对会员的帐户进行操作后然后更新还款时间即可返回
        $member_money_log['uid'] = $borrow_info['borrow_uid'];
        $member_money_log['type'] = 11;
        // 待还本金+利息
        $member_money_log['affect_money'] = - ($investor_detail_list['capital'] + $investor_detail_list['interest']);
        // 如果需要还款的金额大于回款资金池资金总额
        if (($member_money_log['affect_money'] + $borrower_money['back_money']) < 0) {
            $member_money_log['account_money'] = $borrower_money['account_money'] + $borrower_money['back_money'] + $member_money_log['affect_money'];
            $member_money_log['back_money'] = 0;
        } else {
            $member_money_log['account_money'] = $borrower_money['account_money'];
            $member_money_log['back_money'] = $borrower_money['back_money'] + $member_money_log['affect_money']; // 回款资金注入回款资金池
        }
        // 待收金额
        $member_money_log['collect_money'] = $borrower_money['money_collect'];
        // 冻结金额
        $member_money_log['freeze_money'] = $borrower_money['money_freeze'];
        
        // 会员帐户
        $member_money['money_freeze'] = $member_money_log['freeze_money'];
        $member_money['money_collect'] = $member_money_log['collect_money'];
        $member_money['account_money'] = $member_money_log['account_money'];
        $member_money['back_money'] = $member_money_log['back_money'];
        // 会员帐户
        $member_money_log['info'] = "对{$borrow_id}号标第{$sort_order}期还款";
        $member_money_log['add_time'] = time();
        $member_money_log['add_ip'] = get_client_ip();
        $member_money_log['target_uid'] = 0;
        $member_money_log['target_uname'] = '@网站管理员@';
        if (M('member_moneylog')->add($member_money_log)) {
            if(!M('member_money')->where($where)->save($member_money)){
                $this->rollback();
                return false;
            }
        }else{
            $this->rollback();
            return false;
        }
        // 逾期罚息
        $accountMoney = $this->getBorrowerAccount($borrow_info['borrow_uid']);
        $member_money_log['type'] = 30;
        
        $member_money_log['affect_money'] = - ($expired_money);
        if (($member_money_log['affect_money'] + $accountMoney['back_money']) < 0) {
            // 如果需要还款的逾期罚息金额大于回款资金池资金总额
            $member_money_log['account_money'] = $accountMoney['account_money'] + $accountMoney['back_money'] + $member_money_log['affect_money'];
            $member_money_log['back_money'] = 0;
        } else {
            $member_money_log['account_money'] = $accountMoney['account_money'];
            $member_money_log['back_money'] = $accountMoney['back_money'] + $member_money_log['affect_money']; // 回款资金注入回款资金池
        }
        $member_money_log['collect_money'] = $accountMoney['money_collect'];
        $member_money_log['freeze_money'] = $accountMoney['money_freeze'];
        
        // 会员帐户
        $member_money['money_freeze'] = $member_money_log['freeze_money'];
        $member_money['money_collect'] = $member_money_log['collect_money'];
        $member_money['account_money'] = $member_money_log['account_money'];
        $member_money['back_money'] = $member_money_log['back_money'];
        // 会员帐户
        $member_money_log['info'] = "{$borrow_id}号标第{$sort_order}期的逾期罚息";
        if (M('member_moneylog')->add($member_money_log)) {
            if(!M('member_money')->where($where)->save($member_money)){
                $this->rollback();
                return false;
            }
        }else{
            $this->rollback();
            return false;
        }
        // 催收费
        $accountMoney = $this->getBorrowerAccount($borrow_info['borrow_uid']);
        
        $member_money_log['type'] = 31;
        $member_money_log['affect_money'] = - ($call_fee);
        if (($member_money_log['affect_money'] + $accountMoney_2['back_money']) < 0) { // 如果需要还款的催收费金额大于回款资金池资金总额
            $member_money_log['account_money'] = $accountMoney_2['account_money'] + $accountMoney_2['back_money'] + $member_money_log['affect_money'];
            $member_money_log['back_money'] = 0;
        } else {
            $member_money_log['account_money'] = $accountMoney_2['account_money'];
            $member_money_log['back_money'] = $accountMoney_2['back_money'] + $member_money_log['affect_money']; // 回款资金注入回款资金池
        }
        $member_money_log['collect_money'] = $accountMoney_2['money_collect'];
        $member_money_log['freeze_money'] = $accountMoney_2['money_freeze'];
        
        // 会员帐户
        $member_money['money_freeze'] = $member_money_log['freeze_money'];
        $member_money['money_collect'] = $member_money_log['collect_money'];
        $member_money['account_money'] = $member_money_log['account_money'];
        $member_money['back_money'] = $member_money_log['back_money'];
        // 会员帐户
        $member_money_log['info'] = "网站对借款人收取的第{$borrow_id}号标第{$sort_order}期的逾期催收费";
        if (M('member_moneylog')->add($member_money_log)) {
            if(!M('member_money')->where($where)->save($member_money)){
                $this->rollback();
                return false;
            }
        }else{
            $this->rollback();
            return false;
        }
        // 逾期了
        $where = [
            'borrow_id' => $borrow_id,
            'sort_order' => $sort_order
        ];
        // 更新借款信息
        $investor_detail_result = M('investor_detail')->where($where)->save([
            'repayment_time' => time(),
            'status' => 5
        ]);
        // 更新借款信息
        $borrow_info_save = [
            'substitute_money' => 0,
            'borrow_status' => 10,
            'repayment_money' => "repayment_money+{$investor_detail_list['capital']}",
            'repayment_interest' => "`repayment_interest`+ {$investor_detail_list['interest']}"
        ];
        if ($sort_order == $borrow_info['total']) {
            $borrow_info_save['borrow_status'] = 7;
        }
        // 逾期
        if ($is_expired) {
            $borrow_info_save['expired_money'] = "`expired_money`+{$expired_money}";
        }
        $where = [
            'id' => $borrow_id
        ];
        //更新标的状态，更新借款信息
        $borrow_info_result = $this->where($where)->save($borrow_info_save);
        
        if (empty($borrow_info_result) || empty($investor_detail_result)) {
            $this->rollback();
            return false;
        }
        MessageModel::MTip(13, $borrow_info['borrow_uid'], $borrow_id);
        
        $this->commit();
        return true;
        
    }
    
    /**
     * 
     * 
     * @param number $expired_money
     * @param number $call_fee
     * @param number $borrow_id
     * @param array $borrow_info
     * @return boolean
     * @author 周阳阳 2017年3月7日 上午10:23:46
     */
    public function repayForExpired($expired_money = 0,$call_fee = 0,$borrow_id =0,$borrow_info=[])
    {
        if ($expired_money > 0) {
            $accountMoney = $this->getBorrowerAccount($borrow_info['borrow_uid']);
            $member_money_log = [];
            $member_money = [];
            $member_money_log['uid'] = $borrow_info['borrow_uid'];
            $member_money_log['type'] = 30;
            $member_money_log['affect_money'] = - ($expired_money);
            if (($member_money_log['affect_money'] + $accountMoney['back_money']) < 0) { // 如果需要还款的逾期罚息金额大于回款资金池资金总额
                $member_money_log['account_money'] = $accountMoney['account_money'] + $accountMoney['back_money'] + $member_money_log['affect_money'];
                $member_money_log['back_money'] = 0;
            } else {
                $member_money_log['account_money'] = $accountMoney['account_money'];
                $member_money_log['back_money'] = $accountMoney['back_money'] + $member_money_log['affect_money']; // 回款资金注入回款资金池
            }
            $member_money_log['collect_money'] = $accountMoney['money_collect'];
            $member_money_log['freeze_money'] = $accountMoney['money_freeze'];
            
            // 会员帐户
            $member_money['money_freeze'] = $member_money_log['freeze_money'];
            $member_money['money_collect'] = $member_money_log['collect_money'];
            $member_money['account_money'] = $member_money_log['account_money'];
            $member_money['back_money'] = $member_money_log['back_money'];
            
            // 会员帐户
            $member_money_log['info'] = "{$borrow_id}号标第{$sort_order}期的逾期罚息";
            $member_money_log['add_time'] = time();
            $member_money_log['add_ip'] = get_client_ip();
            $member_money_log['target_uid'] = 0;
            $member_money_log['target_uname'] = '@网站管理员@';
            
            if (M('member_moneylog')->add($member_money_log)) {
                $where = [
                    'uid'=>$member_money_log['uid']
                ];
                if(!M('member_money')->where($where)->save($member_money)){
                   return false; 
                }
            }else{
                return false;
            }
        }
        
        // 催收费
        if ($call_fee > 0) {
            
            $borrower_money = $this->getBorrowerAccount($borrow_info['borrow_uid']);
            $member_money_log = [];
            $member_money = [];
            
            $member_money_log['uid'] = $borrow_info['borrow_uid'];
            $member_money_log['type'] = 31;
            $member_money_log['affect_money'] = - ($call_fee);
            if (($member_money_log['affect_money'] + $borrower_money['back_money']) < 0) { // 如果需要还款的催收费金额大于回款资金池资金总额
                $member_money_log['account_money'] = $borrower_money['account_money'] + $borrower_money['back_money'] + $member_money_log['affect_money'];
                $member_money_log['back_money'] = 0;
            } else {
                $member_money_log['account_money'] = $borrower_money['account_money'];
                $member_money_log['back_money'] = $borrower_money['back_money'] + $member_money_log['affect_money']; // 回款资金注入回款资金池
            }
            $member_money_log['collect_money'] = $borrower_money['money_collect'];
            $member_money_log['freeze_money'] = $borrower_money['money_freeze'];
            
            // 会员帐户
            $member_money['money_freeze'] = $member_money_log['freeze_money'];
            $member_money['money_collect'] = $member_money_log['collect_money'];
            $member_money['account_money'] = $member_money_log['account_money'];
            $member_money['back_money'] = $member_money_log['back_money'];
            
            // 会员帐户
            $member_money_log['info'] = "网站对借款人收取的第{$borrow_id}号标第{$sort_order}期的逾期催收费";
            $member_money_log['add_time'] = time();
            $member_money_log['add_ip'] = get_client_ip();
            $member_money_log['target_uid'] = 0;
            $member_money_log['target_uname'] = '@网站管理员@';   
            if (M('member_moneylog')->add($member_money_log)) {
                $where = [
                    'uid'=>$member_money_log['uid']
                ];
                if(!M('member_money')->where($where)->save($member_money)){
                    return false;
                }
            }else{
                return false;
            }
        }
        
        return  true;
    }
    
    public function checkRepay($borrow_info = 0,$sort_order = 0,$type = 0)
    {
        if ($borrow_info['has_pay'] >= $sort_order) {
            return "本期已还过，不用再还";
        }
        if ($borrow_info['has_pay'] == $borrow_info['total'] && $borrow_info['has_pay'] != 0) {
            return "此标已经还完，不用再还";
        }
        if (($borrow_info['has_pay'] + 1) < $sort_order) {
            return "对不起，此借款第" . ($borrow_info['has_pay'] + 1) . "期还未还，请先还第" . ($borrow_info['has_pay'] + 1) . "期";
        }
        if ($borrow_info['deadline'] > time() && $type == 2) {
            return "此标还没逾期，不用代还";
        }
        
        return true;
    }
    
    /**
     * 
     * 
     * @param number $borrow_id
     * @param array $investor_detail_list
     * @param array $borrow_info
     * @param array $global
     * @return string|boolean
     * @author 周阳阳 2017年7月7日 下午2:26:06
     */
    public  function writeIntegralLog($borrow_id=0,$investor_detail_list =[],$borrow_info = [],$global = [])
    {
        $credit_borrow = explode("|", $global['credit_borrow']);
        
        $member_model = new MemberModel();
        $day_span = ceil(($investor_detail_list['deadline'] - time()) / (3600 * 24));
        $credits_money = intval($investor_detail_list['capital'] / $credit_borrow[4]);
        $credits_info = "对第{$borrow_id}号标的还款操作,获取投资积分";
        if ($day_span >= 0 && $day_span < 1) { // 正常还款
            $credits_result = $member_model->memberIntegralLog($borrow_info['borrow_uid'], 1, intval($investor_detail_list['capital'] / 1000), "对第{$borrow_id}号标进行了正常的还款操作,获取投资积分"); // 还款积分处理
            $this->investorDetailStatus = 1;
        } elseif ($day_span >= - 3 && $day_span < 0) { // 迟还
            $credits_result = $member_model->memberCreditsLog($borrow_info['borrow_uid'], 4, $credits_money * $credit_borrow[1], "对第{$borrow_id}号标的还款操作(迟到还款),扣除信用积分");
            $this->investorDetailStatus = 3;
        } elseif ($day_span < - 3) { // 逾期还款
            $credits_result = $member_model->memberCreditsLog($borrow_info['borrow_uid'], 5, $credits_money * $credit_borrow[2], "对第{$borrow_id}号标的还款操作(逾期还款),扣除信用积分");
            $this->investorDetailStatus = 5;
        } elseif ($day_span >= 1) { // 提前还款
            $credits_result = $member_model->memberIntegralLog($borrow_info['borrow_uid'], 1, intval($investor_detail_list['capital'] * $day_span / 1000), "对第{$borrow_id}号标进行了提前还款操作,获取投资积分"); // 还款积分处理
            $this->investorDetailStatus = 2;
        }
        if (! $credits_result) {
            return "因积分记录失败，未完成还款操作";
        }
        
        return true;
    }
    
    /**
     * 
     * 
     * @param array $detailList
     * @param array $borrow_info
     * @param array $member_info
     * @param number $sort_order
     * @return boolean
     * @author 周阳阳 2017年2月7日 下午3:05:58
     */
    public function updateInvestorList($type = 1,$detailList = [],$borrow_info = [],$member_info=[],$sort_order =0)
    {
        
        foreach ($detailList as $v) {
            // 用于判断是否债权转让 ,债权转让日志不一样
            $where = [
                'invest_id' => $v['invest_id'],
                'status' => 1
            ];
            $debt = [];
            $debt = M("invest_detb")->field("serialid")
                ->where($where)
                ->find();
            
            $get_interest = $v['interest'] - $v['interest_fee'];
            // 要保存的数据
            $borrow_investor_save = [
                'receive_capital' => "`receive_capital`+{$v['capital']}",
                'receive_interest' => "`receive_interest`+ {$get_interest}"
            ];
            if ($type == 2) {
                $total_invest = $v['capital'] + $get_interest;
                $borrow_investor_save['substitute_money'] = "`substitute_money` + {$total_invest}";
            }
            if ($sort_order == $borrow_info['total']) {
                
                $borrow_investor_save['status'] = 5; // 还款完成
                $borrow_investor_save['paid_fee'] = "`paid_fee`+{$v['interest_fee']}";
                $where = [
                    'id' => $v['invest_id']
                ];
                // 对投资帐户进行增加
                if (M('borrow_investor')->where($where)->save($borrow_investor_save)) {
                    $member_money_log = [];
                    $accountMoney = [];
                    $accountMoney = $this->getBorrowerAccount($v['investor_uid']);
                    $member_money_log['uid'] = $v['investor_uid'];
                    $member_money_log['type'] = ($type == 2) ? "10" : "9";
                    $member_money_log['affect_money'] = ($v['capital'] + $v['interest']); // 先收利息加本金，再扣管理费
                    $member_money_log['collect_money'] = $accountMoney['money_collect'] - $member_money_log['affect_money'];
                    $member_money_log['freeze_money'] = $accountMoney['money_freeze'];
                    
                    // 秒标回款不进入汇款资金池，也就可实现秒标回款不给回款续投奖励的功能
                    if ($borrow_info['borrow_type'] != 3) {
                        // 如果不是秒标，那么回的款会进入回款资金池，如果是秒标，回款则会进入充值资金池
                        $member_money_log['account_money'] = $accountMoney['account_money'];
                        $member_money_log['back_money'] = ($accountMoney['back_money'] + $member_money_log['affect_money']);
                    } else {
                        $member_money_log['account_money'] = $accountMoney['account_money'] + $member_money_log['affect_money'];
                        $member_money_log['back_money'] = $accountMoney['back_money'];
                    }
                    // 会员帐户
                    $member_money = [];
                    $member_money['money_freeze'] = $member_money_log['freeze_money'];
                    $member_money['money_collect'] = $member_money_log['collect_money'];
                    $member_money['account_money'] = $member_money_log['account_money'];
                    $member_money['back_money'] = $member_money_log['back_money'];
                    // 会员帐户
                    $member_money_log['info'] = ($type == 2) ? "网站对{$v['borrow_id']}号标第{$sort_order}期代还" : "收到会员对{$v['borrow_id']}号标第{$sort_order}期的还款";
                    // 如果债权流水号存在
                    $debt['serialid'] && $member_money_log['info'] = ($type == 2) ? "网站对{$debt['serialid']}号债权第{$sort_order}期代还" : "收到会员对{$debt['serialid']}号债权第{$sort_order}期的还款";
                    $member_money_log['add_time'] = time();
                    $member_money_log['add_ip'] = get_client_ip();
                    if ($type == 2) {
                        $member_money_log['target_uid'] = 0;
                        $member_money_log['target_uname'] = '@网站管理员@';
                    } else {
                        $member_money_log['target_uid'] = $borrow_info['borrow_uid'];
                        $member_money_log['target_uname'] = $member_info['user_name'];
                    }
                    
                    if (M('member_moneylog')->add($member_money_log)) {
                        $where = [
                            'uid' => $member_money_log['uid']
                        ];
                        if (! M('member_money')->where($where)->save($member_money)) {
                            trace(__LINE__);
                            break;
                           
                            return false;
                        }
                    } else {
                        trace(__LINE__);
                        break;
                        return false;
                    }
                    
                    if ($type == 2) { // 如果是网站代还
                        MessageModel::MTip(13, $v['investor_uid'], $borrow_id);
                    } else {
                        MessageModel::MTip(12, $v['investor_uid'], $borrow_id);
                    }
                    // 利息管理费
                    if ($v['interest_fee'] > 0 && $type == 1) {
                        $accountMoney = $this->getBorrowerAccount($v['investor_uid']);
                        $member_money_log = [];
                        $member_money_log['uid'] = $v['investor_uid'];
                        $member_money_log['type'] = 23;
                        $member_money_log['affect_money'] = - ($v['interest_fee']); // 扣管理费
                        $member_money_log['collect_money'] = $accountMoney['money_collect'];
                        $member_money_log['freeze_money'] = $accountMoney['money_freeze'];
                        if (($accountMoney['back_money'] + $member_money_log['affect_money']) < 0) {
                            $member_money_log['back_money'] = 0;
                            $member_money_log['account_money'] = $accountMoney['account_money'] + $accountMoney['back_money'] + $member_money_log['affect_money'];
                        } else {
                            $member_money_log['account_money'] = $accountMoney['account_money'];
                            $member_money_log['back_money'] = ($accountMoney['back_money'] + $member_money_log['affect_money']);
                        }
                        $member_money = [];
                        // 会员帐户
                        $member_money['money_freeze'] = $member_money_log['freeze_money'];
                        $member_money['money_collect'] = $member_money_log['collect_money'];
                        $member_money['account_money'] = $member_money_log['account_money'];
                        $member_money['back_money'] = $member_money_log['back_money'];
                        
                        // 会员帐户
                        $member_money_log['info'] = "网站已将第{$v['borrow_id']}号标第{$sort_order}期还款的利息管理费扣除";
                        $member_money_log['add_time'] = time();
                        $member_money_log['add_ip'] = get_client_ip();
                        $member_money_log['target_uid'] = 0;
                        $member_money_log['target_uname'] = '@网站管理员@';
                        
                        if (M('member_moneylog')->add($member_money_log)) {
                            $where = [
                                'uid' => $member_money_log['uid']
                            ];
                            if (! M('member_money')->where($where)->save($member_money)) {
                                trace(__LINE__);
                                break;
                                return false;
                            }
                        } else {
                            trace(__LINE__);
                            break;
                            return false;
                        }
                    }
                    // 利息管理费
                    MessageModel::MTip(14, $borrow_info['borrow_uid'], $borrow_id);
                    // 撤销转让的债权 ,完成还款更改债权转让状态
                    // @TODO 未有债权功能 cancelDebt($borrow_id);
                    if ($borrow_info['total'] == ($borrow_info['has_pay'] + 1) && $type == 1) {
                        $_is_last = $this->lastRepayment($borrow_info); // 最后一笔还款
                        if($_is_last !==true){
                            trace(__LINE__);
                            break;
                            return false;
                        }
                    } else { 
                        trace(__LINE__);
                        break;
                        return false;
                    }
                }
            }
        }
        
        return true;
    }
}