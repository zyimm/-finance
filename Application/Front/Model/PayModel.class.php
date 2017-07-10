<?php
namespace Front\Model;
use Think\Model;
use Member\Model\MoneyModel;

class PayModel extends Model
{
    protected $tableName        =   'member_payonline';
    
    public $payDetail = [];
    
    public $global = [];
    
    public $returnUrl = '';
    
    public $noticeUrl = '';
    
    public static  $payStatus = [
        0=>'充值未完成',
        1=>'充值成功',
        2=>'签名不符',
        3=>'充值失败'
        
    ];
    
    public function __construct($data = [],$uid = 0)
    {
        parent::__construct();
        $this->global=GlobalModel::getGlobalSetting();
        $this->getPayDetail($data,$uid);
        $this->noticeUrl = 'http://'.$_SERVER['HTTP_HOST'].'/Front/Pay/payNotice';
        $this->returnUrl= 'http://'.$_SERVER['HTTP_HOST'].'/Front/Pay/payReturn';
    }
    
    public function getPayDetail ($data = [],$uid = 0 )
    {
        
        $this->payDetail['money'] = ToolModel::getFloatValue($data['money'], 2);
        $this->payDetail['fee'] = 0;
        $this->payDetail['add_time'] = time();
        $this->payDetail['add_ip'] = get_client_ip();
        $this->payDetail['status'] = 0;
        $this->payDetail['uid'] = $uid;
        $this->payDetail['bank'] = strtoupper($data['bank_code']);
    }
    /**
     * 
     * @param string $type
     */
    public function  getPayConfig($type = '')
    {
        if(!empty($type)){
            $type = explode('::',$type);
            $type = array_pop($type);
            $type = strtolower($type);
            if($type == 'offline'){
                $config = ConfigModel::read("pay_offline");
                $type = 'BANK';
            }else{
                $config = ConfigModel::read("pay_config"); 
            }
            if(!empty($config[$type])){
                return $config[$type];
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    public function baoFoo()
    {
        $config = [];
        $config = $this->getPayConfig(__METHOD__);
        $sub_mitdata = [];
        $sub_mitdata['MemberID'] = $config['MemberID'];
        // 商户号
        $sub_mitdata['TerminalID'] = $config['TerminalID'];
        // '18161';//终端号
        $sub_mitdata['InterfaceVersion'] = '4.0';
        // 接口版本号
        $sub_mitdata['KeyType'] = 1;
        // 接口版本号
        $sub_mitdata['PayID'] = '';
        $sub_mitdata['TradeDate'] = date("Ymdhis");
        // 交易时间
        $sub_mitdata['TransID'] = date("YmdHis") . mt_rand(1000, 9999);
        // 流水号
        $sub_mitdata['OrderMoney'] = number_format($this->payDetail['money'], 2, ".", "") * 100;
        $sub_mitdata['ProductName'] = urlencode($this->global['web_name'] . "帐户充值");
        $sub_mitdata['Amount'] = "1";
        $sub_mitdata['Username'] = "";
        $sub_mitdata['AdditionalInfo'] = "";
        $sub_mitdata['PageUrl'] = $this->returnUrl.'?payid=baofoo';
        $sub_mitdata['ReturnUrl'] = $this->noticeUrl.'?payid=baofoo';
        $sub_mitdata['NoticeType'] = "1";
        $sub_mitdata['Signature'] = $this->getSign("baofoo", $sub_mitdata);
        unset($this->payDetail['bank']);
        $this->payDetail['fee'] = ToolModel::getfloatvalue($config['feerate'] * $this->payDetail['money'] / 100, 2);
        $this->payDetail['nid'] = $this->createId("baofoo", $sub_mitdata['TransID']);
        $this->payDetail['way'] = "baofoo";

        $this->create($sub_mitdata, "https://gw.baofoo.com/payindex", $this->payDetail);
    }
    
    public function goPay()
    {
        $sub_mitdata = array();
        $config = array();
        $config = $this->getPayConfig(__ACTION__);
        $submit_data['charset'] = 2;
        $submit_data['language'] = 1;
        $submit_data['version'] = "2.1";
        $submit_data['tranCode'] = '8888';
        $submit_data['feeAmt'] = ToolModel::getFloatValue($config['feerate'], 2);
        $submit_data['currencyType'] = 156;
        $submit_data['merOrderNum'] = "goPay" . time() . rand(10000, 99999);
        $submit_data['tranDateTime'] = date("YmdHis", time());
        $submit_data['tranIP'] = get_client_ip();
        $submit_data['goodsName'] = $this->global['web_name'] . "帐户充值";
        $submit_data['frontMerUrl'] = $this->returnUrl . "?payid=gfb";
        $submit_data['backgroundMerUrl'] = $this->noticeUrl . "?payid=gfb";
        $submit_data['merchantID'] = $this->payConfig['guofubao']['merchantID'];
        // 商户ID
        $submit_data['virCardNoIn'] = $this->payConfig['guofubao']['virCardNoIn'];
        // 国付宝帐户
        $submit_data['tranAmt'] = $this->payDetail['money'];
        if ($this->payDetail['bank'] != 'GUOFUBAO') {
            $submit_data['bankCode'] = $this->payDetail['bank'];
        }
        // 银行直联必须
        $submit_data['userType'] = 1;
        // 银行直联,1个人,2企业
        $submit_data['signType'] = 1;
        $submit_data['signValue'] = $this->getSign('goPay', $submit_data);
        unset($this->payDetail['bank']);
        $this->payDetail['fee'] = ToolModel::getFloatValue($config['feerate'] * $this->payDetail['money'] / 100, 2);
        $this->payDetail['nid'] = $this->createId('goPay', $submit_data['tranDateTime']);
        $this->payDetail['way'] = 'gopay';
        $this->create($submit_data,'https://gateway.gopay.com.cn/Trans/WebClientAction',$this->payDetail);
    }
     
    public function ecpss()
    {
        $submit_data = [];
        $config = [];
        $config = $this->getPayConfig(__METHOD__);
        $submit_data['MerNo'] = $config['MerNo'];
        $submit_data['BillNo'] = date("YmdHis") . mt_rand(100000, 999999);
        $submit_data['MD5key'] = $config['MD5key'];
        $submit_data['Amount'] = number_format($config['money'], 2, ".", "");
        $submit_data['ReturnURL'] = $this->returnUrl . "?payid=ecpss";
        $submit_data['AdviceURL'] = $this->noticeUrl . "?payid=ecpss";
        $submit_data['OrderTime'] = date("YmdHis");
        // 签名
        $signEcpass = "MerNo=".trim($submit_data['MerNo'])."&"."BillNo=".trim($submit_data['BillNo'])."&"."Amount=".trim($submit_data['Amount']) . "&" . "OrderTime=" . trim($submit_data['OrderTime']) . "&" . "ReturnURL=" . trim($submit_data['ReturnURL']) . "&" . "AdviceURL=" . trim($submit_data['AdviceURL']) . "&" . trim($submit_data['MD5key']);
        $signEcpass = strtoupper(md5($signEcpass));
        $submit_data['SignInfo'] = $signEcpass;
        $submit_data['Remark'] = '';
        $submit_data['products'] = $this->glo['web_name'] . "帐户充值";

        unset($this->payDetail['bank']);
        $this->payDetail['fee'] = ToolModel::getfloatvalue($config['feerate'] * $this->payDetail['money'] / 100, 2);
        $this->payDetail['nid'] = $this->createId("ecpss", $submit_data['BillNo']);
        $this->payDetail['way'] = "ecpss";

        $this->create($submit_data, "https://gwapi.yemadai.com/pay/sslpayment",$this->payDetail);
    }
     
    public function fuYou()
    {
        $config = [];
        $config = $this->getPayConfig(__METHOD__);
        $submit_data = [];
        // 支付校验
        $submit_data['order_id'] = date('ymdhis', time()) . mt_rand(1000, 9999); // 商户订单号
        $submit_data['order_amt'] = (int)($this->payDetail['money'] * 100); // 交易金额
        $submit_data['order_pay_type'] = 'B2C'; // 支付类型
        $submit_data['iss_ins_cd'] = '0000000000'; // 银行
        $submit_data['page_notify_url'] = $this->noticeUrl . "?payid=fuyou"; // 页面跳转URL
        $submit_data['back_notify_url'] = ''; // 后台通知URL
        $submit_data['order_valid_time'] = "10m"; // 超时时间
        $submit_data['mchnt_cd'] = $config['mchnt_cd']; // 商户代码
        $submit_data['mchnt_key'] = $config['mchnt_key']; // 商户代码
        $submit_data['goods_name'] = "在线支付"; // 商品名称
        $submit_data['goods_display_url'] = 'http://'.$_SERVER['HTTP_HOST']; // 商品展示网址
        $submit_data['rem'] = ""; // 备注
        $submit_data['ver'] = "1.0.1"; // 版本号
        // 拼接数据
        $_data = "";
        $_data .= $submit_data['mchnt_cd'] . "|";
        $_data .= $submit_data['order_id'] . "|";
        $_data .= $submit_data['order_amt'] . "|";
        $_data .= $submit_data['order_pay_type'] . "|";
        $_data .= $submit_data['page_notify_url'] . "|";
        $_data .= $submit_data['back_notify_url'] . "|";
        $_data .= $submit_data['order_valid_time'] . "|";
        $_data .= $submit_data['iss_ins_cd'] . "|";
        $_data .= $submit_data['goods_name'] . "|";
        $_data .= $submit_data['goods_display_url'] . "|";
        $_data .= $submit_data['rem'] . "|";
        $_data .= $submit_data['ver'] . "|";
        $_data .= $submit_data['mchnt_key'];
        $submit_data['md5'] = md5($_data); // 签名数据
        $this->paydetail['fee'] = ToolModel::getfloatvalue($config['feerate'] * $this->paydetail['money'] /100, 2);
        $this->paydetail['nid'] = $this->createId('fuyou', $submit_data['order_id']);
        $this->paydetail['way'] = "fuyou";
        $this->create($submit_data, "https://pay.fuiou.com/smpGate.do",$this->paydetail);
    }
    
    public function offLine($pay_data = array())
    {
        $this->getPaydetail();
        $this->payDetail['money'] = floatval($pay_data['money']);
       
        $config = $this->getPayConfig(__ACTION__);
        $bank_id = intval($pay_data['bank']) - 1;
        $this->payDetail['fee'] = 0;
        $this->payDetail['nid'] = 'offline';
        $this->payDetail['way'] = 'off';
        $this->payDetail['tran_id'] = SafeModel::text($pay_data['tran_id']);
        $this->payDetail['off_bank'] = $config['BANK'][$bank_id]['bank'] .'开户名：' . $config['BANK'][$bank_id]['payee'];
        $this->payDetail['off_way'] = SafeModel::text(I('post.off_way'));
        $newid = $this->add($this->payDetail);
        if ($newid) {
            redirect('/member/charge',3,'线下充值提交成功，请等待管理员审核' );
        } else {
            E("线下充值提交失败，请重试");
        }
    }
    
    public function createId()
    {
        return md5("XXXXX@@#\$%" . $type . $static);
    }

    public function getSign($type = '', $data = array())
    {
        $md5str = "";
        $config = $this->getPayConfig($type);
        switch ($type) {
            case "gopay":
                $sign_array = array(
                    "version",
                    "tranCode",
                    "merchantID",
                    "merOrderNum",
                    "tranAmt",
                    "feeAmt",
                    "tranDateTime",
                    "frontMerUrl",
                    "backgroundMerUrl",
                    "orderId",
                    "gopayOutOrderId",
                    "tranIP",
                    "respCode",
                    "gopayServerTime"
                );
                foreach ($sign_array as $v) {
                    if (! isset($data[$v])) {
                        $md5str .= "{$v}=[]";
                    } else {
                        $md5str .= "{$v}=[{$data[$v]}]";
                    }
                }  
                $md5str .= "VerficationCode=[" .$config['VerficationCode'] . "]";
                $md5str = md5($md5str);
                return $md5str;
                break;
            case "baofoo":
                $signarray = array(
                    "MemberID",
                    "PayID",
                    "TradeDate",
                    "TransID",
                    "OrderMoney",
                    "PageUrl",
                    "ReturnUrl",
                    "NoticeType"
                );
                foreach ($signarray as $v) {
                    $md5str .= $data[$v] . '|';
                }
                $md5str .= $config['pkey'];
                $md5str = md5($md5str);
                return $md5str;
                break;
            
            case "ecpss":
                $signarray = array(
                    'MerNo',
                    'BillNo',
                    'Amount',
                    'ReturnURL'
                );
                // 校验源字符串
                foreach ($signarray as $v) {
                    if (! isset($data[$v])) {
                        $md5str .= "";
                    } else {
                        $md5str .= $data[$v];
                    }
                }
                $md5str .= $config['MD5key'];
                // MD5密钥
                $md5str = strtoupper(md5($md5str));
                return $md5str;
                break;
            
            case "fuyou":
                // 富有签名
                unset($data['md5']);
                $data['mchnt_key'] = $config['mchnt_key'];
                $str = $data['mchnt_cd'] . '|' . $data['order_id'] . '|' . $data['order_date'] . '|' . $data['order_amt'] . '|' . $data['order_st'] . '|' . $data['order_pay_code'] . '|' . $data['order_pay_error'] . '|' . $data['resv1'] . '|' . $data['fy_ssn'] . '|' . $data['mchnt_key'];
                // MD5方式签名
                $hmac = md5($str);
                return $hmac;
                break;
        }
    }
    /**
     * post
     * @param array $data
     * @param string $submit_url
     */
    public function create ($data = array(), $submit_url = '',$insert_data)
    {return ;
        $this->table($this->tablePrefix."member_payonline")->add($insert_data);
        
        $input_str = "";
        foreach ($data as $key => $v) {
            $input_str .= "<input type='hidden' id='{$key}' name='{$key}' value='{$v}' />";
        }
        $form = "<form action='{$submit_url}' name='pay' id='pay' method='POST' >";
        $form .= $input_str;
        $form .= '</form>';
        $html = '
		<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
  
		<html xmlns="http://www.w3.org/1999/xhtml"><head>
  
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  
        <title>请不要关闭页面,支付跳转中.....</title>
  
        </head><body>';
        $html .= $form;
        $html .= '<script type="text/javascript">document.getElementById("pay").submit();</script>';
        $html .= ' </body></html>';
        echo ($html);exit;
    }
    
    public function payReturn($data = array())
    {
        $payid = strtolower($data['payid']);
        switch ($payid) {
            case 'gfb':
                $recode = $data['respCode'];
                if ($recode == "0000") {
                    // 充值成功
                    $signGet = $this->getSign('goPay',$data);
                    $nid = $this->create('goPay', $data['tranDateTime']);
                    if ($data['signValue'] == $signGet) {
                        // 充值完成
                        return true;
                    } else {
                        // 签名不付
                        return '签名不付';
                    }
                } else {
                    return '充值失败';
                }
                break;
           
            case 'baofoo':
                $recode = $data['Result'];
                if ($recode == "1") {
                    $signGet = $this->getSign("baofoo_return", $data);
                    $nid = $this->createnid("baofoo", $data['TransID']);
                    if ($data['Md5Sign'] == $signGet) {
                       return true;
                    } else {
                        return "签名不付";
                    }
                } else {
                    return $data['resultDesc'];
                }
                break;
           
            // 汇潮
            case "ecpss":
                $signGet = $this->getSign("ecpss_return", $data);
                if (strtoupper($data['SignInfo']) == $signGet) {
                    $recode = $data['Succeed'];
                    if ($recode == "88") {
                        return true;
                    } else {
                        return"交易不成功";
                    }
                } else {
                    return "充值失败(签名不对)";
                }
                break;
           
            case "fuyou":
                $returnCode = $data["returnCode"];
                $message = $data["message"];
                $mac = $this->getSign("fuyou",$data);
                if ($mac == $data["hmac"]) {
                    if ($returnCode == 00) {
                        $this->success("充值完成", __APP__ . "/member/");
                    } else {
                        return $message;
                    }
                } else {
                       return '签名不付';            
                }
                break;
        }
    }
    
    public function payNotice($data = array())
    {
        $payid = strtolower($data['payid']);
        switch ($payid) {
            case 'gopay':
                $recode = $data['respCode'];
                if ($recode == "0000") {
                    // 充值成功
                    $signGet = $this->getSign('gopay', $data);
                    $nid = $this->createId('gfb', $data['tranDateTime']);
                    $money = $data['tranAmt'];
                    if ($data['signValue'] == $signGet) {
                        // 充值完成
                        $done = $this->payDone(1, $nid, $data['orderId']);
                    } else {
                        // 签名不付
                        $done = $this->payDone(2, $nid, $data['orderId']);
                    }
                } else {
                    // 充值失败
                    $done = $this->payDone(3, $nid);
                }
                if ($done === true) {
                    echo "ResCode=0000|JumpURL=" . $this->member_url;
                } else {
                    echo "ResCode=9999|JumpURL=" . $this->member_url;
                }
                break;
            
            case "baofoo":
                $recode = $data['Result'];
                if ($recode == "1") {
                    $signGet = $this->getSign("baofoo_return", $data);
                    $nid = $this->createnid("baofoo", $data['TransID']);
        
                    if ($data['Md5Sign'] == $signGet) {
                        $done = $this->payDone(1, $nid, $data['TransID']);
                    } else {
                        $done = $this->payDone(2, $nid, $data['TransID']);
                    }
                } else {
                    $done = $this->payDone(3, $nid);
                }
                if ($done === true) {
                    echo "OK";
                } else {
                    echo "Fail";
                }
                break;
          
            case "ecpss":
                $signGet = $this->getSign("ecpss_return", $data);
                if (strtoupper($data['SignInfo']) == $signGet) {
                    $recode = $data['Succeed'];
                    if ($recode == "88") {
                        $nid = $this->createId("ecpss", $data['BillNo']);
                        $done = $this->payDone(1, $nid, $data['BillNo']);
                    } else {
                        $done = $this->payDone(2, $nid, $data['BillNo']);
                    }
                } else {
                    $done = $this->payDone(3, $nid);
                }
                break;
        
            case "fuyou":
                $order_pay_code = $data["order_pay_code"];
                $message = $data["order_pay_error"];
                $sign = $this->getSign("fuyou", $data);
                $nid = $this->createnid('fuyou', $data['order_id']);
                if ($sign == $data["md5"]) {
                    if ($order_pay_code == '0000') {
                        $done = $this->payDone(1, $nid, $data['order_id']);
                        if($done){
                            $this->success("充值完成", __APP__ . "/member/");
                        }else{
                            // 充值失败
                            $this->error('充值失败',  __APP__ . "/member/");
                        }
                    } else {
        
                        $done = $this->payDone(2, $nid, $data['order_id']);
                        $this->error($message,  __APP__ . "/member/");
                    }
                } else {
                    // 签名不付
                    $this->error("签名不付", __APP__ . "/member/");
                }
        
                break;
        }
    }
    
    public function payDone($status, $nid, $oid)
    {
        $done = false;

        $where = array(
            'uid'=>(int)$vo['uid'],
            'nid'=>(int)$nid
        );
        switch ($status) {
            case 1:
                $updata = array();
                $updata['status'] = $status;
                $updata['tran_id'] = SafeModel::text($oid);
                $vo = $this->field('uid,money,fee,status')->where("nid='{$nid}'")->find();
                if ($vo['status'] != 0 || ! is_array($vo)) {
                    return false;
                }
                
                $xid = $this->where($where)->save($updata);
                $tmoney = floatval($vo['money'] - $vo['fee']);
                if ($xid) {
                    $newid = MoneyModel::memberMoneyLog($vo['uid'], 3, $tmoney,"充值订单号:". $oid,0,'@网站管理员@');
                }
                // 更新成功才充值,避免重复充值
                $vx = $this->table($this->tablePrefix."members")->field("user_phone,user_name")->find($vo['uid']);
                MessageModel::smsTip("payonline", $vx['user_phone'],
                    array(
                        "#USERANEM#",
                        "#MONEY#"
                    ), array(
                        $vx['user_name'],
                        $vo['money']
                    ));
                break;
            case 2:
                $updata['status'] = $status;
                $updata['tran_id'] = SafeModel::text($oid);
                $xid = $this->where($where)->save($updata);
                break;
            case 3:
                $updata['status'] = $status;
                $xid = $this->where($where)->save($updata);;
                break;
        }
        if ($status > 0) {
            if ($xid) {
                $done = true;
            }
        }
        return $done;
    }
}