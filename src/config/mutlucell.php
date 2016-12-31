<?php

/**
 * Laravel 5 Mutlucell SMS
 * @license MIT License
 * @author Arda Kılıçdağı <arda@kilicdagi.com>
 * @link https://arda.pw
 *
 */

return [

    //Mutlucell Authentication
    'auth' => [
        'username' => '',
        'password' => '',
    ],

    //Default sender ID, for senders (AKA Originator)
    'default_sender' => '',

    //use Queue service?
    'queue' => false,

    // SMS Charset
    'charset' => 'default', // Values are: default, turkish, unicode

    //Append Unsubscribe text and link for receivers
    'append_unsubscribe_link' => false,

];
