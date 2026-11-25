<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Admin & Monitoring Dashboard — SPBU Monitoring System</title>
  <!-- Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <!-- Leaflet CSS & JS -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <!-- Custom Styling -->
  <script>
    tailwind.config = {
      darkMode: 'class',
      theme: {
        extend: {
          colors: {
            brand: {
              50: '#f0f7ff',
              100: '#e0effe',
              500: '#0284c7',
              600: '#0369a1',
              700: '#075985',
              800: '#0c4a6e',
              900: '#082f49',
              950: '#031c2d',
            }
          }
        }
      }
    }
  </script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');
    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
    }
    .custom-scrollbar::-webkit-scrollbar {
      width: 6px;
      height: 6px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
      background: #0f172a;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
      background: #334155;
      border-radius: 4px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
      background: #475569;
    }
    .leaflet-popup-content-wrapper {
      background: #0f172a;
      color: #f1f5f9;
      border-radius: 0.75rem;
      border: 1px solid #334155;
      box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5);
    }
    .leaflet-popup-tip {
      background: #0f172a;
    }
  </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen flex flex-col antialiased selection:bg-sky-500 selection:text-white">

  <!-- TOP BANNER: DEMO SIMULATION NOTICE (Sesuai PRD Section 1) -->
  <div class="bg-gradient-to-r from-amber-600 via-amber-500 to-amber-600 text-slate-950 px-4 py-1.5 text-xs font-semibold flex items-center justify-between shadow-sm">
    <div class="flex items-center space-x-2">
      <span class="inline-flex items-center justify-center bg-slate-950 text-amber-400 text-[10px] uppercase font-bold px-1.5 py-0.5 rounded">Demo</span>
      <span>Perhatian: Data yang disajikan merupakan <strong>data simulasi / prototype</strong> untuk kebutuhan operasional monitoring Smart City & audit.</span>
    </div>
    <div class="hidden md:flex items-center space-x-4">
      <span id="liveClock" class="font-mono text-slate-900">--:--:-- WIB</span>
      <button onclick="SPBUApp.resetAllDemo()" class="hover:underline flex items-center space-x-1 font-bold text-slate-900">
        <i class="fa-solid fa-rotate-left"></i>
        <span>Reset Data Demo</span>
      </button>
    </div>
  </div>

  <!-- MAIN TOP NAVBAR -->
  <header class="bg-slate-900 border-b border-slate-800 sticky top-0 z-40">
    <div class="px-4 lg:px-6 h-16 flex items-center justify-between">
      
      <!-- Left: Logo & Sidebar Toggle -->
      <div class="flex items-center space-x-3">
        <button id="sidebarToggleBtn" class="lg:hidden text-slate-400 hover:text-white p-2 rounded-lg hover:bg-slate-800">
          <i class="fa-solid fa-bars text-lg"></i>
        </button>
        <div class="flex items-center space-x-3">
          <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-sky-600 to-cyan-400 flex items-center justify-center shadow-lg shadow-sky-500/20 text-white font-black text-xl">
            <i class="fa-solid fa-gas-pump"></i>
          </div>
          <div>
            <div class="flex items-center space-x-2">
              <span class="font-extrabold text-lg text-white tracking-tight">SPBU<span class="text-sky-400">Monitor</span></span>
              <span class="text-[10px] bg-sky-500/10 text-sky-400 border border-sky-500/30 px-1.5 py-0.5 rounded-full font-bold">Smart City Hub</span>
            </div>
            <p class="text-[11px] text-slate-400 hidden sm:block">Sistem Monitoring Terpusat & Pengawasan Stok Minyak SPBU</p>
          </div>
        </div>
      </div>

      <!-- Right: Role Switcher, Simulation Status & Action Links -->
      <div class="flex items-center space-x-2 sm:space-x-3">
        
        <!-- Live IoT Sensor Simulation Toggle -->
        <button id="simTickBtn" onclick="SPBUApp.toggleLiveSimulation()" class="hidden md:flex items-center space-x-2 px-3 py-1.5 text-xs font-semibold rounded-lg border border-emerald-500/30 bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500/20 transition">
          <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
          <span id="simStatusText">Sensor Simulasi: Aktif</span>
        </button>

        <!-- Link to Warga Mobile Portal -->
        <a href="{{ route('warga') }}" target="_blank" class="flex items-center space-x-2 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white px-3 py-1.5 rounded-lg text-xs font-bold shadow-md shadow-emerald-900/30 transition">
          <i class="fa-solid fa-mobile-screen"></i>
          <span class="hidden sm:inline">Portal Warga (Mobile)</span>
          <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
        </a>

        <!-- Role Switcher Dropdown (PRD Multi-Role) -->
        <div class="relative">
          <button id="roleDropdownBtn" onclick="SPBUApp.toggleRoleMenu()" class="flex items-center space-x-2 bg-slate-800 hover:bg-slate-700 border border-slate-700 px-3 py-1.5 rounded-lg text-xs font-medium text-slate-200 transition">
            <span id="currentRoleBadge" class="w-2 h-2 rounded-full bg-sky-400"></span>
            <span id="currentUserName">Admin Pusat</span>
            <i class="fa-solid fa-chevron-down text-[10px] text-slate-400"></i>
          </button>
          
          <!-- Role Menu -->
          <div id="roleDropdownMenu" class="hidden absolute right-0 mt-2 w-64 bg-slate-900 border border-slate-700 rounded-xl shadow-2xl py-2 z-50 text-xs">
            <div class="px-3 py-1.5 border-b border-slate-800 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
              Ganti Peran / Akun Uji Coba
            </div>
            <button onclick="SPBUApp.switchUser(1)" class="w-full text-left px-3 py-2 hover:bg-slate-800 flex items-center justify-between text-slate-200">
              <div>
                <div class="font-bold text-sky-400">Admin Logistik Pusat</div>
                <div class="text-[11px] text-slate-400">admin@spbumonitor.test</div>
              </div>
              <span class="text-[10px] bg-sky-500/20 text-sky-300 px-1.5 py-0.5 rounded">Admin</span>
            </button>
            <button onclick="SPBUApp.switchUser(4)" class="w-full text-left px-3 py-2 hover:bg-slate-800 flex items-center justify-between text-slate-200">
              <div>
                <div class="font-bold text-emerald-400">Budi Santoso</div>
                <div class="text-[11px] text-slate-400">warga@spbumonitor.test</div>
              </div>
              <span class="text-[10px] bg-emerald-500/20 text-emerald-300 px-1.5 py-0.5 rounded">Warga</span>
            </button>
          </div>
        </div>

      </div>
    </div>
  </header>

  <!-- BODY CONTENT AREA (SIDEBAR + MAIN VIEW) -->
  <div class="flex-1 flex overflow-hidden">
    
    <!-- SIDEBAR NAV (DESKTOP + RESPONSIVE DRAWER) -->
    <aside id="sidebar" class="fixed lg:static inset-y-0 left-0 z-30 w-64 bg-slate-900/95 lg:bg-slate-900 border-r border-slate-800 transform -translate-x-full lg:translate-x-0 transition-transform duration-200 ease-in-out flex flex-col justify-between custom-scrollbar overflow-y-auto">
      <div class="p-4 space-y-6">
        
        <!-- User Info Card -->
        <div class="bg-slate-800/60 border border-slate-700/60 rounded-xl p-3 flex items-center space-x-3">
          <div id="userAvatar" class="w-10 h-10 rounded-lg bg-sky-600/30 text-sky-400 border border-sky-500/40 flex items-center justify-center font-bold text-base">
            AD
          </div>
          <div class="overflow-hidden">
            <h4 id="sidebarUserName" class="text-xs font-bold text-white truncate">Admin Pusat</h4>
            <p id="sidebarUserRole" class="text-[11px] text-sky-400 font-semibold uppercase tracking-wider">Administrator</p>
            <p id="sidebarStationAssignment" class="text-[10px] text-slate-400 truncate hidden">SPBU: -</p>
          </div>
        </div>

        <!-- Navigation Menu Items -->
        <nav class="space-y-1">
          <div class="text-[10px] font-bold uppercase text-slate-500 px-3 pb-2 tracking-wider">Menu Navigasi</div>
          
          <button onclick="SPBUApp.switchTab('dashboard')" id="nav-dashboard" class="nav-item w-full flex items-center space-x-3 px-3 py-2.5 rounded-lg text-xs font-semibold text-slate-300 hover:bg-slate-800 hover:text-white transition">
            <i class="fa-solid fa-chart-pie w-5 text-center text-sky-400"></i>
            <span>Dashboard Monitoring</span>
          </button>

          <button onclick="SPBUApp.switchTab('deliveries')" id="nav-deliveries" class="nav-item w-full flex items-center space-x-3 px-3 py-2.5 rounded-lg text-xs font-semibold text-slate-300 hover:bg-slate-800 hover:text-white transition">
            <i class="fa-solid fa-truck-fast w-5 text-center text-sky-400"></i>
            <span>Pengiriman BBM</span>
            <span id="badgeActiveDeliveries" class="ml-auto text-[10px] bg-sky-500/20 text-sky-300 border border-sky-500/30 px-1.5 py-0.5 rounded-full font-bold">3 Aktif</span>
          </button>

          <button onclick="SPBUApp.switchTab('stations')" id="nav-stations" class="nav-item w-full flex items-center space-x-3 px-3 py-2.5 rounded-lg text-xs font-semibold text-slate-300 hover:bg-slate-800 hover:text-white transition">
            <i class="fa-solid fa-gas-pump w-5 text-center text-cyan-400"></i>
            <span>Data SPBU (CRUD)</span>
            <span id="badgeStationCount" class="ml-auto text-[10px] bg-slate-800 text-slate-300 px-1.5 py-0.5 rounded-full font-bold">8</span>
          </button>

          <button onclick="SPBUApp.switchTab('fuel-stocks')" id="nav-fuel-stocks" class="nav-item w-full flex items-center space-x-3 px-3 py-2.5 rounded-lg text-xs font-semibold text-slate-300 hover:bg-slate-800 hover:text-white transition">
            <i class="fa-solid fa-oil-well w-5 text-center text-blue-400"></i>
            <span>Stok BBM (CRUD)</span>
            <span id="badgeLowStockCount" class="ml-auto text-[10px] bg-amber-500/20 text-amber-300 border border-amber-500/30 px-1.5 py-0.5 rounded-full font-bold">2 Menipis</span>
          </button>

          <button onclick="SPBUApp.switchTab('fuel-types')" id="nav-fuel-types" class="nav-item w-full flex items-center space-x-3 px-3 py-2.5 rounded-lg text-xs font-semibold text-slate-300 hover:bg-slate-800 hover:text-white transition">
            <i class="fa-solid fa-tags w-5 text-center text-indigo-400"></i>
            <span>Jenis BBM & Harga</span>
          </button>

          <button onclick="SPBUApp.switchTab('queues')" id="nav-queues" class="nav-item w-full flex items-center space-x-3 px-3 py-2.5 rounded-lg text-xs font-semibold text-slate-300 hover:bg-slate-800 hover:text-white transition">
            <i class="fa-solid fa-users-line w-5 text-center text-emerald-400"></i>
            <span>Monitoring Antrean</span>
          </button>

          <button onclick="SPBUApp.switchTab('reports')" id="nav-reports" class="nav-item w-full flex items-center space-x-3 px-3 py-2.5 rounded-lg text-xs font-semibold text-slate-300 hover:bg-slate-800 hover:text-white transition">
            <i class="fa-solid fa-bullhorn w-5 text-center text-amber-400"></i>
            <span>Laporan Warga</span>
            <span id="badgePendingReports" class="ml-auto text-[10px] bg-red-500/20 text-red-300 border border-red-500/30 px-1.5 py-0.5 rounded-full font-bold">2 Menunggu</span>
          </button>

          <div class="pt-3 text-[10px] font-bold uppercase text-slate-500 px-3 pb-2 tracking-wider">Sistem & Audit</div>

          <button onclick="SPBUApp.switchTab('users')" id="nav-users" class="nav-item w-full flex items-center space-x-3 px-3 py-2.5 rounded-lg text-xs font-semibold text-slate-300 hover:bg-slate-800 hover:text-white transition">
            <i class="fa-solid fa-user-shield w-5 text-center text-purple-400"></i>
            <span>Pengguna & Akses</span>
          </button>

          <button onclick="SPBUApp.switchTab('logs')" id="nav-logs" class="nav-item w-full flex items-center space-x-3 px-3 py-2.5 rounded-lg text-xs font-semibold text-slate-300 hover:bg-slate-800 hover:text-white transition">
            <i class="fa-solid fa-clock-rotate-left w-5 text-center text-slate-400"></i>
            <span>Riwayat Aktivitas</span>
          </button>

          <button onclick="SPBUApp.switchTab('settings')" id="nav-settings" class="nav-item w-full flex items-center space-x-3 px-3 py-2.5 rounded-lg text-xs font-semibold text-slate-300 hover:bg-slate-800 hover:text-white transition">
            <i class="fa-solid fa-sliders w-5 text-center text-teal-400"></i>
            <span>Pengaturan & Demo</span>
          </button>
        </nav>
      </div>

      <!-- Sidebar Footer Status -->
      <div class="p-4 border-t border-slate-800 text-[11px] text-slate-500 space-y-1">
        <div class="flex items-center justify-between text-slate-400">
          <span>Versi Sistem</span>
          <span class="font-mono text-sky-400">v1.0 (PRD Ready)</span>
        </div>
        <div class="flex items-center justify-between text-slate-400">
          <span>Database</span>
          <span class="text-emerald-400 font-semibold">Reactive Store</span>
        </div>
      </div>
    </aside>

    <!-- BACKDROP FOR MOBILE SIDEBAR -->
    <div id="sidebarBackdrop" onclick="SPBUApp.closeSidebar()" class="fixed inset-0 bg-slate-950/70 z-20 backdrop-blur-sm hidden lg:hidden"></div>

    <!-- MAIN VIEW CONTAINER -->
    <main class="flex-1 overflow-y-auto custom-scrollbar p-4 lg:p-6 space-y-6">

      <!-- ======================================================== -->
      <!-- SECTION 1: DASHBOARD MONITORING (PRD Section 5.1) -->
      <!-- ======================================================== -->
      <section id="tab-dashboard" class="tab-content space-y-6">
        
        <!-- Header & Action Row -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div>
            <h1 class="text-2xl font-extrabold text-white tracking-tight">Dashboard Monitoring Logistik & Pengiriman BBM</h1>
            <p class="text-xs text-slate-400">Pusat komando pemantauan pengiriman truk tangki BBM dari TBBM ke SPBU, muatan liter, serta ketersediaan stok.</p>
          </div>
          <div class="flex flex-wrap items-center gap-2">
            <button onclick="SPBUApp.simulateDeliveries()" class="bg-sky-600 hover:bg-sky-500 text-white px-3 py-2 rounded-lg text-xs font-bold transition flex items-center space-x-2 shadow-lg shadow-sky-600/20">
              <i class="fa-solid fa-truck-fast"></i>
              <span>Simulasikan Pergerakan Truk</span>
            </button>
            <button onclick="SPBUApp.triggerSimTick()" class="bg-slate-800 hover:bg-slate-700 text-sky-400 border border-slate-700 px-3 py-2 rounded-lg text-xs font-bold transition flex items-center space-x-2">
              <i class="fa-solid fa-wand-magic-sparkles"></i>
              <span>Simulasi Stok & Antrean</span>
            </button>
            <button onclick="SPBUApp.exportDataJSON()" class="bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 px-3 py-2 rounded-lg text-xs font-semibold transition flex items-center space-x-1.5">
              <i class="fa-solid fa-download"></i>
              <span class="hidden sm:inline">Export</span>
            </button>
          </div>
        </div>

        <!-- 6 METRIC STAT CARDS (PRD Section 5.1) -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3.5">
          <!-- Total SPBU -->
          <div class="bg-slate-900 border border-slate-800 rounded-xl p-3.5 hover:border-sky-500/40 transition">
            <div class="flex items-center justify-between text-slate-400 mb-2">
              <span class="text-[11px] font-bold uppercase tracking-wider">Total SPBU</span>
              <div class="w-7 h-7 rounded-lg bg-sky-500/10 text-sky-400 flex items-center justify-center text-xs">
                <i class="fa-solid fa-gas-pump"></i>
              </div>
            </div>
            <div id="statTotalSPBU" class="text-2xl font-black text-white">8</div>
            <div class="text-[11px] text-slate-400 mt-1">Tersebar di 4 Kota</div>
          </div>

          <!-- SPBU Beroperasi -->
          <div class="bg-slate-900 border border-slate-800 rounded-xl p-3.5 hover:border-emerald-500/40 transition">
            <div class="flex items-center justify-between text-slate-400 mb-2">
              <span class="text-[11px] font-bold uppercase tracking-wider">Beroperasi</span>
              <div class="w-7 h-7 rounded-lg bg-emerald-500/10 text-emerald-400 flex items-center justify-center text-xs">
                <i class="fa-solid fa-circle-check"></i>
              </div>
            </div>
            <div id="statBukaSPBU" class="text-2xl font-black text-emerald-400">7</div>
            <div class="text-[11px] text-emerald-500/80 mt-1">Siap Melayani Warga</div>
          </div>

          <!-- SPBU Tidak Beroperasi -->
          <div class="bg-slate-900 border border-slate-800 rounded-xl p-3.5 hover:border-red-500/40 transition">
            <div class="flex items-center justify-between text-slate-400 mb-2">
              <span class="text-[11px] font-bold uppercase tracking-wider">Tutup / Tera</span>
              <div class="w-7 h-7 rounded-lg bg-red-500/10 text-red-400 flex items-center justify-center text-xs">
                <i class="fa-solid fa-circle-xmark"></i>
              </div>
            </div>
            <div id="statTutupSPBU" class="text-2xl font-black text-red-400">1</div>
            <div class="text-[11px] text-red-400/80 mt-1">Kalibrasi Tangki</div>
          </div>

          <!-- Stok Menipis -->
          <div class="bg-slate-900 border border-slate-800 rounded-xl p-3.5 hover:border-amber-500/40 transition">
            <div class="flex items-center justify-between text-slate-400 mb-2">
              <span class="text-[11px] font-bold uppercase tracking-wider">Stok Menipis</span>
              <div class="w-7 h-7 rounded-lg bg-amber-500/10 text-amber-400 flex items-center justify-center text-xs">
                <i class="fa-solid fa-triangle-exclamation"></i>
              </div>
            </div>
            <div id="statLowStock" class="text-2xl font-black text-amber-400">2</div>
            <div class="text-[11px] text-amber-400/80 mt-1">Perlu Restock Segera</div>
          </div>

          <!-- Laporan Belum Ditangani -->
          <div class="bg-slate-900 border border-slate-800 rounded-xl p-3.5 hover:border-rose-500/40 transition">
            <div class="flex items-center justify-between text-slate-400 mb-2">
              <span class="text-[11px] font-bold uppercase tracking-wider">Laporan Baru</span>
              <div class="w-7 h-7 rounded-lg bg-rose-500/10 text-rose-400 flex items-center justify-center text-xs">
                <i class="fa-solid fa-bullhorn"></i>
              </div>
            </div>
            <div id="statPendingReports" class="text-2xl font-black text-rose-400">2</div>
            <div class="text-[11px] text-rose-400/80 mt-1">Status: Menunggu</div>
          </div>

          <!-- Rata-rata Waktu Tunggu -->
          <div class="bg-slate-900 border border-slate-800 rounded-xl p-3.5 hover:border-indigo-500/40 transition">
            <div class="flex items-center justify-between text-slate-400 mb-2">
              <span class="text-[11px] font-bold uppercase tracking-wider">Rata-rata Antre</span>
              <div class="w-7 h-7 rounded-lg bg-indigo-500/10 text-indigo-400 flex items-center justify-center text-xs">
                <i class="fa-solid fa-stopwatch"></i>
              </div>
            </div>
            <div id="statAvgWait" class="text-2xl font-black text-indigo-400">11 mnt</div>
            <div class="text-[11px] text-indigo-400/80 mt-1">Kondisi: Sedang</div>
          </div>
        </div>

        <!-- INTERACTIVE LEAFLET MAP & RECENT CRITICAL ALERT -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          
          <!-- Leaflet Map Panel (2 cols) -->
          <div class="lg:col-span-2 bg-slate-900 border border-slate-800 rounded-2xl p-4 flex flex-col space-y-3">
            <div class="flex flex-wrap items-center justify-between gap-2">
              <div class="flex items-center space-x-2">
                <i class="fa-solid fa-map-location-dot text-sky-400"></i>
                <h3 class="font-bold text-sm text-white">Peta Live Pengiriman Truk Tangki & Sebaran SPBU</h3>
              </div>
              <div class="flex flex-wrap items-center gap-3 text-[11px] text-slate-400">
                <span class="flex items-center space-x-1"><span class="w-2.5 h-2.5 rounded-full bg-sky-400 inline-block"></span><span>Truk Tangki</span></span>
                <span class="flex items-center space-x-1"><span class="w-2.5 h-2.5 rounded-full bg-purple-400 inline-block"></span><span>TBBM Depot</span></span>
                <span class="flex items-center space-x-1"><span class="w-2.5 h-2.5 rounded-full bg-emerald-400 inline-block"></span><span>SPBU Aman</span></span>
                <span class="flex items-center space-x-1"><span class="w-2.5 h-2.5 rounded-full bg-amber-400 inline-block"></span><span>Menipis</span></span>
                <span class="flex items-center space-x-1"><span class="w-2.5 h-2.5 rounded-full bg-red-400 inline-block"></span><span>Kritis / Tutup</span></span>
              </div>
            </div>
            <!-- Map Container -->
            <div id="adminMap" class="w-full h-80 rounded-xl overflow-hidden border border-slate-800 z-10"></div>
          </div>

          <!-- Laporan Status Donut & Quick Action (1 col) -->
          <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 flex flex-col justify-between space-y-4">
            <div>
              <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                <h3 class="font-bold text-sm text-white flex items-center space-x-2">
                  <i class="fa-solid fa-chart-pie text-amber-400"></i>
                  <span>Status Penanganan Laporan</span>
                </h3>
                <span class="text-[11px] text-slate-400">Total 10 Aduan</span>
              </div>
              <div class="relative h-48 flex items-center justify-center my-2">
                <canvas id="reportChart"></canvas>
              </div>
            </div>

            <!-- Petugas Active Panel Notice -->
            <div class="bg-slate-800/80 border border-slate-700/80 rounded-xl p-3 text-xs space-y-2">
              <div class="font-bold text-sky-400 flex items-center space-x-1.5">
                <i class="fa-solid fa-user-check"></i>
                <span>Akses Pengguna Aktif:</span>
              </div>
              <p id="roleNoticeText" class="text-slate-300 text-[11px]">
                Anda masuk sebagai <strong>Admin Pusat</strong>. Memiliki wewenang penuh untuk mengelola master data SPBU, stok minyak, harga BBM, dan menindaklanjuti seluruh aduan warga.
              </p>
            </div>
          </div>

        </div>

        <!-- ACTIVE FUEL DELIVERIES MONITORING TABLE -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 space-y-3">
          <div class="flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center space-x-2">
              <i class="fa-solid fa-truck-fast text-sky-400"></i>
              <h3 class="font-bold text-sm text-white">Monitoring Pengiriman & Armada Truk Tangki BBM</h3>
            </div>
            <div class="flex items-center space-x-3">
              <button onclick="SPBUApp.openDeliveryModal()" class="text-xs bg-sky-600 hover:bg-sky-500 text-white px-3 py-1.5 rounded-lg font-bold transition">+ Jadwalkan Pengiriman</button>
              <button onclick="SPBUApp.switchTab('deliveries')" class="text-xs text-sky-400 hover:underline font-semibold">Kelola Semua Pengiriman →</button>
            </div>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
              <thead class="text-slate-400 border-b border-slate-800 uppercase text-[10px]">
                <tr>
                  <th class="py-2">Kode</th>
                  <th class="py-2">Kendaraan & Plat</th>
                  <th class="py-2">Sopir</th>
                  <th class="py-2">BBM & Volume Dibawa</th>
                  <th class="py-2">Rute (Asal → SPBU)</th>
                  <th class="py-2 text-center">ETA</th>
                  <th class="py-2 text-center">Status</th>
                  <th class="py-2 text-right">Aksi</th>
                </tr>
              </thead>
              <tbody id="dashboardDeliveriesTableBody" class="divide-y divide-slate-800/60 text-slate-200">
                <!-- Populated dynamically -->
              </tbody>
            </table>
          </div>
        </div>

        <!-- CHARTS & CRITICAL STOCK TABLE -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          
          <!-- Chart Tren Stok BBM -->
          <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 space-y-3">
            <div class="flex items-center justify-between">
              <div class="flex items-center space-x-2">
                <i class="fa-solid fa-chart-line text-sky-400"></i>
                <h3 class="font-bold text-sm text-white">Tren Stok Tangki Minyak (Simulasi Distribusi)</h3>
              </div>
              <span class="text-[11px] text-slate-400">Volume Total (Ribu Liter)</span>
            </div>
            <div class="h-64">
              <canvas id="stockTrendChart"></canvas>
            </div>
          </div>

          <!-- Tabel SPBU Stok Menipis / Kritis (PRD Section 5.1) -->
          <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 space-y-3 flex flex-col justify-between">
            <div>
              <div class="flex items-center justify-between mb-3">
                <div class="flex items-center space-x-2">
                  <i class="fa-solid fa-triangle-exclamation text-amber-400"></i>
                  <h3 class="font-bold text-sm text-white">Daftar Tangki Menipis / Kritis</h3>
                </div>
                <button onclick="SPBUApp.switchTab('fuel-stocks')" class="text-xs text-sky-400 hover:underline font-semibold">Lihat Semua Stok →</button>
              </div>
              <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                  <thead class="text-slate-400 border-b border-slate-800 uppercase text-[10px]">
                    <tr>
                      <th class="py-2">SPBU</th>
                      <th class="py-2">Jenis BBM</th>
                      <th class="py-2 text-right">Sisa Stok</th>
                      <th class="py-2 text-center">Status</th>
                      <th class="py-2 text-right">Aksi</th>
                    </tr>
                  </thead>
                  <tbody id="lowStockTableBody" class="divide-y divide-slate-800/60 text-slate-200">
                    <!-- Populated dynamically -->
                  </tbody>
                </table>
              </div>
            </div>

            <div class="bg-amber-500/10 border border-amber-500/30 rounded-xl p-2.5 flex items-center space-x-2 text-[11px] text-amber-300">
              <i class="fa-solid fa-truck-droplet text-amber-400 text-sm"></i>
              <span>Pengiriman dropping darurat dari TBBM dapat diaktifkan jika stok di bawah ambang batas minimun.</span>
            </div>
          </div>

        </div>

        <!-- RECENT CITIZEN REPORTS TABLE -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 space-y-3">
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-2">
              <i class="fa-solid fa-bullhorn text-rose-400"></i>
              <h3 class="font-bold text-sm text-white">Laporan & Aduan Warga Terbaru</h3>
            </div>
            <button onclick="SPBUApp.switchTab('reports')" class="text-xs text-sky-400 hover:underline font-semibold">Kelola Semua Laporan (10) →</button>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
              <thead class="text-slate-400 border-b border-slate-800 uppercase text-[10px]">
                <tr>
                  <th class="py-2">No. Laporan</th>
                  <th class="py-2">Warga</th>
                  <th class="py-2">SPBU Terkait</th>
                  <th class="py-2">Kategori</th>
                  <th class="py-2">Keluhan</th>
                  <th class="py-2 text-center">Status</th>
                  <th class="py-2 text-right">Tindakan</th>
                </tr>
              </thead>
              <tbody id="recentReportsTableBody" class="divide-y divide-slate-800/60 text-slate-200">
                <!-- Populated dynamically -->
              </tbody>
            </table>
          </div>
        </div>

      </section>

      <!-- ======================================================== -->
      <!-- SECTION 1B: MONITORING PENGIRIMAN BBM (CRUD) -->
      <!-- ======================================================== -->
      <section id="tab-deliveries" class="tab-content hidden space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div>
            <h1 class="text-2xl font-extrabold text-white tracking-tight">Monitoring Pengiriman Logistik BBM</h1>
            <p class="text-xs text-slate-400">Kelola jadwal pengiriman truk tangki dari TBBM ke SPBU, pantau kendaraan, muatan liter, dan konfirmasi bongkar muat.</p>
          </div>
          <div class="flex items-center space-x-2">
            <button onclick="SPBUApp.simulateDeliveries()" class="bg-slate-800 hover:bg-slate-700 text-sky-400 border border-slate-700 px-3 py-2 rounded-xl text-xs font-bold transition flex items-center space-x-2">
              <i class="fa-solid fa-location-crosshairs"></i>
              <span>Simulasi GPS Truk</span>
            </button>
            <button onclick="SPBUApp.openDeliveryModal()" class="bg-sky-600 hover:bg-sky-500 text-white px-4 py-2 rounded-xl text-xs font-bold transition flex items-center space-x-2 shadow-lg shadow-sky-600/20">
              <i class="fa-solid fa-plus"></i>
              <span>Jadwalkan Pengiriman</span>
            </button>
          </div>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 flex flex-wrap gap-3 items-center justify-between">
          <div class="relative flex-1 min-w-[220px]">
            <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-xs"></i>
            <input type="text" id="deliverySearchInput" oninput="SPBUApp.renderDeliveriesTable()" placeholder="Cari kode, kendaraan, plat nomor, sopir, atau SPBU..." class="w-full bg-slate-800 border border-slate-700 rounded-xl pl-9 pr-3 py-2 text-xs text-white focus:outline-none focus:border-sky-500">
          </div>
          <select id="deliveryStatusFilter" onchange="SPBUApp.renderDeliveriesTable()" class="bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-sky-500">
            <option value="Semua">Semua Status</option>
            <option value="Dijadwalkan">Dijadwalkan</option>
            <option value="Dalam Perjalanan">Dalam Perjalanan</option>
            <option value="Tiba di SPBU">Tiba di SPBU</option>
            <option value="Selesai">Selesai</option>
          </select>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
              <thead class="bg-slate-800/80 text-slate-300 uppercase text-[10px] font-bold border-b border-slate-700">
                <tr>
                  <th class="py-3 px-4">Kode Pengiriman</th>
                  <th class="py-3 px-4">Kendaraan & Plat</th>
                  <th class="py-3 px-4">Sopir</th>
                  <th class="py-3 px-4">Jenis BBM & Volume</th>
                  <th class="py-3 px-4">Asal TBBM → SPBU Tujuan</th>
                  <th class="py-3 px-4 text-center">ETA</th>
                  <th class="py-3 px-4 text-center">Status</th>
                  <th class="py-3 px-4 text-right">Aksi</th>
                </tr>
              </thead>
              <tbody id="deliveriesTableBody" class="divide-y divide-slate-800 text-slate-200">
                <!-- Dynamically rendered -->
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- ======================================================== -->
      <!-- SECTION 2: CRUD DATA SPBU (PRD Section 5.2) -->
      <!-- ======================================================== -->
      <section id="tab-stations" class="tab-content hidden space-y-6">
        
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div>
            <h1 class="text-2xl font-extrabold text-white tracking-tight">Manajemen Data SPBU (CRUD)</h1>
            <p class="text-xs text-slate-400">Tambah, periksa, perbarui, dan nonaktifkan lokasi SPBU serta koordinat geografis.</p>
          </div>
          <div>
            <button onclick="SPBUApp.openStationModal()" class="bg-sky-600 hover:bg-sky-500 text-white px-4 py-2 rounded-xl text-xs font-bold transition flex items-center space-x-2 shadow-lg shadow-sky-600/20">
              <i class="fa-solid fa-plus"></i>
              <span>Tambah SPBU Baru</span>
            </button>
          </div>
        </div>

        <!-- Filter & Search Bar -->
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-3 flex flex-wrap items-center justify-between gap-3 text-xs">
          <div class="flex items-center space-x-2 flex-1 min-w-[200px]">
            <i class="fa-solid fa-magnifying-glass text-slate-400"></i>
            <input type="text" id="stationSearchInput" oninput="SPBUApp.filterStations()" placeholder="Cari SPBU berdasarkan nama, kode, atau alamat..." class="bg-transparent border-none focus:outline-none text-white w-full text-xs">
          </div>
          <div class="flex items-center space-x-2">
            <select id="stationRegionFilter" onchange="SPBUApp.filterStations()" class="bg-slate-800 text-slate-200 border border-slate-700 rounded-lg px-2.5 py-1.5 text-xs focus:outline-none">
              <option value="Semua">Semua Wilayah</option>
              <option value="Jakarta Selatan">Jakarta Selatan</option>
              <option value="Jakarta Timur">Jakarta Timur</option>
              <option value="Kota Bekasi">Kota Bekasi</option>
              <option value="Kabupaten Bekasi">Kabupaten Bekasi</option>
              <option value="Kota Bandung">Kota Bandung</option>
              <option value="Kota Surabaya">Kota Surabaya</option>
            </select>
            <select id="stationStatusFilter" onchange="SPBUApp.filterStations()" class="bg-slate-800 text-slate-200 border border-slate-700 rounded-lg px-2.5 py-1.5 text-xs focus:outline-none">
              <option value="Semua">Semua Status</option>
              <option value="Buka">Buka</option>
              <option value="Tutup">Tutup</option>
              <option value="Perbaikan">Perbaikan</option>
            </select>
          </div>
        </div>

        <!-- Stations Table -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
              <thead class="bg-slate-800/80 text-slate-300 uppercase text-[10px] font-bold border-b border-slate-700">
                <tr>
                  <th class="py-3 px-4">Kode & Nama SPBU</th>
                  <th class="py-3 px-4">Wilayah / Alamat</th>
                  <th class="py-3 px-4">Jam Buka</th>
                  <th class="py-3 px-4 text-center">Status Operasional</th>
                  <th class="py-3 px-4 text-center">Kondisi Antrean</th>
                  <th class="py-3 px-4 text-right">Aksi Manajemen</th>
                </tr>
              </thead>
              <tbody id="stationsTableBody" class="divide-y divide-slate-800 text-slate-200">
                <!-- Dynamically rendered -->
              </tbody>
            </table>
          </div>
        </div>

      </section>

      <!-- ======================================================== -->
      <!-- SECTION 3: CRUD STOK BBM (PRD Section 5.3) -->
      <!-- ======================================================== -->
      <section id="tab-fuel-stocks" class="tab-content hidden space-y-6">
        
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div>
            <h1 class="text-2xl font-extrabold text-white tracking-tight">Manajemen Stok Minyak BBM (CRUD)</h1>
            <p class="text-xs text-slate-400">Pantau volume tangki pendam, perbarui stok setelah dropping, dan periksa histori penelusuran audit.</p>
          </div>
          <div class="flex items-center space-x-2">
            <button onclick="SPBUApp.openStockHistoryModal()" class="bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 px-3.5 py-2 rounded-xl text-xs font-semibold transition flex items-center space-x-1.5">
              <i class="fa-solid fa-clock-rotate-left text-sky-400"></i>
              <span>Riwayat Perubahan Stok</span>
            </button>
          </div>
        </div>

        <!-- Filter SPBU for Stocks -->
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-3 flex flex-wrap items-center justify-between gap-3 text-xs">
          <div class="flex items-center space-x-3">
            <span class="text-slate-400 font-semibold">Pilih SPBU:</span>
            <select id="stockStationFilter" onchange="SPBUApp.renderStocksTable()" class="bg-slate-800 text-slate-200 border border-slate-700 rounded-lg px-3 py-1.5 text-xs font-semibold focus:outline-none">
              <option value="Semua">Semua SPBU (Seluruh Tangki)</option>
              <!-- Filled via JS -->
            </select>
          </div>
          <div class="flex items-center space-x-2">
            <select id="stockStatusFilter" onchange="SPBUApp.renderStocksTable()" class="bg-slate-800 text-slate-200 border border-slate-700 rounded-lg px-2.5 py-1.5 text-xs focus:outline-none">
              <option value="Semua">Semua Kondisi Stok</option>
              <option value="Tersedia">Tersedia (Aman)</option>
              <option value="Menipis">Menipis (Perlu Dropping)</option>
              <option value="Habis">Habis (Kosong)</option>
            </select>
          </div>
        </div>

        <!-- Fuel Stocks Cards / Table -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
              <thead class="bg-slate-800/80 text-slate-300 uppercase text-[10px] font-bold border-b border-slate-700">
                <tr>
                  <th class="py-3 px-4">SPBU Lokasi</th>
                  <th class="py-3 px-4">Jenis BBM</th>
                  <th class="py-3 px-4">Kapasitas Tangki</th>
                  <th class="py-3 px-4">Stok Saat Ini (L)</th>
                  <th class="py-3 px-4">Ambang Kritis</th>
                  <th class="py-3 px-4 text-center">Status</th>
                  <th class="py-3 px-4">Pembaruan Terakhir</th>
                  <th class="py-3 px-4 text-right">Aksi</th>
                </tr>
              </thead>
              <tbody id="stocksTableBody" class="divide-y divide-slate-800 text-slate-200">
                <!-- Dynamically rendered -->
              </tbody>
            </table>
          </div>
        </div>

      </section>

      <!-- ======================================================== -->
      <!-- SECTION 4: CRUD JENIS BBM (PRD Section 5.4) -->
      <!-- ======================================================== -->
      <section id="tab-fuel-types" class="tab-content hidden space-y-6">
        
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div>
            <h1 class="text-2xl font-extrabold text-white tracking-tight">Katalog Jenis BBM & Harga (CRUD)</h1>
            <p class="text-xs text-slate-400">Atur produk BBM resmi, keterangan oktan/cetane, dan penyesuaian harga jual per liter.</p>
          </div>
          <div>
            <button onclick="SPBUApp.openFuelTypeModal()" class="bg-sky-600 hover:bg-sky-500 text-white px-4 py-2 rounded-xl text-xs font-bold transition flex items-center space-x-2 shadow-lg shadow-sky-600/20">
              <i class="fa-solid fa-plus"></i>
              <span>Tambah Jenis BBM</span>
            </button>
          </div>
        </div>

        <!-- Disclaimer Banner Sesuai PRD Section 5.4 Line 148 -->
        <div class="bg-sky-950/40 border border-sky-500/30 rounded-xl p-3.5 flex items-start space-x-3 text-xs text-sky-200">
          <i class="fa-solid fa-circle-info text-sky-400 text-base mt-0.5"></i>
          <div>
            <strong>Catatan Pengaturan Harga:</strong> Harga dapat diubah oleh Admin Pengelola Pusat dan langsung tersinkronisasi ke portal warga secara real-time. Data harga merupakan input pengelola untuk keperluan operasional sistem monitoring.
          </div>
        </div>

        <!-- Fuel Types Cards Grid -->
        <div id="fuelTypesGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <!-- Dynamically populated -->
        </div>

      </section>

      <!-- ======================================================== -->
      <!-- SECTION 5: MONITORING ANTREAN (PRD Section 5.5) -->
      <!-- ======================================================== -->
      <section id="tab-queues" class="tab-content hidden space-y-6">
        
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div>
            <h1 class="text-2xl font-extrabold text-white tracking-tight">Monitoring Kondisi Antrean SPBU</h1>
            <p class="text-xs text-slate-400">Perbarui kondisi kepadatan kendaraan, estimasi waktu tunggu, dan sumber data sensor/petugas.</p>
          </div>
        </div>

        <!-- Queue Status Table -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
              <thead class="bg-slate-800/80 text-slate-300 uppercase text-[10px] font-bold border-b border-slate-700">
                <tr>
                  <th class="py-3 px-4">Nama SPBU</th>
                  <th class="py-3 px-4">Wilayah</th>
                  <th class="py-3 px-4">Kategori Kendaraan</th>
                  <th class="py-3 px-4 text-center">Tingkat Antrean</th>
                  <th class="py-3 px-4 text-center">Estimasi Tunggu</th>
                  <th class="py-3 px-4">Sumber Data</th>
                  <th class="py-3 px-4">Diperbarui Oleh</th>
                  <th class="py-3 px-4 text-right">Aksi Cepat</th>
                </tr>
              </thead>
              <tbody id="queuesTableBody" class="divide-y divide-slate-800 text-slate-200">
                <!-- Dynamically rendered -->
              </tbody>
            </table>
          </div>
        </div>

      </section>

      <!-- ======================================================== -->
      <!-- SECTION 6: CRUD LAPORAN WARGA (PRD Section 5.6) -->
      <!-- ======================================================== -->
      <section id="tab-reports" class="tab-content hidden space-y-6">
        
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div>
            <h1 class="text-2xl font-extrabold text-white tracking-tight">Laporan & Aduan Warga (CRUD)</h1>
            <p class="text-xs text-slate-400">Tinjau keluhan masyarakat, verifikasi foto bukti, berikan catatan tindak lanjut, dan selesaikan aduan.</p>
          </div>
        </div>

        <!-- Filter & Search Bar for Reports -->
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-3 flex flex-wrap items-center justify-between gap-3 text-xs">
          <div class="flex items-center space-x-2 flex-1 min-w-[200px]">
            <i class="fa-solid fa-magnifying-glass text-slate-400"></i>
            <input type="text" id="reportSearchInput" oninput="SPBUApp.filterReports()" placeholder="Cari laporan berdasarkan nomor, pelapor, atau isi keluhan..." class="bg-transparent border-none focus:outline-none text-white w-full text-xs">
          </div>
          <div class="flex items-center space-x-2">
            <select id="reportStatusFilter" onchange="SPBUApp.filterReports()" class="bg-slate-800 text-slate-200 border border-slate-700 rounded-lg px-2.5 py-1.5 text-xs focus:outline-none">
              <option value="Semua">Semua Status</option>
              <option value="Menunggu">Menunggu</option>
              <option value="Diverifikasi">Diverifikasi</option>
              <option value="Ditindaklanjuti">Ditindaklanjuti</option>
              <option value="Selesai">Selesai</option>
              <option value="Ditolak">Ditolak</option>
            </select>
            <select id="reportCategoryFilter" onchange="SPBUApp.filterReports()" class="bg-slate-800 text-slate-200 border border-slate-700 rounded-lg px-2.5 py-1.5 text-xs focus:outline-none">
              <option value="Semua">Semua Kategori</option>
              <option value="Antrean panjang">Antrean panjang</option>
              <option value="BBM habis">BBM habis</option>
              <option value="Informasi stok tidak sesuai">Informasi stok tidak sesuai</option>
              <option value="SPBU tutup">SPBU tutup</option>
              <option value="Fasilitas bermasalah">Fasilitas bermasalah</option>
              <option value="Lainnya">Lainnya</option>
            </select>
          </div>
        </div>

        <!-- Reports Table -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
              <thead class="bg-slate-800/80 text-slate-300 uppercase text-[10px] font-bold border-b border-slate-700">
                <tr>
                  <th class="py-3 px-4">Nomor Laporan</th>
                  <th class="py-3 px-4">Pelapor</th>
                  <th class="py-3 px-4">SPBU Terkait</th>
                  <th class="py-3 px-4">Kategori</th>
                  <th class="py-3 px-4">Bukti Foto</th>
                  <th class="py-3 px-4 text-center">Status</th>
                  <th class="py-3 px-4">Waktu</th>
                  <th class="py-3 px-4 text-right">Aksi Proses</th>
                </tr>
              </thead>
              <tbody id="reportsTableBody" class="divide-y divide-slate-800 text-slate-200">
                <!-- Dynamically rendered -->
              </tbody>
            </table>
          </div>
        </div>

      </section>

      <!-- ======================================================== -->
      <!-- SECTION 7: PENGGUNA & PETUGAS (PRD Section 5.7) -->
      <!-- ======================================================== -->
      <section id="tab-users" class="tab-content hidden space-y-6">
        
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div>
            <h1 class="text-2xl font-extrabold text-white tracking-tight">Pengguna & Hak Akses (CRUD)</h1>
            <p class="text-xs text-slate-400">Kelola akun Admin Logistik dan profil akun warga.</p>
          </div>
          <div>
            <button onclick="SPBUApp.openUserModal()" class="bg-sky-600 hover:bg-sky-500 text-white px-4 py-2 rounded-xl text-xs font-bold transition flex items-center space-x-2 shadow-lg shadow-sky-600/20">
              <i class="fa-solid fa-user-plus"></i>
              <span>Tambah Pengguna</span>
            </button>
          </div>
        </div>

        <!-- Users Table -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
              <thead class="bg-slate-800/80 text-slate-300 uppercase text-[10px] font-bold border-b border-slate-700">
                <tr>
                  <th class="py-3 px-4">Nama Pengguna</th>
                  <th class="py-3 px-4">Email</th>
                  <th class="py-3 px-4">Kontak / HP</th>
                  <th class="py-3 px-4 text-center">Peran (Role)</th>
                  <th class="py-3 px-4">Penugasan SPBU</th>
                  <th class="py-3 px-4 text-center">Status Akun</th>
                  <th class="py-3 px-4 text-right">Aksi</th>
                </tr>
              </thead>
              <tbody id="usersTableBody" class="divide-y divide-slate-800 text-slate-200">
                <!-- Dynamically rendered -->
              </tbody>
            </table>
          </div>
        </div>

      </section>

      <!-- ======================================================== -->
      <!-- SECTION 8: RIWAYAT AKTIVITAS / AUDIT LOG (PRD Section 5.8) -->
      <!-- ======================================================== -->
      <section id="tab-logs" class="tab-content hidden space-y-6">
        
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div>
            <h1 class="text-2xl font-extrabold text-white tracking-tight">Riwayat Aktivitas & Audit Log</h1>
            <p class="text-xs text-slate-400">Jejak audit perubahan data operasional, mutasi stok, status antrean, dan respon aduan masyarakat.</p>
          </div>
        </div>

        <!-- Logs Table -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
              <thead class="bg-slate-800/80 text-slate-300 uppercase text-[10px] font-bold border-b border-slate-700">
                <tr>
                  <th class="py-3 px-4">Waktu</th>
                  <th class="py-3 px-4">Pengguna</th>
                  <th class="py-3 px-4">Peran</th>
                  <th class="py-3 px-4 text-center">Tindakan</th>
                  <th class="py-3 px-4">Entitas Terkait</th>
                  <th class="py-3 px-4">Rincian Perubahan Aktivitas</th>
                </tr>
              </thead>
              <tbody id="logsTableBody" class="divide-y divide-slate-800 text-slate-200">
                <!-- Dynamically rendered -->
              </tbody>
            </table>
          </div>
        </div>

      </section>

      <!-- ======================================================== -->
      <!-- SECTION 9: PENGATURAN & DEMO CONTROLS -->
      <!-- ======================================================== -->
      <section id="tab-settings" class="tab-content hidden space-y-6">
        
        <div>
          <h1 class="text-2xl font-extrabold text-white tracking-tight">Pengaturan & Kontrol Data Simulasi</h1>
          <p class="text-xs text-slate-400">Kelola penyimpanan data lokal, inisialisasi demo, dan ekspor data pengujian.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          
          <!-- Card: Reset Demo Data -->
          <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 space-y-4">
            <div class="flex items-center space-x-3 text-amber-400">
              <i class="fa-solid fa-rotate-left text-xl"></i>
              <h3 class="font-bold text-base text-white">Inisialisasi Ulang Data Demo</h3>
            </div>
            <p class="text-xs text-slate-400 leading-relaxed">
              Mengembalikan seluruh data (8 SPBU, 6 Jenis BBM, stok minyak tangki, 10 laporan warga, dan akun) ke kondisi bawaan PRD. Seluruh riwayat perubahan baru akan direset.
            </p>
            <button onclick="SPBUApp.resetAllDemo()" class="bg-amber-600 hover:bg-amber-500 text-slate-950 px-4 py-2 rounded-xl text-xs font-bold transition flex items-center space-x-2">
              <i class="fa-solid fa-arrows-rotate"></i>
              <span>Reset ke Kondisi Default PRD</span>
            </button>
          </div>

          <!-- Card: Backup & Export JSON -->
          <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 space-y-4">
            <div class="flex items-center space-x-3 text-sky-400">
              <i class="fa-solid fa-file-code text-xl"></i>
              <h3 class="font-bold text-base text-white">Ekspor & Impor Data JSON</h3>
            </div>
            <p class="text-xs text-slate-400 leading-relaxed">
              Unduh seluruh snapshot state database LocalStorage saat ini untuk keperluan laporan tugas besar atau restore data.
            </p>
            <div class="flex items-center space-x-3">
              <button onclick="SPBUApp.exportDataJSON()" class="bg-sky-600 hover:bg-sky-500 text-white px-4 py-2 rounded-xl text-xs font-bold transition flex items-center space-x-2">
                <i class="fa-solid fa-download"></i>
                <span>Unduh JSON</span>
              </button>
              <label class="bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 px-4 py-2 rounded-xl text-xs font-bold transition flex items-center space-x-2 cursor-pointer">
                <i class="fa-solid fa-upload"></i>
                <span>Impor File JSON</span>
                <input type="file" id="importJsonInput" accept=".json" onchange="SPBUApp.importDataJSON(event)" class="hidden">
              </label>
            </div>
          </div>

        </div>

      </section>

    </main>
  </div>

  <!-- ======================================================== -->
  <!-- MODALS CONTAINER -->
  <!-- ======================================================== -->

  <!-- 1. MODAL TAMBAH / EDIT SPBU -->
  <div id="stationModal" class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-lg overflow-hidden shadow-2xl flex flex-col max-h-[90vh]">
      <div class="px-5 py-4 border-b border-slate-800 flex items-center justify-between">
        <h3 id="stationModalTitle" class="font-bold text-base text-white">Tambah SPBU Baru</h3>
        <button onclick="SPBUApp.closeModal('stationModal')" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark text-lg"></i></button>
      </div>
      <form id="stationForm" onsubmit="SPBUApp.handleStationSubmit(event)" class="p-5 space-y-4 overflow-y-auto custom-scrollbar flex-1 text-xs">
        <input type="hidden" id="stationFormId">
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block font-semibold text-slate-300 mb-1">Kode SPBU *</label>
            <input type="text" id="stationFormCode" placeholder="contoh: 31.121.05" required class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-sky-500">
          </div>
          <div>
            <label class="block font-semibold text-slate-300 mb-1">Wilayah / Kota *</label>
            <input type="text" id="stationFormRegion" placeholder="contoh: Jakarta Selatan" required class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-sky-500">
          </div>
        </div>
        <div>
          <label class="block font-semibold text-slate-300 mb-1">Nama SPBU *</label>
          <input type="text" id="stationFormName" placeholder="contoh: SPBU Pertamina Mampang" required class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-sky-500">
        </div>
        <div>
          <label class="block font-semibold text-slate-300 mb-1">Alamat Lengkap *</label>
          <textarea id="stationFormAddress" rows="2" placeholder="Jl. Mampang Prapatan No. ..." required class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-sky-500"></textarea>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block font-semibold text-slate-300 mb-1">Latitude *</label>
            <input type="number" step="any" id="stationFormLat" placeholder="-6.24" required class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-sky-500">
          </div>
          <div>
            <label class="block font-semibold text-slate-300 mb-1">Longitude *</label>
            <input type="number" step="any" id="stationFormLng" placeholder="106.82" required class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-sky-500">
          </div>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block font-semibold text-slate-300 mb-1">Nomor Telepon</label>
            <input type="text" id="stationFormPhone" placeholder="(021) 7990-1234" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-sky-500">
          </div>
          <div>
            <label class="block font-semibold text-slate-300 mb-1">Jam Operasional</label>
            <input type="text" id="stationFormHours" placeholder="24 Jam atau 06:00 - 22:00" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-sky-500">
          </div>
        </div>
        <div>
          <label class="block font-semibold text-slate-300 mb-1">Status Operasional</label>
          <select id="stationFormStatus" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-sky-500">
            <option value="Buka">Buka</option>
            <option value="Tutup">Tutup</option>
            <option value="Perbaikan">Perbaikan</option>
          </select>
        </div>
        <div class="pt-2 flex justify-end space-x-2 border-t border-slate-800">
          <button type="button" onclick="SPBUApp.closeModal('stationModal')" class="bg-slate-800 text-slate-300 hover:bg-slate-700 px-4 py-2 rounded-lg font-semibold">Batal</button>
          <button type="submit" class="bg-sky-600 hover:bg-sky-500 text-white px-5 py-2 rounded-lg font-bold">Simpan Data SPBU</button>
        </div>
      </form>
    </div>
  </div>

  <!-- 2. MODAL UPDATE STOK BBM -->
  <div id="stockModal" class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-md overflow-hidden shadow-2xl flex flex-col">
      <div class="px-5 py-4 border-b border-slate-800 flex items-center justify-between">
        <div>
          <h3 class="font-bold text-base text-white">Pembaruan Stok Tangki Pendam</h3>
          <p id="stockModalSubtitle" class="text-xs text-sky-400">Pertalite — SPBU Cilandak</p>
        </div>
        <button onclick="SPBUApp.closeModal('stockModal')" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark text-lg"></i></button>
      </div>
      <form id="stockForm" onsubmit="SPBUApp.handleStockSubmit(event)" class="p-5 space-y-4 text-xs">
        <input type="hidden" id="stockFormId">
        <div class="bg-slate-800/80 p-3 rounded-xl border border-slate-700 space-y-1">
          <div class="flex justify-between text-slate-400">
            <span>Kapasitas Tangki:</span>
            <span id="stockModalCapacity" class="font-bold text-white">30.000 L</span>
          </div>
          <div class="flex justify-between text-slate-400">
            <span>Stok Sebelumnya:</span>
            <span id="stockModalCurrent" class="font-bold text-amber-400">12.500 L</span>
          </div>
          <div class="flex justify-between text-slate-400">
            <span>Batas Menipis:</span>
            <span id="stockModalThreshold" class="font-bold text-rose-400">4.000 L</span>
          </div>
        </div>
        <div>
          <label class="block font-semibold text-slate-300 mb-1">Jumlah Stok Baru (Liter) *</label>
          <input type="number" id="stockFormLiters" min="0" required class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-white font-mono text-base focus:outline-none focus:border-sky-500">
          <p class="text-[10px] text-slate-500 mt-1">Stok tidak boleh negatif dan tidak boleh melebihi kapasitas maksimum.</p>
        </div>
        <div>
          <label class="block font-semibold text-slate-300 mb-1">Catatan / Referensi Dropping *</label>
          <input type="text" id="stockFormNote" placeholder="contoh: Penerimaan DO dari TBBM Plumpang" required class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-sky-500">
        </div>
        <div class="pt-2 flex justify-end space-x-2 border-t border-slate-800">
          <button type="button" onclick="SPBUApp.closeModal('stockModal')" class="bg-slate-800 text-slate-300 hover:bg-slate-700 px-4 py-2 rounded-lg font-semibold">Batal</button>
          <button type="submit" class="bg-sky-600 hover:bg-sky-500 text-white px-5 py-2 rounded-lg font-bold">Simpan Mutasi Stok</button>
        </div>
      </form>
    </div>
  </div>

  <!-- 3. MODAL RIWAYAT STOK -->
  <div id="stockHistoryModal" class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-2xl overflow-hidden shadow-2xl flex flex-col max-h-[85vh]">
      <div class="px-5 py-4 border-b border-slate-800 flex items-center justify-between">
        <h3 class="font-bold text-base text-white flex items-center space-x-2">
          <i class="fa-solid fa-clock-rotate-left text-sky-400"></i>
          <span>Riwayat Perubahan & Penelusuran Stok</span>
        </h3>
        <button onclick="SPBUApp.closeModal('stockHistoryModal')" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark text-lg"></i></button>
      </div>
      <div class="p-5 overflow-y-auto custom-scrollbar flex-1">
        <table class="w-full text-left text-xs">
          <thead class="text-slate-400 border-b border-slate-800 uppercase text-[10px]">
            <tr>
              <th class="py-2">Waktu</th>
              <th class="py-2">SPBU / BBM</th>
              <th class="py-2 text-right">Perubahan</th>
              <th class="py-2 text-right">Stok Akhir</th>
              <th class="py-2">Catatan</th>
              <th class="py-2">Petugas</th>
            </tr>
          </thead>
          <tbody id="stockHistoriesTableBody" class="divide-y divide-slate-800 text-slate-200">
            <!-- Dynamically populated -->
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- 4. MODAL EDIT HARGA / JENIS BBM -->
  <div id="fuelTypeModal" class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-md overflow-hidden shadow-2xl flex flex-col">
      <div class="px-5 py-4 border-b border-slate-800 flex items-center justify-between">
        <h3 id="fuelTypeModalTitle" class="font-bold text-base text-white">Penyesuaian Harga BBM</h3>
        <button onclick="SPBUApp.closeModal('fuelTypeModal')" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark text-lg"></i></button>
      </div>
      <form id="fuelTypeForm" onsubmit="SPBUApp.handleFuelTypeSubmit(event)" class="p-5 space-y-4 text-xs">
        <input type="hidden" id="fuelTypeFormId">
        <div>
          <label class="block font-semibold text-slate-300 mb-1">Nama Produk BBM *</label>
          <input type="text" id="fuelTypeFormName" required class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-sky-500">
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block font-semibold text-slate-300 mb-1">Tingkat Oktan / Cetane</label>
            <input type="text" id="fuelTypeFormRon" placeholder="RON 92 / CN 51" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-sky-500">
          </div>
          <div>
            <label class="block font-semibold text-slate-300 mb-1">Harga per Liter (Rp) *</label>
            <input type="number" id="fuelTypeFormPrice" min="1000" step="50" required class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-white font-mono text-base focus:outline-none focus:border-sky-500">
          </div>
        </div>
        <div>
          <label class="block font-semibold text-slate-300 mb-1">Deskripsi Produk</label>
          <textarea id="fuelTypeFormDesc" rows="2" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-sky-500"></textarea>
        </div>
        <div class="pt-2 flex justify-end space-x-2 border-t border-slate-800">
          <button type="button" onclick="SPBUApp.closeModal('fuelTypeModal')" class="bg-slate-800 text-slate-300 hover:bg-slate-700 px-4 py-2 rounded-lg font-semibold">Batal</button>
          <button type="submit" class="bg-sky-600 hover:bg-sky-500 text-white px-5 py-2 rounded-lg font-bold">Simpan Harga</button>
        </div>
      </form>
    </div>
  </div>

  <!-- 5. MODAL PROSES / TINDAK LANJUT LAPORAN WARGA -->
  <div id="reportDetailModal" class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-xl overflow-hidden shadow-2xl flex flex-col max-h-[90vh]">
      <div class="px-5 py-4 border-b border-slate-800 flex items-center justify-between">
        <div>
          <h3 class="font-bold text-base text-white">Tindak Lanjut Laporan Warga</h3>
          <p id="reportModalCode" class="text-xs font-mono text-sky-400">LAP-20261123-001</p>
        </div>
        <button onclick="SPBUApp.closeModal('reportDetailModal')" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark text-lg"></i></button>
      </div>
      <form id="reportForm" onsubmit="SPBUApp.handleReportSubmit(event)" class="p-5 space-y-4 overflow-y-auto custom-scrollbar flex-1 text-xs">
        <input type="hidden" id="reportFormId">
        
        <!-- Info Warga & SPBU -->
        <div class="grid grid-cols-2 gap-3 bg-slate-800/60 p-3 rounded-xl border border-slate-700/60">
          <div>
            <span class="text-slate-400 block text-[10px] uppercase font-bold">Pelapor:</span>
            <span id="reportModalUser" class="text-white font-semibold">Budi Santoso</span>
          </div>
          <div>
            <span class="text-slate-400 block text-[10px] uppercase font-bold">SPBU Terkait:</span>
            <span id="reportModalStation" class="text-white font-semibold">SPBU Cawang</span>
          </div>
          <div>
            <span class="text-slate-400 block text-[10px] uppercase font-bold">Kategori:</span>
            <span id="reportModalCategory" class="text-sky-300 font-semibold">Antrean Panjang</span>
          </div>
          <div>
            <span class="text-slate-400 block text-[10px] uppercase font-bold">Tanggal Masuk:</span>
            <span id="reportModalTime" class="text-slate-300">2026-11-23 20:10</span>
          </div>
        </div>

        <!-- Deskripsi Keluhan -->
        <div>
          <label class="block font-semibold text-slate-300 mb-1">Rincian Laporan Warga:</label>
          <div id="reportModalDesc" class="bg-slate-800 p-3 rounded-xl text-slate-200 border border-slate-700 leading-relaxed">
            -
          </div>
        </div>

        <!-- Bukti Foto (jika ada) -->
        <div id="reportModalPhotoContainer" class="hidden">
          <label class="block font-semibold text-slate-300 mb-1">Bukti Foto dari Warga:</label>
          <img id="reportModalPhoto" src="" alt="Bukti Foto" class="w-full max-h-56 object-cover rounded-xl border border-slate-700">
        </div>

        <!-- Update Status & Catatan Admin -->
        <div class="border-t border-slate-800 pt-3 space-y-3">
          <div>
            <label class="block font-semibold text-slate-300 mb-1">Status Penanganan Laporan *</label>
            <select id="reportFormStatus" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-white font-semibold focus:outline-none focus:border-sky-500">
              <option value="Menunggu">Menunggu (Pending Verifikasi)</option>
              <option value="Diverifikasi">Diverifikasi (Valid, Diteruskan ke Petugas)</option>
              <option value="Ditindaklanjuti">Ditindaklanjuti (Dalam Proses Solusi)</option>
              <option value="Selesai">Selesai (Masalah Telah Teratasi)</option>
              <option value="Ditolak">Ditolak (Spam / Laporan Tidak Valid)</option>
            </select>
          </div>
          <div>
            <label class="block font-semibold text-slate-300 mb-1">Catatan Tindak Lanjut Admin / Petugas (Dapat dilihat oleh warga):</label>
            <textarea id="reportFormNote" rows="3" placeholder="Tuliskan keterangan tindakan, misalnya: Petugas telah membuka dispenser tambahan..." class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-sky-500"></textarea>
          </div>
        </div>

        <div class="pt-2 flex justify-between items-center border-t border-slate-800">
          <button type="button" onclick="SPBUApp.deleteReportCurrent()" class="text-red-400 hover:text-red-300 font-semibold flex items-center space-x-1">
            <i class="fa-solid fa-trash"></i>
            <span>Hapus Laporan (Spam)</span>
          </button>
          <div class="flex space-x-2">
            <button type="button" onclick="SPBUApp.closeModal('reportDetailModal')" class="bg-slate-800 text-slate-300 hover:bg-slate-700 px-4 py-2 rounded-lg font-semibold">Tutup</button>
            <button type="submit" class="bg-sky-600 hover:bg-sky-500 text-white px-5 py-2 rounded-lg font-bold">Simpan Penanganan</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- 6. MODAL TAMBAH USER / PETUGAS -->
  <div id="userModal" class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-md overflow-hidden shadow-2xl flex flex-col">
      <div class="px-5 py-4 border-b border-slate-800 flex items-center justify-between">
        <h3 id="userModalTitle" class="font-bold text-base text-white">Tambah Akun Pengguna</h3>
        <button onclick="SPBUApp.closeModal('userModal')" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark text-lg"></i></button>
      </div>
      <form id="userForm" onsubmit="SPBUApp.handleUserSubmit(event)" class="p-5 space-y-4 text-xs">
        <input type="hidden" id="userFormId">
        <div>
          <label class="block font-semibold text-slate-300 mb-1">Nama Lengkap *</label>
          <input type="text" id="userFormName" placeholder="contoh: Hendra Setiawan" required class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-sky-500">
        </div>
        <div>
          <label class="block font-semibold text-slate-300 mb-1">Alamat Email *</label>
          <input type="email" id="userFormEmail" placeholder="petugas3@spbumonitor.test" required class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-sky-500">
        </div>
        <div>
          <label class="block font-semibold text-slate-300 mb-1">Nomor Kontak / WhatsApp</label>
          <input type="text" id="userFormPhone" placeholder="0812-xxxx-xxxx" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-sky-500">
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block font-semibold text-slate-300 mb-1">Peran Akun *</label>
            <select id="userFormRole" onchange="SPBUApp.handleRoleChangeInUserModal()" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-sky-500">
              <option value="admin">Admin Logistik</option>
              <option value="warga">Warga</option>
            </select>
          </div>
          <div id="userModalStationGroup">
            <label class="block font-semibold text-slate-300 mb-1">Penugasan SPBU *</label>
            <select id="userFormStation" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-sky-500">
              <!-- Filled dynamically with SPBU list -->
            </select>
          </div>
        </div>
        <div class="pt-2 flex justify-end space-x-2 border-t border-slate-800">
          <button type="button" onclick="SPBUApp.closeModal('userModal')" class="bg-slate-800 text-slate-300 hover:bg-slate-700 px-4 py-2 rounded-lg font-semibold">Batal</button>
          <button type="submit" class="bg-sky-600 hover:bg-sky-500 text-white px-5 py-2 rounded-lg font-bold">Simpan Akun</button>
        </div>
      </form>
    </div>
  </div>

  <!-- 7. MODAL JADWALKAN / EDIT PENGIRIMAN BBM -->
  <div id="deliveryModal" class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-lg overflow-hidden shadow-2xl flex flex-col">
      <div class="px-5 py-4 border-b border-slate-800 flex items-center justify-between">
        <h3 id="deliveryModalTitle" class="font-bold text-base text-white">Jadwalkan Pengiriman BBM</h3>
        <button onclick="SPBUApp.closeModal('deliveryModal')" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark text-lg"></i></button>
      </div>
      <form id="deliveryForm" onsubmit="SPBUApp.handleDeliverySubmit(event)" class="p-5 space-y-4 text-xs">
        <input type="hidden" id="deliveryFormId">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="block font-semibold text-slate-300 mb-1">Asal Depot (TBBM) *</label>
            <select id="deliveryFormOrigin" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-sky-500">
              <option value="TBBM Plumpang">TBBM Plumpang (Jakarta)</option>
              <option value="TBBM Padalarang">TBBM Padalarang (Bandung)</option>
              <option value="TBBM Tanjung Gerem">TBBM Tanjung Gerem (Banten)</option>
            </select>
          </div>
          <div>
            <label class="block font-semibold text-slate-300 mb-1">SPBU Tujuan *</label>
            <select id="deliveryFormStation" required class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-sky-500"></select>
          </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="block font-semibold text-slate-300 mb-1">Kendaraan Pengangkut *</label>
            <input type="text" id="deliveryFormVehicle" placeholder="contoh: Truk Tangki Hino Ranger 16 KL" required class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-sky-500">
          </div>
          <div>
            <label class="block font-semibold text-slate-300 mb-1">Plat Nomor *</label>
            <input type="text" id="deliveryFormPlate" placeholder="contoh: B 9012 TBM" required class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-white uppercase focus:outline-none focus:border-sky-500">
          </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="block font-semibold text-slate-300 mb-1">Nama Sopir *</label>
            <input type="text" id="deliveryFormDriver" placeholder="contoh: Sutrisno" required class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-sky-500">
          </div>
          <div>
            <label class="block font-semibold text-slate-300 mb-1">Jenis BBM *</label>
            <select id="deliveryFormFuelType" required class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-sky-500"></select>
          </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
          <div>
            <label class="block font-semibold text-slate-300 mb-1">Volume Dibawa (Liter) *</label>
            <input type="number" id="deliveryFormQuantity" min="1000" max="40000" step="1000" value="16000" required class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-sky-500">
          </div>
          <div>
            <label class="block font-semibold text-slate-300 mb-1">Status *</label>
            <select id="deliveryFormStatus" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-sky-500">
              <option value="Dijadwalkan">Dijadwalkan</option>
              <option value="Dalam Perjalanan" selected>Dalam Perjalanan</option>
              <option value="Tiba di SPBU">Tiba di SPBU</option>
              <option value="Selesai">Selesai (Bongkar Muat)</option>
            </select>
          </div>
          <div>
            <label class="block font-semibold text-slate-300 mb-1">ETA (Menit)</label>
            <input type="number" id="deliveryFormEta" min="0" max="600" value="30" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-sky-500">
          </div>
        </div>
        <div>
          <label class="block font-semibold text-slate-300 mb-1">Catatan Pengiriman</label>
          <input type="text" id="deliveryFormNotes" placeholder="contoh: Prioritas pengisian tangki menipis" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-sky-500">
        </div>
        <div class="pt-2 flex justify-end space-x-2 border-t border-slate-800">
          <button type="button" onclick="SPBUApp.closeModal('deliveryModal')" class="bg-slate-800 text-slate-300 hover:bg-slate-700 px-4 py-2 rounded-lg font-semibold">Batal</button>
          <button type="submit" class="bg-sky-600 hover:bg-sky-500 text-white px-5 py-2 rounded-lg font-bold">Simpan Pengiriman</button>
        </div>
      </form>
    </div>
  </div>

  <!-- TOAST NOTIFICATION CONTAINER -->
  <div id="toastContainer" class="fixed bottom-5 right-5 z-50 space-y-2 pointer-events-none"></div>

  <!-- LOAD APPLICATION SCRIPT -->
  <script>window.SPBU_BOOTSTRAP = {{ Js::from($bootstrap) }};</script>
  <script src="/js/store.js"></script>
  <script src="/js/admin.js"></script>
</body>
</html>
