<?php
if (!defined('ABSPATH')) {
    exit;
}

trait AI_Post_Summary_Frontend_Trait
{
        public function get_summary($post_id)
        {
            return (string) get_post_meta($post_id, self::META_SUMMARY, true);
        }

        /**
         * @param int $post_id
         * @param bool $schedule_when_missing
         * @return string
         */
        public function render_summary_card($post_id, $schedule_when_missing = true)
        {
            $post = get_post($post_id);
            if (!$post || 'post' !== $post->post_type) {
                return '';
            }

            $summary = $this->get_summary($post_id);
            $options = $this->get_options();
            $status = (string) get_post_meta($post_id, self::META_STATUS, true);

            if ('' === $summary) {
                if ($schedule_when_missing && !empty($options['auto_generate'])) {
                    update_post_meta($post_id, self::META_STATUS, 'queued');
                    $this->queue_generation($post_id, false);
                }

                return '';
            }

            $title = '';
            if (!empty($options['show_title'])) {
                $title = '<div class="box-body notop ai-post-summary__head">'
                    . '<div class="ai-post-summary__head-inner">'
                    . '<div class="ai-post-summary__headline">'
                    . '<div class="ai-post-summary__eyebrow">AI DIGEST</div>'
                    . '<div class="title-theme ai-post-summary__title">' . esc_html__('AI 摘要', 'ai-post-summary') . '</div>'
                    . '</div>'
                    . '<div class="ai-post-summary__mode">LIVE</div>'
                    . '</div>'
                    . '</div>';
            }

            $content = $this->format_summary_html($summary);

            return '<section class="ai-post-summary zib-widget theme-box main-bg radius8 main-shadow' . ('failed' === $status ? ' ai-post-summary--failed' : '') . '"><style>' . $this->get_inline_style() . '</style><div class="ai-post-summary__glow" aria-hidden="true"></div><div class="ai-post-summary__mesh" aria-hidden="true"></div>' . $title . '<div class="box-body ai-post-summary__body wp-posts-content">' . $content . '</div></section>';
        }

        /**
         * @param string $summary
         * @return string
         */
        private function format_summary_html($summary)
        {
            $lines = array_filter(array_map('trim', preg_split('/\n+/u', $summary)));
            $is_list = true;

            foreach ($lines as $line) {
                if (!preg_match('/^[-*•]\s*/u', $line)) {
                    $is_list = false;
                    break;
                }
            }

            if ($is_list && !empty($lines)) {
                $items = '';
                foreach ($lines as $line) {
                    $items .= '<li>' . esc_html(preg_replace('/^[-*•]\s*/u', '', $line)) . '</li>';
                }

                return '<ul class="ai-post-summary__list">' . $items . '</ul>';
            }

            $paragraph = preg_replace('/\s*\n+\s*/u', ' ', $summary);
            $paragraph = preg_replace('/\s{2,}/u', ' ', (string) $paragraph);

            return '<p class="ai-post-summary__paragraph">' . esc_html(trim((string) $paragraph)) . '</p>';
        }

        /**
         * @return string
         */
        private function get_inline_style()
        {
            return '.ai-post-summary{position:relative;overflow:hidden;max-width:100%;margin:0 0 18px;border:1px solid var(--main-shadow);isolation:isolate;background:linear-gradient(180deg,rgba(78,161,255,.035) 0%,rgba(255,255,255,0) 42%),var(--main-bg,#fff);animation:ai-summary-enter .55s cubic-bezier(.2,.8,.2,1)}'
                . '.ai-post-summary:before{content:"";position:absolute;inset:0 auto auto 0;width:100%;height:2px;background:linear-gradient(90deg,#17c964 0%,#4ea1ff 52%,rgba(78,161,255,0) 100%)}'
                . '.ai-post-summary:after{content:"";position:absolute;right:-48px;bottom:-92px;width:168px;height:168px;border-radius:50%;background:radial-gradient(circle,rgba(23,201,100,.08) 0%,rgba(23,201,100,0) 68%);pointer-events:none;animation:ai-summary-float-2 7.5s ease-in-out infinite}'
                . '.ai-post-summary:hover{transform:translateY(-2px);transition:transform .28s ease,box-shadow .28s ease;box-shadow:0 12px 28px var(--main-shadow)}'
                . '.ai-post-summary__glow{position:absolute;right:6px;top:-18px;width:96px;height:96px;border-radius:50%;background:radial-gradient(circle,rgba(78,161,255,.18) 0%,rgba(78,161,255,.07) 35%,rgba(78,161,255,0) 72%);pointer-events:none;animation:ai-summary-float 5.5s ease-in-out infinite}'
                . '.ai-post-summary__mesh{position:absolute;inset:0;background-image:linear-gradient(rgba(78,161,255,.05) 1px,transparent 1px),linear-gradient(90deg,rgba(78,161,255,.05) 1px,transparent 1px);background-size:18px 18px;mask-image:linear-gradient(180deg,rgba(0,0,0,.16),transparent 62%);pointer-events:none;opacity:.55}'
                . '.ai-post-summary__head{padding-bottom:0;position:relative;z-index:1;padding-top:14px;padding-left:16px;padding-right:16px}'
                . '.ai-post-summary__head-inner{display:flex;align-items:center;gap:10px;justify-content:space-between}'
                . '.ai-post-summary__headline{display:flex;flex-direction:column;gap:2px;min-width:0;flex:1;max-width:100%}'
                . '.ai-post-summary__eyebrow{font-size:10px;font-weight:700;letter-spacing:.16em;color:#4ea1ff;text-transform:uppercase;line-height:1.1}'
                . '.ai-post-summary__mode{display:inline-flex;align-items:center;justify-content:center;align-self:center;padding:5px 9px;border-radius:999px;border:1px solid rgba(78,161,255,.12);background:rgba(78,161,255,.06);font-size:10px;font-weight:700;letter-spacing:.16em;color:#4ea1ff}'
                . '.ai-post-summary__title{margin-bottom:0;letter-spacing:.01em;font-size:1.02em}'
                . '.ai-post-summary__body{padding:10px 16px 10px;font-size:14px;line-height:1.72;position:relative;z-index:1;background:linear-gradient(180deg,rgba(78,161,255,.03),rgba(78,161,255,.008));border-radius:12px;min-height:0 !important;height:auto;overflow:visible;white-space:normal;word-break:break-word;overflow-wrap:anywhere;max-width:100%;box-sizing:border-box}'
                . '.ai-post-summary__body:before{content:"";position:absolute;left:0;top:12px;bottom:12px;width:3px;border-radius:999px;background:linear-gradient(180deg,#4ea1ff,#17c964)}'
                . '.ai-post-summary__list{margin:0;padding:0;list-style:none}'
                . '.ai-post-summary .wp-posts-content{min-height:0 !important;white-space:normal;overflow:visible}'
                . '.ai-post-summary__body ul{margin:0;padding-left:0}'
                . '.ai-post-summary__body li{position:relative;margin:.42rem 0;padding-left:22px;animation:ai-summary-rise .45s ease both}'
                . '.ai-post-summary__body li:nth-child(2){animation-delay:.06s}'
                . '.ai-post-summary__body li:nth-child(3){animation-delay:.12s}'
                . '.ai-post-summary__body li:nth-child(4){animation-delay:.18s}'
                . '.ai-post-summary__body li:nth-child(5){animation-delay:.24s}'
                . '.ai-post-summary__body li:before{content:"";position:absolute;left:0;top:.72em;width:8px;height:8px;border-radius:50%;background:radial-gradient(circle,#4ea1ff 0%,#4ea1ff 42%,rgba(78,161,255,.18) 43%,rgba(78,161,255,0) 100%);transform:translateY(-50%);box-shadow:0 0 0 5px rgba(78,161,255,.05)}'
                . '.ai-post-summary__body p{margin:0;white-space:normal;word-break:break-word;overflow:visible;overflow-wrap:anywhere;max-width:100%}'
                . '.ai-post-summary__paragraph{position:relative;padding-left:4px;display:block;white-space:normal;word-break:break-word}'
                . '.ai-post-summary.wp-posts-content .title-theme,.ai-post-summary .title-theme{margin-bottom:0}'
                . '@keyframes ai-summary-enter{0%{opacity:0;transform:translateY(12px) scale(.985)}100%{opacity:1;transform:translateY(0) scale(1)}}'
                . '@keyframes ai-summary-rise{0%{opacity:0;transform:translateY(8px)}100%{opacity:1;transform:translateY(0)}}'
                . '@keyframes ai-summary-float{0%,100%{transform:translate3d(0,0,0)}50%{transform:translate3d(-10px,8px,0)}}'
                . '@keyframes ai-summary-float-2{0%,100%{transform:translate3d(0,0,0)}50%{transform:translate3d(-12px,-6px,0)}}'
                . '@media (max-width:640px){.ai-post-summary__head{padding-top:12px;padding-left:14px;padding-right:14px}.ai-post-summary__head-inner{gap:8px}.ai-post-summary__mode{display:none}.ai-post-summary__body{padding:8px 14px 8px;font-size:13px;line-height:1.68}}';
        }

        /**
         * @param array<string, mixed> $atts
         * @return string
         */
        public function render_summary_shortcode($atts)
        {
            $atts = shortcode_atts(
                array(
                    'post_id' => 0,
                ),
                $atts,
                'ai_post_summary'
            );

            $post_id = absint($atts['post_id']);
            if (!$post_id && get_the_ID()) {
                $post_id = get_the_ID();
            }

            if (!$post_id) {
                return '';
            }

            return $this->render_summary_card($post_id);
        }

        /**
         * @param string $content
         * @return string
         */
        public function maybe_prepend_to_content($content)
        {
            $options = $this->get_options();

            if (empty($options['auto_insert'])) {
                return $content;
            }

            if (!is_singular('post') || !in_the_loop() || !is_main_query()) {
                return $content;
            }

            $card = $this->render_summary_card(get_the_ID());
            if ('' === $card) {
                return $content;
            }

            return $card . $content;
        }

        /**
         * @return void
         */
        public function render_tag_beautify_style()
        {
            $options = $this->get_options();
            if (empty($options['beautify_tags'])) {
                return;
            }

            echo '<style id="ai-post-summary-tag-beautify">'
                . '.article-tags{display:flex;flex-wrap:wrap;gap:10px 8px;align-items:center}'
                . '.article-tags br{display:none}'
                . 'a[title="查看此标签更多文章"]{display:inline-flex;align-items:center;margin:0 8px 8px 0 !important;border:0 !important;border-radius:999px;padding:6px 12px;line-height:1.1;font-size:13px;font-weight:600;color:#24324a;background:#eef4ff;box-shadow:inset 0 0 0 1px rgba(78,161,255,.12);transition:transform .18s ease,box-shadow .18s ease,filter .18s ease}'
                . 'a[title="查看此标签更多文章"]:hover{transform:translateY(-1px);filter:saturate(1.04);box-shadow:0 8px 18px rgba(17,24,39,.08)}'
                . 'a[title="查看此标签更多文章"]:nth-of-type(6n+1){color:#0f5bd8;background:linear-gradient(135deg,rgba(78,161,255,.20),rgba(78,161,255,.08))}'
                . 'a[title="查看此标签更多文章"]:nth-of-type(6n+2){color:#0b7a55;background:linear-gradient(135deg,rgba(23,201,100,.22),rgba(23,201,100,.08))}'
                . 'a[title="查看此标签更多文章"]:nth-of-type(6n+3){color:#9a4d00;background:linear-gradient(135deg,rgba(255,179,71,.24),rgba(255,179,71,.10))}'
                . 'a[title="查看此标签更多文章"]:nth-of-type(6n+4){color:#8f2d6b;background:linear-gradient(135deg,rgba(255,109,178,.22),rgba(255,109,178,.08))}'
                . 'a[title="查看此标签更多文章"]:nth-of-type(6n+5){color:#6c46d9;background:linear-gradient(135deg,rgba(162,122,255,.22),rgba(162,122,255,.08))}'
                . 'a[title="查看此标签更多文章"]:nth-of-type(6n){color:#0d6b83;background:linear-gradient(135deg,rgba(69,196,223,.22),rgba(69,196,223,.08))}'
                . '@media (max-width:640px){.article-tags{gap:8px 6px}a[title="查看此标签更多文章"]{font-size:12px;padding:5px 10px;margin:0 6px 6px 0 !important}}'
                . '</style>';
        }


}
