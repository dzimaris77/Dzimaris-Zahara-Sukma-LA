<?php
session_start();
if ($_SESSION['role'] != 'tenaga_kesehatan') {
    header("Location: ../../login.php");
    exit();
}

require_once '../../config/config.php';

// Get room ID from parameter
$room_id = isset($_GET['room_id']) ? $_GET['room_id'] : '';

if (empty($room_id)) {
    die("Room ID tidak ditemukan!");
}

// Get room details
$sql = "SELECT lantai, sayap, nama_ruangan FROM ruangan WHERE id_ruangan = '$room_id'";
$result = mysqli_query($conn, $sql);
$room_detail = mysqli_fetch_assoc($result);

if (!$room_detail) {
    die("Data ruangan tidak ditemukan!");
}

// Get equipment data for the room
$sql = "
    SELECT ebr.id_equipment, ebr.id_alat, a.nama_alat, a.merk, a.no_seri, a.jenis_alat, 
           ebr.kondisi, ebr.keterangan, ebr.tanggal_laporan, ebr.foto
    FROM equipment_by_room ebr
    JOIN alat a ON ebr.id_alat = a.id_alat
    WHERE ebr.id_ruangan = '$room_id' AND ebr.status = 'aktif'
    ORDER BY a.jenis_alat, a.nama_alat
";
$result = mysqli_query($conn, $sql);

// Store data for summary calculation
$equipment_data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $equipment_data[] = $row;
}

// Generate filename
$room_name = preg_replace('/[^A-Za-z0-9\-]/', '_', $room_detail['nama_ruangan']);
$filename = "Laporan_Alat_" . $room_name . "_" . date('Y-m-d_H-i-s') . ".xls";

// Set headers for Excel download
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Pragma: no-cache");
header("Expires: 0");

// Add BOM for UTF-8
echo "\xEF\xBB\xBF";

// Create Excel content with proper formatting
?>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <style>
        .header { font-weight: bold; font-size: 14pt; text-align: center; }
        .info { font-weight: bold; font-size: 10pt; }
        .table-header { font-weight: bold; background-color: #4CAF50; color: white; text-align: center; border: 1px solid #000; }
        .table-data { border: 1px solid #000; text-align: left; vertical-align: top; }
        .table-number { border: 1px solid #000; text-align: center; }
        .summary { font-weight: bold; background-color: #f0f0f0; }
        .kondisi-baik { background-color: #d4edda; }
        .kondisi-rusak { background-color: #f8d7da; }
    </style>
</head>
<body>

<table border="0" cellpadding="3" cellspacing="0" width="100%">
    <!-- Header Section -->
    <tr>
        <td colspan="9" class="header">LAPORAN ALAT PER RUANGAN</td>
    </tr>
    <tr><td colspan="9">&nbsp;</td></tr>
    
    <!-- Room Information -->
    <tr>
        <td class="info">Ruangan:</td>
        <td colspan="8"><?= htmlspecialchars($room_detail['nama_ruangan']) ?></td>
    </tr>
    <tr>
        <td class="info">Lokasi:</td>
        <td colspan="8">Lantai <?= $room_detail['lantai'] ?><?= $room_detail['sayap'] ? ' - ' . $room_detail['sayap'] : '' ?></td>
    </tr>
    <tr>
        <td class="info">Tanggal Export:</td>
        <td colspan="8"><?= date('d/m/Y H:i:s') ?></td>
    </tr>
    <tr>
        <td class="info">Diekspor oleh:</td>
        <td colspan="8"><?= htmlspecialchars($_SESSION['nama']) ?></td>
    </tr>
    <tr><td colspan="9">&nbsp;</td></tr>
    
    <!-- Table Header -->
    <tr>
        <td class="table-header" width="5%">No</td>
        <td class="table-header" width="12%">Jenis Alat</td>
        <td class="table-header" width="20%">Nama Alat</td>
        <td class="table-header" width="15%">Merk</td>
        <td class="table-header" width="15%">No. Seri</td>
        <td class="table-header" width="10%">Kondisi</td>
        <td class="table-header" width="15%">Keterangan</td>
        <td class="table-header" width="13%">Tanggal Laporan</td>
        <td class="table-header" width="8%">Foto</td>
    </tr>
    
    <!-- Data Rows -->
    <?php
    $no = 1;
    $total_count = 0;
    $medis_count = 0;
    $non_medis_count = 0;
    $baik_count = 0;
    $rusak_count = 0;
    
    foreach ($equipment_data as $row) {
        $jenis_alat = ($row['jenis_alat'] == 'medis') ? 'Medis' : 'Non-Medis';
        $kondisi = ucfirst($row['kondisi']);
        $tanggal = date('d/m/Y H:i', strtotime($row['tanggal_laporan']));
        $status_foto = !empty($row['foto']) ? 'Ada' : 'Tidak Ada';
        $kondisi_class = ($row['kondisi'] == 'baik') ? 'kondisi-baik' : 'kondisi-rusak';
        
        // Count for summary
        $total_count++;
        if ($row['jenis_alat'] == 'medis') {
            $medis_count++;
        } else {
            $non_medis_count++;
        }
        
        if ($row['kondisi'] == 'baik') {
            $baik_count++;
        } else {
            $rusak_count++;
        }
        ?>
        <tr>
            <td class="table-number"><?= $no++ ?></td>
            <td class="table-data"><?= $jenis_alat ?></td>
            <td class="table-data"><?= htmlspecialchars($row['nama_alat']) ?></td>
            <td class="table-data"><?= htmlspecialchars($row['merk']) ?></td>
            <td class="table-data"><?= htmlspecialchars($row['no_seri'] ?? '-') ?></td>
            <td class="table-data <?= $kondisi_class ?>"><?= $kondisi ?></td>
            <td class="table-data"><?= htmlspecialchars($row['keterangan']) ?></td>
            <td class="table-data"><?= $tanggal ?></td>
            <td class="table-data"><?= $status_foto ?></td>
        </tr>
    <?php } ?>
    
    <!-- Empty row for spacing -->
    <tr><td colspan="9">&nbsp;</td></tr>
    <tr><td colspan="9">&nbsp;</td></tr>
    
    <!-- Summary Section -->
    <tr>
        <td colspan="9" class="header">RINGKASAN LAPORAN</td>
    </tr>
    <tr><td colspan="9">&nbsp;</td></tr>
    
    <tr>
        <td class="summary">Total Alat:</td>
        <td class="summary"><?= $total_count ?> unit</td>
        <td colspan="7"></td>
    </tr>
    <tr>
        <td class="summary">Alat Medis:</td>
        <td class="summary"><?= $medis_count ?> unit</td>
        <td class="summary">Alat Non-Medis:</td>
        <td class="summary"><?= $non_medis_count ?> unit</td>
        <td colspan="5"></td>
    </tr>
    <tr>
        <td class="summary kondisi-baik">Kondisi Baik:</td>
        <td class="summary kondisi-baik"><?= $baik_count ?> unit</td>
        <td class="summary kondisi-rusak">Kondisi Rusak:</td>
        <td class="summary kondisi-rusak"><?= $rusak_count ?> unit</td>
        <td colspan="5"></td>
    </tr>
    
    <!-- Percentage calculation -->
    <?php
    $persentase_baik = $total_count > 0 ? round(($baik_count / $total_count) * 100, 1) : 0;
    $persentase_rusak = $total_count > 0 ? round(($rusak_count / $total_count) * 100, 1) : 0;
    ?>
    <tr>
        <td class="summary">Persentase Baik:</td>
        <td class="summary kondisi-baik"><?= $persentase_baik ?>%</td>
        <td class="summary">Persentase Rusak:</td>
        <td class="summary kondisi-rusak"><?= $persentase_rusak ?>%</td>
        <td colspan="5"></td>
    </tr>
    
    <tr><td colspan="9">&nbsp;</td></tr>
    <tr>
        <td colspan="9" style="font-size: 8pt; color: #666;">
            Laporan ini dibuat secara otomatis oleh Sistem Manajemen Alat Kesehatan
        </td>
    </tr>
</table>

</body>
</html>

<?php
mysqli_close($conn);
exit();
?>
