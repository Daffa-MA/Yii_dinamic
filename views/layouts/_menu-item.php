<?php
/**
 * @var array $item Menu item with children
 * @var string $activeMenu Current active menu ID
 */
use yii\helpers\Html;
use yii\helpers\Url;

$activeMenu = (string) ($activeMenu ?? '');
$sessionActiveMenu = (string) Yii::$app->session->get('active_menu', '');
$hasChildren = !empty($item['children']);
$itemId = isset($item['id']) ? (string) $item['id'] : '';
$itemName = strtolower(trim((string) ($item['name'] ?? '')));
$matchesItem = static function (string $candidate, string $targetId, string $targetName): bool {
    $normalized = strtolower(trim($candidate));
    if ($normalized === '') {
        return false;
    }

    return ($targetId !== '' && ($normalized === $targetId || $normalized === 'menu-' . $targetId))
        || ($targetName !== '' && $normalized === $targetName);
};
$hasActiveChild = false;
if ($hasChildren) {
    $walkChildren = static function (array $children) use (&$walkChildren, $matchesItem, $activeMenu, $sessionActiveMenu): bool {
        foreach ($children as $child) {
            $childId = isset($child['id']) ? (string) $child['id'] : '';
            $childName = strtolower(trim((string) ($child['name'] ?? '')));
            if (
                $matchesItem($activeMenu, $childId, $childName)
                || $matchesItem($sessionActiveMenu, $childId, $childName)
            ) {
                return true;
            }

            if (!empty($child['children']) && $walkChildren($child['children'])) {
                return true;
            }
        }

        return false;
    };

    $hasActiveChild = $walkChildren($item['children']);
}

$isActive = $matchesItem($activeMenu, $itemId, $itemName) || $matchesItem($sessionActiveMenu, $itemId, $itemName) || $hasActiveChild;
$url = $item['url'] ?? null;
$href = '#';
if (is_array($url) && !empty($url)) {
    $href = Url::to($url);
} elseif (is_string($url) && $url !== '' && $url !== '#') {
    $href = Url::to($url);
} elseif (!empty($item['form_id'])) {
    $href = Url::to(['/master-form/preview', 'id' => (int) $item['form_id']]);
} elseif (($item['type'] ?? '') !== 'group' && $itemId !== '') {
    $href = Url::to(['/master-menu/resolve-link', 'id' => (int) $itemId]);
}

$baseLink = 'app-sidebar-link group flex min-h-11 items-center gap-3 rounded-2xl border border-transparent px-3.5 py-3 text-sm font-semibold no-underline transition-all duration-200';
$inactive = 'text-slate-600 hover:translate-x-[3px] hover:border-slate-300/30 hover:bg-slate-400/10 hover:text-slate-900';
$active = 'bg-gradient-to-r from-blue-600 via-cyan-500 to-blue-600 text-white shadow-lg shadow-blue-500/20 font-semibold border-none hover:text-white';
$iconBase = 'material-symbols-outlined inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-lg text-[18px] transition-colors duration-200';
$iconInactive = 'bg-slate-400/10 text-slate-500 group-hover:bg-blue-500/15 group-hover:text-blue-700';
$iconActive = 'bg-white/20 text-white group-hover:bg-white/25';

$linkClass = $baseLink . ' ' . ($hasChildren ? 'has-children ' . ($isActive ? 'expanded ' : '') : '') . ($isActive ? $active : $inactive);
$iconClass = $iconBase . ' ' . ($isActive ? $iconActive : $iconInactive);
?>

<?php if ($hasChildren): ?>
    <a href="#" class="<?= Html::encode($linkClass) ?>" data-menu-id="<?= Html::encode($itemId) ?>">
        <span class="<?= Html::encode($iconClass) ?>"><?= Html::encode($item['icon']) ?></span>
        <span class="app-sidebar-link-text min-w-0 flex-1 truncate"><?= Html::encode($item['name']) ?></span>
        <span class="app-sidebar-chevron material-symbols-outlined ml-auto shrink-0 text-base text-slate-400 transition-transform">expand_more</span>
    </a>
    <div class="sub-menu">
        <?php foreach ($item['children'] as $child): ?>
            <?= $this->render('_menu-item', ['item' => $child, 'activeMenu' => $activeMenu]) ?>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <a href="<?= Html::encode($href) ?>" class="<?= Html::encode($linkClass) ?>" data-menu-id="<?= (int)$item['id'] ?>">
        <span class="<?= Html::encode($iconClass) ?>"><?= Html::encode($item['icon']) ?></span>
        <span class="app-sidebar-link-text min-w-0 flex-1 truncate"><?= Html::encode($item['name']) ?></span>
    </a>
<?php endif; ?>
