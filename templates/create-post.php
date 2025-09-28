<?php
if (!defined('ABSPATH')) exit;

if (!is_user_logged_in()) {
    echo '<div id="community-hub-container">
        <div class="ch-main-container">
            <div style="text-align: center; padding: 64px 24px;">
                <span class="ch-icon" style="font-size: 48px; color: var(--ch-text-muted); margin-bottom: 16px; display: block;">🔒</span>
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
                    <a href="<?php echo get_permalink(get_page_by_path('forum')); ?>">
                        <span class="ch-icon ch-icon-users"></span>
                        CommunityHub
                    </a>
                </h1>
            </div>
            <div class="ch-header-right">
                <a href="<?php echo get_permalink(get_page_by_path('forum')); ?>" class="ch-btn ch-btn-outline">
                    <span class="ch-icon ch-icon-arrow-left"></span>
                    Back to Forum
                </a>
                <div class="ch-user-avatar">
                    <span class="ch-icon ch-icon-user"></span>
                </div>
            </div>
        </div>
    </header>

    <div class="ch-main-container">
        <div class="ch-create-post-wrapper">
            <div class="ch-create-post-container">
                <div class="ch-create-header">
                    <h2>
                        <span class="ch-icon ch-icon-edit"></span>
                        Create a new post
                    </h2>
                    <p>Share your thoughts, ask questions, or start a discussion with the community</p>
                </div>

                <form id="ch-create-post-form" class="ch-create-form">
                    <?php wp_nonce_field('community_nonce', 'nonce'); ?>
                    
                    <!-- Community Selection -->
                    <div class="ch-form-group">
                        <label for="community" class="ch-form-label">
                            <span class="ch-icon ch-icon-tag"></span>
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
                            <span class="ch-icon">📝</span>
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
                            <span class="ch-icon">📄</span>
                            Content
                        </label>
                        <div class="ch-editor-toolbar">
                            <button type="button" class="ch-editor-btn" data-format="bold">
                                <span class="ch-icon">🔤</span>
                                Bold
                            </button>
                            <button type="button" class="ch-editor-btn" data-format="italic">
                                <span class="ch-icon">✨</span>
                                Italic
                            </button>
                            <button type="button" class="ch-editor-btn" data-format="link">
                                <span class="ch-icon">🔗</span>
                                Link
                            </button>
                            <button type="button" class="ch-editor-btn" data-format="code">
                                <span class="ch-icon">👨‍💻</span>
                                Code
                            </button>
                            <button type="button" class="ch-editor-btn" data-format="list">
                                <span class="ch-icon">📋</span>
                                List
                            </button>
                            <button type="button" class="ch-editor-btn" data-format="quote">
                                <span class="ch-icon">💬</span>
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
                            <span class="ch-icon">#️⃣</span>
                            Tags (optional)
                        </label>
                        <input type="text" name="tags" id="tags" class="ch-form-input" 
                               placeholder="discussion, help, question, tutorial (separated by commas)">
                        <small class="ch-form-help">
                            <span class="ch-icon">ℹ️</span>
                            Add relevant tags to help others find your post
                        </small>
                    </div>

                    <!-- Post Type -->
                    <div class="ch-form-group">
                        <label class="ch-form-label">
                            <span class="ch-icon">📂</span>
                            Post Type
                        </label>
                        <div style="display: flex; gap: 16px; flex-wrap: wrap;">
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="radio" name="post_type" value="discussion" checked>
                                <span class="ch-icon">💬</span>
                                Discussion
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="radio" name="post_type" value="question">
                                <span class="ch-icon">❓</span>
                                Question
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="radio" name="post_type" value="tutorial">
                                <span class="ch-icon">🎓</span>
                                Tutorial
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="radio" name="post_type" value="announcement">
                                <span class="ch-icon">📢</span>
                                Announcement
                            </label>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="ch-form-actions">
                        <button type="button" class="ch-btn ch-btn-outline" id="preview-btn">
                            <span class="ch-icon">👁️</span>
                            Preview
                        </button>
                        <button type="button" class="ch-btn ch-btn-secondary" id="save-draft-btn">
                            <span class="ch-icon">💾</span>
                            Save Draft
                        </button>
                        <button type="submit" class="ch-btn ch-btn-primary" id="publish-btn">
                            <span class="ch-icon">📤</span>
                            Publish Post
                        </button>
                    </div>
                </form>

                <!-- Preview Modal -->
                <div id="preview-modal" class="ch-modal" style="display: none;">
                    <div class="ch-modal-content">
                        <div class="ch-modal-header">
                            <h3>
                                <span class="ch-icon">👁️</span>
                                Post Preview
                            </h3>
                            <button type="button" class="ch-modal-close" id="close-preview">
                                <span class="ch-icon">❌</span>
                            </button>
                        </div>
                        <div class="ch-modal-body">
                            <div id="preview-content"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tips Sidebar -->
            <aside class="ch-create-sidebar">
                <div class="ch-widget">
                    <h3 class="ch-widget-title">
                        <span class="ch-icon">💡</span>
                        Posting Tips
                    </h3>
                    <ul class="ch-tips-list">
                        <li><span class="ch-icon">✏️</span> Write a clear, descriptive title</li>
                        <li><span class="ch-icon">🎯</span> Choose the right community</li>
                        <li><span class="ch-icon">📖</span> Add relevant context in your post</li>
                        <li><span class="ch-icon">🔍</span> Search before posting duplicates</li>
                        <li><span class="ch-icon">#️⃣</span> Use tags to improve discoverability</li>
                        <li><span class="ch-icon">👥</span> Engage with replies and comments</li>
                    </ul>
                </div>

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

                <div class="ch-widget">
                    <h3 class="ch-widget-title">
                        <span class="ch-icon">⌨️</span>
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