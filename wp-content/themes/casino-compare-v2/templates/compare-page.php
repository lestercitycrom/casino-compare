<?php
/**
 * Template Name: Compare Page
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>
<main class="site-shell compare-page">
    <article class="compare-page__article">

        <div class="section-header" style="text-align:center;padding:40px 0 32px">
            <p class="eyebrow">Outil de comparaison</p>
            <h1><?php the_title(); ?></h1>
            <p class="text-soft" style="max-width:600px;margin:16px auto 0;font-size:0.95rem">
                Construisez une vue côte à côte de jusqu'à 3 casinos. Ajoutez des casinos depuis les pages d'avis, les cartes de comparaison ou les sidebars des guides, puis revenez ici pour comparer les critères clés.
            </p>
        </div>

        <div class="compare-page__panel content-panel" style="margin-bottom:60px">
            <div id="ccc-compare-app"></div>
        </div>

    </article>
</main>
<?php
get_footer();
