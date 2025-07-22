<?php
session_start();
require_once '../../config/config.php';

// Check if user is logged in and is a technician
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'teknisi') {
    header("Location: ../../login.php");
    exit();
}

// Get report ID from URL parameter
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id_laporan = $_GET['id'];
$teknisi_id = $_SESSION['user_id'];

// Get detailed report information - only for reports assigned to this technician
$sql_detail = "SELECT l.*, 
               a.nama_alat, a.merk, a.type_model, a.no_seri, a.jenis_alat,
               r.nama_ruangan, r.lantai, r.sayap,
               u_pelapor.nama as pelapor_nama
               FROM laporan l
               JOIN alat a ON l.id_alat = a.id_alat
               JOIN ruangan r ON l.id_ruangan = r.id_ruangan
               JOIN users u_pelapor ON l.id_pelapor = u_pelapor.id_user
               WHERE l.id_laporan = ? AND l.id_teknisi = ?";

$stmt_detail = $conn->prepare($sql_detail);
$stmt_detail->bind_param("ii", $id_laporan, $teknisi_id);
$stmt_detail->execute();
$result_detail = $stmt_detail->get_result();

if ($result_detail->num_rows == 0) {
    header("Location: index.php");
    exit();
}

$laporan = $result_detail->fetch_assoc();

// Get report photos (bukti only - technician needs to see the problem)
$sql_photos = "SELECT * FROM laporan_foto WHERE id_laporan = ? AND jenis = 'bukti'";
$stmt_photos = $conn->prepare($sql_photos);
$stmt_photos->bind_param("i", $id_laporan);
$stmt_photos->execute();
$photos = $stmt_photos->get_result();

// Function to display technician status badges
function getTeknisiStatusBadge($teknisi_status) {
    switch ($teknisi_status) {
        case 'Laporan Diterima':
            return '<span class="badge badge-info">Laporan Diterima</span>';
        case 'Sedang Diperiksa':
            return '<span class="badge badge-warning">Sedang Diperiksa</span>';
        case 'Sedang Diperbaiki':
            return '<span class="badge badge-primary">Sedang Diperbaiki</span>';
        case 'Selesai':
            return '<span class="badge badge-success">Selesai</span>';
        default:
            return '<span class="badge badge-secondary">Belum Dimulai</span>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Detail Perbaikan | Dashboard Teknisi</title>
    
    <!-- CSS -->
    <link href="../../vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="../../css/sb-admin-2.min.css" rel="stylesheet">
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include '../../includes/sidebar.php'; ?>

        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include '../../includes/topbar.php'; ?>

                <!-- Main Content -->
                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">
                            <i class="fas fa-tools"></i> Perbaikan #<?php echo $laporan['id_laporan']; ?>
                        </h1>
                        <a href="index.php" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
                            <i class="fas fa-arrow-left fa-sm text-white-50"></i> Kembali ke Dashboard
                        </a>
                    </div>

                    <div class="row">
                        <!-- Status Perbaikan -->
                        <div class="col-lg-8">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3 bg-primary">
                                    <h6 class="m-0 font-weight-bold text-white">
                                        <i class="fas fa-clipboard-check"></i> Status Perbaikan
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h5>Status Saat Ini:</h5>
                                            <h4><?php echo getTeknisiStatusBadge($laporan['teknisi_status']); ?></h4>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong>Tanggal Penugasan:</strong><br>
                                            <?php echo date('d/m/Y H:i', strtotime($laporan['tanggal_laporan'])); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Informasi Alat yang Diperbaiki -->
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">
                                        <i class="fas fa-wrench"></i> Alat yang Diperbaiki
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <table class="table table-borderless">
                                                <tr>
                                                    <td><strong>Nama Alat:</strong></td>
                                                    <td><?php echo $laporan['nama_alat']; ?></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Merk:</strong></td>
                                                    <td><?php echo $laporan['merk'] ?: '-'; ?></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Type/Model:</strong></td>
                                                    <td><?php echo $laporan['type_model'] ?: '-'; ?></td>
                                                </tr>
                                            </table>
                                        </div>
                                        <div class="col-md-6">
                                            <table class="table table-borderless">
                                                <tr>
                                                    <td><strong>No. Seri:</strong></td>
                                                    <td><?php echo $laporan['no_seri'] ?: '-'; ?></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Jenis Alat:</strong></td>
                                                    <td>
                                                        <span class="badge badge-<?php echo $laporan['jenis_alat'] == 'medis' ? 'danger' : 'secondary'; ?>">
                                                            <?php echo ucfirst($laporan['jenis_alat']); ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Lokasi:</strong></td>
                                                    <td>
                                                        <i class="fas fa-map-marker-alt text-danger"></i>
                                                        <?php 
                                                        $lokasi = $laporan['nama_ruangan'] . ' - ' . $laporan['lantai'];
                                                        if (!empty($laporan['sayap'])) {
                                                            $lokasi .= ' ' . $laporan['sayap'];
                                                        }
                                                        echo $lokasi;
                                                        ?>
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Deskripsi Masalah -->
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-warning">
                                        <i class="fas fa-exclamation-triangle"></i> Deskripsi Masalah
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-warning">
                                        <strong>Dilaporkan oleh:</strong> <?php echo $laporan['pelapor_nama']; ?>
                                    </div>
                                    <p class="lead"><?php echo nl2br(htmlspecialchars($laporan['deskripsi'])); ?></p>
                                </div>
                            </div>

                            <!-- Catatan Teknisi -->
                            <?php if ($laporan['catatan_teknisi']): ?>
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-info">
                                        <i class="fas fa-sticky-note"></i> Catatan Perbaikan
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <p><?php echo nl2br(htmlspecialchars($laporan['catatan_teknisi'])); ?></p>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Laporan Perbaikan -->
                            <?php if ($laporan['laporan_perbaikan']): ?>
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-success">
                                        <i class="fas fa-file-alt"></i> Laporan Hasil Perbaikan
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-success">
                                        <i class="fas fa-check-circle"></i> <strong>Perbaikan Selesai</strong>
                                    </div>
                                    <p><?php echo nl2br(htmlspecialchars($laporan['laporan_perbaikan'])); ?></p>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Sidebar Aksi -->
                        <div class="col-lg-4">
                            <!-- Aksi Perbaikan -->
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">
                                        <i class="fas fa-cogs"></i> Aksi Perbaikan
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <?php if ($laporan['status'] == 'menunggu_penugasan'): ?>
                                        <a href="mulai_perbaikan.php?id=<?php echo $laporan['id_laporan']; ?>" 
                                           class="btn btn-success btn-block btn-lg">
                                            <i class="fas fa-play"></i> Mulai Perbaikan
                                        </a>
                                        <small class="text-muted">Klik untuk menerima dan memulai perbaikan</small>
                                    
                                    <?php elseif ($laporan['status'] == 'dalam_perbaikan' && $laporan['teknisi_status'] != 'Selesai'): ?>
                                        <a href="process.php?id=<?php echo $laporan['id_laporan']; ?>" 
                                           class="btn btn-warning btn-block btn-lg">
                                            <i class="fas fa-tools"></i> Update Progress
                                        </a>
                                        <small class="text-muted">Update status perbaikan</small>
                                    
                                    <?php elseif ($laporan['status'] == 'dalam_perbaikan' && $laporan['teknisi_status'] == 'Selesai'): ?>
                                        <a href="selesai_perbaikan.php?id=<?php echo $laporan['id_laporan']; ?>" 
                                           class="btn btn-primary btn-block btn-lg">
                                            <i class="fas fa-check"></i> Selesaikan Perbaikan
                                        </a>
                                        <small class="text-muted">Finalisasi dan kirim laporan</small>
                                    
                                    <?php else: ?>
                                        <div class="alert alert-info text-center">
                                            <i class="fas fa-info-circle"></i><br>
                                            Perbaikan telah selesai
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Dokumentasi Masalah -->
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">
                                        <i class="fas fa-camera"></i> Foto Bukti Masalah
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <?php if ($photos->num_rows > 0): ?>
                                        <?php while ($photo = $photos->fetch_assoc()): ?>
                                            <div class="mb-3">
                                                <img src="../../uploads/bukti/<?php echo $photo['path_foto']; ?>" 
                                                     class="img-fluid rounded shadow-sm" 
                                                     alt="Bukti Masalah"
                                                     style="width: 100%; cursor: pointer;"
                                                     data-toggle="modal" 
                                                     data-target="#imageModal<?php echo $photo['id_laporan_foto']; ?>">
                                                
                                                <!-- Modal for image preview -->
                                                <div class="modal fade" id="imageModal<?php echo $photo['id_laporan_foto']; ?>" tabindex="-1">
                                                    <div class="modal-dialog modal-lg">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Foto Bukti Masalah</h5>
                                                                <button type="button" class="close" data-dismiss="modal">
                                                                    <span>&times;</span>
                                                                </button>
                                                            </div>
                                                            <div class="modal-body text-center">
                                                                <img src="../../uploads/bukti/<?php echo $photo['path_foto']; ?>" 
                                                                     class="img-fluid" 
                                                                     alt="Bukti Masalah">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <p class="text-muted text-center">
                                            <i class="fas fa-image fa-2x"></i><br>
                                            Tidak ada foto bukti
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Dokumentasi Perbaikan -->
                            <?php if ($laporan['dokumentasi_perbaikan']): ?>
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-success">
                                        <i class="fas fa-camera"></i> Dokumentasi Hasil Perbaikan
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <img src="../../uploads/perbaikan/<?php echo $laporan['dokumentasi_perbaikan']; ?>" 
                                         class="img-fluid rounded shadow-sm" 
                                         alt="Dokumentasi Perbaikan"
                                         style="width: 100%; cursor: pointer;"
                                         data-toggle="modal" 
                                         data-target="#repairImageModal">
                                    
                                    <!-- Modal for repair image preview -->
                                    <div class="modal fade" id="repairImageModal" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Dokumentasi Hasil Perbaikan</h5>
                                                    <button type="button" class="close" data-dismiss="modal">
                                                        <span>&times;</span>
                                                    </button>
                                                </div>
                                                <div class="modal-body text-center">
                                                    <img src="../../uploads/perbaikan/<?php echo $laporan['dokumentasi_perbaikan']; ?>" 
                                                         class="img-fluid" 
                                                         alt="Dokumentasi Perbaikan">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Progress Timeline -->
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">
                                        <i class="fas fa-history"></i> Timeline Perbaikan
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="timeline">
                                        <?php if ($laporan['teknisi_status'] == 'Laporan Diterima' || $laporan['teknisi_status'] == 'Sedang Diperiksa' || $laporan['teknisi_status'] == 'Sedang Diperbaiki' || $laporan['teknisi_status'] == 'Selesai'): ?>
                                        <div class="timeline-item">
                                            <i class="fas fa-check-circle text-success"></i>
                                            <span>Laporan Diterima</span>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($laporan['teknisi_status'] == 'Sedang Diperiksa' || $laporan['teknisi_status'] == 'Sedang Diperbaiki' || $laporan['teknisi_status'] == 'Selesai'): ?>
                                        <div class="timeline-item">
                                            <i class="fas fa-search text-warning"></i>
                                            <span>Sedang Diperiksa</span>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($laporan['teknisi_status'] == 'Sedang Diperbaiki' || $laporan['teknisi_status'] == 'Selesai'): ?>
                                        <div class="timeline-item">
                                            <i class="fas fa-tools text-primary"></i>
                                            <span>Sedang Diperbaiki</span>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($laporan['teknisi_status'] == 'Selesai'): ?>
                                        <div class="timeline-item">
                                            <i class="fas fa-flag-checkered text-success"></i>
                                            <span>Selesai</span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; Sistem Monitoring Fasilitas 2025</span>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- JS -->
    <script src="../../vendor/jquery/jquery.min.js"></script>
    <script src="../../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../../js/sb-admin-2.min.js"></script>

    <style>
    .timeline {
        position: relative;
        padding-left: 30px;
    }
    .timeline-item {
        position: relative;
        padding-bottom: 15px;
        border-left: 2px solid #e3e6f0;
        padding-left: 20px;
        margin-left: 10px;
    }
    .timeline-item:last-child {
        border-left: none;
    }
    .timeline-item i {
        position: absolute;
        left: -25px;
        background: white;
        padding: 5px;
        border-radius: 50%;
    }
    </style>
</body>
</html>
