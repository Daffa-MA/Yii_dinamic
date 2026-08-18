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
    public $rememberMe = false;

    private $_user = false;

    /**
     * Valid bcrypt hash of a random throwaway string, used to equalize the
     * response time of a failed login when the submitted username does not
     * exist. Without it, an attacker could time the lookup to enumerate which
     * usernames are registered.
     */
    private const DUMMY_HASH = '$2y$13$DqditWT59HfBF9uceXLXR.bS.Qf.tt3Pqft2AeWD.MaCKFvuW4D5.';

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

            if ($user === null) {
                // Unknown username: still run a hash verification so the
                // response time does not reveal whether the username exists.
                Yii::$app->security->validatePassword((string)$this->password, self::DUMMY_HASH);
                $this->addError($attribute, 'Incorrect username or password.');
                return;
            }

            if (!$user->validatePassword((string)$this->password)) {
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

            if (Yii::$app->user->login($user, 0)) {
                (new CommanderAuthContext())->login($user);
                return true;
            }
        }
        return false;
    }

    /**
     * Finds user by username
     *
     * The Commander account is addressed by its canonical username
     * `superadmin`; it is backed by the single framework `users` row.
     *
     * @return User|null
     */
    protected function getUser()
    {
        if ($this->_user === false) {
            $username = strtolower(trim((string)$this->username));
            if ($username === 'superadmin') {
                $username = 'admin';
            }
            $this->_user = User::findByUsername($username);
        }

        return $this->_user;
    }
}
