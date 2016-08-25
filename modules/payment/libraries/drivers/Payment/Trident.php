<?php defined('SYSPATH') or die('No direct access allowed.');
/**
 * Trident Payment Driver
 *
 * $Id: Trident.php 3769 2008-12-15 00:48:56Z zombor $
 *
 * @package    Payment
 * @author     Kohana Team
 * @copyright  (c) 2007-2008 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class Payment_Trident_Driver implements Payment_Driver
{
    // Fields required to do a transaction
    private $required_fields = array(
        'profile_id'         => false,
        'profile_key'        => false,
        'card_number'        => false,
        'card_exp_date'      => false,
        'transaction_amount' => false,
        'transaction_type'   => false
    );

    private $fields = array(
        'profile_id'         => '',
        'profile_key'        => '',
        'card_number'        => '',
        'card_exp_date'      => '',
        'transaction_amount' => '',
        'transaction_type'   => ''
    );

    private $test_mode = true;

    /**
     * Sets the config for the class.
     *
     * @param  array  config passed from the library
     */
    public function __construct($config)
    {
        $this->fields['profile_id'] = $config['profile_id'];
        $this->fields['profile_key'] = $config['profile_key'];
        $this->fields['transaction_type'] = $config['transaction_type'];
        $this->required_fields['profile_id'] = !empty($config['profile_id']);
        $this->required_fields['profile_key'] = !empty($config['profile_key']);
        $this->required_fields['transaction_type'] = !empty($config['transaction_type']);

        $this->curl_config = $config['curl_config'];
        $this->test_mode = $config['test_mode'];

        Kohana::log('debug', 'Trident Payment Driver Initialized');
    }

    public function set_fields($fields)
    {
        foreach ((array) $fields as $key => $value) {
            // Do variable translation
            switch ($key) {
                case 'card_num':
                    $key = 'card_number';
                    break;
                case 'exp_date':
                    $key = 'card_exp_date';
                    break;
                case 'amount':
                    $key = 'transaction_amount';
                    break;
                case 'tax':
                    $key = 'tax_amount';
                    break;
                case 'cvv':
                    $key = 'cvv2';
                    break;
                default:
                    break;
            }

            $this->fields[$key] = $value;
            if (array_key_exists($key, $this->required_fields) and !empty($value)) {
                $this->required_fields[$key] = true;
            }
        }
    }

    public function process()
    {
        // Check for required fields
        if (in_array(false, $this->required_fields)) {
            $fields = array();
            foreach ($this->required_fields as $key => $field) {
                if (!$field) {
                    $fields[] = $key;
                }
            }
            throw new Kohana_Exception('payment.required', implode(', ', $fields));
        }

        $fields = '';
        foreach ($this->fields as $key => $value) {
            $fields .= $key.'='.urlencode($value).'&';
        }

        $post_url = ($this->test_mode)
                  ? 'https://test.merchante-solutions.com/mes-api/tridentApi' // Test mode URL
                  : 'https://api.merchante-solutions.com/mes-api/tridentApi'; // Live URL

        $ch = curl_init($post_url);

        // Set custom curl options
        curl_setopt_array($ch, $this->curl_config);

        // Set the curl POST fields
        curl_setopt($ch, CURLOPT_POSTFIELDS, rtrim($fields, "& "));

        // Execute post and get results
        $response = curl_exec($ch);
        curl_close($ch);
        if (!$response) {
            throw new Kohana_Exception('payment.gateway_connection_error');
        }

        $response = explode('&', $response);
        foreach ($response as $code) {
            $temp = explode('=', $code);
            $response[$temp[0]] = $temp[1];
        }

        return ($response['error_code'] == '000') ? true : Kohana::lang('payment.error', Kohana::lang('payment_Trident.'.$response['error_code']));
    }
} // End Payment_Trident_Driver Class
