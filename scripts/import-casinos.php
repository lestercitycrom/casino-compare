<?php
/**
 * Casino content import script.
 * Inserts 9 casino records directly via WP functions — much faster than Playwright UI.
 *
 * Usage: php scripts/import-casinos.php
 *
 * Idempotent: skips casinos that already exist by slug.
 * To re-import a casino, delete it from WP admin first.
 */

$_SERVER['HTTP_HOST'] = 'casino-compare.local';
$_SERVER['REQUEST_URI'] = '/';

require_once __DIR__ . '/../wp-load.php';

// ── Helpers ───────────────────────────────────────────────────────────────────

function import_upload_logo(string $file_path, string $casino_slug): int
{
    if (!file_exists($file_path)) {
        echo "    ⚠ Logo not found: $file_path\n";
        return 0;
    }

    $upload_dir = wp_upload_dir();
    $dest_dir   = $upload_dir['path'];
    $ext        = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
    $filename   = sanitize_file_name($casino_slug . '-logo.' . $ext);
    $dest_path  = $dest_dir . '/' . $filename;

    if (!copy($file_path, $dest_path)) {
        echo "    ⚠ Failed to copy logo to uploads\n";
        return 0;
    }

    $mime_types = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
    $mime_type  = $mime_types[$ext] ?? 'image/jpeg';

    $attachment_id = wp_insert_attachment([
        'guid'           => $upload_dir['url'] . '/' . $filename,
        'post_mime_type' => $mime_type,
        'post_title'     => $casino_slug . ' logo',
        'post_status'    => 'inherit',
    ], $dest_path);

    if (is_wp_error($attachment_id)) {
        echo "    ⚠ wp_insert_attachment failed: " . $attachment_id->get_error_message() . "\n";
        return 0;
    }

    require_once ABSPATH . 'wp-admin/includes/image.php';
    $meta = wp_generate_attachment_metadata($attachment_id, $dest_path);
    wp_update_attachment_metadata($attachment_id, $meta);

    return $attachment_id;
}

function import_casino(array $data): void
{
    // Idempotency check by slug
    $existing = get_page_by_path($data['slug'], OBJECT, 'casino');
    if ($existing instanceof WP_Post) {
        echo "  ↩ Skipping \"{$data['title']}\" — already exists (ID {$existing->ID})\n";
        return;
    }

    echo "  + Importing: {$data['title']}\n";

    // Insert post
    $post_id = wp_insert_post([
        'post_title'  => $data['title'],
        'post_name'   => $data['slug'],
        'post_type'   => 'casino',
        'post_status' => 'publish',
    ], true);

    if (is_wp_error($post_id)) {
        echo "    ✗ Failed: " . $post_id->get_error_message() . "\n";
        return;
    }

    // Upload and assign logo
    if (!empty($data['logo_file'])) {
        $logo_id = import_upload_logo($data['logo_file'], $data['slug']);
        if ($logo_id) {
            update_post_meta($post_id, 'logo', $logo_id);
            echo "    Logo uploaded (attachment ID $logo_id)\n";
        }
    }

    // Post meta fields
    $meta_fields = [
        // Brand
        'affiliate_link'       => $data['affiliate_link'] ?? '',
        'year_founded'         => $data['year_founded'] ?? '',
        'trustpilot_score'     => $data['trustpilot_score'] ?? '',
        'app_available'        => $data['app_available'] ?? '0',
        'last_updated'         => $data['last_updated'] ?? date('Y-m-d'),
        'author_name'          => $data['author_name'] ?? 'Équipe Casino Compare',
        // Rating
        'overall_rating'       => $data['overall_rating'] ?? '',
        'rating_bonus'         => $data['rating_bonus'] ?? '',
        'rating_games'         => $data['rating_games'] ?? '',
        'rating_payments'      => $data['rating_payments'] ?? '',
        'rating_support'       => $data['rating_support'] ?? '',
        'rating_reliability'   => $data['rating_reliability'] ?? '',
        // Bonus
        'welcome_bonus_text'   => $data['welcome_bonus_text'] ?? '',
        'welcome_bonus_amount' => $data['welcome_bonus_amount'] ?? '',
        'wagering'             => $data['wagering'] ?? '',
        'min_deposit'          => $data['min_deposit'] ?? '',
        'no_deposit_bonus'     => $data['no_deposit_bonus'] ?? '0',
        'free_spins'           => $data['free_spins'] ?? '',
        'promo_code'           => $data['promo_code'] ?? '',
        // Technical
        'license'              => $data['license'] ?? '',
        'license_number'       => $data['license_number'] ?? '',
        'games_count'          => $data['games_count'] ?? '',
        'support_channels'     => $data['support_channels'] ?? '',
        'vip'                  => $data['vip'] ?? '0',
        'mobile_app'           => $data['mobile_app'] ?? '0',
        'withdrawal_time_min'  => $data['withdrawal_time_min'] ?? '',
        'withdrawal_time_max'  => $data['withdrawal_time_max'] ?? '',
        // Content
        'intro_text'           => $data['intro_text'] ?? '',
        'final_verdict'        => $data['final_verdict'] ?? '',
        // SEO
        'seo_title'            => $data['seo_title'] ?? $data['title'],
        'meta_description'     => $data['meta_description'] ?? '',
    ];

    foreach ($meta_fields as $key => $value) {
        update_post_meta($post_id, $key, $value);
    }

    // Repeater fields: providers, deposit_methods, pros, cons, faq
    foreach (['providers', 'deposit_methods', 'withdrawal_methods', 'pros', 'cons', 'faq'] as $repeater_key) {
        if (!empty($data[$repeater_key])) {
            update_post_meta($post_id, $repeater_key, $data[$repeater_key]);
        }
    }

    // Summary sections
    foreach (['summary_1', 'summary_2', 'summary_3'] as $summary_key) {
        if (!empty($data[$summary_key])) {
            update_post_meta($post_id, $summary_key, $data[$summary_key]);
        }
        $title_key = $summary_key . '_title';
        if (!empty($data[$title_key])) {
            update_post_meta($post_id, $title_key, $data[$title_key]);
        }
    }

    // Taxonomy terms
    if (!empty($data['taxonomies'])) {
        foreach ($data['taxonomies'] as $taxonomy => $term_names) {
            $term_ids = [];
            foreach ($term_names as $name) {
                $term = get_term_by('name', $name, $taxonomy);
                if ($term instanceof WP_Term) {
                    $term_ids[] = $term->term_id;
                }
            }
            if ($term_ids) {
                wp_set_object_terms($post_id, $term_ids, $taxonomy);
            }
        }
    }

    // Trigger auto-skeleton subpages (via save_post_casino hook)
    do_action('save_post_casino', $post_id, get_post($post_id), true);

    echo "    ✓ Published (ID $post_id)\n";
}

// ── Logo paths ────────────────────────────────────────────────────────────────

$logo_dir = __DIR__ . '/data/logos/';
$logos    = glob($logo_dir . '*.jpg');
sort($logos);

// ── Casino data ───────────────────────────────────────────────────────────────
// Replace with real casino data before running in production.
// Fields marked (*) are required for the review page to look complete.

$casinos = [
    [
        'title'               => 'LunaBet Casino',                         // (*)
        'slug'                => 'lunabet-casino',                          // (*)
        'logo_file'           => $logos[0] ?? '',
        'affiliate_link'      => 'https://example.com/go/lunabet',
        'year_founded'        => '2019',
        'trustpilot_score'    => '4.1',
        'app_available'       => '1',
        'last_updated'        => '2026-03-01',
        'author_name'         => 'Sophie Martin',
        'overall_rating'      => '8.5',
        'rating_bonus'        => '9.0',
        'rating_games'        => '8.5',
        'rating_payments'     => '8.0',
        'rating_support'      => '8.5',
        'rating_reliability'  => '8.5',
        'welcome_bonus_text'  => '200% jusqu\'à 500€ + 100 tours gratuits',
        'welcome_bonus_amount'=> '500',
        'wagering'            => '35',
        'min_deposit'         => '20',
        'license'             => 'MGA',
        'license_number'      => 'MGA/B2C/123/2019',
        'games_count'         => '2500',
        'support_channels'    => 'Chat en direct, Email, Téléphone',
        'vip'                 => '1',
        'mobile_app'          => '1',
        'withdrawal_time_min' => '24',
        'withdrawal_time_max' => '72',
        'intro_text'          => 'LunaBet Casino est une plateforme de jeux en ligne reconnue pour sa vaste bibliothèque de jeux et ses bonus attrayants. Fondé en 2019, il propose une expérience de jeu sécurisée et fiable sous licence MGA.',
        'seo_title'           => 'LunaBet Casino Avis 2026 — Bonus, Jeux & Fiabilité',
        'meta_description'    => 'Découvrez notre avis complet sur LunaBet Casino : bonus 200% jusqu\'à 500€, sélection de jeux, paiements et fiabilité. Note : 8.5/10.',
        'pros'                => [['value' => 'Grande sélection de jeux (2500+)'], ['value' => 'Bonus de bienvenue généreux'], ['value' => 'Support 24/7 en français']],
        'cons'                => [['value' => 'Exigences de mise élevées (x35)'], ['value' => 'Délais de retrait variables']],
        'summary_1_title'     => 'Bonus et promotions',
        'summary_1'           => '<p>LunaBet propose un bonus de bienvenue généreux de 200% jusqu\'à 500€ accompagné de 100 tours gratuits. Les conditions de mise sont de x35, ce qui est raisonnable pour le secteur.</p>',
        'final_verdict'       => '<p>LunaBet Casino est un choix solide pour les joueurs recherchant une grande sélection de jeux et des bonus compétitifs. Notre note globale : 8.5/10.</p>',
        'faq'                 => [['faq_question' => 'LunaBet est-il fiable ?', 'faq_answer' => 'Oui, LunaBet est licencié par la MGA et dispose d\'une solide réputation.']],
        'taxonomies'          => ['casino_license' => ['MGA'], 'casino_feature' => ['Live Casino', 'Mobile', 'VIP'], 'payment_method' => ['Visa', 'Skrill'], 'game_type' => ['Slots', 'Live Dealer']],
    ],
    [
        'title'               => 'NovaJackpot',
        'slug'                => 'novajackpot',
        'logo_file'           => $logos[1] ?? '',
        'affiliate_link'      => 'https://example.com/go/novajackpot',
        'year_founded'        => '2020',
        'trustpilot_score'    => '3.9',
        'app_available'       => '0',
        'last_updated'        => '2026-03-01',
        'author_name'         => 'Marc Dupont',
        'overall_rating'      => '7.8',
        'rating_bonus'        => '8.5',
        'rating_games'        => '7.5',
        'rating_payments'     => '7.0',
        'rating_support'      => '8.0',
        'rating_reliability'  => '7.8',
        'welcome_bonus_text'  => '150% jusqu\'à 300€',
        'welcome_bonus_amount'=> '300',
        'wagering'            => '40',
        'min_deposit'         => '10',
        'license'             => 'Curacao',
        'license_number'      => 'CUR/2020/456',
        'games_count'         => '1800',
        'support_channels'    => 'Chat en direct, Email',
        'vip'                 => '0',
        'mobile_app'          => '0',
        'withdrawal_time_min' => '48',
        'withdrawal_time_max' => '120',
        'intro_text'          => 'NovaJackpot est un casino en ligne spécialisé dans les jackpots progressifs et les machines à sous. Licencié à Curaçao, il attire les amateurs de grosses cagnottes.',
        'seo_title'           => 'NovaJackpot Avis 2026 — Jackpots, Bonus & Fiabilité',
        'meta_description'    => 'Avis complet NovaJackpot : jackpots progressifs, bonus 150% jusqu\'à 300€ et conditions de jeu. Note : 7.8/10.',
        'pros'                => [['value' => 'Jackpots progressifs importants'], ['value' => 'Dépôt minimum faible (10€)'], ['value' => 'Interface intuitive']],
        'cons'                => [['value' => 'Exigences de mise élevées (x40)'], ['value' => 'Pas d\'application mobile'], ['value' => 'Support limité (pas de téléphone)']],
        'summary_1_title'     => 'Jackpots et jeux',
        'summary_1'           => '<p>NovaJackpot se distingue par ses jackpots progressifs avec des cagnottes pouvant dépasser plusieurs millions d\'euros. La sélection inclut les titres les plus populaires de NetEnt et Microgaming.</p>',
        'final_verdict'       => '<p>NovaJackpot convient aux chasseurs de jackpots cherchant de grandes cagnottes. Les conditions de mise restent le principal frein. Note : 7.8/10.</p>',
        'faq'                 => [['faq_question' => 'NovaJackpot accepte-t-il les joueurs français ?', 'faq_answer' => 'Oui, NovaJackpot accepte les joueurs francophones et propose le site en français.']],
        'taxonomies'          => ['casino_license' => ['Curacao'], 'casino_feature' => ['Mobile', 'No Deposit'], 'payment_method' => ['Visa', 'PayPal'], 'game_type' => ['Slots', 'Roulette']],
    ],
    [
        'title'               => 'HexaSpin Casino',
        'slug'                => 'hexaspin-casino',
        'logo_file'           => $logos[2] ?? '',
        'affiliate_link'      => 'https://example.com/go/hexaspin',
        'year_founded'        => '2018',
        'trustpilot_score'    => '4.5',
        'app_available'       => '1',
        'last_updated'        => '2026-03-01',
        'author_name'         => 'Julie Bernard',
        'overall_rating'      => '9.0',
        'rating_bonus'        => '9.5',
        'rating_games'        => '9.0',
        'rating_payments'     => '9.0',
        'rating_support'      => '9.0',
        'rating_reliability'  => '9.0',
        'welcome_bonus_text'  => '300% jusqu\'à 1000€ + 200 tours gratuits',
        'welcome_bonus_amount'=> '1000',
        'wagering'            => '30',
        'min_deposit'         => '20',
        'license'             => 'MGA',
        'license_number'      => 'MGA/B2C/789/2018',
        'games_count'         => '4000',
        'support_channels'    => 'Chat en direct 24/7, Email, Téléphone',
        'vip'                 => '1',
        'mobile_app'          => '1',
        'withdrawal_time_min' => '12',
        'withdrawal_time_max' => '48',
        'intro_text'          => 'HexaSpin Casino est l\'une des meilleures plateformes du marché, avec plus de 4000 jeux, un programme VIP exclusif et des bonus parmi les plus généreux. Fondé en 2018 sous licence MGA.',
        'seo_title'           => 'HexaSpin Casino Avis 2026 — Top Casino En Ligne',
        'meta_description'    => 'Avis HexaSpin Casino : 300% jusqu\'à 1000€, 4000+ jeux, retrait en 12h. Meilleure note de notre comparatif : 9.0/10.',
        'pros'                => [['value' => '4000+ jeux disponibles'], ['value' => 'Bonus exceptionnel (300% / 1000€)'], ['value' => 'Retrait rapide (12-48h)'], ['value' => 'Programme VIP exclusif']],
        'cons'                => [['value' => 'Dépôt minimum 20€']],
        'summary_1_title'     => 'Bonus de bienvenue',
        'summary_1'           => '<p>HexaSpin offre l\'un des bonus de bienvenue les plus généreux du marché : 300% jusqu\'à 1000€ plus 200 tours gratuits. Les conditions de mise de x30 sont parmi les plus favorables du secteur.</p>',
        'final_verdict'       => '<p>HexaSpin Casino est notre meilleure recommandation tous critères confondus. Excellent pour les joueurs exigeants. Note : 9.0/10.</p>',
        'faq'                 => [['faq_question' => 'HexaSpin est-il le meilleur casino en ligne ?', 'faq_answer' => 'HexaSpin est régulièrement classé parmi les meilleurs casinos en ligne grâce à son offre complète et sa fiabilité.']],
        'taxonomies'          => ['casino_license' => ['MGA'], 'casino_feature' => ['Live Casino', 'Mobile', 'VIP'], 'payment_method' => ['Visa', 'Skrill', 'PayPal'], 'game_type' => ['Slots', 'Live Dealer', 'Blackjack', 'Roulette']],
    ],
    [
        'title'               => 'StarCasino Plus',
        'slug'                => 'starcasino-plus',
        'logo_file'           => $logos[3] ?? '',
        'affiliate_link'      => 'https://example.com/go/starcasino',
        'year_founded'        => '2017',
        'trustpilot_score'    => '4.3',
        'app_available'       => '1',
        'last_updated'        => '2026-03-01',
        'author_name'         => 'Équipe Casino Compare',
        'overall_rating'      => '8.7',
        'rating_bonus'        => '8.5',
        'rating_games'        => '9.0',
        'rating_payments'     => '8.5',
        'rating_support'      => '8.5',
        'rating_reliability'  => '9.0',
        'welcome_bonus_text'  => '100% jusqu\'à 400€ + 50 tours gratuits',
        'welcome_bonus_amount'=> '400',
        'wagering'            => '33',
        'min_deposit'         => '15',
        'license'             => 'MGA',
        'license_number'      => 'MGA/B2C/321/2017',
        'games_count'         => '3200',
        'support_channels'    => 'Chat en direct, Email',
        'vip'                 => '1',
        'mobile_app'          => '1',
        'withdrawal_time_min' => '24',
        'withdrawal_time_max' => '72',
        'intro_text'          => 'StarCasino Plus est une référence dans l\'univers des casinos en ligne depuis 2017. Avec plus de 3200 jeux et une interface soignée, il séduit aussi bien les novices que les joueurs expérimentés.',
        'seo_title'           => 'StarCasino Plus Avis 2026 — Bonus & Meilleurs Jeux',
        'meta_description'    => 'Notre avis StarCasino Plus : 100% jusqu\'à 400€, 3200 jeux, interface premium. Note : 8.7/10.',
        'pros'                => [['value' => 'Interface premium et intuitive'], ['value' => '3200+ jeux'], ['value' => 'Application mobile performante']],
        'cons'                => [['value' => 'Bonus moins généreux que la concurrence']],
        'summary_1_title'     => 'Sélection de jeux',
        'summary_1'           => '<p>Avec 3200+ jeux issus des meilleurs éditeurs (NetEnt, Pragmatic Play, Evolution), StarCasino Plus couvre tous les styles de jeu : machines à sous, table games, live casino.</p>',
        'final_verdict'       => '<p>StarCasino Plus est un casino de confiance idéal pour les joueurs cherchant la qualité avant tout. Note : 8.7/10.</p>',
        'faq'                 => [['faq_question' => 'Quel est le dépôt minimum sur StarCasino Plus ?', 'faq_answer' => 'Le dépôt minimum est de 15€.']],
        'taxonomies'          => ['casino_license' => ['MGA'], 'casino_feature' => ['Live Casino', 'Mobile', 'VIP'], 'payment_method' => ['Visa', 'Skrill'], 'game_type' => ['Slots', 'Live Dealer', 'Roulette']],
    ],
    [
        'title'               => 'GoldenVegas Casino',
        'slug'                => 'goldenvegas-casino',
        'logo_file'           => $logos[4] ?? '',
        'affiliate_link'      => 'https://example.com/go/goldenvegas',
        'year_founded'        => '2021',
        'trustpilot_score'    => '3.7',
        'app_available'       => '0',
        'last_updated'        => '2026-03-01',
        'author_name'         => 'Équipe Casino Compare',
        'overall_rating'      => '7.5',
        'rating_bonus'        => '8.0',
        'rating_games'        => '7.5',
        'rating_payments'     => '7.0',
        'rating_support'      => '7.5',
        'rating_reliability'  => '7.5',
        'welcome_bonus_text'  => '100% jusqu\'à 200€',
        'welcome_bonus_amount'=> '200',
        'wagering'            => '45',
        'min_deposit'         => '20',
        'license'             => 'Curacao',
        'license_number'      => 'CUR/2021/789',
        'games_count'         => '1500',
        'support_channels'    => 'Chat en direct, Email',
        'vip'                 => '0',
        'mobile_app'          => '0',
        'withdrawal_time_min' => '48',
        'withdrawal_time_max' => '96',
        'intro_text'          => 'GoldenVegas Casino adopte l\'esthétique des grandes salles de Las Vegas. Fondé en 2021, ce casino relativement récent mise sur l\'ambiance et une sélection de jeux classiques.',
        'seo_title'           => 'GoldenVegas Casino Avis 2026 — Jeux & Bonus',
        'meta_description'    => 'Avis GoldenVegas Casino : ambiance Vegas, 1500 jeux, bonus 100% jusqu\'à 200€. Note : 7.5/10.',
        'pros'                => [['value' => 'Ambiance Las Vegas authentique'], ['value' => 'Jeux de table variés']],
        'cons'                => [['value' => 'Exigences de mise très élevées (x45)'], ['value' => 'Pas d\'application mobile'], ['value' => 'Catalogue de jeux limité']],
        'summary_1_title'     => 'Atmosphère et design',
        'summary_1'           => '<p>GoldenVegas reproduit fidèlement l\'atmosphère des casinos terrestres de Las Vegas avec une interface dorée et des sons typiques. Un choix pour les nostalgiques du casino classique.</p>',
        'final_verdict'       => '<p>GoldenVegas convient aux amateurs de l\'ambiance Vegas classique, mais ses conditions de mise élevées le pénalisent. Note : 7.5/10.</p>',
        'faq'                 => [['faq_question' => 'GoldenVegas Casino est-il disponible en français ?', 'faq_answer' => 'Oui, le site et le support sont entièrement disponibles en français.']],
        'taxonomies'          => ['casino_license' => ['Curacao'], 'casino_feature' => ['Mobile'], 'payment_method' => ['Visa'], 'game_type' => ['Slots', 'Blackjack', 'Roulette']],
    ],
    [
        'title'               => 'CryptoSpin Casino',
        'slug'                => 'cryptospin-casino',
        'logo_file'           => $logos[5] ?? '',
        'affiliate_link'      => 'https://example.com/go/cryptospin',
        'year_founded'        => '2022',
        'trustpilot_score'    => '4.0',
        'app_available'       => '1',
        'last_updated'        => '2026-03-01',
        'author_name'         => 'Équipe Casino Compare',
        'overall_rating'      => '8.2',
        'rating_bonus'        => '8.5',
        'rating_games'        => '8.0',
        'rating_payments'     => '9.0',
        'rating_support'      => '8.0',
        'rating_reliability'  => '8.0',
        'welcome_bonus_text'  => '200% jusqu\'à 600€ + 50 tours gratuits',
        'welcome_bonus_amount'=> '600',
        'wagering'            => '35',
        'min_deposit'         => '10',
        'license'             => 'Curacao',
        'license_number'      => 'CUR/2022/101',
        'games_count'         => '2000',
        'support_channels'    => 'Chat en direct 24/7, Email',
        'vip'                 => '1',
        'mobile_app'          => '1',
        'withdrawal_time_min' => '1',
        'withdrawal_time_max' => '24',
        'intro_text'          => 'CryptoSpin Casino est spécialisé dans les paiements en cryptomonnaies. Fondé en 2022, il offre des retraits ultra-rapides et accepte Bitcoin, Ethereum et une dizaine d\'autres cryptos.',
        'seo_title'           => 'CryptoSpin Casino Avis 2026 — Bitcoin & Crypto Casino',
        'meta_description'    => 'Avis CryptoSpin Casino : paiements crypto, retrait en 1h, bonus 200% jusqu\'à 600€. Note : 8.2/10.',
        'pros'                => [['value' => 'Retraits ultra-rapides (1-24h)'], ['value' => 'Accepte Bitcoin et 10+ cryptos'], ['value' => 'Bonus généreux']],
        'cons'                => [['value' => 'Moins adapté aux joueurs sans crypto']],
        'summary_1_title'     => 'Paiements crypto',
        'summary_1'           => '<p>CryptoSpin accepte Bitcoin, Ethereum, Litecoin et plus de 10 autres cryptomonnaies. Les retraits sont traités en moins d\'une heure, ce qui est exceptionnel dans le secteur.</p>',
        'final_verdict'       => '<p>CryptoSpin est le meilleur choix pour les joueurs utilisant les cryptomonnaies. Retraits rapides et bonus généreux. Note : 8.2/10.</p>',
        'faq'                 => [['faq_question' => 'Quelles cryptomonnaies CryptoSpin accepte-t-il ?', 'faq_answer' => 'Bitcoin, Ethereum, Litecoin, Ripple et 10+ autres cryptomonnaies sont acceptés.']],
        'taxonomies'          => ['casino_license' => ['Curacao'], 'casino_feature' => ['Mobile', 'No Deposit'], 'payment_method' => ['Bitcoin'], 'game_type' => ['Slots', 'Live Dealer']],
    ],
    [
        'title'               => 'RoyalFlush Casino',
        'slug'                => 'royalflush-casino',
        'logo_file'           => $logos[6] ?? '',
        'affiliate_link'      => 'https://example.com/go/royalflush',
        'year_founded'        => '2016',
        'trustpilot_score'    => '4.2',
        'app_available'       => '1',
        'last_updated'        => '2026-03-01',
        'author_name'         => 'Équipe Casino Compare',
        'overall_rating'      => '8.4',
        'rating_bonus'        => '8.0',
        'rating_games'        => '9.0',
        'rating_payments'     => '8.0',
        'rating_support'      => '8.5',
        'rating_reliability'  => '8.5',
        'welcome_bonus_text'  => '100% jusqu\'à 500€',
        'welcome_bonus_amount'=> '500',
        'wagering'            => '30',
        'min_deposit'         => '20',
        'license'             => 'MGA',
        'license_number'      => 'MGA/B2C/555/2016',
        'games_count'         => '3500',
        'support_channels'    => 'Chat en direct 24/7, Email, Téléphone',
        'vip'                 => '1',
        'mobile_app'          => '1',
        'withdrawal_time_min' => '24',
        'withdrawal_time_max' => '48',
        'intro_text'          => 'RoyalFlush Casino est une valeur sûre depuis 2016. Avec son immense bibliothèque de 3500 jeux et sa licence MGA, ce casino allie tradition et innovation.',
        'seo_title'           => 'RoyalFlush Casino Avis 2026 — Casino MGA de Confiance',
        'meta_description'    => 'Notre avis RoyalFlush Casino : 3500 jeux, licence MGA, bonus 100% jusqu\'à 500€. Note : 8.4/10.',
        'pros'                => [['value' => '3500+ jeux premium'], ['value' => 'Licence MGA — très fiable'], ['value' => 'Wagering favorable (x30)']],
        'cons'                => [['value' => 'Bonus moins agressif que la concurrence']],
        'summary_1_title'     => 'Fiabilité et licence',
        'summary_1'           => '<p>Avec sa licence MGA obtenue en 2016, RoyalFlush Casino est l\'un des opérateurs les plus fiables du marché. La MGA impose des standards stricts en matière de protection des joueurs et d\'équité.</p>',
        'final_verdict'       => '<p>RoyalFlush est le choix idéal pour les joueurs qui privilégient la fiabilité et une grande sélection de jeux. Note : 8.4/10.</p>',
        'faq'                 => [['faq_question' => 'RoyalFlush est-il sécurisé ?', 'faq_answer' => 'Oui, RoyalFlush est licencié par la Malta Gaming Authority (MGA), l\'une des régulations les plus strictes au monde.']],
        'taxonomies'          => ['casino_license' => ['MGA'], 'casino_feature' => ['Live Casino', 'Mobile', 'VIP'], 'payment_method' => ['Visa', 'Skrill', 'PayPal'], 'game_type' => ['Slots', 'Blackjack', 'Live Dealer', 'Roulette']],
    ],
    [
        'title'               => 'MegaWins Casino',
        'slug'                => 'megawins-casino',
        'logo_file'           => $logos[7] ?? '',
        'affiliate_link'      => 'https://example.com/go/megawins',
        'year_founded'        => '2020',
        'trustpilot_score'    => '3.8',
        'app_available'       => '0',
        'last_updated'        => '2026-03-01',
        'author_name'         => 'Équipe Casino Compare',
        'overall_rating'      => '7.6',
        'rating_bonus'        => '8.5',
        'rating_games'        => '7.5',
        'rating_payments'     => '7.0',
        'rating_support'      => '7.5',
        'rating_reliability'  => '7.5',
        'welcome_bonus_text'  => '250% jusqu\'à 750€ + 100 tours',
        'welcome_bonus_amount'=> '750',
        'wagering'            => '42',
        'min_deposit'         => '15',
        'license'             => 'Curacao',
        'license_number'      => 'CUR/2020/222',
        'games_count'         => '1700',
        'support_channels'    => 'Chat en direct, Email',
        'vip'                 => '0',
        'mobile_app'          => '0',
        'withdrawal_time_min' => '48',
        'withdrawal_time_max' => '96',
        'intro_text'          => 'MegaWins Casino mise sur des bonus spectaculaires pour attirer les nouveaux joueurs. Fondé en 2020, il propose une sélection correcte de jeux sous licence Curaçao.',
        'seo_title'           => 'MegaWins Casino Avis 2026 — Bonus & Jeux',
        'meta_description'    => 'Avis MegaWins Casino : bonus 250% jusqu\'à 750€, 1700 jeux, licence Curaçao. Note : 7.6/10.',
        'pros'                => [['value' => 'Bonus de bienvenue très agressif'], ['value' => '100 tours gratuits inclus']],
        'cons'                => [['value' => 'Wagering élevé (x42)'], ['value' => 'Pas d\'application mobile'], ['value' => 'Support limité']],
        'summary_1_title'     => 'Bonus de bienvenue',
        'summary_1'           => '<p>Le bonus 250% jusqu\'à 750€ de MegaWins est l\'un des plus généreux en valeur absolue. Attention cependant aux conditions de mise de x42 qui rendent difficile le retrait des gains.</p>',
        'final_verdict'       => '<p>MegaWins convient aux joueurs attirés par les gros bonus, à condition d\'accepter des wagerings élevés. Note : 7.6/10.</p>',
        'faq'                 => [['faq_question' => 'Comment retirer ses gains sur MegaWins ?', 'faq_answer' => 'Après avoir rempli les conditions de mise (x42), les retraits se font sous 48-96 heures.']],
        'taxonomies'          => ['casino_license' => ['Curacao'], 'casino_feature' => ['No Deposit'], 'payment_method' => ['Visa', 'PayPal'], 'game_type' => ['Slots', 'Roulette']],
    ],
    [
        'title'               => 'DiamondPlay Casino',
        'slug'                => 'diamondplay-casino',
        'logo_file'           => $logos[8] ?? '',
        'affiliate_link'      => 'https://example.com/go/diamondplay',
        'year_founded'        => '2015',
        'trustpilot_score'    => '4.4',
        'app_available'       => '1',
        'last_updated'        => '2026-03-01',
        'author_name'         => 'Sophie Martin',
        'overall_rating'      => '8.8',
        'rating_bonus'        => '8.5',
        'rating_games'        => '9.0',
        'rating_payments'     => '9.0',
        'rating_support'      => '8.5',
        'rating_reliability'  => '9.0',
        'welcome_bonus_text'  => '100% jusqu\'à 300€ + 150 tours',
        'welcome_bonus_amount'=> '300',
        'wagering'            => '30',
        'min_deposit'         => '10',
        'license'             => 'MGA',
        'license_number'      => 'MGA/B2C/001/2015',
        'games_count'         => '5000',
        'support_channels'    => 'Chat en direct 24/7, Email, Téléphone',
        'vip'                 => '1',
        'mobile_app'          => '1',
        'withdrawal_time_min' => '12',
        'withdrawal_time_max' => '24',
        'intro_text'          => 'DiamondPlay Casino est l\'un des opérateurs les plus établis du marché européen, fondé en 2015. Avec 5000+ jeux, des retraits express et une licence MGA, il représente l\'excellence du casino en ligne.',
        'seo_title'           => 'DiamondPlay Casino Avis 2026 — Le Meilleur Casino MGA',
        'meta_description'    => 'Avis DiamondPlay Casino : 5000+ jeux, retrait en 12h, bonus 100% + 150 tours. Meilleure fiabilité du marché. Note : 8.8/10.',
        'pros'                => [['value' => '5000+ jeux — la plus grande sélection'], ['value' => 'Retraits express (12-24h)'], ['value' => 'Licence MGA depuis 2015'], ['value' => 'Support téléphonique disponible']],
        'cons'                => [['value' => 'Bonus moins élevé que certains concurrents']],
        'summary_1_title'     => 'Catalogue de jeux',
        'summary_1'           => '<p>Avec plus de 5000 jeux disponibles — le plus grand catalogue de notre comparatif — DiamondPlay couvre absolument tout : slots classiques, jackpots, live casino avec croupiers en direct, poker et sports virtuels.</p>',
        'final_verdict'       => '<p>DiamondPlay Casino est notre deuxième meilleure recommandation, juste derrière HexaSpin. Un choix impeccable pour les joueurs exigeants en quête de qualité et de fiabilité. Note : 8.8/10.</p>',
        'faq'                 => [['faq_question' => 'DiamondPlay Casino est-il le plus fiable ?', 'faq_answer' => 'DiamondPlay est l\'un des plus anciens opérateurs sous licence MGA (depuis 2015), ce qui en fait l\'un des plus fiables du marché.']],
        'taxonomies'          => ['casino_license' => ['MGA'], 'casino_feature' => ['Live Casino', 'Mobile', 'VIP'], 'payment_method' => ['Visa', 'Skrill', 'PayPal', 'Bitcoin'], 'game_type' => ['Slots', 'Live Dealer', 'Blackjack', 'Roulette']],
    ],
];

// ── Run import ────────────────────────────────────────────────────────────────

echo "=== Casino Import ===\n\n";

foreach ($casinos as $casino) {
    import_casino($casino);
}

echo "\n=== Done ===\n";
echo count($casinos) . " casinos processed.\n";
