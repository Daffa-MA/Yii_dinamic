<?php

namespace app\components;

use app\models\User;
use Yii;

class CommanderAuthContext
{
    public const SESSION_KEY_AUTH = 'commander_auth';
    public const SESSION_KEY_LOGIN = 'commander_login';
    public const SESSION_KEY_ROLE = 'commander_role';
    public const SESSION_KEY_USER_ID = 'commander_user_id';
    public const SESSION_KEY_USERNAME = 'commander_username';

    public function login(User $user): void
    {
        $role = $this->normalizeRole((string)$user->role, (string)$user->username);
        Yii::$app->session->set(self::SESSION_KEY_AUTH, true);
        Yii::$app->session->set(self::SESSION_KEY_LOGIN, true);
        Yii::$app->session->set(self::SESSION_KEY_USER_ID, (int)$user->id);
        Yii::$app->session->set(self::SESSION_KEY_USERNAME, (string)$user->username);
        Yii::$app->session->set(self::SESSION_KEY_ROLE, $role);
    }

    public function logout(): void
    {
        Yii::$app->session->remove(self::SESSION_KEY_AUTH);
        Yii::$app->session->remove(self::SESSION_KEY_LOGIN);
        Yii::$app->session->remove(self::SESSION_KEY_USER_ID);
        Yii::$app->session->remove(self::SESSION_KEY_USERNAME);
        Yii::$app->session->remove(self::SESSION_KEY_ROLE);
    }

    public function isAuthenticated(): bool
    {
        $authData = Yii::$app->session->get(self::SESSION_KEY_AUTH, []);
        if ($authData === true || $authData === 1 || $authData === '1') {
            return true;
        }

        if (is_array($authData) && !empty($authData['user_id'])) {
            return true;
        }

        if ((bool)Yii::$app->session->get(self::SESSION_KEY_LOGIN, false)) {
            return true;
        }

        return false;
    }

    public function getRole(): string
    {
        $role = trim((string)Yii::$app->session->get(self::SESSION_KEY_ROLE, ''));
        if ($role !== '') {
            return $this->normalizeRole($role);
        }

        $authData = Yii::$app->session->get(self::SESSION_KEY_AUTH, []);
        $role = is_array($authData) ? trim((string)($authData['role'] ?? '')) : '';

        if ($role !== '') {
            return $this->normalizeRole($role);
        }

        return '';
    }

    public function isSuperAdmin(): bool
    {
        return $this->isAuthenticated() && $this->getRole() === 'superadmin';
    }

    public function getUser(): ?User
    {
        $authData = Yii::$app->session->get(self::SESSION_KEY_AUTH, []);
        $userId = is_array($authData) ? (int)($authData['user_id'] ?? 0) : 0;
        if ($userId <= 0) {
            $userId = (int)Yii::$app->session->get(self::SESSION_KEY_USER_ID, 0);
        }

        return $userId > 0 ? User::findIdentity($userId) : null;
    }

    private function normalizeRole(string $role, string $username = ''): string
    {
        $role = strtolower(trim($role));
        $username = strtolower(trim($username));
        if (in_array($username, ['admin', 'superadmin'], true) || in_array($role, ['super_admin', 'superadmin'], true)) {
            return 'superadmin';
        }

        return $role;
    }
}
