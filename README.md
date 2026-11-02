1\. Stored Procedure (`sp_laporan_penjualan`)\
Menampilkan laporan penjualan berdasarkan rentang tanggal tertentu. Dipanggil menggunakan `CALL`.

2\. Stored Function (`fn_hitung_ppn`)\
Menghitung nominal PPN berdasarkan subtotal dan persentase pajak. Menghasilkan nilai menggunakan `RETURN`.

3\. Trigger (`trg_validasi_detail_penjualan`)\
Memvalidasi jumlah barang dan menghitung subtotal secara otomatis sebelum data dimasukkan ke tabel `detail_penjualan` (`BEFORE INSERT`).