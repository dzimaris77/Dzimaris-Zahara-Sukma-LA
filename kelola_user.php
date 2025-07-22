<?php
session_start();
if ($_SESSION['role'] != 'admin') {
    header("Location: ../../login.php");
    exit();
}

require_once '../../config/config.php';

// Query data users dengan no_telepon
$sql = "SELECT id_user, nama, email, no_telepon, role FROM users";
$result = mysqli_query($conn, $sql);
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

                <!-- Main Content -->
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Kelola Pengguna</h1>
                    
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-success">Daftar Pengguna Sistem</h6>
                            <a href="tambah_user.php" class="btn btn-success">
                                <i class="fas fa-plus-circle"></i> Tambah User
                            </a>
                        </div>
                        
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Nama</th>
                                            <th>Email</th>
                                            <th>No. Telepon</th> <!-- Kolom baru -->
                                            <th>Role</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(mysqli_num_rows($result) > 0): ?>
                                            <?php while($row = mysqli_fetch_assoc($result)): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($row['nama']) ?></td>
                                                    <td><?= htmlspecialchars($row['email']) ?></td>
                                                    <td>
                                                        <?= $row['no_telepon'] ? htmlspecialchars($row['no_telepon']) : 'Belum ditambahkan' ?>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                        $badge_class = 'badge-primary';
                                                        if($row['role'] == 'tenaga_kesehatan') $badge_class = 'badge-success';
                                                        if($row['role'] == 'teknisi') $badge_class = 'badge-warning';
                                                        if($row['role'] == 'admin_teknisi') $badge_class = 'badge-info';
                                                        ?>
                                                        <span class="badge <?= $badge_class ?> badge-role">
                                                            <?= ucfirst(str_replace('_', ' ', $row['role'])) ?>
                                                        </span>
                                                    </td>
                                                    <td class="action-buttons">
                                                        <a href="edit_user.php?id=<?= $row['id_user'] ?>" class="btn btn-sm btn-warning">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <button class="btn btn-sm btn-danger delete-btn" data-id="<?= $row['id_user'] ?>">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="text-center">Tidak ada data pengguna</td> <!-- Diubah colspan menjadi 5 -->
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
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
