<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$text = (string) ($args['text'] ?? '');
$url = (string) ($args['url'] ?? '');

if ($text === '' || $url === '') {
    return;
}
?>
<p class="cta-block"><a class="button-primary" href="<?php echo esc_url($url); ?>"><?php echo esc_html($text); ?></a></p>
