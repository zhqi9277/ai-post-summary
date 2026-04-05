<?php
if (!defined('ABSPATH')) {
    exit;
}

trait AI_Post_Summary_Logging_Trait
{
        public function maybe_upgrade_schema()
        {
            $version = (string) get_option(self::DB_VERSION_OPTION, '');
            if (self::DB_VERSION !== $version) {
                $this->create_or_upgrade_tables();
                update_option(self::DB_VERSION_OPTION, self::DB_VERSION);
            }
        }

        private function create_or_upgrade_tables()
        {
            global $wpdb;

            require_once ABSPATH . 'wp-admin/includes/upgrade.php';

            $table = $this->get_log_table_name();
            $charset_collate = $wpdb->get_charset_collate();

            $sql = "CREATE TABLE {$table} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                level varchar(20) NOT NULL,
                message text NOT NULL,
                context longtext NULL,
                created_at datetime NOT NULL,
                PRIMARY KEY  (id),
                KEY level (level),
                KEY created_at (created_at)
            ) {$charset_collate};";

            dbDelta($sql);
        }

        /**
         * @return string
         */
        private function get_log_table_name()
        {
            global $wpdb;

            return $wpdb->prefix . 'ai_post_summary_logs';
        }

        /**
         * @return string[]
         */
        private function get_log_levels()
        {
            return array(
                self::LOG_LEVEL_DEBUG,
                self::LOG_LEVEL_INFO,
                self::LOG_LEVEL_WARNING,
                self::LOG_LEVEL_ERROR,
            );
        }

        /**
         * @param string $level
         * @param string $message
         * @param array<string, mixed> $context
         * @return void
         */
        private function log($level, $message, $context = array())
        {
            if (!in_array($level, $this->get_log_levels(), true)) {
                $level = self::LOG_LEVEL_INFO;
            }

            global $wpdb;

            $wpdb->insert(
                $this->get_log_table_name(),
                array(
                    'level' => $level,
                    'message' => wp_strip_all_tags((string) $message),
                    'context' => !empty($context) ? wp_json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '',
                    'created_at' => current_time('mysql'),
                ),
                array('%s', '%s', '%s', '%s')
            );
        }

        /**
         * @return array<string, int>
         */

}
