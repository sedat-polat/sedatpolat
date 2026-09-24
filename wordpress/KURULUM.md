# İşletmeBurada Panel — Kurulum

Bu eklenti İşletme Paneli tasarımını Elementor widget'ları olarak ekler. Widget'lar veriyi Voxel'deki `isletme` post type'ından okur, HTML yazman gerekmez.

> Önce bir **test/staging sitede** dene. Hostinger panelinde "Staging" ile sitenin kopyasını açabilirsin.

## 1. Eklentiyi yükle

1. `wordpress/dist/isletmeburada-panel.zip` dosyasını indir.
2. WordPress: **Eklentiler → Yeni ekle → Eklenti yükle** → zip'i seç → **Şimdi yükle**.
   Eski sürüm kuruluysa **"Mevcut olanı yüklenenle değiştir"** de.
3. **Etkinleştir.**

## 2. Durum kontrolü

Sol menüde yeni bir **İşletmeBurada** bölümü açılır. **Durum** sekmesinde her satırın yanında yeşil tik olmalı:

| Kontrol | Anlamı |
|---|---|
| Elementor / Elementor Pro | Widget'ların görünmesi için |
| Voxel | İşletme alanlarını okumak için |
| İşletme post type | Kaç işletme olduğu |
| Yorum tablosu / Yorum yanıtları | Son yorumlar ve "yanıt bekleyen yorum" sayısı |
| Sayaç tablosu | Görüntüleme ve tıklama grafiği |

Sarı ünlem varsa ekran görüntüsünü gönder.

## 3. Panel sayfalarını oluştur (tek tık)

**Durum** sekmesinde **"Panel sayfalarını oluştur"** düğmesine bas. İki sayfa oluşur:

- **İşletme Paneli** (Genel bakış)
- **İstatistikler**

İkisi de tasarımdaki düzenle, tüm widget'lar yerleşmiş hâlde gelir:
- Solda logo, işletme kartı, menü ve profil durumu
- Üstte başlık, düğme ve kullanıcı menüsü
- Ortada karşılama, sayı kartları, grafik, Şehrin Sahipleri, bekleyen işler, yorumlar ve profil doluluğu

Sonra **Elementor ile düzenle** bağlantısından her şeyi değiştirebilirsin.

**Görünürlük:** Sayfanın en dıştaki container'ını seç → **Gelişmiş → Display Conditions** → "Giriş yapmış kullanıcı". İstersen üyelik rolünü de ekle.

## 4. Mobil ve tablet

- **Masaüstü (1025px ve üstü):** Sol menü sabit durur, sayfa kaydırılsa da yerinde kalır.
- **Tablet ve mobil:** Sol menü gizlenir. Üst bardaki **☰** düğmesi onu soldan açılan bir çekmece olarak açar.
- Sayı kartları masaüstünde 4, tablette 2, mobilde 1 sütun olur. Grafik ile Şehrin Sahipleri tablette alt alta geçer.

Sayfayı elle kuruyorsan sol menü container'ına **Gelişmiş → CSS Sınıfları: `ibp-sidebar`** yaz. Çekmece bu sınıfla çalışır.

## 5. Ayarlar (İşletmeBurada → Ayarlar)

- **Düzenleme sayfası adresi:** Boş bırakırsan Voxel'in kendi düzenleme bağlantısı kullanılır.
- **Varsayılan dönem:** 7, 30 ya da 90 gün.
- **Sayaç:** Açık/kapalı. İşletme sahipleri ve yöneticiler sayılmaz.
- **Şehrin Sahipleri:** Puan 10 ya da 5 üzerinden; sıralamaya girmek için gereken en az yorum sayısı.

## 6. Bir şey yanlış görünürse

**İşletmeBurada → Tanılama** sekmesinde işletmeyi seç. Çıkan metni kopyalayıp gönder.

## Widget'lar

| Widget | Ne gösterir |
|---|---|
| Panel Logosu | Sol üstteki logo ve site adı |
| İşletme Kartı | Logo, işletme adı, şehir, kategori; birden fazla işletmede seçme kutusu |
| Panel Menüsü | Gruplu menü, ikonlar, rozetler, aktif sayfa vurgusu, "Yakında" öğeleri |
| Profil Durumu | Yayın durumu, doluluk çubuğu, "Profili önizle" |
| Üst Bar | Başlık, ana düğme, bildirim zili, kullanıcı menüsü, mobil menü düğmesi |
| Karşılama | Selamlama, tarih, bugünün çalışma saatleri, 7/30/90 gün seçici |
| İstatistik Kartı | Görüntüleme, arama, yol tarifi, web sitesi, puan, yorum sayısı; önceki döneme göre değişim |
| Grafik | Seçilen verinin günlük çubuk grafiği |
| Şehrin Sahipleri | Aynı şehir ve kategorideki işletmeler arasında sıra |
| Bekleyen İşler | Onay durumu, yanıt bekleyen yorum, eksik alan, fotoğraf önerisi |
| Son Yorumlar | Son Voxel yorumları |
| Profil Doluluğu | 13 alandan kaçının dolu olduğu, eksikler |
| Etkileşim Dağılımı | Görüntüleme ve iletişim tıklamaları |

### Menü bağlantılarındaki kısayollar

Menü ve düğme adreslerine şunları yazabilirsin, her işletme için otomatik doldurulur:

- `{edit}`: işletmeyi düzenleme sayfası
- `{view}`: işletmenin herkese açık sayfası
- `{id}`: işletme ID'si

### Henüz verisi olmayan bölümler

Rezervasyon, masa planı, menü, şikâyet, fırsat, duyuru, kupon, etkinlik ve iş ilanı için sitede henüz Voxel post type'ı yok. Bu öğeler menüde **"Yakında"** olarak soluk görünür ve tıklanmaz. İlgili post type açıldığında menü öğesinin **Yakında** anahtarını kapatıp adresini yazman yeterli.
