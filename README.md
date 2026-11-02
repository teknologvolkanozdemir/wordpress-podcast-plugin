# PodcastPress

PodcastPress, WordPress sitenizde podcast bölümleri yayınlamak için erişilebilirlik odaklı bir eklentidir.

## Kurulum

1. Bu depodaki `podcastpress.php` dosyasını `wp-content/plugins/podcastpress/` klasörüne yükleyin.
2. WordPress yönetim panelinde **Eklentiler > PodcastPress** eklentisini etkinleştirin.
3. Yönetim menüsündeki **PodcastPress > Add New Podcast Episode** bölümünden bir bölüm oluşturun. Başlık, açıklama ve ses dosyası URL'sine ek olarak, erişilebilirlik için tam bölüm transkriptini girin.
4. Podcast listesini istediğiniz sayfaya `[podcastpress]` kısa koduyla ekleyin.

Kısa kod, en yeni 10 bölümü gösterir. Sayı ve bölüm başlığı özelleştirilebilir:

```text
[podcastpress limit="5" heading="Son bölümler"]
```

`limit` değeri 1–50 arasında sınırlandırılır. Yayımlanmış ve ses dosyası URL'si olan bölümler listelenir.

## Erişilebilirlik

- Tarayıcının yerleşik ses oynatıcısı kullanılır; oynatma klavye ile kontrol edilebilir.
- Her oynatıcıya bölüm başlığını belirten erişilebilir bir ad verilir.
- Her bölümün transkripti yerleşik açılır/kapanır `details` öğesinde sunulur; klavye kullanıcıları transkripti açıp okuyabilir.
- Bölümler anlamlı başlık ve makale öğeleriyle gruplanır, tarihler makinece okunabilir biçimde işaretlenir ve ses dosyası için ayrıca indirme bağlantısı sağlanır.
- Bölüm düzenleyicisinde transkript alanı zorunludur. Her bölüm için konuşulan içeriğin tamamını transkript olarak ekleyin.
