<?php

abstract class BaseModel implements Crudable {

    protected DBconnection $db;
    protected string $tabel = '';
    protected string $primary_key = '';

    public function __construct(DBconnection $db) {
        $this->db = $db;
    }

    public function find_all(): array {

        $respon = $this->db->send_query(
            'SELECT * FROM ' . $this->tabel .
            ' ORDER BY ' . $this->primary_key
        );

        return $respon->data;
    }

    public function find_by_id(int $id): ?array {

        $respon = $this->db->send_query(
            'SELECT * FROM ' . $this->tabel .
            ' WHERE ' . $this->primary_key . ' = $1',
            [$id]
        );

        return $respon->data[0] ?? null;
    }

    public function nama_tabel(): string {
        return $this->tabel;
    }

    abstract public function insert(array $data): Respon;

    public function update(int $id, array $data): Respon {

        $fields = [];
        $values = [];
        $param = 1;

        foreach ($data as $kolom => $nilai) {

            $fields[] = $kolom . ' = $' . $param;
            $values[] = $nilai;

            $param++;
        }

        $values[] = $id;

        $sql = 'UPDATE ' . $this->tabel
             . ' SET ' . implode(', ', $fields)
             . ' WHERE ' . $this->primary_key
             . ' = $' . $param;

        return $this->db->send_query($sql, $values);
    }

    public function delete(int $id): Respon {

        return $this->db->send_query(
            'DELETE FROM ' . $this->tabel
            . ' WHERE ' . $this->primary_key . ' = $1',
            [$id]
        );
    }
}