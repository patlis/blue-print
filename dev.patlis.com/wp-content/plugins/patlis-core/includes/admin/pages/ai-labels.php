<?php
if (!defined('ABSPATH')) exit;

final class Patlis_Admin_Page_AI_Labels
{
    private const DEFAULT_MIN_WIDTH = 150;

    public static function defaults(): array
    {
        return [
            'enabled'            => 'yes',
            'min_width'          => self::DEFAULT_MIN_WIDTH,
            'assisted_label'     => 'AI-assisted',
            'generated_label'    => 'AI-Generated',
            'modified_label'     => 'AI-Modified',
        ];
    }

    public static function sanitize($input): array
    {
        $in = is_array($input) ? $input : [];
        $defaults = self::defaults();
        $min_width = isset($in['min_width']) ? absint($in['min_width']) : $defaults['min_width'];

        return [
            'enabled'         => !empty($in['enabled']) ? 'yes' : 'no',
            'min_width'       => max(100, min($min_width, 300)),
            'assisted_label'  => isset($in['assisted_label']) ? sanitize_text_field((string) $in['assisted_label']) : $defaults['assisted_label'],
            'generated_label' => isset($in['generated_label']) ? sanitize_text_field((string) $in['generated_label']) : $defaults['generated_label'],
            'modified_label'  => isset($in['modified_label']) ? sanitize_text_field((string) $in['modified_label']) : $defaults['modified_label'],
        ];
    }

    public static function render(): void
    {
        if (!current_user_can('patlis_manage')) {
            wp_die('Not allowed.');
        }

        $option = get_option(Patlis_Core::OPTION_AI_LABELS, []);
        $option = is_array($option) ? array_merge(self::defaults(), $option) : self::defaults();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Labelling for AI-generated content', 'patlis-core'); ?></h1>

            <?php if (!empty($_GET['patlis_saved'])): ?>
                <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Saved.', 'patlis-core'); ?></p></div>
            <?php endif; ?>


            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="patlis_save_ai_labels">
                <?php wp_nonce_field('patlis_save_ai_labels'); ?>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php esc_html_e('Enable AI labeling', 'patlis-core'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="<?php echo esc_attr(Patlis_Core::OPTION_AI_LABELS); ?>[enabled]" value="yes"<?php checked($option['enabled'], 'yes'); ?>>
                                <?php esc_html_e('Enable labels on frontend images', 'patlis-core'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="patlis_ai_labels_min_width"><?php esc_html_e('Minimum image size', 'patlis-core'); ?></label></th>
                        <td>
                            <input id="patlis_ai_labels_min_width" type="number" class="small-text" min="100" max="300" step="1" name="<?php echo esc_attr(Patlis_Core::OPTION_AI_LABELS); ?>[min_width]" value="<?php echo esc_attr((string) $option['min_width']); ?>"> px
                            <p class="description"><?php esc_html_e('Labels are shown only when the rendered image width exceeds this value.', 'patlis-core'); ?></p>
                            <p><strong><?php esc_html_e('Recommended minimum: 150px', 'patlis-core'); ?></strong></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="patlis_ai_labels_assisted"><?php esc_html_e('AI-Assisted Label', 'patlis-core'); ?></label></th>
                        <td>
                            <input id="patlis_ai_labels_assisted" type="text" class="regular-text" name="<?php echo esc_attr(Patlis_Core::OPTION_AI_LABELS); ?>[assisted_label]" value="<?php echo esc_attr($option['assisted_label']); ?>">
                            <p class="description"><?php esc_html_e('Text shown on frontend images marked as AI-assisted.', 'patlis-core'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="patlis_ai_labels_generated"><?php esc_html_e('AI-Generated Label', 'patlis-core'); ?></label></th>
                        <td>
                            <input id="patlis_ai_labels_generated" type="text" class="regular-text" name="<?php echo esc_attr(Patlis_Core::OPTION_AI_LABELS); ?>[generated_label]" value="<?php echo esc_attr($option['generated_label']); ?>">
                            <p class="description"><?php esc_html_e('Text shown on frontend images marked as AI-Generated.', 'patlis-core'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="patlis_ai_labels_modified"><?php esc_html_e('AI-Modified Label', 'patlis-core'); ?></label></th>
                        <td>
                            <input id="patlis_ai_labels_modified" type="text" class="regular-text" name="<?php echo esc_attr(Patlis_Core::OPTION_AI_LABELS); ?>[modified_label]" value="<?php echo esc_attr($option['modified_label']); ?>">
                            <p class="description"><?php esc_html_e('Text shown on frontend images marked as AI-Modified.', 'patlis-core'); ?></p>
                        </td>
                    </tr>
                </table>

                <?php submit_button(__('Save', 'patlis-core')); ?>
            </form>

            <hr>
            <h2><?php esc_html_e('Labelling of images created or modified with AI', 'patlis-core'); ?></h2>
            <p><?php esc_html_e('As of 2 Aug. 2026, new transparency requirements apply in the EU to content that has been created or modified with AI.', 'patlis-core'); ?></p>
            <p><?php esc_html_e('The obligation particularly concerns content that could be mistakenly perceived as authentic.', 'patlis-core'); ?></p>

            <h4>
                <a style="font-size: 1.1em; font-weight: bold;"
                href="https://digital-strategy.ec.europa.eu/en/policies/eu-icons-labelling-ai-generated-content"
                target="_blank">
                    <?php esc_html_e('More information from the European Commission', 'patlis-core'); ?>
                </a>
            </h4>

            <h3><?php esc_html_e('The website owner is responsible for:', 'patlis-core'); ?></h3>

            <ul style="list-style-type: disc; margin-left: 2em; font-size: 1.1em;">
                <li><?php esc_html_e('correctly labelling the images.', 'patlis-core'); ?></li>
                <li><?php esc_html_e('selecting the appropriate label for each image.', 'patlis-core'); ?></li>
                <li><?php esc_html_e('enabling or disabling the labelling feature.', 'patlis-core'); ?></li>
                <li><?php esc_html_e('the text displayed together with the label. (The options are shown above.)', 'patlis-core'); ?></li>
            </ul>

            <p><?php esc_html_e('The website only provides the technical labelling functionality and does not constitute legal advice.', 'patlis-core'); ?></p>

            <h2><?php esc_html_e('How to label an image:', 'patlis-core'); ?></h2>
            <p><?php esc_html_e('The label is selected in the Media Library, as shown in the image below.', 'patlis-core'); ?></p>
            <img style="border: 1px solid #666; display:block; max-width:100%; height:auto;" src="<?php echo esc_url(PATLIS_CORE_URL . 'assets/ai-label/example.webp'); ?>">

        </div>
        <?php
    }
}