<?php

namespace app\models;

use Yii;

class WorkspaceSettings extends \yii\base\Model
{
    public $workspace_title = 'Projects';
    public $workspace_subtitle = 'Beranda & navigasi';
    public $workspace_badge = 'Workspace';
    public $workspace_logo_icon = 'folder_open';
    public $workspace_logo_bg = '#4f46e5';
    
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
    
    const SESSION_KEY = 'workspace_settings';
    
    public function rules()
    {
        return [
            [['workspace_title', 'workspace_subtitle', 'workspace_badge', 'workspace_logo_icon', 'workspace_logo_bg'], 'string', 'max' => 255],
            [['workspace_logo_bg', 'sidebar_bg_start', 'sidebar_bg_end', 'sidebar_border_color', 'sidebar_text_color', 'sidebar_text_muted'], 'string', 'max' => 100],
            [['sidebar_hover_bg', 'sidebar_hover_text', 'sidebar_active_bg_start', 'sidebar_active_bg_end', 'sidebar_active_text', 'sidebar_active_shadow'], 'string', 'max' => 100],
            [['topnav_bg', 'topnav_border_color', 'topnav_text_color', 'light_sidebar_bg', 'light_sidebar_border'], 'string', 'max' => 100],
        ];
    }
    
    public function attributeLabels()
    {
        return [
            'workspace_title' => 'Judul Workspace',
            'workspace_subtitle' => 'Subtitle Workspace',
            'workspace_badge' => 'Badge Workspace',
            
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
    
    public function loadFromSession()
    {
        $data = Yii::$app->session->get(self::SESSION_KEY, []);
        if (!empty($data)) {
            Yii::info('Loading workspace settings from session: ' . json_encode($data), 'workspace-settings');
            $this->load($data, '');
        } else {
            Yii::info('No workspace settings in session, using defaults', 'workspace-settings');
        }
    }
    
    public function save()
    {
        $data = [
            'workspace_title' => $this->workspace_title,
            'workspace_subtitle' => $this->workspace_subtitle,
            'workspace_badge' => $this->workspace_badge,
            'workspace_logo_icon' => $this->workspace_logo_icon,
            'workspace_logo_bg' => $this->workspace_logo_bg,
            
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
        
        Yii::$app->session->set(self::SESSION_KEY, $data);
        Yii::info('Workspace settings saved: ' . json_encode($data), 'workspace-settings');
        
        return true;
    }
    
    public function reset()
    {
        Yii::$app->session->remove(self::SESSION_KEY);
        $this->clear();
        return true;
    }
    
    public function clear()
    {
        $this->workspace_title = 'Projects';
        $this->workspace_subtitle = 'Beranda & navigasi';
        $this->workspace_badge = 'Workspace';
        $this->workspace_logo_icon = 'folder_open';
        $this->workspace_logo_bg = '#4f46e5';
        
        $this->sidebar_bg_start = '#07111f';
        $this->sidebar_bg_end = '#111827';
        $this->sidebar_border_color = 'rgba(148, 163, 184, 0.16)';
        $this->sidebar_text_color = '#e2e8f0';
        $this->sidebar_text_muted = '#94a3b8';
        $this->sidebar_icon_bg = 'rgba(255, 255, 255, 0.05)';
        
        $this->sidebar_active_bg_start = '#2563eb';
        $this->sidebar_active_bg_end = '#06b6d4';
        $this->sidebar_active_text = '#ffffff';
        $this->sidebar_active_icon_bg = 'rgba(255, 255, 255, 0.2)';
        $this->sidebar_active_shadow = '0 8px 24px rgba(37, 99, 235, 0.28)';
        
        $this->sidebar_hover_bg = 'rgba(255, 255, 255, 0.08)';
        $this->sidebar_hover_text = '#ffffff';
        
        $this->topnav_bg = '#ffffff';
        $this->topnav_border_color = '#e2e8f0';
        $this->topnav_text_color = '#1e293b';
        
        $this->light_sidebar_bg = '#f8fafc';
        $this->light_sidebar_border = 'rgba(148, 163, 184, 0.2)';
        
        $this->footer_bg = 'rgba(15, 23, 42, 0.22)';
        $this->footer_text = '#cbd5e1';
    }
    
    public function getCssVars()
    {
        return [
            'workspace-title' => $this->workspace_title,
            'workspace-subtitle' => $this->workspace_subtitle,
            'workspace-badge' => $this->workspace_badge,
            'workspace-logo-icon' => $this->workspace_logo_icon,
            'workspace-logo-bg' => $this->workspace_logo_bg,
            
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
}