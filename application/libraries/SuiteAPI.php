<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 

class SuiteApi
{
    private
        $_username,
        $_secret,
        $_suiteApiUrl;

    public function __construct($username, $secret, $suiteApiUrl = 'https://suite11.emarsys.net/api/v2/')
    {
        $this->_username = $username;
        $this->_secret = $secret;
        $this->_suiteApiUrl = $suiteApiUrl;
    }

    public function send($requestType, $endPoint, $requestBody = '')
    {
        if (!in_array($requestType, array('GET', 'POST', 'PUT', 'DELETE'))) {
            throw new Exception('Send first parameter must be "GET", "POST", "PUT" or "DELETE"');
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        switch ($requestType)
        {
            case 'GET':
                curl_setopt($ch, CURLOPT_HTTPGET, 1);
                break;
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);
                break;
            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);
                break;
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);
                break;
        }
        curl_setopt($ch, CURLOPT_HEADER, true);

        $requestUri = $this->_suiteApiUrl . $endPoint;

        curl_setopt($ch, CURLOPT_URL, $requestUri);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        /**
         * We add X-WSSE header for authentication.
         * Always use random 'nonce' for increased security.
         * timestamp: the current date/time in UTC format encoded as
         *   an ISO 8601 date string like '2010-12-31T15:30:59+00:00' or '2010-12-31T15:30:59Z'
         * passwordDigest looks sg like 'MDBhOTMwZGE0OTMxMjJlODAyNmE1ZWJhNTdmOTkxOWU4YzNjNWZkMw=='
         */
        $nonce = md5(uniqid(mt_rand(), true));

        $timestamp = gmdate("c");
        $passwordDigest = base64_encode(sha1($nonce . $timestamp . $this->_secret, false));

        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "X-WSSE: UsernameToken," .
                "Username=\"".$this->_username."\", " .
                "PasswordDigest=\"".$passwordDigest."\", " .
                "Nonce=\"".$nonce."\", " .
                "Created=\"".$timestamp."\"",
            "Content-type: application/json;charset=\"utf-8\"",
            )
        );

        $output = curl_exec($ch);
        
        curl_close($ch);	
        return $output;
    }
}