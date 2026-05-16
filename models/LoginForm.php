<?php
namespace app\models;

use app\components\CommanderAuthContext;
use Yii;
use yii\base\Model;

/**
 * LoginForm handles login form data collection and validation
 */
class LoginForm extends Model
{
    public $username;
    public $password;
    public $rememberMe = true;

    private $_user = false;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['username', 'password'], 'required'],
            ['rememberMe', 'boolean'],
            ['password', 'validatePassword'],
        ];
    }

    /**
     * Validates the password.
     */
    public function validatePassword($attribute, $params)
    {
        if (!$this->hasErrors()) {
            $user = $this->getUser();

            if (!$user || !$user->validatePassword($this->password)) {
                $this->addError($attribute, 'Incorrect username or password.');
            }
        }
    }

    /**
     * Logs in a user using the provided username and password.
     *
     * @return bool if the user is logged in successfully
     */
    public function login()
    {
        if ($this->validate()) {
            $user = $this->getUser();
            if ($user === null) {
                return false;
            }

            if (Yii::$app->user->login($user, $this->rememberMe ? 3600 * 24 * 30 : 0)) {
                (new CommanderAuthContext())->login($user);
                return true;
            }
        }
        return false;
    }

    /**
     * Finds user by username
     *
     * @return User|null
     */
    protected function getUser()
    {
        if ($this->_user === false) {
            $this->_user = User::findByUsername($this->username);
        }

        return $this->_user;
    }
}
