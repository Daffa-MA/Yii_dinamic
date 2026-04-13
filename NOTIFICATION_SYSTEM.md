# Notification System

## Overview
A complete notification system has been implemented for your Yii2 application. It includes:

- **Database**: Stores user notifications with metadata
- **Backend API**: RESTful endpoints for notification operations
- **Frontend UI**: Modern notification panel with badge indicator
- **Real-time updates**: Auto-refreshes unread count every 30 seconds

## Features

✅ **Notification Panel**: Dropdown panel showing all notifications  
✅ **Unread Badge**: Red badge on notification icon showing unread count  
✅ **Mark as Read**: Click notification or use "Mark all read" button  
✅ **Delete Notifications**: Three-dot menu to delete individual notifications  
✅ **Action Buttons**: Each notification can have a "View", "Chat", or custom action button  
✅ **Time Indicators**: Relative time display (e.g., "2h", "3d")  
✅ **Auto-refresh**: Checks for new notifications every 30 seconds  
✅ **Responsive UI**: Matches your existing design system

## Setup Instructions

### 1. Run Database Migration
```bash
yii migrate/up
```

This will create the `notifications` table in your database.

### 2. Insert Sample Data (Optional)
```bash
yii migrate/up --migrationPath=@app/migrations
```

This will insert sample notifications for testing.

### 3. Clear Cache (if needed)
```bash
yii cache/flush-all
```

## Usage

### Creating Notifications Programmatically

```php
use app\models\Notification;

// Create a simple notification
Notification::create(
    $userId,
    'New Message',
    'You have received a new message from John.',
    Notification::TYPE_INFO,
    [
        'icon' => 'message',  // Material icon name or image URL
        'action_text' => 'View',
        'action_url' => '/messages/123'
    ]
);

// Create success notification
Notification::create(
    $userId,
    'Form Submitted',
    'Your form has been successfully submitted.',
    Notification::TYPE_SUCCESS,
    ['icon' => 'check_circle']
);

// Create warning notification
Notification::create(
    $userId,
    'Action Required',
    'Please verify your email address.',
    Notification::TYPE_WARNING,
    ['icon' => 'warning', 'action_text' => 'Verify Now', 'action_url' => '/verify-email']
);

// Create error notification
Notification::create(
    $userId,
    'Upload Failed',
    'File upload failed. Please try again.',
    Notification::TYPE_ERROR,
    ['icon' => 'error']
);
```

### Notification Types

- `TYPE_INFO` - Blue/info notifications
- `TYPE_SUCCESS` - Green/success notifications  
- `TYPE_WARNING` - Amber/warning notifications
- `TYPE_ERROR` - Red/error notifications

### API Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/notification/count` | GET | Get unread notification count |
| `/api/notification/list` | GET | Get list of notifications |
| `/api/notification/mark-read` | POST | Mark single notification as read |
| `/api/notification/mark-all-read` | POST | Mark all notifications as read |
| `/api/notification/delete` | POST | Delete a notification |

## UI Components

### Notification Button
Located in the top navigation bar of all pages:
```html
<button class="notification-button material-symbols-outlined">notifications</button>
```

### Notification Badge
Automatically appears when there are unread notifications:
- Shows count (or "99+" if more than 99)
- Positioned at top-right of notification icon
- Red background with white text

### Notification Panel
Opens when clicking the notification button:
- Header with "Notifications" title and "Mark all read" button
- Scrollable list of notifications
- Each notification shows:
  - Icon or avatar
  - Title and message
  - Relative time (e.g., "2h", "3d")
  - Action button (if configured)
  - Delete button (three-dot menu)

## Files Created/Modified

### New Files
- `models/Notification.php` - Notification model
- `controllers/NotificationController.php` - API controller
- `web/js/notifications.js` - Frontend JavaScript
- `migrations/m240101_000006_create_notification_table.php` - DB migration
- `migrations/m240101_000007_insert_sample_notifications.php` - Sample data

### Modified Files
- `config/web.php` - Added URL routes
- `database_setup.sql` - Added notifications table
- All view files with notification buttons updated

## Customization

### Styling
The notification panel uses your existing Tailwind CSS configuration and color scheme. Modify in `web/js/notifications.js`.

### Icons
Icons can be:
1. Material icon names (e.g., 'message', 'check_circle')
2. Image URLs (e.g., 'https://example.com/icon.png')
3. Data URIs for base64 images

### Polling Interval
Change the refresh interval in `web/js/notifications.js`:
```javascript
// Default: 30 seconds
setInterval(() => this.updateUnreadCount(), 30000);
```

## Troubleshooting

### Badge not showing
- Check if CSRF token is registered: `$this->registerCsrfMetaTags()`
- Verify user is logged in
- Check browser console for errors

### Panel not opening
- Ensure `notifications.js` is loaded
- Check if `.notification-button` class exists on your button
- Verify no JavaScript errors in console

### API returning 403 Unauthorized
- Ensure user is authenticated
- Check CSRF token in request headers

### Database errors
- Run migrations: `yii migrate/up`
- Check database connection in `config/db.php`

## Notes

- Notifications are user-specific (each user only sees their own)
- Deleting a user cascades to delete their notifications
- Old notifications remain in database until manually deleted
- The system uses your existing Material Symbols icon library
