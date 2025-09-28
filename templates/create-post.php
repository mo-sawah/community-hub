<?php
if (!defined('ABSPATH')) exit;

if (!is_user_logged_in()) {
    echo '<div class="community-hub-container">
        <div class="ch-main-container">
            <div style="text-align: center; padding: 64px 24px;">
                <span style="font-size: 48px; color: var(--ch-text-muted); margin-bottom: 16px; display: block;">🔒</span>
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
                <a href="<?php echo home_url('/community-forum/'); ?>" class="ch-btn ch-btn-outline">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="m19 12-7-7-7 7M5 19h14"/>
                    </svg>
                    Back to Forum
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
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="ch-container">
        <div class="ch-layout">
            <main>
                <!-- Create Post Container -->
                <div class="ch-create-post-container">
                    <div class="ch-create-header">
                        <h2>
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M17 3a2.828 2.828 0 114 4L7.5 20.5 2 22l1.5-5.5L17 3z"/>
                            </svg>
                            Create a new post
                        </h2>
                        <p>Share your thoughts, ask questions, or start a discussion with the community</p>
                    </div>

                    <form id="ch-create-post-form" class="ch-create-form">
                        <?php wp_nonce_field('community_hub_nonce', 'nonce'); ?>
                        
                        <!-- Community Selection -->
                        <div class="ch-form-group">
                            <label for="community" class="ch-form-label">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82z"/>
                                    <circle cx="7" cy="7" r="3"/>
                                </svg>
                                Choose a community
                            </label>
                            <select name="community" id="community" class="ch-form-select" required>
                                <option value="">Select community...</option>
                                <?php foreach ($communities as $community): ?>
                                    <option value="<?php echo esc_attr($community->slug); ?>">
                                        r/<?php echo esc_html($community->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Title -->
                        <div class="ch-form-group">
                            <label for="title" class="ch-form-label">
                                <span>📝</span>
                                Title
                            </label>
                            <input type="text" name="title" id="title" class="ch-form-input" 
                                   placeholder="An interesting and descriptive title..." required maxlength="300">
                            <div class="ch-char-counter">
                                <span id="title-counter">0</span>/300
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="ch-form-group">
                            <label for="content" class="ch-form-label">
                                <span>📄</span>
                                Content
                            </label>
                            <div class="ch-editor-toolbar">
                                <button type="button" class="ch-editor-btn" data-format="bold">
                                    <span>🔤</span>
                                    Bold
                                </button>
                                <button type="button" class="ch-editor-btn" data-format="italic">
                                    <span>✨</span>
                                    Italic
                                </button>
                                <button type="button" class="ch-editor-btn" data-format="link">
                                    <span>🔗</span>
                                    Link
                                </button>
                                <button type="button" class="ch-editor-btn" data-format="code">
                                    <span>👨‍💻</span>
                                    Code
                                </button>
                                <button type="button" class="ch-editor-btn" data-format="list">
                                    <span>📋</span>
                                    List
                                </button>
                                <button type="button" class="ch-editor-btn" data-format="quote">
                                    <span>💬</span>
                                    Quote
                                </button>
                            </div>
                            <textarea name="content" id="content" class="ch-form-textarea" 
                                      placeholder="What are your thoughts? Share your ideas, ask questions, or start a discussion..." 
                                      rows="12" required></textarea>
                        </div>

                        <!-- Tags -->
                        <div class="ch-form-group">
                            <label for="tags" class="ch-form-label">
                                <span>#️⃣</span>
                                Tags (optional)
                            </label>
                            <input type="text" name="tags" id="tags" class="ch-form-input" 
                                   placeholder="discussion, help, question, tutorial (separated by commas)">
                            <small class="ch-form-help">
                                <span>ℹ️</span>
                                Add relevant tags to help others find your post
                            </small>
                        </div>

                        <!-- Post Type -->
                        <div class="ch-form-group">
                            <label class="ch-form-label">
                                <span>📂</span>
                                Post Type
                            </label>
                            <div style="display: flex; gap: 16px; flex-wrap: wrap;">
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                    <input type="radio" name="post_type" value="discussion" checked>
                                    <span>💬</span>
                                    Discussion
                                </label>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                    <input type="radio" name="post_type" value="question">
                                    <span>❓</span>
                                    Question
                                </label>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                    <input type="radio" name="post_type" value="tutorial">
                                    <span>🎓</span>
                                    Tutorial
                                </label>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                    <input type="radio" name="post_type" value="announcement">
                                    <span>📢</span>
                                    Announcement
                                </label>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="ch-form-actions">
                            <button type="button" class="ch-btn ch-btn-outline" id="preview-btn">
                                <span>👁️</span>
                                Preview
                            </button>
                            <button type="button" class="ch-btn ch-btn-secondary" id="save-draft-btn">
                                <span>💾</span>
                                Save Draft
                            </button>
                            <button type="submit" class="ch-btn ch-btn-primary" id="publish-btn">
                                <span>📤</span>
                                Publish Post
                            </button>
                        </div>
                    </form>

                    <!-- Preview Modal -->
                    <div id="preview-modal" class="ch-modal" style="display: none;">
                        <div class="ch-modal-content">
                            <div class="ch-modal-header">
                                <h3>
                                    <span>👁️</span>
                                    Post Preview
                                </h3>
                                <button type="button" class="ch-modal-close" id="close-preview">
                                    <span>❌</span>
                                </button>
                            </div>
                            <div class="ch-modal-body">
                                <div id="preview-content"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>

            <!-- Tips Sidebar -->
            <aside class="ch-sidebar">
                <div class="ch-widget">
                    <h3 class="ch-widget-title">
                        <span>💡</span>
                        Posting Tips
                    </h3>
                    <ul class="ch-tips-list">
                        <li><span>✏️</span> Write a clear, descriptive title</li>
                        <li><span>🎯</span> Choose the right community</li>
                        <li><span>📖</span> Add relevant context in your post</li>
                        <li><span>🔍</span> Search before posting duplicates</li>
                        <li><span>#️⃣</span> Use tags to improve discoverability</li>
                        <li><span>👥</span> Engage with replies and comments</li>
                    </ul>
                </div>

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

                <div class="ch-widget">
                    <h3 class="ch-widget-title">
                        <span>⌨️</span>
                        Formatting Guide
                    </h3>
                    <div style="font-size: 12px; color: var(--ch-text-secondary);">
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