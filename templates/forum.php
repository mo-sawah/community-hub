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
            <i class="fas fa-sun"></i>
            <i class="fas fa-moon" style="display: none;"></i>
        </button>
    </div>

    <!-- Header -->
    <header class="ch-header">
        <div class="ch-header-content">
            <div class="ch-header-left">
                <h1 class="ch-logo">
                    <i class="fas fa-users"></i>
                    CommunityHub
                </h1>
                <div class="ch-search-container">
                    <i class="fas fa-search ch-search-icon"></i>
                    <input type="text" placeholder="Search communities, posts, users..." class="ch-search-input">
                </div>
            </div>
            <div class="ch-header-right">
                <?php if (is_user_logged_in()): ?>
                    <a href="<?php echo get_permalink(get_page_by_path('create-post')); ?>" class="ch-btn ch-btn-primary">
                        <i class="fas fa-plus"></i>
                        Create Post
                    </a>
                    <div class="ch-user-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                <?php else: ?>
                    <a href="<?php echo wp_login_url(get_permalink()); ?>" class="ch-btn ch-btn-outline">
                        <i class="fas fa-sign-in-alt"></i>
                        Login
                    </a>
                    <a href="<?php echo wp_registration_url(); ?>" class="ch-btn ch-btn-primary">
                        <i class="fas fa-user-plus"></i>
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
                        <i class="fas fa-users" style="color: var(--ch-primary); font-size: 24px;"></i>
                        <h2 style="font-size: 24px; font-weight: 700; color: var(--ch-text-primary);">Community Forum</h2>
                    </div>
                    <p style="color: var(--ch-text-secondary); margin-bottom: 16px;">
                        <i class="fas fa-info-circle"></i>
                        Welcome to our community! Share ideas, discuss topics, and connect with others.
                    </p>
                    <div style="display: flex; gap: 24px; font-size: 14px; color: var(--ch-text-muted);">
                        <span><i class="fas fa-file-alt"></i> <?php echo number_format($total_posts); ?> posts</span>
                        <span><i class="fas fa-users"></i> <?php echo number_format($total_users); ?> members</span>
                        <span><i class="fas fa-circle" style="color: var(--ch-success);"></i> <?php echo rand(10, 50); ?> online</span>
                    </div>
                </div>

                <!-- Sort Tabs -->
                <div class="ch-sort-tabs">
                    <button class="ch-tab <?php echo $sort === 'hot' ? 'ch-tab-active' : ''; ?>" data-sort="hot">
                        <i class="fas fa-fire"></i>
                        Hot
                    </button>
                    <button class="ch-tab <?php echo $sort === 'new' ? 'ch-tab-active' : ''; ?>" data-sort="new">
                        <i class="fas fa-clock"></i>
                        New
                    </button>
                    <button class="ch-tab <?php echo $sort === 'top' ? 'ch-tab-active' : ''; ?>" data-sort="top">
                        <i class="fas fa-star"></i>
                        Top
                    </button>
                    <button class="ch-tab <?php echo $sort === 'rising' ? 'ch-tab-active' : ''; ?>" data-sort="rising">
                        <i class="fas fa-chart-line"></i>
                        Rising
                    </button>
                </div>

                <!-- Posts -->
                <div class="ch-posts-container">
                    <?php if (empty($posts)): ?>
                        <div class="ch-empty-state">
                            <i class="fas fa-comments"></i>
                            <h3>No posts yet</h3>
                            <p>Be the first to start a discussion!</p>
                            <?php if (is_user_logged_in()): ?>
                                <a href="<?php echo get_permalink(get_page_by_path('create-post')); ?>" class="ch-btn ch-btn-primary" style="margin-top: 16px;">
                                    <i class="fas fa-plus"></i>
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
                                        <i class="fas fa-chevron-up"></i>
                                    </button>
                                    <span class="ch-vote-count"><?php echo $votes; ?></span>
                                    <button class="ch-vote-btn <?php echo $user_vote === 'down' ? 'ch-voted-down' : ''; ?>" 
                                            data-vote="down" <?php echo !is_user_logged_in() ? 'disabled title="Login to vote"' : ''; ?>>
                                        <i class="fas fa-chevron-down"></i>
                                    </button>
                                </div>

                                <!-- Post Details -->
                                <div class="ch-post-details">
                                    <div class="ch-post-meta">
                                        <span class="ch-community">
                                            <i class="fas fa-tag"></i>
                                            r/<?php echo esc_html($community); ?>
                                        </span>
                                        <span>•</span>
                                        <span>
                                            <i class="fas fa-user"></i>
                                            by u/<?php echo get_the_author_meta('display_name', $post->post_author); ?>
                                        </span>
                                        <span>•</span>
                                        <span>
                                            <i class="fas fa-clock"></i>
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
                                            <i class="fas fa-comment"></i>
                                            <?php echo $comment_count; ?> comments
                                        </a>
                                        <button class="ch-action-btn" onclick="navigator.share ? navigator.share({url: '<?php echo get_permalink($post->ID); ?>'}) : copyToClipboard('<?php echo get_permalink($post->ID); ?>')">
                                            <i class="fas fa-share"></i>
                                            Share
                                        </button>
                                        <button class="ch-action-btn">
                                            <i class="fas fa-bookmark"></i>
                                            Save
                                        </button>
                                        <button class="ch-action-btn">
                                            <i class="fas fa-flag"></i>
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
                        <i class="fas fa-plus"></i>
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
                        <i class="fas fa-info-circle"></i>
                        About Community
                    </h3>
                    <p class="ch-widget-text">
                        Welcome to CommunityHub! A place for meaningful discussions, sharing ideas, and connecting with like-minded individuals.
                    </p>
                    <div class="ch-community-stats">
                        <span>
                            <i class="fas fa-file-alt"></i>
                            <?php echo number_format($total_posts); ?> posts
                        </span>
                        <span>
                            <i class="fas fa-users"></i>
                            <?php echo number_format($total_users); ?> members
                        </span>
                    </div>
                </div>

                <!-- Popular Communities -->
                <div class="ch-widget">
                    <h3 class="ch-widget-title">
                        <i class="fas fa-fire"></i>
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
                        <i class="fas fa-bolt"></i>
                        Quick Actions
                    </h3>
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <?php if (is_user_logged_in()): ?>
                            <a href="<?php echo get_permalink(get_page_by_path('create-post')); ?>" class="ch-btn ch-btn-primary">
                                <i class="fas fa-plus"></i>
                                Create Post
                            </a>
                            <button class="ch-btn ch-btn-outline">
                                <i class="fas fa-plus-circle"></i>
                                Create Community
                            </button>
                            <a href="<?php echo admin_url('profile.php'); ?>" class="ch-btn ch-btn-outline">
                                <i class="fas fa-cog"></i>
                                Settings
                            </a>
                        <?php else: ?>
                            <a href="<?php echo wp_login_url(get_permalink()); ?>" class="ch-btn ch-btn-primary">
                                <i class="fas fa-sign-in-alt"></i>
                                Login to Participate
                            </a>
                        <?php endif; ?>
                        <button class="ch-btn ch-btn-outline">
                            <i class="fas fa-question-circle"></i>
                            Help & FAQ
                        </button>
                    </div>
                </div>

                <!-- Community Rules -->
                <div class="ch-widget">
                    <h3 class="ch-widget-title">
                        <i class="fas fa-gavel"></i>
                        Community Rules
                    </h3>
                    <ul class="ch-rules-list">
                        <li><i class="fas fa-heart"></i> Be respectful and civil</li>
                        <li><i class="fas fa-ban"></i> No spam or self-promotion</li>
                        <li><i class="fas fa-bullseye"></i> Stay on topic</li>
                        <li><i class="fas fa-shield-alt"></i> No personal attacks</li>
                        <li><i class="fas fa-book"></i> Follow community guidelines</li>
                    </ul>
                </div>
            </aside>
        </div>
    </div>
</div>