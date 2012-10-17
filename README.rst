SMS sender
==========

A library for sending SMS messages through various service providers (gateways).

The currently supported gateways are:

 - `Nexmo <http://nexmo.com/>`_
 - `BulkSms <http://bulksms.com/>`_
 - `ProSms.eu <http://pro-sms.eu/>`_

Usage
-----

Example::

    <?php
    use Devture\Component\SmsSender\Gateway\NexmoGateway;
    use Devture\Component\SmsSender\Message;

    $message = new Message('sender-name', 'receiver-phone-number', 'message text');

    $gateway = new NexmoGateway('username', 'password');
    $gateway->send($message);

    echo 'Account Balance is: ', $gateway->getBalance();
