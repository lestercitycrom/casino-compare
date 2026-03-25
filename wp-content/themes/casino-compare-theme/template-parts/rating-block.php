<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$overall = (string) ($args['overall'] ?? '');
$metrics = is_array($args['metrics'] ?? null) ? $args['metrics'] : [];

if ($overall === '' && $metrics === []) {
    return;
}
?>
<section>
    <?php if ($overall !== '') : ?>
        <p><strong><?php esc_html_e('Overall rating', 'casino-compare-theme'); ?>:</strong> <?php echo esc_html($overall); ?></p>
    <?php endif; ?>
    <?php if ($metrics !== []) : ?>
        <ul>
            <?php foreach ($metrics as $label => $value) : ?>
                <?php if ((string) $value === '') : continue; endif; ?>
                <li><?php echo esc_html((string) $label . ': ' . (string) $value); ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
