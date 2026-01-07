<?php
session_start();
include '../includes/header.php';
include '../includes/navbar.php';
// --- 1. HANDSHAKE: TANGKAP DATA ---
if (isset($_POST['simpan_data'])) {
    $_SESSION['bobot']          = $_POST['bobot'];
    $_SESSION['kriteria_nama']  = $_POST['kriteria_nama'];
    $_SESSION['kriteria_sifat'] = $_POST['kriteria_sifat'];
    $_SESSION['alt_nama']       = $_POST['alt_nama'];
    $_SESSION['alt_nilai']      = $_POST['alt_nilai'];
}

if (!isset($_SESSION['alt_nilai'])) { header("Location: ../index.php"); exit(); }

$kriteria_nama  = $_SESSION['kriteria_nama'];
$kriteria_sifat = $_SESSION['kriteria_sifat'];
$bobot_raw      = $_SESSION['bobot'];
$alt_nama       = $_SESSION['alt_nama'];
$alt_nilai      = $_SESSION['alt_nilai'];

// --- 2. PERSIAPAN VARIABEL NARASI DINAMIS ---
// A. Untuk Tab 1 (Footer "Satuan Beragam")
$slice_kriteria = array_slice($kriteria_nama, 0, 3);
$last_kriteria  = array_pop($slice_kriteria);
if (count($slice_kriteria) > 0) {
    $text_ragam_kriteria = implode(', ', $slice_kriteria) . ', dan ' . $last_kriteria;
} else {
    $text_ragam_kriteria = $last_kriteria;
}

// B. Untuk Tab 2 (Footer "Angka Aneh/Besar")
$contoh_k1 = $kriteria_nama[0] ?? 'Kriteria 1';
$contoh_k2 = $kriteria_nama[1] ?? 'Kriteria 2';


// --- 3. LOGIKA WP ---

// A. Perbaikan Bobot (Total harus 1)
$total_bobot_raw = array_sum($bobot_raw);
$bobot_norm = [];
foreach($bobot_raw as $b) {
    $bobot_norm[] = $b / $total_bobot_raw;
}

// B. Perhitungan Vektor S
$vektor_S = [];
$rincian_S = []; 

foreach($alt_nama as $i => $nama) {
    $hasil_kali = 1;
    $step_rumus = [];
    
    foreach($alt_nilai[$i] as $j => $nilai) {
        // Cek Sifat
        $pangkat = $bobot_norm[$j];
        if($kriteria_sifat[$j] == 'cost') {
            $pangkat = $pangkat * -1; // Cost = Pangkat Negatif
        }
        
        // Hitung
        $nilai_pangkat = pow($nilai, $pangkat);
        $hasil_kali *= $nilai_pangkat;
        
        // Text rumus display
        $step_rumus[] = "($nilai<sup>".round($pangkat,2)."</sup>)";
    }
    
    $vektor_S[$i] = $hasil_kali;
    $rincian_S[$i] = [
        'nama' => $nama,
        'rumus' => implode(" × ", $step_rumus),
        'hasil' => $hasil_kali
    ];
}

// C. Perhitungan Vektor V (Normalisasi Akhir)
$total_S = array_sum($vektor_S);
$vektor_V = [];
$final_scores = [];

foreach($alt_nama as $i => $nama) {
    // Hindari division by zero
    $v = ($total_S != 0) ? ($vektor_S[$i] / $total_S) : 0;
    
    $vektor_V[$i] = [
        'nama' => $nama,
        's_val' => $vektor_S[$i],
        'v_val' => $v
    ];
    $final_scores[$i] = $v;
}

// D. Ranking
$sorted_scores = $final_scores;
rsort($sorted_scores);
foreach($vektor_V as $k => $v) {
    $vektor_V[$k]['rank'] = array_search($v['v_val'], $sorted_scores) + 1;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Hasil Perhitungan WP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/mystyle.css">
</head>
<body>
<nav class="navbar navbar-dark navbar-dinus mb-4 shadow-sm">
    <div class="container">
        <span class="navbar-brand">Hasil Analisa Metode WP</span>
    </div>
</nav>

<div class="container mb-5">
    
    <ul class="nav nav-tabs nav-fill shadow-sm rounded-top" id="wpTab" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab1">1. Data Awal</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab2">2. Vektor S (Perpangkatan)</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab3">3. Vektor V (Hasil)</button></li>
        <li class="nav-item"><button class="nav-link fw-bold text-primary bg-light" data-bs-toggle="tab" data-bs-target="#tab4">4. Hasil Rekomendasi</button></li>
    </ul>

    <div class="tab-content bg-white p-4 shadow-sm border border-top-0 rounded-bottom">
        
        <div class="tab-pane fade show active" id="tab1">
            <h5 class="text-primary fw-bold mb-3">Data yang Anda Masukkan</h5>
            
            <div class="alert alert-light border-start border-5 border-primary">
                <p class="mb-0 small text-muted">
                    Berikut adalah data kriteria, bobot beserta alternatif awal yang telah Anda input. Metode WP tidak melakukan normalisasi seperti halnya SAW. Metode WP akan membentuk vektor melalui  perkalian tiap kriteria yang dipangkatkan dengan bobotnya.  
                    <br>Atribut benefit memiliki bobot positif, sedangkan atribut cost  memiliki bobot negatif.
                    <br>Untuk lebih jelasnya dapat dilihat pada hasil vektor S
                </p>
            </div>
            
            <div class="row mb-3">
                <?php foreach($kriteria_nama as $i => $n): ?>
                <div class="col-md-4 mb-2">
                    <div class="border p-2 rounded bg-light text-center h-100">
                        <strong class="d-block text-primary"><?= $n ?></strong>
                        <div class="d-flex justify-content-center gap-2 mt-1">
                            <span class="badge bg-dark">Bobot: <?= $bobot_raw[$i] ?></span>
                            <span class="badge bg-secondary">
                                Pangkat: <?= ($kriteria_sifat[$i]=='cost') ? -1*round($bobot_norm[$i],2) : round($bobot_norm[$i],2) ?>
                            </span>
                            <span class="badge bg-<?= ($kriteria_sifat[$i]=='benefit')?'success':'danger' ?>"><?= ucfirst($kriteria_sifat[$i]) ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

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

            <div class="alert alert-warning small mt-3">
                <i class="bi bi-info-circle-fill me-2"></i> 
                <strong>Perhatikan:</strong> Satuan data di tabel atas masih beragam (ada <strong><?= $text_ragam_kriteria ?></strong>, dll). 
                Metode WP akan menanganinya lewat perpangkatan dan perkalian.
            </div>

            <button class="btn btn-dinus btn-sm float-end" onclick="document.querySelector('#wpTab button[data-bs-target=\'#tab2\']').click()">Lihat Vektor S >></button>
        </div>

        <div class="tab-pane fade" id="tab2">
            <h5 class="text-primary fw-bold"><i class="bi bi-lightning-charge"></i> Menghitung Vektor S</h5>
            
            <div class="card bg-light border-0 mb-3">
                <div class="card-body">
                    <h6 class="fw-bold text-dark">Konsep Weighted Product</h6>
                    <p class="small text-secondary mb-0">
                        Nilai setiap kriteria dipangkatkan dengan bobotnya.
                        <br>
                        <span class="badge bg-success">Benefit</span> Pangkat Positif (+).
                        <span class="badge bg-danger">Cost</span> Pangkat Negatif (-).
                    </p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead class="bg-light text-center">
                        <tr>
                            <th>Alternatif</th>
                            <th>Rumus (Nilai <sup>Pangkat</sup>)</th>
                            <th>Nilai S</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($rincian_S as $row): ?>
                        <tr>
                            <td class="fw-bold"><?= $row['nama'] ?></td>
                            <td class="text-muted small fst-italic text-center">
                                <?= $row['rumus'] ?>
                            </td>
                            <td class="text-center fw-bold text-primary">
                                <?= round($row['hasil'], 5) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="table-warning fw-bold">
                            <td colspan="2" class="text-end">Total Nilai S (∑S):</td>
                            <td class="text-center"><?= round($total_S, 5) ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="card border-danger mt-3" style="background-color: #fff5f5;">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-1 text-center"><i class="bi bi-exclamation-triangle-fill text-danger fs-3"></i></div>
                        <div class="col-md-11">
                            <h6 class="fw-bold text-danger">Kok angkanya begitu?</h6>
                            <p class="small text-secondary mb-0">
                                Metode WP tidak melakukan normalisasi angka di awal seperti metode SAW.<br>
                                WP langsung mengalikan <strong><?= $contoh_k1 ?></strong> dipangkat bobot dengan <strong><?= $contoh_k2 ?></strong> dipangkat bobot ..dst.<br>
                                Akibatnya, hasilnya (Nilai S) disebut vektor yang memiliki satuan "campur aduk / beragam".
                                Normalisasi baru akan dilakukan di Tahap 3  (Vektor V).
                                <br> <strong>Perhatikan bagiamana Total Nilai S (∑S) digunakan untuk menormalisasi vektor!</strong> 
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <button class="btn btn-dinus btn-sm float-end mt-3" onclick="document.querySelector('#wpTab button[data-bs-target=\'#tab3\']').click()">Lihat Vektor V >></button>
        </div>

        <div class="tab-pane fade" id="tab3">
            <h5 class="text-primary fw-bold"><i class="bi bi-calculator"></i> Perhitungan Vektor V (Normalisasi Akhir)</h5>
            
            <div class="card border-info mb-4" style="background-color: #f0f8ff;">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-1 text-center"><i class="bi bi-lightbulb-fill text-info fs-3"></i></div>
                        <div class="col-md-11">
                            <p class="small text-secondary mb-0">
                                Nilai S dari perhitungan vektor V, sekarang dibagi dengan <strong>Total S (<?= round($total_S, 5) ?>)</strong>.
                                <br>
                                Hasilnya adalah skor 0 sampai 1 yang rapi dan siap diranking. Hal ini setara dengan normalisasi pada metode SAW.
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
                            <th>Nilai S</th>
                            <th class="bg-primary text-white">Nilai V (Skor Akhir)</th>
                            <th class="bg-dark text-white" style="width: 100px;">Ranking</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($vektor_V as $row): ?>
                        <tr>
                            <td class="text-start fw-bold"><?= $row['nama'] ?></td>
                            <td class="text-danger small fw-bold"><?= round($row['s_val'], 5) ?></td>
                            
                            <td class="bg-light text-primary position-relative">
                                <div class="d-block text-secondary fst-italic mb-1 border-bottom pb-1" style="font-size: 0.8rem;">
                                    <?= round($row['s_val'], 5) ?> ÷ <?= round($total_S, 5) ?>
                                </div>
                                <div class="fw-bold fs-5">
                                    <?= round($row['v_val'], 5) ?>
                                </div>
                            </td>
                            
                            <td class="fw-bold fs-5 text-dark">#<?= $row['rank'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <button class="btn btn-dinus btn-sm float-end" onclick="document.querySelector('#wpTab button[data-bs-target=\'#tab4\']').click()">Lihat Rekomendasi! >></button>
        </div>

        <div class="tab-pane fade" id="tab4">
            <div class="text-center mb-4">
                <h4 class="fw-bold text-primary">Hasil Akhir (Metode WP)</h4>
                <p class="text-muted">Berdasarkan perhitungan WP, inilah rekomendasi urutan alternatif terbaik untuk:</p>
            </div>

            <div class="row justify-content-center">
                <div class="col-md-8">
                    <table class="table shadow table-hover align-middle">
                        <thead class="bg-primary text-white">
                            <tr>
                                <th class="text-center">Peringkat</th>
                                <th>Nama Kandidat</th>
                                <th class="text-end">Skor Akhir (V)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $sorted = $vektor_V; 
                            usort($sorted, fn($a,$b)=>$b['v_val']<=>$a['v_val']);
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
                                        <br><small class="text-success fw-bold"><i class="bi bi-check-circle-fill"></i> Paling Direkomendasikan</small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end fw-bold text-primary fs-4"><?= round($r['v_val'],5) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card mt-5 border-0 shadow-sm bg-light">
                <div class="card-body text-center p-4">
                    <h5 class="card-title text-secondary">Eksperimen Selesai!</h5>
                    <p class="card-text text-muted mb-4"> Rekomendasi di atas merupakan hasil perhitungan Sistem Pendukung Keputusan menggunakan <strong>metode WP </strong>, sedangkan keputusan akhir tetap berada di tangan Anda selaku pemegang keputusan. Apakah hasilnya sesuai prediksi Anda? Inilah esensi dari Sistem Pendukung Keputusan yang mencoba memberikan rekomendasi berdasarkan metode dan perhitungan objektif berdasarkan kriteria serta alternatif yang ada.  Apakah hasil rekomendasi akan sama atau berubah jika dilakukan menggunakan metode lain? Coba bandingkan !</p>
                    <div class="d-flex justify-content-center gap-3 flex-wrap">
                        <a href="proses_saw.php" class="btn btn-outline-success px-4 py-2">
                            <strong>Coba Metode SAW</strong><br><small>penjumlahan terbobot</small>
                        </a>
                        <a href="proses_topsis.php" class="btn btn-outline-danger px-4 py-2">
                            <strong>Coba Metode TOPSIS</strong><br><small> Solusi jarak terbaik dan terburuk</small>
                        </a>
                        <a href="ahp_input_matrix.php" class="btn btn-outline-primary px-4 py-2">
                            <strong>Coba Metode AHP</strong><br><small>Heirarki</small>
                        </a>
                    </div>
                    <hr class="my-4 w-50 mx-auto">
                    <a href="../index.php" class="btn btn-secondary px-5">Kembali ke Halaman Utama</a>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>