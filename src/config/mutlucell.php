<?php

/**
 * Laravel 9 Mutlucell SMS
 * @license MIT License
 * @author Arda Kılıçdağı <arda@kilicdagi.com>
 * @link https://arda.pw
 *
 */

return [

    //Mutlucell Authentication
    'auth' => [
        'username' => env('MUTLUCELL_USERNAME', ''),
        'password' => env('MUTLUCELL_PASSWORD',''),
    ],

    //Default sender ID, for senders (AKA Originator)
    'default_sender' => env('MUTLUCELL_DEFAULT_SENDER', ''),

    //use Queue service?
    'queue' => false,

    // SMS Charset
    'charset' => 'default', // Values are: default, turkish, unicode

    //Append Unsubscribe text and link for receivers
    'append_unsubscribe_link' => false,

];
