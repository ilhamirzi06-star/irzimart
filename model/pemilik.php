<?php

class Pemilik extends User {

    private string $no_wa;
    private string $alamat;

    public function __construct(
        int $iduser,
        string $nama,
        string $email,
        string $no_wa,
        string $alamat
    ) {
        parent::__construct($iduser, $nama, $email);

        $this->no_wa = $no_wa;
        $this->alamat = $alamat;
    }

    public function get_no_wa(): string {
        return $this->no_wa;
    }

    public function get_alamat(): string {
        return $this->alamat;
    }

    // overriding: data pemilik memuat dua kolom tambahan
    public function get_user(): array {

        $data = parent::get_user();

        $data['no_wa'] = $this->no_wa;
        $data['alamat'] = $this->alamat;

        return $data;
    }

    // overriding magic method milik parent
    public function __toString(): string {

        return parent::__toString()
            . " — pemilik, WA "
            . $this->no_wa;
    }
}