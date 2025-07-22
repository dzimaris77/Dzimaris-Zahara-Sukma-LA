<?php
session_start();
require_once '../../config/config.php';

// Check if user is logged in and has the correct role
if (!isset($_SESSION['role']) || ($_SESSION['role'] != 'tenaga_kesehatan')) {
    // Fixed path to login page - using absolute path from web root
    header("Location: ../../login.php");
    exit();
}

// Get user ID from session
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['nama'];

// Function to get counts for different report statuses
function getStatusCount($conn, $user_id, $status = null) {
    $sql = "SELECT COUNT(*) as count FROM laporan WHERE id_pelapor = ?";
    if ($status) {
        $sql .= " AND status = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $user_id, $status);
    } else {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['count'];
}

// Get latest reports submitted by the current user
$sql_latest_reports = "SELECT l.id_laporan, l.tanggal_laporan, l.status, a.nama_alat, r.nama_ruangan, r.lantai, r.sayap 
                    FROM laporan l
                    JOIN alat a ON l.id_alat = a.id_alat
                    JOIN ruangan r ON l.id_ruangan = r.id_ruangan
                    WHERE l.id_pelapor = ?
                    ORDER BY l.tanggal_laporan DESC
                    LIMIT 5";
$stmt_latest = $conn->prepare($sql_latest_reports);
$stmt_latest->bind_param("i", $user_id);
$stmt_latest->execute();
$latest_reports = $stmt_latest->get_result();

// Get reports needing confirmation/verification from the user
$sql_pending_confirmation = "SELECT l.id_laporan, l.tanggal_laporan, a.nama_alat, r.nama_ruangan, 
                          u.nama as teknisi_nama, l.teknisi_status
                       FROM laporan l
                       JOIN alat a ON l.id_alat = a.id_alat
                       JOIN ruangan r ON l.id_ruangan = r.id_ruangan
                       LEFT JOIN users u ON l.id_teknisi = u.id_user
                       WHERE l.id_pelapor = ? AND l.status = 'menunggu_konfirmasi'
                       ORDER BY l.tanggal_laporan DESC";
$stmt_pending = $conn->prepare($sql_pending_confirmation);
$stmt_pending->bind_param("i", $user_id);
$stmt_pending->execute();
$pending_confirmations = $stmt_pending->get_result();

// Get count of reports by status
$total_reports = getStatusCount($conn, $user_id);
$pending_reports = getStatusCount($conn, $user_id, 'menunggu_verifikasi');
$in_progress_reports = getStatusCount($conn, $user_id, 'dalam_perbaikan');
$completed_reports = getStatusCount($conn, $user_id, 'selesai_total');
$waiting_confirmation = getStatusCount($conn, $user_id, 'menunggu_konfirmasi');

// Function to display status badge
function getStatusBadge($status) {
    switch ($status) {
        case 'menunggu_verifikasi':
            return '<span class="badge badge-warning">Menunggu Verifikasi</span>';
        case 'ditolak_admin':
            return '<span class="badge badge-danger">Ditolak Admin</span>';
        case 'menunggu_penugasan':
            return '<span class="badge badge-info">Menunggu Penugasan</span>';
        case 'dalam_perbaikan':
            return '<span class="badge badge-primary">Dalam Perbaikan</span>';
        case 'selesai':
            return '<span class="badge badge-success">Selesai</span>';
        case 'menunggu_konfirmasi':
            return '<span class="badge badge-warning">Menunggu Konfirmasi</span>';
        case 'selesai_total':
            return '<span class="badge badge-success">Selesai Total</span>';
        default:
            return '<span class="badge badge-secondary">' . $status . '</span>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Tenaga Kesehatan | Sistem Pelaporan Alat</title>
    
    <!-- CSS -->
    <link href="../../vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="../../css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../../vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
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
                        <h1 class="h3 mb-0 text-gray-800">Dashboard Tenaga Kesehatan</h1>
                        <a href="tambah_laporan.php" class="d-none d-sm-inline-block btn btn-sm btn-success shadow-sm">
                            <i class="fas fa-plus fa-sm text-white-50"></i> Buat Laporan Baru
                        </a>
                    </div>

                    <!-- Status Cards -->
                    <div class="row">
                        <!-- Total Reports Card -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                Total Laporan</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_reports; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pending Verification Card -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-warning shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                Menunggu Verifikasi</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $pending_reports; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- In Progress Card -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-info shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                Dalam Perbaikan</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $in_progress_reports; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-tools fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Completed Card -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-success shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                Selesai Total</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $completed_reports; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Content Row -->
                    <div class="row">
                        <!-- Pending Confirmations -->
                        <div class="col-lg-6 mb-4">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Menunggu Konfirmasi Anda</h6>
                                </div>
                                <div class="card-body">
                                    <?php if ($pending_confirmations->num_rows > 0): ?>
                                        <div class="table-responsive">
                                            <table class="table table-bordered" width="100%" cellspacing="0">
                                                <thead>
                                                    <tr>
                                                        <th>ID</th>
                                                        <th>Alat</th>
                                                        <th>Ruangan</th>
                                                        <th>Teknisi</th>
                                                        <th>Status</th>
                                                        <th>Aksi</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php while ($row = $pending_confirmations->fetch_assoc()): ?>
                                                    <tr>
                                                        <td><?php echo $row['id_laporan']; ?></td>
                                                        <td><?php echo $row['nama_alat']; ?></td>
                                                        <td><?php echo $row['nama_ruangan']; ?></td>
                                                        <td><?php echo $row['teknisi_nama']; ?></td>
                                                        <td><?php echo $row['teknisi_status']; ?></td>
                                                        <td>
                                                            <a href="konfirmasi_perbaikan.php?id=<?php echo $row['id_laporan']; ?>" class="btn btn-sm btn-primary">
                                                                <i class="fas fa-check"></i> Konfirmasi
                                                            </a>
                                                        </td>
                                                    </tr>
                                                    <?php endwhile; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-center">Tidak ada laporan yang memerlukan konfirmasi saat ini.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Latest Reports -->
                        <div class="col-lg-6 mb-4">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Laporan Terbaru Anda</h6>
                                </div>
                                <div class="card-body">
                                    <?php if ($latest_reports->num_rows > 0): ?>
                                        <div class="table-responsive">
                                            <table class="table table-bordered" width="100%" cellspacing="0">
                                                <thead>
                                                    <tr>
                                                        <th>ID</th>
                                                        <th>Alat</th>
                                                        <th>Lokasi</th>
                                                        <th>Tanggal</th>
                                                        <th>Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php while ($row = $latest_reports->fetch_assoc()): 
                                                        $lokasi = $row['nama_ruangan'] . ' - ' . $row['lantai'];
                                                        if (!empty($row['sayap'])) {
                                                            $lokasi .= ' ' . $row['sayap'];
                                                        }
                                                        $tanggal = date('d/m/Y H:i', strtotime($row['tanggal_laporan']));
                                                    ?>
                                                    <tr>
                                                        <td><a href="detail_laporan.php?id=<?php echo $row['id_laporan']; ?>"><?php echo $row['id_laporan']; ?></a></td>
                                                        <td><?php echo $row['nama_alat']; ?></td>
                                                        <td><?php echo $lokasi; ?></td>
                                                        <td><?php echo $tanggal; ?></td>
                                                        <td><?php echo getStatusBadge($row['status']); ?></td>
                                                    </tr>
                                                    <?php endwhile; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-center">Belum ada laporan yang dibuat.</p>
                                    <?php endif; ?>
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

    <!-- Logout Modal-->
    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Yakin ingin keluar?</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">Pilih "Logout" jika Anda ingin mengakhiri sesi saat ini.</div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Batal</button>
                    <a class="btn btn-primary" href="../../logout.php">Logout</a>
                </div>
            </div>
        </div>
    </div>

    <!-- JS -->
    <script src="../../vendor/jquery/jquery.min.js"></script>
    <script src="../../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../../js/sb-admin-2.min.js"></script>
    <script src="../../vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="../../vendor/datatables/dataTables.bootstrap4.min.js"></script>
</body>
</html>
