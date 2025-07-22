<?php
session_start();
require_once '../../config/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];
    $room_id = $_POST['room_id'];
    
    if ($action == 'add') {
        // Add new equipment to room
        $equipment_id = $_POST['equipment_id'];
        $condition = $_POST['condition'];
        $notes = $_POST['notes'];
        $pelapor_id = $_SESSION['user_id'];
        
        // Handle foto upload
        $foto_name = null;
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
            $upload_dir = '../../uploads/equipment_photos/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $foto_name = 'equipment_' . time() . '_' . uniqid() . '.' . $file_extension;
            
            if (move_uploaded_file($_FILES['foto']['tmp_name'], $upload_dir . $foto_name)) {
                // File uploaded successfully
            } else {
                header("Location: equipment_report.php?room_id=$room_id&error=" . urlencode("Gagal mengupload foto"));
                exit();
            }
        }
        
        // Validasi foto untuk kondisi rusak
        if ($condition == 'rusak' && !$foto_name) {
            header("Location: equipment_report.php?room_id=$room_id&error=" . urlencode("Foto wajib diupload untuk kondisi rusak"));
            exit();
        }
        
        // Insert to database
        $sql = "INSERT INTO equipment_by_room (id_ruangan, id_alat, kondisi, keterangan, foto, id_pelapor, tanggal_laporan, status) 
                VALUES ('$room_id', '$equipment_id', '$condition', '$notes', '$foto_name', '$pelapor_id', NOW(), 'aktif')";
        
        if (mysqli_query($conn, $sql)) {
            header("Location: equipment_report.php?room_id=$room_id&success=add");
        } else {
            header("Location: equipment_report.php?room_id=$room_id&error=" . urlencode("Gagal menambahkan data: " . mysqli_error($conn)));
        }
        
    } elseif ($action == 'update') {
        // PERBAIKAN: Update existing equipment
        $equipment_report_id = $_POST['equipment_report_id']; // ID dari equipment_by_room
        $condition = $_POST['condition'];
        $notes = $_POST['notes'];
        
        // Get existing data untuk cek foto lama
        $sql_check = "SELECT foto FROM equipment_by_room WHERE id_equipment = '$equipment_report_id'";
        $result_check = mysqli_query($conn, $sql_check);
        $existing_data = mysqli_fetch_assoc($result_check);
        
        // Handle foto upload
        $foto_update = "";
        $new_foto_name = null;
        
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
            $upload_dir = '../../uploads/equipment_photos/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $new_foto_name = 'equipment_' . time() . '_' . uniqid() . '.' . $file_extension;
            
            if (move_uploaded_file($_FILES['foto']['tmp_name'], $upload_dir . $new_foto_name)) {
                // Delete old photo if exists
                if ($existing_data['foto'] && file_exists($upload_dir . $existing_data['foto'])) {
                    unlink($upload_dir . $existing_data['foto']);
                }
                $foto_update = ", foto = '$new_foto_name'";
            } else {
                header("Location: equipment_report.php?room_id=$room_id&error=" . urlencode("Gagal mengupload foto"));
                exit();
            }
        }
        
        // PERBAIKAN: Validasi foto untuk kondisi rusak
        if ($condition == 'rusak') {
            // Jika tidak ada foto baru dan tidak ada foto lama, error
            if (!$new_foto_name && !$existing_data['foto']) {
                header("Location: equipment_report.php?room_id=$room_id&error=" . urlencode("Foto wajib ada untuk kondisi rusak"));
                exit();
            }
        }
        
        // PERBAIKAN: Jika kondisi berubah dari rusak ke baik, hapus foto
        if ($condition == 'baik' && $existing_data['foto']) {
            $upload_dir = '../../uploads/equipment_photos/';
            if (file_exists($upload_dir . $existing_data['foto'])) {
                unlink($upload_dir . $existing_data['foto']);
            }
            $foto_update = ", foto = NULL";
        }
        
        // Update database
        $sql = "UPDATE equipment_by_room SET 
                kondisi = '$condition', 
                keterangan = '$notes',
                tanggal_laporan = NOW()
                $foto_update
                WHERE id_equipment = '$equipment_report_id'";
        
        if (mysqli_query($conn, $sql)) {
            header("Location: equipment_report.php?room_id=$room_id&success=update");
        } else {
            header("Location: equipment_report.php?room_id=$room_id&error=" . urlencode("Gagal mengupdate data: " . mysqli_error($conn)));
        }
        
    } elseif ($action == 'delete') {
        // Delete equipment from room
        $report_id = $_POST['report_id'];
        
        // Get photo filename before deleting
        $photo_sql = "SELECT foto FROM equipment_by_room WHERE id_equipment = '$report_id'";
        $photo_result = mysqli_query($conn, $photo_sql);
        $photo_data = mysqli_fetch_assoc($photo_result);
        
        // Update status to tidak_aktif instead of deleting
        $sql = "UPDATE equipment_by_room SET status = 'tidak_aktif' WHERE id_equipment = '$report_id'";
        
        if (mysqli_query($conn, $sql)) {
            // Delete photo file if exists
            if ($photo_data['foto']) {
                $photo_path = '../../uploads/equipment_photos/' . $photo_data['foto'];
                if (file_exists($photo_path)) {
                    unlink($photo_path);
                }
            }
            
            header("Location: equipment_report.php?room_id=$room_id&success=delete");
        } else {
            header("Location: equipment_report.php?room_id=$room_id&error=" . urlencode("Gagal menghapus data: " . mysqli_error($conn)));
        }
    }
} else {
    header("Location: equipment_report.php");
}
?>
