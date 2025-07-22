<?php
session_start();
if ($_SESSION['role'] != 'admin') {
    header("Location: ../../login.php");
    exit();
}

require_once '../../config/config.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: kelola_user.php");
    exit();
}

$user_id = $_GET['id'];
$user = mysqli_query($conn, "SELECT * FROM users WHERE id_user = $user_id");
$data = mysqli_fetch_assoc($user);

if (!$data) {
    header("Location: kelola_user.php");
    exit();
}

// Proses update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = htmlspecialchars($_POST['nama']);
    $email = htmlspecialchars($_POST['email']);
    $no_telepon = htmlspecialchars($_POST['no_telepon']);
    $role = $_POST['role'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Validasi email unik
    if ($data['email'] != $email) {
        $check_email = mysqli_query($conn, "SELECT * FROM users WHERE email = '$email'");
        if (mysqli_num_rows($check_email) > 0) {
            $error = "Email sudah terdaftar!";
        }
    }

    // Validasi password
    if (!empty($new_password)) {
        if ($new_password !== $confirm_password) {
            $error = "Password baru tidak cocok!";
        }
    }

    if (!isset($error)) {
        // Update tanpa password
        if (empty($new_password)) {
            $stmt = mysqli_prepare($conn, 
                "UPDATE users SET 
                    nama = ?,
                    email = ?,
                    no_telepon = ?,
                    role = ?
                WHERE id_user = ?");
            mysqli_stmt_bind_param($stmt, 'ssssi', 
                $nama, 
                $email, 
                $no_telepon,
                $role, 
                $user_id);
        } 
        // Update dengan password
        else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($conn, 
                "UPDATE users SET 
                    nama = ?,
                    email = ?,
                    no_telepon = ?,
                    role = ?,
                    password = ?
                WHERE id_user = ?");
            mysqli_stmt_bind_param($stmt, 'sssssi', 
                $nama, 
                $email, 
                $no_telepon,
                $role,
                $hashed_password,
                $user_id);
        }

        if (mysqli_stmt_execute($stmt)) {
            header("Location: kelola_user.php?success=edit");
            exit();
        } else {
            $error = "Gagal update user: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Kelola User - SIMONFAST</title>

    <!-- Custom fonts -->
    <link href="../../vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">

    <!-- Custom styles -->
    <link href="../../css/sb-admin-2.min.css" rel="stylesheet">

    <style>
        .badge-role {
            padding: 0.5em 0.75em;
            border-radius: 20px;
        }
        .action-buttons .btn {
            margin: 2px;
            min-width: 80px;
        }
    </style>
</head>
<body id="page-top">
    <div id="wrapper">
        <?php include '../../includes/sidebar.php'; ?>

        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include '../../includes/topbar.php'; ?>

                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Edit User</h1>
                    
                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <?php if(isset($error)): ?>
                                <div class="alert alert-danger"><?= $error ?></div>
                            <?php endif; ?>

                            <form method="POST">
                                <div class="form-group">
                                    <label>Nama Lengkap</label>
                                    <input type="text" name="nama" class="form-control" 
                                           value="<?= htmlspecialchars($data['nama']) ?>" required>
                                </div>

                                <div class="form-group">
                                    <label>Email</label>
                                    <input type="email" name="email" class="form-control"
                                           value="<?= htmlspecialchars($data['email']) ?>" required>
                                </div>

                                <div class="form-group">
                                    <label>Nomor Telepon</label>
                                    <input type="tel" name="no_telepon" class="form-control"
                                           value="<?= htmlspecialchars($data['no_telepon']) ?>"
                                           pattern="[0-9]{10,15}">
                                    <small class="form-text text-muted">
                                        Contoh: 081234567890
                                    </small>
                                </div>

                                <div class="form-group">
                                    <label>Role</label>
                                    <select name="role" class="form-control" required>
                                        <option value="admin" <?= $data['role'] == 'admin' ? 'selected' : '' ?>>Admin</option>
                                        <option value="admin_teknisi" <?= $data['role'] == 'admin_teknisi' ? 'selected' : '' ?>>Admin Teknisi</option>
                                        <option value="tenaga_kesehatan" <?= $data['role'] == 'tenaga_kesehatan' ? 'selected' : '' ?>>Tenaga Kesehatan</option>
                                        <option value="teknisi" <?= $data['role'] == 'teknisi' ? 'selected' : '' ?>>Teknisi</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Password Baru (Kosongkan jika tidak ingin mengubah)</label>
                                    <input type="password" name="new_password" class="form-control">
                                </div>

                                <div class="form-group">
                                    <label>Konfirmasi Password Baru</label>
                                    <input type="password" name="confirm_password" class="form-control">
                                </div>

                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-save"></i> Simpan Perubahan
                                </button>
                                <a href="kelola_user.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Kembali
                                </a>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- Bootstrap core JavaScript-->
    <script src="../../vendor/jquery/jquery.min.js"></script>
    <script src="../../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../../js/sb-admin-2.min.js"></script>

    <script>
    $(document).ready(function() {
        // Konfirmasi hapus user
        $('.delete-btn').click(function() {
            const userId = $(this).data('id');
            if(confirm('Apakah Anda yakin ingin menghapus user ini?')) {
                window.location.href = `hapus_user.php?id=${userId}`;
            }
        });
    });
    </script>
</body>
</html>
