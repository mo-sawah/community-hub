<?php
if (!defined('ABSPATH')) exit;

if (!is_user_logged_in()) {
    echo '<div id="community-hub-container">
        <div class="ch-main-container">
            <div style="text-align: center; padding: 64px 24px;">
                <i class="fas fa-lock" style="font-size: 48px; color: var(--ch-text-muted); margin-bottom: 16px;"></i>
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
            <i class="fas fa-sun"></i>
            <i class="fas fa-moon" style="display: none;"></i>
        </button>
    </div>

    <!-- Header -->
    <header class="ch-header">
        <div class="ch-header-content">
            <div class="ch-header-left">
                <h1 class="ch-logo">
                    <a href="<?php echo get_permalink(get_page_by_path('forum')); ?>">
                        <i class="fas fa-users"></i>
                        CommunityHub
                    </a>
                </h1>
            </div>
            <div class="ch-header-right">
                <a href="<?php echo get_permalink(get_page_by_path('forum')); ?>" class="ch-btn ch-btn-outline">
                    <i class="fas fa-arrow-left"></i>
                    Back to Forum
                </a>
                <div class="ch-user-avatar">
                    <i class="fas fa-user"></i>
                </div>
            </div>
        </div>
    </header>

    <div class="ch-main-container">
        <div class="ch-create-post-wrapper">
            <div class="ch-create-post-container">
                <div class="ch-create-header">
                    <h2>
                        <i class="fas fa-edit"></i>
                        Create a new post
                    </h2>
                    <p>Share your thoughts, ask questions, or start a discussion with the community</p>
                </div>

                <form id="ch-create-post-form" class="ch-create-form">
                    <?php wp_nonce_field('community_nonce', 'nonce'); ?>
                    
                    <!-- Community Selection -->
                    <div class="ch-form-group">
                        <label for="community" class="ch-form-label">
                            <i class="fas fa-tags"></i>
                            Choose a community
                        </label>
                        <select name="community" id="community" class="ch-form-select" required>
                            <option value="">Select community...</option>
                            <?php foreach ($communities as $community): ?>
                                <option value="<?php echo esc_attr($community->slug); ?>">
                                    <i class="fas fa-tag"></i>
                                    r/<?php echo esc_html($community->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Title -->
                    <div class="ch-form-group">
                        <label for="title" class="ch-form-label">
                            <i class="fas fa-heading"></i>
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
                            <i class="fas fa-align-left"></i>
                            Content
                        </label>
                        <div class="ch-editor-toolbar">
                            <button type="button" class="ch-editor-btn" data-format="bold">
                                <i class="fas fa-bold"></i>
                                Bold
                            </button>
                            <button type="button" class="ch-editor-btn" data-format="italic">
                                <i class="fas fa-italic"></i>
                                Italic
                            </button>
                            <button type="button" class="ch-editor-btn" data-format="link">
                                <i class="fas fa-link"></i>
                                Link
                            </button>
                            <button type="button" class="ch-editor-btn" data-format="code">
                                <i class="fas fa-code"></i>
                                Code
                            </button>
                            <button type="button" class="ch-editor-btn" data-format="list">
                                <i class="fas fa-list"></i>
                                List
                            </button>
                            <button type="button" class="ch-editor-btn" data-format="quote">
                                <i class="fas fa-quote-left"></i>
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
                            <i class="fas fa-hashtag"></i>
                            Tags (optional)
                        </label>
                        <input type="text" name="tags" id="tags" class="ch-form-input" 
                               placeholder="discussion, help, question, tutorial (separated by commas)">
                        <small class="ch-form-help">
                            <i class="fas fa-info-circle"></i>
                            Add relevant tags to help others find your post
                        </small>
                    </div>

                    <!-- Post Type -->
                    <div class="ch-form-group">
                        <label class="ch-form-label">
                            <i class="fas fa-layer-group"></i>
                            Post Type
                        </label>
                        <div style="display: flex; gap: 16px; flex-wrap: wrap;">
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="radio" name="post_type" value="discussion" checked>
                                <i class="fas fa-comments"></i>
                                Discussion
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="radio" name="post_type" value="question">
                                <i class="fas fa-question-circle"></i>
                                Question
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="radio" name="post_type" value="tutorial">
                                <i class="fas fa-graduation-cap"></i>
                                Tutorial
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="radio" name="post_type" value="announcement">
                                <i class="fas fa-bullhorn"></i>
                                Announcement
                            </label>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="ch-form-actions">
                        <button type="button" class="ch-btn ch-btn-outline" id="preview-btn">
                            <i class="fas fa-eye"></i>
                            Preview
                        </button>
                        <button type="button" class="ch-btn ch-btn-secondary" id="save-draft-btn">
                            <i class="fas fa-save"></i>
                            Save Draft
                        </button>
                        <button type="submit" class="ch-btn ch-btn-primary" id="publish-btn">
                            <i class="fas fa-paper-plane"></i>
                            Publish Post
                        </button>
                    </div>
                </form>

                <!-- Preview Modal -->
                <div id="preview-modal" class="ch-modal" style="display: none;">
                    <div class="ch-modal-content">
                        <div class="ch-modal-header">
                            <h3>
                                <i class="fas fa-eye"></i>
                                Post Preview
                            </h3>
                            <button type="button" class="ch-modal-close" id="close-preview">
                                <i class="fas fa-times"></i>
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
                        <i class="fas fa-lightbulb"></i>
                        Posting Tips
                    </h3>
                    <ul class="ch-tips-list">
                        <li><i class="fas fa-pen"></i> Write a clear, descriptive title</li>
                        <li><i class="fas fa-bullseye"></i> Choose the right community</li>
                        <li><i class="fas fa-book"></i> Add relevant context in your post</li>
                        <li><i class="fas fa-search"></i> Search before posting duplicates</li>
                        <li><i class="fas fa-hashtag"></i> Use tags to improve discoverability</li>
                        <li><i class="fas fa-users"></i> Engage with replies and comments</li>
                    </ul>
                </div>

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

                <div class="ch-widget">
                    <h3 class="ch-widget-title">
                        <i class="fas fa-keyboard"></i>
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