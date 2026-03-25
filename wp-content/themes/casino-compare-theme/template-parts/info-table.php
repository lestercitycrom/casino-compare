<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$rows = is_array($args['rows'] ?? null) ? $args['rows'] : [];
$rows = array_filter($rows, static fn($value) => cct_has_content($value));

if ($rows === []) {
    return;
}
?>
<table>
    <tbody>
    <?php foreach ($rows as $label => $value) : ?>
        <tr>
            <th scope="row"><?php echo esc_html((string) $label); ?></th>
            <td><?php echo esc_html(is_array($value) ? implode(', ', array_map('strval', $value)) : (string) $value); ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
