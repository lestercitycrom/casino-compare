<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$taxonomies = [
    'casino_license' => __('Licenses', 'casino-compare-theme'),
    'casino_feature' => __('Features', 'casino-compare-theme'),
    'payment_method' => __('Payments', 'casino-compare-theme'),
    'game_type' => __('Games', 'casino-compare-theme'),
];
?>
<form id="ccc-filter-form" method="get">
    <?php foreach ($taxonomies as $taxonomy => $label) : ?>
        <?php $terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => false]); ?>
        <?php if (is_wp_error($terms) || $terms === []) : continue; endif; ?>
        <?php
        $param_name = match ($taxonomy) {
            'payment_method' => 'payment',
            'game_type' => 'game',
            'casino_license' => 'license',
            default => 'feature',
        };
        $selected = array_map('sanitize_title', (array) ($_GET[$param_name] ?? []));
        ?>
        <fieldset>
            <legend><?php echo esc_html($label); ?></legend>
            <?php foreach ($terms as $term) : ?>
                <label>
                    <input type="checkbox" name="<?php echo esc_attr($param_name); ?>[]" value="<?php echo esc_attr($term->slug); ?>"<?php checked(in_array($term->slug, $selected, true)); ?>>
                    <?php echo esc_html($term->name); ?>
                </label>
            <?php endforeach; ?>
        </fieldset>
    <?php endforeach; ?>
    <p>
        <button type="submit"><?php esc_html_e('Apply filters', 'casino-compare-theme'); ?></button>
    </p>
</form>
