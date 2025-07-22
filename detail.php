<?php
session_start();
require '../../config/config.php';

if ($_SESSION['role'] != 'tenaga_kesehatan') {
    header("Location: ../../login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: list.php");
    exit();
}

$id = $conn->real_escape_string($_GET['id']);

// Query dengan informasi jenis alat dan detail perbaikan teknisi
$laporan = $conn->query("
    SELECT l.*, a.nama_alat, a.merk, a.jenis_alat, r.nama_ruangan, u.nama as teknisi,
           l.catatan_admin, l.catatan_teknisi, l.catatan_verifikasi
    FROM laporan l
    JOIN alat a ON l.id_alat = a.id_alat
    JOIN ruangan r ON l.id_ruangan = r.id_ruangan
    LEFT JOIN users u ON l.id_teknisi = u.id_user
    WHERE l.id_laporan = $id AND l.id_pelapor = ".$_SESSION['user_id']
)->fetch_assoc();

if (!$laporan) {
    header("Location: list.php");
    exit();
}

$fotos = $conn->query("SELECT * FROM laporan_foto WHERE id_laporan = $id");
$history = $conn->query("SELECT * FROM status_history WHERE id_laporan = $id ORDER BY timestamp DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Detail Laporan - SIMONFAST</title>
    
    <!-- Custom fonts -->
    <link href="../../vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    
    <!-- Custom styles -->
    <link href="../../css/sb-admin-2.min.css" rel="stylesheet">
    
    <style>
    .timeline {
        border-left: 3px solid #1cc88a;
        position: relative;
        padding-left: 2rem;
        margin-left: 1rem;
    }
    .timeline-item {
        position: relative;
        margin-bottom: 2rem;
    }
    .timeline-marker {
        position: absolute;
        left: -1.3rem;
        top: 0;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: #1cc88a;
        border: 3px solid white;
    }
    .timeline-content {
        padding: 1rem;
        background: #f8f9fc;
        border-radius: 0.35rem;
        box-shadow: 0 0.15rem 0.35rem rgba(0,0,0,.03);
    }
    .img-thumbnail {
        border: 2px solid #d1d3e2;
        transition: transform 0.3s;
    }
    .img-thumbnail:hover {
        transform: scale(1.05);
    }
    .badge-medis {
        background-color: #1cc88a;
        color: white;
    }
    .badge-non-medis {
        background-color: #36b9cc;
        color: white;
    }
    .catatan-box {
        background: #f8f9fc;
        border-left: 4px solid #5a5c69;
        padding: 15px;
        margin: 10px 0;
        border-radius: 8px;
        box-shadow: 0 0.15rem 0.35rem rgba(0,0,0,.03);
    }
    .catatan-admin {
        border-left-color: #e74a3b;
        background: #fdf2f2;
    }
    .catatan-teknisi {
        border-left-color: #36b9cc;
        background: #f0f9ff;
    }
    .catatan-verifikasi {
        border-left-color: #1cc88a;
        background: #f0fff4;
    }
    .detail-perbaikan {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 15px;
        padding: 20px;
        margin: 20px 0;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }
    .status-progress {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin: 20px 0;
        position: relative;
    }
    .status-step {
        background: #e9ecef;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        z-index: 2;
    }
    .status-step.active {
        background: #1cc88a;
        color: white;
    }
    .status-step.completed {
        background: #28a745;
        color: white;
    }
    .status-line {
        position: absolute;
        top: 50%;
        left: 0;
        right: 0;
        height: 2px;
        background: #e9ecef;
        z-index: 1;
    }
    .status-line.completed {
        background: #28a745;
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
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Detail Laporan <span class="text-success">#<?= $id ?></span></h1>
                        <a href="list.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left mr-2"></i>Kembali ke Daftar
                        </a>
                    </div>
                    
                    <!-- Status Progress -->
                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <h6 class="font-weight-bold text-primary mb-3">
                                <i class="fas fa-tasks mr-2"></i>Progress Perbaikan
                            </h6>
                            <div class="status-progress">
                                <div class="status-line <?= in_array($laporan['status'], ['menunggu_penugasan', 'dalam_perbaikan', 'selesai', 'selesai_total']) ? 'completed' : '' ?>"></div>
                                
                                <div class="status-step completed" title="Laporan Dibuat">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                                <div class="status-step <?= in_array($laporan['status'], ['menunggu_penugasan', 'dalam_perbaikan', 'selesai', 'selesai_total']) ? 'completed' : ($laporan['status'] == 'menunggu_verifikasi' ? 'active' : '') ?>" title="Verifikasi Admin">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div class="status-step <?= in_array($laporan['status'], ['dalam_perbaikan', 'selesai', 'selesai_total']) ? 'completed' : ($laporan['status'] == 'menunggu_penugasan' ? 'active' : '') ?>" title="Penugasan Teknisi">
                                    <i class="fas fa-user-cog"></i>
                                </div>
                                <div class="status-step <?= in_array($laporan['status'], ['selesai', 'selesai_total']) ? 'completed' : ($laporan['status'] == 'dalam_perbaikan' ? 'active' : '') ?>" title="Perbaikan">
                                    <i class="fas fa-tools"></i>
                                </div>
                                <div class="status-step <?= $laporan['status'] == 'selesai_total' ? 'completed' : ($laporan['status'] == 'selesai' ? 'active' : '') ?>" title="Selesai">
                                    <i class="fas fa-flag-checkered"></i>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between text-sm text-muted mt-2">
                                <span>Laporan</span>
                                <span>Verifikasi</span>
                                <span>Penugasan</span>
                                <span>Perbaikan</span>
                                <span>Selesai</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card shadow-lg mb-4 border-left-success">
                        <div class="card-header py-3 bg-white">
                            <h6 class="m-0 font-weight-bold text-success">
                                <i class="fas fa-info-circle"></i> Informasi Utama
                            </h6>
                        </div>
                        
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-bordered table-hover">
                                        <tbody>
                                            <tr>
                                                <th class="bg-light" width="40%">Tanggal Laporan</th>
                                                <td><?= date('d/m/Y H:i', strtotime($laporan['tanggal_laporan'])) ?></td>
                                            </tr>
                                            <tr>
                                                <th class="bg-light">Jenis Alat</th>
                                                <td>
                                                    <span class="badge badge-<?= $laporan['jenis_alat'] == 'medis' ? 'medis' : 'non-medis' ?>">
                                                        <i class="fas fa-<?= $laporan['jenis_alat'] == 'medis' ? 'stethoscope' : 'chair' ?> mr-1"></i>
                                                        <?= $laporan['jenis_alat'] == 'medis' ? 'Alat Medis' : 'Alat Non-Medis' ?>
                                                    </span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th class="bg-light">Nama Alat</th>
                                                <td>
                                                    <strong><?= htmlspecialchars($laporan['nama_alat']) ?></strong>
                                                    <?php if ($laporan['merk']): ?>
                                                        <br><small class="text-muted">Merk: <?= htmlspecialchars($laporan['merk']) ?></small>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th class="bg-light">Ruangan</th>
                                                <td><?= htmlspecialchars($laporan['nama_ruangan']) ?></td>
                                            </tr>
                                            <tr>
                                                <th class="bg-light">Teknisi</th>
                                                <td>
                                                    <?php if ($laporan['teknisi']): ?>
                                                        <i class="fas fa-user-cog text-primary mr-2"></i>
                                                        <?= htmlspecialchars($laporan['teknisi']) ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">
                                                            <i class="fas fa-hourglass-half mr-2"></i>Belum ditugaskan
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="alert alert-<?= $laporan['status'] == 'selesai_total' ? 'success' : ($laporan['status'] == 'ditolak_admin' ? 'danger' : 'info') ?> border-left-<?= $laporan['status'] == 'selesai_total' ? 'success' : ($laporan['status'] == 'ditolak_admin' ? 'danger' : 'info') ?>">
                                        <h5 class="alert-heading">
                                            <i class="fas fa-<?= $laporan['status'] == 'selesai_total' ? 'check-circle' : ($laporan['status'] == 'ditolak_admin' ? 'times-circle' : 'info-circle') ?> mr-2"></i>
                                            Status Terkini
                                        </h5>
                                        <hr>
                                        <p class="mb-0 font-weight-bold">
                                            <?php
                                            if ($laporan['konfirmasi_status']) {
                                                echo $laporan['konfirmasi_status'];
                                            } elseif ($laporan['teknisi_status']) {
                                                echo $laporan['teknisi_status'];
                                            } else {
                                                echo ucfirst(str_replace('_', ' ', $laporan['status']));
                                            }
                                            ?>
                                        </p>
                                    </div>
                                    
                                    <div class="border-left-info pl-3">
                                        <h5 class="text-dark font-weight-bold mb-3">
                                            <i class="fas fa-exclamation-triangle text-warning mr-2"></i>Deskripsi Kerusakan
                                        </h5>
                                        <p class="text-gray-800"><?= nl2br(htmlspecialchars($laporan['deskripsi'])) ?></p>
                                    </div>
                                </div>
                            </div>

                            <!-- Detail Perbaikan Teknisi -->
                            <?php if ($laporan['catatan_teknisi'] || $laporan['teknisi_status']): ?>
                            <hr class="mt-4 mb-4">
                            <div class="detail-perbaikan">
                                <h5 class="font-weight-bold mb-4">
                                    <i class="fas fa-tools mr-2"></i>Detail Perbaikan oleh Teknisi
                                </h5>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="font-weight-bold text-white-50">Teknisi yang Menangani:</label>
                                            <p class="mb-0 h6"><?= htmlspecialchars($laporan['teknisi']) ?></p>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="font-weight-bold text-white-50">Status Perbaikan:</label>
                                            <p class="mb-0">
                                                <span class="badge badge-light">
                                                    <i class="fas fa-cog mr-1"></i>
                                                    <?= $laporan['teknisi_status'] ?: 'Belum ada update' ?>
                                                </span>
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <?php if ($laporan['catatan_teknisi']): ?>
                                        <div class="mb-3">
                                            <label class="font-weight-bold text-white-50">Catatan Perbaikan:</label>
                                            <div class="bg-white text-dark p-3 rounded">
                                                <?= nl2br(htmlspecialchars($laporan['catatan_teknisi'])) ?>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <?php if ($laporan['teknisi_status'] == 'Selesai'): ?>
                                <div class="alert alert-light mt-3" role="alert">
                                    <h6 class="alert-heading text-dark">
                                        <i class="fas fa-check-circle text-success mr-2"></i>Perbaikan Selesai
                                    </h6>
                                    <p class="mb-0 text-dark">
                                        Teknisi telah menyelesaikan perbaikan. Silakan konfirmasi hasil perbaikan jika Anda merasa alat sudah berfungsi dengan baik.
                                    </p>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>

                            <!-- Catatan Admin dan Verifikasi -->
                            <?php if ($laporan['catatan_admin'] || $laporan['catatan_verifikasi']): ?>
                            <hr class="mt-4 mb-4">
                            <div class="row">
                                <div class="col-12">
                                    <h5 class="font-weight-bold text-dark mb-4">
                                        <i class="fas fa-comments mr-2"></i>Catatan dan Feedback
                                    </h5>
                                    
                                    <?php if ($laporan['catatan_admin']): ?>
                                    <div class="catatan-box catatan-admin">
                                        <h6 class="font-weight-bold text-danger mb-2">
                                            <i class="fas fa-user-shield mr-2"></i>Catatan Admin
                                        </h6>
                                        <p class="mb-0"><?= nl2br(htmlspecialchars($laporan['catatan_admin'])) ?></p>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($laporan['catatan_verifikasi']): ?>
                                    <div class="catatan-box catatan-verifikasi">
                                        <h6 class="font-weight-bold text-success mb-2">
                                            <i class="fas fa-check-circle mr-2"></i>Catatan Konfirmasi
                                        </h6>
                                        <p class="mb-0"><?= nl2br(htmlspecialchars($laporan['catatan_verifikasi'])) ?></p>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <hr class="mt-4 mb-4">

                            <div class="row">
                                <div class="col-12">
                                    <h5 class="font-weight-bold text-dark mb-4">
                                        <i class="fas fa-camera mr-2"></i>Dokumentasi Bukti Kerusakan
                                    </h5>
                                    <?php if ($fotos->num_rows > 0): ?>
                                    <div class="row">
                                        <?php 
                                        $fotos->data_seek(0); // Reset pointer
                                        while($foto = $fotos->fetch_assoc()): 
                                        ?>
                                        <div class="col-md-3 mb-4">
                                            <div class="thumbnail-container">
                                                <img src="../../uploads/<?= $foto['jenis'] ?>/<?= $foto['path_foto'] ?>" 
                                                     class="img-thumbnail cursor-pointer"
                                                     style="height: 200px; object-fit: cover; width: 100%;"
                                                     data-toggle="modal" data-target="#imageModal<?= $foto['id_laporan_foto'] ?>"
                                                     title="Klik untuk memperbesar">
                                                <div class="text-center mt-2">
                                                    <small class="text-muted">
                                                        <i class="fas fa-<?= $foto['jenis'] == 'bukti' ? 'exclamation-triangle' : 'tools' ?> mr-1"></i>
                                                        <?= ucfirst($foto['jenis']) ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endwhile; ?>
                                    </div>
                                    <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-images fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">Tidak ada foto dokumentasi</p>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php if ($history->num_rows > 0): ?>
                            <hr class="mt-4 mb-4">

                            <div class="row">
                                <div class="col-12">
                                    <h5 class="font-weight-bold text-dark mb-4">
                                        <i class="fas fa-history mr-2"></i>Riwayat Status
                                    </h5>
                                    <div class="timeline">
                                        <?php while($hist = $history->fetch_assoc()): ?>
                                        <div class="timeline-item">
                                            <div class="timeline-marker"></div>
                                            <div class="timeline-content">
                                                <div class="d-flex justify-content-between">
                                                    <span class="font-weight-bold text-success">
                                                        <?= htmlspecialchars($hist['status']) ?>
                                                    </span>
                                                    <small class="text-muted">
                                                        <?= date('d/m/Y H:i', strtotime($hist['timestamp'])) ?>
                                                    </small>
                                                </div>
                                                <?php if(!empty($hist['catatan'])): ?>
                                                <p class="mt-2 mb-0 text-gray-600">
                                                    <?= nl2br(htmlspecialchars($hist['catatan'])) ?>
                                                </p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php endwhile; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Action Buttons -->
                            <hr class="mt-4 mb-4">
                            <div class="d-flex justify-content-between">
                                <a href="list.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left mr-2"></i>Kembali ke Daftar
                                </a>
                                
                                <?php if($laporan['teknisi_status'] == 'Selesai' && empty($laporan['konfirmasi_status'])): ?>
                                <a href="confirm.php?id=<?= $laporan['id_laporan'] ?>" class="btn btn-success">
                                    <i class="fas fa-check-double mr-2"></i>Konfirmasi Perbaikan
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Image Modal -->
    <?php if ($fotos->num_rows > 0): ?>
    <?php $fotos->data_seek(0); ?>
    <?php while($foto = $fotos->fetch_assoc()): ?>
    <div class="modal fade" id="imageModal<?= $foto['id_laporan_foto'] ?>" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-<?= $foto['jenis'] == 'bukti' ? 'exclamation-triangle' : 'tools' ?> mr-2"></i>
                        Foto <?= ucfirst($foto['jenis']) ?>
                    </h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body text-center">
                    <img src="../../uploads/<?= $foto['jenis'] ?>/<?= $foto['path_foto'] ?>" 
                         class="img-fluid"
                         style="max-height: 70vh">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
    <?php endwhile; ?>
    <?php endif; ?>

    <!-- Scripts -->
    <script src="../../vendor/jquery/jquery.min.js"></script>
    <script src="../../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../../js/sb-admin-2.min.js"></script>

    <script>
    $(document).ready(function() {
        // Efek hover pada card
        $('.card').hover(
            function() {
                $(this).addClass('shadow-lg').css('transition', '0.3s');
            },
            function() {
                $(this).removeClass('shadow-lg').css('transition', '0.3s');
            }
        );

        // Zoom gambar on click
        $('.img-thumbnail').click(function() {
            const src = $(this).attr('src');
            $('#imagePreview').attr('src', src);
        });

        // Tooltip untuk status progress
        $('.status-step').tooltip();

        // Smooth scroll untuk timeline
        $('.timeline-item').each(function(index) {
            $(this).delay(100 * index).fadeIn(500);
        });
    });
    </script>
</body>
</html>
