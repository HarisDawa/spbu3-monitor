# SPBU Monitoring System (Multi-Portal Smart City Hub)

Aplikasi web terpadu untuk pemantauan operasional SPBU, pengawasan stok minyak tangki pendam, kondisi antrean, dan sistem laporan keluhan warga secara real-time. Dibangun sesuai spesifikasi dokumen [PRD_SPBU_Monitoring_System.md](file:///E:/TUBES/SPBU3/PRD_SPBU_Monitoring_System.md) v1.0.

🌐 **Live Demo Vercel**: [https://spbu3-monitor.vercel.app](https://spbu3-monitor.vercel.app)  
📱 **Portal Warga (Mobile)**: [https://spbu3-monitor.vercel.app/warga](https://spbu3-monitor.vercel.app/warga)

> **Catatan:** Seluruh data yang disajikan merupakan **data demo / simulasi operasional** untuk kebutuhan prototipe sistem Smart City dan audit tugas besar.

---

## 🚀 Halaman & Arsitektur Sistem

Sistem dibagi menjadi **2 antarmuka utama** yang saling tersinkronisasi secara real-time melalui reactive state engine:

1. **[index.html](file:///E:/TUBES/SPBU3/index.html)** — **Admin & Petugas Monitoring Dashboard (Desktop Optimized)**
   - **Ringkasan KPI**: Total SPBU, SPBU Beroperasi, Tutup/Uji Tera, Stok Menipis/Kritis, Laporan Warga Belum Ditangani, dan Rata-rata Estimasi Antrean.
   - **Peta Interaktif Sebaran SPBU (Leaflet)**: Pemetaan geografis SPBU dengan penanda warna status (Hijau = Aman, Kuning = Antre/Menipis, Merah = Kritis/Tutup).
   - **Grafik Analitik (Chart.js)**: Tren penyaluran stok BBM harian dan distribusi status tindak lanjut aduan warga.
   - **CRUD Master Data SPBU**: Tambah SPBU baru, perbarui alamat & koordinat lat/lng, toggle status operasional (Buka/Tutup/Perbaikan), dan soft delete.
   - **CRUD Stok Tangki BBM**: Monitor volume liter, kapasitas maksimum, batas ambang kritis (<threshold), status otomatis (*Tersedia, Menipis, Habis*), dan validasi mutasi stok.
   - **Riwayat Perubahan Stok**: Jejak audit mutasi dropping truk tangki TBBM vs penyaluran dispenser.
   - **CRUD Jenis BBM & Harga**: Penyesuaian harga jual per liter (dengan catatan harga diinput pengelola), oktan/cetane (RON 90, 92, 98, CN 48, 51, 53), dan status keaktifan.
   - **Monitoring Antrean**: Pembaruan kondisi antrean kendaraan (*Lancar, Sedang, Padat*), estimasi waktu tunggu, dan sumber data (Petugas / Sensor ATG / Simulasi).
   - **CRUD Laporan Warga**: Tinjau aduan masuk, pratinjau bukti foto, perbarui status (*Menunggu ➔ Diverifikasi ➔ Ditindaklanjuti ➔ Selesai / Ditolak*), berikan catatan tindak lanjut, dan hapus laporan spam.
   - **Manajemen Pengguna & Petugas**: Pendaftaran akun petugas dengan penugasan SPBU tertentu dan hak akses role-based.
   - **Audit Log (Riwayat Aktivitas)**: Pencatatan otomatis setiap aksi login, mutasi data, perubahan stok, dan penanganan laporan.
   - **Kontrol Simulasi & Backup**: Reset data bawaan PRD, unduh/impor snapshot JSON, dan trigger live sensor tick.

2. **[warga.html](file:///E:/TUBES/SPBU3/warga.html)** — **Portal BBM Warga (Mobile-First Experience)**
   - **Desain Mobile-First**: Dioptimalkan untuk ponsel dengan opsi beralih antara *Mode Frame HP* dan *Mode Layar Penuh*.
   - **Pencarian Cepat & Filter Pintar**: Pencarian SPBU berdasarkan nama atau jalan, serta filter cepat (*Pertalite Ready, Pertamax Ready, Antrean Lancar <10 mnt, Buka 24 Jam*).
   - **Kartu SPBU Terdekat**: Jarak estimasi dari posisi pengguna, status buka/tutup, badge kondisi antrean menit tunggu, dan tag ketersediaan BBM.
   - **Peta Mobile (Leaflet)**: Peta interaktif sentuh dengan tombol *Lokasi Saya* dan bottom sheet detail saat marker ditekan.
   - **Detail SPBU Lengkap**: Rincian stok BBM dengan indikator bar progres (% tangki terisi), harga per liter, jam operasional, fasilitas, dan tombol langsung rute navigasi Google Maps.
   - **Form Pengaduan Warga**: Pilih SPBU, kategori masalah, isi deskripsi, unggah foto bukti kamera/galeri, dan otomatis memperoleh nomor registrasi laporan (`LAP-YYYYMMDD-XXX`).
   - **Tracking Status Laporan 4 Tahap**: Pelacakan visual progres laporan (*Menunggu ➔ Diverifikasi ➔ Ditindaklanjuti ➔ Selesai*) beserta catatan resmi petugas tanggap darurat.
   - **Profil Pengguna**: Manajemen identitas warga dan peralihan peran cepat.

3. **[store.js](file:///E:/TUBES/SPBU3/store.js)** — **Reactive State Engine & LocalStorage Database**
   - Menjamin setiap aksi CRUD (misal: petugas menambah stok di dashboard atau mengubah antrean) langsung tersinkronisasi ke portal warga secara instan tanpa perlu memuat ulang server backend.

---

## 👥 Akun & Peran Pengguna (Multi-Role)

Sistem menyediakan switcher peran langsung pada navbar untuk memudahkan pengujian semua perspektif pengguna:

| Akun / Nama | Email | Peran (Role) | Hak Akses & Penugasan |
|---|---|---|---|
| **Admin Pengelola Pusat** | `admin@spbumonitor.test` | `admin` | Akses penuh: Master SPBU, penyesuaian harga BBM, seluruh stok, verifikasi laporan, kelola petugas |
| **Ahmad Subarjo** | `petugas@spbumonitor.test` | `petugas` | Ditugaskan di **SPBU Pertamina Cilandak (34.124.01)**: Update stok tangki pendam & antrean |
| **Dedi Kurniawan** | `petugas2@spbumonitor.test` | `petugas` | Ditugaskan di **SPBU Pertamina Bekasi Barat (32.171.04)**: Update stok & antrean unit Bekasi |
| **Budi Santoso** | `warga@spbumonitor.test` | `warga` | Portal publik warga: Cek stok, cari SPBU lancar, buat laporan, pantau tindak lanjut |
| **Siti Rahmawati** | `siti@warga.test` | `warga` | Pelapor warga aktif |

---

## 📦 Data Awal Demo (Sesuai PRD Section 13)

Sistem telah dilengkapi dengan basis data awal yang kaya:
- **8 Data SPBU**: Cilandak, Fatmawati, Cawang, Pulogadung, Bekasi Barat, Cikarang, Bandung Timur, Surabaya Ahmad Yani.
- **6 Jenis BBM Resmi**: Pertalite (Rp 10.000), Pertamax (Rp 13.700), Pertamax Turbo (Rp 15.400), Biosolar (Rp 6.800), Dexlite (Rp 14.200), Pertamina Dex (Rp 15.300).
- **Variasi Kondisi Stok**: Tersedia, Menipis (contoh: Pertalite SPBU Cawang sisa 1.800L), dan Habis (contoh: Pertalite SPBU Bekasi Barat 0L).
- **Variasi Antrean**: Lancar (3-8 mnt), Sedang (12-15 mnt), Padat (30 mnt).
- **10 Laporan Warga**: Mencakup berbagai status (*Menunggu, Diverifikasi, Ditindaklanjuti, Selesai, Ditolak*) dengan foto bukti dan catatan admin.
- **Log Histori Aktivitas**: Audit trail pencatatan mutasi sistem.

---

## 💻 Cara Menjalankan Aplikasi

Aplikasi ini bersifat **standalone static web application** murni tanpa ketergantungan build atau server yang rumit:

### Opsi 1: Buka Langsung di Browser
- Klik ganda file [index.html](file:///E:/TUBES/SPBU3/index.html) untuk membuka Dashboard Admin & Petugas.
- Klik ganda file [warga.html](file:///E:/TUBES/SPBU3/warga.html) untuk membuka Portal Warga Mobile.

### Opsi 2: Menggunakan Local HTTP Server (Opsional)
Jika Anda memiliki Node.js atau Python:
```bash
# Menggunakan Python
cd E:\TUBES\SPBU3
python -m http.server 8080

# Menggunakan Node npx serve
npx serve .
```
Lalu buka `http://localhost:8080` di browser Anda.

---

## ☁️ Deployment ke Vercel

Folder ini sudah dilengkapi file konfigurasi [vercel.json](file:///E:/TUBES/SPBU3/vercel.json) yang siap di-deploy secara instan:

```bash
cd E:\TUBES\SPBU3
vercel --prod
```

---

## 🛠️ Ringkasan Teknologi

- **Antarmuka (Frontend)**: HTML5, CSS3, Tailwind CSS CDN, Font Awesome 6.
- **Peta Digital**: Leaflet.js + CartoDB Dark/Voyager & OpenStreetMap Tiles.
- **Grafik Interaktif**: Chart.js 4.
- **Penyimpanan & Sinkronisasi**: Reactive Engine + LocalStorage API dengan Event Emitter.
