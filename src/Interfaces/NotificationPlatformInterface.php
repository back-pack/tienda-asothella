<?php

namespace App\Interfaces;

use App\Interfaces\NotificationTypeInterface;

interface NotificationPlatformInterface {

    function send(NotificationTypeInterface $notificationType, array $data);
}