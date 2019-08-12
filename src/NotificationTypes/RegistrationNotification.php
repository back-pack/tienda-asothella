<?php

namespace App\NotificationTypes;
use App\Interfaces\NotificationTypeInterface;

class RegistrationNotification implements NotificationTypeInterface {

    private $from = 'info@asothella.com.ar';
    private $body_template = 'emails/registration.html.twig';
    private $subject = 'Bienvenido a Asothella';

    public function getFrom() : string {
        return $this->from;
    }

    public function getBodyTemplate() : string {
        return $this->body_template;
    }

    public function getSubject() : string {
        return $this->subject;
    }
}