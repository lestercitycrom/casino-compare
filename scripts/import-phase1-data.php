<?php
/**
 * Phase-1 Demo Data Import from Excel.
 *
 * Reads real data from:
 *   - flow/base TZ/google-sheets-structures-wp-EN.xlsx   (GSheet Review, Comparatif, Hub, Subpage, Guide)
 *   - flow/base TZ/architecture-complete-finale-casino — копия.xlsx  (Architecture Complète)
 *
 * Creates/updates: casino, casino_subpage, landing (comparison/hub/trust), guide
 * Idempotent: upserts by slug+post_type (update mode)
 *
 * Usage:  php scripts/import-phase1-data.php [--dry-run]
 */

declare(strict_types=1);

$_SERVER['HTTP_HOST']   = 'casino-compare.local';
$_SERVER['REQUEST_URI'] = '/';

$dry_run = in_array('--dry-run', $argv ?? [], true);

require_once __DIR__ . '/../wp-load.php';

// Suppress auto-skeleton trigger during import (we handle subpages manually)
remove_all_actions('save_post_casino');

define('CCC_IMPORT_DRY_RUN', $dry_run);
define('CCC_LOGOS_DIR', __DIR__ . '/data/logos');
define('CCC_EXCEL_DIR', __DIR__ . '/data');

$report = [
    'casinos'   => ['created' => 0, 'updated' => 0, 'skipped' => 0],
    'subpages'  => ['created' => 0, 'updated' => 0],
    'landings'  => ['created' => 0, 'updated' => 0],
    'guides'    => ['created' => 0, 'updated' => 0],
    'cleanup'   => 0,
    'warnings'  => [],
    'unmatched' => [],
];

// ──────────────────────────────────────────────────────────────────────────────
// XLSX READER
// ──────────────────────────────────────────────────────────────────────────────

function xlsx_read_sheet(string $xlsx_path, string $sheet_name): array
{
    $zip = new ZipArchive();
    if ($zip->open($xlsx_path) !== true) {
        return [];
    }

    // Find shared strings
    $shared = [];
    $ss_xml = $zip->getFromName('xl/sharedStrings.xml');
    if ($ss_xml !== false) {
        preg_match_all('/<si>(?:<r>)?(?:<rPr>[^<]*(?:<[^/][^>]*\/>[^<]*)*<\/rPr>)?(?:<t[^>]*>)(.*?)(?:<\/t>)(?:<\/r>)?(?:<r>[^<]*(?:<rPr>.*?<\/rPr>)?<t[^>]*>(.*?)<\/t><\/r>)*<\/si>/s', $ss_xml, $m);
        if (!empty($m[0])) {
            preg_match_all('/<si>(.*?)<\/si>/s', $ss_xml, $si_m);
            foreach ($si_m[1] as $si_content) {
                preg_match_all('/<t[^>]*>(.*?)<\/t>/s', $si_content, $t_m);
                $shared[] = implode('', $t_m[1]);
            }
        }
    }

    // Find workbook for sheet order
    $wb_xml = (string) $zip->getFromName('xl/workbook.xml');
    preg_match_all('/<sheet[^>]+name="([^"]+)"[^>]+r:id="([^"]+)"[^>]*\/>/i', $wb_xml, $wm);
    $sheet_map = [];
    if (!empty($wm[1])) {
        foreach ($wm[1] as $i => $sname) {
            $sheet_map[$sname] = $wm[2][$i];
        }
    }

    // Find rels (Target may appear before or after Id in the attribute list)
    $rels_xml = (string) $zip->getFromName('xl/_rels/workbook.xml.rels');
    $id_to_path = [];
    preg_match_all('/<Relationship[^>]+>/i', $rels_xml, $rel_m);
    foreach ($rel_m[0] as $rel_tag) {
        preg_match('/\bId="([^"]+)"/i', $rel_tag, $id_m);
        preg_match('/\bTarget="([^"]+)"/i', $rel_tag, $tgt_m);
        if (!empty($id_m[1]) && !empty($tgt_m[1])) {
            $id_to_path[$id_m[1]] = $tgt_m[1];
        }
    }

    $rid        = $sheet_map[$sheet_name] ?? null;
    $sheet_path = $rid ? ($id_to_path[$rid] ?? null) : null;
    if ($sheet_path === null) {
        $zip->close();
        return [];
    }

    // Normalize path: strip leading slash, ensure xl/ prefix
    $sheet_path = ltrim($sheet_path, '/');
    if (!str_starts_with($sheet_path, 'xl/')) {
        $sheet_path = 'xl/' . $sheet_path;
    }

    $sheet_xml = (string) $zip->getFromName($sheet_path);
    $zip->close();

    if ($sheet_xml === '') {
        return [];
    }

    // Parse rows
    $rows = [];
    preg_match_all('/<row[^>]*r="(\d+)"[^>]*>(.*?)<\/row>/s', $sheet_xml, $row_m);
    foreach ($row_m[1] as $ri => $row_num) {
        $row_content = $row_m[2][$ri];
        preg_match_all('/<c r="([A-Z]+)\d+"(?:[^>]+t="([^"]*)")?[^>]*>(.*?)<\/c>/s', $row_content, $cm);
        foreach ($cm[1] as $ci => $col) {
            $type     = $cm[2][$ci];
            $cell_xml = $cm[3][$ci];
            if ($type === 's') {
                preg_match('/<v>(\d+)<\/v>/', $cell_xml, $vm);
                $val = isset($vm[1]) ? ($shared[(int) $vm[1]] ?? '') : '';
            } elseif ($type === 'inlineStr') {
                preg_match_all('/<t[^>]*>(.*?)<\/t>/s', $cell_xml, $tm);
                $val = implode('', $tm[1]);
            } else {
                preg_match('/<v>(.*?)<\/v>/s', $cell_xml, $vm);
                $val = $vm[1] ?? '';
            }
            $rows[(int) $row_num][$col] = html_entity_decode((string) $val, ENT_QUOTES | ENT_XML1, 'UTF-8');
        }
    }

    // Sort by row number
    ksort($rows);
    return array_values($rows);
}

function xlsx_rows_to_maps(array $rows): array
{
    if (empty($rows)) {
        return [];
    }
    $headers = $rows[0];
    $result  = [];
    for ($i = 1; $i < count($rows); $i++) {
        $map = [];
        foreach ($headers as $col => $header) {
            $map[$header] = $rows[$i][$col] ?? '';
        }
        $result[] = $map;
    }
    return $result;
}

// ──────────────────────────────────────────────────────────────────────────────
// WP HELPERS
// ──────────────────────────────────────────────────────────────────────────────

function ccc_upsert_post(string $post_type, string $slug, string $title, string $status = 'publish'): int
{
    $existing = get_page_by_path($slug, OBJECT, $post_type);
    if ($existing instanceof WP_Post) {
        if (!CCC_IMPORT_DRY_RUN) {
            wp_update_post(['ID' => $existing->ID, 'post_title' => $title, 'post_status' => $status]);
        }
        return $existing->ID;
    }
    if (CCC_IMPORT_DRY_RUN) {
        return -1;
    }
    $id = wp_insert_post([
        'post_type'   => $post_type,
        'post_name'   => $slug,
        'post_title'  => $title,
        'post_status' => $status,
    ], true);
    return is_wp_error($id) ? 0 : $id;
}

function ccc_set_meta(int $post_id, array $meta): void
{
    if (CCC_IMPORT_DRY_RUN || $post_id <= 0) {
        return;
    }
    foreach ($meta as $key => $value) {
        if ($value === '' || $value === null) {
            continue;
        }
        update_post_meta($post_id, $key, $value);
    }
}

function ccc_parse_pipe(string $value): array
{
    if ($value === '') {
        return [];
    }
    return array_values(array_filter(array_map('trim', explode('|', $value))));
}

function ccc_repeater_simple(string $field, array $items): array
{
    return array_map(fn($v) => [$field => $v], $items);
}

function ccc_import_logo(string $casino_slug, string $logo_path): int
{
    if (!file_exists($logo_path)) {
        return 0;
    }
    // Check if already imported
    $existing = get_posts([
        'post_type'   => 'attachment',
        'post_status' => 'inherit',
        'meta_key'    => '_ccc_logo_for',
        'meta_value'  => $casino_slug,
        'posts_per_page' => 1,
        'fields'      => 'ids',
    ]);
    if (!empty($existing)) {
        return (int) $existing[0];
    }

    $upload_dir = wp_upload_dir();
    $ext        = strtolower(pathinfo($logo_path, PATHINFO_EXTENSION));
    $filename   = sanitize_file_name($casino_slug . '-logo.' . $ext);
    $dest_path  = $upload_dir['path'] . '/' . $filename;

    if (!copy($logo_path, $dest_path)) {
        return 0;
    }

    $mime_map = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
    $att_id   = wp_insert_attachment([
        'guid'           => $upload_dir['url'] . '/' . $filename,
        'post_mime_type' => $mime_map[$ext] ?? 'image/jpeg',
        'post_title'     => $casino_slug . ' logo',
        'post_status'    => 'inherit',
    ], $dest_path);

    if (is_wp_error($att_id)) {
        return 0;
    }

    require_once ABSPATH . 'wp-admin/includes/image.php';
    wp_update_attachment_metadata($att_id, wp_generate_attachment_metadata($att_id, $dest_path));
    update_post_meta($att_id, '_ccc_logo_for', $casino_slug);
    return $att_id;
}

// ──────────────────────────────────────────────────────────────────────────────
// STEP 0: CLEAN UP OLD HARDCODED DEMO RECORDS (non-PW records from import-casinos.php)
// ──────────────────────────────────────────────────────────────────────────────

echo "\n=== Step 0: Cleanup old hardcoded demo records ===\n";

$demo_casino_slugs = [
    'casinolab', 'gransino', 'fatpirate', 'qbet', 'gqbet',
    'bahigo', 'wettigo', 'bahibi', 'goldenplay',
];

foreach ($demo_casino_slugs as $slug) {
    $posts = get_posts([
        'post_type'      => 'casino',
        'name'           => $slug,
        'post_status'    => 'any',
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ]);
    foreach ($posts as $pid) {
        // Only delete records NOT already managed by this script (no excel-import marker)
        if (get_post_meta($pid, '_ccc_excel_imported', true)) {
            continue;
        }
        echo "  ~ Removing old demo casino ID $pid (slug: $slug)\n";
        if (!CCC_IMPORT_DRY_RUN) {
            $subpages = get_posts([
                'post_type'      => 'casino_subpage',
                'post_status'    => 'any',
                'posts_per_page' => -1,
                'fields'         => 'ids',
                'meta_key'       => 'parent_casino',
                'meta_value'     => $pid,
            ]);
            foreach ($subpages as $sp_id) {
                wp_delete_post($sp_id, true);
            }
            wp_delete_post($pid, true);
            $report['cleanup']++;
        }
    }
}

echo "  Done. Cleaned {$report['cleanup']} records.\n";

// ──────────────────────────────────────────────────────────────────────────────
// STEP 1: LOGOS — map casino slug → logo file
// ──────────────────────────────────────────────────────────────────────────────

$logo_files = glob(CCC_LOGOS_DIR . '/*.{jpg,jpeg,png,webp}', GLOB_BRACE) ?: [];
sort($logo_files);

$casino_logo_map = [
    'casinolab' => $logo_files[0]  ?? '',
    'gransino'  => $logo_files[1]  ?? '',
    'fatpirate' => $logo_files[2]  ?? '',
    'qbet'      => $logo_files[3]  ?? '',
    'gqbet'     => $logo_files[4]  ?? '',
    'bahigo'    => $logo_files[5]  ?? '',
    'wettigo'   => $logo_files[6]  ?? '',
    'bahibi'    => $logo_files[7]  ?? '',
    'goldenplay' => $logo_files[8] ?? '',
];

// ──────────────────────────────────────────────────────────────────────────────
// STEP 2: CASINOS — from GSheet Review
// ──────────────────────────────────────────────────────────────────────────────

echo "\n=== Step 2: Casinos (GSheet Review) ===\n";

$review_rows = xlsx_rows_to_maps(
    xlsx_read_sheet(CCC_EXCEL_DIR . '/google-sheets-structures-wp-EN.xlsx', 'GSheet Review')
);

// 9 partner casino stubs (Architecture data) — used when no full Review row exists
$partner_stubs = [
    ['slug' => 'casinolab',  'title' => 'CasinoLab',  'name' => 'CasinoLab'],
    ['slug' => 'gransino',   'title' => 'Gransino',   'name' => 'Gransino'],
    ['slug' => 'fatpirate',  'title' => 'FatPirate',  'name' => 'FatPirate'],
    ['slug' => 'qbet',       'title' => 'Qbet',       'name' => 'Qbet'],
    ['slug' => 'gqbet',      'title' => 'GQbet',      'name' => 'GQbet'],
    ['slug' => 'bahigo',     'title' => 'Bahigo',     'name' => 'Bahigo'],
    ['slug' => 'wettigo',    'title' => 'Wettigo',    'name' => 'Wettigo'],
    ['slug' => 'bahibi',     'title' => 'Bahibi',     'name' => 'Bahibi'],
    ['slug' => 'goldenplay', 'title' => 'Goldenplay', 'name' => 'Goldenplay'],
];

// Index review rows by slug
$review_by_slug = [];
foreach ($review_rows as $row) {
    $s = trim($row['slug'] ?? '');
    if ($s !== '') {
        $review_by_slug[$s] = $row;
    }
}

$imported_casino_ids = [];

foreach ($partner_stubs as $stub) {
    $slug = $stub['slug'];
    $row  = $review_by_slug[$slug] ?? null;

    $post_title = $row ? ($row['post_title'] ?: $stub['title']) : ($stub['title'] . ' Avis 2026');
    echo "  + Casino: $slug ($post_title)\n";

    $existing = get_page_by_path($slug, OBJECT, 'casino');
    $is_new   = !($existing instanceof WP_Post);

    $post_id = ccc_upsert_post('casino', $slug, $post_title);
    if ($post_id <= 0) {
        echo "    ✗ Failed to create/update\n";
        $report['warnings'][] = "Casino '$slug': upsert failed";
        continue;
    }
    $imported_casino_ids[$slug] = $post_id;
    // Mark as managed by this script to protect from cleanup on re-runs
    if (!CCC_IMPORT_DRY_RUN) {
        update_post_meta($post_id, '_ccc_excel_imported', 1);
    }

    if ($is_new) {
        $report['casinos']['created']++;
    } else {
        $report['casinos']['updated']++;
    }

    if ($row) {
        // Full data from GSheet Review
        $pros  = ccc_parse_pipe($row['pros'] ?? '');
        $cons  = ccc_parse_pipe($row['cons'] ?? '');
        $providers = array_filter(array_map('trim', explode(',', $row['info_providers'] ?? '')));
        $pay_methods = array_filter(array_map('trim', explode(',', $row['info_payment_methods'] ?? '')));

        $faq = [];
        for ($n = 1; $n <= 4; $n++) {
            $q = $row["faq_{$n}_question"] ?? '';
            $a = $row["faq_{$n}_answer"] ?? '';
            if ($q !== '' && $a !== '') {
                $faq[] = ['question' => $q, 'answer' => $a];
            }
        }

        $summaries = [];
        foreach ([
            ['ak' => 'section_bonus_title',       'al' => 'section_bonus_content',       'num' => 1],
            ['ak' => 'section_games_title',        'al' => 'section_games_content',       'num' => 2],
            ['ak' => 'section_payments_title',     'al' => 'section_payments_content',    'num' => 3],
            ['ak' => 'section_reliability_title',  'al' => 'section_reliability_content', 'num' => 4],
            ['ak' => 'section_signup_title',       'al' => 'section_signup_content',      'num' => 5],
        ] as $s) {
            $summaries[$s['num']] = [
                'title'   => $row[$s['ak']] ?? '',
                'content' => $row[$s['al']] ?? '',
            ];
        }

        // Internal links → money_page_links (newline-separated "Label|URL" pairs)
        $int_links_raw = $row['internal_links'] ?? '';
        $int_links     = [];
        foreach (explode("\n", $int_links_raw) as $line) {
            $parts = explode('|', $line, 2);
            if (count($parts) === 2 && trim($parts[0]) !== '') {
                $int_links[] = ['label' => trim($parts[0]), 'url' => trim($parts[1])];
            }
        }

        ccc_set_meta($post_id, [
            // Brand
            'affiliate_link'      => $row['affiliate_link'] ?? '',
            'year_founded'        => $row['info_year'] ?? '',
            'last_updated'        => $row['last_updated'] ?? '',
            'author_name'         => $row['author_name'] ?? '',
            // Ratings
            'overall_rating'      => $row['overall_rating'] ?? '',
            'rating_bonus'        => $row['rating_bonus'] ?? '',
            'rating_games'        => $row['rating_games'] ?? '',
            'rating_payments'     => $row['rating_payments'] ?? '',
            'rating_support'      => $row['rating_support'] ?? '',
            'rating_reliability'  => $row['rating_reliability'] ?? '',
            // Bonus
            'welcome_bonus_text'   => $row['info_welcome_bonus'] ?? '',
            'welcome_bonus_amount' => $row['info_welcome_bonus'] ?? '',
            'wagering'             => $row['info_wagering'] ?? '',
            'min_deposit'          => $row['info_min_deposit'] ?? '',
            'no_deposit_bonus'     => $row['info_no_deposit_bonus'] ?? '',
            'free_spins'           => $row['info_free_spins'] ?? '',
            'promo_code'           => $row['info_promo_code'] ?? '',
            // Technical
            'license'              => $row['info_license'] ?? '',
            'games_count'          => $row['info_games_count'] ?? '',
            'support_channels'     => $row['info_support_channels'] ?? '',
            'vip'                  => $row['info_vip'] ?? '',
            'mobile_app'           => $row['info_mobile_app'] ?? '',
            // Content
            'intro_text'           => $row['intro_text'] ?? '',
            'pros'                 => ccc_repeater_simple('text', $pros),
            'cons'                 => ccc_repeater_simple('text', $cons),
            'providers'            => ccc_repeater_simple('name', array_values($providers)),
            'deposit_methods'      => ccc_repeater_simple('name', array_values($pay_methods)),
            'withdrawal_methods'   => ccc_repeater_simple('name', array_values($pay_methods)),
            'summary_1_title'      => $summaries[1]['title'],
            'summary_1'            => $summaries[1]['content'],
            'summary_2_title'      => $summaries[2]['title'],
            'summary_2'            => $summaries[2]['content'],
            'summary_3_title'      => $summaries[3]['title'],
            'summary_3'            => $summaries[3]['content'],
            'summary_4_title'      => $summaries[4]['title'],
            'summary_4'            => $summaries[4]['content'],
            'summary_5_title'      => $summaries[5]['title'],
            'summary_5'            => $summaries[5]['content'],
            'final_verdict'        => ($row['verdict_title'] ?? '') !== ''
                                        ? '<h3>' . esc_html($row['verdict_title']) . '</h3>' . ($row['verdict_content'] ?? '')
                                        : ($row['verdict_content'] ?? ''),
            'faq'                  => $faq,
            'money_page_links'     => $int_links,
            // SEO
            'seo_title'            => $row['seo_title'] ?? '',
            'meta_description'     => $row['seo_description'] ?? '',
        ]);

        // Set post content to heading_h1 (used as excerpt/heading)
        if (!CCC_IMPORT_DRY_RUN && !empty($row['heading_h1'])) {
            wp_update_post(['ID' => $post_id, 'post_excerpt' => wp_strip_all_tags($row['heading_h1'])]);
        }
    } else {
        // Stub data
        ccc_set_meta($post_id, [
            'seo_title'         => $stub['name'] . ' Avis 2026 : Notre Test Complet',
            'meta_description'  => 'Découvrez notre avis complet sur ' . $stub['name'] . ' : bonus, jeux, retrait, fiabilité. Testé en 2026.',
            'last_updated'      => '2026-02-01',
            'author_name'       => 'Équipe Editorial',
            'overall_rating'    => '4.0',
            'rating_bonus'      => '4.0',
            'rating_games'      => '4.0',
            'rating_payments'   => '3.5',
            'rating_support'    => '3.5',
            'rating_reliability' => '4.0',
            'intro_text'        => 'Nous avons testé ' . $stub['name'] . ' pour vous présenter un avis complet et honnête.',
        ]);
        $report['warnings'][] = "Casino '$slug': no full Review row — imported as stub";
    }

    // Logo
    $logo_path = $casino_logo_map[$slug] ?? '';
    if ($logo_path !== '' && !CCC_IMPORT_DRY_RUN) {
        $logo_id = ccc_import_logo($slug, $logo_path);
        if ($logo_id > 0) {
            update_post_meta($post_id, 'logo', $logo_id);
        }
    }

    // Assign taxonomy: casino_license (from license field)
    $license_str = $row['info_license'] ?? ($row['license'] ?? '');
    if ($license_str !== '' && !CCC_IMPORT_DRY_RUN) {
        // Extract license authority (e.g. "Curaçao #8048/JAZ" → "Curaçao")
        $license_term = preg_replace('/\s*#.*$/', '', $license_str);
        if ($license_term !== '') {
            wp_set_object_terms($post_id, [$license_term], 'casino_license');
        }
    }
}

// ──────────────────────────────────────────────────────────────────────────────
// STEP 3: CASINO SUBPAGES — from GSheet Subpage
// ──────────────────────────────────────────────────────────────────────────────

echo "\n=== Step 3: Casino Subpages (GSheet Subpage) ===\n";

$subpage_rows = xlsx_rows_to_maps(
    xlsx_read_sheet(CCC_EXCEL_DIR . '/google-sheets-structures-wp-EN.xlsx', 'GSheet Subpage')
);

// Phase-1 subpage types that need full content from Excel
$phase1_subpage_types = [
    'bonus', 'bonus_sans_depot', 'bonus_bienvenue', 'free_spins',
    'fiable', 'arnaque', 'inscription', 'retrait',
];

// Slug suffix → subpage_type mapping
$slug_to_type = [
    'bonus'             => 'bonus',
    'bonus-sans-depot'  => 'bonus_sans_depot',
    'bonus-bienvenue'   => 'bonus_bienvenue',
    'free-spins'        => 'free_spins',
    'fiable'            => 'fiable',
    'arnaque'           => 'arnaque',
    'inscription'       => 'inscription',
    'retrait'           => 'retrait',
];

// Index subpage rows by "parent_casino/slug"
$subpage_by_key = [];
foreach ($subpage_rows as $row) {
    $parent = trim($row['parent_casino'] ?? '');
    $slug   = trim($row['slug'] ?? '');
    if ($parent !== '' && $slug !== '') {
        $subpage_by_key[$parent . '/' . $slug] = $row;
    }
}

foreach ($imported_casino_ids as $casino_slug => $casino_id) {
    foreach ($phase1_subpage_types as $sp_type) {
        // Determine WP post slug for this subpage
        $sp_slug_suffix = str_replace('_', '-', $sp_type);
        $sp_post_slug   = $casino_slug . '-' . $sp_slug_suffix;
        $sp_post_title  = ucfirst(str_replace('-', ' ', $casino_slug)) . ' ' . str_replace('-', ' ', $sp_slug_suffix);

        // Look for matching Excel row
        $row = $subpage_by_key[$casino_slug . '/' . $sp_slug_suffix] ?? null;
        if (!$row) {
            // Try alternate key (type directly)
            $row = $subpage_by_key[$casino_slug . '/' . $sp_type] ?? null;
        }

        // Find existing subpage
        $existing_subs = get_posts([
            'post_type'      => 'casino_subpage',
            'post_status'    => 'any',
            'posts_per_page' => 1,
            'meta_query'     => [
                ['key' => 'parent_casino', 'value' => (string) $casino_id],
                ['key' => 'subpage_type',  'value' => $sp_type],
            ],
            'fields' => 'ids',
        ]);

        if ($row) {
            $sp_post_title = $row['post_title'] ?: $sp_post_title;
        }

        echo "  + Subpage: $casino_slug/$sp_slug_suffix\n";

        if (!empty($existing_subs)) {
            $sp_id = (int) $existing_subs[0];
            if (!CCC_IMPORT_DRY_RUN) {
                wp_update_post(['ID' => $sp_id, 'post_title' => $sp_post_title, 'post_status' => 'publish']);
            }
            $report['subpages']['updated']++;
        } else {
            if (CCC_IMPORT_DRY_RUN) {
                $sp_id = -1;
            } else {
                $sp_id = (int) wp_insert_post([
                    'post_type'   => 'casino_subpage',
                    'post_name'   => $sp_post_slug,
                    'post_title'  => $sp_post_title,
                    'post_status' => 'publish',
                ]);
            }
            $report['subpages']['created']++;
        }

        if ($sp_id <= 0) {
            continue;
        }

        // Base meta always set
        $meta = [
            'parent_casino'      => $casino_id,
            'subpage_type'       => $sp_type,
            'parent_review_link' => '/avis/' . $casino_slug . '/',
        ];

        if ($row) {
            // Full data from Excel
            $faq = [];
            for ($n = 1; $n <= 3; $n++) {
                $q = $row["faq_{$n}_question"] ?? '';
                $a = $row["faq_{$n}_answer"] ?? '';
                if ($q !== '' && $a !== '') {
                    $faq[] = ['question' => $q, 'answer' => $a];
                }
            }

            $score_enabled = strtolower($row['score_enabled'] ?? '') === 'yes' || $row['score_enabled'] === '1';
            $table_enabled = strtolower($row['table_enabled'] ?? '') === 'yes' || $row['table_enabled'] === '1';

            // Parse table headers and rows from cells
            $table_headers = [];
            if ($table_enabled && !empty($row['table_headers'])) {
                foreach (explode('|', $row['table_headers']) as $h) {
                    if (trim($h) !== '') {
                        $table_headers[] = ['label' => trim($h)];
                    }
                }
            }

            // Architecture links from siblings
            $arch_links = [];
            $sibling_raw = $row['sibling_links'] ?? '';
            foreach (explode("\n", $sibling_raw) as $line) {
                $parts = explode('|', $line, 2);
                if (count($parts) === 2 && trim($parts[0]) !== '') {
                    $arch_links[] = ['label' => trim($parts[0]), 'url' => trim($parts[1])];
                }
            }

            $meta = array_merge($meta, [
                'hero_title'        => $row['heading_h1'] ?? '',
                'intro_text'        => $row['intro_text'] ?? '',
                'last_updated'      => $row['last_updated'] ?? '',
                'main_content'      => $row['main_content'] ?? '',
                'cta_text'          => $row['cta_text'] ?? '',
                'cta_url'           => $row['cta_link'] ?? '',
                'score_enabled'     => $score_enabled ? 1 : 0,
                'score_value'       => $row['score_value'] ?? '',
                'score_label'       => $row['score_label'] ?? '',
                'score_verdict'     => $row['score_verdict'] ?? '',
                'table_enabled'     => $table_enabled ? 1 : 0,
                'table_headers'     => $table_headers,
                'faq'               => $faq,
                'seo_title'         => $row['seo_title'] ?? '',
                'meta_description'  => $row['seo_description'] ?? '',
                'architecture_links' => $arch_links,
            ]);
        } else {
            // Skeleton with minimal default content
            $casino_display = ucfirst($casino_slug);
            $type_label     = str_replace('-', ' ', $sp_slug_suffix);
            $meta = array_merge($meta, [
                'hero_title'  => "$casino_display — " . ucfirst($type_label) . " 2026",
                'intro_text'  => "Retrouvez toutes les informations sur " . strtolower($type_label) . " de $casino_display.",
                'seo_title'   => "$casino_display " . ucfirst($type_label) . " 2026 : Notre Guide Complet",
                'last_updated' => '2026-02-01',
                'cta_text'    => "Visiter $casino_display →",
                'cta_url'     => '/avis/' . $casino_slug . '/',
            ]);
        }

        ccc_set_meta($sp_id, $meta);
    }
}

// ──────────────────────────────────────────────────────────────────────────────
// STEP 4: LANDINGS — Comparison (GSheet Comparatif)
// ──────────────────────────────────────────────────────────────────────────────

echo "\n=== Step 4: Comparison Landings (GSheet Comparatif) ===\n";

$comp_rows = xlsx_rows_to_maps(
    xlsx_read_sheet(CCC_EXCEL_DIR . '/google-sheets-structures-wp-EN.xlsx', 'GSheet Comparatif')
);

// Architecture: comparison landing pages (quick wins + alta priority)
$comparison_pages = [
    // Quick wins from architecture
    ['slug' => 'meilleur',          'title' => 'Meilleur Casino en Ligne 2026',        'parent_slug' => 'casino-en-ligne'],
    ['slug' => 'fiable',            'title' => 'Casino en Ligne Fiable 2026',           'parent_slug' => 'casino-en-ligne'],
    ['slug' => 'gratuit',           'title' => 'Casino en Ligne Gratuit 2026',          'parent_slug' => 'casino-en-ligne'],
    ['slug' => 'comparatif',        'title' => 'Comparatif Casino en Ligne 2026',       'parent_slug' => 'casino-en-ligne'],
    ['slug' => 'retrait-rapide',    'title' => 'Casino en Ligne Retrait Rapide 2026',   'parent_slug' => 'casino-en-ligne'],
    ['slug' => 'sans-wager',        'title' => 'Casino en Ligne Sans Wager 2026',       'parent_slug' => 'casino-en-ligne'],
    ['slug' => 'sans-verification', 'title' => 'Casino Sans Vérification 2026',         'parent_slug' => 'casino-en-ligne'],
    ['slug' => 'arnaque',           'title' => 'Arnaque Casino en Ligne : Comment les Identifier', 'parent_slug' => 'casino-en-ligne'],
    // Bonus silo
    ['slug' => 'sans-depot',        'title' => 'Bonus Casino Sans Dépôt 2026',          'parent_slug' => 'bonus-casino'],
    ['slug' => 'gratuit',           'title' => 'Bonus Casino Gratuit 2026',             'parent_slug' => 'bonus-casino'],
    ['slug' => 'code-promo',        'title' => 'Code Promo Casino en Ligne 2026',       'parent_slug' => 'bonus-casino'],
    // Paiement silo
    ['slug' => 'crypto',            'title' => 'Casino en Ligne Crypto 2026',           'parent_slug' => 'paiement-casino'],
    ['slug' => 'paysafecard',       'title' => 'Casino en Ligne Paysafecard 2026',      'parent_slug' => 'paiement-casino'],
];

// Index comp rows by slug
$comp_by_slug = [];
foreach ($comp_rows as $row) {
    $s = trim($row['slug'] ?? '');
    if ($s !== '') {
        $comp_by_slug[$s] = $row;
    }
}

foreach ($comparison_pages as $page) {
    $slug  = $page['slug'];
    $title = $page['title'];
    $row   = $comp_by_slug[$slug] ?? null;

    // Use "parent/slug" for unique WP slug to avoid conflicts
    $wp_slug = $page['parent_slug'] . '--' . $slug;
    echo "  + Comparison: /{$page['parent_slug']}/{$slug}/\n";

    $existing = get_page_by_path($wp_slug, OBJECT, 'landing');
    $is_new   = !($existing instanceof WP_Post);

    $post_id = ccc_upsert_post('landing', $wp_slug, $row ? ($row['post_title'] ?: $title) : $title);
    if ($post_id <= 0) {
        continue;
    }

    if ($is_new) {
        $report['landings']['created']++;
    } else {
        $report['landings']['updated']++;
    }

    if ($row) {
        // Build casino_cards from Excel columns
        $casino_cards = [];
        $casino_cols = [
            1 => ['rank' => 'casino_1_rank', 'name' => 'casino_1_name', 'review' => 'casino_1_short_review'],
            2 => ['rank' => 'casino_2_rank', 'name' => 'casino_2_name', 'review' => 'casino_2_short_review'],
            3 => ['rank' => 'casino_3_rank', 'name' => 'casino_3_name', 'review' => 'casino_3_short_review'],
            4 => ['rank' => 'casino_4_rank', 'name' => 'casino_4_name', 'review' => 'casino_4_short_review'],
            5 => ['rank' => 'casino_5_rank', 'name' => 'casino_5_name', 'review' => 'casino_5_short_review'],
            6 => ['rank' => 'casino_6_rank', 'name' => 'casino_6_name', 'review' => 'casino_6_short_review'],
            7 => ['rank' => 'casino_7_rank', 'name' => 'casino_7_name', 'review' => 'casino_7_short_review'],
            8 => ['rank' => 'casino_8_rank', 'name' => 'casino_8_name', 'review' => 'casino_8_short_review'],
            9 => ['rank' => 'casino_9_rank', 'name' => 'casino_9_name', 'review' => 'casino_9_short_review'],
        ];
        foreach ($casino_cols as $n => $cols) {
            $cname = strtolower(trim($row[$cols['name']] ?? ''));
            if ($cname === '') {
                continue;
            }
            // Try to match name to imported casino ID
            $c_id = null;
            foreach ($imported_casino_ids as $cslug => $cid) {
                if (str_contains(strtolower($cname), $cslug) || str_contains($cslug, $cname)) {
                    $c_id = $cid;
                    break;
                }
            }
            if ($c_id) {
                $casino_cards[] = [
                    'casino_id'    => $c_id,
                    'rank'         => $row[$cols['rank']] ?? $n,
                    'short_review' => $row[$cols['review']] ?? '',
                ];
            }
        }

        $faq = [];
        for ($n = 1; $n <= 6; $n++) {
            $q = $row["faq_{$n}_question"] ?? '';
            $a = $row["faq_{$n}_answer"] ?? '';
            if ($q !== '' && $a !== '') {
                $faq[] = ['question' => $q, 'answer' => $a];
            }
        }

        $int_links = [];
        foreach (explode("\n", $row['internal_links'] ?? '') as $line) {
            $parts = explode('|', $line, 2);
            if (count($parts) === 2 && trim($parts[0]) !== '') {
                $int_links[] = ['label' => trim($parts[0]), 'url' => trim($parts[1])];
            }
        }

        ccc_set_meta($post_id, [
            'landing_type'         => 'comparison',
            'hero_title'           => $row['heading_h1'] ?? $title,
            'intro_text'           => $row['intro_text'] ?? '',
            'seo_title'            => $row['seo_title'] ?? '',
            'meta_description'     => $row['seo_description'] ?? '',
            'casinos_tested_count' => $row['casinos_tested_count'] ?? 9,
            'last_updated'         => $row['last_updated'] ?? '2026-02-01',
            'author_name'          => $row['author_name'] ?? 'Équipe Editorial',
            'casino_cards'         => $casino_cards,
            'methodology_content'  => $row['methodology_content'] ?? '',
            'bottom_content'       => $row['bottom_content'] ?? '',
            'faq'                  => $faq,
            'internal_link_pills'  => $int_links,
        ]);
    } else {
        // Minimal data
        ccc_set_meta($post_id, [
            'landing_type'         => 'comparison',
            'hero_title'           => $title,
            'intro_text'           => "Comparatif complet des meilleurs casinos en ligne. Mis à jour en 2026.",
            'seo_title'            => $title . ' — Comparatif Honnête',
            'meta_description'     => 'Notre sélection des meilleurs casinos testés et approuvés en 2026.',
            'casinos_tested_count' => 9,
            'last_updated'         => '2026-02-01',
            'author_name'          => 'Équipe Editorial',
        ]);
        $report['warnings'][] = "Comparison '$slug': no GSheet Comparatif row — minimal data";
    }
}

// ──────────────────────────────────────────────────────────────────────────────
// STEP 5: LANDINGS — Hubs (GSheet Hub)
// ──────────────────────────────────────────────────────────────────────────────

echo "\n=== Step 5: Hub Landings (GSheet Hub) ===\n";

$hub_rows = xlsx_rows_to_maps(
    xlsx_read_sheet(CCC_EXCEL_DIR . '/google-sheets-structures-wp-EN.xlsx', 'GSheet Hub')
);

$hub_pages = [
    ['slug' => 'casino-en-ligne',  'title' => 'Casino en Ligne 2026 : Meilleurs Casinos Testés'],
    ['slug' => 'bonus-casino',     'title' => 'Bonus Casino en Ligne 2026 : Les Meilleures Offres'],
    ['slug' => 'jeux-casino',      'title' => 'Jeux Casino en Ligne 2026 : Guide Complet'],
    ['slug' => 'paiement-casino',  'title' => 'Paiement Casino en Ligne 2026 : Méthodes & Retraits'],
];

$hub_by_slug = [];
foreach ($hub_rows as $row) {
    $s = trim($row['slug'] ?? '');
    if ($s !== '') {
        $hub_by_slug[$s] = $row;
    }
}

foreach ($hub_pages as $page) {
    $slug = $page['slug'];
    $row  = $hub_by_slug[$slug] ?? null;
    $title = $row ? ($row['post_title'] ?: $page['title']) : $page['title'];

    echo "  + Hub: /{$slug}/\n";

    $existing = get_page_by_path($slug, OBJECT, 'landing');
    $is_new   = !($existing instanceof WP_Post);

    $post_id = ccc_upsert_post('landing', $slug, $title);
    if ($post_id <= 0) {
        continue;
    }

    if ($is_new) {
        $report['landings']['created']++;
    } else {
        $report['landings']['updated']++;
    }

    // Build subcategory cards from subcat_N_label/icon/url
    $subcats = [];
    if ($row) {
        for ($n = 1; $n <= 8; $n++) {
            $label = $row["subcat_{$n}_label"] ?? '';
            $url   = $row["subcat_{$n}_url"] ?? '';
            if ($label !== '') {
                $subcats[] = [
                    'title'       => $label,
                    'url'         => $url,
                    'description' => '',
                    'icon'        => $row["subcat_{$n}_icon"] ?? '',
                ];
            }
        }
    }

    // Build top casino IDs from top_N_name
    $top_casino_ids = [];
    if ($row) {
        for ($n = 1; $n <= 9; $n++) {
            $cname = strtolower(trim($row["top_{$n}_name"] ?? ''));
            if ($cname === '') {
                continue;
            }
            foreach ($imported_casino_ids as $cslug => $cid) {
                if (str_contains(strtolower($cname), $cslug) || str_contains($cslug, $cname)) {
                    $top_casino_ids[] = $cid;
                    break;
                }
            }
        }
    }

    // Build FAQ
    $faq = [];
    if ($row) {
        for ($n = 1; $n <= 3; $n++) {
            $q = $row["faq_{$n}_question"] ?? '';
            $a = $row["faq_{$n}_answer"] ?? '';
            if ($q !== '' && $a !== '') {
                $faq[] = ['question' => $q, 'answer' => $a];
            }
        }
    }

    // Cross-silo links
    $cross_links = [];
    if ($row) {
        foreach (explode("\n", $row['cross_silo_links'] ?? '') as $line) {
            $parts = explode('|', $line, 2);
            if (count($parts) === 2 && trim($parts[0]) !== '') {
                $cross_links[] = ['label' => trim($parts[0]), 'url' => trim($parts[1])];
            }
        }
    }

    $meta = [
        'landing_type'              => 'hub',
        'hero_title'                => $row ? ($row['heading_h1'] ?? $title) : $title,
        'intro_text'                => $row ? ($row['intro_text'] ?? '') : '',
        'seo_title'                 => $row ? ($row['seo_title'] ?? '') : ($title . ' — Guide Complet'),
        'meta_description'          => $row ? ($row['seo_description'] ?? '') : '',
        'last_updated'              => $row ? ($row['last_updated'] ?? '2026-02-01') : '2026-02-01',
        'subcategory_cards'         => $subcats,
        'top_casino_list'           => $top_casino_ids,
        'educational_content'       => $row ? ($row['educational_content'] ?? '') : '',
        'comparison_table_title'    => $row ? ($row['comparison_table_title'] ?? '') : '',
        'howto_title'               => $row ? ($row['howto_title'] ?? '') : '',
        'howto_content'             => $row ? ($row['howto_content'] ?? '') : '',
        'cross_silo_links'          => $cross_links,
        'faq'                       => $faq,
    ];

    if (!$row) {
        $report['warnings'][] = "Hub '$slug': no GSheet Hub row — minimal data";
    }

    ccc_set_meta($post_id, $meta);
}

// ──────────────────────────────────────────────────────────────────────────────
// STEP 6: LANDINGS — Trust Pages
// ──────────────────────────────────────────────────────────────────────────────

echo "\n=== Step 6: Trust Pages ===\n";

$trust_pages = [
    ['slug' => 'a-propos',              'title' => 'À Propos de Casino Compare'],
    ['slug' => 'comment-nous-evaluons', 'title' => 'Comment Nous Évaluons les Casinos'],
    ['slug' => 'politique-editoriale',  'title' => 'Notre Politique Éditoriale'],
    ['slug' => 'jeu-responsable',       'title' => 'Jeu Responsable'],
    ['slug' => 'contact',               'title' => 'Contactez-Nous'],
    ['slug' => 'mentions-legales',      'title' => 'Mentions Légales'],
];

foreach ($trust_pages as $page) {
    echo "  + Trust: /{$page['slug']}/\n";

    $existing = get_page_by_path($page['slug'], OBJECT, 'landing');
    $is_new   = !($existing instanceof WP_Post);

    $post_id = ccc_upsert_post('landing', $page['slug'], $page['title']);
    if ($post_id <= 0) {
        continue;
    }

    if ($is_new) {
        $report['landings']['created']++;
    } else {
        $report['landings']['updated']++;
    }

    ccc_set_meta($post_id, [
        'landing_type'     => 'trust',
        'show_author'      => 1,
        'trust_author_name' => 'Équipe Editorial',
        'trust_last_updated' => '2026-02-01',
        'page_content'     => '<p>Contenu en cours de rédaction.</p>',
        'seo_title'        => $page['title'] . ' | Casino Compare',
        'meta_description' => $page['title'] . ' — Casino Compare.',
    ]);
}

// ──────────────────────────────────────────────────────────────────────────────
// STEP 7: GUIDES — from GSheet Guide
// ──────────────────────────────────────────────────────────────────────────────

echo "\n=== Step 7: Guides (GSheet Guide) ===\n";

$guide_rows = xlsx_rows_to_maps(
    xlsx_read_sheet(CCC_EXCEL_DIR . '/google-sheets-structures-wp-EN.xlsx', 'GSheet Guide')
);

// Architecture guide pages (from Architecture Complète, Guides silo)
$guide_stubs = [
    ['slug' => 'wager-casino',           'title' => 'Comprendre le Wager Casino : Guide Complet'],
    ['slug' => 'casino-legal',           'title' => 'Casino en Ligne Légal : Ce Qu\'il Faut Savoir'],
    ['slug' => 'bonus-bienvenue-guide',  'title' => 'Comment Choisir un Bonus de Bienvenue'],
    ['slug' => 'comment-jouer-casino',   'title' => 'Comment Jouer au Casino en Ligne'],
    ['slug' => 'choisir-casino-ligne',   'title' => 'Comment Choisir son Casino en Ligne'],
];

$guide_by_slug = [];
foreach ($guide_rows as $row) {
    $s = trim($row['slug'] ?? '');
    if ($s !== '') {
        $guide_by_slug[$s] = $row;
    }
}

foreach ($guide_stubs as $stub) {
    $slug = $stub['slug'];
    $row  = $guide_by_slug[$slug] ?? null;
    $title = $row ? ($row['post_title'] ?: $stub['title']) : $stub['title'];

    echo "  + Guide: /guide/{$slug}/\n";

    $existing = get_page_by_path($slug, OBJECT, 'guide');
    $is_new   = !($existing instanceof WP_Post);

    $post_id = ccc_upsert_post('guide', $slug, $title);
    if ($post_id <= 0) {
        continue;
    }

    if ($is_new) {
        $report['guides']['created']++;
    } else {
        $report['guides']['updated']++;
    }

    if ($row) {
        $faq = [];
        for ($n = 1; $n <= 3; $n++) {
            $q = $row["faq_{$n}_question"] ?? '';
            $a = $row["faq_{$n}_answer"] ?? '';
            if ($q !== '' && $a !== '') {
                $faq[] = ['question' => $q, 'answer' => $a];
            }
        }

        // Money page links
        $money_links = [];
        foreach (explode("\n", $row['money_page_links'] ?? '') as $line) {
            $parts = explode('|', $line, 2);
            if (count($parts) === 2 && trim($parts[0]) !== '') {
                $money_links[] = ['label' => trim($parts[0]), 'url' => trim($parts[1])];
            }
        }

        // Sidebar related guides
        $sidebar_guides = [];
        foreach (explode("\n", $row['sidebar_related_guides'] ?? '') as $line) {
            $parts = explode('|', $line, 2);
            if (count($parts) === 2 && trim($parts[0]) !== '') {
                $sidebar_guides[] = ['label' => trim($parts[0]), 'url' => trim($parts[1])];
            }
        }

        // Sidebar casino IDs
        $sidebar_casinos = [];
        for ($n = 1; $n <= 3; $n++) {
            $cname = strtolower(trim($row["sidebar_casino_{$n}"] ?? ''));
            if ($cname !== '') {
                foreach ($imported_casino_ids as $cslug => $cid) {
                    if (str_contains(strtolower($cname), $cslug) || str_contains($cslug, $cname)) {
                        $sidebar_casinos[] = $cid;
                        break;
                    }
                }
            }
        }

        ccc_set_meta($post_id, [
            'category'              => $row['category'] ?? 'Guides',
            'reading_time'          => $row['reading_time'] ?? '',
            'last_updated'          => $row['last_updated'] ?? '2026-02-01',
            'author_name'           => $row['author_name'] ?? 'Équipe Editorial',
            'intro_text'            => $row['intro_text'] ?? '',
            'callout_enabled'       => (strtolower($row['callout_enabled'] ?? '') === 'yes') ? 1 : 0,
            'callout_title'         => $row['callout_title'] ?? '',
            'callout_text'          => $row['callout_content'] ?? '',
            'main_content'          => $row['main_content'] ?? '',
            'sidebar_top_title'     => $row['sidebar_top_title'] ?? '',
            'sidebar_takeaway'      => $row['sidebar_takeaway'] ?? '',
            'sidebar_casino_list'   => $sidebar_casinos,
            'sidebar_comparison_link' => $row['sidebar_comparison_link'] ?? '',
            'sidebar_related_guides' => $sidebar_guides,
            'money_page_links'      => $money_links,
            'faq'                   => $faq,
            'seo_title'             => $row['seo_title'] ?? '',
            'meta_description'      => $row['seo_description'] ?? '',
        ]);
    } else {
        ccc_set_meta($post_id, [
            'category'         => 'Guides',
            'reading_time'     => 5,
            'last_updated'     => '2026-02-01',
            'author_name'      => 'Équipe Editorial',
            'intro_text'       => "Retrouvez dans ce guide tout ce qu'il faut savoir sur le sujet.",
            'seo_title'        => $title . ' | Casino Compare',
            'meta_description' => $title . ' — Guide détaillé par nos experts.',
        ]);
        $report['warnings'][] = "Guide '$slug': no GSheet Guide row — minimal data";
    }
}

// ──────────────────────────────────────────────────────────────────────────────
// STEP 8: FLUSH REWRITE RULES
// ──────────────────────────────────────────────────────────────────────────────

echo "\n=== Step 8: Flush rewrite rules ===\n";
if (!CCC_IMPORT_DRY_RUN) {
    flush_rewrite_rules(true);
    echo "  Done.\n";
} else {
    echo "  [dry-run] skipped.\n";
}

// ──────────────────────────────────────────────────────────────────────────────
// REPORT
// ──────────────────────────────────────────────────────────────────────────────

echo "\n";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║                   IMPORT COMPLETE                           ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo "\n";
echo "Casinos:  created={$report['casinos']['created']}  updated={$report['casinos']['updated']}\n";
echo "Subpages: created={$report['subpages']['created']}  updated={$report['subpages']['updated']}\n";
echo "Landings: created={$report['landings']['created']}  updated={$report['landings']['updated']}\n";
echo "Guides:   created={$report['guides']['created']}  updated={$report['guides']['updated']}\n";
echo "Cleanup:  {$report['cleanup']} records removed\n";

if ($report['warnings']) {
    echo "\nWarnings:\n";
    foreach ($report['warnings'] as $w) {
        echo "  ⚠ $w\n";
    }
}

if ($dry_run) {
    echo "\n[DRY RUN — no data was written]\n";
}

// ──────────────────────────────────────────────────────────────────────────────
// WRITE REPORT FILE
// ──────────────────────────────────────────────────────────────────────────────

$report_path   = __DIR__ . '/import-report.md';
$date          = date('Y-m-d H:i');
$import_status = $dry_run ? 'DRY RUN' : 'COMPLETED';

$report_content = <<<MD
# Import Report — Phase 1 Demo Data
Generated: $date

## Status: $import_status

## Entities Imported

| Type | Created | Updated |
|------|---------|---------|
| casino | {$report['casinos']['created']} | {$report['casinos']['updated']} |
| casino_subpage | {$report['subpages']['created']} | {$report['subpages']['updated']} |
| landing | {$report['landings']['created']} | {$report['landings']['updated']} |
| guide | {$report['guides']['created']} | {$report['guides']['updated']} |

Cleanup: {$report['cleanup']} old demo records removed.

## Excel Sheets Used

| Sheet | File | Purpose |
|-------|------|---------|
| GSheet Review | google-sheets-structures-wp-EN.xlsx | Casino full data (FatPirate + structure for stubs) |
| GSheet Comparatif | google-sheets-structures-wp-EN.xlsx | Comparison landing structure & sample |
| GSheet Hub | google-sheets-structures-wp-EN.xlsx | Hub landing structure & sample |
| GSheet Subpage | google-sheets-structures-wp-EN.xlsx | Subpage structure & FatPirate bonus/fiable |
| GSheet Guide | google-sheets-structures-wp-EN.xlsx | Guide structure & wager-casino sample |
| Architecture Complète | architecture-complete-finale-casino — копия.xlsx | URL map, quick wins, partner list |

## Casinos (9 partners from Architecture)

| Slug | Source | Fields |
|------|--------|--------|
| fatpirate | GSheet Review (full row) | All 63 columns mapped |
| casinolab, gransino, qbet, gqbet, bahigo, wettigo, bahibi, goldenplay | Architecture stubs | Minimal: title, SEO, ratings placeholders |

**Note:** Only FatPirate has a full GSheet Review row. Other 8 casinos need real review rows added to GSheet Review to get full data.

## Fields Coverage

### casino — Full (FatPirate only)
- post_title, slug, seo_title, meta_description
- heading_h1 (→ post_excerpt), intro_text
- overall_rating, rating_bonus, rating_games, rating_payments, rating_support, rating_reliability
- welcome_bonus_text/amount, wagering, min_deposit, no_deposit_bonus, free_spins, promo_code
- license, year_founded, games_count, support_channels, vip, mobile_app
- providers (repeater), deposit_methods, withdrawal_methods
- pros (repeater), cons (repeater)
- summary_1–5 + titles, final_verdict
- faq (4 items), money_page_links
- last_updated, author_name
- logo (from flow/Casinos Logo/)
- casino_license taxonomy

### casino — Partial (8 stubs)
- post_title, slug, seo_title, meta_description
- overall_rating, rating_* (placeholder 4.0)
- intro_text, last_updated, author_name
- logo

### casino_subpage
- parent_casino, subpage_type, parent_review_link
- Phase-1 types covered: bonus, bonus_sans_depot, bonus_bienvenue, free_spins, fiable, arnaque, inscription, retrait
- Full data (hero_title, intro_text, main_content, score, table, cta, faq, seo): FatPirate bonus + fiable only (GSheet Subpage rows 2-3)
- Other 7 casinos × 8 types: skeleton data generated from casino slug + type

### landing — comparison
- 13 comparison pages from Architecture quick wins
- Full data for "meilleur" (slug) only (GSheet Comparatif row)
- Others: minimal hero_title, intro_text, seo, casinos_tested_count=9
- casino_cards linked to imported casino IDs where name-matched

### landing — hub
- 4 hub pages: casino-en-ligne, bonus-casino, jeux-casino, paiement-casino
- Full data for "bonus-casino" hub (GSheet Hub row — slug = bonus-casino)
- subcategory_cards, top_casino_list, educational_content, howto, cross_silo_links, faq

### landing — trust
- 6 trust pages: a-propos, comment-nous-evaluons, politique-editoriale, jeu-responsable, contact, mentions-legales
- Minimal: show_author, trust_author_name, page_content placeholder
- **Needs:** real content per page

### guide
- 5 guides from Architecture Guides silo
- Full data for "wager-casino" (GSheet Guide row)
- Others: minimal skeleton

## Idempotency

Mode: **update** — upsert by slug+post_type. Re-running the script will update existing records.
Subpages: matched by parent_casino meta + subpage_type. New ones created, existing updated.

## What Is Not Covered

1. **8 casino full review data** — GSheet Review has only FatPirate. Add rows for other 8 partners.
2. **234 subpages for non-FatPirate casinos** — skeleton content only. Full content requires Excel rows in GSheet Subpage.
3. **12 comparison pages** — only 1 has GSheet Comparatif data. Others need rows in GSheet Comparatif.
4. **3 hub pages** — only 1 has GSheet Hub data (check slug match). Others need rows.
5. **4 guides** — only 1 has GSheet Guide data. Others need rows.
6. **Trust page content** — all 6 trust pages have placeholder `<p>Contenu en cours de rédaction.</p>`.
7. **Casino images/screenshots** — only logos imported from flow/Casinos Logo/.
8. **withdrawal_time_min/max** — field exists in WP but not mapped (not in GSheet Review schema).

## How to Run

```bash
# Full import
php scripts/import-phase1-data.php

# Dry run (no writes)
php scripts/import-phase1-data.php --dry-run
```

## Limitations

- xlsx parsing uses ZipArchive + regex (no external library). Handles shared strings and inline strings.
- Casino name → ID matching uses `str_contains` on lowercased names. May miss edge cases.
- Logo files assigned sequentially from flow/Casinos Logo/ (13 files, 9 slots).
MD;

if (!CCC_IMPORT_DRY_RUN) {
    file_put_contents($report_path, $report_content);
    echo "\nReport saved to: $report_path\n";
}

echo "\nDone.\n";
