turbosms.ua HTTP API library 
======
What is this?
-------------

This library implements HTTP api for turbosms.ua service.

🛠Installation
------------
Install Composer (https://getcomposer.org/download/)

As Library via Composer:

    composer require alxjzx100/turbosmsua

🔌 Usage
------------
````php
<?php
require 'vendor/autoload.php';

use alxjzx100\TurboSmsUA\httpApi;

$httpApi = new httpApi('YOUR_API_KEY');
$httpApi->send('380661234567', 'SMS text');
````
###Advanced Example
````php
<?php
require 'vendor/autoload.php';

use alxjzx100\TurboSmsUA\httpApi;
$numbers = [
    '380661234567',
    '380671234567'
];
$smsApi = new httpApi('YOUR_API_KEY');
try {
    $smsApi
        ->setConnectionType('curl')
        ->setMode('hybrid')
        ->setStartTime( new DateTime('22.02.2022 18:00') )
        ->setTTL('48000')
        ->setAction('Some Action')
        ->setImage('https://image.url')
        ->send($numbers,'TEXT TO SEND', 'SMS SENDER NAME', 'VIBER SENDER NAME');

}catch (Exception $e){
    echo $e->getMessage();
}
````

Methods
------------
***
* send
* setConnectionType
* setMode
* setStartTime
* setIsFlash
* setTTL
* setImage
* setCaption
* setAction
* setFileId
* countClicks
* isTransactional
* getBalance
* getFileDetails
* uploadFile
***

### More info
https://turbosms.ua/api.html