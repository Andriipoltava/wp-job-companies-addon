<?php


if (!defined('ABSPATH')) {
    exit;
}

class BG_Job_Companies_Term extends BG_Job_Companies_Abstract
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

        add_action('single_job_listing_meta_end', array($this, 'gma_wpjmcpp_display_job_meta_data'));
        add_action('init', array($this, 'gma_wpjmcpp_job_taxonomy_init'));
        add_action('template_include', array($this, 'gma_wpjmccp_companies_archive_page_template'));

        add_filter('the_company_logo', array($this, 'company_logo'), 10, 2);
        add_filter('post_thumbnail_id', array($this, 'company_logo_post_thumbnail_id'), 10, 2);

        add_filter('the_company_name', array($this, 'company_job_title'), 10, 2);

        add_filter('the_company_website', array($this, 'the_company_website'), 10, 2);

        add_filter('the_company_twitter', array($this, 'the_company_twitter'), 10, 2);

        add_filter('the_company_tagline', array($this, 'the_company_tagline'), 10, 2);

        add_filter('the_company_video', array($this, 'the_company_video'), 10, 2);

        add_filter('submit_job_form_fields', array($this, 'submit_job_form_fields'), 10, 1);

        add_filter('submit_job_form_fields_get_job_data', array($this, 'submit_job_form_fields_get_job_data'), 10, 2);
        add_filter('submit_job_form_fields_get_user_data', array($this, 'submit_job_form_fields_get_user_data'), 10, 2);

        add_filter('submit_job_form_save_job_data', array($this, 'submit_job_form_save_job_data'), 10, 5);

        add_filter('job_manager_get_posted_fields', array($this, 'job_manager_get_posted_fields'), 10, 2);

        add_action("save_post_{$this->post_type}", array($this, 'post_updated'), 10, 2);


        /*
        * Meta management for the custom "Company" taxonomy
        */


    }

    /*
    * Template loader for company-archive-page-template.php
    */
    function gma_wpjmccp_companies_archive_page_template($template)
    {

        $plugin_dir_path = plugin_dir_path(__FILE__);
        $company_template_url = $plugin_dir_path . 'company-archive-page-template.php';

        if (is_tax($taxonomy = "companies")) {

            // TODO: Sometimes this fails as is_tax() returns NULL. This can be fixed flushing the cache via $wp_rewrite->flush_rules( true ); but is an expensive operation. Would be a good idea to integrate this somehow if the page returns a 404 for example.
            $template = $company_template_url;
            return $template;

        }

        return $template;

    }

    /*
    * Creates custom companies/company taxonomy. This will show under Job Listings > Companies as well as within the Editor metabox
    */
    function gma_wpjmcpp_job_taxonomy_init()
    {

        register_taxonomy(
            $this->term,
            $this->post_type,
            array(
                'label' => __('Job Companies', 'wp-job-companies-addon'),
                'rewrite' => array('slug' => 'company'),
                'public' => true,
                'hierarchical' => false,
                'show_ui' => true,
                'show_admin_column' => true,
                'query_var' => true,
                /*
                Necessary after WP 5.0+ in order to show the metabox within the editor. Since the editor operates using the REST API, taxonomies and post types must be whitelisted to be accessible within the editor.
                https://developer.wordpress.org/reference/functions/register_taxonomy/
                */
                'show_in_rest' => true,
                'supports' => array('thumbnail'),


            )
        );

        register_field_group(array(
            'id' => 'acf_taxonomy-authors',
            'title' => 'Taxonomy Authors',
            'fields' => $this->get_term_meta(),
            'location' => array(
                array(
                    array(
                        'param' => 'taxonomy',
                        'operator' => '==',
                        'value' => 'companies',

                    ),
                ),
            ),
            'options' => array(
                'position' => 'normal',
                'layout' => 'default',
                'hide_on_screen' => array(),
            ),
            'menu_order' => 0,
        ));
    }

    /*
    * Taxonomy metadata : Adds a new column to the companies term page under Job Listings > Companies
    */
    function get_term_meta($columns = array())
    {

        return require_once('acf-fields/company-term.php');
    }

    /**
     * Retrieves the term Company Title.
     *
     * @param int $post_id Optional. Post ID.
     * @return null|WP_Term
     *
     */


    function gma_wpjmcpp_display_job_meta_data()
    {

        global $post;

        $data = get_post_meta($post->ID, "_company_name", true);


        $single_company = $this->get_term_post($post->ID);
        $url = home_url() . '/company/' . $single_company->slug; // OK, page is created.


        // Checks if the company name has been added as a tag to the individual job listing
        if (!empty($data)) {
            $company_name = "<li><a href='" . esc_url($url) . "'>" . esc_html($single_company->name) . " profile</a></li>";
        } else {
            $company_name = "<li><a href='" . esc_url($url) . "'>Company profile</a></li>";
        }

        echo $company_name;

    }

    /*
    * Admin notice to activate WP Job Manager
    */
    function gma_wpjmcpp_admin_notice__error()
    {

        $class = 'notice notice-error';
        $message = _e('An error has occurred. WP Job Manager must be installed in order to use WPJM Company Profile Page plugin', 'wp-job-companies-addon');
        /*
        * Debug: error_log( print_r( $message , true ) );
        */
        printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
    }


    /**
     * Retrieves the post thumbnail ID.
     *
     * @param string $company_logo .
     * @param int|WP_Post $post Optional. Post ID or WP_Post object. Default is global `$post`.
     * @return string
     *
     */
    public function company_logo($company_logo, $post)
    {
        if ($post->post_type == 'job_listing') {
            $term = $this->get_term_post($post->ID);
            if ($term) {
                $acf_img = get_field('company_logo', $term);
                $company_logo = $acf_img ? wp_get_attachment_image($acf_img['id'], 'full') : $company_logo;
            }
        }
        return $company_logo;

    }

    /**
     * Retrieves the post thumbnail ID.
     *
     * @param int $thumbnail_id .
     * @param int|WP_Post $post Optional. Post ID or WP_Post object. Default is global `$post`.
     * @return int|false Post thumbnail ID (which can be 0 if the thumbnail is not set),
     *                   or false if the post does not exist.
     *
     */
    public
    function company_logo_post_thumbnail_id($thumbnail_id, $post)
    {

        if ($post->post_type == 'job_listing') {
            $term = $this->get_term_post($post->ID);
            if ($term) {
                $acf_img = get_field('company_logo', $term);
                $thumbnail_id = $acf_img ? $acf_img['id'] : $thumbnail_id;
            }
        }
        return $thumbnail_id;


    }

    /**
     * Retrieves the term Company Title.
     *
     * @param string $title .
     * @param int|WP_Post $post Optional. Post ID or WP_Post object. Default is global `$post`.
     * @return string
     *
     */
    public
    function company_job_title($title, $post)
    {

        if ($post->post_type == 'job_listing') {
            $term = $this->get_term_post($post->ID);
            $title = $term ? $term->name : $title;

        }
        return $title;


    }

    /**
     * Retrieves the term Company Title.
     *
     * @param string $title .
     * @param int|WP_Post $post Optional. Post ID or WP_Post object. Default is global `$post`.
     * @return string
     *
     */
    public
    function the_company_website($url, $post)
    {

        if ($post->post_type == 'job_listing') {
            $term = $this->get_term_post($post->ID);
            if ($term) {
                $acf_url = get_field('company_website', $term);
                $url = $acf_url ? $acf_url : $url;
            }

        }
        return $url;


    }

    /**
     * Retrieves the term Company Title.
     *
     * @param string $title .
     * @param int|WP_Post $post Optional. Post ID or WP_Post object. Default is global `$post`.
     * @return string
     *
     */
    public
    function the_company_twitter($twitter, $post)
    {

        if ($post->post_type == 'job_listing') {
            $term = $this->get_term_post($post->ID);
            if ($term) {
                $acf_twitter = get_field('company_twitter', $term);
                if (0 === strpos($acf_twitter, '@')) {
                    $acf_twitter = substr($acf_twitter, 1);
                }
                $twitter = $acf_twitter ? $acf_twitter : $twitter;
            }

        }
        return $twitter;


    }

    /**
     * Retrieves the term Company Title.
     *
     * @param string $title .
     * @param int|WP_Post $post Optional. Post ID or WP_Post object. Default is global `$post`.
     * @return string
     *
     */
    public
    function the_company_tagline($tagline, $post)
    {

        if ($post->post_type == 'job_listing') {
            $term = $this->get_term_post($post->ID);
            if ($term) {

                $tagline = $term->description ? $term->description : $tagline;
            }

        }
        return $tagline;


    }

    /**
     * Retrieves the term Company Title.
     *
     * @param string $title .
     * @param int|WP_Post $post Optional. Post ID or WP_Post object. Default is global `$post`.
     * @return string
     *
     */
    public
    function the_company_video($video, $post)
    {

        if ($post->post_type == 'job_listing') {
            $term = $this->get_term_post($post->ID);
            if ($term) {
                $acf_video = get_field('company_video', $term);

                $video = $acf_video ? $acf_video['url'] : $video;
            }

        }
        return $video;

    }

    public function submit_job_form_fields($fields)
    {
        if (is_user_logged_in()) {
            $args = [
                'taxonomy' => $this->term, // tax name WP 4.5
                'number' => '',
                'fields' => 'all',
                'hide_empty' => false,
                'count' => false,
                'meta_query' => array(
                    array(
                        'key' => 'company_users',
                        'value' => get_current_user_id(),
                        'compare' => 'LIKE' // optional, default is '=' or 'IN' (if value is an array)
                    )
                ),
            ];
            $newCompany = [];
            $terms = get_terms($args);
            if ($terms) {
                $newCompany['company_currents'] = ['label' => 'Your Company', 'required' => false, 'type' => 'select', 'options' => ['' => 'Create New Company']];
                foreach ($terms as $term) {
                    $newCompany['company_currents']['options'][$term->slug] = $term->name;
                }
            }

            $fields['company'] = array_merge($newCompany, $fields['company']);
            if (isset($_GET['action']) && $_GET['action'] == 'continue') {
                $fields['company']['company_name']['required'] = false;
            }
        }
        if (empty($fields['company']['company_bio'])) {
            $fields['company']['company_bio'] = ['label'=>'Company Bio', 'required' => false,	'type'     => 'wp-editor',	'priority'=> 1, ];
        }
        var_dump(12321);
        unset($fields['company']['company_twitter']);
        unset($fields['company']['company_video']);


        return $fields;
    }

    /**
     * Retrieves the term Company Title.
     *
     * @param array $fields .
     * @param int|WP_Post $job Optional. Post ID or WP_Post object. Default is global `$post`.
     * @return array
     *
     */
    public function submit_job_form_fields_get_job_data($fields, $job)
    {


        if (is_user_logged_in()) {
            if (!$fields['company']['company_currents']['value']) {
                $term = $this->get_term_post($job->ID);
                if ($term) {
                    $fields['company']['company_currents']['value'] = $term->slug;
                }
            }
        }

        return $fields;
    }

    public function submit_job_form_fields_get_user_data($fields, $user)
    {

        if ($fields['company']['company_currents']['value']) {
            foreach ($fields['company'] as $key => $item) {
                if ($key != 'company_currents')
                    $fields['company'][$key]['value'] = '';
                if ($key == 'company_name') {
                    $fields['company']['company_name']['value'] = '';
                }
            }
        }


        return $fields;
    }

    public function job_manager_get_posted_fields($values, $fields)
    {


        if ($values['company']['company_currents']) {
            $values['company']['company_name'] = $values['company']['company_currents'];
        }


        return $values;
    }

    public function submit_job_form_save_job_data($job_data, $post_title, $post_content, $status, $values)
    {

        if ($values['company']) {


            if (isset($values['company']['company_currents']) && $values['company']['company_currents']) {
                $term_slug = $values['company']['company_currents'];
                $args = [
                    'hide_empty' => false,
                    'taxonomy' => $this->term, // tax name WP 4.5
                    'slug' => $term_slug,
                ];
                $terms = get_terms($args);

                if ($terms) {
                    $job_data['meta_input'] = [];

                    $job_data['tax_input'] = array('companies' => $term_slug);    // Which taxonomies to attach the post to. Like 'post_category', but for new taxonomies.
                    foreach ($this->array_st as $item) {
                        $job_data['meta_input'] [$item] = $this->get_company_meta($item, $terms[0]);
                    }
                }

            } else {
                $insert_res = wp_insert_term(
                    $values['company']['company_name'],   // new term
                    'companies', // taxonomy
                    array(
                        'description' => $values['company']['company_bio'] ?: '',
                    )
                );

                if (!is_wp_error($insert_res)) {
                    $term_id = $insert_res['term_id'];
                    foreach ($values['company'] as $key => $value) {
                        update_term_meta($term_id, $key, $value);
                    }
                    update_term_meta($term_id, 'company_users', get_current_user_id());
                    $term_slug = get_term($term_id, 'companies')->slug;

                    $job_data['tax_input'] = array('companies' => $term_slug);    // Which taxonomies to attach the post to. Like 'post_category', but for new taxonomies.

                }
            }

            if ($term_slug) {
                update_user_meta(get_current_user_id(), '_company_currents', $term_slug);
            }


        } else {
            $job_data['tax_input'] = array('companies' => null);
        }


        return $job_data;
    }

    public function post_updated($post_id, $post)
    {

        if (isset($_POST['company_currents']) && !is_admin() || isset($_POST['job_manager_form']) && !is_admin()) {

            $term_slug = $_POST['company_currents'] ?: get_user_meta(get_current_user_id(), '_company_currents', true);

            $args = [
                'hide_empty' => false,
                'taxonomy' => $this->term, // tax name WP 4.5
                'slug' => $term_slug,
            ];
            $terms = get_terms($args);

            if ($terms) {
                wp_set_post_terms($post_id, $terms[0]->slug, $this->term);
//                $_POST = array();
//                wp_redirect(acf_get_current_url());
            }

        }


    }

}


/*
* Front-end styles
*/


