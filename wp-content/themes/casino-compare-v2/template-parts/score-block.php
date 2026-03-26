<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$value   = isset($args['value'])   ? (string) $args['value']   : '';
$label   = isset($args['label'])   ? (string) $args['label']   : '';
$verdict = isset($args['verdict']) ? (string) $args['verdict'] : '';

if ($value === '') {
    return;
}
?>
<div class="score-block">
    <div class="score-block__value"><?php echo esc_html($value); ?></div>
    <?php if ($label !== '') : ?>
        <div class="score-block__label"><?php echo esc_html($label); ?></div>
    <?php endif; ?>
    <?php if ($verdict !== '') : ?>
        <div class="score-block__verdict"><?php echo esc_html($verdict); ?></div>
    <?php endif; ?>
</div>
