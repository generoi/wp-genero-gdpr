<?php

namespace GeneroWP\GDPR\Gravityforms;

use GeneroWP\GDPR\Crypto\Asymmetric as Crypto;
use GFAPI;
use GeneroWP\GDPR\Singleton;

class EncryptedField
{
    use Singleton;

    protected $crypto;
    protected static $validationError;

    public static function is_active()
    {
        return defined('GENERO_GDPR_ENCRYPT_ENABLED') && GENERO_GDPR_ENCRYPT_ENABLED;
    }

    public function plugins_loaded()
    {
        // Verify requirements.
        if (is_admin()) {
            self::activate();
        }

        // Do not attempt to encrypt unless requirements are met
        if (self::verifyRequirements()) {
            if (isset($_POST['genero_gdpr_decrypt_secret'])) {
                add_filter('gform_get_input_value', [$this, 'get_decrypted_value'], 10, 4);
            }

            add_filter('gform_field_standard_settings', [$this, 'encrypt_settings'], 10, 2);
            add_filter('gform_entry_detail_meta_boxes', [$this, 'entry_detail_meta_boxes'], 10, 3);
            add_action('gform_after_submission', [$this, 'encrypt_fields'], 10, 2);
            add_action('gform_editor_js', [$this, 'editor_js']);
            add_action('gform_post_entry_list', [$this, 'decrypt_metabox']);
        }
    }

    /**
     * Return a crypto instance.
     *
     * @return \GeneroWP\GDPR\Crypto
     */
    public function getCrypto()
    {
        if (!isset($this->crypto)) {
            $this->crypto = new Crypto();
        }
        return $this->crypto;
    }

    /**
     * Action callback; Encrypt all fields of a form submission after it has
     * already been submitted, that way all notifications still have access to
     * it.
     *
     * @param  array  $entry
     * @param  array  $form
     * @return void
     */
    public function encrypt_fields($entry, $form)
    {
        foreach ($form['fields'] as $field) {
            if (!empty($field->encrypt)) {
                $inputs = $field->get_entry_inputs();
                if (!is_array($inputs)) {
                    $value = rgar($entry, (string) $field->id);
                    $encrypted = $this->getCrypto()->encrypt($value);
                    GFAPI::update_entry_field($entry['id'], (string) $field->id, $encrypted);
                }
            }
        }
    }

    /**
     * Filter callback; Attach a decrypt meta box when viewing the entry
     * details of a submission.
     *
     * @param  array  $meta_boxes
     * @param  array  $entry
     * @param  array  $form
     * @return array
     */
    public function entry_detail_meta_boxes($meta_boxes, $entry, $form)
    {
        $has_encrypted_fields = false;
        foreach ($form['fields'] as $field) {
            if (!empty($field->encrypt)) {
                $has_encrypted_fields = true;
                break;
            }
        }

        if ($has_encrypted_fields) {
            $meta_boxes['decrypt_fields'] = [
                'title' => __('Decrypt data'),
                'callback' => [$this, 'decrypt_metabox'],
                'context' => 'normal',
            ];
        }
        return $meta_boxes;
    }

    /**
     * Filter callback; Decrypted a field value if it's encrypted and a
     * the secret has been sent through POST.
     *
     * @param  string  $value
     * @param  array  $entry
     * @param  \GF_Field  $field
     * @param  int  $input_id
     * @return string
     */
    public function get_decrypted_value($value, $entry, $field, $input_id)
    {
        if (empty($_POST['genero_gdpr_decrypt_secret'])) {
            return $value;
        }
        if (empty($field->encrypt) || !$value) {
            return $value;
        }
        $secret = sanitize_text_field($_POST['genero_gdpr_decrypt_secret']);
        try {
            $value = $this->getCrypto()->decrypt($value, $secret);
            return $value;
        } catch (\ParagonIE\Halite\Alerts\InvalidKey | \ParagonIE\Halite\Alerts\InvalidMessage $e) {
            return $value;
        }
    }

    /**
     * Callback; Print a form for submitting the decryption secret.
     *
     * @param  array  $args
     * @return void
     */
    public function decrypt_metabox($args)
    {
        ?>
        <form method="POST">
            <input type="password" name="genero_gdpr_decrypt_secret">
            <input type="submit" value="<?php echo __('Decrypt fields'); ?>">
        </form>
        <?php
    }

    /**
     * Filter callback; Print a setting to enable encryption on a gform field.
     *
     * @param  int  $position
     * @param  int  $form_id
     * @return void
     */
    public function encrypt_settings($position, $form_id)
    {
        if ($position === -1) {
            ?>
            <li class="encrypt_setting field_setting" style="display: list-item;">
                <input type="checkbox" id="field_encrypt" />
                <label for="field_encrypt" class="inline">
                    <?php _e('Encrypt', 'gravityforms'); ?>
                    <a href="#" onclick="return false;" onkeypress="return false;" class="gf_tooltip tooltip tooltip_form_field_label" title="<h6>Encrypt</h6>Encrypt this field value after having inserted it into the database. Note that this only activates for new entries."><i class="fa fa-question-circle"></i></a>
                </label>
            </li>
            <?php
        }
    }

    /**
     * Action callback; Process the encrypt setting added to the gform fields.
     * @see encrypt_settings().
     *
     * @return void
     */
    public function editor_js()
    {
        ?>
        <script>
            fieldSettings.text += ', .encrypt_setting';
            jQuery('#field_encrypt').on('click keypress', function(){
                SetFieldProperty('encrypt', this.checked);
            });
            jQuery(document).bind('gform_load_field_settings', function(event, field, form) {
                jQuery('#field_encrypt').prop('checked', field.encrypt == true ? true : false);
                jQuery('.encrypt_setting').show();
            });
        </script>
        <?php
    }

    public static function verifyRequirements()
    {
        if (!class_exists('ParagonIE\Halite\Halite')) {
            self::$validationError = sprintf(__('WP Genero GDPR: Encrypt feature requires the <code>%s</code> composer package.'), 'paragonie/halite');
        }
        if (!defined('GENERO_GDPR_PUBLIC_KEY')) {
            self::$validationError = __(
                'WP Genero GDPR: A public key path is required for <code>GENERO_GDPR_PUBLIC_KEY</code>.<br>' .
                'It can be generated by runnin <code>composer run generate-keys</code> in the plugin folder.'
            );
            return false;
        }
        if (!is_readable(GENERO_GDPR_PUBLIC_KEY)) {
            self::$validationError = sprintf(__('WP Genero GDPR: No public key found in the configured path: <code>%s</code>'), GENERO_GDPR_PUBLIC_KEY);
        }
        if (is_writable(GENERO_GDPR_PUBLIC_KEY)) {
            self::$validationError = sprintf(__('WP Genero GDPR: Public key is writable in path: <code>%s</code>'), GENERO_GDPR_PUBLIC_KEY);
        }

        return self::$validationError ? false : true;
    }

    public static function activate()
    {
        if (!self::verifyRequirements()) {
            wp_die(self::$validationError);
        }
    }
}
