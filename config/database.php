<?php

class Database
{
    private string $host = 'localhost';
    private string $username = 'root';
    private string $password = '';
    private string $database = 'db_manajemen_irzimart';

    private ?mysqli $connection = null;

    public function connect(): mysqli
    {
        if ($this->connection instanceof mysqli) {
            return $this->connection;
        }

        try {
            mysqli_report(
                MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT
            );

            $this->connection = new mysqli(
                $this->host,
                $this->username,
                $this->password,
                $this->database
            );

            $this->connection->set_charset('utf8mb4');

            return $this->connection;

        } catch (mysqli_sql_exception $e) {
            error_log(
                'Database connection error: ' . $e->getMessage()
            );

            throw new RuntimeException(
                'Koneksi database gagal. Periksa konfigurasi database.'
            );
        }
    }

    public function close(): void
    {
        if ($this->connection instanceof mysqli) {
            $this->connection->close();
            $this->connection = null;
        }
    }
}
