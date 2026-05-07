<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * @property int $id
 * @property string $title
 * @property string|null $slug
 * @property string|null $layout
 * @property string|null $layout_json
 * @property string|null $description
 * @property string $layout_type
 * @property int $is_active
 * @property string $created_at
 * @property string $updated_at
 *
 * @property MasterForm[] $masterForms
 * @property MasterMenu[] $masterMenus
 * @property MasterPageForm[] $pageFormAssignments
 * @property Form[] $assignedForms
 */
class MasterPage extends ActiveRecord
{
    const LAYOUT_DEFAULT = 'default';
    const LAYOUT_LIST = 'list';
    const LAYOUT_FORM = 'form';
    const LAYOUT_DASHBOARD = 'dashboard';
    const LAYOUT_BLANK = 'blank';
    const LAYOUT_TWO_COLUMN = 'two_column';

    /** @var int[] */
    public array $formIds = [];

    public static function tableName()
    {
        return 'master_page';
    }

    public static function getDb()
    {
        return Yii::$app->db;
    }

    public function fields()
    {
        return [
            'id',
            'name',
            'slug',
            'layout',
            'description',
            'layout_json',
            'is_active',
            'created_at',
            'updated_at',
        ];
    }

    public function __get($name)
    {
        try {
            return parent::__get($name);
        } catch (\yii\base\UnknownPropertyException $e) {
            switch ($name) {
                case 'title':
                    return $this->name;
                case 'layout_type':
                    return $this->layout;
                case 'layout_json':
                    return $this->layout_json;
                default:
                    return null;
            }
        }
    }

    public function __set($name, $value)
    {
        try {
            parent::__set($name, $value);
        } catch (\yii\base\UnknownPropertyException $e) {
            switch ($name) {
                case 'title':
                    $this->name = $value;
                    break;
                case 'layout_type':
                    $this->layout = $value;
                    break;
                case 'layout_json':
                    $this->layout_json = $value;
                    break;
                default:
                    throw $e;
            }
        }
    }

    public function __isset($name)
    {
        if (parent::__isset($name)) {
            return true;
        }
        return in_array($name, ['title', 'layout_type']);
    }

    public function rules()
    {
        return [
            [['title'], 'required'],
            [['description'], 'string'],
            [['title', 'slug'], 'string', 'max' => 255],
            [['layout', 'layout_type'], 'string', 'max' => 255],
            [['layout_json'], 'string'],
            [['is_active'], 'integer'],
            ['formIds', 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Judul Halaman',
            'slug' => 'Slug (URL)',
            'layout' => 'Layout',
            'description' => 'Deskripsi',
            'is_active' => 'Status Aktif',
            'created_at' => 'Dibuat',
            'updated_at' => 'Diupdate',
        ];
    }

    public function beforeSave($insert)
    {
        if ($insert) {
            $this->created_at = date('Y-m-d H:i:s');
            $this->is_active = $this->is_active ?? 1;
        }
        $this->updated_at = date('Y-m-d H:i:s');
        return parent::beforeSave($insert);
    }

    public function getMasterForms()
    {
        return $this->hasMany(MasterForm::class, ['page_id' => 'id']);
    }

    public function getMasterMenus()
    {
        return $this->hasMany(MasterMenu::class, ['page_id' => 'id']);
    }

    public function getPageFormAssignments()
    {
        return $this->hasMany(MasterPageForm::class, ['page_id' => 'id'])->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_ASC]);
    }

    public function getAssignedForms()
    {
        return $this->hasMany(Form::class, ['id' => 'form_id'])->via('pageFormAssignments');
    }

    public function isActive()
    {
        return $this->is_active == 1;
    }

    public function toggleStatus()
    {
        $this->is_active = $this->is_active == 1 ? 0 : 1;
        return $this->save(false);
    }

    public static function getLayoutOptions()
    {
        return [
            self::LAYOUT_DEFAULT => 'Default',
            self::LAYOUT_LIST => 'List View',
            self::LAYOUT_FORM => 'Form View',
            self::LAYOUT_DASHBOARD => 'Dashboard',
            self::LAYOUT_TWO_COLUMN => 'Two Column',
            self::LAYOUT_BLANK => 'Blank',
        ];
    }

    public static function getActivePages()
    {
        return self::find()
            ->where(['is_active' => 1])
            ->orderBy(['id' => SORT_ASC])
            ->all();
    }

    public function getFormList()
    {
        return ArrayHelper::map(
            $this->assignedForms,
            'id',
            'name'
        );
    }

    public function loadAssignedFormIds(): void
    {
        $this->formIds = $this->getPageFormAssignments()
            ->select('form_id')
            ->column();
    }

    /**
     * @param int[] $formIds
     */
    public function syncAssignedForms(array $formIds): void
    {
        $normalized = [];
        foreach ($formIds as $index => $formId) {
            $formId = (int) $formId;
            if ($formId <= 0 || in_array($formId, $normalized, true)) {
                continue;
            }
            $normalized[] = $formId;
        }

        MasterPageForm::deleteAll(['page_id' => (int) $this->id]);

        foreach ($normalized as $index => $formId) {
            $assignment = new MasterPageForm();
            $assignment->page_id = (int) $this->id;
            $assignment->form_id = $formId;
            $assignment->sort_order = $index + 1;
            $assignment->is_active = 1;
            $assignment->save(false);
        }

        $this->formIds = $normalized;
    }
}
