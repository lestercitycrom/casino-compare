<?php
/**
 * Template Name: Compare Page
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>
<main class="site-shell compare-page">
    <article class="compare-page__article">
        <div class="section-heading">
            <p class="eyebrow"><?php esc_html_e('Compare engine', 'casino-compare-theme'); ?></p>
            <h1><?php the_title(); ?></h1>
            <p class="compare-page__lead"><?php esc_html_e('Build a side-by-side view of up to three casinos. Add items from review pages, comparison cards or guide sidebars, then come back here to inspect the key fields.', 'casino-compare-theme'); ?></p>
        </div>
        <div class="compare-page__panel">
            <div id="ccc-compare-app"></div>
        </div>
    </article>
</main>
<?php
get_footer();
