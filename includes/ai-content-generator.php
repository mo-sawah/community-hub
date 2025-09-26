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
    
    public function generate_bulk_content() {
        check_ajax_referer('ai_generate_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(json_encode(array('success' => false, 'data' => 'Insufficient permissions')));
        }
        
        $settings = get_option('community_hub_settings', array());
        if (empty($settings['openrouter_api_key'])) {
            wp_die(json_encode(array('success' => false, 'data' => 'OpenRouter API key not configured')));
        }
        
        // Validate inputs
        $post_count = intval($_POST['post_count']);
        $communities = isset($_POST['communities']) && is_array($_POST['communities']) ? $_POST['communities'] : array();
        $content_topics = sanitize_textarea_field($_POST['content_topics']);
        
        if ($post_count <= 0 || $post_count > 50) {
            wp_die(json_encode(array('success' => false, 'data' => 'Invalid post count')));
        }
        
        if (empty($communities)) {
            wp_die(json_encode(array('success' => false, 'data' => 'Please select at least one community')));
        }
        
        if (empty($content_topics)) {
            wp_die(json_encode(array('success' => false, 'data' => 'Please provide content topics')));
        }
        
        // Start immediate generation
        $result = $this->generate_posts_immediately($post_count, $communities, $content_topics, $settings['openrouter_api_key']);
        
        wp_die(json_encode($result));
    }
    
    private function generate_posts_immediately($post_count, $community_ids, $topics_string, $api_key) {
        $topics = array_filter(array_map('trim', explode(',', $topics_string)));
        
        if (empty($topics)) {
            return array('success' => false, 'data' => 'No valid topics found');
        }
        
        $communities = get_terms(array(
            'taxonomy' => 'community_category',
            'include' => $community_ids,
            'hide_empty' => false
        ));
        
        if (empty($communities) || is_wp_error($communities)) {
            return array('success' => false, 'data' => 'No valid communities found');
        }
        
        $created_posts = 0;
        $errors = array();
        
        for ($i = 0; $i < $post_count; $i++) {
            $topic = $topics[array_rand($topics)];
            $community = $communities[array_rand($communities)];
            
            $prompt = "Create a forum post about '{$topic}' for a {$community->name} community.

Format exactly as:
TITLE: [engaging title]
CONTENT: [2-3 paragraphs encouraging discussion]
TAGS: [3-5 tags separated by commas]";

            $ai_response = $this->call_openrouter_api($prompt, $api_key);
            
            if ($ai_response) {
                $parsed = $this->parse_ai_response($ai_response, $topic);
                
                $post_id = wp_insert_post(array(
                    'post_title' => $parsed['title'],
                    'post_content' => $parsed['content'],
                    'post_type' => 'community_post',
                    'post_status' => 'publish',
                    'post_author' => get_current_user_id()
                ));
                
                if ($post_id && !is_wp_error($post_id)) {
                    wp_set_object_terms($post_id, $community->term_id, 'community_category');
                    
                    if ($parsed['tags']) {
                        update_post_meta($post_id, '_community_tags', $parsed['tags']);
                    }
                    
                    // Add random votes
                    $this->add_random_votes($post_id);
                    
                    $created_posts++;
                } else {
                    $errors[] = "Failed to create post for topic: {$topic}";
                }
            } else {
                $errors[] = "AI generation failed for topic: {$topic}";
            }
            
            // Small delay
            usleep(500000);
        }
        
        return array(
            'success' => true,
            'data' => array(
                'created_posts' => $created_posts,
                'errors' => $errors,
                'message' => "Successfully created {$created_posts} posts"
            )
        );
    }
    
    private function parse_ai_response($response, $fallback_topic) {
        $title = '';
        $content = '';
        $tags = '';
        
        $lines = explode("\n", $response);
        foreach ($lines as $line) {
            $line = trim($line);
            if (strpos($line, 'TITLE:') === 0) {
                $title = trim(substr($line, 6));
            } elseif (strpos($line, 'CONTENT:') === 0) {
                $content = trim(substr($line, 8));
            } elseif (strpos($line, 'TAGS:') === 0) {
                $tags = trim(substr($line, 5));
            }
        }
        
        // Fallbacks
        if (empty($title)) {
            $title = "Discussion about " . ucfirst($fallback_topic);
        }
        if (empty($content)) {
            $content = "Let's discuss {$fallback_topic}. What are your thoughts on this topic? Share your experiences and insights with the community!";
        }
        if (empty($tags)) {
            $tags = "discussion, " . strtolower(str_replace(' ', '-', $fallback_topic));
        }
        
        return array(
            'title' => $title,
            'content' => $content,
            'tags' => $tags
        );
    }
    
    private function add_random_votes($post_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'community_votes';
        $vote_count = rand(1, 20);
        
        for ($i = 0; $i < $vote_count; $i++) {
            $vote_type = rand(0, 1) ? 'up' : 'down';
            $user_id = rand(1, 10);
            
            $wpdb->replace($table, array(
                'post_id' => $post_id,
                'user_id' => $user_id,
                'vote_type' => $vote_type
            ));
        }
    }
    
    private function call_openrouter_api($prompt, $api_key) {
        $response = wp_remote_post('https://openrouter.ai/api/v1/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
                'HTTP-Referer' => home_url(),
                'X-Title' => 'Community Hub Plugin'
            ),
            'body' => json_encode(array(
                'model' => 'anthropic/claude-3-haiku',
                'messages' => array(
                    array('role' => 'user', 'content' => $prompt)
                ),
                'max_tokens' => 500,
                'temperature' => 0.7
            )),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            error_log('OpenRouter API Error: ' . $response->get_error_message());
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['error'])) {
            error_log('OpenRouter API Error: ' . print_r($data['error'], true));
            return false;
        }
        
        return $data['choices'][0]['message']['content'] ?? false;
    }
    
    public function get_generation_status() {
        // Simple immediate response since we're doing immediate generation
        wp_die(json_encode(array('success' => true, 'data' => array('status' => 'completed'))));
    }
    
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>AI Content Generator</h1>
            <p>Generate realistic community content using AI.</p>
            
            <?php
            $settings = get_option('community_hub_settings', array());
            if (empty($settings['openrouter_api_key'])) {
                echo '<div class="notice notice-error"><p>OpenRouter API key not configured. <a href="' . admin_url('options-general.php?page=community-hub-settings') . '">Configure it here</a>.</p></div>';
            }
            ?>
            
            <form id="ai-generation-form">
                <?php wp_nonce_field('ai_generate_nonce', 'nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th>Number of Posts</th>
                        <td>
                            <input type="number" name="post_count" value="5" min="1" max="50" required>
                            <p class="description">How many posts to generate (1-50)</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th>Communities</th>
                        <td>
                            <?php
                            $communities = get_terms(array('taxonomy' => 'community_category', 'hide_empty' => false));
                            if ($communities):
                                foreach ($communities as $community): ?>
                                    <label style="display: block;">
                                        <input type="checkbox" name="communities[]" value="<?php echo $community->term_id; ?>" checked>
                                        <?php echo $community->name; ?>
                                    </label>
                                <?php endforeach;
                            else: ?>
                                <p style="color: red;">No communities found. Create some first.</p>
                            <?php endif; ?>
                        </td>
                    </tr>
                    
                    <tr>
                        <th>Topics</th>
                        <td>
                            <textarea name="content_topics" rows="4" cols="50" required>technology, programming, web development, mobile apps, artificial intelligence, cybersecurity</textarea>
                            <p class="description">Comma-separated topics</p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="submit" class="button button-primary">Generate Posts</button>
                </p>
            </form>
            
            <div id="generation-results" style="display: none;">
                <h3>Results</h3>
                <div id="results-content"></div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#ai-generation-form').on('submit', function(e) {
                e.preventDefault();
                
                var $btn = $('button[type="submit"]');
                $btn.prop('disabled', true).text('Generating...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: $(this).serialize() + '&action=generate_bulk_content',
                    success: function(response) {
                        var data = typeof response === 'string' ? JSON.parse(response) : response;
                        
                        if (data.success) {
                            $('#results-content').html('<div class="notice notice-success"><p>' + data.data.message + '</p></div>');
                            $('#generation-results').show();
                        } else {
                            alert('Error: ' + data.data);
                        }
                    },
                    error: function() {
                        alert('Request failed');
                    },
                    complete: function() {
                        $btn.prop('disabled', false).text('Generate Posts');
                    }
                });
            });
        });
        </script>
        <?php
    }
}

new CommunityHubAIGenerator();
?>