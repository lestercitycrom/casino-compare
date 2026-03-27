<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>
<main class="site-shell" style="padding:80px 0 120px;text-align:center">

    <p class="eyebrow">Erreur 404</p>
    <h1 style="font-size:clamp(2rem,5vw,3.5rem);margin:16px 0 24px">Page introuvable</h1>
    <p class="text-soft" style="max-width:520px;margin:0 auto 40px;font-size:1.05rem;line-height:1.6">
        La page que vous cherchez n'existe pas ou a été déplacée.<br>
        Utilisez la navigation pour trouver ce dont vous avez besoin.
    </p>

    <div style="display:flex;gap:16px;justify-content:center;flex-wrap:wrap;margin-bottom:64px">
        <a href="<?php echo esc_url(home_url('/')); ?>" class="btn-primary">Accueil</a>
        <a href="<?php echo esc_url(home_url('/casino-en-ligne/meilleur/')); ?>" class="btn-outline">Tous les casinos</a>
        <a href="<?php echo esc_url(home_url('/comparer/')); ?>" class="btn-outline">Comparer</a>
    </div>

    <?php
    $featured = cct_get_top_casinos(3);
    if ($featured !== []) :
    ?>
    <section style="max-width:900px;margin:0 auto;text-align:left">
        <h2 style="font-size:1.15rem;margin-bottom:24px;text-align:center">
            Casinos les mieux notés
        </h2>
        <div class="card-grid card-grid--3">
            <?php foreach ($featured as $index => $casino) : ?>
                <?php get_template_part('template-parts/casino-card', null, [
                    'casino_id' => $casino->ID,
                    'rank'      => (string) ($index + 1),
                ]); ?>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

</main>
<?php
get_footer();
