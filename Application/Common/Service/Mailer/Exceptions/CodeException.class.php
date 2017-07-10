<?php

namespace Common\Service\Mailer\Exceptions;


class CodeException extends SMTPException
{
    public function __construct($expected, $received, $serverMessage = null)
    {
        $message = "Unexpected return code - Expected: {$expected}, Got: {$received}";
        if (isset($serverMessage)) {
            $message .= " | " . $serverMessage;
        }
        parent::__construct($message);
    }

}
