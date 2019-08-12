<?php 

namespace App\NotificationPlatforms;

use App\Interfaces\NotificationPlatformInterface;
use App\Interfaces\NotificationTypeInterface;

class EmailNotificationPlatform implements NotificationPlatformInterface {

    private $subject;
    private $to;
    private $from;
    private $body;
    private $mailer;
    private $loader;
    private $message;
    private $transport;

    public function __construct() {
        $this->loader = new \Twig_Loader_Filesystem('../templates');
        $this->twig = new \Twig_Environment($this->loader);
        $this->message = new \Swift_Message();
        $this->transport = (new \Swift_SmtpTransport('smtp.gmail.com', 25, 'tls'))
        ->setUsername('xxxx')
        ->setPassword('xxxx');
        $this->mailer = new \Swift_Mailer($this->transport);
    }

    public function send(NotificationTypeInterface $notificationType, array $data) {
        $this->subject = $notificationType->getSubject();
        $this->to = $data['to'];
        $this->from = $notificationType->getFrom();
        $this->body = $notificationType->getBodyTemplate();

        $message = $this->message
            ->setSubject($this->subject)
            ->setFrom($this->from)
            ->setTo($this->to)
            ->setBody(
                $this->twig->render(
                    $this->body,
                    $data['data']
                ), 'text/html'
            );

        $this->mailer->send($message);
    }
}