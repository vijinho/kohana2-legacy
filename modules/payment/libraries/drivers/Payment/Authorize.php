<?php defined('SYSPATH') or die('No direct access allowed.');
/**
 * Authorize.net Payment Driver
 *
 * $Id: Authorize.php 4160 2009-04-07 21:03:16Z ixmatus $
 *
 * @package    Payment
 * @author     Kohana Team
 * @copyright  (c) 2007-2008 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class Payment_Authorize_Driver implements Payment_Driver
{
    // Array containing any response codes set from the gateway
    private $response        = null;
    
    private $transaction     = false;

    // Fields required to do a transaction
    private $required_fields = array(
        'x_login'           => false,
        'x_version'         => true,
        'x_delim_char'      => true,
        'x_url'             => true,
        'x_type'            => true,
        'x_method'          => true,
        'x_tran_key'        => false,
        'x_relay_response'  => true,
        'x_card_num'        => false,
        'x_exp_date'        => false,
        'x_amount'          => false,
    );

    // Default required values
    private $authnet_values = array(
        'x_version'         => '3.1',
        'x_delim_char'      => '|',
        'x_delim_data'      => 'TRUE',
        'x_url'             => 'FALSE',
        'x_type'            => 'AUTH_CAPTURE',
        'x_method'          => 'CC',
        'x_relay_response'  => 'FALSE',
    );

    private $test_mode = true;

    /**
     * Sets the config for the class.
     *
     * @param  array  config passed from the library
     */
    public function __construct($config)
    {
        $this->authnet_values['x_login']    = $config['auth_net_login_id'];
        $this->authnet_values['x_tran_key'] = $config['auth_net_tran_key'];
        
        $this->required_fields['x_login']   = !empty($config['auth_net_login_id']);
        $this->required_fields['x_tran_key']= !empty($config['auth_net_tran_key']);

        $this->curl_config  = $config['curl_config'];
        $this->test_mode    = $config['test_mode'];

        Kohana::log('debug', 'Authorize.net Payment Driver Initialized');
    }

    public function set_fields($fields)
    {
        foreach ((array) $fields as $key => $value) {
            $this->authnet_values['x_'.$key] = $value;
            if (array_key_exists('x_'.$key, $this->required_fields) and !empty($value)) {
                $this->required_fields['x_'.$key] = true;
            }
        }
    }
    
    /**
     * Retreives the response array from a successful
     * transaction.
     *
     * @return array or Null
     */
    public function get_response()
    {
        if (!$this->transaction) {
            return $this->response;
        }
        
        return null;
    }

    /**
     * Process a given transaction.
     *
     * @return boolean|null
     */
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
        foreach ($this->authnet_values as $key => $value) {
            $fields .= $key.'='.urlencode($value).'&';
        }

        $post_url = ($this->test_mode) ?
                    'https://certification.authorize.net/gateway/transact.dll' : // Test mode URL
                    'https://secure.authorize.net/gateway/transact.dll'; // Live URL

        $ch = curl_init($post_url);

        // Set custom curl options
        curl_setopt_array($ch, $this->curl_config);

        // Set the curl POST fields
        curl_setopt($ch, CURLOPT_POSTFIELDS, rtrim($fields, '& '));

        //execute post and get results
        $response = curl_exec($ch);
        
        curl_close($ch);
        
        if (!$response) {
            throw new Kohana_Exception('payment.gateway_connection_error');
        }

        // This could probably be done better, but it's taken right from the Authorize.net manual
        // Need testing to opimize probably
        $heading = substr_count($response, '|');

        for ($i=1; $i <= $heading; $i++) {
            $delimiter_position = strpos($response, '|');

            if ($delimiter_position !== false) {
                $response_code = substr($response, 0, $delimiter_position);
                
                $response_code = rtrim($response_code, '|');
                
                if ($response_code == '') {
                    throw new Kohana_Exception('payment.gateway_connection_error');
                }

                switch ($i) {
                    case 1:
                        $this->response    = (($response_code == '1') ? explode('|', $response) : false); // Approved

                        $this->transaction = true;
                        
                        return $this->transaction;
                    default:
                        $this->transaction = false;
                        
                        return $this->transaction;
                }
            }
        }
    }
} // End Payment_Authorize_Driver Class
