<?php
if (!defined('ABSPATH')) exit;

$posts = get_posts(array(
    'post_type' => 'community_post',
    'numberposts' => 20,
    'meta_key' => '_community_votes',
    'orderby' => 'meta_value_num date',
    'order' => 'DESC'
));

function get_post_votes($post_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'community_votes';
    $up = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table WHERE post_id = %d AND vote_type = 'up'", $post_id
    ));
    $down = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table WHERE post_id = %d AND vote_type = 'down'", $post_id
    ));
    return $up - $down;
}

function get_user_vote($post_id, $user_id) {
    if (!$user_id) return null;
    global $wpdb;
    $table = $wpdb->prefix . 'community_votes';
    return $wpdb->get_var($wpdb->prepare(
        "SELECT vote_type FROM $table WHERE post_id = %d AND user_id = %d", $post_id, $user_id
    ));
}
?>

<div id="community-hub-container">
    <!-- Theme Toggle -->
    <div class="ch-theme-toggle">
        <button id="ch-theme-btn" class="ch-theme-btn">
            <span class="ch-sun-icon">☀️</span>
            <span class="ch-moon-icon">🌙</span>
        </button>
    </div>

    <!-- Header -->
    <header class="ch-header">
        <div class="ch-header-content">
            <div class="ch-header-left">
                <h1 class="ch-logo">CommunityHub</h1>
                <div class="ch-search-container">
                    <input type="text" placeholder="Search communities, posts..." class="ch-search-input">
                    <span class="ch-search-icon">🔍</span>
                </div>
            </div>
            <div class="ch-header-right">
                <a href="<?php echo get_permalink(get_page_by_path('create-post')); ?>" class="ch-btn ch-btn-primary">
                    ➕ Create Post
                </a>
                <div class="ch-user-menu">
                    <?php if (is_user_logged_in()): ?>
                        <span class="ch-user-avatar">👤</span>
                    <?php else: ?>
                        <a href="<?php echo wp_login_url(); ?>" class="ch-btn ch-btn-outline">Login</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <div class="ch-main-container">
        <div class="ch-content-wrapper">
            <!-- Main Content -->
            <main class="ch-main-content">
                <!-- Sort Tabs -->
                <div class="ch-sort-tabs">
                    <button class="ch-tab ch-tab-active" data-sort="hot">🔥 Hot</button>
                    <button class="ch-tab" data-sort="new">🕒 New</button>
                    <button class="ch-tab" data-sort="top">⭐ Top</button>
                    <button class="ch-tab" data-sort="rising">📈 Rising</button>
                </div>

                <!-- Posts -->
                <div class="ch-posts-container">
                    <?php foreach ($posts as $post): 
                        $votes = get_post_votes($post->ID);
                        $user_vote = get_user_vote($post->ID, get_current_user_id());
                        $communities = get_the_terms($post->ID, 'community_category');
                        $community = $communities ? $communities[0]->name : 'general';
                        $comment_count = get_comments_number($post->ID);
                    ?>
                    <article class="ch-post-card" data-post-id="<?php echo $post->ID; ?>">
                        <div class="ch-post-content">
                            <!-- Voting -->
                            <div class="ch-vote-section">
                                <button class="ch-vote-btn ch-vote-up <?php echo $user_vote === 'up' ? 'ch-voted' : ''; ?>" 
                                        data-vote="up">⬆</button>
                                <span class="ch-vote-count"><?php echo $votes; ?></span>
                                <button class="ch-vote-btn ch-vote-down <?php echo $user_vote === 'down' ? 'ch-voted' : ''; ?>" 
                                        data-vote="down">⬇</button>
                            </div>

                            <!-- Post Details -->
                            <div class="ch-post-details">
                                <div class="ch-post-meta">
                                    <span class="ch-community">r/<?php echo esc_html($community); ?></span>
                                    <span class="ch-author">by u/<?php echo get_the_author_meta('display_name', $post->post_author); ?></span>
                                    <span class="ch-time"><?php echo human_time_diff(strtotime($post->post_date)); ?> ago</span>
                                </div>

                                <h3 class="ch-post-title">
                                    <a href="<?php echo get_permalink($post->ID); ?>"><?php echo esc_html($post->post_title); ?></a>
                                </h3>

                                <div class="ch-post-excerpt">
                                    <?php echo wp_trim_words($post->post_content, 30); ?>
                                </div>

                                <div class="ch-post-actions">
                                    <button class="ch-action-btn">
                                        💬 <?php echo $comment_count; ?> comments
                                    </button>
                                    <button class="ch-action-btn">📤 Share</button>
                                    <button class="ch-action-btn">🚩 Report</button>
                                </div>
                            </div>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
            </main>

            <!-- Sidebar -->
            <aside class="ch-sidebar">
                <div class="ch-widget">
                    <h3 class="ch-widget-title">About Community</h3>
                    <p class="ch-widget-text">Welcome to CommunityHub! Share ideas, discuss topics, and connect with others.</p>
                    <div class="ch-community-stats">
                        <span><?php echo number_format(wp_count_posts('community_post')->publish); ?>k posts</span>
                        <span><?php echo get_user_count(); ?> members</span>
                    </div>
                </div>

                <div class="ch-widget">
                    <h3 class="ch-widget-title">Popular Communities</h3>
                    <div class="ch-communities-list">
                        <?php
                        $terms = get_terms(array(
                            'taxonomy' => 'community_category',
                            'hide_empty' => false,
                        ));
                        foreach ($terms as $term): ?>
                        <div class="ch-community-item">
                            <span class="ch-community-dot"></span>
                            <span class="ch-community-name">r/<?php echo esc_html($term->name); ?></span>
                            <span class="ch-community-count"><?php echo $term->count; ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</div>