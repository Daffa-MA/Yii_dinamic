<?php

namespace app\services;

use Yii;
use yii\base\Component;

class IconDataService extends Component
{
    public function getIcons($library)
    {
        switch ($library) {
            case 'material-symbols':
                return $this->getMaterialSymbolsIcons();
            case 'tabler':
                return $this->getTablerIcons();
            case 'heroicons':
                return $this->getHeroIcons();
            case 'lucide':
                return $this->getLucideIcons();
            case 'phosphor':
                return $this->getPhosphorIcons();
            case 'remix':
                return $this->getRemixIcons();
            case 'font-awesome':
                return $this->getFontAwesomeIcons();
            case 'bootstrap-icons':
                return $this->getBootstrapIcons();
            default:
                return [];
        }
    }

    public function searchIcons($query, $library, $limit = 200)
    {
        $query = strtolower(trim($query));
        $iconDb = $this->getIcons($library);
        if (empty($iconDb)) return [];

        $results = [];
        foreach ($iconDb as $icon) {
            if ($query) {
                $name = strtolower($icon['name'] ?? '');
                $searchTerms = strtolower($icon['searchTerms'] ?? $name);
                if (strpos($name, $query) === false && strpos($searchTerms, $query) === false) {
                    continue;
                }
            }
            $results[] = $icon;
            if (count($results) >= $limit) break;
        }

        return $results;
    }

    private function getMaterialSymbolsIcons()
    {
        return [
            ['name' => 'home', 'searchTerms' => 'home house building', 'category' => 'action'],
            ['name' => 'search', 'searchTerms' => 'search find look', 'category' => 'action'],
            ['name' => 'settings', 'searchTerms' => 'settings gear configure', 'category' => 'action'],
            ['name' => 'person', 'searchTerms' => 'person user profile account', 'category' => 'social'],
            ['name' => 'star', 'searchTerms' => 'star favorite rating', 'category' => 'toggle'],
            ['name' => 'favorite', 'searchTerms' => 'favorite heart love', 'category' => 'toggle'],
            ['name' => 'delete', 'searchTerms' => 'delete trash remove', 'category' => 'action'],
            ['name' => 'edit', 'searchTerms' => 'edit pencil write', 'category' => 'action'],
            ['name' => 'add', 'searchTerms' => 'add plus new create', 'category' => 'content'],
            ['name' => 'close', 'searchTerms' => 'close x cancel remove', 'category' => 'content'],
            ['name' => 'check', 'searchTerms' => 'check done verify complete', 'category' => 'navigation'],
            ['name' => 'menu', 'searchTerms' => 'menu hamburger navigation', 'category' => 'navigation'],
            ['name' => 'dashboard', 'searchTerms' => 'dashboard grid layout', 'category' => 'action'],
            ['name' => 'email', 'searchTerms' => 'email mail message envelope', 'category' => 'communication'],
            ['name' => 'phone', 'searchTerms' => 'phone call telephone', 'category' => 'communication'],
            ['name' => 'calendar_month', 'searchTerms' => 'calendar month date event', 'category' => 'action'],
            ['name' => 'bar_chart', 'searchTerms' => 'bar chart graph statistics', 'category' => 'editor'],
            ['name' => 'pie_chart', 'searchTerms' => 'pie chart graph statistics', 'category' => 'editor'],
            ['name' => 'trending_up', 'searchTerms' => 'trending up increase growth', 'category' => 'action'],
            ['name' => 'trending_down', 'searchTerms' => 'trending down decrease decline', 'category' => 'action'],
            ['name' => 'shopping_cart', 'searchTerms' => 'shopping cart buy purchase', 'category' => 'action'],
            ['name' => 'notifications', 'searchTerms' => 'notifications bell alert', 'category' => 'notification'],
            ['name' => 'warning', 'searchTerms' => 'warning alert triangle danger', 'category' => 'alert'],
            ['name' => 'info', 'searchTerms' => 'info information help', 'category' => 'alert'],
            ['name' => 'error', 'searchTerms' => 'error mistake bug', 'category' => 'alert'],
            ['name' => 'download', 'searchTerms' => 'download save export', 'category' => 'action'],
            ['name' => 'upload', 'searchTerms' => 'upload import load', 'category' => 'action'],
            ['name' => 'print', 'searchTerms' => 'print printer', 'category' => 'action'],
            ['name' => 'share', 'searchTerms' => 'share send social', 'category' => 'social'],
            ['name' => 'lock', 'searchTerms' => 'lock secure password', 'category' => 'action'],
            ['name' => 'visibility', 'searchTerms' => 'visibility view eye show', 'category' => 'action'],
            ['name' => 'account_balance', 'searchTerms' => 'account balance bank finance', 'category' => 'action'],
            ['name' => 'analytics', 'searchTerms' => 'analytics chart graph gauge', 'category' => 'editor'],
            ['name' => 'api', 'searchTerms' => 'api code developer', 'category' => 'action'],
            ['name' => 'apps', 'searchTerms' => 'apps grid menu', 'category' => 'navigation'],
            ['name' => 'archive', 'searchTerms' => 'archive storage box', 'category' => 'action'],
            ['name' => 'attach_money', 'searchTerms' => 'attach money currency dollar coin', 'category' => 'editor'],
            ['name' => 'autorenew', 'searchTerms' => 'autorenew refresh reload sync', 'category' => 'action'],
            ['name' => 'backup', 'searchTerms' => 'backup save restore', 'category' => 'action'],
            ['name' => 'bookmark', 'searchTerms' => 'bookmark save favorite', 'category' => 'action'],
            ['name' => 'bug_report', 'searchTerms' => 'bug report error issue', 'category' => 'action'],
            ['name' => 'build', 'searchTerms' => 'build tool settings', 'category' => 'action'],
            ['name' => 'business', 'searchTerms' => 'business company office', 'category' => 'places'],
            ['name' => 'cached', 'searchTerms' => 'cached refresh reload', 'category' => 'action'],
            ['name' => 'cake', 'searchTerms' => 'cake birthday celebration', 'category' => 'social'],
            ['name' => 'campaign', 'searchTerms' => 'campaign megaphone announce', 'category' => 'notification'],
            ['name' => 'cancel', 'searchTerms' => 'cancel close x', 'category' => 'navigation'],
            ['name' => 'card_giftcard', 'searchTerms' => 'card giftcard present coupon', 'category' => 'action'],
            ['name' => 'category', 'searchTerms' => 'category group classify', 'category' => 'action'],
            ['name' => 'check_circle', 'searchTerms' => 'check circle done verify', 'category' => 'action'],
            ['name' => 'cloud', 'searchTerms' => 'cloud upload sync', 'category' => 'file'],
            ['name' => 'code', 'searchTerms' => 'code developer brackets', 'category' => 'action'],
            ['name' => 'colorize', 'searchTerms' => 'colorize dropper picker', 'category' => 'editor'],
            ['name' => 'comment', 'searchTerms' => 'comment chat message', 'category' => 'communication'],
            ['name' => 'compare_arrows', 'searchTerms' => 'compare arrows transfer', 'category' => 'action'],
            ['name' => 'construction', 'searchTerms' => 'construction building tools', 'category' => 'action'],
            ['name' => 'contact_mail', 'searchTerms' => 'contact mail email', 'category' => 'communication'],
            ['name' => 'content_copy', 'searchTerms' => 'content copy duplicate', 'category' => 'content'],
            ['name' => 'content_cut', 'searchTerms' => 'content cut scissors', 'category' => 'content'],
            ['name' => 'content_paste', 'searchTerms' => 'content paste clipboard', 'category' => 'content'],
            ['name' => 'credit_card', 'searchTerms' => 'credit card payment', 'category' => 'action'],
            ['name' => 'dangerous', 'searchTerms' => 'dangerous error warning', 'category' => 'alert'],
            ['name' => 'data_exploration', 'searchTerms' => 'data exploration analytics', 'category' => 'editor'],
            ['name' => 'date_range', 'searchTerms' => 'date range calendar period', 'category' => 'action'],
            ['name' => 'design_services', 'searchTerms' => 'design services creative', 'category' => 'action'],
            ['name' => 'devices', 'searchTerms' => 'devices screen responsive', 'category' => 'hardware'],
            ['name' => 'dns', 'searchTerms' => 'dns server network', 'category' => 'hardware'],
            ['name' => 'done_all', 'searchTerms' => 'done all complete multiple', 'category' => 'action'],
            ['name' => 'download_done', 'searchTerms' => 'download done complete', 'category' => 'action'],
            ['name' => 'drafts', 'searchTerms' => 'drafts email unread', 'category' => 'communication'],
            ['name' => 'dynamic_form', 'searchTerms' => 'dynamic form builder', 'category' => 'editor'],
            ['name' => 'eco', 'searchTerms' => 'eco green nature leaf', 'category' => 'social'],
            ['name' => 'euro', 'searchTerms' => 'euro currency money', 'category' => 'editor'],
            ['name' => 'event', 'searchTerms' => 'event calendar date', 'category' => 'action'],
            ['name' => 'exit_to_app', 'searchTerms' => 'exit to app logout', 'category' => 'action'],
            ['name' => 'explore', 'searchTerms' => 'explore compass discover', 'category' => 'action'],
            ['name' => 'extension', 'searchTerms' => 'extension plugin puzzle', 'category' => 'action'],
            ['name' => 'face', 'searchTerms' => 'face smile emoji', 'category' => 'social'],
            ['name' => 'fact_check', 'searchTerms' => 'fact check verify', 'category' => 'action'],
            ['name' => 'family_history', 'searchTerms' => 'family history genealogy', 'category' => 'social'],
            ['name' => 'fast_forward', 'searchTerms' => 'fast forward speed', 'category' => 'av'],
            ['name' => 'fast_rewind', 'searchTerms' => 'fast rewind back', 'category' => 'av'],
            ['name' => 'feedback', 'searchTerms' => 'feedback comment review', 'category' => 'communication'],
            ['name' => 'file_download', 'searchTerms' => 'file download save', 'category' => 'file'],
            ['name' => 'file_present', 'searchTerms' => 'file present document', 'category' => 'file'],
            ['name' => 'file_upload', 'searchTerms' => 'file upload send', 'category' => 'file'],
            ['name' => 'filter_alt', 'searchTerms' => 'filter alt sort', 'category' => 'action'],
            ['name' => 'flag', 'searchTerms' => 'flag country report', 'category' => 'action'],
            ['name' => 'flight', 'searchTerms' => 'flight plane travel', 'category' => 'maps'],
            ['name' => 'folder', 'searchTerms' => 'folder directory', 'category' => 'file'],
            ['name' => 'forum', 'searchTerms' => 'forum chat discussion', 'category' => 'communication'],
            ['name' => 'forward_to_inbox', 'searchTerms' => 'forward to inbox email', 'category' => 'communication'],
            ['name' => 'functions', 'searchTerms' => 'functions formula math', 'category' => 'editor'],
            ['name' => 'gavel', 'searchTerms' => 'gavel law judge', 'category' => 'action'],
            ['name' => 'gesture', 'searchTerms' => 'gesture hand draw', 'category' => 'content'],
            ['name' => 'gif', 'searchTerms' => 'gif animated image', 'category' => 'action'],
            ['name' => 'gite', 'searchTerms' => 'gite house vacation', 'category' => 'places'],
            ['name' => 'grade', 'searchTerms' => 'grade star rating', 'category' => 'action'],
            ['name' => 'group', 'searchTerms' => 'group team users', 'category' => 'social'],
            ['name' => 'groups', 'searchTerms' => 'groups community', 'category' => 'social'],
            ['name' => 'headphones', 'searchTerms' => 'headphones music audio', 'category' => 'hardware'],
            ['name' => 'health_and_safety', 'searchTerms' => 'health safety medical', 'category' => 'social'],
            ['name' => 'help', 'searchTerms' => 'help question support', 'category' => 'action'],
            ['name' => 'history', 'searchTerms' => 'history time recent', 'category' => 'action'],
            ['name' => 'home_work', 'searchTerms' => 'home work building office', 'category' => 'places'],
            ['name' => 'hourglass_empty', 'searchTerms' => 'hourglass empty time waiting', 'category' => 'action'],
            ['name' => 'http', 'searchTerms' => 'http url web', 'category' => 'action'],
            ['name' => 'hub', 'searchTerms' => 'hub network center', 'category' => 'hardware'],
            ['name' => 'image', 'searchTerms' => 'image photo picture', 'category' => 'image'],
            ['name' => 'important_devices', 'searchTerms' => 'important devices sync', 'category' => 'action'],
            ['name' => 'inbox', 'searchTerms' => 'inbox email messages', 'category' => 'communication'],
            ['name' => 'insights', 'searchTerms' => 'insights analytics chart', 'category' => 'editor'],
            ['name' => 'integration_instructions', 'searchTerms' => 'integration instructions code api', 'category' => 'action'],
            ['name' => 'inventory', 'searchTerms' => 'inventory stock warehouse', 'category' => 'action'],
            ['name' => 'key', 'searchTerms' => 'key password security', 'category' => 'action'],
            ['name' => 'label', 'searchTerms' => 'label tag category', 'category' => 'action'],
            ['name' => 'language', 'searchTerms' => 'language translate globe', 'category' => 'action'],
            ['name' => 'layers', 'searchTerms' => 'layers stack multiple', 'category' => 'maps'],
            ['name' => 'leaderboard', 'searchTerms' => 'leaderboard ranking chart', 'category' => 'editor'],
            ['name' => 'lightbulb', 'searchTerms' => 'lightbulb idea suggestion', 'category' => 'action'],
            ['name' => 'link', 'searchTerms' => 'link url hyperlink', 'category' => 'content'],
            ['name' => 'list', 'searchTerms' => 'list checklist todo', 'category' => 'content'],
            ['name' => 'live_help', 'searchTerms' => 'live help support chat', 'category' => 'communication'],
            ['name' => 'local_offer', 'searchTerms' => 'local offer tag discount', 'category' => 'maps'],
            ['name' => 'location_on', 'searchTerms' => 'location on pin map', 'category' => 'maps'],
            ['name' => 'login', 'searchTerms' => 'login sign in', 'category' => 'action'],
            ['name' => 'logout', 'searchTerms' => 'logout sign out', 'category' => 'action'],
            ['name' => 'manage_accounts', 'searchTerms' => 'manage accounts settings', 'category' => 'action'],
            ['name' => 'map', 'searchTerms' => 'map navigation travel', 'category' => 'maps'],
            ['name' => 'markunread', 'searchTerms' => 'markunread email unread', 'category' => 'communication'],
            ['name' => 'mediation', 'searchTerms' => 'mediation negotiate conflict', 'category' => 'action'],
            ['name' => 'meeting_room', 'searchTerms' => 'meeting room office', 'category' => 'places'],
            ['name' => 'message', 'searchTerms' => 'message chat text', 'category' => 'communication'],
            ['name' => 'mic', 'searchTerms' => 'mic microphone voice', 'category' => 'av'],
            ['name' => 'miscellaneous_services', 'searchTerms' => 'miscellaneous services tools', 'category' => 'action'],
            ['name' => 'money', 'searchTerms' => 'money currency finance', 'category' => 'editor'],
            ['name' => 'monitor', 'searchTerms' => 'monitor screen display', 'category' => 'hardware'],
            ['name' => 'mood', 'searchTerms' => 'mood smile happy', 'category' => 'social'],
            ['name' => 'more_horiz', 'searchTerms' => 'more horiz menu dots', 'category' => 'navigation'],
            ['name' => 'more_vert', 'searchTerms' => 'more vert menu dots', 'category' => 'navigation'],
            ['name' => 'movie', 'searchTerms' => 'movie film video', 'category' => 'av'],
            ['name' => 'music_note', 'searchTerms' => 'music note song audio', 'category' => 'av'],
            ['name' => 'navigation', 'searchTerms' => 'navigation arrow direction', 'category' => 'navigation'],
            ['name' => 'near_me', 'searchTerms' => 'near me location nearby', 'category' => 'maps'],
            ['name' => 'network_check', 'searchTerms' => 'network check wifi', 'category' => 'notification'],
            ['name' => 'new_releases', 'searchTerms' => 'new releases alert', 'category' => 'alert'],
            ['name' => 'note', 'searchTerms' => 'note document text', 'category' => 'file'],
            ['name' => 'notifications_active', 'searchTerms' => 'notifications active bell', 'category' => 'notification'],
            ['name' => 'palette', 'searchTerms' => 'palette color design', 'category' => 'image'],
            ['name' => 'pause', 'searchTerms' => 'pause stop break', 'category' => 'av'],
            ['name' => 'payments', 'searchTerms' => 'payments money transaction', 'category' => 'action'],
            ['name' => 'people', 'searchTerms' => 'people users group', 'category' => 'social'],
            ['name' => 'percent', 'searchTerms' => 'percent percentage', 'category' => 'editor'],
            ['name' => 'person_add', 'searchTerms' => 'person add user invite', 'category' => 'social'],
            ['name' => 'person_search', 'searchTerms' => 'person search find user', 'category' => 'social'],
            ['name' => 'pets', 'searchTerms' => 'pets animals dog cat', 'category' => 'action'],
            ['name' => 'pin', 'searchTerms' => 'pin location map', 'category' => 'action'],
            ['name' => 'play_arrow', 'searchTerms' => 'play arrow start', 'category' => 'av'],
            ['name' => 'power', 'searchTerms' => 'power on switch', 'category' => 'action'],
            ['name' => 'precision_manufacturing', 'searchTerms' => 'precision manufacturing industry', 'category' => 'action'],
            ['name' => 'price_change', 'searchTerms' => 'price change up down', 'category' => 'action'],
            ['name' => 'production_quantity_limits', 'searchTerms' => 'production quantity limits stock', 'category' => 'action'],
            ['name' => 'psychology', 'searchTerms' => 'psychology brain mind', 'category' => 'social'],
            ['name' => 'public', 'searchTerms' => 'public globe world', 'category' => 'social'],
            ['name' => 'publish', 'searchTerms' => 'publish upload send', 'category' => 'action'],
            ['name' => 'query_stats', 'searchTerms' => 'query stats analytics database', 'category' => 'editor'],
            ['name' => 'question_answer', 'searchTerms' => 'question answer faq', 'category' => 'communication'],
            ['name' => 'receipt', 'searchTerms' => 'receipt bill invoice', 'category' => 'action'],
            ['name' => 'recent_actors', 'searchTerms' => 'recent actors history', 'category' => 'action'],
            ['name' => 'recommend', 'searchTerms' => 'recommend thumbs up', 'category' => 'social'],
            ['name' => 'record_voice_over', 'searchTerms' => 'record voice over audio', 'category' => 'av'],
            ['name' => 'refresh', 'searchTerms' => 'refresh reload sync', 'category' => 'navigation'],
            ['name' => 'report', 'searchTerms' => 'report problem warning', 'category' => 'content'],
            ['name' => 'request_page', 'searchTerms' => 'request page form', 'category' => 'file'],
            ['name' => 'restart_alt', 'searchTerms' => 'restart alt reset', 'category' => 'action'],
            ['name' => 'reviews', 'searchTerms' => 'reviews feedback rating', 'category' => 'communication'],
            ['name' => 'rocket', 'searchTerms' => 'rocket launch speedy', 'category' => 'action'],
            ['name' => 'schedule', 'searchTerms' => 'schedule time clock', 'category' => 'action'],
            ['name' => 'school', 'searchTerms' => 'school education learn', 'category' => 'social'],
            ['name' => 'science', 'searchTerms' => 'science lab research', 'category' => 'social'],
            ['name' => 'security', 'searchTerms' => 'security lock shield', 'category' => 'action'],
            ['name' => 'send', 'searchTerms' => 'send email message', 'category' => 'communication'],
            ['name' => 'sensors', 'searchTerms' => 'sensors detect measure', 'category' => 'action'],
            ['name' => 'sentiment_satisfied', 'searchTerms' => 'sentiment satisfied happy smile', 'category' => 'social'],
            ['name' => 'sentiment_very_dissatisfied', 'searchTerms' => 'sentiment very dissatisfied sad', 'category' => 'social'],
            ['name' => 'shopping_bag', 'searchTerms' => 'shopping bag purchase', 'category' => 'action'],
            ['name' => 'smart_button', 'searchTerms' => 'smart button click', 'category' => 'action'],
            ['name' => 'sms', 'searchTerms' => 'sms text message', 'category' => 'communication'],
            ['name' => 'sort', 'searchTerms' => 'sort order arrange', 'category' => 'content'],
            ['name' => 'speed', 'searchTerms' => 'speed fast quick', 'category' => 'action'],
            ['name' => 'square', 'searchTerms' => 'square shape box', 'category' => 'action'],
            ['name' => 'stacked_bar_chart', 'searchTerms' => 'stacked bar chart', 'category' => 'editor'],
            ['name' => 'storage', 'searchTerms' => 'storage database server', 'category' => 'hardware'],
            ['name' => 'store', 'searchTerms' => 'store shop market', 'category' => 'maps'],
            ['name' => 'subscriptions', 'searchTerms' => 'subscriptions subscribe', 'category' => 'av'],
            ['name' => 'summarize', 'searchTerms' => 'summarize total sum', 'category' => 'editor'],
            ['name' => 'supervisor_account', 'searchTerms' => 'supervisor account admin', 'category' => 'action'],
            ['name' => 'support', 'searchTerms' => 'support help agent', 'category' => 'action'],
            ['name' => 'sync', 'searchTerms' => 'sync refresh reload', 'category' => 'notification'],
            ['name' => 'table_chart', 'searchTerms' => 'table chart grid', 'category' => 'editor'],
            ['name' => 'table_rows', 'searchTerms' => 'table rows spreadsheet', 'category' => 'editor'],
            ['name' => 'table_view', 'searchTerms' => 'table view data', 'category' => 'editor'],
            ['name' => 'tag', 'searchTerms' => 'tag label category', 'category' => 'content'],
            ['name' => 'task', 'searchTerms' => 'task checklist done', 'category' => 'action'],
            ['name' => 'text_fields', 'searchTerms' => 'text fields input', 'category' => 'editor'],
            ['name' => 'thumb_up', 'searchTerms' => 'thumb up like', 'category' => 'action'],
            ['name' => 'timeline', 'searchTerms' => 'timeline history events', 'category' => 'action'],
            ['name' => 'timer', 'searchTerms' => 'timer clock stopwatch', 'category' => 'av'],
            ['name' => 'today', 'searchTerms' => 'today date current', 'category' => 'action'],
            ['name' => 'toggle_off', 'searchTerms' => 'toggle off switch', 'category' => 'toggle'],
            ['name' => 'toggle_on', 'searchTerms' => 'toggle on switch', 'category' => 'toggle'],
            ['name' => 'topic', 'searchTerms' => 'topic subject category', 'category' => 'file'],
            ['name' => 'touch_app', 'searchTerms' => 'touch app tap', 'category' => 'action'],
            ['name' => 'tour', 'searchTerms' => 'tour travel explore', 'category' => 'action'],
            ['name' => 'toys', 'searchTerms' => 'toys fun games', 'category' => 'hardware'],
            ['name' => 'track_changes', 'searchTerms' => 'track changes update', 'category' => 'action'],
            ['name' => 'translate', 'searchTerms' => 'translate language', 'category' => 'action'],
            ['name' => 'travel_explore', 'searchTerms' => 'travel explore discover', 'category' => 'action'],
            ['name' => 'tune', 'searchTerms' => 'tune settings filter', 'category' => 'action'],
            ['name' => 'turned_in', 'searchTerms' => 'turned in bookmark', 'category' => 'action'],
            ['name' => 'unarchive', 'searchTerms' => 'unarchive restore', 'category' => 'action'],
            ['name' => 'undo', 'searchTerms' => 'undo revert back', 'category' => 'action'],
            ['name' => 'unfold_less', 'searchTerms' => 'unfold less collapse', 'category' => 'navigation'],
            ['name' => 'unfold_more', 'searchTerms' => 'unfold more expand', 'category' => 'navigation'],
            ['name' => 'update', 'searchTerms' => 'update refresh renew', 'category' => 'action'],
            ['name' => 'upload_file', 'searchTerms' => 'upload file import', 'category' => 'file'],
            ['name' => 'verified', 'searchTerms' => 'verified check confirm', 'category' => 'action'],
            ['name' => 'verified_user', 'searchTerms' => 'verified user trust', 'category' => 'action'],
            ['name' => 'video_file', 'searchTerms' => 'video file movie', 'category' => 'file'],
            ['name' => 'video_library', 'searchTerms' => 'video library collection', 'category' => 'av'],
            ['name' => 'videocam', 'searchTerms' => 'videocam camera record', 'category' => 'av'],
            ['name' => 'view_column', 'searchTerms' => 'view column layout', 'category' => 'action'],
            ['name' => 'view_module', 'searchTerms' => 'view module grid', 'category' => 'action'],
            ['name' => 'view_sidebar', 'searchTerms' => 'view sidebar panel', 'category' => 'action'],
            ['name' => 'view_stream', 'searchTerms' => 'view stream list', 'category' => 'action'],
            ['name' => 'visibility_off', 'searchTerms' => 'visibility off hide', 'category' => 'action'],
            ['name' => 'volume_up', 'searchTerms' => 'volume up sound loud', 'category' => 'av'],
            ['name' => 'wallet', 'searchTerms' => 'wallet money payment', 'category' => 'action'],
            ['name' => 'warning_amber', 'searchTerms' => 'warning amber caution', 'category' => 'alert'],
            ['name' => 'web', 'searchTerms' => 'web browser internet', 'category' => 'av'],
            ['name' => 'whatshot', 'searchTerms' => 'whatshot trending fire', 'category' => 'social'],
            ['name' => 'widgets', 'searchTerms' => 'widgets components gadgets', 'category' => 'action'],
            ['name' => 'wifi', 'searchTerms' => 'wifi wireless network', 'category' => 'notification'],
            ['name' => 'work', 'searchTerms' => 'work job briefcase', 'category' => 'action'],
            ['name' => 'workspace_premium', 'searchTerms' => 'workspace premium pro', 'category' => 'action'],
            ['name' => 'wysiwyg', 'searchTerms' => 'wysiwyg editor builder', 'category' => 'editor'],
            ['name' => 'yard', 'searchTerms' => 'yard garden outdoor', 'category' => 'places'],
            ['name' => 'zoom_in', 'searchTerms' => 'zoom in magnify', 'category' => 'action'],
            ['name' => 'zoom_out', 'searchTerms' => 'zoom out reduce', 'category' => 'action'],
            ['name' => 'graduation_cap', 'searchTerms' => 'graduation cap school graduate education university college', 'category' => 'social'],
            ['name' => 'menu_book', 'searchTerms' => 'menu book learn study education', 'category' => 'social'],
            ['name' => 'library_books', 'searchTerms' => 'library books education learn', 'category' => 'social'],
            ['name' => 'auto_stories', 'searchTerms' => 'auto stories book learn education', 'category' => 'social'],
            ['name' => 'cast_for_education', 'searchTerms' => 'cast for education school learn', 'category' => 'social'],
            ['name' => 'terminal', 'searchTerms' => 'terminal code developer command', 'category' => 'action'],
            ['name' => 'developer_mode', 'searchTerms' => 'developer mode code', 'category' => 'hardware'],
            ['name' => 'data_array', 'searchTerms' => 'data array code database', 'category' => 'editor'],
            ['name' => 'data_object', 'searchTerms' => 'data object code json', 'category' => 'editor'],
            ['name' => 'database', 'searchTerms' => 'database storage data', 'category' => 'editor'],
            ['name' => 'cloud_download', 'searchTerms' => 'cloud download sync', 'category' => 'file'],
            ['name' => 'cloud_upload', 'searchTerms' => 'cloud upload sync', 'category' => 'file'],
            ['name' => 'cloud_sync', 'searchTerms' => 'cloud sync refresh', 'category' => 'file'],
            ['name' => 'grid_view', 'searchTerms' => 'grid view layout', 'category' => 'action'],
            ['name' => 'horizontal_rule', 'searchTerms' => 'horizontal rule divider line', 'category' => 'editor'],
            ['name' => 'space_bar', 'searchTerms' => 'space bar spacer gap', 'category' => 'editor'],
            ['name' => 'notes', 'searchTerms' => 'notes text document', 'category' => 'editor'],
            ['name' => 'title', 'searchTerms' => 'title heading text', 'category' => 'editor'],
            ['name' => 'progress_activity', 'searchTerms' => 'progress activity loading', 'category' => 'action'],
            ['name' => 'statistics', 'searchTerms' => 'statistics analytics data', 'category' => 'editor'],
            ['name' => 'calendar_view_month', 'searchTerms' => 'calendar view month', 'category' => 'action'],
            ['name' => 'calendar_today', 'searchTerms' => 'calendar today date', 'category' => 'action'],
            ['name' => 'calendar_clock', 'searchTerms' => 'calendar clock datetime', 'category' => 'action'],
            ['name' => 'bolt', 'searchTerms' => 'bolt lightning fast realtime', 'category' => 'action'],
            ['name' => 'hand_gesture', 'searchTerms' => 'hand gesture manual wave', 'category' => 'social'],
            ['name' => 'auto_fix', 'searchTerms' => 'auto fix magic format', 'category' => 'editor'],
            ['name' => 'numbers', 'searchTerms' => 'numbers numeric digits', 'category' => 'editor'],
            ['name' => 'lowest', 'searchTerms' => 'lowest minimum bottom', 'category' => 'action'],
            ['name' => 'highest', 'searchTerms' => 'highest maximum top', 'category' => 'action'],
            ['name' => 'average', 'searchTerms' => 'average mean rating', 'category' => 'editor'],
            ['name' => 'variables', 'searchTerms' => 'variables code dynamic', 'category' => 'editor'],
            ['name' => 'session', 'searchTerms' => 'session login user', 'category' => 'action'],
            ['name' => 'text_increase', 'searchTerms' => 'text increase font size', 'category' => 'editor'],
            ['name' => 'text_decrease', 'searchTerms' => 'text decrease font size', 'category' => 'editor'],
        ];
    }

    private function getTablerIcons()
    {
        $icons = [];
        $names = ['home', 'user', 'settings', 'search', 'bell', 'mail', 'phone', 'calendar', 'clock', 'chart-bar', 'chart-pie', 'trending-up', 'trending-down', 'star', 'heart', 'plus', 'minus', 'check', 'x', 'arrow-right', 'arrow-left', 'arrow-up', 'arrow-down', 'menu-2', 'dashboard', 'folder', 'file', 'download', 'upload', 'trash', 'edit', 'copy', 'map-pin', 'lock', 'unlock', 'eye', 'eye-off', 'message', 'send', 'refresh', 'filter', 'sort-ascending', 'sort-descending', 'info-circle', 'alert-circle', 'alert-triangle', 'check-circle', 'help-circle', 'question-mark', 'bulb', 'moon', 'sun', 'cloud', 'database', 'code', 'terminal-2', 'brand-github', 'brand-twitter', 'brand-linkedin', 'external-link', 'link', 'paperclip', 'photo', 'video', 'music', 'microphone', 'volume', 'printer', 'share', 'flag', 'tag', 'bookmark', 'thumb-up', 'thumb-down', 'wallet', 'credit-card', 'building', 'building-store', 'map', 'navigation', 'compass', 'gps', 'world', 'globe', 'layers-subtract', 'layers-intersect', 'layers-union', 'layout-dashboard', 'layout-grid', 'layout-list', 'layout-rows', 'border-all', 'border-style', 'border-radius', 'shadow', 'typography', 'font-size', 'bold', 'italic', 'underline', 'strikethrough', 'text-color', 'color-swatch', 'palette', 'gradient', 'opacity', 'rotate', 'crop', 'eraser', 'scissors', 'clipboard', 'clipboard-check', 'clipboard-list', 'report', 'report-analytics', 'report-medical', 'speedometer', 'gauge', 'progress', 'activity', 'pulse', 'heartbeat', 'hand-stop', 'hand-click', 'hand-finger', 'cursor-text', 'cursor-pointer', 'cursor-default', 'selector', 'users', 'user-check', 'user-plus', 'user-minus', 'user-x', 'user-search', 'building-community', 'brand-whatsapp', 'brand-instagram', 'brand-facebook', 'brand-youtube', 'brand-dribbble', 'brand-behance', 'brand-figma', 'brand-xd', 'brand-sketch', 'brand-tailwind', 'brand-bootstrap', 'bulb-off', 'celebration', 'confetti', 'fire', 'rocket', 'ship', 'plane', 'car', 'bus', 'train', 'bike', 'walk', 'run', 'swimming', 'hiking', 'camping', 'coffee', 'cup', 'glass', 'bottle', 'food', 'apple', 'pizza', 'cake', 'gift', 'trophy', 'medal', 'crown', 'diamond', 'coin', 'bank', 'receipt', 'invoice', 'calculator', 'abacus', 'chart-donut', 'chart-line', 'chart-area', 'chart-candle', 'chart-radar', 'chart-scatter', 'chart-bubble', 'chart-infographic', 'chart-ppf', 'chart-sankey', 'chart-funnel', 'chart-histogram', 'chart-boxplot'];
        foreach ($names as $name) {
            $icons[] = [
                'name' => $name,
                'searchTerms' => $name,
                'category' => 'interface',
            ];
        }
        return $icons;
    }

    private function getHeroIcons()
    {
        $icons = [];
        $names = ['home', 'user', 'cog-6-tooth', 'magnifying-glass', 'bell', 'envelope', 'phone', 'calendar', 'clock', 'chart-bar-square', 'chart-pie', 'arrow-trending-up', 'arrow-trending-down', 'star', 'heart', 'plus', 'minus', 'check', 'x-mark', 'arrow-right', 'arrow-left', 'arrow-up', 'arrow-down', 'bars-3', 'squares-2x2', 'folder', 'document', 'arrow-down-tray', 'arrow-up-tray', 'trash', 'pencil-square', 'document-duplicate', 'map-pin', 'lock-closed', 'lock-open', 'eye', 'eye-slash', 'chat-bubble-left-ellipsis', 'paper-airplane', 'arrow-path', 'funnel', 'information-circle', 'exclamation-circle', 'exclamation-triangle', 'check-circle', 'question-mark-circle', 'light-bulb', 'moon', 'sun', 'cloud', 'server', 'code-bracket', 'command-line', 'globe-alt', 'link', 'paper-clip', 'photo', 'video-camera', 'musical-note', 'microphone', 'speaker-wave', 'printer', 'share', 'flag', 'tag', 'bookmark', 'hand-thumb-up', 'hand-thumb-down', 'wallet', 'credit-card', 'building-office', 'building-storefront', 'map', 'compass', 'users', 'user-plus', 'user-minus', 'magnifying-glass-plus', 'magnifying-glass-minus', 'paint-brush', 'swatch', 'no-symbol', 'shield-check', 'shield-exclamation', 'academic-cap', 'book-open', 'cake', 'fire', 'rocket-launch', 'truck', 'trophy', 'gift', 'sparkles', 'bolt', 'banknotes', 'currency-dollar', 'scale', 'wrench', 'wrench-screwdriver', 'cpu-chip', 'device-phone-mobile', 'device-tablet', 'tv', 'window', 'finger-print', 'key', 'adjustments-horizontal', 'adjustments-vertical', 'archive-box', 'arrow-uturn-left', 'arrow-uturn-right', 'backward', 'forward', 'bookmark-slash', 'calendar-days', 'camera', 'chevron-down', 'chevron-up', 'chevron-left', 'chevron-right', 'clipboard', 'clipboard-document', 'clipboard-document-list', 'cloud-arrow-down', 'cloud-arrow-up', 'cube', 'cube-transparent', 'document-chart-bar', 'document-text', 'ellipsis-horizontal', 'ellipsis-vertical', 'face-smile', 'inbox', 'inbox-arrow-down', 'inbox-stack', 'lifebuoy', 'list-bullet', 'paint-brush', 'presentation-chart-bar', 'presentation-chart-line', 'queue-list', 'shopping-cart', 'signal', 'signal-slash', 'square-2-stack', 'stop', 'stop-circle', 'sun', 'swatch', 'table-cells'];
        foreach ($names as $name) {
            $icons[] = [
                'name' => $name,
                'searchTerms' => str_replace('-', ' ', $name),
                'category' => 'outline',
            ];
        }
        return $icons;
    }

    private function getLucideIcons()
    {
        $icons = [];
        $names = ['home', 'user', 'settings', 'search', 'bell', 'mail', 'phone', 'calendar', 'clock', 'bar-chart', 'pie-chart', 'trending-up', 'trending-down', 'star', 'heart', 'plus', 'minus', 'check', 'x', 'arrow-right', 'arrow-left', 'arrow-up', 'arrow-down', 'menu', 'layout-dashboard', 'folder', 'file', 'download', 'upload', 'trash-2', 'pencil', 'copy', 'map-pin', 'lock', 'unlock', 'eye', 'eye-off', 'message-square', 'send', 'refresh-cw', 'filter', 'info', 'alert-circle', 'alert-triangle', 'check-circle-2', 'help-circle', 'lightbulb', 'moon', 'sun', 'cloud', 'database', 'code-2', 'terminal', 'globe', 'link-2', 'paperclip', 'image', 'video', 'music', 'mic', 'volume-2', 'printer', 'share-2', 'flag', 'tag', 'bookmark', 'thumbs-up', 'thumbs-down', 'wallet', 'credit-card', 'building-2', 'store', 'map', 'navigation', 'compass', 'earth', 'layers', 'layout', 'grid-3x3', 'list', 'rows', 'columns', 'border-all', 'radius', 'shadow', 'type', 'font', 'bold', 'italic', 'underline', 'palette', 'brush', 'droplet', 'rotate-3d', 'crop', 'eraser', 'scissors', 'clipboard', 'clipboard-check', 'clipboard-list', 'line-chart', 'area-chart', 'gauge', 'activity', 'pulse', 'users', 'user-plus', 'user-check', 'user-x', 'globe-2', 'instagram', 'twitter', 'github', 'linkedin', 'youtube', 'figma', 'celebration', 'sparkles', 'flame', 'rocket', 'plane', 'truck', 'trophy', 'gift', 'zap', 'wallet-cards', 'banknote', 'graduation-cap', 'book-open', 'school', 'backpack'];
        foreach ($names as $name) {
            $icons[] = [
                'name' => $name,
                'searchTerms' => str_replace('-', ' ', $name),
                'category' => 'interface',
            ];
        }
        return $icons;
    }

    private function getPhosphorIcons()
    {
        return $this->generateSimpleIcons(['house', 'user', 'gear', 'magnifying-glass', 'bell', 'envelope', 'phone', 'calendar', 'clock', 'chart-bar', 'chart-pie', 'trend-up', 'trend-down', 'star', 'heart', 'plus', 'minus', 'check', 'x', 'arrow-right', 'arrow-left', 'arrow-up', 'arrow-down', 'list', 'grid-four', 'folder', 'file', 'download', 'upload', 'trash', 'pencil', 'copy', 'map-pin', 'lock', 'lock-open', 'eye', 'eye-slash', 'chat', 'paper-plane-right', 'arrows-clockwise', 'funnel', 'info', 'warning-circle', 'warning', 'check-circle', 'question', 'lightbulb', 'moon', 'sun', 'cloud', 'database', 'code', 'terminal', 'globe', 'link', 'paperclip', 'image', 'video-camera', 'music-note', 'microphone', 'speaker-high', 'printer', 'share', 'flag', 'tag', 'bookmark', 'thumbs-up', 'thumbs-down', 'wallet', 'credit-card', 'building', 'storefront', 'map-pin-line', 'compass', 'globe-stand', 'users', 'user-plus', 'user-minus', 'user-focus', 'paint-brush', 'swatches', 'gradient', 'opacity', 'hand', 'hand-pointing', 'cursor', 'text-aa', 'text-bolder', 'text-italic', 'text-underline', 'text-strikethrough']);
    }

    private function getRemixIcons()
    {
        return $this->generateSimpleIcons(['home-line', 'user-line', 'settings-line', 'search-line', 'notification-line', 'mail-line', 'phone-line', 'calendar-line', 'clock-line', 'bar-chart-line', 'pie-chart-line', 'line-chart-line', 'star-line', 'heart-line', 'add-line', 'subtract-line', 'check-line', 'close-line', 'arrow-right-line', 'arrow-left-line', 'arrow-up-line', 'arrow-down-line', 'menu-line', 'dashboard-line', 'folder-line', 'file-line', 'download-line', 'upload-line', 'delete-bin-line', 'edit-line', 'file-copy-line', 'map-pin-line', 'lock-line', 'unlock-line', 'eye-line', 'eye-off-line', 'chat-line', 'send-plane-line', 'refresh-line', 'filter-line', 'information-line', 'error-warning-line', 'alert-line', 'check-double-line', 'question-line', 'lightbulb-line', 'moon-line', 'sun-line', 'cloud-line', 'database-2-line', 'code-line', 'terminal-line', 'global-line', 'link', 'attachment-line', 'image-line', 'video-line', 'music-line', 'mic-line', 'volume-up-line', 'printer-line', 'share-line', 'flag-line', 'tag-line', 'bookmark-line', 'thumb-up-line', 'thumb-down-line', 'wallet-line', 'credit-card-line', 'building-line', 'store-line', 'map-line', 'navigation-line', 'compass-line', 'group-line', 'user-add-line', 'user-follow-line', 'user-voice-line', 'brush-line', 'palette-line', 'gradienter-line', 'opacity-line', 'hand-line', 'cursor-line', 'font-size-line', 'bold-line', 'italic-line', 'underline-line']);
    }

    private function getFontAwesomeIcons()
    {
        return $this->generateSimpleIcons(['house', 'user', 'gear', 'magnifying-glass', 'bell', 'envelope', 'phone', 'calendar', 'clock', 'chart-bar', 'chart-pie', 'chart-line', 'star', 'heart', 'plus', 'minus', 'check', 'xmark', 'arrow-right', 'arrow-left', 'arrow-up', 'arrow-down', 'bars', 'grip', 'folder', 'file', 'download', 'upload', 'trash', 'pen', 'copy', 'map-pin', 'lock', 'lock-open', 'eye', 'eye-slash', 'comment', 'paper-plane', 'rotate', 'filter', 'circle-info', 'triangle-exclamation', 'circle-check', 'circle-question', 'lightbulb', 'moon', 'sun', 'cloud', 'database', 'code', 'terminal', 'globe', 'link', 'paperclip', 'image', 'video', 'music', 'microphone', 'volume-high', 'print', 'share', 'flag', 'tag', 'bookmark', 'thumbs-up', 'thumbs-down', 'wallet', 'credit-card', 'building', 'shop', 'map-location', 'location-dot', 'compass', 'users', 'user-plus', 'user-pen', 'paintbrush', 'palette', 'droplet', 'hand', 'hand-pointer', 'text-height', 'bold', 'italic', 'underline']);
    }

    private function getBootstrapIcons()
    {
        return $this->generateSimpleIcons(['house', 'person', 'gear', 'search', 'bell', 'envelope', 'telephone', 'calendar', 'clock', 'bar-chart', 'pie-chart', 'line-chart', 'star', 'heart', 'plus', 'dash', 'check', 'x', 'arrow-right', 'arrow-left', 'arrow-up', 'arrow-down', 'list', 'grid', 'folder', 'file', 'download', 'upload', 'trash', 'pencil', 'files', 'geo-alt', 'lock', 'unlock', 'eye', 'eye-slash', 'chat', 'send', 'arrow-repeat', 'filter', 'info-circle', 'exclamation-triangle', 'check-circle', 'question-circle', 'lightbulb', 'moon', 'sun', 'cloud', 'server', 'code', 'terminal', 'globe', 'link-45deg', 'paperclip', 'image', 'camera-video', 'music-note', 'mic', 'volume-up', 'printer', 'share', 'flag', 'tag', 'bookmark', 'hand-thumbs-up', 'hand-thumbs-down', 'wallet', 'credit-card', 'building', 'shop', 'geo', 'compass', 'people', 'person-add', 'person-check', 'brush', 'palette', 'droplet', 'hand-index', 'cursor', 'font', 'type-bold', 'type-italic', 'type-underline']);
    }

    private function generateSimpleIcons($names)
    {
        $icons = [];
        foreach ($names as $name) {
            $icons[] = [
                'name' => $name,
                'searchTerms' => str_replace('-', ' ', $name),
                'category' => 'default',
            ];
        }
        return $icons;
    }
}
