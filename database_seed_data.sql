-- =============================================
-- SEED DATA: Dynamic Menu/Page/Form System
-- Database: yii2basic
-- =============================================

-- Delete existing data first
DELETE FROM `master_menu`;
DELETE FROM `master_form`;
DELETE FROM `master_page`;

-- Reset AUTO_INCREMENT
ALTER TABLE `master_menu` AUTO_INCREMENT = 1;
ALTER TABLE `master_form` AUTO_INCREMENT = 1;
ALTER TABLE `master_page` AUTO_INCREMENT = 1;

-- Insert Master Pages
INSERT INTO `master_page` (`title`, `description`, `layout_type`, `is_active`, `created_at`, `updated_at`) VALUES
('Dashboard', 'Halaman utama dashboard', 'single_column', 1, NOW(), NOW()),
('Tabel Builder', 'Build custom tables', 'single_column', 1, NOW(), NOW()),
('Form Builder', 'Create dynamic forms', 'single_column', 1, NOW(), NOW()),
('Published Forms', 'View submitted forms', 'single_column', 1, NOW(), NOW()),
('Profil', 'User profile settings', 'single_column', 1, NOW(), NOW());

-- Insert Master Forms
INSERT INTO `master_form` (`page_id`, `form_name`, `slug`, `form_data`, `created_at`, `updated_at`) VALUES
(2, 'Table Config', 'table-config', '{"fields":[{"name":"table_name","type":"text","label":"Table Name","required":true},{"name":"columns","type":"textarea","label":"Columns (JSON)","required":true}]}', NOW(), NOW()),
(3, 'Form Fields', 'form-fields', '{"fields":[{"name":"field_name","type":"text","label":"Field Name","required":true},{"name":"field_type","type":"select","label":"Field Type","required":true},{"name":"label","type":"text","label":"Label","required":true}]}', NOW(), NOW()),
(4, 'Form Responses', 'form-responses', '{"fields":[{"name":"respondent","type":"text","label":"Respondent","required":false},{"name":"submitted_at","type":"date","label":"Submitted Date","required":false}]}', NOW(), NOW()),
(5, 'Profile Info', 'profile-info', '{"fields":[{"name":"full_name","type":"text","label":"Full Name","required":true},{"name":"email","type":"email","label":"Email","required":true},{"name":"bio","type":"textarea","label":"Bio","required":false}]}', NOW(), NOW());

-- Insert Master Menu (Original + Dynamic)
INSERT INTO `master_menu` (`parent_id`, `page_id`, `name`, `icon`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES
-- Original fixed menus (now dynamic)
(NULL, 1, 'Dashboard', 'dashboard', 1, 1, NOW(), NOW()),
(NULL, 2, 'Tabel Builder', 'table_chart', 2, 1, NOW(), NOW()),
(NULL, 3, 'Form', 'description', 3, 1, NOW(), NOW()),
(NULL, 4, 'Published Form', 'assignment', 4, 1, NOW(), NOW()),
(NULL, 5, 'Profil', 'person', 5, 1, NOW(), NOW());

-- =============================================
-- END SEED DATA
-- =============================================
