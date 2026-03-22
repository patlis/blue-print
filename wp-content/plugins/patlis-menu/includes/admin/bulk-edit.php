<?php
if (!defined('ABSPATH')) exit;

final class Patlis_Menu_Admin_Bulk_Edit
{
	public const SLUG = 'patlis-menu-bulk-edit';

	public static function init(): void
	{
		add_action('admin_menu', [__CLASS__, 'register_submenu'], 30);
	}

	public static function register_submenu(): void
	{
		add_submenu_page(
			'patlis-menu',
			'Bulk Edit Menu Items',
			'Bulk Edit',
			'manage_options',
			self::SLUG,
			[__CLASS__, 'render_page']
		);
	}

	public static function render_page(): void
	{
		if (!current_user_can('manage_options')) {
			wp_die('Not allowed.');
		}

		$result = null;

		if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['patlis_menu_bulk_edit_submit'])) {
			$result = self::handle_save();
		}

		$sections = self::get_grouped_items();
		$has_items = false;
		foreach ($sections as $section) {
			if (!empty($section['items']) && is_array($section['items'])) {
				$has_items = true;
				break;
			}
		}
		?>
		<div class="wrap">
			<style>
				.patlis-bulk-edit-wrap h1 { margin-bottom: 6px; }
				.patlis-bulk-edit-wrap .patlis-intro { margin: 0 0 8px 0; }
				.patlis-bulk-edit-wrap .patlis-result { max-width: 1200px; margin-top: 8px; }
				.patlis-bulk-edit-wrap .notice { margin: 0 0 8px 0; padding: 8px 10px; }
				.patlis-bulk-edit-wrap .patlis-errors { background: #fff; padding: 10px; border: 1px solid #dcdcde; }
				.patlis-bulk-edit-wrap .patlis-errors h2 { margin: 0 0 8px 0; }
				.patlis-bulk-edit-wrap .patlis-errors ul { margin: 0; padding-left: 18px; }
				.patlis-bulk-edit-wrap .patlis-form { margin-top: 8px; }
				.patlis-bulk-edit-wrap .patlis-section { margin-bottom: 10px; }
				.patlis-bulk-edit-wrap .patlis-section-title { margin: 0 0 6px 0; font-size: 14px; }
				.patlis-bulk-edit-wrap .patlis-table-wrap { max-width: 100%; overflow: auto; background: #fff; border: 1px solid #dcdcde; padding: 6px; }
				.patlis-bulk-edit-wrap .widefat th,
				.patlis-bulk-edit-wrap .widefat td { padding: 4px 6px; }
				.patlis-bulk-edit-wrap input[type="text"],
				.patlis-bulk-edit-wrap textarea { width: 100%; min-height: 26px; padding: 3px 5px; font-size: 12px; }
				.patlis-bulk-edit-wrap textarea { min-height: 42px; }
				.patlis-bulk-edit-wrap p.submit { margin: 8px 0 0 0; padding: 0; }
			.patlis-bulk-edit-wrap .patlis-field-changed { border-color: #f0a500 !important; background-color: #fffbf0 !important; box-shadow: 0 0 0 1px #f0a500 !important; }
			</style>

			<div class="patlis-bulk-edit-wrap">
			<h1>Bulk Edit Menu Items</h1>
			<p class="patlis-intro">Μαζικό edit στα υπάρχοντα items της default γλώσσας (χωρίς αλλαγή category).</p>

			<?php if (is_array($result)) : ?>
				<div class="patlis-result">
					<?php if (!empty($result['updated'])) : ?>
						<div class="notice notice-success">
							<p style="margin:0;">
								<strong>Bulk edit completed.</strong>
								Updated: <?php echo esc_html((string) $result['updated']); ?> |
								Skipped: <?php echo esc_html((string) $result['skipped']); ?>
							</p>
						</div>
					<?php else : ?>
						<div class="notice notice-warning">
							<p style="margin:0;">
								<strong>No items updated.</strong>
								Skipped: <?php echo esc_html((string) $result['skipped']); ?>
							</p>
						</div>
					<?php endif; ?>

					<?php if (!empty($result['errors'])) : ?>
						<div class="patlis-errors">
							<h2>Errors</h2>
							<ul>
								<?php foreach ($result['errors'] as $error) : ?>
									<li><?php echo esc_html($error); ?></li>
								<?php endforeach; ?>
							</ul>
						</div>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<form method="post" action="" class="patlis-form">
				<?php wp_nonce_field('patlis_menu_bulk_edit_action', 'patlis_menu_bulk_edit_nonce'); ?>

				<?php if (empty($sections)) : ?>
					<div class="patlis-table-wrap">
						<table class="widefat fixed striped" style="min-width:1280px;">
							<tbody>
								<tr>
									<td>No menu items found.</td>
								</tr>
							</tbody>
						</table>
					</div>
				<?php else : ?>
					<?php foreach ($sections as $section) : ?>
						<?php
						$term_name = (string) ($section['term_name'] ?? '');
						$section_items = (array) ($section['items'] ?? []);
						?>
						<div class="patlis-section">
							<h2 class="patlis-section-title"><?php echo esc_html($term_name); ?></h2>
							<div class="patlis-table-wrap">
								<table class="widefat fixed striped" style="min-width:1280px;">
									<thead>
										<tr>
											<th style="width:50px;">Sort</th>
											<th style="width:50px;">Item Nr</th>
											<th style="width:220px;">Name</th>
											<th style="width:350px;">Description</th>
											<th style="width:100px;">Allergies</th>
											<th style="width:120px;">Size 1</th>
											<th style="width:50px;">Price</th>
											<th style="width:120px;">Size 2</th>
											<th style="width:50px;">Price 2</th>
											<th style="width:120px;">Size 3</th>
											<th style="width:50px;">Price 3</th>
										</tr>
									</thead>
									<tbody>
										<?php if (empty($section_items)) : ?>
											<tr>
												<td colspan="11">No items in this section.</td>
											</tr>
										<?php else : ?>
											<?php foreach ($section_items as $item) : ?>
												<?php
												$post_id    = (int) $item->ID;
												$sort       = (string) get_post_meta($post_id, 'pmi_sort', true);
												$itemnr     = (string) get_post_meta($post_id, 'pmi_itemnr', true);
												$desc       = (string) get_post_meta($post_id, 'pmi_description', true);
												$allergies  = (string) get_post_meta($post_id, 'pmi_allergies', true);
												$price      = (string) get_post_meta($post_id, 'pmi_price', true);
												$price2     = (string) get_post_meta($post_id, 'pmi_price2', true);
												$price3     = (string) get_post_meta($post_id, 'pmi_price3', true);
												$size1      = (string) get_post_meta($post_id, 'pmi_size1', true);
												$size2      = (string) get_post_meta($post_id, 'pmi_size2', true);
												$size3      = (string) get_post_meta($post_id, 'pmi_size3', true);
												?>
												<tr>
													<td>
														<input type="hidden" name="items[<?php echo esc_attr((string) $post_id); ?>][post_id]" value="<?php echo esc_attr((string) $post_id); ?>">
														<input type="text" name="items[<?php echo esc_attr((string) $post_id); ?>][sort]" value="<?php echo esc_attr($sort); ?>">
													</td>
													<td><input type="text" name="items[<?php echo esc_attr((string) $post_id); ?>][itemnr]" value="<?php echo esc_attr($itemnr); ?>"></td>
													<td><input type="text" name="items[<?php echo esc_attr((string) $post_id); ?>][name]" value="<?php echo esc_attr($item->post_title); ?>"></td>
													<td><textarea name="items[<?php echo esc_attr((string) $post_id); ?>][description]" rows="2"><?php echo esc_textarea($desc); ?></textarea></td>
													<td><input type="text" name="items[<?php echo esc_attr((string) $post_id); ?>][allergies]" value="<?php echo esc_attr($allergies); ?>"></td>
												<td><input type="text" name="items[<?php echo esc_attr((string) $post_id); ?>][size1]" value="<?php echo esc_attr($size1); ?>"></td>
										<td><input type="text" name="items[<?php echo esc_attr((string) $post_id); ?>][price]" value="<?php echo esc_attr($price); ?>"></td>
										<td><input type="text" name="items[<?php echo esc_attr((string) $post_id); ?>][size2]" value="<?php echo esc_attr($size2); ?>"></td>
										<td><input type="text" name="items[<?php echo esc_attr((string) $post_id); ?>][price2]" value="<?php echo esc_attr($price2); ?>"></td>
										<td><input type="text" name="items[<?php echo esc_attr((string) $post_id); ?>][size3]" value="<?php echo esc_attr($size3); ?>"></td>
										<td><input type="text" name="items[<?php echo esc_attr((string) $post_id); ?>][price3]" value="<?php echo esc_attr($price3); ?>"></td>
												</tr>
											<?php endforeach; ?>
										<?php endif; ?>
									</tbody>
								</table>
							</div>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>

				<?php if ($has_items) : ?>
					<p class="submit">
						<button type="submit" name="patlis_menu_bulk_edit_submit" value="1" class="button button-primary">Save All Changes</button>
					</p>
				<?php endif; ?>
			</form>
			</div>
		</div>

		<script>
		(function () {
			document.querySelectorAll('.patlis-bulk-edit-wrap input[type="text"], .patlis-bulk-edit-wrap textarea').forEach(function (field) {
				var original = field.value;
				field.addEventListener('input', function () {
					if (field.value !== original) {
						field.classList.add('patlis-field-changed');
					} else {
						field.classList.remove('patlis-field-changed');
					}
				});
			});

			document.querySelector('.patlis-form')?.addEventListener('submit', function () {
				document.querySelectorAll('.patlis-field-changed').forEach(function (f) {
					f.classList.remove('patlis-field-changed');
				});
			});

			document.querySelectorAll('.patlis-bulk-edit-wrap input[name*="[price"]').forEach(function (field) {
				field.addEventListener('keydown', function (e) {
					if (e.key !== 'ArrowUp' && e.key !== 'ArrowDown') return;
					e.preventDefault();
					var val = parseFloat(field.value.replace(',', '.')) || 0;
					val = e.key === 'ArrowUp' ? val + 1 : Math.max(0, val - 1);
					field.value = Number.isInteger(val) ? String(val) : val.toFixed(2);
					field.dispatchEvent(new Event('input'));
				});
			});
		})();
		</script>
		<?php
	}

	private static function get_grouped_items(): array
	{
		$default_lang = '';
		if (function_exists('pll_default_language')) {
			$default_lang = (string) pll_default_language('slug');
		}

		$term_args = [
			'taxonomy'   => 'menu_section',
			'hide_empty' => false,
			'orderby'    => 'name',
			'order'      => 'ASC',
		];

		if ($default_lang !== '') {
			$term_args['lang'] = $default_lang;
		}

		$terms = get_terms($term_args);
		if (is_wp_error($terms) || !is_array($terms)) {
			$terms = [];
		}

		$sections = [];

		foreach ($terms as $term) {
			if (!($term instanceof WP_Term)) {
				continue;
			}

			$items = get_posts([
				'post_type'         => 'menu_item',
				'post_status'       => 'publish',
				'posts_per_page'    => -1,
				'patlis_menu_order' => 1,
				'suppress_filters'  => false,
				'lang'              => $default_lang !== '' ? $default_lang : '',
				'tax_query'         => [
					[
						'taxonomy' => 'menu_section',
						'field'    => 'term_id',
						'terms'    => [(int) $term->term_id],
					],
				],
			]);

			$sections[] = [
				'term_name' => (string) $term->name,
				'items'     => is_array($items) ? $items : [],
			];
		}

		$uncategorized_items = get_posts([
			'post_type'         => 'menu_item',
			'post_status'       => 'publish',
			'posts_per_page'    => -1,
			'patlis_menu_order' => 1,
			'suppress_filters'  => false,
			'lang'              => $default_lang !== '' ? $default_lang : '',
			'tax_query'         => [
				[
					'taxonomy' => 'menu_section',
					'operator' => 'NOT EXISTS',
				],
			],
		]);

		if (is_array($uncategorized_items) && !empty($uncategorized_items)) {
			$sections[] = [
				'term_name' => 'Uncategorized',
				'items'     => $uncategorized_items,
			];
		}

		return $sections;
	}

	private static function handle_save(): array
	{
		if (!current_user_can('manage_options')) {
			wp_die('Not allowed.');
		}

		if (
			!isset($_POST['patlis_menu_bulk_edit_nonce']) ||
			!wp_verify_nonce((string) $_POST['patlis_menu_bulk_edit_nonce'], 'patlis_menu_bulk_edit_action')
		) {
			return [
				'updated' => 0,
				'skipped' => 0,
				'errors'  => ['Invalid nonce.'],
			];
		}

		$items = $_POST['items'] ?? [];
		if (!is_array($items) || empty($items)) {
			return [
				'updated' => 0,
				'skipped' => 0,
				'errors'  => ['No items submitted.'],
			];
		}

		$updated = 0;
		$skipped = 0;
		$errors  = [];

		foreach ($items as $row_key => $row) {
			$post_id = isset($row['post_id']) ? (int) $row['post_id'] : (int) $row_key;

			if ($post_id <= 0 || get_post_type($post_id) !== 'menu_item') {
				$skipped++;
				$errors[] = 'Invalid item ID: ' . $post_id;
				continue;
			}

			if (!current_user_can('edit_post', $post_id)) {
				$skipped++;
				$errors[] = 'No permission for item ID: ' . $post_id;
				continue;
			}

			if (!self::is_default_language_item($post_id)) {
				$skipped++;
				$errors[] = 'Item ID ' . $post_id . ': not in default language.';
				continue;
			}

			$name = sanitize_text_field((string) ($row['name'] ?? ''));

			if ($name === '') {
				$skipped++;
				$errors[] = 'Item ID ' . $post_id . ': name is empty.';
				continue;
			}

			wp_update_post([
				'ID'         => $post_id,
				'post_title' => $name,
			]);

			$sort_raw = isset($row['sort']) ? trim((string) $row['sort']) : '';
			if ($sort_raw === '') {
				delete_post_meta($post_id, 'pmi_sort');
			} else {
				update_post_meta($post_id, 'pmi_sort', (string) intval($sort_raw));
			}

			update_post_meta($post_id, 'pmi_itemnr', sanitize_text_field((string) ($row['itemnr'] ?? '')));
			update_post_meta($post_id, 'pmi_description', sanitize_textarea_field((string) ($row['description'] ?? '')));
			update_post_meta($post_id, 'pmi_allergies', sanitize_text_field((string) ($row['allergies'] ?? '')));

			update_post_meta($post_id, 'pmi_price', self::sanitize_price((string) ($row['price'] ?? '')));
			update_post_meta($post_id, 'pmi_price2', self::sanitize_price((string) ($row['price2'] ?? '')));
			update_post_meta($post_id, 'pmi_price3', self::sanitize_price((string) ($row['price3'] ?? '')));

			update_post_meta($post_id, 'pmi_size1', sanitize_text_field((string) ($row['size1'] ?? '')));
			update_post_meta($post_id, 'pmi_size2', sanitize_text_field((string) ($row['size2'] ?? '')));
			update_post_meta($post_id, 'pmi_size3', sanitize_text_field((string) ($row['size3'] ?? '')));

			$updated++;
		}

		return [
			'updated' => $updated,
			'skipped' => $skipped,
			'errors'  => $errors,
		];
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

	private static function is_default_language_item(int $post_id): bool
	{
		if (!function_exists('pll_default_language') || !function_exists('pll_get_post_language')) {
			return true;
		}

		$default_lang = (string) pll_default_language('slug');
		if ($default_lang === '') {
			return true;
		}

		$post_lang = (string) pll_get_post_language($post_id, 'slug');
		if ($post_lang === '') {
			return true;
		}

		return $post_lang === $default_lang;
	}
}

Patlis_Menu_Admin_Bulk_Edit::init();
