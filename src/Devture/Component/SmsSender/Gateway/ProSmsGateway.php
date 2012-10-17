<?php
namespace Devture\Component\SmsSender\Gateway;

use Devture\Component\SmsSender\Message;
use Devture\Component\SmsSender\Exception\SendingFailedException;
use Devture\Component\SmsSender\Exception\BalanceRetrievalFailedException;

class ProSmsGateway implements GatewayInterface {

    const RESPONSE_STATUS_SUCCESS = 0;

    private $username;
    private $password;

    public function __construct($username, $password) {
        $this->username = $username;
        $this->password = $password;
    }

    public function send(Message $message) {
        $data = array(
            'uname' => $this->username,
            'pass' => $this->password,
            'from' => $message->getSender(),
            'phone' => $message->getPhoneNumber(),
            'message' => $message->getText(),
        );

        $url = 'http://pro-sms.eu/feed/send.php?' . http_build_query($data);

        $contents = @file_get_contents($url);

        if ($contents === false) {
            throw new SendingFailedException('Cannot make HTTP request for: ' . $url);
        }

        if ((int)$contents !== self::RESPONSE_STATUS_SUCCESS) {
            throw new SendingFailedException('Received bad result code (' . $contents . ') for: ' . $url);
        }
    }

    public function getBalance() {
        $url = 'http://pro-sms.eu/feed/balans.php?uname=' . $this->username . '&pass=' . $this->password;

        $contents = @file_get_contents($url);

        if ($contents === false) {
            throw new BalanceRetrievalFailedException('Cannot get credits data from: ' . $url);
        }

        if (!is_numeric($contents)) {
            throw new BalanceRetrievalFailedException('Invalid response (' . $contents . ') from: ' . $url);
        }

        return (double)$contents;
    }

}