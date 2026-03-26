<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$current_license = (array) ($_GET['license'] ?? []);
$current_feature = (array) ($_GET['feature'] ?? []);
$current_payment = (array) ($_GET['payment'] ?? []);
$current_game    = (array) ($_GET['game'] ?? []);
$current_sort    = (string) ($_GET['sort'] ?? '');

$license_options = ['malta' => 'Malta (MGA)', 'curacao' => 'Curaçao', 'gibraltar' => 'Gibraltar', 'kahnawake' => 'Kahnawake'];
$feature_options = ['live-casino' => 'Live Casino', 'crypto' => 'Crypto', 'mobile-app' => 'App mobile', 'no-kyc' => 'Sans KYC'];
$payment_options = ['visa' => 'Visa/MC', 'paypal' => 'PayPal', 'bitcoin' => 'Bitcoin', 'paysafecard' => 'Paysafecard'];
$game_options    = ['slots' => 'Slots', 'blackjack' => 'Blackjack', 'roulette' => 'Roulette', 'poker' => 'Poker'];
$sort_options    = ['' => 'Tri par défaut', 'rating_desc' => 'Note ↓', 'rating_asc' => 'Note ↑', 'bonus_desc' => 'Bonus ↓'];
?>
<div class="filter-bar">

    <!-- Sort select -->
    <select class="filter-select" name="sort" onchange="this.form && this.form.submit()">
        <?php foreach ($sort_options as $val => $label) : ?>
            <option value="<?php echo esc_attr($val); ?>" <?php selected($current_sort, $val); ?>>
                <?php echo esc_html($label); ?>
            </option>
        <?php endforeach; ?>
    </select>

    <!-- Licence pills -->
    <div class="filter-pill">
        <?php foreach ($license_options as $val => $label) : ?>
            <?php $active = in_array($val, $current_license, true); ?>
            <a href="<?php echo esc_url(add_query_arg('license', $active ? null : $val)); ?>"
               class="<?php echo $active ? 'is-active' : ''; ?>">
                <?php echo esc_html($label); ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Feature pills -->
    <div class="filter-pill">
        <?php foreach ($feature_options as $val => $label) : ?>
            <?php $active = in_array($val, $current_feature, true); ?>
            <a href="<?php echo esc_url(add_query_arg('feature', $active ? null : $val)); ?>"
               class="<?php echo $active ? 'is-active' : ''; ?>">
                <?php echo esc_html($label); ?>
            </a>
        <?php endforeach; ?>
    </div>

</div>
