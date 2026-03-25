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
<main class="site-shell">
    <article>
        <h1><?php the_title(); ?></h1>
        <div id="ccc-compare-app"></div>
    </article>
</main>
<?php
get_footer();
