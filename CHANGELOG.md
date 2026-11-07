# Changelog

## Unreleased (breaking)

PHP 8 isimli argümanlara geçiş ve eski yüklerden temizlik. `README.md`
içindeki "Yükseltme" bölümü geçiş örneklerini içerir.

### Değişen API

- Parametre adları: `$senderID` -> `sender:`, `$receiversMessage` -> `messages:`.
- `sendMulti2` kaldırıldı, `sendMulti` devraldı (sadece `numara => mesaj` eşlemesi;
  liste-içi-liste formatı kalktı).
- `deleteBlackList` -> `deleteBlacklist`.
- `sendBulk(recipients:)` ve kara liste metotları artık sadece dizi kabul eder;
  string girişi `TypeError` verir.
- `sendBulk(recipients: [])` boş diziyle istek atmadan `102` döner.
- Numarasız `deleteBlacklist()` tüm listeyi sildiği için artık `confirm: true`
  ister, aksi halde `InvalidArgumentException` atar.
- `getMessageReport` hata biçimi: `$report[0]` yerine `$report['error']`.
- Config hataları artık `InvalidArgumentException` (`\Exception` yerine).
- Tüm public metotlara dönüş tipi eklendi.
- Yeni sabitler: `Mutlucell::STATUS_SUCCESS` / `Mutlucell::STATUS_FAILED`.

### Düzeltilenler

- `sendMulti` boş olmayan `sender:` değerini artık dikkate alıyor.
- `setConfig` eksik anahtarda uyarı verip sonra hata atıyordu; artık önce
  doğrulayıp sonra atama yapıyor.
- Yayınlanmamış config ile kullanımda çöken akış için `mergeConfigFrom` eklendi.

### Altyapı

- `Queue` FQCN importu, ölü `$defer`/`provides()` kaldırıldı, `switch` ->
  `match`/lookup sadeleştirmeleri, paylaşılan `SMS_ID_PATTERN` ve
  `postBlacklist()` yardımcısı.
- Testler Docker'da çalışır (`make test`), kapsam %100 satır.
