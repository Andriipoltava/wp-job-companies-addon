<?php
/**
 * Plugin Name: WP Job - Company Addon
 * Plugin URI:  https://github.com/astoundify/wp-job-manager-companies
 * Description: Output a list of all companies that have posted a job, with a link to a company profile.
 * Author:      Black Digital
 * Author URI:  https://blackdigitalgroup.com/
 * Version:     0.1
 * Text Domain: wp-job-companies-addon
 */

include 'includes/class-wp-job-companies.php';
include 'includes/class-wp-job-companies-edit.php';
include 'includes/class-wp-job-companies-term.php';


add_action('plugins_loaded',function (){
    BG_Job_Companies_Edit::instance();
    BG_Job_Companies_Term::instance();

});

add_action('wp_footer', function () {


        ?>

    <script>
        jQuery(document).ready(function ($) {
            const _form = $('#submit-job-form'),
                company_curren = _form.find('#company_currents')

            refactForm(company_curren.val())
            company_curren.on('change', function () {
                refactForm(company_curren.val())
            })

            function refactForm(hide = false) {
                let lists = _form.find('.fieldset-company_name,' +
                    '.fieldset-company_website,' +
                    '.fieldset-company_tagline,' +
                    '.fieldset-company_video,' +
                    '.fieldset-company_twitter,' +
                    '.fieldset-company_bio,' +
                    '.fieldset-company_logo')
                lists.each(function (r) {
                    $(this).find('input').prop('disabled', hide);
                })
                if (hide) {
                    lists.hide()
                } else {

                    lists.show()

                }
            }

        })
    </script>


    <?php
});
