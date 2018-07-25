<?php 

namespace App\Manager;

class ClientManager {

    public function getPrices($tiles)
    {
        $prices = [];
        foreach ($tiles as $tile) {
            $prices[$tile->getId()] = $tile->getCost();
        }
        return $prices;
    }
}