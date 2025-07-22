<?php
session_start();
require '../../config/config.php';
require '../../config/telegram.php';

if ($_SESSION['role'] != 'admin_teknisi') {
    header("Location: ../../login.php");
    exit();
}

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Proses penugasan
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $laporan_id = $conn->real_escape_string($_POST['laporan_id']);
        $teknisi_id = $conn->real_escape_string($_POST['teknisi']);
        
        // Update status sesuai enum yang valid
        $update_query = "UPDATE laporan 
                        SET id_teknisi = '$teknisi_id', 
                            status = 'dalam_perbaikan', 
                            teknisi_status = 'Laporan Diterima' 
                        WHERE id_laporan = '$laporan_id'";
        
        if ($conn->query($update_query)) {
            // Dapatkan info teknisi
            $teknisi = $conn->query("SELECT * FROM users WHERE id_user='$teknisi_id'")->fetch_assoc();
            
            // Notifikasi
            send_telegram("🔧 TUGAS BARU #$laporan_id\nTeknisi: ".$teknisi['nama']."\nStatus: Laporan Diterima");
            
            $_SESSION['success'] = "Penugasan berhasil dikirim ke ".$teknisi['nama']."!";
            header("Location: assign.php");
            exit();
        } else {
            throw new Exception("Error database: " . $conn->error);
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: assign.php");
        exit();
    }
}

// Ambil data laporan yang siap ditugaskan
$laporan = $conn->query("SELECT * FROM laporan WHERE status='menunggu_penugasan'");
$teknisi = $conn->query("SELECT * FROM users WHERE role='teknisi'");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Penugasan Teknisi - SIMONFAST</title>
    
    <!-- Custom fonts -->
    <link href="../../vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    
    <!-- Custom styles -->
    <link href="../../css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../../vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">

    <style>
    .assign-form {
        display: contents; /* Mempertahankan struktur tabel */
    }
    .assign-card:hover {
        background-color: #f8f9fc;
        transition: background-color 0.3s;
    }
    .technician-select {
        border: 2px solid #1cc88a;
        border-radius: 8px;
        min-width: 250px;
    }
    </style>
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include '../../includes/sidebar.php'; ?>

        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include '../../includes/topbar.php'; ?>

                <!-- Notifikasi -->
                <?php if(isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show m-3">
                    <?= $_SESSION['success'] ?>
                    <button type="button" class="close" data-dismiss="alert">
                        <span>&times;</span>
                    </button>
                </div>
                <?php unset($_SESSION['success']); endif; ?>

                <?php if(isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show m-3">
                    <?= $_SESSION['error'] ?>
                    <button type="button" class="close" data-dismiss="alert">
                        <span>&times;</span>
                    </button>
                </div>
                <?php unset($_SESSION['error']); endif; ?>

                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Penugasan Teknisi</h1>
                    
                    <div class="card shadow-lg mb-4">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center bg-white">
                            <h6 class="m-0 font-weight-bold text-success">
                                <i class="fas fa-tasks"></i> Daftar Laporan Siap Ditugaskan
                            </h6>
                            <div class="badge badge-success">Total: <?= $laporan->num_rows ?></div>
                        </div>
                        
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover" id="dataTable" width="100%" cellspacing="0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>ID Laporan</th>
                                            <th>Tanggal</th>
                                            <th>Deskripsi</th>
                                            <th>Teknisi</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($row = $laporan->fetch_assoc()): ?>
                                        <tr class="assign-card">
                                            <form method="POST" class="assign-form">
                                                <td class="font-weight-bold align-middle">#<?= $row['id_laporan'] ?></td>
                                                <td class="align-middle">
                                                    <?= date('d/m/Y H:i', strtotime($row['tanggal_laporan'])) ?>
                                                </td>
                                                <td class="align-middle"><?= substr($row['deskripsi'], 0, 50) ?>...</td>
                                                <td class="align-middle">
                                                    <select name="teknisi" class="form-control technician-select" required>
                                                        <?php 
                                                        // Reset pointer hasil query teknisi
                                                        $teknisi->data_seek(0);
                                                        while($t = $teknisi->fetch_assoc()): ?>
                                                        <option value="<?= $t['id_user'] ?>">
                                                            <?= $t['nama'] ?> 
                                                            <small class="text-muted">(<?= $t['no_telepon'] ?>)</small>
                                                        </option>
                                                        <?php endwhile; ?>
                                                    </select>
                                                </td>
                                                <td class="align-middle">
                                                    <input type="hidden" name="laporan_id" value="<?= $row['id_laporan'] ?>">
                                                    <button type="submit" 
                                                            class="btn btn-success btn-sm"
                                                            data-toggle="tooltip"
                                                            title="Assign teknisi ini">
                                                        <i class="fas fa-user-check"></i> Assign
                                                    </button>
                                                </td>
                                            </form>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="../../vendor/jquery/jquery.min.js"></script>
    <script src="../../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="../../vendor/datatables/dataTables.bootstrap4.min.js"></script>
    <script src="../../js/sb-admin-2.min.js"></script>

    <script>
    $(document).ready(function() {
        // Inisialisasi DataTables
        $('#dataTable').DataTable({
            "columnDefs": [
                { "orderable": false, "targets": [3,4] }
            ],
            "language": {
                "search": "Cari laporan:",
                "zeroRecords": "Tidak ada laporan yang perlu ditugaskan",
                "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ laporan",
                "paginate": {
                    "previous": "‹",
                    "next": "›"
                }
            }
        });

        // Konfirmasi sebelum assign
        $('.assign-form').submit(function(e) {
            const teknisi = $(this).find('select option:selected').text();
            if(!confirm(`Yakin ingin menugaskan ${teknisi} untuk laporan ini?`)) {
                e.preventDefault();
            }
        });
    });
    </script>
</body>
</html>
