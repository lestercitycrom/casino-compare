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
            <?php if (has_custom_logo()) : ?>
                <?php the_custom_logo(); ?>
            <?php else : ?>
                <span class="site-logo__text"><?php bloginfo('name'); ?></span>
            <?php endif; ?>
        </a>
        <button class="nav-toggle" id="nav-toggle" aria-label="Menu" aria-expanded="false">&#9776;</button>
        <?php
        if (has_nav_menu('primary')) {
            wp_nav_menu([
                'theme_location' => 'primary',
                'container'      => 'nav',
                'container_class'=> 'site-nav',
                'container_id'   => 'nav-menu',
                'menu_class'     => '',
                'depth'          => 1,
                'items_wrap'     => '%3$s',
            ]);
        } else {
            ?>
            <nav class="site-nav" id="nav-menu" role="navigation">
                <a href="<?php echo esc_url(home_url('/casino-en-ligne/')); ?>">Casinos</a>
                <a href="<?php echo esc_url(home_url('/bonus-casino/')); ?>">Bonus</a>
                <a href="<?php echo esc_url(home_url('/jeux-casino/')); ?>">Jeux</a>
                <a href="<?php echo esc_url(home_url('/guide/')); ?>">Guides</a>
            </nav>
            <?php
        }
        ?>
        <a href="<?php echo esc_url(home_url('/comparer/')); ?>" class="compare-badge" id="ccc-compare-badge">Comparer (0)</a>
    </div>
</header>
<div id="page-wrap">
