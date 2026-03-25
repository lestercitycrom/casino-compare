<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

get_header();

$guide_id = get_the_ID();
$sidebar_ids = array_map('intval', (array) cct_get_meta('sidebar_casino_list', $guide_id, []));
$related_guide_ids = array_map('intval', (array) cct_get_meta('related_guides', $guide_id, []));
?>
<main class="site-shell">
    <?php get_template_part('template-parts/breadcrumb'); ?>
    <article>
        <h1><?php the_title(); ?></h1>
        <p>
            <?php echo esc_html((string) cct_get_meta('category', $guide_id)); ?>
            <?php if (cct_has_content(cct_get_meta('reading_time', $guide_id))) : ?>
                · <?php echo esc_html((string) cct_get_meta('reading_time', $guide_id)); ?>
            <?php endif; ?>
            <?php if (cct_has_content(cct_get_meta('last_updated', $guide_id))) : ?>
                · <?php echo esc_html((string) cct_get_meta('last_updated', $guide_id)); ?>
            <?php endif; ?>
            <?php if (cct_has_content(cct_get_meta('author_name', $guide_id))) : ?>
                · <?php echo esc_html((string) cct_get_meta('author_name', $guide_id)); ?>
            <?php endif; ?>
        </p>

        <?php if (cct_has_content(cct_get_meta('intro_text', $guide_id))) : ?>
            <div><?php echo wp_kses_post(wpautop((string) cct_get_meta('intro_text', $guide_id))); ?></div>
        <?php endif; ?>

        <?php if (cct_has_content(cct_get_meta('callout_text', $guide_id))) : ?>
            <aside><p><?php echo esc_html((string) cct_get_meta('callout_text', $guide_id)); ?></p></aside>
        <?php endif; ?>

        <section>
            <?php if (cct_has_content(cct_get_meta('main_content', $guide_id))) : ?>
                <div><?php echo wp_kses_post((string) cct_get_meta('main_content', $guide_id)); ?></div>
            <?php endif; ?>

            <?php if (cct_has_content(cct_get_meta('sidebar_takeaway', $guide_id)) || $sidebar_ids !== []) : ?>
                <aside>
                    <?php if (cct_has_content(cct_get_meta('sidebar_takeaway', $guide_id))) : ?>
                        <div><?php echo wp_kses_post((string) cct_get_meta('sidebar_takeaway', $guide_id)); ?></div>
                    <?php endif; ?>
                    <?php foreach ($sidebar_ids as $index => $casino_id) : ?>
                        <?php get_template_part('template-parts/casino-card', null, [
                            'casino_id' => $casino_id,
                            'rank' => (string) ($index + 1),
                        ]); ?>
                    <?php endforeach; ?>
                </aside>
            <?php endif; ?>
        </section>

        <?php if ($related_guide_ids !== []) : ?>
            <section>
                <h2><?php esc_html_e('Related guides', 'casino-compare-theme'); ?></h2>
                <ul>
                    <?php foreach ($related_guide_ids as $related_id) : ?>
                        <li><a href="<?php echo esc_url(get_permalink($related_id)); ?>"><?php echo esc_html(get_the_title($related_id)); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>

        <?php get_template_part('template-parts/faq-block', null, ['faq' => cct_get_meta('faq', $guide_id, [])]); ?>
        <?php get_template_part('template-parts/internal-links', null, ['links' => cct_get_meta('money_page_links', $guide_id, [])]); ?>
    </article>
</main>
<?php
get_footer();
