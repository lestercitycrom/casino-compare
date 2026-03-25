<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<header class="site-shell">
    <nav>
        <a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Home', 'casino-compare-theme'); ?></a>
        <a href="<?php echo esc_url(home_url('/comparer/')); ?>" id="ccc-compare-badge"><?php esc_html_e('Comparer (0)', 'casino-compare-theme'); ?></a>
    </nav>
</header>
