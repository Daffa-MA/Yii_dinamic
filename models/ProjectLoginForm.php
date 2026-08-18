<?php

namespace app\models;

use Yii;
use yii\base\Model;

class ProjectLoginForm extends Model
{
    public $username;
    public $password;

    /**
     * Valid bcrypt hash of a random throwaway string, used to equalize the
     * response time of a failed login when the submitted username does not
     * exist. Without it, an attacker could time the lookup to enumerate which
     * usernames are registered.
     */
    private const DUMMY_HASH = '$2y$13$DqditWT59HfBF9uceXLXR.bS.Qf.tt3Pqft2AeWD.MaCKFvuW4D5.';

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
        if ($user === null) {
            // Unknown username: still run a hash verification so the response
            // time does not reveal whether the username is registered.
            Yii::$app->security->validatePassword((string)$this->password, self::DUMMY_HASH);
            $this->addError($attribute, 'Username atau password salah.');
            return;
        }

        if (!$user->validatePassword((string)$this->password)) {
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
