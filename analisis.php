<?php
session_start();
if ($_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once '../../config/config.php';

// Query untuk mendapatkan alat yang paling sering rusak
$query_top_rusak = "
    SELECT 
        a.id_alat,
        a.nama_alat,
        a.merk,
        a.type_model,
        a.no_seri,
        a.jenis_alat,
        COUNT(l.id_laporan) AS jumlah_rusak
    FROM alat a
    LEFT JOIN laporan l ON a.id_alat = l.id_alat
    WHERE l.status IN ('dalam_perbaikan', 'selesai', 'selesai_total')
    GROUP BY a.id_alat
    ORDER BY jumlah_rusak DESC
    LIMIT 5
";
$top_rusak = $conn->query($query_top_rusak);

// Query untuk mendapatkan statistik laporan
$query_stats = "
    SELECT 
        COUNT(*) AS total_laporan,
        SUM(CASE WHEN status = 'menunggu_verifikasi' THEN 1 ELSE 0 END) AS menunggu_verifikasi,
        SUM(CASE WHEN status = 'menunggu_penugasan' THEN 1 ELSE 0 END) AS menunggu_penugasan,
        SUM(CASE WHEN status = 'dalam_perbaikan' THEN 1 ELSE 0 END) AS dalam_perbaikan,
        SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) AS selesai,
        SUM(CASE WHEN status = 'selesai_total' THEN 1 ELSE 0 END) AS selesai_total
    FROM laporan
";
$stats_result = $conn->query($query_stats);
$stats = $stats_result->fetch_assoc();

// Query untuk distribusi kerusakan per jenis alat
$query_jenis = "
    SELECT 
        a.jenis_alat,
        COUNT(l.id_laporan) AS jumlah_laporan
    FROM alat a
    LEFT JOIN laporan l ON a.id_alat = l.id_alat
    WHERE l.status IN ('dalam_perbaikan', 'selesai', 'selesai_total')
    GROUP BY a.jenis_alat
";
$jenis_result = $conn->query($query_jenis);
$jenis_data = [];
$jenis_labels = [];
$jenis_colors = [];
while ($row = $jenis_result->fetch_assoc()) {
    $jenis_data[] = $row['jumlah_laporan'];
    $jenis_labels[] = $row['jenis_alat'] == 'medis' ? 'Medis' : 'Non-Medis';
    $jenis_colors[] = $row['jenis_alat'] == 'medis' ? '#1cc88a' : '#36b9cc';
}

// Query untuk distribusi kerusakan per ruangan
$query_ruangan = "
    SELECT 
        r.nama_ruangan,
        COUNT(l.id_laporan) AS jumlah_laporan
    FROM ruangan r
    LEFT JOIN laporan l ON r.id_ruangan = l.id_ruangan
    WHERE l.status IN ('dalam_perbaikan', 'selesai', 'selesai_total')
    GROUP BY r.id_ruangan
    ORDER BY jumlah_laporan DESC
    LIMIT 5
";
$ruangan_result = $conn->query($query_ruangan);
$ruangan_data = [];
$ruangan_labels = [];
while ($row = $ruangan_result->fetch_assoc()) {
    $ruangan_data[] = $row['jumlah_laporan'];
    $ruangan_labels[] = $row['nama_ruangan'];
}

// Query untuk tren bulanan
$query_tren = "
    SELECT 
        DATE_FORMAT(tanggal_laporan, '%Y-%m') AS bulan,
        COUNT(id_laporan) AS jumlah_laporan
    FROM laporan
    WHERE tanggal_laporan >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY bulan
    ORDER BY bulan ASC
";
$tren_result = $conn->query($query_tren);
$tren_labels = [];
$tren_data = [];
while ($row = $tren_result->fetch_assoc()) {
    $tren_labels[] = date('M Y', strtotime($row['bulan']));
    $tren_data[] = $row['jumlah_laporan'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Analisis Alat - SIMONFAST</title>
    
    <!-- Custom fonts -->
    <link href="../../vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    
    <!-- Custom styles -->
    <link href="../../css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../../vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
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
            border-left-color: #5a5c69;
        }
        .stats-card.laporan {
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
        .card-chart {
            height: 300px;
        }
        .highlight-row {
            background-color: #fff3cd;
            font-weight: bold;
        }
        .analytics-header {
            border-bottom: 2px solid #e3e6f0;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .table-hover tbody tr:hover {
            background-color: rgba(0, 0, 0, 0.03);
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
                        <h1 class="h3 mb-0 text-gray-800">Analisis Peralatan Medis & Non-Medis</h1>
                        <a href="kelola_alat.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                            <i class="fas fa-tools fa-sm text-white-50"></i> Kelola Alat
                        </a>
                    </div>

                    <!-- Statistik Utama -->
                    <div class="row mb-4">
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card stats-card laporan shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                Total Laporan Kerusakan</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['total_laporan'] ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card stats-card shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                Dalam Perbaikan</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['dalam_perbaikan'] ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-tools fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card stats-card shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                Selesai Diperbaiki</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['selesai'] + $stats['selesai_total'] ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card stats-card shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">
                                                Menunggu Penanganan</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['menunggu_verifikasi'] + $stats['menunggu_penugasan'] ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Alat Paling Sering Rusak -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-danger">
                                <i class="fas fa-exclamation-triangle mr-2"></i>5 Alat Paling Sering Dilaporkan Rusak
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>No</th>
                                            <th>Nama Alat</th>
                                            <th>Merk & Model</th>
                                            <th>Jenis</th>
                                            <th>No. Seri</th>
                                            <th>Jumlah Kerusakan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($top_rusak->num_rows > 0): ?>
                                            <?php $counter = 1; ?>
                                            <?php while($row = $top_rusak->fetch_assoc()): ?>
                                            <tr <?= $counter == 1 ? 'class="highlight-row"' : '' ?>>
                                                <td><?= $counter ?></td>
                                                <td>
                                                    <i class="fas fa-<?= $row['jenis_alat'] == 'medis' ? 'stethoscope' : 'chair' ?> mr-2 text-muted"></i>
                                                    <?= htmlspecialchars($row['nama_alat']) ?>
                                                    <?php if($counter == 1): ?>
                                                        <span class="badge badge-danger ml-2">Paling Sering Rusak</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= htmlspecialchars($row['merk']) ?> - <?= htmlspecialchars($row['type_model']) ?></td>
                                                <td>
                                                    <span class="badge badge-<?= $row['jenis_alat'] == 'medis' ? 'medis' : 'non-medis' ?>">
                                                        <?= $row['jenis_alat'] == 'medis' ? 'Medis' : 'Non-Medis' ?>
                                                    </span>
                                                </td>
                                                <td><?= htmlspecialchars($row['no_seri']) ?></td>
                                                <td class="font-weight-bold"><?= $row['jumlah_rusak'] ?> laporan</td>
                                            </tr>
                                            <?php $counter++; ?>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center py-4">
                                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i><br>
                                                    <span class="text-muted">Tidak ada data laporan kerusakan</span>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Grafik Analisis -->
                    <div class="row">
                        <!-- Distribusi Kerusakan per Jenis Alat -->
                        <div class="col-xl-6 col-lg-6 mb-4">
                            <div class="card shadow">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">
                                        <i class="fas fa-chart-pie mr-2"></i>Distribusi Kerusakan per Jenis Alat
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="card-chart">
                                        <canvas id="jenisChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tren Kerusakan 6 Bulan Terakhir -->
                        <div class="col-xl-6 col-lg-6 mb-4">
                            <div class="card shadow">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">
                                        <i class="fas fa-chart-line mr-2"></i>Tren Kerusakan Alat (6 Bulan Terakhir)
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="card-chart">
                                        <canvas id="trenChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Ruangan dengan Kerusakan Terbanyak -->
                        <div class="col-xl-6 col-lg-6 mb-4">
                            <div class="card shadow">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">
                                        <i class="fas fa-hospital mr-2"></i>Ruangan dengan Kerusakan Terbanyak
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>No</th>
                                                    <th>Nama Ruangan</th>
                                                    <th>Jumlah Kerusakan</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (count($ruangan_data) > 0): ?>
                                                    <?php for ($i = 0; $i < count($ruangan_data); $i++): ?>
                                                    <tr>
                                                        <td><?= $i + 1 ?></td>
                                                        <td><?= htmlspecialchars($ruangan_labels[$i]) ?></td>
                                                        <td class="font-weight-bold"><?= $ruangan_data[$i] ?> laporan</td>
                                                    </tr>
                                                    <?php endfor; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="3" class="text-center py-4">
                                                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i><br>
                                                            <span class="text-muted">Tidak ada data ruangan dengan kerusakan</span>
                                                        </td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Waktu Respon Rata-Rata -->
                        <div class="col-xl-6 col-lg-6 mb-4">
                            <div class="card shadow">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">
                                        <i class="fas fa-clock mr-2"></i>Waktu Respon Rata-Rata
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="text-center py-5">
                                        <h2 class="text-gray-800 mb-3">24 Jam</h2>
                                        <p class="text-muted">Rata-rata waktu dari laporan dibuat hingga penugasan teknisi</p>
                                        <hr>
                                        <h2 class="text-gray-800 mb-3">3 Hari</h2>
                                        <p class="text-muted">Rata-rata waktu penyelesaian perbaikan</p>
                                    </div>
                                </div>
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
    
    <script>
    // Grafik Distribusi Jenis Alat
    const jenisCtx = document.getElementById('jenisChart').getContext('2d');
    const jenisChart = new Chart(jenisCtx, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($jenis_labels) ?>,
            datasets: [{
                data: <?= json_encode($jenis_data) ?>,
                backgroundColor: <?= json_encode($jenis_colors) ?>,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.raw || 0;
                            const total = context.chart.getDatasetMeta(0).total;
                            const percentage = Math.round((value / total) * 100);
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });

    // Grafik Tren Kerusakan
    const trenCtx = document.getElementById('trenChart').getContext('2d');
    const trenChart = new Chart(trenCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode($tren_labels) ?>,
            datasets: [{
                label: 'Jumlah Laporan Kerusakan',
                data: <?= json_encode($tren_data) ?>,
                backgroundColor: 'rgba(78, 115, 223, 0.05)',
                borderColor: 'rgba(78, 115, 223, 1)',
                pointBackgroundColor: 'rgba(78, 115, 223, 1)',
                pointBorderColor: '#fff',
                pointHoverBackgroundColor: '#fff',
                pointHoverBorderColor: 'rgba(78, 115, 223, 1)',
                pointRadius: 4,
                borderWidth: 2,
                tension: 0.3,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        title: function(context) {
                            return context[0].label;
                        }
                    }
                }
            }
        }
    });
    
    // Auto dismiss alerts
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
    </script>
</body>
</html>