Laravel 5 ve 4 için Mutlucell SMS
=========

[![Latest Stable Version](https://poser.pugx.org/ardakilic/mutlucell/v/stable.svg)](https://packagist.org/packages/ardakilic/mutlucell) [![Total Downloads](https://poser.pugx.org/ardakilic/mutlucell/downloads.svg)](https://packagist.org/packages/ardakilic/mutlucell) [![Latest Unstable Version](https://poser.pugx.org/ardakilic/mutlucell/v/unstable.svg)](https://packagist.org/packages/ardakilic/mutlucell) [![License](https://poser.pugx.org/ardakilic/mutlucell/license.svg)](https://packagist.org/packages/ardakilic/mutlucell)

Bu paket sayesinde Laravel 5.x veya 4.x kullanan projelerinizde [Mutlucell](http://www.mutlucell.com.tr/) altyapısını kullanarak tekli veya çoklu sms gönderebilir, bakiye ve originator ID sorgulayabilirsiniz.

Uyarı, hata ve bilgilendirme için Türkçe ve de İngilizce dillerinde uyarı ve bilgi mesajlarını barındırır.


Kurulum (Laravel 5.x için)
-----------

* Öncelikle `composer.json` dosyanızdaki `require` kısmına aşağıdaki değeri ekleyin:

    ```json
    "ardakilic/mutlucell": "2.0.0"
    ```

    Alternatif olarak `composer require ardakilic/mutlucell:2.0.0` komutu ile de paketi ekleyebilirsiniz.
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
    php artisan vendor:publish
    ```
* `app/config/mutlucell.php` dosyası paylaşılacak. Burada Mutlucell için size atanan kullanıcı adı, parola ve sender_id (originator) değerlerini girmelisiniz.

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
22 Temmuz 2014'den önce kurulum gerçekleştirdiyseniz config dosyasını ortama yeniden paylaşmalısınız

Lisans
----

Mu yazılım paketi MIT lisansı ile lisanslanmıştır.
