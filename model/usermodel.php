<?php

class UserModel extends BaseModel implements Autentikasi {

    public function __construct(DBconnection $db) {
        parent::__construct($db);

        $this->tabel = '"user"';
        $this->primary_key = 'iduser';
    }

    public function insert(array $data): Respon {

        $hash = password_hash(
            $data['password'],
            PASSWORD_DEFAULT
        );

        return $this->db->send_query(
            'INSERT INTO "user" (nama, email, password)
             VALUES ($1, $2, $3)
             RETURNING iduser',
            [
                $data['nama'],
                strtolower(trim($data['email'])),
                $hash
            ]
        );
    }

    public function find_all(): array {

        $respon = $this->db->send_query(
            'SELECT iduser, nama, email
             FROM "user"
             ORDER BY iduser'
        );

        return $respon->data;
    }

    public function find_by_email(string $email): ?array {

        $respon = $this->db->send_query(
            'SELECT * FROM "user"
             WHERE email = $1',
            [
                strtolower(trim($email))
            ]
        );

        return $respon->data[0] ?? null;
    }

    public function simpan_role_aktif(
        int $iduser,
        int $idrole
    ): Respon {

        $this->db->send_query(
            'UPDATE user_role
             SET status = FALSE
             WHERE iduser = $1',
            [$iduser]
        );

        return $this->db->send_query(
            'INSERT INTO user_role
             (iduser, idrole, status)
             VALUES ($1, $2, TRUE)',
            [
                $iduser,
                $idrole
            ]
        );
    }

    public function verifikasi(
        string $email,
        string $password
    ): ?User {

        $row = $this->find_by_email($email);

        if (
            $row === null ||
            !password_verify(
                $password,
                $row['password']
            )
        ) {
            return null;
        }

        $iduser = (int) $row['iduser'];

        /*
         * Cek apakah user merupakan pemilik
         */
        $pemilik = (new PemilikModel($this->db))
            ->find_by_iduser($iduser);

        /*
         * Cek apakah user merupakan dokter
         */
        $dokter = (new DokterModel($this->db))
            ->find_by_iduser($iduser);

        if ($pemilik !== null) {

            $user = new Pemilik(
                $iduser,
                $row['nama'],
                $row['email'],
                $pemilik['no_wa'],
                $pemilik['alamat']
            );

        } elseif ($dokter !== null) {

            $user = new Dokter(
                $iduser,
                $row['nama'],
                $row['email'],
                $dokter['no_izin'],
                $dokter['spesialisasi']
            );

        } else {

            $user = new User(
                $iduser,
                $row['nama'],
                $row['email']
            );
        }

        /*
         * Ambil role user
         */
        $respon = $this->db->send_query(
            'SELECT r.idrole, r.nama_role, ur.status
             FROM user_role ur
             JOIN role r
               ON r.idrole = ur.idrole
             WHERE ur.iduser = $1',
            [$iduser]
        );

        foreach ($respon->data as $baris) {

            $user->set_role(
                new Role(
                    (int) $baris['idrole'],
                    $baris['nama_role'],
                    $baris['status'] === 't'
                )
            );
        }

        return $user;
    }
}