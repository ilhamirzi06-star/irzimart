1\. Stored Procedure (`sp_laporan_penjualan`)\
Menampilkan laporan penjualan berdasarkan rentang tanggal tertentu. Dipanggil menggunakan `CALL`.

2\. Stored Function (`fn_hitung_ppn`)\
Menghitung nominal PPN berdasarkan subtotal dan persentase pajak. Menghasilkan nilai menggunakan `RETURN`.

3\. Trigger (`trg_validasi_detail_penjualan`)\
Memvalidasi jumlah barang dan menghitung subtotal secara otomatis sebelum data dimasukkan ke tabel `detail_penjualan` (`BEFORE INSERT`).

# IrziMart

### Sistem Informasi Manajemen Toko Bangunan

IrziMart merupakan aplikasi berbasis web yang dirancang untuk membantu pengelolaan operasional toko bangunan secara terintegrasi. Sistem ini menyediakan pengelolaan data master, transaksi pengadaan, penerimaan barang, penjualan, hingga pemantauan stok barang.

IrziMart dikembangkan untuk mempermudah proses administrasi toko, meningkatkan keteraturan pencatatan transaksi, serta membantu pengelolaan informasi barang dan persediaan dalam satu sistem.

## Fitur Utama

### 1. Dashboard
- Menampilkan ringkasan informasi sistem.
- Statistik jumlah barang dan vendor.
- Informasi total transaksi dan nilai penjualan.
- Grafik penjualan tahunan.
- Riwayat transaksi penjualan terbaru.
- Akses cepat ke berbagai modul.

### 2. Master Data
- **Barang:** Mengelola informasi barang, harga, satuan, dan status barang.
- **Satuan:** Mengelola satuan barang.
- **Vendor:** Mengelola data pemasok atau vendor.
- **User:** Mengelola akun pengguna sistem.
- **Role:** Mengatur peran dan hak akses pengguna.
- **Margin Penjualan:** Mengelola persentase margin penjualan.

### 3. Transaksi
- **Pengadaan:** Mengelola transaksi pembelian barang dari vendor.
- **Penerimaan:** Mencatat penerimaan barang berdasarkan transaksi pengadaan.
- **Penjualan:** Mengelola transaksi penjualan barang kepada pelanggan.
- **Retur:** Mengelola transaksi pengembalian barang.

### 4. Manajemen Stok
- Mencatat barang masuk dan barang keluar.
- Mengelola riwayat pergerakan stok melalui kartu stok.
- Memantau saldo persediaan barang.

### 5. Manajemen Pengguna
- Mengelola autentikasi pengguna.
- Menerapkan pembagian hak akses berdasarkan role.
- Membatasi akses modul sesuai kewenangan pengguna.

## Struktur Direktori

```text
IrziMart/
│
├── assets/
│   ├── css/
│   │   └── dashboard.css
│   │
│   └── images/
│       └── logo.png
│
├── config/
│   └── Database.php
│
├── modules/
│   ├── barang/
│   ├── satuan/
│   ├── vendor/
│   ├── user/
│   ├── role/
│   │
│   ├── margin_penjualan/
│   │
│   ├── pengadaan/
│   ├── penerimaan/
│   │   ├── index.php
│   │   ├── create.php
│   │   ├── detail.php
│   │   └── proses.php
│   │
│   ├── penjualan/
│   └── retur/
│
├── index.php
├── login.php
├── logout.php
├── .gitignore
└── README.md
```

### Keterangan Direktori

| Direktori/File | Keterangan |
|---|---|
| `assets/` | Menyimpan aset pendukung aplikasi |
| `assets/css/` | Menyimpan file stylesheet |
| `assets/images/` | Menyimpan gambar dan logo |
| `config/` | Menyimpan konfigurasi aplikasi dan koneksi database |
| `modules/` | Menyimpan modul-modul utama sistem |
| `modules/barang/` | Pengelolaan data barang |
| `modules/satuan/` | Pengelolaan data satuan |
| `modules/vendor/` | Pengelolaan data vendor |
| `modules/user/` | Pengelolaan pengguna |
| `modules/role/` | Pengelolaan hak akses |
| `modules/margin_penjualan/` | Pengelolaan margin penjualan |
| `modules/pengadaan/` | Pengelolaan transaksi pengadaan |
| `modules/penerimaan/` | Pengelolaan transaksi penerimaan |
| `modules/penjualan/` | Pengelolaan transaksi penjualan |
| `modules/retur/` | Pengelolaan transaksi retur |
| `index.php` | Halaman utama atau dashboard |
| `login.php` | Halaman autentikasi pengguna |
| `logout.php` | Proses keluar dari sistem |

---

**IrziMart**  
*Sistem Informasi Manajemen Toko Bangunan*

© 2026 IrziMart. All Rights Reserved.