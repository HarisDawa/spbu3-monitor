/**
 * SPBU Monitoring System - Portal Warga Mobile Controller Script
 * Menghubungkan UI Warga Mobile dengan SPBUStore (LocalStorage Reactive Engine)
 * Beranda: Peta Interaktif Real-time + Overlay Pencarian & Quick Bottom Sheet
 */

(function () {
  'use strict';

  let wargaMapInstance = null;
  let wargaMarkersLayer = null;
  let currentFilter = 'semua';
  let activeDetailStationId = null;
  let activeSelectedStationId = null;
  let selectedPhotoBase64 = '';
  let isFrameMode = false;
  let myReportsFilterStatus = 'Semua';

  window.WargaApp = {
    currentTab: 'beranda',

    init() {
      // 1. Clock simulation
      this.updateMobileClock();
      setInterval(() => this.updateMobileClock(), 10000);

      // 2. Subscribe to reactive store
      SPBUStore.subscribe(() => {
        this.renderAll();
      });

      // 3. Init Mobile Leaflet Map on Beranda
      this.initMap();

      // 4. Initial Render
      this.renderAll();

      // 5. Populate Form Defaults & User Profile
      this.populateFormStationOptions();
      this.loadUserProfile();

      // 6. Set initial selected station on map bottom card
      const stations = SPBUStore.getStations();
      if (stations.length > 0) {
        this.selectStationOnMap(stations[0].id);
      }
    },

    updateMobileClock() {
      const el = document.getElementById('mobileClock');
      if (el) {
        const now = new Date();
        el.textContent = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
      }
    },

    // ================== TOGGLE FRAME MODE ==================
    toggleFrameMode() {
      const cont = document.getElementById('appContainer');
      const text = document.getElementById('frameBtnText');
      if (!cont) return;

      isFrameMode = !isFrameMode;
      if (isFrameMode) {
        cont.classList.add('mobile-frame');
        if (text) text.textContent = 'Mode Layar Penuh';
      } else {
        cont.classList.remove('mobile-frame');
        if (text) text.textContent = 'Mode Frame HP';
      }

      if (wargaMapInstance) {
        setTimeout(() => wargaMapInstance.invalidateSize(), 300);
      }
    },

    // ================== TAB NAVIGATION ==================
    switchTab(tabId, stationId = null) {
      this.currentTab = tabId;

      // Hide all views
      document.querySelectorAll('.warga-view').forEach(el => el.classList.add('hidden'));
      const target = document.getElementById(`view-${tabId}`);
      if (target) {
        target.classList.remove('hidden');
      }

      // Update Bottom Nav state
      document.querySelectorAll('.botnav-item').forEach(el => {
        el.classList.remove('text-emerald-400');
        el.classList.add('text-slate-400');
      });
      const activeNav = document.getElementById(`botnav-${tabId}`);
      if (activeNav) {
        activeNav.classList.add('text-emerald-400');
        activeNav.classList.remove('text-slate-400');
      }

      // Map invalidation when switching to Beranda
      if (tabId === 'beranda' && wargaMapInstance) {
        setTimeout(() => wargaMapInstance.invalidateSize(), 200);
      }

      if (tabId === 'detail' && stationId) {
        this.showStationDetail(stationId);
      }
      if (tabId === 'daftar') {
        this.renderStationCards();
      }
      if (tabId === 'aktivitas') {
        this.renderMyReports(myReportsFilterStatus);
      }

      // Scroll to top for scrollable views
      const scrollCont = document.querySelector('#appContainer .overflow-y-auto');
      if (scrollCont) scrollCont.scrollTop = 0;
    },

    // ================== RENDER ALL ==================
    renderAll() {
      this.renderQuickStats();
      this.renderMapMarkers();
      this.renderStationCards();
      this.populateFormStationOptions();

      if (activeSelectedStationId) {
        this.selectStationOnMap(activeSelectedStationId);
      }
      if (this.currentTab === 'detail' && activeDetailStationId) {
        this.showStationDetail(activeDetailStationId);
      }
      if (this.currentTab === 'aktivitas') {
        this.renderMyReports(myReportsFilterStatus);
      }
    },

    renderQuickStats() {
      const stations = SPBUStore.getStations();
      const bukaCount = stations.filter(s => s.operational_status === 'Buka').length;
      const queues = stations.map(s => SPBUStore.getQueueStatus(s.id));
      const lancarCount = queues.filter(q => q.queue_level === 'Lancar').length;
      const stocks = SPBUStore.getStocks();
      const safeStocks = stocks.filter(s => s.status === 'Tersedia').length;

      const statBuka = document.getElementById('statWargaBuka');
      const statLancar = document.getElementById('statWargaLancar');
      const statAman = document.getElementById('statWargaAman');

      if (statBuka) statBuka.textContent = bukaCount;
      if (statLancar) statLancar.textContent = lancarCount;
      if (statAman) statAman.textContent = safeStocks;
    },

    // ================== MOBILE LEAFLET MAP ON BERANDA ==================
    initMap() {
      const mapCont = document.getElementById('wargaMap');
      if (!mapCont) return;

      // Center around South Jakarta / Cilandak
      wargaMapInstance = L.map('wargaMap', {
        zoomControl: false,
        attributionControl: false
      }).setView([-6.285, 106.82], 12);

      L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
        maxZoom: 19
      }).addTo(wargaMapInstance);

      wargaMarkersLayer = L.layerGroup().addTo(wargaMapInstance);
      this.renderMapMarkers();
    },

    renderMapMarkers() {
      if (!wargaMarkersLayer || !wargaMapInstance) return;
      wargaMarkersLayer.clearLayers();

      const search = (document.getElementById('wargaSearchInput')?.value || '').toLowerCase();
      let stations = SPBUStore.getStations();

      // Search filter
      if (search) {
        stations = stations.filter(s => s.name.toLowerCase().includes(search) || s.region.toLowerCase().includes(search) || s.address.toLowerCase().includes(search) || s.code.toLowerCase().includes(search));
      }

      // Filter pills
      if (currentFilter === 'pertalite') {
        stations = stations.filter(s => {
          const stocks = SPBUStore.getStocks(s.id);
          const p = stocks.find(stk => stk.fuel_name === 'Pertalite');
          return p && p.status === 'Tersedia';
        });
      } else if (currentFilter === 'pertamax') {
        stations = stations.filter(s => {
          const stocks = SPBUStore.getStocks(s.id);
          const p = stocks.find(stk => stk.fuel_name === 'Pertamax');
          return p && p.status === 'Tersedia';
        });
      } else if (currentFilter === 'lancar') {
        stations = stations.filter(s => {
          const q = SPBUStore.getQueueStatus(s.id);
          return q.queue_level === 'Lancar';
        });
      } else if (currentFilter === '24jam') {
        stations = stations.filter(s => s.opening_hours.includes('24 Jam'));
      }

      const bounds = [];

      stations.forEach(st => {
        const queue = SPBUStore.getQueueStatus(st.id);
        const stocks = SPBUStore.getStocks(st.id);
        const isCritical = stocks.some(s => s.status === 'Habis' || s.status === 'Menipis');

        let markerColor = '#10b981'; // Green
        if (st.operational_status !== 'Buka') markerColor = '#ef4444';
        else if (queue.queue_level === 'Padat' || isCritical) {
          markerColor = queue.queue_level === 'Padat' ? '#ef4444' : '#f59e0b';
        } else if (queue.queue_level === 'Sedang') {
          markerColor = '#f59e0b';
        }

        const customIcon = L.divIcon({
          className: 'warga-pin',
          html: `<div style="background-color: ${markerColor}; width: 30px; height: 30px; border-radius: 50%; border: 3px solid #0f172a; box-shadow: 0 4px 10px rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; color: white; font-size: 12px;">
                  <i class="fa-solid fa-gas-pump"></i>
                 </div>`,
          iconSize: [30, 30],
          iconAnchor: [15, 15]
        });

        const marker = L.marker([st.latitude, st.longitude], { icon: customIcon }).addTo(wargaMarkersLayer);

        marker.on('click', () => {
          this.selectStationOnMap(st.id);
          wargaMapInstance.panTo([st.latitude, st.longitude], { animate: true, duration: 0.5 });
        });

        bounds.push([st.latitude, st.longitude]);
      });

      if (search && bounds.length > 0) {
        wargaMapInstance.fitBounds(bounds, { padding: [50, 50], maxZoom: 14 });
      }
    },

    selectStationOnMap(stationId) {
      activeSelectedStationId = stationId;
      const st = SPBUStore.getStationById(stationId);
      if (!st) return;

      const queue = SPBUStore.getQueueStatus(st.id);
      const stocks = SPBUStore.getStocks(st.id);

      // Update card elements
      document.getElementById('mapCardCode').textContent = st.code;
      document.getElementById('mapCardName').textContent = st.name;
      document.getElementById('mapCardAddress').textContent = st.address;

      const opBadge = document.getElementById('mapCardStatus');
      opBadge.textContent = st.operational_status;
      opBadge.className = `text-[10px] px-2 py-0.2 rounded-full font-bold ${st.operational_status === 'Buka' ? 'bg-emerald-500/20 text-emerald-400' : 'bg-red-500/20 text-red-400'}`;

      // Queue badge
      const queueEl = document.getElementById('mapCardQueue');
      let queueClass = 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30';
      if (queue.queue_level === 'Padat') queueClass = 'bg-red-500/20 text-red-400 border-red-500/30';
      if (queue.queue_level === 'Sedang') queueClass = 'bg-amber-500/20 text-amber-300 border-amber-500/30';
      queueEl.textContent = `${queue.queue_level} (~${queue.estimated_wait_minutes} mnt)`;
      queueEl.className = `px-2 py-0.2 rounded-full text-[10px] font-bold border ${queueClass}`;

      // Fuel tags
      const fuelCont = document.getElementById('mapCardFuelTags');
      if (fuelCont) {
        fuelCont.innerHTML = stocks.slice(0, 2).map(s => `
          <span class="text-[9px] bg-slate-900 border border-slate-800 px-1.5 py-0.5 rounded text-slate-300">
            ${s.fuel_name}: <strong class="${s.status === 'Tersedia' ? 'text-emerald-400' : 'text-amber-400'}">${s.status}</strong>
          </span>
        `).join('');
      }

      // Actions
      const detailBtn = document.getElementById('mapCardDetailBtn');
      if (detailBtn) {
        detailBtn.onclick = () => {
          this.switchTab('detail', st.id);
        };
      }

      const routeBtn = document.getElementById('mapCardRouteBtn');
      if (routeBtn) {
        routeBtn.href = `https://www.google.com/maps/dir/?api=1&destination=${st.latitude},${st.longitude}`;
      }
    },

    locateUser() {
      if (wargaMapInstance) {
        wargaMapInstance.setView([-6.285, 106.82], 13, { animate: true });
        this.showToast('Menampilkan SPBU di sekitar lokasi Anda (Jakarta Selatan).', 'info');
      }
    },

    // ================== SEARCH & FILTER ==================
    handleSearch() {
      const val = document.getElementById('wargaSearchInput')?.value || '';
      const clearBtn = document.getElementById('clearSearchBtn');
      if (clearBtn) {
        if (val) clearBtn.classList.remove('hidden');
        else clearBtn.classList.add('hidden');
      }
      this.renderMapMarkers();
      this.renderStationCards();
    },

    clearSearch() {
      const input = document.getElementById('wargaSearchInput');
      if (input) input.value = '';
      this.handleSearch();
    },

    setFilter(filterType) {
      currentFilter = filterType;
      document.querySelectorAll('.filter-pill').forEach(btn => {
        btn.classList.remove('bg-emerald-600', 'text-white');
        btn.classList.add('bg-slate-900/90', 'text-slate-300');
      });
      event.target.classList.add('bg-emerald-600', 'text-white');
      event.target.classList.remove('bg-slate-900/90', 'text-slate-300');

      this.renderMapMarkers();
      this.renderStationCards();
    },

    // ================== DAFTAR KARTU SPBU (TAB 2) ==================
    renderStationCards() {
      const cont = document.getElementById('wargaStationList');
      if (!cont) return;

      const search = (document.getElementById('wargaSearchInput')?.value || '').toLowerCase();
      let list = SPBUStore.getStations();

      if (search) {
        list = list.filter(s => s.name.toLowerCase().includes(search) || s.region.toLowerCase().includes(search) || s.address.toLowerCase().includes(search) || s.code.toLowerCase().includes(search));
      }

      if (currentFilter === 'pertalite') {
        list = list.filter(s => {
          const stocks = SPBUStore.getStocks(s.id);
          const p = stocks.find(stk => stk.fuel_name === 'Pertalite');
          return p && p.status === 'Tersedia';
        });
      } else if (currentFilter === 'pertamax') {
        list = list.filter(s => {
          const stocks = SPBUStore.getStocks(s.id);
          const p = stocks.find(stk => stk.fuel_name === 'Pertamax');
          return p && p.status === 'Tersedia';
        });
      } else if (currentFilter === 'lancar') {
        list = list.filter(s => {
          const q = SPBUStore.getQueueStatus(s.id);
          return q.queue_level === 'Lancar';
        });
      } else if (currentFilter === '24jam') {
        list = list.filter(s => s.opening_hours.includes('24 Jam'));
      }

      if (list.length === 0) {
        cont.innerHTML = `
          <div class="bg-slate-800/40 border border-slate-800 rounded-2xl p-6 text-center space-y-2">
            <i class="fa-solid fa-gas-pump text-slate-600 text-3xl"></i>
            <div class="text-xs font-bold text-slate-400">Tidak ada SPBU yang sesuai</div>
            <p class="text-[11px] text-slate-500">Coba ubah kata kunci pencarian atau reset filter.</p>
          </div>
        `;
        return;
      }

      const distances = ['0.8 km', '1.4 km', '2.1 km', '3.5 km', '4.2 km', '6.0 km', '8.5 km', '12 km'];

      cont.innerHTML = list.map((st, idx) => {
        const queue = SPBUStore.getQueueStatus(st.id);
        const stocks = SPBUStore.getStocks(st.id);
        const distance = distances[idx % distances.length];

        let queueBadgeClass = 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30';
        if (queue.queue_level === 'Padat') queueBadgeClass = 'bg-red-500/20 text-red-400 border-red-500/30';
        if (queue.queue_level === 'Sedang') queueBadgeClass = 'bg-amber-500/20 text-amber-300 border-amber-500/30';

        let opBadgeClass = st.operational_status === 'Buka' ? 'bg-emerald-500/20 text-emerald-400' : 'bg-red-500/20 text-red-400';

        const fuelTags = stocks.slice(0, 3).map(s => {
          let statusText = s.status === 'Tersedia' ? 'Ready' : (s.status === 'Menipis' ? 'Menipis' : 'Habis');
          let color = s.status === 'Tersedia' ? 'text-emerald-400' : (s.status === 'Menipis' ? 'text-amber-400' : 'text-red-400');
          return `
            <span class="inline-flex items-center space-x-1 text-[10px] bg-slate-900 px-2 py-0.5 rounded-md border border-slate-700/60">
              <span class="text-slate-300 font-semibold">${s.fuel_name}:</span>
              <span class="${color} font-bold">${statusText}</span>
            </span>
          `;
        }).join('');

        return `
          <div class="bg-slate-800/80 border border-slate-700/80 hover:border-emerald-500/40 rounded-2xl p-3.5 space-y-2.5 transition shadow-lg">
            <div class="flex items-start justify-between">
              <div>
                <div class="flex items-center space-x-2">
                  <span class="text-[10px] font-mono text-emerald-400 font-bold bg-emerald-500/10 px-1.5 py-0.2 rounded border border-emerald-500/20">${st.code}</span>
                  <span class="text-[10px] px-1.5 py-0.2 rounded-full font-bold ${opBadgeClass}">${st.operational_status}</span>
                </div>
                <h4 class="font-bold text-sm text-white mt-1">${st.name}</h4>
                <p class="text-[11px] text-slate-400 truncate max-w-[240px]">${st.address}</p>
              </div>
              <div class="text-right">
                <span class="text-xs font-black text-emerald-400 block">${distance}</span>
                <span class="text-[9px] text-slate-500 uppercase font-semibold">dari Anda</span>
              </div>
            </div>

            <div class="bg-slate-900/90 rounded-xl p-2 flex items-center justify-between text-xs">
              <div class="flex items-center space-x-1.5">
                <i class="fa-solid fa-users text-slate-400 text-[11px]"></i>
                <span class="text-slate-300">Antrean:</span>
                <span class="px-2 py-0.2 rounded-full text-[10px] font-bold border ${queueBadgeClass}">${queue.queue_level}</span>
              </div>
              <div class="font-mono text-slate-200 font-bold text-[11px]">
                ~${queue.estimated_wait_minutes} menit
              </div>
            </div>

            <div class="flex flex-wrap gap-1">
              ${fuelTags}
            </div>

            <div class="pt-1 flex items-center space-x-2 border-t border-slate-700/60">
              <button onclick="WargaApp.switchTab('detail', '${st.id}')" class="flex-1 bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-2 rounded-xl text-xs transition flex items-center justify-center space-x-1.5 shadow-md shadow-emerald-600/20">
                <i class="fa-solid fa-circle-info"></i>
                <span>Lihat Detail & Stok</span>
              </button>
              <button onclick="WargaApp.switchTab('beranda'); WargaApp.selectStationOnMap('${st.id}'); wargaMapInstance.setView([${st.latitude}, ${st.longitude}], 14);" class="bg-slate-700 hover:bg-slate-600 text-emerald-400 px-3 py-2 rounded-xl text-xs font-bold transition flex items-center space-x-1" title="Tampilkan di Peta">
                <i class="fa-solid fa-map-pin"></i>
                <span>Peta</span>
              </button>
              <a href="https://www.google.com/maps/dir/?api=1&destination=${st.latitude},${st.longitude}" target="_blank" class="bg-slate-700 hover:bg-slate-600 text-sky-400 p-2 rounded-xl text-xs transition" title="Petunjuk Arah Google Maps">
                <i class="fa-solid fa-location-arrow"></i>
              </a>
            </div>
          </div>
        `;
      }).join('');
    },

    // ================== DETAIL SPBU VIEW (TAB 3) ==================
    showStationDetail(stationId) {
      activeDetailStationId = stationId;
      const st = SPBUStore.getStationById(stationId);
      if (!st) return;

      const queue = SPBUStore.getQueueStatus(st.id);
      const stocks = SPBUStore.getStocks(st.id);

      // Station Header
      document.getElementById('detailStationCode').textContent = st.code;
      document.getElementById('detailStationName').textContent = st.name;
      document.getElementById('detailStationAddress').textContent = `${st.address}, ${st.region}`;
      document.getElementById('detailStationHours').textContent = st.opening_hours;
      document.getElementById('detailStationPhone').textContent = st.phone || '(021) 7590-1234';
      document.getElementById('detailStationFacility').textContent = st.facility || 'Toilet, Musholla, ATM';

      const opBadge = document.getElementById('detailOperationalStatus');
      opBadge.textContent = st.operational_status.toUpperCase();
      opBadge.className = `text-xs px-2.5 py-0.5 rounded-full font-black border ${st.operational_status === 'Buka' ? 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30' : 'bg-red-500/20 text-red-400 border-red-500/30'}`;

      // Queue details
      document.getElementById('detailQueueLevelBadge').textContent = queue.queue_level;
      document.getElementById('detailQueueLevelBadge').className = `text-xs font-black px-2.5 py-0.5 rounded-full ${queue.queue_level === 'Padat' ? 'bg-red-500/20 text-red-400' : (queue.queue_level === 'Sedang' ? 'bg-amber-500/20 text-amber-300' : 'bg-emerald-500/20 text-emerald-400')}`;
      document.getElementById('detailQueueMinutes').textContent = `~${queue.estimated_wait_minutes}`;
      document.getElementById('detailQueueSource').textContent = `Sumber: ${queue.source} (${queue.vehicle_category})`;
      document.getElementById('detailQueueUpdated').textContent = `Diperbarui: ${queue.updated_at}`;

      // Route Link
      document.getElementById('detailRouteButton').href = `https://www.google.com/maps/dir/?api=1&destination=${st.latitude},${st.longitude}`;

      // Fuel Stocks Breakdown
      const listCont = document.getElementById('detailFuelStocksList');
      if (listCont) {
        listCont.innerHTML = stocks.map(s => {
          let barColor = 'bg-emerald-500';
          let statusBadgeClass = 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30';
          if (s.status === 'Habis') {
            barColor = 'bg-red-500';
            statusBadgeClass = 'bg-red-500/20 text-red-400 border-red-500/30';
          } else if (s.status === 'Menipis') {
            barColor = 'bg-amber-500';
            statusBadgeClass = 'bg-amber-500/20 text-amber-300 border-amber-500/30';
          }

          return `
            <div class="bg-slate-800/80 border border-slate-700/80 rounded-xl p-3 space-y-2">
              <div class="flex items-center justify-between">
                <div>
                  <span class="font-extrabold text-sm text-white">${s.fuel_name}</span>
                  <span class="text-[10px] text-slate-400 ml-1">(${s.fuel_ron})</span>
                </div>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-black border ${statusBadgeClass}">${s.status}</span>
              </div>

              <div class="flex items-center justify-between text-xs font-mono">
                <span class="text-slate-300">${s.stock_liters.toLocaleString('id-ID')} / ${s.capacity_liters.toLocaleString('id-ID')} Liter</span>
                <span class="text-emerald-400 font-bold">Rp ${s.fuel_price.toLocaleString('id-ID')}/L</span>
              </div>

              <div class="w-full h-2 bg-slate-900 rounded-full overflow-hidden">
                <div class="${barColor} h-full rounded-full transition-all duration-300" style="width: ${Math.min(s.percentage, 100)}%"></div>
              </div>
            </div>
          `;
        }).join('');
      }
    },

    prefillReport() {
      if (activeDetailStationId) {
        const select = document.getElementById('reportStationSelect');
        if (select) select.value = activeDetailStationId;
      }
      this.switchTab('laporan');
    },

    // ================== BUAT LAPORAN (TAB 4) ==================
    populateFormStationOptions() {
      const select = document.getElementById('reportStationSelect');
      if (!select) return;

      const stations = SPBUStore.getStations();
      select.innerHTML = stations.map(s => `<option value="${s.id}">${s.name} (${s.code})</option>`).join('');
    },

    handlePhotoSelect(event) {
      const file = event.target.files[0];
      if (!file) return;

      const reader = new FileReader();
      reader.onload = (e) => {
        selectedPhotoBase64 = e.target.result;
        this.showPhotoPreview(selectedPhotoBase64);
      };
      reader.readAsDataURL(file);
    },

    useSamplePhoto() {
      selectedPhotoBase64 = "https://images.unsplash.com/photo-1545454675-3531b543be5d?w=600&auto=format&fit=crop&q=80";
      this.showPhotoPreview(selectedPhotoBase64);
      this.showToast('Foto contoh antrean SPBU disematkan!', 'info');
    },

    showPhotoPreview(src) {
      const cont = document.getElementById('photoPreviewContainer');
      const img = document.getElementById('photoPreviewImg');
      if (cont && img) {
        img.src = src;
        cont.classList.remove('hidden');
      }
    },

    removePhoto() {
      selectedPhotoBase64 = '';
      const cont = document.getElementById('photoPreviewContainer');
      const input = document.getElementById('reportPhotoInput');
      if (cont) cont.classList.add('hidden');
      if (input) input.value = '';
    },

    handleReportSubmit(event) {
      event.preventDefault();
      const station_id = document.getElementById('reportStationSelect').value;
      const category = document.getElementById('reportCategorySelect').value;
      const description = document.getElementById('reportDescriptionInput').value.trim();
      const user_name = document.getElementById('reportUserNameInput').value.trim();

      if (!description) {
        this.showToast('Harap tuliskan deskripsi laporan Anda!', 'error');
        return;
      }

      const newReport = SPBUStore.addReport({
        station_id: station_id,
        category: category,
        description: description,
        photo_url: selectedPhotoBase64,
        user_name: user_name
      });

      document.getElementById('reportDescriptionInput').value = '';
      this.removePhoto();

      this.showToast(`Laporan #${newReport.report_code} berhasil dikirim! Status: Menunggu`, 'success');
      this.switchTab('aktivitas');
    },

    // ================== AKTIVITAS & TRACKING (TAB 5) ==================
    filterMyReports(status) {
      myReportsFilterStatus = status;
      document.querySelectorAll('.my-rep-filter').forEach(btn => {
        btn.classList.remove('bg-emerald-600', 'text-white');
        btn.classList.add('bg-slate-800', 'text-slate-300');
      });
      event.target.classList.add('bg-emerald-600', 'text-white');
      event.target.classList.remove('bg-slate-800', 'text-slate-300');

      this.renderMyReports(status);
    },

    renderMyReports(filterStatus = 'Semua') {
      const cont = document.getElementById('myReportsList');
      if (!cont) return;

      const user = SPBUStore.getCurrentUser();
      let reports = SPBUStore.getReports().filter(r => r.user_id === user.id || r.user_name.toLowerCase().includes(user.name.toLowerCase()));

      if (filterStatus !== 'Semua') {
        reports = reports.filter(r => r.status === filterStatus);
      }

      if (reports.length === 0) {
        cont.innerHTML = `
          <div class="bg-slate-800/40 border border-slate-800 rounded-2xl p-6 text-center space-y-2">
            <i class="fa-solid fa-clipboard-check text-slate-600 text-3xl"></i>
            <div class="text-xs font-bold text-slate-400">Belum ada riwayat laporan</div>
            <p class="text-[11px] text-slate-500">Laporan yang Anda kirimkan akan terpantau status penanganannya di sini.</p>
          </div>
        `;
        return;
      }

      cont.innerHTML = reports.map(r => {
        const steps = ['Menunggu', 'Diverifikasi', 'Ditindaklanjuti', 'Selesai'];
        let currentStepIndex = steps.indexOf(r.status);
        if (r.status === 'Ditolak') currentStepIndex = -1;

        const stepperHtml = r.status === 'Ditolak' ? `
          <div class="bg-red-500/10 border border-red-500/30 rounded-xl p-2.5 text-center text-xs font-bold text-red-400">
            Laporan Ditolak / Dinyatakan Tidak Sesuai
          </div>
        ` : `
          <div class="grid grid-cols-4 gap-1 text-center pt-2">
            ${steps.map((st, i) => {
              const isPastOrCurrent = i <= currentStepIndex;
              const isCurrent = i === currentStepIndex;
              return `
                <div class="space-y-1">
                  <div class="h-1.5 rounded-full ${isPastOrCurrent ? 'bg-emerald-500' : 'bg-slate-700'}"></div>
                  <span class="text-[9px] block ${isCurrent ? 'text-emerald-400 font-extrabold' : (isPastOrCurrent ? 'text-slate-300' : 'text-slate-500')}">
                    ${st}
                  </span>
                </div>
              `;
            }).join('')}
          </div>
        `;

        return `
          <div class="bg-slate-800/80 border border-slate-700/80 rounded-2xl p-4 space-y-3 shadow-lg">
            <div class="flex items-center justify-between">
              <span class="text-xs font-mono font-bold text-emerald-400">${r.report_code}</span>
              <span class="px-2 py-0.5 rounded-full text-[10px] font-black ${this.getReportStatusBadge(r.status)}">${r.status}</span>
            </div>

            <div>
              <h4 class="font-bold text-sm text-white">${r.station_name}</h4>
              <span class="text-[10px] bg-slate-900 text-slate-300 px-2 py-0.5 rounded font-semibold inline-block mt-1">${r.category}</span>
            </div>

            <p class="text-xs text-slate-300 bg-slate-900/60 p-2.5 rounded-xl border border-slate-800 leading-relaxed">${r.description}</p>

            ${r.photo_url ? `
              <div>
                <img src="${r.photo_url}" alt="Foto Bukti" class="w-full h-32 object-cover rounded-xl border border-slate-700">
              </div>
            ` : ''}

            ${stepperHtml}

            ${r.admin_note ? `
              <div class="bg-emerald-500/10 border border-emerald-500/30 rounded-xl p-2.5 text-xs text-emerald-200 space-y-0.5">
                <span class="text-[10px] font-bold text-emerald-400 uppercase tracking-wider block">Catatan Resmi Petugas:</span>
                <p class="text-[11px]">${r.admin_note}</p>
              </div>
            ` : `
              <div class="text-[10px] text-slate-500 italic">Menunggu respon verifikasi dari pos pemantau...</div>
            `}

            <div class="text-[10px] text-slate-500 text-right pt-1 border-t border-slate-700/60">
              Dikirim: ${r.created_at}
            </div>
          </div>
        `;
      }).join('');
    },

    getReportStatusBadge(st) {
      switch (st) {
        case 'Menunggu': return 'bg-rose-500/20 text-rose-400 border border-rose-500/30';
        case 'Diverifikasi': return 'bg-sky-500/20 text-sky-400 border border-sky-500/30';
        case 'Ditindaklanjuti': return 'bg-amber-500/20 text-amber-300 border border-amber-500/30';
        case 'Selesai': return 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30';
        case 'Ditolak': return 'bg-slate-700 text-slate-300';
        default: return 'bg-slate-800 text-slate-400';
      }
    },

    // ================== PROFIL (TAB 6) ==================
    loadUserProfile() {
      const user = SPBUStore.getCurrentUser();
      const topName = document.getElementById('topBarUserName');
      const nameDisp = document.getElementById('profileNameDisplay');
      const emailDisp = document.getElementById('profileEmailDisplay');
      const nameInput = document.getElementById('profileNameInput');
      const emailInput = document.getElementById('profileEmailInput');
      const phoneInput = document.getElementById('profilePhoneInput');
      const reportUserInput = document.getElementById('reportUserNameInput');

      if (topName) topName.textContent = user.name.split(' ')[0];
      if (nameDisp) nameDisp.textContent = user.name;
      if (emailDisp) emailDisp.textContent = user.email;
      if (nameInput) nameInput.value = user.name;
      if (emailInput) emailInput.value = user.email;
      if (phoneInput) phoneInput.value = user.phone || '0819-5566-7788';
      if (reportUserInput) reportUserInput.value = user.name;
    },

    saveProfile() {
      const user = SPBUStore.getCurrentUser();
      const name = document.getElementById('profileNameInput').value.trim();
      const email = document.getElementById('profileEmailInput').value.trim();
      const phone = document.getElementById('profilePhoneInput').value.trim();

      if (!name || !email) {
        this.showToast('Nama dan Email tidak boleh kosong!', 'error');
        return;
      }

      SPBUStore.updateUser(user.id, { name, email, phone });
      this.loadUserProfile();
      this.showToast('Profil pengguna berhasil disimpan!', 'success');
    },

    // ================== TOAST NOTIFICATION ==================
    showToast(message, type = 'info') {
      const cont = document.getElementById('wargaToastContainer');
      if (!cont) return;

      const toast = document.createElement('div');
      let colors = 'bg-slate-900 border-emerald-500 text-emerald-300';
      let icon = 'fa-circle-info';

      if (type === 'success') {
        colors = 'bg-slate-900 border-emerald-500 text-emerald-300';
        icon = 'fa-circle-check';
      } else if (type === 'error') {
        colors = 'bg-slate-900 border-rose-500 text-rose-300';
        icon = 'fa-circle-exclamation';
      }

      toast.className = `border pointer-events-auto p-3 rounded-2xl shadow-2xl flex items-center space-x-2.5 text-xs font-semibold transition ${colors}`;
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
    window.WargaApp.init();
  });

})();
