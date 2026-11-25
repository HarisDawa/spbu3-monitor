/**
 * SPBU Monitoring System - Client state mirror.
 * Source of truth: Laravel database. Snapshot injected by Blade, mutations posted via JSON API.
 */
(function (window) {
  'use strict';

  function request(method, url, body) {
    const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
    return fetch(url, {
      method,
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': token
      },
      body: body === undefined ? undefined : JSON.stringify(body)
    }).then((response) => {
      if (!response.ok) {
        return response.json().catch(() => ({})).then((json) => {
          throw new Error(json.message || `Request gagal (${response.status})`);
        });
      }
      return response.json();
    }).catch((error) => {
      console.error('SPBU API:', error);
      const toast = window.SPBUApp?.showToast;
      if (toast) {
        toast.call(window.SPBUApp, `Gagal menyimpan ke server: ${error.message}. Halaman akan dimuat ulang.`, 'error');
      }
      setTimeout(() => window.location.reload(), 2500);
      return null;
    });
  }

  function stamp() {
    return new Date().toISOString().replace('T', ' ').substring(0, 16);
  }

  class SPBUStore {
    constructor() {
      this.listeners = [];
      this.data = JSON.parse(JSON.stringify(window.SPBU_BOOTSTRAP || {}));
      for (const key of ['users', 'fuel_types', 'stations', 'fuel_stocks', 'queue_statuses', 'citizen_reports', 'stock_histories', 'activity_logs']) {
        if (!Array.isArray(this.data[key])) {
          this.data[key] = [];
        }
      }
    }

    save() {
      this.notify();
    }

    subscribe(fn) {
      if (typeof fn === 'function') {
        this.listeners.push(fn);
      }
    }

    notify() {
      this.listeners.forEach((fn) => {
        try { fn(this.data); } catch (error) { console.error(error); }
      });
    }

    logActivity(action, entityType, entityId, description) {
      const user = this.getCurrentUser();
      const newLog = {
        id: (this.data.activity_logs.length ? Math.max(...this.data.activity_logs.map((l) => l.id)) : 0) + 1,
        user_name: user.name || 'System',
        user_role: user.role || 'system',
        action,
        entity_type: entityType,
        entity_id: String(entityId),
        description,
        created_at: stamp()
      };
      this.data.activity_logs.unshift(newLog);
      if (this.data.activity_logs.length > 200) {
        this.data.activity_logs = this.data.activity_logs.slice(0, 200);
      }
    }

    getActivityLogs() {
      return this.data.activity_logs;
    }

    // ================== USER & AUTH ==================
    getUsers() {
      return this.data.users;
    }

    getCurrentUser() {
      return this.data.currentUser || this.data.users[0];
    }

    setCurrentUser(userIdOrRole) {
      let target = null;
      if (typeof userIdOrRole === 'number') {
        target = this.data.users.find((u) => u.id === userIdOrRole);
      } else if (typeof userIdOrRole === 'string') {
        target = this.data.users.find((u) => u.role === userIdOrRole || u.email === userIdOrRole);
      }
      if (target) {
        request('POST', '/api/switch-user', { id: target.id }).then((result) => {
          if (result?.ok) {
            window.location.assign(target.role === 'warga' ? '/warga' : '/');
          }
        });
        this.data.currentUser = JSON.parse(JSON.stringify(target));
        this.save();
        this.logActivity('SWITCH_USER', 'Pengguna', String(target.id), `Beralih peran ke: ${target.name} (${target.role.toUpperCase()})`);
      }
      return this.data.currentUser;
    }

    addUser(userData) {
      const id = (this.data.users.length ? Math.max(...this.data.users.map((u) => u.id)) : 0) + 1;
      const user = {
        id,
        name: userData.name || 'User Baru',
        email: userData.email,
        phone: userData.phone || '-',
        role: userData.role || 'petugas',
        station_id: userData.station_id || null,
        is_active: true,
        created_at: stamp()
      };
      this.data.users.push(user);
      this.save();
      this.logActivity('CREATE', 'Pengguna', String(id), `Menambahkan pengguna baru: ${user.name} (${user.role})`);
      request('POST', '/api/users', userData);
      return user;
    }

    updateUser(id, updates) {
      const index = this.data.users.findIndex((u) => u.id === Number(id));
      if (index === -1) return null;
      this.data.users[index] = { ...this.data.users[index], ...updates };
      if (this.data.currentUser.id === Number(id)) {
        this.data.currentUser = { ...this.data.currentUser, ...updates };
      }
      this.save();
      this.logActivity('UPDATE', 'Pengguna', String(id), `Memperbarui data pengguna: ${this.data.users[index].name}`);
      request('PUT', `/api/users/${id}`, updates);
      return this.data.users[index];
    }

    deleteUser(id) {
      const user = this.data.users.find((u) => u.id === Number(id));
      if (!user) return false;
      this.data.users = this.data.users.filter((u) => u.id !== Number(id));
      this.save();
      this.logActivity('DELETE', 'Pengguna', String(id), `Menghapus pengguna: ${user.name}`);
      request('DELETE', `/api/users/${id}`);
      return true;
    }

    // ================== STATIONS ==================
    getStations(includeDeleted = false) {
      if (includeDeleted) return this.data.stations;
      return this.data.stations.filter((s) => !s.deleted_at);
    }

    getStationById(id) {
      return this.data.stations.find((s) => s.id === id || s.code === id);
    }

    addStation(stationData) {
      const code = String(stationData.code).trim();
      const id = `SPBU-${code}`;
      const station = {
        id,
        code,
        name: String(stationData.name).trim(),
        address: String(stationData.address).trim(),
        region: String(stationData.region).trim(),
        latitude: parseFloat(stationData.latitude) || -6.2,
        longitude: parseFloat(stationData.longitude) || 106.8,
        phone: stationData.phone ? String(stationData.phone).trim() : '-',
        opening_hours: stationData.opening_hours || '24 Jam',
        operational_status: stationData.operational_status || 'Buka',
        facility: stationData.facility || 'Toilet, Musholla',
        deleted_at: null,
        created_at: stamp()
      };

      this.data.stations.push(station);
      this.data.fuel_types.filter((f) => f.is_active).slice(0, 4).forEach((fuel, index) => {
        this.data.fuel_stocks.push({
          id: Date.now() + index,
          station_id: id,
          fuel_type_id: fuel.id,
          stock_liters: 15000,
          capacity_liters: 25000,
          low_stock_threshold: 3000,
          updated_by: this.getCurrentUser().name,
          updated_at: stamp()
        });
      });
      this.data.queue_statuses.push({
        id: Date.now() + 99,
        station_id: id,
        vehicle_category: 'Semua Kendaraan',
        estimated_wait_minutes: 5,
        queue_level: 'Lancar',
        source: 'Petugas',
        updated_by: this.getCurrentUser().name,
        updated_at: stamp()
      });

      this.save();
      this.logActivity('CREATE', 'SPBU', id, `Menambahkan SPBU baru: ${station.name} (${station.code})`);
      request('POST', '/api/stations', stationData);
      return station;
    }

    updateStation(id, updates) {
      const station = this.getStationById(id);
      if (!station) return null;
      Object.assign(station, updates);
      this.save();
      this.logActivity('UPDATE', 'SPBU', station.id, `Memperbarui data SPBU: ${station.name}`);
      request('PUT', `/api/stations/${encodeURIComponent(station.id)}`, updates);
      return station;
    }

    deleteStation(id) {
      const station = this.getStationById(id);
      if (!station) return false;
      station.deleted_at = stamp();
      this.save();
      this.logActivity('DELETE', 'SPBU', station.id, `Menonaktifkan / Soft delete SPBU: ${station.name}`);
      request('DELETE', `/api/stations/${encodeURIComponent(station.id)}`);
      return true;
    }

    toggleStationStatus(id) {
      const station = this.getStationById(id);
      if (!station) return null;
      const next = station.operational_status === 'Buka' ? 'Tutup' : (station.operational_status === 'Tutup' ? 'Perbaikan' : 'Buka');
      station.operational_status = next;
      this.save();
      this.logActivity('UPDATE_STATUS', 'SPBU', station.id, `Mengubah status operasional ${station.name} ke: ${next}`);
      request('POST', `/api/stations/${encodeURIComponent(station.id)}/toggle`, {});
      return next;
    }

    // ================== FUEL TYPES ==================
    getFuelTypes(activeOnly = true) {
      return activeOnly ? this.data.fuel_types.filter((f) => f.is_active) : this.data.fuel_types;
    }

    getFuelTypeById(id) {
      return this.data.fuel_types.find((f) => f.id === Number(id));
    }

    addFuelType(data) {
      const id = (this.data.fuel_types.length ? Math.max(...this.data.fuel_types.map((f) => f.id)) : 0) + 1;
      const fuel = {
        id,
        name: String(data.name).trim(),
        ron: data.ron || 'Oktan/Cetane',
        description: data.description || '',
        price_per_liter: parseInt(data.price_per_liter) || 10000,
        is_active: true,
        color: data.color || '#0284c7'
      };
      this.data.fuel_types.push(fuel);
      this.save();
      this.logActivity('CREATE', 'Jenis BBM', String(id), `Menambahkan jenis BBM: ${fuel.name} (Rp ${fuel.price_per_liter.toLocaleString('id-ID')})`);
      request('POST', '/api/fuel-types', fuel);
      return fuel;
    }

    updateFuelType(id, updates) {
      const fuel = this.getFuelTypeById(id);
      if (!fuel) return null;
      const oldPrice = fuel.price_per_liter;
      Object.assign(fuel, updates);
      this.save();
      const desc = updates.price_per_liter && updates.price_per_liter !== oldPrice
        ? `Penyesuaian harga ${fuel.name}: Rp ${oldPrice.toLocaleString('id-ID')} -> Rp ${fuel.price_per_liter.toLocaleString('id-ID')}`
        : `Memperbarui info jenis BBM ${fuel.name}`;
      this.logActivity('UPDATE', 'Jenis BBM', String(id), desc);
      request('PUT', `/api/fuel-types/${id}`, updates);
      return fuel;
    }

    deleteFuelType(id) {
      const fuel = this.getFuelTypeById(id);
      if (!fuel) return false;
      fuel.is_active = !fuel.is_active;
      this.save();
      this.logActivity('STATUS', 'Jenis BBM', String(id), `BBM ${fuel.name} ${fuel.is_active ? 'diaktifkan' : 'dinonaktifkan'}`);
      request('POST', `/api/fuel-types/${id}/toggle`, {});
      return true;
    }

    // ================== STOCKS ==================
    getStocks(stationId = null) {
      let list = this.data.fuel_stocks;
      if (stationId) {
        list = list.filter((s) => s.station_id === stationId);
      }
      return list.map((stock) => {
        const fuel = this.getFuelTypeById(stock.fuel_type_id) || { name: 'BBM', ron: '-', price_per_liter: 0, color: '#94a3b8' };
        const station = this.getStationById(stock.station_id) || { name: 'SPBU', region: '-' };
        let status = 'Tersedia';
        if (stock.stock_liters <= 0) {
          status = 'Habis';
        } else if (stock.stock_liters <= stock.low_stock_threshold) {
          status = 'Menipis';
        }
        return {
          ...stock,
          fuel_name: fuel.name,
          fuel_ron: fuel.ron,
          fuel_price: fuel.price_per_liter,
          fuel_color: fuel.color,
          station_name: station.name,
          station_region: station.region,
          status,
          percentage: stock.capacity_liters > 0 ? Math.round((stock.stock_liters / stock.capacity_liters) * 100) : 0
        };
      });
    }

    updateStock(stockId, newLiters, note = 'Pembaruan manual petugas') {
      const stock = this.data.fuel_stocks.find((s) => s.id === Number(stockId));
      if (!stock) return { success: false, message: 'Catatan stok tidak ditemukan' };
      const liters = parseInt(newLiters);
      if (isNaN(liters) || liters < 0) {
        return { success: false, message: 'Jumlah stok tidak boleh negatif!' };
      }
      if (liters > stock.capacity_liters) {
        return { success: false, message: `Stok melebihi kapasitas maksimum (${stock.capacity_liters.toLocaleString('id-ID')} L)!` };
      }

      const previous = stock.stock_liters;
      const diff = liters - previous;
      const user = this.getCurrentUser();
      const fuel = this.getFuelTypeById(stock.fuel_type_id);
      stock.stock_liters = liters;
      stock.updated_by = user.name;
      stock.updated_at = stamp();

      this.data.stock_histories.unshift({
        id: (this.data.stock_histories.length ? Math.max(...this.data.stock_histories.map((h) => h.id)) : 0) + 1,
        fuel_stock_id: stock.id,
        station_id: stock.station_id,
        fuel_name: fuel ? fuel.name : 'BBM',
        previous_stock: previous,
        new_stock: liters,
        change_amount: diff,
        note,
        updated_by: user.name,
        created_at: stamp()
      });

      this.save();
      this.logActivity('UPDATE_STOCK', 'Stok BBM', String(stock.id), `Update stok ${fuel?.name || 'BBM'} di ${stock.station_id}: ${previous.toLocaleString()}L -> ${liters.toLocaleString()}L (${diff >= 0 ? '+' : ''}${diff.toLocaleString()}L)`);
      request('PUT', `/api/fuel-stocks/${stock.id}`, { stock_liters: liters, note });
      return { success: true, item: stock };
    }

    getStockHistories(stationId = null) {
      return stationId
        ? this.data.stock_histories.filter((h) => h.station_id === stationId)
        : this.data.stock_histories;
    }

    // ================== QUEUES ==================
    getQueueStatus(stationId) {
      return this.data.queue_statuses.find((q) => q.station_id === stationId) || {
        station_id: stationId,
        vehicle_category: 'Semua Kendaraan',
        estimated_wait_minutes: 5,
        queue_level: 'Lancar',
        source: 'Simulasi',
        updated_by: 'Sistem',
        updated_at: stamp()
      };
    }

    applyQueue(stationId, updates) {
      const index = this.data.queue_statuses.findIndex((q) => q.station_id === stationId);
      const payload = {
        station_id: stationId,
        vehicle_category: updates.vehicle_category || 'Semua Kendaraan',
        estimated_wait_minutes: parseInt(updates.estimated_wait_minutes) || 5,
        queue_level: updates.queue_level || 'Lancar',
        source: updates.source || (this.getCurrentUser().role === 'petugas' ? 'Petugas' : 'Simulasi'),
        updated_by: this.getCurrentUser().name,
        updated_at: stamp()
      };
      if (index !== -1) {
        this.data.queue_statuses[index] = { ...this.data.queue_statuses[index], ...payload };
      } else {
        payload.id = Date.now();
        this.data.queue_statuses.push(payload);
      }
      return payload;
    }

    updateQueueStatus(stationId, updates) {
      const payload = this.applyQueue(stationId, updates);
      this.save();
      this.logActivity('UPDATE_QUEUE', 'Antrean', stationId, `Update antrean SPBU: ${payload.queue_level} (Est. ${payload.estimated_wait_minutes} menit)`);
      request('PUT', `/api/queue-statuses/${encodeURIComponent(stationId)}`, payload);
      return payload;
    }

    simulateLiveTick() {
      request('POST', '/api/queue-statuses/simulate', {}).then((result) => {
        if (!result?.ok) return;
        this.applyQueue(result.station_id, {
          estimated_wait_minutes: result.estimated_wait_minutes,
          queue_level: result.queue_level,
          source: 'Sensor ATG'
        });
        this.save();
        this.logActivity('UPDATE_QUEUE', 'Antrean', result.station_id, `Simulasi sensor: ${result.queue_level} (${result.estimated_wait_minutes} menit)`);
      });
    }

    // ================== REPORTS ==================
    getReports(filterStationId = null, filterStatus = null) {
      let list = this.data.citizen_reports;
      if (filterStationId) list = list.filter((r) => r.station_id === filterStationId);
      if (filterStatus && filterStatus !== 'Semua') list = list.filter((r) => r.status === filterStatus);
      return list;
    }

    getReportById(id) {
      return this.data.citizen_reports.find((r) => r.id === id || r.report_code === id);
    }

    addReport(reportData) {
      const dateStr = new Date().toISOString().slice(0, 10).replace(/-/g, '');
      const code = `LAP-${dateStr}-${String(this.data.citizen_reports.length + 1).padStart(3, '0')}`;
      const station = this.getStationById(reportData.station_id);
      const user = this.getCurrentUser();
      const report = {
        id: code,
        report_code: code,
        user_id: user.id || 4,
        user_name: reportData.user_name || user.name || 'Warga',
        station_id: reportData.station_id,
        station_name: station ? station.name : 'SPBU Umum',
        category: reportData.category || 'Antrean panjang',
        description: String(reportData.description).trim(),
        photo_url: reportData.photo_url || '',
        status: 'Menunggu',
        admin_note: '',
        handled_by: null,
        created_at: stamp(),
        updated_at: stamp()
      };
      this.data.citizen_reports.unshift(report);
      this.save();
      this.logActivity('CREATE_REPORT', 'Laporan Warga', code, `Laporan warga baru #${code} diajukan untuk ${report.station_name}`);
      request('POST', '/api/reports', reportData).then((result) => {
        if (!result?.ok || result.id === code) return;
        report.id = result.id;
        report.report_code = result.id;
        this.save();
      });
      return report;
    }

    updateReportStatus(reportId, newStatus, adminNote = '') {
      const report = this.getReportById(reportId);
      if (!report) return null;
      report.status = newStatus;
      if (adminNote) report.admin_note = adminNote;
      report.handled_by = this.getCurrentUser().name;
      report.updated_at = stamp();
      this.save();
      this.logActivity('UPDATE_REPORT', 'Laporan Warga', report.id, `Status laporan #${report.id} diubah ke: ${newStatus}`);
      request('PUT', `/api/reports/${encodeURIComponent(report.id)}`, { status: newStatus, admin_note: adminNote });
      return report;
    }

    deleteReport(reportId) {
      const report = this.getReportById(reportId);
      if (!report) return false;
      this.data.citizen_reports = this.data.citizen_reports.filter((r) => r.id !== report.id && r.report_code !== report.id);
      this.save();
      this.logActivity('DELETE_REPORT', 'Laporan Warga', report.id, `Menghapus laporan #${report.id}`);
      request('DELETE', `/api/reports/${encodeURIComponent(report.id)}`);
      return true;
    }

    // ================== SYSTEM ==================
    resetDefaults() {
      request('POST', '/api/system/reset', {}).then((result) => {
        if (result?.ok) window.location.reload();
      });
    }

    importSnapshot(data) {
      request('POST', '/api/system/import', data).then((result) => {
        if (result?.ok) window.location.reload();
      });
    }
  }

  window.SPBUStore = new SPBUStore();
})(window);
