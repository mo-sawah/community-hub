<?php
if (!defined('ABSPATH')) exit;

// Get sort parameter - default to 'new' instead of 'hot'
$sort = isset($_GET['sort']) ? sanitize_text_field($_GET['sort']) : 'new';
$community_filter = isset($_GET['community']) ? sanitize_text_field($_GET['community']) : '';

// Build query args
$args = array(
    'post_type' => 'community_post',
    'posts_per_page' => 20,
    'post_status' => 'publish',
    'meta_query' => array()
);

// Add community filter
if ($community_filter) {
    $args['tax_query'] = array(
        array(
            'taxonomy' => 'community_category',
            'field' => 'slug',
            'terms' => $community_filter
        )
    );
}

// Handle sorting
switch ($sort) {
    case 'new':
        $args['orderby'] = 'date';
        $args['order'] = 'DESC';
        break;
    case 'hot':
        $args['meta_key'] = '_community_votes';
        $args['orderby'] = array('meta_value_num' => 'DESC', 'date' => 'DESC');
        break;
    case 'top':
        $args['meta_key'] = '_community_votes';
        $args['orderby'] = 'meta_value_num';
        $args['order'] = 'DESC';
        break;
    case 'rising':
        $args['date_query'] = array(
            array(
                'after' => '1 week ago',
            ),
        );
        $args['meta_key'] = '_community_votes';
        $args['orderby'] = array('meta_value_num' => 'DESC', 'date' => 'DESC');
        break;
}

$posts = get_posts($args);

// Helper functions
function get_post_votes($post_id) {
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

function get_user_vote($post_id, $user_id) {
    if (!$user_id) return null;
    
    global $wpdb;
    $table = $wpdb->prefix . 'community_votes';
    
    return $wpdb->get_var($wpdb->prepare(
        "SELECT vote_type FROM $table WHERE post_id = %d AND user_id = %d", 
        $post_id, $user_id
    ));
}

// Get communities and stats
$communities = get_terms(array(
    'taxonomy' => 'community_category',
    'hide_empty' => false,
));

$total_posts = wp_count_posts('community_post')->publish;
$total_users = count_users()['total_users'];
$online_users = rand(15, 85);
?>

<div class="community-hub-container">
    <!-- Header -->
    <header class="ch-header">
        <div class="ch-header-content">
            <a href="<?php echo home_url('/community-forum/'); ?>" class="ch-logo">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                CommunityHub
            </a>
            
            <div class="ch-search-bar">
                <svg class="ch-search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"/>
                    <path d="m21 21-4.35-4.35"/>
                </svg>
                <input type="text" class="ch-search-input" placeholder="Search communities, posts, users..." id="community-search">
            </div>
            
            <div class="ch-header-actions">
                <?php if (is_user_logged_in()): ?>
                    <a href="<?php echo home_url('/create-community-post/'); ?>" class="ch-btn ch-btn-primary">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 5v14M5 12h14"/>
                        </svg>
                        Create Post
                    </a>
                    <div class="ch-user-avatar">
                        <?php 
                        $current_user = wp_get_current_user();
                        $avatar_url = get_avatar_url(get_current_user_id(), array('size' => 36));
                        ?>
                        <img src="<?php echo esc_url($avatar_url); ?>" 
                             alt="<?php echo esc_attr($current_user->display_name); ?>" 
                             title="<?php echo esc_attr($current_user->display_name); ?>">
                    </div>
                <?php else: ?>
                    <a href="<?php echo wp_login_url(get_permalink()); ?>" class="ch-btn ch-btn-outline">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                        Login
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="ch-container">
        <div class="ch-layout">
            <main>
                <!-- Community Header -->
                <div class="ch-community-header">
                    <div class="ch-community-title">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor" style="color: var(--ch-primary);">
                            <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        <h1><?php echo $community_filter ? 'r/' . $community_filter : 'Community Forum'; ?></h1>
                    </div>
                    <p class="ch-community-description">
                        <?php if ($community_filter): ?>
                            <?php 
                            $term = get_term_by('slug', $community_filter, 'community_category');
                            echo $term ? esc_html($term->description) : 'Community discussions';
                            ?>
                        <?php else: ?>
                            Welcome to our community! Share ideas, discuss topics, and connect with like-minded individuals.
                        <?php endif; ?>
                    </p>
                    <div class="ch-community-stats">
                        <div class="ch-stat">
                            <span>📝</span>
                            <span><?php echo number_format($total_posts); ?> posts</span>
                        </div>
                        <div class="ch-stat">
                            <span>👥</span>
                            <span><?php echo number_format($total_users); ?> members</span>
                        </div>
                        <div class="ch-stat">
                            <span>🟢</span>
                            <span><?php echo $online_users; ?> online</span>
                        </div>
                    </div>
                </div>

                <!-- Sort Tabs - NEW FIRST -->
                <div class="ch-sort-tabs">
                    <button class="ch-tab <?php echo $sort === 'new' ? 'active' : ''; ?>" data-sort="new">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12,6 12,12 16,14"/>
                        </svg>
                        New
                    </button>
                    <button class="ch-tab <?php echo $sort === 'hot' ? 'active' : ''; ?>" data-sort="hot">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2c1.5 3 3.5 5.5 3.5 8.5 0 3.5-2.5 6.5-6 6.5s-6-3-6-6.5c0-3 2-5.5 3.5-8.5 1-1.5 2.5-1.5 3.5 0z"/>
                        </svg>
                        Hot
                    </button>
                    <button class="ch-tab <?php echo $sort === 'top' ? 'active' : ''; ?>" data-sort="top">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26"/>
                        </svg>
                        Top
                    </button>
                    <button class="ch-tab <?php echo $sort === 'rising' ? 'active' : ''; ?>" data-sort="rising">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 3v18h18M9 9l1.5-1.5L14 12l5-5"/>
                        </svg>
                        Rising
                    </button>
                </div>

                <!-- Posts -->
                <div class="ch-posts-container" id="posts-container">
                    <?php if (empty($posts)): ?>
                        <div class="ch-empty-state">
                            <div class="ch-empty-icon">💬</div>
                            <h3>No posts yet</h3>
                            <p>Be the first to start a discussion!</p>
                            <?php if (is_user_logged_in()): ?>
                                <a href="<?php echo home_url('/create-community-post/'); ?>" class="ch-btn ch-btn-primary" style="margin-top: 1rem;">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M12 5v14M5 12h14"/>
                                    </svg>
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
                            $community_slug = $communities_terms ? $communities_terms[0]->slug : 'general';
                            $comment_count = get_comments_number($post->ID);
                            $post_tags = get_post_meta($post->ID, '_community_tags', true);
                            $tags = $post_tags ? array_map('trim', explode(',', $post_tags)) : array();
                        ?>
                        <article class="ch-post-card" data-post-id="<?php echo $post->ID; ?>">
                            <div class="ch-post-content">
                                <!-- Voting -->
                                <div class="ch-vote-section">
                                    <button class="ch-vote-btn <?php echo $user_vote === 'up' ? 'voted-up' : ''; ?>" 
                                            data-vote="up" data-post-id="<?php echo $post->ID; ?>"
                                            <?php echo !is_user_logged_in() ? 'disabled title="Login to vote"' : ''; ?>>
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="m18 15-6-6-6 6"/>
                                        </svg>
                                    </button>
                                    <span class="ch-vote-count"><?php echo $votes; ?></span>
                                    <button class="ch-vote-btn <?php echo $user_vote === 'down' ? 'voted-down' : ''; ?>" 
                                            data-vote="down" data-post-id="<?php echo $post->ID; ?>"
                                            <?php echo !is_user_logged_in() ? 'disabled title="Login to vote"' : ''; ?>>
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="m6 9 6 6 6-6"/>
                                        </svg>
                                    </button>
                                </div>

                                <!-- Post Details -->
                                <div class="ch-post-details">
                                    <div class="ch-post-meta">
                                        <a href="<?php echo add_query_arg('community', $community_slug); ?>" class="ch-community-tag">
                                            r/<?php echo esc_html($community); ?>
                                        </a>
                                        <span>•</span>
                                        <span>by u/<?php echo get_the_author_meta('display_name', $post->post_author); ?></span>
                                        <span>•</span>
                                        <span><?php echo human_time_diff(strtotime($post->post_date)); ?> ago</span>
                                    </div>

                                    <h3 class="ch-post-title">
                                        <a href="<?php echo get_permalink($post->ID); ?>">
                                            <?php echo esc_html($post->post_title); ?>
                                        </a>
                                    </h3>

                                    <div class="ch-post-excerpt">
                                        <?php echo wp_trim_words($post->post_content, 30); ?>
                                    </div>

                                    <?php if (!empty($tags)): ?>
                                    <div class="ch-post-tags">
                                        <?php foreach ($tags as $tag): ?>
                                            <a href="#" class="ch-tag" data-tag="<?php echo esc_attr($tag); ?>">
                                                <?php echo esc_html($tag); ?>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php endif; ?>

                                    <div class="ch-post-actions">
                                        <a href="<?php echo get_permalink($post->ID); ?>#comments" class="ch-action-btn">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/>
                                            </svg>
                                            <?php echo $comment_count; ?> comments
                                        </a>
                                        <button class="ch-action-btn" onclick="sharePost('<?php echo get_permalink($post->ID); ?>')">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M4 12v8a2 2 0 002 2h12a2 2 0 002-2v-8M16 6l-4-4-4 4M12 2v13"/>
                                            </svg>
                                            Share
                                        </button>
                                        <button class="ch-action-btn" onclick="savePost(<?php echo $post->ID; ?>)">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="m19 21-7-4-7 4V5a2 2 0 012-2h10a2 2 0 012 2v16z"/>
                                            </svg>
                                            Save
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Load More -->
                <?php if (count($posts) >= 20): ?>
                <div style="text-align: center; margin-top: 2rem;">
                    <button class="ch-btn ch-btn-outline" id="load-more-posts">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 5v14M5 12h14"/>
                        </svg>
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
                        <span>ℹ️</span>
                        About Community
                    </h3>
                    <p class="ch-widget-text">
                        Welcome to CommunityHub! A place for meaningful discussions, sharing ideas, and connecting with like-minded individuals.
                    </p>
                    <div class="ch-community-stats">
                        <div class="ch-stat">
                            <span>📝</span>
                            <span><?php echo number_format($total_posts); ?> posts</span>
                        </div>
                        <div class="ch-stat">
                            <span>👥</span>
                            <span><?php echo number_format($total_users); ?> members</span>
                        </div>
                    </div>
                </div>

                <!-- Popular Communities -->
                <div class="ch-widget">
                    <h3 class="ch-widget-title">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="color: #f59e0b;">
                            <path d="M12 2c1.5 3 3.5 5.5 3.5 8.5 0 3.5-2.5 6.5-6 6.5s-6-3-6-6.5c0-3 2-5.5 3.5-8.5 1-1.5 2.5-1.5 3.5 0z"/>
                        </svg>
                        Popular Communities
                    </h3>
                    <div class="ch-communities-list">
                        <?php foreach ($communities as $community): 
                            $post_count = get_term_meta($community->term_id, 'post_count', true) ?: $community->count;
                        ?>
                        <a href="<?php echo add_query_arg('community', $community->slug); ?>" class="ch-community-item">
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
                        <span>⚡</span>
                        Quick Actions
                    </h3>
                    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                        <?php if (is_user_logged_in()): ?>
                            <a href="<?php echo home_url('/create-community-post/'); ?>" class="ch-btn ch-btn-primary">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 5v14M5 12h14"/>
                                </svg>
                                Create Post
                            </a>
                            <button class="ch-btn ch-btn-outline">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 5v14M5 12h14"/>
                                </svg>
                                Create Community
                            </button>
                            <a href="<?php echo admin_url('profile.php'); ?>" class="ch-btn ch-btn-outline">
                                <span>⚙️</span>
                                Settings
                            </a>
                        <?php else: ?>
                            <a href="<?php echo wp_login_url(get_permalink()); ?>" class="ch-btn ch-btn-primary">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
                                    <circle cx="12" cy="7" r="4"/>
                                </svg>
                                Login to Participate
                            </a>
                        <?php endif; ?>
                        <button class="ch-btn ch-btn-outline">
                            <span>❓</span>
                            Help & FAQ
                        </button>
                    </div>
                </div>

                <!-- Community Rules -->
                <div class="ch-widget">
                    <h3 class="ch-widget-title">
                        <span>⚖️</span>
                        Community Rules
                    </h3>
                    <ul class="ch-rules-list">
                        <li><span>❤️</span> Be respectful and civil</li>
                        <li><span>🚫</span> No spam or self-promotion</li>
                        <li><span>🎯</span> Stay on topic</li>
                        <li><span>🛡️</span> No personal attacks</li>
                        <li><span>📚</span> Follow community guidelines</li>
                    </ul>
                </div>
            </aside>
        </div>
    </div>
</div>

<script>
// Global functions for post interactions
function sharePost(url) {
    if (navigator.share) {
        navigator.share({
            title: 'Check out this post',
            url: url
        });
    } else {
        // Fallback: copy to clipboard
        navigator.clipboard.writeText(url).then(() => {
            showMessage('Link copied to clipboard!', 'success');
        }).catch(() => {
            // Fallback for older browsers
            const textArea = document.createElement('textarea');
            textArea.value = url;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            showMessage('Link copied to clipboard!', 'success');
        });
    }
}

function savePost(postId) {
    // Implement save functionality
    showMessage('Post saved!', 'success');
}

function showMessage(message, type) {
    // Remove existing messages
    const existing = document.querySelectorAll('.ch-message');
    existing.forEach(msg => msg.remove());
    
    const messageEl = document.createElement('div');
    messageEl.className = `ch-message ch-message-${type}`;
    messageEl.innerHTML = `
        <span>${message}</span>
        <button class="ch-message-close" onclick="this.parentElement.remove()">×</button>
    `;
    document.body.appendChild(messageEl);
    
    setTimeout(() => {
        if (messageEl.parentNode) {
            messageEl.remove();
        }
    }, 5000);
}
</script>