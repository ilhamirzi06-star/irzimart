<?php

class Notification {

    private int $id;
    private int $userId;
    private string $title;
    private string $body;
    private bool $isRead;
    private string $createdAt;

    public function __construct(
        int $id,
        int $userId,
        string $title,
        string $body,
        bool $isRead,
        string $createdAt
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->title = $title;
        $this->body = $body;
        $this->isRead = $isRead;
        $this->createdAt = $createdAt;
    }

    // GETTER

    public function getId(): int {
        return $this->id;
    }

    public function getUserId(): int {
        return $this->userId;
    }

    public function getTitle(): string {
        return $this->title;
    }

    public function getBody(): string {
        return $this->body;
    }

    public function isRead(): bool {
        return $this->isRead;
    }

    public function getCreatedAt(): string {
        return $this->createdAt;
    }


    // CREATE

    public static function create(
        int $userId,
        string $title,
        string $body
    ): self {

        $db = new DBconnection();

        $respon = $db->send_query(
            'INSERT INTO notifications
             (user_id, title, body)
             VALUES ($1, $2, $3)
             RETURNING id, user_id, title, body, is_read, created_at',
            [$userId, $title, $body]
        );

        $db->close_connection();

        if (!$respon->status || empty($respon->data)) {
            throw new DatabaseException(
                $respon->message
            );
        }

        $row = $respon->data[0];

        return new self(
            (int) $row['id'],
            (int) $row['user_id'],
            $row['title'],
            $row['body'],
            $row['is_read'] === 't' || $row['is_read'] === true,
            $row['created_at']
        );
    }

    // READ - berdasarkan ID

    public static function find(int $id): ?self {

        $db = new DBconnection();

        $respon = $db->send_query(
            'SELECT id, user_id, title, body, is_read, created_at
             FROM notifications
             WHERE id = $1',
            [$id]
        );

        $db->close_connection();

        if (!$respon->status) {
            throw new DatabaseException(
                $respon->message
            );
        }

        if (empty($respon->data)) {
            return null;
        }

        $row = $respon->data[0];

        return new self(
            (int) $row['id'],
            (int) $row['user_id'],
            $row['title'],
            $row['body'],
            $row['is_read'] === 't' || $row['is_read'] === true,
            $row['created_at']
        );
    }


    // READ - berdasarkan user

    public static function findByUser(
        int $userId
    ): array {

        $db = new DBconnection();

        $respon = $db->send_query(
            'SELECT id, user_id, title, body, is_read, created_at
             FROM notifications
             WHERE user_id = $1
             ORDER BY created_at DESC',
            [$userId]
        );

        $db->close_connection();

        if (!$respon->status) {
            throw new DatabaseException(
                $respon->message
            );
        }

        $notifications = [];

        foreach ($respon->data as $row) {

            $notifications[] = new self(
                (int) $row['id'],
                (int) $row['user_id'],
                $row['title'],
                $row['body'],
                $row['is_read'] === 't' || $row['is_read'] === true,
                $row['created_at']
            );
        }

        return $notifications;
    }


    // UPDATE

    public function update(
        string $title,
        string $body
    ): void {

        $db = new DBconnection();

        $respon = $db->send_query(
            'UPDATE notifications
             SET title = $1, body = $2
             WHERE id = $3',
            [$title, $body, $this->id]
        );

        $db->close_connection();

        if (!$respon->status) {
            throw new DatabaseException(
                $respon->message
            );
        }

        $this->title = $title;
        $this->body = $body;
    }


    // UPDATE - tandai sudah dibaca

    public function markAsRead(): void {

        $db = new DBconnection();

        $respon = $db->send_query(
            'UPDATE notifications
             SET is_read = TRUE
             WHERE id = $1',
            [$this->id]
        );

        $db->close_connection();

        if (!$respon->status) {
            throw new DatabaseException(
                $respon->message
            );
        }

        $this->isRead = true;
    }


    // DELETE

    public function delete(): void {

        $db = new DBconnection();

        $respon = $db->send_query(
            'DELETE FROM notifications
             WHERE id = $1',
            [$this->id]
        );

        $db->close_connection();

        if (!$respon->status) {
            throw new DatabaseException(
                $respon->message
            );
        }
    }


    // METHOD TANTANGAN
    // Mengambil notification yang belum dibaca

    public static function unread(array $list): array {

        return array_filter(
            $list,
            function ($notification) {
                return !$notification->isRead();
            }
        );
    }

    public static function countUnread(array $list): int {

        return count(
            self::unread($list)
        );
    }

    public function __toString(): string {

        return "[" . $this->title . "] - "
            . ($this->isRead ? "READ" : "UNREAD");
    }
}