<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use app\models\Notification;

/**
 * Notification controller for handling notification API endpoints
 */
class NotificationController extends Controller
{
    public function beforeAction($action)
    {
        $this->enableCsrfValidation = false;
        return parent::beforeAction($action);
    }

    /**
     * Get unread notification count
     */
    public function actionCount()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (Yii::$app->user->isGuest) {
            return ['error' => 'Unauthorized', 'count' => 0];
        }

        $userId = Yii::$app->user->id;
        $count = Notification::getUnreadCount($userId);

        return [
            'success' => true,
            'count' => $count
        ];
    }

    /**
     * Get latest notifications
     */
    public function actionList()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (Yii::$app->user->isGuest) {
            return ['error' => 'Unauthorized', 'notifications' => []];
        }

        $userId = Yii::$app->user->id;
        $limit = Yii::$app->request->get('limit', 20);
        $notifications = Notification::getLatest($userId, $limit);

        $result = [];
        foreach ($notifications as $notification) {
            $result[] = [
                'id' => $notification->id,
                'title' => $notification->title,
                'message' => $notification->message,
                'type' => $notification->type,
                'icon' => $notification->icon,
                'action_text' => $notification->action_text,
                'action_url' => $notification->action_url,
                'is_read' => (bool) $notification->is_read,
                'created_at' => $notification->created_at,
                'relative_time' => $notification->getRelativeTime(),
            ];
        }

        return [
            'success' => true,
            'notifications' => $result,
            'unread_count' => Notification::getUnreadCount($userId)
        ];
    }

    /**
     * Mark a notification as read
     */
    public function actionMarkRead()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (Yii::$app->user->isGuest) {
            return ['error' => 'Unauthorized', 'success' => false];
        }

        $userId = Yii::$app->user->id;
        $id = Yii::$app->request->post('id');

        $notification = Notification::findOne([
            'id' => $id,
            'user_id' => $userId
        ]);

        if (!$notification) {
            return ['error' => 'Notification not found', 'success' => false];
        }

        $notification->markAsRead();

        return [
            'success' => true,
            'message' => 'Notification marked as read'
        ];
    }

    /**
     * Mark all notifications as read
     */
    public function actionMarkAllRead()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (Yii::$app->user->isGuest) {
            return ['error' => 'Unauthorized', 'success' => false];
        }

        $userId = Yii::$app->user->id;
        Notification::markAllAsRead($userId);

        return [
            'success' => true,
            'message' => 'All notifications marked as read'
        ];
    }

    /**
     * Delete a notification
     */
    public function actionDelete()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (Yii::$app->user->isGuest) {
            return ['error' => 'Unauthorized', 'success' => false];
        }

        $userId = Yii::$app->user->id;
        $id = Yii::$app->request->post('id');

        $notification = Notification::findOne([
            'id' => $id,
            'user_id' => $userId
        ]);

        if (!$notification) {
            return ['error' => 'Notification not found', 'success' => false];
        }

        $notification->delete();

        return [
            'success' => true,
            'message' => 'Notification deleted'
        ];
    }
}
