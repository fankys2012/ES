<?php
/**
 * Created by IntelliJ IDEA.
 * User: fankys
 * Date: 2019/10/25
 * Time: 14:05
 */
interface EmailInterface
{
    public function send();
}

//定义gmail邮件服务器
class Gmail implements EmailInterface
{
    public function send()
    {
        // TODO: Implement send() method.
    }
}

class SendEMail
{
    private $_emailSender = null;

    public function __construct()
    {
        $this->_emailSender = new Gmail();
    }

    public function sendEmail()
    {
        $this->_emailSender->send();
    }
}