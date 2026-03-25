<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$faq_rows = cct_normalize_repeater($args['faq'] ?? []);

if ($faq_rows === []) {
    return;
}
?>
<section>
    <h2><?php esc_html_e('FAQ', 'casino-compare-theme'); ?></h2>
    <?php foreach ($faq_rows as $row) : ?>
        <?php $question = (string) ($row['question'] ?? ''); ?>
        <?php $answer = (string) ($row['answer'] ?? ''); ?>
        <?php if ($question === '' && $answer === '') : continue; endif; ?>
        <article>
            <?php if ($question !== '') : ?><h3><?php echo esc_html($question); ?></h3><?php endif; ?>
            <?php if ($answer !== '') : ?><p><?php echo esc_html($answer); ?></p><?php endif; ?>
        </article>
    <?php endforeach; ?>
</section>
