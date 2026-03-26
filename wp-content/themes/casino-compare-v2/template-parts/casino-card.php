<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$casino_id    = isset($args['casino_id']) ? (int) $args['casino_id'] : get_the_ID();
$rank         = isset($args['rank']) ? (string) $args['rank'] : '';
$short_review = isset($args['short_review']) ? (string) $args['short_review'] : '';

if (!$casino_id) {
    return;
}

$title         = get_the_title($casino_id);
$permalink     = get_permalink($casino_id);
$logo_id       = (int) get_post_meta($casino_id, 'logo', true);
$bonus         = (string) get_post_meta($casino_id, 'welcome_bonus_text', true);
$rating        = (string) get_post_meta($casino_id, 'overall_rating', true);
$free_spins    = (string) get_post_meta($casino_id, 'free_spins', true);
$wagering      = (string) get_post_meta($casino_id, 'wagering', true);
$license       = (string) get_post_meta($casino_id, 'license', true);
$affiliate_link= (string) get_post_meta($casino_id, 'affiliate_link', true);
?>
<article class="casino-card">

    <div class="casino-card__header">
        <?php if ($rank !== '') : ?>
            <span class="casino-card__rank">#<?php echo esc_html($rank); ?></span>
        <?php endif; ?>

        <div class="casino-card__logo">
            <?php if ($logo_id > 0) : ?>
                <?php echo wp_get_attachment_image($logo_id, 'medium', false, ['class' => 'casino-card__logo-img']); ?>
            <?php else : ?>
                <span class="casino-card__logo-placeholder"><?php echo esc_html(mb_strtoupper(mb_substr($title, 0, 2))); ?></span>
            <?php endif; ?>
        </div>

        <div class="casino-card__title-wrap">
            <h3 class="casino-card__name">
                <a href="<?php echo esc_url($permalink); ?>"><?php echo esc_html($title); ?></a>
            </h3>
            <?php if ($rating !== '') : ?>
                <div class="casino-card__rating">
                    <span class="casino-card__rating-score"><?php echo esc_html($rating); ?></span>
                    <span class="casino-card__stars">&#9733;&#9733;&#9733;&#9733;&#9734;</span>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="casino-card__bonus">
        <?php if ($bonus !== '') : ?>
            <span class="bonus-tag">🎁 <?php echo esc_html($bonus); ?></span>
        <?php endif; ?>
        <?php if ($free_spins !== '') : ?>
            <span class="bonus-tag">🎰 <?php echo esc_html($free_spins); ?> FS</span>
        <?php endif; ?>
    </div>

    <div class="casino-card__info">
        <?php if ($wagering !== '') : ?>
            <div class="info-table__row">
                <span class="info-table__label">Wager</span>
                <span class="info-table__value"><?php echo esc_html($wagering); ?></span>
            </div>
        <?php endif; ?>
        <?php if ($license !== '') : ?>
            <div class="info-table__row">
                <span class="info-table__label">Licence</span>
                <span class="info-table__value"><?php echo esc_html($license); ?></span>
            </div>
        <?php endif; ?>
        <?php if ($short_review !== '') : ?>
            <p class="casino-card__review text-soft"><?php echo esc_html($short_review); ?></p>
        <?php endif; ?>
    </div>

    <div class="casino-card__actions">
        <a href="<?php echo esc_url($affiliate_link !== '' ? $affiliate_link : $permalink); ?>"
           class="btn-primary"
           target="_blank"
           rel="nofollow noopener">
            Jouer
        </a>
        <button type="button" class="btn-outline" data-ccc-compare-id="<?php echo esc_attr((string) $casino_id); ?>">
            Comparer
        </button>
        <a href="<?php echo esc_url($permalink); ?>" class="casino-card__review-link">Notre avis &#8594;</a>
    </div>

</article>
