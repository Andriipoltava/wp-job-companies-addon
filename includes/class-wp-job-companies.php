<?php


if (!defined('ABSPATH')) {
    exit;
}

abstract class BG_Job_Companies_Abstract
{
    protected $term = 'companies';

    protected $post_type = 'job_listing';
    public $array_st = ['company_name', 'company_website', 'company_tagline', 'company_bio', 'company_logo'];

    protected $fields = [];

    public function __construct()
    {
        $this->fields = apply_filters(
            'submit_job_form_fields',
            [
                'job' => [
                    'job_title' => [
                        'label' => __('Job Title', 'wp-job-manager'),
                        'type' => 'text',
                        'required' => true,
                        'placeholder' => '',
                        'priority' => 1,
                    ],
                    'job_location' => [
                        'label' => __('Location', 'wp-job-manager'),
                        'description' => __('Leave this blank if the location is not important', 'wp-job-manager'),
                        'type' => 'text',
                        'required' => false,
                        'placeholder' => __('e.g. "London"', 'wp-job-manager'),
                        'priority' => 2,
                    ],
                    'remote_position' => [
                        'label' => __('Remote Position', 'wp-job-manager'),
                        'description' => __('Select if this is a remote position.', 'wp-job-manager'),
                        'type' => 'checkbox',
                        'required' => false,
                        'priority' => 3,
                    ],

                    'job_category' => [
                        'label' => __('Job category', 'wp-job-manager'),
                        'type' => 'term-multiselect',
                        'required' => true,
                        'placeholder' => '',
                        'priority' => 5,
                        'default' => '',
                        'taxonomy' => 'job_listing_category',
                    ],
                    'job_description' => [
                        'label' => __('Description', 'wp-job-manager'),
                        'type' => 'wp-editor',
                        'required' => true,
                        'priority' => 6,
                    ],

                    'job_salary' => [
                        'label' => __('Salary', 'wp-job-manager'),
                        'type' => 'text',
                        'required' => false,
                        'placeholder' => __('e.g. 20000', 'wp-job-manager'),
                        'priority' => 8,
                    ],
                    'job_salary_currency' => [
                        'label' => __('Salary Currency', 'wp-job-manager'),
                        'type' => 'text',
                        'required' => false,
                        'placeholder' => __('e.g. USD', 'wp-job-manager'),
                        'description' => __('Add a salary currency, this field is optional. Leave it empty to use the default salary currency.', 'wp-job-manager'),
                        'priority' => 9,
                    ],

                ],
                'company' => [
                    'company_name' => [
                        'label' => __('Company name', 'wp-job-manager'),
                        'type' => 'text',
                        'required' => true,
                        'placeholder' => __('Enter the name of the company', 'wp-job-manager'),
                        'priority' => 1,
                    ],
                    'company_bio' => [
                        'label' => __('Company Bio', 'wp-job-manager'),
                        'type' => 'wp-editor',

                        'required' => false,

                        'priority' => 1,
                    ],
                    'company_website' => [
                        'label' => __('Website', 'wp-job-manager'),
                        'type' => 'text',
                        'sanitizer' => 'url',
                        'required' => false,
                        'placeholder' => __('http://', 'wp-job-manager'),
                        'priority' => 2,
                    ],
                    'company_tagline' => [
                        'label' => __('Tagline', 'wp-job-manager'),
                        'type' => 'text',
                        'required' => false,
                        'placeholder' => __('Briefly describe your company', 'wp-job-manager'),
                        'maxlength' => 64,
                        'priority' => 3,
                    ],
//                    'company_video' => [
//                        'label' => __('Video', 'wp-job-manager'),
//                        'type' => 'text',
//                        'sanitizer' => 'url',
//                        'required' => false,
//                        'placeholder' => __('A link to a video about your company', 'wp-job-manager'),
//                        'priority' => 4,
//                    ],
//                    'company_twitter' => [
//                        'label' => __('Twitter username', 'wp-job-manager'),
//                        'type' => 'text',
//                        'required' => false,
//                        'placeholder' => __('@yourcompany', 'wp-job-manager'),
//                        'priority' => 5,
//                    ],
                    'company_logo' => [
                        'label' => __('Logo', 'wp-job-manager'),
                        'type' => 'file',
                        'required' => false,
                        'placeholder' => '',
                        'priority' => 6,
                        'ajax' => true,
                        'multiple' => false,
                        'allowed_mime_types' => [
                            'jpg' => 'image/jpeg',
                            'jpeg' => 'image/jpeg',
                            'gif' => 'image/gif',
                            'png' => 'image/png',
                        ],
                    ],
                ],
            ]
        );

    }

    /**
     * Retrieves the post thumbnail ID.
     *
     * @param string $field_key .
     * @param int|WP_Term $term Optional. Post ID or WP_Post object. Default is global `$post`.
     * @return string|null
     *
     */

    function get_company_meta($field_key, $term)
    {
        if ($field_key == 'company_bio') {
            return $term->description;
        }
        if ($field_key == 'company_name') {
            return $term->name;
        }
        $value = get_field($field_key, $term);
        if ($field_key == 'company_video') {
            $value = $value ? $value['url'] : null;
        }
        if ($field_key == 'company_logo') {
            $value = $value ? $value['id'] : null;
        }
        return $value;

    }

    function get_term_post($post_id)
    {
        $the_new_company_taxonomy = wp_get_post_terms($post_id, $this->term);

        return is_array($the_new_company_taxonomy) ? $the_new_company_taxonomy[0] : null;
    }

    public function get_fields($name)
    {
        return $this->fields[$name] ?: null;
    }


    function set_company_meta($field_key, $value, $tax_obj)
    {
        if ($field_key == 'company_bio') {
            return wp_update_term($tax_obj->term_id, $this->term, [
                'description' => $value,
            ]);
        }
        if ($field_key == 'company_name') {
            return wp_update_term($tax_obj->term_id, $this->term, [
                'name' => $value,
            ]);
        }

        return update_field($field_key, $value, $tax_obj);;

    }

    public function has_auther($current_user, $tax_obj)
    {
        $test = false;
        $list_user = get_field('company_users', $tax_obj);
        if (is_string($list_user) && $list_user == $current_user) $test = true;
        if (is_array($list_user)) {
            foreach ($list_user as $user) {
                if ($user['ID'] == $current_user) $test = true;
            }
        }


        return $test;
    }

}


/*
* Front-end styles
*/


