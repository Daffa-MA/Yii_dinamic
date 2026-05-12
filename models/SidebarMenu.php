<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\helpers\Json;

/**
 * SidebarMenu model
 * Handles dynamic sidebar menu rendering
 */
class SidebarMenu extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%sidebar_menu}}';
    }

    const TYPE_LINK = 'link';
    const TYPE_PAGE = 'page';
    const TYPE_DIVIDER = 'divider';
    const TYPE_HEADER = 'header';

    const VISIBILITY_PUBLIC = 'public';
    const VISIBILITY_AUTHENTICATED = 'authenticated';
    const VISIBILITY_GUEST = 'guest';
    const VISIBILITY_ADMIN = 'admin';

    public function rules()
    {
        return [
            [['label'], 'required'],
            [['parent_id', 'user_id', 'project_id', 'sort_order'], 'integer'],
            [['label', 'icon', 'route', 'url', 'type', 'visibility'], 'string', 'max' => 255],
            [['target'], 'string', 'max' => 10],
            [['params'], 'string'],
            [['is_active'], 'boolean'],
            [['parent_id'], 'exist', 'targetClass' => SidebarMenu::class, 'targetAttribute' => ['parent_id' => 'id']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'parent_id' => 'Parent Menu',
            'label' => 'Label',
            'icon' => 'Icon',
            'route' => 'Route',
            'url' => 'URL',
            'type' => 'Type',
            'target' => 'Target',
            'visibility' => 'Visibility',
            'sort_order' => 'Sort Order',
            'is_active' => 'Active',
            'params' => 'Params',
        ];
    }

    public function getParent()
    {
        return $this->hasOne(SidebarMenu::class, ['id' => 'parent_id']);
    }

    public function getChildren()
    {
        return $this->hasMany(SidebarMenu::class, ['parent_id' => 'id'])
            ->orderBy(['sort_order' => SORT_ASC]);
    }

    public function getActiveChildren()
    {
        return $this->getChildren()->where(['is_active' => true])->all();
    }

    public function getFormPlacement()
    {
        return $this->hasOne(FormPlacement::class, ['menu_id' => 'id']);
    }

    public function hasChildren()
    {
        return $this->getChildren()->count() > 0;
    }

    public function getDepth()
    {
        $depth = 0;
        $parent = $this->parent;
        while ($parent) {
            $depth++;
            $parent = $parent->parent;
        }
        return $depth;
    }

    public function getIconHtml()
    {
        $icon = $this->icon ?: 'circle';
        return '<i class="' . $icon . '"></i>';
    }

    public function getLinkUrl()
    {
        if ($this->url) {
            return $this->url;
        }
        if ($this->route) {
            return Yii::$app->urlManager->createUrl([$this->route]);
        }
        return '#';
    }

    public function isVisible()
    {
        $user = Yii::$app->user;
        
        switch ($this->visibility) {
            case self::VISIBILITY_PUBLIC:
                return true;
            case self::VISIBILITY_AUTHENTICATED:
                return !$user->isGuest;
            case self::VISIBILITY_GUEST:
                return $user->isGuest;
            case self::VISIBILITY_ADMIN:
                return !$user->isGuest && $user->identity->isAdmin();
            default:
                return !$user->isGuest;
        }
    }

    public static function getMenuTree($parentId = null, $userId = null)
    {
        $query = static::find()
            ->where(['parent_id' => $parentId, 'is_active' => true])
            ->orderBy(['sort_order' => SORT_ASC]);

        if ($userId !== null) {
            $query->andWhere(['user_id' => $userId]);
        }

        $items = $query->all();
        $result = [];

        foreach ($items as $item) {
            if (!$item->isVisible()) {
                continue;
            }

            $children = $item->hasChildren() ? static::getMenuTree($item->id, $userId) : [];
            
            $result[] = [
                'id' => $item->id,
                'label' => $item->label,
                'icon' => $item->icon,
                'url' => $item->getLinkUrl(),
                'route' => $item->route,
                'type' => $item->type,
                'target' => $item->target,
                'children' => $children,
                'params' => $item->params ? Json::decode($item->params) : [],
                'model' => $item,
            ];
        }

        return $result;
    }

    public static function getFlatList($userId = null)
    {
        $query = static::find()
            ->where(['is_active' => true])
            ->orderBy(['sort_order' => SORT_ASC]);

        if ($userId !== null) {
            $query->andWhere(['user_id' => $userId]);
        }

        return $query->all();
    }

    public static function createFromPlacement(FormPlacement $placement, $parentId = null)
    {
        $menu = new self();
        $menu->parent_id = $parentId;
        $menu->user_id = $placement->form->user_id ?? Yii::$app->user->id;
        $menu->label = $placement->page_title ?: $placement->form->form_name;
        $menu->route = $placement->route_path;
        $menu->type = self::TYPE_LINK;
        $menu->visibility = $placement->is_public ? self::VISIBILITY_PUBLIC : self::VISIBILITY_AUTHENTICATED;
        $menu->sort_order = $placement->sort_order;
        
        if ($menu->save()) {
            $placement->menu_id = $menu->id;
            $placement->save(false);
            return $menu;
        }
        
        return null;
    }

    public static function updateOrder($items, $parentId = null)
    {
        foreach ($items as $order => $id) {
            $model = static::findOne($id);
            if ($model) {
                $model->sort_order = $order;
                $model->parent_id = $parentId;
                $model->save(false);
            }
        }
    }

    public static function getDropdownItems($excludeId = null, $userId = null)
    {
        $query = static::find()
            ->where(['is_active' => true, 'type' => self::TYPE_LINK])
            ->orderBy(['sort_order' => SORT_ASC]);

        if ($userId !== null) {
            $query->andWhere(['user_id' => $userId]);
        }

        $items = $query->all();
        $result = [];

        foreach ($items as $item) {
            if ($item->id == $excludeId) {
                continue;
            }
            $result[] = [
                'id' => $item->id,
                'label' => $item->label,
                'depth' => $item->getDepth(),
            ];
        }

        return $result;
    }
}
