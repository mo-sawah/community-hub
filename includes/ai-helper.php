<?php
if (!defined('ABSPATH')) exit;

class CommunityHubAI {
    
    private $api_key;
    
    public function __construct() {
        $settings = get_option('community_hub_settings');
        $this->api_key = $settings['openrouter_api_key'] ?? '';
        
        add_action('wp_ajax_generate_ai_content', array($this, 'generate_content'));
        add_action('wp_ajax_get_plugin_settings', array($this, 'get_settings'));
        add_action('wp_cron_generate_sample_posts', array($this, 'generate_sample_posts'));
    }
    
    public function generate_content() {
        check_ajax_referer('community_nonce', 'nonce');
        
        if (empty($this->api_key)) {
            wp_die(json_encode(array('error' => 'API key not configured')));
        }
        
        $prompt = sanitize_text_field($_POST['prompt']);
        $content = $this->call_openrouter_api($prompt);
        
        wp_die(json_encode(array('content' => $content)));
    }
    
    public function get_settings() {
        check_ajax_referer('community_nonce', 'nonce');
        
        $settings = get_option('community_hub_settings', array());
        // Don't expose the full API key
        $settings['openrouter_api_key'] = !empty($settings['openrouter_api_key']) ? 'configured' : '';
        
        wp_die(json_encode($settings));
    }
    
    private function call_openrouter_api($prompt, $options = array()) {
        if (empty($this->api_key)) {
            return false;
        }
        
        $defaults = array(
            'model' => 'anthropic/claude-3-sonnet',
            'max_tokens' => 1000,
            'temperature' => 0.7
        );
        
        $options = wp_parse_args($options, $defaults);
        
        $body = array(
            'model' => $options['model'],
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => $prompt
                )
            ),
            'max_tokens' => $options['max_tokens'],
            'temperature' => $options['temperature']
        );
        
        $response = wp_remote_post('https://openrouter.ai/api/v1/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json',
                'X-Title' => 'Community Hub Plugin'
            ),
            'body' => json_encode($body),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            error_log('OpenRouter API Error: ' . $response->get_error_message());
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['choices'][0]['message']['content'])) {
            return $data['choices'][0]['message']['content'];
        }
        
        return false;
    }
    
    // Generate sample posts for testing
    public function generate_sample_posts() {
        if (empty($this->api_key)) return;
        
        $communities = get_terms(array('taxonomy' => 'community_category', 'hide_empty' => false));
        
        foreach ($communities as $community) {
            $prompt = "Generate a forum post for the {$community->name} community. 
            Create an engaging title and content that would spark discussion. 
            The post should be relevant to {$community->name} and encourage community interaction.
            Format: Return just 'TITLE: [title here]' on first line, then 'CONTENT: [content here]'";
            
            $response = $this->call_openrouter_api($prompt);
            
            if ($response) {
                $lines = explode("\n", $response, 2);
                $title = str_replace('TITLE: ', '', $lines[0]);
                $content = isset($lines[1]) ? str_replace('CONTENT: ', '', $lines[1]) : '';
                
                if ($title && $content) {
                    $post_id = wp_insert_post(array(
                        'post_title' => $title,
                        'post_content' => $content,
                        'post_type' => 'community_post',
                        'post_status' => 'publish',
                        'post_author' => 1 // Admin user
                    ));
                    
                    if ($post_id) {
                        wp_set_object_terms($post_id, $community->term_id, 'community_category');
                        
                        // Add some random votes
                        global $wpdb;
                        $votes_table = $wpdb->prefix . 'community_votes';
                        $vote_count = rand(1, 50);
                        
                        for ($i = 0; $i < $vote_count; $i++) {
                            $wpdb->insert($votes_table, array(
                                'post_id' => $post_id,
                                'user_id' => rand(1, 10),
                                'vote_type' => rand(0, 1) ? 'up' : 'down'
                            ));
                        }
                    }
                }
            }
            
            // Don't overwhelm the API
            sleep(2);
        }
    }
}

new CommunityHubAI();
?>