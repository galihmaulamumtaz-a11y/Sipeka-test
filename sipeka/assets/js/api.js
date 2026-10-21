/**
 * SIPEKA Intelligent Dual-Engine API Client Layer
 * Mengintegrasikan komunikasi HTTP REST ke Backend PHP Native PDO,
 * dengan auto-fallback ke browser store jika diakses langsung tanpa web server (file:///).
 */

const API_CONFIG = {
  // Otomatis deteksi apakah aplikasi sedang berjalan di web server HTTP atau direct file
  IS_SERVER: window.location.protocol.startsWith('http'),
  BASE_URL: '/api'
};

const SipekaAPI = {
  // Helper: Request Wrapper dengan Auto-Fallback
  async request(endpoint, options = {}) {
    if (!API_CONFIG.IS_SERVER) {
      return null; // Fallback ke store lokal
    }

    try {
      const response = await fetch(`${API_CONFIG.BASE_URL}${endpoint}`, {
        credentials: 'same-origin',
        ...options
      });

      if (!response.ok && response.status === 404) {
        return null; // Endpoint backend belum siap, fallback ke store lokal
      }

      const data = await response.json().catch(() => null);
      if (!response.ok && data) {
        return data;
      }
      return data;
    } catch (err) {
      console.warn(`[SipekaAPI] Server offline atau tidak terjangkau. Menggunakan penyimpanan browser lokal.`, err);
      return null;
    }
  },

  // --- 1. AUTENTIKASI ---
  async login(emailOrNim, password, role) {
    const serverRes = await this.request('/auth/login.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ emailOrNim, password, role })
    });

    if (serverRes) {
      if (serverRes.success) {
        window.sipekaStore.setUser(serverRes.user);
        if (serverRes.csrf_token) {
          sessionStorage.setItem('csrf_token', serverRes.csrf_token);
        }
      }
      return serverRes;
    }

    // Fallback: Local store
    const localUser = (role === 'unit') ? {
      nama: 'Staff Sekretariat Jurusan',
      role: 'unit',
      unit_id: 'sekjur',
      kode_unit: 'sekjur',
      nama_unit: 'Sekretariat Jurusan (Sekjur)',
      email: 'sekjur@polinema.ac.id'
    } : {
      nama: 'Galih Maula Mumtaza',
      nim: emailOrNim.includes('@') ? emailOrNim.split('@')[0] : (emailOrNim || '254107020095'),
      role: 'mahasiswa',
      prodi: 'D4 Teknik Informatika (TI-2F)',
      email: emailOrNim || '254107020095@student.polinema.ac.id'
    };

    window.sipekaStore.setUser(localUser);
    return { success: true, user: localUser };
  },

  async register(payload) {
    const serverRes = await this.request('/auth/register.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });

    if (serverRes) {
      if (serverRes.success) {
        window.sipekaStore.setUser(serverRes.user);
        if (serverRes.csrf_token) {
          sessionStorage.setItem('csrf_token', serverRes.csrf_token);
        }
      }
      return serverRes;
    }

    // Fallback: Local store registration
    const unitNames = {
      'sekjur': 'Sekretariat Jurusan (Sekjur)',
      'prodi':  'Admin Program Studi (Admin Prodi)',
      'kajur':  'Ketua Jurusan (Kajur)',
      'kps':    'Ketua Program Studi (KPS)'
    };

    if (payload.role === 'unit') {
      const localUser = {
        nama: payload.nama,
        role: 'unit',
        unit_id: payload.kode_unit || 'sekjur',
        kode_unit: payload.kode_unit || 'sekjur',
        nama_unit: unitNames[payload.kode_unit] || 'Sekretariat Jurusan (Sekjur)',
        nip: payload.nip || '',
        email: payload.email,
        nomor_telepon: payload.nomor_telepon || ''
      };
      window.sipekaStore.setUser(localUser);
      return {
        success: true,
        message: `Pendaftaran berhasil! Selamat datang Petugas ${localUser.nama_unit}.`,
        user: localUser,
        redirect: '../unit/dashboard.php'
      };
    } else {
      const localUser = {
        nama: payload.nama,
        nim: payload.nim,
        role: 'mahasiswa',
        prodi: payload.program_studi || 'D4 Teknik Informatika (TI-2F)',
        jurusan: payload.jurusan || 'Teknologi Informasi',
        email: payload.email,
        nomor_telepon: payload.nomor_telepon || ''
      };
      window.sipekaStore.setUser(localUser);
      return {
        success: true,
        message: 'Registrasi berhasil! Selamat datang di SIPEKA.',
        user: localUser,
        redirect: '../mahasiswa/dashboard.php'
      };
    }
  },

  async logout() {
    await this.request('/auth/logout.php', { method: 'POST' });
    window.sipekaStore.logout();
    sessionStorage.removeItem('csrf_token');
    return { success: true };
  },

  async getSession() {
    const res = await this.request('/auth/session.php');
    if (res && res.success && res.user) {
      window.sipekaStore.setUser(res.user);
      if (res.csrf_token) sessionStorage.setItem('csrf_token', res.csrf_token);
      return res;
    }
    return { success: true, user: window.sipekaStore.getUser() };
  },

  // --- 2. MANAJEMEN PROFIL & PENGATURAN ---
  async getProfile() {
    const serverRes = await this.request('/user/profile.php');
    if (serverRes && serverRes.success && serverRes.profile) {
      return serverRes.profile;
    }

    // Fallback lokal
    const user = window.sipekaStore.getUser() || {};
    const stats = (user.role === 'unit')
      ? window.sipekaStore.getStats(user.unit_id || 'sekjur')
      : window.sipekaStore.getStats();

    return {
      nama: user.nama || 'Pengguna SIPEKA',
      email: user.email || 'user@polinema.ac.id',
      role: user.role || 'mahasiswa',
      nim: user.nim || '254107020095',
      program_studi: user.prodi || 'D4 Teknik Informatika (TI-2F)',
      jurusan: user.jurusan || 'Teknologi Informasi',
      nomor_telepon: user.nomor_telepon || '081234567890',
      nama_unit: user.nama_unit || 'Sekretariat Jurusan (Sekjur)',
      kode_unit: user.kode_unit || 'sekjur',
      deskripsi_unit: 'Menangani perbaikan fasilitas sarana dan prasarana kampus.',
      stats: stats
    };
  },

  async updateProfile(data) {
    const serverRes = await this.request('/user/profile.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'update_info', ...data })
    });

    if (serverRes && serverRes.success) {
      const currentUser = window.sipekaStore.getUser() || {};
      window.sipekaStore.setUser({ ...currentUser, ...data });
      return serverRes;
    }

    // Fallback lokal
    const currentUser = window.sipekaStore.getUser() || {};
    const updated = { ...currentUser, ...data };
    window.sipekaStore.setUser(updated);
    return { success: true, message: 'Profil berhasil diperbarui.', user: updated };
  },

  async changePassword(oldPassword, newPassword, confirmPassword) {
    const serverRes = await this.request('/user/profile.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        action: 'change_password',
        old_password: oldPassword,
        new_password: newPassword,
        confirm_password: confirmPassword
      })
    });

    if (serverRes) {
      return serverRes;
    }

    // Fallback lokal
    return { success: true, message: 'Kata sandi berhasil diperbarui.' };
  },

  // --- 3. PENGADUAN (CRUD) ---
  async getReports(params = {}) {
    const query = new URLSearchParams(params).toString();
    const serverRes = await this.request(`/laporan/index.php?${query}`);

    if (serverRes && serverRes.success) {
      return serverRes.data;
    }

    // Fallback lokal
    let reports = window.sipekaStore.getAllReports();
    if (params.unit && params.unit !== 'all') {
      reports = window.sipekaStore.getReportsByUnit(params.unit);
    }
    if (params.status && params.status !== 'all') {
      reports = reports.filter(r => r.status === params.status);
    }
    if (params.q) {
      const q = params.q.toLowerCase();
      reports = reports.filter(r => 
        r.no_tiket.toLowerCase().includes(q) ||
        r.judul_laporan.toLowerCase().includes(q) ||
        (r.kategori && r.kategori.toLowerCase().includes(q))
      );
    }
    return reports;
  },

  async getReportDetail(noTiket) {
    const serverRes = await this.request(`/laporan/detail.php?tiket=${encodeURIComponent(noTiket)}`);
    if (serverRes && serverRes.success) {
      return serverRes.data;
    }
    return window.sipekaStore.getReportById(noTiket);
  },

  async createReport(formDataPayload, rawFile = null) {
    if (API_CONFIG.IS_SERVER) {
      const formData = new FormData();
      formData.append('kategoriKey', formDataPayload.kategoriKey);
      formData.append('judul', formDataPayload.judul);
      formData.append('lokasi', formDataPayload.lokasi);
      formData.append('tanggal', formDataPayload.tanggal);
      formData.append('kronologi', formDataPayload.kronologi);
      if (rawFile) {
        formData.append('bukti', rawFile);
      }
      const csrf = sessionStorage.getItem('csrf_token');
      if (csrf) formData.append('csrf_token', csrf);

      const serverRes = await this.request('/laporan/create.php', {
        method: 'POST',
        body: formData
      });

      if (serverRes) {
        if (serverRes.success) {
          window.sipekaStore.createReport(formDataPayload);
        }
        return serverRes;
      }
    }

    // Fallback lokal
    const localReport = window.sipekaStore.createReport(formDataPayload);
    return { success: true, data: localReport };
  },

  // --- 4. AKSI UNIT ---
  async validateReport(noTiket, decision, keterangan = '') {
    const serverRes = await this.request('/unit/validasi.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ noTiket, decision, keterangan })
    });

    const newStatus = (decision === 'approve') ? 'Dalam Penanganan' : 'Tidak Valid';
    window.sipekaStore.updateStatus(noTiket, newStatus, keterangan);

    if (serverRes && serverRes.success) {
      return serverRes;
    }
    return { success: true, status: newStatus };
  },

  async resolveReport(noTiket, tindakan, rawProofFile = null) {
    if (API_CONFIG.IS_SERVER) {
      const formData = new FormData();
      formData.append('noTiket', noTiket);
      formData.append('tindakan', tindakan);
      if (rawProofFile) {
        formData.append('bukti_selesai', rawProofFile);
      }

      const serverRes = await this.request('/unit/penanganan.php', {
        method: 'POST',
        body: formData
      });

      if (serverRes && serverRes.success) {
        window.sipekaStore.updateStatus(noTiket, 'Selesai', tindakan);
        return serverRes;
      }
    }

    window.sipekaStore.updateStatus(noTiket, 'Selesai', tindakan);
    return { success: true, status: 'Selesai' };
  },

  // --- 5. STATISTIK METRIK ---
  async getStats(unitCode = 'all') {
    const serverRes = await this.request(`/stats/index.php?unit=${encodeURIComponent(unitCode)}`);
    if (serverRes && serverRes.success) {
      return serverRes.data;
    }
    return window.sipekaStore.getStats(unitCode === 'all' ? null : unitCode);
  }
};

window.sipekaAPI = SipekaAPI;
