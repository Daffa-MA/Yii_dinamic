<?php

use yii\db\Migration;

class m240101_000007_insert_sample_notifications extends Migration
{
    public function safeUp()
    {
        // Get the first user ID (admin)
        $userId = (new \yii\db\Query())
            ->select('id')
            ->from('users')
            ->where(['username' => 'admin'])
            ->scalar();

        if (!$userId) {
            // If no admin user, use user ID 1
            $userId = 1;
        }

        $now = date('Y-m-d H:i:s');

        $this->batchInsert('notifications', 
            ['user_id', 'title', 'message', 'type', 'icon', 'action_text', 'action_url', 'is_read', 'created_at'],
            [
                // Recent notifications (19 hours ago)
                [
                    $userId,
                    'This Season\'s Top Styles',
                    'Freshen up your look for the season with some brand new picks just for you!',
                    'info',
                    'style',
                    'View',
                    '/form/index',
                    false,
                    date('Y-m-d H:i:s', strtotime('-19 hours'))
                ],
                // 16 days ago
                [
                    $userId,
                    'Purple Clockwork Headphones',
                    'The legendary classic, reborn in purple! Grab this first-ever release by March 31.',
                    'success',
                    'headphones',
                    'View',
                    '/form/index',
                    true,
                    date('Y-m-d H:i:s', strtotime('-16 days'))
                ],
                // 17 days ago - Friend request accepted
                [
                    $userId,
                    'krei @iKwnqz accepted your friend request.',
                    '',
                    'info',
                    'person',
                    'Chat',
                    '/site/profile',
                    true,
                    date('Y-m-d H:i:s', strtotime('-17 days'))
                ],
                // 21 days ago - Another friend request
                [
                    $userId,
                    'Shinaa @JustShinaa accepted your friend request.',
                    '',
                    'info',
                    'person',
                    'Chat',
                    '/site/profile',
                    true,
                    date('Y-m-d H:i:s', strtotime('-21 days'))
                ],
                // 23 days ago - Announcement
                [
                    $userId,
                    'BRIANO10 posted an announcement in USSF - United States Special Forces',
                    'Incoming Update: New features and improvements are coming soon!',
                    'warning',
                    'campaign',
                    'View',
                    '/site/dashboard',
                    true,
                    date('Y-m-d H:i:s', strtotime('-23 days'))
                ],
                // New unread notification - Form submission
                [
                    $userId,
                    'New Form Submission',
                    'You received a new submission on "Contact Form". Click to view details.',
                    'success',
                    'inbox',
                    'View Submission',
                    '/form/submissions/1',
                    false,
                    date('Y-m-d H:i:s', strtotime('-2 hours'))
                ],
                // Unread notification - System update
                [
                    $userId,
                    'System Update Available',
                    'A new version of the platform is available with improved performance and bug fixes.',
                    'info',
                    'system_update',
                    'Learn More',
                    '/site/dashboard',
                    false,
                    date('Y-m-d H:i:s', strtotime('-5 hours'))
                ],
            ]
        );
    }

    public function safeDown()
    {
        $this->delete('notifications', ['user_id' => 1]);
    }
}
