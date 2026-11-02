<?php

class User
{
    private mysqli $db;

    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }

    /**
     * Mencari pengguna berdasarkan username.
     */
    public function findByUsername(string $username): ?array
    {
        $sql = "
            SELECT
                u.iduser,
                u.username,
                u.password,
                u.idrole,
                r.nama_role
            FROM user AS u
            INNER JOIN role AS r
                ON u.idrole = r.idrole
            WHERE u.username = ?
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $username);
        $stmt->execute();

        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        $stmt->close();

        return $user ?: null;
    }

    /**
     * Mendaftarkan pengguna baru.
     * Role ditentukan dari database, bukan input form.
     */
    public function create(
        string $username,
        string $password
    ): bool {
        // Pastikan username belum digunakan.
        if ($this->findByUsername($username) !== null) {
            return false;
        }

        // Cari ID role Auditor.
        $roleName = 'Auditor';

        $stmt = $this->db->prepare(
            'SELECT idrole FROM role WHERE nama_role = ? LIMIT 1'
        );

        $stmt->bind_param('s', $roleName);
        $stmt->execute();

        $result = $stmt->get_result();
        $role = $result->fetch_assoc();

        $stmt->close();

        if (!$role) {
            throw new RuntimeException(
                'Role Auditor belum tersedia di database.'
            );
        }

        // Simpan password dalam bentuk hash.
        $hashedPassword = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        $idrole = (int) $role['idrole'];

        $stmt = $this->db->prepare(
            'INSERT INTO user (username, password, idrole)
             VALUES (?, ?, ?)'
        );

        $stmt->bind_param(
            'ssi',
            $username,
            $hashedPassword,
            $idrole
        );

        $stmt->execute();
        $created = $stmt->affected_rows === 1;

        $stmt->close();

        return $created;
    }
}
