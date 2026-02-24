<?php
class Semantic_Sitemap_API {
    public function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }
    public function register_routes() {
        register_rest_route('semantic-sitemap/v1', '/generate', [
            'methods'             => 'POST',
            'callback'            => [$this, 'generate'],
            'permission_callback' => fn() => current_user_can('edit_posts'),
        ]);
        register_rest_route('semantic-sitemap/v1', '/get', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get'],
            'permission_callback' => '__return_true',
        ]);
    }
    public function generate($req) {
        $gen = new Semantic_Sitemap_Generator();
        $sitemap = $gen->generate();
        update_option('semantic_sitemap_data', $sitemap);
        update_option('semantic_sitemap_last_generated', current_time('mysql'));
        return new WP_REST_Response($sitemap, 200);
    }
    public function get($req) {
        $sitemap = get_option('semantic_sitemap_data');
        if (!$sitemap) return new WP_Error('not_found', 'No sitemap yet — POST to /generate first', ['status' => 404]);
        return new WP_REST_Response($sitemap, 200);
    }
}
