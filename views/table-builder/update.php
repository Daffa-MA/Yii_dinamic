<?php

/** @var yii\web\View $this */
/** @var app\models\DbTable $model */
/** @var array $savedColumns */
/** @var array $foreignKeyReferenceMap */
/** @var array $referenceMetadata */
/** @var array $referenceTables */

echo $this->render('create', [
    'model' => $model,
    'savedColumns' => $savedColumns,
    'foreignKeyReferenceMap' => $foreignKeyReferenceMap ?? ($referenceMetadata ?? ($referenceTables ?? [])),
    'pageTitle' => 'Update Database Table',
    'pageHeading' => 'Update Database Table',
    'heroText' => 'Update the stored table metadata and column structure here. Changes are saved back into the application database first, then can be applied again to the physical SQL table when needed.',
    'submitLabel' => 'Save Changes',
]);
