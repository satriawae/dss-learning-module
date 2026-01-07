<?php
session_start();
include '../includes/header.php';
include '../includes/navbar.php';

// --- 1. HANDSHAKE: TANGKAP DATA DARI INPUT ALTERNATIF ---
// Bagian ini PENTING agar data dari halaman sebelumnya tersimpan ke Session
if (isset($_POST['simpan_data'])) {
    $_SESSION['bobot']          = $_POST['bobot'];
    $_SESSION['kriteria_nama']  = $_POST['kriteria_nama'];
    $_SESSION['kriteria_sifat'] = $_POST['kriteria_sifat'];
    $_SESSION['alt_nama']       = $_POST['alt_nama'];
    $_SESSION['alt_nilai']      = $_POST['alt_nilai'];
}

// Cek apakah data sudah ada di session
if (!isset($_SESSION['alt_nilai'])) { header("Location: ../index.php"); exit(); }

$kriteria_nama  = $_SESSION['kriteria_nama'];
$kriteria_sifat = $_SESSION['kriteria_sifat'];
$bobot_raw      = $_SESSION['bobot'];
$alt_nama       = $_SESSION['alt_nama'];
$alt_nilai      = $_SESSION['alt_nilai'];

// --- 2. LOGIKA PERHITUNGAN SAW (BACKEND) ---

// A. Normalisasi Bobot (Ubah % jadi desimal jika perlu, atau pakai raw jika total 1)
$total_bobot = array_sum($bobot_raw);
$bobot_norm = [];
foreach ($bobot_raw as $b) $bobot_norm[] = $b / $total_bobot;

// B. Cari Nilai Max/Min tiap Kriteria (Untuk Rumus Normalisasi)
$min_max = [];
foreach ($kriteria_nama as $key => $val) {
    $col_values = array_column($alt_nilai, $key);
    $min_max[$key] = ($kriteria_sifat[$key] == 'benefit') ? max($col_values) : min($col_values);
}

// C. Proses Normalisasi Matriks (R)
$data_R = [];
foreach($alt_nama as $i => $nama) {
    foreach($alt_nilai[$i] as $j => $nilai) {
        $pembagi = $min_max[$j];
        $sifat = $kriteria_sifat[$j];
        
        // Rumus SAW
        if($sifat == 'benefit') {
            $hasil = $nilai / $pembagi; // Nilai / Max
            $txt_rumus = "$nilai ÷ $pembagi";
        } else {
            $hasil = $pembagi / $nilai; // Min / Nilai
            $txt_rumus = "$pembagi ÷ $nilai";
        }
        
        $data_R[$i][$j] = [
            'val' => $hasil,
            'rumus' => $txt_rumus
        ];
    }
}

// D. Proses Perhitungan Skor Akhir (V)
$data_V = [];
$final_scores = [];
foreach($alt_nama as $i => $nama) {
    $total = 0;
    $cols = [];
    foreach($data_R[$i] as $j => $r) {
        $bobot = $bobot_norm[$j];
        $v_val = $r['val'] * $bobot;
        $total += $v_val;
        
        $cols[] = [
            'r' => $r['val'],
            'w' => $bobot,
            'v' => $v_val,
            'rumus' => round($r['val'],3) . " × " . round($bobot,2)
        ];
    }
    $data_V[$i] = ['nama'=>$nama, 'cols'=>$cols, 'total'=>$total];
    $final_scores[$i] = $total;
}

// E. Ranking (Disiapkan untuk Tab 3 & 4)
// Kita buat array sorting terpisah agar data_V urutannya tetap sesuai input awal (A1, A2, A3)
$sorted_scores = $final_scores;
rsort($sorted_scores); // Urutkan dari besar ke kecil

foreach($data_V as $k => $v) {
    // Cari posisi skor di array yang sudah diurutkan (Index + 1 = Ranking)
    $data_V[$k]['rank'] = array_search($v['total'], $sorted_scores) + 1;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Hasil Perhitungan SAW</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/mystyle.css">
</head>
<body>
<nav class="navbar navbar-dark navbar-dinus mb-4 shadow-sm"><div class="container"><span class="navbar-brand">Hasil Analisa Metode SAW</span></div></nav>

<div class="container mb-5">
    
    <ul class="nav nav-tabs nav-fill shadow-sm rounded-top" id="sawTab" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab1">1. Data Awal</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab2">2. Normalisasi (Penyetaraan)</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab3">3. Perhitungan Skor</button></li>
        <li class="nav-item"><button class="nav-link fw-bold text-primary bg-light" data-bs-toggle="tab" data-bs-target="#tab4">4. Hasil Rekomendasi</button></li>
    </ul>

    <div class="tab-content bg-white p-4 shadow-sm border border-top-0 rounded-bottom">
        
        <div class="tab-pane fade show active" id="tab1">
            <h5 class="text-primary fw-bold mb-3">Data yang Anda Masukkan</h5>
            
            <div class="alert alert-light border-start border-5 border-primary">
                <p class="mb-0 small text-muted">
                    "Ini adalah catatan spesifikasi HP yang temukan di toko. Data ini masih memiliki satuan yang berbeda-beda (Rupiah, GB, Mega Pixel, dll)."
                </p>
            </div>

            <div class="row mb-3">
                <?php foreach($kriteria_nama as $i => $n): ?>
                <div class="col-md-4 mb-2">
                    <div class="border p-2 rounded bg-light text-center h-100">
                        <strong class="d-block text-primary"><?= $n ?></strong>
                        <div class="d-flex justify-content-center gap-2 mt-1">
                            <span class="badge bg-dark">Bobot: <?= $bobot_raw[$i] ?></span>
                            <span class="badge bg-<?= ($kriteria_sifat[$i]=='benefit')?'success':'danger' ?>"><?= ucfirst($kriteria_sifat[$i]) ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <h6>Matriks Keputusan Awal (X):</h6>
            <div class="table-responsive">
                <table class="table table-bordered table-striped text-center align-middle">
                    <thead class="table-dark">
                        <tr><th>Alternatif</th><?php foreach($kriteria_nama as $n) echo "<th>$n</th>"; ?></tr>
                    </thead>
                    <tbody>
                        <?php foreach($alt_nama as $i => $n): ?>
                        <tr>
                            <td class="text-start fw-bold"><?= $n ?></td>
                            <?php foreach($alt_nilai[$i] as $v) echo "<td>$v</td>"; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <button class="btn btn-dinus btn-sm float-end" onclick="document.querySelector('#sawTab button[data-bs-target=\'#tab2\']').click()">Lihat Cara Normalisasi >></button>
        </div>

        <div class="tab-pane fade" id="tab2">
            <h5 class="text-primary fw-bold"><i class="bi bi-shuffle"></i> Tahap Normalisasi</h5>
            
            <div class="card border-warning mb-4" style="background-color: #fffbf0;">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-1 text-center"><i class="bi bi-question-circle-fill text-warning fs-3"></i></div>
                        <div class="col-md-11">
                            <h6 class="fw-bold text-dark">Mengapa datanya berubah jadi 0 s.d. 1 ?</h6>
                            <p class="small text-secondary mb-0">
                                Komputer tidak bisa menjumlahkan "Rupiah" dengan "Gigabyte" ataupun dengan "Mega Pixel". 
                                Maka, semua angka harus diubah melalui normalisasi sehingga menjadi sama  <strong>diantara 0 s.d. 1</strong>. Untuk atribut cost, maka semakin kecil nilainya akan menjadi semakin besar (Harga lebih murah lebih dipilih). Berkebalikan dengan cost, atribut benefit, semakin besar nilainya maka hasil normalisasi juga semakin besar (RAM lebih besar lebih dipilih). Adapun rumus normalisasi adalah sebagai berikut:
                                <br>
                                <span class="badge bg-success me-1">Benefit</span> Nilai asli dibagi Max (Makin besar makin mendekati 1).<br>
                                <span class="badge bg-danger me-1">Cost</span> Nilai Min dibagi Nilai Asli (Makin kecil harganya, makin mendekati 1 skornya).
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered text-center align-middle">
                    <thead class="bg-light">
                        <tr><th>Alternatif</th><?php foreach($kriteria_nama as $n) echo "<th>$n</th>"; ?></tr>
                        <tr class="table-warning small text-muted"><td>Nilai Max/Min</td><?php foreach($min_max as $m) echo "<td>$m</td>"; ?></tr>
                    </thead>
                    <tbody>
                        <?php foreach($alt_nama as $i => $n): ?>
                        <tr>
                            <td class="text-start fw-bold"><?= $n ?></td>
                            <?php foreach($data_R[$i] as $r): ?>
                            <td>
                                <span class="step-rumus"><?= $r['rumus'] ?></span>
                                <span class="val-num"><?= round($r['val'], 4) ?></span>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <button class="btn btn-dinus btn-sm float-end" onclick="document.querySelector('#sawTab button[data-bs-target=\'#tab3\']').click()">Lanjut Menghitung Skor >></button>
        </div>

        <div class="tab-pane fade" id="tab3">
            <h5 class="text-primary fw-bold"><i class="bi bi-calculator"></i> Perhitungan Skor Akhir</h5>
            
            <div class="card border-info mb-4" style="background-color: #f0f8ff;">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-1 text-center"><i class="bi bi-lightbulb-fill text-info fs-3"></i></div>
                        <div class="col-md-11">
                           <p class="small text-secondary mb-0">
                                Di sini kita akan mengalikan <strong>Skor Normalisasi</strong> dengan <strong>Bobot Prioritas</strong>.
                                <br>Setelah itu jumlahkan hasilnya untuk mendapatkan Total Skor.
                                <br><em><strong>Rumus Total Skor = (Skor Harga × Bobotnya) + (Skor RAM × Bobotnya) + (Skor kamera × Bobotnya)... dst.</strong></em>
                                
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered align-middle text-center table-v">
                    <thead class="table-secondary">
                        <tr>
                            <th>Alternatif</th>
                            <?php foreach($kriteria_nama as $idx=>$n) echo "<th>$n <br><small class='text-muted'>bobot=".round($bobot_norm[$idx],4)."</small></th>"; ?>
                            <th class="bg-primary text-white">Total Skor</th>
                            <th class="bg-dark text-white" style="width: 100px;">Ranking</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($data_V as $row): ?>
                        <tr>
                            <td class="text-start fw-bold"><?= $row['nama'] ?></td>
                            <?php foreach($row['cols'] as $c): ?>
                            <td>
                                <span class="step-rumus"><?= $c['rumus'] ?></span>
                                <span class="text-dark fw-bold"><?= round($c['v'], 4) ?></span>
                            </td>
                            <?php endforeach; ?>
                            <td class="bg-light fw-bold fs-5 text-primary"><?= round($row['total'],5) ?></td>
                            <td class="fw-bold fs-5 text-dark">#<?= $row['rank'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <button class="btn btn-dinus btn-sm float-end" onclick="document.querySelector('#sawTab button[data-bs-target=\'#tab4\']').click()">Lihat Pemenangnya! >></button>
        </div>

        <div class="tab-pane fade" id="tab4">
            <div class="text-center mb-4">
                <h4 class="fw-bold text-primary">Hasil Rekomendasi Akhir</h4>
                <p class="text-muted">Berdasarkan perhitungan SAW, inilah rekomendasi urutan alternatif terbaik untuk:</p>
            </div>

            <div class="row justify-content-center">
                <div class="col-md-8">
                    <table class="table shadow table-hover align-middle">
                        <thead class="bg-primary text-white">
                            <tr>
                                <th class="text-center">Peringkat</th>
                                <th>Nama Kandidat</th>
                                <th class="text-end">Skor Akhir</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $sorted = $data_V; 
                            usort($sorted, fn($a,$b)=>$b['total']<=>$a['total']);
                            foreach($sorted as $r): ?>
                            <tr class="<?=($r['rank']==1)?'table-warning border-warning border-2':''?>">
                                <td class="fw-bold text-center fs-4">
                                    <span class="badge bg-<?=($r['rank']==1)?'warning text-dark':'light text-dark border'?> rounded-circle p-3">
                                        <?= $r['rank'] ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="fw-bold fs-5"><?= $r['nama'] ?></span>
                                    <?php if($r['rank']==1): ?>
                                        <br><small class="text-success fw-bold"><i class="bi bi-check-circle-fill"></i> Sangat Direkomendasikan</small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end fw-bold text-primary fs-4"><?= round($r['total'],5) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card mt-5 border-0 shadow-sm bg-light">
                <div class="card-body text-center p-4">
                    <h5 class="card-title text-secondary">Eksperimen Selesai!</h5>
                    <p class="card-text text-muted mb-4"> Rekomendasi di atas merupakan hasil perhitungan Sistem Pendukung Keputusan menggunakan <strong>metode SAW </strong>, sedangkan keputusan akhir tetap berada di tangan Anda selaku pemegang keputusan. Apakah hasilnya sesuai prediksi Anda? Inilah esensi dari Sistem Pendukung Keputusan yang mencoba memberikan rekomendasi berdasarkan metode dan perhitungan objektif berdasarkan kriteria serta alternatif yang ada.  Apakah hasil rekomendasi akan sama atau berubah jika dilakukan menggunakan metode lain? Coba bandingkan !</p>
                    
                    <div class="d-flex justify-content-center gap-3 flex-wrap">
                        <a href="proses_wp.php" class="btn btn-outline-success px-4 py-2">
                            <strong>Coba Metode WP</strong><br><small>Weighted Product</small>
                        </a>
                        <a href="proses_topsis.php" class="btn btn-outline-danger px-4 py-2">
                            <strong>Coba Metode TOPSIS</strong><br><small> Solusi jarak terbaik dan terburuk</small>
                        </a>
                        <a href="ahp_input_matrix.php" class="btn btn-outline-primary px-4 py-2">
                            <strong>Coba Metode AHP</strong><br><small>Heirarki</small>
                        </a>
                    </div>
                    
                    <hr class="my-4 w-50 mx-auto">
                    
                    <a href="../index.php" class="btn btn-secondary px-5">
                        <i class="bi bi-house-door"></i> Kembali ke Halaman Utama
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>