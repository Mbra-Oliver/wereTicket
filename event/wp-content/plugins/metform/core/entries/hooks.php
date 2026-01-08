<?php
namespace MetForm\Core\Entries;

defined('ABSPATH') || exit;

class Hooks
{
    use \MetForm\Traits\Singleton;

    public function __construct()
    {

        add_filter('manage_metform-entry_posts_columns', [$this, 'set_columns']);
        add_action('manage_metform-entry_posts_custom_column', [$this, 'render_column'], 10, 2);
        add_filter('parse_query', [$this, 'query_filter']);
        add_filter('wp_mail_from_name', [$this, 'wp_mail_from']);
        add_filter('upload_mimes', [$this, 'metfom_additional_upload_mimes']);
    }

    public function set_columns($columns)
    {

        $date_column = $columns['date'];

        unset($columns['date']);

		$columns['form_name'] = esc_html__('Form Name', 'metform');
		
        $columns['referral'] = esc_html__('Referral','metform');

        $columns['email_verified'] = esc_html__('Email Verified','metform');

        $columns['date'] = esc_html($date_column);
        $columns['export_actions'] = esc_html__('Export Actions', 'metform');

        
        return $columns;
    }

    public function render_column($column, $post_id)
    {
        if(!empty(get_option('permalink_structure', true))) {
            $entry_api = get_rest_url('', 'metform-pro/v1/pdf-export/entry?entry_id');
        }else{
            $entry_api = get_rest_url('', 'metform-pro/v1/pdf-export/entry&entry_id');
        }

        switch ($column) {
            case 'form_name':
                $form_id = get_post_meta($post_id, 'metform_entries__form_id', true);
                $form_name = get_post((int) $form_id);
                $post_title = (isset($form_name->post_title) ? $form_name->post_title : '');

                global $wp;
                $current_url = add_query_arg($wp->query_string . "&mf_form_id=" . $form_id, '', home_url($wp->request));
             
                echo "<a data-metform-form-id=" . esc_attr($form_id) . " class='mf-entry-filter mf-entry-flter-form_id' href=" . esc_url($current_url) . ">" . esc_html($post_title) . "</a>";
                break;

            case 'referral':
                $page_id = get_post_meta( $post_id, 'mf_page_id',true );

                global $wp;
                $current_url = add_query_arg($wp->query_string . "&mf_ref_id=" . $page_id, '', home_url($wp->request));

				echo "<a class='mf-entry-filter mf-entry-flter-form_id' href='" . esc_url($current_url) . "'>".esc_html(get_the_title($page_id))."</a>";
                break;
            
            case 'email_verified':
                if(class_exists('\MetForm_Pro\Plugin')) :
                    $email_verified = get_post_meta($post_id, 'email_verified', true);
                    if($email_verified == true) : ?>
                        <button type='button' style='background:#00cd00;box-shadow:1px 1px 5px rgba(0, 205, 0, 0.3);border:none;color:white;padding:2px 6px 3px;border-radius: 5px;font-weight:400'><?php echo esc_html__('Yes', 'metform'); ?></button>
                    <?php else : ?>
                        <button type='button' style='background:#888;border:none;color:white;padding:2px 6px 3px;border-radius: 5px;font-weight:400'><?php echo esc_html__('No', 'metform'); ?></button>
                    <?php endif;
                else: ?>
                    <div class="mf-entry-pro mf-svg-container">
                        <div class="mf-svg-inner mf-upgrade-btn">
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="14" viewBox="0 0 13 14" fill="none"><path d="M10.225 6.025h-8.4a1.2 1.2 0 0 0-1.2 1.2v4.2a1.2 1.2 0 0 0 1.2 1.2h8.4a1.2 1.2 0 0 0 1.2-1.2v-4.2a1.2 1.2 0 0 0-1.2-1.2m-7.2 0v-2.4a3 3 0 1 1 6 0v2.4" stroke="#E81454" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            <div class="mf-svg-text"><?php echo esc_html__('Upgrade', 'metform'); ?></div>
                        </div>
                    </div>
                <?php endif;
                break;

            case 'export_actions':
                // Show PDF export button when pro plugin is activated
                if(class_exists('\MetForm_Pro\Plugin')) : ?>
                    <button class='metform-pdf-export-btn attr-btn attr-btn-primary' data-id='<?php echo esc_attr($post_id); ?>' data-nonce='<?php echo esc_attr(wp_create_nonce('metform-pdf-export')); ?>' data-rest-api='<?php echo esc_url($entry_api) . '=' . esc_attr($post_id); ?>'><?php echo esc_html__('PDF Export', 'metform'); ?> <i class='pdf-spinner'></i></button>
                <?php else : ?>
                  <div class="mf-entry-pro mf-svg-container mf-tooltip-wrapper" data-tooltip="<?php echo esc_attr__('Upgrade for premium access.', 'metform'); ?>">
                        <div class="mf-svg-inner mf-export-pdf-btn">
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="14" viewBox="0 0 13 14" fill="none"><path d="M10.225 6.025h-8.4a1.2 1.2 0 0 0-1.2 1.2v4.2a1.2 1.2 0 0 0 1.2 1.2h8.4a1.2 1.2 0 0 0 1.2-1.2v-4.2a1.2 1.2 0 0 0-1.2-1.2m-7.2 0v-2.4a3 3 0 1 1 6 0v2.4" stroke="#2271B1" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            <div class="mf-svg-text"><?php echo esc_html__('Export PDF', 'metform'); ?></div>
                        </div>
                    </div>
                <?php endif;
        }
    }

    public function query_filter($query)
    {
        global $pagenow;
        //phpcs:ignore WordPress.Security.NonceVerification -- Ignore because of This is CPT page
        $current_page = isset($_GET['post_type']) ? sanitize_key($_GET['post_type']) : '';
        if (
            is_admin()
            && 'metform-entry' == $current_page
            && 'edit.php' == $pagenow
            && $query->query_vars['post_type'] == 'metform-entry'
            && isset($_GET['mf_form_id']) //phpcs:ignore WordPress.Security.NonceVerification
            && $_GET['mf_form_id'] != 'all' //phpcs:ignore WordPress.Security.NonceVerification
        ) {

            $form_id = sanitize_key($_GET['mf_form_id']); //phpcs:ignore WordPress.Security.NonceVerification
            $query->query_vars['meta_key'] = 'metform_entries__form_id';
            $query->query_vars['meta_value'] = $form_id;
            $query->query_vars['meta_compare'] = '=';
        }

        if (
            is_admin()
            && 'metform-entry' == $current_page
            && 'edit.php' == $pagenow
            && $query->query_vars['post_type'] == 'metform-entry'
            && isset($_GET['mf_ref_id']) //phpcs:ignore WordPress.Security.NonceVerification
            && $_GET['mf_ref_id'] != 'all' //phpcs:ignore WordPress.Security.NonceVerification
        ) {

            $page_id = sanitize_key($_GET['mf_ref_id']); //phpcs:ignore WordPress.Security.NonceVerification
            $query->query_vars['meta_key'] = 'mf_page_id';
            $query->query_vars['meta_value'] = $page_id;
            $query->query_vars['meta_compare'] = '=';
        }
    }

    public function wp_mail_from($name)
    {
        return get_bloginfo('name');
    }

    /**
     * Metform Additional Upload Mimes
     * 
     * @since 3.8.9
     * @access public
     * @param array $mimes
     * @return array
     */
    public function metfom_additional_upload_mimes( $mimes )
    {
        
        $mimes['stl'] = 'application/octet-stream';
        $mimes['psd'] = 'image/vnd.adobe.photoshop';
        $mimes['stp'] = 'text/plain; charset=us-ascii';

        return $mimes;
    }
}
