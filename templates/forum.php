<?php
if (!defined('ABSPATH')) exit;

// Get posts with better sorting
$sort = isset($_GET['sort']) ? sanitize_text_field($_GET['sort']) : 'hot';
$orderby = 'date';
$meta_key = '';

switch ($sort) {
    case 'hot':
        $meta_key = '_community_votes';
        $orderby = 'meta_value_num date';
        break;
    case 'new':
        $orderby = 'date';
        break;
    case 'top':
        $meta_key = '_community_votes';
        $orderby = 'meta_value_num';
        break;
    case 'rising':
        $orderby = 'date';
        $meta_key = '_community_votes';
        break;
}

$posts = get_posts(array(
    'post_type' => 'community_post',
    'numberposts' => 20,
    'meta_key' => $meta_key,
    'orderby' => $orderby,
    'order' => 'DESC',
    'post_status' => 'publish'
));

function get_post_votes($post_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'community_votes';
    $up = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table WHERE post_id = %d AND vote_type = 'up'", $post_id
    )) ?: 0;
    $down = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table WHERE post_id = %d AND vote_type = 'down'", $post_id
    )) ?: 0;
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

$communities = get_terms(array(
    'taxonomy' => 'community_category',
    'hide_empty' => false,
));

$total_posts = wp_count_posts('community_post')->publish;
$total_users = count_users()['total_users'];
?>

<div id="community-hub-container">
    <!-- Theme Toggle -->
    <div class="ch-theme-toggle">
        <button id="ch-theme-btn" class="ch-theme-btn">
            <span class="ch-icon ch-icon-sun"></span>
            <span class="ch-icon ch-icon-moon" style="display: none;"></span>
        </button>
    </div>

    <!-- Header -->
    <header class="ch-header">
        <div class="ch-header-content">
            <div class="ch-header-left">
                <h1 class="ch-logo">
                    <span class="ch-icon ch-icon-users"></span>
                    CommunityHub
                </h1>
                <div class="ch-search-container">
                    <span class="ch-icon ch-icon-search ch-search-icon"></span>
                    <input type="text" placeholder="Search communities, posts, users..." class="ch-search-input">
                </div>
            </div>
            <div class="ch-header-right">
                <?php if (is_user_logged_in()): ?>
                    <a href="<?php echo get_permalink(get_page_by_path('create-post')); ?>" class="ch-btn ch-btn-primary">
                        <span class="ch-icon ch-icon-plus"></span>
                        Create Post
                    </a>
                    <div class="ch-user-avatar">
                        <span class="ch-icon ch-icon-user"></span>
                    </div>
                <?php else: ?>
                    <a href="<?php echo wp_login_url(get_permalink()); ?>" class="ch-btn ch-btn-outline">
                        <span class="ch-icon ch-icon-user"></span>
                        Login
                    </a>
                    <a href="<?php echo wp_registration_url(); ?>" class="ch-btn ch-btn-primary">
                        <span class="ch-icon ch-icon-plus"></span>
                        Sign Up
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <div class="ch-main-container">
        <div class="ch-content-wrapper">
            <!-- Main Content -->
            <main class="ch-main-content">
                <!-- Community Header -->
                <div class="ch-community-header" style="margin-bottom: 24px;">
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                        <span class="ch-icon ch-icon-users" style="color: var(--ch-primary); font-size: 24px;"></span>
                        <h2 style="font-size: 24px; font-weight: 700; color: var(--ch-text-primary);">Community Forum</h2>
                    </div>
                    <p style="color: var(--ch-text-secondary); margin-bottom: 16px;">
                        Welcome to our community! Share ideas, discuss topics, and connect with others.
                    </p>
                    <div style="display: flex; gap: 24px; font-size: 14px; color: var(--ch-text-muted);">
                        <span>📝 <?php echo number_format($total_posts); ?> posts</span>
                        <span>👥 <?php echo number_format($total_users); ?> members</span>
                        <span>🟢 <?php echo rand(10, 50); ?> online</span>
                    </div>
                </div>

                <!-- Sort Tabs -->
                <div class="ch-sort-tabs">
                    <button class="ch-tab <?php echo $sort === 'hot' ? 'ch-tab-active' : ''; ?>" data-sort="hot">
                        <span class="ch-icon ch-icon-fire"></span>
                        Hot
                    </button>
                    <button class="ch-tab <?php echo $sort === 'new' ? 'ch-tab-active' : ''; ?>" data-sort="new">
                        <span class="ch-icon ch-icon-clock"></span>
                        New
                    </button>
                    <button class="ch-tab <?php echo $sort === 'top' ? 'ch-tab-active' : ''; ?>" data-sort="top">
                        <span class="ch-icon ch-icon-star"></span>
                        Top
                    </button>
                    <button class="ch-tab <?php echo $sort === 'rising' ? 'ch-tab-active' : ''; ?>" data-sort="rising">
                        <span class="ch-icon ch-icon-chart-line"></span>
                        Rising
                    </button>
                </div>

                <!-- Posts -->
                <div class="ch-posts-container">
                    <?php if (empty($posts)): ?>
                        <div class="ch-empty-state">
                            <span class="ch-icon ch-icon-comment"></span>
                            <h3>No posts yet</h3>
                            <p>Be the first to start a discussion!</p>
                            <?php if (is_user_logged_in()): ?>
                                <a href="<?php echo get_permalink(get_page_by_path('create-post')); ?>" class="ch-btn ch-btn-primary" style="margin-top: 16px;">
                                    <span class="ch-icon ch-icon-plus"></span>
                                    Create First Post
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <?php foreach ($posts as $post): 
                            $votes = get_post_votes($post->ID);
                            $user_vote = get_user_vote($post->ID, get_current_user_id());
                            $communities_terms = get_the_terms($post->ID, 'community_category');
                            $community = $communities_terms ? $communities_terms[0]->name : 'general';
                            $comment_count = get_comments_number($post->ID);
                            $post_tags = get_post_meta($post->ID, '_community_tags', true);
                            $tags = $post_tags ? explode(',', $post_tags) : array();
                        ?>
                        <article class="ch-post-card" data-post-id="<?php echo $post->ID; ?>">
                            <div class="ch-post-content">
                                <!-- Voting -->
                                <div class="ch-vote-section">
                                    <button class="ch-vote-btn <?php echo $user_vote === 'up' ? 'ch-voted-up' : ''; ?>" 
                                            data-vote="up" <?php echo !is_user_logged_in() ? 'disabled title="Login to vote"' : ''; ?>>
                                        <span class="ch-icon ch-icon-chevron-up"></span>
                                    </button>
                                    <span class="ch-vote-count"><?php echo $votes; ?></span>
                                    <button class="ch-vote-btn <?php echo $user_vote === 'down' ? 'ch-voted-down' : ''; ?>" 
                                            data-vote="down" <?php echo !is_user_logged_in() ? 'disabled title="Login to vote"' : ''; ?>>
                                        <span class="ch-icon ch-icon-chevron-down"></span>
                                    </button>
                                </div>

                                <!-- Post Details -->
                                <div class="ch-post-details">
                                    <div class="ch-post-meta">
                                        <span class="ch-community">
                                            <span class="ch-icon ch-icon-tag"></span>
                                            r/<?php echo esc_html($community); ?>
                                        </span>
                                        <span>•</span>
                                        <span>
                                            <span class="ch-icon ch-icon-user"></span>
                                            by u/<?php echo get_the_author_meta('display_name', $post->post_author); ?>
                                        </span>
                                        <span>•</span>
                                        <span>
                                            <span class="ch-icon ch-icon-clock"></span>
                                            <?php echo human_time_diff(strtotime($post->post_date)); ?> ago
                                        </span>
                                    </div>

                                    <h3 class="ch-post-title">
                                        <a href="<?php echo get_permalink($post->ID); ?>"><?php echo esc_html($post->post_title); ?></a>
                                    </h3>

                                    <div class="ch-post-excerpt">
                                        <?php echo wp_trim_words($post->post_content, 30); ?>
                                    </div>

                                    <?php if (!empty($tags)): ?>
                                    <div class="ch-post-tags">
                                        <?php foreach ($tags as $tag): ?>
                                            <a href="#" class="ch-tag"><?php echo esc_html(trim($tag)); ?></a>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php endif; ?>

                                    <div class="ch-post-actions">
                                        <a href="<?php echo get_permalink($post->ID); ?>#comments" class="ch-action-btn">
                                            <span class="ch-icon ch-icon-comment"></span>
                                            <?php echo $comment_count; ?> comments
                                        </a>
                                        <button class="ch-action-btn" onclick="navigator.share ? navigator.share({url: '<?php echo get_permalink($post->ID); ?>'}) : copyToClipboard('<?php echo get_permalink($post->ID); ?>')">
                                            <span class="ch-icon ch-icon-share"></span>
                                            Share
                                        </button>
                                        <button class="ch-action-btn">
                                            <span class="ch-icon ch-icon-bookmark"></span>
                                            Save
                                        </button>
                                        <button class="ch-action-btn">
                                            <span class="ch-icon">🏴</span>
                                            Report
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Load More Button -->
                <?php if (count($posts) >= 20): ?>
                <div style="text-align: center; margin-top: 32px;">
                    <button class="ch-btn ch-btn-outline" id="load-more-posts">
                        <span class="ch-icon ch-icon-plus"></span>
                        Load More Posts
                    </button>
                </div>
                <?php endif; ?>
            </main>

            <!-- Sidebar -->
            <aside class="ch-sidebar">
                <!-- About Community -->
                <div class="ch-widget">
                    <h3 class="ch-widget-title">
                        <span class="ch-icon">ℹ️</span>
                        About Community
                    </h3>
                    <p class="ch-widget-text">
                        Welcome to CommunityHub! A place for meaningful discussions, sharing ideas, and connecting with like-minded individuals.
                    </p>
                    <div class="ch-community-stats">
                        <span>
                            <span class="ch-icon">📝</span>
                            <?php echo number_format($total_posts); ?> posts
                        </span>
                        <span>
                            <span class="ch-icon ch-icon-users"></span>
                            <?php echo number_format($total_users); ?> members
                        </span>
                    </div>
                </div>

                <!-- Popular Communities -->
                <div class="ch-widget">
                    <h3 class="ch-widget-title">
                        <span class="ch-icon ch-icon-fire"></span>
                        Popular Communities
                    </h3>
                    <div class="ch-communities-list">
                        <?php foreach ($communities as $community): 
                            $post_count = get_term_meta($community->term_id, 'post_count', true) ?: $community->count;
                        ?>
                        <a href="?community=<?php echo $community->slug; ?>" class="ch-community-item">
                            <div class="ch-community-dot"></div>
                            <span class="ch-community-name">r/<?php echo esc_html($community->name); ?></span>
                            <span class="ch-community-count"><?php echo number_format($post_count); ?></span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="ch-widget">
                    <h3 class="ch-widget-title">
                        <span class="ch-icon">⚡</span>
                        Quick Actions
                    </h3>
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <?php if (is_user_logged_in()): ?>
                            <a href="<?php echo get_permalink(get_page_by_path('create-post')); ?>" class="ch-btn ch-btn-primary">
                                <span class="ch-icon ch-icon-plus"></span>
                                Create Post
                            </a>
                            <button class="ch-btn ch-btn-outline">
                                <span class="ch-icon ch-icon-plus"></span>
                                Create Community
                            </button>
                            <a href="<?php echo admin_url('profile.php'); ?>" class="ch-btn ch-btn-outline">
                                <span class="ch-icon">⚙️</span>
                                Settings
                            </a>
                        <?php else: ?>
                            <a href="<?php echo wp_login_url(get_permalink()); ?>" class="ch-btn ch-btn-primary">
                                <span class="ch-icon ch-icon-user"></span>
                                Login to Participate
                            </a>
                        <?php endif; ?>
                        <button class="ch-btn ch-btn-outline">
                            <span class="ch-icon">❓</span>
                            Help & FAQ
                        </button>
                    </div>
                </div>

                <!-- Community Rules -->
                <div class="ch-widget">
                    <h3 class="ch-widget-title">
                        <span class="ch-icon">⚖️</span>
                        Community Rules
                    </h3>
                    <ul class="ch-rules-list">
                        <li><span class="ch-icon">❤️</span> Be respectful and civil</li>
                        <li><span class="ch-icon">🚫</span> No spam or self-promotion</li>
                        <li><span class="ch-icon">🎯</span> Stay on topic</li>
                        <li><span class="ch-icon">🛡️</span> No personal attacks</li>
                        <li><span class="ch-icon">📚</span> Follow community guidelines</li>
                    </ul>
                </div>
            </aside>
        </div>
    </div>
</div>