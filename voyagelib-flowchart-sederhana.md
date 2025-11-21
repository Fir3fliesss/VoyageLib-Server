# VoyageLib - Flowchart Sederhana

## Alur Aplikasi Lengkap (Sederhana)

```mermaid
flowchart TD
    Start([Mulai]) --> Landing[Landing Page<br/>VoyageLib]
    
    Landing --> PilihAksi{Pilih Aksi}
    
    %% LOGIN
    PilihAksi -->|Login| FormLogin[Form Login<br/>Email & Password]
    FormLogin --> CekLogin{Cek Kredensial<br/>di Supabase}
    CekLogin -->|Salah| ErrorLogin[Tampilkan Error]
    ErrorLogin --> FormLogin
    CekLogin -->|Benar| AmbilRole[Ambil Data User<br/>& Role]
    
    %% REGISTER
    PilihAksi -->|Register| FormRegister[Form Register<br/>Email, Password, Nama,<br/>Phone, Alamat]
    FormRegister --> CekRegister{Validasi Data}
    CekRegister -->|Gagal| ErrorRegister[Tampilkan Error]
    ErrorRegister --> FormRegister
    CekRegister -->|Berhasil| BuatAkun[Buat Akun di Supabase<br/>Role: User]
    BuatAkun --> AmbilRole
    
    %% ROLE CHECK
    AmbilRole --> CekRole{Cek Role}
    
    %% ==========================
    %% ALUR USER
    %% ==========================
    CekRole -->|User| DashboardUser[Dashboard User]
    DashboardUser --> MenuUser{Menu User}
    
    MenuUser -->|Lihat Buku| TampilBuku[Tampilkan Semua Buku<br/>Local DB + API Gutendex]
    TampilBuku --> AksiBuku{Aksi pada Buku}
    
    AksiBuku -->|Baca Online| BukaBaca[Buka Reader<br/>untuk buku dari API]
    BukaBaca --> MenuUser
    
    AksiBuku -->|Pinjam Buku| CekStok{Stok Tersedia?}
    CekStok -->|Tidak| StokHabis[Tampilkan:<br/>Stok Habis]
    StokHabis --> AksiBuku
    CekStok -->|Ya| FormPinjam[Form Peminjaman<br/>Input Durasi]
    FormPinjam --> KirimRequest[Kirim Request<br/>Status: Pending]
    KirimRequest --> Notif1[Notifikasi ke Staff]
    Notif1 --> MenuUser
    
    AksiBuku -->|Kembali| MenuUser
    
    MenuUser -->|Peminjaman Saya| LihatPinjaman[Lihat History<br/>Peminjaman]
    LihatPinjaman --> MenuUser
    
    MenuUser -->|Profil| LihatProfil[Lihat & Edit Profil]
    LihatProfil --> MenuUser
    
    MenuUser -->|Logout| Logout1[Logout]
    Logout1 --> End([Selesai])
    
    %% ==========================
    %% ALUR STAFF
    %% ==========================
    CekRole -->|Staff| DashboardStaff[Dashboard Staff]
    DashboardStaff --> TampilStatistik[Tampilkan Statistik]
    TampilStatistik --> MenuStaff{Menu Staff}
    
    MenuStaff -->|Kelola Buku| LihatSemuaBuku[Lihat Semua Buku]
    LihatSemuaBuku --> AksiBukuStaff{Aksi}
    
    AksiBukuStaff -->|Tambah Buku| FormTambah[Form Tambah Buku<br/>Judul, Cover, Penulis,<br/>Deskripsi, ISBN,<br/>Kategori, Stok]
    FormTambah --> SimpanBuku[Simpan ke Database]
    SimpanBuku --> LihatSemuaBuku
    
    AksiBukuStaff -->|Edit Buku| EditBuku[Edit Data Buku]
    EditBuku --> UpdateBuku[Update Database]
    UpdateBuku --> LihatSemuaBuku
    
    AksiBukuStaff -->|Hapus Buku| HapusBuku[Hapus dari Database]
    HapusBuku --> LihatSemuaBuku
    
    AksiBukuStaff -->|Kembali| MenuStaff
    
    MenuStaff -->|Kelola Peminjaman| LihatRequest[Lihat Request Pending]
    LihatRequest --> AksiRequest{Aksi}
    
    AksiRequest -->|Approve| SetujuiPinjam[Update Status: Approved<br/>Kurangi Stok<br/>Set Tanggal Kembali]
    SetujuiPinjam --> Notif2[Notifikasi User]
    Notif2 --> JadwalKembali[Jadwalkan<br/>Auto Return]
    JadwalKembali --> LihatRequest
    
    AksiRequest -->|Reject| TolakPinjam[Update Status: Rejected<br/>Isi Alasan]
    TolakPinjam --> Notif3[Notifikasi User]
    Notif3 --> LihatRequest
    
    AksiRequest -->|Kembali| MenuStaff
    
    MenuStaff -->|Promosi| KelolaPromo[Kelola Promosi<br/>Upload Infografis]
    KelolaPromo --> MenuStaff
    
    MenuStaff -->|Laporan| ExportLaporan[Export Laporan<br/>Excel/PDF/CSV]
    ExportLaporan --> MenuStaff
    
    MenuStaff -->|Profil| ProfilStaff[Lihat & Edit Profil]
    ProfilStaff --> MenuStaff
    
    MenuStaff -->|Logout| Logout2[Logout]
    Logout2 --> End
    
    %% ==========================
    %% ALUR ADMIN
    %% ==========================
    CekRole -->|Admin| DashboardAdmin[Dashboard Admin]
    DashboardAdmin --> TampilStatAdmin[Tampilkan Statistik Lengkap]
    TampilStatAdmin --> MenuAdmin{Menu Admin}
    
    MenuAdmin -->|Fitur Staff| AksesStaff[Akses Semua<br/>Fitur Staff]
    AksesStaff --> MenuStaff
    
    MenuAdmin -->|Kelola Staff| LihatStaff[Lihat Semua Staff]
    LihatStaff --> AksiStaff{Aksi}
    
    AksiStaff -->|Tambah Staff| FormStaff[Form Tambah Staff<br/>Email, Password,<br/>Nama, Phone, Alamat]
    FormStaff --> BuatStaff[Buat Akun Staff<br/>di Supabase]
    BuatStaff --> LihatStaff
    
    AksiStaff -->|Edit Staff| EditStaff[Edit Data Staff]
    EditStaff --> UpdateStaff[Update Database]
    UpdateStaff --> LihatStaff
    
    AksiStaff -->|Hapus Staff| HapusStaff[Hapus Staff]
    HapusStaff --> LihatStaff
    
    AksiStaff -->|Kembali| MenuAdmin
    
    MenuAdmin -->|Lihat Semua User| TampilUser[Tampilkan<br/>Semua User]
    TampilUser --> MenuAdmin
    
    MenuAdmin -->|Profil| ProfilAdmin[Lihat & Edit Profil]
    ProfilAdmin --> MenuAdmin
    
    MenuAdmin -->|Logout| Logout3[Logout]
    Logout3 --> End
    
    %% ==========================
    %% AUTO RETURN (Background)
    %% ==========================
    JadwalKembali -.->|Cron Job Tiap Jam| CekTanggal[Cek Tanggal<br/>Pengembalian]
    CekTanggal -->|Sudah Lewat| AutoReturn[Update Status: Returned<br/>Tambah Stok]
    AutoReturn --> NotifReturn[Notifikasi User]
    NotifReturn --> CekTanggal
    
    %% STYLING
    style Start fill:#4caf50,stroke:#2e7d32,color:#fff
    style End fill:#f44336,stroke:#c62828,color:#fff
    style DashboardUser fill:#c8e6c9,stroke:#4caf50,stroke-width:3px
    style DashboardStaff fill:#b3e5fc,stroke:#03a9f4,stroke-width:3px
    style DashboardAdmin fill:#e1bee7,stroke:#9c27b0,stroke-width:3px
    style CekTanggal fill:#fff9c4,stroke:#fbc02d
    style KirimRequest fill:#ffccbc
    style SetujuiPinjam fill:#c5e1a5
    style TolakPinjam fill:#ffcdd2
```

---

## Penjelasan Singkat:

### 🟢 **Alur Utama:**
1. **Mulai** → Landing Page
2. **Pilih Login/Register**
3. **Login**: Cek kredensial → Ambil role → Dashboard
4. **Register**: Buat akun → Otomatis role User → Dashboard

### 👤 **User:**
- Lihat & baca buku online (dari API)
- Pinjam buku (jika stok ada)
- Lihat history peminjaman
- Edit profil
- Logout

### 👔 **Staff:**
- Semua fitur User +
- Tambah/edit/hapus buku lokal
- Approve/reject peminjaman
- Upload promosi
- Export laporan
- Logout

### 👑 **Admin:**
- Semua fitur Staff +
- Tambah/edit/hapus akun staff
- Lihat semua user
- Logout

### ⏰ **Background Job:**
- Cron job jalan tiap jam
- Cek tanggal pengembalian
- Otomatis kembalikan buku jika sudah lewat waktu
- Notifikasi user

---

## Warna:
- 🟢 Hijau = Start/Berhasil
- 🔴 Merah = End/Selesai
- 🟢 Hijau muda = Alur User
- 🔵 Biru muda = Alur Staff
- 🟣 Ungu muda = Alur Admin
- 🟡 Kuning = Background job
