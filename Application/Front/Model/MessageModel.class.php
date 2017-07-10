<?php
/**
 * 信息处理
 * @Author ZYIMM <799783009@qq.com>
 * @version 1.0
 * Create By 2017年3月13日 下午9:23:29  版权所有@copyright 2013~2017 www.zyimm.com
 */
namespace Front\Model;

use Think\Model;

class MessageModel extends Model
{

    /**
     * 会员站内信,单/群发
     *
     * @param number $type            
     * @param number|array $uid            
     * @param string $info            
     * @param string $autoid            
     */
    public static function MTip($type = 0, $uid = 0, $info = "", $autoid = "")
    {
        $datag = GlobalModel::getGlobalSetting();
        $per = C('DB_PREFIX');
        if (is_int($uid)) {
            $map['id'] = $uid;
        } else {
            $map['id'] = array(
                'in',
                $uid
            );
        }
        $memail = M('members')->field('id')
            ->where($map)
            ->select();
        switch ($type) {
            case 1: // 修改密码
                $subject = "您刚刚在" . $datag['web_name'] . "修改了登陆密码";
                $body = "您刚刚在" . $datag['web_name'] . "修改了登陆密码,如不是自己操作,请尽快联系客服";
                $innerbody = "您刚刚修改了登陆密码,如不是自己操作,请尽快联系客服";
                foreach ($memail as $v) {
                    self::addInsideMsg($v['id'], "您刚刚修改了登陆密码", $innerbody);
                }
                break;
            
            case 2: // 修改银行帐号
                $subject = "您刚刚在" . $datag['web_name'] . "修改了提现的银行帐户";
                $body = "您刚刚在" . $datag['web_name'] . "修改了提现的银行帐户,如不是自己操作,请尽快联系客服";
                $innerbody = "您刚刚修改了提现的银行帐户,如不是自己操作,请尽快联系客服";
                /* 邮件 */
                foreach ($memail as $v) {
                    self::addInsideMsg($v['id'], "您刚刚修改了提现的银行帐户", $innerbody);
                }
                break;
            case 3: // 资金提现
                $subject = "您刚刚在" . $datag['web_name'] . "申请了提现操作";
                $body = "您刚刚在" . $datag['web_name'] . "申请了提现操作,如不是自己操作,请尽快联系客服";
                $innerbody = "您刚刚申请了提现操作,如不是自己操作,请尽快联系客服";
                /* sms */
                foreach ($memail as $v) {
                    self::addInsideMsg($v['id'], "您刚刚申请了提现操作", $innerbody);
                    sendSms($v['user_phone'], $body);
                }
                break;
            case 4: // 借款标初审未通过
                $subject = "您在" . $datag['web_name'] . "发布的借款标刚刚初审未通过";
                $body = "您在" . $datag['web_name'] . "发布的第{$info}号借款标刚刚初审未通过";
                $innerbody = "您发布的第{$info}号借款标刚刚初审未通过";
                foreach ($memail as $v) {
                    self::addInsideMsg($v['id'], "刚刚您的借款标初审未通过", $innerbody);
                }
                break;
            
            case 5: // 借款标初审通过
                $subject = "您在" . $datag['web_name'] . "发布的借款标刚刚初审通过";
                $body = "您在" . $datag['web_name'] . "发布的第{$info}号借款标刚刚初审通过";
                $innerbody = "您发布的第{$info}号借款标刚刚初审通过";
                foreach ($memail as $v) {
                    self::addInsideMsg($v['id'], "刚刚您的借款标初审通过", $innerbody);
                }
                break;
            
            case 6: // 借款标复审通过
                $subject = "您在" . $datag['web_name'] . "发布的借款标刚刚复审通过";
                $body = "您在" . $datag['web_name'] . "发布的第{$info}号借款标刚刚复审通过";
                $innerbody = "您发布的第{$info}号借款标刚刚复审通过";
                /* 邮件 */
                foreach ($memail as $v) {
                    self::addInsideMsg($v['id'], "刚刚您的借款标复审通过", $innerbody);
                }
                break;
            case 7: // 借款标满标
                $subject = "您在" . $datag['web_name'] . "的借款标已满标";
                $body = "刚刚您在" . $datag['web_name'] . "的第{$info}号借款标已满标";
                $innerbody = "刚刚您的借款标已满标";
                /* 邮件 */
                foreach ($memail as $v) {
                    self::addInsideMsg($v['id'], "刚刚您的第{$info}号借款标已满标", $innerbody);
                }
                break;
            case 8: // 借款标流标
                $subject = "您在" . $datag['web_name'] . "的借款标已流标";
                $body = "您在" . $datag['web_name'] . "发布的第{$info}号借款标已流标，请登陆查看";
                $innerbody = "您的第{$info}号借款标已流标";
                /* 邮件 */
                foreach ($memail as $v) {
                    self::addInsideMsg($v['id'], "刚刚您的借款标已流标", $innerbody);
                }
                break;
            
            case 9: // 借款标复审未通过
                $subject = "您在" . $datag['web_name'] . "的发布的借款标刚刚复审未通过";
                $body = "您在" . $datag['web_name'] . "的发布的第{$info}号借款标复审未通过";
                $innerbody = "您发布的第{$info}号借款标复审未通过";
                /* 邮件 */
                foreach ($memail as $v) {
                    self::addInsideMsg($v['id'], "刚刚您的借款标复审未通过", $innerbody);
                }
                break;
            
            case 10: // 借出成功
                $subject = "您在" . $datag['web_name'] . "投标的借款成功了";
                $body = "您在" . $datag['web_name'] . "投标的第{$info}号借款借出成功了";
                $innerbody = "您在" . $datag['web_name'] . "投标的第{$info}号借款借出成功了";
                /* 邮件 */
                foreach ($memail as $v) {
                    self::addInsideMsg($v['id'], "您投标的第{$info}号借款借款成功", $innerbody);
                }
                break;
            case 11: // 借出流标
                $subject = "您在" . $datag['web_name'] . "投标的借款流标了";
                $body = "您在" . $datag['web_name'] . "投标的第{$info}号借款流标了，相关资金已经返回帐户，请查看";
                $innerbody = "您投标的借款流标了";
                /* 邮件 */
                foreach ($memail as $v) {
                    self::addInsideMsg($v['id'], "您投标的第{$info}号借款流标了，相关资金已经返回帐户", $innerbody);
                }
                break;
            case 12: // 收到还款
                $subject = "您在" . $datag['web_name'] . "借出的借款收到了新的还款";
                $body = "您在" . $datag['web_name'] . "借出的第{$info}号借款收到了新的还款，请登陆查看";
                $innerbody = "您借出的借款收到了新的还款";
                /* 邮件 */
                foreach ($memail as $v) {
                    self::addInsideMsg($v['id'], "您借出的第{$info}号借款收到了新的还款", $innerbody);
                }
                break;
            case 13: // 网站代为偿还
                $subject = "您在" . $datag['web_name'] . "借出的借款逾期网站代还了本金";
                $body = "您在" . $datag['web_name'] . "借出的第{$info}号借款逾期网站代还了本金，请登陆查看";
                $innerbody = "您借出的第{$info}号借款逾期网站代还了本金";
                /* 邮件 */
                foreach ($memail as $v) {
                    self::addInsideMsg($v['id'], "您借出的第{$info}号借款逾期网站代还了本金", $innerbody);
                }
                break;
            case 14: // 借入人还款成功
                /* 邮件 */
                $subject = "您在" . $datag['web_name'] . "的借入的还款进行了还款操作";
                $body = "您对在" . $datag['web_name'] . "借入的第{$info}号借款进行了还款，请登陆查看";
                $innerbody = "您对借入的第{$info}号借款进行了还款";
                /* 邮件 */
                foreach ($memail as $v) {
                    self::addInsideMsg($v['id'], "您对借入标还款进行了还款操作", $innerbody);
                }
                break;
            case 15: // 自动投标借出完成
                /* 邮件 */
                $subject = "您在" . $datag['web_name'] . "设置的第{$autoid}号自动投标按设置投了新标";
                $body = "您在" . $datag['web_name'] . "设置的第{$autoid}号自动投标按设置对第{$info}号借款进行了投标";
                $innerbody = "您设置的第{$autoid}号自动投标对第{$info}号借款进行了投标";
                /* 邮件 */
                foreach ($memail as $v) {
                    self::addInsideMsg($v['id'], "您设置的第{$autoid}号自动投标按设置投了新标", $innerbody);
                }
                break;
            case 16: // 你修改了密保
                /* 邮件 */
                $subject = "您在" . $datag['web_name'] . "修改了密保";
                $body = "您在" . $datag['web_name'] . "修改了密保，如果不是本人操作请联系客服";
                /* 邮件 */
                foreach ($memail as $v) {
                    self::addInsideMsg($v['id'], $subject, $body);
                }
                break;
            case 17: // 你修改认证信息
                $subject = "您在" . $datag['web_name'] . "你修改认证信息";
                $body = $info;
                foreach ($memail as $v) {
                    self::addInsideMsg($v['id'], $subject, $body);
                }
                break;
            case 18: // 你修改认证信息
                $subject = "您在" . $datag['web_name'] . "实名认证信息";
                $body = $info;
                foreach ($memail as $v) {
                    self::addInsideMsg($v['id'], $subject, $body);
                }
                break;
            case 19: // 积分信息
                $subject = "您在" . $datag['web_name'] . "积分的变动信息";
                $body = $info;
                foreach ($memail as $v) {
                    self::addInsideMsg($v['id'], $subject, $body);
                }
                break;
            case 20: // 充值信息
                $subject = "您在" . $datag['web_name'] . "充值的变动信息";
                $body = $info;
                foreach ($memail as $v) {
                    self::addInsideMsg($v['id'], $subject, $body);
                }
                break;
            case 21: // 修改支付密码
                $subject = "您刚刚在" . $datag['web_name'] . "修改了支付密码";
                $body = "您刚刚在" . $datag['web_name'] . "修改了登陆密码,如不是自己操作,请尽快联系客服";
                $innerbody = "您刚刚修改了支付密码,如不是自己操作,请尽快联系客服";
                foreach ($memail as $v) {
                    self::addInsideMsg($v['id'], "您刚刚修改了支付密码", $innerbody);
                }
                break;
            case 22: // 获取邮箱验证码
                $subject = "您刚刚在" . $datag['web_name'] . "获取邮箱验证码";
                $body = "您刚刚在" . $datag['web_name'] . "获取邮箱验证码,如不是自己操作,请尽快联系客服";
                $innerbody = "您刚刚修改了支付密码,如不是自己操作,请尽快联系客服";
                foreach ($memail as $v) {
                    self::addInsideMsg($v['id'], "您刚刚修改了支付密码", $innerbody);
                }
                break;
        }
    }

    /**
     * 添加站内信息
     *
     * @param number $uid            
     * @param string $title            
     * @param string $msg            
     * @return boolean
     */
    public static function addInsideMsg($uid = 0, $title = '', $msg = '')
    {
        if (empty($uid)){
            return false;
        } 
        $data = array(
            'uid' => $uid,
            'title' => $title,
            'msg' => $msg,
            'send_time' => time(),
            'status' => 0
        );
        if (!M('insite_msg')->add($data)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 短信提醒
     * 
     * @param number $type            
     * @param string $mob            
     * @param array $from            
     * @param array $to            
     */
    public static function smsTip($type = '', $mob = '', $from = array(), $to = array())
    {
        if (empty($mob)){
            return false;
        }
        $smsTxt = ConfigModel::read('smstxt');
        if (!empty($smsTxt[$type])) {
            $body = str_replace($from, $to, $smsTxt[$type]['content']);
            $send = self::sendSms($mob, $body);
        } else {
            return false;
            
        }
    }


    /**
     *  手机短信接口
     * @param number $mob 手机号
     * @param string $content
     * @return boolean
     */
    public static  function sendSms($mob = 0, $content ='')
    {
        $msgConf = ConfigModel::read('message');
        $type = (int)$msgConf['sms']['type']; // 1 吉信通 2 漫道3 亿美4建周
        if ($type == 1) {
            $uid = $msgConf['sms']['user1']; // 分配给你的账号
            $pwd = $msgConf['sms']['pass1']; // 密码
            $mob = $mob; // 发送号码用逗号分隔
            // 如果是Linux系统，则执行linux短息接口
            if (PATH_SEPARATOR == ':') {
                $url = "http://service.winic.org:8009/sys_port/gateway/?id=%s&pwd=%s&to=%s&content=%s&time=";
                $id = urlencode($uid);
                $pwd = urlencode($pwd);
                $to = urlencode($mob);
                $content = iconv("UTF-8", "GB2312", $content);
                $rurl = sprintf($url, $id, $pwd, $to, $content);
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_HEADER, 0);
                curl_setopt($ch, CURLOPT_URL, $rurl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $result = curl_exec($ch);
                curl_close($ch);
                $status = substr($result, 0, 3);
                if ($status === "000") {
                    return true;
                } else {
                    return false;
                }
            } else {
                $content = urlencode(ToolModel::autoCharset($content, "utf-8", 'gbk')); // 短信内容
                $sendurl = "http://service.winic.org:8009/sys_port/gateway/?";
                $sdata = "id=" . $uid . "&pwd=" . $pwd . "&to=" . $mob . "&content=" . $content . "&time=";
                
                $xhr = new \COM("MSXML2.XMLHTTP");
                $xhr->open("POST", $sendurl, false);
                $xhr->setRequestHeader("Content-type:", "text/xml;charset=GB2312");
                $xhr->setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                $xhr->send($sdata);
                $data = explode("/", $xhr->responseText);
                if ($data[0] == "000")
                    return true;
                else
                    return false;
            }
            // 漫道
        } elseif ($type == 2) {
            // 如果您的系统是utf-8,请转成GB2312 后，再提交、
            $flag = 0;
            // 要post的数据
            $argv = array(
                'sn' => $msgConf['sms']['user2'], // //替换成您自己的序列号
                'pwd' => strtoupper(md5($msgConf['sms']['user2'] . '' . $msgConf['sms']['pass2'])), // 此处密码需要加密 加密方式为 md5(sn+password) 32位大写
                'mobile' => $mob, // 手机号 多个用英文的逗号隔开 post理论没有长度限制.推荐群发一次小于等于10000个手机号
                'content' => $content,
                'ext' => '',
                'stime' => '', // 定时时间 格式为2011-6-29 11:09:21
                'msgfmt' => '',
                'rrid' => ''
            );
            $params = '';
            // 构造要post的字符串
            foreach ($argv as $key => $value) {
                if ($flag != 0) {
                    $params .= "&";
                    $flag = 1;
                }
                $params .= $key . "=";
                $params .= urlencode($value);
                $flag = 1;
            }
            $length = strlen($params);
            // 创建socket连接
            $fp = fsockopen("sdk.entinfo.cn", 8061, $errno, $errstr, 10) or exit($errstr . "--->" . $errno);
            // 构造post请求的头
            $header = "POST /webservice.asmx/mdsmssend HTTP/1.1\r\n";
            $header .= "Host:sdk.entinfo.cn\r\n";
            $header .= "Content-Type: application/x-www-form-urlencoded\r\n";
            $header .= "Content-Length: " . $length . "\r\n";
            $header .= "Connection: Close\r\n\r\n";
            // 添加post的字符串
            $header .= $params . "\r\n";
            // 发送post的数据
            fputs($fp, $header);
            $inheader = 1;
            while (! feof($fp)) {
                $line = fgets($fp, 1024); // 去除请求包的头只显示页面的返回数据
                if ($inheader && ($line == "\n" || $line == "\r\n")) {
                    $inheader = 0;
                }
                if ($inheader == 0) {
                    // echo $line;
                }
            }
            $line = str_replace("<string xmlns=\"http://tempuri.org/\">", "", $line);
            $line = str_replace("</string>", "", $line);
            $result = explode("-", $line);
            // echo $line."-------------";
            if (count($result) > 1) {
                return false;
            } else {
                return true;
            }
            // 亿美
        } elseif ($type == 3) {
            $uid = $msgConf['sms']['user3']; // 分配给你的账号
            $pwd = $msgConf['sms']['pass3']; // 密码
            $mob = $mob; // 发送号码用逗号分隔
            $content = urlencode(ToolModel::autoCharset($content, "utf-8", 'gbk')); // 短信内容
            $sendurl = "http://sdk229ws.eucp.b2m.cn:8080/sdkproxy/sendsms.action?";
            $sendurl .= 'cdkey=' . $serialNumber . '&password=' . $pwd . '&phone=' . $mob . '&message=' . $content . '&addserial=';
            $d = @file_get_contents($sendurl, false);
            preg_match_all('/<response>(.*)<\/response>/isU', $d, $arr);
            foreach ($arr[1] as $k => $v) {
                preg_match_all('#<error>(.*)</error>#isU', $v, $ar[$k]);
                $data[] = $ar[$k][1];
            }
            if ($data[0][0] == "0") {
                return true;
            } else {
                return false;
            }
            // 建周
        } elseif ($type == 4) {
            //TODO 这个接口要改
            import('@.Sms.nusoap');
            $client = new nusoap_client('http://www.jianzhou.sh.cn/JianzhouSMSWSServer/services/BusinessService?wsdl', true);
            $client->soap_defencoding = 'utf-8';
            $client->decode_utf8 = false;
            $client->xml_encoding = 'utf-8';
            $params = array(
                'account' => $msgConf['sms']['user4'],
                'password' => $msgConf['sms']['pass4'],
                'destmobile' => $mob, // 手机号
                // 内容
                'msgText' => $content
            );
            $result = $client->call('sendBatchMessage', $params, 'http://www.jianzhou.sh.cn/JianzhouSMSWSServer/services/BusinessService');
            if ($result > 0) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * 邮箱接口 封装发送
     * @param string $address     收件人地址
     * @param string $title  标题
     * @param string $message 内容
     */
    public function SendMail($address, $title, $message)
    {
        $messConf = FS("Webconfig/Messageconf");
        $port = 25;
        $smtpServer = $messConf['stmp']['server'];
        $smtpUser = $messConf['stmp']['user'];
        $smtpPwd = $messConf['stmp']['pass'];
        $mail = new PHPMailer();
        // 设置PHPMailer使用SMTP服务器发送Email
        $mail->IsSMTP();
        // 设置邮件的字符编码，若不指定，则为'UTF-8'
        $mail->CharSet = 'UTF-8';
        // 添加收件人地址，可以多次使用来添加多个收件人
        if (is_array($address)) {
            foreach ($address as $v) {
                $mail->AddAddress($v);
            }
        } else {
            $mail->AddAddress($address);
        }
        // 设置邮件正文
        // $mail->Body=$message;
        $mail->MsgHTML($message);
        // 设置邮件头的From字段。
        $mail->From = $smtpUser;
        // 设置发件人名字
        $mail->FromName = $smtpUser;
        // 设置邮件标题
        $mail->Subject = $title;
        // 设置SMTP服务器。
        $mail->Host = $smtpServer;
        // 设置为“需要验证”
        $mail->SMTPAuth = true;
        // 设置用户名和密码。
        $mail->Username = $smtpUser;
        $mail->Password = $smtpPwd;
        // 发送邮件。
        return ($mail->Send());
    }
}