<?php

class DokterModel extends BaseModel {

    public function __construct(DBconnection $db) {

        parent::__construct($db);

        $this->tabel = 'dokter';
        $this->primary_key = 'iddokter';
    }

    public function insert(array $data): Respon {

        return $this->db->send_query(
            'INSERT INTO dokter
             (no_izin, spesialisasi, iduser)
             VALUES ($1, $2, $3)
             RETURNING iddokter',
            [
                $data['no_izin'],
                $data['spesialisasi'],
                $data['iduser']
            ]
        );
    }

    public function find_by_iduser(int $iduser): ?array {

        $respon = $this->db->send_query(
            'SELECT * FROM dokter WHERE iduser = $1',
            [$iduser]
        );

        return $respon->data[0] ?? null;
    }
}