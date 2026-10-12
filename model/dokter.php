<?php

class Dokter extends User {

    private string $no_izin;
    private string $spesialisasi;

    public function __construct(
        int $iduser,
        string $nama,
        string $email,
        string $no_izin,
        string $spesialisasi
    ) {
        parent::__construct(
            $iduser,
            $nama,
            $email
        );

        $this->no_izin = $no_izin;
        $this->spesialisasi = $spesialisasi;
    }

    public function get_no_izin(): string {
        return $this->no_izin;
    }

    public function get_spesialisasi(): string {
        return $this->spesialisasi;
    }

    public function get_user(): array {

        $data = parent::get_user();

        $data['no_izin'] = $this->no_izin;
        $data['spesialisasi'] = $this->spesialisasi;

        return $data;
    }

    public function __toString(): string {

        return parent::__toString()
            . " — dokter, "
            . $this->spesialisasi;
    }
}