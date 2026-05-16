<?php

namespace app\models;

use yii\base\Model;

class ProjectLoginForm extends Model
{
    public $username;
    public $password;

    private ?ProjectUser $_user = null;

    public function rules()
    {
        return [
            [['username', 'password'], 'required'],
            [['username'], 'trim'],
            ['password', 'validatePassword'],
        ];
    }

    public function validatePassword($attribute, $params): void
    {
        if ($this->hasErrors()) {
            return;
        }

        $user = $this->getUser();
        if ($user === null || !$user->validatePassword((string)$this->password)) {
            $this->addError($attribute, 'Username atau password salah.');
        }
    }

    public function getUser(): ?ProjectUser
    {
        if ($this->_user === null) {
            $this->_user = ProjectUser::findByUsername((string)$this->username);
        }

        return $this->_user;
    }
}
