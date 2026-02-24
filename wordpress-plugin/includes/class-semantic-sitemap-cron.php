<?php
class Semantic_Sitemap_Cron {
    public function schedule_generation($schedule = 'daily') {
        if (!wp_next_scheduled('semantic_sitemap_cron')) wp_schedule_event(time(), $schedule, 'semantic_sitemap_cron');
        add_action('semantic_sitemap_cron', [$this, 'run']);
    }
    public function unschedule_generation() {
        $ts = wp_next_scheduled('semantic_sitemap_cron');
        if ($ts) wp_unschedule_event($ts, 'semantic_sitemap_cron');
    }
    public function run() {
        $gen = new Semantic_Sitemap_Generator();
        $sitemap = $gen->generate();
        update_option('semantic_sitemap_data', $sitemap);
        update_option('semantic_sitemap_last_generated', current_time('mysql'));
    }
}
