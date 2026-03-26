<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$score = isset($args['score']) ? (string) $args['score'] : '';
$stars = isset($args['stars']) ? (int)    $args['stars'] : 4;
$label = isset($args['label']) ? (string) $args['label'] : '';

if ($score === '') {
    return;
}

$max       = 5;
$filled    = min($stars, $max);
$empty     = $max - $filled;
$stars_html= str_repeat('&#9733;', $filled) . str_repeat('&#9734;', $empty);
?>
<div class="rating-block">
    <span class="rating-block__score"><?php echo esc_html($score); ?></span>
    <div>
        <span class="rating-block__stars"><?php echo $stars_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
        <?php if ($label !== '') : ?>
            <div style="font-size:0.75rem;color:#64748b;margin-top:2px"><?php echo esc_html($label); ?></div>
        <?php endif; ?>
    </div>
</div>
