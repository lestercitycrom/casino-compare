<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<header class="site-header">
    <div class="site-shell site-header__inner">
        <a href="<?php echo esc_url(home_url('/')); ?>" class="site-logo">
            <span class="site-logo__text">Casino<span class="site-logo__accent">Compare</span></span>
        </a>
        <button class="nav-toggle" id="nav-toggle" aria-label="Menu" aria-expanded="false">&#9776;</button>
        <nav class="site-nav" id="nav-menu" role="navigation" aria-label="<?php esc_attr_e('Navigation principale', 'casino-compare-v2'); ?>">
            <a href="<?php echo esc_url(home_url('/casino-en-ligne/')); ?>" class="site-nav__link">Casinos</a>
            <a href="<?php echo esc_url(home_url('/bonus-casino/')); ?>" class="site-nav__link">Bonus</a>
            <a href="<?php echo esc_url(home_url('/jeux-casino/')); ?>" class="site-nav__link">Jeux</a>
            <a href="<?php echo esc_url(home_url('/guide/')); ?>" class="site-nav__link">Guides</a>
        </nav>
        <a href="<?php echo esc_url(home_url('/comparer/')); ?>" class="compare-badge" id="ccc-compare-badge">Comparer (0)</a>
    </div>
</header>
<div id="page-wrap">
