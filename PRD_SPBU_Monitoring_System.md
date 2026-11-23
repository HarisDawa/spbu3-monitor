# PRD --- SPBU Monitoring System

**Versi:** 1.0\
**Jenis Produk:** Web Application (Admin Desktop + Warga Mobile)\
**Tujuan:** Membantu pengelola memantau operasional SPBU dan membantu
masyarakat memperoleh informasi ketersediaan BBM serta kondisi antrean.

------------------------------------------------------------------------

## 1. Ringkasan Produk

SPBU Monitoring System adalah aplikasi berbasis web untuk memantau
informasi SPBU secara terpusat. Sistem memiliki dua jenis antarmuka:

1.  **Admin / Monitoring Dashboard** --- dioptimalkan untuk desktop dan
    digunakan oleh admin atau petugas untuk mengelola data serta
    memantau kondisi SPBU.
2.  **Warga Dashboard** --- dioptimalkan untuk perangkat mobile agar
    masyarakat dapat mencari SPBU, melihat stok BBM, memeriksa antrean,
    dan mengirim laporan.

Aplikasi harus memiliki fitur **CRUD (Create, Read, Update, Delete)**
yang berfungsi, bukan hanya tampilan dashboard statis.

> Catatan: Jika belum tersedia integrasi sensor IoT atau API resmi,
> gunakan data simulasi dan beri label yang jelas bahwa data merupakan
> data demo. Jangan menyebut data simulasi sebagai kondisi real-time
> sebenarnya.

## 2. Tujuan

-   Menyediakan informasi stok BBM di setiap SPBU.
-   Memantau kondisi antrean dan status operasional SPBU.
-   Memudahkan admin mengelola data SPBU, stok, jenis BBM, dan laporan
    warga.
-   Membantu warga menemukan SPBU berdasarkan lokasi dan ketersediaan
    BBM.
-   Menyediakan riwayat perubahan data untuk kebutuhan audit sederhana.

## 3. Pengguna dan Hak Akses

### 3.1 Admin

-   Login ke dashboard admin.
-   Melihat ringkasan seluruh SPBU.
-   Mengelola data SPBU dan jenis BBM.
-   Menambah, melihat, mengubah, dan menghapus data stok.
-   Memperbarui kondisi antrean dan status operasional.
-   Memeriksa serta mengelola laporan warga.
-   Melihat riwayat aktivitas.

### 3.2 Petugas SPBU

-   Login ke dashboard petugas.
-   Melihat data SPBU yang menjadi tanggung jawabnya.
-   Memperbarui stok BBM.
-   Memperbarui kondisi antrean dan status operasional.
-   Melihat laporan warga yang berkaitan dengan SPBU tersebut.

### 3.3 Warga

-   Melihat daftar dan peta SPBU.
-   Melihat stok BBM, harga, dan kondisi antrean.
-   Mencari dan memfilter SPBU.
-   Melihat detail SPBU.
-   Mengirim laporan atau keluhan.
-   Melihat status laporan miliknya.

## 4. Struktur Halaman

### 4.1 Admin Dashboard --- Desktop

Gunakan sidebar navigasi dan area konten utama.

Menu: - Dashboard - Data SPBU - Stok BBM - Jenis BBM - Monitoring
Antrean - Laporan Warga - Pengguna dan Petugas - Riwayat Aktivitas -
Pengaturan - Logout

### 4.2 Warga Dashboard --- Mobile

Gunakan desain mobile-first dengan navigasi bawah.

Menu: - Beranda - Peta - Laporan - Aktivitas - Profil

Halaman warga harus tetap responsif pada tablet dan desktop, tetapi
prioritas desain adalah layar ponsel.

## 5. Fitur Admin

### 5.1 Ringkasan Dashboard

Tampilkan kartu statistik: - Total SPBU. - SPBU beroperasi. - SPBU tidak
beroperasi. - SPBU dengan stok menipis. - Total laporan warga yang belum
ditangani. - Rata-rata kondisi antrean.

Tambahkan: - Grafik perubahan stok BBM. - Grafik laporan warga
berdasarkan status. - Tabel SPBU dengan stok terendah. - Daftar laporan
terbaru. - Peta lokasi SPBU jika data koordinat tersedia.

### 5.2 CRUD Data SPBU

Admin dapat melakukan operasi berikut:

**Create** - Menambahkan SPBU baru. - Mengisi kode SPBU, nama, alamat,
kecamatan/kota, koordinat, nomor telepon, jam operasional, dan status.

**Read** - Melihat seluruh SPBU dalam tabel. - Melihat detail SPBU. -
Mencari berdasarkan nama atau kode. - Memfilter berdasarkan wilayah dan
status.

**Update** - Mengubah informasi SPBU. - Memperbarui alamat, koordinat,
jam operasional, dan status.

**Delete** - Menghapus data SPBU dengan dialog konfirmasi. - Cegah
penghapusan jika masih ada data transaksi atau histori penting yang
terhubung; gunakan soft delete atau nonaktifkan data jika diperlukan.

Kolom tabel: - Kode SPBU - Nama SPBU - Wilayah - Status operasional -
Kondisi antrean - Aksi: Detail, Edit, Hapus

### 5.3 CRUD Stok BBM

Admin/petugas dapat mengelola stok setiap jenis BBM pada masing-masing
SPBU.

Data: - SPBU - Jenis BBM - Stok tersedia (liter) - Kapasitas maksimum
(liter) - Ambang batas stok menipis (liter) - Waktu pembaruan - Petugas
yang memperbarui

Operasi: - Tambah catatan stok. - Lihat stok berdasarkan SPBU atau jenis
BBM. - Edit jumlah stok. - Hapus catatan stok dengan konfirmasi jika
belum menjadi bagian histori permanen.

Ketentuan: - Stok tidak boleh bernilai negatif. - Stok tidak boleh
melebihi kapasitas maksimum. - Jika stok sama dengan atau di bawah
ambang batas, tampilkan status **Menipis**. - Jika stok 0, tampilkan
status **Habis**. - Simpan histori perubahan stok agar perubahan dapat
ditelusuri.

### 5.4 CRUD Jenis BBM

Data awal contoh: - Pertalite - Pertamax - Pertamax Turbo - Solar -
Dexlite - Pertamina Dex

Operasi: - Tambah jenis BBM. - Lihat daftar jenis BBM. - Edit nama,
deskripsi, dan harga per liter. - Hapus atau nonaktifkan jenis BBM.

Harga harus dapat diubah admin dan diberi keterangan bahwa harga
merupakan data yang dimasukkan pengelola, bukan otomatis harga resmi.

### 5.5 Monitoring Antrean

Data antrean: - SPBU - Kategori kendaraan (opsional) - Estimasi waktu
tunggu (menit) - Tingkat antrean: Lancar, Sedang, Padat - Waktu
pembaruan - Sumber data: Petugas, Sensor, atau Simulasi

Admin/petugas dapat menambah, melihat, dan memperbarui data antrean.
Riwayat pembaruan sebaiknya disimpan.

### 5.6 CRUD Laporan Warga

Admin dapat: - Melihat seluruh laporan. - Membuka detail laporan. -
Mengubah status laporan. - Menambahkan catatan tindak lanjut. -
Menghapus laporan spam atau laporan duplikat dengan konfirmasi.

Status laporan: - Menunggu - Diverifikasi - Ditindaklanjuti - Selesai -
Ditolak

Data laporan: - Nomor laporan - Nama pelapor (atau anonim jika
diizinkan) - SPBU terkait - Kategori laporan - Deskripsi - Foto bukti
(opsional) - Waktu laporan - Status - Catatan admin

Kategori laporan: - Antrean panjang - BBM habis - Informasi stok tidak
sesuai - SPBU tutup - Fasilitas bermasalah - Lainnya

### 5.7 CRUD Pengguna dan Petugas

Admin dapat: - Membuat akun petugas. - Melihat daftar pengguna. -
Mengubah nama, email, role, dan penugasan SPBU. - Menonaktifkan atau
menghapus akun dengan konfirmasi. - Mengatur ulang kata sandi melalui
mekanisme yang aman.

Role: - Admin - Petugas - Warga

Jangan menyimpan password dalam bentuk teks biasa. Gunakan password
hashing.

### 5.8 Riwayat Aktivitas

Catat aktivitas penting: - Login dan logout. - Penambahan, perubahan,
dan penghapusan data. - Perubahan stok. - Perubahan status laporan. -
Pengubahan status operasional SPBU.

Tampilkan waktu, pengguna, jenis aktivitas, dan ringkasan perubahan.

## 6. Fitur Warga --- Mobile

### 6.1 Beranda

Tampilkan: - Kolom pencarian SPBU. - SPBU terdekat (jika izin lokasi
diberikan). - Ringkasan SPBU dengan stok tersedia. - Daftar SPBU
populer/terdekat. - Informasi antrean. - Peringatan informasi penting.

### 6.2 Peta SPBU

-   Tampilkan marker lokasi SPBU.
-   Marker atau label status ketersediaan BBM.
-   Filter jenis BBM dan status operasional.
-   Tombol untuk membuka detail SPBU.
-   Tombol navigasi menggunakan aplikasi peta eksternal jika tersedia.

Jika izin lokasi ditolak, sediakan pencarian wilayah secara manual.

### 6.3 Detail SPBU

Tampilkan: - Nama dan kode SPBU. - Alamat. - Jam operasional. - Status
buka/tutup berdasarkan data operasional. - Daftar jenis BBM dan stok. -
Harga BBM yang tercatat. - Kondisi antrean dan estimasi waktu tunggu. -
Waktu terakhir data diperbarui. - Tombol navigasi. - Tombol buat
laporan.

### 6.4 Buat Laporan

Form warga: - Pilih SPBU. - Pilih kategori laporan. - Isi deskripsi. -
Unggah foto bukti (opsional). - Kirim laporan.

Setelah dikirim, tampilkan nomor laporan dan status awal **Menunggu**.

### 6.5 Aktivitas dan Status Laporan

Warga dapat melihat laporan yang pernah dikirim beserta: - Nomor
laporan. - SPBU terkait. - Tanggal laporan. - Kategori. - Status
terbaru. - Catatan tindak lanjut yang boleh ditampilkan kepada warga.

### 6.6 Profil

-   Nama.
-   Email.
-   Nomor telepon (opsional).
-   Edit profil.
-   Logout.

## 7. Aturan Sinkronisasi Data

-   Data SPBU, stok, antrean, dan laporan dikelola dari backend yang
    sama.
-   Perubahan stok oleh petugas harus terlihat pada dashboard admin dan
    dashboard warga setelah data berhasil disimpan.
-   Perubahan status laporan oleh admin harus terlihat pada akun warga
    pemilik laporan.
-   Warga hanya boleh melihat dan mengubah data profil serta laporan
    miliknya sesuai aturan.
-   Warga tidak boleh mengubah stok, harga, atau status operasional
    SPBU.
-   Semua endpoint harus memeriksa autentikasi dan hak akses di sisi
    server.

## 8. Rekomendasi Database

Gunakan database relasional, misalnya MySQL atau PostgreSQL.

### Tabel `users`

-   id
-   name
-   email
-   password
-   phone
-   role
-   created_at
-   updated_at

### Tabel `stations`

-   id
-   code
-   name
-   address
-   region
-   latitude
-   longitude
-   phone
-   opening_hours
-   operational_status
-   created_at
-   updated_at
-   deleted_at (opsional)

### Tabel `fuel_types`

-   id
-   name
-   description
-   price_per_liter
-   is_active
-   created_at
-   updated_at

### Tabel `fuel_stocks`

-   id
-   station_id
-   fuel_type_id
-   stock_liters
-   capacity_liters
-   low_stock_threshold
-   updated_by
-   created_at
-   updated_at

### Tabel `stock_histories`

-   id
-   fuel_stock_id
-   previous_stock
-   new_stock
-   change_amount
-   note
-   updated_by
-   created_at

### Tabel `queue_statuses`

-   id
-   station_id
-   vehicle_category
-   estimated_wait_minutes
-   queue_level
-   source
-   updated_by
-   created_at
-   updated_at

### Tabel `citizen_reports`

-   id
-   report_code
-   user_id
-   station_id
-   category
-   description
-   photo_path
-   status
-   admin_note
-   handled_by
-   created_at
-   updated_at

### Tabel `activity_logs`

-   id
-   user_id
-   action
-   entity_type
-   entity_id
-   description
-   created_at

Relasi utama: - Satu SPBU memiliki banyak catatan stok. - Satu jenis BBM
dapat digunakan di banyak SPBU. - Satu SPBU memiliki banyak status
antrean. - Satu warga dapat membuat banyak laporan. - Satu laporan
terkait dengan satu SPBU. - Satu catatan stok memiliki banyak histori
perubahan.

## 9. Rekomendasi Teknologi

Pilihan stack utama: - Frontend: React + Tailwind CSS. - Backend:
Laravel REST API. - Database: MySQL. - Peta: Leaflet + OpenStreetMap. -
Grafik: Recharts. - Autentikasi: Laravel Sanctum atau session
authentication. - Upload foto: penyimpanan lokal dengan validasi tipe
dan ukuran file.

Jika proyek hanya berupa prototype frontend, gunakan data mock terlebih
dahulu, tetapi struktur kode harus mudah dihubungkan ke API backend
nantinya.

## 10. Desain UI/UX

### Admin

-   Gaya modern Smart City.
-   Warna utama navy/biru, putih, dan aksen cyan.
-   Sidebar desktop.
-   Kartu statistik.
-   Tabel dengan pencarian, filter, pagination.
-   Grafik yang mudah dibaca.
-   Badge status dengan warna konsisten.
-   Form tambah/edit menggunakan modal atau halaman khusus.
-   Dialog konfirmasi sebelum menghapus.

### Warga

-   Mobile-first.
-   Tampilan sederhana dan mudah dipahami.
-   Navigasi bawah.
-   Kartu SPBU dengan informasi stok dan antrean.
-   Tombol aksi yang mudah dijangkau.
-   Peta yang dapat diperbesar dan digeser.
-   Loading, empty state, error state, dan feedback setelah aksi.

## 11. Ketentuan CRUD dan Validasi

Setiap modul CRUD wajib memiliki: - Form tambah data. - Daftar data. -
Detail data jika relevan. - Form edit. - Tombol hapus dengan
konfirmasi. - Validasi input di frontend dan backend. - Notifikasi
sukses atau gagal. - Loading state saat menyimpan. - Empty state ketika
belum ada data.

Ketentuan tambahan: - Gunakan pagination untuk tabel panjang. - Gunakan
pencarian dan filter. - Hindari penghapusan permanen untuk data histori
penting. - Tangani error validasi dengan pesan yang jelas. - Jangan
hanya membuat tombol dekoratif; setiap tombol harus menjalankan aksi
yang benar. - Setelah CRUD berhasil, perbarui tampilan data tanpa perlu
refresh manual jika memungkinkan.

## 12. Keamanan

-   Autentikasi untuk admin dan petugas.
-   Role-based access control.
-   Validasi dan sanitasi input.
-   Password hashing.
-   Validasi file upload.
-   Proteksi endpoint berdasarkan role dan kepemilikan data.
-   Jangan menampilkan data pribadi warga secara terbuka.
-   Simpan log aktivitas penting.
-   Terapkan CSRF protection jika menggunakan session/cookie.
-   Gunakan HTTPS pada deployment.

## 13. Data Demo

Buat minimal: - 8 data SPBU contoh. - 6 jenis BBM. - Data stok untuk
beberapa jenis BBM di setiap SPBU. - Variasi antrean Lancar, Sedang, dan
Padat. - 10 laporan warga dengan status berbeda. - Akun demo untuk
admin, petugas, dan warga.

Gunakan nama dan lokasi fiktif atau tandai data sebagai data demo.
Jangan menampilkan data demo seolah-olah data operasional resmi.

## 14. Kriteria Penerimaan

Aplikasi dianggap memenuhi kebutuhan apabila:

1.  Admin dapat login dan melihat dashboard monitoring.
2.  Admin dapat melakukan CRUD data SPBU.
3.  Admin/petugas dapat melakukan CRUD stok BBM dengan validasi.
4.  Admin dapat mengelola jenis BBM.
5.  Petugas dapat memperbarui antrean dan status operasional.
6.  Admin dapat melihat dan memproses laporan warga.
7.  Admin dapat mengelola akun petugas sesuai hak akses.
8.  Warga dapat membuka dashboard melalui tampilan mobile.
9.  Warga dapat mencari SPBU dan melihat detail stok serta antrean.
10. Warga dapat mengirim laporan dan melihat statusnya.
11. Perubahan data tampil konsisten pada dashboard terkait.
12. Semua tombol, form, filter, dan aksi CRUD berfungsi.
13. Tampilan responsif, termasuk loading, error, dan empty state.
14. Data simulasi diberi label yang jelas.

## 15. Prioritas Pengerjaan

**Tahap 1 --- Fondasi** - Setup project dan database. - Autentikasi dan
role. - Layout admin dan warga. - Seed data demo.

**Tahap 2 --- Modul Admin** - Dashboard monitoring. - CRUD SPBU. - CRUD
jenis BBM. - CRUD stok BBM dan histori. - Monitoring antrean. - CRUD
laporan warga. - Pengelolaan petugas.

**Tahap 3 --- Modul Warga** - Beranda mobile. - Pencarian dan peta
SPBU. - Detail SPBU. - Form laporan. - Riwayat laporan dan profil.

**Tahap 4 --- Integrasi dan Pengujian** - Sinkronisasi data
antar-dashboard. - Validasi role dan keamanan. - Pengujian seluruh
CRUD. - Pengujian responsivitas. - Perbaikan bug dan dokumentasi
instalasi.

------------------------------------------------------------------------

## Instruksi untuk AI Coding Agent

Bangun aplikasi berdasarkan PRD ini secara bertahap dan hasilkan
aplikasi yang benar-benar dapat dijalankan.

Prioritaskan fungsionalitas daripada sekadar tampilan: - Semua menu
harus memiliki halaman yang sesuai. - Semua fitur CRUD harus terhubung
ke penyimpanan data. - Gunakan relasi database yang benar. - Buat
validasi, notifikasi, loading, dan error handling. - Pastikan dashboard
admin dan warga menggunakan sumber data yang sama. - Terapkan hak akses
berdasarkan role. - Buat README berisi cara instalasi, konfigurasi
database, migrasi, seed data, dan cara menjalankan aplikasi. - Jika
integrasi IoT belum tersedia, gunakan data demo dan jelaskan bagian yang
nantinya perlu dihubungkan ke perangkat atau API.
