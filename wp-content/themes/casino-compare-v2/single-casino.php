<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

get_header();

$casino_id     = get_the_ID();
$title         = get_the_title();
$logo_id       = (int) get_post_meta($casino_id, 'logo', true);
$bonus         = get_post_meta($casino_id, 'welcome_bonus_text', true);
$rating        = get_post_meta($casino_id, 'overall_rating', true);
$free_spins    = get_post_meta($casino_id, 'free_spins', true);
$promo_code    = get_post_meta($casino_id, 'promo_code', true);
$affiliate_link= get_post_meta($casino_id, 'affiliate_link', true);
$intro         = get_post_meta($casino_id, 'intro_text', true);
$faq           = cct_get_meta('faq', $casino_id, []);
$pros          = cct_repeater_text_list(get_post_meta($casino_id, 'pros', true), 'text');
$cons          = cct_repeater_text_list(get_post_meta($casino_id, 'cons', true), 'text');
$verdict       = get_post_meta($casino_id, 'final_verdict', true);
$casino_slug   = get_post_field('post_name', $casino_id);

$author_name  = (string) get_post_meta($casino_id, 'author_name', true);
$last_updated = (string) get_post_meta($casino_id, 'last_updated', true);

// Info table fields
$info = array_filter([
    'Licence'     => get_post_meta($casino_id, 'license', true),
    'Wager'       => get_post_meta($casino_id, 'wagering', true),
    'Dépôt min.'  => get_post_meta($casino_id, 'min_deposit', true),
    'Jeux'        => get_post_meta($casino_id, 'games_count', true),
    'Support'     => get_post_meta($casino_id, 'support_channels', true),
    'Application' => get_post_meta($casino_id, 'mobile_app', true),
    'VIP'         => get_post_meta($casino_id, 'vip', true),
]);

// Subpage navigation
$subpage_types  = ['bonus', 'bonus-sans-depot', 'bonus-bienvenue', 'free-spins', 'fiable', 'arnaque', 'licence', 'retrait', 'inscription', 'connexion', 'jeux', 'live'];
$subpage_labels = [
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
<main class="site-shell single-casino-page">

    <?php get_template_part('template-parts/breadcrumb'); ?>

    <!-- ================================================
         HERO
         ================================================ -->
    <header class="single-hero">
        <div class="single-hero__logo">
            <?php if ($logo_id > 0) : ?>
                <?php echo wp_get_attachment_image($logo_id, 'medium', false, ['class' => 'casino-logo-img']); ?>
            <?php else : ?>
                <span class="casino-card__logo-placeholder"><?php echo esc_html(mb_strtoupper(mb_substr($title, 0, 2))); ?></span>
            <?php endif; ?>
        </div>
        <div class="single-hero__info">
            <h1><?php echo esc_html($title); ?></h1>
            <?php if ($rating) : ?>
                <?php
                $stars_full  = (int) round((float) $rating / 2);
                $stars_full  = max(0, min(5, $stars_full));
                $stars_empty = 5 - $stars_full;
                ?>
                <div class="rating-block">
                    <span class="rating-block__score"><?php echo esc_html($rating); ?></span>
                    <span class="rating-block__stars">
                        <?php echo str_repeat('&#9733;', $stars_full) . str_repeat('&#9734;', $stars_empty); ?>
                    </span>
                </div>
            <?php endif; ?>
            <div class="bonus-tags">
                <?php if ($bonus) : ?>
                    <span class="bonus-tag">🎁 <?php echo esc_html($bonus); ?></span>
                <?php endif; ?>
                <?php if ($free_spins) : ?>
                    <span class="bonus-tag">🎰 <?php echo esc_html($free_spins); ?> FS</span>
                <?php endif; ?>
                <?php if ($promo_code) : ?>
                    <span class="bonus-tag">🏷 <?php echo esc_html($promo_code); ?></span>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- ================================================
         SUBPAGE NAV
         ================================================ -->
    <nav class="subpage-nav" aria-label="<?php esc_attr_e('Sous-pages', 'casino-compare-v2'); ?>">
        <span class="subpage-nav__label">Pages :</span>
        <?php foreach ($subpage_types as $type) : ?>
            <a href="<?php echo esc_url(home_url('/avis/' . $casino_slug . '/' . $type . '/')); ?>" class="subpage-nav__link">
                <?php echo esc_html($subpage_labels[$type] ?? $type); ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <!-- ================================================
         2-COLUMN LAYOUT
         ================================================ -->
    <div class="casino-layout">

        <div class="casino-layout__main">

            <?php if ($intro) : ?>
                <div class="content-panel"><?php echo wp_kses_post(wpautop($intro)); ?></div>
            <?php endif; ?>

            <?php get_template_part('template-parts/pros-cons', null, ['pros' => $pros, 'cons' => $cons]); ?>

            <?php for ($n = 1; $n <= 5; $n++) :
                $st = get_post_meta($casino_id, "summary_{$n}_title", true);
                $sc = get_post_meta($casino_id, "summary_{$n}", true);
                if ($st || $sc) : ?>
                    <div class="content-section">
                        <?php if ($st) : ?><h2><?php echo esc_html($st); ?></h2><?php endif; ?>
                        <?php if ($sc) : echo wp_kses_post($sc); endif; ?>
                    </div>
            <?php endif; endfor; ?>

            <?php if ($verdict) : ?>
                <div class="content-panel content-panel--verdict"><?php echo wp_kses_post($verdict); ?></div>
            <?php endif; ?>

            <?php get_template_part('template-parts/faq-block', null, ['faq' => $faq]); ?>

        </div><!-- .casino-layout__main -->

        <aside class="casino-layout__aside">

            <?php if ($affiliate_link) : ?>
                <a href="<?php echo esc_url($affiliate_link); ?>" class="btn-primary btn-block" target="_blank" rel="nofollow noopener">
                    Jouer sur <?php echo esc_html($title); ?>
                </a>
            <?php endif; ?>

            <?php if ($info) : ?>
                <div class="info-table" style="margin-top:20px">
                    <?php foreach ($info as $label => $value) : ?>
                        <div class="info-table__row">
                            <span class="info-table__label"><?php echo esc_html($label); ?></span>
                            <span class="info-table__value"><?php echo esc_html($value); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <button type="button" class="btn-outline btn-block" style="margin-top:12px" data-ccc-compare-id="<?php echo esc_attr((string) $casino_id); ?>">
                Comparer
            </button>

            <?php if ($author_name !== '' || $last_updated !== '') : ?>
                <div class="meta-badges" style="margin-top:16px">
                    <?php if ($author_name !== '') : ?>
                        <span class="meta-badge"><?php echo esc_html($author_name); ?></span>
                    <?php endif; ?>
                    <?php if ($last_updated !== '') : ?>
                        <span class="meta-badge"><?php echo esc_html($last_updated); ?></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        </aside>

    </div><!-- .casino-layout -->

</main>
<?php
get_footer();
