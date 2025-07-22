<?php
session_start();
require '../../config/config.php';

if ($_SESSION['role'] != 'admin_teknisi') {
    header("Location: ../../login.php");
    exit();
}

// PROSES CRUD
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Tambah Alat
        if (isset($_POST['tambah_alat'])) {
            $nama = $conn->real_escape_string($_POST['nama_alat']);
            $merk = $conn->real_escape_string($_POST['merk']);
            $type = $conn->real_escape_string($_POST['type_model']);
            $seri = $conn->real_escape_string($_POST['no_seri']);

            $conn->query("INSERT INTO alat (nama_alat, merk, type_model, no_seri) 
                        VALUES ('$nama', '$merk', '$type', '$seri')");
            $_SESSION['success'] = "Alat berhasil ditambahkan!";
        }

        // Edit Alat
        if (isset($_POST['edit_alat'])) {
            $id = $conn->real_escape_string($_POST['id']);
            $nama = $conn->real_escape_string($_POST['nama_alat']);
            $merk = $conn->real_escape_string($_POST['merk']);
            $type = $conn->real_escape_string($_POST['type_model']);
            $seri = $conn->real_escape_string($_POST['no_seri']);

            $conn->query("UPDATE alat 
                        SET nama_alat = '$nama',
                            merk = '$merk',
                            type_model = '$type',
                            no_seri = '$seri'
                        WHERE id_alat = '$id'");
            $_SESSION['success'] = "Alat berhasil diperbarui!";
        }

        // Hapus Alat
        if (isset($_POST['delete_alat'])) {
            $id = $conn->real_escape_string($_POST['id']);
            $conn->query("DELETE FROM alat WHERE id_alat = '$id'");
            $_SESSION['success'] = "Alat berhasil dihapus!";
        }

    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
    header("Location: maintenance.php");
    exit();
}

// Ambil data alat
$alat = $conn->query("SELECT * FROM alat ORDER BY nama_alat ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Maintenance Alat - SIMONFAST</title>
    
    <!-- Custom fonts -->
    <link href="../../vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    
    <!-- Custom styles -->
    <link href="../../css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../../vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">

    <style>
    .maintenance-card { transition: transform 0.2s; }
    .maintenance-card:hover { transform: translateY(-3px); }
    .export-btn {
        background: #1cc88a;
        border: none;
        padding: 10px 20px;
        border-radius: 8px;
        color: white;
        transition: all 0.3s;
    }
    .export-btn:hover { 
        background: #17a673;
        transform: scale(1.05);
    }
    .action-btns .btn { margin: 0 3px; }
    .modal-header { border-bottom: 2px solid #1cc88a; }
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
                    <h1 class="h3 mb-4 text-gray-800">Maintenance Alat Medis</h1>
                    
                    <div class="card shadow-lg mb-4">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center bg-white">
                            <h6 class="m-0 font-weight-bold text-success">
                                <i class="fas fa-tools"></i> Daftar Alat Medis
                            </h6>
                            <div>
                                <button class="export-btn mr-2" onclick="exportToExcel()">
                                    <i class="fas fa-file-excel"></i> Export Excel
                                </button>
                                <button class="btn btn-success" data-toggle="modal" data-target="#tambahAlatModal">
                                    <i class="fas fa-plus"></i> Tambah Alat
                                </button>
                            </div>
                        </div>
                        
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover" id="dataTable" width="100%" cellspacing="0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Nama Alat</th>
                                            <th>Merk</th>
                                            <th>Type/Model</th>
                                            <th>No. Seri</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($row = $alat->fetch_assoc()): ?>
                                        <tr class="maintenance-card">
                                            <td><?= htmlspecialchars($row['nama_alat']) ?></td>
                                            <td><?= htmlspecialchars($row['merk']) ?></td>
                                            <td><?= htmlspecialchars($row['type_model']) ?></td>
                                            <td><?= htmlspecialchars($row['no_seri']) ?></td>
                                            <td class="action-btns">
                                                <button class="btn btn-sm btn-warning edit-btn"
                                                        data-id="<?= $row['id_alat'] ?>"
                                                        data-nama="<?= htmlspecialchars($row['nama_alat']) ?>"
                                                        data-merk="<?= htmlspecialchars($row['merk']) ?>"
                                                        data-type="<?= htmlspecialchars($row['type_model']) ?>"
                                                        data-seri="<?= htmlspecialchars($row['no_seri']) ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form method="POST" class="d-inline" 
                                                      onsubmit="return confirm('Hapus alat ini?')">
                                                    <input type="hidden" name="id" value="<?= $row['id_alat'] ?>">
                                                    <button type="submit" name="delete_alat" 
                                                            class="btn btn-sm btn-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
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

    <!-- Modal Tambah -->
    <div class="modal fade" id="tambahAlatModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-success"><i class="fas fa-toolbox"></i> Tambah Alat Baru</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Nama Alat</label>
                            <input type="text" name="nama_alat" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Merk</label>
                            <input type="text" name="merk" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Type/Model</label>
                            <input type="text" name="type_model" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>No. Seri</label>
                            <input type="text" name="no_seri" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" name="tambah_alat" class="btn btn-success">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Edit -->
    <div class="modal fade" id="editAlatModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-success"><i class="fas fa-edit"></i> Edit Alat</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form method="POST">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Nama Alat</label>
                            <input type="text" name="nama_alat" id="edit_nama" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Merk</label>
                            <input type="text" name="merk" id="edit_merk" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Type/Model</label>
                            <input type="text" name="type_model" id="edit_type" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>No. Seri</label>
                            <input type="text" name="no_seri" id="edit_seri" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" name="edit_alat" class="btn btn-success">Simpan Perubahan</button>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>

    <script>
    // Handle Edit Button
    $(document).ready(function() {
        $('.edit-btn').click(function() {
            const data = $(this).data();
            $('#edit_id').val(data.id);
            $('#edit_nama').val(data.nama);
            $('#edit_merk').val(data.merk);
            $('#edit_type').val(data.type);
            $('#edit_seri').val(data.seri);
            $('#editAlatModal').modal('show');
        });

        // DataTable
        $('#dataTable').DataTable({
            language: {
                search: "Cari alat:",
                zeroRecords: "Tidak ada data alat",
                info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ alat",
                paginate: {
                    previous: "‹",
                    next: "›"
                }
            }
        });
    });

    // Export Excel
    function exportToExcel() {
        const table = document.getElementById('dataTable');
        const ws = XLSX.utils.table_to_sheet(table);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, "Maintenance");
        XLSX.writeFile(wb, 'maintenance_alat_'+new Date().toISOString().split('T')[0]+'.xlsx');
    }
    </script>
</body>
</html>
