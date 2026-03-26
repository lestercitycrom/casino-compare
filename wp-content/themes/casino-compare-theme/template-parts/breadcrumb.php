<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$crumbs = function_exists('ccc_get_breadcrumbs') ? ccc_get_breadcrumbs() : [[
    'label' => __('Home', 'casino-compare-theme'),
    'url' => home_url('/'),
]];
?>
<nav class="breadcrumb" aria-label="<?php esc_attr_e('Breadcrumb', 'casino-compare-theme'); ?>">
    <?php foreach ($crumbs as $index => $crumb) : ?>
        <?php if ($index > 0) : ?>
            <span class="breadcrumb__sep"> / </span>
        <?php endif; ?>
        <a href="<?php echo esc_url((string) $crumb['url']); ?>"><?php echo esc_html((string) $crumb['label']); ?></a>
    <?php endforeach; ?>
</nav>
