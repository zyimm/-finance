<?php
namespace Front\Model;

use Think\Model;
use Member\Model\AutoModel;
use Member\Model\MemberModel;
use Member\Model\MoneyModel;

class InvestModel extends Model
{
    public $prefix ;
    protected $tableName = 'borrow_investor';
    public  function __construct()
    {
        parent::__construct();
        $this->prefix = C('DB_PREFIX');
        
    }
    /**
     * 
     * 
     * @param number $borrow_id
     * @return boolean
     * @author 周阳阳 2017年6月9日 下午5:10:04
     */
    public static function autoInvest($borrow_id = 0)
    {
        if (empty($borrow_id)) {
            return false;
        }

        $borrow_info = $this->table($this->tablePrefix."borrow_info")->find($borrow_id);
        if ($borrow_info['can_auto'] == '0') {
            return false;
        }
        $map = [];
        $map['a.is_use'] = 1;
        $map['a.borrow_type'] = 1;
        $map['a.end_time'] = [
            "gt",
            time()
        ];
        // 按照时间
        $auto_model = new AutoModel();
        //自动投标
        $auto_list = $auto_model->autoList();
        $need_money = $borrow_info['borrow_money'] - $borrow_info['has_borrow'];
        if ((float) $need_money <= 0) {
            return false;
        }
        foreach ($auto_list as $key => $v) {
            
            if ($v['uid'] == $borrow_info['borrow_uid']) {
                continue;
            }
            // 能够投资金额数
            $can_invest_money = intval($v['money'] - $v['account_money']); // 账户余额-设置的最少剩余金额=投资金额数
                                                                   // 最大投资金额
            $limit_invest_money= $borrow_info['borrow_money'] * 0.1; // 不能超过10%
            if ($v['invest_money'] > $borrow_info['borrow_max'] && $borrow_info['borrow_max'] > 0) { // 大于最大投标 且设置最大投标
                                                                                                     // 限制后的金额
                $invest_money = $borrow_info['borrow_max'];
            }
            if ($can_invest_money > $v['invest_money']) { // 如果可用的投资金额大于最大投资金额，则投资金额=最大投资金额
                $invest_money = $v['invest_money'];
            } else {
                $invest_money = $can_invest_money; // 如果未设置投标后账户余额，则会投出全部余额
            }
            if ($invest_money > $need_money) {
                $invest_money = $need_money;
            } else 
                if ($borrow_info['borrow_min']) { // 设置了最小投标 如果直接满标则不考虑最小投标
                    if ($invest_money < $borrow_info['borrow_min']) { // 小于最低投标
                        continue; // 不符合最低投资金额
                    } elseif (($need_money - $invest_money) > 0 && ($need_money - $invest_money) < $borrow_info['borrow_min']) { // 剩余金额小于最小投标金额
                        if (($invest_money - $borrow_info['borrow_min']) >= $borrow_info['borrow_min']) { // 投资金额- 最小投资金额 大于最小投资金额
                            $invest_money = $invest_money - $borrow_info['borrow_min']; // 投资 = 投资-最小投资（保证下次投资金额大于最小投资金额）
                        } else {
                            continue;
                        }
                    }
                }
            if ($invest_money > $limit_invest_money) { // 投资金额不能大于借款金额的10%
                $invest_money = $limit_invest_money;
            }
            if ($invest_money % $borrow_info['borrow_min'] != 0 && $invest_money % $borrow_info['borrow_min'] > 0) { // 如果当前可投金额不是最小投资金额的整数倍
                                                                                                                   // $invest_money = $borrow_info['borrow_min']*floor($invest_money%$borrow_info['borrow_min']);
                $invest_money -= floor($invest_money % $borrow_info['borrow_min']);
            }
            if ($v['interest_rate'] > 0) {
                if (! ($borrow_info['borrow_interest_rate'] >= $v['interest_rate'])) { // 利率范围
                    continue;
                }
            }
            if ($v['duration_from'] > 0 && $v['duration_to'] > 0 && $v['duration_from'] <= $v['duration_to']) { // 借款期限范围
                if (! (($borrow_info['borrow_duration'] >= $v['duration_from']) && ($borrow_info['borrow_duration'] <= $v['duration_to']))) {
                    continue;
                }
            }
            if (! ($invest_money >= $v['min_invest'])) { //
                continue;
            }
            if (! ($v['money'] - $v['account_money'] >= $invest_money)) { // 余额限制
                continue;
            }
            
            $x = $this->investMoney($v['uid'], $borrow_id, $invest_money, 1);
            if ($x === true) {
                $need_money = $need_money - $invest_money; // 减去剩余已投金额
                MessageModel::MTip(15, $v['id'], $borrow_id, $v['id']); //
                $auto_model->where('id = ' . $v['id'])->save(array(
                    "invest_time" => time()
                ));
            }
        }
        return true;
    }
    /**
     * 投标
     * 
     * @param number $uid
     * @param string $user_name
     * @param number $borrow_id
     * @param number $money
     * @param number $_is_auto
     * @return string|boolean
     * @author 周阳阳 2017年3月6日 下午5:11:45
     */
    public function investMoney($uid = 0, $user_name='',$borrow_id = 0, $money = 0,$_is_auto = 0)
    {
    
        $final_result = false;
        $golbal = GlobalModel::getGlobalSetting();
        $member_model = new MemberModel();

        //@TODO
        $check_result = $this->checkInvest($borrow_id,$money,I('post.'),$uid,$user_name);
        if(!is_array($check_result)){
            return $check_result;
        }else{
            $borrow_info =$check_result;
        }
        $member_info = [];
        $field = 'm.user_leve,m.time_limit,mm.account_money,mm.back_money,mm.money_collect';
        $member_info = $member_model->getMinfo($uid,$field);
        
        if (($member_info['account_money'] + $member_info['back_money'] + $borrow_info['reward_money']) < $money) {
            return "您当前的可用金额为：" . ($member_info['account_money'] + $member_info['back_money'] + $borrow_info['reward_money']) . " 对不起，可用余额不足，不能投标";
        }
        
        // 新增投标时检测会员的待收金额是否大于标的设置的代收金额限制，大于就可投标，小于就不让投标
        if ($borrow_info['money_collect'] > 0) { // 判断是否设置了投标待收金额限制
            if ($member_info['money_collect'] < $borrow_info['money_collect']) {
                return "对不起，此标设置有投标待收金额限制，您当前的待收金额为" .$member_info['money_collect'] . "元，小于该标设置的待收金额限制" . $borrow_info['money_collect'] . "元。";
            }
        }
        // 不同会员级别的费率
        $fee_rate = $golbal['fee_invest_manage'] / 100;
        // 投入的钱
        $have_money = $borrow_info['has_borrow'];
       
        $where = [
            'borrow_id'=>$borrow_id,
        ];
        $borrow_invest = M('borrow_investor')->where($where)->sum('investor_capital'); // 新加投资金额检测
      
        if (($borrow_info['borrow_money'] - $have_money- $money) < 0) {
            return "对不起，此标还差" . ($borrow_info['borrow_money'] - $have_money) . "元满标，您最多投标" . ($borrow_info['borrow_money'] - $have_money) . "元";
        }
        // 防止恶意窜改投标金额
        if (($money % $borrow_info['borrow_min']) > 0) {
            return "投标金额不是起投金额的整数倍!";
        }
        // 开启事务
        $this->startTrans();
        $invest_info = [];
        $invest_info['status'] = 1; // 等待复审
        $invest_info['borrow_id'] = $borrow_id;
        $invest_info['investor_uid'] = $uid;
        $invest_info['borrow_uid'] = $borrow_info['borrow_uid'];
        // 新加投资金额检测
        if ($borrow_invest > $borrow_info['borrow_money']) {
            $invest_info['investor_capital'] = $borrow_info['borrow_money'] - $borrow_info['has_borrow'];
        } else {
            $invest_info['investor_capital'] = $money;
        }
        $invest_info['is_auto'] = $_is_auto;
        $invest_info['add_time'] = time();
        // 还款详细公共信息
        $invest_detail = [];
        $save_detail = [];
        switch ($borrow_info['repayment_type']) {
            case 1: // 按天到期还款
                //利息
                $invest_info['investor_interest'] = ToolModel::getFloatValue($borrow_info['borrow_interest_rate'] / 365 * $invest_info['investor_capital'] * $borrow_info['borrow_duration'] / 100, 4);
                $invest_info['invest_fee'] = ToolModel::getFloatValue($fee_rate * $invest_info['investor_interest'], 4); // 修改投资人的天标利息管理费2014-12-3
                $invest_info_id = M('borrow_investor')->add($invest_info); // 返回插入id
              
                // 还款概要END
                $invest_detail['borrow_id'] = $borrow_id;
                $invest_detail['invest_id'] = $invest_info_id;
                $invest_detail['investor_uid'] = $uid;
                $invest_detail['borrow_uid'] = $borrow_info['borrow_uid'];
                $invest_detail['capital'] = $invest_info['investor_capital'];
                $invest_detail['interest'] = $invest_info['investor_interest'];
                $invest_detail['interest_fee'] = $invest_info['invest_fee'];
                $invest_detail['status'] = 0;
                $invest_detail['sort_order'] = 1;
                $invest_detail['total'] = 1;
                $save_detail[] = $invest_detail;
                break;
            case 2: // 每月还款
                $month_data = [];
                $month_data_detail = [];
                $month_data['type'] = "all";
                $month_data['money'] = $invest_info['investor_capital'];
                $month_data['year_apr'] = $borrow_info['borrow_interest_rate'];
                $month_data['duration'] = $borrow_info['borrow_duration'];
                $repay_detail = ToolModel::EqualMonth($month_data);
                
                $invest_info['investor_interest'] = ($repay_detail['repayment_money'] - $invest_info['investor_capital']);
                $invest_info['invest_fee'] = ToolModel::getFloatValue($fee_rate * $invest_info['investor_interest'], 4);
                $invest_info_id = M('borrow_investor')->add($invest_info);
                // 还款概要END
                
                $month_data_detail['money'] = $invest_info['investor_capital'];
                $month_data_detail['year_apr'] = $borrow_info['borrow_interest_rate'];
                $month_data_detail['duration'] = $borrow_info['borrow_duration'];
                $repay_list = ToolModel::EqualMonth($month_data_detail);
                $i = 1;
                foreach ($repay_list as $key => $v) {
                    $invest_detail['borrow_id'] = $borrow_id;
                    $invest_detail['invest_id'] = $invest_info_id;
                    $invest_detail['investor_uid'] = $uid;
                    $invest_detail['borrow_uid'] = $borrow_info['borrow_uid'];
                    $invest_detail['capital'] = $v['capital'];
                    $invest_detail['interest'] = $v['interest'];
                    $invest_detail['interest_fee'] = ToolModel::getFloatValue($fee_rate * $v['interest'], 4);
                    $invest_detail['status'] = 0;
                    $invest_detail['sort_order'] = $i;
                    $invest_detail['total'] = $borrow_info['borrow_duration'];
                    $i ++;
                    $save_detail[] = $invest_detail;
                }
                break;
            case 3: // 按季分期还款
                $month_data = [];
                $month_data_detail = [];
                $month_data['month_times'] = $borrow_info['borrow_duration'];
                $month_data['account'] = $invest_info['investor_capital'];
                $month_data['year_apr'] = $borrow_info['borrow_interest_rate'];
                $month_data['type'] = "all";
                $repay_detail = ToolModel::EqualSeason($month_data);
                
                $invest_info['investor_interest'] = ($repay_detail['repayment_money'] - $invest_info['investor_capital']);
                $invest_info['invest_fee'] = ToolModel::getFloatValue($fee_rate * $invest_info['investor_interest'], 4);
                $invest_info_id = M('borrow_investor')->add($invest_info);
                
                $month_data_detail['month_times'] = $borrow_info['borrow_duration'];
                $month_data_detail['account'] = $invest_info['investor_capital'];
                $month_data_detail['year_apr'] = $borrow_info['borrow_interest_rate'];
                $repay_list = ToolModel::EqualSeason($month_data_detail);
                $i = 1;
                foreach ($repay_list as $key => $v) {
                    $invest_detail['borrow_id'] = $borrow_id;
                    $invest_detail['invest_id'] = $invest_info_id;
                    $invest_detail['investor_uid'] = $uid;
                    $invest_detail['borrow_uid'] = $borrow_info['borrow_uid'];
                    $invest_detail['capital'] = $v['capital'];
                    $invest_detail['interest'] = $v['interest'];
                    $invest_detail['interest_fee'] = ToolModel::getFloatValue($fee_rate * $v['interest'], 4);
                    $invest_detail['status'] = 0;
                    $invest_detail['sort_order'] = $i;
                    $invest_detail['total'] = $borrow_info['borrow_duration'];
                    $i ++;
                    $save_detail[] = $invest_detail;
                }
                break;
            case 4: // 每月还息到期还本
                $month_data = [];
                $month_data_detail = [];
                $month_data['month_times'] = $borrow_info['borrow_duration'];
                $month_data['account'] = $invest_info['investor_capital'];
                $month_data['year_apr'] = $borrow_info['borrow_interest_rate'];
                $month_data['type'] = "all";
                $repay_detail = ToolModel::equalEndMonth($month_data);
                
                $invest_info['investor_interest'] = ($repay_detail['repayment_account'] - $invest_info['investor_capital']);
                $invest_info['invest_fee'] = ToolModel::getFloatValue($fee_rate * $invest_info['investor_interest'], 4);
                $invest_info_id = M('borrow_investor')->add($invest_info);
                // 还款概要END
                
                $month_data_detail['month_times'] = $borrow_info['borrow_duration'];
                $month_data_detail['account'] = $invest_info['investor_capital'];
                $month_data_detail['year_apr'] = $borrow_info['borrow_interest_rate'];
                $repay_list = ToolModel::EqualEndMonth($month_data_detail);
                $i = 1;
                foreach ($repay_list as $key => $v) {
                    $invest_detail['borrow_id'] = $borrow_id;
                    $invest_detail['invest_id'] = $invest_info_id;
                    $invest_detail['investor_uid'] = $uid;
                    $invest_detail['borrow_uid'] = $borrow_info['borrow_uid'];
                    $invest_detail['capital'] = $v['capital'];
                    $invest_detail['interest'] = $v['interest'];
                    $invest_detail['interest_fee'] = ToolModel::getFloatValue($fee_rate * $v['interest'], 4);
                    $invest_detail['status'] = 0;
                    $invest_detail['sort_order'] = $i;
                    $invest_detail['total'] = $borrow_info['borrow_duration'];
                    $i ++;
                    $save_detail[] = $invest_detail;
                }
                break;
            case 5: // 一次性还款
                $month_data = [];
                $month_data_detail = [];
                $month_data['month_times'] = $borrow_info['borrow_duration'];
                $month_data['account'] = $invest_info['investor_capital'];
                $month_data['year_apr'] = $borrow_info['borrow_interest_rate'];
                $month_data['type'] = "all";
                $repay_detail = ToolModel::EqualEndMonthOnly($month_data);
                
                $invest_info['investor_interest'] = ($repay_detail['repayment_account'] - $invest_info['investor_capital']);
                $invest_info['invest_fee'] = getFloatValue($fee_rate * $invest_info['investor_interest'], 4);
                $invest_info_id = M('borrow_investor')->add($invest_info);
                // 还款概要END
                
                $month_data_detail['month_times'] = $borrow_info['borrow_duration'];
                $month_data_detail['account'] = $invest_info['investor_capital'];
                $month_data_detail['year_apr'] = $borrow_info['borrow_interest_rate'];
                $month_data_detail['type'] = "all";
                $repay_list = ToolModel::equalEndMonthOnly($month_data_detail);
                
                $invest_detail['borrow_id'] = $borrow_id;
                $invest_detail['invest_id'] = $invest_info_id;
                $invest_detail['investor_uid'] = $uid;
                $invest_detail['borrow_uid'] = $borrow_info['borrow_uid'];
                $invest_detail['capital'] = $repay_list['capital'];
                $invest_detail['interest'] = $repay_list['interest'];
                $invest_detail['interest_fee'] = ToolModel::getFloatValue($fee_rate * $repay_list['interest'], 4);
                $invest_detail['status'] = 0;
                $invest_detail['sort_order'] = 1;
                $invest_detail['total'] = 1;
                $save_detail[] = $invest_detail;
                
                break;
        }
        $invest_detail_id = $save_borrow_id =false;
        // 保存还款详情
        
        $invest_detail_id = M('investor_detail')->addAll($save_detail); 
        $last_have_money = $this->table($this->tablePrefix."borrow_info")->getFieldById($borrow_id, "has_borrow");

        $save_data = [
            'has_borrow'=>floatval($last_have_money + $money),
            'borrow_times'=>'borrow_times+1',
        ];
        //@TODO 限制一下标的状态
        $save_borrow_id = M('borrow_info')->where(['id'=>$borrow_id])->save($save_data);
   
        // 更新投标进度
        if ($invest_detail_id && $invest_info_id && $save_borrow_id) {
            // 还款概要和详情投标进度都保存成功    
            $money_save_result = MoneyModel::memberMoneyLog($uid, 6, - $money, "对{$borrow_id}号标进行投标", $borrow_info['borrow_uid']);
            $today_reward = 0;
            $today_reward = explode("|", $golbal['today_reward']);
            if ($borrow_info['repayment_type'] == '1') { // 如果是天标，则执行1个月的续投奖励利率
                $reward_rate = floatval($today_reward[0]);
            } else {
                if ($borrow_info['borrow_duration'] == 1) {
                    $reward_rate = floatval($today_reward[0]);
                } else
                    if ($borrow_info['borrow_duration'] == 2) {
                        $reward_rate = floatval($today_reward[1]);
                    } else {
                        $reward_rate = floatval($today_reward[2]);
                    }
            }
            // 回款续投奖励规则
            if ($borrow_info['borrow_type'] != 3) {
                $insert_today_reward = false;
                // 如果是秒标(borrow_type==3)，则没有续投奖励这一说
                $where = [];
                $where['add_time'] = [
                    "lt",
                    time()
                ];
                $where['investor_uid'] = $uid;
                $borrow_invest_count = $this->where($where)->count('id'); // 检测是否投过标且大于一次
                if ($reward_rate > 0 && $member_info['back_money'] > 0 && $borrow_invest_count > 0) { // 首次投标不给续投奖励
                    if ($money > $member_info['back_money']) { 
                        $reward_money_s = 0;
                        // 如果投标金额大于回款资金池金额，有效续投奖励以回款金额资金池总额为标准，否则以投标金额为准
                        $reward_money_s = $member_info['back_money'];
                    } else {
                        $reward_money_s = $money;
                    }
                    $save_reward = [];
                    $save_reward['borrow_id'] = $borrow_id;
                    $save_reward['reward_uid'] = $uid;
                    $save_reward['invest_money'] = $reward_money_s; // 如果投标金额大于回款资金池金额，有效续投奖励以回款金额资金池总额为标准，否则以投标金额为准
                    $save_reward['reward_money'] = $reward_money_s * $reward_rate / 1000; // 续投奖励
                    $save_reward['reward_status'] = 0;
                    $save_reward['add_time'] = time();
                    $save_reward['add_ip'] = get_client_ip();
                    $insert_today_reward = M('today_reward')->add($save_reward);
                    if (!empty($insert_today_reward)) {
                        $result = true;
                        $money_model = new MoneyModel();
                        $result = $money_model->memberMoneylog($uid, 33, $save_reward['reward_money'], "续投有效金额({$reward_money_s})的奖励({$borrow_id}号标)预奖励", 0, "@平台自动结算@");
                    }
                } else {
                    trace('检测是否投过标且大于一次fail');
                    $result = false;
                }
            }
            // 回款续投奖励结束
            if (!empty($money_save_result) && !empty($result)) {
                // 满标
                if(intval($have_money + $money) == intval($borrow_info['borrow_money'])){
                    $borrow_info_model = new BorrowInfoModel();
                    $final_result = $borrow_info_model->borrowFull($borrow_id, $borrow_info['borrow_type']); // 满标，标记为还款中，更新相关数据
                }else{
                    $final_result = true;
                }
                if($final_result === true){
                    trace($money);
                    $this->commit();
                }else{
                    trace('满标fail');
                    return false;
                }   
            } else {
                trace('还款概要和详情投标进度fail');
                $final_result = false;
            }  
        } else {
            trace('更新投标进度fail');
            $this->rollback();
            return false;
        }
        return $final_result;
    }
    
    /**
     * check invest
     * 
     * @param number $borrow_id
     * @param number $money
     * @param array $data
     * @param number $uid
     * @param string $user_name
     * @return array|boolean
     * @author 周阳阳  2017年3月7日 上午 10:06:14
     */
    public function checkInvest($borrow_id = 0,$money = 0, $data = [], $uid = 0, $user_name = '')
    {
        $field = 'account_money,back_money,money_collect';
        $member_money = M("member_money")->field($field)->find($uid);
        $amoney = 0;
        $amoney = $member_money['account_money'] + $member_money['back_money'];
        if ($amoney < $money) {
            return "尊敬的{$user_name}，您准备投标{$money}元，但您的账户可用余额为{$amoney}元，请先去充值再投标.";
        }
        $field ='m.pay_pass,mm.account_money,mm.back_money,mm.money_collect';
        $member_info = (new MemberModel())->getMinfo($this->uid, $field);
        $pay_pass = $member_info['pay_pass'];
       
        if (empty($data['pay_pass']) && $pay_pass!= md5($data['pay_pass'])) {
            //return '支付密码错误，请重试';
        }
        $borrow_info = [];
        $borrow_info = $this->table($this->tablePrefix."borrow_info")->find($borrow_id);
        if($borrow_info['borrow_uid'] == $uid){
            return '不能投自己发标';
        }
        //@TODO 定向标检测
        if (!empty($borrow_info['password']) ) {         
            $password = md5($data['password']);
            if($password != $borrow_info['password']){
                return '定向标支付密码错误，请重试';
            }
        }
        // 待收金额限制
        if ($borrow_info['money_collect'] > 0) {
            if ($member_info['money_collect'] < $borrow_info['money_collect']) {
                return "此标设置有投标待收金额限制，您账户里必须有足够的待收才能投此标";
            }
        }
        // 投标总数检测
        $where = [
            'borrow_id' => $borrow_id,
            'investor_uid' => $uid
        ];
        $capital = M('borrow_investor')->where($where)->sum('investor_capital');
        if (($capital + $money) > $borrow_info['borrow_max'] && $borrow_info['borrow_max'] > 0) {
            $balance = 0;
            $balance = $borrow_info['borrow_max'] - $capital;
            return "您已投标{$capital}元，此投上限为{$borrow_info['borrow_max']}元，你最多只能再投{$balance}";
        }
        $need = $borrow_info['borrow_money'] - $borrow_info['has_borrow'];
        $caninvest = $need - $borrow_info['borrow_min'];
        if ($money > $caninvest && $need == 0) {
            return "尊敬的{$user_name}，此标已被抢投满了,下次投标手可一定要快呦！";
        }  
        if (($borrow_info['borrow_min'] - $money) > 0) {
            return "尊敬的{$user_name}，本标最低投标金额为{$binfo['borrow_min']}元，请重新输入投标金额";
        }
        if (($need - $money) < 0) {
            return "尊敬的{$user_name}，此标还差{$need}元满标,您最多只能再投{$need}元";
        }
        return $borrow_info;
    }
}