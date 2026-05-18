<?php

namespace app\components;

use app\models\Project;
use app\models\ProjectUser;
use Yii;

class ProjectAuthContext
{
    public const SESSION_KEY_PREFIX = 'project_auth';

    public function getSessionKey(int $projectId): string
    {
        return self::SESSION_KEY_PREFIX . '_' . $projectId;
    }

    public function getActiveProjectId(): ?int
    {
        return (new ActiveProjectContext())->getActiveProjectId();
    }

    public function isAuthenticated(?int $projectId = null): bool
    {
        $projectId = $this->resolveProjectId($projectId);
        if ($projectId === null) {
            return false;
        }

        if ((new CommanderAuthContext())->isSuperAdmin()) {
            return true;
        }

        $data = $this->getSessionData($projectId);
        if (empty($data['user_id'])) {
            return false;
        }

        return $this->loadUser($projectId, (int)$data['user_id']) instanceof ProjectUser;
    }

    public function getAuthenticatedUser(?int $projectId = null): ?ProjectUser
    {
        $projectId = $this->resolveProjectId($projectId);
        if ($projectId === null) {
            return null;
        }

        $data = $this->getSessionData($projectId);
        $userId = (int)($data['user_id'] ?? 0);
        if ($userId <= 0) {
            return null;
        }

        return $this->loadUser($projectId, $userId);
    }

    public function login(Project $project, ProjectUser $user): void
    {
        $projectId = (int)$project->id;
        Yii::$app->session->set($this->getSessionKey($projectId), [
            'project_id' => $projectId,
            'user_id' => (int)$user->id,
            'username' => (string)$user->username,
            'role' => (string)$user->role,
            'must_change_password' => (bool)$user->must_change_password,
            'logged_in_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function logout(?int $projectId = null): void
    {
        $projectId = $this->resolveProjectId($projectId);
        if ($projectId === null) {
            return;
        }

        Yii::$app->session->remove($this->getSessionKey($projectId));
    }

    public function requiresPasswordChange(?int $projectId = null): bool
    {
        $projectId = $this->resolveProjectId($projectId);
        if ($projectId === null) {
            return false;
        }

        $user = $this->getAuthenticatedUser($projectId);
        return $user !== null && (bool)$user->must_change_password;
    }

    public function getProjectContextKey(?int $projectId = null): string
    {
        $projectId = $this->resolveProjectId($projectId);
        return $projectId !== null ? $this->getSessionKey($projectId) : self::SESSION_KEY_PREFIX;
    }

    private function resolveProjectId(?int $projectId = null): ?int
    {
        if ($projectId !== null && $projectId > 0) {
            return $projectId;
        }

        $activeProjectId = $this->getActiveProjectId();
        return $activeProjectId !== null && $activeProjectId > 0 ? $activeProjectId : null;
    }

    private function getSessionData(int $projectId): array
    {
        $data = Yii::$app->session->get($this->getSessionKey($projectId), []);
        if (!is_array($data) || empty($data)) {
            $data = Yii::$app->session->get('project_app_auth:' . $projectId, []);
        }

        return is_array($data) ? $data : [];
    }

    private function loadUser(int $projectId, int $userId): ?ProjectUser
    {
        $project = Project::findOne(['id' => $projectId]);
        if ($project === null) {
            return null;
        }

        $context = new ActiveProjectContext();
        $context->setResolvedDomainProject($projectId);

        (new ActiveDatabaseContext())->resolveAndApply();
        $user = ProjectUser::findOne(['id' => $userId]);
        if ($user === null || (int)$user->status !== 1) {
            return null;
        }

        return $user;
    }
}
