<?php

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (! class_exists('SEW_Admin')) {
    class SEW_Admin
    {
        /**
         * Initialize the admin menu and settings.
         */
        public function __construct()
        {
            // Hook to add the settings menu
            add_action('admin_menu', array($this, 'add_settings_menu'));
            // Hook to register settings
            add_action('admin_init', array($this, 'register_settings'));
        }

        /**
         * Add the settings page to the WordPress admin menu.
         */
        public function add_settings_menu()
        {
            add_options_page(
                'Custom Slug Manager', // Page title
                'Custom Slug Manager', // Menu title
                'manage_options',   // Capability
                'slug-editor-for-wordpress', // Menu slug
                array($this, 'settings_page') // Callback function
            );
        }

        /**
         * Render the settings page.
         */
        public function settings_page()
        {
?>
            <div class="wrap">
                <h1><?php esc_html_e('Custom Slug Manager', 'slug-editor-for-wordpress'); ?></h1>
                <!-- <div id="message" class="notice notice-warning">
                    <p>
                        <?php
                        printf(
                            __("If your custom slug isn't working, go to %s and click Save Changes to refresh the permalinks."),
                            '<code>Settings > Permalinks</code>'
                        ); ?>
                    </p>
                </div> -->
                <div id="message" class="notice notice-info">
                    <p>
                        <?php
                        echo __('To change the slug for a post type or taxonomy, enter the desired slug without the leading slash. For example, if you want the slug to be "blog", just enter "blog" (not "/blog")', '<code>/</code>');
                        ?>
                    </p>
                    <p>
                        <?php esc_html_e('Leave the field blank to keep the default slug or enter "/" to remove the slug completely.', 'slug-editor-for-wordpress'); ?>
                    </p>
                </div>
                <div id="sew-manager-container" style="display: flex; gap: 20px;">
                    <!-- Sidebar -->
                    <div id="sew-manager-sidebar" style="flex: 0 0 200px; border-right: 1px solid #ddd;">
                        <ul style="list-style: none; padding: 0;">
                            <li>
                                <a href="#" data-section="post-types" class="sew-manager-tab active">
                                    <?php esc_html_e('Post Types', 'slug-editor-for-wordpress'); ?>
                                </a>
                            </li>
                            <li>
                                <a href="#" data-section="categories" class="sew-manager-tab">
                                    <?php esc_html_e('Categories', 'slug-editor-for-wordpress'); ?>
                                </a>
                            </li>
                        </ul>
                    </div>

                    <!-- Content Area -->
                    <div id="sew-manager-content" style="flex: 1;">
                        <form method="post" action="options.php">
                            <?php
                            settings_fields('sew_manager_settings_group');
                            ?>
                            <!-- Post Types Section -->
                            <div id="sew-manager-post-types-section" class="sew-manager-section" style="display: block;">
                                <h2 style="display: flex; align-items: center; gap: 8px;">
                                    <?php esc_html_e('Manage Post Type Slugs', 'sew-manager'); ?>
                                    <span class="tooltip-icon" title="<?php
                                                                        printf(
                                                                            __("If your custom slug isn't working, go to Settings > Permalinks and click Save Changes to refresh the permalinks.")
                                                                        ); ?>">?</span>
                                </h2>
                                <?php do_settings_sections('sew-manager-post-types'); ?>
                            </div>

                            <!-- Categories Section -->
                            <div id="sew-manager-categories-section" class="sew-manager-section" style="display: none;">
                                <h2 style="display: flex; align-items: center; gap: 8px;">
                                    <?php esc_html_e('Manage Taxonomy Slugs', 'sew-manager'); ?>
                                    <span class="tooltip-icon" title="<?php
                                                                        printf(
                                                                            __("If your custom slug isn't working, go to Settings > Permalinks and click Save Changes to refresh the permalinks.")
                                                                        ); ?>">?</span>
                                </h2>
                                <?php do_settings_sections('sew-manager-categories'); ?>
                            </div>

                            <?php
                            submit_button();
                            ?>
                        </form>
                    </div>
                </div>
            </div>
        <?php
        }

        /**
         * Register the settings for the plugin.
         */
        public function register_settings()
        {
            register_setting(
                'sew_manager_settings_group',
                'sew_manager_settings'
            );

            // Post Types Section
            add_settings_section(
                'sew_manager_post_types_section',
                __('', 'slug-editor-for-wordpress'),
                null,
                'sew-manager-post-types'
            );

            // Add only the default 'post' type
            add_settings_field(
                'cpt_slug_post',
                __('Post :', 'slug-editor-for-wordpress'),
                array($this, 'slug_field_callback'),
                'sew-manager-post-types',
                'sew_manager_post_types_section',
                array(
                    'label_for' => 'cpt_slug_post',
                    'post_type' => 'post',
                )
            );

            // Get all custom public post types (excluding built-in types like 'page', 'attachment', etc.)
            $custom_post_types = get_post_types(['_builtin' => false, 'public' => true], 'objects');

            // List of post types to exclude
            $exclude_post_types = $this->get_excluded_post_types();

            foreach ($custom_post_types as $post_type) {
                if (in_array($post_type->name, $exclude_post_types)) {
                    continue;
                }

                add_settings_field(
                    'cpt_slug_' . $post_type->name,
                    sprintf(__('%s : ', 'sew-manager'), $post_type->label),
                    [$this, 'slug_field_callback'],
                    'sew-manager-post-types',
                    'sew_manager_post_types_section',
                    ['label_for' => 'cpt_slug_' . $post_type->name, 'post_type' => $post_type->name]
                );
            }

            // Categories Section
            add_settings_section(
                'sew_manager_categories_section',
                __('', 'sew-manager'),
                null,
                'sew-manager-categories'
            );

            $taxonomies = get_taxonomies(['public' => true], 'objects');
            foreach ($taxonomies as $taxonomy) {
                add_settings_field(
                    'taxonomy_slug_' . $taxonomy->name,
                    sprintf(__('%s : ', 'sew-manager'), $taxonomy->label),
                    [$this, 'slug_field_callback'],
                    'sew-manager-categories',
                    'sew_manager_categories_section',
                    ['label_for' => 'taxonomy_slug_' . $taxonomy->name, 'post_type' => $taxonomy->name]
                );
            }
        }

        /**
         * Returns an array of post types that should be excluded from the settings page.
         * @return array
         */
        private function get_excluded_post_types()
        {
            $excluded = array();

            // Add hardcoded exclusions
            $excluded = array_merge($excluded, array(
                'scheduled-action',
                'patterns_ai_data'
            ));

            // Check if WooCommerce is active
            if (class_exists('WooCommerce')) {
                $excluded = array_merge($excluded, array(
                    'product',
                    'product_variation',
                    'shop_order',
                    'shop_order_refund',
                    'shop_coupon',
                    'shop_webhook',
                    'shop_subscription',
                    'shop_order_placehold',
                    'shop_subscription_plan'
                ));
            }

            // Check if ACF is active
            if (class_exists('ACF')) {
                $excluded = array_merge($excluded, array(
                    'acf-field-group',
                    'acf-field',
                    'acf-taxonomy',
                    'acf-post-type',
                    'acf-options-page',
                    'acf-ui-options-page'
                ));
            }

            // Check if Elementor is active
            if (defined('ELEMENTOR_VERSION')) {
                $excluded = array_merge($excluded, array(
                    'e-floating-buttons',
                    'elementor_library',
                    'elementor_templates'
                ));
            }

            return $excluded;
        }

        /**
         * Renders a text input field for the settings page to set a custom slug for a post type.
         *
         * @param array $args {
         *     @type string $post_type The post type name.
         *     @type string $label_for The HTML `for` attribute for the label.
         * }
         */
        public function slug_field_callback($args)
        {
            $options = get_option('sew_manager_settings');

            $slug = isset($options[$args['post_type']]) ? esc_attr($options[$args['post_type']]) : '';
        ?>
            <label for="<?php echo esc_attr($args['label_for']); ?>">
                /<?php echo esc_html($args['post_type']); ?> to
            </label>
            <input type="text" id="<?php echo esc_attr($args['label_for']); ?>" name="sew_manager_settings[<?php echo esc_attr($args['post_type']); ?>]" value="<?php echo $slug; ?>" placeholder="<?php esc_attr_e('Enter new slug or "/" to remove', 'slug-editor-for-wordpress'); ?>">
<?php
        }
    }
}
