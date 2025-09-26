<?php
if (!defined('ABSPATH')) exit;

class CommunityHubAIGenerator {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_generate_bulk_content', array($this, 'generate_bulk_content'));
        add_action('wp_ajax_get_generation_status', array($this, 'get_generation_status'));
    }
    
    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=community_post',
            'AI Content Generator',
            'AI Generator',
            'manage_options',
            'community-ai-generator',
            array($this, 'admin_page')
        );
    }
    
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><i class="fas fa-robot"></i> AI Content Generator</h1>
            <p>Generate realistic community content using AI to populate your forum with engaging discussions.</p>
            
            <div id="ai-generator-container">
                <form id="ai-generation-form" method="post">
                    <?php wp_nonce_field('ai_generate_nonce', 'nonce'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="post_count">Number of Posts</label>
                            </th>
                            <td>
                                <input type="number" id="post_count" name="post_count" value="10" min="1" max="100" class="regular-text">
                                <p class="description">How many posts to generate (1-100)</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="communities">Target Communities</label>
                            </th>
                            <td>
                                <?php
                                $communities = get_terms(array('taxonomy' => 'community_category', 'hide_empty' => false));
                                foreach ($communities as $community): ?>
                                    <label>
                                        <input type="checkbox" name="communities[]" value="<?php echo $community->term_id; ?>" checked>
                                        r/<?php echo $community->name; ?>
                                    </label><br>
                                <?php endforeach; ?>
                                <p class="description">Select which communities to generate content for</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="replies_per_post">Replies per Post</label>
                            </th>
                            <td>
                                <input type="number" id="replies_per_post" name="replies_per_post" value="3" min="0" max="20" class="regular-text">
                                <p class="description">Average number of replies per post (0-20)</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="vote_range">Vote Range</label>
                            </th>
                            <td>
                                Min: <input type="number" name="min_votes" value="1" min="-50" max="500" style="width: 80px;">
                                Max: <input type="number" name="max_votes" value="50" min="-50" max="500" style="width: 80px;">
                                <p class="description">Random vote range for each post</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="view_range">View Range</label>
                            </th>
                            <td>
                                Min: <input type="number" name="min_views" value="10" min="1" max="10000" style="width: 80px;">
                                Max: <input type="number" name="max_views" value="500" min="1" max="10000" style="width: 80px;">
                                <p class="description">Random view count range for each post</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="content_topics">Content Topics</label>
                            </th>
                            <td>
                                <textarea id="content_topics" name="content_topics" rows="4" cols="50" class="large-text">
technology trends, programming tips, web development, mobile apps, artificial intelligence, cybersecurity, startup advice, career guidance, coding tutorials, software reviews, tech news discussion, open source projects, database optimization, cloud computing, DevOps practices</textarea>
                                <p class="description">Comma-separated list of topics for AI to create posts about</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="content_style">Content Style</label>
                            </th>
                            <td>
                                <select name="content_style" id="content_style">
                                    <option value="discussion">Discussion-based</option>
                                    <option value="educational">Educational/Tutorial</option>
                                    <option value="questions">Question-focused</option>
                                    <option value="news">News and updates</option>
                                    <option value="mixed">Mixed styles</option>
                                </select>
                                <p class="description">Type of content to generate</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="author_variation">Author Variation</label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" name="create_fake_users" value="1" checked>
                                    Create realistic usernames for authors
                                </label><br>
                                <label>
                                    <input type="checkbox" name="vary_posting_times" value="1" checked>
                                    Vary posting times (spread over last 30 days)
                                </label>
                                <p class="description">Make content appear more natural</p>
                            </td>
                        </tr>
                    </table>
                    
                    <div class="ai-generator-actions">
                        <button type="submit" class="button button-primary button-large" id="generate-btn">
                            <i class="fas fa-magic"></i> Generate Content
                        </button>
                        <button type="button" class="button" id="preview-btn">
                            <i class="fas fa-eye"></i> Preview Sample
                        </button>
                    </div>
                </form>
                
                <div id="generation-progress" style="display: none;">
                    <h3>Generation in Progress...</h3>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 0%;"></div>
                    </div>
                    <p id="progress-text">Initializing...</p>
                    <div id="generation-log"></div>
                </div>
                
                <div id="generation-results" style="display: none;">
                    <h3>Generation Complete!</h3>
                    <div id="results-summary"></div>
                    <div id="generated-content-preview"></div>
                </div>
            </div>
        </div>
        
        <style>
        .ai-generator-actions {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }
        .progress-bar {
            width: 100%;
            height: 20px;
            background: #f1f1f1;
            border-radius: 10px;
            overflow: hidden;
            margin: 10px 0;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(45deg, #4f46e5, #06b6d4);
            transition: width 0.3s ease;
        }
        #generation-log {
            max-height: 200px;
            overflow-y: auto;
            background: #f9f9f9;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
            font-family: monospace;
            font-size: 12px;
        }
        .log-entry {
            padding: 2px 0;
            border-bottom: 1px solid #eee;
        }
        .log-success { color: #059669; }
        .log-error { color: #dc2626; }
        .log-info { color: #0369a1; }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            $('#ai-generation-form').on('submit', function(e) {
                e.preventDefault();
                startGeneration();
            });
            
            $('#preview-btn').on('click', function() {
                previewSample();
            });
            
            function startGeneration() {
                $('#generate-btn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Generating...');
                $('#generation-progress').show();
                $('#generation-results').hide();
                
                const formData = $('#ai-generation-form').serialize();
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: formData + '&action=generate_bulk_content',
                    success: function(response) {
                        if (response.success) {
                            monitorProgress(response.data.batch_id);
                        } else {
                            showError('Failed to start generation: ' + response.data);
                        }
                    },
                    error: function() {
                        showError('Failed to start generation');
                    }
                });
            }
            
            function monitorProgress(batchId) {
                const progressInterval = setInterval(function() {
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'get_generation_status',
                            batch_id: batchId,
                            nonce: $('input[name="nonce"]').val()
                        },
                        success: function(response) {
                            if (response.success) {
                                updateProgress(response.data);
                                
                                if (response.data.status === 'completed') {
                                    clearInterval(progressInterval);
                                    showResults(response.data);
                                } else if (response.data.status === 'failed') {
                                    clearInterval(progressInterval);
                                    showError('Generation failed: ' + response.data.error);
                                }
                            }
                        }
                    });
                }, 2000);
            }
            
            function updateProgress(data) {
                const percent = (data.completed / data.total) * 100;
                $('.progress-fill').css('width', percent + '%');
                $('#progress-text').text(`Generated ${data.completed} of ${data.total} posts...`);
                
                if (data.logs && data.logs.length > 0) {
                    data.logs.forEach(function(log) {
                        $('#generation-log').append(`<div class="log-entry log-${log.type}">${log.message}</div>`);
                    });
                    $('#generation-log').scrollTop($('#generation-log')[0].scrollHeight);
                }
            }
            
            function showResults(data) {
                $('#generation-progress').hide();
                $('#generation-results').show();
                $('#generate-btn').prop('disabled', false).html('<i class="fas fa-magic"></i> Generate Content');
                
                $('#results-summary').html(`
                    <div class="notice notice-success">
                        <p><strong>Successfully generated ${data.completed} posts!</strong></p>
                        <ul>
                            <li>Posts created: ${data.stats.posts}</li>
                            <li>Comments created: ${data.stats.comments}</li>
                            <li>Total votes added: ${data.stats.votes}</li>
                            <li>Total views added: ${data.stats.views}</li>
                        </ul>
                    </div>
                `);
            }
            
            function showError(message) {
                $('#generation-progress').hide();
                $('#generate-btn').prop('disabled', false).html('<i class="fas fa-magic"></i> Generate Content');
                alert('Error: ' + message);
            }
            
            function previewSample() {
                // Show a sample of what would be generated
                const topics = $('#content_topics').val().split(',');
                const randomTopic = topics[Math.floor(Math.random() * topics.length)].trim();
                
                alert(`Sample post would be about: "${randomTopic}"\n\nThis would generate a realistic discussion post with engagement metrics as specified.`);
            }
        });
        </script>
        <?php
    }
    
    public function generate_bulk_content() {
        check_ajax_referer('ai_generate_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(json_encode(array('success' => false, 'data' => 'Insufficient permissions')));
        }
        
        $settings = get_option('community_hub_settings');
        if (empty($settings['openrouter_api_key'])) {
            wp_die(json_encode(array('success' => false, 'data' => 'OpenRouter API key not configured. Please go to Settings > Community Hub and add your API key.')));
        }
        
        $batch_id = uniqid('batch_');
        $params = array(
            'post_count' => intval($_POST['post_count']),
            'communities' => isset($_POST['communities']) ? $_POST['communities'] : array(),
            'replies_per_post' => intval($_POST['replies_per_post']),
            'min_votes' => intval($_POST['min_votes']),
            'max_votes' => intval($_POST['max_votes']),
            'min_views' => intval($_POST['min_views']),
            'max_views' => intval($_POST['max_views']),
            'content_topics' => sanitize_textarea_field($_POST['content_topics']),
            'content_style' => sanitize_text_field($_POST['content_style']),
            'create_fake_users' => isset($_POST['create_fake_users']),
            'vary_posting_times' => isset($_POST['vary_posting_times'])
        );
        
        // Validate communities
        if (empty($params['communities'])) {
            wp_die(json_encode(array('success' => false, 'data' => 'Please select at least one community')));
        }
        
        // Store batch info
        set_transient("ai_batch_$batch_id", array(
            'status' => 'processing',
            'params' => $params,
            'total' => $params['post_count'],
            'completed' => 0,
            'stats' => array('posts' => 0, 'comments' => 0, 'votes' => 0, 'views' => 0),
            'logs' => array()
        ), HOUR_IN_SECONDS);
        
        // Process immediately instead of scheduling
        $this->process_ai_generation_immediate($batch_id);
        
        wp_die(json_encode(array('success' => true, 'data' => array('batch_id' => $batch_id))));
    }

    // Add this new method for immediate processing
    public function process_ai_generation_immediate($batch_id) {
        $batch_data = get_transient("ai_batch_$batch_id");
        if (!$batch_data) return;
        
        $params = $batch_data['params'];
        $settings = get_option('community_hub_settings');
        
        // Update initial log
        $batch_data['logs'][] = array(
            'type' => 'info',
            'message' => 'Starting AI content generation...'
        );
        set_transient("ai_batch_$batch_id", $batch_data, HOUR_IN_SECONDS);
        
        // Validate topics
        $topics = array_filter(array_map('trim', explode(',', $params['content_topics'])));
        if (empty($topics)) {
            $batch_data['status'] = 'failed';
            $batch_data['error'] = 'No topics provided';
            $batch_data['logs'][] = array(
                'type' => 'error',
                'message' => 'No valid topics found'
            );
            set_transient("ai_batch_$batch_id", $batch_data, HOUR_IN_SECONDS);
            return;
        }
        
        // Validate communities
        $communities = get_terms(array(
            'taxonomy' => 'community_category',
            'include' => $params['communities'],
            'hide_empty' => false
        ));
        
        if (empty($communities) || is_wp_error($communities)) {
            $batch_data['status'] = 'failed';
            $batch_data['error'] = 'No valid communities found';
            $batch_data['logs'][] = array(
                'type' => 'error',
                'message' => 'No valid communities found. Selected IDs: ' . implode(', ', $params['communities'])
            );
            set_transient("ai_batch_$batch_id", $batch_data, HOUR_IN_SECONDS);
            return;
        }
        
        $batch_data['logs'][] = array(
            'type' => 'info',
            'message' => 'Found ' . count($topics) . ' topics and ' . count($communities) . ' communities'
        );
        set_transient("ai_batch_$batch_id", $batch_data, HOUR_IN_SECONDS);
        
        // Generate fake usernames if needed
        $fake_users = array();
        if (isset($params['create_fake_users']) && $params['create_fake_users']) {
            $fake_users = array(
                'tech_guru_99', 'code_ninja', 'dev_master', 'pixel_pusher', 'data_wizard',
                'script_kiddie', 'byte_bender', 'logic_lord', 'syntax_sage', 'bug_hunter'
            );
        }
        
        for ($i = 0; $i < $params['post_count']; $i++) {
            // Update progress
            $batch_data['completed'] = $i;
            $batch_data['logs'][] = array(
                'type' => 'info',
                'message' => "Generating post " . ($i + 1) . " of " . $params['post_count'] . "..."
            );
            set_transient("ai_batch_$batch_id", $batch_data, HOUR_IN_SECONDS);
            
            // Safely select random topic and community
            $topic = $topics[array_rand($topics)];
            $community = $communities[array_rand($communities)];
            
            $prompt = "Create a realistic forum post about '{$topic}' for a {$community->name} community. 
            
            Write engaging content that would encourage discussion. Format the response exactly as:
            TITLE: [write an engaging title here]
            CONTENT: [write 2-3 paragraphs of post content that encourages discussion]
            TAGS: [write 3-5 relevant tags separated by commas]";
            
            $ai_response = $this->call_openrouter_api_simple($prompt, $settings['openrouter_api_key']);
            
            if ($ai_response) {
                // Parse AI response
                $title = '';
                $content = '';
                $tags = '';
                
                // Split by lines and parse
                $lines = explode("\n", $ai_response);
                $current_section = '';
                
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (empty($line)) continue;
                    
                    if (strpos($line, 'TITLE:') === 0) {
                        $title = trim(substr($line, 6));
                        $current_section = 'title';
                    } elseif (strpos($line, 'CONTENT:') === 0) {
                        $content = trim(substr($line, 8));
                        $current_section = 'content';
                    } elseif (strpos($line, 'TAGS:') === 0) {
                        $tags = trim(substr($line, 5));
                        $current_section = 'tags';
                    } else {
                        // Continue current section
                        if ($current_section === 'content' && !empty($line)) {
                            $content .= "\n\n" . $line;
                        } elseif ($current_section === 'title' && empty($title)) {
                            $title = $line;
                        } elseif ($current_section === 'tags' && empty($tags)) {
                            $tags = $line;
                        }
                    }
                }
                
                // Fallback if parsing failed
                if (empty($title)) {
                    $title = "Discussion about " . $topic;
                }
                if (empty($content)) {
                    $content = "Let's discuss " . $topic . ". What are your thoughts on this topic? I'd love to hear different perspectives from the community.";
                }
                if (empty($tags)) {
                    $tags = "discussion, " . strtolower(str_replace(' ', '-', $topic));
                }
                
                // Create post with error handling
                $post_date = (isset($params['vary_posting_times']) && $params['vary_posting_times']) ? 
                    date('Y-m-d H:i:s', strtotime('-' . rand(0, 30) . ' days -' . rand(0, 23) . ' hours')) :
                    current_time('mysql');
                
                $author_id = 1; // Default to admin
                if (!empty($fake_users)) {
                    $username = $fake_users[array_rand($fake_users)] . rand(100, 999);
                    $existing_user = get_user_by('login', $username);
                    if (!$existing_user) {
                        $new_user_id = wp_create_user($username, wp_generate_password(), $username . '@example.com');
                        if (!is_wp_error($new_user_id)) {
                            $author_id = $new_user_id;
                        }
                    } else {
                        $author_id = $existing_user->ID;
                    }
                }
                
                $post_id = wp_insert_post(array(
                    'post_title' => sanitize_text_field($title),
                    'post_content' => wp_kses_post($content),
                    'post_type' => 'community_post',
                    'post_status' => 'publish',
                    'post_author' => $author_id,
                    'post_date' => $post_date
                ), true); // Enable error return
                
                if (!is_wp_error($post_id) && $post_id) {
                    // Set community
                    $term_result = wp_set_object_terms($post_id, $community->term_id, 'community_category');
                    
                    // Set tags
                    if ($tags) {
                        update_post_meta($post_id, '_community_tags', sanitize_text_field($tags));
                    }
                    
                    // Add random votes with validation
                    if (isset($params['min_votes']) && isset($params['max_votes'])) {
                        global $wpdb;
                        $votes_table = $wpdb->prefix . 'community_votes';
                        $vote_count = rand(max(0, $params['min_votes']), max(1, $params['max_votes']));
                        
                        for ($v = 0; $v < abs($vote_count); $v++) {
                            $vote_type = $vote_count > 0 ? 'up' : 'down';
                            $voter_id = rand(1, 10);
                            
                            $wpdb->replace($votes_table, array(
                                'post_id' => $post_id,
                                'user_id' => $voter_id,
                                'vote_type' => $vote_type
                            ));
                        }
                        
                        $batch_data['stats']['votes'] += abs($vote_count);
                    }
                    
                    // Add view count with validation
                    if (isset($params['min_views']) && isset($params['max_views'])) {
                        $view_count = rand(max(1, $params['min_views']), max(1, $params['max_views']));
                        update_post_meta($post_id, '_community_views', $view_count);
                        $batch_data['stats']['views'] += $view_count;
                    }
                    
                    $batch_data['stats']['posts']++;
                    
                    $batch_data['logs'][] = array(
                        'type' => 'success',
                        'message' => "Created post: " . $title
                    );
                } else {
                    $error_message = is_wp_error($post_id) ? $post_id->get_error_message() : 'Unknown error creating post';
                    $batch_data['logs'][] = array(
                        'type' => 'error',
                        'message' => "Failed to create post: " . $error_message
                    );
                }
            } else {
                $batch_data['logs'][] = array(
                    'type' => 'error',
                    'message' => "AI API failed for topic: " . $topic
                );
            }
            
            // Update progress
            $batch_data['completed'] = $i + 1;
            set_transient("ai_batch_$batch_id", $batch_data, HOUR_IN_SECONDS);
            
            // Small delay to avoid overwhelming the server
            usleep(500000); // 0.5 seconds
        }
        
        // Mark as completed
        $batch_data['status'] = 'completed';
        $batch_data['logs'][] = array(
            'type' => 'success',
            'message' => 'Generation completed successfully!'
        );
        set_transient("ai_batch_$batch_id", $batch_data, HOUR_IN_SECONDS);
    }

    // Add this simplified API call method
    private function call_openrouter_api_simple($prompt, $api_key) {
        $response = wp_remote_post('https://openrouter.ai/api/v1/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
                'HTTP-Referer' => home_url(),
                'X-Title' => 'Community Hub Plugin'
            ),
            'body' => json_encode(array(
                'model' => 'anthropic/claude-3-haiku', // Use cheaper model for bulk generation
                'messages' => array(
                    array('role' => 'user', 'content' => $prompt)
                ),
                'max_tokens' => 800,
                'temperature' => 0.7
            )),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            error_log('OpenRouter API Error: ' . $response->get_error_message());
            return false;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            error_log('OpenRouter API HTTP Error: ' . $response_code);
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['error'])) {
            error_log('OpenRouter API Error: ' . $data['error']['message']);
            return false;
        }
        
        return $data['choices'][0]['message']['content'] ?? false;
    }
    
    public function get_generation_status() {
        check_ajax_referer('ai_generate_nonce', 'nonce');
        
        $batch_id = sanitize_text_field($_POST['batch_id']);
        $batch_data = get_transient("ai_batch_$batch_id");
        
        if (!$batch_data) {
            wp_die(json_encode(array('success' => false, 'data' => 'Batch not found')));
        }
        
        wp_die(json_encode(array('success' => true, 'data' => $batch_data)));
    }
}

new CommunityHubAIGenerator();

// Background processing
add_action('process_ai_generation', function($batch_id) {
    $batch_data = get_transient("ai_batch_$batch_id");
    if (!$batch_data) return;
    
    $params = $batch_data['params'];
    $settings = get_option('community_hub_settings');
    
    // Generate fake usernames if needed
    $fake_users = array();
    if ($params['create_fake_users']) {
        $fake_users = array(
            'tech_guru_99', 'code_ninja', 'dev_master', 'pixel_pusher', 'data_wizard',
            'script_kiddie', 'byte_bender', 'logic_lord', 'syntax_sage', 'bug_hunter',
            'stack_overflow', 'commit_crusher', 'merge_master', 'refactor_rebel', 'deploy_demon'
        );
    }
    
    $topics = array_map('trim', explode(',', $params['content_topics']));
    $communities = get_terms(array(
        'taxonomy' => 'community_category',
        'include' => $params['communities'],
        'hide_empty' => false
    ));
    
    for ($i = 0; $i < $params['post_count']; $i++) {
        // Generate post content using AI
        $topic = $topics[array_rand($topics)];
        $community = $communities[array_rand($communities)];
        
        $prompt = "Create a realistic forum post about '{$topic}' for a {$community->name} community. 
        Style: {$params['content_style']}
        
        Format the response as:
        TITLE: [engaging title here]
        CONTENT: [2-3 paragraph post content that encourages discussion]
        TAGS: [3-5 relevant tags separated by commas]";
        
        $ai_response = call_openrouter_api($prompt, $settings['openrouter_api_key']);
        
        if ($ai_response) {
            $lines = explode("\n", $ai_response);
            $title = '';
            $content = '';
            $tags = '';
            
            foreach ($lines as $line) {
                if (strpos($line, 'TITLE:') === 0) {
                    $title = trim(substr($line, 6));
                } elseif (strpos($line, 'CONTENT:') === 0) {
                    $content = trim(substr($line, 8));
                } elseif (strpos($line, 'TAGS:') === 0) {
                    $tags = trim(substr($line, 5));
                }
            }
            
            if ($title && $content) {
                // Create post
                $post_date = $params['vary_posting_times'] ? 
                    date('Y-m-d H:i:s', strtotime('-' . rand(0, 30) . ' days -' . rand(0, 23) . ' hours')) :
                    current_time('mysql');
                
                $author_id = 1; // Default to admin
                if (!empty($fake_users)) {
                    // Create or get fake user
                    $username = $fake_users[array_rand($fake_users)] . rand(100, 999);
                    $user = get_user_by('login', $username);
                    if (!$user) {
                        $author_id = wp_create_user($username, wp_generate_password(), $username . '@example.com');
                    } else {
                        $author_id = $user->ID;
                    }
                }
                
                $post_id = wp_insert_post(array(
                    'post_title' => $title,
                    'post_content' => $content,
                    'post_type' => 'community_post',
                    'post_status' => 'publish',
                    'post_author' => $author_id,
                    'post_date' => $post_date
                ));
                
                if ($post_id) {
                    // Set community
                    wp_set_object_terms($post_id, $community->term_id, 'community_category');
                    
                    // Set tags
                    if ($tags) {
                        update_post_meta($post_id, '_community_tags', $tags);
                    }
                    
                    // Add random votes
                    global $wpdb;
                    $votes_table = $wpdb->prefix . 'community_votes';
                    $vote_count = rand($params['min_votes'], $params['max_votes']);
                    
                    for ($v = 0; $v < abs($vote_count); $v++) {
                        $vote_type = $vote_count > 0 ? 'up' : 'down';
                        $voter_id = rand(1, 10); // Random user ID
                        
                        $wpdb->insert($votes_table, array(
                            'post_id' => $post_id,
                            'user_id' => $voter_id,
                            'vote_type' => $vote_type
                        ));
                    }
                    
                    // Add view count
                    $view_count = rand($params['min_views'], $params['max_views']);
                    update_post_meta($post_id, '_community_views', $view_count);
                    
                    // Generate replies if specified
                    $reply_count = rand(0, $params['replies_per_post']);
                    for ($r = 0; $r < $reply_count; $r++) {
                        $reply_prompt = "Write a realistic reply to this forum post: '$title'. Keep it conversational and relevant.";
                        $reply_content = call_openrouter_api($reply_prompt, $settings['openrouter_api_key']);
                        
                        if ($reply_content) {
                            $reply_author = !empty($fake_users) ? 
                                get_user_by('login', $fake_users[array_rand($fake_users)] . rand(100, 999))->ID ?? 1 : 1;
                            
                            wp_insert_comment(array(
                                'comment_post_ID' => $post_id,
                                'comment_author' => get_user_by('ID', $reply_author)->display_name ?? 'Anonymous',
                                'comment_author_email' => get_user_by('ID', $reply_author)->user_email ?? 'anon@example.com',
                                'comment_content' => trim($reply_content),
                                'comment_approved' => 1,
                                'user_id' => $reply_author
                            ));
                            
                            $batch_data['stats']['comments']++;
                        }
                    }
                    
                    $batch_data['stats']['posts']++;
                    $batch_data['stats']['votes'] += abs($vote_count);
                    $batch_data['stats']['views'] += $view_count;
                }
            }
        }
        
        // Update progress
        $batch_data['completed'] = $i + 1;
        $batch_data['logs'][] = array(
            'type' => 'success',
            'message' => "Generated post: " . ($title ?? 'Untitled')
        );
        
        set_transient("ai_batch_$batch_id", $batch_data, HOUR_IN_SECONDS);
        
        // Small delay to avoid rate limiting
        sleep(1);
    }
    
    // Mark as completed
    $batch_data['status'] = 'completed';
    set_transient("ai_batch_$batch_id", $batch_data, HOUR_IN_SECONDS);
});

function call_openrouter_api($prompt, $api_key) {
    $response = wp_remote_post('https://openrouter.ai/api/v1/chat/completions', array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json',
        ),
        'body' => json_encode(array(
            'model' => 'anthropic/claude-3-sonnet',
            'messages' => array(
                array('role' => 'user', 'content' => $prompt)
            ),
            'max_tokens' => 1000,
            'temperature' => 0.7
        )),
        'timeout' => 30
    ));
    
    if (is_wp_error($response)) {
        return false;
    }
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    return $data['choices'][0]['message']['content'] ?? false;
}
?>