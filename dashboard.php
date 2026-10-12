<?php

include_once("bootstrap.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$user = $_SESSION['user'];

Log::catat("AKSES", [
    "email" => $user['email'],
    "method" => $_SERVER['REQUEST_METHOD'],
    "url" => $_SERVER['REQUEST_URI']
]);

try {

    // ID user yang sedang login
    $userId = (int) $user['iduser'];

    // =========================
    // PROSES CREATE
    // =========================

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        if (isset($_POST['aksi']) && $_POST['aksi'] === 'buat') {

            $title = trim($_POST['title']);
            $body = trim($_POST['body']);

            Notification::create(
                $userId,
                $title,
                $body
            );

            header("Location: dashboard.php");
            exit();
        }


        // =========================
        // PROSES UPDATE
        // =========================

        if (isset($_POST['aksi']) && $_POST['aksi'] === 'ubah') {

            $id = (int) $_POST['id'];

            $notification = Notification::find($id);

            if (
                $notification !== null &&
                $notification->getUserId() === $userId
            ) {

                $notification->update(
                    trim($_POST['title']),
                    trim($_POST['body'])
                );
            }

            header("Location: dashboard.php");
            exit();
        }


        // =========================
        // PROSES MARK AS READ
        // =========================

        if (isset($_POST['aksi']) && $_POST['aksi'] === 'baca') {

            $id = (int) $_POST['id'];

            $notification = Notification::find($id);

            if (
                $notification !== null &&
                $notification->getUserId() === $userId
            ) {

                $notification->markAsRead();
            }

            header("Location: dashboard.php");
            exit();
        }


        // =========================
        // PROSES DELETE
        // =========================

        if (isset($_POST['aksi']) && $_POST['aksi'] === 'hapus') {

            $id = (int) $_POST['id'];

            $notification = Notification::find($id);

            if (
                $notification !== null &&
                $notification->getUserId() === $userId
            ) {

                $notification->delete();
            }

            header("Location: dashboard.php");
            exit();
        }
    }


    // =========================
    // AMBIL NOTIFICATION USER
    // =========================

    $notifications = Notification::findByUser($userId);

    $unreadNotifications = Notification::unread($notifications);

    $jumlahUnread = Notification::countUnread($notifications);

} catch (DatabaseException $e) {

    $error = $e->getMessage();

    $notifications = [];
    $unreadNotifications = [];
    $jumlahUnread = 0;
}

?>

<!DOCTYPE html>
<html>

<head>
    <title>Dashboard</title>
</head>

<body>

<h1>Selamat datang, <?= htmlspecialchars($user['nama']) ?></h1>

<p>
    Role aktif :
    <strong><?= htmlspecialchars($user['role']) ?></strong>
</p>

<p>
    <?= Konfigurasi::APP_NAME ?>
    versi
    <?= Konfigurasi::VERSI ?>
</p>

<a href="logout.php">Logout</a>

<hr>

<h2>Notification</h2>

<?php if (isset($error)): ?>

    <p style="color:red;">
        <?= htmlspecialchars($error) ?>
    </p>

<?php endif; ?>


<p>
    Jumlah notification belum dibaca:
    <strong><?= $jumlahUnread ?></strong>
</p>


<!-- =========================
     FORM CREATE
     ========================= -->

<h3>Buat Notification</h3>

<form method="POST">

    <input type="hidden" name="aksi" value="buat">

    <p>
        <label>Judul:</label><br>

        <input
            type="text"
            name="title"
            required
        >
    </p>

    <p>
        <label>Isi:</label><br>

        <textarea
            name="body"
            required
        ></textarea>
    </p>

    <button type="submit">
        Buat Notification
    </button>

</form>


<hr>


<!-- =========================
     DAFTAR NOTIFICATION
     ========================= -->

<h3>Daftar Notification</h3>

<?php if (empty($notifications)): ?>

    <p>Belum ada notification.</p>

<?php else: ?>

    <?php foreach ($notifications as $notification): ?>

        <div style="border:1px solid #ccc; padding:10px; margin-bottom:10px;">

            <strong>
                <?= htmlspecialchars($notification->getTitle()) ?>
            </strong>

            <p>
                <?= htmlspecialchars($notification->getBody()) ?>
            </p>

            <p>
                Status:
                <strong>
                    <?= $notification->isRead() ? 'READ' : 'UNREAD' ?>
                </strong>
            </p>

            <p>
                Dibuat:
                <?= htmlspecialchars($notification->getCreatedAt()) ?>
            </p>


            <!-- =========================
                 MARK AS READ
                 ========================= -->

            <?php if (!$notification->isRead()): ?>

                <form method="POST" style="display:inline;">

                    <input
                        type="hidden"
                        name="aksi"
                        value="baca"
                    >

                    <input
                        type="hidden"
                        name="id"
                        value="<?= $notification->getId() ?>"
                    >

                    <button type="submit">
                        Tandai Dibaca
                    </button>

                </form>

            <?php endif; ?>


            <!-- =========================
                 DELETE
                 ========================= -->

            <form method="POST" style="display:inline;">

                <input
                    type="hidden"
                    name="aksi"
                    value="hapus"
                >

                <input
                    type="hidden"
                    name="id"
                    value="<?= $notification->getId() ?>"
                >

                <button type="submit">
                    Hapus
                </button>

            </form>


            <!-- =========================
                 UPDATE
                 ========================= -->

            <details style="margin-top:10px;">

                <summary>
                    Ubah Notification
                </summary>

                <form method="POST">

                    <input
                        type="hidden"
                        name="aksi"
                        value="ubah"
                    >

                    <input
                        type="hidden"
                        name="id"
                        value="<?= $notification->getId() ?>"
                    >

                    <p>
                        <label>Judul:</label><br>

                        <input
                            type="text"
                            name="title"
                            value="<?= htmlspecialchars($notification->getTitle()) ?>"
                            required
                        >
                    </p>

                    <p>
                        <label>Isi:</label><br>

                        <textarea
                            name="body"
                            required
                        ><?= htmlspecialchars($notification->getBody()) ?></textarea>
                    </p>

                    <button type="submit">
                        Simpan Perubahan
                    </button>

                </form>

            </details>

        </div>

    <?php endforeach; ?>

<?php endif; ?>

</body>
</html>