<?php

namespace app\components;

use Yii;
use app\models\Project;

class ActiveProjectContext
{
    public const SESSION_KEY = 'active_project_id';

    public function getActiveProjectId(): ?int
    {
        if (Yii::$app->user->isGuest) {
            return null;
        }

        if (!ProjectSchema::supportsProjectContext()) {
            return null;
        }

        $projectId = (int)Yii::$app->session->get(self::SESSION_KEY, 0);
        if ($projectId <= 0) {
            return null;
        }

        $isOwned = Project::find()
            ->where(['id' => $projectId, 'user_id' => Yii::$app->user->id])
            ->exists();

        if (!$isOwned) {
            Yii::$app->session->remove(self::SESSION_KEY);
            return null;
        }

        return $projectId;
    }

    public function getActiveProject(): ?Project
    {
        if (!ProjectSchema::supportsProjectContext()) {
            return null;
        }

        $projectId = $this->getActiveProjectId();
        if ($projectId === null) {
            return null;
        }

        return Project::findOne(['id' => $projectId, 'user_id' => Yii::$app->user->id]);
    }

    public function setActiveProject(int $projectId): bool
    {
        if (Yii::$app->user->isGuest) {
            return false;
        }

        if (!ProjectSchema::supportsProjectContext()) {
            return false;
        }

        $isOwned = Project::find()
            ->where(['id' => $projectId, 'user_id' => Yii::$app->user->id])
            ->exists();

        if (!$isOwned) {
            return false;
        }

        Yii::$app->session->set(self::SESSION_KEY, $projectId);
        return true;
    }

    public function clear(): void
    {
        Yii::$app->session->remove(self::SESSION_KEY);
    }

    public function userHasProjects(): bool
    {
        if (Yii::$app->user->isGuest) {
            return false;
        }

        if (!ProjectSchema::supportsProjectContext()) {
            return false;
        }

        return Project::find()
            ->where(['user_id' => Yii::$app->user->id])
            ->exists();
    }

    public function isEnabled(): bool
    {
        return ProjectSchema::supportsProjectContext();
    }
}
