<?php

class Role {
    private int $idrole;
    private string $nama_role;
    private bool $status;

    public function __construct(int $idrole, string $nama_role, bool $status = false) {
        $this->idrole = $idrole;
        $this->nama_role = $nama_role;
        $this->status = $status;
    }

    public function get_data(): array {
        return [
            'idrole' => $this->idrole,
            'nama_role' => $this->nama_role,
            'status' => $this->status
        ];
    }

    public function set_status(bool $newstatus): void {
        $this->status = $newstatus;
    }

    public function get_status(): bool {
        return $this->status;
    }

    public static function get_opsi_role(): array {
        $db = new DBconnection();

        $respon = $db->send_query(
            'SELECT idrole, nama_role FROM role ORDER BY idrole'
        );

        $db->close_connection();

        $opsi = [];

        foreach ($respon->data as $row) {
            $opsi[] = new Role(
                (int) $row['idrole'],
                $row['nama_role'],
                false
            );
        }

        return $opsi;
    }
}