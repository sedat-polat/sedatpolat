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

**Durum** sekmesinde **"Panel sayfalarını oluştur"** düğmesine bas. Üç sayfa oluşur:

- **İşletme Paneli** (Genel bakış)
- **İstatistikler**
- **Bağlı İşletmeler**

Sayfalar zaten varsa düğme **"varsayılan düzene sıfırla"** olur. Aynı sayfaları günceller, kopya açmaz; eksik sayfayı ekler. Ancak o sayfalarda Elementor'la yaptığın değişiklikler silinir.

Hepsi tasarımdaki düzenle, tüm widget'lar yerleşmiş hâlde gelir:
- Solda logo, işletme kartı, menü ve profil durumu
- Üstte başlık, düğme ve kullanıcı menüsü
- Ortada karşılama, sayı kartları, grafik, Şehrin Sahipleri, bekleyen işler, yorumlar ve profil doluluğu

Sonra **Elementor ile düzenle** bağlantısından her şeyi değiştirebilirsin.

**Görünürlük:** Sayfanın en dıştaki container'ını seç → **Gelişmiş → Display Conditions** → "Giriş yapmış kullanıcı". İstersen üyelik rolünü de ekle.

## 4. Mobil ve tablet

- **Üst bar** sayfa kaydırılınca ekranın üstünde sabit kalır. İstemezsen Üst Bar widget'ında **"Kaydırınca üstte sabit kalsın: Hayır"** seç.
- **Masaüstü (1025px ve üstü):** Sol menü sabit durur, sayfa kaydırılsa da yerinde kalır. Üst bardaki menü düğmesi onu yalnız ikonlara daraltır; üzerine gelince açılır. Seçim tarayıcıda hatırlanır.
- **Tablet ve mobil:** Sol menü gizlenir. Üst bardaki **☰** düğmesi onu soldan açılan bir çekmece olarak açar.
- Sayı kartları masaüstünde 4, tablette 2, mobilde 1 sütun olur. Grafik ile Şehrin Sahipleri tablette alt alta geçer.

Sayfayı elle kuruyorsan sol menü container'ına **Gelişmiş → CSS Sınıfları: `ibp-sidebar`** yaz. Çekmece bu sınıfla çalışır.

## 5. Kategoriye göre menü (İşletmeBurada → Sektörler)

Menüdeki operasyon grubu işletmenin kategorisine göre değişir:

| Sektör | Menü grubu | Görünenler |
|---|---|---|
| Restoran, Bar | Operasyon | Rezervasyonlar, Masa planı · Menü ve fiyatlar |
| Gece Kulübü | Operasyon | Rezervasyonlar, Masa planı · Geceler ve biletler |
| Sağlık | Klinik | Randevular, Hekimler · Hizmetler ve fiyatlar |
| Güzellik & Kişisel Bakım | Salon | Randevular, Uzmanlar, Paket müşterileri |
| Evcil Hayvan | Klinik | Randevular, Personel |
| Fitness | Kulüp | Üyeler, Ders programı, Randevular, Eğitmenler |
| Eğitim & Kurslar | Akademi | Öğrenciler, Ders programı, Randevular, Öğretmenler · Kurslar ve ücretler |
| Konaklama | Otel | Oda takvimi · Oda tipleri ve fiyatlar |
| Alışveriş & Perakende, Online Mağaza | Mağaza | Siparişler, Ürünler ve stok · Ürün vitrini |
| Otomotiv | Servis | Randevular, Ustalar ve liftler, Servis talepleri |
| Profesyonel & Danışmanlık | Ofis | Randevular, Danışmanlar, Danışmanlık talepleri |
| Ev & Yaşam Hizmetleri | Saha | İş talepleri, Randevular, Ekipler |
| Etkinlik & Organizasyon | Organizasyon | Organizasyon talepleri · Paketler ve fiyatlar |
| Sinema & Eğlence | Gişe | Seanslar ve biletler · Bilet fiyatları |
| Turizm & Gezi | Tur operasyonu | Özel tur talepleri · Turlar ve fiyatlar |

Kategoriler adlarına göre **otomatik** eşleşir; örneğin "Restaurant" → Restoran, "Diş Kliniği" → Sağlık, "Pilates" → Fitness. Alt kategoriler üst kategorinin sektörünü alır. Yanlış eşleşeni **Sektörler** sekmesinden düzeltebilirsin.

Rezervasyon, randevu gibi modüllerin sitede henüz sayfası yok; menüde **"Yakında"** görünürler. Bir modülün sayfası hazır olunca **Sektörler → Modül sayfaları** bölümüne adresini yaz, "Yakında" kalkar.

Önceden oluşturduğun sayfalardaki eski sabit menü de otomatik olarak sektör menüsüne çevrilir.

## 6. Marka, şube, bayi ve franchise

Bir işletme bir markanın **şubesi**, **bayisi** ya da **franchise'ı** olabilir. Örneğin Arçelik → Arçelik Kadıköy Bayi.

**Bağlanma:**
1. Alt işletme panelinde **Bağlı İşletmeler** sayfasını açar, markayı arar, türü seçer ve **İstek gönder**'e basar.
2. Markanın panelinde istek hem **Bekleyen işler**'de hem de menüdeki **Bağlı işletmeler** rozetinde görünür. Marka **Onayla** ya da **Reddet** der.
3. Alt işletme isteğini geri çekebilir ya da sonradan markadan ayrılabilir. Marka da bağlantıyı kaldırabilir.

**Marka ne görür:**
- İşletme seçme kutusunda marka adıyla bir grup çıkar. En üstte **"Tüm bağlı işletmeler"** seçeneği vardır. Bu görünümde:
  - Görüntüleme, arama, yol tarifi toplamları ve ortalama puan
  - Şehrin Sahipleri kartı yerine **bağlı işletmelerin sıralaması**
  - Ortalama profil doluluğu ve en eksik işletmeler
  - Tüm işletmelerin son yorumları
- Altında her işletmeye tek tek geçilebilir.

**Yetki:**
- **Şube:** Marka paneli görür ve profili düzenleyebilir.
- **Bayi / Franchise:** Marka istatistik, puan ve yorumları görür. Profili yalnız sahibi düzenler.

**Kurallar:**
- Tek seviye: bağlı işletmesi olan bir marka başka bir markaya bağlanamaz, bir alt işletmeye de bağlanılamaz.
- Bir işletme aynı anda tek bir markaya bağlı olabilir.

**Site yöneticisi** tüm bağlantıları **İşletmeBurada → Marka ağı** sekmesinden görür, onaylayabilir ya da kaldırabilir.

## 7. Ayarlar (İşletmeBurada → Ayarlar)

- **Düzenleme sayfası adresi:** Boş bırakırsan Voxel'in kendi düzenleme bağlantısı kullanılır.
- **Varsayılan dönem:** 7, 30 ya da 90 gün.
- **Sayaç:** Açık/kapalı. İşletme sahipleri ve yöneticiler sayılmaz.
- **Şehrin Sahipleri:** Puan 10 ya da 5 üzerinden; sıralamaya girmek için gereken en az yorum sayısı.

## 8. Bir şey yanlış görünürse

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
| Bağlı İşletmeler | Marka için istekler ve bağlı işletme listesi; alt işletme için bağlantı durumu ya da "markaya bağlan" |

### Menü bağlantılarındaki kısayollar

Menü ve düğme adreslerine şunları yazabilirsin, her işletme için otomatik doldurulur:

- `{edit}`: işletmeyi düzenleme sayfası
- `{view}`: işletmenin herkese açık sayfası
- `{id}`: işletme ID'si

### Henüz verisi olmayan bölümler

Şikâyet, fırsat, duyuru, kupon, etkinlik ve iş ilanı için sitede henüz Voxel post type'ı yok. Bu öğeler menüde **"Yakında"** olarak soluk görünür ve tıklanmaz. İlgili sayfa hazır olunca:
- Menü öğesinin **Yakında** anahtarını kapat ve adresini yaz.
- Sektör modülleri (rezervasyon, randevu vb.) için adresi **Sektörler → Modül sayfaları** bölümüne yaz.
