<?php

namespace App\Interfaces;

interface NotificationTypeInterface {
    
    function getSubject(): string;
    function getBodyTemplate(): string;
    function getFrom(): string;
}