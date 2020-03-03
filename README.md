Laravel 7, 6, 5 ve 4 için Mutlucell SMS
=========

[![Latest Stable Version](https://poser.pugx.org/ardakilic/mutlucell/v/stable.svg)](https://packagist.org/packages/ardakilic/mutlucell) [![Total Downloads](https://poser.pugx.org/ardakilic/mutlucell/downloads.svg)](https://packagist.org/packages/ardakilic/mutlucell) [![Latest Unstable Version](https://poser.pugx.org/ardakilic/mutlucell/v/unstable.svg)](https://packagist.org/packages/ardakilic/mutlucell) [![License](https://poser.pugx.org/ardakilic/mutlucell/license.svg)](https://packagist.org/packages/ardakilic/mutlucell)

Bu paket sayesinde Laravel 7.x, 6.x, 5.x veya 4.x kullanan projelerinizde [Mutlucell](https://www.mutlucell.com.tr/) altyapısını kullanarak tekli veya çoklu sms gönderebilir, bakiye ve originator ID sorgulayabilirsiniz. 

Bu branch Laravel 7 içindir.  Eğer bu paketi Laravel 6.x üzerinde kullanmak istiyorsanız *3.x sürümünü* `"ardakilic/mutlucell": "~3"` etiketi ile, Laravel 5.x üzerinde kullanmak istiyorsanız *2.x sürümünü*  `"ardakilic/mutlucell": "~2"` etiketi ile, Laravel 4 üzerinde kullanmak istiyorsanız *1.x sürümünü*, `"ardakilic/mutlucell": "~1"` etiketi ile kullanmalısınız.

Uyarı, hata ve bilgilendirme için Türkçe ve de İngilizce dillerinde uyarı ve bilgi mesajlarını barındırır.

Ekstra Bağımlılıklar (Laravel 6.x sürümü ve üstü için)
-----------
* SimpleXML PHP Eklentisi

Kurulum (Laravel 6.x için)
-----------

* Öncelikle `composer.json` dosyanızdaki `require` kısmına aşağıdaki değeri ekleyin:

    ```json
    "ardakilic/mutlucell": "~4"
    ```

    Alternatif olarak `composer require ardakilic/mutlucell:~4` komutu ile de paketi ekleyebilirsiniz.
* Ardından eğer `composer.json` dosyasını elinizle güncellediyseniz kodları projenize dahil etmek için Composer paketlerinizi güncellemelisiniz. `composer update` komutu ile bunu yapabilirsiniz.
* Şimdi de `config/app.php` dosyasını açın, `providers` dizisi içine en alta şunu girin:

    ```php
    Ardakilic\Mutlucell\MutlucellServiceProvider::class,
    ```
    _(Laravel 5.5 ve sonrası için gerekli değildir)_
    
* Şimdi yine aynı dosyada `aliases` dizisi altına şu değeri girin:

    ```php
    'Mutlucell' => Ardakilic\Mutlucell\Facades\Mutlucell::class,
    ```
    _(Laravel 5.5 ve sonrası için gerekli değildir)_
    
* Şimdi de environment'ınıza konfigürasyon dosyasını paylaşmalısınız. Bunun için aşağıdaki komutu çalıştırın:

    ```shell
    php artisan vendor:publish
    ```
* `config/mutlucell.php` dosyası paylaşılacak. Burada Mutlucell için size atanan kullanıcı adı, parola ve sender_id (originator) değerlerini, ve de diğer ayarları doldurmalısınız. 

Ayrıca environment dosyanıza `MUTLUCELL_USERNAME`, `MUTLUCELL_PASSWORD` ve `MUTLUCELL_DEFAULT_SENDER` değerlerini de doldurarak config dosyanızı besleyebilirsiniz.

**Laravel 6.x sürümünde kullanım bilgisi için [ilgili branch'ın README.md dosyasına](https://github.com/Ardakilic/laravel-mutlucell-sms/tree/l6) bakmalısınız.**

**Laravel 5.x sürümünde kullanım bilgisi için [ilgili branch'ın README.md dosyasına](https://github.com/Ardakilic/laravel-mutlucell-sms/tree/l5) bakmalısınız.**

**Laravel 4.x sürümünde kullanım bilgisi için [ilgili branch'ın README.md dosyasına](https://github.com/Ardakilic/laravel-mutlucell-sms/tree/l4) bakmalısınız.**

Kullanım
-------------

#### Birine o anda tekil SMS göndermek için:

```php
$send = Mutlucell::send('05312345678', 'Merhaba');
var_dump(Mutlucell::parseOutput($send));
```

#### SMS gönderildi mi ?

```php
$send = Mutlucell::send('05312345678', 'Merhaba');
if(Mutlucell::getStatus($send)) {
    echo 'SMS başarı ile gönderildi!';
} else {
    echo 'SMS gönderilemedi';
}
```

#### Birden fazla kişiye aynı anda aynı SMS'i göndermek için:

```php
$kisiler = ['00905312345678', '+905351114478', '05369998874', '5315558896'];
$send = Mutlucell::sendBulk($kisiler, 'Merhaba');
var_dump(Mutlucell::parseOutput($send));
```

Veya 

```php
$send = Mutlucell::sendBulk('00905312345678, +905351114478, 05369998874, 5315558896', 'Merhaba');
Mutlucell::parseOutput($send);
```

#### Birden fazla kişiye aynı anda farklı SMS'ler göndermek için:

```php
$kisiMesajlar = [
    ['05315558964', 'Merhaba1'],
    ['+905415589632', 'Merhaba2'],
    ['00905369998874', 'Merhaba3']
];
$send = Mutlucell::sendMulti($kisiMesajlar);
var_dump(Mutlucell::parseOutput($send));
```

Veya

```php
$kisiMesajlar = [
    ['05315558964' => 'Merhaba1'],
    ['+905415589632' => 'Merhaba2'],
    ['00905369998874' => 'Merhaba3']
];
$send = Mutlucell::sendMulti2($kisiMesajlar);
var_dump(Mutlucell::parseOutput($send));
```

#### Bir veya birden Fazla Kullanıcıyı Kara Listeye Eklemek İçin

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


#### Bir veya Birden Fazla Kullanıcıyı Kara Listeden Çıkartmak İçin

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

#### Farklı bir ayar dosyası ile SMS göndermek için

```php
$gonder = Mutlucell::setConfig(config('app.baskaConfig'))->send('05312345678', 'Merhaba');
```

Hatta

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


#### Kalan Kontör Sorgulaması için:

```php
var_dump(Mutlucell::checkBalance());
```

#### Originatörleri listelemek için:

```php
var_dump(Mutlucell::listOriginators());
```

#### Gelecek bir tarihe SMS yollamak için:

```php
Mutlucell::send('05312223665', 'Geç gidecek mesaj', '2099-06-30 15:00'); //saniye yok, dikkat!
```

#### Farklı bir Originatör (Sender ID) kullanarak SMS yollamak için:

```php
Mutlucell::send('05312223665', 'merhaba', '', 'diğerOriginator');
```

Yapılacaklar
----

* Kara Listeye giren kullanıcı listesini alma metodu
* ?

Lisans
----

Bu yazılım paketi MIT lisansı ile lisanslanmıştır.

Destek
--------

Bu proje eğer işinize yaradıysa kripto paralarla bana bağışta bulunabilirsiniz. Aşağıda cüzdan adreslerimi bulabilirsiniz:

BTC: 1QFHeSrhWWVhmneDBkArKvpmPohRjpf7p6

ETH / ERC20 Tokens: 0x3C2b0AC49257300DaB96dF8b49d254Bb696B3458

NEO / Nep5 Tokens: AYbHEah5Y4J6BV8Y9wkWJY7cCyHQameaHc