<?php

namespace App\Models;

use App\Interfaces\NotificationPlatformInterface;
use App\Interfaces\NotificationTypeInterface;

class Messenger {
    /*
    * Array with Email and/or phone platforms
    */
    private $notificationPlatform;

    public function __construct($platforms_availables) {
        foreach ($platforms_availables as $platform) {
            $this->setPlatformNotificator($platform);
        }
    }

    /**
    * Set PlatformNotificator that will send email or phone or both messages.
    * @param NotificationPlatformInterface Email, Phone or whatever platform.
    */
    public function setPlatformNotificator(NotificationPlatformInterface $notificationPlatform) {
        $this->notificationPlatform[] = $notificationPlatform;
    }

    /**
    * Sends the type of notification to specific receivers.
    * @param NotificationTypeInterface Registration, New product or whatever.
    * @param Array specific subscribers.
    */
    public function send(NotificationTypeInterface $notificationType, array $to) {
        foreach ($this->notificationPlatform as $platform) {
            $platform->send($notificationType, $to);
        }
    }
}