<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Elementor_BetterPortal_Embed_Widget extends \Elementor\Widget_Base
{
    public function get_name()
    {
        return 'betterportal_embed';
    }

    public function get_title()
    {
        return esc_html__('BetterPortal Embed', 'betterportal-theme-embedded');
    }

    public function get_icon()
    {
        return 'eicon-code';
    }

    public function get_categories()
    {
        return ['betterportal'];
    }

    protected function _register_controls()
    {
        $this->start_controls_section(
            'content_section',
            [
                'label' => esc_html__('Content', 'betterportal-theme-embedded'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'path',
            [
                'label' => esc_html__('Path', 'betterportal-theme-embedded'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'placeholder' => esc_html__('Optional: specific path', 'betterportal-theme-embedded'),
            ]
        );

        $this->end_controls_section();
    }

    protected function render()
    {
        $settings = $this->get_settings_for_display();
        $betterportal = new BetterPortal_Theme_Embedded();
        echo wp_kses_post($betterportal->generate_embed_output($settings, wp_rand()));
    }

    protected function _content_template()
    {
?>
        <div class="betterportal-embed-placeholder">
            <p><?php echo esc_html__('BetterPortal Embed will be displayed here', 'betterportal-theme-embedded'); ?></p>
        </div>
<?php
    }
}
