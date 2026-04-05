<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('AI_Post_Summary_Widget')) {
    class AI_Post_Summary_Widget extends WP_Widget
    {
        public function __construct()
        {
            parent::__construct(
                'ai_post_summary_widget',
                __('AI 文章摘要', 'ai-post-summary'),
                array(
                    'classname' => 'ai_post_summary_widget',
                    'description' => __('根据当前文章显示 AI 摘要。', 'ai-post-summary'),
                )
            );
        }

        /**
         * @param array<string, mixed> $args
         * @param array<string, mixed> $instance
         * @return void
         */
        public function widget($args, $instance)
        {
            if (!is_singular('post')) {
                return;
            }

            $post_id = get_the_ID();
            if (!$post_id) {
                return;
            }

            $output = AI_Post_Summary_Plugin::instance()->render_summary_card($post_id);
            if ('' === $output) {
                return;
            }

            echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }

        /**
         * @param array<string, mixed> $instance
         * @return void
         */
        public function form($instance)
        {
            echo '<p>' . esc_html__('此小工具会自动读取当前文章的 AI 摘要，无需额外配置。', 'ai-post-summary') . '</p>';
        }
    }
}