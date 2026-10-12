<?php

class RoleModel extends BaseModel {

    public function __construct(DBconnection $db) {

        parent::__construct($db);

        $this->tabel = 'role';
        $this->primary_key = 'idrole';
    }

    public function insert(array $data): Respon {

        return $this->db->send_query(
            'INSERT INTO role (nama_role)
             VALUES ($1)
             RETURNING idrole',
            [$data['nama_role']]
        );
    }
}