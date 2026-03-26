<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$pros = isset($args['pros']) ? (array) $args['pros'] : [];
$cons = isset($args['cons']) ? (array) $args['cons'] : [];

if (empty($pros) && empty($cons)) {
    return;
}
?>
<div class="pros-cons">
    <?php if (!empty($pros)) : ?>
        <div class="pros-cons__list pros-cons__list--pros">
            <div class="pros-cons__title">&#9989; Avantages</div>
            <?php foreach (array_slice($pros, 0, 5) as $pro) : ?>
                <div class="pros-cons__item"><?php echo esc_html($pro); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($cons)) : ?>
        <div class="pros-cons__list pros-cons__list--cons">
            <div class="pros-cons__title">&#10060; Inconvénients</div>
            <?php foreach (array_slice($cons, 0, 5) as $con) : ?>
                <div class="pros-cons__item"><?php echo esc_html($con); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
