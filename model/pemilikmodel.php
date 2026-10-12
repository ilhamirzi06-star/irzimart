<?php

class PemilikModel extends BaseModel {

    public function __construct(DBconnection $db) {

        parent::__construct($db);

        $this->tabel = 'pemilik';
        $this->primary_key = 'idpemilik';
    }

    public function insert(array $data): Respon {

        return $this->db->send_query(
            'INSERT INTO pemilik
             (no_wa, alamat, iduser)
             VALUES ($1, $2, $3)
             RETURNING idpemilik',
            [
                $data['no_wa'],
                $data['alamat'],
                $data['iduser']
            ]
        );
    }

    public function find_by_iduser(int $iduser): ?array {

        $respon = $this->db->send_query(
            'SELECT * FROM pemilik WHERE iduser = $1',
            [$iduser]
        );

        return $respon->data[0] ?? null;
    }
}