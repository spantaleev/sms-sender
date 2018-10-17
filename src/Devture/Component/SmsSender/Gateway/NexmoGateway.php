<?php
namespace Devture\Component\SmsSender\Gateway;

use Devture\Component\SmsSender\Message;
use Devture\Component\SmsSender\Exception\SendingFailedException;
use Devture\Component\SmsSender\Exception\BalanceRetrievalFailedException;

class NexmoGateway implements GatewayInterface {

    private $apiKey;
    private $apiSecret;
    private $baseApiUrl;

    public function __construct($apiKey, $apiSecret) {
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
        $this->baseApiUrl = 'https://rest.nexmo.com';
    }

    public function send(Message $message) {
        $data = array(
            'api_key' => $this->apiKey,
            'api_secret' => $this->apiSecret,
            'from' => $message->getSender(),
            'to' => $message->getPhoneNumber(),
            'text' => $message->getText(),
        );

        $url = $this->baseApiUrl . '/sms/json?' . http_build_query($data);

        $contents = @file_get_contents($url);

        $response = json_decode($contents, 1);
        if (!is_array($response) || !array_key_exists('message-count', $response)) {
            throw new SendingFailedException('Invalid response (' . $contents . ') from: ' . $url);
        }

        if ((int) $response['message-count'] < 1) {
            throw new SendingFailedException('Failed sending (' . $contents . ') for: ' . $url);
        }
    }

    public function getBalance() {
        $url = $this->baseApiUrl . '/account/get-balance/' . $this->apiKey . '/' . $this->apiSecret;

        $contents = @file_get_contents($url);

        if ($contents === false) {
            throw new BalanceRetrievalFailedException('Cannot get credits data from: ' . $url);
        }

        $response = json_decode($contents, 1);
        if (!is_array($response) || !array_key_exists('value', $response)) {
            throw new BalanceRetrievalFailedException('Invalid response (' . $contents . ') from: ' . $url);
        }

        return $response['value'];
    }

    public function setBaseApiUrl($url) {
        $this->baseApiUrl = $url;
    }

}
