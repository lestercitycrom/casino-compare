<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$faq = $args['faq'] ?? [];
$faq = cct_normalize_repeater($faq);

if (empty($faq)) {
    return;
}
?>
<section class="faq">
    <h2 class="faq__title">Questions fréquentes</h2>
    <?php foreach ($faq as $item) :
        $q = (string) ($item['question'] ?? '');
        $a = (string) ($item['answer'] ?? '');
        if ($q === '') {
            continue;
        }
    ?>
        <div class="faq__item">
            <div class="faq__question"><?php echo esc_html($q); ?></div>
            <div class="faq__answer"><?php echo wp_kses_post(wpautop($a)); ?></div>
        </div>
    <?php endforeach; ?>
</section>
