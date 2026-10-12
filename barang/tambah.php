<?php
include "../config/database.php";

$satuan = mysqli_query($koneksi, "SELECT * FROM satuan WHERE status = 1");

if (isset($_POST['simpan'])) {

    $jenis = $_POST['jenis'];
    $nama = $_POST['nama'];
    $idsatuan = $_POST['idsatuan'];
    $harga = $_POST['harga'];

    mysqli_query($koneksi, "
        INSERT INTO barang
        (jenis, nama, idsatuan, status, harga)
        VALUES
        ('$jenis', '$nama', '$idsatuan', 1, '$harga')
    ");

    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>

    <meta charset="UTF-8">

    <title>Tambah Barang - IrziMart</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

</head>

<body>

<div class="container mt-5">

    <div class="card shadow-sm">

        <div class="card-body">

            <h3 class="mb-4">Tambah Barang</h3>

            <form method="POST">

                <div class="mb-3">

                    <label class="form-label">
                        Jenis Barang
                    </label>

                    <select name="jenis" class="form-select" required>

                        <option value="">-- Pilih Jenis --</option>

                        <option value="A">A</option>
                        <option value="B">B</option>

                    </select>

                </div>


                <div class="mb-3">

                    <label class="form-label">
                        Nama Barang
                    </label>

                    <input type="text"
                           name="nama"
                           class="form-control"
                           required>

                </div>


                <div class="mb-3">

                    <label class="form-label">
                        Satuan
                    </label>

                    <select name="idsatuan"
                            class="form-select"
                            required>

                        <option value="">
                            -- Pilih Satuan --
                        </option>

                        <?php while ($data = mysqli_fetch_assoc($satuan)) { ?>

                            <option value="<?= $data['idsatuan']; ?>">
                                <?= $data['nama_satuan']; ?>
                            </option>

                        <?php } ?>

                    </select>

                </div>


                <div class="mb-3">

                    <label class="form-label">
                        Harga
                    </label>

                    <input type="number"
                           name="harga"
                           class="form-control"
                           required>

                </div>


                <div class="d-flex gap-2">

                    <button type="submit"
                            name="simpan"
                            class="btn btn-primary">
                        Simpan
                    </button>

                    <a href="index.php"
                       class="btn btn-secondary">
                        Kembali
                    </a>

                </div>

            </form>

        </div>

    </div>

</div>

</body>
</html>