<?php
/**
 * Architecture-first import.
 *
 * Source of truth:
 *   1. architecture-complete-finale-casino — копия (CSV) → all URLs, slugs, KW, types
 *   2. google-sheets-structures-wp-EN (CSV) → content examples for specific slugs only
 *
 * Rules (agreed Clark + Chester):
 *   - All 312 architecture pages are created with real URL + real KW → seo_title
 *   - GSheet examples applied ONLY to their specific slugs
 *   - No invented affiliate facts (ratings, bonuses, license details)
 *   - Empty fields are left empty, not filled with synthetic data
 *
 * Usage:  php scripts/import-architecture.php [--dry-run]
 */

declare(strict_types=1);

$_SERVER['HTTP_HOST']   = 'casino-compare.local';
$_SERVER['REQUEST_URI'] = '/';
$dry_run = in_array('--dry-run', $argv ?? [], true);

require_once __DIR__ . '/../wp-load.php';

remove_all_actions('save_post_casino'); // suppress auto-skeleton hook

define('IMP_DRY',      $dry_run);
define('IMP_ARCH_CSV', __DIR__ . '/data/architecture.csv');
define('IMP_GSHEET_DIR', __DIR__ . '/data/gsheet');

$stats = ['created' => [], 'updated' => [], 'skipped' => 0, 'deleted' => 0, 'warnings' => []];

// ──────────────────────────────────────────────────────────────────────────────
// HELPERS
// ──────────────────────────────────────────────────────────────────────────────

function imp_warn(string $msg): void {
    global $stats;
    $stats['warnings'][] = $msg;
    echo "  ⚠ $msg\n";
}

function imp_csv(string $path): array {
    if (!file_exists($path)) { imp_warn("CSV not found: $path"); return []; }
    $rows = [];
    $headers = null;
    $fh = fopen($path, 'r');
    while ($row = fgetcsv($fh)) {
        if ($headers === null) { $headers = $row; continue; }
        if (array_filter($row) === []) continue; // skip empty rows
        $map = array_combine($headers, array_pad($row, count($headers), ''));
        $rows[] = $map;
    }
    fclose($fh);
    return $rows;
}

function imp_upsert(string $post_type, string $slug, string $title, int $parent = 0, string $status = 'publish'): int {
    global $stats;
    if ($parent > 0) {
        // Hierarchical: find by slug + parent
        $existing = get_posts([
            'post_type' => $post_type, 'name' => $slug,
            'post_parent' => $parent, 'post_status' => 'any',
            'posts_per_page' => 1, 'fields' => 'ids',
        ]);
        $existing_id = $existing[0] ?? 0;
    } else {
        $p = get_page_by_path($slug, OBJECT, $post_type);
        $existing_id = ($p instanceof WP_Post) ? $p->ID : 0;
    }

    if ($existing_id) {
        if (!IMP_DRY) wp_update_post(['ID' => $existing_id, 'post_title' => $title, 'post_status' => $status, 'post_parent' => $parent]);
        $stats['updated'][$post_type] = ($stats['updated'][$post_type] ?? 0) + 1;
        return $existing_id;
    }
    if (IMP_DRY) return -1;
    $id = wp_insert_post(['post_type' => $post_type, 'post_name' => $slug, 'post_title' => $title, 'post_status' => $status, 'post_parent' => $parent], true);
    if (is_wp_error($id)) { imp_warn("Insert failed ($post_type/$slug): " . $id->get_error_message()); return 0; }
    $stats['created'][$post_type] = ($stats['created'][$post_type] ?? 0) + 1;
    return $id;
}

function imp_meta(int $id, array $meta): void {
    if (IMP_DRY || $id <= 0) return;
    foreach ($meta as $k => $v) {
        if ($v === '' || $v === null || $v === []) continue;
        update_post_meta($id, $k, $v);
    }
}

function imp_kw_to_title(string $kw): string {
    if ($kw === '' || $kw === '—') return '';
    return mb_strtoupper(mb_substr($kw, 0, 1)) . mb_substr($kw, 1);
}

function imp_url_to_slug(string $url): string {
    return trim($url, '/');
}

function imp_last_segment(string $url): string {
    $parts = array_filter(explode('/', trim($url, '/')));
    return end($parts) ?: '';
}

function imp_subtype_from_slug(string $slug): string {
    return str_replace('-', '_', $slug);
}

function imp_pipe(string $v): array {
    if ($v === '') return [];
    return array_values(array_filter(array_map('trim', explode('|', $v))));
}

// ──────────────────────────────────────────────────────────────────────────────
// STEP 0: CLEAN ORPHANED SUBPAGES
// ──────────────────────────────────────────────────────────────────────────────

echo "\n=== Step 0: Clean orphaned subpages ===\n";
$all_sps = get_posts(['post_type' => 'casino_subpage', 'post_status' => 'any', 'posts_per_page' => -1]);
foreach ($all_sps as $sp) {
    $parent_id = (int) get_post_meta($sp->ID, 'parent_casino', true);
    if (!$parent_id) { echo "  ~ Delete orphan #{$sp->ID} (no parent)\n"; if (!IMP_DRY) { wp_delete_post($sp->ID, true); $stats['deleted']++; } continue; }
    $parent = get_post($parent_id);
    if (!($parent instanceof WP_Post) || $parent->post_status === 'trash') {
        echo "  ~ Delete orphan #{$sp->ID} (dead parent $parent_id)\n";
        if (!IMP_DRY) { wp_delete_post($sp->ID, true); $stats['deleted']++; }
    }
}
echo "  Done.\n";

// ──────────────────────────────────────────────────────────────────────────────
// STEP 1: PARSE ARCHITECTURE CSV
// ──────────────────────────────────────────────────────────────────────────────

echo "\n=== Step 1: Parse Architecture CSV ===\n";
$arch_rows = imp_csv(IMP_ARCH_CSV);
// Filter out section-header rows (no URL)
$arch_rows = array_filter($arch_rows, fn($r) => isset($r['URL']) && str_starts_with(trim($r['URL']), '/'));
echo "  " . count($arch_rows) . " architecture rows loaded.\n";

// Categorise rows
$casinos_arch    = []; // slug → row  (Avis pilier + Avis tiers)
$subpages_arch   = []; // casino_slug/subpage_slug → row
$landings_arch   = []; // url → row
$guides_arch     = []; // slug → row

foreach ($arch_rows as $row) {
    $url  = trim($row['URL']);
    $type = trim($row['Type'] ?? '');
    $silo = trim($row['Silo'] ?? '');

    if ($type === 'Avis (pilier)' || ($type === 'Avis' && str_starts_with($url, '/avis/'))) {
        $slug = imp_last_segment($url);
        $casinos_arch[$slug] = $row;
    } elseif ($type === 'Sous-page') {
        // /avis/{casino}/{subtype}/
        $parts = array_values(array_filter(explode('/', trim($url, '/'))));
        if (count($parts) === 3) { // avis / casino / subtype
            $key = $parts[1] . '/' . $parts[2];
            $subpages_arch[$key] = $row;
        }
    } elseif ($type === 'Article' || (str_starts_with($url, '/guide/') && $type !== 'Sous-page')) {
        $slug = imp_last_segment($url);
        $guides_arch[$slug] = $row;
    } elseif (in_array($silo, ['Trust', ''], true) && in_array($type, ['Instit.', ''], true) && !str_starts_with($url, '/avis/') && !str_starts_with($url, '/guide/') && !str_starts_with($url, '/fournisseurs')) {
        $slug = imp_url_to_slug($url);
        $landings_arch[$url] = $row;
    } else {
        $landings_arch[$url] = $row;
    }
}

echo "  Casinos: " . count($casinos_arch) . "\n";
echo "  Subpages: " . count($subpages_arch) . "\n";
echo "  Landings: " . count($landings_arch) . "\n";
echo "  Guides: " . count($guides_arch) . "\n";

// ──────────────────────────────────────────────────────────────────────────────
// STEP 2: LOAD GSHEET EXAMPLES
// ──────────────────────────────────────────────────────────────────────────────

echo "\n=== Step 2: Load GSheet examples ===\n";

$gsheet_review  = [];
$gsheet_subpage = [];
$gsheet_hub     = [];
$gsheet_guide   = [];

$review_rows = imp_csv(IMP_GSHEET_DIR . '/google-sheets-structures-wp-EN - GSheet Review.csv');
foreach ($review_rows as $r) {
    $s = trim($r['slug'] ?? '');
    if ($s !== '' && !str_starts_with($s, '💡')) $gsheet_review[$s] = $r;
}

$subpage_rows = imp_csv(IMP_GSHEET_DIR . '/google-sheets-structures-wp-EN - GSheet Subpage.csv');
foreach ($subpage_rows as $r) {
    $parent = trim($r['parent_casino'] ?? '');
    $slug   = trim($r['slug'] ?? '');
    if ($parent !== '' && $slug !== '' && !str_starts_with($slug, '💡')) {
        $gsheet_subpage[$parent . '/' . $slug] = $r;
    }
}

$hub_rows = imp_csv(IMP_GSHEET_DIR . '/google-sheets-structures-wp-EN - GSheet Hub.csv');
foreach ($hub_rows as $r) {
    $s = trim($r['slug'] ?? '');
    if ($s !== '' && !str_starts_with($s, '💡')) $gsheet_hub[$s] = $r;
}

$guide_rows = imp_csv(IMP_GSHEET_DIR . '/google-sheets-structures-wp-EN - GSheet Guide.csv');
foreach ($guide_rows as $r) {
    $s = trim($r['slug'] ?? '');
    if ($s !== '' && !str_starts_with($s, '💡')) $gsheet_guide[$s] = $r;
}

echo "  GSheet Review examples: " . count($gsheet_review) . "\n";
echo "  GSheet Subpage examples: " . count($gsheet_subpage) . "\n";
echo "  GSheet Hub examples: " . count($gsheet_hub) . "\n";
echo "  GSheet Guide examples: " . count($gsheet_guide) . "\n";

// ──────────────────────────────────────────────────────────────────────────────
// STEP 3: IMPORT CASINOS
// ──────────────────────────────────────────────────────────────────────────────

echo "\n=== Step 3: Import casinos ===\n";

$casino_ids = []; // slug → post_id

foreach ($casinos_arch as $slug => $row) {
    $kw    = $row['KW Principal'] ?? '';
    $page  = $row['Page'] ?? '';
    $title = imp_kw_to_title($kw) ?: $page;

    echo "  + casino: $slug\n";
    $id = imp_upsert('casino', $slug, $title);
    if ($id <= 0) continue;
    $casino_ids[$slug] = $id;

    if (!IMP_DRY) update_post_meta($id, '_ccc_arch_imported', 1);

    // Base meta from Architecture — real data
    $meta = [
        'seo_title'        => imp_kw_to_title($kw),
        'meta_description' => '', // no real meta desc in architecture
        '_arch_kw'         => $kw,
        '_arch_kw_secondary' => $row['KWs Secondaires'] ?? '',
        '_arch_priority'   => $row['Priorité'] ?? '',
        '_arch_quick_win'  => ($row['Quick Win?'] ?? '') === '★ OUI' ? '1' : '0',
    ];

    // Enrich from GSheet Review if example exists for this slug
    $gs = $gsheet_review[$slug] ?? null;
    if ($gs) {
        $pros = imp_pipe($gs['pros'] ?? '');
        $cons = imp_pipe($gs['cons'] ?? '');
        $providers = array_filter(array_map('trim', explode(',', $gs['info_providers'] ?? '')));
        $pay_methods = array_filter(array_map('trim', explode(',', $gs['info_payment_methods'] ?? '')));
        $faq = [];
        for ($n = 1; $n <= 4; $n++) {
            $q = $gs["faq_{$n}_question"] ?? ''; $a = $gs["faq_{$n}_answer"] ?? '';
            if ($q !== '' && $a !== '') $faq[] = ['question' => $q, 'answer' => $a];
        }
        $summaries = [];
        foreach ([['ak'=>'section_bonus_title','al'=>'section_bonus_content','n'=>1],['ak'=>'section_games_title','al'=>'section_games_content','n'=>2],['ak'=>'section_payments_title','al'=>'section_payments_content','n'=>3],['ak'=>'section_reliability_title','al'=>'section_reliability_content','n'=>4],['ak'=>'section_signup_title','al'=>'section_signup_content','n'=>5]] as $s) {
            $summaries[$s['n']] = ['title' => $gs[$s['ak']] ?? '', 'content' => $gs[$s['al']] ?? ''];
        }
        $int_links = [];
        foreach (explode("\n", $gs['internal_links'] ?? '') as $line) {
            $pts = explode('|', $line, 2);
            if (count($pts) === 2 && trim($pts[0]) !== '') $int_links[] = ['label' => trim($pts[0]), 'url' => trim($pts[1])];
        }
        $meta = array_merge($meta, [
            'seo_title'          => $gs['seo_title'] ?? $meta['seo_title'],
            'meta_description'   => $gs['seo_description'] ?? '',
            'overall_rating'     => $gs['overall_rating'] ?? '',
            'rating_bonus'       => $gs['rating_bonus'] ?? '',
            'rating_games'       => $gs['rating_games'] ?? '',
            'rating_payments'    => $gs['rating_payments'] ?? '',
            'rating_support'     => $gs['rating_support'] ?? '',
            'rating_reliability' => $gs['rating_reliability'] ?? '',
            'affiliate_link'     => $gs['affiliate_link'] ?? '',
            'year_founded'       => $gs['info_year'] ?? '',
            'last_updated'       => $gs['last_updated'] ?? '',
            'author_name'        => $gs['author_name'] ?? '',
            'welcome_bonus_text' => $gs['info_welcome_bonus'] ?? '',
            'wagering'           => $gs['info_wagering'] ?? '',
            'min_deposit'        => $gs['info_min_deposit'] ?? '',
            'no_deposit_bonus'   => $gs['info_no_deposit_bonus'] ?? '',
            'free_spins'         => $gs['info_free_spins'] ?? '',
            'promo_code'         => $gs['info_promo_code'] ?? '',
            'license'            => $gs['info_license'] ?? '',
            'games_count'        => $gs['info_games_count'] ?? '',
            'support_channels'   => $gs['info_support_channels'] ?? '',
            'vip'                => $gs['info_vip'] ?? '',
            'mobile_app'         => $gs['info_mobile_app'] ?? '',
            'intro_text'         => $gs['intro_text'] ?? '',
            'pros'               => array_map(fn($v) => ['text' => $v], $pros),
            'cons'               => array_map(fn($v) => ['text' => $v], $cons),
            'providers'          => array_map(fn($v) => ['name' => $v], array_values($providers)),
            'deposit_methods'    => array_map(fn($v) => ['name' => $v], array_values($pay_methods)),
            'summary_1_title'    => $summaries[1]['title'],  'summary_1' => $summaries[1]['content'],
            'summary_2_title'    => $summaries[2]['title'],  'summary_2' => $summaries[2]['content'],
            'summary_3_title'    => $summaries[3]['title'],  'summary_3' => $summaries[3]['content'],
            'summary_4_title'    => $summaries[4]['title'],  'summary_4' => $summaries[4]['content'],
            'summary_5_title'    => $summaries[5]['title'],  'summary_5' => $summaries[5]['content'],
            'final_verdict'      => ($gs['verdict_title'] ?? '') !== ''
                                        ? '<h3>' . esc_html($gs['verdict_title']) . '</h3>' . ($gs['verdict_content'] ?? '')
                                        : ($gs['verdict_content'] ?? ''),
            'faq'                => $faq,
            'money_page_links'   => $int_links,
        ]);
        if (!IMP_DRY && !empty($gs['heading_h1'])) {
            wp_update_post(['ID' => $id, 'post_excerpt' => wp_strip_all_tags($gs['heading_h1'])]);
        }
    }
    // For casinos without GSheet enrichment: clear any stale invented meta from previous imports
    if (!$gs && !IMP_DRY) {
        $stale_keys = ['overall_rating','rating_bonus','rating_games','rating_payments','rating_support','rating_reliability','intro_text','last_updated','author_name'];
        foreach ($stale_keys as $k) {
            delete_post_meta($id, $k);
        }
    }

    imp_meta($id, $meta);
}

// ──────────────────────────────────────────────────────────────────────────────
// STEP 4: IMPORT CASINO SUBPAGES
// ──────────────────────────────────────────────────────────────────────────────

echo "\n=== Step 4: Import casino subpages ===\n";

foreach ($subpages_arch as $key => $row) {
    [$casino_slug, $subtype_slug] = explode('/', $key, 2);
    $subtype = imp_subtype_from_slug($subtype_slug);
    $casino_id = $casino_ids[$casino_slug] ?? 0;

    if (!$casino_id) {
        imp_warn("Subpage $key: casino '$casino_slug' not found, skipping");
        continue;
    }

    $kw    = $row['KW Principal'] ?? '';
    $page  = $row['Page'] ?? '';
    $title = imp_kw_to_title($kw) ?: $page;

    echo "  + subpage: $key\n";

    // Find existing by parent + type
    $existing = get_posts([
        'post_type' => 'casino_subpage', 'post_status' => 'any',
        'posts_per_page' => 1, 'fields' => 'ids',
        'meta_query' => [
            ['key' => 'parent_casino', 'value' => (string) $casino_id],
            ['key' => 'subpage_type',  'value' => $subtype],
        ],
    ]);
    $sp_id = $existing[0] ?? 0;

    if ($sp_id) {
        if (!IMP_DRY) wp_update_post(['ID' => $sp_id, 'post_title' => $title, 'post_status' => 'publish']);
        $stats['updated']['casino_subpage'] = ($stats['updated']['casino_subpage'] ?? 0) + 1;
    } else {
        if (IMP_DRY) { $sp_id = -1; } else {
            $sp_id = (int) wp_insert_post(['post_type' => 'casino_subpage', 'post_title' => $title, 'post_status' => 'publish'], true);
        }
        $stats['created']['casino_subpage'] = ($stats['created']['casino_subpage'] ?? 0) + 1;
    }
    if ($sp_id <= 0) continue;

    $meta = [
        'parent_casino'      => $casino_id,
        'subpage_type'       => $subtype,
        'parent_review_link' => '/avis/' . $casino_slug . '/',
        'seo_title'          => imp_kw_to_title($kw),
        '_arch_kw'           => $kw,
        '_arch_kw_secondary' => $row['KWs Secondaires'] ?? '',
        '_arch_priority'     => $row['Priorité'] ?? '',
    ];

    // Enrich from GSheet Subpage example
    // GSheet key uses the URL slug (e.g. "fatpirate/bonus"), but subtype_slug is also the URL slug
    $gs_key_bonus  = $casino_slug . '/bonus';
    $gs_key_fiable = $casino_slug . '/fiable';
    $gs = $gsheet_subpage[$casino_slug . '/' . $subtype_slug] ?? null;

    if ($gs) {
        $score_enabled = strtolower($gs['score_enabled'] ?? '') === 'yes';
        $table_enabled = strtolower($gs['table_enabled'] ?? '') === 'yes';
        $table_headers = [];
        if ($table_enabled && !empty($gs['table_headers'])) {
            foreach (explode('|', $gs['table_headers']) as $h) {
                if (trim($h) !== '') $table_headers[] = ['label' => trim($h)];
            }
        }
        $table_rows = [];
        if ($table_enabled) {
            for ($ri = 1; $ri <= 5; $ri++) {
                $raw = $gs["table_row_{$ri}"] ?? '';
                if ($raw === '') continue;
                $table_rows[] = ['cells' => array_map('trim', explode('|', $raw))];
            }
        }
        $faq = [];
        for ($n = 1; $n <= 3; $n++) {
            $q = $gs["faq_{$n}_question"] ?? ''; $a = $gs["faq_{$n}_answer"] ?? '';
            if ($q !== '' && $a !== '') $faq[] = ['question' => $q, 'answer' => $a];
        }
        $arch_links = [];
        foreach (explode("\n", $gs['architecture_links'] ?? '') as $line) {
            $pts = explode('|', $line, 2);
            if (count($pts) === 2 && trim($pts[0]) !== '') $arch_links[] = ['label' => trim($pts[0]), 'url' => trim($pts[1])];
        }
        $meta = array_merge($meta, [
            'seo_title'         => $gs['seo_title'] ?? $meta['seo_title'],
            'meta_description'  => $gs['seo_description'] ?? '',
            'hero_title'        => $gs['heading_h1'] ?? '',
            'intro_text'        => $gs['intro_text'] ?? '',
            'last_updated'      => $gs['last_updated'] ?? '',
            'main_content'      => $gs['main_content'] ?? '',
            'cta_text'          => $gs['cta_text'] ?? '',
            'cta_url'           => $gs['cta_link'] ?? '',
            'score_enabled'     => $score_enabled ? 1 : 0,
            'score_value'       => $gs['score_value'] ?? '',
            'score_label'       => $gs['score_label'] ?? '',
            'score_verdict'     => $gs['score_verdict'] ?? '',
            'table_enabled'     => $table_enabled ? 1 : 0,
            'table_headers'     => $table_headers,
            'table_rows'        => $table_rows,
            'faq'               => $faq,
            'architecture_links' => $arch_links,
        ]);
    } else {
        // No GSheet example — clear any stale invented content from previous imports
        if (!IMP_DRY) {
            foreach (['hero_title', 'intro_text', 'main_content', 'score_value', 'score_label', 'score_verdict', 'cta_text', 'cta_url'] as $k) {
                delete_post_meta($sp_id, $k);
            }
        }
    }
    imp_meta($sp_id, $meta);
}

// ──────────────────────────────────────────────────────────────────────────────
// STEP 5: IMPORT LANDINGS + GUIDES
// ──────────────────────────────────────────────────────────────────────────────

echo "\n=== Step 5: Import landings ===\n";

// Determine landing type from Architecture data
function imp_landing_type(array $row): string {
    $silo = trim($row['Silo'] ?? '');
    $type = trim($row['Type'] ?? '');
    $url  = trim($row['URL'] ?? '');
    if ($silo === 'Trust') return 'trust';
    // Hub = silo root pages (★ Pilier)
    if (str_contains($row['Page'] ?? '', '★ Pilier') || str_contains($row['Page'] ?? '', '★ Hub')) return 'hub';
    // Fournisseurs root
    if ($url === '/fournisseurs-jeux/') return 'hub';
    return 'comparison';
}

// Build parent hierarchy: for /casino-en-ligne/meilleur/ the parent is /casino-en-ligne/
// We need to create parents before children
// Sort landing URLs by depth (shorter first)
$landing_urls = array_keys($landings_arch);
usort($landing_urls, fn($a, $b) => substr_count($a, '/') <=> substr_count($b, '/'));

$landing_ids = []; // url → post_id

foreach ($landing_urls as $url) {
    $row = $landings_arch[$url];
    $kw   = $row['KW Principal'] ?? '';
    $page = $row['Page'] ?? '';

    // Skip section headers without real title
    if ($kw === '' && $page === '') continue;

    $url_clean = trim($url, '/');
    $parts     = explode('/', $url_clean);
    $slug      = array_pop($parts);          // last segment
    $parent_url = '/' . implode('/', $parts) . '/';

    // Find parent post_id
    $parent_id = 0;
    if (!empty($parts)) {
        $parent_id = $landing_ids[$parent_url] ?? 0;
        if (!$parent_id) {
            // Try to find existing
            $parent_path = implode('/', $parts);
            $p = get_page_by_path($parent_path, OBJECT, 'landing');
            if ($p instanceof WP_Post) $parent_id = $p->ID;
        }
    }

    $title = imp_kw_to_title($kw) ?: preg_replace('/[★✓]\s*/', '', $page);
    $ltype = imp_landing_type($row);

    echo "  + landing: $url ($ltype)\n";
    $id = imp_upsert('landing', $slug, $title, $parent_id);
    if ($id <= 0) continue;
    $landing_ids[$url] = $id;

    $meta = [
        'landing_type'     => $ltype,
        'seo_title'        => imp_kw_to_title($kw),
        '_arch_kw'         => $kw,
        '_arch_kw_secondary' => $row['KWs Secondaires'] ?? '',
        '_arch_priority'   => $row['Priorité'] ?? '',
        '_arch_quick_win'  => ($row['Quick Win?'] ?? '') === '★ OUI' ? '1' : '0',
    ];

    // Trust pages
    if ($ltype === 'trust') {
        $meta['show_author'] = 1;
        $meta['trust_last_updated'] = date('Y-m-d');
    }

    // Enrich from GSheet Hub if example matches
    $gs_h = $gsheet_hub[$slug] ?? null;
    if ($gs_h && $ltype === 'hub') {
        $subcats = [];
        for ($n = 1; $n <= 8; $n++) {
            $label = $gs_h["subcat_{$n}_label"] ?? '';
            if ($label !== '') $subcats[] = ['title' => $label, 'url' => $gs_h["subcat_{$n}_url"] ?? '', 'description' => '', 'icon' => $gs_h["subcat_{$n}_icon"] ?? ''];
        }
        $faq = [];
        for ($n = 1; $n <= 3; $n++) {
            $q = $gs_h["faq_{$n}_question"] ?? ''; $a = $gs_h["faq_{$n}_answer"] ?? '';
            if ($q !== '' && $a !== '') $faq[] = ['question' => $q, 'answer' => $a];
        }
        $cross = [];
        foreach (explode("\n", $gs_h['cross_silo_links'] ?? '') as $line) {
            $pts = explode('|', $line, 2);
            if (count($pts) === 2 && trim($pts[0]) !== '') $cross[] = ['label' => trim($pts[0]), 'url' => trim($pts[1])];
        }
        $meta = array_merge($meta, [
            'seo_title'           => $gs_h['seo_title'] ?? $meta['seo_title'],
            'meta_description'    => $gs_h['seo_description'] ?? '',
            'hero_title'          => $gs_h['heading_h1'] ?? '',
            'intro_text'          => $gs_h['intro_text'] ?? '',
            'last_updated'        => $gs_h['last_updated'] ?? '',
            'subcategory_cards'   => $subcats,
            'educational_content' => $gs_h['educational_content'] ?? '',
            'comparison_table_title' => $gs_h['comparison_table_title'] ?? '',
            'howto_title'         => $gs_h['howto_title'] ?? '',
            'howto_content'       => $gs_h['howto_content'] ?? '',
            'cross_silo_links'    => $cross,
            'faq'                 => $faq,
        ]);
    }

    imp_meta($id, $meta);
}

// ──────────────────────────────────────────────────────────────────────────────
// STEP 6: IMPORT GUIDES
// ──────────────────────────────────────────────────────────────────────────────

echo "\n=== Step 6: Import guides ===\n";

foreach ($guides_arch as $slug => $row) {
    $kw   = $row['KW Principal'] ?? '';
    $page = $row['Page'] ?? '';
    $title = imp_kw_to_title($kw) ?: $page;

    echo "  + guide: $slug\n";
    $id = imp_upsert('guide', $slug, $title);
    if ($id <= 0) continue;

    $meta = [
        'seo_title'   => imp_kw_to_title($kw),
        'category'    => 'Guides',
        '_arch_kw'    => $kw,
        '_arch_priority' => $row['Priorité'] ?? '',
    ];

    // Enrich from GSheet Guide example
    $gs_g = $gsheet_guide[$slug] ?? null;
    if ($gs_g) {
        $faq = [];
        for ($n = 1; $n <= 3; $n++) {
            $q = $gs_g["faq_{$n}_question"] ?? ''; $a = $gs_g["faq_{$n}_answer"] ?? '';
            if ($q !== '' && $a !== '') $faq[] = ['question' => $q, 'answer' => $a];
        }
        $money = [];
        foreach (explode("\n", $gs_g['money_page_links'] ?? '') as $line) {
            $pts = explode('|', $line, 2);
            if (count($pts) === 2 && trim($pts[0]) !== '') $money[] = ['label' => trim($pts[0]), 'url' => trim($pts[1])];
        }
        $sidebar_guides = [];
        foreach (explode("\n", $gs_g['sidebar_related_guides'] ?? '') as $line) {
            $pts = explode('|', $line, 2);
            if (count($pts) === 2 && trim($pts[0]) !== '') $sidebar_guides[] = ['label' => trim($pts[0]), 'url' => trim($pts[1])];
        }
        $meta = array_merge($meta, [
            'seo_title'              => $gs_g['seo_title'] ?? $meta['seo_title'],
            'meta_description'       => $gs_g['seo_description'] ?? '',
            'category'               => $gs_g['category'] ?? 'Guides',
            'reading_time'           => $gs_g['reading_time'] ?? '',
            'last_updated'           => $gs_g['last_updated'] ?? '',
            'author_name'            => $gs_g['author_name'] ?? '',
            'intro_text'             => $gs_g['intro_text'] ?? '',
            'callout_enabled'        => strtolower($gs_g['callout_enabled'] ?? '') === 'yes' ? 1 : 0,
            'callout_title'          => $gs_g['callout_title'] ?? '',
            'callout_text'           => $gs_g['callout_content'] ?? '',
            'main_content'           => $gs_g['main_content'] ?? '',
            'sidebar_top_title'      => $gs_g['sidebar_top_title'] ?? '',
            'sidebar_takeaway'       => $gs_g['sidebar_takeaway'] ?? '',
            'sidebar_comparison_link'=> $gs_g['sidebar_comparison_link'] ?? '',
            'sidebar_related_guides' => $sidebar_guides,
            'money_page_links'       => $money,
            'faq'                    => $faq,
        ]);
    }
    imp_meta($id, $meta);
}

// ──────────────────────────────────────────────────────────────────────────────
// STEP 7: FLUSH REWRITE RULES
// ──────────────────────────────────────────────────────────────────────────────

echo "\n=== Step 7: Flush rewrite rules ===\n";
if (!IMP_DRY) { flush_rewrite_rules(true); echo "  Done.\n"; }
else echo "  [dry-run] skipped.\n";

// ──────────────────────────────────────────────────────────────────────────────
// REPORT
// ──────────────────────────────────────────────────────────────────────────────

echo "\n";
echo "╔══════════════════════════════════════════════════════╗\n";
echo "║              ARCHITECTURE IMPORT COMPLETE           ║\n";
echo "╚══════════════════════════════════════════════════════╝\n\n";

$all_types = array_unique(array_merge(array_keys($stats['created']), array_keys($stats['updated'])));
foreach ($all_types as $t) {
    $c = $stats['created'][$t] ?? 0;
    $u = $stats['updated'][$t] ?? 0;
    echo sprintf("  %-20s created=%-4d updated=%d\n", $t, $c, $u);
}
echo "\n  Orphaned deleted: {$stats['deleted']}\n";

if ($stats['warnings']) {
    echo "\nWarnings (" . count($stats['warnings']) . "):\n";
    foreach ($stats['warnings'] as $w) echo "  ⚠ $w\n";
}

// Write report file
$report_path = __DIR__ . '/import-report.md';
$date = date('Y-m-d H:i');
$status_str = IMP_DRY ? 'DRY RUN' : 'COMPLETED';

$report_lines = ["# Architecture Import Report\nGenerated: $date\nStatus: $status_str\n\n## Counts\n"];
foreach ($all_types as $t) {
    $c = $stats['created'][$t] ?? 0; $u = $stats['updated'][$t] ?? 0;
    $report_lines[] = "- **$t**: created=$c updated=$u\n";
}
$report_lines[] = "- **orphaned deleted**: {$stats['deleted']}\n";
$report_lines[] = "\n## Data Sources\n\n| Source | Used for |\n|--------|----------|\n";
$report_lines[] = "| Architecture Complète CSV | All URLs, slugs, KW Principal → seo_title, page types |\n";
$report_lines[] = "| GSheet Review (FatPirate row) | Full content for /avis/fatpirate/ |\n";
$report_lines[] = "| GSheet Subpage (2 rows) | Full content for FatPirate/bonus + FatPirate/fiable |\n";
$report_lines[] = "| GSheet Hub (bonus-casino row) | Full content for /bonus-casino/ hub |\n";
$report_lines[] = "| GSheet Guide (wager-casino row) | Full content for /guide/wager-casino/ |\n";
$report_lines[] = "\n## What is empty (no content source)\n\n";
$report_lines[] = "- 14 casino records: only seo_title from KW, all content fields empty\n";
$report_lines[] = "- ~220 subpages: only parent_casino, subpage_type, seo_title from KW\n";
$report_lines[] = "- ~60 landing pages: only landing_type, seo_title from KW\n";
$report_lines[] = "- 9 guides: only seo_title from KW\n";
$report_lines[] = "\nAll empty fields are intentionally empty — no invented data.\n";

if ($stats['warnings']) {
    $report_lines[] = "\n## Warnings\n\n";
    foreach ($stats['warnings'] as $w) $report_lines[] = "- $w\n";
}

if (!IMP_DRY) {
    file_put_contents($report_path, implode('', $report_lines));
    echo "\nReport: $report_path\n";
}

if (IMP_DRY) echo "\n[DRY RUN — no data written]\n";
echo "\nDone.\n";
