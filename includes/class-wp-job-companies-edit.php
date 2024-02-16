<?php


if (!defined('ABSPATH')) {
    exit;
}

class BG_Job_Companies_Edit extends BG_Job_Companies_Abstract
{

    private static $instance = null;

    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct()
    {
        parent::__construct();

        add_shortcode('edit_company_form', array($this, 'edit_company_form'), 10, 1);
        add_action('wp_loaded', array($this, 'request_edit_form'));
        add_action('elementor/query/company_job', array($this, 'company_job_query_callback'));


    }

    public function company_job_query_callback($query)
    {
        $tax_obj = get_queried_object();
        $has_auther = $this->has_auther(get_current_user_id(), $tax_obj);
        if ($has_auther) {
//            $query->set( 'post_status', [ 'draft', 'publish' ,'pending' ] );
        }

    }

    public function request_edit_form()
    {


        if (isset($_POST['update_company']) && isset($_POST['update_company_id'])) {
            $term_id = $_POST['update_company_id'];
            $tax_obj = get_term($term_id, $this->term);
            if ($tax_obj && isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'edit-company-' . $tax_obj->name)) {
                foreach ($this->array_st as $key) {
                    if ($key == 'company_logo') {
                        $key = 'current_company_logo';
                        $attachment_url = $_POST['current_company_logo'];

                        if (filter_var($attachment_url, FILTER_VALIDATE_URL) ) {
                            $rm_image_id = attachment_url_to_postid($attachment_url);
                            if (!$rm_image_id) {
                                $rm_image_id = $this->create_attachment($attachment_url);
                            }
                            $this->set_company_meta('company_logo', $rm_image_id, $tax_obj);
                        }


                    }
                    if (isset($_POST[$key])) {
                        $this->set_company_meta($key, $_POST[$key], $tax_obj);

                    }

                }

            }
        }
    }

    protected function create_attachment($attachment_url)
    {
        include_once ABSPATH . 'wp-admin/includes/image.php';
        include_once ABSPATH . 'wp-admin/includes/media.php';

        $upload_dir = wp_upload_dir();
        $attachment_url = esc_url($attachment_url, ['http', 'https']);
        if (empty($attachment_url)) {
            return 0;
        }

        $attachment_url_parts = wp_parse_url($attachment_url);

        // Relative paths aren't allowed.
        if (false !== strpos($attachment_url_parts['path'], '../')) {
            return 0;
        }

        $attachment_url = sprintf('%s://%s%s', $attachment_url_parts['scheme'], $attachment_url_parts['host'], $attachment_url_parts['path']);

        $attachment_url = str_replace([$upload_dir['baseurl'], WP_CONTENT_URL, site_url('/')], [$upload_dir['basedir'], WP_CONTENT_DIR, ABSPATH], $attachment_url);
        if (empty($attachment_url) || !is_string($attachment_url)) {
            return 0;
        }

        $attachment = [
            'post_title' => wpjm_get_the_job_title($this->job_id),
            'post_content' => '',
            'post_status' => 'inherit',
            'post_parent' => $this->job_id,
            'guid' => $attachment_url,
        ];

        $info = wp_check_filetype($attachment_url);
        if ($info) {
            $attachment['post_mime_type'] = $info['type'];
        }

        $attachment_id = wp_insert_attachment($attachment, $attachment_url, $this->job_id);

        if (!is_wp_error($attachment_id)) {
            wp_update_attachment_metadata($attachment_id, wp_generate_attachment_metadata($attachment_id, $attachment_url));
            return $attachment_id;
        }

        return 0;
    }


    public function edit_company_form($attr)
    {
        $tax_obj = get_queried_object();
        $has_auther = $this->has_auther(get_current_user_id(), $tax_obj);


        if (is_tax($this->term) && $has_auther) {
            wp_enqueue_script('wp-job-manager-job-submission');

            WP_Job_Manager::register_style('wp-job-manager-job-submission', 'css/job-submission.css', []);


            wp_enqueue_style('wp-job-manager-job-submission');

            ob_start();
            $company_fields = $this->get_fields('company');


            foreach ($company_fields as $key => $item) {
                $company_fields[$key]['value'] = $this->get_company_meta($key, $tax_obj);

            }
            ?>

        <form action="<?php echo esc_url(acf_get_current_url()); ?>" method="post" id="" class="job-manager-form"
              enctype="multipart/form-data">

            <?php do_action('submit_job_form_start'); ?>

            <?php if ($company_fields) : ?>
                <h2><?php esc_html_e('Company Edit', 'wp-job-manager'); ?></h2>

                <?php do_action('submit_job_form_company_fields_start'); ?>

                <?php foreach ($company_fields as $key => $field) : ?>
                    <fieldset
                            class="fieldset-<?php echo esc_attr($key); ?> fieldset-type-<?php echo esc_attr($field['type']); ?>">
                        <label for="<?php echo esc_attr($key); ?>"><?php echo wp_kses_post($field['label']) . wp_kses_post(apply_filters('submit_job_form_required_label', $field['required'] ? '' : ' <small>' . __('(optional)', 'wp-job-manager') . '</small>', $field)); ?></label>
                        <div class="field <?php echo $field['required'] ? 'required-field' : ''; ?>">
                            <?php get_job_manager_template('form-fields/' . $field['type'] . '-field.php', ['key' => $key, 'field' => $field]); ?>
                        </div>
                    </fieldset>
                    <?php do_action('submit_job_form_end'); ?>
                <?php endforeach; ?>
            <?php endif; ?>
            <p>

                <input type="hidden" name="update_company" value="1"/>
                <input type="hidden" name="update_company_id" value="<?php echo $tax_obj->term_id ?>"/>
                <input type="submit" name="submit_company" class="button"
                       value="<?php echo esc_attr('Update'); ?>"/>
                <?php wp_nonce_field('edit-company-' . $tax_obj->name);; ?>

                <span class="spinner"
                      style="background-image: url(<?php echo esc_url(includes_url('images/spinner.gif')); ?>);"></span>
            </p>


            </form><?php

            return ob_get_clean();
        }

    }


}


/*
* Front-end styles
*/


