<?php

namespace app\components;

use Yii;
use app\models\Project;
use app\components\CommanderAuthContext;

class ActiveProjectContext
{
    public const SESSION_KEY = 'active_project_id';
    public const SESSION_KEY_DOMAIN = 'resolved_domain_project_id';

    public function getActiveProjectId(): ?int
    {
        if (!ProjectSchema::supportsProjectContext()) {
            return null;
        }

        $domainContext = new DomainContext();
        if ($domainContext->isWorkspaceDomain()) {
            $domainProjectId = (int)Yii::$app->session->get(self::SESSION_KEY_DOMAIN, 0);
            if ($domainProjectId > 0) {
                return $domainProjectId;
            }
        }

        $projectId = (int)Yii::$app->session->get(self::SESSION_KEY, 0);
        if ($projectId <= 0) {
            return null;
        }

        if (Yii::$app->user->isGuest) {
            return null;
        }

        if ((new CommanderAuthContext())->isSuperAdmin()) {
            return $projectId;
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

        if (Yii::$app->user->isGuest) {
            return Project::findOne(['id' => $projectId]);
        }

        if ((new CommanderAuthContext())->isSuperAdmin()) {
            return Project::findOne(['id' => $projectId]);
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

        if ((new CommanderAuthContext())->isSuperAdmin()) {
            Yii::$app->session->set(self::SESSION_KEY, $projectId);
            Yii::$app->session->remove(self::SESSION_KEY_DOMAIN);
            return true;
        }

        $isOwned = Project::find()
            ->where(['id' => $projectId, 'user_id' => Yii::$app->user->id])
            ->exists();

        if (!$isOwned) {
            return false;
        }

        Yii::$app->session->set(self::SESSION_KEY, $projectId);
        Yii::$app->session->remove(self::SESSION_KEY_DOMAIN);
        return true;
    }

    public function clear(): void
    {
        Yii::$app->session->remove(self::SESSION_KEY);
        Yii::$app->session->remove(self::SESSION_KEY_DOMAIN);
    }

    public function clearResolvedDomainProject(): void
    {
        Yii::$app->session->remove(self::SESSION_KEY_DOMAIN);
    }

    public function setResolvedDomainProject(int $projectId): void
    {
        if ($projectId > 0) {
            Yii::$app->session->set(self::SESSION_KEY_DOMAIN, $projectId);
        }
    }

    public function getResolvedDomainProjectId(): ?int
    {
        $projectId = (int)Yii::$app->session->get(self::SESSION_KEY_DOMAIN, 0);
        return $projectId > 0 ? $projectId : null;
    }

    public function userHasProjects(): bool
    {
        if (Yii::$app->user->isGuest) {
            return false;
        }

        if ((new CommanderAuthContext())->isSuperAdmin()) {
            return Project::find()->exists();
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
