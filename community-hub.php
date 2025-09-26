<?php
/**
 * Plugin Name: Community Hub
 * Description: A modern community forum plugin with AI integration
 * Version: 1.0.0
 * Author: Your Name
 */

if (!defined('ABSPATH')) exit;

define('COMMUNITY_HUB_URL', plugin_dir_url(__FILE__));
define('COMMUNITY_HUB_PATH', plugin_dir_path(__FILE__));

class CommunityHub {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('community_forum', array($this, 'forum_shortcode'));
        add_shortcode('create_post', array($this, 'create_post_shortcode'));

        // Include additional files
        require_once COMMUNITY_HUB_PATH . 'includes/admin.php';
        require_once COMMUNITY_HUB_PATH . 'includes/ai-helper.php';
        require_once COMMUNITY_HUB_PATH . 'includes/installer.php';
    }
    
    public function init() {
        $this->create_post_type();
        add_action('wp_ajax_vote_post', array($this, 'handle_vote'));
        add_action('wp_ajax_nopriv_vote_post', array($this, 'handle_vote'));
        add_action('wp_ajax_create_post', array($this, 'handle_create_post'));
        add_action('wp_ajax_nopriv_create_post', array($this, 'handle_create_post'));
    }
    
    public function activate() {
        $this->create_tables();
        CommunityHubInstaller::install();
        flush_rewrite_rules();
    }
    
    public function create_tables() {
        global $wpdb;
        
        $votes_table = $wpdb->prefix . 'community_votes';
        $sql = "CREATE TABLE IF NOT EXISTS $votes_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            post_id int(11) NOT NULL,
            user_id int(11) NOT NULL,
            vote_type varchar(10) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_vote (post_id, user_id)
        )";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    public function create_post_type() {
        register_post_type('community_post', array(
            'public' => true,
            'label' => 'Community Posts',
            'supports' => array('title', 'editor', 'author', 'comments'),
            'show_in_menu' => true,
            'menu_icon' => 'dashicons-groups',
            'has_archive' => true,
        ));
        
        register_taxonomy('community_category', 'community_post', array(
            'hierarchical' => true,
            'label' => 'Communities',
            'public' => true,
        ));
    }
    
    public function enqueue_scripts() {
        wp_enqueue_script('community-hub-js', COMMUNITY_HUB_URL . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('community-hub-css', COMMUNITY_HUB_URL . 'assets/style.css', array(), '1.0.0');
        
        wp_localize_script('community-hub-js', 'communityAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('community_nonce')
        ));
    }
    
    public function forum_shortcode($atts) {
        ob_start();
        include COMMUNITY_HUB_PATH . 'templates/forum.php';
        return ob_get_clean();
    }
    
    public function create_post_shortcode($atts) {
        ob_start();
        include COMMUNITY_HUB_PATH . 'templates/create-post.php';
        return ob_get_clean();
    }
    
    public function handle_vote() {
        check_ajax_referer('community_nonce', 'nonce');
        
        global $wpdb;
        $post_id = intval($_POST['post_id']);
        $vote_type = sanitize_text_field($_POST['vote_type']);
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            wp_die(json_encode(array('error' => 'Must be logged in')));
        }
        
        $table = $wpdb->prefix . 'community_votes';
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE post_id = %d AND user_id = %d",
            $post_id, $user_id
        ));
        
        if ($existing) {
            if ($existing->vote_type === $vote_type) {
                $wpdb->delete($table, array('post_id' => $post_id, 'user_id' => $user_id));
            } else {
                $wpdb->update($table, array('vote_type' => $vote_type), 
                    array('post_id' => $post_id, 'user_id' => $user_id));
            }
        } else {
            $wpdb->insert($table, array(
                'post_id' => $post_id,
                'user_id' => $user_id,
                'vote_type' => $vote_type
            ));
        }
        
        $up_votes = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE post_id = %d AND vote_type = 'up'", $post_id
        ));
        $down_votes = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE post_id = %d AND vote_type = 'down'", $post_id
        ));
        
        wp_die(json_encode(array('total' => $up_votes - $down_votes)));
    }
    
    public function handle_create_post() {
        check_ajax_referer('community_nonce', 'nonce');
        
        $title = sanitize_text_field($_POST['title']);
        $content = wp_kses_post($_POST['content']);
        $community = sanitize_text_field($_POST['community']);
        
        $post_id = wp_insert_post(array(
            'post_title' => $title,
            'post_content' => $content,
            'post_type' => 'community_post',
            'post_status' => 'publish',
            'post_author' => get_current_user_id()
        ));
        
        if ($post_id && $community) {
            wp_set_object_terms($post_id, $community, 'community_category');
        }
        
        wp_die(json_encode(array('success' => true, 'post_id' => $post_id)));
    }
}

new CommunityHub();
?>