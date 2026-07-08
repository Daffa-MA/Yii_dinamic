<?php

namespace app\services;

use app\models\MasterForm;
use app\models\MasterFormActivityLog;
use Yii;
use yii\helpers\Json;

class FormActivityLogService
{
    public function log(MasterForm $form, string $eventType, string $status, string $message, array $meta = []): void
    {
        if (Yii::$app->db->getTableSchema(MasterFormActivityLog::tableName(), true) === null) {
            return;
        }

        $log = new MasterFormActivityLog();
        $log->form_id = (int)$form->id;
        $projectId = $form->hasAttribute('project_id') ? (int)$form->project_id : null;
        if ($projectId !== null) {
            $projectExists = (new \yii\db\Query())
                ->from('projects')
                ->where(['id' => $projectId])
                ->exists();
            $log->project_id = $projectExists ? $projectId : null;
        } else {
            $log->project_id = null;
        }
        $log->database_context = $form->database_context;
        $log->event_type = $eventType;
        $log->status = $status;
        $log->message = $message;
        $log->meta_json = !empty($meta) ? Json::encode($meta) : null;
        $log->save(false);
    }
}
