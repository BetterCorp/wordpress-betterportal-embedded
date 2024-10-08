<?php
/*
Plugin Name: BetterPortal Theme Embedded
Description: Handles embedding BetterPortal.cloud embedded theme in your WP Site
Version: {{VERSION}}
Author: BetterCorp
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class BetterPortal_Theme_Embedded {
    private $defaultHost = 'embedded-theme.betterportal.net';

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
        add_shortcode('betterportal_embed', array($this, 'betterportal_embed_shortcode'));
        add_action('elementor/widgets/widgets_registered', array($this, 'register_elementor_widget'));
        add_action('elementor/elements/categories_registered', array($this, 'add_elementor_widget_category'));
        add_action('save_post', array($this, 'maybe_flush_rules'));
        add_filter('the_content', array($this, 'check_for_shortcode'));
        add_action('admin_init', array($this, 'handle_flush_rewrites'));
        add_action('add_meta_boxes', array($this, 'add_betterportal_meta_box'));
        add_action('save_post', array($this, 'save_betterportal_meta_box'));
    }

    public function init() {
        $this->register_betterportal_rewrites();
    }

    public function register_betterportal_rewrites() {
        $pages_with_shortcode = $this->get_pages_with_shortcode();
        foreach ($pages_with_shortcode as $page) {
            $rewrite_enabled = get_post_meta($page['page']->ID, '_betterportal_rewrite_enabled', true);
            if ($page['needs_rewrite'] && $rewrite_enabled === '1') {
                $page_path = trim(str_replace(home_url(), '', get_permalink($page['page'])), '/');
                add_rewrite_rule(
                    $page_path . '/(.*)$',
                    'index.php?page_id=' . $page['page']->ID,
                    'top'
                );
            }
        }
    }

    public function get_pages_with_shortcode() {
        $pages = get_pages();
        $pages_with_shortcode = array();
        foreach ($pages as $page) {
            $shortcodes = $this->get_shortcodes_info($page->post_content);
            $elementor_widgets = $this->get_elementor_widgets_info($page->ID);
            
            if ($shortcodes['count'] > 0 || $elementor_widgets['count'] > 0) {
                $pages_with_shortcode[] = array(
                    'page' => $page,
                    'shortcodes_count' => $shortcodes['count'] + $elementor_widgets['count'],
                    'has_path' => $shortcodes['has_path'] || $elementor_widgets['has_path'],
                    'needs_rewrite' => ($shortcodes['count'] - $shortcodes['path_count'] + $elementor_widgets['count'] - $elementor_widgets['path_count']) > 0
                );
            }
        }
        return $pages_with_shortcode;
    }

    public function get_shortcodes_info($content) {
        $count = 0;
        $path_count = 0;
        $has_path = false;
        if (has_shortcode($content, 'betterportal_embed')) {
            $pattern = '/\[betterportal_embed([^\]]*)\]/';
            preg_match_all($pattern, $content, $matches);
            $count = count($matches[0]);
            foreach ($matches[1] as $attrs) {
                if (strpos($attrs, 'path') !== false) {
                    $path_count++;
                    $has_path = true;
                }
            }
        }
        return array('count' => $count, 'path_count' => $path_count, 'has_path' => $has_path);
    }

    public function get_elementor_widgets_info($post_id) {
        $count = 0;
        $path_count = 0;
        $has_path = false;
        if (class_exists('\Elementor\Plugin')) {
            $document = \Elementor\Plugin::$instance->documents->get($post_id);
            if ($document) {
                $data = $document->get_elements_data();
                $result = $this->find_widgets_recursive($data);
                $count = $result['count'];
                $path_count = $result['path_count'];
                $has_path = $result['has_path'];
            }
        }
        return array('count' => $count, 'path_count' => $path_count, 'has_path' => $has_path);
    }

    private function find_widgets_recursive($elements) {
        $count = 0;
        $path_count = 0;
        $has_path = false;
        foreach ($elements as $element) {
            if (isset($element['widgetType']) && $element['widgetType'] === 'betterportal_embed') {
                $count++;
                if (isset($element['settings']['path']) && !empty($element['settings']['path'])) {
                    $path_count++;
                    $has_path = true;
                }
            }
            if (isset($element['elements'])) {
                $result = $this->find_widgets_recursive($element['elements']);
                $count += $result['count'];
                $path_count += $result['path_count'];
                $has_path = $has_path || $result['has_path'];
            }
        }
        return array('count' => $count, 'path_count' => $path_count, 'has_path' => $has_path);
    }

    public function maybe_flush_rules($post_id) {
        if (wp_is_post_revision($post_id)) {
            return;
        }
        $this->register_betterportal_rewrites();
        flush_rewrite_rules();
    }

    public function check_for_shortcode($content) {
        if ($this->page_has_shortcode($content)) {
            $this->maybe_flush_rules(get_the_ID());
        }
        return $content;
    }

    public function page_has_shortcode($content) {
        return has_shortcode($content, 'betterportal_embed');
    }

    public function betterportal_embed_shortcode($atts) {
        static $instance = 0;
        $instance++;

        $atts = shortcode_atts(array(
            'path' => ''
        ), $atts, 'betterportal_embed');

        return $this->generate_embed_output($atts, $instance);
    }

    public function generate_embed_output($atts, $instance) {
        $div_id = 'betterportal-form-' . $instance;
        $host = $this->get_host();
        $script_url = 'https://' . esc_attr($host) . '/import.js?div=' . $div_id;

        if (!empty($atts['path'])) {
            $script_url .= '&path=' . urlencode($atts['path']);
        }

        $output = '<script src="' . esc_url($script_url) . '"></script>';
        $output .= '<div id="' . esc_attr($div_id) . '">';
        $output .= '<div class="betterportal-loader"></div>';
        $output .= '</div>';

        if ($instance === 1) {
            $output .= '
            <style>
                .betterportal-loader {
                    border: 4px solid #f3f3f3;
                    border-top: 4px solid #3498db;
                    border-radius: 50%;
                    width: 40px;
                    height: 40px;
                    animation: betterportal-spin 1s linear infinite;
                    margin: 20px auto;
                }
                @keyframes betterportal-spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
            </style>';
        }

        return $output;
    }

    public function get_host() {
        $options = get_option('betterportal_options');
        return isset($options['host']) && !empty($options['host']) ? $options['host'] : $this->defaultHost;
    }

    public function register_elementor_widget($widgets_manager) {
        require_once(__DIR__ . '/widgets/betterportal-embed-widget.php');
        $widgets_manager->register_widget_type(new \Elementor_BetterPortal_Embed_Widget());
    }

    public function add_elementor_widget_category($elements_manager) {
        $elements_manager->add_category(
            'betterportal',
            [
                'title' => __('BetterPortal', 'betterportal-theme-embedded'),
                'icon' => 'fa fa-plug',
            ]
        );
    }

    public function add_settings_page() {
        add_options_page(
            'BetterPortal Settings',
            'BetterPortal',
            'manage_options',
            'betterportal-settings',
            array($this, 'render_settings_page')
        );
    }

    public function register_settings() {
        register_setting('betterportal_options', 'betterportal_options');

        // Actions tab
        add_settings_section(
            'betterportal_actions_section',
            'Actions',
            null,
            'betterportal-settings-actions'
        );

        // Config tab
        add_settings_section(
            'betterportal_config_section',
            'Configuration',
            null,
            'betterportal-settings-config'
        );
        add_settings_field(
            'betterportal_host',
            'BetterPortal Host',
            array($this, 'host_field_callback'),
            'betterportal-settings-config',
            'betterportal_config_section'
        );

        // Pages tab
        add_settings_section(
            'betterportal_pages_section',
            'Pages with BetterPortal Embed',
            array($this, 'render_pages_section'),
            'betterportal-settings-pages'
        );
    }

    public function host_field_callback() {
        $host = $this->get_host();
        echo "<input type='text' name='betterportal_options[host]' value='" . esc_attr($host) . "' />";
        $this->register_betterportal_rewrites();
        flush_rewrite_rules();
    }

    public function flush_rewrites_button_callback() {
        echo '<input type="submit" name="flush_rewrites" class="button button-secondary" value="Flush Rewrite Rules">';
    }

    public function render_pages_section() {
        $pages = $this->get_pages_with_shortcode();
        if (empty($pages)) {
            echo '<p>No pages with BetterPortal embed found.</p>';
            return;
        }

        echo '<table class="widefat">';
        echo '<thead><tr><th>Page Title</th><th>URL</th><th>Shortcodes</th><th>Rewrite</th></tr></thead>';
        echo '<tbody>';
        foreach ($pages as $page) {
            $edit_link = get_edit_post_link($page['page']->ID);
            $page_url = get_permalink($page['page']->ID);
            $rewrite_enabled = get_post_meta($page['page']->ID, '_betterportal_rewrite_enabled', true);
            $rewrite_enabled = $rewrite_enabled !== '' ? $rewrite_enabled : '1'; // Default to enabled
            
            echo '<tr>';
            echo '<td><a href="' . esc_url($edit_link) . '">' . esc_html($page['page']->post_title) . '</a></td>';
            echo '<td><a href="' . esc_url($page_url) . '" target="_blank">' . esc_url($page_url) . '</a></td>';
            echo '<td>' . esc_html($page['shortcodes_count']) . '</td>';
            echo '<td>';
            if ($page['needs_rewrite']) {
                echo '<label><input type="checkbox" name="betterportal_rewrite[' . $page['page']->ID . ']" value="1" ' . checked($rewrite_enabled, '1', false) . '> Enable';
                echo ' rewrites <i><u>'.esc_url(str_replace(home_url(), '', $page_url)).'*</u></i> to <i><u>'.esc_url(str_replace(home_url(), '', $page_url)).'</u></i></label>';
            } else {
                echo 'N/A';
            }
            echo '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
        
        submit_button('Save Rewrite Settings');
    }

    public function handle_rewrite_settings() {
        if (isset($_POST['betterportal_rewrite'])) {
            $rewrite_settings = $_POST['betterportal_rewrite'];
            $pages = $this->get_pages_with_shortcode();
            
            foreach ($pages as $page) {
                $enabled = isset($rewrite_settings[$page['page']->ID]) ? '1' : '0';
                update_post_meta($page['page']->ID, '_betterportal_rewrite_enabled', $enabled);
            }
            
            add_settings_error('betterportal_messages', 'betterportal_message', 'Rewrite settings saved.', 'updated');
        }
    }

    public function render_settings_page() {
        $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'actions';
        ?>
        <div class="wrap">
            <h1>BetterPortal Settings</h1>
            <h2 class="nav-tab-wrapper">
                <a href="?page=betterportal-settings&tab=actions" class="nav-tab <?php echo $active_tab == 'actions' ? 'nav-tab-active' : ''; ?>">Actions</a>
                <a href="?page=betterportal-settings&tab=config" class="nav-tab <?php echo $active_tab == 'config' ? 'nav-tab-active' : ''; ?>">Configuration</a>
                <a href="?page=betterportal-settings&tab=pages" class="nav-tab <?php echo $active_tab == 'pages' ? 'nav-tab-active' : ''; ?>">Pages/Rewrites</a>
            </h2>

            <?php if ($active_tab == 'actions'): ?>
                <?php do_settings_sections('betterportal-settings-actions'); ?>
                <form method="post" action="">
                    <?php
                    wp_nonce_field('betterportal_flush_rewrites', 'betterportal_flush_rewrites_nonce');
                    submit_button('Flush Rewrite Rules', 'primary', 'flush_rewrites', false);
                    ?>
                </form>

            <?php elseif ($active_tab == 'config'): ?>
                <form method="post" action="options.php">
                    <?php
                    settings_fields('betterportal_options');
                    do_settings_sections('betterportal-settings-config');
                    submit_button();
                    ?>
                </form>

            <?php elseif ($active_tab == 'pages'): ?>
                <?php do_settings_sections('betterportal-settings-pages'); ?>
            <?php endif; ?>
        </div>
        <?php
    }

    public function handle_flush_rewrites() {
        if (isset($_POST['flush_rewrites']) && check_admin_referer('betterportal_flush_rewrites', 'betterportal_flush_rewrites_nonce')) {
            $this->register_betterportal_rewrites();
            flush_rewrite_rules();
            add_settings_error('betterportal_messages', 'betterportal_message', 'Rewrite rules have been flushed.', 'updated');
        }
    }

    public function add_betterportal_meta_box() {
        add_meta_box(
            'betterportal_rewrite_settings',
            'BetterPortal Settings',
            array($this, 'render_betterportal_meta_box'),
            'page',
            'side',
            'default'
        );
    }

    public function render_betterportal_meta_box($post) {
        wp_nonce_field('betterportal_rewrite_settings', 'betterportal_rewrite_settings_nonce');

        $rewrite_enabled = get_post_meta($post->ID, '_betterportal_rewrite_enabled', true);
        $rewrite_enabled = $rewrite_enabled !== '' ? $rewrite_enabled : '1'; // Default to enabled

        $page_info = $this->get_page_shortcode_info($post->ID);

        echo '<p><strong>Shortcodes/Widgets:</strong> ' . esc_html($page_info['shortcodes_count']) . '</p>';
        
        if ($page_info['needs_rewrite']) {
            echo '<label for="betterportal_rewrite_enabled">';
            echo '<input type="checkbox" id="betterportal_rewrite_enabled" name="betterportal_rewrite_enabled" value="1" ' . checked($rewrite_enabled, '1', false) . '>';
            echo ' Enable rewrites <i><u>'.esc_url(str_replace(home_url(), '', get_permalink($post->ID))).'*</u></i> to <i><u>'.esc_url(str_replace(home_url(), '', get_permalink($post->ID))).'</u></i></label>';
        } else {
            echo '<p>This page does not need a rewrite rule.</p>';
        }
    }

    public function save_betterportal_meta_box($post_id) {
        if (!isset($_POST['betterportal_rewrite_settings_nonce']) || 
            !wp_verify_nonce($_POST['betterportal_rewrite_settings_nonce'], 'betterportal_rewrite_settings')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $rewrite_enabled = isset($_POST['betterportal_rewrite_enabled']) ? '1' : '0';
        update_post_meta($post_id, '_betterportal_rewrite_enabled', $rewrite_enabled);

        // Flush rewrite rules if the setting has changed
        if ($rewrite_enabled !== get_post_meta($post_id, '_betterportal_rewrite_enabled', true)) {
            $this->register_betterportal_rewrites();
            flush_rewrite_rules();
        }
    }

    public function get_page_shortcode_info($post_id) {
        $post = get_post($post_id);
        $shortcodes = $this->get_shortcodes_info($post->post_content);
        $elementor_widgets = $this->get_elementor_widgets_info($post_id);
        
        return array(
            'shortcodes_count' => $shortcodes['count'] + $elementor_widgets['count'],
            'has_path' => $shortcodes['has_path'] || $elementor_widgets['has_path'],
            'needs_rewrite' => ($shortcodes['count'] - $shortcodes['path_count'] + $elementor_widgets['count'] - $elementor_widgets['path_count']) > 0
        );
    }
}

new BetterPortal_Theme_Embedded();