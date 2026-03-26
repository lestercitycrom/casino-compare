<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$links = isset($args['links']) ? (array) $args['links'] : [];
$links = cct_normalize_link_rows($links);

if (empty($links)) {
    return;
}
?>
<nav class="internal-links" aria-label="<?php esc_attr_e('Liens internes', 'casino-compare-v2'); ?>">
    <div class="filter-pill">
        <?php foreach ($links as $link) : ?>
            <a href="<?php echo esc_url((string) $link['url']); ?>">
                <?php echo esc_html((string) $link['label']); ?>
            </a>
        <?php endforeach; ?>
    </div>
</nav>
