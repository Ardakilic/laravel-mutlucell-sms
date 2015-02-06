<?php

/**
 * Laravel 5 Mutlucell SMS
 * @license MIT License
 * @author Arda Kılıçdağı <arda@kilicdagi.com>
 * @link http://arda.pw
 *
*/

return array (
  
        
    //Paket Raporları
    'reports'  => array(
         
        '20'    =>  'Post edilen xml eksik veya hatalı.',
        '21'    =>  'Kullanılan originatöre sahip değilsiniz (Gönderen ID)',
        '22'    =>  'Kontörünüz yetersiz',
        '23'    =>  'Kullanıcı adı ya da parolanız hatalı.',
        '24'    =>  'Şu anda size ait başka bir işlem aktif',
        '25'    =>  'Lütfen birkaç dakika sonra işlemi yeniden deneyin',
        '30'    =>  'Hesap Aktivasyonu sağlanmamış.',
        
        '999'   =>  'Bilinmeyen hata',
    
    ),
    
    //Sms Gönderim Raporları
    'sms'       => array(
         
        '0'     =>  'Gönderilemedi',
        '1'     =>  'İşleniyor',
        '2'     =>  'Gönderildi',
        '3'     =>  'Başarılı',
        '4'     =>  'Beklemede',
        '5'     =>  'Zaman Aşımı',
        '6'     =>  'Başarısız',
        '7'     =>  'Reddedildi',
        '11'    =>  'Bilinmiyor',
        '12'    =>  'Hat Yok',
        '13'    =>  'Hatalı',
        
    ),
        
    //App-specific
    'app'   => array(
        
        '0'     => 'You must provide a message',
        '1'     => 'Recipent(s) must be an array',
        '2'     => 'Misformatted Recipent(s), all of them have to be an integer',
        '3'     => 'You must provide a receiver number',
        
        '100'   => 'SMS have been sent successfully!',     
        '101'   => 'No SMS sent',
        
    ),

    
);