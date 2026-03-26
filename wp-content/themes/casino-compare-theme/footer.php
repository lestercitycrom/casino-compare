<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}
?>
<footer class="site-footer">
    <div class="site-shell site-footer__inner">
        <div>
            <p class="site-footer__title"><?php esc_html_e('Casino Compare', 'casino-compare-theme'); ?></p>
            <p class="site-footer__text"><?php esc_html_e('Independent casino reviews, comparison pages and editorial guides built for SEO and operator efficiency.', 'casino-compare-theme'); ?></p>
        </div>
        <div class="site-footer__links">
            <a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Home', 'casino-compare-theme'); ?></a>
            <a href="<?php echo esc_url(home_url('/comparer/')); ?>"><?php esc_html_e('Comparer', 'casino-compare-theme'); ?></a>
        </div>
    </div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
