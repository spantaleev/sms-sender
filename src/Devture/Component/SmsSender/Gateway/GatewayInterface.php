<?php
namespace Devture\Component\SmsSender\Gateway;

use Devture\Component\SmsSender\Message;

interface GatewayInterface {

    /**
     * @param Message $message
     * @throws \Devture\Component\SmsSender\Exception\SendingThrottledException
     * @throws \Devture\Component\SmsSender\Exception\SendingFailedException
     */
    public function send(Message $message);

    /**
     * @throws \Devture\Component\SmsSender\Exception\BalanceRetrievalFailedException
     */
    public function getBalance();

    /**
     * @param string $url
     */
    public function setBaseApiUrl($url);
}
