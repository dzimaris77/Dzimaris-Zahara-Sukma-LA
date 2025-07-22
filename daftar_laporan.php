<?php
session_start();
require '../../config/config.php';

if ($_SESSION['role'] != 'admin') {
    header("Location: ../../login.php");
    exit();
}

// Handle delete action
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    
    // Get laporan data untuk hapus foto jika ada
    $get_laporan = $conn->query("SELECT foto, dokumentasi_perbaikan FROM laporan WHERE id_laporan = '$delete_id'");
    $laporan_data = $get_laporan->fetch_assoc();
    
    // Delete laporan from database
    $delete_query = "DELETE FROM laporan WHERE id_laporan = '$delete_id'";
    
    if ($conn->query($delete_query)) {
        // Delete associated files if they exist
        if ($laporan_data['foto'] && file_exists('../../uploads/laporan/' . $laporan_data['foto'])) {
            unlink('../../uploads/laporan/' . $laporan_data['foto']);
        }
        if ($laporan_data['dokumentasi_perbaikan'] && file_exists('../../uploads/perbaikan/' . $laporan_data['dokumentasi_perbaikan'])) {
            unlink('../../uploads/perbaikan/' . $laporan_data['dokumentasi_perbaikan']);
        }
        
        $success_message = "Laporan berhasil dihapus!";
    } else {
        $error_message = "Gagal menghapus laporan: " . $conn->error;
    }
}

// Default filter values
$current_month = date('m');
$current_year = date('Y');

// Get filter parameters
$filter_month = isset($_GET['month']) ? $_GET['month'] : $current_month;
$filter_year = isset($_GET['year']) ? $_GET['year'] : $current_year;
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_jenis = isset($_GET['jenis']) ? $_GET['jenis'] : '';

// Build WHERE clause for filtering
$where_conditions = [];

// Add date filtering
if (!empty($filter_month) && !empty($filter_year)) {
    $where_conditions[] = "MONTH(l.tanggal_laporan) = '$filter_month' AND YEAR(l.tanggal_laporan) = '$filter_year'";
}

// Add status filtering
if (!empty($filter_status)) {
    $where_conditions[] = "l.status = '$filter_status'";
}

// Add jenis alat filtering
if (!empty($filter_jenis)) {
    $where_conditions[] = "a.jenis_alat = '$filter_jenis'";
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Query to get all reports dengan jenis alat dan laporan perbaikan
$query = "
    SELECT l.*, a.nama_alat, a.merk, a.jenis_alat, r.nama_ruangan, 
           p.nama as nama_pelapor, 
           t.nama as nama_teknisi,
           l.laporan_perbaikan, l.dokumentasi_perbaikan
    FROM laporan l
    JOIN alat a ON l.id_alat = a.id_alat
    JOIN ruangan r ON l.id_ruangan = r.id_ruangan
    JOIN users p ON l.id_pelapor = p.id_user
    LEFT JOIN users t ON l.id_teknisi = t.id_user
    $where_clause
    ORDER BY l.tanggal_laporan DESC
";

$reports = $conn->query($query);

// Get statistics
$stats_total = $conn->query("SELECT COUNT(*) as count FROM laporan l JOIN alat a ON l.id_alat = a.id_alat $where_clause")->fetch_assoc()['count'];
$stats_medis = $conn->query("SELECT COUNT(*) as count FROM laporan l JOIN alat a ON l.id_alat = a.id_alat WHERE a.jenis_alat='medis' " . (!empty($where_conditions) ? 'AND ' . implode(' AND ', $where_conditions) : ''))->fetch_assoc()['count'];
$stats_non_medis = $conn->query("SELECT COUNT(*) as count FROM laporan l JOIN alat a ON l.id_alat = a.id_alat WHERE a.jenis_alat='non_medis' " . (!empty($where_conditions) ? 'AND ' . implode(' AND ', $where_conditions) : ''))->fetch_assoc()['count'];

// Get all years for filter dropdown
$years_query = "SELECT DISTINCT YEAR(tanggal_laporan) as year FROM laporan ORDER BY year DESC";
$years_result = $conn->query($years_query);
$years = [];
while ($year = $years_result->fetch_assoc()) {
    $years[] = $year['year'];
}
if (empty($years)) {
    $years[] = date('Y');
}
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
    
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    
    <style>
        .filter-card {
            transition: all 0.3s;
        }
        .filter-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
        }
        .btn-export {
            transition: all 0.3s;
        }
        .btn-export:hover {
            transform: translateY(-2px);
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
        .laporan-perbaikan {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .dokumentasi-thumb {
            max-width: 50px;
            max-height: 50px;
            cursor: pointer;
        }
        .btn-delete {
            transition: all 0.3s;
        }
        .btn-delete:hover {
            transform: scale(1.1);
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
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">
                            <i class="fas fa-clipboard-list text-primary"></i> Daftar Semua Laporan
                        </h1>
                        <button id="exportBtn" class="btn btn-success btn-sm btn-export shadow-sm">
                            <i class="fas fa-file-excel fa-sm text-white-50 mr-1"></i> Export ke Excel
                        </button>
                    </div>

                    <!-- Alert Messages -->
                    <?php if (isset($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle mr-2"></i><?= $success_message ?>
                        <button type="button" class="close" data-dismiss="alert">
                            <span>&times;</span>
                        </button>
                    </div>
                    <?php endif; ?>

                    <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle mr-2"></i><?= $error_message ?>
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
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Total Laporan</div>
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
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Laporan Alat Medis</div>
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
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Laporan Alat Non-Medis</div>
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

                    <!-- Filter Card -->
                    <div class="card shadow mb-4 border-left-primary filter-card">
                        <div class="card-header py-3 bg-white">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-filter mr-2"></i>Filter Data Laporan
                            </h6>
                        </div>
                        <div class="card-body">
                            <form method="GET" action="" class="row align-items-end">
                                <div class="col-md-2 mb-3">
                                    <label for="month" class="form-label font-weight-bold">Bulan</label>
                                    <select class="form-control" id="month" name="month">
                                        <option value="">Semua Bulan</option>
                                        <option value="01" <?= $filter_month == '01' ? 'selected' : '' ?>>Januari</option>
                                        <option value="02" <?= $filter_month == '02' ? 'selected' : '' ?>>Februari</option>
                                        <option value="03" <?= $filter_month == '03' ? 'selected' : '' ?>>Maret</option>
                                        <option value="04" <?= $filter_month == '04' ? 'selected' : '' ?>>April</option>
                                        <option value="05" <?= $filter_month == '05' ? 'selected' : '' ?>>Mei</option>
                                        <option value="06" <?= $filter_month == '06' ? 'selected' : '' ?>>Juni</option>
                                        <option value="07" <?= $filter_month == '07' ? 'selected' : '' ?>>Juli</option>
                                        <option value="08" <?= $filter_month == '08' ? 'selected' : '' ?>>Agustus</option>
                                        <option value="09" <?= $filter_month == '09' ? 'selected' : '' ?>>September</option>
                                        <option value="10" <?= $filter_month == '10' ? 'selected' : '' ?>>Oktober</option>
                                        <option value="11" <?= $filter_month == '11' ? 'selected' : '' ?>>November</option>
                                        <option value="12" <?= $filter_month == '12' ? 'selected' : '' ?>>Desember</option>
                                    </select>
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label for="year" class="form-label font-weight-bold">Tahun</label>
                                    <select class="form-control" id="year" name="year">
                                        <option value="">Semua Tahun</option>
                                        <?php foreach ($years as $year): ?>
                                            <option value="<?= $year ?>" <?= $filter_year == $year ? 'selected' : '' ?>><?= $year ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label for="jenis" class="form-label font-weight-bold">Jenis Alat</label>
                                    <select class="form-control" id="jenis" name="jenis">
                                        <option value="">Semua Jenis</option>
                                        <option value="medis" <?= $filter_jenis == 'medis' ? 'selected' : '' ?>>Alat Medis</option>
                                        <option value="non_medis" <?= $filter_jenis == 'non_medis' ? 'selected' : '' ?>>Alat Non-Medis</option>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="status" class="form-label font-weight-bold">Status</label>
                                    <select class="form-control" id="status" name="status">
                                        <option value="">Semua Status</option>
                                        <option value="menunggu_verifikasi" <?= $filter_status == 'menunggu_verifikasi' ? 'selected' : '' ?>>Menunggu Verifikasi</option>
                                        <option value="ditolak_admin" <?= $filter_status == 'ditolak_admin' ? 'selected' : '' ?>>Ditolak Admin</option>
                                        <option value="menunggu_penugasan" <?= $filter_status == 'menunggu_penugasan' ? 'selected' : '' ?>>Menunggu Penugasan</option>
                                        <option value="dalam_perbaikan" <?= $filter_status == 'dalam_perbaikan' ? 'selected' : '' ?>>Dalam Perbaikan</option>
                                        <option value="selesai" <?= $filter_status == 'selesai' ? 'selected' : '' ?>>Selesai</option>
                                        <option value="selesai_total" <?= $filter_status == 'selesai_total' ? 'selected' : '' ?>>Selesai Total</option>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <button type="submit" class="btn btn-primary mr-2">
                                        <i class="fas fa-filter mr-1"></i> Filter
                                    </button>
                                    <a href="daftar_laporan.php" class="btn btn-secondary">
                                        <i class="fas fa-sync-alt mr-1"></i> Reset
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Data Table -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-table mr-2"></i>Data Laporan
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead class="bg-primary text-white">
                                        <tr>
                                            <th>ID</th>
                                            <th>Tanggal</th>
                                            <th>Jenis</th>
                                            <th>Alat</th>
                                            <th>Ruangan</th>
                                            <th>Pelapor</th>
                                            <th>Teknisi</th>
                                            <th>Status</th>
                                            <th>Laporan Perbaikan</th>
                                            <th>Dokumentasi</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($reports && $reports->num_rows > 0): ?>
                                            <?php while ($report = $reports->fetch_assoc()): ?>
                                                <tr>
                                                    <td class="font-weight-bold">#<?= $report['id_laporan'] ?></td>
                                                    <td><?= date('d/m/Y H:i', strtotime($report['tanggal_laporan'])) ?></td>
                                                    <td>
                                                        <span class="badge badge-<?= $report['jenis_alat'] == 'medis' ? 'medis' : 'non-medis' ?>">
                                                            <i class="fas fa-<?= $report['jenis_alat'] == 'medis' ? 'stethoscope' : 'chair' ?> mr-1"></i>
                                                            <?= $report['jenis_alat'] == 'medis' ? 'Medis' : 'Non-Medis' ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <strong><?= htmlspecialchars($report['nama_alat']) ?></strong>
                                                        <?php if ($report['merk']): ?>
                                                            <br><small class="text-muted"><?= htmlspecialchars($report['merk']) ?></small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?= htmlspecialchars($report['nama_ruangan']) ?></td>
                                                    <td><?= htmlspecialchars($report['nama_pelapor']) ?></td>
                                                    <td><?= $report['nama_teknisi'] ? htmlspecialchars($report['nama_teknisi']) : '<span class="text-muted">-</span>' ?></td>
                                                    <td>
                                                        <?php
                                                        $status_colors = [
                                                            'menunggu_verifikasi' => 'warning',
                                                            'ditolak_admin' => 'danger',
                                                            'menunggu_penugasan' => 'info',
                                                            'dalam_perbaikan' => 'primary',
                                                            'selesai' => 'success',
                                                            'selesai_total' => 'dark'
                                                        ];
                                                        
                                                        $status_labels = [
                                                            'menunggu_verifikasi' => 'Menunggu Verifikasi',
                                                            'ditolak_admin' => 'Ditolak Admin',
                                                            'menunggu_penugasan' => 'Menunggu Penugasan',
                                                            'dalam_perbaikan' => 'Dalam Perbaikan',
                                                            'selesai' => 'Selesai',
                                                            'selesai_total' => 'Selesai Total'
                                                        ];
                                                        
                                                        $color = $status_colors[$report['status']] ?? 'secondary';
                                                        $label = $status_labels[$report['status']] ?? $report['status'];
                                                        ?>
                                                        <span class="badge badge-<?= $color ?> p-2">
                                                            <?= $label ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php if ($report['laporan_perbaikan']): ?>
                                                            <div class="laporan-perbaikan" 
                                                                 data-toggle="tooltip" 
                                                                 title="<?= htmlspecialchars($report['laporan_perbaikan']) ?>">
                                                                <?= htmlspecialchars(substr($report['laporan_perbaikan'], 0, 50)) ?>...
                                                            </div>
                                                        <?php else: ?>
                                                            <span class="text-muted">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-center">
                                                        <?php if ($report['dokumentasi_perbaikan']): ?>
                                                            <img src="../../uploads/perbaikan/<?= $report['dokumentasi_perbaikan'] ?>" 
                                                                 class="dokumentasi-thumb rounded" 
                                                                 data-toggle="modal" 
                                                                 data-target="#imageModal"
                                                                 data-src="../../uploads/perbaikan/<?= $report['dokumentasi_perbaikan'] ?>"
                                                                 title="Klik untuk memperbesar">
                                                        <?php else: ?>
                                                            <span class="text-muted">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <a href="detail_laporan.php?id=<?= $report['id_laporan'] ?>" 
                                                               class="btn btn-info btn-sm" title="Detail Laporan">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            <button type="button" 
                                                                    class="btn btn-danger btn-sm btn-delete" 
                                                                    data-id="<?= $report['id_laporan'] ?>"
                                                                    data-nama="<?= htmlspecialchars($report['nama_alat']) ?>"
                                                                    title="Hapus Laporan">
                                                                <i class="fas fa-trash-alt"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="11" class="text-center py-4">
                                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i><br>
                                                    <span class="text-muted">Tidak ada data laporan</span>
                                                </td>
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

    <!-- Image Modal -->
    <div class="modal fade" id="imageModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-camera mr-2"></i>Dokumentasi Perbaikan
                    </h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body text-center">
                    <img src="" class="img-fluid" id="modalImage" style="max-height: 70vh">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="../../vendor/jquery/jquery.min.js"></script>
    <script src="../../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../../js/sb-admin-2.min.js"></script>
    <script src="../../vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="../../vendor/datatables/dataTables.bootstrap4.min.js"></script>
    
    <!-- SheetJS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
    $(document).ready(function() {
        // Initialize DataTable
        $('#dataTable').DataTable({
            "ordering": true,
            "searching": true,
            "paging": true,
            "pageLength": 25,
            "order": [[0, 'desc']],
            "language": {
                "search": "Cari:",
                "lengthMenu": "Tampilkan _MENU_ data per halaman",
                "zeroRecords": "Tidak ada data yang ditemukan",
                "info": "Menampilkan halaman _PAGE_ dari _PAGES_",
                "infoEmpty": "Tidak ada data yang tersedia",
                "infoFiltered": "(difilter dari _MAX_ total data)",
                "paginate": {
                    "first": "Pertama",
                    "last": "Terakhir",
                    "next": "Selanjutnya",
                    "previous": "Sebelumnya"
                }
            },
            "columnDefs": [
                { "width": "5%", "targets": 0 },   // ID
                { "width": "10%", "targets": 1 },  // Tanggal
                { "width": "8%", "targets": 2 },   // Jenis
                { "width": "15%", "targets": 3 },  // Alat
                { "width": "10%", "targets": 4 },  // Ruangan
                { "width": "10%", "targets": 5 },  // Pelapor
                { "width": "10%", "targets": 6 },  // Teknisi
                { "width": "8%", "targets": 7 },   // Status
                { "width": "15%", "targets": 8 },  // Laporan Perbaikan
                { "width": "5%", "targets": 9 },   // Dokumentasi
                { "width": "10%", "targets": 10 }  // Aksi
            ]
        });
        
        // Initialize tooltips
        $('[data-toggle="tooltip"]').tooltip();
        
        // Image modal handler
        $('.dokumentasi-thumb').click(function() {
            const imgSrc = $(this).data('src');
            $('#modalImage').attr('src', imgSrc);
        });
        
        // Delete button handler dengan SweetAlert2
        $('.btn-delete').click(function() {
            const id = $(this).data('id');
            const nama = $(this).data('nama');
            
            Swal.fire({
                title: 'Konfirmasi Hapus',
                html: `Apakah Anda yakin ingin menghapus laporan untuk alat:<br><strong>${nama}</strong>?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: '<i class="fas fa-trash-alt mr-1"></i> Ya, Hapus!',
                cancelButtonText: '<i class="fas fa-times mr-1"></i> Batal',
                reverseButtons: true,
                customClass: {
                    confirmButton: 'btn btn-danger',
                    cancelButton: 'btn btn-secondary'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading
                    Swal.fire({
                        title: 'Menghapus...',
                        text: 'Mohon tunggu sebentar',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    // Redirect to delete
                    window.location.href = `daftar_laporan.php?delete_id=${id}`;
                }
            });
        });
        
        // Export to Excel function
        $("#exportBtn").click(function() {
            var data = [];
            var headers = [];
            
            // Get headers (exclude Actions and Dokumentasi columns)
            $('#dataTable thead tr th').each(function(index) {
                if (index !== 9 && index !== 10) { // Skip Dokumentasi and Aksi columns
                    headers.push($(this).text());
                }
            });
            
            data.push(headers);
            
            // Get data rows
            $('#dataTable tbody tr').each(function() {
                var rowData = [];
                var $tds = $(this).find('td');
                
                // Skip Dokumentasi and Aksi columns
                for (var i = 0; i < $tds.length; i++) {
                    if (i !== 9 && i !== 10) {
                        rowData.push($tds.eq(i).text().trim());
                    }
                }
                
                if (rowData.length > 0) {
                    data.push(rowData);
                }
            });
            
            // Create workbook and worksheet
            var wb = XLSX.utils.book_new();
            var ws = XLSX.utils.aoa_to_sheet(data);
            
            // Set column widths
            var wscols = [
                {wch: 8},  // ID
                {wch: 15}, // Tanggal
                {wch: 10}, // Jenis
                {wch: 25}, // Alat
                {wch: 20}, // Ruangan
                {wch: 20}, // Pelapor
                {wch: 20}, // Teknisi
                {wch: 15}, // Status
                {wch: 40}  // Laporan Perbaikan
            ];
            ws['!cols'] = wscols;
            
            // Add the worksheet to the workbook
            XLSX.utils.book_append_sheet(wb, ws, "Daftar Laporan");
            
            // Generate filename with current date
            var today = new Date();
            var fileName = "Daftar_Laporan_" + 
                           today.getFullYear() + "-" + 
                           String(today.getMonth() + 1).padStart(2, '0') + "-" + 
                           String(today.getDate()).padStart(2, '0') + ".xlsx";
            
            // Export to Excel
            XLSX.writeFile(wb, fileName);
        });
        
        // Hover effects for stats cards
        $('.stats-card').hover(
            function() {
                $(this).addClass('shadow-lg');
            },
            function() {
                $(this).removeClass('shadow-lg');
            }
        );
        
        // Hover effects for cards
        $('.card').hover(
            function() {
                $(this).addClass('shadow-lg').css('transition', '0.3s');
            },
            function() {
                $(this).removeClass('shadow-lg').css('transition', '0.3s');
            }
        );
        
        // Auto hide alerts after 5 seconds
        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 5000);
    });
    </script>
</body>
</html>
