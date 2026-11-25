/**
 * SPBU Monitoring System - Admin & Petugas Controller Script
 * Menghubungkan UI Admin Desktop dengan SPBUStore (LocalStorage Reactive Engine)
 */

(function () {
  'use strict';

  let mapInstance = null;
  let adminTileLayer = null;
  let markersLayer = null;
  let stockChartInstance = null;
  let reportChartInstance = null;
  let simulationTimer = null;
  let isSimulating = true;
  let currentMapFilter = 'ALL';

  window.SPBUApp = {
    currentTab: 'dashboard',

    init() {
      // 1. Setup Live Clock
      this.updateClock();
      setInterval(() => this.updateClock(), 1000);

      // 2. Setup Subscriptions & Auto-render
      SPBUStore.subscribe(() => {
        this.renderAll();
      });

      // 3. Initialize Leaflet Map
      this.initMap();

      // 4. Initialize Charts
      this.initCharts();

      // 5. Initial Render
      this.renderAll();

      // 6. Setup Live Simulation Interval (IoT Sensor Demo)
      if (isSimulating) {
        this.startSimulation();
      }

      // 7. Setup Sidebar Toggle for Mobile
      const toggleBtn = document.getElementById('sidebarToggleBtn');
      if (toggleBtn) {
        toggleBtn.addEventListener('click', () => this.toggleSidebar());
      }
    },

    updateClock() {
      const el = document.getElementById('liveClock');
      if (el) {
        const now = new Date();
        const timeStr = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }) + ' WIB';
        el.textContent = timeStr;
      }
    },

    // ================== SIDEBAR & TAB SWITCHING ==================
    switchTab(tabId) {
      this.currentTab = tabId;
      document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
      const target = document.getElementById(`tab-${tabId}`);
      if (target) {
        target.classList.remove('hidden');
      }

      // Update sidebar nav active state
      document.querySelectorAll('.nav-item').forEach(el => {
        el.classList.remove('bg-white/[0.08]', 'text-white', 'border', 'border-white/[0.08]');
        el.classList.add('text-zinc-400');
      });
      const activeNav = document.getElementById(`nav-${tabId}`);
      if (activeNav) {
        activeNav.classList.add('bg-white/[0.08]', 'text-white', 'border', 'border-white/[0.08]');
        activeNav.classList.remove('text-zinc-400');
      }

      const titles = {
        dashboard: 'Dashboard',
        deliveries: 'Pengiriman BBM',
        stations: 'Data SPBU',
        'fuel-stocks': 'Stok Tangki',
        'fuel-types': 'Jenis BBM & Harga',
        queues: 'Antrean SPBU',
        reports: 'Laporan Warga',
        users: 'Pengguna & Akses',
        logs: 'Riwayat Aktivitas',
        settings: 'Pengaturan',
      };
      const titleEl = document.getElementById('headerPageTitle');
      if (titleEl && titles[tabId]) titleEl.textContent = titles[tabId];

      // Re-invalidate map if switching to dashboard
      if (tabId === 'dashboard' && mapInstance) {
        setTimeout(() => mapInstance.invalidateSize(), 200);
      }

      this.closeSidebar();
    },

    toggleSidebar() {
      const sidebar = document.getElementById('sidebar');
      const backdrop = document.getElementById('sidebarBackdrop');
      if (sidebar && backdrop) {
        const isClosed = sidebar.classList.contains('-translate-x-full');
        if (isClosed) {
          sidebar.classList.remove('-translate-x-full');
          backdrop.classList.remove('hidden');
        } else {
          sidebar.classList.add('-translate-x-full');
          backdrop.classList.add('hidden');
        }
      }
    },

    closeSidebar() {
      const sidebar = document.getElementById('sidebar');
      const backdrop = document.getElementById('sidebarBackdrop');
      if (sidebar && backdrop && window.innerWidth < 1024) {
        sidebar.classList.add('-translate-x-full');
        backdrop.classList.add('hidden');
      }
    },

    // ================== ROLE SWITCHER (MULTI-ROLE) ==================
    toggleRoleMenu() {
      const menu = document.getElementById('roleDropdownMenu');
      if (menu) {
        menu.classList.toggle('hidden');
      }
    },

    switchUser(userId) {
      const user = SPBUStore.setCurrentUser(userId);
      const menu = document.getElementById('roleDropdownMenu');
      if (menu) menu.classList.add('hidden');

      this.showToast(`Beralih peran ke: ${user.name} (${user.role.toUpperCase()})`, 'info');
      this.renderAll();
    },

    // ================== MAP INITIALIZATION ==================
    initMap() {
      const mapContainer = document.getElementById('adminMap');
      if (!mapContainer) return;

      // Center around Greater Jakarta / West Java
      mapInstance = L.map('adminMap', {
        zoomControl: true,
        attributionControl: false
      }).setView([-6.28, 106.85], 11);

      this.setupTileLayer();

      markersLayer = L.layerGroup().addTo(mapInstance);
      this.renderMapMarkers();
    },

    getMapboxToken() {
      return (localStorage.getItem('spbu_mapbox_token') || (window.SPBU_BOOTSTRAP && window.SPBU_BOOTSTRAP.mapbox_token) || '').trim();
    },

    setupTileLayer() {
      if (adminTileLayer && mapInstance) {
        mapInstance.removeLayer(adminTileLayer);
      }
      const token = this.getMapboxToken();
      const badge = document.getElementById('mapProviderBadge');
      const mapContainer = document.getElementById('adminMap');

      if (token && token.startsWith('pk.')) {
        if (mapContainer) mapContainer.classList.remove('dark-tiles');
        adminTileLayer = L.tileLayer('https://api.mapbox.com/styles/v1/mapbox/dark-v11/tiles/{z}/{x}/{y}?access_token=' + encodeURIComponent(token), {
          maxZoom: 19,
          tileSize: 512,
          zoomOffset: -1,
          attribution: '© Mapbox'
        });
        adminTileLayer.on('tileerror', () => {
          console.warn('Mapbox tile warning: periksa kembali validitas Mapbox access token Anda.');
        });
        if (badge) {
          badge.textContent = 'Mapbox Dark v11';
          badge.className = 'text-[9px] text-emerald-400 font-mono font-semibold';
        }
      } else {
        // Free OpenStreetMap with Obsidian Dark Theme (NO API KEY REQUIRED, NO WATERMARK)
        if (mapContainer) mapContainer.classList.add('dark-tiles');
        adminTileLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
          maxZoom: 19,
          attribution: '© OpenStreetMap'
        });
        if (badge) {
          badge.textContent = 'OSM Dark (Free)';
          badge.className = 'text-[9px] text-zinc-400 font-mono';
        }
      }
      if (mapInstance) {
        adminTileLayer.addTo(mapInstance);
      }
    },

    openMapboxModal() {
      const modal = document.getElementById('mapboxTokenModal');
      const input = document.getElementById('mapboxTokenInput');
      if (!modal) return;
      if (input) input.value = this.getMapboxToken();
      modal.classList.remove('hidden');
    },

    closeMapboxModal() {
      const modal = document.getElementById('mapboxTokenModal');
      if (modal) modal.classList.add('hidden');
    },

    saveMapboxToken() {
      const input = document.getElementById('mapboxTokenInput');
      const token = (input?.value || '').trim();
      if (token) {
        localStorage.setItem('spbu_mapbox_token', token);
        this.showToast('Token Mapbox disimpan! Peta aktif dengan Mapbox Dark v11.', 'success');
      } else {
        localStorage.removeItem('spbu_mapbox_token');
        this.showToast('Token Mapbox dikosongkan. Peta beralih ke CartoDB Dark Matter gratis.', 'info');
      }
      this.closeMapboxModal();
      this.setupTileLayer();
    },

    setMapFilter(filter) {
      currentMapFilter = filter;
      document.querySelectorAll('.map-filter-btn').forEach(btn => {
        btn.classList.remove('bg-white/[0.1]', 'text-white');
        btn.classList.add('text-zinc-400');
      });
      const active = document.getElementById(`mapFilter-${filter}`);
      if (active) {
        active.classList.add('bg-white/[0.1]', 'text-white');
        active.classList.remove('text-zinc-400');
      }
      this.renderMapMarkers();
    },

    quickSearch(query) {
      const q = (query || '').trim().toLowerCase();
      if (!q) return;
      const deliveries = SPBUStore.getDeliveries();
      const matchDelivery = deliveries.some(d => [d.vehicle_name, d.vehicle_plate, d.driver_name, d.station_name].some(v => (v || '').toLowerCase().includes(q)));
      if (matchDelivery && this.currentTab !== 'deliveries' && this.currentTab !== 'dashboard') {
        this.switchTab('deliveries');
      }
      const deliveryInput = document.getElementById('deliverySearchInput');
      if (deliveryInput) {
        deliveryInput.value = query;
        this.renderDeliveriesTable();
      }
    },

    renderMapMarkers() {
      if (!markersLayer || !mapInstance) return;
      markersLayer.clearLayers();

      const stations = SPBUStore.getStations();
      if (currentMapFilter === 'ALL' || currentMapFilter === 'SPBU') {
      stations.forEach(st => {
        const queue = SPBUStore.getQueueStatus(st.id);
        const stocks = SPBUStore.getStocks(st.id);
        const isCritical = stocks.some(s => s.status === 'Habis' || s.status === 'Menipis');

        // Color determination
        let markerColor = '#10b981'; // Green (Lancar)
        if (st.operational_status !== 'Buka') {
          markerColor = '#ef4444'; // Red
        } else if (queue.queue_level === 'Padat' || isCritical) {
          markerColor = queue.queue_level === 'Padat' ? '#ef4444' : '#f59e0b';
        } else if (queue.queue_level === 'Sedang') {
          markerColor = '#f59e0b';
        }

        const customIcon = L.divIcon({
          className: 'custom-pin',
          html: `<div style="background-color: ${markerColor}; width: 28px; height: 28px; border-radius: 50%; border: 3px solid #0f172a; box-shadow: 0 4px 10px rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; color: white; font-size: 11px; font-weight: bold;">
                  <i class="fa-solid fa-gas-pump"></i>
                 </div>`,
          iconSize: [28, 28],
          iconAnchor: [14, 14]
        });

        const stockItemsHtml = stocks.map(s => `
          <div class="flex items-center justify-between text-[11px] py-0.5 border-b border-slate-800/60">
            <span class="font-semibold text-slate-300">${s.fuel_name}</span>
            <span class="${s.status === 'Habis' ? 'text-red-400 font-bold' : (s.status === 'Menipis' ? 'text-amber-400 font-bold' : 'text-emerald-400')}">
              ${s.stock_liters.toLocaleString('id-ID')} L (${s.status})
            </span>
          </div>
        `).join('');

        const popupContent = `
          <div class="p-2 space-y-2 min-w-[220px]">
            <div class="flex items-center justify-between">
              <span class="text-[10px] font-mono text-sky-400 font-bold">${st.code}</span>
              <span class="text-[10px] px-1.5 py-0.5 rounded font-bold ${st.operational_status === 'Buka' ? 'bg-emerald-500/20 text-emerald-400' : 'bg-red-500/20 text-red-400'}">
                ${st.operational_status}
              </span>
            </div>
            <h4 class="font-bold text-sm text-white">${st.name}</h4>
            <p class="text-[11px] text-slate-400">${st.address}</p>
            <div class="bg-slate-800/80 p-1.5 rounded-lg text-[11px] flex items-center justify-between">
              <span class="text-slate-400">Antrean (${queue.vehicle_category}):</span>
              <span class="font-bold ${queue.queue_level === 'Padat' ? 'text-red-400' : (queue.queue_level === 'Sedang' ? 'text-amber-400' : 'text-emerald-400')}">
                ${queue.queue_level} (~${queue.estimated_wait_minutes} mnt)
              </span>
            </div>
            <div class="space-y-0.5 pt-1">
              <div class="text-[10px] font-bold text-slate-400 uppercase">Stok Tangki:</div>
              ${stockItemsHtml}
            </div>
            <div class="pt-2">
              <button onclick="SPBUApp.switchTab('fuel-stocks'); document.getElementById('stockStationFilter').value='${st.id}'; SPBUApp.renderStocksTable();" class="w-full bg-sky-600 hover:bg-sky-500 text-white text-[10px] font-bold py-1 px-2 rounded transition">
                Kelola Stok SPBU Ini
              </button>
            </div>
          </div>
        `;

        L.marker([st.latitude, st.longitude], { icon: customIcon })
          .bindPopup(popupContent)
          .addTo(markersLayer);
      });
      }

      if (currentMapFilter === 'ALL' || currentMapFilter === 'TRUK') {
      // Render TBBM Depots, Routes, and Active Truck Tankers
      const deliveries = SPBUStore.getDeliveries();
      const renderedDepots = new Set();

      deliveries.forEach(del => {
        if (!renderedDepots.has(del.origin_depot) && del.origin_lat && del.origin_lng) {
          renderedDepots.add(del.origin_depot);
          const depotIcon = L.divIcon({
            className: 'custom-pin',
            html: `<div style="background-color: #9333ea; width: 28px; height: 28px; border-radius: 8px; border: 2px solid #0f172a; box-shadow: 0 4px 10px rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; color: white; font-size: 12px;">
                    <i class="fa-solid fa-warehouse"></i>
                   </div>`,
            iconSize: [28, 28],
            iconAnchor: [14, 14]
          });
          L.marker([del.origin_lat, del.origin_lng], { icon: depotIcon })
            .bindPopup(`<div class="p-2 text-xs"><div class="font-bold text-purple-400">Depot Logistik</div><div class="font-bold text-white">${del.origin_depot}</div></div>`)
            .addTo(markersLayer);
        }

        if (del.status === 'Selesai') return;

        if (del.origin_lat && del.origin_lng && del.station_lat && del.station_lng) {
          L.polyline(
            [[del.origin_lat, del.origin_lng], [del.current_lat, del.current_lng], [del.station_lat, del.station_lng]],
            { color: '#38bdf8', weight: 2.5, dashArray: '6, 6', opacity: 0.75 }
          ).addTo(markersLayer);
        }

        if (del.current_lat && del.current_lng) {
          const truckColor = del.status === 'Tiba di SPBU' ? '#10b981' : (del.status === 'Dalam Perjalanan' ? '#0284c7' : '#64748b');
          const truckIcon = L.divIcon({
            className: 'custom-pin',
            html: `<div style="background-color: ${truckColor}; width: 32px; height: 32px; border-radius: 50%; border: 3px solid #f8fafc; box-shadow: 0 4px 14px rgba(2,132,199,0.7); display: flex; align-items: center; justify-content: center; color: white; font-size: 13px;">
                    <i class="fa-solid fa-truck-fast"></i>
                   </div>`,
            iconSize: [32, 32],
            iconAnchor: [16, 16]
          });

          const truckPopup = `
            <div class="p-2 space-y-1.5 min-w-[230px] text-xs">
              <div class="flex items-center justify-between">
                <span class="font-mono text-[10px] text-sky-400 font-bold">${del.id}</span>
                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-sky-500/20 text-sky-300">${del.status}</span>
              </div>
              <div class="font-bold text-white text-sm">${del.vehicle_name}</div>
              <div class="text-[11px] text-slate-300">Plat: <span class="font-mono font-bold text-amber-300">${del.vehicle_plate}</span> • Sopir: <strong>${del.driver_name}</strong></div>
              <div class="bg-slate-800/90 rounded-lg p-2 space-y-1 border border-slate-700">
                <div class="flex justify-between">
                  <span class="text-slate-400">Muatan BBM:</span>
                  <span class="font-bold text-white">${del.fuel_name}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-slate-400">Volume Dibawa:</span>
                  <span class="font-bold text-emerald-400">${Number(del.quantity_liters).toLocaleString('id-ID')} Liter</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-slate-400">Tujuan:</span>
                  <span class="font-semibold text-sky-300">${del.station_name}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-slate-400">Estimasi Tiba:</span>
                  <span class="font-bold text-white">${del.eta_minutes > 0 ? del.eta_minutes + ' menit' : 'Sudah Tiba'}</span>
                </div>
              </div>
            </div>
          `;

          L.marker([del.current_lat, del.current_lng], { icon: truckIcon, zIndexOffset: 500 })
            .bindPopup(truckPopup)
            .addTo(markersLayer);
        }
      });
      }
    },

    // ================== CHARTS INITIALIZATION ==================
    initCharts() {
      // 1. Stock Trends Line Chart
      const stockCtx = document.getElementById('stockTrendChart');
      if (stockCtx) {
        stockChartInstance = new Chart(stockCtx, {
          type: 'line',
          data: {
            labels: ['08:00', '10:00', '12:00', '14:00', '16:00', '18:00', '20:00', '22:00'],
            datasets: [
              {
                label: 'Pertalite',
                data: [145, 138, 126, 110, 102, 94, 86, 79],
                borderColor: '#0284c7',
                backgroundColor: 'rgba(2, 132, 199, 0.1)',
                fill: true,
                tension: 0.35,
                borderWidth: 2
              },
              {
                label: 'Pertamax',
                data: [92, 88, 82, 75, 71, 68, 64, 59],
                borderColor: '#2563eb',
                backgroundColor: 'rgba(37, 99, 235, 0.05)',
                fill: true,
                tension: 0.35,
                borderWidth: 2
              },
              {
                label: 'Biosolar',
                data: [160, 152, 140, 128, 120, 115, 110, 105],
                borderColor: '#16a34a',
                backgroundColor: 'rgba(22, 163, 74, 0.05)',
                fill: true,
                tension: 0.35,
                borderWidth: 2
              }
            ]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                labels: { color: '#94a3b8', font: { size: 11 } }
              }
            },
            scales: {
              x: {
                grid: { color: 'rgba(51, 65, 85, 0.3)' },
                ticks: { color: '#94a3b8', font: { size: 10 } }
              },
              y: {
                grid: { color: 'rgba(51, 65, 85, 0.3)' },
                ticks: { color: '#94a3b8', font: { size: 10 } }
              }
            }
          }
        });
      }

      // 2. Report Status Doughnut Chart
      const reportCtx = document.getElementById('reportChart');
      if (reportCtx) {
        reportChartInstance = new Chart(reportCtx, {
          type: 'doughnut',
          data: {
            labels: ['Menunggu', 'Diverifikasi', 'Ditindaklanjuti', 'Selesai', 'Ditolak'],
            datasets: [{
              data: [2, 2, 2, 3, 1],
              backgroundColor: ['#f43f5e', '#38bdf8', '#fbbf24', '#10b981', '#64748b'],
              borderWidth: 0,
              hoverOffset: 4
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                position: 'right',
                labels: { color: '#94a3b8', boxWidth: 10, font: { size: 10 } }
              }
            },
            cutout: '70%'
          }
        });
      }
    },

    updateReportChart() {
      if (!reportChartInstance) return;
      const reports = SPBUStore.getReports();
      const counts = {
        'Menunggu': 0,
        'Diverifikasi': 0,
        'Ditindaklanjuti': 0,
        'Selesai': 0,
        'Ditolak': 0
      };
      reports.forEach(r => {
        if (counts[r.status] !== undefined) counts[r.status]++;
      });
      reportChartInstance.data.datasets[0].data = [
        counts['Menunggu'],
        counts['Diverifikasi'],
        counts['Ditindaklanjuti'],
        counts['Selesai'],
        counts['Ditolak']
      ];
      reportChartInstance.update();
    },

    // ================== RENDER ALL MODULES ==================
    renderAll() {
      this.renderUserBadge();
      this.renderDashboardKPI();
      this.renderLogisticsGauge();
      this.renderMapMarkers();
      this.renderDeliveriesTable();
      this.updateReportChart();
      this.renderLowStockTable();
      this.renderRecentReportsTable();
      this.renderStationsTable();
      this.renderStocksTable();
      this.renderFuelTypesGrid();
      this.renderQueuesTable();
      this.renderReportsTable();
      this.renderUsersTable();
      this.renderLogsTable();
      this.populateStationDropdowns();
    },

    renderUserBadge() {
      const user = SPBUStore.getCurrentUser();
      const currentUserNameEl = document.getElementById('currentUserName');
      const sidebarUserNameEl = document.getElementById('sidebarUserName');
      const sidebarUserRoleEl = document.getElementById('sidebarUserRole');
      const userAvatarEl = document.getElementById('userAvatar');
      const currentRoleBadgeEl = document.getElementById('currentRoleBadge');
      const sidebarStationAssignmentEl = document.getElementById('sidebarStationAssignment');
      const roleNoticeTextEl = document.getElementById('roleNoticeText');

      if (currentUserNameEl) currentUserNameEl.textContent = user.name;
      if (sidebarUserNameEl) sidebarUserNameEl.textContent = user.name;
      if (sidebarUserRoleEl) sidebarUserRoleEl.textContent = user.role.toUpperCase();

      const initials = user.name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
      if (userAvatarEl) userAvatarEl.textContent = initials;

      if (currentRoleBadgeEl) {
        currentRoleBadgeEl.className = 'w-2 h-2 rounded-full ' + (user.role === 'admin' ? 'bg-sky-400' : (user.role === 'petugas' ? 'bg-amber-400' : 'bg-emerald-400'));
      }

      if (sidebarStationAssignmentEl) {
        if (user.role === 'petugas' && user.station_id) {
          sidebarStationAssignmentEl.classList.remove('hidden');
          sidebarStationAssignmentEl.textContent = `Unit: ${user.station_id}`;
        } else {
          sidebarStationAssignmentEl.classList.add('hidden');
        }
      }

      if (roleNoticeTextEl) {
        if (user.role === 'admin') {
          roleNoticeTextEl.innerHTML = `Anda masuk sebagai <strong>Admin Pusat</strong>. Memiliki wewenang penuh untuk mengelola master data SPBU, stok minyak, harga BBM, dan memproses laporan masyarakat.`;
        } else if (user.role === 'petugas') {
          roleNoticeTextEl.innerHTML = `Anda masuk sebagai <strong>Petugas Lapangan (${user.station_id || 'Umum'})</strong>. Memiliki akses langsung untuk memperbarui stok minyak tangki pendam dan kondisi antrean SPBU penugasan.`;
        } else {
          roleNoticeTextEl.innerHTML = `Anda masuk sebagai <strong>Warga (${user.name})</strong>. Tinjau ketersediaan minyak real-time atau ajukan aduan langsung dari portal mobile.`;
        }
      }
    },

    renderDashboardKPI() {
      const stations = SPBUStore.getStations();
      const bukaCount = stations.filter(s => s.operational_status === 'Buka').length;
      const tutupCount = stations.filter(s => s.operational_status !== 'Buka').length;
      const stocks = SPBUStore.getStocks();
      const lowStockCount = stocks.filter(s => s.status === 'Menipis' || s.status === 'Habis').length;
      const reports = SPBUStore.getReports();
      const pendingReports = reports.filter(r => r.status === 'Menunggu').length;
      const queues = stations.map(s => SPBUStore.getQueueStatus(s.id));
      const avgWait = Math.round(queues.reduce((acc, q) => acc + (q.estimated_wait_minutes || 0), 0) / (queues.length || 1));

      // Elements
      document.getElementById('statTotalSPBU').textContent = stations.length;
      document.getElementById('statBukaSPBU').textContent = bukaCount;
      document.getElementById('statTutupSPBU').textContent = tutupCount;
      document.getElementById('statLowStock').textContent = lowStockCount;
      document.getElementById('statPendingReports').textContent = pendingReports;
      document.getElementById('statAvgWait').textContent = `${avgWait} mnt`;

      // Sidebar badges
      document.getElementById('badgeStationCount').textContent = stations.length;
      document.getElementById('badgeLowStockCount').textContent = `${lowStockCount} Kritis`;
      document.getElementById('badgePendingReports').textContent = `${pendingReports} Menunggu`;
    },

    renderLowStockTable() {
      const tbody = document.getElementById('lowStockTableBody');
      if (!tbody) return;

      const stocks = SPBUStore.getStocks();
      const criticalStocks = stocks
        .filter(s => s.status === 'Menipis' || s.status === 'Habis')
        .sort((a, b) => a.stock_liters - b.stock_liters)
        .slice(0, 5);

      if (criticalStocks.length === 0) {
        tbody.innerHTML = `<tr><td colspan="5" class="py-4 text-center text-slate-500">Semua tangki BBM dalam kondisi aman (stok normal).</td></tr>`;
        return;
      }

      tbody.innerHTML = criticalStocks.map(s => `
        <tr class="hover:bg-slate-800/40">
          <td class="py-2.5">
            <div class="font-bold text-white">${s.station_name}</div>
            <div class="text-[10px] text-slate-400">${s.station_region}</div>
          </td>
          <td class="py-2.5">
            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold" style="background-color: ${s.fuel_color}20; color: ${s.fuel_color};">
              ${s.fuel_name}
            </span>
          </td>
          <td class="py-2.5 text-right font-mono font-bold text-slate-200">
            ${s.stock_liters.toLocaleString('id-ID')} / ${s.capacity_liters.toLocaleString('id-ID')} L
          </td>
          <td class="py-2.5 text-center">
            <span class="px-2 py-0.5 rounded-full text-[10px] font-black ${s.status === 'Habis' ? 'bg-red-500/20 text-red-400 border border-red-500/30' : 'bg-amber-500/20 text-amber-300 border border-amber-500/30'}">
              ${s.status}
            </span>
          </td>
          <td class="py-2.5 text-right">
            <button onclick="SPBUApp.openStockModal(${s.id})" class="bg-sky-600 hover:bg-sky-500 text-white px-2.5 py-1 rounded text-[11px] font-bold transition">
              Restock
            </button>
          </td>
        </tr>
      `).join('');
    },

    renderRecentReportsTable() {
      const tbody = document.getElementById('recentReportsTableBody');
      if (!tbody) return;

      const reports = SPBUStore.getReports().slice(0, 5);
      if (reports.length === 0) {
        tbody.innerHTML = `<tr><td colspan="7" class="py-4 text-center text-slate-500">Belum ada aduan masyarakat yang masuk.</td></tr>`;
        return;
      }

      tbody.innerHTML = reports.map(r => `
        <tr class="hover:bg-slate-800/40">
          <td class="py-2.5 font-mono text-sky-400 font-bold">${r.report_code}</td>
          <td class="py-2.5 font-semibold text-slate-200">${r.user_name}</td>
          <td class="py-2.5 text-slate-300">${r.station_name}</td>
          <td class="py-2.5"><span class="bg-slate-800 text-slate-300 px-2 py-0.5 rounded text-[10px]">${r.category}</span></td>
          <td class="py-2.5 text-slate-400 max-w-xs truncate">${r.description}</td>
          <td class="py-2.5 text-center">
            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold ${this.getReportStatusBadgeClass(r.status)}">
              ${r.status}
            </span>
          </td>
          <td class="py-2.5 text-right">
            <button onclick="SPBUApp.openReportModal('${r.id}')" class="bg-slate-800 hover:bg-slate-700 text-sky-400 px-2.5 py-1 rounded text-[11px] font-semibold transition">
              Tindak Lanjuti
            </button>
          </td>
        </tr>
      `).join('');
    },

    // ================== STATIONS CRUD ==================
    filterStations() {
      this.renderStationsTable();
    },

    renderStationsTable() {
      const tbody = document.getElementById('stationsTableBody');
      if (!tbody) return;

      const search = (document.getElementById('stationSearchInput')?.value || '').toLowerCase();
      const region = document.getElementById('stationRegionFilter')?.value || 'Semua';
      const status = document.getElementById('stationStatusFilter')?.value || 'Semua';

      let list = SPBUStore.getStations();
      if (search) {
        list = list.filter(s => s.name.toLowerCase().includes(search) || s.code.toLowerCase().includes(search) || s.address.toLowerCase().includes(search));
      }
      if (region !== 'Semua') {
        list = list.filter(s => s.region === region);
      }
      if (status !== 'Semua') {
        list = list.filter(s => s.operational_status === status);
      }

      if (list.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6" class="py-6 text-center text-slate-500">Tidak ada SPBU yang cocok dengan kriteria pencarian.</td></tr>`;
        return;
      }

      tbody.innerHTML = list.map(st => {
        const queue = SPBUStore.getQueueStatus(st.id);
        const statusBadgeClass = st.operational_status === 'Buka' ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : (st.operational_status === 'Tutup' ? 'bg-red-500/20 text-red-400 border border-red-500/30' : 'bg-amber-500/20 text-amber-300 border border-amber-500/30');

        return `
          <tr class="hover:bg-slate-800/40 transition">
            <td class="py-3 px-4">
              <div class="font-bold text-white text-sm">${st.name}</div>
              <div class="font-mono text-sky-400 text-[11px]">Kode: ${st.code}</div>
            </td>
            <td class="py-3 px-4">
              <div class="text-slate-300 font-semibold">${st.region}</div>
              <div class="text-slate-400 text-[11px] max-w-xs truncate">${st.address}</div>
            </td>
            <td class="py-3 px-4 font-mono text-slate-300">${st.opening_hours}</td>
            <td class="py-3 px-4 text-center">
              <button onclick="SPBUApp.toggleOperationalStatus('${st.id}')" title="Klik untuk ubah status" class="px-2.5 py-1 rounded-full text-[10px] font-black cursor-pointer hover:opacity-80 transition ${statusBadgeClass}">
                ${st.operational_status}
              </button>
            </td>
            <td class="py-3 px-4 text-center">
              <span class="font-bold text-xs ${queue.queue_level === 'Padat' ? 'text-red-400' : (queue.queue_level === 'Sedang' ? 'text-amber-400' : 'text-emerald-400')}">
                ${queue.queue_level} (~${queue.estimated_wait_minutes} mnt)
              </span>
            </td>
            <td class="py-3 px-4 text-right space-x-1">
              <button onclick="SPBUApp.openStationModal('${st.id}')" title="Edit Data" class="bg-slate-800 hover:bg-slate-700 text-sky-400 p-2 rounded-lg transition">
                <i class="fa-solid fa-pen-to-square"></i>
              </button>
              <button onclick="SPBUApp.deleteStation('${st.id}')" title="Hapus / Nonaktifkan SPBU" class="bg-slate-800 hover:bg-red-500/20 text-red-400 p-2 rounded-lg transition">
                <i class="fa-solid fa-trash"></i>
              </button>
            </td>
          </tr>
        `;
      }).join('');
    },

    openStationModal(id = null) {
      const modal = document.getElementById('stationModal');
      const title = document.getElementById('stationModalTitle');
      const form = document.getElementById('stationForm');

      if (!modal || !form) return;
      form.reset();

      if (id) {
        const st = SPBUStore.getStationById(id);
        if (st) {
          title.textContent = 'Edit Data SPBU';
          document.getElementById('stationFormId').value = st.id;
          document.getElementById('stationFormCode').value = st.code;
          document.getElementById('stationFormCode').disabled = true;
          document.getElementById('stationFormName').value = st.name;
          document.getElementById('stationFormRegion').value = st.region;
          document.getElementById('stationFormAddress').value = st.address;
          document.getElementById('stationFormLat').value = st.latitude;
          document.getElementById('stationFormLng').value = st.longitude;
          document.getElementById('stationFormPhone').value = st.phone || '';
          document.getElementById('stationFormHours').value = st.opening_hours || '24 Jam';
          document.getElementById('stationFormStatus').value = st.operational_status;
        }
      } else {
        title.textContent = 'Tambah SPBU Baru';
        document.getElementById('stationFormId').value = '';
        document.getElementById('stationFormCode').disabled = false;
        document.getElementById('stationFormHours').value = '24 Jam';
      }

      modal.classList.remove('hidden');
    },

    handleStationSubmit(event) {
      event.preventDefault();
      const id = document.getElementById('stationFormId').value;
      const code = document.getElementById('stationFormCode').value.trim();
      const name = document.getElementById('stationFormName').value.trim();
      const region = document.getElementById('stationFormRegion').value.trim();
      const address = document.getElementById('stationFormAddress').value.trim();
      const lat = parseFloat(document.getElementById('stationFormLat').value);
      const lng = parseFloat(document.getElementById('stationFormLng').value);
      const phone = document.getElementById('stationFormPhone').value.trim();
      const hours = document.getElementById('stationFormHours').value.trim();
      const status = document.getElementById('stationFormStatus').value;

      if (!code || !name || !region || !address) {
        this.showToast('Harap lengkapi semua kolom wajib!', 'error');
        return;
      }

      if (id) {
        SPBUStore.updateStation(id, {
          name, region, address, latitude: lat, longitude: lng, phone, opening_hours: hours, operational_status: status
        });
        this.showToast(`Data SPBU ${name} berhasil diperbarui!`, 'success');
      } else {
        SPBUStore.addStation({
          code, name, region, address, latitude: lat, longitude: lng, phone, opening_hours: hours, operational_status: status
        });
        this.showToast(`SPBU baru ${name} (${code}) berhasil ditambahkan!`, 'success');
      }

      this.closeModal('stationModal');
      this.renderAll();
    },

    toggleOperationalStatus(id) {
      const nextStatus = SPBUStore.toggleStationStatus(id);
      this.showToast(`Status operasional diubah menjadi: ${nextStatus}`, 'info');
      this.renderAll();
    },

    deleteStation(id) {
      const st = SPBUStore.getStationById(id);
      if (!st) return;

      if (confirm(`Apakah Anda yakin ingin menonaktifkan data ${st.name} (${st.code})? Data histori tetap tersimpan untuk audit.`)) {
        SPBUStore.deleteStation(id);
        this.showToast(`SPBU ${st.name} berhasil dinonaktifkan!`, 'success');
        this.renderAll();
      }
    },

    // ================== FUEL STOCKS CRUD ==================
    renderStocksTable() {
      const tbody = document.getElementById('stocksTableBody');
      if (!tbody) return;

      const stationFilter = document.getElementById('stockStationFilter')?.value || 'Semua';
      const statusFilter = document.getElementById('stockStatusFilter')?.value || 'Semua';

      let stocks = SPBUStore.getStocks(stationFilter === 'Semua' ? null : stationFilter);
      if (statusFilter !== 'Semua') {
        stocks = stocks.filter(s => s.status === statusFilter);
      }

      if (stocks.length === 0) {
        tbody.innerHTML = `<tr><td colspan="8" class="py-6 text-center text-slate-500">Tidak ada catatan stok tangki yang sesuai filter.</td></tr>`;
        return;
      }

      tbody.innerHTML = stocks.map(s => {
        let barColor = 'bg-emerald-500';
        let statusBadge = 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30';
        if (s.status === 'Habis') {
          barColor = 'bg-red-500';
          statusBadge = 'bg-red-500/20 text-red-400 border border-red-500/30';
        } else if (s.status === 'Menipis') {
          barColor = 'bg-amber-500';
          statusBadge = 'bg-amber-500/20 text-amber-300 border border-amber-500/30';
        }

        return `
          <tr class="hover:bg-slate-800/40 transition">
            <td class="py-3 px-4">
              <div class="font-bold text-white">${s.station_name}</div>
              <div class="text-[10px] text-slate-400">${s.station_region}</div>
            </td>
            <td class="py-3 px-4">
              <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold" style="background-color: ${s.fuel_color}20; color: ${s.fuel_color};">
                ${s.fuel_name} (${s.fuel_ron})
              </span>
            </td>
            <td class="py-3 px-4 font-mono text-slate-300">${s.capacity_liters.toLocaleString('id-ID')} L</td>
            <td class="py-3 px-4">
              <div class="flex items-center justify-between text-xs font-mono font-bold text-white mb-1">
                <span>${s.stock_liters.toLocaleString('id-ID')} L</span>
                <span class="text-[10px] text-slate-400">${s.percentage}%</span>
              </div>
              <div class="w-full h-1.5 bg-slate-800 rounded-full overflow-hidden">
                <div class="${barColor} h-full rounded-full transition-all duration-300" style="width: ${Math.min(s.percentage, 100)}%"></div>
              </div>
            </td>
            <td class="py-3 px-4 font-mono text-amber-400">${s.low_stock_threshold.toLocaleString('id-ID')} L</td>
            <td class="py-3 px-4 text-center">
              <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black ${statusBadge}">
                ${s.status}
              </span>
            </td>
            <td class="py-3 px-4 text-[11px] text-slate-400">
              <div>${s.updated_at}</div>
              <div class="text-[10px] text-slate-500">Oleh: ${s.updated_by}</div>
            </td>
            <td class="py-3 px-4 text-right">
              <button onclick="SPBUApp.openStockModal(${s.id})" class="bg-sky-600 hover:bg-sky-500 text-white px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center space-x-1.5 ml-auto">
                <i class="fa-solid fa-pen"></i>
                <span>Update</span>
              </button>
            </td>
          </tr>
        `;
      }).join('');
    },

    openStockModal(stockId) {
      const modal = document.getElementById('stockModal');
      const stocks = SPBUStore.getStocks();
      const item = stocks.find(s => s.id === Number(stockId));
      if (!modal || !item) return;

      document.getElementById('stockFormId').value = item.id;
      document.getElementById('stockModalSubtitle').textContent = `${item.fuel_name} — ${item.station_name}`;
      document.getElementById('stockModalCapacity').textContent = `${item.capacity_liters.toLocaleString('id-ID')} Liter`;
      document.getElementById('stockModalCurrent').textContent = `${item.stock_liters.toLocaleString('id-ID')} Liter`;
      document.getElementById('stockModalThreshold').textContent = `${item.low_stock_threshold.toLocaleString('id-ID')} Liter`;
      document.getElementById('stockFormLiters').value = item.stock_liters;
      document.getElementById('stockFormLiters').max = item.capacity_liters;
      document.getElementById('stockFormNote').value = '';

      modal.classList.remove('hidden');
    },

    handleStockSubmit(event) {
      event.preventDefault();
      const stockId = document.getElementById('stockFormId').value;
      const liters = document.getElementById('stockFormLiters').value;
      const note = document.getElementById('stockFormNote').value.trim();

      const result = SPBUStore.updateStock(stockId, liters, note);
      if (!result.success) {
        this.showToast(result.message, 'error');
        return;
      }

      this.showToast(`Stok berhasil diperbarui menjadi ${parseInt(liters).toLocaleString('id-ID')} Liter!`, 'success');
      this.closeModal('stockModal');
      this.renderAll();
    },

    openStockHistoryModal(stationId = null) {
      const modal = document.getElementById('stockHistoryModal');
      const tbody = document.getElementById('stockHistoriesTableBody');
      if (!modal || !tbody) return;

      const histories = SPBUStore.getStockHistories(stationId);
      if (histories.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6" class="py-4 text-center text-slate-500">Belum ada riwayat mutasi stok tercatat.</td></tr>`;
      } else {
        tbody.innerHTML = histories.map(h => `
          <tr class="hover:bg-slate-800/40">
            <td class="py-2.5 font-mono text-slate-400 text-[11px]">${h.created_at}</td>
            <td class="py-2.5">
              <span class="font-bold text-white">${h.fuel_name}</span>
              <div class="text-[10px] text-slate-400">${h.station_id}</div>
            </td>
            <td class="py-2.5 text-right font-mono font-bold ${h.change_amount >= 0 ? 'text-emerald-400' : 'text-red-400'}">
              ${h.change_amount >= 0 ? '+' : ''}${h.change_amount.toLocaleString('id-ID')} L
            </td>
            <td class="py-2.5 text-right font-mono text-slate-200">
              ${h.new_stock.toLocaleString('id-ID')} L
            </td>
            <td class="py-2.5 text-slate-300 max-w-xs truncate">${h.note}</td>
            <td class="py-2.5 text-slate-400">${h.updated_by}</td>
          </tr>
        `).join('');
      }

      modal.classList.remove('hidden');
    },

    // ================== FUEL TYPES CRUD ==================
    renderFuelTypesGrid() {
      const container = document.getElementById('fuelTypesGrid');
      if (!container) return;

      const types = SPBUStore.getFuelTypes(false);
      container.innerHTML = types.map(f => `
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 flex flex-col justify-between hover:border-sky-500/30 transition shadow-lg">
          <div>
            <div class="flex items-center justify-between mb-2">
              <span class="px-2.5 py-0.5 rounded-full text-xs font-bold" style="background-color: ${f.color}20; color: ${f.color};">
                ${f.ron}
              </span>
              <span class="text-[10px] font-bold px-2 py-0.5 rounded ${f.is_active ? 'bg-emerald-500/20 text-emerald-400' : 'bg-red-500/20 text-red-400'}">
                ${f.is_active ? 'Aktif Dijual' : 'Non-aktif'}
              </span>
            </div>
            <h3 class="text-lg font-black text-white">${f.name}</h3>
            <p class="text-xs text-slate-400 my-2 leading-relaxed min-h-[36px]">${f.description}</p>
          </div>
          <div class="pt-3 border-t border-slate-800 flex items-center justify-between">
            <div>
              <span class="text-[10px] text-slate-500 uppercase font-bold block">Harga Jual:</span>
              <span class="text-xl font-black font-mono text-sky-400">Rp ${f.price_per_liter.toLocaleString('id-ID')}</span>
              <span class="text-[10px] text-slate-400">/ liter</span>
            </div>
            <div class="flex space-x-1.5">
              <button onclick="SPBUApp.openFuelTypeModal(${f.id})" class="bg-slate-800 hover:bg-slate-700 text-sky-400 px-3 py-1.5 rounded-lg text-xs font-bold transition">
                <i class="fa-solid fa-pen-to-square mr-1"></i> Edit
              </button>
              <button onclick="SPBUApp.toggleFuelTypeActive(${f.id})" title="${f.is_active ? 'Nonaktifkan' : 'Aktifkan'}" class="bg-slate-800 hover:bg-slate-700 ${f.is_active ? 'text-amber-400' : 'text-emerald-400'} px-2.5 py-1.5 rounded-lg text-xs font-bold transition">
                <i class="fa-solid ${f.is_active ? 'fa-ban' : 'fa-check'}"></i>
              </button>
            </div>
          </div>
        </div>
      `).join('');
    },

    openFuelTypeModal(id = null) {
      const modal = document.getElementById('fuelTypeModal');
      const title = document.getElementById('fuelTypeModalTitle');
      const form = document.getElementById('fuelTypeForm');
      if (!modal || !form) return;
      form.reset();

      if (id) {
        const item = SPBUStore.getFuelTypeById(id);
        if (item) {
          title.textContent = `Penyesuaian Harga: ${item.name}`;
          document.getElementById('fuelTypeFormId').value = item.id;
          document.getElementById('fuelTypeFormName').value = item.name;
          document.getElementById('fuelTypeFormRon').value = item.ron;
          document.getElementById('fuelTypeFormPrice').value = item.price_per_liter;
          document.getElementById('fuelTypeFormDesc').value = item.description;
        }
      } else {
        title.textContent = 'Tambah Jenis BBM Baru';
        document.getElementById('fuelTypeFormId').value = '';
      }

      modal.classList.remove('hidden');
    },

    handleFuelTypeSubmit(event) {
      event.preventDefault();
      const id = document.getElementById('fuelTypeFormId').value;
      const name = document.getElementById('fuelTypeFormName').value.trim();
      const ron = document.getElementById('fuelTypeFormRon').value.trim();
      const price = parseInt(document.getElementById('fuelTypeFormPrice').value);
      const desc = document.getElementById('fuelTypeFormDesc').value.trim();

      if (id) {
        SPBUStore.updateFuelType(id, { name, ron, price_per_liter: price, description: desc });
        this.showToast(`Harga BBM ${name} berhasil disesuaikan!`, 'success');
      } else {
        SPBUStore.addFuelType({ name, ron, price_per_liter: price, description: desc });
        this.showToast(`Jenis BBM ${name} berhasil didaftarkan!`, 'success');
      }

      this.closeModal('fuelTypeModal');
      this.renderAll();
    },

    toggleFuelTypeActive(id) {
      SPBUStore.deleteFuelType(id);
      this.renderAll();
    },

    // ================== MONITORING ANTREAN ==================
    renderQueuesTable() {
      const tbody = document.getElementById('queuesTableBody');
      if (!tbody) return;

      const stations = SPBUStore.getStations();
      tbody.innerHTML = stations.map(st => {
        const q = SPBUStore.getQueueStatus(st.id);
        const levelClass = q.queue_level === 'Padat' ? 'bg-red-500/20 text-red-400 border border-red-500/30' : (q.queue_level === 'Sedang' ? 'bg-amber-500/20 text-amber-300 border border-amber-500/30' : 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30');

        return `
          <tr class="hover:bg-slate-800/40 transition">
            <td class="py-3 px-4 font-bold text-white">${st.name}</td>
            <td class="py-3 px-4 text-slate-300">${st.region}</td>
            <td class="py-3 px-4 text-slate-400">${q.vehicle_category}</td>
            <td class="py-3 px-4 text-center">
              <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black ${levelClass}">
                ${q.queue_level}
              </span>
            </td>
            <td class="py-3 px-4 text-center font-mono font-bold text-white">
              ~${q.estimated_wait_minutes} menit
            </td>
            <td class="py-3 px-4">
              <span class="text-[10px] bg-slate-800 text-slate-300 px-2 py-0.5 rounded font-mono">${q.source}</span>
            </td>
            <td class="py-3 px-4 text-[11px] text-slate-400">
              <div>${q.updated_at}</div>
              <div class="text-[10px] text-slate-500">${q.updated_by}</div>
            </td>
            <td class="py-3 px-4 text-right">
              <div class="flex justify-end space-x-1">
                <button onclick="SPBUApp.quickUpdateQueue('${st.id}', 'Lancar', 5)" class="px-2 py-1 bg-emerald-500/20 hover:bg-emerald-500/40 text-emerald-400 rounded text-[10px] font-bold">
                  Lancar
                </button>
                <button onclick="SPBUApp.quickUpdateQueue('${st.id}', 'Sedang', 15)" class="px-2 py-1 bg-amber-500/20 hover:bg-amber-500/40 text-amber-300 rounded text-[10px] font-bold">
                  Sedang
                </button>
                <button onclick="SPBUApp.quickUpdateQueue('${st.id}', 'Padat', 30)" class="px-2 py-1 bg-red-500/20 hover:bg-red-500/40 text-red-400 rounded text-[10px] font-bold">
                  Padat
                </button>
              </div>
            </td>
          </tr>
        `;
      }).join('');
    },

    quickUpdateQueue(stationId, level, waitMinutes) {
      SPBUStore.updateQueueStatus(stationId, {
        queue_level: level,
        estimated_wait_minutes: waitMinutes,
        source: 'Petugas'
      });
      this.showToast(`Status antrean diperbarui: ${level} (~${waitMinutes} mnt)`, 'success');
      this.renderAll();
    },

    // ================== CITIZEN REPORTS CRUD ==================
    filterReports() {
      this.renderReportsTable();
    },

    renderReportsTable() {
      const tbody = document.getElementById('reportsTableBody');
      if (!tbody) return;

      const search = (document.getElementById('reportSearchInput')?.value || '').toLowerCase();
      const status = document.getElementById('reportStatusFilter')?.value || 'Semua';
      const category = document.getElementById('reportCategoryFilter')?.value || 'Semua';

      let list = SPBUStore.getReports();
      if (search) {
        list = list.filter(r => r.report_code.toLowerCase().includes(search) || r.user_name.toLowerCase().includes(search) || r.description.toLowerCase().includes(search));
      }
      if (status !== 'Semua') {
        list = list.filter(r => r.status === status);
      }
      if (category !== 'Semua') {
        list = list.filter(r => r.category === category);
      }

      if (list.length === 0) {
        tbody.innerHTML = `<tr><td colspan="8" class="py-6 text-center text-slate-500">Tidak ada laporan warga yang cocok dengan filter.</td></tr>`;
        return;
      }

      tbody.innerHTML = list.map(r => `
        <tr class="hover:bg-slate-800/40 transition">
          <td class="py-3 px-4 font-mono font-bold text-sky-400">${r.report_code}</td>
          <td class="py-3 px-4 font-semibold text-white">${r.user_name}</td>
          <td class="py-3 px-4 text-slate-300">${r.station_name}</td>
          <td class="py-3 px-4"><span class="bg-slate-800 text-slate-300 px-2 py-0.5 rounded text-[10px]">${r.category}</span></td>
          <td class="py-3 px-4">
            ${r.photo_url ? `<span class="text-sky-400 mr-1"><i class="fa-solid fa-camera"></i></span>` : ''}
            <span class="text-slate-400 text-xs">${r.photo_url ? 'Ada Foto' : 'Teks'}</span>
          </td>
          <td class="py-3 px-4 text-center">
            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black ${this.getReportStatusBadgeClass(r.status)}">
              ${r.status}
            </span>
          </td>
          <td class="py-3 px-4 text-slate-400 text-[11px]">${r.created_at}</td>
          <td class="py-3 px-4 text-right">
            <button onclick="SPBUApp.openReportModal('${r.id}')" class="bg-sky-600 hover:bg-sky-500 text-white px-3 py-1.5 rounded-lg text-xs font-bold transition">
              Proses
            </button>
          </td>
        </tr>
      `).join('');
    },

    openReportModal(id) {
      const modal = document.getElementById('reportDetailModal');
      const item = SPBUStore.getReportById(id);
      if (!modal || !item) return;

      document.getElementById('reportFormId').value = item.id;
      document.getElementById('reportModalCode').textContent = item.report_code;
      document.getElementById('reportModalUser').textContent = item.user_name;
      document.getElementById('reportModalStation').textContent = item.station_name;
      document.getElementById('reportModalCategory').textContent = item.category;
      document.getElementById('reportModalTime').textContent = item.created_at;
      document.getElementById('reportModalDesc').textContent = item.description;

      const photoCont = document.getElementById('reportModalPhotoContainer');
      const photoImg = document.getElementById('reportModalPhoto');
      if (item.photo_url) {
        photoCont.classList.remove('hidden');
        photoImg.src = item.photo_url;
      } else {
        photoCont.classList.add('hidden');
        photoImg.src = '';
      }

      document.getElementById('reportFormStatus').value = item.status;
      document.getElementById('reportFormNote').value = item.admin_note || '';

      modal.classList.remove('hidden');
    },

    handleReportSubmit(event) {
      event.preventDefault();
      const id = document.getElementById('reportFormId').value;
      const status = document.getElementById('reportFormStatus').value;
      const note = document.getElementById('reportFormNote').value.trim();

      SPBUStore.updateReportStatus(id, status, note);
      this.showToast(`Laporan #${id} status diubah ke: ${status}`, 'success');
      this.closeModal('reportDetailModal');
      this.renderAll();
    },

    deleteReportCurrent() {
      const id = document.getElementById('reportFormId').value;
      if (confirm(`Hapus laporan #${id} (dianggap spam/duplikat)?`)) {
        SPBUStore.deleteReport(id);
        this.showToast(`Laporan #${id} berhasil dihapus!`, 'success');
        this.closeModal('reportDetailModal');
        this.renderAll();
      }
    },

    getReportStatusBadgeClass(st) {
      switch (st) {
        case 'Menunggu': return 'bg-rose-500/20 text-rose-400 border border-rose-500/30';
        case 'Diverifikasi': return 'bg-sky-500/20 text-sky-400 border border-sky-500/30';
        case 'Ditindaklanjuti': return 'bg-amber-500/20 text-amber-300 border border-amber-500/30';
        case 'Selesai': return 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30';
        case 'Ditolak': return 'bg-slate-700 text-slate-300';
        default: return 'bg-slate-800 text-slate-400';
      }
    },

    // ================== USERS & PETUGAS CRUD ==================
    renderUsersTable() {
      const tbody = document.getElementById('usersTableBody');
      if (!tbody) return;

      const users = SPBUStore.getUsers();
      tbody.innerHTML = users.map(u => {
        let roleBadge = 'bg-sky-500/20 text-sky-400';
        if (u.role === 'petugas') roleBadge = 'bg-amber-500/20 text-amber-300';
        if (u.role === 'warga') roleBadge = 'bg-emerald-500/20 text-emerald-400';

        return `
          <tr class="hover:bg-slate-800/40 transition">
            <td class="py-3 px-4 font-bold text-white">${u.name}</td>
            <td class="py-3 px-4 font-mono text-slate-300">${u.email}</td>
            <td class="py-3 px-4 text-slate-400">${u.phone}</td>
            <td class="py-3 px-4 text-center">
              <span class="px-2.5 py-0.5 rounded text-[10px] font-bold uppercase ${roleBadge}">
                ${u.role}
              </span>
            </td>
            <td class="py-3 px-4 text-slate-300">${u.station_id || '-'}</td>
            <td class="py-3 px-4 text-center">
              <span class="px-2 py-0.5 rounded-full text-[10px] font-bold ${u.is_active ? 'bg-emerald-500/20 text-emerald-400' : 'bg-red-500/20 text-red-400'}">
                ${u.is_active ? 'Aktif' : 'Non-aktif'}
              </span>
            </td>
            <td class="py-3 px-4 text-right space-x-1">
              <button onclick="SPBUApp.deleteUser(${u.id})" class="bg-slate-800 hover:bg-red-500/20 text-red-400 p-1.5 rounded transition" title="Hapus User">
                <i class="fa-solid fa-trash"></i>
              </button>
            </td>
          </tr>
        `;
      }).join('');
    },

    openUserModal() {
      const modal = document.getElementById('userModal');
      const form = document.getElementById('userForm');
      if (!modal || !form) return;
      form.reset();
      this.handleRoleChangeInUserModal();
      modal.classList.remove('hidden');
    },

    handleRoleChangeInUserModal() {
      const role = document.getElementById('userFormRole')?.value;
      const grp = document.getElementById('userModalStationGroup');
      if (grp) {
        if (role === 'petugas') {
          grp.classList.remove('hidden');
        } else {
          grp.classList.add('hidden');
        }
      }
    },

    handleUserSubmit(event) {
      event.preventDefault();
      const name = document.getElementById('userFormName').value.trim();
      const email = document.getElementById('userFormEmail').value.trim();
      const phone = document.getElementById('userFormPhone').value.trim();
      const role = document.getElementById('userFormRole').value;
      const station_id = role === 'petugas' ? document.getElementById('userFormStation').value : null;

      SPBUStore.addUser({ name, email, phone, role, station_id });
      this.showToast(`Akun ${name} (${role.toUpperCase()}) berhasil ditambahkan!`, 'success');
      this.closeModal('userModal');
      this.renderAll();
    },

    deleteUser(id) {
      const current = SPBUStore.getCurrentUser();
      if (current.id === Number(id)) {
        this.showToast('Tidak dapat menghapus akun yang sedang aktif digunakan!', 'error');
        return;
      }
      if (confirm('Yakin ingin menghapus akun pengguna ini?')) {
        SPBUStore.deleteUser(id);
        this.showToast('Pengguna berhasil dihapus!', 'success');
        this.renderAll();
      }
    },

    // ================== AUDIT LOGS ==================
    renderLogsTable() {
      const tbody = document.getElementById('logsTableBody');
      if (!tbody) return;

      const logs = SPBUStore.getActivityLogs();
      if (logs.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6" class="py-4 text-center text-slate-500">Belum ada riwayat aktivitas.</td></tr>`;
        return;
      }

      tbody.innerHTML = logs.map(l => `
        <tr class="hover:bg-slate-800/40">
          <td class="py-2.5 font-mono text-slate-400 text-[11px]">${l.created_at}</td>
          <td class="py-2.5 font-semibold text-white">${l.user_name}</td>
          <td class="py-2.5"><span class="bg-slate-800 text-slate-300 px-2 py-0.5 rounded text-[10px] uppercase font-bold">${l.user_role}</span></td>
          <td class="py-2.5 text-center"><span class="font-mono text-sky-400 font-bold text-[11px]">${l.action}</span></td>
          <td class="py-2.5 text-slate-300">${l.entity_type} (#${l.entity_id})</td>
          <td class="py-2.5 text-slate-300">${l.description}</td>
        </tr>
      `).join('');
    },

    // ================== SYSTEM & DEMO CONTROLS ==================
    renderLogisticsGauge() {
      const stocks = SPBUStore.getStocks();
      const stations = SPBUStore.getStations();
      const deliveries = SPBUStore.getDeliveries();

      const totalStock = stocks.reduce((sum, s) => sum + (s.stock_liters || 0), 0);
      const totalCap = stocks.reduce((sum, s) => sum + (s.capacity_liters || 0), 0);
      const stockPct = totalCap > 0 ? Math.round((totalStock / totalCap) * 100) : 0;

      const openStations = stations.filter(s => s.operational_status === 'Buka').length;
      const openPct = stations.length > 0 ? Math.round((openStations / stations.length) * 100) : 0;

      const activeDeliveries = deliveries.filter(d => d.status !== 'Selesai').length;
      const truckPct = deliveries.length > 0 ? Math.round((activeDeliveries / deliveries.length) * 100) : 0;

      const overallPct = Math.min(100, Math.max(0, Math.round((stockPct * 0.6) + (openPct * 0.4))));

      const kpiStockEl = document.getElementById('kpiStockPct');
      const kpiTruckEl = document.getElementById('kpiTruckPct');
      const kpiOpenEl = document.getElementById('kpiOpenPct');
      const centerPctEl = document.getElementById('gaugeCenterPct');
      const insightEl = document.getElementById('logisticsInsightText');

      if (kpiStockEl) kpiStockEl.textContent = `${stockPct}%`;
      if (kpiTruckEl) kpiTruckEl.textContent = `${truckPct}%`;
      if (kpiOpenEl) kpiOpenEl.textContent = `${openPct}%`;
      if (centerPctEl) centerPctEl.textContent = `${overallPct}%`;

      if (insightEl) {
        const lowCount = stocks.filter(s => s.status === 'Menipis' || s.status === 'Habis').length;
        const totalLitersInTransit = deliveries
          .filter(d => d.status !== 'Selesai')
          .reduce((sum, d) => sum + (d.quantity_liters || 0), 0);
        insightEl.innerHTML = `Saat ini terdapat <strong>${activeDeliveries} armada truk tangki</strong> aktif membawa total <strong class="text-emerald-400">${totalLitersInTransit.toLocaleString('id-ID')} Liter</strong> BBM menuju SPBU. Terdeteksi <strong>${lowCount} tangki</strong> dalam status menipis/kritis.`;
      }

      const arcGroup = document.getElementById('logisticsGaugeArc');
      if (!arcGroup) return;

      const segments = 20;
      const filled = Math.round((overallPct / 100) * segments);
      const cx = 130;
      const cy = 128;
      const radius = 98;
      let html = '';

      for (let i = 0; i < segments; i++) {
        const angleDeg = 180 + (i / (segments - 1)) * 180;
        const rad = (angleDeg * Math.PI) / 180;
        const x = cx + radius * Math.cos(rad);
        const y = cy + radius * Math.sin(rad);
        const rot = angleDeg + 90;
        const isActive = i < filled;
        const fill = isActive ? '#22c55e' : '#1f242d';
        const filter = isActive ? 'drop-shadow(0 0 4px rgba(34,197,94,0.45))' : 'none';
        html += `<rect x="${(x - 4).toFixed(1)}" y="${(y - 14).toFixed(1)}" width="8" height="28" rx="4" fill="${fill}" style="filter:${filter}" transform="rotate(${rot.toFixed(1)} ${x.toFixed(1)} ${y.toFixed(1)})" />`;
      }
      arcGroup.innerHTML = html;
    },

    renderDeliveriesTable() {
      const deliveries = SPBUStore.getDeliveries();
      const activeCount = deliveries.filter(d => d.status !== 'Selesai').length;
      const badge = document.getElementById('badgeActiveDeliveries');
      if (badge) badge.textContent = `${activeCount} Aktif`;

      const statusBadge = (status) => {
        if (status === 'Selesai') return 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30';
        if (status === 'Tiba di SPBU') return 'bg-teal-500/20 text-teal-300 border-teal-500/30';
        if (status === 'Dalam Perjalanan') return 'bg-sky-500/20 text-sky-300 border-sky-500/30';
        return 'bg-slate-700 text-slate-300 border-slate-600';
      };

      const renderRows = (rows, emptyColspan) => {
        if (rows.length === 0) {
          return `<tr><td colspan="${emptyColspan}" class="py-6 text-center text-slate-500">Belum ada jadwal pengiriman BBM.</td></tr>`;
        }
        return rows.map(d => `
          <tr class="hover:bg-slate-800/40">
            <td class="py-2.5 px-3 font-mono text-sky-400 font-bold text-[11px]">${d.id}</td>
            <td class="py-2.5 px-3">
              <div class="font-bold text-white">${d.vehicle_name}</div>
              <div class="text-[11px] font-mono text-amber-300">${d.vehicle_plate}</div>
            </td>
            <td class="py-2.5 px-3 text-slate-300">${d.driver_name}</td>
            <td class="py-2.5 px-3">
              <div class="font-bold text-white">${d.fuel_name}</div>
              <div class="text-[11px] font-bold text-emerald-400">${Number(d.quantity_liters).toLocaleString('id-ID')} Liter</div>
            </td>
            <td class="py-2.5 px-3">
              <div class="text-[11px] text-purple-300">${d.origin_depot}</div>
              <div class="text-xs font-semibold text-white">→ ${d.station_name}</div>
            </td>
            <td class="py-2.5 px-3 text-center font-semibold text-slate-300">${d.eta_minutes > 0 ? d.eta_minutes + ' mnt' : '-'}</td>
            <td class="py-2.5 px-3 text-center">
              <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border ${statusBadge(d.status)}">${d.status}</span>
            </td>
            <td class="py-2.5 px-3 text-right space-x-1 whitespace-nowrap">
              ${d.status !== 'Selesai' ? `<button onclick="SPBUApp.completeDelivery('${d.id}')" class="bg-emerald-600 hover:bg-emerald-500 text-white px-2.5 py-1 rounded text-[11px] font-bold transition" title="Konfirmasi Bongkar Muat"><i class="fa-solid fa-check mr-1"></i>Selesai</button>` : ''}
              <button onclick="SPBUApp.openDeliveryModal('${d.id}')" class="bg-slate-800 hover:bg-slate-700 text-slate-200 px-2 py-1 rounded text-[11px] transition" title="Edit"><i class="fa-solid fa-pen"></i></button>
              <button onclick="SPBUApp.deleteDelivery('${d.id}')" class="bg-red-500/20 hover:bg-red-500/30 text-red-400 px-2 py-1 rounded text-[11px] transition" title="Hapus"><i class="fa-solid fa-trash"></i></button>
            </td>
          </tr>
        `).join('');
      };

      const dashBody = document.getElementById('dashboardDeliveriesTableBody');
      if (dashBody) {
        if (deliveries.length === 0) {
          dashBody.innerHTML = `<tr><td colspan="5" class="py-6 text-center text-zinc-500">Belum ada jadwal pengiriman BBM.</td></tr>`;
        } else {
          dashBody.innerHTML = deliveries.slice(0, 5).map(d => {
            const pct = Math.min(100, Math.round((d.quantity_liters / 24000) * 100));
            const bars = 10;
            const activeBars = Math.max(1, Math.round((pct / 100) * bars));
            const barColor = d.status === 'Selesai' ? 'bg-emerald-400' : (d.status === 'Tiba di SPBU' ? 'bg-teal-400' : 'bg-sky-400');
            const segHtml = Array.from({ length: bars }, (_, idx) =>
              `<span class="w-1 h-3 rounded-sm ${idx < activeBars ? barColor : 'bg-zinc-800'}"></span>`
            ).join('');

            return `
              <tr class="hover:bg-white/[0.02] transition">
                <td class="py-3 pr-3">
                  <div class="flex items-center space-x-2.5">
                    <div class="w-7 h-7 rounded-lg bg-white/[0.05] border border-white/[0.08] flex items-center justify-center text-sky-400 text-xs shrink-0">
                      <i class="fa-solid fa-truck-fast"></i>
                    </div>
                    <div>
                      <div class="font-semibold text-white text-xs">${d.vehicle_name}</div>
                      <div class="text-[10px] font-mono text-zinc-500">${d.vehicle_plate}</div>
                    </div>
                  </div>
                </td>
                <td class="py-3 px-3">
                  <div class="text-xs text-zinc-200 font-medium">${d.station_name}</div>
                  <div class="text-[10px] text-zinc-500">${d.driver_name} • ${d.origin_depot}</div>
                </td>
                <td class="py-3 px-3">
                  <div class="flex items-center space-x-2">
                    <div class="flex items-center space-x-0.5">${segHtml}</div>
                    <span class="text-xs font-semibold text-white">${Number(d.quantity_liters).toLocaleString('id-ID')} L</span>
                  </div>
                  <div class="text-[10px] text-zinc-500 mt-0.5">${d.fuel_name}</div>
                </td>
                <td class="py-3 px-3 text-center">
                  <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold border ${statusBadge(d.status)}">${d.status}</span>
                </td>
                <td class="py-3 pl-3 text-right whitespace-nowrap">
                  ${d.status !== 'Selesai' ? `<button onclick="SPBUApp.completeDelivery('${d.id}')" class="text-[11px] font-semibold text-emerald-400 hover:text-emerald-300 bg-emerald-500/10 hover:bg-emerald-500/20 border border-emerald-500/20 px-2.5 py-1 rounded-lg transition">Selesai</button>` : `<span class="text-[11px] text-zinc-500">Terkirim</span>`}
                </td>
              </tr>
            `;
          }).join('');
        }
      }

      const fullBody = document.getElementById('deliveriesTableBody');
      if (fullBody) {
        const search = (document.getElementById('deliverySearchInput')?.value || '').toLowerCase();
        const statusFilter = document.getElementById('deliveryStatusFilter')?.value || 'Semua';
        const filtered = deliveries.filter(d => {
          const matchStatus = statusFilter === 'Semua' || d.status === statusFilter;
          const matchSearch = !search || [d.id, d.vehicle_name, d.vehicle_plate, d.driver_name, d.station_name, d.fuel_name].some(v => (v || '').toLowerCase().includes(search));
          return matchStatus && matchSearch;
        });
        fullBody.innerHTML = renderRows(filtered, 8);
      }
    },

    simulateDeliveries() {
      SPBUStore.simulateDeliveries();
      this.showToast('Posisi GPS armada truk tangki diperbarui!', 'info');
    },

    openDeliveryModal(id = null) {
      this.populateStationDropdowns();
      const modalTitle = document.getElementById('deliveryModalTitle');
      const formId = document.getElementById('deliveryFormId');
      if (id) {
        const d = SPBUStore.getDeliveries().find(item => item.id === id);
        if (!d) return;
        modalTitle.textContent = `Edit Pengiriman ${d.id}`;
        formId.value = d.id;
        document.getElementById('deliveryFormOrigin').value = d.origin_depot;
        document.getElementById('deliveryFormStation').value = d.station_id;
        document.getElementById('deliveryFormVehicle').value = d.vehicle_name;
        document.getElementById('deliveryFormPlate').value = d.vehicle_plate;
        document.getElementById('deliveryFormDriver').value = d.driver_name;
        document.getElementById('deliveryFormFuelType').value = d.fuel_type_id;
        document.getElementById('deliveryFormQuantity').value = d.quantity_liters;
        document.getElementById('deliveryFormStatus').value = d.status;
        document.getElementById('deliveryFormEta').value = d.eta_minutes;
        document.getElementById('deliveryFormNotes').value = d.notes || '';
      } else {
        modalTitle.textContent = 'Jadwalkan Pengiriman BBM';
        formId.value = '';
        document.getElementById('deliveryForm').reset();
        document.getElementById('deliveryFormQuantity').value = 16000;
        document.getElementById('deliveryFormEta').value = 30;
        document.getElementById('deliveryFormStatus').value = 'Dalam Perjalanan';
      }
      document.getElementById('deliveryModal').classList.remove('hidden');
    },

    handleDeliverySubmit(e) {
      e.preventDefault();
      const id = document.getElementById('deliveryFormId').value;
      const payload = {
        origin_depot: document.getElementById('deliveryFormOrigin').value,
        station_id: document.getElementById('deliveryFormStation').value,
        vehicle_name: document.getElementById('deliveryFormVehicle').value,
        vehicle_plate: document.getElementById('deliveryFormPlate').value.toUpperCase(),
        driver_name: document.getElementById('deliveryFormDriver').value,
        fuel_type_id: Number(document.getElementById('deliveryFormFuelType').value),
        quantity_liters: Number(document.getElementById('deliveryFormQuantity').value),
        status: document.getElementById('deliveryFormStatus').value,
        eta_minutes: Number(document.getElementById('deliveryFormEta').value || 0),
        notes: document.getElementById('deliveryFormNotes').value,
      };
      this.closeModal('deliveryModal');
      if (id) {
        SPBUStore.updateDelivery(id, payload);
      } else {
        SPBUStore.addDelivery(payload);
      }
    },

    completeDelivery(id) {
      if (confirm('Konfirmasi truk tangki selesai bongkar muat? Stok BBM di SPBU tujuan akan otomatis bertambah.')) {
        SPBUStore.updateDelivery(id, { status: 'Selesai' });
      }
    },

    deleteDelivery(id) {
      if (confirm(`Hapus jadwal pengiriman ${id}?`)) {
        SPBUStore.deleteDelivery(id);
      }
    },

    populateStationDropdowns() {
      const stations = SPBUStore.getStations();
      const stockFilter = document.getElementById('stockStationFilter');
      const userStation = document.getElementById('userFormStation');
      const deliveryStation = document.getElementById('deliveryFormStation');
      const deliveryFuel = document.getElementById('deliveryFormFuelType');

      if (stockFilter) {
        const currVal = stockFilter.value;
        const optionsHtml = `<option value="Semua">Semua SPBU (Seluruh Tangki)</option>` +
          stations.map(s => `<option value="${s.id}">${s.name} (${s.code})</option>`).join('');
        stockFilter.innerHTML = optionsHtml;
        if (currVal) stockFilter.value = currVal;
      }

      if (userStation) {
        userStation.innerHTML = stations.map(s => `<option value="${s.id}">${s.name} (${s.code})</option>`).join('');
      }

      if (deliveryStation) {
        deliveryStation.innerHTML = stations.map(s => `<option value="${s.id}">${s.name} (${s.code})</option>`).join('');
      }

      if (deliveryFuel) {
        const fuels = SPBUStore.getFuelTypes().filter(f => f.is_active);
        deliveryFuel.innerHTML = fuels.map(f => `<option value="${f.id}">${f.name} (${f.ron})</option>`).join('');
      }
    },

    resetAllDemo() {
      if (confirm('Apakah Anda yakin ingin mereset seluruh database demo ke kondisi awal sesuai PRD? Seluruh perubahan baru akan dikembalikan.')) {
        SPBUStore.resetDefaults();
        this.showToast('Database berhasil direset ke kondisi default demo!', 'info');
        this.renderAll();
      }
    },

    exportDataJSON() {
      const dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(SPBUStore.data, null, 2));
      const downloadAnchor = document.createElement('a');
      downloadAnchor.setAttribute("href", dataStr);
      downloadAnchor.setAttribute("download", `SPBU_Monitoring_Backup_${new Date().toISOString().slice(0, 10)}.json`);
      document.body.appendChild(downloadAnchor);
      downloadAnchor.click();
      downloadAnchor.remove();
      this.showToast('File JSON backup berhasil diunduh!', 'success');
    },

    importDataJSON(event) {
      const file = event.target.files[0];
      if (!file) return;

      const reader = new FileReader();
      reader.onload = (e) => {
        try {
          const parsed = JSON.parse(e.target.result);
          if (parsed.stations && parsed.fuel_stocks) {
            SPBUStore.importSnapshot(parsed);
            this.showToast('Data snapshot berhasil diimpor ke sistem!', 'success');
          } else {
            this.showToast('Format JSON tidak valid untuk database SPBU!', 'error');
          }
        } catch (err) {
          this.showToast('Gagal memproses file JSON!', 'error');
        }
      };
      reader.readAsText(file);
    },

    // ================== REAL-TIME LIVE SIMULATION ==================
    toggleLiveSimulation() {
      isSimulating = !isSimulating;
      const text = document.getElementById('simStatusText');
      const btn = document.getElementById('simTickBtn');

      if (isSimulating) {
        this.startSimulation();
        if (text) text.textContent = 'Sensor Simulasi: Aktif';
        if (btn) btn.className = 'hidden md:flex items-center space-x-2 px-3 py-1.5 text-xs font-semibold rounded-lg border border-emerald-500/30 bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500/20 transition';
        this.showToast('Simulasi sensor antrean IoT otomatis diaktifkan!', 'info');
      } else {
        if (simulationTimer) clearInterval(simulationTimer);
        if (text) text.textContent = 'Sensor Simulasi: Jeda';
        if (btn) btn.className = 'hidden md:flex items-center space-x-2 px-3 py-1.5 text-xs font-semibold rounded-lg border border-slate-700 bg-slate-800 text-slate-400 hover:bg-slate-700 transition';
        this.showToast('Simulasi otomatis dijeda.', 'info');
      }
    },

    startSimulation() {
      if (simulationTimer) clearInterval(simulationTimer);
      // Run every 20 seconds
      simulationTimer = setInterval(() => {
        SPBUStore.simulateLiveTick();
      }, 20000);
    },

    triggerSimTick() {
      SPBUStore.simulateLiveTick();
      this.showToast('Data sensor antrean SPBU disimulasikan secara langsung!', 'info');
    },

    // ================== UTILITIES & MODAL HELPERS ==================
    closeModal(modalId) {
      const m = document.getElementById(modalId);
      if (m) m.classList.add('hidden');
    },

    showToast(message, type = 'info') {
      const cont = document.getElementById('toastContainer');
      if (!cont) return;

      const toast = document.createElement('div');
      let colors = 'bg-slate-900 border-sky-500 text-sky-300';
      let icon = 'fa-circle-info';

      if (type === 'success') {
        colors = 'bg-slate-900 border-emerald-500 text-emerald-300';
        icon = 'fa-circle-check';
      } else if (type === 'error') {
        colors = 'bg-slate-900 border-rose-500 text-rose-300';
        icon = 'fa-circle-exclamation';
      }

      toast.className = `border pointer-events-auto p-3 rounded-xl shadow-2xl flex items-center space-x-2.5 text-xs font-semibold animate-bounce-short transition ${colors}`;
      toast.innerHTML = `<i class="fa-solid ${icon} text-base"></i><span>${message}</span>`;

      cont.appendChild(toast);
      setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
      }, 3500);
    }
  };

  // Launch on DOM ready
  document.addEventListener('DOMContentLoaded', () => {
    window.SPBUApp.init();
  });

})();
