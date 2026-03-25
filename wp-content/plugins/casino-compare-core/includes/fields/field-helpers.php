<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

function ccc_enqueue_admin_field_assets(): void
{
    $screen = get_current_screen();

    if (!$screen instanceof WP_Screen) {
        return;
    }

    $allowed = ['casino', 'casino_subpage', 'landing', 'guide'];

    if (!in_array($screen->post_type, $allowed, true)) {
        return;
    }

    wp_enqueue_media();

    wp_enqueue_script(
        'ccc-admin-fields',
        CCC_PLUGIN_URL . 'assets/js/admin-fields.js',
        [],
        CCC_VERSION,
        true
    );
}
add_action('admin_enqueue_scripts', 'ccc_enqueue_admin_field_assets');

function ccc_get_meta_value(int $post_id, string $key, $default = '')
{
    $value = get_post_meta($post_id, $key, true);

    if ($value === '' || $value === null) {
        return $default;
    }

    return $value;
}

function ccc_render_text(string $key, string $label, string $value = '', array $args = []): void
{
    $placeholder = (string) ($args['placeholder'] ?? '');
    printf(
        '<p><label for="%1$s"><strong>%2$s</strong></label><br><input class="widefat" type="text" id="%1$s" name="%1$s" value="%3$s" placeholder="%4$s"></p>',
        esc_attr($key),
        esc_html($label),
        esc_attr($value),
        esc_attr($placeholder)
    );
}

function ccc_render_textarea(string $key, string $label, string $value = '', array $args = []): void
{
    $rows = (int) ($args['rows'] ?? 4);
    printf(
        '<p><label for="%1$s"><strong>%2$s</strong></label><br><textarea class="widefat" id="%1$s" name="%1$s" rows="%3$d">%4$s</textarea></p>',
        esc_attr($key),
        esc_html($label),
        max(2, $rows),
        esc_textarea($value)
    );
}

function ccc_render_wysiwyg(string $key, string $label, string $value = '', array $args = []): void
{
    echo '<p><strong>' . esc_html($label) . '</strong></p>';

    wp_editor($value, $key, [
        'textarea_name' => $key,
        'textarea_rows' => (int) ($args['rows'] ?? 6),
        'media_buttons' => false,
    ]);
}

function ccc_render_number(string $key, string $label, $value = '', array $args = []): void
{
    $step = (string) ($args['step'] ?? '1');
    $min = isset($args['min']) ? ' min="' . esc_attr((string) $args['min']) . '"' : '';
    $max = isset($args['max']) ? ' max="' . esc_attr((string) $args['max']) . '"' : '';

    printf(
        '<p><label for="%1$s"><strong>%2$s</strong></label><br><input class="small-text" type="number" step="%3$s"%4$s%5$s id="%1$s" name="%1$s" value="%6$s"></p>',
        esc_attr($key),
        esc_html($label),
        esc_attr($step),
        $min,
        $max,
        esc_attr((string) $value)
    );
}

function ccc_render_image(string $key, string $label, $value = 0): void
{
    $attachment_id = absint((string) $value);
    $preview = $attachment_id ? wp_get_attachment_image($attachment_id, 'thumbnail', false, ['style' => 'display:block;margin-bottom:8px;']) : '';

    echo '<p><strong>' . esc_html($label) . '</strong></p>';
    echo '<div class="ccc-image-field">';
    echo '<div class="ccc-image-preview">' . $preview . '</div>';
    echo '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr((string) $attachment_id) . '">';
    echo '<button type="button" class="button ccc-media-button" data-ccc-media-target="' . esc_attr($key) . '">' . esc_html__('Select image', 'casino-compare-core') . '</button>';
    echo '</div>';
}

function ccc_render_boolean(string $key, string $label, bool $checked = false): void
{
    printf(
        '<p><label><input type="checkbox" name="%1$s" value="1"%2$s> %3$s</label></p>',
        esc_attr($key),
        checked($checked, true, false),
        esc_html($label)
    );
}

function ccc_get_relation_posts(string $post_type, array $args = []): array
{
    return get_posts([
        'post_type' => $post_type,
        'post_status' => ['publish', 'draft'],
        'posts_per_page' => (int) ($args['posts_per_page'] ?? 50),
        'orderby' => 'title',
        'order' => 'ASC',
    ]);
}

function ccc_render_relation(string $key, string $label, string $post_type, $selected = '', array $args = []): void
{
    $multiple = !empty($args['multiple']);
    $selected_values = array_map('strval', (array) $selected);
    $posts = ccc_get_relation_posts($post_type, $args);
    $description = '';
    $max_items_attr = '';

    if (!empty($args['max_items'])) {
        $max_items_attr = ' data-ccc-max-items="' . esc_attr((string) (int) $args['max_items']) . '"';
        $description = sprintf(
            '<p class="description">%s</p>',
            esc_html(sprintf(__('Select up to %d items.', 'casino-compare-core'), (int) $args['max_items']))
        );
    }

    printf(
        '<p><label for="%1$s"><strong>%2$s</strong></label><br><select class="widefat" id="%1$s" name="%3$s"%4$s%5$s>',
        esc_attr($key),
        esc_html($label),
        esc_attr($multiple ? $key . '[]' : $key),
        $multiple ? ' multiple' : '',
        $max_items_attr
    );

    if (!$multiple) {
        echo '<option value="">' . esc_html__('Select an item', 'casino-compare-core') . '</option>';
    }

    foreach ($posts as $post) {
        printf(
            '<option value="%1$d"%2$s>%3$s</option>',
            (int) $post->ID,
            selected(in_array((string) $post->ID, $selected_values, true), true, false),
            esc_html(get_the_title($post))
        );
    }

    echo '</select>' . $description . '</p>';
}

function ccc_normalize_repeater_subfields(array $subfields): array
{
    $normalized = [];

    foreach ($subfields as $subfield_key => $subfield) {
        if (is_string($subfield)) {
            $normalized[$subfield_key] = [
                'label' => $subfield,
                'type' => 'text',
            ];
            continue;
        }

        if (!is_array($subfield)) {
            continue;
        }

        $normalized_subfield = array_merge([
            'label' => ucfirst(str_replace('_', ' ', (string) $subfield_key)),
            'type' => 'text',
        ], $subfield);

        if (($normalized_subfield['type'] ?? 'text') === 'relation' && !empty($normalized_subfield['post_type'])) {
            $normalized_subfield['options'] = array_map(
                static fn(WP_Post $post): array => [
                    'value' => (int) $post->ID,
                    'label' => get_the_title($post),
                ],
                ccc_get_relation_posts((string) $normalized_subfield['post_type'], $normalized_subfield)
            );
        }

        $normalized[$subfield_key] = $normalized_subfield;
    }

    return $normalized;
}

function ccc_render_repeater_control(string $field_name, $field_value, array $subfield): void
{
    $type = (string) ($subfield['type'] ?? 'text');

    if ($type === 'textarea') {
        printf(
            '<textarea class="widefat" name="%1$s" rows="%2$d" placeholder="%3$s">%4$s</textarea>',
            esc_attr($field_name),
            max(2, (int) ($subfield['rows'] ?? 3)),
            esc_attr((string) ($subfield['label'] ?? '')),
            esc_textarea((string) $field_value)
        );
        return;
    }

    if ($type === 'number') {
        printf(
            '<input class="widefat" type="number" step="%1$s" name="%2$s" value="%3$s" placeholder="%4$s">',
            esc_attr((string) ($subfield['step'] ?? '1')),
            esc_attr($field_name),
            esc_attr((string) $field_value),
            esc_attr((string) ($subfield['label'] ?? ''))
        );
        return;
    }

    if ($type === 'relation' && !empty($subfield['post_type'])) {
        $posts = ccc_get_relation_posts((string) $subfield['post_type'], $subfield);

        printf(
            '<select class="widefat" name="%1$s"><option value="">%2$s</option>',
            esc_attr($field_name),
            esc_html__('Select an item', 'casino-compare-core')
        );

        foreach ($posts as $post) {
            printf(
                '<option value="%1$d"%2$s>%3$s</option>',
                (int) $post->ID,
                selected((int) $field_value === (int) $post->ID, true, false),
                esc_html(get_the_title($post))
            );
        }

        echo '</select>';
        return;
    }

    printf(
        '<input class="widefat" type="text" name="%1$s" value="%2$s" placeholder="%3$s">',
        esc_attr($field_name),
        esc_attr((string) $field_value),
        esc_attr((string) ($subfield['label'] ?? ''))
    );
}

function ccc_render_repeater(string $key, string $label, array $rows = [], array $subfields = []): void
{
    $subfields = ccc_normalize_repeater_subfields($subfields);

    if ($rows === []) {
        $rows = [[]];
    }

    $encoded_subfields = esc_attr(wp_json_encode($subfields));

    echo '<div class="ccc-repeater" data-key="' . esc_attr($key) . '" data-subfields="' . $encoded_subfields . '">';
    echo '<p><strong>' . esc_html($label) . '</strong></p>';
    echo '<div class="ccc-repeater-rows">';

    foreach ($rows as $index => $row) {
        echo '<div class="ccc-repeater-row">';

        foreach ($subfields as $subfield_key => $subfield) {
            $field_name = sprintf('%s[%d][%s]', $key, (int) $index, $subfield_key);
            $field_value = $row[$subfield_key] ?? '';
            ccc_render_repeater_control($field_name, $field_value, $subfield);
        }

        echo '<button type="button" class="button-link-delete ccc-remove-row">Г—</button>';
        echo '</div>';
    }

    echo '</div>';
    echo '<p><button type="button" class="button ccc-add-row">' . esc_html__('Add row', 'casino-compare-core') . '</button></p>';
    echo '</div>';
}

function ccc_limit_relation_value($value, int $max_items)
{
    if (!is_array($value)) {
        return $value;
    }

    return array_slice(array_values(array_unique(array_map('absint', $value))), 0, max(1, $max_items));
}

function ccc_sanitize_repeater_row_value(array $subfield, $value)
{
    $type = (string) ($subfield['type'] ?? 'text');

    if ($type === 'textarea') {
        return sanitize_textarea_field((string) $value);
    }

    if ($type === 'number') {
        return is_numeric($value) ? 0 + $value : 0;
    }

    if ($type === 'relation') {
        return is_numeric($value) ? (int) $value : 0;
    }

    return sanitize_text_field((string) $value);
}

function ccc_sanitize_field(string $type, $value, array $args = [])
{
    switch ($type) {
        case 'text':
            return sanitize_text_field((string) $value);
        case 'textarea':
            return sanitize_textarea_field((string) $value);
        case 'wysiwyg':
            return wp_kses_post((string) $value);
        case 'image':
            if (is_array($value)) {
                return array_map('absint', $value);
            }

            return is_numeric($value) ? (int) $value : 0;
        case 'number':
            if (is_array($value)) {
                return array_map('floatval', $value);
            }

            return is_numeric($value) ? 0 + $value : 0;
        case 'relation':
            if (is_array($value)) {
                $sanitized = array_values(array_filter(array_map('absint', $value)));

                if (!empty($args['max_items'])) {
                    return ccc_limit_relation_value($sanitized, (int) $args['max_items']);
                }

                return $sanitized;
            }

            return is_numeric($value) ? (int) $value : 0;
        case 'boolean':
            return $value ? 1 : 0;
        case 'repeater':
            $subfields = ccc_normalize_repeater_subfields((array) ($args['subfields'] ?? []));
            $sanitized = [];

            foreach ((array) $value as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $clean_row = [];

                foreach ($subfields as $row_key => $subfield) {
                    $clean_key = sanitize_key((string) $row_key);
                    $clean_row[$clean_key] = ccc_sanitize_repeater_row_value($subfield, $row[$row_key] ?? '');
                }

                if (array_filter($clean_row, static fn($item) => $item !== '' && $item !== 0 && $item !== '0')) {
                    $sanitized[] = $clean_row;
                }
            }

            return $sanitized;
        default:
            return $value;
    }
}
