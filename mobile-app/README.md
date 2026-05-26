# Panduan Aplikasi HP (Mobile App) - MonitorPro

Repositori ini kini dilengkapi dengan dua opsi untuk menjalankan MonitorPro di Ponsel (HP):
1. **Progressive Web App (PWA)**: Langsung dapat diinstal dari browser HP tanpa compile.
2. **Native Android App (WebView)**: Project native Android Studio yang siap di-compile menjadi file APK.

---

## Opsi 1: Progressive Web App (PWA)

PWA memungkinkan pengguna untuk menginstal MonitorPro langsung ke layar beranda HP mereka dari browser web.

### Keuntungan PWA:
- Tidak perlu proses build/compile yang rumit.
- Bekerja di Android (Chrome/Firefox) dan iOS (Safari).
- Tampilan standalone tanpa navigasi browser (terlihat seperti aplikasi native).
- Mendukung mode offline menggunakan Service Worker.

### Cara Instalasi:
1. **Android**: Buka browser Chrome, akses domain web MonitorPro Anda (misal: `https://monitoring-perusahaan.up.railway.app`). Klik tombol menu titik tiga di kanan atas lalu pilih **Tambahkan ke Layar Utama** (atau **Instal Aplikasi**).
2. **iOS (iPhone)**: Buka browser Safari, akses domain web MonitorPro Anda. Ketuk tombol **Bagikan (Share)** (ikon kotak dengan panah atas), gulir ke bawah lalu pilih **Tambahkan ke Layar Utama (Add to Home Screen)**.

---

## Opsi 2: Native Android App (WebView)

Kami menyediakan source code lengkap project Android Studio di folder `/mobile-app/android` untuk di-compile menjadi file `.apk`.

### Fitur Native App:
- Kontrol penuh atas hardware HP.
- **Swipe to Refresh**: Geser ke bawah di layar mana saja untuk menyegarkan halaman.
- **File Upload & Kamera**: Terintegrasi penuh dengan FileProvider Android untuk mengambil foto lewat kamera langsung atau memilih berkas laporan dari galeri.
- **Back Navigation**: Tombol back HP akan mengontrol history navigasi web daripada menutup paksa aplikasi.
- **Keamanan Session**: Didukung dengan DOM Storage untuk mengamankan data login.

### Persyaratan Compile:
1. Unduh dan instal [Android Studio Koala (atau versi terbaru)](https://developer.android.com/studio).
2. Pastikan PC Anda terhubung ke internet untuk download dependencies (Gradle).

### Langkah Build/Compile ke APK:
1. Buka **Android Studio**.
2. Pilih **Open an Existing Project** lalu arahkan ke folder `mobile-app/android` di repositori ini.
3. Tunggu proses sinkronisasi Gradle selesai (memerlukan waktu beberapa menit untuk unduhan awal).
4. Untuk mengganti URL target web aplikasi Anda:
   - Buka file [MainActivity.java](file:///f:/Monitoring/mobile-app/android/app/src/main/java/com/monitorpro/app/MainActivity.java).
   - Cari baris variable `APP_URL`:
     ```java
     private static final String APP_URL = "https://monitoring-perusahaan.up.railway.app";
     ```
   - Ubah URL tersebut dengan domain Railway Anda yang aktif.
5. Jalankan aplikasi menggunakan Emulator Android Studio, atau hubungkan HP Android Anda via USB Debugging.
6. Untuk membuat file APK mentah untuk dibagikan:
   - Pilih menu **Build** -> **Build Bundle(s) / APK(s)** -> **Build APK(s)**.
   - Setelah selesai, klik tautan **locate** untuk mengambil file `app-debug.apk` yang siap diinstal di HP Android mana saja!
