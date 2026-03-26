<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$value = (string) ($args['value'] ?? '');
$label = (string) ($args['label'] ?? '');
$verdict = (string) ($args['verdict'] ?? '');

if ($value === '' && $label === '' && $verdict === '') {
    return;
}
?>
<section class="score-block">
    <?php if ($label !== '') : ?><h2><?php echo esc_html($label); ?></h2><?php endif; ?>
    <?php if ($value !== '') : ?><p class="score-block__value"><strong><?php echo esc_html($value); ?></strong></p><?php endif; ?>
    <?php if ($verdict !== '') : ?><p class="score-block__verdict"><?php echo esc_html($verdict); ?></p><?php endif; ?>
</section>
