<?php

namespace App\Listeners;

use App\Events\ProductSavedEvent;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

class ProductSavedListener
{
    /**
     * Handle the event.
     */
    public function handle(ProductSavedEvent $event): void
    {
        $eshop1Url = env('ESHOP1_URL');

        if (empty($eshop1Url)) {
            return;
        }

        $client = new Client();

        $response = $client->post(env('ESHOP1_URL'), [
            RequestOptions::JSON => $event->product
        ]);
    }
}
