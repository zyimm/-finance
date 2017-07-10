<?php
/**
 * 工具处理
 * 
 * @Author ZYIMM <799783009@qq.com>
 * @version 1.0
 * Create By 2017年3月13日 下午9:37:19  版权所有@copyright 2013~2017 www.zyimm.com
 */
namespace Front\Model;


class ToolModel
{

    public static function getLeftTime($time_end = 0, $type = 1)
    {
        if ($type == 1) {
            $time_end = strtotime(date("Y-m-d", $time_end) . " 23:59:59");
            $time_now = strtotime(date("Y-m-d", time()) . " 23:59:59");
            $left = ceil(($time_end - $time_now) / 3600 / 24);
        } else {
            $left_arr = self::timediff(time(), $time_end);
            $left = $left_arr['day'] . "天 " . $left_arr['hour'] . "小时 " . $left_arr['min'] . "分钟 " . $left_arr['sec'] . "秒";
        }
        return $left;
    }

    /**
     * 获取两个时间点的区间
     *
     * @param number $begin_time  开始时间
     * @param number $end_time      结束时间
     * @return number[]
     * @author 周阳阳 2017年3月14日 下午12:38:11
     */
    public static function timediff($begin_time = 0, $end_time = 0)
    {
        if ($begin_time < $end_time) {
            $starttime = $begin_time;
            $endtime = $end_time;
        } else {
            $starttime = $end_time;
            $endtime = $begin_time;
        }
        $timediff = $endtime - $starttime;
        $days = intval($timediff / 86400);
        $remain = $timediff % 86400;
        $hours = intval($remain / 3600);
        $remain = $remain % 3600;
        $mins = intval($remain / 60);
        $secs = $remain % 60;
        $res = array(
            "day" => $days,
            "hour" => $hours,
            "min" => $mins,
            "sec" => $secs
        );
        return $res;
    }

    public static function getFloatValue($val = 0, $len = 2)
    {
        return number_format($val, $len, '.', '');
    }

    /**
     * 自动转换字符集 支持数组转换
     *
     * @param string $contents            
     * @param string $from            
     * @param string $to            
     * @return string|array
     * @author 周阳阳 2017年3月14日 下午12:30:39
     */
    public static function autoCharset($contents = '', $from = 'gbk', $to = 'utf-8')
    {
        $from = strtoupper($from) == 'UTF8' ? 'utf-8' : $from;
        $to = strtoupper($to) == 'UTF8' ? 'utf-8' : $to;
        if (($to == 'utf-8' && is_utf8($contents)) || strtoupper($from) === strtoupper($to) || empty($contents) || (is_scalar($contents) && ! is_string($contents))) {
            // 如果编码相同或者非字符串标量则不转换
            return $contents;
        }
        if (is_string($contents)) {
            if (function_exists('mb_convert_encoding')) {
                return mb_convert_encoding($contents, $to, $from);
            } elseif (function_exists('iconv')) {
                return iconv($from, $to, $contents);
            } else {
                return $contents;
            }
        } elseif (is_array($contents)) {
            foreach ($contents as $key => $val) {
                $_key = auto_charset($key, $from, $to);
                $contents[$_key] = self::autoCharset($val, $from, $to);
                if ($key != $_key)
                    unset($contents[$key]);
            }
            return $contents;
        } else {
            return $contents;
        }
    }

    /**
     * 等额本息法 贷款本金×月利率×（1+月利率）还款月数/[（1+月利率）还款月数-1]
     * a*[i*(1+i)^n]/[(1+I)^n-1]
     * （a×i－b）×（1＋i）
     * money,year_apr,duration,borrow_time(用来算还款时间的),type(==all时，返回还款概要)
     *
     * @param array $data            
     * @return string|number[]
     * @author 周阳阳 2017年3月27日 下午3:28:57
     */
    public static function equalMonth($data = array())
    {
        if (isset($data['money']) && $data['money'] > 0) {
            $account = $data['money'];
        } else {
            return "";
        }
        
        if (isset($data['year_apr']) && $data['year_apr'] > 0) {
            $year_apr = $data['year_apr'];
        } else {
            return "";
        }
        
        if (isset($data['duration']) && $data['duration'] > 0) {
            $duration = $data['duration'];
        }
        if (isset($data['borrow_time']) && $data['borrow_time'] > 0) {
            $borrow_time = $data['borrow_time'];
        } else {
            $borrow_time = time();
        }
        $month_apr = $year_apr / (12 * 100);
        $_li = pow((1 + $month_apr), $duration);
        $repayment = round($account * ($month_apr * $_li) / ($_li - 1), 4);
        $_result = array();
        if (isset($data['type']) && $data['type'] == "all") {
            $_result['repayment_money'] = $repayment * $duration;
            $_result['monthly_repayment'] = $repayment;
            $_result['month_apr'] = round($month_apr * 100, 4);
        } else {
            for ($i = 0; $i < $duration; $i ++) {
                if ($i == 0) {
                    $interest = round($account * $month_apr, 4);
                } else {
                    $_lu = pow((1 + $month_apr), $i);
                    $interest = round(($account * $month_apr - $repayment) * $_lu + $repayment, 4);
                }
                $_result[$i]['repayment_money'] = self::getFloatValue($repayment, 4);
                $_result[$i]['repayment_time'] = self::getTimes(array(
                    "time" => $borrow_time,
                    "num" => $i + 1
                ));
                $_result[$i]['interest'] = self::getFloatValue($interest, 4);
                $_result[$i]['capital'] = self::getFloatValue($repayment - $interest, 4);
            }
        }
        return $_result;
    }

    /**
     * 到期还本，按月付息
     * 
     * @param array $data
     * @return array
     * @author 周阳阳 2017年5月25日 下午3:50:17
     */
    public static function equalEndMonth ($data = array()){
        //借款的月数
        if (isset($data['month_times']) && $data['month_times']>0){
            $month_times = $data['month_times'];
        }
        
        //借款的总金额
        if (isset($data['account']) && $data['account']>0){
            $account = $data['account'];
        }else{
            return "";
        }
        
        //借款的年利率
        if (isset($data['year_apr']) && $data['year_apr']>0){
            $year_apr = $data['year_apr'];
        }else{
            return "";
        }
        
        
        //借款的时间
        if (isset($data['borrow_time']) && $data['borrow_time']>0){
            $borrow_time = $data['borrow_time'];
        }else{
            $borrow_time = time();
        }
        
        //月利率
        $month_apr = $year_apr/(12*100);
        $_yes_account = 0 ;
        $repayment_account = 0;//总还款额
        $_all_interest=0;
        
        $interest = round($account*$month_apr,4);//利息等于应还金额乘月利率
        for($i=0;$i<$month_times;$i++){
            $capital = 0;
            if ($i+1 == $month_times){
                $capital = $account;//本金只在最后一个月还，本金等于借款金额除季度
            } 
            $_result[$i]['repayment_account'] = $interest+$capital;
            $_result[$i]['repayment_time'] = self::getTimes(array("time"=>$borrow_time,"num"=>$i+1));
            $_result[$i]['interest'] = $interest;
            $_result[$i]['capital'] = $capital;
            $_all_interest += $interest;
        }
        if (isset($data['type']) && $data['type']=="all"){
            $_resul['repayment_account'] = $account + $interest*$month_times;
            $_resul['monthly_repayment'] = $interest;
            $_resul['month_apr'] = round($month_apr*100,4);
            $_resul['interest'] = $_all_interest;
            return $_resul;
        }else{
            return $_result;
        }
    }
    
    /**
     * 到期还本，按月付息
     *
     * @param array $data            
     * @return string|unknown
     * @author 周阳阳 2017年3月27日 下午3:37:27
     */
    public static function equalEndMonthOnly($data = [])
    {
        // 借款的月数
        if (isset($data['month_times']) && $data['month_times'] > 0) {
            $month_times = $data['month_times'];
        }
        // 借款的总金额
        if (isset($data['account']) && $data['account'] > 0) {
            $account = $data['account'];
        } else {
            return "";
        }
        // 借款的年利率
        if (isset($data['year_apr']) && $data['year_apr'] > 0) {
            $year_apr = $data['year_apr'];
        } else {
            return "";
        }
        
        // 借款的时间
        if (isset($data['borrow_time']) && $data['borrow_time'] > 0) {
            $borrow_time = $data['borrow_time'];
        } else {
            $borrow_time = time();
        }
        // 月利率
        $month_apr = $year_apr / (12 * 100);
        $interest = self::getFloatValue($account * $month_apr * $month_times, 4); // 利息等于应还金额*月利率*借款月数
        if (isset($data['type']) && $data['type'] == "all") {
            $_resul['repayment_account'] = $account + $interest;
            $_resul['monthly_repayment'] = $interest;
            $_resul['month_apr'] = round($month_apr * 100, 4);
            $_resul['interest'] = $interest;
            $_resul['capital'] = $account;
            return $_resul;
        }
    }

    /**
     * 获得时间天数
     *
     * @param array $data            
     * @return string
     * @author 周阳阳 2017年3月27日 下午3:27:48
     */
    public static function getTimes($data = array())
    {
        $_result = 0;
        if (isset($data['time']) && $data['time'] != "") {
            $time = $data['time']; // 时间
        } elseif (isset($data['date']) && $data['date'] != "") {
            $time = strtotime($data['date']); // 日期
        } else {
            $time = time(); // 现在时间
        }
        if (isset($data['type']) && $data['type'] != "") {
            $type = $data['type']; // 时间转换类型，有day week month year
        } else {
            $type = "month";
        }
        if (isset($data['num']) && $data['num'] != "") {
            $num = $data['num'];
        } else {
            $num = 1;
        }
        if ($type == "month") {
            $month = date("m", $time);
            $year = date("Y", $time);
            $_result = strtotime("$num month", $time);
            $_month = (int) date("m", $_result);
            if ($month + $num > 12) {
                $_num = $month + $num - 12;
                $year = $year + 1;
            } else {
                $_num = $month + $num;
            }
            if ($_num != $_month) {
                $_result = strtotime("-1 day", strtotime("{$year}-{$_month}-01"));
            }
        } else {
            $_result = strtotime("$num $type", $time);
        }
        if (isset($data['format']) && $data['format'] != "") {
            return date($data['format'], $_result);
        } else {
            return $_result;
        }
    }

    public static function getExpiredDays($deadline = 0)
    {
        if ($deadline < 1000) {
            return "数据有误";
        }
        return ceil((time() - $deadline) / 3600 / 24);
    }

    public static function getExpiredMoney($expired, $capital, $interest)
    {
        $global = GlobalModel::getGlobalSetting();
        $expired_fee = explode("|", $global['fee_expired']);
        
        if ($expired <= $expired_fee[0]) {
            return 0;
        }
        return self::getFloatValue(($capital + $interest) * $expired * $expired_fee[1] / 1000, 2);
    }

    public static function getExpiredCallFee($expired, $capital, $interest)
    {
        $global = GlobalModel::getGlobalSetting();
        $call_fee = explode("|", $global['fee_call']);
        
        if ($expired <= $call_fee[0]) {
            return 0;
        }
        return self::getFloatValue(($capital + $interest) * $expired * $call_fee[1] / 1000, 2);
    }

    public static function isMobile($mobile = '')
    {
        if (! is_numeric($mobile)) {
            return false;
        }
        return preg_match('#^13[\d]{9}$|^14[5,7]{1}\d{8}$|^15[^4]{1}\d{8}$|^17[0,6,7,8]{1}\d{8}$|^18[\d]{9}$#', $mobile) ? true : false;
    }

    public static function isEmail($email)
    {
        if (empty($email)) {
            return false;
        }
        return preg_match('#^13[\d]{9}$|^14[5,7]{1}\d{8}$|^15[^4]{1}\d{8}$|^17[0,6,7,8]{1}\d{8}$|^18[\d]{9}$#', $email) ? true : false;
    }

    /**
     * 字段文字内容隐藏处理方法 
     *
     * @param string $card_num            
     * @param number $type            
     * @param string $default            
     * @return string
     * @author 周阳阳 2017年5月5日 下午3:10:07
     */
    public static function hidecard($card_num = '', $type = 1, $default = "")
    {
        if (empty($card_num)) {
            return $default;
        }
        
        if ($type == 1) {
            $card_num = mb_substr($card_num, 0, 2, 'utf-8') . str_repeat("*", 12) . mb_substr($card_num, - 3, 3, 'utf-8'); // 身份证
        } elseif ($type == 2) {
            $card_num = mb_substr($card_num, 0, 3, 'utf-8') . str_repeat("*", 5) . mb_substr($card_num, - 3, 3, 'utf-8'); // 手机号
        } elseif ($type == 3) {
            $card_num = str_repeat("*", strlen($card_num) - 4) . mb_substr($card_num, - 4, 4, 'utf-8'); // 银行卡
        } elseif ($type == 4) {
            $card_num = mb_substr($card_num, 0, 1, 'utf-8') . str_repeat("*", strlen($card_num) - 3) . mb_substr($card_num, - 1, 1, 'utf-8'); // 用户名
        } elseif ($type == 5) {
            $card_num = mb_substr($card_num, 0, 1, 'utf-8') . str_repeat("*", 3) . mb_substr($card_num, - 1, 1, 'utf-8'); // 新用户名
        }
        return $card_num;
    }

    public static function borrowCalc($data = [])
    {
        if(empty($data)){
            return false;
        }
        
        $amount = round(floatval($data['amount']), 4); // 借款金额
        $date_limit = intval($data['date_limit']); // 借款期限
        $rate = floatval($data['rate']); // 借款利率
        $reward_rate = floatval($data['reward_rate']); // 借款奖励
        $borrow_manage = floatval($data['borrow_manage']); // 借款管理费
        
        $rate_type = (intval($data['rate_type']) == 2) ? 2 : 1; // 投资利率：1：年利率；2：日利率
        $date_type = (intval($data['date_type']) == 2) ? 2 : 1; // 投资类型：1：月；2：日
        
        $repayment_type = intval($data['repayment_type']); // 借款类型
        if ($repayment_type != 1 && $rate_type == 2){
            $rate = $rate * 365; // 利率
        }
            
        if ($repayment_type == 1 && $rate_type == 1){
            $rate = $rate / 365;
        }
            
        $repay_detail =[];
        
        $repay_detail['risk_reserve'] = 0; // 风险准备金
        $repay_detail['borrow_manage'] = round($amount * $borrow_manage * $date_limit / 100, 2); // 借款管理费
        $repay_detail['reward_money'] = round($amount * $reward_rate / 100, 2); // 奖励
        $repay_detail['borrow_money'] = $amount - $repay_detail['risk_reserve'] - $repay_detail['borrow_manage'] - $repay_detail['reward_money'];
        switch ($repayment_type) {
            case '1': // 按天到期还款
                $repay_detail['repayment_money'] = round($amount * ($rate * $date_limit + 100) / 100, 2);
                $repay_detail['interest'] = $repay_detail['repayment_money'] - $amount;
                $repay_detail['day_apr'] = round(($repay_detail['repayment_money'] - $repay_detail['borrow_money']) * 100 / ($repay_detail['borrow_money'] * $date_limit), 2);
                $repay_detail['year_apr'] = round($repay_detail['day_apr'] * 365, 2);
                $repay_detail['month_apr'] = round($repay_detail['day_apr'] * 365 / 12, 2);
                break;
            case '4': // 到期还本息
                $repay_detail['repayment_money'] = round($amount * ($date_limit * $rate / 12 + 100) / 100, 2);
                $repay_detail['interest'] = $repay_detail['repayment_money'] - $amount;
                $repay_detail['month_apr'] = round(($repay_detail['repayment_money'] - $repay_detail['borrow_money']) * 100 / ($repay_detail['borrow_money'] * $date_limit), 2);
                $repay_detail['year_apr'] = round($repay_detail['month_apr'] * 12, 2);
                $repay_detail['day_apr'] = round($repay_detail['month_apr'] * 12 / 365, 2);
                break;
            case '3': // 每月还息到期还本
                $repay_detail['repayment_money'] = round($amount * ($rate * $date_limit / 12 + 100) / 100, 2);
                $repay_detail['interest'] = $repay_detail['repayment_money'] - $amount;
                $repay_detail['month_apr'] = round(($repay_detail['repayment_money'] - $repay_detail['borrow_money']) * 100 / ($repay_detail['borrow_money'] * $date_limit), 2);
                $repay_detail['year_apr'] = round($repay_detail['month_apr'] * 12, 2);
                $repay_detail['day_apr'] = round($repay_detail['month_apr'] * 12 / 365, 2);
                
                $interest = round($amount * $rate / 12 / 100, 2); // 利息等于应还金额乘月利率
                for ($i = 0; $i < $date_limit; $i ++) {
                    if ($i + 1 == $date_limit){
                        $capital = $amount; // 本金只在最后一个月还，本金等于借款金额除季度
                    }else{
                        $capital = 0;
                    }
  
                    $_result[$i]['repayment_money'] = $interest + $capital;
                    $_result[$i]['interest'] = $interest;
                    $_result[$i]['capital'] = $capital;
                }
                break;
            case '5': // 先息后本
                $repay_detail['interest'] = round($amount * $rate * $date_limit / 12 / 100, 2);
                $repay_detail['borrow_money'] -= $repay_detail['interest'];
                $repay_detail['repayment_money'] = $amount;
                
                $repay_detail['month_apr'] = round(($repay_detail['repayment_money'] - $repay_detail['borrow_money']) * 100 / ($repay_detail['borrow_money'] * $date_limit), 2);
                $repay_detail['year_apr'] = round($repay_detail['month_apr'] * 12, 2);
                $repay_detail['day_apr'] = round($repay_detail['month_apr'] * 12 / 365, 2);
                break;
            case '2': // 按月分期还款
            default:
                $month_apr = $rate / (12 * 100);
                $_li = pow((1 + $month_apr), $date_limit);
                $repayment = ($_li != 1) ? round($amount * ($month_apr * $_li) / ($_li - 1), 2) : round($amount / $date_limit, 2);
                $repay_detail['repayment_money'] = $repayment * $date_limit;
                $repay_detail['interest'] = $repay_detail['repayment_money'] - $amount;
                
                for ($i = 0; $i < $date_limit; $i ++) {
                    if ($i == 0) {
                        $interest = round($amount * $month_apr, 2);
                    } else {
                        $_lu = pow((1 + $month_apr), $i);
                        $interest = round(($amount * $month_apr - $repayment) * $_lu + $repayment, 2);
                    }
                    $_result[$i]['repayment_money'] = self::getFloatValue($repayment, 2);
                    $_result[$i]['interest'] = self::getFloatValue($interest, 2);
                    $_result[$i]['capital'] = self::getFloatValue($repayment - $interest, 2);
                }
                
                $month_apr2 = ($repay_detail['repayment_money'] - $repay_detail['borrow_money']) / ($repay_detail['borrow_money'] * $date_limit);
                $rekursiv = 0.001;
                for ($i = 0; $i < 100; $i ++) {
                    $_li2 = pow((1 + $month_apr2), $date_limit);
                    $repay = $repay_detail['borrow_money'] * $date_limit * ($month_apr2 * $_li2) / ($_li2 - 1);
                    if ($repay < $repay_detail['repayment_money'] * 0.99999) {
                        $month_apr2 += $rekursiv;
                    } elseif ($repay > $repay_detail['repayment_money'] * 1.00001) {
                        $month_apr2 -= $rekursiv * 0.9;
                        $rekursiv *= 0.1;
                    } else
                        break;
                }
                $repay_detail['month_apr'] = round($month_apr2 * 100, 2);
                
                $repay_detail['year_apr'] = round($repay_detail['month_apr'] * 12, 2);
                $repay_detail['day_apr'] = round($repay_detail['month_apr'] * 12 / 365, 2);
                break;
        }
        $repay_detail['total_interest'] = round($repay_detail['repayment_money'] - $repay_detail['borrow_money'], 2);
        
        return [
            'repayment_type' => $repayment_type,
            'month' => $date_limit,
            'repay_list' => $_result,
            'repay_detail' => $repay_detail
        
        ];
    }

    public static function investCalc($data = [])
    {
        if(empty($data)){
            return false;
        }
        $amount = round(floatval($data['amount']), 2); // 投资金额
        $date_limit = intval($data['date_limit']); // 投资期限
        $rate = floatval($data['rate']); // 投资利率
        $reward_rate = floatval($data['reward_rate']); // 借款奖励
        $invest_manage = floatval($data['invest_manage']); // 利息管理费
        
        $rate_type = (intval($data['rate_type']) == 2) ? 2 : 1; // 投资利率：1：年利率；2：日利率
        $date_type = (intval($data['date_type']) == 2) ? 2 : 1; // 投资类型：1：月；2：日
        
        $repayment_type = intval($data['repayment_type']);
        if ($repayment_type != 1 && $rate_type == 2){
            $rate = $rate * 365;
        }
            
        if ($repayment_type == 1 && $rate_type == 1){
            $rate = $rate / 365;
        }
           
        $repay_detail = [];
        $repay_detail['reward_money'] = round($amount * $reward_rate / 100, 2);
        $repay_detail['invest_money'] = $amount - $repay_detail['reward_money'];
        switch ($repayment_type) {
            case '1': // 按天到期还款
                $repay_detail['repayment_money'] = round($amount * ($rate * $date_limit * (100 - $invest_manage) / 100 + 100) / 100, 2);
                $repay_detail['interest'] = $repay_detail['repayment_money'] - $amount;
                $repay_detail['day_apr'] = round(($repay_detail['repayment_money'] - $repay_detail['invest_money']) * 100 / ($repay_detail['invest_money'] * $date_limit), 2);
                $repay_detail['year_apr'] = round($repay_detail['day_apr'] * 365, 2);
                $repay_detail['month_apr'] = round($repay_detail['day_apr'] * 365 / 12, 2);
                break;
            case '4': // 到期还本息
                $repay_detail['repayment_money'] = round(($amount + $amount * ($date_limit * $rate / 12 / 100) * (100 - $invest_manage) / 100), 2);
                $repay_detail['interest'] = $repay_detail['repayment_money'] - $amount;
                $repay_detail['month_apr'] = round(($repay_detail['repayment_money'] - $repay_detail['invest_money']) * 100 / ($repay_detail['invest_money'] * $date_limit), 2);
                $repay_detail['year_apr'] = round($repay_detail['month_apr'] * 12, 2);
                $repay_detail['day_apr'] = round($repay_detail['month_apr'] * 12 / 365, 2);
                break;
            case '3': // 每月还息到期还本
                $repay_detail['repayment_money'] = round($amount * ($rate * $date_limit * (100 - $invest_manage) / 100 / 12 + 100) / 100, 2);
                $repay_detail['interest'] = $repay_detail['repayment_money'] - $amount;
                $repay_detail['month_apr'] = round(($repay_detail['repayment_money'] - $repay_detail['invest_money']) * 100 / ($repay_detail['invest_money'] * $date_limit), 2);
                $repay_detail['year_apr'] = round($repay_detail['month_apr'] * 12, 2);
                $repay_detail['day_apr'] = round($repay_detail['month_apr'] * 12 / 365, 2);
                
                $interest = round($amount * $rate * (100 - $invest_manage) / 100 / 12 / 100, 2); // 利息等于应还金额乘月利率
                $repay = $repay_detail['repayment_money'];
                for ($i = 0; $i < $date_limit; $i ++) {
                    if ($i + 1 == $date_limit) {
                        $capital = $amount; // 本金只在最后一个月还，本金等于借款金额除季度
                        $repay = $interest + $capital;
                    } else {
                        $capital = 0;
                        $repay = $repay - $interest;
                    }
                    
                    $_result[$i]['repayment_money'] = $interest + $capital;
                    $_result[$i]['interest'] = $interest;
                    $_result[$i]['capital'] = $capital;
                    $_result[$i]['last_money'] = $repay;
                }
                break;
            case '5': // 先息后本
                $repay_detail['interest'] = round(($amount * ($rate / 12 / 100) * $date_limit) * ((100 - $invest_manage) / 100), 2);
                $repay_detail['invest_money'] -= $repay_detail['interest'];
                $repay_detail['repayment_money'] = $amount;
                
                $repay_detail['month_apr'] = round(($repay_detail['repayment_money'] - $repay_detail['invest_money']) * 100 / ($repay_detail['invest_money'] * $date_limit), 2);
                $repay_detail['year_apr'] = round($repay_detail['month_apr'] * 12, 2);
                $repay_detail['day_apr'] = round($repay_detail['month_apr'] * 12 / 365, 2);
                break;
            case '2': // 按月分期还款
            default:
                $month_apr = $rate / (12 * 100);
                $_li = pow((1 + $month_apr), $date_limit);
                $repayment = ($_li != 1) ? round($amount * ($month_apr * $_li) / ($_li - 1), 2) : round($amount / $date_limit, 2);
                $repay_detail['repayment_money'] = round(($repayment * $date_limit - $amount) * (100 - $invest_manage) / 100 + $amount, 2);
                $repay_detail['interest'] = $repay_detail['repayment_money'] - $amount;
                
                $repay = $repay_detail['repayment_money'];
                for ($i = 0; $i < $date_limit; $i ++) {
                    if ($i == 0) {
                        $interest = round($amount * $month_apr, 2);
                    } else {
                        $_lu = pow((1 + $month_apr), $i);
                        $interest = round(($amount * $month_apr - $repayment) * $_lu + $repayment, 2);
                    }
                    $fee = $interest * $invest_manage / 100;
                    
                    $_result[$i]['repayment_money'] = self::getFloatValue($repayment - $fee, 2);
                    $_result[$i]['interest'] = self::getFloatValue($interest - $fee, 2);
                    $_result[$i]['capital'] = self::getFloatValue($repayment - $interest, 2);
                    
                    if ($i + 1 != $date_limit){
                        $repay = $repay - $_result[$i]['repayment_money'];
                    }else{
                        $repay = 0;
                    }
                  
                       
                    $_result[$i]['last_money'] = $repay;
                }
                
                $month_apr2 = ($repay_detail['repayment_money'] - $repay_detail['invest_money']) / ($repay_detail['invest_money'] * $date_limit);
                $rekursiv = 0.001;
                for ($i = 0; $i < 100; $i ++) {
                    $_li2 = pow((1 + $month_apr2), $date_limit);
                    $repay = $repay_detail['invest_money'] * $date_limit * ($month_apr2 * $_li2) / ($_li2 - 1);
                    if ($repay < $repay_detail['repayment_money'] * 0.99999) {
                        $month_apr2 += $rekursiv;
                    } elseif ($repay > $repay_detail['repayment_money'] * 1.00001) {
                        $month_apr2 -= $rekursiv * 0.9;
                        $rekursiv *= 0.1;
                    } else{
                        break;
                    }
                        
                }
                $repay_detail['month_apr'] = round($month_apr2 * 100, 2);
                
                $repay_detail['year_apr'] = round($repay_detail['month_apr'] * 12, 2);
                $repay_detail['day_apr'] = round($repay_detail['month_apr'] * 12 / 365, 2);
                break;
        }
        $repay_detail['total_interest'] = round($repay_detail['repayment_money'] - $repay_detail['invest_money'], 2);
        
        return [
            'repayment_type'=>$repayment_type,
            'month'=>$date_limit,
            'repay_list'=>$_result,
            'repay_detail'=>$repay_detail 
        ];
       
    }
    
    public static function   isBirth($uid = 0)
    {
        $pre = C('DB_PREFIX');
        $id = M("member_info i")->field("i.id_card")->join("{$pre}member_status s ON s.uid=i.uid",'left')->where("i.uid = {$uid} AND s.id_status=1 ")->find();
        if(!empty($id['idcard'])){
            return false;
        }		
        $bir = substr($id['idcard'], 10, 4);
        $now = date("md");
        if( $bir==$now ){
            return true;
        }else{
            return false;
        }
        
    }
}