<?php

namespace App\Interfaces;

interface NotificationTypeInterface {
    
    function getSubject(): string;
    function getBody(): string;
    function getFrom(): string;
}