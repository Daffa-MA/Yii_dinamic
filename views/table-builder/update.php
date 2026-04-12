<?php

/** @var yii\web\View $this */
/** @var app\models\DbTable $model */
/** @var array $savedColumns */

echo $this->render('create', [
    'model' => $model,
    'savedColumns' => $savedColumns,
    'pageTitle' => 'Update Database Table',
    'pageHeading' => 'Update Database Table',
    'heroText' => 'Update the stored table metadata and column structure here. Changes are saved back into the application database first, then can be applied again to the physical SQL table when needed.',
    'submitLabel' => 'Save Changes',
]);
