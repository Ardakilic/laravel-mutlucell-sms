Laravel Mutlucell SMS
=========

[![Latest Stable Version](https://poser.pugx.org/ardakilic/mutlucell/v/stable.svg)](https://packagist.org/packages/ardakilic/mutlucell) [![Total Downloads](https://poser.pugx.org/ardakilic/mutlucell/downloads.svg)](https://packagist.org/packages/ardakilic/mutlucell) [![Latest Unstable Version](https://poser.pugx.org/ardakilic/mutlucell/v/unstable.svg)](https://packagist.org/packages/ardakilic/mutlucell) [![License](https://poser.pugx.org/ardakilic/mutlucell/license.svg)](https://packagist.org/packages/ardakilic/mutlucell)

Bu paket sayesinde Laravel kullanan projelerinizde [Mutlucell](https://www.mutlucell.com.tr/) altyapısını kullanarak tekli veya çoklu sms gönderebilir, bakiye ve originator ID sorgulayabilirsiniz. 

Paket Laravel 4 ve üzerindeki tüm sürümleri destekler.

Uyarı, hata ve bilgilendirme için Türkçe ve de İngilizce dillerinde uyarı ve bilgi mesajlarını barındırır.

Ekstra Bağımlılıklar (Laravel 6.x sürümü ve üstü için)
-----------
* SimpleXML PHP Eklentisi

Kurulum
-----------

* Öncelikle `composer.json` paketinize `composer require ardakilic/mutlucell` komutu ile de paketi ekleyin.
* Ardından eğer `composer.json` dosyasını elinizle güncellediyseniz kodları projenize dahil etmek için Composer paketlerinizi güncellemelisiniz. `composer install` komutu ile bunu yapabilirsiniz.
* _(Sadece Laravel 5.5'ten daha eski sürümler için)_ Şimdi de `config/app.php` dosyasını açın, `providers` dizisi içine en alta şunu girin:

  ```php
  Ardakilic\Mutlucell\MutlucellServiceProvider::class,
  ```
  
* _(Sadece Laravel 5.5'ten daha eski sürümler için)_ Yine aynı dosyadaki `aliases` dizisi altına şu değeri girin:

  ```php
  'Mutlucell' => Ardakilic\Mutlucell\Facades\Mutlucell::class,
  ```
  
* Şimdi de environment'ınıza konfigürasyon dosyasını paylaşmalısınız. Bunun için aşağıdaki komutu çalıştırın:

  ```shell
  php artisan vendor:publish
  ```
* `config/mutlucell.php` dosyası paylaşılacak. Burada Mutlucell için size atanan kullanıcı adı, parola ve sender_id (originator) değerlerini, ve de diğer ayarları doldurmalısınız. 

Ayrıca environment dosyanıza `MUTLUCELL_USERNAME`, `MUTLUCELL_PASSWORD` ve `MUTLUCELL_DEFAULT_SENDER` değerlerini de doldurarak config dosyanızı besleyebilirsiniz.

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

#### Mutlucell SMS ID

Gönderilen mesajın durumunu (karşı tarafa ulaşıp ulaşmadığı) takip edebilmeniz için SMS ID değerine ihtiyacınız var. 

Aşağıdaki şekilde, SMS ID edinip, daha sonra bununla sorgulama yapabilirsiniz. 

```php
$send = Mutlucell::send('05312345678', 'Merhaba');
if(Mutlucell::getStatus($send)) {
  $messageId = Mutlucell::getMessageId($send);
  echo 'SMS başarı ile gönderildi! SMS ID: '. $messageId;
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

#### Gönderilen mesajın durumunu sorgulamak için:

```bash
>>> \Mutlucell::getMessageReport('1234567890');
=> [
    [
      "number" => "905321234567",
      "result" => "3",
      "result_text" => "Başarılı",
    ],
   ]
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
$gonder = Mutlucell::setConfig(config('app.baskaConfig'))
  ->send('05312345678', 'Merhaba');
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
Mutlucell::send('05312223665', 'Geç gidecek mesaj', '2099-06-30 15:00'); // Saniye yok, dikkat!
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

ETH / ERC20 Token'ları: 0x3C2b0AC49257300DaB96dF8b49d254Bb696B3458
