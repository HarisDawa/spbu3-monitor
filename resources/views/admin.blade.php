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
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
    body {
      font-family: 'Inter', sans-serif;
      background-color: #08090b;
    }
    .obsidian-card {
      background: linear-gradient(180deg, #121418 0%, #0d0f12 100%);
      border: 1px solid rgba(255, 255, 255, 0.08);
    }
    .obsidian-subcard {
      background: rgba(255, 255, 255, 0.03);
      border: 1px solid rgba(255, 255, 255, 0.07);
    }
    .custom-scrollbar::-webkit-scrollbar {
      width: 5px;
      height: 5px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
      background: #08090b;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
      background: #232730;
      border-radius: 4px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
      background: #333947;
    }
    .leaflet-popup-content-wrapper {
      background: #111317;
      color: #f1f5f9;
      border-radius: 0.75rem;
      border: 1px solid rgba(255, 255, 255, 0.12);
      box-shadow: 0 16px 32px -8px rgba(0, 0, 0, 0.75);
    }
    .leaflet-popup-tip {
      background: #111317;
    }
    .leaflet-container {
      background: #0b0d11 !important;
    }
  </style>
</head>
<body class="bg-[#08090b] text-zinc-100 min-h-screen flex flex-col antialiased selection:bg-emerald-500 selection:text-black">

  <!-- MAIN TOP NAVBAR (Obsidian Minimalist) -->
  <header class="bg-[#0a0b0e]/95 backdrop-blur border-b border-white/[0.07] sticky top-0 z-40">
    <div class="px-4 lg:px-6 h-14 flex items-center justify-between gap-4">

      <!-- Left: Sidebar Toggle + Breadcrumb -->
      <div class="flex items-center space-x-3">
        <button id="sidebarToggleBtn" class="lg:hidden text-zinc-400 hover:text-white p-2 rounded-lg hover:bg-white/[0.06]">
          <i class="fa-solid fa-bars text-base"></i>
        </button>
        <div class="flex items-center space-x-2 text-xs">
          <span class="w-6 h-6 rounded-md bg-white/[0.05] border border-white/[0.08] flex items-center justify-center text-zinc-300">
            <i class="fa-solid fa-table-columns text-[11px]"></i>
          </span>
          <span id="headerPageTitle" class="font-semibold text-white text-sm">Dashboard</span>
          <span class="hidden sm:inline-flex items-center space-x-1.5 ml-2 px-2 py-0.5 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-[10px] font-medium text-emerald-400">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
            <span id="simStatusText">Demo Live</span>
          </span>
        </div>
      </div>

      <!-- Right: Search, Quick Action & Role Switcher -->
      <div class="flex items-center space-x-2.5">
        <div class="hidden md:flex items-center bg-[#121418] border border-white/[0.08] rounded-lg px-3 py-1.5 w-60 text-xs text-zinc-400">
          <i class="fa-solid fa-magnifying-glass text-[11px] mr-2 text-zinc-500"></i>
          <input type="text" placeholder="Cari SPBU atau truk..." oninput="SPBUApp.quickSearch(this.value)" class="bg-transparent text-zinc-200 placeholder-zinc-500 focus:outline-none w-full text-xs">
          <span class="text-[10px] px-1.5 py-0.5 rounded bg-white/[0.05] border border-white/[0.08] text-zinc-500 font-mono">⌘K</span>
        </div>

        <button onclick="SPBUApp.simulateDeliveries()" class="flex items-center space-x-1.5 bg-[#15181e] hover:bg-[#1c2028] border border-white/[0.1] text-white px-3 py-1.5 rounded-lg text-xs font-semibold transition">
          <i class="fa-solid fa-sparkles text-emerald-400 text-[11px]"></i>
          <span class="hidden sm:inline">Simulasi Truk</span>
        </button>

        <button id="simTickBtn" onclick="SPBUApp.toggleLiveSimulation()" class="hidden xl:flex items-center space-x-1.5 bg-[#121418] hover:bg-white/[0.06] border border-white/[0.08] px-2.5 py-1.5 rounded-lg text-[11px] text-zinc-300 transition" title="Toggle Auto Simulasi">
          <i class="fa-regular fa-bell text-zinc-400"></i>
        </button>

        <!-- Role Switcher Dropdown -->
        <div class="relative">
          <button id="roleDropdownBtn" onclick="SPBUApp.toggleRoleMenu()" class="flex items-center space-x-2 bg-[#121418] hover:bg-white/[0.06] border border-white/[0.08] px-2.5 py-1.5 rounded-lg text-xs font-medium text-zinc-200 transition">
            <span id="currentRoleBadge" class="w-2 h-2 rounded-full bg-emerald-400"></span>
            <span id="currentUserName" class="hidden sm:inline">Admin Pusat</span>
            <i class="fa-solid fa-chevron-down text-[10px] text-zinc-500"></i>
          </button>

          <!-- Role Menu -->
          <div id="roleDropdownMenu" class="hidden absolute right-0 mt-2 w-64 bg-[#121418] border border-white/[0.1] rounded-xl shadow-2xl py-2 z-50 text-xs">
            <div class="px-3 py-1.5 border-b border-white/[0.07] text-[10px] font-bold text-zinc-500 uppercase tracking-wider">
              Ganti Peran / Akun Demo
            </div>
            <button onclick="SPBUApp.switchUser(1)" class="w-full text-left px-3 py-2 hover:bg-white/[0.05] flex items-center justify-between text-zinc-200">
              <div>
                <div class="font-semibold text-white">Admin Logistik Pusat</div>
                <div class="text-[11px] text-zinc-500">admin@spbumonitor.test</div>
              </div>
              <span class="text-[10px] bg-emerald-500/15 text-emerald-400 px-1.5 py-0.5 rounded font-semibold">Admin</span>
            </button>
            <button onclick="SPBUApp.switchUser(4)" class="w-full text-left px-3 py-2 hover:bg-white/[0.05] flex items-center justify-between text-zinc-200">
              <div>
                <div class="font-semibold text-white">Budi Santoso</div>
                <div class="text-[11px] text-zinc-500">warga@spbumonitor.test</div>
              </div>
              <span class="text-[10px] bg-sky-500/15 text-sky-400 px-1.5 py-0.5 rounded font-semibold">Warga</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </header>

  <!-- BODY CONTENT AREA (SIDEBAR + MAIN VIEW) -->
  <div class="flex-1 flex overflow-hidden">

    <!-- SIDEBAR NAV (Obsidian Reference Style) -->
    <aside id="sidebar" class="fixed lg:static inset-y-0 left-0 z-30 w-60 bg-[#0a0b0e] border-r border-white/[0.07] transform -translate-x-full lg:translate-x-0 transition-transform duration-200 ease-in-out flex flex-col justify-between custom-scrollbar overflow-y-auto">
      <div class="p-3.5 space-y-5">

        <!-- Custom Fuel Pump Logo -->
        <div class="flex items-center justify-between px-2 pt-1">
          <div class="flex items-center space-x-2.5">
            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-emerald-500/20 to-emerald-600/5 border border-emerald-500/30 flex items-center justify-center shadow-[0_0_15px_rgba(16,185,129,0.2)]">
              <!-- Fuel Pump SVG Logo -->
              <svg class="w-4 h-4 text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 22V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v17"/>
                <path d="M2 22h14"/>
                <rect x="6" y="7" width="6" height="4" rx="1"/>
                <path d="M15 9h2a2 2 0 0 1 2 2v5a2 2 0 0 0 2 2 2 2 0 0 0 2-2V9.83a2 2 0 0 0-.59-1.42L19 5"/>
              </svg>
            </div>
            <div>
              <span class="font-bold text-base text-white tracking-tight">spbu<span class="text-emerald-400">.hub</span></span>
            </div>
          </div>
          <span class="text-[10px] font-mono text-zinc-500" id="liveClock">--:--</span>
        </div>

        <!-- Navigation Menu Items -->
        <nav class="space-y-0.5">
          <button onclick="SPBUApp.switchTab('dashboard')" id="nav-dashboard" class="nav-item w-full flex items-center space-x-2.5 px-3 py-2 rounded-lg text-xs font-medium text-zinc-300 hover:bg-white/[0.05] hover:text-white transition">
            <i class="fa-solid fa-table-columns w-4 text-center text-zinc-400"></i>
            <span>Dashboard</span>
          </button>

          <button onclick="SPBUApp.switchTab('deliveries')" id="nav-deliveries" class="nav-item w-full flex items-center space-x-2.5 px-3 py-2 rounded-lg text-xs font-medium text-zinc-400 hover:bg-white/[0.05] hover:text-white transition">
            <i class="fa-solid fa-truck-fast w-4 text-center text-zinc-400"></i>
            <span>Pengiriman BBM</span>
            <span id="badgeActiveDeliveries" class="ml-auto text-[10px] bg-emerald-500/15 text-emerald-400 border border-emerald-500/30 px-1.5 py-0.5 rounded font-semibold">Live</span>
          </button>

          <button onclick="SPBUApp.switchTab('stations')" id="nav-stations" class="nav-item w-full flex items-center space-x-2.5 px-3 py-2 rounded-lg text-xs font-medium text-zinc-400 hover:bg-white/[0.05] hover:text-white transition">
            <i class="fa-solid fa-gas-pump w-4 text-center text-zinc-400"></i>
            <span>Data SPBU</span>
            <span id="badgeStationCount" class="ml-auto text-[10px] text-zinc-500 font-mono">8</span>
          </button>

          <button onclick="SPBUApp.switchTab('fuel-stocks')" id="nav-fuel-stocks" class="nav-item w-full flex items-center space-x-2.5 px-3 py-2 rounded-lg text-xs font-medium text-zinc-400 hover:bg-white/[0.05] hover:text-white transition">
            <i class="fa-solid fa-oil-well w-4 text-center text-zinc-400"></i>
            <span>Stok Tangki</span>
            <span id="badgeLowStockCount" class="ml-auto text-[10px] text-amber-400 font-semibold">2</span>
          </button>

          <button onclick="SPBUApp.switchTab('fuel-types')" id="nav-fuel-types" class="nav-item w-full flex items-center space-x-2.5 px-3 py-2 rounded-lg text-xs font-medium text-zinc-400 hover:bg-white/[0.05] hover:text-white transition">
            <i class="fa-solid fa-tags w-4 text-center text-zinc-400"></i>
            <span>Jenis BBM & Harga</span>
          </button>

          <button onclick="SPBUApp.switchTab('queues')" id="nav-queues" class="nav-item w-full flex items-center space-x-2.5 px-3 py-2 rounded-lg text-xs font-medium text-zinc-400 hover:bg-white/[0.05] hover:text-white transition">
            <i class="fa-solid fa-users-line w-4 text-center text-zinc-400"></i>
            <span>Antrean SPBU</span>
          </button>

          <button onclick="SPBUApp.switchTab('reports')" id="nav-reports" class="nav-item w-full flex items-center space-x-2.5 px-3 py-2 rounded-lg text-xs font-medium text-zinc-400 hover:bg-white/[0.05] hover:text-white transition">
            <i class="fa-solid fa-triangle-exclamation w-4 text-center text-zinc-400"></i>
            <span>Laporan Warga</span>
            <span id="badgePendingReports" class="ml-auto w-2 h-2 rounded-full bg-amber-400"></span>
          </button>

          <div class="pt-4 mt-4 border-t border-white/[0.06] space-y-0.5">
            <button onclick="SPBUApp.switchTab('users')" id="nav-users" class="nav-item w-full flex items-center space-x-2.5 px-3 py-2 rounded-lg text-xs font-medium text-zinc-400 hover:bg-white/[0.05] hover:text-white transition">
              <i class="fa-solid fa-user-shield w-4 text-center text-zinc-400"></i>
              <span>Pengguna & Akses</span>
            </button>

            <button onclick="SPBUApp.switchTab('logs')" id="nav-logs" class="nav-item w-full flex items-center space-x-2.5 px-3 py-2 rounded-lg text-xs font-medium text-zinc-400 hover:bg-white/[0.05] hover:text-white transition">
              <i class="fa-solid fa-clock-rotate-left w-4 text-center text-zinc-400"></i>
              <span>Riwayat Aktivitas</span>
            </button>

            <button onclick="SPBUApp.switchTab('settings')" id="nav-settings" class="nav-item w-full flex items-center space-x-2.5 px-3 py-2 rounded-lg text-xs font-medium text-zinc-400 hover:bg-white/[0.05] hover:text-white transition">
              <i class="fa-solid fa-gear w-4 text-center text-zinc-400"></i>
              <span>Pengaturan</span>
            </button>
          </div>
        </nav>
      </div>

      <!-- Bottom Card (Portal Warga Mobile) + User Profile -->
      <div class="p-3.5 space-y-3">
        <div class="rounded-xl bg-gradient-to-br from-sky-600/30 via-blue-700/20 to-[#111317] border border-sky-500/25 p-3 space-y-2.5">
          <div>
            <div class="text-xs font-bold text-white">Portal Warga Mobile</div>
            <p class="text-[11px] text-zinc-400 leading-snug mt-0.5">Cek stok BBM & posisi SPBU dari tampilan HP.</p>
          </div>
          <a href="{{ route('warga') }}" target="_blank" class="block w-full text-center bg-white/[0.08] hover:bg-white/[0.14] border border-white/[0.1] text-white text-[11px] font-semibold py-1.5 rounded-lg transition">
            Buka Portal Warga
          </a>
        </div>

        <div class="flex items-center space-x-2.5 px-1 pt-1 border-t border-white/[0.06]">
          <div id="userAvatar" class="w-8 h-8 rounded-full bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 flex items-center justify-center font-bold text-xs shrink-0">
            AD
          </div>
          <div class="overflow-hidden flex-1">
            <h4 id="sidebarUserName" class="text-xs font-semibold text-white truncate">Admin Pusat</h4>
            <p id="sidebarUserRole" class="text-[10px] text-zinc-500 truncate">admin@spbumonitor.test</p>
            <p id="sidebarStationAssignment" class="hidden"></p>
          </div>
        </div>
      </div>
    </aside>

    <!-- BACKDROP FOR MOBILE SIDEBAR -->
    <div id="sidebarBackdrop" onclick="SPBUApp.closeSidebar()" class="fixed inset-0 bg-slate-950/70 z-20 backdrop-blur-sm hidden lg:hidden"></div>

    <!-- MAIN VIEW CONTAINER -->
    <main class="flex-1 overflow-y-auto custom-scrollbar p-4 lg:p-6 space-y-6">

      <!-- ======================================================== -->
      <!-- SECTION 1: DASHBOARD MONITORING (Obsidian Reference Bento) -->
      <!-- ======================================================== -->
      <section id="tab-dashboard" class="tab-content space-y-5">

        <!-- TOP 2-COLUMN BENTO ROW: SYSTEM/LOGISTICS HEALTH (5 cols) + TREN FORECAST (7 cols) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">

          <!-- LEFT: Ketersediaan Logistik & Stok (Segmented Arc Gauge) -->
          <div class="lg:col-span-5 obsidian-card rounded-2xl p-5 flex flex-col justify-between space-y-4">
            <div class="flex items-center justify-between">
              <h2 class="text-sm font-semibold text-white">Ketersediaan Logistik</h2>
              <button onclick="SPBUApp.simulateDeliveries()" class="text-[11px] font-medium text-zinc-300 bg-white/[0.05] hover:bg-white/[0.1] border border-white/[0.08] px-3 py-1 rounded-lg transition">
                Simulasi Truk
              </button>
            </div>

            <!-- 3 Mini KPIs -->
            <div class="grid grid-cols-3 gap-2 pt-1">
              <div class="text-center">
                <div class="flex items-center justify-center space-x-1.5">
                  <i class="fa-solid fa-oil-well text-sky-400 text-xs"></i>
                  <span id="kpiStockPct" class="text-lg font-bold text-white">--%</span>
                </div>
                <div class="text-[11px] text-zinc-500 mt-0.5">Stok Aman</div>
              </div>
              <div class="text-center">
                <div class="flex items-center justify-center space-x-1.5">
                  <i class="fa-solid fa-truck-fast text-emerald-400 text-xs"></i>
                  <span id="kpiTruckPct" class="text-lg font-bold text-white">--%</span>
                </div>
                <div class="text-[11px] text-zinc-500 mt-0.5">Armada Aktif</div>
              </div>
              <div class="text-center">
                <div class="flex items-center justify-center space-x-1.5">
                  <i class="fa-solid fa-gas-pump text-indigo-400 text-xs"></i>
                  <span id="kpiOpenPct" class="text-lg font-bold text-white">--%</span>
                </div>
                <div class="text-[11px] text-zinc-500 mt-0.5">SPBU Buka</div>
              </div>
            </div>

            <!-- Segmented Emerald Arc Gauge -->
            <div class="relative flex flex-col items-center justify-center py-2">
              <svg id="logisticsGaugeSvg" viewBox="0 0 260 150" class="w-full max-w-[270px] overflow-visible">
                <g id="logisticsGaugeArc"></g>
              </svg>
              <div class="absolute bottom-4 flex flex-col items-center">
                <span id="gaugeCenterPct" class="text-4xl font-bold text-white tracking-tight">76%</span>
                <span class="text-[11px] text-zinc-500 mt-0.5">kapasitas stok & armada</span>
              </div>
              <div class="w-full max-w-[240px] flex justify-between text-[11px] text-zinc-500 font-mono -mt-2 px-2">
                <span>0</span>
                <span>100</span>
              </div>
            </div>

            <!-- 2x2 Status Metadata Grid -->
            <div class="grid grid-cols-2 gap-x-4 gap-y-2 pt-2 border-t border-white/[0.06] text-[11px]">
              <div class="flex items-center justify-between">
                <span class="text-zinc-500">Total SPBU:</span>
                <span class="font-semibold text-zinc-200"><span id="statTotalSPBU">8</span> unit (<span id="statBukaSPBU" class="text-emerald-400">7</span> buka)</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-zinc-500">TBBM Depot:</span>
                <span class="font-semibold text-emerald-400">Connected</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-zinc-500">Stok Menipis:</span>
                <span class="font-semibold text-amber-400"><span id="statLowStock">2</span> tangki</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-zinc-500">Laporan Warga:</span>
                <span class="font-semibold text-zinc-200"><span id="statPendingReports">2</span> menunggu</span>
              </div>
            </div>
            <!-- Hidden legacy KPI IDs for compatibility -->
            <span id="statTutupSPBU" class="hidden">1</span>
            <span id="statAvgWait" class="hidden">11 mnt</span>
          </div>

          <!-- RIGHT: Tren Pengiriman & Stok BBM + Insight Box -->
          <div class="lg:col-span-7 obsidian-card rounded-2xl p-5 flex flex-col justify-between space-y-4">
            <div class="flex items-center justify-between">
              <div class="flex items-center space-x-2">
                <i class="fa-solid fa-sparkles text-zinc-300 text-xs"></i>
                <h2 class="text-sm font-semibold text-white">Tren Pengiriman & Stok BBM</h2>
              </div>
              <button onclick="SPBUApp.triggerSimTick()" class="text-[11px] font-medium text-zinc-300 bg-white/[0.05] hover:bg-white/[0.1] border border-white/[0.08] px-3 py-1 rounded-lg transition flex items-center space-x-1.5">
                <i class="fa-solid fa-rotate text-[10px]"></i>
                <span>Refresh</span>
              </button>
            </div>

            <div class="h-56 w-full">
              <canvas id="stockTrendChart"></canvas>
            </div>

            <!-- Insight Box -->
            <div class="obsidian-subcard rounded-xl p-3.5 space-y-1.5">
              <div class="flex items-center space-x-1.5 text-xs font-semibold text-white">
                <i class="fa-solid fa-sparkles text-emerald-400 text-[11px]"></i>
                <span>Logistik Insight</span>
              </div>
              <p id="logisticsInsightText" class="text-[11px] text-zinc-400 leading-relaxed">
                Armada truk tangki aktif memantau pasokan ke SPBU prioritas. Klik <strong>Simulasi Truk</strong> untuk memperbarui posisi GPS armada atau konfirmasi bongkar muat saat tiba.
              </p>
            </div>
          </div>

        </div>

        <!-- BOTTOM 2-COLUMN BENTO ROW: PENGIRIMAN LOGISTIK TABLE (7 cols) + PETA LIVE (5 cols) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">

          <!-- LEFT: Pengiriman Logistik BBM Aktif -->
          <div class="lg:col-span-7 obsidian-card rounded-2xl p-5 space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
              <h2 class="text-sm font-semibold text-white">Pengiriman Logistik BBM</h2>
              <div class="flex items-center space-x-2">
                <button onclick="SPBUApp.openDeliveryModal()" class="text-[11px] font-medium text-white bg-emerald-600/90 hover:bg-emerald-500 px-2.5 py-1 rounded-lg transition">
                  + Jadwalkan
                </button>
                <button onclick="SPBUApp.switchTab('deliveries')" class="text-[11px] font-medium text-zinc-300 bg-white/[0.05] hover:bg-white/[0.1] border border-white/[0.08] px-2.5 py-1 rounded-lg transition">
                  Semua Pengiriman
                </button>
              </div>
            </div>

            <div class="overflow-x-auto">
              <table class="w-full text-left text-xs">
                <thead class="text-zinc-500 border-b border-white/[0.07] text-[11px] font-medium">
                  <tr>
                    <th class="py-2.5 pr-3">Kendaraan</th>
                    <th class="py-2.5 px-3">Sopir & Tujuan</th>
                    <th class="py-2.5 px-3">Volume Dibawa</th>
                    <th class="py-2.5 px-3 text-center">Status</th>
                    <th class="py-2.5 pl-3 text-right">Aksi</th>
                  </tr>
                </thead>
                <tbody id="dashboardDeliveriesTableBody" class="divide-y divide-white/[0.05] text-zinc-200">
                  <!-- Populated dynamically -->
                </tbody>
              </table>
            </div>
          </div>

          <!-- RIGHT: Peta Live Pengiriman & SPBU (Dark Map) -->
          <div class="lg:col-span-5 obsidian-card rounded-2xl p-5 flex flex-col space-y-3">
            <div class="flex items-center justify-between gap-2">
              <h2 class="text-sm font-semibold text-white">Aktivitas Pengiriman</h2>
              <div class="flex items-center bg-[#0a0b0e] border border-white/[0.08] rounded-lg p-0.5 text-[10px] font-semibold">
                <button onclick="SPBUApp.setMapFilter('ALL')" id="mapFilter-ALL" class="map-filter-btn px-2 py-1 rounded-md bg-white/[0.1] text-white">ALL</button>
                <button onclick="SPBUApp.setMapFilter('TRUK')" id="mapFilter-TRUK" class="map-filter-btn px-2 py-1 rounded-md text-zinc-400 hover:text-white">TRUK</button>
                <button onclick="SPBUApp.setMapFilter('SPBU')" id="mapFilter-SPBU" class="map-filter-btn px-2 py-1 rounded-md text-zinc-400 hover:text-white">SPBU</button>
              </div>
            </div>

            <div id="adminMap" class="w-full h-72 rounded-xl overflow-hidden border border-white/[0.08] z-10"></div>
          </div>

        </div>

        <!-- SECONDARY ROW: TANGKI KRITIS + LAPORAN WARGA -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
          <div class="lg:col-span-5 obsidian-card rounded-2xl p-5 space-y-3">
            <div class="flex items-center justify-between">
              <h3 class="font-semibold text-sm text-white">Tangki Menipis / Kritis</h3>
              <button onclick="SPBUApp.switchTab('fuel-stocks')" class="text-[11px] text-emerald-400 hover:underline font-medium">Lihat Semua →</button>
            </div>
            <div class="overflow-x-auto">
              <table class="w-full text-left text-xs">
                <thead class="text-zinc-500 border-b border-white/[0.07] text-[10px] uppercase">
                  <tr>
                    <th class="py-2">SPBU</th>
                    <th class="py-2">BBM</th>
                    <th class="py-2 text-right">Sisa</th>
                    <th class="py-2 text-center">Status</th>
                    <th class="py-2 text-right">Aksi</th>
                  </tr>
                </thead>
                <tbody id="lowStockTableBody" class="divide-y divide-white/[0.05] text-zinc-200"></tbody>
              </table>
            </div>
          </div>

          <div class="lg:col-span-5 obsidian-card rounded-2xl p-5 space-y-3">
            <div class="flex items-center justify-between">
              <h3 class="font-semibold text-sm text-white">Laporan Warga Terbaru</h3>
              <button onclick="SPBUApp.switchTab('reports')" class="text-[11px] text-emerald-400 hover:underline font-medium">Kelola →</button>
            </div>
            <div class="overflow-x-auto">
              <table class="w-full text-left text-xs">
                <thead class="text-zinc-500 border-b border-white/[0.07] text-[10px] uppercase">
                  <tr>
                    <th class="py-2">No.</th>
                    <th class="py-2">Warga</th>
                    <th class="py-2">SPBU</th>
                    <th class="py-2">Kategori</th>
                    <th class="py-2">Keluhan</th>
                    <th class="py-2 text-center">Status</th>
                    <th class="py-2 text-right">Aksi</th>
                  </tr>
                </thead>
                <tbody id="recentReportsTableBody" class="divide-y divide-white/[0.05] text-zinc-200"></tbody>
              </table>
            </div>
          </div>

          <div class="lg:col-span-2 obsidian-card rounded-2xl p-5 flex flex-col justify-between">
            <h3 class="font-semibold text-sm text-white">Status Aduan</h3>
            <div class="relative h-40 flex items-center justify-center my-2">
              <canvas id="reportChart"></canvas>
            </div>
            <p id="roleNoticeText" class="text-[10px] text-zinc-500 leading-relaxed">
              Admin Logistik Pusat aktif.
            </p>
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
