<?php
session_start();
require '../../config/config.php';
require '../../config/telegram.php';

if ($_SESSION['role'] != 'admin') {
    header("Location: ../../login.php");
    exit();
}

// Proses approve
if (isset($_GET['approve'])) {
    $id = $conn->real_escape_string($_GET['id']);
    $conn->query("UPDATE laporan SET status='menunggu_penugasan' WHERE id_laporan=$id");
    send_telegram("✅ LAPORAN #$id TELAH DISETUJUI\nStatus: Menunggu Penugasan");
    header("Location: verify.php?success=approved");
    exit();
}

// Proses reject dengan catatan
if (isset($_POST['reject_laporan'])) {
    $id = $conn->real_escape_string($_POST['laporan_id']);
    $catatan = $conn->real_escape_string($_POST['catatan_penolakan']);
    
    $conn->query("UPDATE laporan SET status='ditolak_admin', catatan_admin='$catatan' WHERE id_laporan=$id");
    send_telegram("❌ LAPORAN #$id DITOLAK\nAlasan: $catatan");
    header("Location: verify.php?success=rejected");
    exit();
}

// Query laporan dengan informasi alat termasuk jenis_alat
$laporan = $conn->query("SELECT l.*, a.nama_alat, a.merk, a.jenis_alat, r.nama_ruangan, u.nama as pelapor_nama
                       FROM laporan l
                       JOIN alat a ON l.id_alat = a.id_alat
                       JOIN ruangan r ON l.id_ruangan = r.id_ruangan
                       JOIN users u ON l.id_pelapor = u.id_user
                       WHERE l.status='menunggu_verifikasi'
                       ORDER BY l.tanggal_laporan DESC");

// Hitung statistik
$stats_total = $laporan->num_rows;
$stats_medis = $conn->query("SELECT COUNT(*) as count FROM laporan l JOIN alat a ON l.id_alat = a.id_alat WHERE l.status='menunggu_verifikasi' AND a.jenis_alat='medis'")->fetch_assoc()['count'];
$stats_non_medis = $conn->query("SELECT COUNT(*) as count FROM laporan l JOIN alat a ON l.id_alat = a.id_alat WHERE l.status='menunggu_verifikasi' AND a.jenis_alat='non_medis'")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verifikasi Laporan - SIMONFAST</title>
    
    <!-- Custom fonts -->
    <link href="../../vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    
    <!-- Custom styles -->
    <link href="../../css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../../vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">

    <style>
    .verification-card {
        border-left: 4px solid #1cc88a;
        transition: transform 0.3s;
    }
    .verification-card:hover {
        transform: translateX(5px);
    }
    .action-buttons .btn {
        min-width: 100px;
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
    .reject-form {
        background: #f8f9fc;
        border-radius: 8px;
        padding: 15px;
        margin-top: 10px;
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
                    <h1 class="h3 mb-4 text-gray-800">Verifikasi Laporan Kerusakan</h1>

                    <!-- Alert Messages -->
                    <?php if (isset($_GET['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php
                            switch($_GET['success']) {
                                case 'approved': echo 'Laporan berhasil disetujui dan diteruskan ke tahap penugasan!'; break;
                                case 'rejected': echo 'Laporan berhasil ditolak dengan catatan yang diberikan!'; break;
                            }
                            ?>
                            <button type="button" class="close" data-dismiss="alert">
                                <span>&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>

                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-xl-4 col-md-6 mb-4">
                            <div class="card stats-card total shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                Total Laporan Pending</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats_total ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-clipboard-check fa-2x text-gray-300"></i>
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
                    
                    <div class="card shadow-lg mb-4">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center bg-white">
                            <h6 class="m-0 font-weight-bold text-success">
                                <i class="fas fa-clipboard-check"></i> Laporan Masuk - Menunggu Verifikasi
                            </h6>
                            <span class="badge badge-warning badge-pill"><?= $stats_total ?> Laporan</span>
                        </div>
                        
                        <div class="card-body">
                            <?php if ($stats_total > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover" id="dataTable" width="100%" cellspacing="0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Tanggal</th>
                                            <th>Jenis Alat</th>
                                            <th>Nama Alat</th>
                                            <th>Ruangan</th>
                                            <th>Pelapor</th>
                                            <th>Deskripsi</th>
                                            <th>Status</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $laporan->data_seek(0); // Reset pointer
                                        while($row = $laporan->fetch_assoc()): 
                                        ?>
                                        <tr class="verification-card">
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
                                            <td><?= htmlspecialchars($row['pelapor_nama']) ?></td>
                                            <td>
                                                <span data-toggle="tooltip" title="<?= htmlspecialchars($row['deskripsi']) ?>">
                                                    <?= substr(htmlspecialchars($row['deskripsi']), 0, 50) ?>...
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-warning">
                                                    <i class="fas fa-clock mr-1"></i>
                                                    Menunggu Verifikasi
                                                </span>
                                            </td>
                                            <td class="action-buttons">
                                                <div class="btn-group-vertical" role="group">
                                                    <a href="detail_laporan.php?id=<?= $row['id_laporan'] ?>" 
                                                       class="btn btn-info btn-sm mb-1"
                                                       data-toggle="tooltip"
                                                       title="Lihat detail lengkap laporan">
                                                        <i class="fas fa-eye"></i> Detail
                                                    </a>
                                                    <a href="verify.php?approve&id=<?= $row['id_laporan'] ?>" 
                                                       class="btn btn-success btn-sm mb-1"
                                                       data-toggle="tooltip"
                                                       title="Setujui laporan dan lanjutkan ke penugasan"
                                                       onclick="return confirm('Apakah Anda yakin ingin menyetujui laporan ini?')">
                                                        <i class="fas fa-check"></i> Approve
                                                    </a>
                                                    <button class="btn btn-danger btn-sm reject-btn"
                                                            data-id="<?= $row['id_laporan'] ?>"
                                                            data-alat="<?= htmlspecialchars($row['nama_alat']) ?>"
                                                            data-toggle="tooltip"
                                                            title="Tolak laporan dengan memberikan alasan">
                                                        <i class="fas fa-times"></i> Reject
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-check-circle fa-5x text-success mb-3"></i>
                                <h5 class="text-muted">Tidak Ada Laporan yang Perlu Diverifikasi</h5>
                                <p class="text-muted">Semua laporan telah diproses atau belum ada laporan baru masuk.</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Reject -->
    <div class="modal fade" id="rejectModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-times-circle mr-2"></i>Tolak Laporan
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="laporan_id" id="reject_laporan_id">
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            <strong>Perhatian!</strong> Anda akan menolak laporan untuk alat: <span id="reject_alat_name" class="font-weight-bold"></span>
                        </div>

                        <div class="form-group">
                            <label for="catatan_penolakan" class="font-weight-bold">
                                <i class="fas fa-comment mr-2"></i>Alasan Penolakan <span class="text-danger">*</span>
                            </label>
                            <textarea name="catatan_penolakan" 
                                      id="catatan_penolakan" 
                                      class="form-control" 
                                      rows="4" 
                                      placeholder="Berikan alasan yang jelas mengapa laporan ini ditolak..."
                                      required></textarea>
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle mr-1"></i>
                                Alasan penolakan akan dikirimkan kepada pelapor
                            </small>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">Contoh Alasan Penolakan:</label>
                            <div class="reject-form">
                                <button type="button" class="btn btn-outline-secondary btn-sm mb-2 reason-btn" 
                                        data-reason="Laporan tidak jelas atau kurang detail">
                                    Laporan tidak jelas atau kurang detail
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm mb-2 reason-btn" 
                                        data-reason="Foto bukti tidak sesuai atau tidak jelas">
                                    Foto bukti tidak sesuai atau tidak jelas
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm mb-2 reason-btn" 
                                        data-reason="Alat yang dilaporkan tidak ditemukan masalah">
                                    Alat yang dilaporkan tidak ditemukan masalah
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm mb-2 reason-btn" 
                                        data-reason="Laporan duplikat atau sudah pernah dilaporkan">
                                    Laporan duplikat atau sudah pernah dilaporkan
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm mb-2 reason-btn" 
                                        data-reason="Bukan kerusakan alat, melainkan kesalahan penggunaan">
                                    Bukan kerusakan alat, melainkan kesalahan penggunaan
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="fas fa-arrow-left mr-1"></i>Batal
                        </button>
                        <button type="submit" name="reject_laporan" class="btn btn-danger">
                            <i class="fas fa-times mr-1"></i>Tolak Laporan
                        </button>
                    </div>
                </form>
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
        // DataTable initialization
        $('#dataTable').DataTable({
            ordering: true,
            order: [[0, 'desc']],
            language: {
                search: "Cari laporan:",
                zeroRecords: "Tidak ada laporan yang perlu diverifikasi",
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
                { "width": "12%", "targets": 3 }, // Ruangan
                { "width": "10%", "targets": 4 }, // Pelapor
                { "width": "25%", "targets": 5 }, // Deskripsi
                { "width": "10%", "targets": 6 }, // Status
                { "width": "10%", "targets": 7 }  // Aksi
            ]
        });
        
        // Tooltip initialization
        $('[data-toggle="tooltip"]').tooltip();

        // Reject button handler
        $('.reject-btn').click(function() {
            const laporanId = $(this).data('id');
            const alatName = $(this).data('alat');
            
            $('#reject_laporan_id').val(laporanId);
            $('#reject_alat_name').text(alatName);
            $('#catatan_penolakan').val('');
            $('#rejectModal').modal('show');
        });

        // Reason button handler
        $('.reason-btn').click(function() {
            const reason = $(this).data('reason');
            $('#catatan_penolakan').val(reason);
        });

        // Form validation
        $('#rejectModal form').submit(function(e) {
            const catatan = $('#catatan_penolakan').val().trim();
            if (catatan.length < 10) {
                e.preventDefault();
                alert('Alasan penolakan minimal 10 karakter!');
                return false;
            }
            
            if (!confirm('Apakah Anda yakin ingin menolak laporan ini?')) {
                e.preventDefault();
                return false;
            }
        });

        // Auto dismiss alerts
        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 5000);

        // Enhanced hover effects
        $('.verification-card').hover(
            function() {
                $(this).addClass('bg-light');
            },
            function() {
                $(this).removeClass('bg-light');
            }
        );
    });
    </script>
</body>
</html>
