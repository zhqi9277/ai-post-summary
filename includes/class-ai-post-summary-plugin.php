<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('AI_Post_Summary_Plugin')) {
    class AI_Post_Summary_Plugin
    {
        use AI_Post_Summary_Admin_Trait;
        use AI_Post_Summary_Generation_Trait;
        use AI_Post_Summary_Frontend_Trait;
        use AI_Post_Summary_Logging_Trait;

        const OPTION_KEY = 'ai_post_summary_options';
        const META_SUMMARY = '_ai_summary';
        const META_STATUS = '_ai_summary_status';
        const META_HASH = '_ai_summary_source_hash';
        const META_UPDATED_AT = '_ai_summary_updated_at';
        const META_ERROR = '_ai_summary_error';
        const META_MODEL = '_ai_summary_model';
        const META_TERMS_STATUS = '_ai_terms_status';
        const META_TERMS_HASH = '_ai_terms_source_hash';
        const META_TERMS_UPDATED_AT = '_ai_terms_updated_at';
        const META_TERMS_ERROR = '_ai_terms_error';
        const CRON_HOOK = 'ai_post_summary_generate_event';
        const CRON_TERMS_HOOK = 'ai_post_summary_generate_terms_event';
        const NONCE_ACTION = 'ai_post_summary_generate';
        const NONCE_TERMS_ACTION = 'ai_post_summary_generate_terms';
        const DB_VERSION = '0.2.0';
        const DB_VERSION_OPTION = 'ai_post_summary_db_version';
        const LOG_LEVEL_DEBUG = 'debug';
        const LOG_LEVEL_INFO = 'info';
        const LOG_LEVEL_WARNING = 'warning';
        const LOG_LEVEL_ERROR = 'error';

        /**
         * @var AI_Post_Summary_Plugin|null
         */
        private static $instance = null;

        /**
         * @return AI_Post_Summary_Plugin
         */
        public static function instance()
        {
            if (null === self::$instance) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        private function __construct()
        {
            add_action('plugins_loaded', array($this, 'maybe_upgrade_schema'));
            add_action('admin_init', array($this, 'register_settings'));
            add_action('admin_menu', array($this, 'register_settings_page'));
            add_action('widgets_init', array($this, 'register_widget'));
            add_action('save_post_post', array($this, 'schedule_summary_generation'), 20, 3);
            add_action('save_post_post', array($this, 'schedule_terms_generation'), 21, 3);
            add_action(self::CRON_HOOK, array($this, 'handle_cron_generation'), 10, 2);
            add_action(self::CRON_TERMS_HOOK, array($this, 'handle_cron_terms_generation'), 10, 2);
            add_shortcode('ai_post_summary', array($this, 'render_summary_shortcode'));
            add_filter('the_content', array($this, 'maybe_prepend_to_content'));
            add_action('wp_head', array($this, 'render_tag_beautify_style'));
            add_action('add_meta_boxes', array($this, 'register_meta_box'));
            add_action('admin_post_ai_post_summary_generate', array($this, 'handle_manual_generation'));
            add_action('admin_post_ai_post_summary_bulk_queue', array($this, 'handle_bulk_queue'));
            add_action('admin_post_ai_post_summary_clear_logs', array($this, 'handle_clear_logs'));
            add_action('admin_post_ai_post_summary_save_summary', array($this, 'handle_save_summary'));
            add_action('admin_post_ai_post_summary_retry_summary', array($this, 'handle_retry_summary'));
            add_action('admin_post_ai_post_summary_generate_terms', array($this, 'handle_manual_terms_generation'));
            add_action('admin_post_ai_post_summary_save_terms', array($this, 'handle_save_terms'));
            add_action('admin_post_ai_post_summary_retry_terms', array($this, 'handle_retry_terms'));
            add_action('admin_notices', array($this, 'render_admin_notice'));
        }
        public function get_options()
        {
            $defaults = array(
                'endpoint' => 'https://api.openai.com/v1/chat/completions',
                'api_key' => '',
                'model' => 'gpt-4.1-mini',
                'summary_style' => 'bullets',
                'summary_length' => 4,
                'auto_generate' => 1,
                'auto_insert' => 0,
                'show_title' => 1,
                'auto_generate_terms' => 1,
                'tag_limit' => 5,
                'keyword_limit' => 8,
                'beautify_tags' => 0,
                'system_prompt' => $this->get_default_system_prompt(),
                'user_prompt_template' => $this->get_default_user_prompt_template(),
            );

            return wp_parse_args(get_option(self::OPTION_KEY, array()), $defaults);
        }


        public function register_widget()
        {
            register_widget('AI_Post_Summary_Widget');
        }


        public static function activate()
        {
            if (!get_option(self::OPTION_KEY)) {
                add_option(self::OPTION_KEY, self::instance()->get_options());
            }

            self::instance()->create_or_upgrade_tables();
            update_option(self::DB_VERSION_OPTION, self::DB_VERSION);
        }

        public static function deactivate()
        {
            wp_clear_scheduled_hook(self::CRON_HOOK);
            wp_clear_scheduled_hook(self::CRON_TERMS_HOOK);
        }

    }
}
