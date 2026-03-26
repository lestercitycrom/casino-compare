<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}
?>
</div><!-- #page-wrap -->
<footer class="site-footer">
    <div class="site-shell">
        <?php if (has_nav_menu('footer')) : ?>
        <div class="site-footer__nav-wrap">
            <?php
            wp_nav_menu([
                'theme_location' => 'footer',
                'container'      => false,
                'menu_class'     => 'site-footer__links site-footer__links--nav',
                'depth'          => 1,
            ]);
            ?>
        </div>
        <?php else : ?>
        <div class="site-footer__grid">
            <div>
                <div class="site-footer__heading">Casinos</div>
                <ul class="site-footer__links">
                    <li><a href="<?php echo esc_url(home_url('/casino-en-ligne/meilleur/')); ?>">Meilleurs casinos</a></li>
                    <li><a href="<?php echo esc_url(home_url('/casino-en-ligne/nouveau/')); ?>">Nouveaux casinos</a></li>
                    <li><a href="<?php echo esc_url(home_url('/casino-en-ligne/fiable/')); ?>">Casinos fiables</a></li>
                </ul>
            </div>
            <div>
                <div class="site-footer__heading">Bonus</div>
                <ul class="site-footer__links">
                    <li><a href="<?php echo esc_url(home_url('/bonus-casino/')); ?>">Tous les bonus</a></li>
                    <li><a href="<?php echo esc_url(home_url('/bonus-casino/sans-depot/')); ?>">Sans dépôt</a></li>
                    <li><a href="<?php echo esc_url(home_url('/bonus-casino/tours-gratuits/')); ?>">Free Spins</a></li>
                </ul>
            </div>
            <div>
                <div class="site-footer__heading">Guides</div>
                <ul class="site-footer__links">
                    <li><a href="<?php echo esc_url(home_url('/guide/choisir-casino/')); ?>">Choisir un casino</a></li>
                    <li><a href="<?php echo esc_url(home_url('/guide/wager-casino/')); ?>">Comprendre le wager</a></li>
                    <li><a href="<?php echo esc_url(home_url('/guide/casino-legal/')); ?>">Casino légal</a></li>
                </ul>
            </div>
            <div>
                <div class="site-footer__heading">À propos</div>
                <ul class="site-footer__links">
                    <li><a href="<?php echo esc_url(home_url('/comment-nous-evaluons/')); ?>">Notre méthode</a></li>
                    <li><a href="<?php echo esc_url(home_url('/a-propos/')); ?>">Qui sommes-nous</a></li>
                    <li><a href="<?php echo esc_url(home_url('/jeu-responsable/')); ?>">Jeu responsable</a></li>
                </ul>
            </div>
        </div>
        <?php endif; ?>
        <div class="site-footer__bottom">
            <?php
            $copyright = get_theme_mod('ccv2_footer_copyright', '');
            if ($copyright !== '') {
                echo '<p class="text-muted">' . esc_html($copyright) . '</p>';
            } else {
                $year      = gmdate('Y');
                $site_name = get_bloginfo('name');
                echo '<p class="text-muted">© ' . esc_html($year) . ' ' . esc_html($site_name) . '. Jouer comporte des risques. Réservé aux personnes majeures.</p>';
            }
            ?>
            <p class="text-muted" style="font-size:0.75rem;margin-top:8px">Jeu responsable | Ce site contient des liens affiliés</p>
        </div>
    </div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
