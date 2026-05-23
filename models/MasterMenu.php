<?php

namespace app\models;

use app\components\ProjectPermissionRegistry;
use app\components\ProjectPermissionService;
use Yii;
use yii\db\ActiveRecord;
use yii\helpers\Inflector;

class MasterMenu extends ActiveRecord
{
    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 0;
    
    // Type constants
    const TYPE_GROUP = 'group';
    const TYPE_PAGE = 'page';
    const TYPE_ROUTE = 'route';
    const TYPE_BUTTON = 'button';
    const TYPE_DIVIDER = 'divider';
    const TYPE_FORM = 'form';

    // Target constants
    const TARGET_SELF = '_self';
    const TARGET_BLANK = '_blank';
    const TARGET_MODAL = '_modal';
    const TARGET_AJAX = '_ajax';
    const TARGET_POPUP = '_popup';

    // Action type constants
    const ACTION_LINK = 'link';
    const ACTION_MODAL = 'modal';
    const ACTION_AJAX = 'ajax';
    const ACTION_FORM_SUBMIT = 'form_submit';
    const ACTION_DOWNLOAD = 'download';
    const ACTION_JAVASCRIPT = 'javascript';

    // Button style constants
    const BUTTON_STYLE_PRIMARY = 'primary';
    const BUTTON_STYLE_SECONDARY = 'secondary';
    const BUTTON_STYLE_SUCCESS = 'success';
    const BUTTON_STYLE_DANGER = 'danger';
    const BUTTON_STYLE_WARNING = 'warning';
    const BUTTON_STYLE_INFO = 'info';
    const BUTTON_STYLE_LINK = 'link';
    const BUTTON_STYLE_OUTLINE_PRIMARY = 'outline-primary';
    const BUTTON_STYLE_OUTLINE_SECONDARY = 'outline-secondary';

    // Button size constants
    const BUTTON_SIZE_SM = 'sm';
    const BUTTON_SIZE_MD = 'md';
    const BUTTON_SIZE_LG = 'lg';
    const BUTTON_SIZE_BLOCK = 'block';

    // Icon position
    const ICON_POSITION_LEFT = 'left';
    const ICON_POSITION_RIGHT = 'right';
    const ICON_POSITION_TOP = 'top';

    // Animation types
    const ANIMATION_NONE = 'none';
    const ANIMATION_FADE = 'fade';
    const ANIMATION_SLIDE = 'slide';
    const ANIMATION_BOUNCE = 'bounce';
    const ANIMATION_PULSE = 'pulse';
    const ANIMATION_ZOOM = 'zoom';

    // Border style
    const BORDER_STYLE_NONE = 'none';
    const BORDER_STYLE_SOLID = 'solid';
    const BORDER_STYLE_DASHED = 'dashed';
    const BORDER_STYLE_DOTTED = 'dotted';
    const BORDER_STYLE_DOUBLE = 'double';
    const BORDER_STYLE_GROOVE = 'groove';
    const BORDER_STYLE_RIDGE = 'ridge';
    const BORDER_STYLE_INSET = 'inset';
    const BORDER_STYLE_OUTSET = 'outset';

    // Border position
    const BORDER_POSITION_ALL = 'all';
    const BORDER_POSITION_TOP = 'top';
    const BORDER_POSITION_RIGHT = 'right';
    const BORDER_POSITION_BOTTOM = 'bottom';
    const BORDER_POSITION_LEFT = 'left';
    const BORDER_POSITION_TOP_BOTTOM = 'top-bottom';
    const BORDER_POSITION_LEFT_RIGHT = 'left-right';

    // Border radius
    const BORDER_RADIUS_NONE = 'none';
    const BORDER_RADIUS_SM = 'sm';
    const BORDER_RADIUS_MD = 'md';
    const BORDER_RADIUS_LG = 'lg';
    const BORDER_RADIUS_XL = 'xl';
    const BORDER_RADIUS_CIRCLE = 'circle';
    const BORDER_RADIUS_PILL = 'pill';

    public static function tableName()
    {
        return 'master_menu';
    }

    public static function getDb()
    {
        return Yii::$app->db;
    }
    
    public static function ensureColumnsExist()
    {
        $db = Yii::$app->db;
        $schema = $db->getTableSchema('master_menu', true);
        
        if ($schema === null) {
            return;
        }
        
        $columnsToAdd = [
            'form_id' => ['type' => 'integer', 'default' => null],
            'target' => ['type' => 'string', 'length' => 20, 'default' => '_self'],
            'action_type' => ['type' => 'string', 'length' => 20, 'default' => 'link'],
            'button_text' => ['type' => 'string', 'length' => 100],
            'button_style' => ['type' => 'string', 'length' => 30, 'default' => 'primary'],
            'button_size' => ['type' => 'string', 'length' => 10, 'default' => 'md'],
            'button_icon' => ['type' => 'string', 'length' => 50],
            'button_full_width' => ['type' => 'integer', 'length' => 1, 'default' => 0],
            'css_class' => ['type' => 'string', 'length' => 255],
            'css_style' => ['type' => 'text'],
            'custom_html' => ['type' => 'text'],
            'badge_text' => ['type' => 'string', 'length' => 100],
            'badge_style' => ['type' => 'string', 'length' => 30, 'default' => 'primary'],
            'show_tooltip' => ['type' => 'string', 'length' => 255],
            'tooltip_position' => ['type' => 'string', 'length' => 10, 'default' => 'top'],
            'animation_type' => ['type' => 'string', 'length' => 20, 'default' => 'none'],
            'animation_duration' => ['type' => 'integer', 'default' => 300],
            'icon_position' => ['type' => 'string', 'length' => 10, 'default' => 'left'],
            'sort_priority' => ['type' => 'integer', 'default' => 0],
            'visibility_roles' => ['type' => 'string', 'length' => 255],
            'visibility_condition' => ['type' => 'text'],
            'metadata' => ['type' => 'text'],
            'border_style' => ['type' => 'string', 'length' => 20, 'default' => 'none'],
            'border_width' => ['type' => 'string', 'length' => 20, 'default' => '1px'],
            'border_color' => ['type' => 'string', 'length' => 20, 'default' => '#000000'],
            'border_position' => ['type' => 'string', 'length' => 20, 'default' => 'all'],
            'border_radius' => ['type' => 'string', 'length' => 10, 'default' => 'none'],
            'border_radius_size' => ['type' => 'string', 'length' => 20],
        ];

        foreach ($columnsToAdd as $column => $config) {
            if (!isset($schema->columns[$column])) {
                try {
                    $columnSchema = $db->schema->createColumnSchemaBuilder($config['type'], $config['length'] ?? null);
                    if (isset($config['default'])) {
                        $columnSchema->defaultValue($config['default']);
                    }
                    $db->createCommand()->addColumn('master_menu', $column, $columnSchema)->execute();
                } catch (\Exception $e) {
                    // Column may already exist
                }
            }
        }
        
// Refresh schema cache
        $db->schema->refresh();
    }
    
    public function attributes()
    {
        // Get columns from table schema plus our custom properties
        $schema = static::getTableSchema();
        $baseAttrs = $schema ? array_keys($schema->columns) : [];
        
        // Add custom properties that might not be in schema yet
        $customAttrs = [
            'target', 'action_type', 'button_text', 'button_style', 'button_size',
            'button_icon', 'button_full_width', 'css_class', 'css_style', 'custom_html',
            'badge_text', 'badge_style', 'show_tooltip', 'tooltip_position',
            'animation_type', 'animation_duration', 'icon_position', 'sort_priority',
            'visibility_roles', 'visibility_condition', 'metadata',
            'border_style', 'border_width', 'border_color', 'border_position',
            'border_radius', 'border_radius_size'
        ];
        
        return array_unique(array_merge($baseAttrs, $customAttrs));
    }

    public function fields()
    {
        return [
            'id',
            'parent_id',
            'page_id',
            'form_id',
            'name',
            'type',
            'route',
            'menu_key',
            'sort_order',
            'is_active',
            'created_at',
            'updated_at',
            'target',
            'action_type',
            'button_text',
            'button_style',
            'button_size',
            'button_icon',
            'button_full_width',
            'css_class',
            'css_style',
            'custom_html',
            'badge_text',
            'badge_style',
            'show_tooltip',
            'tooltip_position',
            'animation_type',
            'animation_duration',
            'metadata',
            'icon_position',
            'sort_priority',
            'visibility_roles',
            'visibility_condition',
            
            // Border properties
            'border_style',
            'border_width',
            'border_color',
            'border_position',
            'border_radius',
            'border_radius_size',
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
            return null;
        }
    }

public function __set($name, $value)
    {
        // Check if this is a valid database column
        $schema = static::getTableSchema();
        if ($schema && isset($schema->columns[$name])) {
            parent::__set($name, $value);
        }
        // Silently ignore non-column properties
    }
    
    public function __isset($name)
    {
        if (parent::__isset($name)) {
            return true;
        }
        // Check if attribute exists in table schema
        $schema = static::getTableSchema();
        if ($schema && isset($schema->columns[$name])) {
            return true;
        }
        return false;
    }

    public function rules()
    {
        return [
            [['name'], 'required'],
            [['parent_id', 'page_id', 'form_id', 'sort_order', 'is_active', 'sort_priority', 'animation_duration'], 'integer'],
            [['name'], 'string', 'max' => 100],
            [['icon'], 'string', 'max' => 50],
            [['route'], 'string', 'max' => 255],
            [['menu_key'], 'string', 'max' => 50],
            [['type'], 'string', 'max' => 20],
            
            // New string fields validation
            [['target', 'action_type', 'button_text', 'button_style', 'button_size', 'button_icon', 'css_class', 'css_style', 'custom_html', 'badge_text', 'badge_style', 'show_tooltip', 'tooltip_position', 'animation_type', 'icon_position', 'visibility_roles'], 'string', 'max' => 255],
            [['button_full_width'], 'boolean'],
            [['metadata', 'visibility_condition'], 'string'],
            
            // Type validation
            [['type'], 'in', 'range' => [self::TYPE_GROUP, self::TYPE_PAGE, self::TYPE_ROUTE, self::TYPE_BUTTON, self::TYPE_DIVIDER, self::TYPE_FORM], 'message' => 'Pilih tipe menu yang valid'],
            
            // Prevent duplicate page_id usage (page can only be used by one menu)
            ['page_id', 'uniquePageId', 'when' => function($model) {
                return $model->type === self::TYPE_PAGE && !empty($model->page_id);
            }],
            
            // Prevent duplicate form_id usage (form can only be used by one menu)
            ['form_id', 'uniqueFormId', 'when' => function($model) {
                return $model->type === self::TYPE_FORM && !empty($model->form_id);
            }],
            
            // Prevent duplicate route usage (route can only be used by one menu)
            ['route', 'uniqueRoute', 'when' => function($model) {
                return $model->type === self::TYPE_ROUTE && !empty($model->route);
            }],
            
            // Target validation
            [['target'], 'in', 'range' => [self::TARGET_SELF, self::TARGET_BLANK, self::TARGET_MODAL, self::TARGET_AJAX, self::TARGET_POPUP]],
            
            // Action type validation
            [['action_type'], 'in', 'range' => [self::ACTION_LINK, self::ACTION_MODAL, self::ACTION_AJAX, self::ACTION_FORM_SUBMIT, self::ACTION_DOWNLOAD, self::ACTION_JAVASCRIPT]],
            
            // Button style validation
            [['button_style'], 'in', 'range' => [self::BUTTON_STYLE_PRIMARY, self::BUTTON_STYLE_SECONDARY, self::BUTTON_STYLE_SUCCESS, self::BUTTON_STYLE_DANGER, self::BUTTON_STYLE_WARNING, self::BUTTON_STYLE_INFO, self::BUTTON_STYLE_LINK, self::BUTTON_STYLE_OUTLINE_PRIMARY, self::BUTTON_STYLE_OUTLINE_SECONDARY]],
            
            // Button size validation
            [['button_size'], 'in', 'range' => [self::BUTTON_SIZE_SM, self::BUTTON_SIZE_MD, self::BUTTON_SIZE_LG, self::BUTTON_SIZE_BLOCK]],
            
            // Icon position validation
            [['icon_position'], 'in', 'range' => [self::ICON_POSITION_LEFT, self::ICON_POSITION_RIGHT, self::ICON_POSITION_TOP]],
            
            // Animation type validation
            [['animation_type'], 'in', 'range' => [self::ANIMATION_NONE, self::ANIMATION_FADE, self::ANIMATION_SLIDE, self::ANIMATION_BOUNCE, self::ANIMATION_PULSE, self::ANIMATION_ZOOM]],
            
            // Border style validation
            [['border_style'], 'in', 'range' => [self::BORDER_STYLE_NONE, self::BORDER_STYLE_SOLID, self::BORDER_STYLE_DASHED, self::BORDER_STYLE_DOTTED, self::BORDER_STYLE_DOUBLE, self::BORDER_STYLE_GROOVE, self::BORDER_STYLE_RIDGE, self::BORDER_STYLE_INSET, self::BORDER_STYLE_OUTSET]],
            
            // Border position validation
            [['border_position'], 'in', 'range' => [self::BORDER_POSITION_ALL, self::BORDER_POSITION_TOP, self::BORDER_POSITION_RIGHT, self::BORDER_POSITION_BOTTOM, self::BORDER_POSITION_LEFT, self::BORDER_POSITION_TOP_BOTTOM, self::BORDER_POSITION_LEFT_RIGHT]],
            
            // Border radius validation
            [['border_radius'], 'in', 'range' => [self::BORDER_RADIUS_NONE, self::BORDER_RADIUS_SM, self::BORDER_RADIUS_MD, self::BORDER_RADIUS_LG, self::BORDER_RADIUS_XL, self::BORDER_RADIUS_CIRCLE, self::BORDER_RADIUS_PILL]],
            
            // Border string fields
            [['border_width', 'border_color', 'border_radius_size'], 'string', 'max' => 50],
            
            // Custom validation for type-specific requirements
            ['page_id', 'required', 'when' => function($model) {
                return $model->type === self::TYPE_PAGE;
            }, 'message' => 'Menu tipe Page wajib memilih Halaman'],
            
            ['route', 'required', 'when' => function($model) {
                return $model->type === self::TYPE_ROUTE;
            }, 'message' => 'Menu tipe Route wajib isi URL'],
            
            // Button type requires route or page
            ['route', 'required', 'when' => function($model) {
                return $model->type === self::TYPE_BUTTON;
            }, 'message' => 'Button wajib memiliki URL/Route'],
            
            ['form_id', 'safe'],
            
            // Page type should NOT have route
            ['route', 'validateRouteForPage', 'when' => function($model) {
                return $model->type === self::TYPE_PAGE;
            }],
            
            ['form_id', 'validateFormType', 'when' => function($model) {
                return $model->type === self::TYPE_FORM;
            }],
            
            ['parent_id', 'exist', 'skipOnError' => true, 'targetClass' => MasterMenu::class, 'targetAttribute' => ['parent_id' => 'id']],
            ['page_id', 'exist', 'skipOnError' => true, 'targetClass' => MasterPage::class, 'targetAttribute' => ['page_id' => 'id']],
            ['form_id', 'exist', 'skipOnError' => true, 'targetClass' => MasterForm::class, 'targetAttribute' => ['form_id' => 'id']],
        ];
    }

    public function validateRouteForPage($attribute, $params)
    {
        if ($this->type === self::TYPE_PAGE && !empty($this->route)) {
            $this->addError($attribute, 'Menu tipe Page tidak boleh menggunakan Route');
        }
    }
    
    public function validateFormType($attribute, $params)
    {
        $post = Yii::$app->request->post('MasterMenu', []);
        $postedType = trim((string)($post['type'] ?? $this->type ?? ''));
        $isFormSubmission = $postedType === self::TYPE_FORM || $this->type === self::TYPE_FORM;

        // Only enforce form selection for explicit "Type = Form" submissions.
        if ($isFormSubmission && empty($this->form_id)) {
            $this->addError('form_id', 'Menu tipe Form wajib memilih Formulir');
        }
    }
    
    public function uniquePageId($attribute, $params)
    {
        if (empty($this->page_id)) return;
        
        $query = self::find()
            ->where(['page_id' => $this->page_id, 'type' => self::TYPE_PAGE, 'is_active' => 1]);
        
        if (isset($this->id) && $this->id > 0) {
            $query->andWhere(['!=', 'id', $this->id]);
        }
        
        $existing = $query->one();
            
        if ($existing) {
            $this->addError($attribute, "Halaman ini sudah digunakan oleh menu '{$existing->name}'. Setiap halaman hanya bisa dipakai satu menu.");
        }
    }
    
    public function uniqueFormId($attribute, $params)
    {
        if (empty($this->form_id)) return;
        
        $query = self::find()
            ->where(['form_id' => $this->form_id, 'type' => self::TYPE_FORM, 'is_active' => 1]);
        
        if (isset($this->id) && $this->id > 0) {
            $query->andWhere(['!=', 'id', $this->id]);
        }
        
        $existing = $query->one();
            
        if ($existing) {
            $this->addError($attribute, "Formulir ini sudah digunakan oleh menu '{$existing->name}'. Setiap formulir hanya bisa dipakai satu menu.");
        }
    }
    
    public function uniqueRoute($attribute, $params)
    {
        if (empty($this->route)) return;
        
        $normalizedRoute = '/' . ltrim($this->route, '/');
        
        $query = self::find()
            ->where(['type' => self::TYPE_ROUTE, 'is_active' => 1]);
        
        if (isset($this->id) && $this->id > 0) {
            $query->andWhere(['!=', 'id', $this->id]);
        }
        
        $existingMenus = $query->all();
            
        foreach ($existingMenus as $menu) {
            if (!empty($menu->route)) {
                $existingRoute = '/' . ltrim($menu->route, '/');
                if ($normalizedRoute === $existingRoute) {
                    $this->addError($attribute, "Route '{$this->route}' sudah digunakan oleh menu '{$menu->name}'. Gunakan route yang berbeda.");
                    return;
                }
            }
        }
    }

    public function beforeValidate()
    {
        if (!parent::beforeValidate()) {
            return false;
        }

        self::ensureColumnsExist();
        $this->normalizeTypeRelations();

        return true;
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'parent_id' => 'Parent Menu',
            'type' => 'Tipe',
            'page_id' => 'Halaman',
            'form_id' => 'Form',
            'name' => 'Nama Menu',
            'icon' => 'Icon',
            'route' => 'Route (URL)',
            'menu_key' => 'Menu Key',
            'sort_order' => 'Urutan',
            'order' => 'Order',
            'is_active' => 'Status Aktif',
            'created_at' => 'Dibuat',
            'updated_at' => 'Diupdate',
            
            // Navigation Properties
            'target' => 'Target Window',
            'action_type' => 'Action Type',
            'redirect_url' => 'Redirect URL',
            
            // Button UI Properties
            'button_text' => 'Button Text',
            'button_style' => 'Button Style',
            'button_size' => 'Button Size',
            'button_icon' => 'Button Icon',
            'button_full_width' => 'Full Width',
            
            // Styling Properties
            'css_class' => 'Custom CSS Class',
            'css_style' => 'Custom CSS Style',
            'custom_html' => 'Custom HTML',
            'badge_text' => 'Badge Text',
            'badge_style' => 'Badge Style',
            
            // Visibility & Access
            'show_tooltip' => 'Tooltip Text',
            'tooltip_position' => 'Tooltip Position',
            'visibility_roles' => 'Visibility Roles',
            'visibility_condition' => 'Visibility Condition (JSON)',
            
            // Animation & Effects
            'animation_type' => 'Animation Type',
            'animation_duration' => 'Animation Duration (ms)',
            'metadata' => 'Metadata (JSON)',
            
            // Advanced
            'icon_position' => 'Icon Position',
            'sort_priority' => 'Sort Priority',
            
            // Border Properties
            'border_style' => 'Border Style',
            'border_width' => 'Border Width',
            'border_color' => 'Border Color',
            'border_position' => 'Border Position',
            'border_radius' => 'Border Radius',
            'border_radius_size' => 'Border Radius Size',
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
        self::ensureColumnsExist();
        $this->normalizeTypeRelations();

        if (empty($this->menu_key)) {
            $seedValue = !empty($this->route) ? $this->route : ($this->name ?? ('menu-' . ($this->id ?? time())));
            $this->menu_key = Inflector::slug((string)$seedValue, '-');
        }

        if ($insert) {
            $this->created_at = date('Y-m-d H:i:s');
            $this->is_active = $this->is_active ?? self::STATUS_ACTIVE;
            $this->sort_order = $this->sort_order ?? (self::find()->max('[[sort_order]]') + 1);
        }
        $this->updated_at = date('Y-m-d H:i:s');
        return parent::beforeSave($insert);
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        try {
            $registry = new ProjectPermissionRegistry();
            $registry->syncMenuPermissions($this);
        } catch (\Throwable $e) {
            Yii::warning('Failed to sync menu permissions: ' . $e->getMessage(), 'permission-registry');
        }
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
    
    public function isForm(): bool
    {
        return $this->type === self::TYPE_FORM;
    }
    
    public function getUrl()
    {
        if (!empty($this->form_id) && ($this->type === self::TYPE_FORM || ($this->type === self::TYPE_PAGE && empty($this->page_id)))) {
            return \yii\helpers\Url::to(['/master-form/preview', 'id' => $this->form_id]);
        }
        if ($this->type === self::TYPE_ROUTE && !empty($this->route)) {
            return $this->route[0] === '/' ? $this->route : '/' . ltrim($this->route, '/');
        }
        if ($this->isDashboardMenu()) {
            return \yii\helpers\Url::to(['/dashboard']);
        }
        if ($this->type === self::TYPE_PAGE && $this->page_id) {
            return \yii\helpers\Url::to(['/page/view', 'id' => $this->page_id]);
        }
        if ($this->type === self::TYPE_FORM && $this->form_id) {
            return \yii\helpers\Url::to(['/master-form/preview', 'id' => $this->form_id]);
        }
        return null;
    }

    public function isButton(): bool
    {
        return $this->type === self::TYPE_BUTTON;
    }

    public function isDivider(): bool
    {
        return $this->type === self::TYPE_DIVIDER;
    }

    public function getTarget(): string
    {
        return $this->target ?? self::TARGET_SELF;
    }

    public function getActionType(): string
    {
        return $this->action_type ?? self::ACTION_LINK;
    }

    public function getButtonText(): string
    {
        return $this->button_text ?? $this->name;
    }

    public function getButtonClass(): string
    {
        $classes = ['btn'];
        
        $style = $this->button_style ?? self::BUTTON_STYLE_PRIMARY;
        $classes[] = 'btn-' . $style;
        
        $size = $this->button_size ?? self::BUTTON_SIZE_MD;
        if ($size !== self::BUTTON_SIZE_MD) {
            $classes[] = 'btn-' . $size;
        }
        
        if ($this->button_full_width) {
            $classes[] = 'w-100';
        }
        
        if (!empty($this->css_class)) {
            $classes[] = $this->css_class;
        }
        
        return implode(' ', $classes);
    }

    public function getAnimationClass(): string
    {
        $animation = $this->animation_type ?? self::ANIMATION_NONE;
        if ($animation === self::ANIMATION_NONE) {
            return '';
        }
        
        $duration = $this->animation_duration ?? 300;
        return "animate__animated animate__{$animation}" . ($duration ? " animate__duration-{$duration}" : "");
    }

    public function getTooltipConfig(): ?array
    {
        if (empty($this->show_tooltip)) {
            return null;
        }
        
        return [
            'text' => $this->show_tooltip,
            'position' => $this->tooltip_position ?? 'top',
        ];
    }

    public function isVisibleForCurrentUser(): bool
    {
        return (new ProjectPermissionService())->canAccessMenu([
            'name' => $this->name,
            'type' => $this->type,
            'route' => $this->route,
            'page_id' => $this->page_id,
            'form_id' => $this->form_id,
            'menu_key' => $this->menu_key,
            'visibility_roles' => $this->visibility_roles,
        ]);
    }

    public function getVisibilityCondition(): ?array
    {
        if (empty($this->visibility_condition)) {
            return null;
        }
        
        try {
            return json_decode($this->visibility_condition, true);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getMetadata(): ?array
    {
        if (empty($this->metadata)) {
            return null;
        }
        
        try {
            return json_decode($this->metadata, true);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getIconPosition(): string
    {
        return $this->icon_position ?? self::ICON_POSITION_LEFT;
    }

    public function getRenderConfig(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'url' => $this->getUrl(),
            'icon' => $this->icon,
            'icon_position' => $this->getIconPosition(),
            'target' => $this->getTarget(),
            'action_type' => $this->getActionType(),
            'is_button' => $this->isButton(),
            'is_divider' => $this->isDivider(),
            'is_group' => $this->isGroup(),
            
            // Button specific
            'button_text' => $this->getButtonText(),
            'button_class' => $this->getButtonClass(),
            'button_style' => $this->button_style,
            'button_size' => $this->button_size,
            'button_icon' => $this->button_icon,
            
            // Styling
            'css_class' => $this->css_class,
            'css_style' => $this->css_style,
            'custom_html' => $this->custom_html,
            
            // Badge
            'badge_text' => $this->badge_text,
            'badge_style' => $this->badge_style,
            
            // Tooltip
            'tooltip' => $this->getTooltipConfig(),
            
            // Animation
            'animation_class' => $this->getAnimationClass(),
            'animation_type' => $this->animation_type,
            'animation_duration' => $this->animation_duration,
            
            // Visibility
            'is_visible' => $this->isVisibleForCurrentUser(),
            'visibility_condition' => $this->getVisibilityCondition(),
            'visibility_roles' => $this->visibility_roles,
            
            // Metadata
            'metadata' => $this->getMetadata(),
            
            // Sort
            'sort_priority' => $this->sort_priority ?? 0,
            
            // Border
            'border_style' => $this->border_style,
            'border_width' => $this->border_width,
            'border_color' => $this->border_color,
            'border_position' => $this->border_position ?? 'all',
            'border_radius' => $this->border_radius,
            'border_radius_size' => $this->border_radius_size,
            'border_css' => $this->getBorderCss(),
        ];
    }

    public function getBorderCss(): string
    {
        $css = '';
        
        $style = $this->border_style ?? 'none';
        if ($style !== 'none') {
            $width = $this->border_width ?? '1px';
            $color = $this->border_color ?? '#000000';
            $position = $this->border_position ?? 'all';
            
            $borderProp = $this->buildBorderProperty($position, $style, $width, $color);
            $css .= 'border:' . $borderProp . ';';
        }
        
        $radius = $this->border_radius;
        if ($radius && $radius !== 'none') {
            $radiusValue = $this->getBorderRadiusValue($radius);
            $customSize = $this->border_radius_size;
            
            if (!empty($customSize)) {
                $css .= 'border-radius:' . $customSize . ';';
            } else {
                $css .= 'border-radius:' . $radiusValue . ';';
            }
        }
        
        return $css;
    }

    private function buildBorderProperty($position, $style, $width, $color): string
    {
        switch ($position) {
            case 'top':
                return $width . ' ' . $style . ' ' . $color . ' !important; border-top:' . $width . ' ' . $style . ' ' . $color;
            case 'right':
                return $width . ' ' . $style . ' ' . $color . ' !important; border-right:' . $width . ' ' . $style . ' ' . $color;
            case 'bottom':
                return $width . ' ' . $style . ' ' . $color . ' !important; border-bottom:' . $width . ' ' . $style . ' ' . $color;
            case 'left':
                return $width . ' ' . $style . ' ' . $color . ' !important; border-left:' . $width . ' ' . $style . ' ' . $color;
            case 'top-bottom':
                return $width . ' ' . $style . ' ' . $color . ' !important; border-top:' . $width . ' ' . $style . ' ' . $color . '; border-bottom:' . $width . ' ' . $style . ' ' . $color;
            case 'left-right':
                return $width . ' ' . $style . ' ' . $color . ' !important; border-left:' . $width . ' ' . $style . ' ' . $color . '; border-right:' . $width . ' ' . $style . ' ' . $color;
            default:
                return $width . ' ' . $style . ' ' . $color;
        }
    }

    private function getBorderRadiusValue($radius): string
    {
        $values = [
            'none' => '0',
            'sm' => '2px',
            'md' => '4px',
            'lg' => '8px',
            'xl' => '12px',
            'circle' => '50%',
            'pill' => '9999px',
        ];
        
        return $values[$radius] ?? '4px';
    }

    public function hasBorder(): bool
    {
        return !empty($this->border_style) && $this->border_style !== 'none';
    }

    public function hasBorderRadius(): bool
    {
        return !empty($this->border_radius) && $this->border_radius !== 'none';
    }
    
    public function hasChildren(): bool
    {
        return self::find()->where(['parent_id' => $this->id])->count() > 0;
    }

    public static function getMenuTree($activeOnly = true)
    {
        self::ensureColumnsExist();
        $items = self::find()->orderBy(['sort_order' => SORT_ASC])->all();
        
        if ($activeOnly) {
            $items = array_filter($items, function($item) {
                return (int) $item->is_active === self::STATUS_ACTIVE;
            });
            $items = array_values($items);
        }
        
        $tree = self::buildTree($items);
        return (new ProjectPermissionRegistry())->filterMenuTree($tree);
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
                    'form_id' => $item->form_id,
                    'route' => $item->route,
                    'has_children' => !empty($children),
                    'children' => !empty($children) ? $children : null,
                    
                    // New flexible properties
                    'target' => $item->target ?? '_self',
                    'action_type' => $item->action_type ?? 'link',
                    'button_text' => $item->button_text,
                    'button_style' => $item->button_style,
                    'button_size' => $item->button_size,
                    'button_icon' => $item->button_icon,
                    'button_full_width' => (bool) $item->button_full_width,
                    'css_class' => $item->css_class,
                    'css_style' => $item->css_style,
                    'custom_html' => $item->custom_html,
                    'badge_text' => $item->badge_text,
                    'badge_style' => $item->badge_style,
                    'show_tooltip' => $item->show_tooltip,
                    'tooltip_position' => $item->tooltip_position,
                    'animation_type' => $item->animation_type,
                    'animation_duration' => $item->animation_duration,
                    'icon_position' => $item->icon_position ?? 'left',
                    'sort_priority' => $item->sort_priority ?? 0,
                    'visibility_roles' => $item->visibility_roles,
                    'is_button' => $item->isButton(),
                    'is_divider' => $item->isDivider(),
                    
                    // Border properties
                    'border_style' => $item->border_style,
                    'border_width' => $item->border_width,
                    'border_color' => $item->border_color,
                    'border_position' => $item->border_position ?? 'all',
                    'border_radius' => $item->border_radius,
                    'border_radius_size' => $item->border_radius_size,
                    'border_css' => $item->getBorderCss(),
                ];
                $branch[] = $node;
            }
        }
        
        // Sort by sort_priority then sort_order
        usort($branch, function($a, $b) {
            $priorityA = $a['sort_priority'] ?? 0;
            $priorityB = $b['sort_priority'] ?? 0;
            if ($priorityA !== $priorityB) {
                return $priorityA <=> $priorityB;
            }
            return 0;
        });
        
        return $branch;
    }

    private function isDashboardMenu(): bool
    {
        $menuKey = strtolower(trim((string)$this->menu_key));
        $name = strtolower(trim((string)$this->name));
        $route = strtolower(trim((string)$this->route));

        if ($menuKey === 'dashboard' || $name === 'dashboard') {
            return true;
        }

        return in_array($route, ['/dashboard', 'dashboard', '/site/dashboard', 'site/dashboard'], true);
    }

    public function normalizeTypeRelations(): void
    {
        $this->type = strtolower(trim((string)$this->type));
        $this->parent_id = $this->normalizeRelationId($this->parent_id);
        $this->page_id = $this->normalizeRelationId($this->page_id);
        $this->form_id = $this->normalizeRelationId($this->form_id);
        $this->route = $this->normalizeTextValue($this->route);

        if ($this->type === '') {
            if ($this->route !== null) {
                $this->type = self::TYPE_ROUTE;
            } elseif ($this->form_id !== null) {
                $this->type = self::TYPE_FORM;
            } elseif ($this->page_id !== null) {
                $this->type = self::TYPE_PAGE;
            } else {
                $this->type = self::TYPE_GROUP;
            }
        }

        switch ($this->type) {
            case self::TYPE_PAGE:
                $this->form_id = null;
                $this->route = null;
                break;
            case self::TYPE_FORM:
                $this->page_id = null;
                $this->route = null;
                break;
            case self::TYPE_ROUTE:
                $this->page_id = null;
                $this->form_id = null;
                break;
            case self::TYPE_BUTTON:
                $this->page_id = null;
                $this->form_id = null;
                break;
            case self::TYPE_DIVIDER:
            case self::TYPE_GROUP:
            default:
                $this->page_id = null;
                $this->form_id = null;
                $this->route = null;
                break;
        }
    }

    private function normalizeRelationId($value): ?int
    {
        if ($value === null || $value === '' || $value === false) {
            return null;
        }

        $value = (int)$value;
        return $value > 0 ? $value : null;
    }

    private function normalizeTextValue($value): ?string
    {
        $value = trim((string)$value);
        return $value !== '' ? $value : null;
    }
}
