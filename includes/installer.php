<?php
if (!defined('ABSPATH')) exit;

class CommunityHubInstaller {
    
    public static function install() {
        self::create_pages();
        self::create_default_communities();
        self::schedule_ai_tasks();
    }
    
    private static function create_pages() {
        // Create Forum page
        $forum_page = get_page_by_path('forum');
        if (!$forum_page) {
            wp_insert_post(array(
                'post_title' => 'Community Forum',
                'post_content' => '[community_forum]',
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_name' => 'forum'
            ));
        }
        
        // Create Create Post page
        $create_page = get_page_by_path('create-post');
        if (!$create_page) {
            wp_insert_post(array(
                'post_title' => 'Create Post',
                'post_content' => '[create_post]',
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_name' => 'create-post'
            ));
        }
    }
    
    private static function create_default_communities() {
        $communities = array(
            'announcements' => 'Official announcements and updates',
            'development' => 'Development discussions and code sharing',
            'feature-requests' => 'Suggest new features and improvements',
            'bug-reports' => 'Report bugs and technical issues',
            'general' => 'General discussions and community chat'
        );
        
        foreach ($communities as $slug => $description) {
            if (!term_exists($slug, 'community_category')) {
                wp_insert_term($slug, 'community_category', array(
                    'description' => $description,
                    'slug' => $slug
                ));
            }
        }
    }
    
    private static function schedule_ai_tasks() {
        if (!wp_next_scheduled('wp_cron_generate_sample_posts')) {
            wp_schedule_event(time(), 'daily', 'wp_cron_generate_sample_posts');
        }
    }
}
?>