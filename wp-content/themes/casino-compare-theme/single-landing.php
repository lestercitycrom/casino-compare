<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

get_header();

$landing_id = get_the_ID();
$landing_type = (string) cct_get_meta('landing_type', $landing_id);
$faq = cct_get_meta('faq', $landing_id, []);
$cross_silo_links = function_exists('ccc_get_cross_silo_links') ? ccc_get_cross_silo_links($landing_id) : [];
$trust_author_name = (string) (cct_get_meta('trust_author_name', $landing_id) ?: cct_get_meta('author_name', $landing_id));
$trust_last_updated = (string) (cct_get_meta('trust_last_updated', $landing_id) ?: cct_get_meta('last_updated', $landing_id));
$comparison_badges = array_values(array_filter([
    cct_get_meta('last_updated', $landing_id),
    cct_get_meta('author_name', $landing_id),
    cct_get_meta('casinos_tested_count', $landing_id),
], static fn($value) => cct_has_content($value)));
$filter_params = [
    'license' => (array) ($_GET['license'] ?? []),
    'feature' => (array) ($_GET['feature'] ?? []),
    'payment' => (array) ($_GET['payment'] ?? []),
    'game' => (array) ($_GET['game'] ?? []),
    'sort' => (string) ($_GET['sort'] ?? ''),
];
$has_active_filters = (bool) array_filter($filter_params, static fn($value) => $value !== '' && $value !== []);
?>
<main class="site-shell">
    <?php get_template_part('template-parts/breadcrumb'); ?>
    <article>
        <h1><?php echo esc_html((string) (cct_get_meta('hero_title', $landing_id) ?: get_the_title())); ?></h1>

        <?php if (cct_has_content(cct_get_meta('intro_text', $landing_id))) : ?>
            <div><?php echo wp_kses_post(wpautop((string) cct_get_meta('intro_text', $landing_id))); ?></div>
        <?php endif; ?>

        <?php if ($landing_type === 'comparison') : ?>
            <?php if ($comparison_badges !== []) : ?>
                <p><?php echo esc_html(implode(' · ', array_map('strval', $comparison_badges))); ?></p>
            <?php endif; ?>
            <?php get_template_part('template-parts/filter-ui'); ?>
            <div id="ccc-filter-results">
                <?php if ($has_active_filters && function_exists('ccc_render_filter_results')) : ?>
                    <?php echo ccc_render_filter_results($filter_params); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <?php else : ?>
                    <?php $cards = cct_normalize_repeater(cct_get_meta('casino_cards', $landing_id, [])); ?>
                    <?php if ($cards !== []) : ?>
                        <section>
                            <?php foreach ($cards as $card) : ?>
                                <?php get_template_part('template-parts/casino-card', null, [
                                    'casino_id' => (int) ($card['casino_id'] ?? 0),
                                    'rank' => (string) ($card['rank'] ?? ''),
                                    'short_review' => (string) ($card['short_review'] ?? ''),
                                ]); ?>
                            <?php endforeach; ?>
                        </section>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <?php get_template_part('template-parts/methodology-block', null, [
                'content' => cct_get_meta('methodology_content', $landing_id),
            ]); ?>

            <?php if (cct_has_content(cct_get_meta('bottom_content', $landing_id))) : ?>
                <section>
                    <div><?php echo wp_kses_post((string) cct_get_meta('bottom_content', $landing_id)); ?></div>
                </section>
            <?php endif; ?>

            <?php get_template_part('template-parts/internal-links', null, [
                'links' => cct_get_meta('internal_link_pills', $landing_id, []),
            ]); ?>
        <?php endif; ?>

        <?php if ($landing_type === 'hub') : ?>
            <?php $subcategories = cct_normalize_repeater(cct_get_meta('subcategory_cards', $landing_id, [])); ?>
            <?php if ($subcategories !== []) : ?>
                <section>
                    <h2><?php esc_html_e('Subcategories', 'casino-compare-theme'); ?></h2>
                    <ul>
                        <?php foreach ($subcategories as $card) : ?>
                            <li>
                                <a href="<?php echo esc_url((string) ($card['url'] ?? '#')); ?>"><?php echo esc_html((string) ($card['title'] ?? '')); ?></a>
                                <?php if (!empty($card['description'])) : ?>
                                    <p><?php echo esc_html((string) $card['description']); ?></p>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endif; ?>

            <?php $top_ids = array_map('intval', (array) cct_get_meta('top_casino_list', $landing_id, [])); ?>
            <?php if ($top_ids !== []) : ?>
                <section>
                    <h2><?php esc_html_e('Top casinos', 'casino-compare-theme'); ?></h2>
                    <?php foreach ($top_ids as $index => $casino_id) : ?>
                        <?php get_template_part('template-parts/casino-card', null, [
                            'casino_id' => $casino_id,
                            'rank' => (string) ($index + 1),
                        ]); ?>
                    <?php endforeach; ?>
                </section>
            <?php endif; ?>

            <?php if (cct_has_content(cct_get_meta('educational_content', $landing_id))) : ?>
                <section><div><?php echo wp_kses_post((string) cct_get_meta('educational_content', $landing_id)); ?></div></section>
            <?php endif; ?>
            <?php if (cct_has_content(cct_get_meta('howto_content', $landing_id))) : ?>
                <section><div><?php echo wp_kses_post((string) cct_get_meta('howto_content', $landing_id)); ?></div></section>
            <?php endif; ?>
            <?php get_template_part('template-parts/internal-links', null, ['links' => $cross_silo_links]); ?>
        <?php endif; ?>

        <?php if ($landing_type === 'trust' && cct_has_content(cct_get_meta('page_content', $landing_id))) : ?>
            <section>
                <div><?php echo wp_kses_post((string) cct_get_meta('page_content', $landing_id)); ?></div>
                <?php if ((bool) cct_get_meta('show_author', $landing_id)) : ?>
                    <p><?php echo esc_html($trust_author_name); ?><?php if (cct_has_content($trust_last_updated)) : ?> · <?php echo esc_html($trust_last_updated); ?><?php endif; ?></p>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <?php get_template_part('template-parts/faq-block', null, ['faq' => $faq]); ?>
    </article>
</main>
<?php
get_footer();