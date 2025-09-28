<?php
/**
 * Template for single community posts
 * Save as: templates/single-community-post.php
 */

if (!defined('ABSPATH')) exit;

// Force load our styles and scripts
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

// Get the current post
global $post;
if (!$post || $post->post_type !== 'community_post') {
    wp_redirect(home_url('/community-forum/'));
    exit;
}

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

// Get post data
$votes = get_post_votes($post->ID);
$user_vote = get_user_vote($post->ID, get_current_user_id());
$communities_terms = get_the_terms($post->ID, 'community_category');
$community = $communities_terms ? $communities_terms[0]->name : 'general';
$community_slug = $communities_terms ? $communities_terms[0]->slug : 'general';
$post_tags = get_post_meta($post->ID, '_community_tags', true);
$tags = $post_tags ? array_map('trim', explode(',', $post_tags)) : array();

// Increment view count
$views = get_post_meta($post->ID, '_community_views', true) ?: 0;
update_post_meta($post->ID, '_community_views', $views + 1);

// Get comments
$comments = get_comments(array(
    'post_id' => $post->ID,
    'status' => 'approve',
    'order' => 'ASC'
));

// Get communities for sidebar
$communities = get_terms(array(
    'taxonomy' => 'community_category',
    'hide_empty' => false,
));

$total_posts = wp_count_posts('community_post')->publish;
$total_users = count_users()['total_users'];

// Start output buffering to capture the entire page
ob_start();
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo esc_html($post->post_title); ?> - Community Forum</title>
    <?php wp_head(); ?>
</head>
<body <?php body_class('community-single-post'); ?>>

<div class="community-hub-container">
    <!-- Header -->
    <header class="chp-header">
        <div class="chp-header-content">
            <a href="<?php echo home_url('/community-forum/'); ?>" class="chp-logo">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                CommunityHub
            </a>
            
            <div class="chp-search-bar">
                <svg class="chp-search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"/>
                    <path d="m21 21-4.35-4.35"/>
                </svg>
                <input type="text" class="chp-search-input" placeholder="Search communities, posts, users..." id="community-search">
            </div>
            
            <div class="chp-header-actions">
                <a href="<?php echo home_url('/community-forum/'); ?>" class="chp-btn chp-btn-outline">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="m19 12-7-7-7 7M5 19h14"/>
                    </svg>
                    Back to Forum
                </a>
                <?php if (is_user_logged_in()): ?>
                    <div class="chp-user-avatar">
                        <?php 
                        $current_user = wp_get_current_user();
                        $avatar_url = get_avatar_url(get_current_user_id(), array('size' => 36));
                        ?>
                        <img src="<?php echo esc_url($avatar_url); ?>" 
                             alt="<?php echo esc_attr($current_user->display_name); ?>" 
                             title="<?php echo esc_attr($current_user->display_name); ?>">
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="chp-container">
        <div class="chp-layout">
            <main>
                <!-- Breadcrumb -->
                <div class="chp-breadcrumb">
                    <a href="<?php echo home_url('/community-forum/'); ?>">Forum</a>
                    <span>></span>
                    <a href="<?php echo add_query_arg('community', $community_slug, home_url('/community-forum/')); ?>">
                        r/<?php echo esc_html($community); ?>
                    </a>
                    <span>></span>
                    <span>Post</span>
                </div>

                <!-- Post Content -->
                <article class="chp-single-post" data-post-id="<?php echo $post->ID; ?>">
                    <div class="chp-post-header">
                        <div class="chp-post-meta">
                            <a href="<?php echo add_query_arg('community', $community_slug, home_url('/community-forum/')); ?>" class="chp-community-tag">
                                r/<?php echo esc_html($community); ?>
                            </a>
                            <span>•</span>
                            <span>by u/<?php echo get_the_author_meta('display_name', $post->post_author); ?></span>
                            <span>•</span>
                            <span><?php echo human_time_diff(strtotime($post->post_date)); ?> ago</span>
                            <span>•</span>
                            <span><?php echo $views + 1; ?> views</span>
                        </div>

                        <h1 class="chp-post-title"><?php echo esc_html($post->post_title); ?></h1>

                        <?php if (!empty($tags)): ?>
                        <div class="chp-post-tags">
                            <?php foreach ($tags as $tag): ?>
                                <span class="chp-tag"><?php echo esc_html($tag); ?></span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="chp-post-content">
                        <!-- Voting -->
                        <div class="chp-vote-section">
                            <button class="chp-vote-btn <?php echo $user_vote === 'up' ? 'voted-up' : ''; ?>" 
                                    data-vote="up" data-post-id="<?php echo $post->ID; ?>"
                                    <?php echo !is_user_logged_in() ? 'disabled title="Login to vote"' : ''; ?>>
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="m18 15-6-6-6 6"/>
                                </svg>
                            </button>
                            <span class="chp-vote-count"><?php echo $votes; ?></span>
                            <button class="chp-vote-btn <?php echo $user_vote === 'down' ? 'voted-down' : ''; ?>" 
                                    data-vote="down" data-post-id="<?php echo $post->ID; ?>"
                                    <?php echo !is_user_logged_in() ? 'disabled title="Login to vote"' : ''; ?>>
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="m6 9 6 6 6-6"/>
                                </svg>
                            </button>
                        </div>

                        <!-- Post Body -->
                        <div class="chp-post-body">
                            <?php echo wpautop($post->post_content); ?>
                        </div>
                    </div>

                    <!-- Post Actions -->
                    <div class="chp-post-actions">
                        <button class="chp-action-btn" onclick="sharePost('<?php echo get_permalink($post->ID); ?>')">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 12v8a2 2 0 002 2h12a2 2 0 002-2v-8M16 6l-4-4-4 4M12 2v13"/>
                            </svg>
                            Share
                        </button>
                        <button class="chp-action-btn" onclick="savePost(<?php echo $post->ID; ?>)">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="m19 21-7-4-7 4V5a2 2 0 012-2h10a2 2 0 012 2v16z"/>
                            </svg>
                            Save
                        </button>
                        <button class="chp-action-btn">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 21l1.9-5.7a8.5 8.5 0 113.8 3.8z"/>
                            </svg>
                            Report
                        </button>
                    </div>
                </article>

                <!-- Comments Section -->
                <section class="chp-comments-section" id="comments">
                    <div class="chp-comments-header">
                        <h3>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/>
                            </svg>
                            <?php echo count($comments); ?> Comments
                        </h3>
                        <div class="chp-comments-sort">
                            <select id="comments-sort" class="chp-form-select">
                                <option value="oldest">Oldest First</option>
                                <option value="newest">Newest First</option>
                                <option value="top">Top Comments</option>
                            </select>
                        </div>
                    </div>

                    <!-- Add Comment Form -->
                    <?php if (is_user_logged_in()): ?>
                    <div class="chp-add-comment">
                        <div class="chp-comment-avatar">
                            <img src="<?php echo get_avatar_url(get_current_user_id(), array('size' => 32)); ?>" 
                                 alt="Your Avatar">
                        </div>
                        <form class="chp-comment-form" id="comment-form">
                            <textarea placeholder="What are your thoughts?" rows="3" id="comment-content" class="chp-form-textarea" required></textarea>
                            <div class="chp-form-actions">
                                <button type="button" class="chp-btn chp-btn-outline">Cancel</button>
                                <button type="submit" class="chp-btn chp-btn-primary">Comment</button>
                            </div>
                        </form>
                    </div>
                    <?php else: ?>
                    <div class="chp-login-prompt">
                        <p><a href="<?php echo wp_login_url(get_permalink($post->ID)); ?>">Login</a> to join the discussion</p>
                    </div>
                    <?php endif; ?>

                    <!-- Comments List -->
                    <div class="chp-comments-list">
                        <?php if (empty($comments)): ?>
                            <div class="chp-empty-comments">
                                <div class="chp-empty-icon">💬</div>
                                <h4>No comments yet</h4>
                                <p>Be the first to share your thoughts!</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($comments as $comment): ?>
                            <div class="chp-comment" data-comment-id="<?php echo $comment->comment_ID; ?>">
                                <div class="chp-comment-avatar">
                                    <img src="<?php echo get_avatar_url($comment->user_id ?: $comment->comment_author_email, array('size' => 32)); ?>" 
                                         alt="Avatar">
                                </div>
                                <div class="chp-comment-content">
                                    <div class="chp-comment-meta">
                                        <span class="chp-comment-author">u/<?php echo esc_html($comment->comment_author); ?></span>
                                        <span>•</span>
                                        <span class="chp-comment-time"><?php echo human_time_diff(strtotime($comment->comment_date)); ?> ago</span>
                                    </div>
                                    <div class="chp-comment-text">
                                        <?php echo wpautop($comment->comment_content); ?>
                                    </div>
                                    <div class="chp-comment-actions">
                                        <button class="chp-comment-vote-btn" data-vote="up">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="m18 15-6-6-6 6"/>
                                            </svg>
                                        </button>
                                        <span class="chp-comment-votes">0</span>
                                        <button class="chp-comment-vote-btn" data-vote="down">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="m6 9 6 6 6-6"/>
                                            </svg>
                                        </button>
                                        <button class="chp-comment-reply-btn">Reply</button>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>
            </main>

            <!-- Sidebar -->
            <aside class="chp-sidebar">
                <!-- About Community -->
                <div class="chp-widget">
                    <h3 class="chp-widget-title">
                        <span>ℹ️</span>
                        About r/<?php echo esc_html($community); ?>
                    </h3>
                    <p class="chp-widget-text">
                        <?php 
                        $term = get_term_by('slug', $community_slug, 'community_category');
                        echo $term ? esc_html($term->description) : 'Community discussions and shared interests.';
                        ?>
                    </p>
                    <div class="chp-community-stats">
                        <div class="chp-stat">
                            <span>📝</span>
                            <span><?php echo number_format($total_posts); ?> posts</span>
                        </div>
                        <div class="chp-stat">
                            <span>👥</span>
                            <span><?php echo number_format($total_users); ?> members</span>
                        </div>
                    </div>
                </div>

                <!-- Related Posts -->
                <div class="chp-widget">
                    <h3 class="chp-widget-title">
                        <span>🔗</span>
                        Related Posts
                    </h3>
                    <?php
                    $related_posts = get_posts(array(
                        'post_type' => 'community_post',
                        'posts_per_page' => 5,
                        'post__not_in' => array($post->ID),
                        'tax_query' => array(
                            array(
                                'taxonomy' => 'community_category',
                                'field' => 'slug',
                                'terms' => $community_slug
                            )
                        )
                    ));
                    ?>
                    <div class="chp-related-posts">
                        <?php foreach ($related_posts as $related_post): ?>
                        <a href="<?php echo get_permalink($related_post->ID); ?>" class="chp-related-post">
                            <h4><?php echo esc_html(wp_trim_words($related_post->post_title, 8)); ?></h4>
                            <div class="chp-related-meta">
                                <?php echo human_time_diff(strtotime($related_post->post_date)); ?> ago
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Community Rules -->
                <div class="chp-widget">
                    <h3 class="chp-widget-title">
                        <span>⚖️</span>
                        Community Rules
                    </h3>
                    <ul class="chp-rules-list">
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
// Comment form submission
jQuery(document).ready(function($) {
    $('#comment-form').on('submit', function(e) {
        e.preventDefault();
        
        const content = $('#comment-content').val().trim();
        if (!content) {
            showMessage('Please enter a comment', 'warning');
            return;
        }
        
        const $btn = $(this).find('button[type="submit"]');
        $btn.addClass('chp-loading').html('<div class="chp-spinner"></div> Posting...');
        
        $.ajax({
            url: communityHub.ajaxurl,
            type: 'POST',
            data: {
                action: 'ch_add_comment',
                post_id: <?php echo $post->ID; ?>,
                content: content,
                parent_id: 0,
                nonce: communityHub.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#comment-content').val('');
                    $('.chp-comments-list').prepend(response.data.html);
                    showMessage('Comment posted successfully!', 'success');
                } else {
                    showMessage(response.data || 'Failed to post comment', 'error');
                }
            },
            error: function() {
                showMessage('Failed to post comment. Please try again.', 'error');
            },
            complete: function() {
                $btn.removeClass('chp-loading').html('Comment');
            }
        });
    });
    
    function showMessage(message, type) {
        $('.chp-message').remove();
        
        let icon = '';
        switch(type) {
            case 'success': icon = '✅'; break;
            case 'error': icon = '❌'; break;
            case 'warning': icon = '⚠️'; break;
            default: icon = 'ℹ️';
        }
        
        const $message = $(`
            <div class="chp-message chp-message-${type}">
                <span>${icon}</span>
                <span>${message}</span>
                <button class="chp-message-close">×</button>
            </div>
        `);
        
        $('body').append($message);
        
        setTimeout(() => {
            $message.fadeOut(() => $message.remove());
        }, 5000);
        
        $message.find('.chp-message-close').on('click', () => {
            $message.fadeOut(() => $message.remove());
        });
    }
});

// Global functions for post interactions
function sharePost(url) {
    if (navigator.share) {
        navigator.share({
            title: 'Check out this post',
            url: url
        });
    } else {
        navigator.clipboard.writeText(url).then(() => {
            showMessage('Link copied to clipboard!', 'success');
        }).catch(() => {
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
    showMessage('Post saved!', 'success');
}
</script>

<?php wp_footer(); ?>
</body>
</html>