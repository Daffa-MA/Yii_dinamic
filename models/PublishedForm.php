<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\helpers\Inflector;

/**
 * PublishedForm model - stores published form information
 *
 * @property integer $id
 * @property integer $user_id
 * @property integer $form_id
 * @property string $name
 * @property string $slug
 * @property string $created_at
 * @property string $updated_at
 *
 * @property User $user
 * @property Form $form
 */
class PublishedForm extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function getDb()
    {
        return Yii::$app->get('metadataDb', false) ?: parent::getDb();
    }

    public static function tableName()
    {
        return 'published_forms';
    }

    public function rules()
    {
        return [
            [['user_id', 'form_id', 'name'], 'required'],
            [['user_id', 'form_id'], 'integer'],
            [['name'], 'string', 'max' => 255],
            [['slug'], 'string', 'max' => 255],
            [['slug'], 'unique'],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
            [['form_id'], 'exist', 'skipOnError' => true, 'targetClass' => Form::class, 'targetAttribute' => ['form_id' => 'id']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'form_id' => 'Form',
            'name' => 'Published Name',
            'slug' => 'URL Slug',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function getForm()
    {
        return $this->hasOne(Form::class, ['id' => 'form_id']);
    }

    public function behaviors()
    {
        $timestampExpression = $this->db->driverName === 'sqlite' ? 'CURRENT_TIMESTAMP' : 'NOW()';

        return [
            [
                'class' => \yii\behaviors\TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
                'value' => new \yii\db\Expression($timestampExpression),
            ],
            [
                'class' => \yii\behaviors\SluggableBehavior::class,
                'attribute' => 'name',
                'slugAttribute' => 'slug',
                'immutable' => false,
                'ensureUnique' => true,
            ],
        ];
    }

    /**
     * Get the full URL for the published form
     */
    public function getUrl()
    {
        return Yii::$app->urlManager->createAbsoluteUrl(['form/render', 'id' => $this->form_id]);
    }
}
