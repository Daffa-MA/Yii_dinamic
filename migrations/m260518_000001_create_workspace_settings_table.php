<?php

use yii\db\Migration;

class m260518_000001_create_workspace_settings_table extends Migration
{
    public function safeUp()
    {
        if ($this->db->schema->getTableSchema('workspace_settings', true) === null) {
            $this->createTable('workspace_settings', [
                'id' => $this->primaryKey(),
                'setting_key' => $this->string(100)->notNull()->unique(),
                'workspace_title' => $this->string(255)->defaultValue('Projects'),
                'workspace_subtitle' => $this->string(255)->defaultValue('Beranda & navigasi'),
                'workspace_badge' => $this->string(255)->defaultValue('Workspace'),
                'workspace_logo_icon' => $this->string(100)->defaultValue('folder_open'),
                'workspace_logo_bg' => $this->string(50)->defaultValue('#4f46e5'),
                'sidebar_bg_start' => $this->string(50)->defaultValue('#07111f'),
                'sidebar_bg_end' => $this->string(50)->defaultValue('#111827'),
                'sidebar_border_color' => $this->string(100)->defaultValue('rgba(148, 163, 184, 0.16)'),
                'sidebar_text_color' => $this->string(50)->defaultValue('#e2e8f0'),
                'sidebar_text_muted' => $this->string(50)->defaultValue('#94a3b8'),
                'sidebar_icon_bg' => $this->string(100)->defaultValue('rgba(255, 255, 255, 0.05)'),
                'sidebar_active_bg_start' => $this->string(50)->defaultValue('#2563eb'),
                'sidebar_active_bg_end' => $this->string(50)->defaultValue('#06b6d4'),
                'sidebar_active_text' => $this->string(50)->defaultValue('#ffffff'),
                'sidebar_active_icon_bg' => $this->string(100)->defaultValue('rgba(255, 255, 255, 0.2)'),
                'sidebar_active_shadow' => $this->string(200)->defaultValue('0 8px 24px rgba(37, 99, 235, 0.28)'),
                'sidebar_hover_bg' => $this->string(100)->defaultValue('rgba(255, 255, 255, 0.08)'),
                'sidebar_hover_text' => $this->string(50)->defaultValue('#ffffff'),
                'topnav_bg' => $this->string(50)->defaultValue('#ffffff'),
                'topnav_border_color' => $this->string(50)->defaultValue('#e2e8f0'),
                'topnav_text_color' => $this->string(50)->defaultValue('#1e293b'),
                'light_sidebar_bg' => $this->string(50)->defaultValue('#f8fafc'),
                'light_sidebar_border' => $this->string(100)->defaultValue('rgba(148, 163, 184, 0.2)'),
                'light_sidebar_text' => $this->string(50)->defaultValue('#475569'),
                'footer_bg' => $this->string(100)->defaultValue('rgba(15, 23, 42, 0.22)'),
                'footer_text' => $this->string(50)->defaultValue('#cbd5e1'),
                'footer_logout_bg' => $this->string(100)->defaultValue('rgba(248, 250, 252, 0.8)'),
                'footer_logout_hover_bg' => $this->string(50)->defaultValue('#fee2e2'),
                'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
                'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            ]);

            $this->createIndex('idx-workspace_settings-key', 'workspace_settings', 'setting_key');
        }

        $exists = (new \yii\db\Query())->from('workspace_settings')->where(['setting_key' => 'default'])->exists($this->db);
        if ($exists) {
            return;
        }

        $this->insert('workspace_settings', [
            'setting_key' => 'default',
            'workspace_title' => 'Projects',
            'workspace_subtitle' => 'Beranda & navigasi',
            'workspace_badge' => 'Workspace',
            'workspace_logo_icon' => 'folder_open',
            'workspace_logo_bg' => '#4f46e5',
            'sidebar_bg_start' => '#07111f',
            'sidebar_bg_end' => '#111827',
            'sidebar_border_color' => 'rgba(148, 163, 184, 0.16)',
            'sidebar_text_color' => '#e2e8f0',
            'sidebar_text_muted' => '#94a3b8',
            'sidebar_icon_bg' => 'rgba(255, 255, 255, 0.05)',
            'sidebar_active_bg_start' => '#2563eb',
            'sidebar_active_bg_end' => '#06b6d4',
            'sidebar_active_text' => '#ffffff',
            'sidebar_active_icon_bg' => 'rgba(255, 255, 255, 0.2)',
            'sidebar_active_shadow' => '0 8px 24px rgba(37, 99, 235, 0.28)',
            'sidebar_hover_bg' => 'rgba(255, 255, 255, 0.08)',
            'sidebar_hover_text' => '#ffffff',
            'topnav_bg' => '#ffffff',
            'topnav_border_color' => '#e2e8f0',
            'topnav_text_color' => '#1e293b',
            'light_sidebar_bg' => '#f8fafc',
            'light_sidebar_border' => 'rgba(148, 163, 184, 0.2)',
            'light_sidebar_text' => '#475569',
            'footer_bg' => 'rgba(15, 23, 42, 0.22)',
            'footer_text' => '#cbd5e1',
            'footer_logout_bg' => 'rgba(248, 250, 252, 0.8)',
            'footer_logout_hover_bg' => '#fee2e2',
        ]);
    }

    public function safeDown()
    {
        if ($this->db->schema->getTableSchema('workspace_settings', true) !== null) {
            $this->dropTable('workspace_settings');
        }
    }
}
