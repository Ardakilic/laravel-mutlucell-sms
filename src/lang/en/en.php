<?php

/**
 * Laravel 10 Mutlucell SMS
 * @license MIT License
 * @author Arda Kılıçdağı <arda@kilicdagi.com>
 * @link https://arda.pw
 *
 */

return [

    //Bundle reports
    'reports' => [

        '20' => 'The xml that has been posted is misformatted.',
        '21' => 'You don\'t own the originator (sender ID)',
        '22' => 'Your balance is insufficent',
        '23' => 'Username or password is incorrect.',
        '24' => 'There is another action happening on your account',
        '25' => 'Please try again in a couple of minutes later',
        '30' => 'Account is not activated yet.',

        '999' => 'Unknwon error',

    ],

    //Sms Delivery Reports
    'sms' => [

        '0' => 'Could not be sent',
        '1' => 'Processing',
        '2' => 'Sent',
        '3' => 'Successful',
        '4' => 'In Queue',
        '5' => 'Timeout',
        '6' => 'Unsuccessful',
        '7' => 'Rejected',
        '11' => 'Unknown',
        '12' => 'No Network',
        '13' => 'Error',

    ],

    //App-specific
    'app' => [

        '0' => 'You must provide a message',
        '1' => 'Recipent(s) must be an array',
        '2' => 'Misformatted Recipent(s), all of them have to be an integer',
        '3' => 'You must provide a receiver number',

        '100' => 'SMS have been sent successfully!',
        '101' => 'No SMS sent',

    ],

    //Exception Messages
    'exceptions' => [

        '0' => 'Mutlucell auth parameter is not set in the config!',
        '1' => 'Mutlucell Auth User and Password are not set correctly!',
        '2' => 'Mutlucell Default sender (originator) is not set!',

    ],

];
