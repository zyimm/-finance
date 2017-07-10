<?php
namespace Common\Service;

use Psr\Log\LoggerInterface;
use Common\Service\Mailer\SMTP;
use Common\Service\Mailer\Message;


/**
 * @example
 ```
  $ok = (new Mailer())
 ->setServer('smtp.server.com', 25)
 ->setAuth('tom@server.com', 'password')
 ->setFrom('Tom', 'tom@server.com')
 ->setFakeFrom('Obama', 'fake@address.com') // if u want, a fake name, a fake email
 ->addTo('Jerry', 'jerry@server.com')
 ->setSubject('Hello')
 ->setBody('Hi, Jerry! I <strong>love</strong> you.')
 ->addAttachment('host', '/etc/hosts')
 ->send();
 ```
 */
class Mailer
{
    
    /**
     * SMTP Class
     *
     * @var SMTP
     */
    protected $smtp;
    
    /**
     * Mail Message
     *
     * @var Message
     */
    protected $message;
    
    /**
     * construct function
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger = null)
    {
        $this->smtp = new SMTP($logger);
        $this->message = new Message();
    }
    
    
    /**
     * set server and port
     * 
     * @param string $host
     * @param number $port
     * @param unknown $secure
     * @return \app\common\service\Mailer
     */
    public function setServer($host ='', $port=25, $secure = null)
    {
        $this->smtp->setServer($host, $port, $secure);
        return $this;
    }
    
    /**
     * auth with server
     *
     * @param string $username
     * @param string $password
     * @return $this
     */
    public function setAuth($username='', $password='')
    {
        $this->smtp->setAuth($username, $password);
        return $this;
    }
    
    /**
     * auth oauthbearer with server
     *
     * @param string $accessToken
     * @return $this
     */
    public function setOAuth($accessToken)
    {
        $this->smtp->setOAuth($accessToken);
        return $this;
    }
    
    /**
     * set mail from
     *
     * @param string $name
     * @param string $email
     * @return $this
     */
    public function setFrom($name, $email)
    {
        $this->message->setFrom($name, $email);
        return $this;
    }
    
    /**
     * set fake mail from
     *
     * @param string $name
     * @param string $email
     * @return $this
     */
    public function setFakeFrom($name, $email)
    {
        $this->message->setFakeFrom($name, $email);
        return $this;
    }
    
    /**
     * set mail receiver
     *
     * @param string $name
     * @param string $email
     * @return $this
     */
    public function setTo($name, $email)
    {
        $this->message->addTo($name, $email);
        return $this;
    }
    
    /**
     * add mail receiver
     *
     * @param string $name
     * @param string $email
     * @return $this
     */
    public function addTo($name, $email)
    {
        $this->message->addTo($name, $email);
        return $this;
    }
    
    /**
     * add cc mail receiver
     *
     * @param string $name
     * @param string $email
     * @return $this
     */
    public function addCc($name, $email)
    {
        $this->message->addCc($name, $email);
        return $this;
    }
    
    /**
     * add bcc mail receiver
     *
     * @param string $name
     * @param string $email
     * @return $this
     */
    public function addBcc($name, $email)
    {
        $this->message->addBcc($name, $email);
        return $this;
    }
    
    /**
     * set mail subject
     *
     * @param string $subject
     * @return $this
     */
    public function setSubject($subject)
    {
        $this->message->setSubject($subject);
        return $this;
    }
    
    /**
     * set mail body
     *
     * @param string $body
     * @return $this
     */
    public function setBody($body)
    {
        $this->message->setBody($body);
        return $this;
    }
    
    /**
     * set mail attachment
     * @param string $name
     * @param string $path
     * @return \app\common\service\Mailer
     */
    public function setAttachment($name ='', $path='')
    {
        $this->message->addAttachment($name, $path);
        return $this;
    }
    
    /**
     * add mail attachment
     * @param unknown $name
     * @param unknown $path
     * @return \app\common\service\Mailer
     */
    public function addAttachment($name = '', $path ='')
    {
        $this->message->addAttachment($name, $path);
        return $this;
    }
    
    /**
     * Send the message...
     *
     * @return boolean
     */
    public function send()
    {
        return $this->smtp->send($this->message);
    }
}

