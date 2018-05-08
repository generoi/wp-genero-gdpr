<?php

namespace GeneroWP\GDPR\Gravityforms;

use GFAPI;
use GeneroWP\GDPR\Singleton;

class ExpireSubmissions
{
    use Singleton;

    public static function is_active()
    {
        return true;
    }

    public function plugins_loaded()
    {
        add_action('gf_expire_submissions_cron', [$this, 'expire_submissions']);
        add_filter('gform_form_settings', [$this, 'gform_settings'], 10, 2);
        add_filter('gform_pre_form_settings_save', [$this, 'gform_settings_save'], 10, 2);

        if (!wp_next_scheduled('gf_expire_submissions_cron')) {
            wp_schedule_event(time(), 'daily', 'gf_expire_submissions_cron');
        }
    }

    public function expire_submissions()
    {
        $forms = array_merge(
            GFAPI::get_forms(true, false),
            GFAPI::get_forms(false, false)
        );

        foreach ($forms as $form) {
            $disable_expiration = rgar($form, 'disable_expiration');
            $expiration_time = rgar($form, 'expiration_time') ?: apply_filters('wp-genero-gdpr/expire-submissions/default_expiration_time', '1 day');
            if (empty($disable_expiration)) {
                $entries = GFAPI::get_entries($form['id'], $this->search_criteria($expiration_time));
                foreach ($entries as $entry) {
                    GFAPI::delete_entry($entry['id']);
                }
            }

        }
    }

    public function gform_settings($settings, $form)
    {
        $settings['Expiration']['disable_expiration'] = '
            <tr>
                <th><label for="disable_expiration">' . __('Disable expiration', 'wp-genero-gdpr') . '</label></th>
                <td><input type="checkbox" value="1" ' . (rgar($form, 'disable_expiration') ? 'checked' : '') . ' name="disable_expiration"></td>
            </tr>
            <tr>
                <th><label for="expiration_time">' . __('Maximum age', 'wp-genero-gdpr') . '</label></th>
                <td><input value="' . rgar($form, 'expiration_time') . '" placeholder="1 year" name="expiration_time"></td>
            </tr>
        ';
        return $settings;
    }

    /**
    * Save the added form options.
    */
    public function gform_settings_save($form) {
        $form['disable_expiration'] = rgpost('disable_expiration');
        $form['expiration_time'] = rgpost('expiration_time');
        return $form;
    }

    public function search_criteria($expiration_time)
    {
        return [
            'page_size' => apply_filters('wp-genero-gdpr/expire-submissions/pager', 200),
            'start_date' => date('Y-m-d H:i:s', 0),
            'end_date' => date('Y-m-d H:i:s', strtotime('-' . apply_filters('wp-genero-gdpr/expire-submissions/expiration_time', $expiration_time))),
        ];
    }

    public static function deactivate()
    {
        $timestamp = wp_next_scheduled('gf_expire_submissions_cron');
        wp_unschedule_event($timestamp, 'gf_expire_submissions_cron');
    }
}
