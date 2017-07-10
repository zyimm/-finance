<?php
namespace Common\Service\Mailer\Exceptions;

class SMTPException extends \Exception
{
    public function __construct($message)
    {
        parent::__construct($message);
    }

}
