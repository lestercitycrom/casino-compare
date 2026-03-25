<?php
/**
 * Plugin Name: Casino Compare Core
 * Plugin URI: https://casino-compare.local
 * Description: Core business logic for the casino compare platform.
 * Version: 0.1.0
 * Author: Codex
 * Text Domain: casino-compare-core
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define('CCC_VERSION', '0.1.0');
define('CCC_PLUGIN_FILE', __FILE__);
define('CCC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CCC_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once CCC_PLUGIN_DIR . 'includes/cpt/register-cpts.php';
require_once CCC_PLUGIN_DIR . 'includes/cpt/register-taxonomies.php';
require_once CCC_PLUGIN_DIR . 'includes/cpt/rewrite.php';
require_once CCC_PLUGIN_DIR . 'includes/fields/field-helpers.php';
require_once CCC_PLUGIN_DIR . 'includes/fields/casino-fields.php';
require_once CCC_PLUGIN_DIR . 'includes/fields/subpage-fields.php';
require_once CCC_PLUGIN_DIR . 'includes/fields/landing-fields.php';
require_once CCC_PLUGIN_DIR . 'includes/fields/guide-fields.php';
require_once CCC_PLUGIN_DIR . 'includes/helpers/internal-links.php';
require_once CCC_PLUGIN_DIR . 'includes/helpers/system-pages.php';
require_once CCC_PLUGIN_DIR . 'includes/rest/compare-endpoint.php';
require_once CCC_PLUGIN_DIR . 'includes/rest/filter-endpoint.php';
require_once CCC_PLUGIN_DIR . 'includes/seo/breadcrumbs.php';
require_once CCC_PLUGIN_DIR . 'includes/seo/schema.php';
require_once CCC_PLUGIN_DIR . 'includes/seo/seo-meta.php';

function ccc_register_plugin_hooks(): void
{
    ccc_register_post_types();
    ccc_register_taxonomies();
    ccc_register_rewrite_rules();
}

function ccc_activate_plugin(): void
{
    ccc_register_plugin_hooks();
    ccc_seed_base_terms();
    ccc_ensure_system_pages();
    flush_rewrite_rules();
}
register_activation_hook(CCC_PLUGIN_FILE, 'ccc_activate_plugin');

function ccc_deactivate_plugin(): void
{
    flush_rewrite_rules();
}
register_deactivation_hook(CCC_PLUGIN_FILE, 'ccc_deactivate_plugin');

add_action('init', 'ccc_register_plugin_hooks');
