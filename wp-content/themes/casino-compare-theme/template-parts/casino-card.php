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
$wagering = (string) get_post_meta($casino_id, 'wagering', true);
$withdrawal_min = (string) get_post_meta($casino_id, 'withdrawal_time_min', true);
$withdrawal_max = (string) get_post_meta($casino_id, 'withdrawal_time_max', true);
$affiliate_link = (string) get_post_meta($casino_id, 'affiliate_link', true);
$logo_id = (int) get_post_meta($casino_id, 'logo', true);
$pros = cct_repeater_text_list(get_post_meta($casino_id, 'pros', true), 'text');
$cons = cct_repeater_text_list(get_post_meta($casino_id, 'cons', true), 'text');
$quick_facts = array_filter([
    __('License', 'casino-compare-theme') => $license,
    __('Wagering', 'casino-compare-theme') => $wagering,
    __('Withdrawal', 'casino-compare-theme') => trim($withdrawal_min . ($withdrawal_max !== '' ? ' - ' . $withdrawal_max : '')),
]);
?>
<article class="casino-card">
    <?php if ($rank !== '') : ?>
        <p><strong>#<?php echo esc_html($rank); ?></strong></p>
    <?php endif; ?>
    <?php if ($logo_id > 0) : ?>
        <div class="casino-card__logo"><?php echo wp_kses_post(wp_get_attachment_image($logo_id, 'medium')); ?></div>
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
    <?php if ($wagering !== '') : ?>
        <p><?php esc_html_e('Wagering:', 'casino-compare-theme'); ?> <?php echo esc_html($wagering); ?></p>
    <?php endif; ?>
    <?php if ($withdrawal_min !== '' || $withdrawal_max !== '') : ?>
        <p><?php esc_html_e('Withdrawal:', 'casino-compare-theme'); ?> <?php echo esc_html(trim($withdrawal_min . ($withdrawal_max !== '' ? ' - ' . $withdrawal_max : ''))); ?></p>
    <?php endif; ?>
    <?php if ($short_review !== '') : ?>
        <p><?php echo esc_html($short_review); ?></p>
    <?php endif; ?>
    <?php if ($quick_facts !== []) : ?>
        <ul>
            <?php foreach ($quick_facts as $label => $value) : ?>
                <li><?php echo esc_html((string) $label . ': ' . (string) $value); ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <?php if ($pros !== [] || $cons !== []) : ?>
        <div class="casino-card__pros-cons">
            <?php if ($pros !== []) : ?>
                <p><strong><?php esc_html_e('Pros', 'casino-compare-theme'); ?></strong></p>
                <ul>
                    <?php foreach (array_slice($pros, 0, 3) as $pro) : ?>
                        <li><?php echo esc_html($pro); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <?php if ($cons !== []) : ?>
                <p><strong><?php esc_html_e('Cons', 'casino-compare-theme'); ?></strong></p>
                <ul>
                    <?php foreach (array_slice($cons, 0, 3) as $con) : ?>
                        <li><?php echo esc_html($con); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <?php get_template_part('template-parts/cta-block', null, [
        'text' => __('Play now', 'casino-compare-theme'),
        'url' => $affiliate_link ?: $permalink,
    ]); ?>
    <p><button type="button" data-ccc-compare-id="<?php echo esc_attr((string) $casino_id); ?>"><?php esc_html_e('Comparer', 'casino-compare-theme'); ?></button></p>
</article>
