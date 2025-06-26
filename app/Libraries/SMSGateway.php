<?php

namespace App\Libraries;

class SMSGateway
{
    protected $gatewayVariables = array(
        'sender_id' => 'HAPSIN',
        // 'sender_id' => 'SEEBDS',
        'key' => 'addGYdRieeyho068Dp6uwg==',
        'version' => '1.0',
        'encrypt' => '0',
    );

    public function get($variable)
    {
        return $this->gatewayVariables[$variable];
    }

    public function sendOTP($destination, $otp)
    {
        // $text = "Your SEEB verification code is $otp. It is valid for 10 minutes. Do not share this code with anyone. -www.seeb.in";
        $text = "$otp is your OTP for verification with Seeb \n\n-Team Haps";
        $text = rawurlencode($text);
        $data = array(
            'ver' => $this->gatewayVariables['version'],
            'key' => $this->gatewayVariables['key'],
            'encrpt' => $this->gatewayVariables['encrypt'],
            'dest' => $destination,
            'send' => $this->gatewayVariables['sender_id'],
            'text' => $text,
        );

        return $this->sendRequest($data);
    }

    public function sendRequest($data)
    {
        $url = 'https://japi.instaalerts.zone/httpapi/QueryStringReceiver';
        $optionsArray = array();
        // print_r($data);

        foreach ($data as $key => $value) {
            $optionsArray[] = $key . '=' . $value;
        }

        $options = implode('&', $optionsArray);

        // echo ($url . '?' . urlencode($options));die;
        $sendURI = $url . '?' . $options;
        // echo $sendURI;
        $crl = curl_init();
        curl_setopt($crl, CURLOPT_URL, $sendURI);
        curl_setopt($crl, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($crl, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($crl);
        $output = explode(' & ', $response);
        $responseArray = array(
            'requestID' => explode('=', $output[0])[1],
            'statusCode' => explode('=', $output[1])[1],
            'info' => explode('=', $output[2])[1],
            'time' => explode('=', $output[3])[1],
        );
        // print_r($responseArray);
        return (object)$responseArray;
    }
}
