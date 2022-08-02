<?php
// -----
// Part of the "Access Blocker" plugin by lat9 (https://vinosdefrutastropicales.com)
// Copyright (c) 2019-2022, Vinos de Frutas Tropicales.
//
// Last updated: v1.5.0
//
// A class to gather and return information about a specified IP address using the
// service provided by https://ipdata.co.
//
if (!defined('IPDATA_LOGGING')) {
    define('IPDATA_LOGGING', 'false');
}
if (!defined('ACCESSBLOCK_USE_EU_ENDPOINT')) {
    define('ACCESSBLOCK_USE_EU_ENDPOINT', 'false');
}
class ipData
{
    protected $response;

    public function __construct($api_key, $ip_address)
    {
        $this->response = false;

        $ch = curl_init();

        $api_key = (empty($api_key)) ? '' : "?api-key=$api_key";
        $endpoint = (ACCESSBLOCK_USE_EU_ENDPOINT === 'false') ? 'https://api.ipdata.co/' : 'https://eu-api.ipdata.co/';

        curl_setopt($ch, CURLOPT_URL, $endpoint . $ip_address . $api_key);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);

        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Accept: application/json"
        ]);

        $response = curl_exec($ch);
        $error_msg = curl_error($ch);
        $error_num = curl_errno($ch);
        $comm_info = @curl_getinfo($ch);
        curl_close($ch);

        if ($error_num !== 0) {
            $this->debug("Error returned checking $ip_address$api_key: [$error_num] - $error_msg. " . var_export($comm_info, true) . var_export($response, true), 'error');
        } else {
            $this->response = json_decode($response);
            $this->debug("ipData($endpoint$ip_address$api_key) returned: " . $response);
        }
    }

    public function getIpOrganization()
    {
        return ($this->response === false) ? false : $this->response->organisation;
    }

    public function getIpCountry()
    {
        return ($this->response === false) ? false : $this->response->country_code;
    }

    public function isIpKnownAbuser()
    {
        return ($this->response === false) ? false : $this->response->threat->is_known_abuser;
    }

    public function isIpThreat()
    {
        return ($this->response === false) ? false : $this->response->threat->is_threat;
    }

    protected function debug($message, $type = '')
    {
        if (IPDATA_LOGGING === 'all' || $type === 'error') {
            error_log(date('Y-m-d H:i:s: ') . $message . PHP_EOL, 3, DIR_FS_LOGS . '/ipData.log');
        }
    }
}
