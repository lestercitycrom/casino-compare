<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

get_header();

$subpage_id = get_the_ID();
$parent_casino_id = (int) cct_get_meta('parent_casino', $subpage_id);
$table_headers = cct_normalize_repeater(cct_get_meta('table_headers', $subpage_id, []));
$table_rows = cct_normalize_repeater(cct_get_meta('table_rows', $subpage_id, []));
$sibling_links = function_exists('ccc_get_sibling_subpages') ? ccc_get_sibling_subpages($subpage_id) : [];
$column_count = $table_headers !== [] ? count($table_headers) : 0;

if ($column_count === 0 && $table_rows !== []) {
    foreach ($table_rows as $row) {
        $filled_cells = array_values(array_filter([
            $row['col_1'] ?? '',
            $row['col_2'] ?? '',
            $row['col_3'] ?? '',
            $row['col_4'] ?? '',
            $row['col_5'] ?? '',
            $row['col_6'] ?? '',
        ], static fn($value) => trim((string) $value) !== ''));

        $column_count = max($column_count, count($filled_cells));
    }
}
?>
<main class="site-shell">
    <?php get_template_part('template-parts/breadcrumb'); ?>
    <article>
        <h1><?php echo esc_html((string) (cct_get_meta('hero_title', $subpage_id) ?: get_the_title())); ?></h1>

        <?php if (cct_has_content(cct_get_meta('intro_text', $subpage_id))) : ?>
            <div><?php echo wp_kses_post(wpautop((string) cct_get_meta('intro_text', $subpage_id))); ?></div>
        <?php endif; ?>

        <?php if ((bool) cct_get_meta('score_enabled', $subpage_id)) : ?>
            <?php get_template_part('template-parts/score-block', null, [
                'value' => cct_get_meta('score_value', $subpage_id),
                'label' => cct_get_meta('score_label', $subpage_id),
            ]); ?>
        <?php endif; ?>

        <?php if ((bool) cct_get_meta('table_enabled', $subpage_id) && ($table_headers !== [] || $table_rows !== [])) : ?>
            <section>
                <table>
                    <?php if ($table_headers !== []) : ?>
                        <thead>
                            <tr>
                                <?php foreach ($table_headers as $header) : ?>
                                    <th><?php echo esc_html((string) ($header['label'] ?? '')); ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                    <?php endif; ?>
                    <?php if ($table_rows !== []) : ?>
                        <tbody>
                            <?php foreach ($table_rows as $row) : ?>
                                <tr>
                                    <?php for ($column_index = 1; $column_index <= max(1, $column_count); $column_index++) : ?>
                                        <td><?php echo esc_html((string) ($row['col_' . $column_index] ?? '')); ?></td>
                                    <?php endfor; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    <?php endif; ?>
                </table>
            </section>
        <?php endif; ?>

        <?php if (cct_has_content(cct_get_meta('main_content', $subpage_id))) : ?>
            <div><?php echo wp_kses_post((string) cct_get_meta('main_content', $subpage_id)); ?></div>
        <?php endif; ?>

        <?php get_template_part('template-parts/cta-block', null, [
            'text' => cct_get_meta('cta_text', $subpage_id),
            'url' => cct_get_meta('cta_url', $subpage_id),
        ]); ?>

        <?php if ($parent_casino_id > 0) : ?>
            <p><a href="<?php echo esc_url(get_permalink($parent_casino_id)); ?>"><?php esc_html_e('Back to review', 'casino-compare-theme'); ?></a></p>
        <?php endif; ?>

        <?php if (is_array($sibling_links) && $sibling_links !== []) : ?>
            <?php get_template_part('template-parts/internal-links', null, ['links' => $sibling_links]); ?>
        <?php endif; ?>

        <?php get_template_part('template-parts/faq-block', null, ['faq' => cct_get_meta('faq', $subpage_id, [])]); ?>
    </article>
</main>
<?php
get_footer();
