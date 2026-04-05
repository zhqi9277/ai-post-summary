<?php
/**
 * Plugin Name: AI Post Summary
 * Plugin URI: https://github.com/zhqi9277
 * Description: 为 WordPress 文章生成和管理 AI 摘要、标签与关键词，支持小工具、短代码、正文自动插入、批量任务与日志。
 * Version: 0.4.2
 * Author: zhqi9277
 * Author URI: mailto:zhqi9277@qq.com
 * Requires at least: 6.0
 * Tested up to: 6.9.4
 * Requires PHP: 7.4
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ai-post-summary
 */

if (!defined('ABSPATH')) {
    exit;
}

$ai_post_summary_base = plugin_dir_path(__FILE__);

require_once $ai_post_summary_base . 'includes/traits/trait-ai-post-summary-admin.php';
require_once $ai_post_summary_base . 'includes/traits/trait-ai-post-summary-generation.php';
require_once $ai_post_summary_base . 'includes/traits/trait-ai-post-summary-frontend.php';
require_once $ai_post_summary_base . 'includes/traits/trait-ai-post-summary-logging.php';
require_once $ai_post_summary_base . 'includes/class-ai-post-summary-plugin.php';
require_once $ai_post_summary_base . 'includes/class-ai-post-summary-widget.php';

register_activation_hook(__FILE__, array('AI_Post_Summary_Plugin', 'activate'));
register_deactivation_hook(__FILE__, array('AI_Post_Summary_Plugin', 'deactivate'));

AI_Post_Summary_Plugin::instance();