<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (!class_exists('SEW_Rewrite')) {
    class SEW_Rewrite
    {
        /**
         * Initialize rewrite rules and hooks.
         */
        public function init_rewrite_rules()
        {
            add_filter('register_post_type_args', array($this, 'csm_register_custom_slug'), 10, 2);
            add_filter('post_type_link', array($this, 'csm_modify_post_links'), 10, 2);
            add_action('pre_get_posts', array($this, 'csm_parse_custom_post_type_request'));
            add_action('template_redirect', array($this, 'redirect_old_to_new'));

            // Taxonomy-specific hooks
            add_filter('rewrite_rules_array', array($this, 'csm_modify_taxonomy_rewrite_rules'));
            add_action('pre_get_posts', array($this, 'csm_parse_taxonomy_request'));
            add_filter('term_link', array($this, 'csm_modify_term_links'), 10, 3);
        }

        /**
         * Modify post type rewrite rules to apply custom slugs or remove slugs.
         */
        public function csm_register_custom_slug($args, $post_type)
        {
            $options = get_option('sew_manager_settings');
            if (isset($options[$post_type])) {
                $new_slug = $options[$post_type];
                if ($new_slug === '/') {
                    $args['rewrite'] = array('slug' => '', 'with_front' => false);
                } elseif (!empty($new_slug)) {
                    $args['rewrite'] = array('slug' => $new_slug, 'with_front' => true);
                }
            }
            return $args;
        }

        /**
         * Modify post links to reflect the new slug or removed slug.
         */
        public function csm_modify_post_links($post_link, $post)
        {
            $options = get_option('sew_manager_settings');
            if (isset($options[$post->post_type])) {
                $new_slug = $options[$post->post_type];

                if ($new_slug === '/') {
                    $post_link = str_replace('/' . $post->post_type . '/', '/', $post_link);
                } elseif (!empty($new_slug)) {
                    $post_link = str_replace('/' . $post->post_type . '/', '/' . $new_slug . '/', $post_link);
                }
            }
            return $post_link;
        }

        /**
         * Adjust main query to handle requests for post types with removed slugs.
         */
        public function csm_parse_custom_post_type_request($query)
        {
            if (!$query->is_main_query() || !isset($query->query['name'])) {
                return;
            }

            $options = get_option('sew_manager_settings');
            $post_types = get_post_types(array('_builtin' => false), 'names');

            foreach ($post_types as $post_type) {
                if (isset($options[$post_type]) && $options[$post_type] === '/') {
                    $query->set('post_type', array_merge(array('post', 'page'), array($post_type)));
                }
            }
        }

        /**
         * Modify taxonomy rewrite rules to apply custom slugs or remove slugs.
         */
        public function csm_modify_taxonomy_rewrite_rules($rules)
        {
            $options = get_option('sew_manager_settings');
            $taxonomies = get_taxonomies(array('public' => true), 'objects');

            foreach ($taxonomies as $taxonomy) {
                if (isset($options[$taxonomy->name])) {
                    $new_slug = $options[$taxonomy->name];
                    if ($new_slug === '/') {
                        // Remove the slug entirely
                        $new_rules = array();
                        foreach ($rules as $rule => $rewrite) {
                            if (strpos($rule, $taxonomy->rewrite['slug']) !== false) {
                                $new_rules[str_replace($taxonomy->rewrite['slug'] . '/', '', $rule)] = $rewrite;
                            }
                        }
                        $rules = array_merge($new_rules, $rules);
                    } elseif (!empty($new_slug)) {
                        // Replace with the new slug
                        $new_rules = array();
                        foreach ($rules as $rule => $rewrite) {
                            if (strpos($rule, $taxonomy->rewrite['slug']) !== false) {
                                $new_rules[str_replace($taxonomy->rewrite['slug'], $new_slug, $rule)] = $rewrite;
                            }
                        }
                        $rules = array_merge($new_rules, $rules);
                    }
                }
            }

            return $rules;
        }

        /**
         * Modify term links to reflect the new slug or removed slug.
         */
        public function csm_modify_term_links($termlink, $term, $taxonomy)
        {
            $options = get_option('sew_manager_settings');
            if (isset($options[$taxonomy])) {
                $new_slug = $options[$taxonomy];

                if ($new_slug === '/') {
                    $termlink = str_replace('/' . $taxonomy . '/', '/', $termlink);
                } elseif (!empty($new_slug)) {
                    $termlink = str_replace('/' . $taxonomy . '/', '/' . $new_slug . '/', $termlink);
                }
            }
            return $termlink;
        }

        /**
         * Adjust main query to handle taxonomy archives with removed slugs.
         */
        public function csm_parse_taxonomy_request($query)
        {
            if (!$query->is_main_query() || !isset($query->query['taxonomy'])) {
                return;
            }

            $options = get_option('sew_manager_settings');
            $taxonomies = get_taxonomies(array('public' => true), 'names');

            foreach ($taxonomies as $taxonomy) {
                if (isset($options[$taxonomy]) && $options[$taxonomy] === '/') {
                    $query->set('taxonomy', $taxonomy);
                }
            }
        }

        /**
         * Redirect old URLs with the original slug to the new or removed slug URL.
         */
        public function redirect_old_to_new()
        {
            if (is_singular() || is_category() || is_tax()) {
                $options = get_option('sew_manager_settings');

                if (is_singular()) {
                    global $post;
                    $post_type = $post->post_type;

                    if (isset($options[$post_type])) {
                        $new_slug = $options[$post_type];
                        $current_url = trailingslashit($_SERVER['REQUEST_URI']);
                        $expected_url = '';

                        if ($new_slug === '/') {
                            $expected_url = trailingslashit(home_url($post->post_name));
                        } elseif (!empty($new_slug)) {
                            $expected_url = trailingslashit(home_url($new_slug . '/' . $post->post_name));
                        }

                        if ($expected_url && $current_url !== trailingslashit(parse_url($expected_url, PHP_URL_PATH))) {
                            wp_redirect($expected_url, 301);
                            exit;
                        }
                    }
                }

                if (is_category() || is_tax()) {
                    $taxonomy = get_query_var('taxonomy');
                    $term_slug = get_query_var('term');

                    if (isset($options[$taxonomy])) {
                        $new_slug = $options[$taxonomy];
                        $current_url = trailingslashit($_SERVER['REQUEST_URI']);
                        $expected_url = '';

                        if ($new_slug === '/') {
                            $expected_url = trailingslashit(home_url($term_slug));
                        } elseif (!empty($new_slug)) {
                            $expected_url = trailingslashit(home_url($new_slug . '/' . $term_slug));
                        }

                        if ($expected_url && $current_url !== trailingslashit(parse_url($expected_url, PHP_URL_PATH))) {
                            wp_redirect($expected_url, 301);
                            exit;
                        }
                    }
                }
            }
        }
    }
}
