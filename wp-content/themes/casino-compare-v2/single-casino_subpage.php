<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

get_header();

$sp_id         = get_the_ID();
$casino_id     = (int) get_post_meta($sp_id, 'parent_casino', true);
$subtype       = (string) get_post_meta($sp_id, 'subpage_type', true);
$casino_title  = get_the_title($casino_id);
$casino_slug   = get_post_field('post_name', $casino_id);
$hero_title    = (string) (get_post_meta($sp_id, 'hero_title', true) ?: get_the_title());
$intro         = get_post_meta($sp_id, 'intro_text', true);
$main_content  = get_post_meta($sp_id, 'main_content', true);
$cta_text      = (string) get_post_meta($sp_id, 'cta_text', true);
$cta_url       = (string) get_post_meta($sp_id, 'cta_url', true);
$table_enabled = (bool) get_post_meta($sp_id, 'table_enabled', true);
$table_headers = cct_normalize_repeater(get_post_meta($sp_id, 'table_headers', true));
$table_rows    = cct_normalize_repeater(get_post_meta($sp_id, 'table_rows', true));
$score_enabled = (bool) get_post_meta($sp_id, 'score_enabled', true);
$score_value   = (string) get_post_meta($sp_id, 'score_value', true);
$score_verdict = (string) get_post_meta($sp_id, 'score_verdict', true);
$faq                = cct_get_meta('faq', $sp_id, []);
$architecture_links = cct_normalize_link_rows(cct_get_meta('architecture_links', $sp_id, []));
$parent_link        = (string) (get_post_meta($sp_id, 'parent_review_link', true) ?: home_url('/avis/' . $casino_slug . '/'));

$subpage_types = [
    'bonus'           => 'Bonus',
    'bonus-sans-depot'=> 'Sans dépôt',
    'bonus-bienvenue' => 'Bienvenue',
    'free-spins'      => 'Free Spins',
    'fiable'          => 'Fiabilité',
    'arnaque'         => 'Arnaque',
    'licence'         => 'Licence',
    'retrait'         => 'Retrait',
    'inscription'     => 'Inscription',
    'connexion'       => 'Connexion',
    'jeux'            => 'Jeux',
    'live'            => 'Live',
];
?>
<main class="site-shell">

    <?php get_template_part('template-parts/breadcrumb'); ?>

    <!-- Back link -->
    <a href="<?php echo esc_url($parent_link); ?>" class="back-link">
        &#8592; Retour à l'avis <?php echo esc_html($casino_title); ?>
    </a>

    <!-- Subpage nav pills -->
    <nav class="subpage-nav" aria-label="<?php esc_attr_e('Navigation sous-pages', 'casino-compare-v2'); ?>">
        <?php foreach ($subpage_types as $type_slug => $type_label) :
            $is_active = ($subtype === str_replace('-', '_', $type_slug));
        ?>
            <a href="<?php echo esc_url(home_url('/avis/' . $casino_slug . '/' . $type_slug . '/')); ?>"
               class="subpage-nav__link<?php echo $is_active ? ' is-active' : ''; ?>">
                <?php echo esc_html($type_label); ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <h1><?php echo esc_html($hero_title); ?></h1>

    <?php if ($intro) : ?>
        <div class="content-panel" style="margin-top:20px"><?php echo wp_kses_post(wpautop($intro)); ?></div>
    <?php endif; ?>

    <!-- Bonus table -->
    <?php if ($table_enabled && $table_headers !== []) : ?>
        <div class="content-panel" style="margin-top:20px">
            <table class="bonus-table">
                <thead>
                    <tr>
                        <?php foreach ($table_headers as $h) : ?>
                            <th><?php echo esc_html((string) ($h['label'] ?? '')); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($table_rows as $row) : ?>
                        <tr>
                            <?php foreach ($row['cells'] ?? [] as $cell) : ?>
                                <td><?php echo esc_html((string) $cell); ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <?php if ($main_content) : ?>
        <div class="content-section" style="margin-top:24px"><?php echo wp_kses_post($main_content); ?></div>
    <?php endif; ?>

    <?php if ($score_enabled && $score_value !== '') : ?>
        <div class="score-block" style="margin-top:24px">
            <div class="score-block__value"><?php echo esc_html($score_value); ?></div>
            <?php if ($score_verdict !== '') : ?>
                <div class="score-block__verdict"><?php echo esc_html($score_verdict); ?></div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($cta_text !== '' && $cta_url !== '') : ?>
        <?php get_template_part('template-parts/cta-block', null, ['text' => $cta_text, 'url' => $cta_url]); ?>
    <?php endif; ?>

    <?php if ($architecture_links !== []) : ?>
        <div class="content-panel" style="margin-top:24px">
            <h3 style="margin-bottom:12px;font-size:0.875rem;text-transform:uppercase;letter-spacing:0.05em">Liens associés</h3>
            <ul style="display:flex;flex-direction:column;gap:8px">
                <?php foreach ($architecture_links as $link) : ?>
                    <li>
                        <a href="<?php echo esc_url((string) $link['url']); ?>">
                            &#8594; <?php echo esc_html((string) $link['label']); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php get_template_part('template-parts/faq-block', null, ['faq' => $faq]); ?>

</main>
<?php
get_footer();
