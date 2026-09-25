# İşletmeBurada Panel — Kurulum

Bu eklenti İşletme Paneli tasarımını Elementor widget'ları olarak ekler. Widget'lar veriyi Voxel'deki `isletme` post type'ından okur, HTML yazman gerekmez.

> Önce bir **test/staging sitede** dene. Hostinger panelinde "Staging" ile sitenin kopyasını açabilirsin.

## 1. Eklentiyi yükle

1. `wordpress/dist/isletmeburada-panel.zip` dosyasını indir.
2. WordPress: **Eklentiler → Yeni ekle → Eklenti yükle** → zip'i seç → **Şimdi yükle**.
   Eski sürüm kuruluysa **"Mevcut olanı yüklenenle değiştir"** de.
3. **Etkinleştir.**

## 2. Durum kontrolü

Sol menüde yeni bir **İşletmeBurada** bölümü açılır. Altında şu sayfalar var: **Genel bakış, Widget'lar, Sektörler, Marka ağı, Ayarlar, Tanılama**.

**Genel bakış** sayfasında her satırın yanında yeşil tik olmalı:

| Kontrol | Anlamı |
|---|---|
| Elementor / Elementor Pro | Widget'ların görünmesi için |
| Voxel | İşletme alanlarını okumak için |
| İşletme post type | Kaç işletme olduğu |
| Yorum tablosu / Yorum yanıtları | Son yorumlar ve "yanıt bekleyen yorum" sayısı |
| Sayaç tablosu | Görüntüleme ve tıklama grafiği |

Sarı ünlem varsa ekran görüntüsünü gönder.

## 3. Panel sayfalarını oluştur (tek tık)

**Genel bakış** sayfasında **"Panel sayfalarını oluştur"** düğmesine bas. Üç sayfa oluşur:

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

## 7. Bireysel panel (Hesabım)

Her giriş yapan kullanıcı için kişisel panel. **Genel bakış → Panel sayfalarını oluştur / sıfırla** düğmesi **Hesabım** sayfasını da kurar. Mevcut panel sayfaların da varsayılan düzene döner.

Görünenler:
- **Kullanıcı kartı:** avatar, ad, yerel rehber seviyesi, sonraki seviyeye kalan puan
- **Sayı kartları:** Yorumlarım, Favorilerim, Bekleyen rezervasyon, Yerel rehber puanı
- **Rezervasyon ve siparişlerim:** Voxel Ecommerce siparişleri ve durumları
- **Hızlı git:** kısayollar, sayı rozetleriyle
- **Yorumlarım, Favorilerim, Takip ettiklerim:** Voxel yorumları, koleksiyonları ve takip listesi
- **Tanıtım kutusu:** Ör. "Bireysel Plus". Düğme adresine Voxel üyelik paketleri sayfasını yaz. Paketi olan kullanıcıların rolünü "Bu rollerde gizle" alanına yazarsan onlar görmez.

**Yerel rehber puanı:** Her yorum +50, favorilere eklenen her işletme +5, her 300 puanda bir seviye. Değerleri **Ayarlar**'dan değiştirebilirsin.

**İki panel arası geçiş:** İşletme panelinin menüsünde **"Bireysel panelim"**, bireysel panelde **"İşletme panelim"** bağlantısı var. İkincisi yalnız işletmesi olan kullanıcıda görünür. Üst bardaki kullanıcı menüsünde de iki bağlantı bulunur.

Biletler, iş başvuruları, özgeçmiş, kuponlar, etkinlikler, teklif talepleri, şikâyetler ve abonelik için sitede henüz veri yok. Bunlar menüde **"Yakında"** görünür.

**Sipariş satırları:** Tıklanınca sipariş ayrıntısına gitmesi için Kişisel Liste widget'ındaki **"Sipariş ayrıntı adresi"** alanına Voxel'in sipariş sayfasını yaz, ör. `/siparislerim/?order_id={id}`.

## 8. Ana sayfa

**Genel bakış → Ana sayfa taslağı oluştur** düğmesi prototipteki ana sayfayı **taslak** olarak kurar. Mevcut ana sayfan değişmez. Voxel temanın üst menüsü ve alt bilgisi korunur. Beğenince **Ayarlar → Okuma → Ana sayfa** olarak seç.

Bölümler (Elementor'da **"İşletmeBurada Ana Sayfa"** başlığı altında):

| Widget | Veri |
|---|---|
| Arama | Kategoriler Voxel'den; kategori → şehir → arama sayfası |
| Şehrin Sahipleri | Her kategoride şehrin 1 numarası, rakibi ve aradaki fark, Yarış Arenası |
| Kategori Sıralaması | Bir kategorinin şehirdeki en yüksek puanlı, en popüler ve yükselen işletmeleri |
| Şehirler | Şehir kartları, işletme sayıları, tüm iller |
| İşletme Paketleri | Düzenlenebilir tanıtım ve paketler |
| Referanslar | Sitedeki gerçek 4–5 yıldızlı yorumlar; yetmezse elle yazılanlar |
| Blog | Son yazılar |
| Çağrı Bandı | Başlık ve düğmeler |

**Şehir:** Sıralamalar ziyaretçinin seçtiği şehre göre gösterilir (`?sehir=izmir`). Seçim yoksa **Ayarlar → Ana sayfa varsayılan şehri** kullanılır. Her şehir ayrı adres olduğu için LiteSpeed önbelleğiyle sorunsuz çalışır.

**Arama sayfası (önemli):** Arama ve şehir kartlarının doğru sonuca gitmesi için **Ayarlar → Arama sayfası** alanına Voxel arama sayfanın adresini yaz. Kategori ve şehir parametre adlarını da gir. Bunları bulmak için: Voxel arama sayfasında bir kategori ve şehir filtresi seç, adres çubuğundaki adları kopyala.

**Taht:** Şehrin Sahipleri, işletme panelindeki sıralamayla aynı hesabı kullanır. Taht değişiklikleri kaydedilir; "3 aydır tahtta" rozetleri buradan gelir. 1. ile 2. arasındaki fark çok azsa kart **"Canlı yarış"** olarak işaretlenir.

**Henüz eklenmeyenler:** Prototipteki **Çözüm Liderleri** (şikâyet) ve **Bu hafta** (etkinlik, fırsat, iş ilanı) bölümlerinin sitede verisi yok. Bu post type'lar açılınca eklenebilir.

## 9. Ayarlar (İşletmeBurada → Ayarlar)

- **Düzenleme sayfası adresi:** Boş bırakırsan Voxel'in kendi düzenleme bağlantısı kullanılır.
- **Varsayılan dönem:** 7, 30 ya da 90 gün.
- **Sayaç:** Açık/kapalı. İşletme sahipleri ve yöneticiler sayılmaz.
- **Şehrin Sahipleri:** Puan 10 ya da 5 üzerinden; sıralamaya girmek için gereken en az yorum sayısı.

## 10. Widget'ları aç/kapat (İşletmeBurada → Widget'lar)

Tüm widget'lar kartlar hâlinde listelenir. Her kartta şunlar var:
- ne işe yaradığı,
- hangi gruba ait olduğu (İşletme paneli / Bireysel panel / Ana sayfa),
- kaç sayfada kullanıldığı.

**Aç/kapat:** Anahtarla açıp kapatabilir, arama ve grup filtresiyle listeyi daraltabilirsin. Kapatılan widget Elementor'un listesinde görünmez ve yüklenmez.

**Uyarı:** Bir sayfada kullanılan widget'ı kapatırsan kaydetmeden önce uyarı alırsın. Kapatılan widget, kullanıldığı sayfalarda görünmez olur.

## 11. Bir şey yanlış görünürse

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
| Kullanıcı Kartı | Bireysel: avatar, ad, yerel rehber seviyesi |
| Kişisel İstatistik | Bireysel: yorum, favori, takip, rezervasyon sayısı ya da rehber puanı |
| Kişisel Liste | Bireysel: Yorumlarım, Favorilerim, Takip ettiklerim ya da Rezervasyon ve siparişlerim |
| Hızlı Git | İkonlu kısayol kutuları |
| Tanıtım Kutusu | Koyu tanıtım kutusu (ör. Bireysel Plus) |

### Menü bağlantılarındaki kısayollar

Menü ve düğme adreslerine şunları yazabilirsin, her işletme için otomatik doldurulur:

- `{edit}`: işletmeyi düzenleme sayfası
- `{view}`: işletmenin herkese açık sayfası
- `{id}`: işletme ID'si

### Henüz verisi olmayan bölümler

Şikâyet, fırsat, duyuru, kupon, etkinlik ve iş ilanı için sitede henüz Voxel post type'ı yok. Bu öğeler menüde **"Yakında"** olarak soluk görünür ve tıklanmaz. İlgili sayfa hazır olunca:
- Menü öğesinin **Yakında** anahtarını kapat ve adresini yaz.
- Sektör modülleri (rezervasyon, randevu vb.) için adresi **Sektörler → Modül sayfaları** bölümüne yaz.
