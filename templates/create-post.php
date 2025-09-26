<?php
if (!defined('ABSPATH')) exit;

if (!is_user_logged_in()) {
    echo '<p>Please <a href="' . wp_login_url() . '">login</a> to create a post.</p>';
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
            <span class="ch-sun-icon">☀️</span>
            <span class="ch-moon-icon">🌙</span>
        </button>
    </div>

    <!-- Header -->
    <header class="ch-header">
        <div class="ch-header-content">
            <div class="ch-header-left">
                <h1 class="ch-logo">
                    <a href="<?php echo get_permalink(get_page_by_path('forum')); ?>">CommunityHub</a>
                </h1>
            </div>
            <div class="ch-header-right">
                <a href="<?php echo get_permalink(get_page_by_path('forum')); ?>" class="ch-btn ch-btn-outline">
                    ← Back to Forum
                </a>
            </div>
        </div>
    </header>

    <div class="ch-main-container">
        <div class="ch-create-post-wrapper">
            <div class="ch-create-post-container">
                <div class="ch-create-header">
                    <h2>Create a new post</h2>
                    <p>Share your thoughts, ask questions, or start a discussion</p>
                </div>

                <form id="ch-create-post-form" class="ch-create-form">
                    <?php wp_nonce_field('community_nonce', 'nonce'); ?>
                    
                    <!-- Community Selection -->
                    <div class="ch-form-group">
                        <label for="community" class="ch-form-label">Choose a community</label>
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
                        <label for="title" class="ch-form-label">Title</label>
                        <input type="text" name="title" id="title" class="ch-form-input" 
                               placeholder="An interesting title..." required maxlength="300">
                        <div class="ch-char-counter">
                            <span id="title-counter">0</span>/300
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="ch-form-group">
                        <label for="content" class="ch-form-label">Content</label>
                        <div class="ch-editor-toolbar">
                            <button type="button" class="ch-editor-btn" data-format="bold">B</button>
                            <button type="button" class="ch-editor-btn" data-format="italic">I</button>
                            <button type="button" class="ch-editor-btn" data-format="link">🔗</button>
                            <button type="button" class="ch-editor-btn" data-format="code">{ }</button>
                            <button type="button" class="ch-ai-generate-btn" id="ai-generate-btn">✨ AI Generate</button>
                        </div>
                        <textarea name="content" id="content" class="ch-form-textarea" 
                                  placeholder="What are your thoughts?" rows="8" required></textarea>
                    </div>

                    <!-- Tags -->
                    <div class="ch-form-group">
                        <label for="tags" class="ch-form-label">Tags (optional)</label>
                        <input type="text" name="tags" id="tags" class="ch-form-input" 
                               placeholder="discussion, help, question (separated by commas)">
                        <small class="ch-form-help">Add relevant tags to help others find your post</small>
                    </div>

                    <!-- Actions -->
                    <div class="ch-form-actions">
                        <button type="button" class="ch-btn ch-btn-outline" id="preview-btn">👁 Preview</button>
                        <button type="button" class="ch-btn ch-btn-outline" id="save-draft-btn">💾 Save Draft</button>
                        <button type="submit" class="ch-btn ch-btn-primary" id="publish-btn">
                            🚀 Publish Post
                        </button>
                    </div>
                </form>

                <!-- Preview Modal -->
                <div id="preview-modal" class="ch-modal" style="display: none;">
                    <div class="ch-modal-content">
                        <div class="ch-modal-header">
                            <h3>Post Preview</h3>
                            <button type="button" class="ch-modal-close" id="close-preview">&times;</button>
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
                    <h3 class="ch-widget-title">Posting Tips</h3>
                    <ul class="ch-tips-list">
                        <li>📝 Write a clear, descriptive title</li>
                        <li>🏷 Choose the right community</li>
                        <li>📖 Add relevant context in your post</li>
                        <li>🔍 Search before posting duplicates</li>
                        <li>✨ Use AI generate for inspiration</li>
                    </ul>
                </div>

                <div class="ch-widget">
                    <h3 class="ch-widget-title">Community Rules</h3>
                    <ul class="ch-rules-list">
                        <li>Be respectful and civil</li>
                        <li>No spam or self-promotion</li>
                        <li>Stay on topic</li>
                        <li>No personal attacks</li>
                        <li>Follow community guidelines</li>
                    </ul>
                </div>
            </aside>
        </div>
    </div>
</div>