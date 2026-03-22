<?php
if (!defined('ABSPATH')) exit;

final class Patlis_Menu_Admin_Import
{
    public const SLUG = 'patlis-menu-import';

    public static function init(): void
    {
        add_action('admin_menu', [__CLASS__, 'register_submenu'], 20);
    }

    public static function register_submenu(): void
    {
        add_submenu_page(
            'patlis-menu',
            'Import Menu Items',
            'Import',
            'manage_options',
            self::SLUG,
            [__CLASS__, 'render_page'],
            99
        );
    }

    public static function render_page(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('Not allowed.');
        }

        $result = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['patlis_menu_import_submit'])) {
            $result = self::handle_import();
        }

        ?>
        <div class="wrap">
            <h1>Import Menu Items</h1>

            <p>Εδώ γίνεται το αρχικό insert των menu items από CSV export του Excel.</p>

            <div style="max-width:900px; background:#fff; padding:20px; border:1px solid #dcdcde; margin-top:20px;">
                <h2 style="margin-top:0;">CSV Import</h2>

                <p>
                    Αποθήκευση από Excel ως:
                    <strong>CSV UTF-8 (durch Trennzeichen getrennt) (*.csv)</strong>
                </p>

                <p>
                    Standard header:
                    <code>category-id;item-nr;name;description;allergies;price;price-2;price-3;size-1;size-2;size-3</code>
                </p>

                <form method="post" action="" enctype="multipart/form-data">
                    <?php wp_nonce_field('patlis_menu_import_action', 'patlis_menu_import_nonce'); ?>

                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row">
                                    <label for="patlis_menu_import_file">CSV File</label>
                                </th>
                                <td>
                                    <input type="file" id="patlis_menu_import_file" name="patlis_menu_import_file" accept=".csv,text/csv" required>
                                    <p class="description">Μόνο CSV UTF-8.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <p class="submit">
                        <button type="submit" name="patlis_menu_import_submit" value="1" class="button button-primary">
                            Import
                        </button>
                    </p>
                </form>
            </div>

            <?php if (is_array($result)) : ?>
                <div style="max-width:900px; margin-top:20px;">
                    <?php if (!empty($result['imported'])) : ?>
                        <div class="notice notice-success" style="margin:0 0 15px 0; padding:12px;">
                            <p style="margin:0;">
                                <strong>Import completed.</strong>
                                Imported: <?php echo esc_html((string) $result['imported']); ?> |
                                Skipped: <?php echo esc_html((string) $result['skipped']); ?>
                            </p>
                        </div>
                    <?php else : ?>
                        <div class="notice notice-warning" style="margin:0 0 15px 0; padding:12px;">
                            <p style="margin:0;">
                                <strong>No items imported.</strong>
                                Skipped: <?php echo esc_html((string) $result['skipped']); ?>
                            </p>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($result['errors'])) : ?>
                        <div style="background:#fff; padding:20px; border:1px solid #dcdcde;">
                            <h2 style="margin-top:0;">Errors</h2>
                            <ul style="list-style:disc; padding-left:20px; margin-bottom:0;">
                                <?php foreach ($result['errors'] as $error) : ?>
                                    <li><?php echo esc_html($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    private static function handle_import(): array
    {
        if (!current_user_can('manage_options')) {
            wp_die('Not allowed.');
        }

        if (
            !isset($_POST['patlis_menu_import_nonce']) ||
            !wp_verify_nonce((string) $_POST['patlis_menu_import_nonce'], 'patlis_menu_import_action')
        ) {
            return [
                'imported' => 0,
                'skipped'  => 0,
                'errors'   => ['Invalid nonce.'],
            ];
        }

        if (
            !isset($_FILES['patlis_menu_import_file']) ||
            !is_array($_FILES['patlis_menu_import_file']) ||
            (int) $_FILES['patlis_menu_import_file']['error'] !== UPLOAD_ERR_OK
        ) {
            return [
                'imported' => 0,
                'skipped'  => 0,
                'errors'   => ['File upload failed.'],
            ];
        }

        $tmp_name = (string) ($_FILES['patlis_menu_import_file']['tmp_name'] ?? '');
        if ($tmp_name === '' || !file_exists($tmp_name)) {
            return [
                'imported' => 0,
                'skipped'  => 0,
                'errors'   => ['Uploaded file not found.'],
            ];
        }

        $lines = file($tmp_name, FILE_IGNORE_NEW_LINES);
        if (!$lines || !is_array($lines) || count($lines) < 2) {
            return [
                'imported' => 0,
                'skipped'  => 0,
                'errors'   => ['CSV is empty or has no data rows.'],
            ];
        }

        $delimiter = self::detect_delimiter((string) $lines[0]);

        $rows = [];
        foreach ($lines as $line) {
            $rows[] = str_getcsv($line, $delimiter);
        }

        if (empty($rows[0]) || !is_array($rows[0])) {
            return [
                'imported' => 0,
                'skipped'  => 0,
                'errors'   => ['Could not read CSV header.'],
            ];
        }

        $header = array_map([__CLASS__, 'normalize_header'], $rows[0]);

        $expected_header = [
            'category-id',
            'item-nr',
            'name',
            'description',
            'allergies',
            'price',
            'price-2',
            'price-3',
            'size-1',
            'size-2',
            'size-3',
        ];

        if ($header !== $expected_header) {
            return [
                'imported' => 0,
                'skipped'  => 0,
                'errors'   => [
                    'Invalid header.',
                    'Expected: ' . implode('; ', $expected_header),
                    'Found: ' . implode('; ', $header),
                ],
            ];
        }

        $imported = 0;
        $skipped  = 0;
        $errors   = [];

        $taxonomy = 'menu_section';

        foreach ($rows as $index => $row) {
            if ($index === 0) {
                continue;
            }

            $row_number = $index + 1;

            if (!is_array($row)) {
                $skipped++;
                $errors[] = 'Row ' . $row_number . ': invalid row.';
                continue;
            }
            
            // ignore fully empty rows
            $non_empty = array_filter($row, function ($v) {
                return trim((string) $v) !== '';
            });

            if (empty($non_empty)) {
                continue;
            }

            $row = array_pad($row, count($expected_header), '');
            $row = array_slice($row, 0, count($expected_header));
            $row = array_map('trim', $row);

            $data = array_combine($expected_header, $row);
            if ($data === false) {
                $skipped++;
                $errors[] = 'Row ' . $row_number . ': could not map columns.';
                continue;
            }

            $name       = (string) $data['name'];
            $category_id = (int) $data['category-id'];

            if ($name === '') {
                $skipped++;
                $errors[] = 'Row ' . $row_number . ': name is empty.';
                continue;
            }

            if ($category_id <= 0 || !term_exists($category_id, $taxonomy)) {
                $skipped++;
                $errors[] = 'Row ' . $row_number . ': invalid category-id "' . $data['category-id'] . '".';
                continue;
            }

            $post_id = wp_insert_post([
                'post_type'   => 'menu_item',
                'post_status' => 'publish',
                'post_title'  => $name,
            ], true);

            if (is_wp_error($post_id) || !$post_id) {
                $skipped++;
                $errors[] = 'Row ' . $row_number . ': wp_insert_post failed.';
                continue;
            }

            wp_set_object_terms($post_id, [$category_id], $taxonomy);

            update_post_meta($post_id, 'pmi_show', '1');
            update_post_meta($post_id, 'pmi_itemnr', self::sanitize_text($data['item-nr']));
            update_post_meta($post_id, 'pmi_description', self::sanitize_textarea($data['description']));
            update_post_meta($post_id, 'pmi_allergies', self::sanitize_text($data['allergies']));

            update_post_meta($post_id, 'pmi_price',  self::sanitize_price($data['price']));
            update_post_meta($post_id, 'pmi_price2', self::sanitize_price($data['price-2']));
            update_post_meta($post_id, 'pmi_price3', self::sanitize_price($data['price-3']));

            update_post_meta($post_id, 'pmi_size1', self::sanitize_text($data['size-1']));
            update_post_meta($post_id, 'pmi_size2', self::sanitize_text($data['size-2']));
            update_post_meta($post_id, 'pmi_size3', self::sanitize_text($data['size-3']));

            $imported++;
        }

        return [
            'imported' => $imported,
            'skipped'  => $skipped,
            'errors'   => $errors,
        ];
    }

    private static function detect_delimiter(string $line): string
    {
        return substr_count($line, ';') > substr_count($line, ',') ? ';' : ',';
    }

    private static function normalize_header(string $value): string
    {
        $value = trim($value);

        // Remove UTF-8 BOM from first header cell if present
        $value = preg_replace('/^\xEF\xBB\xBF/', '', $value);

        $value = strtolower($value);

        return $value;
    }

    private static function sanitize_text(string $value): string
    {
        return sanitize_text_field($value);
    }

    private static function sanitize_textarea(string $value): string
    {
        return sanitize_textarea_field($value);
    }

    private static function sanitize_price(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $value = str_replace(',', '.', $value);
        $value = preg_replace('/[^0-9.]/', '', $value);

        if ($value === '' || $value === '.') {
            return '';
        }

        return $value;
    }
}

Patlis_Menu_Admin_Import::init();