<?php
namespace Emite;

use Psr\Log\LogLevel;

class Emite
{
    /**
     * @var string Version
     */
    public static $VERSION = '2019.06.1';
    private $log;
    /**
     * @var array Settings
     */
    private $settings = array(
        'environment' => 'production',
        'scheme' => 'https',
        'port' => 443,
        'timeout' => 30,
        'debug' => false,
        'curl_options' => array(),
        'host' => array(
            'production' => 'api.emite.pe',
            'qa' => 'api.qa.emite.pe',
            'integ' => 'api.integ.emite.pe'
        ),
    );
    /**
     * @var null|resource
     */
    private $curl = null; // Curl handler
    /**
     * Initializes a new Doulivery instance with key, secret, app_id.
     *
     * @param string $key
     * @param string $secret
     * @param array $options [optional]
     *                         Options to configure the Emite instance.
     *                         environment - e.g. production, integ, qa (production is default)
     *                         scheme - e.g. http or https
     *                         host - the host e.g. api.emite.pe. No trailing forward slash.
     *                         port - the http port
     *                         timeout - the http timeout
     *                         useTLS - quick option to use scheme of https and port 443.
     *                         debug - (default `false`) if `true`, every `trigger()` and `triggerBatch()` call will return a `$response` object, useful for logging/inspection purposes.
     *                         curl_options - wrapper for curl_setopt, more here: http://php.net/manual/en/function.curl-setopt.php
     * @throws EmiteException If any required dependencies are missing
     */
    public function __construct($key, $secret, $options = array())
    {
        $this->check_compatibility();

        $useTLS = false;
        if (isset($options['useTLS'])) {
            $useTLS = $options['useTLS'] === true;
        }
        if (
            $useTLS &&
            !isset($options['scheme']) &&
            !isset($options['port'])
        ) {
            $options['scheme'] = 'https';
            $options['port'] = 443;
        }
        $this->settings['key'] = $key;
        $this->settings['secret'] = $secret;
        foreach ($options as $k => $v) {
            if (isset($this->settings[$k])) {
                $this->settings[$k] = $v;
            }
        }
        $this->settings['host'] = preg_replace('/http[s]?\:\/\//', '', $this->settings['host'], 1);
        $this->log = new Log();
    }
    /**
     * Fetch the settings.
     *
     * @return array
     */
    public function getSettings()
    {
        return $this->settings;
    }
    /**
     * Set a logger to be informed of internal log messages.
     *
     * @param object $logger A object
     *
     * @return void
     */
    public function setLogger($logger)
    {
        $this->log->setLogger($logger);
    }
    /**
     * Check if the current PHP setup is sufficient to run this class.
     *
     * @return void
     * @throws EmiteException If any required dependencies are missing
     *
     */
    private function check_compatibility()
    {
        if (!extension_loaded('curl')) {
            throw new EmiteException('The Emite library requires the PHP cURL module. Please ensure it is installed');
        }
        if (!extension_loaded('json')) {
            throw new EmiteException('The Emite library requires the PHP JSON module. Please ensure it is installed');
        }
    }
    /**
     * Utility function used to create the curl object with common settings.
     *
     * @param string $domain
     * @param string $s_url
     * @param string [optional] $request_method
     * @param array [optional]  $query
     *
     * @return resource
     * @throws EmiteException Throws exception if curl wasn't initialized correctly
     *
     */
    private function create_curl($domain, $s_url, $request_method = 'GET', $query = array())
    {
        $full_url = $domain . $s_url . '?' . $query;
        $this->log->log('create_curl( {{full_url}} )', array('full_url' => $full_url));
        if (!is_resource($this->curl)) {
            $this->curl = curl_init();
        }
        if ($this->curl === false) {
            throw new EmiteException('Could not initialise cURL!');
        }
        $curl = $this->curl;
        if (function_exists('curl_reset')) {
            curl_reset($curl);
        }
        curl_setopt($curl, CURLOPT_URL, $full_url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'emite_id: ' . $this->settings['key'],
            'emite_key: ' . $this->settings['secret'],
            'Content-Type: application/json',
            'X-Emite-Library: emite-php ' . self::$VERSION,
        ));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, $this->settings['timeout']);
        if ($request_method === 'POST') {
            curl_setopt($curl, CURLOPT_POST, 1);
        } elseif ($request_method === 'GET') {
            curl_setopt($curl, CURLOPT_POST, 0);
        }
        if (!empty($this->settings['curl_options'])) {
            foreach ($this->settings['curl_options'] as $option => $value) {
                curl_setopt($curl, $option, $value);
            }
        }
        return $curl;
    }
    /**
     * Utility function to execute curl and create capture response information.
     *
     * @param $curl resource
     *
     * @return array
     */
    private function exec_curl($curl)
    {
        $response = array();
        $response['body'] = curl_exec($curl);
        $response['status'] = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if ($response['body'] === false) {
            $this->log->log('exec_curl error: {error}', array('error' => curl_error($curl)), LogLevel::ERROR);
        } elseif ($response['status'] < 200 || 400 <= $response['status']) {
            $this->log->log('exec_curl {{status}} error from server: {{body}}', $response, LogLevel::ERROR);
        } else {
            $this->log->log('exec_curl {{status}} response: {{body}}', $response);
        }
        $this->log->log('exec_curl response: {{response}}', array('response' => print_r($response, true)));
        return $response;
    }
    /**
     * Build the Channels domain.
     *
     * @return string
     */
    private function get_domain()
    {
        return $this->settings['scheme'] . '://' . $this->settings['host']['environment'] . ':' . $this->settings['port'] . '/';
    }
    /**
     * Fetch a list containing all channels.
     *
     * @param array $serie
     * @param array $correlativo
     * @param array $params Additional parameters for the query e.g. $params = array( 'info' => 'connection_count' )
     *
     * @return array|bool
     * @throws EmiteException Throws exception if curl wasn't initialized correctly
     *
     */
    public function get_factura_status($serie, $correlativo, $params = array())
    {
        $response = $this->get('/factura/{$serie}-{$correlativo}', $params);
        if ($response['status'] === 200) {
            $response = json_decode($response['body']);
            $response->channels = get_object_vars($response->channels);
            return $response;
        }
        return false;
    }
    /**
     * Trigger an event by providing event name and payload.
     * Optionally provide a socket ID to exclude a client (most likely the sender).
     *
     * @param string $s_url
     * @param array $data
     * @param bool $debug [optional]
     *
     * @return bool|array
     * @throws EmiteException Throws exception if $channels is an array of size 101 or above or $socket_id is invalid
     *
     */
    private function post($s_url, $data, $debug = false)
    {
        $query_params = array();
        $data_encoded = json_encode($data);
        //$s_url = sprintf('/app/%s/environment/%s/publish', $this->settings['app_id'], $this->settings['environment']);
        if (!$data_encoded) {
            $this->log->log('Failed to perform json_encode on the the provided data: {{error}}', array(
                'error' => print_r($data, true),
            ), LogLevel::ERROR);
        }
        $post_params = array();
        $post_params['data'] = $data_encoded;
        $post_value = json_encode($post_params);
        $query_params['body_md5'] = md5($post_value);
        $curl = $this->create_curl($this->get_domain(), $s_url, 'POST', $query_params);
        $this->log->log('trigger POST: {{post_value}}', compact('post_value'));
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post_value);
        $response = $this->exec_curl($curl);
        if ($debug === true || $this->settings['debug'] === true) {
            return $response;
        }
        if ($response['status'] === 200) {
            return true;
        }
        return false;
    }
    /**
     * GET arbitrary REST API resource using a synchronous http client.
     * All request signing is handled automatically.
     *
     * @param string $path Path excluding /apps/APP_ID
     * @param array $params API params
     *
     * @return array|bool
     * @throws EmiteException Throws exception if curl wasn't initialized correctly
     *
     */
    public function get($path, $params = array())
    {
        $s_url = $this->settings['base_path'] . $path;
        $curl = $this->create_curl($this->get_domain(), $s_url, 'GET', $params);
        $response = $this->exec_curl($curl);
        if ($response['status'] === 200) {
            $response['result'] = json_decode($response['body'], true);
            return $response;
        }
        return false;
    }
}