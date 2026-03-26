<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$content = (string) ($args['content'] ?? '');

if (!cct_has_content($content)) {
    return;
}
?>
<section class="methodology-block">
    <h2><?php esc_html_e('Methodology', 'casino-compare-theme'); ?></h2>
    <div><?php echo wp_kses_post(wpautop($content)); ?></div>
</section>
