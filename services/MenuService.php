<?php

namespace app\services;

use Yii;
use yii\db\ActiveRecord;
use app\models\MasterMenu;
use app\models\MasterPage;

class MenuService
{
    const TYPE_GROUP = 'group';
    const TYPE_PAGE = 'page';
    const TYPE_ROUTE = 'route';
    const TYPE_FORM = 'form';
    const TYPE_BUTTON = 'button';
    const TYPE_DIVIDER = 'divider';

    /**
     * Validasi data menu sebelum save
     * @param MasterMenu $menu
     * @return array ['success' => bool, 'errors' => array]
     */
    public function validateMenu(MasterMenu $menu): array
    {
        $errors = [];

        // 1. Validate type is required and valid
        if (empty($menu->type)) {
            $errors[] = 'Tipe menu wajib dipilih';
        } elseif (!in_array($menu->type, [self::TYPE_GROUP, self::TYPE_PAGE, self::TYPE_ROUTE, self::TYPE_FORM, self::TYPE_BUTTON, self::TYPE_DIVIDER])) {
            $errors[] = 'Tipe menu tidak valid';
        }

        // 2. Type-specific validation
        switch ($menu->type) {
            case self::TYPE_GROUP:
                // Group tidak boleh punya page_id atau route
                if (!empty($menu->page_id)) {
                    $errors[] = 'Menu tipe Group tidak boleh terhubung ke halaman';
                }
                if (!empty($menu->route)) {
                    $errors[] = 'Menu tipe Group tidak boleh menggunakan Route';
                }
                if (!empty($menu->form_id)) {
                    $errors[] = 'Menu tipe Group tidak boleh terhubung ke Form';
                }
                break;

            case self::TYPE_PAGE:
                // Page wajib ada page_id
                if (empty($menu->page_id)) {
                    $errors[] = 'Menu tipe Page wajib memilih Halaman';
                }
                // Page tidak boleh punya route
                if (!empty($menu->route)) {
                    $errors[] = 'Menu tipe Page tidak boleh menggunakan Route';
                }
                if (!empty($menu->form_id)) {
                    $errors[] = 'Menu tipe Page tidak boleh terhubung ke Form';
                }
                break;

            case self::TYPE_FORM:
                // Form wajib ada form_id
                if (empty($menu->form_id)) {
                    $errors[] = 'Menu tipe Form wajib memilih Form';
                }
                if (!empty($menu->page_id)) {
                    $errors[] = 'Menu tipe Form tidak boleh terhubung ke Halaman';
                }
                if (!empty($menu->route)) {
                    $errors[] = 'Menu tipe Form tidak boleh menggunakan Route';
                }
                break;

            case self::TYPE_ROUTE:
            case self::TYPE_BUTTON:
                // Route/Button wajib ada route
                if (empty($menu->route)) {
                    $errors[] = 'URL wajib diisi untuk tipe ' . $menu->type;
                }
                // Route tidak boleh punya page_id
                if (!empty($menu->page_id)) {
                    $errors[] = 'Menu tipe Route/Button tidak boleh terhubung ke halaman';
                }
                if (!empty($menu->form_id)) {
                    $errors[] = 'Menu tipe Route/Button tidak boleh terhubung ke Form';
                }
                break;

            case self::TYPE_DIVIDER:
                // Divider tidak perlu validasi khusus, hanya tidak punya target
                break;
        }

        // 3. Validate parent_id tidak sama dengan id sendiri
        if (!empty($menu->parent_id) && $menu->parent_id == $menu->id) {
            $errors[] = 'Menu tidak bisa menjadi parent dirinya sendiri';
        }

        // 4. Validate circular parent (A → B → A)
        if (!empty($menu->parent_id)) {
            $circular = $this->checkCircularParent($menu->id, $menu->parent_id);
            if ($circular) {
                $errors[] = 'Tidak boleh ada circular parent (A → B → A)';
            }
        }

        // 5. Validate parent exists
        if (!empty($menu->parent_id)) {
            $parentExists = MasterMenu::find()->where(['id' => $menu->parent_id])->exists();
            if (!$parentExists) {
                $errors[] = 'Parent menu tidak ditemukan';
            }
        }

        // 6. Validate page_id exists if provided
        if (!empty($menu->page_id)) {
            $pageExists = MasterPage::find()->where(['id' => $menu->page_id, 'is_active' => 1])->exists();
            if (!$pageExists) {
                $errors[] = 'Halaman yang dipilih tidak ditemukan atau tidak aktif';
            }
        }

        return [
            'success' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Cek circular parent
     */
    private function checkCircularParent(int $menuId, int $parentId, array $visited = []): bool
    {
        if (in_array($parentId, $visited)) {
            return true;
        }

        $visited[] = $parentId;

        $parent = MasterMenu::findOne($parentId);
        if (!$parent || !$parent->parent_id) {
            return false;
        }

        // If parent_id equals original menu id, it's circular
        if ($parent->parent_id == $menuId) {
            return true;
        }

        return $this->checkCircularParent($menuId, $parent->parent_id, $visited);
    }

    /**
     * Create new menu
     */
    public function createMenu(array $data): array
    {
        $menu = new MasterMenu();
        $menu->load($data);
        $this->normalizePostedMenuFields($menu, $data);

        // Set default values
        $menu->is_active = $menu->is_active ?? 1;
        $menu->type = $menu->type ?? self::TYPE_PAGE;

        // Validate
        $validation = $this->validateMenu($menu);
        if (!$validation['success']) {
            return [
                'success' => false,
                'errors' => $validation['errors'],
                'model' => $menu
            ];
        }

        // Auto-clean fields based on type
        $this->cleanFieldsByType($menu);

        if ($menu->save()) {
            return [
                'success' => true,
                'model' => $menu,
                'message' => 'Menu berhasil dibuat'
            ];
        }

        return [
            'success' => false,
            'errors' => $menu->getErrors(),
            'model' => $menu
        ];
    }

    /**
     * Update existing menu
     */
    public function updateMenu(int $id, array $data): array
    {
        $menu = MasterMenu::findOne($id);
        if (!$menu) {
            return [
                'success' => false,
                'errors' => ['Menu tidak ditemukan']
            ];
        }

        $menu->load($data);
        $this->normalizePostedMenuFields($menu, $data);

        // Validate
        $validation = $this->validateMenu($menu);
        if (!$validation['success']) {
            return [
                'success' => false,
                'errors' => $validation['errors'],
                'model' => $menu
            ];
        }

        // Auto-clean fields based on type
        $this->cleanFieldsByType($menu);

        if ($menu->save()) {
            return [
                'success' => true,
                'model' => $menu,
                'message' => 'Menu berhasil diupdate'
            ];
        }

        return [
            'success' => false,
            'errors' => $menu->getErrors(),
            'model' => $menu
        ];
    }

    /**
     * Normalize critical fields from raw POST payload.
     */
    private function normalizePostedMenuFields(MasterMenu $menu, array $data): void
    {
        $payload = is_array($data['MasterMenu'] ?? null) ? $data['MasterMenu'] : [];
        $postedType = strtolower(trim((string)($payload['type'] ?? '')));

        if ($postedType !== '') {
            $menu->type = $postedType;
        }
        foreach (['parent_id', 'page_id', 'form_id'] as $attribute) {
            if (array_key_exists($attribute, $payload)) {
                $value = $payload[$attribute];
                $menu->$attribute = ($value === '' || $value === null) ? null : (int)$value;
            }
        }

        if (array_key_exists('route', $payload)) {
            $route = trim((string)$payload['route']);
            $menu->route = $route !== '' ? $route : null;
        }

        $menu->normalizeTypeRelations();
    }

    /**
     * Clean fields based on type
     */
    private function cleanFieldsByType(MasterMenu $menu): void
    {
        $menu->normalizeTypeRelations();
    }

    /**
     * Delete menu (cascade untuk child akan diset parent_id = null)
     */
    public function deleteMenu(int $id): array
    {
        $menu = MasterMenu::findOne($id);
        if (!$menu) {
            return [
                'success' => false,
                'errors' => ['Menu tidak ditemukan']
            ];
        }

        // Set child menus parent_id to null instead of delete
        MasterMenu::updateAll(
            ['parent_id' => null],
            ['parent_id' => $id]
        );

        if ($menu->delete()) {
            return [
                'success' => true,
                'message' => 'Menu berhasil dihapus'
            ];
        }

        return [
            'success' => false,
            'errors' => ['Gagal menghapus menu']
        ];
    }

    /**
     * Toggle menu status
     */
    public function toggleStatus(int $id): array
    {
        $menu = MasterMenu::findOne($id);
        if (!$menu) {
            return [
                'success' => false,
                'errors' => ['Menu tidak ditemukan']
            ];
        }

        $menu->is_active = $menu->is_active == 1 ? 0 : 1;
        
        if ($menu->save(false)) {
            $statusText = $menu->is_active == 1 ? 'diaktifkan' : 'dinonaktifkan';
            return [
                'success' => true,
                'is_active' => $menu->is_active,
                'message' => "Menu {$menu->name} berhasil {$statusText}"
            ];
        }

        return [
            'success' => false,
            'errors' => ['Gagal toggle status']
        ];
    }

    /**
     * Get menu tree for sidebar
     */
    public function getMenuTree(bool $activeOnly = true): array
    {
        return MasterMenu::getMenuTree($activeOnly);
    }

    /**
     * Reorder menus
     */
    public function reorder(array $orderData): array
    {
        $success = true;
        $errors = [];

        foreach ($orderData as $item) {
            $menu = MasterMenu::findOne($item['id']);
            if ($menu) {
                $menu->order = $item['order'];
                if (!$menu->save(false)) {
                    $success = false;
                    $errors[] = "Gagal update urutan menu {$menu->name}";
                }
            }
        }

        return [
            'success' => $success,
            'errors' => $errors,
            'message' => $success ? 'Urutan menu berhasil diupdate' : 'Beberapa menu gagal diupdate'
        ];
    }
}
