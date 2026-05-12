<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\web\NotFoundHttpException;

/**
 * FormPlacement model
 * Handles form placement, menu mapping, and page generation
 */
class FormPlacement extends ActiveRecord
{
    public $parent_menu_id;
    public $icon;
    public $layout_template = 'default';

    public static function tableName()
    {
        return '{{%form_placement}}';
    }

    public function attributes()
    {
        return [
            'id', 'form_id', 'menu_id', 'page_id', 'page_title', 'page_slug', 'route_path',
            'show_in_menu', 'show_in_navbar', 'show_in_sidebar', 'is_public', 'is_published',
            'sort_order', 'meta_title', 'meta_description', 'icon', 'layout_template',
            'custom_css', 'custom_js', 'params', 'created_at', 'updated_at',
        ];
    }

    public function __get($name)
    {
        try {
            return parent::__get($name);
        } catch (\yii\base\UnknownPropertyException $e) {
            return null;
        }
    }

    public function __set($name, $value)
    {
        try {
            parent::__set($name, $value);
        } catch (\yii\base\UnknownPropertyException $e) {
            // Ignore unknown properties
        }
    }

    public function __isset($name)
    {
        if (parent::__isset($name)) {
            return true;
        }
        $schema = static::getTableSchema();
        return $schema && isset($schema->columns[$name]);
    }

    public function rules()
    {
        return [
            [['form_id'], 'required'],
            [['form_id', 'menu_id', 'page_id', 'sort_order'], 'integer'],
            [['show_in_menu', 'show_in_navbar', 'show_in_sidebar', 'is_public', 'is_published'], 'boolean'],
            [['page_title', 'page_slug', 'route_path', 'meta_title', 'icon', 'layout_template'], 'string', 'max' => 255],
            [['meta_description', 'custom_css', 'custom_js', 'params'], 'string'],
            [['form_id'], 'exist', 'targetClass' => MasterForm::class, 'targetAttribute' => ['form_id' => 'id']],
            [['menu_id'], 'exist', 'targetClass' => SidebarMenu::class, 'targetAttribute' => ['menu_id' => 'id'], 'skipOnEmpty' => true],
            [['page_id'], 'exist', 'targetClass' => MasterPage::class, 'targetAttribute' => ['page_id' => 'id'], 'skipOnEmpty' => true],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'form_id' => 'Form',
            'menu_id' => 'Menu',
            'page_title' => 'Page Title',
            'page_slug' => 'URL Slug',
            'route_path' => 'Route Path',
            'show_in_menu' => 'Show in Menu',
            'show_in_navbar' => 'Show in Navbar',
            'show_in_sidebar' => 'Show in Sidebar',
            'is_public' => 'Public Page',
            'is_published' => 'Published',
            'sort_order' => 'Sort Order',
            'meta_title' => 'Meta Title',
            'meta_description' => 'Meta Description',
            'layout_template' => 'Layout Template',
            'custom_css' => 'Custom CSS',
            'custom_js' => 'Custom JS',
        ];
    }

    public function getForm()
    {
        return $this->hasOne(MasterForm::class, ['id' => 'form_id']);
    }

    public function getMenu()
    {
        return $this->hasOne(SidebarMenu::class, ['id' => 'menu_id']);
    }

    public static function findBySlug($slug)
    {
        return static::find()->where(['page_slug' => $slug, 'is_published' => true])->one();
    }

    public static function findByRoute($route)
    {
        return static::find()->where(['route_path' => $route, 'is_published' => true])->one();
    }

    public static function generateSlug($name)
    {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        
        $counter = 1;
        $originalSlug = $slug;
        while (static::find()->where(['page_slug' => $slug])->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }

    public static function generateRoute($slug)
    {
        return '/form/' . $slug;
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($insert) {
                if (empty($this->page_slug)) {
                    $this->page_slug = self::generateSlug($this->page_title ?: 'page');
                }
                if (empty($this->route_path)) {
                    $this->route_path = self::generateRoute($this->page_slug);
                }
            }
            return true;
        }
        return false;
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        
        if ($insert || isset($changedAttributes['menu_id']) || isset($changedAttributes['show_in_sidebar'])) {
            $this->syncMenu();
        }
    }

    public function syncMenu()
    {
        if ($this->menu_id && $this->show_in_sidebar) {
            $menu = $this->menu;
            if ($menu) {
                $menu->route = $this->route_path;
                $menu->label = $this->page_title ?: $this->form->form_name ?? 'Untitled';
                $menu->save(false);
            }
        }
    }

    public static function getVisiblePlacements($userId = null)
    {
        $query = static::find()
            ->where(['is_published' => true])
            ->andWhere(['show_in_sidebar' => true])
            ->orderBy(['sort_order' => SORT_ASC]);

        if ($userId !== null) {
            $query->joinWith('form')
                ->andWhere(['master_form.user_id' => $userId]);
        }

        return $query->all();
    }
}
