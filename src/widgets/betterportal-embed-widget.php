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

class Elementor_BetterPortal_Embed_Widget extends \Elementor\Widget_Base {
    public function get_name() {
        return 'betterportal_embed';
    }

    public function get_title() {
        return __('BetterPortal Embed', 'betterportal-theme-embedded');
    }

    public function get_icon() {
        return 'eicon-code';
    }

    public function get_categories() {
        return ['betterportal'];
    }

    protected function _register_controls() {
        $this->start_controls_section(
            'content_section',
            [
                'label' => __('Content', 'betterportal-theme-embedded'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'path',
            [
                'label' => __('Path', 'betterportal-theme-embedded'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'placeholder' => __('Optional: specific path', 'betterportal-theme-embedded'),
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $betterportal = new BetterPortal_Theme_Embedded();
        echo $betterportal->generate_embed_output($settings, rand());
    }

    protected function _content_template() {
        ?>
        <div class="betterportal-embed-placeholder">
            <p><?php echo __('BetterPortal Embed will be displayed here', 'betterportal-theme-embedded'); ?></p>
        </div>
        <?php
    }
}