<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

get_header();

$featured_casinos  = cct_get_top_casinos(3);
$hub_pages         = get_posts([
    'post_type'      => 'landing',
    'post_status'    => 'publish',
    'posts_per_page' => 6,
    'meta_key'       => 'landing_type',
    'meta_value'     => 'hub',
]);
$comparison_pages  = get_posts([
    'post_type'      => 'landing',
    'post_status'    => 'publish',
    'posts_per_page' => 4,
    'meta_key'       => 'landing_type',
    'meta_value'     => 'comparison',
    'orderby'        => 'date',
    'order'          => 'DESC',
]);
$latest_guides     = get_posts([
    'post_type'      => 'guide',
    'post_status'    => 'publish',
    'posts_per_page' => 3,
    'orderby'        => 'date',
    'order'          => 'DESC',
]);
?>
<main>

    <!-- ================================================
         HERO
         ================================================ -->
    <section class="hero-home">
        <div class="site-shell">
            <p class="eyebrow">Comparateur de casinos en ligne</p>
            <h1>Trouvez le meilleur casino en ligne pour vous</h1>
            <p class="hero-home__lead text-soft">Notre équipe teste et compare les meilleurs casinos pour vous aider à faire le bon choix.</p>
            <div class="hero-home__actions">
                <a href="<?php echo esc_url(home_url('/casino-en-ligne/meilleur/')); ?>" class="btn-primary">Voir tous les casinos</a>
                <a href="<?php echo esc_url(home_url('/comparer/')); ?>" class="btn-outline" id="ccc-compare-badge-home">Comparer (0)</a>
            </div>
        </div>
    </section>

    <!-- ================================================
         TOP CASINOS
         ================================================ -->
    <?php if ($featured_casinos) : ?>
    <section class="homepage-section">
        <div class="site-shell">
            <div class="section-header">
                <p class="eyebrow">Meilleurs avis</p>
                <h2>Top casinos par notre équipe</h2>
            </div>
            <div class="card-grid card-grid--3">
                <?php foreach ($featured_casinos as $i => $casino) : ?>
                    <?php get_template_part('template-parts/casino-card', null, [
                        'casino_id' => $casino->ID,
                        'rank'      => (string) ($i + 1),
                    ]); ?>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- ================================================
         HOW IT WORKS
         ================================================ -->
    <section class="homepage-section homepage-section--alt">
        <div class="site-shell">
            <div class="section-header">
                <p class="eyebrow">Fonctionnement</p>
                <h2>Comment fonctionne notre comparateur</h2>
            </div>
            <div class="how-steps">
                <div class="how-step">
                    <div class="how-step__number">1</div>
                    <div class="how-step__title">Définissez vos critères</div>
                    <p class="how-step__desc">Bonus, méthodes de paiement, types de jeux...</p>
                </div>
                <div class="how-step">
                    <div class="how-step__number">2</div>
                    <div class="how-step__title">Comparez les casinos</div>
                    <p class="how-step__desc">Nos experts ont testé chaque plateforme pour vous.</p>
                </div>
                <div class="how-step">
                    <div class="how-step__number">3</div>
                    <div class="how-step__title">Choisissez votre casino</div>
                    <p class="how-step__desc">Profitez des meilleurs bonus du marché.</p>
                </div>
                <div class="how-step">
                    <div class="how-step__number">4</div>
                    <div class="how-step__title">Jouez en sécurité</div>
                    <p class="how-step__desc">Toutes nos recommandations sont licenciées et vérifiées.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ================================================
         CATEGORIES
         ================================================ -->
    <?php if ($hub_pages || $comparison_pages) : ?>
    <section class="homepage-section">
        <div class="site-shell">
            <div class="section-header">
                <p class="eyebrow">Explorer</p>
                <h2>Toutes les catégories</h2>
            </div>
            <div class="category-grid">
                <?php foreach ($hub_pages as $hub) : ?>
                    <a href="<?php echo esc_url(get_permalink($hub)); ?>" class="category-card">
                        <div class="category-card__title"><?php echo esc_html(get_the_title($hub)); ?></div>
                    </a>
                <?php endforeach; ?>
                <?php foreach ($comparison_pages as $page) : ?>
                    <a href="<?php echo esc_url(get_permalink($page)); ?>" class="category-card">
                        <div class="category-card__title"><?php echo esc_html(get_the_title($page)); ?></div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- ================================================
         GUIDES
         ================================================ -->
    <?php if ($latest_guides) : ?>
    <section class="homepage-section homepage-section--alt">
        <div class="site-shell">
            <div class="section-header">
                <p class="eyebrow">Guides</p>
                <h2>Guides &amp; conseils</h2>
            </div>
            <div class="card-grid card-grid--3">
                <?php foreach ($latest_guides as $guide) : ?>
                    <article class="content-panel">
                        <p class="eyebrow"><?php echo esc_html((string) cct_get_meta('reading_time', $guide->ID)); ?> min</p>
                        <h3 style="margin: 8px 0;">
                            <a href="<?php echo esc_url(get_permalink($guide)); ?>"><?php echo esc_html(get_the_title($guide)); ?></a>
                        </h3>
                        <p class="text-soft" style="font-size:0.875rem">
                            <?php echo esc_html(wp_trim_words((string) cct_get_meta('intro_text', $guide->ID), 20)); ?>
                        </p>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- ================================================
         STATS BAND
         ================================================ -->
    <section class="stats-band">
        <div class="site-shell">
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-item__number">15</div>
                    <div class="stat-item__label">Casinos testés</div>
                </div>
                <div class="stat-item">
                    <div class="stat-item__number">216</div>
                    <div class="stat-item__label">Pages d'avis</div>
                </div>
                <div class="stat-item">
                    <div class="stat-item__number">52</div>
                    <div class="stat-item__label">Pages comparatives</div>
                </div>
                <div class="stat-item">
                    <div class="stat-item__number">11</div>
                    <div class="stat-item__label">Guides experts</div>
                </div>
            </div>
        </div>
    </section>

</main>
<?php
get_footer();
