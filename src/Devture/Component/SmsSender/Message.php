<?php
namespace Devture\Component\SmsSender;

class Message {

    private $sender;
    private $phoneNumber;
    private $text;

    public function __construct($sender, $phoneNumber, $text) {
        $this->sender = $sender;
        $this->phoneNumber = $phoneNumber;
        $this->text = $text;
    }

    public function getSender() {
        return $this->sender;
    }

    public function getPhoneNumber() {
        return $this->phoneNumber;
    }

    public function getText() {
        return $this->text;
    }

}