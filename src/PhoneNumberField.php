<?php

namespace Log1x\AcfPhoneNumber;

class PhoneNumberField extends \acf_field
{
    /**
     * Field Name
     *
     * @var string
     */
    public $name = 'phone_number';

    /**
     * Field Label
     *
     * @var string
     */
    public $label = 'Phone Number';

    /**
     * The field type.
     *
     * @var string
     */
    public $type = 'phone';

    /**
     * Field Category
     *
     * @var string
     */
    public $category = 'basic';

    /**
     * Field Defaults
     *
     * @var array
     */
    public $defaults = [
        'country' => 'us',
    ];

    /**
     * Settings
     *
     * @var object
     */
    protected $settings;

    /**
     * Create a new phone number field instance.
     *
     * @param  string $uri
     * @param  string $path
     * @return void
     */
    public function __construct($uri, $path)
    {
        $this->uri = $uri;
        $this->path = $path;
        $this->phoneNumber = new PhoneNumber();

        parent::__construct();
    }

    /**
     * Create the HTML interface for your field.
     *
     * @param  array $field
     * @return void
     */
    public function render_field($field)
    {
        if (! is_array($field['value']) || empty($field['value']['number'])) {
            $field['value'] = ['number' => $value ?? null];
        }

        if (empty($field['value']['country'])) {
            $field['value']['country'] = $field['default_country'] ?? $this->defaults['country'];
        }

        echo sprintf(
            '<input type="tel" name="%s[number]" value="%s" />',
            $field['name'],
            $field['value']['number']
        );

        echo sprintf(
            '<input data-default-country="%s" type="hidden" name="%s[country]" value="%s" />',
            $field['default_country'] ?? $this->defaults['country'],
            $field['name'],
            $field['value']['country'] ?? $field['default_country'] ?? $this->defaults['country']
        );
    }

    /**
     * Create extra settings for your field. These are visible when editing a field.
     *
     * @param  array $field
     * @return void
     */
    public function render_field_settings($field)
    {
        acf_render_field_setting($field, [
            'label' => 'Default Country',
            'instructions' => 'The default country value for the phone number.',
            'type' => 'select',
            'ui' => 1,
            'name' => 'default_country',
            'default_value' => $this->defaults['country'],
            'choices' => $this->phoneNumber->getCountries()
        ]);
    }

    /**
     * This action is called in the admin_enqueue_scripts action on the edit screen where
     * your field is created.
     *
     * @return void
     */
    public function input_admin_enqueue_scripts()
    {
        wp_enqueue_script('acf-' . $this->name, $this->asset('/js/field.js'), ['acf-input'], null);
        wp_enqueue_style('acf-' . $this->name, $this->asset('/css/field.css'), [], null);
    }

    /**
     * This filter is applied to the $value after it is loaded from the database and
     * before it is returned to the template.
     *
     * @param  mixed $value
     * @param  mixed $post_id
     * @param  array $field
     * @return mixed
     */
    public function format_value($value, $post_id, $field)
    {
        return $this->phoneNumber->parse($value);
    }

    /**
     * This filter is applied to the $value before it is saved in the database.
     *
     * @param  mixed $value
     * @param  mixed $post_id
     * @param  array $field
     * @return mixed
     */
    public function update_value($value, $post_id, $field)
    {
        if (! is_array($value) || empty($value['number'])) {
            return;
        }

        return $value;
    }

    /**
     * This filter is used to perform validation on the value prior to saving.
     *
     * @param  boolean $valid
     * @param  mixed   $value
     * @param  array   $field
     * @param  array   $input
     * @return boolean
     */
    public function validate_value($valid, $value, $field, $input)
    {
        if (! is_array($value) || empty($value['number'])) {
            return $valid;
        }

        if (empty($value['country'])) {
            return 'The phone number country entered is not valid.';
        }

        return $this->phoneNumber->parse($value)->isValid() ?
            $valid : 'The phone number entered is not valid.';
    }

    /**
     * Resolve the URI for an asset using the manifest.
     *
     * @return string
     */
    public function asset($asset = '', $manifest = 'mix-manifest.json')
    {
        if (! file_exists($manifest = $this->path . $manifest)) {
            return $this->uri . $asset;
        }

        $manifest = json_decode(file_get_contents($manifest), true);

        return $this->uri . ($manifest[$asset] ?? $asset);
    }
}
