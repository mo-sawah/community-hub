<?php
if (!defined('ABSPATH')) exit;

class CommunityHubAdmin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
    }
    
    public function add_admin_menu() {
        add_options_page(
            'Community Hub Settings',
            'Community Hub',
            'manage_options',
            'community-hub-settings',
            array($this, 'options_page')
        );
    }
    
    public function settings_init() {
        register_setting('community_hub', 'community_hub_settings');
        
        add_settings_section(
            'community_hub_ai_section',
            'AI Settings',
            null,
            'community_hub'
        );
        
        add_settings_field(
            'openrouter_api_key',
            'OpenRouter API Key',
            array($this, 'api_key_render'),
            'community_hub',
            'community_hub_ai_section'
        );
    }
    
    public function api_key_render() {
        $options = get_option('community_hub_settings');
        echo '<input type="text" name="community_hub_settings[openrouter_api_key]" value="' . 
             esc_attr($options['openrouter_api_key'] ?? '') . '" class="regular-text" />';
    }
    
    public function options_page() {
        ?>
        <form action="options.php" method="post">
            <h1>Community Hub Settings</h1>
            <?php
            settings_fields('community_hub');
            do_settings_sections('community_hub');
            submit_button();
            ?>
        </form>
        <?php
    }
}

new CommunityHubAdmin();
?>