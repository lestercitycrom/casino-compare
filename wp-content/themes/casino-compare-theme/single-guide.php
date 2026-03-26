<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

get_header();

$guide_id = get_the_ID();
$sidebar_ids = array_map('intval', (array) cct_get_meta('sidebar_casino_list', $guide_id, []));
$related_guide_ids = array_map('intval', (array) cct_get_meta('related_guides', $guide_id, []));
$sidebar_manual_related = cct_normalize_link_rows(cct_get_meta('sidebar_related_guides', $guide_id, []));
$money_page_links = cct_normalize_link_rows(cct_get_meta('money_page_links', $guide_id, []));
$sidebar_comparison_link = (string) cct_get_meta('sidebar_comparison_link', $guide_id);
?>
<main class="site-shell">
    <?php get_template_part('template-parts/breadcrumb'); ?>
    <article class="single-single single-single--guide">
        <header class="single-hero">
            <div class="single-hero__main">
                <h1><?php the_title(); ?></h1>
                <div class="meta-badges">
                    <?php if (cct_has_content(cct_get_meta('category', $guide_id))) : ?>
                        <span class="meta-badge"><?php echo esc_html((string) cct_get_meta('category', $guide_id)); ?></span>
                    <?php endif; ?>
                    <?php if (cct_has_content(cct_get_meta('reading_time', $guide_id))) : ?>
                        <span class="meta-badge"><?php echo esc_html((string) cct_get_meta('reading_time', $guide_id)); ?></span>
                    <?php endif; ?>
                    <?php if (cct_has_content(cct_get_meta('last_updated', $guide_id))) : ?>
                        <span class="meta-badge"><?php echo esc_html((string) cct_get_meta('last_updated', $guide_id)); ?></span>
                    <?php endif; ?>
                    <?php if (cct_has_content(cct_get_meta('author_name', $guide_id))) : ?>
                        <span class="meta-badge"><?php echo esc_html((string) cct_get_meta('author_name', $guide_id)); ?></span>
                    <?php endif; ?>
                </div>
                <?php if (cct_has_content(cct_get_meta('intro_text', $guide_id))) : ?>
                    <div class="content-panel content-panel--soft">
                        <?php echo wp_kses_post(wpautop((string) cct_get_meta('intro_text', $guide_id))); ?>
                    </div>
                <?php endif; ?>
            </div>
        </header>

        <?php if ((bool) cct_get_meta('callout_enabled', $guide_id) && (cct_has_content(cct_get_meta('callout_title', $guide_id)) || cct_has_content(cct_get_meta('callout_text', $guide_id)))) : ?>
            <aside class="content-panel guide-callout">
                <?php if (cct_has_content(cct_get_meta('callout_title', $guide_id))) : ?>
                    <h2><?php echo esc_html((string) cct_get_meta('callout_title', $guide_id)); ?></h2>
                <?php endif; ?>
                <?php if (cct_has_content(cct_get_meta('callout_text', $guide_id))) : ?>
                    <p><?php echo esc_html((string) cct_get_meta('callout_text', $guide_id)); ?></p>
                <?php endif; ?>
            </aside>
        <?php endif; ?>

        <section class="guide-layout">
            <?php if (cct_has_content(cct_get_meta('main_content', $guide_id))) : ?>
                <div class="guide-layout__main content-panel"><?php echo wp_kses_post((string) cct_get_meta('main_content', $guide_id)); ?></div>
            <?php endif; ?>

            <?php if (cct_has_content(cct_get_meta('sidebar_takeaway', $guide_id)) || cct_has_content(cct_get_meta('sidebar_top_title', $guide_id)) || $sidebar_ids !== [] || $sidebar_comparison_link !== '' || $related_guide_ids !== [] || $sidebar_manual_related !== []) : ?>
                <aside class="guide-layout__sidebar content-panel">
                    <?php if (cct_has_content(cct_get_meta('sidebar_top_title', $guide_id))) : ?>
                        <h2><?php echo esc_html((string) cct_get_meta('sidebar_top_title', $guide_id)); ?></h2>
                    <?php endif; ?>
                    <?php if (cct_has_content(cct_get_meta('sidebar_takeaway', $guide_id))) : ?>
                        <div><?php echo wp_kses_post((string) cct_get_meta('sidebar_takeaway', $guide_id)); ?></div>
                    <?php endif; ?>
                    <?php foreach ($sidebar_ids as $index => $casino_id) : ?>
                        <?php get_template_part('template-parts/casino-card', null, [
                            'casino_id' => $casino_id,
                            'rank' => (string) ($index + 1),
                        ]); ?>
                    <?php endforeach; ?>
                    <?php if ($sidebar_comparison_link !== '') : ?>
                        <p><a class="button-secondary" href="<?php echo esc_url($sidebar_comparison_link); ?>"><?php esc_html_e('View comparison', 'casino-compare-theme'); ?></a></p>
                    <?php endif; ?>
                    <?php if ($related_guide_ids !== [] || $sidebar_manual_related !== []) : ?>
                        <div class="related-guides">
                            <h3><?php esc_html_e('Related guides', 'casino-compare-theme'); ?></h3>
                            <ul>
                                <?php foreach ($related_guide_ids as $related_id) : ?>
                                    <li><a href="<?php echo esc_url(get_permalink($related_id)); ?>"><?php echo esc_html(get_the_title($related_id)); ?></a></li>
                                <?php endforeach; ?>
                                <?php foreach ($sidebar_manual_related as $link) : ?>
                                    <li><a href="<?php echo esc_url((string) $link['url']); ?>"><?php echo esc_html((string) $link['label']); ?></a></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </aside>
            <?php endif; ?>
        </section>

        <?php get_template_part('template-parts/faq-block', null, ['faq' => cct_get_meta('faq', $guide_id, [])]); ?>
        <?php get_template_part('template-parts/internal-links', null, ['links' => $money_page_links]); ?>
    </article>
</main>
<?php
get_footer();
