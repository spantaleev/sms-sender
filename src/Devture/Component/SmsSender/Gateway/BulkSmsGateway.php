<?php
namespace Devture\Component\SmsSender\Gateway;

use Devture\Component\SmsSender\Message;
use Devture\Component\SmsSender\Exception\SendingFailedException;
use Devture\Component\SmsSender\Exception\BalanceRetrievalFailedException;

class BulkSmsGateway implements GatewayInterface {

    const ROUTING_GROUP_ECONOMY = 1;
    const ROUTING_GROUP_STANDARD = 2;
    const ROUTING_GROUP_PREMIUM = 3;

    private $username;
    private $password;
    private $routingGroup;

    const ENCODING_16BIT = '16bit';

    const RESPONSE_SEPARATOR = '|';
    const RESPONSE_STATUS_SUCCESS = 0;

    public function __construct($username, $password, $routingGroup = self::ROUTING_GROUP_ECONOMY) {
        $this->username = $username;
        $this->password = $password;
        $this->routingGroup = $routingGroup;
    }

    private function isUnicodeString($string) {
        return mb_strlen($string) !== strlen($string);
    }

    public function send(Message $message) {
        $data = array();
        $data['msisdn'] = $message->getPhoneNumber();
        $data['username'] = $this->username;
        $data['password'] = $this->password;
        $data['routing_group'] = $this->routingGroup;
        $messageText = $message->getText();
        if ($this->isUnicodeString($messageText)) {
            $data['dca'] = self::ENCODING_16BIT;
            $data['message'] = bin2hex(mb_convert_encoding($messageText, 'UTF-16', 'UTF-8'));
        } else {
            $data['message'] = $messageText;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://bulksms.vsms.net/eapi/submission/send_sms/2/2.0');
        curl_setopt ($ch, CURLOPT_PORT, 5567);
        curl_setopt ($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        $responseString = curl_exec($ch);
        $curlInfo = curl_getinfo($ch);

        if ($responseString === false) {
            throw new SendingFailedException('cURL error: ' . curl_error($ch));
        }

        curl_close($ch);

        if ($curlInfo['http_code'] !== 200) {
            throw new SendingFailedException('Received non-200 http response code (' . $curlInfo['http_code'] . ')');
        }

        if (strpos($responseString, self::RESPONSE_SEPARATOR) === false) {
            $length = strlen($responseString);
            throw new SendingFailedException('Response separator not found in response string (length: ' . $length . ')');
        }

        $parts = explode(self::RESPONSE_SEPARATOR, $responseString);
        $partsCount = count($parts);

        if ($partsCount === 2) {
            //batchId is missing when there are errors - let's set it to some benign value.
            $parts[] = 0;
            ++ $partsCount;
        }

        if ($partsCount !== 3) {
            throw new SendingFailedException('Unexpected response parts count - ' . count($parts));
        }

        list($statusCode, $statusText, $batchId) = $parts;
        $statusCode = (int)$statusCode;
        $batchId = (int)$batchId;

        if ($statusCode !== self::RESPONSE_STATUS_SUCCESS) {
            throw new SendingFailedException('Bad status code: ' . $statusCode . ' -- ' . $statusText);
        }

        if ($batchId <= 0) {
            throw new SendingFailedException('Bad batch id. It should always be positive!');
        }
    }

    public function getBalance() {
        $url = 'http://bulksms.vsms.net:5567/eapi/user/get_credits/1/1.1?username=' . $this->username . '&password=' . $this->password;

        $contents = @file_get_contents($url);

        if ($contents === false) {
            throw new BalanceRetrievalFailedException('Cannot get credits data.');
        }

        if (strpos($contents, '|') === false) {
            throw new BalanceRetrievalFailedException('Invalid response format.');
        }

        list($statusCode, $creditsCount) = explode('|', $contents);

        if ($statusCode !== '0') {
            throw new BalanceRetrievalFailedException('Invalud status code: ' . $statusCode);
        }

        return (double)$creditsCount;
    }

}
