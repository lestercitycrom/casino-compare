<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$parts   = [];
$parts[] = '<span><a href="' . esc_url(home_url('/')) . '">Accueil</a></span>';

if (is_singular('casino')) {
    $parts[] = '<span><a href="' . esc_url(home_url('/casino-en-ligne/')) . '">Casinos</a></span>';
    $parts[] = '<span>' . esc_html(get_the_title()) . '</span>';
} elseif (is_singular('casino_subpage')) {
    $casino_id   = (int) get_post_meta(get_the_ID(), 'parent_casino', true);
    $casino_slug = get_post_field('post_name', $casino_id);
    $parts[]     = '<span><a href="' . esc_url(home_url('/casino-en-ligne/')) . '">Casinos</a></span>';
    $parts[]     = '<span><a href="' . esc_url(home_url('/avis/' . $casino_slug . '/')) . '">' . esc_html(get_the_title($casino_id)) . '</a></span>';
    $parts[]     = '<span>' . esc_html(get_the_title()) . '</span>';
} elseif (is_singular('guide')) {
    $parts[] = '<span><a href="' . esc_url(home_url('/guide/')) . '">Guides</a></span>';
    $parts[] = '<span>' . esc_html(get_the_title()) . '</span>';
} elseif (is_singular('landing')) {
    $parts[] = '<span>' . esc_html(get_the_title()) . '</span>';
}

if (count($parts) > 1) :
?>
<nav class="breadcrumb" aria-label="<?php esc_attr_e('Fil d\'Ariane', 'casino-compare-v2'); ?>">
    <?php echo implode('', $parts); ?>
</nav>
<?php
endif;
