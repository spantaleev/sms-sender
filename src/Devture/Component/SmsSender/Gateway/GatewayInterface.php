<?php
namespace Devture\Component\SmsSender\Gateway;

use Devture\Component\SmsSender\Message;
use Devture\Component\SmsSender\Exception\SendingFailedException;
use Devture\Component\SmsSender\Exception\BalanceRetrievalFailedException;

interface GatewayInterface {

    /**
     * @param Message $message
     * @throws SendingFailedException
     */
    public function send(Message $message);

    /**
     * @throws BalanceRetrievalFailedException
     */
    public function getBalance();

}