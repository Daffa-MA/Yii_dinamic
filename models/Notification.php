<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * Notification model
 *
 * @property integer $id
 * @property integer $user_id
 * @property string $title
 * @property string $message
 * @property string $type
 * @property string $icon
 * @property string $action_text
 * @property string $action_url
 * @property boolean $is_read
 * @property string $created_at
 *
 * @property User $user
 */
class Notification extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function getDb()
    {
        return Yii::$app->get('metadataDb', false) ?: parent::getDb();
    }

    const TYPE_INFO = 'info';
    const TYPE_SUCCESS = 'success';
    const TYPE_WARNING = 'warning';
    const TYPE_ERROR = 'error';

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'notifications';
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => null,
                'value' => new \yii\db\Expression('NOW()'),
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'title', 'message'], 'required'],
            [['user_id', 'is_read'], 'integer'],
            [['message'], 'string'],
            [['title'], 'string', 'max' => 255],
            [['type'], 'string', 'max' => 50],
            [['icon'], 'string', 'max' => 100],
            [['action_text'], 'string', 'max' => 100],
            [['action_url'], 'string', 'max' => 255],
            [['type'], 'in', 'range' => [self::TYPE_INFO, self::TYPE_SUCCESS, self::TYPE_WARNING, self::TYPE_ERROR]],
            [['is_read'], 'boolean'],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'title' => 'Title',
            'message' => 'Message',
            'type' => 'Type',
            'icon' => 'Icon',
            'action_text' => 'Action Text',
            'action_url' => 'Action URL',
            'is_read' => 'Is Read',
            'created_at' => 'Created At',
        ];
    }

    /**
     * Gets query for User
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }

    /**
     * Get unread notifications count for a user
     */
    public static function getUnreadCount($userId)
    {
        return static::find()
            ->where(['user_id' => $userId, 'is_read' => false])
            ->count();
    }

    /**
     * Get latest notifications for a user
     */
    public static function getLatest($userId, $limit = 20)
    {
        return static::find()
            ->where(['user_id' => $userId])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit($limit)
            ->all();
    }

    /**
     * Mark notification as read
     */
    public function markAsRead()
    {
        $this->is_read = true;
        return $this->save(false);
    }

    /**
     * Mark all notifications as read for a user
     */
    public static function markAllAsRead($userId)
    {
        return static::updateAll(
            ['is_read' => true],
            ['user_id' => $userId, 'is_read' => false]
        );
    }

    /**
     * Get relative time string (e.g., "2h ago", "3d ago")
     */
    public function getRelativeTime()
    {
        $timestamp = strtotime($this->created_at);
        $now = time();
        $diff = $now - $timestamp;

        if ($diff < 60) {
            return 'Just now';
        } elseif ($diff < 3600) {
            $minutes = floor($diff / 60);
            return $minutes . 'm';
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . 'h';
        } else {
            $days = floor($diff / 86400);
            return $days . 'd';
        }
    }

    /**
     * Create a new notification
     */
    public static function create($userId, $title, $message, $type = self::TYPE_INFO, $options = [])
    {
        $notification = new self();
        $notification->user_id = $userId;
        $notification->title = $title;
        $notification->message = $message;
        $notification->type = $type;
        $notification->icon = $options['icon'] ?? null;
        $notification->action_text = $options['action_text'] ?? null;
        $notification->action_url = $options['action_url'] ?? null;
        
        if ($notification->save()) {
            return $notification;
        }
        
        return false;
    }
}
