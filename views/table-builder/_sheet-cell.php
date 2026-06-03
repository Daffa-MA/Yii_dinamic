<?php

use yii\helpers\Html;

/** @var app\models\DbTable $model */
/** @var array<string, mixed> $column */
/** @var mixed $value */
/** @var string $inputId */
/** @var int $rowIndex */

$name = (string)($column['name'] ?? '');
$type = strtolower((string)($column['inputType'] ?? 'text'));
$options = (array)($column['options'] ?? []);
$readOnly = (bool)($column['readOnly'] ?? false);
$displayValue = $value;
if (is_array($displayValue) || is_object($displayValue)) {
    $displayValue = json_encode($displayValue);
}
$displayValue = $displayValue === null ? '' : (string)$displayValue;

if ($type === 'boolean') {
    ?>
    <select
        id="<?= Html::encode($inputId) ?>"
        class="sheet-control sheet-field"
        data-sheet-field
        data-column="<?= Html::encode($name) ?>"
        data-row-index="<?= (int)$rowIndex ?>"
        <?= $readOnly ? 'disabled' : '' ?>
    >
        <option value="1"<?= $displayValue === '1' || strtolower($displayValue) === 'true' ? ' selected' : '' ?>>Aktif</option>
        <option value="0"<?= $displayValue === '0' || strtolower($displayValue) === 'false' || $displayValue === '' ? ' selected' : '' ?>>Nonaktif</option>
    </select>
    <?php
    return;
}

if ($type === 'select') {
    ?>
    <select
        id="<?= Html::encode($inputId) ?>"
        class="sheet-control sheet-field"
        data-sheet-field
        data-column="<?= Html::encode($name) ?>"
        data-row-index="<?= (int)$rowIndex ?>"
        <?= $readOnly ? 'disabled' : '' ?>
    >
        <option value="">--</option>
        <?php foreach ($options as $option): ?>
            <?php
            $optionValue = (string)($option['value'] ?? '');
            $optionLabel = (string)($option['label'] ?? $optionValue);
            ?>
            <option value="<?= Html::encode($optionValue) ?>"<?= $optionValue === $displayValue ? ' selected' : '' ?>>
                <?= Html::encode($optionLabel) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <?php
    return;
}

if ($type === 'datalist') {
    $listId = $inputId . '-list';
    ?>
    <input
        type="text"
        list="<?= Html::encode($listId) ?>"
        id="<?= Html::encode($inputId) ?>"
        class="sheet-control sheet-field"
        data-sheet-field
        data-column="<?= Html::encode($name) ?>"
        data-row-index="<?= (int)$rowIndex ?>"
        value="<?= Html::encode($displayValue) ?>"
        <?= $readOnly ? 'readonly' : '' ?>
    >
    <datalist id="<?= Html::encode($listId) ?>">
        <?php foreach ($options as $option): ?>
            <?php $optionValue = (string)($option['value'] ?? ''); ?>
            <option value="<?= Html::encode($optionValue) ?>"></option>
        <?php endforeach; ?>
    </datalist>
    <?php
    return;
}

if ($type === 'password') {
    ?>
    <input
        type="password"
        id="<?= Html::encode($inputId) ?>"
        class="sheet-control sheet-field"
        data-sheet-field
        data-column="<?= Html::encode($name) ?>"
        data-row-index="<?= (int)$rowIndex ?>"
        value=""
        placeholder="••••••••"
        <?= $readOnly ? 'readonly' : '' ?>
    >
    <?php
    return;
}

if ($type === 'date') {
    ?>
    <input
        type="date"
        id="<?= Html::encode($inputId) ?>"
        class="sheet-control sheet-field"
        data-sheet-field
        data-column="<?= Html::encode($name) ?>"
        data-row-index="<?= (int)$rowIndex ?>"
        value="<?= Html::encode($displayValue) ?>"
        <?= $readOnly ? 'readonly' : '' ?>
    >
    <?php
    return;
}

if ($type === 'datetime') {
    $datetimeValue = $displayValue !== '' ? str_replace(' ', 'T', substr($displayValue, 0, 16)) : '';
    ?>
    <input
        type="datetime-local"
        id="<?= Html::encode($inputId) ?>"
        class="sheet-control sheet-field"
        data-sheet-field
        data-column="<?= Html::encode($name) ?>"
        data-row-index="<?= (int)$rowIndex ?>"
        value="<?= Html::encode($datetimeValue) ?>"
        <?= $readOnly ? 'readonly' : '' ?>
    >
    <?php
    return;
}

if ($type === 'number') {
    ?>
    <input
        type="number"
        id="<?= Html::encode($inputId) ?>"
        class="sheet-control sheet-field"
        data-sheet-field
        data-column="<?= Html::encode($name) ?>"
        data-row-index="<?= (int)$rowIndex ?>"
        value="<?= Html::encode($displayValue) ?>"
        <?= $readOnly ? 'readonly' : '' ?>
    >
    <?php
    return;
}

if ($type === 'textarea') {
    ?>
    <textarea
        id="<?= Html::encode($inputId) ?>"
        class="sheet-control sheet-field"
        data-sheet-field
        data-column="<?= Html::encode($name) ?>"
        data-row-index="<?= (int)$rowIndex ?>"
        rows="1"
        <?= $readOnly ? 'readonly' : '' ?>
    ><?= Html::encode($displayValue) ?></textarea>
    <?php
    return;
}
?>
<input
    type="text"
    id="<?= Html::encode($inputId) ?>"
    class="sheet-control sheet-field"
    data-sheet-field
    data-column="<?= Html::encode($name) ?>"
    data-row-index="<?= (int)$rowIndex ?>"
    value="<?= Html::encode($displayValue) ?>"
    <?= $readOnly ? 'readonly' : '' ?>
>
