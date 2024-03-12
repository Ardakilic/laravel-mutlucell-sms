<?php

/**
 * Laravel 11 Mutlucell SMS
 * @license MIT License
 * @author Arda Kılıçdağı <arda@kilicdagi.com>
 * @link https://arda.pw
 *
 */

return [

  //Paket Raporları
  'reports' => [

    '20' => 'Post edilen xml eksik veya hatalı.',
    '21' => 'Kullanılan originatöre sahip değilsiniz (Gönderen ID)',
    '22' => 'Kontörünüz yetersiz',
    '23' => 'Kullanıcı adı ya da parolanız hatalı.',
    '24' => 'Şu anda size ait başka bir işlem aktif',
    '25' => 'Lütfen birkaç dakika sonra işlemi yeniden deneyin',
    '30' => 'Hesap Aktivasyonu sağlanmamış.',

    '999' => 'Bilinmeyen hata',

  ],

  //Sms Gönderim Raporları
  'sms' => [

    '0' => 'Gönderilemedi',
    '1' => 'İşleniyor',
    '2' => 'Gönderildi',
    '3' => 'Başarılı',
    '4' => 'Beklemede',
    '5' => 'Zaman Aşımı',
    '6' => 'Başarısız',
    '7' => 'Reddedildi',
    '11' => 'Bilinmiyor',
    '12' => 'Hat Yok',
    '13' => 'Hatalı',

  ],

  //App-specific
  'app' => [

    '0' => 'Bir mesaj girmelisiniz',
    '1' => 'Alıcılar bir dizi içinde verilmelidir',
    '2' => 'Alıcıların biçimleri bozuk, tümünü tam sayı olarak girmelisiniz',
    '3' => 'Bir alıcı numarası girmelisiniz',

    '100' => 'SMS başarı ile gönderildi!',
    '101' => 'Hiç SMS gönderilemedi',

  ],

  //Exception Hata Mesajları
  'exceptions' => [

    '0' => 'Mutlucell auth parametresi ayarlarda tanımlanmamış!',
    '1' => 'Mutlucell Auth User and Password are not set correctly!',
    '2' => 'Mutlucell Default sender (originator) is not set!',

  ],

];
