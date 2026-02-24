<?php
/**
 * Plugin Name: Semantic Sitemap Generator
 * Description: Machine-generated semantic sitemap combining business
 * identity with verified site content. Solves the LLMS.txt trust problem.
 * Version: 1.0.0
 * License: GPL2
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Developer Contact: https://www.ethical.ink
 * Developer Contact: Samurai Labs tools@ethical.ink
 */

if (!defined('ABSPATH')) exit;

define('SEMANTIC_SITEMAP_VERSION',  '1.0.0');
define('SEMANTIC_SITEMAP_DIR',      plugin_dir_path(__FILE__));
define('SEMANTIC_SITEMAP_URL',      plugin_dir_url(__FILE__));

require_once SEMANTIC_SITEMAP_DIR . 'includes/class-semantic-sitemap-generator.php';
require_once SEMANTIC_SITEMAP_DIR . 'includes/class-semantic-sitemap-admin.php';
require_once SEMANTIC_SITEMAP_DIR . 'includes/class-semantic-sitemap-cron.php';
require_once SEMANTIC_SITEMAP_DIR . 'includes/class-semantic-sitemap-api.php';

class Semantic_Sitemap {
    private static $instance = null;
    public $admin, $generator, $cron, $api;

    public static function get_instance() {
        if (!self::$instance) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        $this->admin     = new Semantic_Sitemap_Admin();
        $this->generator = new Semantic_Sitemap_Generator();
        $this->cron      = new Semantic_Sitemap_Cron();
        $this->api       = new Semantic_Sitemap_API();

        add_action('init', [$this, 'register_rewrite_rules']);
        add_filter('query_vars', [$this, 'add_query_vars']);
        add_action('template_redirect', [$this, 'handle_request']);

        register_activation_hook(__FILE__,   [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
    }

    public function register_rewrite_rules() {
        add_rewrite_rule('^semantic-sitemap\.(json|xml|txt)$', 'index.php?semantic_sitemap=1&format=$matches[1]', 'top');
        if (!get_option('semantic_sitemap_flushed')) {
            flush_rewrite_rules();
            update_option('semantic_sitemap_flushed', 1);
        }
    }

    public function add_query_vars($vars) {
        $vars[] = 'semantic_sitemap';
        $vars[] = 'format';
        return $vars;
    }

    public function handle_request() {
        if (get_query_var('semantic_sitemap') != '1') return;
        $format  = get_query_var('format', 'json');
        $sitemap = get_option('semantic_sitemap_data');
        if (!$sitemap) {
            $sitemap = $this->generator->generate();
            update_option('semantic_sitemap_data', $sitemap);
            update_option('semantic_sitemap_last_generated', current_time('mysql'));
        }
        switch ($format) {
            case 'xml':
                header('Content-Type: application/xml; charset=utf-8');
                echo $this->generator->to_xml($sitemap);
                break;
            case 'txt':
                header('Content-Type: text/plain; charset=utf-8');
                echo $this->generator->to_text($sitemap);
                break;
            default:
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode($sitemap, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }
        exit;
    }

    public function activate() {
        $defaults = [
            'business_name'         => get_bloginfo('name'),
            'business_description'  => get_bloginfo('description'),
            'business_mission'      => '',
            'business_products'     => [],
            'business_services'     => [],
            'include_pages'         => true,
            'include_posts'         => true,
            'include_categories'    => true,
            'include_tags'          => false,
            'auto_generate'         => true,
            'generation_schedule'   => 'daily',
        ];
        add_option('semantic_sitemap_options', $defaults);
        delete_option('semantic_sitemap_flushed');
        $this->cron->schedule_generation('daily');
    }

    public function deactivate() {
        $this->cron->unschedule_generation();
        delete_option('semantic_sitemap_flushed');
        flush_rewrite_rules();
    }
}

add_action('plugins_loaded', function() { Semantic_Sitemap::get_instance(); });
