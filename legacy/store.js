/**
 * SPBU Monitoring System - Central Reactive Store & Database Engine
 * Sesuai PRD SPBU Monitoring System v1.0
 * Fitur: Relational Data Model, LocalStorage Persistence, Multi-Role Access, Audit Logging
 */

(function (window) {
  'use strict';

  const STORAGE_KEY = 'SPBU3_DATA_STORAGE_V1';

  // Master Data Awal (Demo Data sesuai PRD Section 13)
  const INITIAL_DATA = {
    users: [
      { id: 1, name: "Admin Pengelola Pusat", email: "admin@spbumonitor.test", role: "admin", phone: "0811-2233-4455", station_id: null, is_active: true, created_at: "2026-11-20 08:00" },
      { id: 2, name: "Ahmad Subarjo", email: "petugas@spbumonitor.test", role: "petugas", phone: "0812-3456-7890", station_id: "SPBU-34.124.01", is_active: true, created_at: "2026-11-20 08:30" },
      { id: 3, name: "Dedi Kurniawan", email: "petugas2@spbumonitor.test", role: "petugas", phone: "0813-8899-0011", station_id: "SPBU-32.171.04", is_active: true, created_at: "2026-11-20 09:00" },
      { id: 4, name: "Budi Santoso", email: "warga@spbumonitor.test", role: "warga", phone: "0819-5566-7788", station_id: null, is_active: true, created_at: "2026-11-21 10:15" },
      { id: 5, name: "Siti Rahmawati", email: "siti@warga.test", role: "warga", phone: "0857-1122-3344", station_id: null, is_active: true, created_at: "2026-11-22 14:00" }
    ],
    fuel_types: [
      { id: 1, name: "Pertalite", ron: "RON 90", description: "Bahan bakar bensin bersubsidi oktan 90 untuk kendaraan harian", price_per_liter: 10000, is_active: true, color: "#0284c7" },
      { id: 2, name: "Pertamax", ron: "RON 92", description: "Bensin tanpa timbal oktan 92 untuk mesin berteknologi tinggi", price_per_liter: 13700, is_active: true, color: "#2563eb" },
      { id: 3, name: "Pertamax Turbo", ron: "RON 98", description: "Bensin performa tinggi oktan 98 dengan Ignition Boost Formula", price_per_liter: 15400, is_active: true, color: "#dc2626" },
      { id: 4, name: "Biosolar", ron: "CN 48", description: "Bahan bakar solar bersubsidi campuran biodiesel B35", price_per_liter: 6800, is_active: true, color: "#16a34a" },
      { id: 5, name: "Dexlite", ron: "CN 51", description: "Bahan bakar diesel cetane 51 untuk mesin diesel modern hemat emisi", price_per_liter: 14200, is_active: true, color: "#7c3aed" },
      { id: 6, name: "Pertamina Dex", ron: "CN 53", description: "Bahan bakar diesel kualitas tertinggi dengan cetane number 53", price_per_liter: 15300, is_active: true, color: "#ea580c" }
    ],
    stations: [
      {
        id: "SPBU-34.124.01",
        code: "34.124.01",
        name: "SPBU Pertamina Cilandak",
        address: "Jl. TB Simatupang No. 12, Cilandak Barat",
        region: "Jakarta Selatan",
        latitude: -6.2925,
        longitude: 106.8042,
        phone: "(021) 7590-1234",
        opening_hours: "24 Jam",
        operational_status: "Buka",
        facility: "Toilet, Musholla, ATM Center, Nitrogen, Mini Market",
        deleted_at: null,
        created_at: "2026-11-15 09:00"
      },
      {
        id: "SPBU-34.124.07",
        code: "34.124.07",
        name: "SPBU Pertamina Fatmawati",
        address: "Jl. RS Fatmawati Raya No. 45, Cilandak",
        region: "Jakarta Selatan",
        latitude: -6.2750,
        longitude: 106.7930,
        phone: "(021) 7690-5678",
        opening_hours: "06:00 - 23:00",
        operational_status: "Buka",
        facility: "Toilet, Musholla, Nitrogen, Bright Store",
        deleted_at: null,
        created_at: "2026-11-15 09:30"
      },
      {
        id: "SPBU-31.121.02",
        code: "31.121.02",
        name: "SPBU Pertamina Cawang",
        address: "Jl. MT Haryono Kav. 8, Cawang",
        region: "Jakarta Timur",
        latitude: -6.2480,
        longitude: 106.8680,
        phone: "(021) 8590-2345",
        opening_hours: "24 Jam",
        operational_status: "Buka",
        facility: "Toilet, Musholla, Tambal Ban, Cafe Mini",
        deleted_at: null,
        created_at: "2026-11-15 10:00"
      },
      {
        id: "SPBU-31.121.11",
        code: "31.121.11",
        name: "SPBU Pertamina Pulogadung",
        address: "Jl. Raya Bekasi KM 21, Pulogadung",
        region: "Jakarta Timur",
        latitude: -6.1910,
        longitude: 106.9020,
        phone: "(021) 4890-3456",
        opening_hours: "06:00 - 22:00",
        operational_status: "Buka",
        facility: "Toilet, Musholla, Nitrogen, Ganti Oli",
        deleted_at: null,
        created_at: "2026-11-15 10:30"
      },
      {
        id: "SPBU-32.171.04",
        code: "32.171.04",
        name: "SPBU Pertamina Bekasi Barat",
        address: "Jl. Jend. Sudirman No. 88, Kranji",
        region: "Kota Bekasi",
        latitude: -6.2390,
        longitude: 106.9880,
        phone: "(021) 8890-4567",
        opening_hours: "24 Jam",
        operational_status: "Buka",
        facility: "Toilet, Musholla, ATM, Food Court Mini",
        deleted_at: null,
        created_at: "2026-11-15 11:00"
      },
      {
        id: "SPBU-32.171.09",
        code: "32.171.09",
        name: "SPBU Pertamina Cikarang",
        address: "Jl. Industri Pasir Gombong No. 15, Cikarang Utara",
        region: "Kabupaten Bekasi",
        latitude: -6.2820,
        longitude: 107.1490,
        phone: "(021) 8990-5678",
        opening_hours: "05:00 - 23:00",
        operational_status: "Buka",
        facility: "Toilet, Musholla, Rest Area Truk, Mini Market",
        deleted_at: null,
        created_at: "2026-11-15 11:30"
      },
      {
        id: "SPBU-32.173.05",
        code: "32.173.05",
        name: "SPBU Pertamina Bandung Timur",
        address: "Jl. Soekarno Hatta No. 590, Buahbatu",
        region: "Kota Bandung",
        latitude: -6.9420,
        longitude: 107.6450,
        phone: "(022) 7560-6789",
        opening_hours: "24 Jam",
        operational_status: "Buka",
        facility: "Toilet, Musholla, Cuci Mobil, Rest Area",
        deleted_at: null,
        created_at: "2026-11-15 12:00"
      },
      {
        id: "SPBU-35.174.02",
        code: "35.174.02",
        name: "SPBU Pertamina Surabaya Ahmad Yani",
        address: "Jl. Ahmad Yani No. 115, Wonokromo",
        region: "Kota Surabaya",
        latitude: -7.3150,
        longitude: 112.7360,
        phone: "(031) 8290-7890",
        opening_hours: "06:00 - 22:00",
        operational_status: "Perbaikan",
        facility: "Dalam proses perawatan tangki pendam",
        deleted_at: null,
        created_at: "2026-11-15 12:30"
      }
    ],
    fuel_stocks: [
      // SPBU Cilandak (34.124.01) - Normal & Aman
      { id: 101, station_id: "SPBU-34.124.01", fuel_type_id: 1, stock_liters: 24500, capacity_liters: 30000, low_stock_threshold: 4000, updated_by: "Ahmad Subarjo", updated_at: "2026-11-23 20:30" },
      { id: 102, station_id: "SPBU-34.124.01", fuel_type_id: 2, stock_liters: 14200, capacity_liters: 20000, low_stock_threshold: 3000, updated_by: "Ahmad Subarjo", updated_at: "2026-11-23 20:30" },
      { id: 103, station_id: "SPBU-34.124.01", fuel_type_id: 3, stock_liters: 7500, capacity_liters: 10000, low_stock_threshold: 1500, updated_by: "Ahmad Subarjo", updated_at: "2026-11-23 20:30" },
      { id: 104, station_id: "SPBU-34.124.01", fuel_type_id: 4, stock_liters: 25800, capacity_liters: 30000, low_stock_threshold: 5000, updated_by: "Ahmad Subarjo", updated_at: "2026-11-23 20:30" },
      { id: 105, station_id: "SPBU-34.124.01", fuel_type_id: 5, stock_liters: 8900, capacity_liters: 15000, low_stock_threshold: 2000, updated_by: "Ahmad Subarjo", updated_at: "2026-11-23 20:30" },

      // SPBU Fatmawati (34.124.07) - Sedang
      { id: 201, station_id: "SPBU-34.124.07", fuel_type_id: 1, stock_liters: 11500, capacity_liters: 25000, low_stock_threshold: 4000, updated_by: "Petugas Fatmawati", updated_at: "2026-11-23 21:00" },
      { id: 202, station_id: "SPBU-34.124.07", fuel_type_id: 2, stock_liters: 7600, capacity_liters: 20000, low_stock_threshold: 3000, updated_by: "Petugas Fatmawati", updated_at: "2026-11-23 21:00" },
      { id: 203, station_id: "SPBU-34.124.07", fuel_type_id: 4, stock_liters: 18200, capacity_liters: 25000, low_stock_threshold: 4000, updated_by: "Petugas Fatmawati", updated_at: "2026-11-23 21:00" },
      { id: 204, station_id: "SPBU-34.124.07", fuel_type_id: 5, stock_liters: 4400, capacity_liters: 10000, low_stock_threshold: 2000, updated_by: "Petugas Fatmawati", updated_at: "2026-11-23 21:00" },

      // SPBU Cawang (31.121.02) - Kritis / Stok Menipis (Pertalite sisa 1.800L)
      { id: 301, station_id: "SPBU-31.121.02", fuel_type_id: 1, stock_liters: 1800, capacity_liters: 35000, low_stock_threshold: 5000, updated_by: "Petugas Cawang", updated_at: "2026-11-23 21:45" },
      { id: 302, station_id: "SPBU-31.121.02", fuel_type_id: 2, stock_liters: 4200, capacity_liters: 25000, low_stock_threshold: 4000, updated_by: "Petugas Cawang", updated_at: "2026-11-23 21:45" },
      { id: 303, station_id: "SPBU-31.121.02", fuel_type_id: 4, stock_liters: 19500, capacity_liters: 30000, low_stock_threshold: 5000, updated_by: "Petugas Cawang", updated_at: "2026-11-23 21:45" },
      { id: 304, station_id: "SPBU-31.121.02", fuel_type_id: 5, stock_liters: 3100, capacity_liters: 15000, low_stock_threshold: 2000, updated_by: "Petugas Cawang", updated_at: "2026-11-23 21:45" },

      // SPBU Pulogadung (31.121.11)
      { id: 401, station_id: "SPBU-31.121.11", fuel_type_id: 1, stock_liters: 21300, capacity_liters: 30000, low_stock_threshold: 4000, updated_by: "Petugas Pulogadung", updated_at: "2026-11-23 19:20" },
      { id: 402, station_id: "SPBU-31.121.11", fuel_type_id: 2, stock_liters: 12400, capacity_liters: 20000, low_stock_threshold: 3000, updated_by: "Petugas Pulogadung", updated_at: "2026-11-23 19:20" },
      { id: 403, station_id: "SPBU-31.121.11", fuel_type_id: 4, stock_liters: 26500, capacity_liters: 30000, low_stock_threshold: 4000, updated_by: "Petugas Pulogadung", updated_at: "2026-11-23 19:20" },

      // SPBU Bekasi Barat (32.171.04) - Pertalite Habis (0L)!
      { id: 501, station_id: "SPBU-32.171.04", fuel_type_id: 1, stock_liters: 0, capacity_liters: 30000, low_stock_threshold: 4000, updated_by: "Dedi Kurniawan", updated_at: "2026-11-23 22:15" },
      { id: 502, station_id: "SPBU-32.171.04", fuel_type_id: 2, stock_liters: 2800, capacity_liters: 20000, low_stock_threshold: 3000, updated_by: "Dedi Kurniawan", updated_at: "2026-11-23 22:15" },
      { id: 503, station_id: "SPBU-32.171.04", fuel_type_id: 4, stock_liters: 12000, capacity_liters: 30000, low_stock_threshold: 4000, updated_by: "Dedi Kurniawan", updated_at: "2026-11-23 22:15" },
      { id: 504, station_id: "SPBU-32.171.04", fuel_type_id: 5, stock_liters: 3500, capacity_liters: 15000, low_stock_threshold: 2000, updated_by: "Dedi Kurniawan", updated_at: "2026-11-23 22:15" },

      // SPBU Cikarang (32.171.09)
      { id: 601, station_id: "SPBU-32.171.09", fuel_type_id: 1, stock_liters: 23100, capacity_liters: 35000, low_stock_threshold: 5000, updated_by: "Petugas Cikarang", updated_at: "2026-11-23 18:00" },
      { id: 602, station_id: "SPBU-32.171.09", fuel_type_id: 2, stock_liters: 16500, capacity_liters: 25000, low_stock_threshold: 3500, updated_by: "Petugas Cikarang", updated_at: "2026-11-23 18:00" },
      { id: 603, station_id: "SPBU-32.171.09", fuel_type_id: 4, stock_liters: 28900, capacity_liters: 35000, low_stock_threshold: 5000, updated_by: "Petugas Cikarang", updated_at: "2026-11-23 18:00" },

      // SPBU Bandung Timur (32.173.05)
      { id: 701, station_id: "SPBU-32.173.05", fuel_type_id: 1, stock_liters: 20700, capacity_liters: 30000, low_stock_threshold: 4000, updated_by: "Petugas Bandung", updated_at: "2026-11-23 20:00" },
      { id: 702, station_id: "SPBU-32.173.05", fuel_type_id: 2, stock_liters: 11400, capacity_liters: 20000, low_stock_threshold: 3000, updated_by: "Petugas Bandung", updated_at: "2026-11-23 20:00" },
      { id: 703, station_id: "SPBU-32.173.05", fuel_type_id: 4, stock_liters: 22100, capacity_liters: 30000, low_stock_threshold: 4000, updated_by: "Petugas Bandung", updated_at: "2026-11-23 20:00" },
      { id: 704, station_id: "SPBU-32.173.05", fuel_type_id: 5, stock_liters: 7600, capacity_liters: 15000, low_stock_threshold: 2000, updated_by: "Petugas Bandung", updated_at: "2026-11-23 20:00" },

      // SPBU Surabaya Ahmad Yani (35.174.02) - Tutup / Tangki Kosong untuk kalibrasi
      { id: 801, station_id: "SPBU-35.174.02", fuel_type_id: 1, stock_liters: 0, capacity_liters: 40000, low_stock_threshold: 5000, updated_by: "Petugas Surabaya", updated_at: "2026-11-23 16:00" },
      { id: 802, station_id: "SPBU-35.174.02", fuel_type_id: 2, stock_liters: 0, capacity_liters: 25000, low_stock_threshold: 3500, updated_by: "Petugas Surabaya", updated_at: "2026-11-23 16:00" },
      { id: 803, station_id: "SPBU-35.174.02", fuel_type_id: 4, stock_liters: 0, capacity_liters: 35000, low_stock_threshold: 5000, updated_by: "Petugas Surabaya", updated_at: "2026-11-23 16:00" }
    ],
    queue_statuses: [
      { id: 1, station_id: "SPBU-34.124.01", vehicle_category: "Semua Kendaraan", estimated_wait_minutes: 5, queue_level: "Lancar", source: "Petugas", updated_by: "Ahmad Subarjo", updated_at: "2026-11-23 22:30" },
      { id: 2, station_id: "SPBU-34.124.07", vehicle_category: "Semua Kendaraan", estimated_wait_minutes: 15, queue_level: "Sedang", source: "Sensor ATG", updated_by: "Sistem Otomatis", updated_at: "2026-11-23 22:20" },
      { id: 3, station_id: "SPBU-31.121.02", vehicle_category: "Semua Kendaraan", estimated_wait_minutes: 12, queue_level: "Sedang", source: "Petugas", updated_by: "Petugas Cawang", updated_at: "2026-11-23 22:10" },
      { id: 4, station_id: "SPBU-31.121.11", vehicle_category: "Semua Kendaraan", estimated_wait_minutes: 3, queue_level: "Lancar", source: "Simulasi", updated_by: "Sistem", updated_at: "2026-11-23 22:00" },
      { id: 5, station_id: "SPBU-32.171.04", vehicle_category: "Semua Kendaraan", estimated_wait_minutes: 30, queue_level: "Padat", source: "Petugas", updated_by: "Dedi Kurniawan", updated_at: "2026-11-23 22:35" },
      { id: 6, station_id: "SPBU-32.171.09", vehicle_category: "Semua Kendaraan", estimated_wait_minutes: 8, queue_level: "Lancar", source: "Sensor ATG", updated_by: "Sistem Otomatis", updated_at: "2026-11-23 22:05" },
      { id: 7, station_id: "SPBU-32.173.05", vehicle_category: "Semua Kendaraan", estimated_wait_minutes: 6, queue_level: "Lancar", source: "Petugas", updated_by: "Petugas Bandung", updated_at: "2026-11-23 22:25" },
      { id: 8, station_id: "SPBU-35.174.02", vehicle_category: "Semua Kendaraan", estimated_wait_minutes: 0, queue_level: "Tutup", source: "Petugas", updated_by: "Petugas Surabaya", updated_at: "2026-11-23 16:00" }
    ],
    citizen_reports: [
      {
        id: "LAP-20261120-001",
        report_code: "LAP-20261120-001",
        user_id: 4,
        user_name: "Budi Santoso",
        station_id: "SPBU-31.121.02",
        station_name: "SPBU Pertamina Cawang",
        category: "Antrean panjang",
        description: "Antrean sepeda motor mengular hingga keluar trotoar jalan raya MT Haryono. Mohon buka nozzle Pertalite tambahan.",
        photo_url: "https://images.unsplash.com/photo-1545454675-3531b543be5d?w=600&auto=format&fit=crop&q=80",
        status: "Selesai",
        admin_note: "Dispenser 3 & 4 jalur motor sudah diaktifkan kembali. Kondisi lalu lintas antrean kembali lancar.",
        handled_by: "Admin Pengelola Pusat",
        created_at: "2026-11-20 09:15",
        updated_at: "2026-11-20 11:30"
      },
      {
        id: "LAP-20261121-002",
        report_code: "LAP-20261121-002",
        user_id: 4,
        user_name: "Budi Santoso",
        station_id: "SPBU-32.171.04",
        station_name: "SPBU Pertamina Bekasi Barat",
        category: "BBM habis",
        description: "Pertalite habis sekitar jam 21.00 tadi malam, banyak driver ojek online terpaksa putar balik.",
        photo_url: "https://images.unsplash.com/photo-1527018607616-a656a38047a7?w=600&auto=format&fit=crop&q=80",
        status: "Ditindaklanjuti",
        admin_note: "Dropping BBM dari TBBM Plumpang telah dijadwalkan tiba pukul 23:30 malam ini.",
        handled_by: "Admin Pengelola Pusat",
        created_at: "2026-11-21 21:20",
        updated_at: "2026-11-21 22:00"
      },
      {
        id: "LAP-20261122-003",
        report_code: "LAP-20261122-003",
        user_id: 5,
        user_name: "Siti Rahmawati",
        station_id: "SPBU-34.124.07",
        station_name: "SPBU Pertamina Fatmawati",
        category: "Antrean panjang",
        description: "Antrean mobil Pertamax panjang karena hanya 1 mesin dispenser yang menerima pembayaran non-tunai.",
        photo_url: "https://images.unsplash.com/photo-1578632767115-351597cf2477?w=600&auto=format&fit=crop&q=80",
        status: "Diverifikasi",
        admin_note: "Laporan terverifikasi. Petugas IT sedang memeriksa mesin EDC di pulau pompa 2.",
        handled_by: "Admin Pengelola Pusat",
        created_at: "2026-11-22 14:10",
        updated_at: "2026-11-22 15:00"
      },
      {
        id: "LAP-20261122-004",
        report_code: "LAP-20261122-004",
        user_id: 4,
        user_name: "Budi Santoso",
        station_id: "SPBU-34.124.01",
        station_name: "SPBU Pertamina Cilandak",
        category: "Informasi stok tidak sesuai",
        description: "Di dashboard aplikasi tertulis Dexlite masih ada, namun saat sampai di lokasi papan dispenser tertulis Kosong.",
        photo_url: "",
        status: "Menunggu",
        admin_note: "",
        handled_by: null,
        created_at: "2026-11-22 17:45",
        updated_at: "2026-11-22 17:45"
      },
      {
        id: "LAP-20261123-005",
        report_code: "LAP-20261123-005",
        user_id: 5,
        user_name: "Siti Rahmawati",
        station_id: "SPBU-32.173.05",
        station_name: "SPBU Pertamina Bandung Timur",
        category: "Fasilitas bermasalah",
        description: "Kran air dan toilet samping musholla kotor serta lampu penerangan redup di malam hari.",
        photo_url: "",
        status: "Menunggu",
        admin_note: "",
        handled_by: null,
        created_at: "2026-11-23 18:30",
        updated_at: "2026-11-23 18:30"
      },
      {
        id: "LAP-20261123-006",
        report_code: "LAP-20261123-006",
        user_id: 4,
        user_name: "Budi Santoso",
        station_id: "SPBU-35.174.02",
        station_name: "SPBU Pertamina Surabaya Ahmad Yani",
        category: "SPBU tutup",
        description: "SPBU tertutup cone oranye di pintu masuk, tidak ada kejelasan kapan buka kembali.",
        photo_url: "",
        status: "Selesai",
        admin_note: "SPBU sedang dalam jadwal pemeliharaan dan uji tera berkala oleh Dinas Metrologi.",
        handled_by: "Admin Pengelola Pusat",
        created_at: "2026-11-23 19:00",
        updated_at: "2026-11-23 19:40"
      },
      {
        id: "LAP-20261123-007",
        report_code: "LAP-20261123-007",
        user_id: 5,
        user_name: "Siti Rahmawati",
        station_id: "SPBU-32.171.09",
        station_name: "SPBU Pertamina Cikarang",
        category: "Antrean panjang",
        description: "Banyak truk industri antre Biosolar sehingga menutupi jalur keluar mobil pribadi.",
        photo_url: "https://images.unsplash.com/photo-1601584115197-04ecc0da31d7?w=600&auto=format&fit=crop&q=80",
        status: "Diverifikasi",
        admin_note: "Petugas keamanan lapangan telah diarahkan mengatur jalur khusus truk kontainer.",
        handled_by: "Admin Pengelola Pusat",
        created_at: "2026-11-23 19:50",
        updated_at: "2026-11-23 20:15"
      },
      {
        id: "LAP-20261123-008",
        report_code: "LAP-20261123-008",
        user_id: 4,
        user_name: "Budi Santoso",
        station_id: "SPBU-31.121.11",
        station_name: "SPBU Pertamina Pulogadung",
        category: "Fasilitas bermasalah",
        description: "Mesin pengisian angin nitrogen bocor dan jarum meteran tekanan tidak akurat.",
        photo_url: "",
        status: "Selesai",
        admin_note: "Vendor nitrogen rekanan telah mengganti selang nozzle dan melakukan kalibrasi manometer.",
        handled_by: "Admin Pengelola Pusat",
        created_at: "2026-11-23 20:10",
        updated_at: "2026-11-23 21:00"
      },
      {
        id: "LAP-20261123-009",
        report_code: "LAP-20261123-009",
        user_id: 5,
        user_name: "Siti Rahmawati",
        station_id: "SPBU-34.124.01",
        station_name: "SPBU Pertamina Cilandak",
        category: "BBM habis",
        description: "Stok Pertamax habis sementara waktu saat jam pulang kantor sore tadi.",
        photo_url: "",
        status: "Ditindaklanjuti",
        admin_note: "Bongkar muatan truk tangki MT-8802 selesai dilaksanakan, penjualan normal kembali.",
        handled_by: "Admin Pengelola Pusat",
        created_at: "2026-11-23 20:40",
        updated_at: "2026-11-23 21:15"
      },
      {
        id: "LAP-20261123-010",
        report_code: "LAP-20261123-010",
        user_id: 4,
        user_name: "Budi Santoso",
        station_id: "SPBU-32.171.04",
        station_name: "SPBU Pertamina Bekasi Barat",
        category: "Lainnya",
        description: "Struk pembelian tidak tercetak otomatis di dispenser kasir 2.",
        photo_url: "",
        status: "Ditolak",
        admin_note: "Kertas roll struk telah diganti dan struk cetak ulang telah diserahkan di pos pengawas.",
        handled_by: "Admin Pengelola Pusat",
        created_at: "2026-11-23 21:30",
        updated_at: "2026-11-23 22:00"
      }
    ],
    stock_histories: [
      { id: 1, fuel_stock_id: 101, station_id: "SPBU-34.124.01", fuel_name: "Pertalite", previous_stock: 16500, new_stock: 24500, change_amount: 8000, note: "Penerimaan dropping truk tangki TBBM Plumpang", updated_by: "Ahmad Subarjo", created_at: "2026-11-23 15:30" },
      { id: 2, fuel_stock_id: 301, station_id: "SPBU-31.121.02", fuel_name: "Pertalite", previous_stock: 5200, new_stock: 1800, change_amount: -3400, note: "Penyaluran intensif jam sibuk sore", updated_by: "Petugas Cawang", created_at: "2026-11-23 21:45" },
      { id: 3, fuel_stock_id: 501, station_id: "SPBU-32.171.04", fuel_name: "Pertalite", previous_stock: 2100, new_stock: 0, change_amount: -2100, note: "Stok habis total, menunggu DO malam", updated_by: "Dedi Kurniawan", created_at: "2026-11-23 22:15" },
      { id: 4, fuel_stock_id: 701, station_id: "SPBU-32.173.05", fuel_name: "Pertalite", previous_stock: 26000, new_stock: 20700, change_amount: -5300, note: "Penjualan harian shift 1 & 2", updated_by: "Petugas Bandung", created_at: "2026-11-23 20:00" }
    ],
    activity_logs: [
      { id: 1, user_name: "System", user_role: "system", action: "INIT", entity_type: "Database", entity_id: "-", description: "Inisialisasi sistem database demo SPBU Monitoring", created_at: "2026-11-23 00:00" },
      { id: 2, user_name: "Ahmad Subarjo", user_role: "petugas", action: "UPDATE_STOCK", entity_type: "Stok BBM", entity_id: "101", description: "Menambah stok Pertalite SPBU Cilandak +8.000L", created_at: "2026-11-23 15:30" },
      { id: 3, user_name: "Admin Pengelola Pusat", user_role: "admin", action: "VERIFY_REPORT", entity_type: "Laporan Warga", entity_id: "LAP-20261122-003", description: "Memverifikasi laporan kendala antrean SPBU Fatmawati", created_at: "2026-11-23 15:00" },
      { id: 4, user_name: "Dedi Kurniawan", user_role: "petugas", action: "UPDATE_QUEUE", entity_type: "Antrean", entity_id: "SPBU-32.171.04", description: "Mengubah status antrean SPBU Bekasi Barat menjadi Padat (30 mnt)", created_at: "2026-11-23 22:35" }
    ],
    currentUser: {
      id: 1,
      name: "Admin Pengelola Pusat",
      email: "admin@spbumonitor.test",
      role: "admin",
      phone: "0811-2233-4455",
      station_id: null
    }
  };

  class SPBUStore {
    constructor() {
      this.listeners = [];
      this.init();
    }

    init() {
      try {
        const raw = localStorage.getItem(STORAGE_KEY);
        if (!raw) {
          this.data = JSON.parse(JSON.stringify(INITIAL_DATA));
          this.save();
        } else {
          this.data = JSON.parse(raw);
          // Migration safety: ensure all keys exist
          for (let key in INITIAL_DATA) {
            if (!this.data[key]) {
              this.data[key] = JSON.parse(JSON.stringify(INITIAL_DATA[key]));
            }
          }
        }
      } catch (e) {
        console.error("Gagal load storage SPBU3:", e);
        this.data = JSON.parse(JSON.stringify(INITIAL_DATA));
      }
    }

    save() {
      try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(this.data));
        this.notify();
      } catch (e) {
        console.error("Gagal save storage SPBU3:", e);
      }
    }

    subscribe(fn) {
      if (typeof fn === 'function') {
        this.listeners.push(fn);
      }
    }

    notify() {
      this.listeners.forEach(fn => {
        try { fn(this.data); } catch (e) { console.error(e); }
      });
    }

    resetDefaults() {
      this.data = JSON.parse(JSON.stringify(INITIAL_DATA));
      this.save();
      this.logActivity("RESET", "Sistem", "-", "Reset database ke kondisi awal demo");
      return true;
    }

    // ================== USER & AUTH SIMULATION ==================
    getUsers() {
      return this.data.users || [];
    }

    getCurrentUser() {
      return this.data.currentUser || this.data.users[0];
    }

    setCurrentUser(userIdOrRole) {
      let targetUser = null;
      if (typeof userIdOrRole === 'number') {
        targetUser = this.data.users.find(u => u.id === userIdOrRole);
      } else if (typeof userIdOrRole === 'string') {
        targetUser = this.data.users.find(u => u.role === userIdOrRole || u.email === userIdOrRole);
      }
      if (targetUser) {
        this.data.currentUser = JSON.parse(JSON.stringify(targetUser));
        this.save();
        this.logActivity("SWITCH_USER", "Pengguna", String(targetUser.id), `Beralih peran ke: ${targetUser.name} (${targetUser.role.toUpperCase()})`);
      }
      return this.data.currentUser;
    }

    addUser(userData) {
      const newId = (this.data.users.length ? Math.max(...this.data.users.map(u => u.id)) : 0) + 1;
      const newUser = {
        id: newId,
        name: userData.name || "User Baru",
        email: userData.email,
        phone: userData.phone || "-",
        role: userData.role || "petugas",
        station_id: userData.station_id || null,
        is_active: true,
        created_at: new Date().toISOString().replace('T', ' ').substring(0, 16)
      };
      this.data.users.push(newUser);
      this.save();
      this.logActivity("CREATE", "Pengguna", String(newId), `Menambahkan pengguna baru: ${newUser.name} (${newUser.role})`);
      return newUser;
    }

    updateUser(id, updates) {
      const idx = this.data.users.findIndex(u => u.id === Number(id));
      if (idx !== -1) {
        this.data.users[idx] = { ...this.data.users[idx], ...updates };
        if (this.data.currentUser.id === Number(id)) {
          this.data.currentUser = { ...this.data.currentUser, ...updates };
        }
        this.save();
        this.logActivity("UPDATE", "Pengguna", String(id), `Memperbarui data pengguna: ${this.data.users[idx].name}`);
        return this.data.users[idx];
      }
      return null;
    }

    deleteUser(id) {
      const user = this.data.users.find(u => u.id === Number(id));
      if (!user) return false;
      this.data.users = this.data.users.filter(u => u.id !== Number(id));
      this.save();
      this.logActivity("DELETE", "Pengguna", String(id), `Menghapus pengguna: ${user.name}`);
      return true;
    }

    // ================== SPBU STATIONS CRUD ==================
    getStations(includeDeleted = false) {
      if (includeDeleted) return this.data.stations || [];
      return (this.data.stations || []).filter(s => !s.deleted_at);
    }

    getStationById(id) {
      return (this.data.stations || []).find(s => s.id === id || s.code === id);
    }

    addStation(stationData) {
      const code = stationData.code.trim();
      const id = `SPBU-${code}`;
      const newStation = {
        id: id,
        code: code,
        name: stationData.name.trim(),
        address: stationData.address.trim(),
        region: stationData.region.trim(),
        latitude: parseFloat(stationData.latitude) || -6.2,
        longitude: parseFloat(stationData.longitude) || 106.8,
        phone: stationData.phone ? stationData.phone.trim() : "-",
        opening_hours: stationData.opening_hours || "24 Jam",
        operational_status: stationData.operational_status || "Buka",
        facility: stationData.facility || "Toilet, Musholla",
        deleted_at: null,
        created_at: new Date().toISOString().replace('T', ' ').substring(0, 16)
      };

      this.data.stations.push(newStation);

      // Auto inisialisasi default stok & status antrean
      const defaultFuelTypes = this.getFuelTypes();
      defaultFuelTypes.slice(0, 4).forEach((ft, i) => {
        const stockId = Date.now() + i;
        this.data.fuel_stocks.push({
          id: stockId,
          station_id: id,
          fuel_type_id: ft.id,
          stock_liters: 15000,
          capacity_liters: 25000,
          low_stock_threshold: 3000,
          updated_by: this.getCurrentUser().name,
          updated_at: new Date().toISOString().replace('T', ' ').substring(0, 16)
        });
      });

      this.data.queue_statuses.push({
        id: Date.now() + 99,
        station_id: id,
        vehicle_category: "Semua Kendaraan",
        estimated_wait_minutes: 5,
        queue_level: "Lancar",
        source: "Petugas",
        updated_by: this.getCurrentUser().name,
        updated_at: new Date().toISOString().replace('T', ' ').substring(0, 16)
      });

      this.save();
      this.logActivity("CREATE", "SPBU", id, `Menambahkan SPBU baru: ${newStation.name} (${newStation.code})`);
      return newStation;
    }

    updateStation(id, updates) {
      const idx = this.data.stations.findIndex(s => s.id === id);
      if (idx !== -1) {
        this.data.stations[idx] = { ...this.data.stations[idx], ...updates };
        this.save();
        this.logActivity("UPDATE", "SPBU", id, `Memperbarui data SPBU: ${this.data.stations[idx].name}`);
        return this.data.stations[idx];
      }
      return null;
    }

    deleteStation(id) {
      const idx = this.data.stations.findIndex(s => s.id === id);
      if (idx !== -1) {
        // Soft delete
        this.data.stations[idx].deleted_at = new Date().toISOString().replace('T', ' ').substring(0, 16);
        this.save();
        this.logActivity("DELETE", "SPBU", id, `Menonaktifkan / Soft delete SPBU: ${this.data.stations[idx].name}`);
        return true;
      }
      return false;
    }

    toggleStationStatus(id) {
      const st = this.getStationById(id);
      if (st) {
        const nextStatus = st.operational_status === 'Buka' ? 'Tutup' : (st.operational_status === 'Tutup' ? 'Perbaikan' : 'Buka');
        st.operational_status = nextStatus;
        this.save();
        this.logActivity("UPDATE_STATUS", "SPBU", id, `Mengubah status operasional ${st.name} ke: ${nextStatus}`);
        return nextStatus;
      }
      return null;
    }

    // ================== FUEL TYPES CRUD ==================
    getFuelTypes(activeOnly = true) {
      if (activeOnly) {
        return (this.data.fuel_types || []).filter(f => f.is_active);
      }
      return this.data.fuel_types || [];
    }

    getFuelTypeById(id) {
      return (this.data.fuel_types || []).find(f => f.id === Number(id));
    }

    addFuelType(data) {
      const newId = (this.data.fuel_types.length ? Math.max(...this.data.fuel_types.map(f => f.id)) : 0) + 1;
      const newType = {
        id: newId,
        name: data.name.trim(),
        ron: data.ron || "Oktan/Cetane",
        description: data.description || "",
        price_per_liter: parseInt(data.price_per_liter) || 10000,
        is_active: true,
        color: data.color || "#0284c7"
      };
      this.data.fuel_types.push(newType);
      this.save();
      this.logActivity("CREATE", "Jenis BBM", String(newId), `Menambahkan jenis BBM: ${newType.name} (Rp ${newType.price_per_liter.toLocaleString('id-ID')})`);
      return newType;
    }

    updateFuelType(id, updates) {
      const idx = this.data.fuel_types.findIndex(f => f.id === Number(id));
      if (idx !== -1) {
        const oldPrice = this.data.fuel_types[idx].price_per_liter;
        this.data.fuel_types[idx] = { ...this.data.fuel_types[idx], ...updates };
        this.save();
        let desc = `Memperbarui info jenis BBM ${this.data.fuel_types[idx].name}`;
        if (updates.price_per_liter && updates.price_per_liter !== oldPrice) {
          desc = `Penyesuaian harga ${this.data.fuel_types[idx].name}: Rp ${oldPrice.toLocaleString('id-ID')} -> Rp ${updates.price_per_liter.toLocaleString('id-ID')}`;
        }
        this.logActivity("UPDATE", "Jenis BBM", String(id), desc);
        return this.data.fuel_types[idx];
      }
      return null;
    }

    deleteFuelType(id) {
      const idx = this.data.fuel_types.findIndex(f => f.id === Number(id));
      if (idx !== -1) {
        // Toggle is_active instead of hard delete to preserve history
        this.data.fuel_types[idx].is_active = !this.data.fuel_types[idx].is_active;
        const status = this.data.fuel_types[idx].is_active ? "diaktifkan" : "dinonaktifkan";
        this.save();
        this.logActivity("STATUS", "Jenis BBM", String(id), `BBM ${this.data.fuel_types[idx].name} ${status}`);
        return true;
      }
      return false;
    }

    // ================== FUEL STOCKS CRUD & HISTORIES ==================
    getStocks(stationId = null) {
      let list = this.data.fuel_stocks || [];
      if (stationId) {
        list = list.filter(s => s.station_id === stationId);
      }
      return list.map(stock => {
        const fuelType = this.getFuelTypeById(stock.fuel_type_id) || { name: "BBM", price_per_liter: 0, color: "#94a3b8" };
        const station = this.getStationById(stock.station_id) || { name: "SPBU", region: "-" };

        let status = 'Tersedia';
        if (stock.stock_liters <= 0) {
          status = 'Habis';
        } else if (stock.stock_liters <= stock.low_stock_threshold) {
          status = 'Menipis';
        }

        const percentage = stock.capacity_liters > 0 ? Math.round((stock.stock_liters / stock.capacity_liters) * 100) : 0;

        return {
          ...stock,
          fuel_name: fuelType.name,
          fuel_ron: fuelType.ron,
          fuel_price: fuelType.price_per_liter,
          fuel_color: fuelType.color,
          station_name: station.name,
          station_region: station.region,
          status: status,
          percentage: percentage
        };
      });
    }

    updateStock(stockId, newLiters, note = "Pembaruan manual petugas") {
      const idx = this.data.fuel_stocks.findIndex(s => s.id === Number(stockId));
      if (idx === -1) return { success: false, message: "Catatan stok tidak ditemukan" };

      const item = this.data.fuel_stocks[idx];
      const parsedLiters = parseInt(newLiters);

      if (isNaN(parsedLiters) || parsedLiters < 0) {
        return { success: false, message: "Jumlah stok tidak boleh negatif!" };
      }
      if (parsedLiters > item.capacity_liters) {
        return { success: false, message: `Stok melebihi kapasitas maksimum (${item.capacity_liters.toLocaleString('id-ID')} L)!` };
      }

      const prev = item.stock_liters;
      const diff = parsedLiters - prev;
      const currentUser = this.getCurrentUser();
      const fuelType = this.getFuelTypeById(item.fuel_type_id);

      item.stock_liters = parsedLiters;
      item.updated_by = currentUser.name;
      item.updated_at = new Date().toISOString().replace('T', ' ').substring(0, 16);

      // Simpan histori perubahan
      const historyRecord = {
        id: (this.data.stock_histories.length ? Math.max(...this.data.stock_histories.map(h => h.id)) : 0) + 1,
        fuel_stock_id: item.id,
        station_id: item.station_id,
        fuel_name: fuelType ? fuelType.name : "BBM",
        previous_stock: prev,
        new_stock: parsedLiters,
        change_amount: diff,
        note: note,
        updated_by: currentUser.name,
        created_at: new Date().toISOString().replace('T', ' ').substring(0, 16)
      };
      this.data.stock_histories.unshift(historyRecord);

      this.save();
      this.logActivity("UPDATE_STOCK", "Stok BBM", String(item.id), `Update stok ${fuelType?.name || 'BBM'} di ${item.station_id}: ${prev.toLocaleString()}L -> ${parsedLiters.toLocaleString()}L (${diff >= 0 ? '+' : ''}${diff.toLocaleString()}L)`);

      return { success: true, item: item };
    }

    getStockHistories(stationId = null) {
      let list = this.data.stock_histories || [];
      if (stationId) {
        list = list.filter(h => h.station_id === stationId);
      }
      return list;
    }

    // ================== QUEUE STATUSES ==================
    getQueueStatus(stationId) {
      const q = (this.data.queue_statuses || []).find(item => item.station_id === stationId);
      if (q) return q;
      return {
        station_id: stationId,
        vehicle_category: "Semua Kendaraan",
        estimated_wait_minutes: 5,
        queue_level: "Lancar",
        source: "Simulasi",
        updated_by: "Sistem",
        updated_at: new Date().toISOString().replace('T', ' ').substring(0, 16)
      };
    }

    updateQueueStatus(stationId, updates) {
      let idx = (this.data.queue_statuses || []).findIndex(q => q.station_id === stationId);
      const currentUser = this.getCurrentUser();

      const payload = {
        station_id: stationId,
        vehicle_category: updates.vehicle_category || "Semua Kendaraan",
        estimated_wait_minutes: parseInt(updates.estimated_wait_minutes) || 5,
        queue_level: updates.queue_level || "Lancar",
        source: updates.source || (currentUser.role === 'petugas' ? 'Petugas' : 'Simulasi'),
        updated_by: currentUser.name,
        updated_at: new Date().toISOString().replace('T', ' ').substring(0, 16)
      };

      if (idx !== -1) {
        this.data.queue_statuses[idx] = { ...this.data.queue_statuses[idx], ...payload };
      } else {
        payload.id = Date.now();
        this.data.queue_statuses.push(payload);
      }

      this.save();
      this.logActivity("UPDATE_QUEUE", "Antrean", stationId, `Update antrean SPBU: ${payload.queue_level} (Est. ${payload.estimated_wait_minutes} menit)`);
      return payload;
    }

    // ================== CITIZEN REPORTS CRUD ==================
    getReports(filterStationId = null, filterStatus = null) {
      let list = this.data.citizen_reports || [];
      if (filterStationId) {
        list = list.filter(r => r.station_id === filterStationId);
      }
      if (filterStatus && filterStatus !== 'Semua') {
        list = list.filter(r => r.status === filterStatus);
      }
      return list;
    }

    getReportById(id) {
      return (this.data.citizen_reports || []).find(r => r.id === id || r.report_code === id);
    }

    addReport(reportData) {
      const dateStr = new Date().toISOString().slice(0, 10).replace(/-/g, '');
      const count = (this.data.citizen_reports.length + 1).toString().padStart(3, '0');
      const code = `LAP-${dateStr}-${count}`;

      const station = this.getStationById(reportData.station_id);
      const currentUser = this.getCurrentUser();

      const newReport = {
        id: code,
        report_code: code,
        user_id: currentUser.id || 4,
        user_name: reportData.user_name || currentUser.name || "Warga",
        station_id: reportData.station_id,
        station_name: station ? station.name : "SPBU Umum",
        category: reportData.category || "Antrean panjang",
        description: reportData.description.trim(),
        photo_url: reportData.photo_url || "",
        status: "Menunggu",
        admin_note: "",
        handled_by: null,
        created_at: new Date().toISOString().replace('T', ' ').substring(0, 16),
        updated_at: new Date().toISOString().replace('T', ' ').substring(0, 16)
      };

      this.data.citizen_reports.unshift(newReport);
      this.save();
      this.logActivity("CREATE_REPORT", "Laporan Warga", code, `Laporan warga baru #${code} diajukan untuk ${newReport.station_name}`);
      return newReport;
    }

    updateReportStatus(reportId, newStatus, adminNote = "") {
      const idx = this.data.citizen_reports.findIndex(r => r.id === reportId || r.report_code === reportId);
      if (idx !== -1) {
        const currentUser = this.getCurrentUser();
        this.data.citizen_reports[idx].status = newStatus;
        if (adminNote) {
          this.data.citizen_reports[idx].admin_note = adminNote;
        }
        this.data.citizen_reports[idx].handled_by = currentUser.name;
        this.data.citizen_reports[idx].updated_at = new Date().toISOString().replace('T', ' ').substring(0, 16);

        this.save();
        this.logActivity("UPDATE_REPORT", "Laporan Warga", reportId, `Status laporan #${reportId} diubah ke: ${newStatus}`);
        return this.data.citizen_reports[idx];
      }
      return null;
    }

    deleteReport(reportId) {
      const item = this.getReportById(reportId);
      if (!item) return false;
      this.data.citizen_reports = this.data.citizen_reports.filter(r => r.id !== reportId && r.report_code !== reportId);
      this.save();
      this.logActivity("DELETE_REPORT", "Laporan Warga", reportId, `Menghapus laporan #${reportId}`);
      return true;
    }

    // ================== AUDIT LOGS ==================
    logActivity(action, entityType, entityId, description) {
      const currentUser = this.getCurrentUser();
      const newLog = {
        id: (this.data.activity_logs.length ? Math.max(...this.data.activity_logs.map(l => l.id)) : 0) + 1,
        user_name: currentUser.name || "System",
        user_role: currentUser.role || "system",
        action: action,
        entity_type: entityType,
        entity_id: String(entityId),
        description: description,
        created_at: new Date().toISOString().replace('T', ' ').substring(0, 16)
      };
      this.data.activity_logs.unshift(newLog);
      // Keep max 200 logs
      if (this.data.activity_logs.length > 200) {
        this.data.activity_logs = this.data.activity_logs.slice(0, 200);
      }
      this.save();
    }

    getActivityLogs() {
      return this.data.activity_logs || [];
    }

    // ================== REAL-TIME TICK SIMULATION ==================
    simulateLiveTick() {
      // Modifikasi kecil pada antrean dan sedikit konsumsi stok untuk demonstrasi real-time
      const openStations = this.getStations().filter(s => s.operational_status === 'Buka');
      if (openStations.length === 0) return;

      const randomStation = openStations[Math.floor(Math.random() * openStations.length)];
      const queues = ['Lancar', 'Sedang', 'Padat'];
      const randomLevel = queues[Math.floor(Math.random() * queues.length)];
      const waitTime = randomLevel === 'Lancar' ? Math.floor(Math.random() * 5) + 3 : (randomLevel === 'Sedang' ? Math.floor(Math.random() * 10) + 10 : Math.floor(Math.random() * 15) + 20);

      this.updateQueueStatus(randomStation.id, {
        vehicle_category: "Semua Kendaraan",
        estimated_wait_minutes: waitTime,
        queue_level: randomLevel,
        source: "Sensor ATG"
      });
    }
  }

  // Global Singleton Instance
  window.SPBUStore = new SPBUStore();

})(window);
