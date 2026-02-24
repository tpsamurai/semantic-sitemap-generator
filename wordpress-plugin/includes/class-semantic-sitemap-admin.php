<?php
class Semantic_Sitemap_Admin {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue']);
        add_action('wp_ajax_semantic_sitemap_generate', [$this, 'ajax_generate']);
    }
    public function add_menu() {
        add_options_page('Semantic Sitemap', 'Semantic Sitemap', 'manage_options', 'semantic-sitemap', [$this, 'render']);
    }
    public function register_settings() {
        register_setting('semantic_sitemap_options', 'semantic_sitemap_options', ['sanitize_callback' => [$this, 'sanitize']]);
    }
    public function sanitize($o) {
        return [
            'business_name'        => sanitize_text_field($o['business_name'] ?? ''),
            'business_description' => sanitize_textarea_field($o['business_description'] ?? ''),
            'business_mission'     => sanitize_textarea_field($o['business_mission'] ?? ''),
            'business_products'    => array_map('sanitize_text_field', $o['business_products'] ?? []),
            'business_services'    => array_map('sanitize_text_field', $o['business_services'] ?? []),
            'include_pages'        => isset($o['include_pages']),
            'include_posts'        => isset($o['include_posts']),
            'include_categories'   => isset($o['include_categories']),
            'include_tags'         => isset($o['include_tags']),
            'auto_generate'        => isset($o['auto_generate']),
            'generation_schedule'  => sanitize_text_field($o['generation_schedule'] ?? 'daily'),
        ];
    }
    public function enqueue($hook) {
        if ($hook !== 'settings_page_semantic-sitemap') return;
        wp_enqueue_style('semantic-sitemap-admin', SEMANTIC_SITEMAP_URL.'assets/css/admin.css', [], SEMANTIC_SITEMAP_VERSION);
        wp_enqueue_script('semantic-sitemap-admin', SEMANTIC_SITEMAP_URL.'assets/js/admin.js', ['jquery'], SEMANTIC_SITEMAP_VERSION, true);
        wp_localize_script('semantic-sitemap-admin', 'semanticSitemap', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('semantic_sitemap_generate'),
        ]);
    }
    public function ajax_generate() {
        check_ajax_referer('semantic_sitemap_generate', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Permission denied']);
        $gen     = new Semantic_Sitemap_Generator();
        $sitemap = $gen->generate();
        update_option('semantic_sitemap_data', $sitemap);
        update_option('semantic_sitemap_last_generated', current_time('mysql'));
        wp_send_json_success(['pages' => count($sitemap['siteStructure']['pages']), 'time' => current_time('mysql')]);
    }
    public function render() {
        if (!current_user_can('manage_options')) return;
        $opts = get_option('semantic_sitemap_options', []);
        $last = get_option('semantic_sitemap_last_generated');
        ?>
        <div class="wrap">
            <h1>Semantic Sitemap Generator</h1>
            <div class="notice notice-info"><p>
                Your semantic sitemap is available at:<br>
                JSON: <a href="<?=home_url('/semantic-sitemap.json')?>" target="_blank"><?=home_url('/semantic-sitemap.json')?></a><br>
                XML: <a href="<?=home_url('/semantic-sitemap.xml')?>" target="_blank"><?=home_url('/semantic-sitemap.xml')?></a><br>
                TXT: <a href="<?=home_url('/semantic-sitemap.txt')?>" target="_blank"><?=home_url('/semantic-sitemap.txt')?></a>
                <?php if ($last): ?><br><em>Last generated: <?=esc_html($last)?></em><?php endif; ?>
            </p></div>
            <p><button type="button" id="ss-generate" class="button button-primary">Generate Sitemap Now</button> <span id="ss-status"></span></p>
            <form method="post" action="options.php">
                <?php settings_fields('semantic_sitemap_options'); ?>
                <h2>Business Information</h2>
                <table class="form-table">
                    <tr><th><label for="ss_name">Business Name</label></th><td>
                        <input type="text" id="ss_name" name="semantic_sitemap_options[business_name]" value="<?=esc_attr($opts['business_name'] ?? get_bloginfo('name'))?>" class="regular-text">
                    </td></tr>
                    <tr><th><label>Description</label></th><td>
                        <textarea name="semantic_sitemap_options[business_description]" rows="3" class="large-text"><?=esc_textarea($opts['business_description'] ?? '')?></textarea>
                        <p class="description">What does your business do?</p>
                    </td></tr>
                    <tr><th><label>Mission Statement</label></th><td>
                        <textarea name="semantic_sitemap_options[business_mission]" rows="2" class="large-text"><?=esc_textarea($opts['business_mission'] ?? '')?></textarea>
                    </td></tr>
                </table>
                <h2>Content Settings</h2>
                <table class="form-table">
                    <tr><th>Include</th><td>
                        <label><input type="checkbox" name="semantic_sitemap_options[include_pages]" <?php checked($opts['include_pages'] ?? true)?>> Pages</label><br>
                        <label><input type="checkbox" name="semantic_sitemap_options[include_posts]" <?php checked($opts['include_posts'] ?? true)?>> Posts</label><br>
                        <label><input type="checkbox" name="semantic_sitemap_options[include_categories]" <?php checked($opts['include_categories'] ?? true)?>> Categories</label><br>
                        <label><input type="checkbox" name="semantic_sitemap_options[include_tags]" <?php checked($opts['include_tags'] ?? false)?>> Tags</label>
                    </td></tr>
                </table>
                <h2>Schedule</h2>
                <table class="form-table">
                    <tr><th>Auto-generate</th><td>
                        <label><input type="checkbox" name="semantic_sitemap_options[auto_generate]" <?php checked($opts['auto_generate'] ?? true)?>> Enable</label>
                    </td></tr>
                    <tr><th><label for="ss_sched">Frequency</label></th><td>
                        <select id="ss_sched" name="semantic_sitemap_options[generation_schedule]">
                            <option value="hourly" <?php selected($opts['generation_schedule'] ?? '', 'hourly')?>>Hourly</option>
                            <option value="daily"  <?php selected($opts['generation_schedule'] ?? 'daily', 'daily')?>>Daily</option>
                            <option value="weekly" <?php selected($opts['generation_schedule'] ?? '', 'weekly')?>>Weekly</option>
                        </select>
                    </td></tr>
                </table>
                <?php submit_button('Save Settings'); ?>
            </form>
        </div>
        <?php
    }
}
