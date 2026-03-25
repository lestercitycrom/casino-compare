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

    wp_enqueue_style(
        'ccc-admin-fields',
        CCC_PLUGIN_URL . 'assets/css/admin-fields.css',
        [],
        CCC_VERSION
    );

    wp_enqueue_script(
        'ccc-admin-fields',
        CCC_PLUGIN_URL . 'assets/js/admin-fields.js',
        [],
        CCC_VERSION,
        true
    );
}
add_action('admin_enqueue_scripts', 'ccc_enqueue_admin_field_assets');

function ccc_admin_body_class(string $classes): string
{
    $screen = get_current_screen();

    if (!$screen instanceof WP_Screen) {
        return $classes;
    }

    if (!in_array((string) $screen->post_type, ['casino', 'casino_subpage', 'landing', 'guide'], true)) {
        return $classes;
    }

    return trim($classes . ' ccc-admin-screen');
}
add_filter('admin_body_class', 'ccc_admin_body_class');

function ccc_render_meta_box_nonce(string $action, string $name): void
{
    static $rendered = [];

    $key = $action . '|' . $name;

    if (isset($rendered[$key])) {
        return;
    }

    wp_nonce_field($action, $name);
    $rendered[$key] = true;
}

function ccc_get_meta_value(int $post_id, string $key, $default = '')
{
    $value = get_post_meta($post_id, $key, true);

    if ($value === '' || $value === null) {
        return $default;
    }

    return $value;
}

function ccc_get_field_layout(string $type, array $args = []): string
{
    if (!empty($args['layout'])) {
        return (string) $args['layout'];
    }

    return match ($type) {
        'textarea', 'wysiwyg', 'image', 'repeater' => 'full',
        'boolean' => 'inline',
        default => 'half',
    };
}

function ccc_get_field_classes(string $type, array $args = []): string
{
    $classes = [
        'ccc-admin-field',
        'ccc-admin-field--' . sanitize_html_class($type),
        'ccc-admin-field--' . sanitize_html_class(ccc_get_field_layout($type, $args)),
    ];

    if (!empty($args['class'])) {
        $classes[] = sanitize_html_class((string) $args['class']);
    }

    return implode(' ', array_filter($classes));
}

function ccc_get_field_description_html(array $args = []): string
{
    if (empty($args['description'])) {
        return '';
    }

    return '<p class="description">' . esc_html((string) $args['description']) . '</p>';
}

function ccc_render_field_wrapper_start(string $key, string $type, string $label, array $args = []): void
{
    echo '<div class="' . esc_attr(ccc_get_field_classes($type, $args)) . '">';

    if ($type !== 'boolean') {
        echo '<label class="ccc-admin-field__label" for="' . esc_attr($key) . '"><strong>' . esc_html($label) . '</strong></label>';
    }
}

function ccc_render_field_wrapper_end(array $args = []): void
{
    echo ccc_get_field_description_html($args);
    echo '</div>';
}

function ccc_admin_grid_open(string $class = ''): void
{
    $classes = trim('ccc-admin-grid ' . $class);
    echo '<div class="' . esc_attr($classes) . '">';
}

function ccc_admin_grid_close(): void
{
    echo '</div>';
}

function ccc_render_text(string $key, string $label, string $value = '', array $args = []): void
{
    $placeholder = (string) ($args['placeholder'] ?? '');

    ccc_render_field_wrapper_start($key, 'text', $label, $args);
    printf(
        '<input class="widefat" type="text" id="%1$s" name="%1$s" value="%2$s" placeholder="%3$s">',
        esc_attr($key),
        esc_attr($value),
        esc_attr($placeholder)
    );
    ccc_render_field_wrapper_end($args);
}

function ccc_render_textarea(string $key, string $label, string $value = '', array $args = []): void
{
    $rows = (int) ($args['rows'] ?? 4);

    ccc_render_field_wrapper_start($key, 'textarea', $label, $args);
    printf(
        '<textarea class="widefat" id="%1$s" name="%1$s" rows="%2$d">%3$s</textarea>',
        esc_attr($key),
        max(2, $rows),
        esc_textarea($value)
    );
    ccc_render_field_wrapper_end($args);
}

function ccc_render_select(string $key, string $label, array $options, $value = '', array $args = []): void
{
    ccc_render_field_wrapper_start($key, 'select', $label, $args);
    echo '<select class="widefat" id="' . esc_attr($key) . '" name="' . esc_attr($key) . '">';

    if (!empty($args['placeholder'])) {
        echo '<option value="">' . esc_html((string) $args['placeholder']) . '</option>';
    }

    foreach ($options as $option_value => $option_label) {
        echo '<option value="' . esc_attr((string) $option_value) . '"' . selected((string) $value, (string) $option_value, false) . '>' . esc_html((string) $option_label) . '</option>';
    }

    echo '</select>';
    ccc_render_field_wrapper_end($args);
}

function ccc_render_wysiwyg(string $key, string $label, string $value = '', array $args = []): void
{
    ccc_render_field_wrapper_start($key, 'wysiwyg', $label, $args);

    wp_editor($value, $key, [
        'textarea_name' => $key,
        'textarea_rows' => (int) ($args['rows'] ?? 6),
        'media_buttons' => false,
    ]);

    ccc_render_field_wrapper_end($args);
}

function ccc_render_number(string $key, string $label, $value = '', array $args = []): void
{
    $step = (string) ($args['step'] ?? '1');
    $min = isset($args['min']) ? ' min="' . esc_attr((string) $args['min']) . '"' : '';
    $max = isset($args['max']) ? ' max="' . esc_attr((string) $args['max']) . '"' : '';

    ccc_render_field_wrapper_start($key, 'number', $label, $args);
    printf(
        '<input class="widefat" type="number" step="%1$s"%2$s%3$s id="%4$s" name="%4$s" value="%5$s">',
        esc_attr($step),
        $min,
        $max,
        esc_attr($key),
        esc_attr((string) $value)
    );
    ccc_render_field_wrapper_end($args);
}

function ccc_render_image(string $key, string $label, $value = 0, array $args = []): void
{
    $attachment_id = absint((string) $value);
    $preview = $attachment_id ? wp_get_attachment_image($attachment_id, 'thumbnail', false, ['style' => 'display:block;']) : '';

    ccc_render_field_wrapper_start($key, 'image', $label, $args);
    echo '<div class="ccc-image-field">';
    echo '<div class="ccc-image-preview">' . $preview . '</div>';
    echo '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr((string) $attachment_id) . '">';
    echo '<button type="button" class="button button-secondary ccc-media-button" data-ccc-media-target="' . esc_attr($key) . '">' . esc_html__('Select image', 'casino-compare-core') . '</button>';
    echo '</div>';
    ccc_render_field_wrapper_end($args);
}

function ccc_render_boolean(string $key, string $label, bool $checked = false, array $args = []): void
{
    ccc_render_field_wrapper_start($key, 'boolean', $label, $args);
    printf(
        '<label class="ccc-admin-toggle"><input type="checkbox" id="%1$s" name="%1$s" value="1"%2$s><span>%3$s</span></label>',
        esc_attr($key),
        checked($checked, true, false),
        esc_html($label)
    );
    ccc_render_field_wrapper_end($args);
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
    $max_items_attr = '';

    if (!empty($args['max_items'])) {
        $max_items_attr = ' data-ccc-max-items="' . esc_attr((string) (int) $args['max_items']) . '"';
        $args['description'] = trim(((string) ($args['description'] ?? '')) . ' ' . sprintf(__('Select up to %d items.', 'casino-compare-core'), (int) $args['max_items']));
    }

    ccc_render_field_wrapper_start($key, 'relation', $label, $args);
    printf(
        '<select class="widefat" id="%1$s" name="%2$s"%3$s%4$s>',
        esc_attr($key),
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

    echo '</select>';
    ccc_render_field_wrapper_end($args);
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
            'layout' => 'full',
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
    $layout = (string) ($subfield['layout'] ?? 'full');

    echo '<div class="ccc-repeater-cell ccc-repeater-cell--' . esc_attr($layout) . ' ccc-repeater-cell--' . esc_attr($type) . '">';
    echo '<label class="ccc-repeater-cell__label">' . esc_html((string) ($subfield['label'] ?? '')) . '</label>';

    if ($type === 'textarea') {
        printf(
            '<textarea class="widefat" name="%1$s" rows="%2$d" placeholder="%3$s">%4$s</textarea>',
            esc_attr($field_name),
            max(2, (int) ($subfield['rows'] ?? 3)),
            esc_attr((string) ($subfield['label'] ?? '')),
            esc_textarea((string) $field_value)
        );
        echo '</div>';
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
        echo '</div>';
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
        echo '</div>';
        return;
    }

    printf(
        '<input class="widefat" type="text" name="%1$s" value="%2$s" placeholder="%3$s">',
        esc_attr($field_name),
        esc_attr((string) $field_value),
        esc_attr((string) ($subfield['label'] ?? ''))
    );
    echo '</div>';
}

function ccc_render_repeater(string $key, string $label, array $rows = [], array $subfields = [], array $args = []): void
{
    $subfields = ccc_normalize_repeater_subfields($subfields);

    if ($rows === []) {
        $rows = [[]];
    }

    $encoded_subfields = esc_attr(wp_json_encode($subfields));

    ccc_render_field_wrapper_start($key, 'repeater', $label, $args);
    echo '<div class="ccc-repeater" data-key="' . esc_attr($key) . '" data-subfields="' . $encoded_subfields . '">';
    echo '<div class="ccc-repeater-rows">';

    foreach ($rows as $index => $row) {
        echo '<div class="ccc-repeater-row">';
        echo '<div class="ccc-repeater-row__fields">';

        foreach ($subfields as $subfield_key => $subfield) {
            $field_name = sprintf('%s[%d][%s]', $key, (int) $index, $subfield_key);
            $field_value = $row[$subfield_key] ?? '';
            ccc_render_repeater_control($field_name, $field_value, $subfield);
        }

        echo '</div>';
        echo '<button type="button" class="button-link-delete ccc-remove-row" aria-label="' . esc_attr__('Remove row', 'casino-compare-core') . '">' . esc_html__('Remove', 'casino-compare-core') . '</button>';
        echo '</div>';
    }

    echo '</div>';
    echo '<p class="ccc-repeater__actions"><button type="button" class="button button-secondary ccc-add-row">' . esc_html__('Add row', 'casino-compare-core') . '</button></p>';
    echo '</div>';
    ccc_render_field_wrapper_end($args);
}

function ccc_render_fields_collection(array $fields, int $post_id): void
{
    ccc_admin_grid_open();

    foreach ($fields as $field) {
        $key = $field['key'];
        $value = ccc_get_meta_value($post_id, $key, $field['type'] === 'repeater' ? [] : '');

        switch ($field['type']) {
            case 'text':
                ccc_render_text($key, $field['label'], (string) $value, $field);
                break;
            case 'textarea':
                ccc_render_textarea($key, $field['label'], (string) $value, $field);
                break;
            case 'select':
                ccc_render_select($key, $field['label'], (array) ($field['options'] ?? []), $value, $field);
                break;
            case 'wysiwyg':
                ccc_render_wysiwyg($key, $field['label'], (string) $value, $field);
                break;
            case 'image':
                ccc_render_image($key, $field['label'], $value, $field);
                break;
            case 'number':
                ccc_render_number($key, $field['label'], $value, $field);
                break;
            case 'boolean':
                ccc_render_boolean($key, $field['label'], (bool) $value, $field);
                break;
            case 'relation':
                ccc_render_relation($key, $field['label'], (string) ($field['post_type'] ?? ''), $value, $field);
                break;
            case 'repeater':
                ccc_render_repeater($key, $field['label'], is_array($value) ? $value : [], (array) ($field['subfields'] ?? []), $field);
                break;
        }
    }

    ccc_admin_grid_close();
}

function ccc_expand_field_map(array $field_map): array
{
    $fields = [];

    foreach ($field_map as $key => $field) {
        if (!is_array($field)) {
            continue;
        }

        $field['key'] = $field['key'] ?? (string) $key;
        $fields[] = $field;
    }

    return $fields;
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
        case 'select':
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
