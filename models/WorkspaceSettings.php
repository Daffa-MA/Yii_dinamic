<?php

namespace app\models;

use Yii;
use yii\db\Connection;
use yii\db\Query;

class WorkspaceSettings extends \yii\base\Model
{
    public const DB_TABLE = 'workspace_settings';
    public const DEFAULT_KEY = 'default';
    private const SESSION_KEY_PREFIX = 'workspace_settings';
    private const PROJECT_ROUTE_DEFAULTS = ['project/index', 'project-list/index'];

    public $id;
    public $setting_key = self::DEFAULT_KEY;
    public $workspace_title = 'Projects';
    public $workspace_subtitle = 'Beranda & navigasi';
    public $workspace_badge = 'Workspace';
    public $workspace_logo_icon = 'folder_open';
    public $workspace_logo_bg = '#4f46e5';
    public $workspace_logo_image = null;
    public $workspace_logo_width = 120;
    public $workspace_logo_height = 120;
    public $login_title = 'Login Aplikasi';
    public $login_subtitle = 'Masuk ke aplikasi Anda';
    public $login_background_start = '#07111f';
    public $login_background_end = '#111827';
    public $login_background_image = null;
    public $login_background_upload = null;
    public $login_button_color = '#2563eb';
    public $login_card_color = 'rgba(255, 255, 255, 0.96)';
    public $login_text_color = '#0f172a';
    public $login_accent_color = '#4f46e5';
    public $login_border_radius = 28;
    public $login_theme = 'dark';

    public $sidebar_bg_start = '#07111f';
    public $sidebar_bg_end = '#111827';
    public $sidebar_border_color = 'rgba(148, 163, 184, 0.16)';
    public $sidebar_text_color = '#e2e8f0';
    public $sidebar_text_muted = '#94a3b8';
    public $sidebar_icon_bg = 'rgba(255, 255, 255, 0.05)';

    public $sidebar_active_bg_start = '#2563eb';
    public $sidebar_active_bg_end = '#06b6d4';
    public $sidebar_active_text = '#ffffff';
    public $sidebar_active_icon_bg = 'rgba(255, 255, 255, 0.2)';
    public $sidebar_active_shadow = '0 8px 24px rgba(37, 99, 235, 0.28)';

    public $sidebar_hover_bg = 'rgba(255, 255, 255, 0.08)';
    public $sidebar_hover_text = '#ffffff';

    public $topnav_bg = '#ffffff';
    public $topnav_border_color = '#e2e8f0';
    public $topnav_text_color = '#1e293b';

    public $light_sidebar_bg = '#f8fafc';
    public $light_sidebar_border = 'rgba(148, 163, 184, 0.2)';
    public $light_sidebar_text = '#475569';

    public $footer_bg = 'rgba(15, 23, 42, 0.22)';
    public $footer_text = '#cbd5e1';
    public $footer_logout_bg = 'rgba(248, 250, 252, 0.8)';
    public $footer_logout_hover_bg = '#fee2e2';

    private static $defaults = null;

    public static function getDefaults()
    {
        if (self::$defaults === null) {
            self::$defaults = [
                'workspace_title' => 'Projects',
                'workspace_subtitle' => 'Beranda & navigasi',
                'workspace_badge' => 'Workspace',
                'workspace_logo_icon' => 'folder_open',
                'workspace_logo_bg' => '#4f46e5',
                'workspace_logo_image' => null,
                'workspace_logo_width' => 120,
                'workspace_logo_height' => 120,
                'login_title' => 'Login Aplikasi',
                'login_subtitle' => 'Masuk ke aplikasi Anda',
                'login_background_start' => '#07111f',
                'login_background_end' => '#111827',
                'login_background_image' => null,
                'login_button_color' => '#2563eb',
                'login_card_color' => 'rgba(255, 255, 255, 0.96)',
                'login_text_color' => '#0f172a',
                'login_accent_color' => '#4f46e5',
                'login_border_radius' => 28,
                'login_theme' => 'dark',
                'sidebar_bg_start' => '#f8fafc',
                'sidebar_bg_end' => '#f1f5f9',
                'sidebar_border_color' => 'rgba(148, 163, 184, 0.16)',
                'sidebar_text_color' => '#475569',
                'sidebar_text_muted' => '#64748b',
                'sidebar_icon_bg' => 'rgba(99, 102, 241, 0.08)',
                'sidebar_active_bg_start' => '#4f46e5',
                'sidebar_active_bg_end' => '#6366f1',
                'sidebar_active_text' => '#ffffff',
                'sidebar_active_icon_bg' => 'rgba(255, 255, 255, 0.25)',
                'sidebar_active_shadow' => '0 8px 24px rgba(79, 70, 229, 0.28)',
                'sidebar_hover_bg' => 'rgba(99, 102, 241, 0.08)',
                'sidebar_hover_text' => '#1e293b',
                'topnav_bg' => '#ffffff',
                'topnav_border_color' => '#e2e8f0',
                'topnav_text_color' => '#0f172a',
                'light_sidebar_bg' => '#f8fafc',
                'light_sidebar_border' => 'rgba(148, 163, 184, 0.2)',
                'light_sidebar_text' => '#475569',
                'footer_bg' => 'rgba(248, 250, 252, 0.8)',
                'footer_text' => '#64748b',
                'footer_logout_bg' => 'rgba(248, 250, 252, 0.8)',
                'footer_logout_hover_bg' => '#fee2e2',
            ];
        }

        return self::$defaults;
    }

    public function rules()
    {
        return [
            [['workspace_logo_width', 'workspace_logo_height'], 'integer', 'min' => 40, 'max' => 300],
            [['login_border_radius'], 'integer', 'min' => 0, 'max' => 64],
            [['login_theme'], 'in', 'range' => ['light', 'dark']],
            [['workspace_title', 'workspace_subtitle', 'workspace_badge', 'workspace_logo_icon', 'workspace_logo_bg', 'login_title', 'login_subtitle'], 'string', 'max' => 255],
            [['workspace_logo_image'], 'string', 'max' => 500],
            [['workspace_logo_bg', 'sidebar_bg_start', 'sidebar_bg_end', 'sidebar_border_color', 'sidebar_text_color', 'sidebar_text_muted', 'login_background_start', 'login_background_end', 'login_button_color', 'login_text_color', 'login_accent_color'], 'string', 'max' => 100],
            [['sidebar_hover_bg', 'sidebar_hover_text', 'sidebar_active_bg_start', 'sidebar_active_bg_end', 'sidebar_active_text', 'sidebar_active_shadow'], 'string', 'max' => 100],
            [['topnav_bg', 'topnav_border_color', 'topnav_text_color', 'light_sidebar_bg', 'light_sidebar_border'], 'string', 'max' => 100],
            [['login_background_image'], 'string', 'max' => 500],
            [['login_background_upload'], 'file', 'skipOnEmpty' => true, 'extensions' => ['jpg', 'jpeg', 'png', 'webp', 'gif', 'mp4', 'webm', 'ogg'], 'checkExtensionByMimeType' => false, 'maxSize' => 20 * 1024 * 1024],
            [['login_card_color'], 'string', 'max' => 100],
        ];
    }

    public function attributeLabels()
    {
        return [
            'workspace_title' => 'Judul Workspace',
            'workspace_subtitle' => 'Subtitle Workspace',
            'workspace_badge' => 'Badge Workspace',
            'workspace_logo_width' => 'Logo Width',
            'workspace_logo_height' => 'Logo Height',
            'login_title' => 'Login Title',
            'login_subtitle' => 'Login Subtitle',
            'login_background_start' => 'Login Background Start',
            'login_background_end' => 'Login Background End',
            'login_background_image' => 'Login Background Image',
            'login_background_upload' => 'Upload Login Background',
            'login_button_color' => 'Login Button Color',
            'login_card_color' => 'Login Card Color',
            'login_text_color' => 'Login Text Color',
            'login_accent_color' => 'Login Accent Color',
            'login_border_radius' => 'Login Border Radius',
            'login_theme' => 'Login Theme',

            'sidebar_bg_start' => 'Sidebar Background Start',
            'sidebar_bg_end' => 'Sidebar Background End',
            'sidebar_border_color' => 'Sidebar Border Color',
            'sidebar_text_color' => 'Sidebar Text Color',
            'sidebar_text_muted' => 'Sidebar Muted Text',

            'sidebar_active_bg_start' => 'Active Background Start',
            'sidebar_active_bg_end' => 'Active Background End',
            'sidebar_active_text' => 'Active Text Color',
            'sidebar_active_shadow' => 'Active Shadow',

            'sidebar_hover_bg' => 'Hover Background',
            'sidebar_hover_text' => 'Hover Text Color',

            'topnav_bg' => 'Top Nav Background',
            'topnav_border_color' => 'Top Nav Border',
            'topnav_text_color' => 'Top Nav Text',

            'light_sidebar_bg' => 'Light Theme Sidebar BG',
            'light_sidebar_border' => 'Light Theme Border',

            'footer_bg' => 'Footer Background',
            'footer_text' => 'Footer Text',
        ];
    }

    public function loadFromDatabase($key = null)
    {
        if ($this->isProjectListRoute()) {
            $this->clear();
            $this->setting_key = self::DEFAULT_KEY;
            return false;
        }

        $scopeKey = $this->resolveScopeKey($key);
        $this->ensureStorageReady();

        $row = $this->findRowByKey($scopeKey);
        if ($row === null && $scopeKey !== self::DEFAULT_KEY) {
            $row = $this->findRowByKey(self::DEFAULT_KEY);
        }

        if ($row !== null) {
            Yii::info('Loading workspace settings from database scope: ' . $scopeKey, 'workspace-settings');
            $this->load($row, '');
            $this->populateDefaults();
            $this->setting_key = $scopeKey;
            return true;
        }

        $this->clear();
        $this->setting_key = $scopeKey;
        Yii::info('No workspace settings row found for scope: ' . $scopeKey . ', using defaults', 'workspace-settings');
        return false;
    }

    public function loadFromSession()
    {
        if ($this->isProjectListRoute()) {
            $this->clear();
            $this->setting_key = self::DEFAULT_KEY;
            return;
        }

        $scopeKey = $this->resolveScopeKey();
        $sessionKey = $this->getSessionKey($scopeKey);
        $data = Yii::$app->session->get($sessionKey, []);

        if (!empty($data)) {
            Yii::info('Loading workspace settings from session scope: ' . $scopeKey, 'workspace-settings');
            $this->load($data, '');
            $this->populateDefaults();
            $this->setting_key = $scopeKey;
            return;
        }

        $this->loadFromDatabase($scopeKey);
        $this->saveToSession($scopeKey);
        Yii::info('No workspace settings in session for scope: ' . $scopeKey . ', loaded from database', 'workspace-settings');
    }

    public function saveToDatabase($key = null)
    {
        if ($this->isProjectListRoute()) {
            return true;
        }

        $scopeKey = $this->resolveScopeKey($key);
        $this->ensureStorageReady();

        try {
            $exists = (new Query())
                ->select('id')
                ->from(self::DB_TABLE)
                ->where(['setting_key' => $scopeKey])
                ->exists();

            $data = $this->toStorageData($scopeKey);

            if ($exists) {
                Yii::$app->db->createCommand()
                    ->update(self::DB_TABLE, $data, ['setting_key' => $scopeKey])
                    ->execute();
            } else {
                $data['created_at'] = date('Y-m-d H:i:s');
                Yii::$app->db->createCommand()
                    ->insert(self::DB_TABLE, $data)
                    ->execute();
            }

            Yii::info('Workspace settings saved to database scope: ' . $scopeKey, 'workspace-settings');
            return true;
        } catch (\Exception $e) {
            Yii::error('Failed to save workspace settings to database: ' . $e->getMessage(), 'workspace-settings');
            return false;
        }
    }

    public function saveToSession($key = null)
    {
        if ($this->isProjectListRoute()) {
            return;
        }

        $scopeKey = $this->resolveScopeKey($key);
        Yii::$app->session->set($this->getSessionKey($scopeKey), $this->toSessionData());
        Yii::info('Workspace settings saved to session scope: ' . $scopeKey, 'workspace-settings');
    }

    public function save()
    {
        if ($this->isProjectListRoute()) {
            return true;
        }

        $scopeKey = $this->resolveScopeKey();
        $dbResult = $this->saveToDatabase($scopeKey);
        $this->saveToSession($scopeKey);

        if ($dbResult) {
            Yii::info('Workspace settings saved successfully to database and session for scope: ' . $scopeKey, 'workspace-settings');
        }

        return true;
    }

    public function reset($key = null)
    {
        if ($this->isProjectListRoute()) {
            $this->clear();
            $this->setting_key = self::DEFAULT_KEY;
            return true;
        }

        $scopeKey = $this->resolveScopeKey($key);

        try {
            Yii::$app->db->createCommand()
                ->delete(self::DB_TABLE, ['setting_key' => $scopeKey])
                ->execute();
        } catch (\Exception $e) {
            Yii::warning('Failed to delete workspace settings from database: ' . $e->getMessage(), 'workspace-settings');
        }

        Yii::$app->session->remove($this->getSessionKey($scopeKey));
        $this->loadFromDatabase($scopeKey);
        $this->saveToSession($scopeKey);

        return true;
    }

    public function clear()
    {
        $defaults = self::getDefaults();
        foreach ($defaults as $key => $value) {
            $this->$key = $value;
        }
    }

    public function getCssVars()
    {
        return [
            'workspace-title' => $this->workspace_title,
            'workspace-subtitle' => $this->workspace_subtitle,
            'workspace-badge' => $this->workspace_badge,
            'workspace-logo-icon' => $this->workspace_logo_icon,
            'workspace-logo-bg' => $this->workspace_logo_bg,
            'workspace-logo-image' => $this->workspace_logo_image,
            'workspace-logo-width' => $this->workspace_logo_width,
            'workspace-logo-height' => $this->workspace_logo_height,

            'sidebar-bg-start' => $this->sidebar_bg_start,
            'sidebar-bg-end' => $this->sidebar_bg_end,
            'sidebar-border-color' => $this->sidebar_border_color,
            'sidebar-text-color' => $this->sidebar_text_color,
            'sidebar-text-muted' => $this->sidebar_text_muted,

            'sidebar-active-bg-start' => $this->sidebar_active_bg_start,
            'sidebar-active-bg-end' => $this->sidebar_active_bg_end,
            'sidebar-active-text' => $this->sidebar_active_text,
            'sidebar-active-shadow' => $this->sidebar_active_shadow,

            'sidebar-hover-bg' => $this->sidebar_hover_bg,
            'sidebar-hover-text' => $this->sidebar_hover_text,

            'topnav-bg' => $this->topnav_bg,
            'topnav-border-color' => $this->topnav_border_color,
            'topnav-text-color' => $this->topnav_text_color,

            'light-sidebar-bg' => $this->light_sidebar_bg,
            'light-sidebar-border' => $this->light_sidebar_border,

            'footer-bg' => $this->footer_bg,
            'footer-text' => $this->footer_text,
        ];
    }

    public function getLoginBackgroundAsset(): array
    {
        $value = trim((string)($this->login_background_image ?? ''));
        if ($value === '') {
            return [
                'url' => '',
                'type' => 'none',
                'is_remote' => false,
            ];
        }

        $isRemote = (bool)preg_match('#^https?://#i', $value);
        $url = $isRemote ? $value : Yii::getAlias('@web/uploads/workspace/') . ltrim($value, '/');
        $path = parse_url($value, PHP_URL_PATH);
        $source = is_string($path) && $path !== '' ? $path : $value;
        $ext = strtolower(pathinfo($source, PATHINFO_EXTENSION));
        $type = in_array($ext, ['mp4', 'webm', 'ogg'], true) ? 'video' : (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif'], true) ? 'image' : 'image');

        return [
            'url' => $url,
            'type' => $type,
            'is_remote' => $isRemote,
        ];
    }

    private function resolveScopeKey($key = null): string
    {
        $scopeKey = trim((string)$key);
        if ($scopeKey !== '') {
            return $scopeKey;
        }

        if ($this->isProjectListRoute()) {
            return self::DEFAULT_KEY;
        }

        $projectId = $this->getActiveProjectId();
        if ($projectId !== null) {
            return 'project:' . $projectId;
        }

        $databaseName = $this->resolveCurrentDatabaseName(Yii::$app->db);
        if ($databaseName !== '') {
            return 'database:' . $databaseName;
        }

        return self::DEFAULT_KEY;
    }

    private function getSessionKey(string $scopeKey): string
    {
        return self::SESSION_KEY_PREFIX . ':' . $scopeKey;
    }

    private function toSessionData(): array
    {
        return [
            'workspace_title' => $this->workspace_title,
            'workspace_subtitle' => $this->workspace_subtitle,
            'workspace_badge' => $this->workspace_badge,
            'workspace_logo_icon' => $this->workspace_logo_icon,
            'workspace_logo_bg' => $this->workspace_logo_bg,
            'workspace_logo_image' => $this->workspace_logo_image,
            'workspace_logo_width' => $this->workspace_logo_width,
            'workspace_logo_height' => $this->workspace_logo_height,
            'login_title' => $this->login_title,
            'login_subtitle' => $this->login_subtitle,
            'login_background_start' => $this->login_background_start,
            'login_background_end' => $this->login_background_end,
            'login_background_image' => $this->login_background_image,
            'login_button_color' => $this->login_button_color,
            'login_card_color' => $this->login_card_color,
            'login_text_color' => $this->login_text_color,
            'login_accent_color' => $this->login_accent_color,
            'login_border_radius' => $this->login_border_radius,
            'login_theme' => $this->login_theme,

            'sidebar_bg_start' => $this->sidebar_bg_start,
            'sidebar_bg_end' => $this->sidebar_bg_end,
            'sidebar_border_color' => $this->sidebar_border_color,
            'sidebar_text_color' => $this->sidebar_text_color,
            'sidebar_text_muted' => $this->sidebar_text_muted,

            'sidebar_active_bg_start' => $this->sidebar_active_bg_start,
            'sidebar_active_bg_end' => $this->sidebar_active_bg_end,
            'sidebar_active_text' => $this->sidebar_active_text,
            'sidebar_active_shadow' => $this->sidebar_active_shadow,

            'sidebar_hover_bg' => $this->sidebar_hover_bg,
            'sidebar_hover_text' => $this->sidebar_hover_text,

            'topnav_bg' => $this->topnav_bg,
            'topnav_border_color' => $this->topnav_border_color,
            'topnav_text_color' => $this->topnav_text_color,

            'light_sidebar_bg' => $this->light_sidebar_bg,
            'light_sidebar_border' => $this->light_sidebar_border,

            'footer_bg' => $this->footer_bg,
            'footer_text' => $this->footer_text,
        ];
    }

    private function toStorageData(string $scopeKey): array
    {
        return array_merge($this->toSessionData(), [
            'setting_key' => $scopeKey,
            'sidebar_icon_bg' => $this->sidebar_icon_bg ?? 'rgba(255, 255, 255, 0.05)',
            'sidebar_active_icon_bg' => $this->sidebar_active_icon_bg ?? 'rgba(255, 255, 255, 0.2)',
            'light_sidebar_text' => $this->light_sidebar_text ?? '#475569',
            'footer_logout_bg' => $this->footer_logout_bg ?? 'rgba(248, 250, 252, 0.8)',
            'footer_logout_hover_bg' => $this->footer_logout_hover_bg ?? '#fee2e2',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function findRowByKey(string $key): ?array
    {
        try {
            $row = (new Query())
                ->select('*')
                ->from(self::DB_TABLE)
                ->where(['setting_key' => $key])
                ->one();

            return is_array($row) ? $row : null;
        } catch (\Throwable $e) {
            Yii::warning('Failed to query workspace settings: ' . $e->getMessage(), 'workspace-settings');
            return null;
        }
    }

    private function ensureStorageReady(): void
    {
        $connection = Yii::$app->db;
        if ($connection->getTableSchema(self::DB_TABLE, true) !== null) {
            $this->ensureLoginColumnsExist($connection);
            return;
        }

        $connection->createCommand()->createTable(self::DB_TABLE, [
            'id' => $connection->schema->createColumnSchemaBuilder('pk'),
            'setting_key' => $connection->schema->createColumnSchemaBuilder('string', 100)->notNull()->unique(),
            'workspace_title' => $connection->schema->createColumnSchemaBuilder('string', 255)->defaultValue('Projects'),
            'workspace_subtitle' => $connection->schema->createColumnSchemaBuilder('string', 255)->defaultValue('Beranda & navigasi'),
            'workspace_badge' => $connection->schema->createColumnSchemaBuilder('string', 255)->defaultValue('Workspace'),
            'workspace_logo_icon' => $connection->schema->createColumnSchemaBuilder('string', 100)->defaultValue('folder_open'),
            'workspace_logo_bg' => $connection->schema->createColumnSchemaBuilder('string', 50)->defaultValue('#4f46e5'),
            'workspace_logo_image' => $connection->schema->createColumnSchemaBuilder('string', 500)->defaultValue(null),
            'login_title' => $connection->schema->createColumnSchemaBuilder('string', 255)->defaultValue('Login Aplikasi'),
            'login_subtitle' => $connection->schema->createColumnSchemaBuilder('string', 255)->defaultValue('Masuk ke aplikasi Anda'),
            'login_background_start' => $connection->schema->createColumnSchemaBuilder('string', 50)->defaultValue('#07111f'),
            'login_background_end' => $connection->schema->createColumnSchemaBuilder('string', 50)->defaultValue('#111827'),
            'login_background_image' => $connection->schema->createColumnSchemaBuilder('string', 500)->defaultValue(null),
            'login_button_color' => $connection->schema->createColumnSchemaBuilder('string', 50)->defaultValue('#2563eb'),
            'login_card_color' => $connection->schema->createColumnSchemaBuilder('string', 100)->defaultValue('rgba(255, 255, 255, 0.96)'),
            'login_text_color' => $connection->schema->createColumnSchemaBuilder('string', 50)->defaultValue('#0f172a'),
            'login_accent_color' => $connection->schema->createColumnSchemaBuilder('string', 50)->defaultValue('#4f46e5'),
            'login_border_radius' => $connection->schema->createColumnSchemaBuilder('integer')->defaultValue(28),
            'login_theme' => $connection->schema->createColumnSchemaBuilder('string', 20)->defaultValue('dark'),
            'sidebar_bg_start' => $connection->schema->createColumnSchemaBuilder('string', 50)->defaultValue('#07111f'),
            'sidebar_bg_end' => $connection->schema->createColumnSchemaBuilder('string', 50)->defaultValue('#111827'),
            'sidebar_border_color' => $connection->schema->createColumnSchemaBuilder('string', 100)->defaultValue('rgba(148, 163, 184, 0.16)'),
            'sidebar_text_color' => $connection->schema->createColumnSchemaBuilder('string', 50)->defaultValue('#e2e8f0'),
            'sidebar_text_muted' => $connection->schema->createColumnSchemaBuilder('string', 50)->defaultValue('#94a3b8'),
            'sidebar_icon_bg' => $connection->schema->createColumnSchemaBuilder('string', 100)->defaultValue('rgba(255, 255, 255, 0.05)'),
            'sidebar_active_bg_start' => $connection->schema->createColumnSchemaBuilder('string', 50)->defaultValue('#2563eb'),
            'sidebar_active_bg_end' => $connection->schema->createColumnSchemaBuilder('string', 50)->defaultValue('#06b6d4'),
            'sidebar_active_text' => $connection->schema->createColumnSchemaBuilder('string', 50)->defaultValue('#ffffff'),
            'sidebar_active_icon_bg' => $connection->schema->createColumnSchemaBuilder('string', 100)->defaultValue('rgba(255, 255, 255, 0.2)'),
            'sidebar_active_shadow' => $connection->schema->createColumnSchemaBuilder('string', 200)->defaultValue('0 8px 24px rgba(37, 99, 235, 0.28)'),
            'sidebar_hover_bg' => $connection->schema->createColumnSchemaBuilder('string', 100)->defaultValue('rgba(255, 255, 255, 0.08)'),
            'sidebar_hover_text' => $connection->schema->createColumnSchemaBuilder('string', 50)->defaultValue('#ffffff'),
            'topnav_bg' => $connection->schema->createColumnSchemaBuilder('string', 50)->defaultValue('#ffffff'),
            'topnav_border_color' => $connection->schema->createColumnSchemaBuilder('string', 50)->defaultValue('#e2e8f0'),
            'topnav_text_color' => $connection->schema->createColumnSchemaBuilder('string', 50)->defaultValue('#1e293b'),
            'light_sidebar_bg' => $connection->schema->createColumnSchemaBuilder('string', 50)->defaultValue('#f8fafc'),
            'light_sidebar_border' => $connection->schema->createColumnSchemaBuilder('string', 100)->defaultValue('rgba(148, 163, 184, 0.2)'),
            'light_sidebar_text' => $connection->schema->createColumnSchemaBuilder('string', 50)->defaultValue('#475569'),
            'footer_bg' => $connection->schema->createColumnSchemaBuilder('string', 100)->defaultValue('rgba(15, 23, 42, 0.22)'),
            'footer_text' => $connection->schema->createColumnSchemaBuilder('string', 50)->defaultValue('#cbd5e1'),
            'footer_logout_bg' => $connection->schema->createColumnSchemaBuilder('string', 100)->defaultValue('rgba(248, 250, 252, 0.8)'),
            'footer_logout_hover_bg' => $connection->schema->createColumnSchemaBuilder('string', 50)->defaultValue('#fee2e2'),
            'created_at' => $connection->schema->createColumnSchemaBuilder('timestamp')->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $connection->schema->createColumnSchemaBuilder('timestamp')->defaultExpression('CURRENT_TIMESTAMP'),
        ])->execute();

        $this->ensureLoginColumnsExist($connection);

        $connection->createCommand()->createIndex('idx-workspace_settings-key', self::DB_TABLE, 'setting_key', true)->execute();

        $connection->createCommand()->insert(self::DB_TABLE, [
            'setting_key' => self::DEFAULT_KEY,
            'workspace_title' => self::getDefaults()['workspace_title'],
            'workspace_subtitle' => self::getDefaults()['workspace_subtitle'],
            'workspace_badge' => self::getDefaults()['workspace_badge'],
            'workspace_logo_icon' => self::getDefaults()['workspace_logo_icon'],
            'workspace_logo_bg' => self::getDefaults()['workspace_logo_bg'],
            'workspace_logo_image' => self::getDefaults()['workspace_logo_image'],
            'login_title' => self::getDefaults()['login_title'],
            'login_subtitle' => self::getDefaults()['login_subtitle'],
            'login_background_start' => self::getDefaults()['login_background_start'],
            'login_background_end' => self::getDefaults()['login_background_end'],
            'login_background_image' => self::getDefaults()['login_background_image'],
            'login_button_color' => self::getDefaults()['login_button_color'],
            'login_card_color' => self::getDefaults()['login_card_color'],
            'login_text_color' => self::getDefaults()['login_text_color'],
            'login_accent_color' => self::getDefaults()['login_accent_color'],
            'login_border_radius' => self::getDefaults()['login_border_radius'],
            'login_theme' => self::getDefaults()['login_theme'],
            'sidebar_bg_start' => self::getDefaults()['sidebar_bg_start'],
            'sidebar_bg_end' => self::getDefaults()['sidebar_bg_end'],
            'sidebar_border_color' => self::getDefaults()['sidebar_border_color'],
            'sidebar_text_color' => self::getDefaults()['sidebar_text_color'],
            'sidebar_text_muted' => self::getDefaults()['sidebar_text_muted'],
            'sidebar_icon_bg' => self::getDefaults()['sidebar_icon_bg'],
            'sidebar_active_bg_start' => self::getDefaults()['sidebar_active_bg_start'],
            'sidebar_active_bg_end' => self::getDefaults()['sidebar_active_bg_end'],
            'sidebar_active_text' => self::getDefaults()['sidebar_active_text'],
            'sidebar_active_icon_bg' => self::getDefaults()['sidebar_active_icon_bg'],
            'sidebar_active_shadow' => self::getDefaults()['sidebar_active_shadow'],
            'sidebar_hover_bg' => self::getDefaults()['sidebar_hover_bg'],
            'sidebar_hover_text' => self::getDefaults()['sidebar_hover_text'],
            'topnav_bg' => self::getDefaults()['topnav_bg'],
            'topnav_border_color' => self::getDefaults()['topnav_border_color'],
            'topnav_text_color' => self::getDefaults()['topnav_text_color'],
            'light_sidebar_bg' => self::getDefaults()['light_sidebar_bg'],
            'light_sidebar_border' => self::getDefaults()['light_sidebar_border'],
            'light_sidebar_text' => self::getDefaults()['light_sidebar_text'],
            'footer_bg' => self::getDefaults()['footer_bg'],
            'footer_text' => self::getDefaults()['footer_text'],
            'footer_logout_bg' => self::getDefaults()['footer_logout_bg'],
            'footer_logout_hover_bg' => self::getDefaults()['footer_logout_hover_bg'],
        ])->execute();
    }

    private function ensureLoginColumnsExist(Connection $connection): void
    {
        $schema = $connection->schema->getTableSchema(self::DB_TABLE, true);
        if ($schema === null) {
            return;
        }

        $columns = [
            'login_title' => $connection->schema->createColumnSchemaBuilder('string', 255)->defaultValue('Login Aplikasi'),
            'login_subtitle' => $connection->schema->createColumnSchemaBuilder('string', 255)->defaultValue('Masuk ke aplikasi Anda'),
            'login_background_start' => $connection->schema->createColumnSchemaBuilder('string', 50)->defaultValue('#07111f'),
            'login_background_end' => $connection->schema->createColumnSchemaBuilder('string', 50)->defaultValue('#111827'),
            'login_background_image' => $connection->schema->createColumnSchemaBuilder('string', 500)->defaultValue(null),
            'login_button_color' => $connection->schema->createColumnSchemaBuilder('string', 50)->defaultValue('#2563eb'),
            'login_card_color' => $connection->schema->createColumnSchemaBuilder('string', 100)->defaultValue('rgba(255, 255, 255, 0.96)'),
            'login_text_color' => $connection->schema->createColumnSchemaBuilder('string', 50)->defaultValue('#0f172a'),
            'login_accent_color' => $connection->schema->createColumnSchemaBuilder('string', 50)->defaultValue('#4f46e5'),
            'login_border_radius' => $connection->schema->createColumnSchemaBuilder('integer')->defaultValue(28),
            'login_theme' => $connection->schema->createColumnSchemaBuilder('string', 20)->defaultValue('dark'),
        ];

        foreach ($columns as $columnName => $columnDefinition) {
            if (!isset($schema->columns[$columnName])) {
                $connection->createCommand()->addColumn(self::DB_TABLE, $columnName, $columnDefinition)->execute();
                $connection->schema->refreshTableSchema(self::DB_TABLE);
                $schema = $connection->schema->getTableSchema(self::DB_TABLE, true);
            }
        }
    }

    private function resolveCurrentDatabaseName(Connection $connection): string
    {
        if (preg_match('/dbname=([^;]+)/i', $connection->dsn, $matches) === 1) {
            return trim((string)$matches[1]);
        }

        try {
            return trim((string)$connection->createCommand('SELECT DATABASE()')->queryScalar());
        } catch (\Throwable $e) {
            return '';
        }
    }

    private function getActiveProjectId(): ?int
    {
        if (!class_exists('\app\components\ActiveProjectContext')) {
            return null;
        }

        if (!class_exists('\app\components\ProjectSchema') || !\app\components\ProjectSchema::supportsProjectContext()) {
            return null;
        }

        $projectId = (new \app\components\ActiveProjectContext())->getActiveProjectId();
        return $projectId !== null && $projectId > 0 ? $projectId : null;
    }

    private function isProjectListRoute(): bool
    {
        $route = Yii::$app->controller->route ?? '';
        return in_array($route, self::PROJECT_ROUTE_DEFAULTS, true);
    }

    private function populateDefaults()
    {
        $defaults = self::getDefaults();
        $attributes = $this->attributes();
        foreach ($attributes as $attr) {
            if ($attr === 'id' || $attr === 'setting_key') {
                continue;
            }

            if ($this->$attr === null || $this->$attr === '') {
                $this->$attr = $defaults[$attr] ?? null;
            }
        }
    }
}
