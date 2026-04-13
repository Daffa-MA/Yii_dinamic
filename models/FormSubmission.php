<?php
namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * FormSubmission model - stores form submissions
 *
 * @property integer $id
 * @property integer $form_id
 * @property integer $user_id
 * @property string $data_json
 * @property string $created_at
 *
 * @property Form $form
 * @property User $user
 */
class FormSubmission extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'form_submissions';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['form_id', 'data_json'], 'required'],
            [['form_id', 'user_id'], 'integer'],
            [['data_json'], 'string'],
            [['form_id'], 'exist', 'skipOnError' => true, 'targetClass' => Form::class, 'targetAttribute' => ['form_id' => 'id']],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'form_id' => 'Form ID',
            'user_id' => 'User ID',
            'data_json' => 'Submission Data',
            'created_at' => 'Submitted At',
        ];
    }

    /**
     * Gets the form
     */
    public function getForm()
    {
        return $this->hasOne(Form::class, ['id' => 'form_id']);
    }

    /**
     * Gets the user who submitted
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    /**
     * Decodes data_json to array
     *
     * @return array
     */
    public function getData()
    {
        return json_decode((string)$this->data_json, true) ?: [];
    }

    /**
     * Encodes array to data_json
     *
     * @param array $data
     */
    public function setData($data)
    {
        $this->data_json = json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    public function getData_json()
    {
        if ($this->hasAttribute('data_json')) {
            return $this->getAttribute('data_json');
        }

        if ($this->hasAttribute('data_js')) {
            return $this->getAttribute('data_js');
        }

        return null;
    }

    public function setData_json($value)
    {
        if ($this->hasAttribute('data_json')) {
            $this->setAttribute('data_json', $value);
            return;
        }

        if ($this->hasAttribute('data_js')) {
            $this->setAttribute('data_js', $value);
        }
    }

    /**
     * Before save - set timestamp
     */
    public function behaviors()
    {
        $timestampExpression = $this->db->driverName === 'sqlite' ? 'CURRENT_TIMESTAMP' : 'NOW()';

        return [
            [
                'class' => \yii\behaviors\TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => null, // No update timestamp for submissions
                'value' => new \yii\db\Expression($timestampExpression),
            ],
        ];
    }
}
