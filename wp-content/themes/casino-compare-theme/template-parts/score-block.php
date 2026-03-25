<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$value = (string) ($args['value'] ?? '');
$label = (string) ($args['label'] ?? '');

if ($value === '' && $label === '') {
    return;
}
?>
<section>
    <?php if ($label !== '') : ?><h2><?php echo esc_html($label); ?></h2><?php endif; ?>
    <?php if ($value !== '') : ?><p><strong><?php echo esc_html($value); ?></strong></p><?php endif; ?>
</section>
