<?php
if (!defined('ABSPATH')) {
    exit;
}

trait AI_Post_Summary_Admin_Trait
{
        public function register_settings()
        {
            register_setting(self::OPTION_KEY, self::OPTION_KEY, array($this, 'sanitize_options'));
        }

        /**
         * @param array<string, mixed> $input
         * @return array<string, mixed>
         */
        public function sanitize_options($input)
        {
            $output = $this->get_options();

            $output['endpoint'] = isset($input['endpoint']) ? esc_url_raw(trim((string) $input['endpoint'])) : $output['endpoint'];
            $output['api_key'] = isset($input['api_key']) ? sanitize_text_field((string) $input['api_key']) : '';
            $output['model'] = isset($input['model']) ? sanitize_text_field((string) $input['model']) : $output['model'];
            $output['summary_style'] = (isset($input['summary_style']) && in_array($input['summary_style'], array('bullets', 'paragraph'), true)) ? $input['summary_style'] : 'bullets';
            $output['system_prompt'] = isset($input['system_prompt']) ? sanitize_textarea_field((string) $input['system_prompt']) : $this->get_default_system_prompt();
            $output['user_prompt_template'] = isset($input['user_prompt_template']) ? sanitize_textarea_field((string) $input['user_prompt_template']) : $this->get_default_user_prompt_template();

            $length = isset($input['summary_length']) ? absint($input['summary_length']) : 4;
            $output['summary_length'] = min(8, max(1, $length));
            $tag_limit = isset($input['tag_limit']) ? absint($input['tag_limit']) : 5;
            $keyword_limit = isset($input['keyword_limit']) ? absint($input['keyword_limit']) : 8;
            $output['tag_limit'] = min(12, max(1, $tag_limit));
            $output['keyword_limit'] = min(20, max(1, $keyword_limit));

            $output['auto_generate'] = empty($input['auto_generate']) ? 0 : 1;
            $output['auto_insert'] = empty($input['auto_insert']) ? 0 : 1;
            $output['show_title'] = empty($input['show_title']) ? 0 : 1;
            $output['auto_generate_terms'] = empty($input['auto_generate_terms']) ? 0 : 1;
            $output['beautify_tags'] = empty($input['beautify_tags']) ? 0 : 1;

            return $output;
        }

        public function register_settings_page()
        {
            add_options_page(
                __('AI 摘要', 'ai-post-summary'),
                __('AI 摘要', 'ai-post-summary'),
                'manage_options',
                'ai-post-summary',
                array($this, 'render_settings_page')
            );
        }

        public function render_settings_page()
        {
            if (!current_user_can('manage_options')) {
                return;
            }

            $options = $this->get_options();
            $tab = $this->get_current_tab();
            ?>
            <div class="wrap">
                <h1><?php echo esc_html__('AI 文章摘要', 'ai-post-summary'); ?></h1>
                <?php $this->render_tab_nav($tab); ?>
                <?php
                if ('tools' === $tab) {
                    $this->render_tools_page(true);
                } elseif ('summaries' === $tab) {
                    $this->render_summaries_page(true);
                } elseif ('terms' === $tab) {
                    $this->render_terms_page(true);
                } elseif ('logs' === $tab) {
                    $this->render_logs_page(true);
                } else {
                    ?>
                    <p><?php echo esc_html__('配置 AI 接口后，可以为文章生成摘要、标签和关键词，并通过小工具、短代码或正文自动插入显示摘要。', 'ai-post-summary'); ?></p>
                    <form method="post" action="options.php">
                        <?php settings_fields(self::OPTION_KEY); ?>
                        <table class="form-table" role="presentation">
                            <tbody>
                            <tr>
                                <th scope="row"><label for="ai-post-summary-endpoint"><?php echo esc_html__('接口地址', 'ai-post-summary'); ?></label></th>
                                <td>
                                    <input id="ai-post-summary-endpoint" name="<?php echo esc_attr(self::OPTION_KEY); ?>[endpoint]" type="url" class="regular-text code" value="<?php echo esc_attr($options['endpoint']); ?>">
                                    <p class="description"><?php echo esc_html__('默认使用兼容 Chat Completions 的接口。', 'ai-post-summary'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="ai-post-summary-api-key"><?php echo esc_html__('API Key', 'ai-post-summary'); ?></label></th>
                                <td>
                                    <input id="ai-post-summary-api-key" name="<?php echo esc_attr(self::OPTION_KEY); ?>[api_key]" type="password" class="regular-text code" value="<?php echo esc_attr($options['api_key']); ?>" autocomplete="off">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="ai-post-summary-model"><?php echo esc_html__('模型名称', 'ai-post-summary'); ?></label></th>
                                <td>
                                    <input id="ai-post-summary-model" name="<?php echo esc_attr(self::OPTION_KEY); ?>[model]" type="text" class="regular-text code" value="<?php echo esc_attr($options['model']); ?>">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="ai-post-summary-style"><?php echo esc_html__('摘要样式', 'ai-post-summary'); ?></label></th>
                                <td>
                                    <select id="ai-post-summary-style" name="<?php echo esc_attr(self::OPTION_KEY); ?>[summary_style]">
                                        <option value="bullets" <?php selected($options['summary_style'], 'bullets'); ?>><?php echo esc_html__('要点列表', 'ai-post-summary'); ?></option>
                                        <option value="paragraph" <?php selected($options['summary_style'], 'paragraph'); ?>><?php echo esc_html__('短段落', 'ai-post-summary'); ?></option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="ai-post-summary-length"><?php echo esc_html__('摘要上限', 'ai-post-summary'); ?></label></th>
                                <td>
                                    <input id="ai-post-summary-length" name="<?php echo esc_attr(self::OPTION_KEY); ?>[summary_length]" type="number" class="small-text" min="1" max="8" value="<?php echo esc_attr((string) $options['summary_length']); ?>">
                                    <p class="description"><?php echo esc_html__('要点模式表示最多多少条，段落模式表示最多多少句。', 'ai-post-summary'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="ai-post-summary-system-prompt"><?php echo esc_html__('系统提示词', 'ai-post-summary'); ?></label></th>
                                <td>
                                    <textarea id="ai-post-summary-system-prompt" name="<?php echo esc_attr(self::OPTION_KEY); ?>[system_prompt]" rows="5" class="large-text code"><?php echo esc_textarea($options['system_prompt']); ?></textarea>
                                    <p class="description"><?php echo esc_html__('用于定义模型角色与硬约束。留空建议恢复默认。', 'ai-post-summary'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="ai-post-summary-user-template"><?php echo esc_html__('摘要模板', 'ai-post-summary'); ?></label></th>
                                <td>
                                    <textarea id="ai-post-summary-user-template" name="<?php echo esc_attr(self::OPTION_KEY); ?>[user_prompt_template]" rows="8" class="large-text code"><?php echo esc_textarea($options['user_prompt_template']); ?></textarea>
                                    <p class="description"><?php echo esc_html__('可用变量：{title}、{style_instruction}、{content}。', 'ai-post-summary'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php echo esc_html__('自动生成', 'ai-post-summary'); ?></th>
                                <td>
                                    <label>
                                        <input name="<?php echo esc_attr(self::OPTION_KEY); ?>[auto_generate]" type="checkbox" value="1" <?php checked($options['auto_generate'], 1); ?>>
                                        <?php echo esc_html__('文章发布或更新后自动排队生成摘要', 'ai-post-summary'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php echo esc_html__('正文自动插入', 'ai-post-summary'); ?></th>
                                <td>
                                    <label>
                                        <input name="<?php echo esc_attr(self::OPTION_KEY); ?>[auto_insert]" type="checkbox" value="1" <?php checked($options['auto_insert'], 1); ?>>
                                        <?php echo esc_html__('自动插入到文章正文顶部', 'ai-post-summary'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php echo esc_html__('显示标题', 'ai-post-summary'); ?></th>
                                <td>
                                    <label>
                                        <input name="<?php echo esc_attr(self::OPTION_KEY); ?>[show_title]" type="checkbox" value="1" <?php checked($options['show_title'], 1); ?>>
                                        <?php echo esc_html__('显示“AI 摘要”标题', 'ai-post-summary'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php echo esc_html__('自动补全标签/关键词', 'ai-post-summary'); ?></th>
                                <td>
                                    <label>
                                        <input name="<?php echo esc_attr(self::OPTION_KEY); ?>[auto_generate_terms]" type="checkbox" value="1" <?php checked($options['auto_generate_terms'], 1); ?>>
                                        <?php echo esc_html__('文章发布或更新时，如果标签或关键词为空，则自动排队补全', 'ai-post-summary'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="ai-post-summary-tag-limit"><?php echo esc_html__('标签上限', 'ai-post-summary'); ?></label></th>
                                <td>
                                    <input id="ai-post-summary-tag-limit" name="<?php echo esc_attr(self::OPTION_KEY); ?>[tag_limit]" type="number" class="small-text" min="1" max="12" value="<?php echo esc_attr((string) $options['tag_limit']); ?>">
                                    <p class="description"><?php echo esc_html__('AI 每次生成的文章标签最多多少个。', 'ai-post-summary'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="ai-post-summary-keyword-limit"><?php echo esc_html__('关键词上限', 'ai-post-summary'); ?></label></th>
                                <td>
                                    <input id="ai-post-summary-keyword-limit" name="<?php echo esc_attr(self::OPTION_KEY); ?>[keyword_limit]" type="number" class="small-text" min="1" max="20" value="<?php echo esc_attr((string) $options['keyword_limit']); ?>">
                                    <p class="description"><?php echo esc_html__('AI 每次生成的 SEO 关键词最多多少个，会写入 zibll 的 keywords 字段。', 'ai-post-summary'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php echo esc_html__('标签彩色美化', 'ai-post-summary'); ?></th>
                                <td>
                                    <label>
                                        <input name="<?php echo esc_attr(self::OPTION_KEY); ?>[beautify_tags]" type="checkbox" value="1" <?php checked($options['beautify_tags'], 1); ?>>
                                        <?php echo esc_html__('为文章页标签区域启用多色胶囊样式', 'ai-post-summary'); ?>
                                    </label>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                        <?php submit_button(); ?>
                    </form>
                    <p><code>[ai_post_summary]</code> <?php echo esc_html__('可用于手动插入到指定位置。', 'ai-post-summary'); ?></p>
                    <?php
                }
                ?>
            </div>
            <?php
        }

        /**
         * @return string
         */
        private function get_current_tab()
        {
            $tab = isset($_GET['tab']) ? sanitize_text_field(wp_unslash($_GET['tab'])) : 'settings';
            return in_array($tab, array('settings', 'summaries', 'terms', 'tools', 'logs'), true) ? $tab : 'settings';
        }

        /**
         * @param string $tab
         * @return string
         */
        private function get_admin_page_url($tab = 'settings')
        {
            $args = array('page' => 'ai-post-summary');
            if ('settings' !== $tab) {
                $args['tab'] = $tab;
            }

            return add_query_arg($args, admin_url('options-general.php'));
        }

        /**
         * @param string $current_tab
         * @return void
         */
        private function render_tab_nav($current_tab)
        {
            $tabs = array(
                'settings' => __('设置', 'ai-post-summary'),
                'summaries' => __('摘要管理', 'ai-post-summary'),
                'terms' => __('标签/关键词', 'ai-post-summary'),
                'tools' => __('任务', 'ai-post-summary'),
                'logs' => __('日志', 'ai-post-summary'),
            );

            echo '<nav class="nav-tab-wrapper" style="margin:18px 0 16px;">';
            foreach ($tabs as $tab => $label) {
                $class = ('nav-tab' . ($current_tab === $tab ? ' nav-tab-active' : ''));
                echo '<a class="' . esc_attr($class) . '" href="' . esc_url($this->get_admin_page_url($tab)) . '">' . esc_html($label) . '</a>';
            }
            echo '</nav>';
        }

        public function render_tools_page($embedded = false)
        {
            if (!current_user_can('manage_options')) {
                return;
            }

            $counts = $this->get_summary_counts();
            $terms_counts = $this->get_terms_counts();
            $queue_url = admin_url('admin-post.php?action=ai_post_summary_bulk_queue');
            if (!$embedded) {
                echo '<div class="wrap">';
            }
            ?>
            <div>
                <h1><?php echo esc_html__('AI 摘要任务', 'ai-post-summary'); ?></h1>
                <p><?php echo esc_html__('这里可以为历史文章批量补摘要，或者重试失败任务。每次操作只会排队一批，避免瞬时请求过多。', 'ai-post-summary'); ?></p>

                <table class="widefat striped" style="max-width:720px;margin:16px 0;">
                    <thead>
                    <tr>
                        <th><?php echo esc_html__('状态', 'ai-post-summary'); ?></th>
                        <th><?php echo esc_html__('数量', 'ai-post-summary'); ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td><?php echo esc_html__('已生成', 'ai-post-summary'); ?></td>
                        <td><?php echo esc_html((string) $counts['ready']); ?></td>
                    </tr>
                    <tr>
                        <td><?php echo esc_html__('等待生成', 'ai-post-summary'); ?></td>
                        <td><?php echo esc_html((string) $counts['queued']); ?></td>
                    </tr>
                    <tr>
                        <td><?php echo esc_html__('生成失败', 'ai-post-summary'); ?></td>
                        <td><?php echo esc_html((string) $counts['failed']); ?></td>
                    </tr>
                    <tr>
                        <td><?php echo esc_html__('缺少摘要', 'ai-post-summary'); ?></td>
                        <td><?php echo esc_html((string) $counts['missing']); ?></td>
                    </tr>
                    </tbody>
                </table>

                <div style="display:flex;gap:24px;flex-wrap:wrap;">
                    <form method="post" action="<?php echo esc_url($queue_url); ?>" style="min-width:320px;max-width:420px;padding:16px;border:1px solid #dcdcde;background:#fff;">
                        <?php wp_nonce_field('ai_post_summary_bulk_missing'); ?>
                        <input type="hidden" name="mode" value="missing">
                        <h2><?php echo esc_html__('批量补齐历史文章', 'ai-post-summary'); ?></h2>
                        <p><?php echo esc_html__('为还没有 AI 摘要的历史文章批量排队。', 'ai-post-summary'); ?></p>
                        <p>
                            <label for="ai-summary-missing-limit"><?php echo esc_html__('本次排队数量', 'ai-post-summary'); ?></label><br>
                            <input id="ai-summary-missing-limit" type="number" min="1" max="100" class="small-text" name="limit" value="20">
                        </p>
                        <?php submit_button(__('开始排队', 'ai-post-summary'), 'primary', 'submit', false); ?>
                    </form>

                    <form method="post" action="<?php echo esc_url($queue_url); ?>" style="min-width:320px;max-width:420px;padding:16px;border:1px solid #dcdcde;background:#fff;">
                        <?php wp_nonce_field('ai_post_summary_bulk_failed'); ?>
                        <input type="hidden" name="mode" value="failed">
                        <h2><?php echo esc_html__('重试失败任务', 'ai-post-summary'); ?></h2>
                        <p><?php echo esc_html__('把失败状态的文章重新排队生成。', 'ai-post-summary'); ?></p>
                        <p>
                            <label for="ai-summary-failed-limit"><?php echo esc_html__('本次排队数量', 'ai-post-summary'); ?></label><br>
                            <input id="ai-summary-failed-limit" type="number" min="1" max="100" class="small-text" name="limit" value="20">
                        </p>
                        <?php submit_button(__('重试失败摘要', 'ai-post-summary'), 'secondary', 'submit', false); ?>
                    </form>
                </div>

                <hr style="margin:28px 0;">

                <h2><?php echo esc_html__('标签 / 关键词任务', 'ai-post-summary'); ?></h2>
                <p><?php echo esc_html__('这里可以为历史文章补齐标签与关键词，或者重试失败的标签/关键词任务。', 'ai-post-summary'); ?></p>

                <table class="widefat striped" style="max-width:720px;margin:16px 0;">
                    <thead>
                    <tr>
                        <th><?php echo esc_html__('状态', 'ai-post-summary'); ?></th>
                        <th><?php echo esc_html__('数量', 'ai-post-summary'); ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td><?php echo esc_html__('已生成', 'ai-post-summary'); ?></td>
                        <td><?php echo esc_html((string) $terms_counts['ready']); ?></td>
                    </tr>
                    <tr>
                        <td><?php echo esc_html__('等待生成', 'ai-post-summary'); ?></td>
                        <td><?php echo esc_html((string) $terms_counts['queued']); ?></td>
                    </tr>
                    <tr>
                        <td><?php echo esc_html__('生成失败', 'ai-post-summary'); ?></td>
                        <td><?php echo esc_html((string) $terms_counts['failed']); ?></td>
                    </tr>
                    <tr>
                        <td><?php echo esc_html__('缺少标签或关键词', 'ai-post-summary'); ?></td>
                        <td><?php echo esc_html((string) $terms_counts['missing']); ?></td>
                    </tr>
                    </tbody>
                </table>

                <div style="display:flex;gap:24px;flex-wrap:wrap;">
                    <form method="post" action="<?php echo esc_url($queue_url); ?>" style="min-width:320px;max-width:420px;padding:16px;border:1px solid #dcdcde;background:#fff;">
                        <?php wp_nonce_field('ai_post_summary_bulk_terms_missing'); ?>
                        <input type="hidden" name="mode" value="terms_missing">
                        <h2><?php echo esc_html__('批量补齐标签/关键词', 'ai-post-summary'); ?></h2>
                        <p><?php echo esc_html__('为缺少标签或关键词的历史文章批量排队。', 'ai-post-summary'); ?></p>
                        <p>
                            <label for="ai-summary-terms-missing-limit"><?php echo esc_html__('本次排队数量', 'ai-post-summary'); ?></label><br>
                            <input id="ai-summary-terms-missing-limit" type="number" min="1" max="100" class="small-text" name="limit" value="20">
                        </p>
                        <?php submit_button(__('开始排队', 'ai-post-summary'), 'primary', 'submit', false); ?>
                    </form>

                    <form method="post" action="<?php echo esc_url($queue_url); ?>" style="min-width:320px;max-width:420px;padding:16px;border:1px solid #dcdcde;background:#fff;">
                        <?php wp_nonce_field('ai_post_summary_bulk_terms_failed'); ?>
                        <input type="hidden" name="mode" value="terms_failed">
                        <h2><?php echo esc_html__('重试失败标签/关键词任务', 'ai-post-summary'); ?></h2>
                        <p><?php echo esc_html__('把标签/关键词失败状态的文章重新排队生成。', 'ai-post-summary'); ?></p>
                        <p>
                            <label for="ai-summary-terms-failed-limit"><?php echo esc_html__('本次排队数量', 'ai-post-summary'); ?></label><br>
                            <input id="ai-summary-terms-failed-limit" type="number" min="1" max="100" class="small-text" name="limit" value="20">
                        </p>
                        <?php submit_button(__('重试失败任务', 'ai-post-summary'), 'secondary', 'submit', false); ?>
                    </form>
                </div>
            </div>
            <?php
            if (!$embedded) {
                echo '</div>';
            }
        }

        public function render_summaries_page($embedded = false)
        {
            if (!current_user_can('manage_options')) {
                return;
            }

            $status_filter = isset($_GET['summary_status']) ? sanitize_text_field(wp_unslash($_GET['summary_status'])) : 'ready';
            $allowed = array('all', 'ready', 'failed', 'queued', 'generating');
            if (!in_array($status_filter, $allowed, true)) {
                $status_filter = 'ready';
            }

            $paged = isset($_GET['summary_paged']) ? max(1, absint($_GET['summary_paged'])) : 1;
            $per_page = 10;
            $query = $this->get_summary_management_query($status_filter, $paged, $per_page);
            $items = $query->posts;

            if (!$embedded) {
                echo '<div class="wrap">';
            }
            ?>
            <div>
                <h1><?php echo esc_html__('摘要管理', 'ai-post-summary'); ?></h1>
                <p><?php echo esc_html__('这里可以查看已生成摘要、手动编辑摘要内容，或将现有摘要重新排队生成。', 'ai-post-summary'); ?></p>

                <form method="get" action="" style="margin:12px 0 18px;">
                    <input type="hidden" name="page" value="ai-post-summary">
                    <input type="hidden" name="tab" value="summaries">
                    <label for="ai-post-summary-status-filter"><?php echo esc_html__('状态筛选：', 'ai-post-summary'); ?></label>
                    <select id="ai-post-summary-status-filter" name="summary_status">
                        <?php foreach ($allowed as $item) : ?>
                            <option value="<?php echo esc_attr($item); ?>" <?php selected($status_filter, $item); ?>><?php echo esc_html('all' === $item ? __('全部', 'ai-post-summary') : strtoupper($item)); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php submit_button(__('筛选', 'ai-post-summary'), 'secondary', '', false); ?>
                </form>

                <?php if (!$items) : ?>
                    <p><?php echo esc_html__('当前没有可管理的摘要。', 'ai-post-summary'); ?></p>
                <?php else : ?>
                    <style>
                        .ai-summary-manager-list { display:grid; gap:12px; }
                        .ai-summary-manager-item { background:#fff; border:1px solid #dcdcde; padding:14px 16px; }
                        .ai-summary-manager-meta { margin:4px 0 0; color:#666; font-size:13px; }
                        .ai-summary-manager-preview { margin:10px 0 0; color:#2c3338; line-height:1.6; }
                        .ai-summary-manager-actions { display:flex; gap:8px; flex-wrap:wrap; margin-top:12px; }
                        .ai-summary-manager-editor { margin-top:10px; }
                        .ai-summary-manager-editor summary { cursor:pointer; user-select:none; list-style:none; }
                        .ai-summary-manager-editor summary::-webkit-details-marker { display:none; }
                        .ai-summary-manager-editor[open] summary { margin-bottom:10px; }
                        .ai-summary-manager-editor-box { padding:12px; border:1px solid #dcdcde; background:#f8f9fa; }
                        .ai-summary-manager-editor-box textarea { width:100%; min-height:160px; font-family:monospace; }
                    </style>
                    <div style="display:grid;gap:16px;">
                        <?php foreach ($items as $post) : ?>
                            <?php
                            $summary = (string) get_post_meta($post->ID, self::META_SUMMARY, true);
                            $status = (string) get_post_meta($post->ID, self::META_STATUS, true);
                            $updated_at = (string) get_post_meta($post->ID, self::META_UPDATED_AT, true);
                            $preview = wp_trim_words(preg_replace('/\s+/u', ' ', wp_strip_all_tags($summary)), 40, '...');
                            $save_url = admin_url('admin-post.php?action=ai_post_summary_save_summary');
                            $retry_url = wp_nonce_url(
                                add_query_arg(
                                    array(
                                        'action' => 'ai_post_summary_retry_summary',
                                        'post_id' => $post->ID,
                                    ),
                                    admin_url('admin-post.php')
                                ),
                                'ai_post_summary_retry_' . $post->ID
                            );
                            ?>
                            <div class="ai-summary-manager-item">
                                <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                                    <div>
                                        <h2 style="margin:0 0 6px;"><a href="<?php echo esc_url(get_edit_post_link($post->ID)); ?>"><?php echo esc_html(get_the_title($post)); ?></a></h2>
                                        <p class="ai-summary-manager-meta"><?php echo esc_html(sprintf(__('状态：%1$s；更新时间：%2$s', 'ai-post-summary'), $status ? $status : 'empty', $updated_at ? $updated_at : '-')); ?></p>
                                    </div>
                                    <div class="ai-summary-manager-actions">
                                        <a class="button" href="<?php echo esc_url(get_permalink($post)); ?>" target="_blank"><?php echo esc_html__('查看文章', 'ai-post-summary'); ?></a>
                                        <a class="button button-secondary" href="<?php echo esc_url($retry_url); ?>"><?php echo esc_html__('重新生成', 'ai-post-summary'); ?></a>
                                    </div>
                                </div>
                                <div class="ai-summary-manager-preview"><?php echo esc_html($preview); ?></div>
                                <details class="ai-summary-manager-editor">
                                    <summary class="button"><?php echo esc_html__('编辑摘要', 'ai-post-summary'); ?></summary>
                                    <div class="ai-summary-manager-editor-box">
                                        <form method="post" action="<?php echo esc_url($save_url); ?>">
                                            <?php wp_nonce_field('ai_post_summary_save_' . $post->ID); ?>
                                            <input type="hidden" name="post_id" value="<?php echo esc_attr((string) $post->ID); ?>">
                                            <textarea name="summary"><?php echo esc_textarea($summary); ?></textarea>
                                            <p style="margin:10px 0 0;">
                                                <?php submit_button(__('保存摘要', 'ai-post-summary'), 'primary', 'submit', false); ?>
                                            </p>
                                        </form>
                                    </div>
                                </details>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php
                    echo paginate_links(
                        array(
                            'base' => add_query_arg(
                                array(
                                    'page' => 'ai-post-summary',
                                    'tab' => 'summaries',
                                    'summary_status' => $status_filter,
                                    'summary_paged' => '%#%',
                                ),
                                admin_url('options-general.php')
                            ),
                            'format' => '',
                            'current' => $paged,
                            'total' => max(1, (int) $query->max_num_pages),
                        )
                    );
                    ?>
                <?php endif; ?>
            </div>
            <?php
            if (!$embedded) {
                echo '</div>';
            }
        }

        public function render_terms_page($embedded = false)
        {
            if (!current_user_can('manage_options')) {
                return;
            }

            $status_filter = isset($_GET['terms_status']) ? sanitize_text_field(wp_unslash($_GET['terms_status'])) : 'all';
            $allowed = array('all', 'ready', 'failed', 'queued', 'generating');
            if (!in_array($status_filter, $allowed, true)) {
                $status_filter = 'all';
            }

            $paged = isset($_GET['terms_paged']) ? max(1, absint($_GET['terms_paged'])) : 1;
            $per_page = 10;
            $query = $this->get_terms_management_query($status_filter, $paged, $per_page);
            $items = $query->posts;

            if (!$embedded) {
                echo '<div class="wrap">';
            }
            ?>
            <div>
                <h1><?php echo esc_html__('标签 / 关键词管理', 'ai-post-summary'); ?></h1>
                <p><?php echo esc_html__('这里可以查看当前文章的标签和关键词，手动编辑，或重新交给 AI 生成。关键词会写入 zibll 的 keywords 字段。', 'ai-post-summary'); ?></p>

                <form method="get" action="" style="margin:12px 0 18px;">
                    <input type="hidden" name="page" value="ai-post-summary">
                    <input type="hidden" name="tab" value="terms">
                    <label for="ai-post-summary-terms-filter"><?php echo esc_html__('状态筛选：', 'ai-post-summary'); ?></label>
                    <select id="ai-post-summary-terms-filter" name="terms_status">
                        <?php foreach ($allowed as $item) : ?>
                            <option value="<?php echo esc_attr($item); ?>" <?php selected($status_filter, $item); ?>><?php echo esc_html('all' === $item ? __('全部', 'ai-post-summary') : strtoupper($item)); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php submit_button(__('筛选', 'ai-post-summary'), 'secondary', '', false); ?>
                </form>

                <?php if (!$items) : ?>
                    <p><?php echo esc_html__('当前没有可管理的文章。', 'ai-post-summary'); ?></p>
                <?php else : ?>
                    <style>
                        .ai-terms-manager-item { background:#fff; border:1px solid #dcdcde; padding:14px 16px; margin-bottom:12px; }
                        .ai-terms-manager-meta { margin:4px 0 0; color:#666; font-size:13px; }
                        .ai-terms-manager-grid { display:grid; gap:8px; margin-top:12px; }
                        .ai-terms-manager-line { color:#2c3338; line-height:1.6; }
                        .ai-terms-manager-label { display:inline-block; min-width:74px; color:#50575e; font-weight:600; }
                        .ai-terms-manager-actions { display:flex; gap:8px; flex-wrap:wrap; margin-top:12px; }
                        .ai-terms-manager-editor { margin-top:10px; }
                        .ai-terms-manager-editor summary { cursor:pointer; user-select:none; list-style:none; }
                        .ai-terms-manager-editor summary::-webkit-details-marker { display:none; }
                        .ai-terms-manager-editor[open] summary { margin-bottom:10px; }
                        .ai-terms-manager-editor-box { padding:12px; border:1px solid #dcdcde; background:#f8f9fa; }
                        .ai-terms-manager-editor-box input,
                        .ai-terms-manager-editor-box textarea { width:100%; }
                    </style>
                    <?php foreach ($items as $post) : ?>
                        <?php
                        $keywords = $this->get_post_keywords($post->ID);
                        $tags = $this->get_post_tag_names($post->ID);
                        $status = (string) get_post_meta($post->ID, self::META_TERMS_STATUS, true);
                        $updated_at = (string) get_post_meta($post->ID, self::META_TERMS_UPDATED_AT, true);
                        $save_url = admin_url('admin-post.php?action=ai_post_summary_save_terms');
                        $retry_url = wp_nonce_url(
                            add_query_arg(
                                array(
                                    'action' => 'ai_post_summary_retry_terms',
                                    'post_id' => $post->ID,
                                ),
                                admin_url('admin-post.php')
                            ),
                            'ai_post_summary_retry_terms_' . $post->ID
                        );
                        ?>
                        <div class="ai-terms-manager-item">
                            <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                                <div>
                                    <h2 style="margin:0 0 6px;"><a href="<?php echo esc_url(get_edit_post_link($post->ID)); ?>"><?php echo esc_html(get_the_title($post)); ?></a></h2>
                                    <p class="ai-terms-manager-meta"><?php echo esc_html(sprintf(__('状态：%1$s；更新时间：%2$s', 'ai-post-summary'), $status ? $status : 'empty', $updated_at ? $updated_at : '-')); ?></p>
                                </div>
                                <div class="ai-terms-manager-actions">
                                    <a class="button" href="<?php echo esc_url(get_permalink($post)); ?>" target="_blank"><?php echo esc_html__('查看文章', 'ai-post-summary'); ?></a>
                                    <a class="button button-secondary" href="<?php echo esc_url($retry_url); ?>"><?php echo esc_html__('重新生成', 'ai-post-summary'); ?></a>
                                </div>
                            </div>
                            <div class="ai-terms-manager-grid">
                                <div class="ai-terms-manager-line"><span class="ai-terms-manager-label"><?php echo esc_html__('关键词', 'ai-post-summary'); ?></span><?php echo esc_html($keywords ? $keywords : '—'); ?></div>
                                <div class="ai-terms-manager-line"><span class="ai-terms-manager-label"><?php echo esc_html__('标签', 'ai-post-summary'); ?></span><?php echo esc_html($tags ? implode('、', $tags) : '—'); ?></div>
                            </div>
                            <details class="ai-terms-manager-editor">
                                <summary class="button"><?php echo esc_html__('编辑标签/关键词', 'ai-post-summary'); ?></summary>
                                <div class="ai-terms-manager-editor-box">
                                    <form method="post" action="<?php echo esc_url($save_url); ?>">
                                        <?php wp_nonce_field('ai_post_summary_save_terms_' . $post->ID); ?>
                                        <input type="hidden" name="post_id" value="<?php echo esc_attr((string) $post->ID); ?>">
                                        <p>
                                            <label for="ai-post-summary-keywords-<?php echo esc_attr((string) $post->ID); ?>"><?php echo esc_html__('关键词', 'ai-post-summary'); ?></label>
                                            <textarea id="ai-post-summary-keywords-<?php echo esc_attr((string) $post->ID); ?>" name="keywords" rows="3"><?php echo esc_textarea($keywords); ?></textarea>
                                            <span class="description"><?php echo esc_html__('使用逗号分隔，会写入文章的 keywords 字段。', 'ai-post-summary'); ?></span>
                                        </p>
                                        <p>
                                            <label for="ai-post-summary-tags-<?php echo esc_attr((string) $post->ID); ?>"><?php echo esc_html__('标签', 'ai-post-summary'); ?></label>
                                            <input id="ai-post-summary-tags-<?php echo esc_attr((string) $post->ID); ?>" type="text" name="tags" value="<?php echo esc_attr(implode(', ', $tags)); ?>">
                                            <span class="description"><?php echo esc_html__('使用逗号分隔，会同步到 WordPress 文章标签。', 'ai-post-summary'); ?></span>
                                        </p>
                                        <p style="margin:10px 0 0;">
                                            <?php submit_button(__('保存标签/关键词', 'ai-post-summary'), 'primary', 'submit', false); ?>
                                        </p>
                                    </form>
                                </div>
                            </details>
                        </div>
                    <?php endforeach; ?>

                    <?php
                    echo paginate_links(
                        array(
                            'base' => add_query_arg(
                                array(
                                    'page' => 'ai-post-summary',
                                    'tab' => 'terms',
                                    'terms_status' => $status_filter,
                                    'terms_paged' => '%#%',
                                ),
                                admin_url('options-general.php')
                            ),
                            'format' => '',
                            'current' => $paged,
                            'total' => max(1, (int) $query->max_num_pages),
                        )
                    );
                    ?>
                <?php endif; ?>
            </div>
            <?php
            if (!$embedded) {
                echo '</div>';
            }
        }

        public function render_logs_page($embedded = false)
        {
            if (!current_user_can('manage_options')) {
                return;
            }

            global $wpdb;

            $allowed_levels = $this->get_log_levels();
            $level = isset($_GET['level']) ? sanitize_text_field(wp_unslash($_GET['level'])) : '';
            if ($level && !in_array($level, $allowed_levels, true)) {
                $level = '';
            }

            $paged = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
            $per_page = 50;
            $offset = ($paged - 1) * $per_page;
            $table = $this->get_log_table_name();

            $where_sql = '';
            $where_args = array();
            if ($level) {
                $where_sql = 'WHERE level = %s';
                $where_args[] = $level;
            }

            if ($where_sql) {
                $count_sql = $wpdb->prepare("SELECT COUNT(*) FROM {$table} {$where_sql}", $where_args);
                $query_args = array_merge($where_args, array($per_page, $offset));
                $logs_sql = $wpdb->prepare(
                    "SELECT id, level, message, context, created_at FROM {$table} {$where_sql} ORDER BY id DESC LIMIT %d OFFSET %d",
                    $query_args
                );
            } else {
                $count_sql = "SELECT COUNT(*) FROM {$table}";
                $logs_sql = $wpdb->prepare(
                    "SELECT id, level, message, context, created_at FROM {$table} ORDER BY id DESC LIMIT %d OFFSET %d",
                    $per_page,
                    $offset
                );
            }

            $total = (int) $wpdb->get_var($count_sql);
            $logs = $wpdb->get_results($logs_sql);
            $base_url = $this->get_admin_page_url('logs');
            if ($level) {
                $base_url = add_query_arg(array('level' => $level), $base_url);
            }
            $clear_url = admin_url('admin-post.php?action=ai_post_summary_clear_logs');
            if (!$embedded) {
                echo '<div class="wrap">';
            }
            ?>
            <div>
                <h1><?php echo esc_html__('AI 摘要日志', 'ai-post-summary'); ?></h1>
                <p><?php echo esc_html__('日志会按级别持久化保存，便于排查生成失败、批量任务和接口调用问题。', 'ai-post-summary'); ?></p>

                <form method="get" action="">
                    <input type="hidden" name="page" value="ai-post-summary">
                    <input type="hidden" name="tab" value="logs">
                    <label for="ai-post-summary-log-level"><?php echo esc_html__('级别筛选：', 'ai-post-summary'); ?></label>
                    <select id="ai-post-summary-log-level" name="level">
                        <option value=""><?php echo esc_html__('全部', 'ai-post-summary'); ?></option>
                        <?php foreach ($allowed_levels as $item) : ?>
                            <option value="<?php echo esc_attr($item); ?>" <?php selected($level, $item); ?>><?php echo esc_html(strtoupper($item)); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php submit_button(__('筛选', 'ai-post-summary'), 'secondary', '', false); ?>
                </form>

                <form method="post" action="<?php echo esc_url($clear_url); ?>" style="margin:12px 0 18px;">
                    <?php wp_nonce_field('ai_post_summary_clear_logs'); ?>
                    <input type="hidden" name="level" value="<?php echo esc_attr($level); ?>">
                    <?php submit_button($level ? __('清空当前级别日志', 'ai-post-summary') : __('清空全部日志', 'ai-post-summary'), 'delete', 'submit', false, array('onclick' => "return confirm('确认清空日志？');")); ?>
                </form>

                <table class="widefat striped">
                    <thead>
                    <tr>
                        <th style="width:80px;"><?php echo esc_html__('级别', 'ai-post-summary'); ?></th>
                        <th style="width:170px;"><?php echo esc_html__('时间', 'ai-post-summary'); ?></th>
                        <th><?php echo esc_html__('消息', 'ai-post-summary'); ?></th>
                        <th style="width:32%;"><?php echo esc_html__('上下文', 'ai-post-summary'); ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (!$logs) : ?>
                        <tr>
                            <td colspan="4"><?php echo esc_html__('暂无日志。', 'ai-post-summary'); ?></td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($logs as $log) : ?>
                            <tr>
                                <td><strong class="ai-post-summary-log-level ai-post-summary-log-level-<?php echo esc_attr($log->level); ?>"><?php echo esc_html(strtoupper($log->level)); ?></strong></td>
                                <td><?php echo esc_html($log->created_at); ?></td>
                                <td><?php echo esc_html($log->message); ?></td>
                                <td><code style="white-space:pre-wrap;word-break:break-word;"><?php echo esc_html($log->context); ?></code></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>

                <?php
                echo paginate_links(
                    array(
                        'base' => add_query_arg('paged', '%#%', $base_url),
                        'format' => '',
                        'current' => $paged,
                        'total' => max(1, (int) ceil($total / $per_page)),
                    )
                );
                ?>

                <style>
                    .ai-post-summary-log-level { display:inline-block; min-width:56px; text-align:center; padding:4px 8px; border-radius:999px; }
                    .ai-post-summary-log-level-debug { background:#eef4ff; color:#2f5aa8; }
                    .ai-post-summary-log-level-info { background:#edf8ef; color:#18794e; }
                    .ai-post-summary-log-level-warning { background:#fff6e6; color:#a15c00; }
                    .ai-post-summary-log-level-error { background:#fdecec; color:#b42318; }
                </style>
            </div>
            <?php
            if (!$embedded) {
                echo '</div>';
            }
        }

        public function register_widget()
        {
            register_widget('AI_Post_Summary_Widget');
        }

        /**
         * @param int $post_id
         * @param WP_Post $post
         * @param bool $update
         * @return void
         */
        public function schedule_summary_generation($post_id, $post, $update)
        {
            $options = $this->get_options();

            if (empty($options['auto_generate'])) {
                return;
            }

            if (wp_is_post_revision($post_id) || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)) {
                return;
            }

            if ('publish' !== $post->post_status) {
                return;
            }

            $hash = $this->build_source_hash($post);
            $stored_hash = (string) get_post_meta($post_id, self::META_HASH, true);
            $summary = (string) get_post_meta($post_id, self::META_SUMMARY, true);

            if ($update && $summary && $stored_hash === $hash) {
                $this->log(self::LOG_LEVEL_DEBUG, '文章内容未变化，跳过摘要重建', array('post_id' => $post_id));
                return;
            }

            update_post_meta($post_id, self::META_STATUS, 'queued');
            $this->queue_generation($post_id, false);
            $this->log(self::LOG_LEVEL_INFO, '文章摘要已加入生成队列', array('post_id' => $post_id, 'update' => (bool) $update));
        }

        /**
         * @param int $post_id
         * @param WP_Post $post
         * @param bool $update
         * @return void
         */
        public function schedule_terms_generation($post_id, $post, $update)
        {
            $options = $this->get_options();

            if (empty($options['auto_generate_terms'])) {
                return;
            }

            if (wp_is_post_revision($post_id) || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)) {
                return;
            }

            if ('publish' !== $post->post_status) {
                return;
            }

            if (!$this->is_terms_missing($post_id)) {
                $this->log(self::LOG_LEVEL_DEBUG, '标签和关键词都已存在，跳过自动补全', array('post_id' => $post_id, 'update' => (bool) $update));
                return;
            }

            update_post_meta($post_id, self::META_TERMS_STATUS, 'queued');
            $this->queue_terms_generation($post_id, false);
            $this->log(self::LOG_LEVEL_INFO, '文章标签/关键词已加入生成队列', array('post_id' => $post_id, 'update' => (bool) $update));
        }

        /**
         * @param int $post_id
         * @param bool $force
         * @return void
         */
        public function queue_generation($post_id, $force)
        {
            $timestamp = wp_next_scheduled(self::CRON_HOOK, array($post_id, (int) $force));
            if ($timestamp) {
                wp_unschedule_event($timestamp, self::CRON_HOOK, array($post_id, (int) $force));
            }

            wp_schedule_single_event(time() + 10, self::CRON_HOOK, array($post_id, (int) $force));
            $this->log(self::LOG_LEVEL_DEBUG, '已调度摘要生成任务', array('post_id' => $post_id, 'force' => (bool) $force));
        }

        /**
         * @param int $post_id
         * @param bool $force
         * @return void
         */
        public function queue_terms_generation($post_id, $force)
        {
            $timestamp = wp_next_scheduled(self::CRON_TERMS_HOOK, array($post_id, (int) $force));
            if ($timestamp) {
                wp_unschedule_event($timestamp, self::CRON_TERMS_HOOK, array($post_id, (int) $force));
            }

            wp_schedule_single_event(time() + 10, self::CRON_TERMS_HOOK, array($post_id, (int) $force));
            $this->log(self::LOG_LEVEL_DEBUG, '已调度标签/关键词生成任务', array('post_id' => $post_id, 'force' => (bool) $force));
        }

        /**
         * @param int $post_id
         * @param int $force
         * @return void
         */
        public function handle_cron_generation($post_id, $force)
        {
            $this->log(self::LOG_LEVEL_INFO, '开始执行摘要生成任务', array('post_id' => $post_id, 'force' => (bool) $force));
            $this->generate_summary($post_id, !empty($force));
        }

        /**
         * @param int $post_id
         * @param int $force
         * @return void
         */
        public function handle_cron_terms_generation($post_id, $force)
        {
            $this->log(self::LOG_LEVEL_INFO, '开始执行标签/关键词生成任务', array('post_id' => $post_id, 'force' => (bool) $force));
            $this->generate_terms($post_id, !empty($force));
        }

        public function register_meta_box()
        {
            add_meta_box(
                'ai-post-summary-box',
                __('AI 摘要', 'ai-post-summary'),
                array($this, 'render_meta_box'),
                'post',
                'side',
                'default'
            );
        }

        /**
         * @param WP_Post $post
         * @return void
         */
        public function render_meta_box($post)
        {
            $status = (string) get_post_meta($post->ID, self::META_STATUS, true);
            $updated_at = (string) get_post_meta($post->ID, self::META_UPDATED_AT, true);
            $summary = (string) get_post_meta($post->ID, self::META_SUMMARY, true);
            $error = (string) get_post_meta($post->ID, self::META_ERROR, true);
            $terms_status = (string) get_post_meta($post->ID, self::META_TERMS_STATUS, true);
            $terms_updated_at = (string) get_post_meta($post->ID, self::META_TERMS_UPDATED_AT, true);
            $terms_error = (string) get_post_meta($post->ID, self::META_TERMS_ERROR, true);
            $keywords = $this->get_post_keywords($post->ID);
            $tags = $this->get_post_tag_names($post->ID);

            $url = wp_nonce_url(
                add_query_arg(
                    array(
                        'action' => 'ai_post_summary_generate',
                        'post_id' => $post->ID,
                    ),
                    admin_url('admin-post.php')
                ),
                self::NONCE_ACTION . '_' . $post->ID
            );
            $terms_url = wp_nonce_url(
                add_query_arg(
                    array(
                        'action' => 'ai_post_summary_generate_terms',
                        'post_id' => $post->ID,
                    ),
                    admin_url('admin-post.php')
                ),
                self::NONCE_TERMS_ACTION . '_' . $post->ID
            );
            ?>
            <p><strong><?php echo esc_html__('状态：', 'ai-post-summary'); ?></strong><?php echo esc_html($status ? $status : 'empty'); ?></p>
            <?php if ($updated_at) : ?>
                <p><strong><?php echo esc_html__('更新时间：', 'ai-post-summary'); ?></strong><?php echo esc_html($updated_at); ?></p>
            <?php endif; ?>
            <?php if ($error) : ?>
                <p style="color:#b32d2e;"><?php echo esc_html($error); ?></p>
            <?php endif; ?>
            <p>
                <a class="button button-primary" href="<?php echo esc_url($url); ?>"><?php echo esc_html($summary ? __('重新生成', 'ai-post-summary') : __('立即生成', 'ai-post-summary')); ?></a>
            </p>
            <?php if ($summary) : ?>
                <div style="max-height:140px;overflow:auto;border:1px solid #dcdcde;padding:8px;background:#fff;">
                    <?php echo wp_kses_post(nl2br(esc_html($summary))); ?>
                </div>
            <?php endif; ?>
            <hr>
            <p><strong><?php echo esc_html__('标签/关键词：', 'ai-post-summary'); ?></strong><?php echo esc_html($terms_status ? $terms_status : 'empty'); ?></p>
            <?php if ($terms_updated_at) : ?>
                <p><strong><?php echo esc_html__('更新时间：', 'ai-post-summary'); ?></strong><?php echo esc_html($terms_updated_at); ?></p>
            <?php endif; ?>
            <?php if ($terms_error) : ?>
                <p style="color:#b32d2e;"><?php echo esc_html($terms_error); ?></p>
            <?php endif; ?>
            <p>
                <a class="button" href="<?php echo esc_url($terms_url); ?>"><?php echo esc_html(($keywords || $tags) ? __('重新生成标签/关键词', 'ai-post-summary') : __('生成标签/关键词', 'ai-post-summary')); ?></a>
            </p>
            <div style="max-height:120px;overflow:auto;border:1px solid #dcdcde;padding:8px;background:#fff;">
                <p style="margin:0 0 6px;"><strong><?php echo esc_html__('关键词：', 'ai-post-summary'); ?></strong><?php echo esc_html($keywords ? $keywords : '—'); ?></p>
                <p style="margin:0;"><strong><?php echo esc_html__('标签：', 'ai-post-summary'); ?></strong><?php echo esc_html($tags ? implode('、', $tags) : '—'); ?></p>
            </div>
            <?php
        }

        public function handle_manual_generation()
        {
            $post_id = isset($_GET['post_id']) ? absint($_GET['post_id']) : 0;

            if (!$post_id || !current_user_can('edit_post', $post_id)) {
                wp_die(esc_html__('无权限操作。', 'ai-post-summary'));
            }

            check_admin_referer(self::NONCE_ACTION . '_' . $post_id);

            $result = $this->generate_summary($post_id, true);
            $flag = $result ? 'success' : 'error';
            $this->log($result ? self::LOG_LEVEL_INFO : self::LOG_LEVEL_ERROR, '后台手动触发摘要生成', array('post_id' => $post_id, 'result' => $flag));

            wp_safe_redirect(
                add_query_arg(
                    array(
                        'post' => $post_id,
                        'action' => 'edit',
                        'ai_post_summary_notice' => $flag,
                    ),
                    admin_url('post.php')
                )
            );
            exit;
        }

        public function handle_manual_terms_generation()
        {
            $post_id = isset($_GET['post_id']) ? absint($_GET['post_id']) : 0;

            if (!$post_id || !current_user_can('edit_post', $post_id)) {
                wp_die(esc_html__('无权限操作。', 'ai-post-summary'));
            }

            check_admin_referer(self::NONCE_TERMS_ACTION . '_' . $post_id);

            $result = $this->generate_terms($post_id, true);
            $flag = $result ? 'terms_success' : 'terms_error';
            $this->log($result ? self::LOG_LEVEL_INFO : self::LOG_LEVEL_ERROR, '后台手动触发标签/关键词生成', array('post_id' => $post_id, 'result' => $flag));

            wp_safe_redirect(
                add_query_arg(
                    array(
                        'post' => $post_id,
                        'action' => 'edit',
                        'ai_post_summary_notice' => $flag,
                    ),
                    admin_url('post.php')
                )
            );
            exit;
        }

        public function handle_bulk_queue()
        {
            if (!current_user_can('manage_options')) {
                wp_die(esc_html__('无权限操作。', 'ai-post-summary'));
            }

            $mode = isset($_POST['mode']) ? sanitize_text_field(wp_unslash($_POST['mode'])) : '';
            $limit = isset($_POST['limit']) ? absint($_POST['limit']) : 20;
            $limit = min(100, max(1, $limit));

            if ('missing' === $mode) {
                check_admin_referer('ai_post_summary_bulk_missing');
            } elseif ('failed' === $mode) {
                check_admin_referer('ai_post_summary_bulk_failed');
            } elseif ('terms_missing' === $mode) {
                check_admin_referer('ai_post_summary_bulk_terms_missing');
            } elseif ('terms_failed' === $mode) {
                check_admin_referer('ai_post_summary_bulk_terms_failed');
            } else {
                $this->log(self::LOG_LEVEL_WARNING, '收到无效的批量摘要操作', array('mode' => $mode));
                wp_die(esc_html__('无效操作。', 'ai-post-summary'));
            }

            $ids = in_array($mode, array('terms_missing', 'terms_failed'), true)
                ? $this->get_post_ids_for_terms_batch($mode, $limit)
                : $this->get_post_ids_for_batch($mode, $limit);
            foreach ($ids as $post_id) {
                if (in_array($mode, array('terms_missing', 'terms_failed'), true)) {
                    update_post_meta($post_id, self::META_TERMS_STATUS, 'queued');
                    $this->queue_terms_generation($post_id, 'terms_failed' === $mode);
                } else {
                    update_post_meta($post_id, self::META_STATUS, 'queued');
                    $this->queue_generation($post_id, 'failed' === $mode);
                }
            }

            $this->log(
                self::LOG_LEVEL_INFO,
                in_array($mode, array('terms_missing', 'terms_failed'), true) ? '批量标签/关键词任务已排队' : '批量摘要任务已排队',
                array(
                    'mode' => $mode,
                    'limit' => $limit,
                    'queued_count' => count($ids),
                    'post_ids' => $ids,
                )
            );

            wp_safe_redirect(
                add_query_arg(
                    array(
                        'page' => 'ai-post-summary',
                        'tab' => 'tools',
                        'ai_post_summary_notice' => 'bulk_' . $mode,
                        'ai_post_summary_count' => count($ids),
                    ),
                    admin_url('options-general.php')
                )
            );
            exit;
        }

        public function handle_clear_logs()
        {
            if (!current_user_can('manage_options')) {
                wp_die(esc_html__('无权限操作。', 'ai-post-summary'));
            }

            check_admin_referer('ai_post_summary_clear_logs');

            global $wpdb;

            $level = isset($_POST['level']) ? sanitize_text_field(wp_unslash($_POST['level'])) : '';
            $table = $this->get_log_table_name();

            if ($level && in_array($level, $this->get_log_levels(), true)) {
                $wpdb->delete($table, array('level' => $level), array('%s'));
                $this->log(self::LOG_LEVEL_WARNING, '清空指定级别日志', array('level' => $level));
            } else {
                $wpdb->query("TRUNCATE TABLE {$table}");
            }

            wp_safe_redirect(
                add_query_arg(
                    array(
                        'page' => 'ai-post-summary',
                        'tab' => 'logs',
                        'ai_post_summary_notice' => 'logs_cleared',
                    ),
                    admin_url('options-general.php')
                )
            );
            exit;
        }

        public function handle_save_summary()
        {
            $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
            if (!$post_id || !current_user_can('edit_post', $post_id)) {
                wp_die(esc_html__('无权限操作。', 'ai-post-summary'));
            }

            check_admin_referer('ai_post_summary_save_' . $post_id);

            $summary = isset($_POST['summary']) ? wp_kses_post(wp_unslash($_POST['summary'])) : '';
            $summary = $this->normalize_summary_text($summary);

            update_post_meta($post_id, self::META_SUMMARY, $summary);
            update_post_meta($post_id, self::META_STATUS, 'ready');
            update_post_meta($post_id, self::META_UPDATED_AT, current_time('mysql'));
            delete_post_meta($post_id, self::META_ERROR);

            $this->log(self::LOG_LEVEL_INFO, '后台手动保存摘要', array('post_id' => $post_id));

            wp_safe_redirect(
                add_query_arg(
                    array(
                        'page' => 'ai-post-summary',
                        'tab' => 'summaries',
                        'ai_post_summary_notice' => 'summary_saved',
                    ),
                    admin_url('options-general.php')
                )
            );
            exit;
        }

        public function handle_save_terms()
        {
            $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
            if (!$post_id || !current_user_can('edit_post', $post_id)) {
                wp_die(esc_html__('无权限操作。', 'ai-post-summary'));
            }

            check_admin_referer('ai_post_summary_save_terms_' . $post_id);

            $options = $this->get_options();
            $keyword_limit = max(1, absint($options['keyword_limit']));
            $tag_limit = max(1, absint($options['tag_limit']));
            $keywords = isset($_POST['keywords']) ? $this->normalize_terms_list(wp_unslash($_POST['keywords']), $keyword_limit) : array();
            $tags = isset($_POST['tags']) ? $this->normalize_terms_list(wp_unslash($_POST['tags']), $tag_limit) : array();

            update_post_meta($post_id, 'keywords', implode(', ', $keywords));
            wp_set_post_tags($post_id, $tags, false);

            if ($keywords || $tags) {
                update_post_meta($post_id, self::META_TERMS_STATUS, 'ready');
                update_post_meta($post_id, self::META_TERMS_HASH, $this->build_source_hash(get_post($post_id)));
                update_post_meta($post_id, self::META_TERMS_UPDATED_AT, current_time('mysql'));
                delete_post_meta($post_id, self::META_TERMS_ERROR);
            } else {
                delete_post_meta($post_id, self::META_TERMS_STATUS);
                delete_post_meta($post_id, self::META_TERMS_HASH);
                delete_post_meta($post_id, self::META_TERMS_UPDATED_AT);
                delete_post_meta($post_id, self::META_TERMS_ERROR);
            }

            $this->log(self::LOG_LEVEL_INFO, '后台手动保存标签/关键词', array('post_id' => $post_id, 'keywords_count' => count($keywords), 'tags_count' => count($tags)));

            wp_safe_redirect(
                add_query_arg(
                    array(
                        'page' => 'ai-post-summary',
                        'tab' => 'terms',
                        'ai_post_summary_notice' => 'terms_saved',
                    ),
                    admin_url('options-general.php')
                )
            );
            exit;
        }

        public function handle_retry_summary()
        {
            $post_id = isset($_GET['post_id']) ? absint($_GET['post_id']) : 0;
            if (!$post_id || !current_user_can('edit_post', $post_id)) {
                wp_die(esc_html__('无权限操作。', 'ai-post-summary'));
            }

            check_admin_referer('ai_post_summary_retry_' . $post_id);

            update_post_meta($post_id, self::META_STATUS, 'queued');
            $this->queue_generation($post_id, true);
            $this->log(self::LOG_LEVEL_INFO, '从摘要管理页重新排队生成摘要', array('post_id' => $post_id));

            wp_safe_redirect(
                add_query_arg(
                    array(
                        'page' => 'ai-post-summary',
                        'tab' => 'summaries',
                        'ai_post_summary_notice' => 'summary_retried',
                    ),
                    admin_url('options-general.php')
                )
            );
            exit;
        }

        public function handle_retry_terms()
        {
            $post_id = isset($_GET['post_id']) ? absint($_GET['post_id']) : 0;
            if (!$post_id || !current_user_can('edit_post', $post_id)) {
                wp_die(esc_html__('无权限操作。', 'ai-post-summary'));
            }

            check_admin_referer('ai_post_summary_retry_terms_' . $post_id);

            update_post_meta($post_id, self::META_TERMS_STATUS, 'queued');
            $this->queue_terms_generation($post_id, true);
            $this->log(self::LOG_LEVEL_INFO, '从标签/关键词管理页重新排队生成', array('post_id' => $post_id));

            wp_safe_redirect(
                add_query_arg(
                    array(
                        'page' => 'ai-post-summary',
                        'tab' => 'terms',
                        'ai_post_summary_notice' => 'terms_retried',
                    ),
                    admin_url('options-general.php')
                )
            );
            exit;
        }

        public function render_admin_notice()
        {
            if (empty($_GET['ai_post_summary_notice'])) {
                return;
            }

            $flag = sanitize_text_field(wp_unslash($_GET['ai_post_summary_notice']));
            if ('success' === $flag) {
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('AI 摘要已生成。', 'ai-post-summary') . '</p></div>';
            } elseif ('terms_success' === $flag) {
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('标签和关键词已生成。', 'ai-post-summary') . '</p></div>';
            } elseif ('error' === $flag) {
                echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('AI 摘要生成失败，请检查接口配置。', 'ai-post-summary') . '</p></div>';
            } elseif ('terms_error' === $flag) {
                echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('标签和关键词生成失败，请检查接口配置或日志。', 'ai-post-summary') . '</p></div>';
            } elseif ('bulk_missing' === $flag || 'bulk_failed' === $flag) {
                $count = isset($_GET['ai_post_summary_count']) ? absint($_GET['ai_post_summary_count']) : 0;
                $label = ('bulk_failed' === $flag) ? __('失败任务重试', 'ai-post-summary') : __('历史文章补摘要', 'ai-post-summary');
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html(sprintf(__('%s已排队 %d 篇文章。', 'ai-post-summary'), $label, $count)) . '</p></div>';
            } elseif ('bulk_terms_missing' === $flag || 'bulk_terms_failed' === $flag) {
                $count = isset($_GET['ai_post_summary_count']) ? absint($_GET['ai_post_summary_count']) : 0;
                $label = ('bulk_terms_failed' === $flag) ? __('失败标签/关键词任务重试', 'ai-post-summary') : __('历史文章补标签/关键词', 'ai-post-summary');
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html(sprintf(__('%s已排队 %d 篇文章。', 'ai-post-summary'), $label, $count)) . '</p></div>';
            } elseif ('logs_cleared' === $flag) {
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('日志已清空。', 'ai-post-summary') . '</p></div>';
            } elseif ('summary_saved' === $flag) {
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('摘要已保存。', 'ai-post-summary') . '</p></div>';
            } elseif ('summary_retried' === $flag) {
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('摘要已重新排队生成。', 'ai-post-summary') . '</p></div>';
            } elseif ('terms_saved' === $flag) {
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('标签和关键词已保存。', 'ai-post-summary') . '</p></div>';
            } elseif ('terms_retried' === $flag) {
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('标签和关键词已重新排队生成。', 'ai-post-summary') . '</p></div>';
            }
        }
}
