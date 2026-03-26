<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

get_header();

$guide_id              = get_the_ID();
$sidebar_ids           = array_map('intval', (array) cct_get_meta('sidebar_casino_list', $guide_id, []));
$related_guide_ids     = array_map('intval', (array) cct_get_meta('related_guides', $guide_id, []));
$sidebar_manual_related= cct_normalize_link_rows(cct_get_meta('sidebar_related_guides', $guide_id, []));
$money_page_links      = cct_normalize_link_rows(cct_get_meta('money_page_links', $guide_id, []));
$sidebar_comparison_link = (string) cct_get_meta('sidebar_comparison_link', $guide_id);
?>
<main class="site-shell">

    <?php get_template_part('template-parts/breadcrumb'); ?>

    <article class="single-single single-single--guide">

        <!-- =============================================
             HERO
             ============================================= -->
        <header class="single-hero single-hero--guide">
            <div class="single-hero__info">
                <h1><?php the_title(); ?></h1>
                <div class="meta-badges" style="margin-top:12px">
                    <?php if (cct_has_content(cct_get_meta('category', $guide_id))) : ?>
                        <span class="meta-badge"><?php echo esc_html((string) cct_get_meta('category', $guide_id)); ?></span>
                    <?php endif; ?>
                    <?php if (cct_has_content(cct_get_meta('reading_time', $guide_id))) : ?>
                        <span class="meta-badge">&#128336; <?php echo esc_html((string) cct_get_meta('reading_time', $guide_id)); ?> min</span>
                    <?php endif; ?>
                    <?php if (cct_has_content(cct_get_meta('last_updated', $guide_id))) : ?>
                        <span class="meta-badge">&#128197; <?php echo esc_html((string) cct_get_meta('last_updated', $guide_id)); ?></span>
                    <?php endif; ?>
                    <?php if (cct_has_content(cct_get_meta('author_name', $guide_id))) : ?>
                        <span class="meta-badge">&#9997; <?php echo esc_html((string) cct_get_meta('author_name', $guide_id)); ?></span>
                    <?php endif; ?>
                </div>
                <?php if (cct_has_content(cct_get_meta('intro_text', $guide_id))) : ?>
                    <div class="content-panel content-panel--soft" style="margin-top:20px">
                        <?php echo wp_kses_post(wpautop((string) cct_get_meta('intro_text', $guide_id))); ?>
                    </div>
                <?php endif; ?>
            </div>
        </header>

        <!-- Callout box -->
        <?php if ((bool) cct_get_meta('callout_enabled', $guide_id) && (cct_has_content(cct_get_meta('callout_title', $guide_id)) || cct_has_content(cct_get_meta('callout_text', $guide_id)))) : ?>
            <aside class="guide-callout content-panel" style="border-left:3px solid #10b981;margin:20px 0">
                <?php if (cct_has_content(cct_get_meta('callout_title', $guide_id))) : ?>
                    <h2 style="margin-bottom:8px"><?php echo esc_html((string) cct_get_meta('callout_title', $guide_id)); ?></h2>
                <?php endif; ?>
                <?php if (cct_has_content(cct_get_meta('callout_text', $guide_id))) : ?>
                    <p><?php echo esc_html((string) cct_get_meta('callout_text', $guide_id)); ?></p>
                <?php endif; ?>
            </aside>
        <?php endif; ?>

        <!-- =============================================
             2-COLUMN LAYOUT: MAIN + SIDEBAR
             ============================================= -->
        <section class="guide-layout">

            <!-- MAIN CONTENT -->
            <?php if (cct_has_content(cct_get_meta('main_content', $guide_id))) : ?>
                <div class="guide-layout__main content-panel">
                    <?php echo wp_kses_post((string) cct_get_meta('main_content', $guide_id)); ?>
                </div>
            <?php endif; ?>

            <!-- SIDEBAR -->
            <?php
            $has_sidebar = (
                cct_has_content(cct_get_meta('sidebar_takeaway', $guide_id))
                || cct_has_content(cct_get_meta('sidebar_top_title', $guide_id))
                || $sidebar_ids !== []
                || $sidebar_comparison_link !== ''
                || $related_guide_ids !== []
                || $sidebar_manual_related !== []
            );
            if ($has_sidebar) : ?>
                <aside class="guide-layout__sidebar">

                    <?php if (cct_has_content(cct_get_meta('sidebar_top_title', $guide_id))) : ?>
                        <div class="content-panel" style="margin-bottom:16px">
                            <h3 style="margin-bottom:12px"><?php echo esc_html((string) cct_get_meta('sidebar_top_title', $guide_id)); ?></h3>
                            <?php if (cct_has_content(cct_get_meta('sidebar_takeaway', $guide_id))) : ?>
                                <div><?php echo wp_kses_post((string) cct_get_meta('sidebar_takeaway', $guide_id)); ?></div>
                            <?php endif; ?>
                        </div>
                    <?php elseif (cct_has_content(cct_get_meta('sidebar_takeaway', $guide_id))) : ?>
                        <div class="content-panel" style="margin-bottom:16px">
                            <div><?php echo wp_kses_post((string) cct_get_meta('sidebar_takeaway', $guide_id)); ?></div>
                        </div>
                    <?php endif; ?>

                    <?php foreach ($sidebar_ids as $index => $casino_id) : ?>
                        <?php get_template_part('template-parts/casino-card', null, [
                            'casino_id' => $casino_id,
                            'rank'      => (string) ($index + 1),
                        ]); ?>
                    <?php endforeach; ?>

                    <?php if ($sidebar_comparison_link !== '') : ?>
                        <div style="margin-top:16px">
                            <a class="btn-secondary btn-block" href="<?php echo esc_url($sidebar_comparison_link); ?>">
                                Voir la comparaison complète
                            </a>
                        </div>
                    <?php endif; ?>

                    <?php if ($related_guide_ids !== [] || $sidebar_manual_related !== []) : ?>
                        <div class="content-panel" style="margin-top:16px">
                            <h3 style="margin-bottom:12px;font-size:0.875rem;text-transform:uppercase;letter-spacing:0.05em;color:#10b981">Guides associés</h3>
                            <ul style="display:flex;flex-direction:column;gap:8px">
                                <?php foreach ($related_guide_ids as $related_id) : ?>
                                    <li>
                                        <a href="<?php echo esc_url(get_permalink($related_id)); ?>" style="color:#94a3b8;font-size:0.875rem;transition:color 0.2s" onmouseover="this.style.color='#f8fafc'" onmouseout="this.style.color='#94a3b8'">
                                            &#8594; <?php echo esc_html(get_the_title($related_id)); ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                                <?php foreach ($sidebar_manual_related as $link) : ?>
                                    <li>
                                        <a href="<?php echo esc_url((string) $link['url']); ?>" style="color:#94a3b8;font-size:0.875rem;transition:color 0.2s" onmouseover="this.style.color='#f8fafc'" onmouseout="this.style.color='#94a3b8'">
                                            &#8594; <?php echo esc_html((string) $link['label']); ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                </aside>
            <?php endif; ?>

        </section><!-- .guide-layout -->

        <?php get_template_part('template-parts/faq-block', null, ['faq' => cct_get_meta('faq', $guide_id, [])]); ?>
        <?php get_template_part('template-parts/internal-links', null, ['links' => $money_page_links]); ?>

    </article>

</main>
<?php
get_footer();
