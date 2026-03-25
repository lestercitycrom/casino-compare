<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$links = is_array($args['links'] ?? null) ? $args['links'] : [];
$links = array_values(array_filter($links, static fn($link) => is_array($link) && !empty($link['url']) && !empty($link['label'])));

if ($links === []) {
    return;
}
?>
<nav aria-label="<?php esc_attr_e('Internal links', 'casino-compare-theme'); ?>">
    <ul>
        <?php foreach ($links as $link) : ?>
            <li><a href="<?php echo esc_url((string) $link['url']); ?>"><?php echo esc_html((string) $link['label']); ?></a></li>
        <?php endforeach; ?>
    </ul>
</nav>
