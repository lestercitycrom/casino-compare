<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

get_header();

$casino_id = get_the_ID();
$rating_metrics = [
    __('Bonus', 'casino-compare-theme') => cct_get_meta('rating_bonus', $casino_id),
    __('Games', 'casino-compare-theme') => cct_get_meta('rating_games', $casino_id),
    __('Payments', 'casino-compare-theme') => cct_get_meta('rating_payments', $casino_id),
    __('Support', 'casino-compare-theme') => cct_get_meta('rating_support', $casino_id),
    __('Reliability', 'casino-compare-theme') => cct_get_meta('rating_reliability', $casino_id),
];
$hero_badges = array_values(array_filter([
    cct_get_meta('last_updated', $casino_id),
    cct_get_meta('author_name', $casino_id),
], static fn($value) => cct_has_content($value)));
$info_rows = [
    __('License', 'casino-compare-theme') => cct_get_meta('license', $casino_id),
    __('Year founded', 'casino-compare-theme') => cct_get_meta('year_founded', $casino_id),
    __('Trustpilot score', 'casino-compare-theme') => cct_get_meta('trustpilot_score', $casino_id),
    __('Games count', 'casino-compare-theme') => cct_get_meta('games_count', $casino_id),
    __('Support channels', 'casino-compare-theme') => cct_get_meta('support_channels', $casino_id),
    __('VIP program', 'casino-compare-theme') => cct_get_meta('vip', $casino_id),
    __('Mobile app', 'casino-compare-theme') => cct_get_meta('mobile_app', $casino_id),
    __('Withdrawal min', 'casino-compare-theme') => cct_get_meta('withdrawal_time_min', $casino_id),
    __('Withdrawal max', 'casino-compare-theme') => cct_get_meta('withdrawal_time_max', $casino_id),
];

$alternative_ids = array_map('intval', (array) cct_get_meta('alternative_casinos', $casino_id, []));
$money_page_links = function_exists('ccc_get_money_pages_for_casino') ? ccc_get_money_pages_for_casino($casino_id) : [];
?>
<main class="site-shell">
    <?php get_template_part('template-parts/breadcrumb'); ?>
    <article class="single-single single-single--casino">
        <header class="single-hero">
            <div class="single-hero__main">
                <h1><?php the_title(); ?></h1>
                <?php if ($hero_badges !== []) : ?>
                    <div class="meta-badges">
                        <?php foreach ($hero_badges as $badge) : ?>
                            <span class="meta-badge"><?php echo esc_html((string) $badge); ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php if (cct_has_content(cct_get_meta('intro_text', $casino_id))) : ?>
                    <div class="content-panel content-panel--soft">
                        <?php echo wp_kses_post(wpautop((string) cct_get_meta('intro_text', $casino_id))); ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="single-hero__actions">
                <?php get_template_part('template-parts/cta-block', null, [
                    'text' => __('Play now', 'casino-compare-theme'),
                    'url' => cct_get_meta('affiliate_link', $casino_id),
                ]); ?>
                <p class="compare-cta"><button class="button-secondary" type="button" data-ccc-compare-id="<?php echo esc_attr((string) $casino_id); ?>"><?php esc_html_e('Comparer', 'casino-compare-theme'); ?></button></p>
            </div>
        </header>

        <?php get_template_part('template-parts/pros-cons', null, [
            'pros' => cct_get_meta('pros', $casino_id, []),
            'cons' => cct_get_meta('cons', $casino_id, []),
        ]); ?>

        <?php get_template_part('template-parts/rating-block', null, [
            'overall' => cct_get_meta('overall_rating', $casino_id),
            'metrics' => $rating_metrics,
        ]); ?>

        <?php get_template_part('template-parts/info-table', null, ['rows' => $info_rows]); ?>

        <?php for ($i = 1; $i <= 5; $i++) : ?>
            <?php $summary_title = (string) cct_get_meta('summary_' . $i . '_title', $casino_id); ?>
            <?php $summary = (string) cct_get_meta('summary_' . $i, $casino_id); ?>
            <?php if (!cct_has_content($summary_title) || !cct_has_content($summary)) : continue; endif; ?>
            <section class="content-panel">
                <h2><?php echo esc_html($summary_title); ?></h2>
                <div><?php echo wp_kses_post($summary); ?></div>
            </section>
        <?php endfor; ?>

        <?php if (cct_has_content(cct_get_meta('final_verdict', $casino_id))) : ?>
            <section class="content-panel">
                <h2><?php esc_html_e('Final verdict', 'casino-compare-theme'); ?></h2>
                <div><?php echo wp_kses_post((string) cct_get_meta('final_verdict', $casino_id)); ?></div>
            </section>
        <?php endif; ?>

        <?php if ($alternative_ids !== []) : ?>
            <section class="content-panel">
                <h2><?php esc_html_e('Alternatives', 'casino-compare-theme'); ?></h2>
                <div class="homepage-card-grid">
                    <?php foreach ($alternative_ids as $alternative_id) : ?>
                        <?php get_template_part('template-parts/casino-card', null, ['casino_id' => $alternative_id]); ?>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php get_template_part('template-parts/faq-block', null, ['faq' => cct_get_meta('faq', $casino_id, [])]); ?>

        <?php if ($money_page_links !== []) : ?>
            <?php get_template_part('template-parts/internal-links', null, ['links' => $money_page_links]); ?>
        <?php endif; ?>
    </article>
</main>
<?php
get_footer();
