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

    public function login(User $user): void
    {
        $role = $this->normalizeRole((string)$user->role, (string)$user->username);
        Yii::$app->session->set(self::SESSION_KEY_AUTH, [
            'user_id' => (int)$user->id,
            'role' => $role,
            'logged_in_at' => date('Y-m-d H:i:s'),
        ]);
        Yii::$app->session->set(self::SESSION_KEY_LOGIN, true);
        Yii::$app->session->set(self::SESSION_KEY_USER_ID, (int)$user->id);
        Yii::$app->session->set(self::SESSION_KEY_ROLE, $role);
    }

    public function logout(): void
    {
        Yii::$app->session->remove(self::SESSION_KEY_AUTH);
        Yii::$app->session->remove(self::SESSION_KEY_LOGIN);
        Yii::$app->session->remove(self::SESSION_KEY_USER_ID);
        Yii::$app->session->remove(self::SESSION_KEY_ROLE);
    }

    public function isAuthenticated(): bool
    {
        if (Yii::$app->user->isGuest) {
            return false;
        }

        $authData = Yii::$app->session->get(self::SESSION_KEY_AUTH, []);
        if (is_array($authData) && !empty($authData['user_id'])) {
            return true;
        }

        if ((bool)Yii::$app->session->get(self::SESSION_KEY_LOGIN, false)) {
            return true;
        }

        return Yii::$app->user->identity instanceof User;
    }

    public function getRole(): string
    {
        $authData = Yii::$app->session->get(self::SESSION_KEY_AUTH, []);
        $role = is_array($authData) ? trim((string)($authData['role'] ?? '')) : '';
        if ($role === '') {
            $role = trim((string)Yii::$app->session->get(self::SESSION_KEY_ROLE, ''));
        }

        if ($role !== '') {
            return $this->normalizeRole($role);
        }

        $identity = Yii::$app->user->identity;
        if ($identity instanceof User) {
            return $this->normalizeRole((string)$identity->role, (string)$identity->username);
        }

        return '';
    }

    public function isSuperAdmin(): bool
    {
        return $this->isAuthenticated() && $this->getRole() === 'superadmin';
    }

    public function getUser(): ?User
    {
        $identity = Yii::$app->user->identity;
        return $identity instanceof User ? $identity : null;
    }

    private function normalizeRole(string $role, string $username = ''): string
    {
        $role = strtolower(trim($role));
        $username = strtolower(trim($username));
        if ($role === 'super_admin' || $role === 'superadmin' || $role === 'admin' || $username === 'admin') {
            return 'superadmin';
        }

        return $role;
    }
}
