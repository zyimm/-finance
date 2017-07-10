<?php
namespace Member\Model;

use Think\Model;
use Think\Page;
use Front\Model\MessageModel;
use Front\Model\ConfigModel;
use Front\Model\ToolModel;

class MemberModel extends Model
{
    protected $tableName = 'members';
    
    
    public static function memberLimitLog($uid = 0, $type = 0, $alimit, $info = "")
    {
        $alimit = floatval($alimit);
        if (empty($uid)){
            return true;
        }
        $result = false;
        $field = "money_freeze,money_collect,account_money,back_money";
        $member_money= [];
        $member_money= M("member_money")->field($field, true)->find($uid);
        //@TODO 为空新增
        if (!is_array($member_money)) {
            M("member_money")->add(array(
                'uid' => $uid
            ));
            $member_money= M("member_money")->field($field, true)->find($uid);
        }
        $member_moneylog_model= M('member_moneylog');
        //@TODO
        $_type = [
            71,
            72,
            73
        ];
        if (in_array($type,$_type)){
            $type_save = 7;
        }else{
            $type_save = $type;
        }
        M("member_money")->startTrans();
        $data = [];
        $data['uid'] = $uid;
        $data['type'] = $type_save;
        $data['info'] = $info;
        $data['add_time'] = time();
        $data['add_ip'] = get_client_ip();
        $data['credit_limit'] = 0;
        $data['borrow_vouch_limit'] = 0;
        $data['invest_vouch_limit'] = 0;
        //@TODO
        $_data = [];
        $final_member_money = [];
        switch ($type) {
            case 1: // 信用标初审通过暂扣
            case 4: // 信用标复审未通过返回
            case 7: // 标的完成，返回
            case 12: // 流标，返回
                $_data['credit_limit'] = $alimit;
                break;
            case 2: // 担保标初审通过暂扣
            case 5: // 担保标复审未通过返回
            case 8: // 标的完成，返回
                $_data['borrow_vouch_limit'] = $alimit;
                break;
            case 3: // 参与担保暂扣
            case 6: // 所担保的标初审未通过，返回
            case 9: // 所担保的标复审未通过，返回
            case 10: // 标的完成，返回
                $_data['invest_vouch_limit'] = $alimit;
                break;
            case 11: // VIP审核通过
                $_data['credit_limit'] = $alimit;
                $final_member_money['credit_limit'] = $member_money['credit_limit'] + $_data['credit_limit'];
                break;
        }
        $data = array_merge($data, $_data);
        $insert_id = false;
        $insert_id = M('member_limitlog')->add($data);
        // 帐户更新
        $final_member_money['credit_cuse'] = $member_money['credit_cuse'] + $data['credit_limit'];
        $final_member_money['borrow_vouch_cuse'] = $member_money['borrow_vouch_cuse'] + $data['borrow_vouch_limit'];
        $final_member_money['invest_vouch_cuse'] = $member_money['invest_vouch_cuse'] + $data['invest_vouch_limit'];
        if (!empty($insert_id)){
            
            if (M('member_money')->where(['uid'=>$uid])->save($final_member_money)) {
                M("member_money")->commit();
                $result = true;
            } else {
                M("member_money")->rollback();
            }
        }else{
            M("member_money")->rollback();
        }    
        return $result;
    }

    /**
     * 获取用户的资金流水记录
     * 
     * @param array $map
     * @param number $size
     * @return array
     * @author 周阳阳 2017年3月14日 下午5:04:24
     */
    public function getMoneyLog($map = array(), $size = 10)
    {
        if (empty($map['uid'])){
            return [];
        }  
        $size = empty($size)?C('PAGE_SIZE'):$size;
        $count = M('member_moneylog')->where($map)->count('id');
        $p = new Page($count, $size);
        $page_show = $p->ajaxShow();
        $rows = M('member_moneylog')->where($map)
                ->order('id DESC')
                ->page($map['page_now'],$size)
                ->select();
        $type = C("MONEY_LOG");
        foreach ($rows as $key => $v) {
            $rows[$key]['type'] = $type[$v['type']];
        }
        return [
            'rows'=>$rows,
            'page_show'=>$page_show      
        ];
    }

  
    /**
     * 获取个概要信息列表
     * @param number $uid            
     * @param string $field            
     * @return array
     * @author 周阳阳 2017年3月17日 下午5:18:48
     */
    public function getMinfo($uid = 0, $field = 'm.pay_pass,mm.account_money,mm.back_money')
    {
        $pre = $this->tablePrefix;
        $data = [];
        $where = [
            'm.id'=>(int)$uid,
            'is_deny'=>0
        ];
        $data = $this->alias('m')->field($field)
            ->join("{$pre}member_money mm ON mm.uid=m.id")
            ->where($where)
            ->find();
        return $data;
    }
    /**
     * 修改的积分记录
     * 
     * @param number $uid
     * @param number $type
     * @param number $integral
     * @param string $info
     * @return boolean
     * @author 周阳阳 2017年3月27日 下午4:07:59
     */
    public function memberIntegralLog($uid =0,$type = 0,$integral = 0,$info="无")
    {
        if($integral==0){
            return true;
        }
        $pre = $this->tablePrefix;
        $result = false;
        $this->startTrans(); //多表事务
        $member = $this->table($pre."members")->where(['id'=>$uid])->find();
        $data = [];
        $data['uid'] = $uid;
        $data['type'] = $type;
        $data['affect_integral'] = $integral;
        $data['active_integral'] = $integral + $member['active_integral'];
        $data['account_integral'] = $integral + $member['integral'];
        $data['info'] = $info;
        $data['add_time'] = time();
        $data['add_ip'] = get_client_ip();
        if ($integral<0 && $data['active_integral']<0){//判断积分是否消费过头
            return false;
        } elseif ($integral<0 && $data['active_integral']>0){//消费积分只减活跃积分，总积分不变
            $data['account_integral'] = $member['integral'];
        }
    
        //消费积分为负数，消费积分只减活跃积分，不减总积分
        $insert_id = $active_id = $save_id = false;
        $insert_id = M('member_integrallog')->add($data);//积分细则
        $active_id = $this->where(['id'=>$uid])->setInc('active_integral',$integral);//活跃积分总数
        //积分总数
        if($integral>0){
            $save_id = $this->where("id=$uid")->setInc('integral',$integral);
        }else{
            $save_id= true;
        }
        if(!empty($insert_id) && !empty($active_id) && !empty($save_id)){
            $this->commit() ;
            $result = true;
        }else{
            $this->rollback() ;
        }
    
        return $result;
    }
    
    /**
     * 会员认证奖励积分记录
     * 
     * @param int $uid 用户id
     * @param int $type 类型
     * @param int $acredits 增加的积分
     * @param string $info 积分变动说明
     */
    public function memberCreditsLog($uid = 0,$type = 0,$acredits = 0,$info=null)
    {
        if($acredits==0 || empty($uid)){
            return true;
        }
        $flag = false;
        $mCredits = $this->getFieldById($uid,'credits');
        $Creditslog = M('memeber_credits_log');
        $Creditslog->startTrans();
        $data = [];
        $data['uid'] = $uid;
        $data['type'] = $type;
        $data['affect_credits'] = $acredits;
        $data['account_credits'] = $mCredits + $acredits;
        $data['info'] = $info;
        $data['add_time'] = time();
        $data['add_ip'] = get_client_ip();
        $neId = $Creditslog->add($data);
        $sid = $this->where("id={$uid}")->setField('credits',$data['account_credits']);
        if(!empty($info)){
            MessageModel::MTip(19,$uid,$info);
        }else{
            MessageModel::MTip(19,$uid,'您获得'.$acredits.'积分');
        }
        if($sid){
            $Creditslog->commit() ;
            $flag = true;
        }else{
            $Creditslog->rollback() ;
        }
        return $flag;
    }
    
    /**
     * 设置用户认证积分 
     * 
     * @param int $uid  // 用户id
     * @param int  $status // 状态0 or 1
     * @param string $type //类别
     * @param string $field //字段
     */
    public function setMemberStatus($uid,$type,$status,$id,$field)
    {
        $uid = intval($uid);
        $status = intval($status);
        $integration = ConfigModel::read('integration');
        $credits = $integration[$type]['fraction'];
        $log_info = $integration[$type]['description'];
        $where = [
            'uid'=>$uid
        ];
        if(M('members_status')->where($where)->setField($field,$status)){
            if($status!=0){
                if($this->memberCreditsLog($uid,$id,$credits,$log_info."认证通过,奖励积分{$credits}")){
                    return true;
                }else{
                    return false;
                }
            }
        }else{
            if(M('members_status')->where($where)->getField($field)){
                return true;
            }else{
                return false;
            }
        }
    }

    public function getMemberDetail($uid = 0)
    {
        $pre = C('DB_PREFIX');
        $map['m.id'] = $uid;
        $field = 'province,province_now,province_now,city,city_now';
        $list = $this->table($this->tablePrefix . 'member_info')
            ->field($field)
            ->find($uid);
        return $list;
    }

    public function getMemberBorrowScan($uid = 0)
    {
        // 借款次数相关
        $field = "borrow_status,count(id) as num,sum(borrow_money) as money,sum(repayment_money) as repayment_money";
        $where = [
            "borrow_uid" => $uid
        ];
        $borrowNum = M('borrow_info')->field($field)
            ->where($where)
            ->group('borrow_status')
            ->select();
        
        foreach ($borrowNum as $v) {
            $borrowCount[$v['borrow_status']] = $v;
        }
        // 借款次数相关&还款情况相关
        $field = "status,sort_order,borrow_id,sum(capital) as capital,sum(interest) as interest";
        $repaymentNum = M('investor_detail')->field($field)
            ->where($where)
            ->group('sort_order,borrow_id')
            ->select();
        $repaymentStatus = [];
        foreach ($repaymentNum as $v) {
            $repaymentStatus[$v['status']]['capital'] += $v['capital']; // 当前状态下的数金额
            $repaymentStatus[$v['status']]['interest'] += $v['interest']; // 当前状态下的数金额
            $repaymentStatus[$v['status']]['num'] ++; // 当前状态下的总笔数
        }
        // 还款情况相关&借出情况相关
        $investStatus = [];
        $field = "status,count(id) as num,sum(investor_capital) as investor_capital,sum(reward_money) as reward_money,sum(investor_interest) as investor_interest,sum(receive_capital) as receive_capital,sum(receive_interest) as receive_interest,sum(invest_fee) as invest_fee";
        $investNum = M('borrow_investor')->field($field)
            ->where("investor_uid = {$uid}")
            ->group('status')
            ->select();
        $_reward_money = 0;
        foreach ($investNum as $v) {
            $investStatus[$v['status']] = $v;
            $_reward_money += floatval($v['reward_money']);
        }
        // 借出情况相关&逾期的借入
        $field = "borrow_id,sort_order,sum(`capital`) as capital,count(id) as num";
        $expiredNum = M('investor_detail')->field($field)
            ->where("`repayment_time`=0 and borrow_uid={$uid} AND status=7 and `deadline`<" . time() . " ")
            ->group('borrow_id,sort_order')
            ->select();
        $_expired_money = 0;
        $expiredStatus = [];
        foreach ($expiredNum as $v) {
            $expiredStatus[$v['borrow_id']][$v['sort_order']] = $v;
            $_expired_money += floatval($v['capital']);
        }
        // 统计
        $statistics = [];
        $statistics['expiredMoney'] = ToolModel::getFloatValue($_expired_money, 2); // 逾期金额
        $statistics['expiredNum'] = count($expiredNum); // 逾期期数
                                                        // 逾期的借入&逾期的投资
        $field = "borrow_id,sort_order,sum(`capital`) as capital,count(id) as num";
        $expiredInvestNum = M('investor_detail')->field($field)
            ->where("`repayment_time`=0 and `deadline`<" . time() . " and investor_uid={$uid} AND status <> 0")
            ->group('borrow_id,sort_order')
            ->select();
        $_expired_invest_money = 0;
        $expiredInvestStatus = [];
        foreach ($expiredInvestNum as $v) {
            $expiredInvestStatus[$v['borrow_id']][$v['sort_order']] = $v;
            $_expired_invest_money += floatval($v['capital']);
        }
        $statistics['expiredInvestMoney'] = ToolModel::getFloatValue($_expired_invest_money, 2); // 逾期金额
        $statistics['expiredInvestNum'] = count($expiredInvestNum); // 逾期期数
       
        $statistics['jkze'] = ToolModel::getFloatValue(floatval($borrowCount[6]['money'] + $borrowCount[7]['money'] + $borrowCount[8]['money'] + $borrowCount[9]['money']), 2); // 借款总额
        $statistics['yhze'] = ToolModel::getFloatValue(floatval($borrowCount[6]['repayment_money'] + $borrowCount[7]['repayment_money'] + $borrowCount[8]['repayment_money'] + $borrowCount[9]['repayment_money']), 2); // 应还总额
        $statistics['dhze'] = ToolModel::getFloatValue($statistics['jkze'] - $statistics['yhze'], 2); // 待还总额
        $statistics['jcze'] = ToolModel::getFloatValue(floatval($investStatus[4]['investor_capital']), 2); // 借出总额
        $statistics['ysze'] = ToolModel::getFloatValue(floatval($investStatus[4]['receive_capital']), 2); // 应收总额
        $statistics['dsze'] = ToolModel::getFloatValue($statistics['jcze'] - $statistics['ysze'], 2);
        $statistics['fz'] = ToolModel::getFloatValue($statistics['jcze'] - $statistics['jkze'], 2);
        
        $statistics['dqrtb'] = ToolModel::getFloatValue($investStatus[1]['investor_capital'], 2); // 待确认投标
        // 净赚利息
        $field = 'sum(investor_interest)as investor_interest, sum(invest_fee) as invest_fee';
        $where = [
            'investor_uid' => $uid,
            'status' => 1
        
        ];
        $circulation = M('transfer_borrow_investor')->field($field)
            ->where($where)
            ->find();
        $statistics['earnInterest'] = ToolModel::getFloatValue(floatval($investStatus[5]['receive_interest'] + $investStatus[6]['receive_interest'] + $circulation['investor_interest'] - $investStatus[5]['invest_fee'] - $investStatus[6]['invest_fee'] - $circulation['invest_fee']), 2);
        // 净赚利息
        $receive_interest = M('transfer_borrow_investor')->where('investor_uid=' . $uid)->sum('investor_capital');
        $statistics['payInterest'] = ToolModel::getFloatValue(floatval($repaymentStatus[1]['interest'] + $repaymentStatus[2]['interest'] + $repaymentStatus[3]['interest']), 2); // 净付利息
        $statistics['willgetInterest'] = ToolModel::getFloatValue(floatval($investStatus[4]['investor_interest'] - $investStatus[4]['receive_interest']), 2); // 待收利息
        $statistics['willpayInterest'] = ToolModel::getFloatValue(floatval($repaymentStatus[7]['interest']), 2); // 待确认支付管理费
        $statistics['borrowOut'] = ToolModel::getFloatValue(floatval($investStatus[4]['investor_capital'] + $investStatus[5]['investor_capital'] + $investStatus[6]['investor_capital'] + $receive_interest), 2); // 借出总额
        $statistics['borrowIn'] = ToolModel::getFloatValue(floatval($borrowCount[6]['money'] + $borrowCount[7]['money'] + $borrowCount[8]['money'] + $borrowCount[9]['money']), 2); // 借入总额
        
        $statistics['jkcgcs'] = $borrowCount[6]['num'] + $borrowCount[7]['num'] + $borrowCount[8]['num'] + $borrowCount[9]['num']; // 借款成功次数
        $statistics['tbjl'] = $_reward_money; // 投标奖励
                                              
        // 处理流转标的相关数据
        // 流转标借出未确定的金额及数量
        $field = 'sum(investor_capital) as investor_capital, count(id) as num';
        $where = [
            'investor_uid' => $uid,
            'status' => 1
        ];
        $circulation_bor = M('transfer_borrow_investor')->field($field)
            ->where($where)
            ->find();
        $investStatus[8]['investor_capital'] += $circulation_bor['investor_capital'];
        $investStatus[8]['num'] += $circulation_bor['num'];
        unset($circulation_bor);
        // 流转标已回收的投资及数量
        $field = 'sum(investor_capital) as investor_capital, count(id) as num';
        $where = [
            'investor_uid' => $uid,
            'status' => 2
        ];
        $circulation_bor = M('transfer_borrow_investor')->field($field)
            ->where($where)
            ->find();
        $investStatus[9]['investor_capital'] += $circulation_bor['investor_capital'];
        $investStatus[9]['num'] += $circulation_bor['num'];
        // 完成的投资
        $circulation_bor = M("transfer_borrow_investor i")->field('sum(i.investor_capital) as investor_capital, count(i.id) as num')
            ->where('i.status=2 and i.investor_uid=' . $uid)
            ->join("{$pre}transfer_borrow_info b ON b.id=i.borrow_id")
            ->order("i.id DESC")
            ->find();
        $row = [];
        $row['tborrowOut'] = $receive_interest; // 流转标借出总额
        $row['borrow'] = $borrowCount;
        $row['repayment'] = $repaymentStatus;
        $row['invest'] = $investStatus;
        $row['statistics'] = $statistics;
        $row['circulation_bor'] = $circulation_bor;
        return $row;
    }
}