<?php

class DBconnection {

    private string $host = "localhost";
    private string $port = "5432";
    private string $dbname = "kuliah_wf_2025";
    private string $username = "postgres";
    private string $password = "1";
    private $dbconn = null;

    public function __construct() {
        $this->init_connect();
    }

    public function init_connect(): void {

        $conn_string = "host=" . $this->host
            . " port=" . $this->port
            . " dbname=" . $this->dbname
            . " user=" . $this->username
            . " password=" . $this->password;

        $this->dbconn = @pg_connect($conn_string);

        if (!$this->dbconn) {
            throw new DatabaseException(
                "Koneksi ke database {$this->dbname} tidak dapat dibentuk."
            );
        }
    }

    public function send_query(
        string $query,
        array $params = []
    ): Respon {

        if (!$this->dbconn) {
            return new Respon(
                false,
                "Koneksi belum terbentuk."
            );
        }

        $result = @pg_query_params(
            $this->dbconn,
            $query,
            $params
        );

        if ($result === false) {
            return new Respon(
                false,
                pg_last_error($this->dbconn)
            );
        }

        $data = (pg_num_rows($result) > 0)
            ? pg_fetch_all($result)
            : [];

        return new Respon(
            true,
            "Query executed successfully",
            $data
        );
    }

    public function mulai_transaksi(): void {
        if ($this->dbconn) {
            pg_query(
                $this->dbconn,
                "BEGIN"
            );
        }
    }

    public function commit(): void {
        if ($this->dbconn) {
            pg_query(
                $this->dbconn,
                "COMMIT"
            );
        }
    }

    public function rollback(): void {
        if ($this->dbconn) {
            pg_query(
                $this->dbconn,
                "ROLLBACK"
            );
        }
    }

    public function close_connection(): void {
        if ($this->dbconn) {
            pg_close($this->dbconn);
            $this->dbconn = null;
        }
    }

    public function __destruct() {
        $this->close_connection();
    }
}