Laravel Mutlucell SMS
=========

[![Latest Stable Version](https://poser.pugx.org/ardakilic/mutlucell/v/stable.svg)](https://packagist.org/packages/ardakilic/mutlucell) [![Total Downloads](https://poser.pugx.org/ardakilic/mutlucell/downloads.svg)](https://packagist.org/packages/ardakilic/mutlucell) [![Latest Unstable Version](https://poser.pugx.org/ardakilic/mutlucell/v/unstable.svg)](https://packagist.org/packages/ardakilic/mutlucell) [![License](https://poser.pugx.org/ardakilic/mutlucell/license.svg)](https://packagist.org/packages/ardakilic/mutlucell)

Bu paket sayesinde Laravel 4.x ve 5.x kullanan projelerinizde [Mutlucell](http://www.mutlucell.com.tr/) altyapısını kullanarak tekli veya çoklu sms gönderebilir, bakiye ve originator ID sorgulayabilirsiniz.

Uyarı, hata ve bilgilendirme için Türkçe ve de İngilizce dillerinde uyarı ve bilgi mesajlarını barındırır.


Kurulum
-----------

### Bu branch ve sürüm (l4, 1.x) Laravel 4 içindir, eğer Laravel 5'te kullanacaksanız sunumlar sayfasından veya master branchından sürüm 2'yi incelemelisiniz.

* Öncelikle Laravel 4'te kullanacaksanız `composer.json` dosyanızdaki `require` kısmına aşağıdaki değeri ekleyin:

    ```json
    "ardakilic/mutlucell": "~1"
    ```

    Alternatif olarak `composer require ardakilic/mutlucell:~1` komutu ile de paketi ekleyebilirsiniz.
* Ardından composer paketlerinizi güncellemelisiniz. `composer update` komutu ile bunu yapabilirsiniz.
* Şimdi de `app/config/app.php` dosyasını açın, `providers` içine en alta şunu girin:

    ```php
    'Ardakilic\Mutlucell\MutlucellServiceProvider',
    ```
* Şimdi yine aynı dosyada `aliases` altına şu değeri girin:

    ```php
    'Mutlucell' => 'Ardakilic\Mutlucell\Facades\Mutlucell',
    ```
* Şimdi de environment'ınıza konfigürasyon dosyasını paylaşmalısınız. Bunun için aşağıdaki komutu çalıştırın:

    ```shell
    php artisan config:publish ardakilic/mutlucell
    ```
* `app/config/packages/ardakilic/mutlucell` klasörü altına `config.php` dosyası paylaşılacak. Burada Mutlucell için size atanan kullanıcı adı, parola ve sender_id (originator) değerlerini girmelisiniz.

Kullanım
-------------

####Birine o anda tekil SMS göndermek için:

```php
$send = Mutlucell::send('05312345678', 'Merhaba');
echo Mutlucell::parseOutput($send);
```

####SMS gönderildi mi ?

```php
$send = Mutlucell::send('05312345678', 'Merhaba');
if(Mutlucell::getStatus($send)) {
    echo 'SMS başarı ile gönderildi!';
} else {
    echo 'SMS gönderilemedi';
}
```

####Birden fazla kişiye aynı anda aynı SMS'i göndermek için:

```php
$kisiler = array('00905312345678', '+905351114478', '05369998874', '5315558896');
$send = Mutlucell::sendBulk($kisiler, 'Merhaba');
echo Mutlucell::parseOutput($send);
```

Veya 

```php
$send = Mutlucell::sendBulk('00905312345678, +905351114478, 05369998874, 5315558896', 'Merhaba');
echo Mutlucell::parseOutput($send);
```

####Birden fazla kişiye aynı anda farklı SMS'ler göndermek için:

```php
$kisiMesajlar = array(
    array('05315558964', 'Merhaba1'),
    array('+905415589632', 'Merhaba2'),
    array('00905369998874', 'Merhaba3')
);
$send = Mutlucell::sendMulti($kisiMesajlar);
echo Mutlucell::parseOutput($send);
```

Veya

```php
$kisiMesajlar = array(
    array('05315558964' => 'Merhaba1'),
    array('+905415589632' => 'Merhaba2'),
    array('00905369998874' => 'Merhaba3')
);
$send = Mutlucell::sendMulti2($kisiMesajlar);
echo Mutlucell::parseOutput($send);
```

####Bir veya birden Fazla Kullanıcıyı Kara Listeye Eklemek İçin

```php
$sil = Mutlucell::addBlacklist('00905312345678');
var_dump(Mutlucell::parseOutput($sil));
```

Veya

```php
$sil = Mutlucell::addBlacklist('00905312345678, +905351114478, 05369998874, 5315558896');
var_dump(Mutlucell::parseOutput($sil));
```

Veya

```php
$kisiler = ['00905312345678', '+905351114478', '05369998874', '5315558896'];
$sil = Mutlucell::addBlacklist($kisiler);
var_dump(Mutlucell::parseOutput($sil));
```


####Bir veya Birden Fazla Kullanıcıyı Kara Listeden Çıkartmak İçin

```php
$sil = Mutlucell::deleteBlackList('00905312345678');
var_dump(Mutlucell::parseOutput($sil));
```

Veya


```php
$sil = Mutlucell::deleteBlackList('00905312345678, +905351114478, 05369998874, 5315558896');
var_dump(Mutlucell::parseOutput($sil));
```

Veya

```php
$kisiler = ['00905312345678', '+905351114478', '05369998874', '5315558896'];
$sil = Mutlucell::deleteBlackList($kisiler);
var_dump(Mutlucell::parseOutput($sil));
```

Eğer tüm kullanıcıları kara listeden çıkartmak istiyorsanız parametre boş olmalı:

```php
$sil = Mutlucell::deleteBlackList();
var_dump(Mutlucell::parseOutput($sil));
```

####Farklı bir ayar dosyası ile SMS göndermek için
```php
$gonder = Mutlucell::setConfig(Config::get('app.baskaConfig'))->send('05312345678', 'Merhaba');
```

Hatta;

```php
$sms = Mutlucell::setConfig([
    'auth' => [
        'username' => 'baskauser',
        'password' => 'baskaparola',
    ],
    'default_sender' => 'baskaoriginator',
]);

$sms->send('05312345678', 'Merhaba');
```

####Kalan Kontör Sorgulaması için:

```php
echo Mutlucell::checkBalance();
```

####Originatörleri listelemek için:

```php
echo Mutlucell::listOriginators();
```

#### Gelecek bir tarihe SMS yollamak için:

```php
echo Mutlucell::send('05312223665', 'Geç gidecek mesaj', '2099-06-30 15:00'); //saniye yok, dikkat!
```

#### Farklı bir Originatör (Sender ID) kullanarak SMS yollamak için:

```php
echo Mutlucell::send('05312223665', 'merhaba', '', 'diğerOriginator');
```

Yapılacaklar
----
* Guzzle entegrasyonu
* ?

Notlar
----
* 29 Aralık 2016'dan önce kurulum gerçekleştirdiyseniz config dosyanıza 2 değer eklemeniz lazım:

```php
// SMS Charset
'charset' => 'default', // Values are: default, turkish, unicode

//Append Unsubscribe text and link for receivers
'append_unsubscribe_link' => false,
```

Bu 2 değer SMS gönderim karakter dilini ve de sms'lerin sonuna gelecek olan "sms aboneliğinden çık" linkini barındırmakta.

* 22 Temmuz 2014'den önce kurulum gerçekleştirdiyseniz config dosyasını ortama yeniden paylaşmalısınız

Lisans
----

Bu yazılım paketi MIT lisansı ile lisanslanmıştır.
