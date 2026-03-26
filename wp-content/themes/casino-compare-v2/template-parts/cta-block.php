<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$text = isset($args['text']) ? (string) $args['text'] : '';
$url  = isset($args['url'])  ? (string) $args['url']  : '';

if ($text === '' || $url === '') {
    return;
}
?>
<div class="cta-block">
    <a href="<?php echo esc_url($url); ?>" target="_blank" rel="nofollow noopener">
        <?php echo esc_html($text); ?>
    </a>
</div>
