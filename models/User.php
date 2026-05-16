<?php
namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

/**
 * User model - database-backed
 *
 * @property integer $id
 * @property string $username
 * @property string $password_hash
 * @property string $auth_key
 * @property string $created_at
 * @property string $updated_at
 */
class User extends ActiveRecord implements IdentityInterface
{
    public static function ensureCommanderStructure(): void
    {
        $db = Yii::$app->get('metadataDb', false) ?: parent::getDb();
        $schema = $db->schema->getTableSchema(static::tableName(), true);
        if ($schema === null) {
            return;
        }

        $columns = [
            'role' => $db->schema->createColumnSchemaBuilder('string', 50)->notNull()->defaultValue('user'),
            'status' => $db->schema->createColumnSchemaBuilder('tinyint', 1)->notNull()->defaultValue(1),
        ];

        foreach ($columns as $columnName => $columnSchema) {
            if (!isset($schema->columns[$columnName])) {
                $db->createCommand()->addColumn(static::tableName(), $columnName, $columnSchema)->execute();
                $db->schema->refreshTableSchema(static::tableName());
                $schema = $db->schema->getTableSchema(static::tableName(), true);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public static function getDb()
    {
        return Yii::$app->get('metadataDb', false) ?: parent::getDb();
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'users';
    }

    /**
     * @inheritdoc
     */
    public static function findIdentity($id)
    {
        self::ensureCommanderStructure();
        return static::findOne(['id' => $id]);
    }

    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        return null; // Not implementing token-based auth
    }

    /**
     * Finds user by username
     *
     * @param string $username
     * @return static|null
     */
    public static function findByUsername($username)
    {
        self::ensureCommanderStructure();
        $username = strtolower(trim((string)$username));
        $user = static::findOne(['username' => $username]);
        if ($user !== null) {
            return $user;
        }

        if (in_array($username, ['admin', 'superadmin'], true)) {
            $fallback = $username === 'admin' ? 'superadmin' : 'admin';
            return static::findOne(['username' => $fallback]);
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @inheritdoc
     */
    public function getAuthKey()
    {
        return $this->auth_key;
    }

    /**
     * @inheritdoc
     */
    public function validateAuthKey($authKey)
    {
        return $this->auth_key === $authKey;
    }

    /**
     * Validates password
     *
     * @param string $password password to validate
     * @return bool if password provided is valid for current user
     */
    public function validatePassword($password)
    {
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    /**
     * Generates password hash from plain password
     *
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }

    /**
     * Generates auth key
     */
    public function generateAuthKey()
    {
        $this->auth_key = Yii::$app->security->generateRandomString();
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['username', 'password_hash'], 'required'],
            [['username'], 'string', 'max' => 100],
            [['role'], 'string', 'max' => 50],
            [['username'], 'unique'],
        ];
    }

    public function isSuperAdmin(): bool
    {
        $role = strtolower(trim((string)($this->role ?? '')));
        return in_array($role, ['super_admin', 'superadmin', 'admin'], true) || strtolower(trim((string)($this->username ?? ''))) === 'admin';
    }
}
