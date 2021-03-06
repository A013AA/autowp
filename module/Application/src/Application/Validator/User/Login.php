<?php

namespace Application\Validator\User;

use Zend\Validator\AbstractValidator;

use Users;

class Login extends AbstractValidator
{
    const USER_NOT_FOUND = 'userNotFound';

    protected $messageTemplates = [
        self::USER_NOT_FOUND => "login/user-%value%-not-found"
    ];

    public function isValid($value)
    {
        $this->setValue($value);

        $users = new Users();
        $user = $users->fetchRow(
            $users->select(true)
                  ->where('login = ?', (string)$value)
                  ->orWhere('e_mail = ?', (string)$value)
        );

        if (!$user) {
            $this->error(self::USER_NOT_FOUND);
            return false;
        }

        return true;
    }
}