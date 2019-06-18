<?php 

namespace App\NotificationPlatforms;

use App\Interfaces\NotificationPlatformInterfaces;

class EmailNotificationPlatform implements NotificationPlatformInterface {

    private $subject;
    private $to;
    private $from;
    private $body;

    public function __construct() {
        $this->mailer = new \Swift_Message();
    }

    public function send(NotificationTypeInterface $notificationType, array $data) {
        $this->subject = $notificationType->getSubject();
        $this->to = $data['to'];
        $this->from = $notificationType->getFrom();
        $this->body = $notificationType->getBody();

        $message = $this->mailer
            ->setSubject($this->subject)
            ->setFrom($this->from)
            ->setTo($this->to)
            ->setBody(
                $this->renderView(
                    $this->body,
                    $data['data']
                ), 'text/html'
            );

        $this->mailer->send($message);
    }
}