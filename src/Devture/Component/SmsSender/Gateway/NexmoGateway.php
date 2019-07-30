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
        $postData = array(
            'api_key' => $this->apiKey,
            'api_secret' => $this->apiSecret,
            'from' => $message->getSender(),
            'to' => $message->getPhoneNumber(),
            'text' => $message->getText(),
        );

        $postBody = http_build_query($postData);

        $context  = stream_context_create(array(
            'http' => array(
                'method'  => 'POST',
                'header'  => array(
                    'Content-Type: application/x-www-form-urlencoded',
                    sprintf('Content-Length: %d', strlen($postBody)),
                ),
                'content' => $postBody,
            ),
        ));

        $url = $this->baseApiUrl . '/sms/json';

        $contents = @file_get_contents($url, false, $context);

        $response = json_decode($contents, 1);
        if (!is_array($response) || !array_key_exists('message-count', $response)) {
            throw new SendingFailedException('Invalid response (' . $contents . ') from: ' . $url);
        }

        // `messages-count` merely contains the number of messages in the request,
        // not the messages that were sent or anything.
        if ((int) $response['message-count'] < 1) {
            throw new SendingFailedException('Failed sending (' . $contents . ') for: ' . $url);
        }

        if (!array_key_exists('messages', $response) || !is_array($response['messages'])) {
            throw new SendingFailedException('Failed to find messages field in response');
        }

        if (count($response['messages']) !== 1) {
            throw new SendingFailedException(sprintf(
                'Unexpected number of objects in messages field: %d',
                count($response['messages'])
            ));
        }

        $messageResult = $response['messages'][0];

        // Statuses are documented here: https://developer.nexmo.com/messaging/sms/guides/troubleshooting-sms
        if ((int) $messageResult['status'] !== 0) {
            throw new SendingFailedException(sprintf(
                'Message delivery returned a non-0 status: %s',
                json_encode($messageResult)
            ));
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
