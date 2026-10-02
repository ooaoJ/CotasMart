<?php

return [

    'subscription' => [
        'price' => (float) env('COTASMART_SUBSCRIPTION_PRICE'),
        'currency' => env('COTASMART_SUBSCRIPTION_CURRENCY', 'BRL'),
    ],

];