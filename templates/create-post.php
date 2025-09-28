<?php
if (!defined('ABSPATH')) exit;

if (!is_user_logged_in()) {
    echo '<div class="community-hub-container">
        <div class="chp-container">
            <div style="text-align: center; padding: 64px 24px;">
                <span style="font-size: 48px; color: var(--chp-text-muted); margin-bottom: 16px; display: block;">🔒</span>
                <h2>Login Required</h2>
                <p>Please <a href="' . wp_login_url(get_permalink()) . '">login</a> to create a post.</p>
            </div>
        </div>
    </div>';
    return;
}

$communities = get_terms(array(
    'taxonomy' => 'community_category',
    'hide_empty' => false,
));
?>

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
                <div class="chp-user-avatar">
                    <?php 
                    $current_user = wp_get_current_user();
                    $avatar_url = get_avatar_url(get_current_user_id(), array('size' => 36));
                    ?>
                    <img src="<?php echo esc_url($avatar_url); ?>" 
                         alt="<?php echo esc_attr($current_user->display_name); ?>" 
                         title="<?php echo esc_attr($current_user->display_name); ?>">
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="chp-container">
        <div class="chp-layout">
            <main>
                <!-- Page Header -->
                <div class="chp-page-header">
                    <div class="chp-page-title">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 3a2.828 2.828 0 114 4L7.5 20.5 2 22l1.5-5.5L17 3z"/>
                        </svg>
                        <h1>Create a new post</h1>
                    </div>
                    <p class="chp-page-description">Share your thoughts, ask questions, or start a discussion with the community</p>
                </div>

                <!-- Create Post Form -->
                <div class="chp-form">
                    <form id="chp-create-post-form">
                        <?php wp_nonce_field('community_hub_nonce', 'nonce'); ?>
                        
                        <!-- Community Selection -->
                        <div class="chp-form-group">
                            <label for="community" class="chp-form-label">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82z"/>
                                    <circle cx="7" cy="7" r="3"/>
                                </svg>
                                Choose a community
                            </label>
                            <select name="community" id="community" class="chp-form-select" required>
                                <option value="">Select community...</option>
                                <?php foreach ($communities as $community): ?>
                                    <option value="<?php echo esc_attr($community->slug); ?>">
                                        r/<?php echo esc_html($community->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Title -->
                        <div class="chp-form-group">
                            <label for="title" class="chp-form-label">
                                <span>📝</span>
                                Title
                            </label>
                            <div style="position: relative;">
                                <input type="text" name="title" id="title" class="chp-form-input" 
                                       placeholder="An interesting and descriptive title..." required maxlength="300">
                                <div class="chp-char-counter">
                                    <span id="title-counter">0</span>/300
                                </div>
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="chp-form-group">
                            <label for="content" class="chp-form-label">
                                <span>📄</span>
                                Content
                            </label>
                            <div class="chp-editor-toolbar">
                                <button type="button" class="chp-editor-btn" data-format="bold">
                                    <span>🔤</span>
                                    Bold
                                </button>
                                <button type="button" class="chp-editor-btn" data-format="italic">
                                    <span>✨</span>
                                    Italic
                                </button>
                                <button type="button" class="chp-editor-btn" data-format="link">
                                    <span>🔗</span>
                                    Link
                                </button>
                                <button type="button" class="chp-editor-btn" data-format="code">
                                    <span>👨‍💻</span>
                                    Code
                                </button>
                                <button type="button" class="chp-editor-btn" data-format="list">
                                    <span>📋</span>
                                    List
                                </button>
                                <button type="button" class="chp-editor-btn" data-format="quote">
                                    <span>💬</span>
                                    Quote
                                </button>
                            </div>
                            <textarea name="content" id="content" class="chp-form-textarea" 
                                      placeholder="What are your thoughts? Share your ideas, ask questions, or start a discussion..." 
                                      rows="12" required></textarea>
                        </div>

                        <!-- Tags -->
                        <div class="chp-form-group">
                            <label for="tags" class="chp-form-label">
                                <span>#️⃣</span>
                                Tags (optional)
                            </label>
                            <input type="text" name="tags" id="tags" class="chp-form-input" 
                                   placeholder="discussion, help, question, tutorial (separated by commas)">
                            <small class="chp-form-help">
                                <span>ℹ️</span>
                                Add relevant tags to help others find your post
                            </small>
                        </div>

                        <!-- Post Type -->
                        <div class="chp-form-group">
                            <label class="chp-form-label">
                                <span>📂</span>
                                Post Type
                            </label>
                            <div class="chp-radio-group">
                                <label class="chp-radio-item">
                                    <input type="radio" name="post_type" value="discussion" checked>
                                    <span>💬</span>
                                    Discussion
                                </label>
                                <label class="chp-radio-item">
                                    <input type="radio" name="post_type" value="question">
                                    <span>❓</span>
                                    Question
                                </label>
                                <label class="chp-radio-item">
                                    <input type="radio" name="post_type" value="tutorial">
                                    <span>🎓</span>
                                    Tutorial
                                </label>
                                <label class="chp-radio-item">
                                    <input type="radio" name="post_type" value="announcement">
                                    <span>📢</span>
                                    Announcement
                                </label>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="chp-form-actions">
                            <button type="button" class="chp-btn chp-btn-outline" id="preview-btn">
                                <span>👁️</span>
                                Preview
                            </button>
                            <button type="button" class="chp-btn chp-btn-secondary" id="save-draft-btn">
                                <span>💾</span>
                                Save Draft
                            </button>
                            <button type="submit" class="chp-btn chp-btn-primary" id="publish-btn">
                                <span>🚀</span>
                                Publish Post
                            </button>
                        </div>
                    </form>

                    <!-- Preview Modal -->
                    <div id="preview-modal" class="chp-modal" style="display: none;">
                        <div class="chp-modal-content">
                            <div class="chp-modal-header">
                                <h3>
                                    <span>👁️</span>
                                    Post Preview
                                </h3>
                                <button type="button" class="chp-modal-close" id="close-preview">
                                    <span>✕</span>
                                </button>
                            </div>
                            <div class="chp-modal-body">
                                <div id="preview-content"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>

            <!-- Sidebar -->
            <aside class="chp-sidebar">
                <div class="chp-widget">
                    <h3 class="chp-widget-title">
                        <span>💡</span>
                        Posting Tips
                    </h3>
                    <ul class="chp-tips-list">
                        <li><span>✏️</span> Write a clear, descriptive title</li>
                        <li><span>🎯</span> Choose the right community</li>
                        <li><span>📖</span> Add relevant context in your post</li>
                        <li><span>🔍</span> Search before posting duplicates</li>
                        <li><span>#️⃣</span> Use tags to improve discoverability</li>
                        <li><span>👥</span> Engage with replies and comments</li>
                    </ul>
                </div>

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

                <div class="chp-widget">
                    <h3 class="chp-widget-title">
                        <span>⌨️</span>
                        Formatting Guide
                    </h3>
                    <div style="font-size: 12px; color: var(--chp-text-secondary);">
                        <p><strong>**bold text**</strong></p>
                        <p><em>*italic text*</em></p>
                        <p><code>`inline code`</code></p>
                        <p>> Quote text</p>
                        <p>- List item</p>
                        <p>[link](url)</p>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Character counter
    $('#title').on('input', function() {
        $('#title-counter').text(this.value.length);
    });

    // Form submission
    $('#chp-create-post-form').on('submit', function(e) {
        e.preventDefault();
        
        const $btn = $('#publish-btn');
        $btn.addClass('chp-loading').html('<div class="chp-spinner"></div> Publishing...');
        
        $.ajax({
            url: communityHub.ajaxurl,
            type: 'POST',
            data: $(this).serialize() + '&action=ch_create_post',
            success: function(response) {
                if (response.success) {
                    window.location.href = response.data.redirect;
                } else {
                    showMessage(response.data || 'Failed to create post', 'error');
                }
            },
            error: function() {
                showMessage('Failed to create post. Please try again.', 'error');
            },
            complete: function() {
                $btn.removeClass('chp-loading').html('<span>🚀</span> Publish Post');
            }
        });
    });

    // Preview functionality
    $('#preview-btn').on('click', function() {
        const title = $('#title').val();
        const content = $('#content').val();
        const community = $('#community option:selected').text();
        
        if (!title || !content) {
            showMessage('Please fill in title and content to preview', 'warning');
            return;
        }
        
        $('#preview-content').html(`
            <div style="border-bottom: 1px solid var(--chp-border); padding-bottom: 16px; margin-bottom: 16px;">
                <div style="font-size: 12px; color: var(--chp-text-muted); margin-bottom: 8px;">
                    ${community} • by u/<?php echo esc_js(wp_get_current_user()->display_name); ?> • just now
                </div>
                <h2 style="font-size: 20px; font-weight: 600; margin-bottom: 12px; color: var(--chp-text-primary);">${title}</h2>
            </div>
            <div style="line-height: 1.6; color: var(--chp-text-primary);">${content.replace(/\n/g, '<br>')}</div>
        `);
        $('#preview-modal').show();
    });

    $('#close-preview').on('click', function() {
        $('#preview-modal').hide();
    });

    // Close modal on outside click
    $('#preview-modal').on('click', function(e) {
        if (e.target === this) {
            $(this).hide();
        }
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
</script>