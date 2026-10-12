<?php
include "../config/database.php";

$id = $_GET['id'];

$data = mysqli_fetch_assoc(
    mysqli_query(
        $koneksi,
        "SELECT * FROM barang WHERE idbarang = '$id'"
    )
);

$satuan = mysqli_query(
    $koneksi,
    "SELECT * FROM satuan WHERE status = 1"
);

if (isset($_POST['update'])) {

    $jenis = $_POST['jenis'];
    $nama = $_POST['nama'];
    $idsatuan = $_POST['idsatuan'];
    $harga = $_POST['harga'];
    $status = $_POST['status'];

    mysqli_query($koneksi, "
        UPDATE barang
        SET
            jenis = '$jenis',
            nama = '$nama',
            idsatuan = '$idsatuan',
            status = '$status',
            harga = '$harga'
        WHERE idbarang = '$id'
    ");

    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <title>Edit Barang - IrziMart</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

</head>

<body>

<div class="container mt-5">

    <div class="card shadow-sm">

        <div class="card-body">

            <h3 class="mb-4">
                Edit Barang
            </h3>

            <form method="POST">

                <div class="mb-3">

                    <label class="form-label">
                        Jenis Barang
                    </label>

                    <select name="jenis"
                            class="form-select"
                            required>

                        <option value="A"
                            <?= $data['jenis'] == 'A' ? 'selected' : ''; ?>>
                            A
                        </option>

                        <option value="B"
                            <?= $data['jenis'] == 'B' ? 'selected' : ''; ?>>
                            B
                        </option>

                    </select>

                </div>


                <div class="mb-3">

                    <label class="form-label">
                        Nama Barang
                    </label>

                    <input type="text"
                           name="nama"
                           class="form-control"
                           value="<?= $data['nama']; ?>"
                           required>

                </div>


                <div class="mb-3">

                    <label class="form-label">
                        Satuan
                    </label>

                    <select name="idsatuan"
                            class="form-select"
                            required>

                        <?php while ($s = mysqli_fetch_assoc($satuan)) { ?>

                            <option value="<?= $s['idsatuan']; ?>"
                                <?= $data['idsatuan'] == $s['idsatuan'] ? 'selected' : ''; ?>>

                                <?= $s['nama_satuan']; ?>

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
                           value="<?= $data['harga']; ?>"
                           required>

                </div>


                <div class="mb-3">

                    <label class="form-label">
                        Status
                    </label>

                    <select name="status"
                            class="form-select">

                        <option value="1"
                            <?= $data['status'] == 1 ? 'selected' : ''; ?>>
                            Aktif
                        </option>

                        <option value="0"
                            <?= $data['status'] == 0 ? 'selected' : ''; ?>>
                            Nonaktif
                        </option>

                    </select>

                </div>


                <div class="d-flex gap-2">

                    <button type="submit"
                            name="update"
                            class="btn btn-warning">
                        Update
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