<?php
class Semantic_Sitemap_Generator {

    public function generate() {
        $options = get_option('semantic_sitemap_options', []);
        $sitemap = [
            'generatedAt'      => current_time('c'),
            'generator'        => 'Semantic Sitemap Generator for WordPress v' . SEMANTIC_SITEMAP_VERSION,
            'trustLevel'       => 'machine-generated',
            'businessIdentity' => $this->get_business($options),
            'siteStructure'    => [
                'pages'      => $this->get_pages($options),
                'categories' => $this->get_categories($options),
                'tags'       => $this->get_tags($options),
            ],
        ];
        $sitemap['statistics'] = [
            'totalPages'      => count($sitemap['siteStructure']['pages']),
            'totalCategories' => count($sitemap['siteStructure']['categories']),
            'totalTags'       => count($sitemap['siteStructure']['tags']),
        ];
        $sitemap = apply_filters('semantic_sitemap_generated', $sitemap);
        do_action('semantic_sitemap_after_generation', $sitemap);
        return $sitemap;
    }

    private function get_business($options) {
        return apply_filters('semantic_sitemap_business_info', [
            'name'        => $options['business_name']        ?? get_bloginfo('name'),
            'description' => $options['business_description'] ?? get_bloginfo('description'),
            'mission'     => $options['business_mission']     ?? '',
            'products'    => $options['business_products']    ?? [],
            'services'    => $options['business_services']    ?? [],
            'url'         => home_url(),
        ]);
    }

    private function get_pages($options) {
        $pages = [];
        $types = [];
        if ($options['include_pages'] ?? true)  $types[] = 'page';
        if ($options['include_posts'] ?? true)  $types[] = 'post';

        foreach ($types as $type) {
            $posts = get_posts(['post_type' => $type, 'post_status' => 'publish', 'numberposts' => -1]);
            foreach ($posts as $post) $pages[] = $this->extract($post);
        }

        foreach (($options['include_custom_post_types'] ?? []) as $cpt) {
            $posts = get_posts(['post_type' => $cpt, 'post_status' => 'publish', 'numberposts' => -1]);
            foreach ($posts as $post) $pages[] = $this->extract($post);
        }

        return $pages;
    }

    private function extract($post) {
        $content = apply_filters('the_content', $post->post_content);
        return [
            'url'            => get_permalink($post),
            'title'          => get_the_title($post),
            'metaDescription' => $this->get_meta_desc($post),
            'keywords'       => $this->get_keywords($post),
            'abstract'       => $this->get_abstract($content),
            'headings'       => $this->get_headings($content),
            'structuredData' => [['type'=>'wordpress','data'=>[
                'postType'  => $post->post_type,
                'postId'    => $post->ID,
                'author'    => get_the_author_meta('display_name', $post->post_author),
                'published' => get_the_date('c', $post),
                'modified'  => get_the_modified_date('c', $post),
            ]]],
            'lastModified'   => get_the_modified_date('c', $post),
            'postType'       => $post->post_type,
        ];
    }

    private function get_meta_desc($post) {
        // Yoast
        if (function_exists('YoastSEO')) {
            $d = get_post_meta($post->ID, '_yoast_wpseo_metadesc', true);
            if ($d) return $d;
        }
        // Rank Math
        if (class_exists('RankMath')) {
            $d = get_post_meta($post->ID, 'rank_math_description', true);
            if ($d) return $d;
        }
        return $post->post_excerpt
            ? wp_trim_words($post->post_excerpt, 30, '...')
            : wp_trim_words(strip_tags($post->post_content), 30, '...');
    }

    private function get_keywords($post) {
        $kws = [];
        $tags = get_the_tags($post->ID);
        if ($tags) foreach ($tags as $t) $kws[] = $t->name;
        $cats = get_the_category($post->ID);
        if ($cats) foreach ($cats as $c) if ($c->name !== 'Uncategorized') $kws[] = $c->name;
        if (function_exists('YoastSEO')) {
            $fk = get_post_meta($post->ID, '_yoast_wpseo_focuskw', true);
            if ($fk) $kws[] = $fk;
        }
        return array_unique($kws);
    }

    private function get_abstract($content, $words = 50) {
        return wp_trim_words(strip_tags(preg_replace('/\s+/', ' ', $content)), $words, '...');
    }

    private function get_headings($content) {
        $h = ['h1'=>[],'h2'=>[],'h3'=>[],'h4'=>[],'h5'=>[],'h6'=>[]];
        for ($i = 1; $i <= 6; $i++) {
            preg_match_all("/<h{$i}[^>]*>(.*?)<\/h{$i}>/is", $content, $m);
            if (!empty($m[1])) $h["h{$i}"] = array_map('strip_tags', $m[1]);
        }
        return $h;
    }

    private function get_categories($options) {
        if (!($options['include_categories'] ?? true)) return [];
        return array_map(fn($c) => [
            'name'        => $c->name,
            'slug'        => $c->slug,
            'url'         => get_category_link($c->term_id),
            'description' => $c->description,
            'count'       => $c->count,
        ], get_categories(['hide_empty' => true]));
    }

    private function get_tags($options) {
        if (!($options['include_tags'] ?? false)) return [];
        return array_map(fn($t) => [
            'name'  => $t->name,
            'slug'  => $t->slug,
            'url'   => get_tag_link($t->term_id),
            'count' => $t->count,
        ], get_tags(['hide_empty' => true]));
    }

    public function to_xml($sitemap) {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><semanticSitemap/>');
        $xml->addAttribute('generatedAt', $sitemap['generatedAt']);
        $xml->addAttribute('trustLevel',  $sitemap['trustLevel']);
        $b = $xml->addChild('businessIdentity');
        foreach ($sitemap['businessIdentity'] as $k => $v) {
            if (is_array($v)) { $node = $b->addChild($k); foreach ($v as $item) $node->addChild('item', htmlspecialchars($item)); }
            else $b->addChild($k, htmlspecialchars((string)$v));
        }
        $s = $xml->addChild('siteStructure');
        foreach ($sitemap['siteStructure']['pages'] as $p) {
            $node = $s->addChild('page');
            $node->addAttribute('url', $p['url']);
            $node->addChild('title',          htmlspecialchars($p['title']));
            $node->addChild('metaDescription', htmlspecialchars((string)$p['metaDescription']));
            $node->addChild('abstract',       htmlspecialchars((string)$p['abstract']));
        }
        return $xml->asXML();
    }

    public function to_text($sitemap) {
        $t  = "# SEMANTIC SITEMAP\n# Generated: {$sitemap['generatedAt']}\n# Trust: {$sitemap['trustLevel']}\n\n";
        $t .= "## BUSINESS\nName: {$sitemap['businessIdentity']['name']}\n";
        $t .= "Description: {$sitemap['businessIdentity']['description']}\n\n";
        $t .= "## PAGES ({$sitemap['statistics']['totalPages']})\n\n";
        foreach ($sitemap['siteStructure']['pages'] as $i => $p) {
            $t .= "### " . ($i+1) . ". {$p['title']}\nURL: {$p['url']}\n";
            if ($p['metaDescription']) $t .= "Desc: {$p['metaDescription']}\n";
            $t .= "\n";
        }
        return $t;
    }
}
