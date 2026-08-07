<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

class ProjectUser extends ActiveRecord
{
    public static function tableName()
    {
        return 'users';
    }

    public static function getDb()
    {
        return Yii::$app->db;
    }

    public static function findByUsername(string $username): ?self
    {
        return static::findOne(['username' => $username]);
    }

    public function rules()
    {
        return [
            [['name', 'username', 'email', 'password_hash', 'role', 'status'], 'required'],
            [['must_change_password'], 'boolean'],
            [['status'], 'integer'],
            [['name', 'username', 'email', 'role', 'identity_table', 'identity_record_id'], 'string', 'max' => 255],
            [['password_hash'], 'string', 'max' => 255],
            [['username', 'email'], 'unique'],
            [['email'], 'email'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'username' => 'Username',
            'email' => 'Email',
            'password_hash' => 'Password Hash',
            'role' => 'Role',
            'status' => 'Status',
            'must_change_password' => 'Must Change Password',
            'identity_table' => 'Identity Table',
            'identity_record_id' => 'Identity Record ID',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    public function validatePassword(string $password): bool
    {
        return Yii::$app->security->validatePassword($password, (string)$this->password_hash);
    }

    public function setPassword(string $password): void
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }

    public function generateDefaultEmail(): string
    {
        return $this->email ?: ($this->username . '@local');
    }
}
