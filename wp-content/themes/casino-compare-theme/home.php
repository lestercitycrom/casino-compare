<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

get_header();

$featured_casinos = cct_get_top_casinos(3);
$comparison_pages = get_posts([
    'post_type' => 'landing',
    'post_status' => 'publish',
    'posts_per_page' => 3,
    'meta_key' => 'landing_type',
    'meta_value' => 'comparison',
    'orderby' => 'date',
    'order' => 'DESC',
]);
$hub_pages = get_posts([
    'post_type' => 'landing',
    'post_status' => 'publish',
    'posts_per_page' => 4,
    'meta_key' => 'landing_type',
    'meta_value' => 'hub',
    'orderby' => 'date',
    'order' => 'DESC',
]);
$trust_page_url = cct_get_first_landing_url('trust');
$latest_guides = get_posts([
    'post_type' => 'guide',
    'post_status' => 'publish',
    'posts_per_page' => 3,
    'orderby' => 'date',
    'order' => 'DESC',
]);
?>
<main class="homepage">
    <section class="hero-home">
        <div class="site-shell hero-home__grid">
            <div class="hero-home__copy">
                <p class="eyebrow"><?php esc_html_e('Phase 1 platform', 'casino-compare-theme'); ?></p>
                <h1><?php esc_html_e('Compare casinos with structured reviews, money pages and editorial guides.', 'casino-compare-theme'); ?></h1>
                <p class="hero-home__lead"><?php esc_html_e('This front layer is built on the same WordPress architecture that powers the review pages, subpages, comparison landings, trust pages and guides.', 'casino-compare-theme'); ?></p>
                <div class="hero-home__actions">
                    <a class="button-primary" href="<?php echo esc_url(cct_get_first_landing_url('comparison') ?: home_url('/comparer/')); ?>"><?php esc_html_e('Explore comparisons', 'casino-compare-theme'); ?></a>
                    <a class="button-secondary" href="<?php echo esc_url(home_url('/comparer/')); ?>"><?php esc_html_e('Open compare tool', 'casino-compare-theme'); ?></a>
                </div>
            </div>
            <aside class="hero-home__panel">
                <p class="hero-home__panel-label"><?php esc_html_e('What is live now', 'casino-compare-theme'); ?></p>
                <ul class="hero-home__stats">
                    <li><strong>4</strong><span><?php esc_html_e('content types', 'casino-compare-theme'); ?></span></li>
                    <li><strong>8</strong><span><?php esc_html_e('phase 1 subpages per casino', 'casino-compare-theme'); ?></span></li>
                    <li><strong>3</strong><span><?php esc_html_e('landing families', 'casino-compare-theme'); ?></span></li>
                    <li><strong>1</strong><span><?php esc_html_e('compare engine', 'casino-compare-theme'); ?></span></li>
                </ul>
            </aside>
        </div>
    </section>

    <section class="homepage-section">
        <div class="site-shell">
            <div class="section-heading">
                <p class="eyebrow"><?php esc_html_e('Featured reviews', 'casino-compare-theme'); ?></p>
                <h2><?php esc_html_e('Top casinos by current rating', 'casino-compare-theme'); ?></h2>
            </div>
            <?php if ($featured_casinos !== []) : ?>
                <div class="homepage-card-grid">
                    <?php foreach ($featured_casinos as $index => $casino) : ?>
                        <?php get_template_part('template-parts/casino-card', null, [
                            'casino_id' => (int) $casino->ID,
                            'rank' => (string) ($index + 1),
                        ]); ?>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <p class="empty-note"><?php esc_html_e('Publish a few casino reviews to populate the homepage showcase.', 'casino-compare-theme'); ?></p>
            <?php endif; ?>
        </div>
    </section>

    <section class="homepage-section homepage-section--alt">
        <div class="site-shell">
            <div class="section-heading">
                <p class="eyebrow"><?php esc_html_e('Money pages', 'casino-compare-theme'); ?></p>
                <h2><?php esc_html_e('Comparison pages ready for filter and ranking logic', 'casino-compare-theme'); ?></h2>
            </div>
            <div class="landing-link-grid">
                <?php foreach ($comparison_pages as $page) : ?>
                    <article class="landing-link-card">
                        <p class="landing-link-card__type"><?php esc_html_e('Comparison', 'casino-compare-theme'); ?></p>
                        <h3><a href="<?php echo esc_url(get_permalink($page)); ?>"><?php echo esc_html(get_the_title($page)); ?></a></h3>
                        <p><?php echo esc_html((string) cct_get_meta('intro_text', (int) $page->ID)); ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="homepage-section">
        <div class="site-shell">
            <div class="section-heading">
                <p class="eyebrow"><?php esc_html_e('Hubs and guides', 'casino-compare-theme'); ?></p>
                <h2><?php esc_html_e('Editorial navigation for categories and intent pages', 'casino-compare-theme'); ?></h2>
            </div>
            <div class="hub-guide-grid">
                <div class="hub-guide-grid__column">
                    <?php foreach ($hub_pages as $page) : ?>
                        <article class="mini-card">
                            <h3><a href="<?php echo esc_url(get_permalink($page)); ?>"><?php echo esc_html(get_the_title($page)); ?></a></h3>
                            <p><?php echo esc_html((string) cct_get_meta('intro_text', (int) $page->ID)); ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
                <div class="hub-guide-grid__column">
                    <?php foreach ($latest_guides as $guide) : ?>
                        <article class="mini-card mini-card--guide">
                            <p class="mini-card__meta"><?php echo esc_html((string) cct_get_meta('reading_time', (int) $guide->ID)); ?> <?php esc_html_e('min read', 'casino-compare-theme'); ?></p>
                            <h3><a href="<?php echo esc_url(get_permalink($guide)); ?>"><?php echo esc_html(get_the_title($guide)); ?></a></h3>
                            <p><?php echo esc_html((string) cct_get_meta('intro_text', (int) $guide->ID)); ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <section class="homepage-section homepage-section--band">
        <div class="site-shell promo-band">
            <div>
                <p class="eyebrow"><?php esc_html_e('Trust layer', 'casino-compare-theme'); ?></p>
                <h2><?php esc_html_e('Methodology, responsible gaming and editorial transparency', 'casino-compare-theme'); ?></h2>
            </div>
            <div class="promo-band__actions">
                <a class="button-primary" href="<?php echo esc_url($trust_page_url ?: home_url('/')); ?>"><?php esc_html_e('Read methodology', 'casino-compare-theme'); ?></a>
                <a class="button-secondary" href="<?php echo esc_url(home_url('/comparer/')); ?>"><?php esc_html_e('Try comparer', 'casino-compare-theme'); ?></a>
            </div>
        </div>
    </section>
</main>
<?php
get_footer();
