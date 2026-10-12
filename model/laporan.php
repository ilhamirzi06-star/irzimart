<?php

abstract class Laporan {

    protected DBconnection $db;

    public function __construct(DBconnection $db) {
        $this->db = $db;
    }

    public function header(string $judul): void {
        echo "========================================<br>";
        echo " LAPORAN DATA " . strtoupper($judul) . "<br>";
        echo "========================================<br>";
    }

    public function footer(): void {
        echo "----------------------------------------<br>";
        echo "Dicetak pada: " . date('Y-m-d H:i:s') . "<br><br>";
    }

    abstract public function isi(): void;
}


class LaporanUser extends Laporan {

    public function isi(): void {

        $this->header("Pengguna (User)");

        $userModel = new UserModel($this->db);

        $data = $userModel->find_all();

        foreach ($data as $row) {

            echo "ID: {$row['iduser']} | "
                . "Nama: {$row['nama']} | "
                . "Email: {$row['email']}<br>";
        }

        $this->footer();
    }
}


class LaporanPemilik extends Laporan {

    public function isi(): void {

        $this->header("Pemilik Hewan");

        $respon = $this->db->send_query(
            'SELECT p.idpemilik, p.no_wa, p.alamat, u.nama, u.email
             FROM pemilik p
             JOIN "user" u ON u.iduser = p.iduser
             ORDER BY p.idpemilik'
        );

        $data = $respon->data ?? [];

        if (empty($data)) {

            echo "Belum ada data pemilik.<br>";

        } else {

            foreach ($data as $row) {

                echo "ID Pemilik: {$row['idpemilik']} | "
                    . "Nama: {$row['nama']} | "
                    . "WA: {$row['no_wa']} | "
                    . "Alamat: {$row['alamat']}<br>";
            }
        }

        $this->footer();
    }
}