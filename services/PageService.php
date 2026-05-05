<?php

namespace app\services;

use Yii;
use app\models\MasterPage;
use app\models\PageForms;

class PageService
{
    const LAYOUT_DEFAULT = 'default';
    const LAYOUT_LIST = 'list';
    const LAYOUT_FORM = 'form';
    const LAYOUT_DASHBOARD = 'dashboard';
    const LAYOUT_BLANK = 'blank';
    const LAYOUT_TWO_COLUMN = 'two_column';

    /**
     * Get all layout options
     */
    public static function getLayoutOptions(): array
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

    /**
     * Validate page data
     */
    public function validatePage(MasterPage $page): array
    {
        $errors = [];

        // Validate title
        if (empty($page->title)) {
            $errors[] = 'Judul halaman wajib diisi';
        }

        // Validate slug uniqueness (if provided)
        if (!empty($page->slug)) {
            $existing = MasterPage::find()
                ->where(['slug' => $page->slug]);
            
            if (!$page->isNewRecord) {
                $existing->andWhere(['!=', 'id', $page->id]);
            }
            
            if ($existing->exists()) {
                $errors[] = 'Slug sudah digunakan oleh halaman lain';
            }
        }

        // Validate layout
        $validLayouts = array_keys(self::getLayoutOptions());
        if (!empty($page->layout) && !in_array($page->layout, $validLayouts)) {
            $errors[] = 'Layout tidak valid';
        }

        return [
            'success' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Create new page with forms
     * @param array $data Page data
     * @param array $formIds Array of form IDs to attach
     * @return array
     */
    public function createPage(array $data, array $formIds = []): array
    {
        $page = new MasterPage();
        $page->load($data);

        // Set defaults
        $page->is_active = $page->is_active ?? 1;
        $page->layout = $page->layout ?? self::LAYOUT_DEFAULT;

        // Generate slug if empty
        if (empty($page->slug)) {
            $page->slug = $this->generateSlug($page->title);
        }

        // Validate
        $validation = $this->validatePage($page);
        if (!$validation['success']) {
            return [
                'success' => false,
                'errors' => $validation['errors'],
                'model' => $page
            ];
        }

        // Save page
        if (!$page->save()) {
            return [
                'success' => false,
                'errors' => $page->getErrors(),
                'model' => $page
            ];
        }

        // Attach forms if any
        if (!empty($formIds)) {
            $this->syncForms($page->id, $formIds);
        }

        return [
            'success' => true,
            'model' => $page,
            'message' => 'Halaman berhasil dibuat'
        ];
    }

    /**
     * Update page with form sync
     */
    public function updatePage(int $id, array $data, array $formIds = []): array
    {
        $page = MasterPage::findOne($id);
        if (!$page) {
            return [
                'success' => false,
                'errors' => ['Halaman tidak ditemukan']
            ];
        }

        $page->load($data);

        // Validate
        $validation = $this->validatePage($page);
        if (!$validation['success']) {
            return [
                'success' => false,
                'errors' => $validation['errors'],
                'model' => $page
            ];
        }

        // Save page
        if (!$page->save()) {
            return [
                'success' => false,
                'errors' => $page->getErrors(),
                'model' => $page
            ];
        }

        // Sync forms
        if ($formIds !== []) {
            $this->syncForms($page->id, $formIds);
        }

        return [
            'success' => true,
            'model' => $page,
            'message' => 'Halaman berhasil diupdate'
        ];
    }

    /**
     * Sync forms to page (delete old + insert new)
     */
    public function syncForms(int $pageId, array $formIds): bool
    {
        // Delete existing
        PageForms::deleteAll(['page_id' => $pageId]);

        // Insert new with order
        foreach ($formIds as $order => $formId) {
            $pageForm = new PageForms();
            $pageForm->page_id = $pageId;
            $pageForm->form_id = (int) $formId;
            $pageForm->order = (int) $order + 1;
            $pageForm->save(false);
        }

        return true;
    }

    /**
     * Add single form to page
     */
    public function addFormToPage(int $pageId, int $formId, int $order = 0): array
    {
        // Check if already exists
        $exists = PageForms::find()
            ->where(['page_id' => $pageId, 'form_id' => $formId])
            ->exists();

        if ($exists) {
            return [
                'success' => false,
                'errors' => ['Form sudah terikat dengan halaman ini']
            ];
        }

        $pageForm = new PageForms();
        $pageForm->page_id = $pageId;
        $pageForm->form_id = $formId;
        $pageForm->order = $order;

        if ($pageForm->save()) {
            return [
                'success' => true,
                'message' => 'Form berhasil ditambahkan ke halaman'
            ];
        }

        return [
            'success' => false,
            'errors' => $pageForm->getErrors()
        ];
    }

    /**
     * Remove form from page
     */
    public function removeFormFromPage(int $pageId, int $formId): array
    {
        $deleted = PageForms::deleteAll([
            'page_id' => $pageId,
            'form_id' => $formId
        ]);

        if ($deleted > 0) {
            return [
                'success' => true,
                'message' => 'Form berhasil dihapus dari halaman'
            ];
        }

        return [
            'success' => false,
            'errors' => ['Form tidak ditemukan di halaman ini']
        ];
    }

    /**
     * Get forms for a page
     */
    public function getPageForms(int $pageId): array
    {
        return PageForms::find()
            ->where(['page_id' => $pageId])
            ->orderBy(['order' => SORT_ASC])
            ->all();
    }

    /**
     * Delete page (also deletes page_forms due to CASCADE)
     */
    public function deletePage(int $id): array
    {
        $page = MasterPage::findOne($id);
        if (!$page) {
            return [
                'success' => false,
                'errors' => ['Halaman tidak ditemukan']
            ];
        }

        // page_forms will be deleted automatically due to FK CASCADE
        if ($page->delete()) {
            return [
                'success' => true,
                'message' => 'Halaman berhasil dihapus'
            ];
        }

        return [
            'success' => false,
            'errors' => ['Gagal menghapus halaman']
        ];
    }

    /**
     * Toggle page status
     */
    public function toggleStatus(int $id): array
    {
        $page = MasterPage::findOne($id);
        if (!$page) {
            return [
                'success' => false,
                'errors' => ['Halaman tidak ditemukan']
            ];
        }

        $page->is_active = $page->is_active == 1 ? 0 : 1;
        
        if ($page->save(false)) {
            $statusText = $page->is_active == 1 ? 'diaktifkan' : 'dinonaktifkan';
            return [
                'success' => true,
                'is_active' => $page->is_active,
                'message' => "Halaman {$page->title} berhasil {$statusText}"
            ];
        }

        return [
            'success' => false,
            'errors' => ['Gagal toggle status']
        ];
    }

    /**
     * Generate URL-friendly slug
     */
    private function generateSlug(string $title): string
    {
        // Convert to lowercase, replace spaces with hyphens
        $slug = strtolower(trim($title));
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        $slug = trim($slug, '-');

        // Check uniqueness
        $exists = MasterPage::find()
            ->where(['like', 'slug', $slug . '%'])
            ->exists();

        if ($exists) {
            $slug .= '-' . time();
        }

        return $slug;
    }

    /**
     * Get all active pages for dropdown
     */
    public static function getActivePagesList(): array
    {
        return \yii\helpers\ArrayHelper::map(
            MasterPage::find()
                ->where(['is_active' => 1])
                ->orderBy(['title' => SORT_ASC])
                ->all(),
            'id',
            'title'
        );
    }
}