<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$rows  = isset($args['rows'])  ? (array) $args['rows']  : [];
$title = isset($args['title']) ? (string) $args['title'] : '';

if (empty($rows)) {
    return;
}
?>
<div class="info-table">
    <?php if ($title !== '') : ?>
        <div class="info-table__heading" style="padding:12px 16px;font-weight:700;font-size:0.875rem;border-bottom:1px solid #334155">
            <?php echo esc_html($title); ?>
        </div>
    <?php endif; ?>
    <?php foreach ($rows as $label => $value) :
        if (empty($value)) {
            continue;
        }
    ?>
        <div class="info-table__row">
            <span class="info-table__label"><?php echo esc_html((string) $label); ?></span>
            <span class="info-table__value"><?php echo esc_html((string) $value); ?></span>
        </div>
    <?php endforeach; ?>
</div>
