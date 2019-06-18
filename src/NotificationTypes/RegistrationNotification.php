<?php

namespace App\NotificationTypes;
use App\Interfaces\NotificationTypeInterface;

class RegistrationNotification implements NotificationTypeInterface {

    private $from = 'info@asothella.com.ar';
    private $body = 'templates/emails/registration.html.twig';
    private $subject = 'Bienvenido a Asothella';

    public function getFrom() {
        return $this->from;
    }

    public function getBody() {
        return $this->body;
    }

    public function getSubject() {
        return $this->subject;
    }
}