<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$casino_id = isset($args['casino_id']) ? (int) $args['casino_id'] : get_the_ID();
$rank = isset($args['rank']) ? (string) $args['rank'] : '';
$short_review = isset($args['short_review']) ? (string) $args['short_review'] : '';

if (!$casino_id) {
    return;
}

$title = get_the_title($casino_id);
$permalink = get_permalink($casino_id);
$bonus = (string) get_post_meta($casino_id, 'welcome_bonus_text', true);
$rating = (string) get_post_meta($casino_id, 'overall_rating', true);
$license = (string) get_post_meta($casino_id, 'license', true);
$affiliate_link = (string) get_post_meta($casino_id, 'affiliate_link', true);
?>
<article class="casino-card">
    <?php if ($rank !== '') : ?>
        <p><strong>#<?php echo esc_html($rank); ?></strong></p>
    <?php endif; ?>
    <h3><a href="<?php echo esc_url($permalink); ?>"><?php echo esc_html($title); ?></a></h3>
    <?php if ($rating !== '') : ?>
        <p><?php esc_html_e('Rating:', 'casino-compare-theme'); ?> <?php echo esc_html($rating); ?></p>
    <?php endif; ?>
    <?php if ($bonus !== '') : ?>
        <p><?php echo esc_html($bonus); ?></p>
    <?php endif; ?>
    <?php if ($license !== '') : ?>
        <p><?php esc_html_e('License:', 'casino-compare-theme'); ?> <?php echo esc_html($license); ?></p>
    <?php endif; ?>
    <?php if ($short_review !== '') : ?>
        <p><?php echo esc_html($short_review); ?></p>
    <?php endif; ?>
    <?php get_template_part('template-parts/cta-block', null, [
        'text' => __('Play now', 'casino-compare-theme'),
        'url' => $affiliate_link ?: $permalink,
    ]); ?>
    <p><button type="button" data-ccc-compare-id="<?php echo esc_attr((string) $casino_id); ?>"><?php esc_html_e('Comparer', 'casino-compare-theme'); ?></button></p>
</article>
