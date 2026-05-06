<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

class MasterMenu extends ActiveRecord
{
    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 0;
    
    // Type constants
    const TYPE_GROUP = 'group';
    const TYPE_PAGE = 'page';
    const TYPE_ROUTE = 'route';

    public static function tableName()
    {
        return 'master_menu';
    }

    public static function getDb()
    {
        return Yii::$app->db;
    }

    public function fields()
    {
        return [
            'id',
            'parent_id',
            'page_id',
            'name',
            'type',
            'route',
            'menu_key',
            'sort_order',
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
            if ($name === 'icon') {
                return null;
            }
            throw $e;
        }
    }

    public function rules()
    {
        return [
            [['name'], 'required'],
            [['parent_id', 'page_id', 'sort_order', 'is_active'], 'integer'],
            [['name'], 'string', 'max' => 100],
            [['icon'], 'string', 'max' => 50],
            [['route'], 'string', 'max' => 255],
            [['menu_key'], 'string', 'max' => 50],
            [['type'], 'string', 'max' => 20],
            [['type'], 'in', 'range' => [self::TYPE_GROUP, self::TYPE_PAGE, self::TYPE_ROUTE], 'message' => 'Pilih tipe menu yang valid'],
            
            // Custom validation for type-specific requirements
            ['page_id', 'required', 'when' => function($model) {
                return $model->type === self::TYPE_PAGE;
            }, 'message' => 'Menu tipe Page wajib memilih Halaman'],
            
            ['route', 'required', 'when' => function($model) {
                return $model->type === self::TYPE_ROUTE;
            }, 'message' => 'Menu tipe Route wajib isi URL'],
            
            // Page type should NOT have route
            ['route', 'validateRouteForPage', 'when' => function($model) {
                return $model->type === self::TYPE_PAGE;
            }],
            
            ['parent_id', 'exist', 'skipOnError' => true, 'targetClass' => MasterMenu::class, 'targetAttribute' => ['parent_id' => 'id']],
            ['page_id', 'exist', 'skipOnError' => true, 'targetClass' => MasterPage::class, 'targetAttribute' => ['page_id' => 'id']],
        ];
    }

    public function validateRouteForPage($attribute, $params)
    {
        if ($this->type === self::TYPE_PAGE && !empty($this->route)) {
            $this->addError($attribute, 'Menu tipe Page tidak boleh menggunakan Route');
        }
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'parent_id' => 'Parent Menu',
            'type' => 'Tipe',
            'page_id' => 'Halaman',
            'name' => 'Nama Menu',
            'icon' => 'Icon',
            'route' => 'Route (URL)',
            'menu_key' => 'Menu Key',
            'sort_order' => 'Urutan',
            'order' => 'Order',
            'is_active' => 'Status Aktif',
            'created_at' => 'Dibuat',
            'updated_at' => 'Diupdate',
        ];
    }

    public function getParent()
    {
        return $this->hasOne(MasterMenu::class, ['id' => 'parent_id']);
    }

    public function getChildren()
    {
        return $this->hasMany(MasterMenu::class, ['parent_id' => 'id']);
    }

    public function getPage()
    {
        return $this->hasOne(MasterPage::class, ['id' => 'page_id']);
    }

    public function beforeSave($insert)
    {
        // Auto-detect type if not set
        if (empty($this->type)) {
            if (!empty($this->route)) {
                $this->type = self::TYPE_ROUTE;
            } elseif (!empty($this->page_id)) {
                $this->type = self::TYPE_PAGE;
            } else {
                $this->type = self::TYPE_GROUP;
            }
        }
        
        // Clear irrelevant fields based on type
        if ($this->type === self::TYPE_GROUP) {
            $this->page_id = null;
            $this->route = null;
        } elseif ($this->type === self::TYPE_PAGE) {
            $this->route = null;
        } elseif ($this->type === self::TYPE_ROUTE) {
            $this->page_id = null;
        }
        
        if ($insert) {
            $this->created_at = date('Y-m-d H:i:s');
            $this->is_active = $this->is_active ?? self::STATUS_ACTIVE;
            $this->sort_order = $this->sort_order ?? (self::find()->max('[[sort_order]]') + 1);
        }
        $this->updated_at = date('Y-m-d H:i:s');
        return parent::beforeSave($insert);
    }

    public function toggleStatus()
    {
        $this->is_active = $this->is_active == self::STATUS_ACTIVE ? self::STATUS_INACTIVE : self::STATUS_ACTIVE;
        return $this->save(false);
    }

    public function isActive(): bool
    {
        return (int) $this->is_active === self::STATUS_ACTIVE;
    }
    
    public function isGroup(): bool
    {
        return $this->type === self::TYPE_GROUP;
    }
    
    public function isPage(): bool
    {
        return $this->type === self::TYPE_PAGE;
    }
    
    public function isRoute(): bool
    {
        return $this->type === self::TYPE_ROUTE;
    }
    
    public function getUrl()
    {
        if ($this->type === self::TYPE_ROUTE && !empty($this->route)) {
            return $this->route[0] === '/' ? $this->route : '/' . ltrim($this->route, '/');
        }
        if ($this->type === self::TYPE_PAGE && $this->page_id) {
            return \yii\helpers\Url::to(['/page/view', 'id' => $this->page_id]);
        }
        return null;
    }
    
    public function hasChildren(): bool
    {
        return self::find()->where(['parent_id' => $this->id])->count() > 0;
    }

    public static function getMenuTree($activeOnly = true)
    {
        $items = self::find()->orderBy(['sort_order' => SORT_ASC])->all();
        
        if ($activeOnly) {
            $items = array_filter($items, function($item) {
                return (int) $item->is_active === self::STATUS_ACTIVE;
            });
            $items = array_values($items);
        }
        
        return self::buildTree($items);
    }

    private static function buildTree($items, $parentId = null)
    {
        $branch = [];
        foreach ($items as $item) {
            if ($item->parent_id == $parentId) {
                $children = self::buildTree($items, $item->id);
                
                $node = [
                    'id' => $item->id,
                    'name' => $item->name,
                    'type' => $item->type,
                    'icon' => $item->icon ?: 'folder',
                    'url' => $item->getUrl(),
                    'menu_key' => $item->menu_key,
                    'page_id' => $item->page_id,
                    'route' => $item->route,
                    'has_children' => !empty($children),
                    'children' => !empty($children) ? $children : null,
                ];
                $branch[] = $node;
            }
        }
        return $branch;
    }
}