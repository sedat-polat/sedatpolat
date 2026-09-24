# İşletmeBurada Panel — Kurulum ve ilk sayfa

Bu eklenti, İşletme Paneli tasarımını Elementor widget'ları olarak ekler. Widget'lar veriyi Voxel'deki `isletme` post type'ından okur. HTML yazman gerekmez.

> Önce bir **test/staging sitede** dene. Hostinger panelinde "Staging" ile sitenin kopyasını açabilirsin.

## 1. Eklentiyi yükle

1. `wordpress/dist/isletmeburada-panel.zip` dosyasını bilgisayarına indir (GitHub'da dosyaya tıkla → sağ üstteki **Download raw file** düğmesi).
2. WordPress: **Eklentiler → Yeni ekle → Eklenti yükle → Dosya seç** → zip'i seç → **Şimdi yükle** → **Etkinleştir**.

## 2. Tasarımın renklerini Elementor'a gir (bir kez)

**Elementor → Site Ayarları → Global Renkler** bölümüne şu renkleri ekle:

| Ad | Renk | Nerede |
|---|---|---|
| Panel Kırmızı | `#D93A40` | Ana düğmeler, vurgular |
| Panel Mercan | `#FF5A5F` | Çubuklar, logo kutusu |
| Panel Yazı | `#1C1E21` | Başlıklar, ana yazı |
| Panel Gri Yazı | `#6B7280` | Açıklamalar |
| Panel Kenar | `#E6E8EB` | Kart kenarları |
| Panel Arka Plan | `#F7F8FA` | Sayfa zemini |
| Panel Menü Zemini | `#FBFBFC` | Sol menü |

**Global Yazı Tipleri**'nde tasarımın fontu **Inter**.

## 3. Genel bakış sayfasını kur

1. **Sayfalar → Yeni ekle**, adı: `Panel`. **Elementor ile düzenle**.
2. Sayfa ayarlarında (sol alttaki dişli) **Sayfa düzeni: Elementor Tuval** (Canvas) seç. Böylece sitenin üst ve alt menüsü panelde görünmez.
3. Bir **Container** ekle. Yön: **Yatay**, arka plan `#F7F8FA`, en az yükseklik `100vh`.
4. İçine iki container daha koy:
   - **Sol menü**: genişlik `256px`, arka plan `#FBFBFC`, sağ kenar `1px #EEF0F2`, iç boşluk `18px 12px`.
   - **İçerik**: genişlik kalan alan, iç boşluk `32px`, boşluk (gap) `20px`.
5. Widget panelinde **İşletmeBurada** başlığını bul ve widget'ları sürükle:
   - Sol menüye: **İşletme Kartı**. Altına Voxel'in **Navbar** widget'ını menü için ekle.
   - İçeriğe: **Karşılama**.
   - Altına 4 sütunlu bir container ve içine 4 adet **İstatistik Kartı**. Örneğin biri "Ortalama puan", biri "Yorum sayısı".
   - Sağ tarafa ya da alta: **Profil Doluluğu**.
6. **Görünürlük**: İçerik container'ını seç → **Gelişmiş → Display Conditions** → "Giriş yapmış kullanıcı" koşulunu ekle.

## 4. Kontrol et ve bana bildir

İşletmesi olan bir kullanıcıyla giriş yapıp sayfayı aç ve şunlara bak:

- [ ] İşletme Kartı'nda doğru isim, şehir ve kategori görünüyor mu?
- [ ] Birden fazla işletmesi olan kullanıcıda seçme kutusu işletmeyi değiştiriyor mu?
- [ ] Karşılama'daki saat, işletmenin bugünkü çalışma saatiyle aynı mı?
- [ ] **Ortalama puan** işletme sayfasındaki puanla aynı mı? Farklıysa iki sayıyı da yaz.
- [ ] Profil Doluluğu'ndaki eksik alanlar gerçekten boş mu? "… ekle" bağlantısı düzenleme sayfasını açıyor mu? Açmıyorsa widget'taki **Düzenleme sayfası adresi** kutusuna Voxel'in düzenleme adresini yaz, ör. `/isletme-ekle/?post_id={id}`.

Bir şey yanlışsa ekran görüntüsünü gönder, düzeltelim.

## Bu sürümdeki widget'lar

| Widget | Veri |
|---|---|
| İşletme Kartı | Logo, İşletme İsmi, Şehir ve Kategori taksonomileri. Birden fazla işletmede seçme kutusu. |
| Karşılama | Kullanıcının adı, tarih, Çalışma Saatleri alanından bugünün saatleri |
| İstatistik Kartı | Ortalama puan, yorum sayısı, galeri fotoğraf sayısı ya da elle yazılan değer |
| Profil Doluluğu | 13 alanın kaçının dolu olduğu, eksik alanlar |

Sırada: profil görüntüleme grafiği, son yorumlar, Şehrin Sahipleri sıralaması.
