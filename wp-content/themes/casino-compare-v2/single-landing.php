<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

get_header();

$landing_id   = get_the_ID();
$landing_type = (string) cct_get_meta('landing_type', $landing_id);
$faq          = cct_get_meta('faq', $landing_id, []);

// Author / date badges
$trust_author_name   = (string) (cct_get_meta('trust_author_name', $landing_id) ?: cct_get_meta('author_name', $landing_id));
$trust_last_updated  = (string) (cct_get_meta('trust_last_updated', $landing_id) ?: cct_get_meta('last_updated', $landing_id));

$comparison_badges = array_values(array_filter([
    cct_get_meta('last_updated', $landing_id),
    cct_get_meta('author_name', $landing_id),
    cct_get_meta('casinos_tested_count', $landing_id),
], static fn($value) => cct_has_content($value)));

$hub_badges = array_values(array_filter([
    cct_get_meta('last_updated', $landing_id),
], static fn($value) => cct_has_content($value)));

// Filter params (comparison pages)
$filter_params = [
    'license' => (array) ($_GET['license'] ?? []),
    'feature' => (array) ($_GET['feature'] ?? []),
    'payment' => (array) ($_GET['payment'] ?? []),
    'game'    => (array) ($_GET['game'] ?? []),
    'sort'    => (string) ($_GET['sort'] ?? ''),
];
$has_active_filters = (bool) array_filter($filter_params, static fn($value) => $value !== '' && $value !== []);

// Cross-silo links
$cross_silo_links = function_exists('ccc_get_cross_silo_links') ? ccc_get_cross_silo_links($landing_id) : [];
?>
<main class="site-shell">

    <?php get_template_part('template-parts/breadcrumb'); ?>

    <article class="single-single single-single--landing">

        <!-- =============================================
             HERO
             ============================================= -->
        <header class="single-hero single-hero--landing">
            <div class="single-hero__info">
                <h1><?php echo esc_html((string) (cct_get_meta('hero_title', $landing_id) ?: get_the_title())); ?></h1>

                <?php if ($landing_type === 'comparison' && $comparison_badges !== []) : ?>
                    <div class="meta-badges" style="margin-top:12px">
                        <?php foreach ($comparison_badges as $badge) : ?>
                            <span class="meta-badge"><?php echo esc_html((string) $badge); ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($landing_type === 'hub' && $hub_badges !== []) : ?>
                    <div class="meta-badges" style="margin-top:12px">
                        <?php foreach ($hub_badges as $badge) : ?>
                            <span class="meta-badge"><?php echo esc_html((string) $badge); ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($landing_type === 'trust' && (cct_has_content($trust_author_name) || cct_has_content($trust_last_updated))) : ?>
                    <div class="meta-badges" style="margin-top:12px">
                        <?php if (cct_has_content($trust_author_name)) : ?>
                            <span class="meta-badge"><?php echo esc_html($trust_author_name); ?></span>
                        <?php endif; ?>
                        <?php if (cct_has_content($trust_last_updated)) : ?>
                            <span class="meta-badge"><?php echo esc_html($trust_last_updated); ?></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if (cct_has_content(cct_get_meta('intro_text', $landing_id))) : ?>
                    <div class="content-panel content-panel--soft" style="margin-top:20px">
                        <?php echo wp_kses_post(wpautop((string) cct_get_meta('intro_text', $landing_id))); ?>
                    </div>
                <?php endif; ?>
            </div>
        </header>

        <?php /* ============================================================
               COMPARISON TYPE
               ============================================================ */ ?>
        <?php if ($landing_type === 'comparison') : ?>

            <?php get_template_part('template-parts/filter-ui'); ?>

            <div id="ccc-filter-results" class="landing-results">
                <?php if ($has_active_filters && function_exists('ccc_render_filter_results')) : ?>
                    <?php echo ccc_render_filter_results($filter_params); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <?php else : ?>
                    <?php $cards = cct_normalize_repeater(cct_get_meta('casino_cards', $landing_id, [])); ?>
                    <?php if ($cards !== []) : ?>
                        <div class="card-grid">
                            <?php foreach ($cards as $card) : ?>
                                <?php get_template_part('template-parts/casino-card', null, [
                                    'casino_id'    => (int) ($card['casino_id'] ?? 0),
                                    'rank'         => (string) ($card['rank'] ?? ''),
                                    'short_review' => (string) ($card['short_review'] ?? ''),
                                ]); ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <?php get_template_part('template-parts/methodology-block', null, [
                'content' => cct_get_meta('methodology_content', $landing_id),
            ]); ?>

            <?php if (cct_has_content(cct_get_meta('bottom_content', $landing_id))) : ?>
                <section class="content-panel" style="margin-top:24px">
                    <div><?php echo wp_kses_post((string) cct_get_meta('bottom_content', $landing_id)); ?></div>
                </section>
            <?php endif; ?>

            <?php get_template_part('template-parts/internal-links', null, [
                'links' => cct_get_meta('internal_link_pills', $landing_id, []),
            ]); ?>

        <?php endif; /* comparison */ ?>

        <?php /* ============================================================
               HUB TYPE
               ============================================================ */ ?>
        <?php if ($landing_type === 'hub') : ?>

            <?php $subcategories = cct_normalize_repeater(cct_get_meta('subcategory_cards', $landing_id, [])); ?>
            <?php if ($subcategories !== []) : ?>
                <section class="content-section">
                    <h2>Sous-catégories</h2>
                    <div class="category-grid" style="margin-top:16px">
                        <?php foreach ($subcategories as $card) : ?>
                            <article class="category-card">
                                <a href="<?php echo esc_url((string) ($card['url'] ?? '#')); ?>" class="category-card__title">
                                    <?php echo esc_html((string) ($card['title'] ?? '')); ?>
                                </a>
                                <?php if (!empty($card['description'])) : ?>
                                    <p style="font-size:0.8rem;color:#94a3b8;margin-top:6px"><?php echo esc_html((string) $card['description']); ?></p>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php $top_ids = array_map('intval', (array) cct_get_meta('top_casino_list', $landing_id, [])); ?>
            <?php if ($top_ids === []) : $top_ids = array_column(cct_get_top_casinos(3), 'ID'); endif; ?>
            <?php if ($top_ids !== []) : _prime_post_caches($top_ids, false, true); ?>
                <section class="content-section">
                    <h2>Top casinos</h2>
                    <div class="card-grid card-grid--3" style="margin-top:16px">
                        <?php foreach ($top_ids as $index => $casino_id) : ?>
                            <?php get_template_part('template-parts/casino-card', null, [
                                'casino_id' => $casino_id,
                                'rank'      => (string) ($index + 1),
                            ]); ?>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php if (cct_has_content(cct_get_meta('educational_content', $landing_id))) : ?>
                <section class="content-panel" style="margin-top:24px">
                    <div><?php echo wp_kses_post((string) cct_get_meta('educational_content', $landing_id)); ?></div>
                </section>
            <?php endif; ?>

            <?php $comparison_table_headers = cct_normalize_repeater(cct_get_meta('comparison_table_headers', $landing_id, [])); ?>
            <?php $comparison_table_rows    = cct_normalize_repeater(cct_get_meta('comparison_table_rows', $landing_id, [])); ?>
            <?php if (cct_has_content(cct_get_meta('comparison_table_title', $landing_id)) || $comparison_table_headers !== [] || $comparison_table_rows !== []) : ?>
                <section class="content-panel" style="margin-top:24px">
                    <?php if (cct_has_content(cct_get_meta('comparison_table_title', $landing_id))) : ?>
                        <h2 style="margin-bottom:16px"><?php echo esc_html((string) cct_get_meta('comparison_table_title', $landing_id)); ?></h2>
                    <?php endif; ?>
                    <table class="bonus-table" style="width:100%">
                        <?php if ($comparison_table_headers !== []) : ?>
                            <thead>
                                <tr>
                                    <?php foreach ($comparison_table_headers as $header) : ?>
                                        <th><?php echo esc_html((string) ($header['label'] ?? '')); ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                        <?php endif; ?>
                        <?php if ($comparison_table_rows !== []) : ?>
                            <tbody>
                                <?php foreach ($comparison_table_rows as $row) : ?>
                                    <tr>
                                        <?php for ($column_index = 1; $column_index <= 6; $column_index++) : ?>
                                            <?php $value = trim((string) ($row['col_' . $column_index] ?? '')); ?>
                                            <?php if ($comparison_table_headers === [] && $value === '') : continue; endif; ?>
                                            <td><?php echo esc_html($value); ?></td>
                                        <?php endfor; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        <?php endif; ?>
                    </table>
                </section>
            <?php endif; ?>

            <?php if (cct_has_content(cct_get_meta('howto_content', $landing_id))) : ?>
                <section class="content-panel" style="margin-top:24px">
                    <?php if (cct_has_content(cct_get_meta('howto_title', $landing_id))) : ?>
                        <h2 style="margin-bottom:12px"><?php echo esc_html((string) cct_get_meta('howto_title', $landing_id)); ?></h2>
                    <?php endif; ?>
                    <div><?php echo wp_kses_post((string) cct_get_meta('howto_content', $landing_id)); ?></div>
                </section>
            <?php endif; ?>

            <?php get_template_part('template-parts/internal-links', null, ['links' => $cross_silo_links]); ?>

        <?php endif; /* hub */ ?>

        <?php /* ============================================================
               TRUST TYPE
               ============================================================ */ ?>
        <?php if ($landing_type === 'trust') : ?>

            <?php if (cct_has_content(cct_get_meta('page_content', $landing_id))) : ?>
                <section class="content-panel" style="margin-top:24px">
                    <div><?php echo wp_kses_post((string) cct_get_meta('page_content', $landing_id)); ?></div>
                    <?php if ((bool) cct_get_meta('show_author', $landing_id)) : ?>
                        <div class="meta-badges" style="margin-top:16px;border-top:1px solid #334155;padding-top:16px">
                            <?php if (cct_has_content($trust_author_name)) : ?>
                                <span class="meta-badge"><?php echo esc_html($trust_author_name); ?></span>
                            <?php endif; ?>
                            <?php if (cct_has_content($trust_last_updated)) : ?>
                                <span class="meta-badge"><?php echo esc_html($trust_last_updated); ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </section>
            <?php endif; ?>

            <?php /* Additional trust sections */ ?>
            <?php for ($n = 1; $n <= 5; $n++) :
                $section_title   = (string) cct_get_meta("section_{$n}_title", $landing_id);
                $section_content = (string) cct_get_meta("section_{$n}_content", $landing_id);
                if (!cct_has_content($section_title) && !cct_has_content($section_content)) : continue; endif;
            ?>
                <section class="content-section" style="margin-top:32px">
                    <?php if (cct_has_content($section_title)) : ?>
                        <h2><?php echo esc_html($section_title); ?></h2>
                    <?php endif; ?>
                    <?php if (cct_has_content($section_content)) : ?>
                        <div class="content-panel" style="margin-top:12px"><?php echo wp_kses_post($section_content); ?></div>
                    <?php endif; ?>
                </section>
            <?php endfor; ?>

        <?php endif; /* trust */ ?>

        <?php get_template_part('template-parts/faq-block', null, ['faq' => $faq]); ?>

    </article>

</main>
<?php
get_footer();
