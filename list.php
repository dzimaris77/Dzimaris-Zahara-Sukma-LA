<?php
session_start();
require '../../config/config.php';

if ($_SESSION['role'] != 'tenaga_kesehatan') {
    header("Location: ../../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Query dengan informasi jenis alat dan catatan
$laporan = $conn->query("
    SELECT l.*, a.nama_alat, a.merk, a.jenis_alat, r.nama_ruangan, 
           l.catatan_admin, l.catatan_teknisi, l.catatan_verifikasi
    FROM laporan l
    JOIN alat a ON l.id_alat = a.id_alat
    JOIN ruangan r ON l.id_ruangan = r.id_ruangan
    WHERE l.id_pelapor = $user_id
    ORDER BY l.tanggal_laporan DESC 
");

// Hitung statistik
$stats_total = $laporan->num_rows;
$stats_medis = $conn->query("SELECT COUNT(*) as count FROM laporan l JOIN alat a ON l.id_alat = a.id_alat WHERE l.id_pelapor = $user_id AND a.jenis_alat='medis'")->fetch_assoc()['count'];
$stats_non_medis = $conn->query("SELECT COUNT(*) as count FROM laporan l JOIN alat a ON l.id_alat = a.id_alat WHERE l.id_pelapor = $user_id AND a.jenis_alat='non_medis'")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar Laporan - SIMONFAST</title>
    
    <!-- Custom fonts -->
    <link href="../../vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    
    <!-- Custom styles -->
    <link href="../../css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../../vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">

    <style>
    .status-badge {
        min-width: 100px;
        padding: 0.5em 0.75em;
        border-radius: 20px;
        font-size: 0.9em;
    }
    .table-hover tbody tr:hover {
        background-color: #f8f9fc;
    }
    .action-buttons .btn {
        min-width: 40px;
        margin: 2px;
    }
    .stats-card {
        border-left: 4px solid;
        transition: transform 0.2s;
    }
    .stats-card:hover {
        transform: translateY(-2px);
    }
    .stats-card.medis {
        border-left-color: #1cc88a;
    }
    .stats-card.non-medis {
        border-left-color: #36b9cc;
    }
    .stats-card.total {
        border-left-color: #f6c23e;
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
        padding: 10px;
        margin: 5px 0;
        border-radius: 4px;
        font-size: 0.85em;
    }
    .catatan-admin {
        border-left-color: #e74a3b;
    }
    .catatan-teknisi {
        border-left-color: #36b9cc;
    }
    .catatan-verifikasi {
        border-left-color: #1cc88a;
    }
    .expandable-content {
        max-height: 50px;
        overflow: hidden;
        transition: max-height 0.3s ease;
    }
    .expandable-content.expanded {
        max-height: 200px;
    }
    .expand-btn {
        cursor: pointer;
        color: #007bff;
        font-size: 0.8em;
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
                    <h1 class="h3 mb-4 text-gray-800">Daftar Laporan Saya</h1>

                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-xl-4 col-md-6 mb-4">
                            <div class="card stats-card total shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                Total Laporan Saya</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats_total ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-4 col-md-6 mb-4">
                            <div class="card stats-card medis shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                Laporan Alat Medis</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats_medis ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-stethoscope fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-4 col-md-6 mb-4">
                            <div class="card stats-card non-medis shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                Laporan Alat Non-Medis</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats_non_medis ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-chair fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card shadow-lg border-left-success">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center bg-white">
                            <h6 class="m-0 font-weight-bold text-success">
                                <i class="fas fa-clipboard-list"></i> Riwayat Laporan
                            </h6>
                            <a href="tambah_laporan.php" class="btn btn-success btn-sm">
                                <i class="fas fa-plus-circle"></i> Buat Baru
                            </a>
                        </div>
                        
                        <div class="card-body">
                            <?php if($stats_total > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Tanggal</th>
                                            <th>Jenis Alat</th>
                                            <th>Nama Alat</th>
                                            <th>Lokasi</th>
                                            <th>Status</th>
                                            <th>Catatan</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $laporan->data_seek(0); // Reset pointer
                                        while($row = $laporan->fetch_assoc()): 
                                        ?>
                                        <tr>
                                            <td>
                                                <small class="text-muted">
                                                    <?= date('d/m/Y', strtotime($row['tanggal_laporan'])) ?><br>
                                                    <?= date('H:i', strtotime($row['tanggal_laporan'])) ?>
                                                </small>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?= $row['jenis_alat'] == 'medis' ? 'medis' : 'non-medis' ?>">
                                                    <i class="fas fa-<?= $row['jenis_alat'] == 'medis' ? 'stethoscope' : 'chair' ?> mr-1"></i>
                                                    <?= $row['jenis_alat'] == 'medis' ? 'Medis' : 'Non-Medis' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <strong><?= htmlspecialchars($row['nama_alat']) ?></strong>
                                                <?php if ($row['merk']): ?>
                                                    <br><small class="text-muted"><?= htmlspecialchars($row['merk']) ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($row['nama_ruangan']) ?></td>
                                            <td>
                                                <?php
                                                // Prioritas status: konfirmasi_status > teknisi_status > status
                                                if ($row['konfirmasi_status']) {
                                                    $status = $row['konfirmasi_status'];
                                                    $badge_color = $status == 'Dikonfirmasi' ? 'success' : 'danger';
                                                } elseif ($row['teknisi_status']) {
                                                    $status = $row['teknisi_status'];
                                                    $badge_color = match($status) {
                                                        'Laporan Diterima' => 'info',
                                                        'Sedang Diperiksa' => 'primary',
                                                        'Sedang Diperbaiki' => 'warning',
                                                        'Selesai' => 'success',
                                                        default => 'secondary'
                                                    };
                                                } else {
                                                    $status = ucfirst(str_replace('_', ' ', $row['status']));
                                                    $badge_color = match($row['status']) {
                                                        'menunggu_verifikasi' => 'warning',
                                                        'ditolak_admin' => 'danger',
                                                        'menunggu_penugasan' => 'info',
                                                        'dalam_perbaikan' => 'primary',
                                                        'selesai' => 'success',
                                                        'selesai_total' => 'success',
                                                        default => 'secondary'
                                                    };
                                                }
                                                ?>
                                                <span class="badge badge-<?= $badge_color ?> status-badge">
                                                    <?= $status ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="expandable-content" id="catatan-<?= $row['id_laporan'] ?>">
                                                    <?php if ($row['catatan_admin']): ?>
                                                    <div class="catatan-box catatan-admin">
                                                        <strong><i class="fas fa-user-shield mr-1"></i>Admin:</strong><br>
                                                        <?= htmlspecialchars($row['catatan_admin']) ?>
                                                    </div>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($row['catatan_teknisi']): ?>
                                                    <div class="catatan-box catatan-teknisi">
                                                        <strong><i class="fas fa-tools mr-1"></i>Teknisi:</strong><br>
                                                        <?= htmlspecialchars($row['catatan_teknisi']) ?>
                                                    </div>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($row['catatan_verifikasi']): ?>
                                                    <div class="catatan-box catatan-verifikasi">
                                                        <strong><i class="fas fa-check-circle mr-1"></i>Konfirmasi:</strong><br>
                                                        <?= htmlspecialchars($row['catatan_verifikasi']) ?>
                                                    </div>
                                                    <?php endif; ?>
                                                    
                                                    <?php if (!$row['catatan_admin'] && !$row['catatan_teknisi'] && !$row['catatan_verifikasi']): ?>
                                                    <small class="text-muted">
                                                        <i class="fas fa-info-circle mr-1"></i>Belum ada catatan
                                                    </small>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <?php if ($row['catatan_admin'] || $row['catatan_teknisi'] || $row['catatan_verifikasi']): ?>
                                                <div class="expand-btn" onclick="toggleExpand('catatan-<?= $row['id_laporan'] ?>')">
                                                    <i class="fas fa-chevron-down"></i> Lihat Selengkapnya
                                                </div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="action-buttons">
                                                <a href="detail.php?id=<?= $row['id_laporan'] ?>" 
                                                   class="btn btn-info btn-sm"
                                                   data-toggle="tooltip" title="Detail Lengkap">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <?php if($row['teknisi_status'] == 'Selesai' && empty($row['konfirmasi_status'])): ?>
                                                <a href="confirm.php?id=<?= $row['id_laporan'] ?>" 
                                                   class="btn btn-success btn-sm"
                                                   data-toggle="tooltip" title="Konfirmasi Perbaikan">
                                                    <i class="fas fa-check-double"></i>
                                                </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-inbox fa-5x text-muted mb-3"></i>
                                <h5 class="text-muted">Belum Ada Laporan</h5>
                                <p class="text-muted">Anda belum membuat laporan kerusakan. Klik tombol "Buat Baru" untuk membuat laporan pertama.</p>
                                <a href="tambah_laporan.php" class="btn btn-success">
                                    <i class="fas fa-plus-circle mr-2"></i>Buat Laporan Pertama
                                </a>
                            </div>
                            <?php endif; ?>
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
        // Inisialisasi DataTable
        $('#dataTable').DataTable({
            ordering: true,
            order: [[0, 'desc']], // Sort by date descending
            language: {
                search: "Cari laporan:",
                zeroRecords: "Tidak ditemukan data yang sesuai",
                info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ laporan",
                infoEmpty: "Menampilkan 0 sampai 0 dari 0 laporan",
                paginate: {
                    previous: "‹",
                    next: "›"
                }
            },
            columnDefs: [
                { "width": "10%", "targets": 0 }, // Tanggal
                { "width": "8%", "targets": 1 },  // Jenis
                { "width": "15%", "targets": 2 }, // Nama Alat
                { "width": "12%", "targets": 3 }, // Lokasi
                { "width": "10%", "targets": 4 }, // Status
                { "width": "25%", "targets": 5 }, // Catatan
                { "width": "10%", "targets": 6 }  // Aksi
            ]
        });

        // Inisialisasi tooltip
        $('[data-toggle="tooltip"]').tooltip();

        // Efek hover pada baris tabel
        $('.table-hover tbody tr').hover(
            function() {
                $(this).css('transform', 'translateX(5px)');
            },
            function() {
                $(this).css('transform', 'none');
            }
        );

        // Auto dismiss untuk alert jika ada
        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 5000);
    });

    // Function untuk expand/collapse catatan
    function toggleExpand(elementId) {
        const element = document.getElementById(elementId);
        const button = element.nextElementSibling;
        
        if (element.classList.contains('expanded')) {
            element.classList.remove('expanded');
            button.innerHTML = '<i class="fas fa-chevron-down"></i> Lihat Selengkapnya';
        } else {
            element.classList.add('expanded');
            button.innerHTML = '<i class="fas fa-chevron-up"></i> Sembunyikan';
        }
    }
    </script>
</body>
</html>
