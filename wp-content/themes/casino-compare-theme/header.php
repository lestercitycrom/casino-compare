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
<?php
$comparison_url = cct_get_first_landing_url('comparison');
$hub_url = cct_get_first_landing_url('hub');
$trust_url = cct_get_first_landing_url('trust');
$guide_url = cct_get_first_guide_url();
?>
<header class="site-header">
    <div class="site-shell site-header__inner">
        <a class="site-brand" href="<?php echo esc_url(home_url('/')); ?>">
            <span class="site-brand__eyebrow"><?php esc_html_e('Casino Compare', 'casino-compare-theme'); ?></span>
            <span class="site-brand__title"><?php esc_html_e('Reviews, guides and comparisons', 'casino-compare-theme'); ?></span>
        </a>
        <nav class="site-nav" aria-label="<?php esc_attr_e('Primary navigation', 'casino-compare-theme'); ?>">
            <a href="<?php echo esc_url($comparison_url ?: home_url('/')); ?>"><?php esc_html_e('Top casinos', 'casino-compare-theme'); ?></a>
            <a href="<?php echo esc_url($hub_url ?: home_url('/')); ?>"><?php esc_html_e('Categories', 'casino-compare-theme'); ?></a>
            <a href="<?php echo esc_url($guide_url ?: home_url('/')); ?>"><?php esc_html_e('Guides', 'casino-compare-theme'); ?></a>
            <a href="<?php echo esc_url($trust_url ?: home_url('/')); ?>"><?php esc_html_e('Methodology', 'casino-compare-theme'); ?></a>
            <a href="<?php echo esc_url(home_url('/comparer/')); ?>" id="ccc-compare-badge" class="site-nav__badge"><?php esc_html_e('Comparer (0)', 'casino-compare-theme'); ?></a>
        </nav>
    </div>
</header>
