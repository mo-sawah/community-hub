<?php
/**
 * Plugin Name: Community Hub Pro
 * Author URI: https://sawahsolutions.com
 * Description: A modern, professional community forum plugin
 * Version: 2.1.2
 * Author: Mohamed Sawah
 */

if (!defined('ABSPATH')) exit;

define('COMMUNITY_HUB_URL', plugin_dir_url(__FILE__));
define('COMMUNITY_HUB_PATH', plugin_dir_path(__FILE__));
define('COMMUNITY_HUB_VERSION', '2.1.2');

class CommunityHubPro {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('template_redirect', array($this, 'template_redirect'));
        add_filter('single_template', array($this, 'load_custom_template'));
        add_filter('template_include', array($this, 'force_community_post_template'), 99);
        add_action('wp_head', array($this, 'force_single_post_assets'));
        add_shortcode('community_forum', array($this, 'forum_shortcode'));
        add_shortcode('create_post', array($this, 'create_post_shortcode'));
        add_shortcode('community_single_post', array($this, 'single_post_shortcode'));

        // Include additional files
        $this->include_files();
    }
    
    private function include_files() {
        $files = array(
            'includes/admin.php',
            'includes/ai-helper.php', 
            'includes/ai-content-generator.php',
            'includes/installer.php'
        );
        
        foreach ($files as $file) {
            $file_path = COMMUNITY_HUB_PATH . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            }
        }
    }
    
    public function init() {
        $this->create_post_type();
        $this->register_ajax_handlers();
    }
    
    public function template_redirect() {
        // Force our styling to load on community post pages
        if (is_singular('community_post')) {
            add_action('wp_head', array($this, 'force_community_styles'));
        }
    }
    
    public function load_custom_template($template) {
        if (is_singular('community_post')) {
            $custom_template = COMMUNITY_HUB_PATH . 'templates/single-community-post.php';
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }
        return $template;
    }
    
    public function force_community_styles() {
        // Force load our CSS and JS on community post pages
        wp_enqueue_style(
            'community-hub-pro-css',
            COMMUNITY_HUB_URL . 'assets/style.css',
            array(),
            COMMUNITY_HUB_VERSION
        );
        
        wp_enqueue_script(
            'community-hub-pro-js',
            COMMUNITY_HUB_URL . 'assets/script.js',
            array('jquery'),
            COMMUNITY_HUB_VERSION,
            true
        );
        
        wp_localize_script('community-hub-pro-js', 'communityHub', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('community_hub_nonce'),
            'user_id' => get_current_user_id(),
            'is_logged_in' => is_user_logged_in()
        ));
    }
    
    private function register_ajax_handlers() {
        add_action('wp_ajax_ch_vote_post', array($this, 'handle_vote'));
        add_action('wp_ajax_nopriv_ch_vote_post', array($this, 'handle_vote'));
        add_action('wp_ajax_ch_create_post', array($this, 'handle_create_post'));
        add_action('wp_ajax_nopriv_ch_create_post', array($this, 'handle_create_post'));
        add_action('wp_ajax_ch_search_posts', array($this, 'handle_search'));
        add_action('wp_ajax_nopriv_ch_search_posts', array($this, 'handle_search'));
        add_action('wp_ajax_ch_add_comment', array($this, 'handle_add_comment'));
        add_action('wp_ajax_nopriv_ch_add_comment', array($this, 'handle_add_comment'));
    }
    
    public function activate() {
        $this->create_tables();
        $this->create_default_communities();
        $this->create_pages();
        flush_rewrite_rules();
    }
    
    private function create_tables() {
        global $wpdb;
        
        $votes_table = $wpdb->prefix . 'community_votes';
        $sql = "CREATE TABLE IF NOT EXISTS $votes_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            post_id int(11) NOT NULL,
            user_id int(11) NOT NULL,
            vote_type varchar(10) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_vote (post_id, user_id),
            KEY post_id (post_id),
            KEY user_id (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    private function create_default_communities() {
        $communities = array(
            'technology' => 'Technology news and discussions',
            'programming' => 'Programming tips and code sharing', 
            'general' => 'General discussions and community chat',
            'announcements' => 'Official announcements and updates'
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
    
    private function create_pages() {
        // Create Forum page
        $forum_page = get_page_by_path('community-forum');
        if (!$forum_page) {
            wp_insert_post(array(
                'post_title' => 'Community Forum',
                'post_content' => '[community_forum]',
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_name' => 'community-forum'
            ));
        }
        
        // Create Create Post page  
        $create_page = get_page_by_path('create-community-post');
        if (!$create_page) {
            wp_insert_post(array(
                'post_title' => 'Create Post',
                'post_content' => '[create_post]',
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_name' => 'create-community-post'
            ));
        }
    }
    
    public function create_post_type() {
        register_post_type('community_post', array(
            'public' => true,
            'label' => 'Community Posts',
            'labels' => array(
                'name' => 'Community Posts',
                'singular_name' => 'Community Post',
                'add_new' => 'Add New Post',
                'add_new_item' => 'Add New Community Post',
                'edit_item' => 'Edit Community Post',
                'new_item' => 'New Community Post',
                'view_item' => 'View Community Post',
                'search_items' => 'Search Community Posts',
                'not_found' => 'No community posts found',
                'not_found_in_trash' => 'No community posts found in trash'
            ),
            'supports' => array('title', 'editor', 'author', 'comments', 'excerpt'),
            'show_in_menu' => true,
            'menu_icon' => 'dashicons-groups',
            'menu_position' => 25,
            'has_archive' => true,
            'rewrite' => array('slug' => 'community-post'),
            'show_in_rest' => true
        ));
        
        register_taxonomy('community_category', 'community_post', array(
            'hierarchical' => true,
            'label' => 'Communities',
            'labels' => array(
                'name' => 'Communities',
                'singular_name' => 'Community',
                'add_new_item' => 'Add New Community',
                'edit_item' => 'Edit Community',
                'update_item' => 'Update Community',
                'view_item' => 'View Community',
                'search_items' => 'Search Communities'
            ),
            'public' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'rewrite' => array('slug' => 'community'),
            'show_in_rest' => true
        ));
    }
    
    public function enqueue_assets() {
        // Only load on community pages
        if (!$this->should_load_assets()) {
            return;
        }
        
        wp_enqueue_style(
            'community-hub-pro-css',
            COMMUNITY_HUB_URL . 'assets/style.css',
            array(),
            COMMUNITY_HUB_VERSION
        );
        
        wp_enqueue_script(
            'community-hub-pro-js',
            COMMUNITY_HUB_URL . 'assets/script.js',
            array('jquery'),
            COMMUNITY_HUB_VERSION,
            true
        );
        
        wp_localize_script('community-hub-pro-js', 'communityHub', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('community_hub_nonce'),
            'user_id' => get_current_user_id(),
            'is_logged_in' => is_user_logged_in()
        ));
    }
    
    private function should_load_assets() {
        global $post;
        
        // Load on community pages
        if (is_page('community-forum') || is_page('create-community-post')) {
            return true;
        }
        
        // Load on community post single pages
        if (is_singular('community_post')) {
            return true;
        }
        
        // Load if shortcode is present
        if (is_a($post, 'WP_Post') && (
            has_shortcode($post->post_content, 'community_forum') || 
            has_shortcode($post->post_content, 'create_post')
        )) {
            return true;
        }
        
        // Load on community post archives
        if (is_post_type_archive('community_post')) {
            return true;
        }
        
        return false;
    }
    
    public function forum_shortcode($atts) {
        $atts = shortcode_atts(array(
            'posts_per_page' => 20,
            'show_sidebar' => true,
            'community' => ''
        ), $atts);
        
        ob_start();
        include COMMUNITY_HUB_PATH . 'templates/forum.php';
        return ob_get_clean();
    }
    
    public function create_post_shortcode($atts) {
        $atts = shortcode_atts(array(
            'redirect_after' => home_url('/community-forum/')
        ), $atts);
        
        ob_start();
        include COMMUNITY_HUB_PATH . 'templates/create-post.php';
        return ob_get_clean();
    }
    
    public function handle_vote() {
        check_ajax_referer('community_hub_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id']);
        $vote_type = sanitize_text_field($_POST['vote_type']);
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            wp_send_json_error('Must be logged in to vote');
        }
        
        if (!in_array($vote_type, array('up', 'down'))) {
            wp_send_json_error('Invalid vote type');
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'community_votes';
        
        // Check existing vote
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT vote_type FROM $table WHERE post_id = %d AND user_id = %d",
            $post_id, $user_id
        ));
        
        if ($existing) {
            if ($existing->vote_type === $vote_type) {
                // Remove vote
                $wpdb->delete($table, array('post_id' => $post_id, 'user_id' => $user_id));
                $user_vote = null;
            } else {
                // Change vote
                $wpdb->update($table, 
                    array('vote_type' => $vote_type),
                    array('post_id' => $post_id, 'user_id' => $user_id)
                );
                $user_vote = $vote_type;
            }
        } else {
            // New vote
            $wpdb->insert($table, array(
                'post_id' => $post_id,
                'user_id' => $user_id,
                'vote_type' => $vote_type
            ));
            $user_vote = $vote_type;
        }
        
        // Get updated totals
        $up_votes = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE post_id = %d AND vote_type = 'up'",
            $post_id
        ));
        
        $down_votes = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE post_id = %d AND vote_type = 'down'",
            $post_id
        ));
        
        wp_send_json_success(array(
            'total' => intval($up_votes) - intval($down_votes),
            'user_vote' => $user_vote
        ));
    }
    
    public function handle_create_post() {
        check_ajax_referer('community_hub_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Must be logged in to create posts');
        }
        
        $title = sanitize_text_field($_POST['title']);
        $content = wp_kses_post($_POST['content']);
        $community = sanitize_text_field($_POST['community']);
        $tags = sanitize_text_field($_POST['tags']);
        $post_type_meta = sanitize_text_field($_POST['post_type']);
        
        // Validation
        if (strlen($title) < 5) {
            wp_send_json_error('Title must be at least 5 characters');
        }
        
        if (strlen($content) < 10) {
            wp_send_json_error('Content must be at least 10 characters');
        }
        
        if (empty($community)) {
            wp_send_json_error('Please select a community');
        }
        
        // Create post
        $post_id = wp_insert_post(array(
            'post_title' => $title,
            'post_content' => $content,
            'post_type' => 'community_post',
            'post_status' => 'publish',
            'post_author' => get_current_user_id()
        ));
        
        if (is_wp_error($post_id)) {
            wp_send_json_error('Failed to create post');
        }
        
        // Set community
        if ($community) {
            wp_set_object_terms($post_id, $community, 'community_category');
        }
        
        // Set meta data
        if ($tags) {
            update_post_meta($post_id, '_community_tags', $tags);
        }
        
        if ($post_type_meta) {
            update_post_meta($post_id, '_community_post_type', $post_type_meta);
        }
        
        // Initialize counters
        update_post_meta($post_id, '_community_views', 0);
        
        // Return normal WordPress permalink
        wp_send_json_success(array(
            'post_id' => $post_id,
            'redirect' => get_permalink($post_id)
        ));
    }
    
    public function handle_search() {
        check_ajax_referer('community_hub_nonce', 'nonce');
        
        $query = sanitize_text_field($_POST['query']);
        $community = sanitize_text_field($_POST['community']);
        
        if (strlen($query) < 2) {
            wp_send_json_error('Query too short');
        }
        
        $args = array(
            'post_type' => 'community_post',
            'posts_per_page' => 20,
            's' => $query,
            'post_status' => 'publish'
        );
        
        if ($community) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'community_category',
                    'field' => 'slug',
                    'terms' => $community
                )
            );
        }
        
        $posts = get_posts($args);
        $results = array();
        
        foreach ($posts as $post) {
            $results[] = array(
                'id' => $post->ID,
                'title' => $post->post_title,
                'excerpt' => wp_trim_words($post->post_content, 30),
                'url' => get_permalink($post->ID),
                'author' => get_the_author_meta('display_name', $post->post_author),
                'date' => human_time_diff(strtotime($post->post_date))
            );
        }
        
        wp_send_json_success($results);
    }
    
    public function handle_add_comment() {
        check_ajax_referer('community_hub_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Must be logged in to comment');
        }
        
        $post_id = intval($_POST['post_id']);
        $content = sanitize_textarea_field($_POST['content']);
        $parent_id = intval($_POST['parent_id']) ?: 0;
        
        if (strlen($content) < 5) {
            wp_send_json_error('Comment must be at least 5 characters');
        }
        
        $comment_data = array(
            'comment_post_ID' => $post_id,
            'comment_content' => $content,
            'comment_parent' => $parent_id,
            'user_id' => get_current_user_id(),
            'comment_approved' => 1
        );
        
        $comment_id = wp_insert_comment($comment_data);
        
        if ($comment_id) {
            $comment = get_comment($comment_id);
            wp_send_json_success(array(
                'comment_id' => $comment_id,
                'html' => $this->render_comment($comment)
            ));
        } else {
            wp_send_json_error('Failed to create comment');
        }
    }
    
    private function render_comment($comment) {
        ob_start();
        ?>
        <div class="ch-comment" data-comment-id="<?php echo $comment->comment_ID; ?>">
            <div class="ch-comment-avatar">
                <img src="<?php echo get_avatar_url($comment->user_id ?: $comment->comment_author_email, array('size' => 32)); ?>" 
                     alt="Avatar">
            </div>
            <div class="ch-comment-content">
                <div class="ch-comment-meta">
                    <span class="ch-comment-author">u/<?php echo esc_html($comment->comment_author); ?></span>
                    <span>•</span>
                    <span class="ch-comment-time">just now</span>
                </div>
                <div class="ch-comment-text">
                    <?php echo wpautop($comment->comment_content); ?>
                </div>
                <div class="ch-comment-actions">
                    <button class="ch-comment-vote-btn" data-vote="up">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="m18 15-6-6-6 6"/>
                        </svg>
                    </button>
                    <span class="ch-comment-votes">0</span>
                    <button class="ch-comment-vote-btn" data-vote="down">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="m6 9 6 6 6-6"/>
                        </svg>
                    </button>
                    <button class="ch-comment-reply-btn">Reply</button>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function force_community_post_template($template) {
        if (is_singular('community_post')) {
            $custom_template = COMMUNITY_HUB_PATH . 'templates/single-community-post.php';
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }
        return $template;
    }

    public function force_single_post_assets() {
        if (is_singular('community_post')) {
            wp_enqueue_style(
                'community-hub-pro-css',
                COMMUNITY_HUB_URL . 'assets/style.css',
                array(),
                COMMUNITY_HUB_VERSION
            );
            
            wp_enqueue_script(
                'community-hub-pro-js',
                COMMUNITY_HUB_URL . 'assets/script.js',
                array('jquery'),
                COMMUNITY_HUB_VERSION,
                true
            );
            
            wp_localize_script('community-hub-pro-js', 'communityHub', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('community_hub_nonce'),
                'user_id' => get_current_user_id(),
                'is_logged_in' => is_user_logged_in()
            ));
        }
    }

    public function single_post_shortcode($atts) {
        $atts = shortcode_atts(array(
            'post_id' => get_the_ID(), // Default to current post
            'show_comments' => 'true',
            'show_sidebar' => 'true'
        ), $atts);
        
        $post_id = intval($atts['post_id']);
        $show_comments = $atts['show_comments'] === 'true';
        $show_sidebar = $atts['show_sidebar'] === 'true';
        
        // Get the post
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'community_post') {
            return '<p>Community post not found.</p>';
        }
        
        // Helper functions for votes
        $votes = $this->get_post_votes($post_id);
        $user_vote = $this->get_user_vote($post_id, get_current_user_id());
        
        // Get post data
        $communities_terms = get_the_terms($post_id, 'community_category');
        $community = $communities_terms ? $communities_terms[0]->name : 'general';
        $community_slug = $communities_terms ? $communities_terms[0]->slug : 'general';
        $post_tags = get_post_meta($post_id, '_community_tags', true);
        $tags = $post_tags ? array_map('trim', explode(',', $post_tags)) : array();
        
        // Update view count
        $views = get_post_meta($post_id, '_community_views', true) ?: 0;
        update_post_meta($post_id, '_community_views', $views + 1);
        
        // Get comments if needed
        $comments = array();
        if ($show_comments) {
            $comments = get_comments(array(
                'post_id' => $post_id,
                'status' => 'approve',
                'order' => 'ASC'
            ));
        }
        
        // Start output buffering
        ob_start();
        ?>
        
        <div class="community-single-post-container" data-post-id="<?php echo $post_id; ?>">
            <div class="csp-layout <?php echo $show_sidebar ? 'with-sidebar' : 'full-width'; ?>">
                <main class="csp-main">
                    <!-- Breadcrumb -->
                    <div class="csp-breadcrumb">
                        <a href="<?php echo home_url('/community-forum/'); ?>">Forum</a>
                        <span>&gt;</span>
                        <a href="<?php echo add_query_arg('community', $community_slug, home_url('/community-forum/')); ?>">
                            r/<?php echo esc_html($community); ?>
                        </a>
                        <span>&gt;</span>
                        <span>Post</span>
                    </div>

                    <!-- Post Content -->
                    <article class="csp-post" data-post-id="<?php echo $post_id; ?>">
                        <div class="csp-post-header">
                            <div class="csp-post-meta">
                                <a href="<?php echo add_query_arg('community', $community_slug, home_url('/community-forum/')); ?>" class="csp-community-tag">
                                    r/<?php echo esc_html($community); ?>
                                </a>
                                <span>&bull;</span>
                                <span>by u/<?php echo get_the_author_meta('display_name', $post->post_author); ?></span>
                                <span>&bull;</span>
                                <span><?php echo human_time_diff(strtotime($post->post_date)); ?> ago</span>
                                <span>&bull;</span>
                                <span><?php echo $views + 1; ?> views</span>
                            </div>

                            <h1 class="csp-post-title"><?php echo esc_html($post->post_title); ?></h1>

                            <?php if (!empty($tags)): ?>
                            <div class="csp-post-tags">
                                <?php foreach ($tags as $tag): ?>
                                    <span class="csp-tag"><?php echo esc_html($tag); ?></span>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="csp-post-content">
                            <!-- Voting -->
                            <div class="csp-vote-section">
                                <button class="csp-vote-btn <?php echo $user_vote === 'up' ? 'voted-up' : ''; ?>" 
                                        data-vote="up" data-post-id="<?php echo $post_id; ?>"
                                        <?php echo !is_user_logged_in() ? 'disabled title="Login to vote"' : ''; ?>>
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="m18 15-6-6-6 6"/>
                                    </svg>
                                </button>
                                <span class="csp-vote-count"><?php echo $votes; ?></span>
                                <button class="csp-vote-btn <?php echo $user_vote === 'down' ? 'voted-down' : ''; ?>" 
                                        data-vote="down" data-post-id="<?php echo $post_id; ?>"
                                        <?php echo !is_user_logged_in() ? 'disabled title="Login to vote"' : ''; ?>>
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="m6 9 6 6 6-6"/>
                                    </svg>
                                </button>
                            </div>

                            <!-- Post Body -->
                            <div class="csp-post-body">
                                <?php echo wpautop($post->post_content); ?>
                            </div>
                        </div>

                        <!-- Post Actions -->
                        <div class="csp-post-actions">
                            <button class="csp-action-btn" onclick="sharePost('<?php echo get_permalink($post_id); ?>')">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M4 12v8a2 2 0 002 2h12a2 2 0 002-2v-8M16 6l-4-4-4 4M12 2v13"/>
                                </svg>
                                Share
                            </button>
                            <button class="csp-action-btn" onclick="savePost(<?php echo $post_id; ?>)">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="m19 21-7-4-7 4V5a2 2 0 012-2h10a2 2 0 012 2v16z"/>
                                </svg>
                                Save
                            </button>
                            <button class="csp-action-btn">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M3 21l1.9-5.7a8.5 8.5 0 113.8 3.8z"/>
                                </svg>
                                Report
                            </button>
                        </div>
                    </article>

                    <?php if ($show_comments): ?>
                    <!-- Comments Section -->
                    <section class="csp-comments" id="comments">
                        <div class="csp-comments-header">
                            <h3>
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/>
                                </svg>
                                <?php echo count($comments); ?> Comments
                            </h3>
                            <div class="csp-comments-sort">
                                <select id="comments-sort">
                                    <option value="oldest">Oldest First</option>
                                    <option value="newest">Newest First</option>
                                    <option value="top">Top Comments</option>
                                </select>
                            </div>
                        </div>

                        <!-- Add Comment Form -->
                        <?php if (is_user_logged_in()): ?>
                        <div class="csp-add-comment">
                            <div class="csp-comment-avatar">
                                <img src="<?php echo get_avatar_url(get_current_user_id(), array('size' => 32)); ?>" 
                                    alt="Your Avatar">
                            </div>
                            <form class="csp-comment-form" id="comment-form">
                                <textarea placeholder="What are your thoughts?" rows="3" id="comment-content" required></textarea>
                                <div class="csp-comment-actions">
                                    <button type="button" class="csp-btn csp-btn-outline">Cancel</button>
                                    <button type="submit" class="csp-btn csp-btn-primary">Comment</button>
                                </div>
                            </form>
                        </div>
                        <?php else: ?>
                        <div class="csp-login-prompt">
                            <p><a href="<?php echo wp_login_url(get_permalink($post_id)); ?>">Login</a> to join the discussion</p>
                        </div>
                        <?php endif; ?>

                        <!-- Comments List -->
                        <div class="csp-comments-list">
                            <?php if (empty($comments)): ?>
                                <div class="csp-empty-comments">
                                    <div class="csp-empty-icon">💬</div>
                                    <h4>No comments yet</h4>
                                    <p>Be the first to share your thoughts!</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($comments as $comment): ?>
                                <div class="csp-comment" data-comment-id="<?php echo $comment->comment_ID; ?>">
                                    <div class="csp-comment-avatar">
                                        <img src="<?php echo get_avatar_url($comment->user_id ?: $comment->comment_author_email, array('size' => 32)); ?>" 
                                            alt="Avatar">
                                    </div>
                                    <div class="csp-comment-content">
                                        <div class="csp-comment-meta">
                                            <span class="csp-comment-author">u/<?php echo esc_html($comment->comment_author); ?></span>
                                            <span>&bull;</span>
                                            <span class="csp-comment-time"><?php echo human_time_diff(strtotime($comment->comment_date)); ?> ago</span>
                                        </div>
                                        <div class="csp-comment-text">
                                            <?php echo wpautop($comment->comment_content); ?>
                                        </div>
                                        <div class="csp-comment-actions">
                                            <button class="csp-comment-vote-btn" data-vote="up">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="m18 15-6-6-6 6"/>
                                                </svg>
                                            </button>
                                            <span class="csp-comment-votes">0</span>
                                            <button class="csp-comment-vote-btn" data-vote="down">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="m6 9 6 6 6-6"/>
                                                </svg>
                                            </button>
                                            <button class="csp-comment-reply-btn">Reply</button>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </section>
                    <?php endif; ?>
                </main>

                <?php if ($show_sidebar): ?>
                <!-- Sidebar -->
                <aside class="csp-sidebar">
                    <!-- About Community -->
                    <div class="csp-widget">
                        <h3 class="csp-widget-title">
                            <span>ℹ️</span>
                            About r/<?php echo esc_html($community); ?>
                        </h3>
                        <p class="csp-widget-text">
                            <?php 
                            $term = get_term_by('slug', $community_slug, 'community_category');
                            echo $term ? esc_html($term->description) : 'Community discussions and shared interests.';
                            ?>
                        </p>
                        <div class="csp-community-stats">
                            <div class="csp-stat">
                                <span>📝</span>
                                <span><?php echo number_format(wp_count_posts('community_post')->publish); ?> posts</span>
                            </div>
                            <div class="csp-stat">
                                <span>👥</span>
                                <span><?php echo number_format(count_users()['total_users']); ?> members</span>
                            </div>
                        </div>
                    </div>

                    <!-- Related Posts -->
                    <div class="csp-widget">
                        <h3 class="csp-widget-title">
                            <span>🔗</span>
                            Related Posts
                        </h3>
                        <?php
                        $related_posts = get_posts(array(
                            'post_type' => 'community_post',
                            'posts_per_page' => 5,
                            'post__not_in' => array($post_id),
                            'tax_query' => array(
                                array(
                                    'taxonomy' => 'community_category',
                                    'field' => 'slug',
                                    'terms' => $community_slug
                                )
                            )
                        ));
                        ?>
                        <div class="csp-related-posts">
                            <?php if (!empty($related_posts)): ?>
                                <?php foreach ($related_posts as $related_post): ?>
                                <a href="<?php echo get_permalink($related_post->ID); ?>" class="csp-related-post">
                                    <h4><?php echo esc_html(wp_trim_words($related_post->post_title, 8)); ?></h4>
                                    <div class="csp-related-meta">
                                        <?php echo human_time_diff(strtotime($related_post->post_date)); ?> ago
                                    </div>
                                </a>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p>No related posts found.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Community Rules -->
                    <div class="csp-widget">
                        <h3 class="csp-widget-title">
                            <span>⚖️</span>
                            Community Rules
                        </h3>
                        <ul class="csp-rules-list">
                            <li><span>❤️</span> Be respectful and civil</li>
                            <li><span>🚫</span> No spam or self-promotion</li>
                            <li><span>🎯</span> Stay on topic</li>
                            <li><span>🛡️</span> No personal attacks</li>
                            <li><span>📚</span> Follow community guidelines</li>
                        </ul>
                    </div>
                </aside>
                <?php endif; ?>
            </div>
        </div>

        <script>
        // Add JavaScript for functionality
        function sharePost(url) {
            if (navigator.share) {
                navigator.share({
                    title: 'Check out this post',
                    url: url
                });
            } else {
                navigator.clipboard.writeText(url).then(() => {
                    alert('Link copied to clipboard!');
                });
            }
        }

        function savePost(postId) {
            alert('Post saved!');
        }

        // Comment form handling
        jQuery(document).ready(function($) {
            $('#comment-form').on('submit', function(e) {
                e.preventDefault();
                
                const content = $('#comment-content').val().trim();
                if (!content) {
                    alert('Please enter a comment');
                    return;
                }
                
                const $btn = $(this).find('button[type="submit"]');
                $btn.text('Posting...');
                
                $.ajax({
                    url: communityHub.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'ch_add_comment',
                        post_id: <?php echo $post_id; ?>,
                        content: content,
                        parent_id: 0,
                        nonce: communityHub.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#comment-content').val('');
                            location.reload(); // Simple reload to show new comment
                        } else {
                            alert(response.data || 'Failed to post comment');
                        }
                    },
                    error: function() {
                        alert('Failed to post comment. Please try again.');
                    },
                    complete: function() {
                        $btn.text('Comment');
                    }
                });
            });

            // Voting functionality
            $('.csp-vote-btn').on('click', function() {
                const $btn = $(this);
                const postId = $btn.data('post-id');
                const voteType = $btn.data('vote');
                
                $.ajax({
                    url: communityHub.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'ch_vote_post',
                        post_id: postId,
                        vote_type: voteType,
                        nonce: communityHub.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            const data = response.data;
                            $('.csp-vote-count').text(data.total);
                            
                            // Reset vote classes
                            $('.csp-vote-btn').removeClass('voted-up voted-down');
                            
                            // Apply new vote state
                            if (data.user_vote === 'up') {
                                $('[data-vote="up"]').addClass('voted-up');
                            } else if (data.user_vote === 'down') {
                                $('[data-vote="down"]').addClass('voted-down');
                            }
                        }
                    }
                });
            });
        });
        </script>
        
        <?php
        return ob_get_clean();
    }

    // Helper function to get post votes
    private function get_post_votes($post_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'community_votes';
        
        $up = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE post_id = %d AND vote_type = 'up'", 
            $post_id
        )) ?: 0;
        
        $down = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE post_id = %d AND vote_type = 'down'", 
            $post_id
        )) ?: 0;
        
        return intval($up) - intval($down);
    }

    // Helper function to get user vote
    private function get_user_vote($post_id, $user_id) {
        if (!$user_id) return null;
        
        global $wpdb;
        $table = $wpdb->prefix . 'community_votes';
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT vote_type FROM $table WHERE post_id = %d AND user_id = %d", 
            $post_id, $user_id
        ));
    }
}

// Initialize the plugin
new CommunityHubPro();