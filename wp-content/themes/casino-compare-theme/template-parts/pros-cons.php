<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$pros = cct_normalize_repeater($args['pros'] ?? []);
$cons = cct_normalize_repeater($args['cons'] ?? []);

if ($pros === [] && $cons === []) {
    return;
}
?>
<section class="pros-cons">
    <div class="pros-cons__column">
        <?php if ($pros !== []) : ?>
            <h2><?php esc_html_e('Pros', 'casino-compare-theme'); ?></h2>
            <ul>
                <?php foreach ($pros as $row) : ?>
                    <li><?php echo esc_html((string) ($row['text'] ?? '')); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
    <div class="pros-cons__column">
        <?php if ($cons !== []) : ?>
            <h2><?php esc_html_e('Cons', 'casino-compare-theme'); ?></h2>
            <ul>
                <?php foreach ($cons as $row) : ?>
                    <li><?php echo esc_html((string) ($row['text'] ?? '')); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</section>
