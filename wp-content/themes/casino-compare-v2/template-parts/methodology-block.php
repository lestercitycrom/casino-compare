<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$content = isset($args['content']) ? (string) $args['content'] : '';

if (!cct_has_content($content)) {
    return;
}
?>
<section class="content-panel methodology-block" style="margin:32px 0">
    <p class="eyebrow" style="margin-bottom:8px">Notre méthode</p>
    <h2 style="margin-bottom:16px">Comment nous évaluons les casinos</h2>
    <div><?php echo wp_kses_post($content); ?></div>
</section>
