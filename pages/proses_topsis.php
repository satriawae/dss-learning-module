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

// --- 2. NARASI DINAMIS ---
$slice_kriteria = array_slice($kriteria_nama, 0, 3);
$last_kriteria  = array_pop($slice_kriteria);
if (count($slice_kriteria) > 0) {
    $text_kriteria = implode(', ', $slice_kriteria) . ', dan ' . $last_kriteria;
} else {
    $text_kriteria = $last_kriteria;
}

// --- 3. LOGIKA TOPSIS (BACKEND) ---

// A. Normalisasi Bobot
$total_bobot = array_sum($bobot_raw);
$bobot_norm = [];
foreach($bobot_raw as $b) $bobot_norm[] = $b / $total_bobot;

// B. Hitung Pembagi (Divisor) & Siapkan Tampilan Rumusnya
$pembagi = [];
$pembagi_rumus = []; 

foreach($kriteria_nama as $j => $kn) {
    $sum_sq = 0;
    $temp_rumus_parts = [];
    foreach($alt_nilai as $i => $vals) {
        $val = $vals[$j];
        $sum_sq += pow($val, 2); 
        // Ambil max 3 item untuk display rumus pembagi (sum ke bawah)
        if ($i < 3) {
            $temp_rumus_parts[] = "({$val}²)";
        }
    }
    $str_rumus = implode("+", $temp_rumus_parts);
    if (count($alt_nilai) > 3) $str_rumus .= "+...";
    
    $pembagi[$j] = sqrt($sum_sq); 
    $pembagi_rumus[$j] = "√(" . $str_rumus . ")"; 
}

// C. Matriks Normalisasi (R)
$matriks_R = [];
foreach($alt_nilai as $i => $row) {
    foreach($row as $j => $val) {
        $matriks_R[$i][$j] = $val / $pembagi[$j];
    }
}

// D. Matriks Terbobot (Y)
$matriks_Y = [];
foreach($matriks_R as $i => $row) {
    foreach($row as $j => $val) {
        $matriks_Y[$i][$j] = $val * $bobot_norm[$j];
    }
}

// E. Solusi Ideal (A+ dan A-)
$A_plus = [];
$A_min  = [];
foreach($kriteria_nama as $j => $kn) {
    $col_Y = array_column($matriks_Y, $j);
    if($kriteria_sifat[$j] == 'benefit') {
        $A_plus[$j] = max($col_Y);
        $A_min[$j]  = min($col_Y);
    } else {
        $A_plus[$j] = min($col_Y);
        $A_min[$j]  = max($col_Y);
    }
}

// F. Jarak Solusi (D+ dan D-) & Tampilan Rumus Intuitif
$jarak_plus = [];
$jarak_min  = [];
$rumus_jarak_plus = [];
$rumus_jarak_min  = [];

foreach($matriks_Y as $i => $row) {
    $sum_plus = 0;
    $sum_min  = 0;
    
    // Array penampung teks rumus (untuk max 3 kriteria)
    $parts_plus = [];
    $parts_min  = [];

    foreach($row as $j => $y) {
        // Hitung Jarak
        $sum_plus += pow($y - $A_plus[$j], 2);
        $sum_min  += pow($y - $A_min[$j], 2);
        
        // Susun Rumus Teks (Hanya ambil 3 kriteria pertama)
        if ($j < 3) {
            $y_disp = round($y, 4);
            $ap_disp = round($A_plus[$j], 4);
            $am_disp = round($A_min[$j], 4);
            
           // $parts_plus[] = "({$y_disp}-{$ap_disp})²";
           // $parts_min[]  = "({$y_disp}-{$am_disp})²";
            $parts_plus[] = "({$ap_disp}-{$y_disp})²";
            $parts_min[]  = "({$y_disp}-{$am_disp})²";
        }
    }
    
    $jarak_plus[$i] = sqrt($sum_plus);
    $jarak_min[$i]  = sqrt($sum_min);

    // Gabungkan teks rumus
    $str_p = implode(" + ", $parts_plus);
    $str_m = implode(" + ", $parts_min);
    
    // Jika kriteria lebih dari 3, beri tanda ...
    if (count($kriteria_nama) > 3) {
        $str_p .= " + ...";
        $str_m .= " + ...";
    }

    $rumus_jarak_plus[$i] = "√(" . $str_p . ")";
    $rumus_jarak_min[$i]  = "√(" . $str_m . ")";
}

// G. Nilai Preferensi (V)
$nilai_V = [];
$final_scores = [];
foreach($alt_nama as $i => $nama) {
    $d_min = $jarak_min[$i];
    $d_plus = $jarak_plus[$i];
    $pembagi_v = ($d_min + $d_plus);
    $v = ($pembagi_v != 0) ? ($d_min / $pembagi_v) : 0;
    
    $nilai_V[$i] = [
        'nama' => $nama,
        'd_plus' => $d_plus,
        'd_min'  => $d_min,
        'v_val'  => $v
    ];
    $final_scores[$i] = $v;
}

// H. Ranking
$sorted_scores = $final_scores;
rsort($sorted_scores);
foreach($nilai_V as $k => $v) {
    $nilai_V[$k]['rank'] = array_search($v['v_val'], $sorted_scores) + 1;
}







?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Hasil Perhitungan TOPSIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/mystyle.css">
</head>
<body>
<nav class="navbar navbar-dark navbar-dinus mb-4 shadow-sm">
    <div class="container">
        <span class="navbar-brand">Hasil Analisa Metode TOPSIS</span>
    </div>
</nav>

<div class="container mb-5">
    
    <ul class="nav nav-tabs nav-fill shadow-sm rounded-top" id="topsisTab" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab1">1. Data Awal</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab2">2. Normalisasi (R)</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab3">3. Matriks Terbobot (Y)</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab4">4. Hasil (V)</button></li>
        <li class="nav-item"><button class="nav-link fw-bold text-primary bg-light" data-bs-toggle="tab" data-bs-target="#tab5">5. Rekomendasi</button></li>
    </ul>

    <div class="tab-content bg-white p-4 shadow-sm border border-top-0 rounded-bottom">
        
        <div class="tab-pane fade show active" id="tab1">
            <h5 class="text-primary fw-bold mb-3">Data Awal & Pencarian Pembagi</h5>
            <div class="alert alert-light border-start border-5 border-primary">
                <p class="mb-0 small text-muted">Berikut adalah data kriteria, bobot beserta alternatif awal yang telah Anda input. Pada metode TOPSIS dicari <strong>'Akar Kuadrat Total'</strong> dari setiap kolom untuk dijadikan pembagi yang nantinya akan digunakan untuk penyetaraan/normalisasi. Setiap nilai dikuadratkan dan dijumlahkan, kemudian dicari akar kuadratnya. Hal ini sesuai dengan prinsip jarak/ sisi miring segitiga dari rumus Pythagoras, dimana jarak terdekat/ sisi miring segitiga adalah akar dari jumlah kuadrat tiap sisi yang saling tegak lurus.</p>
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



            <div class="table-responsive">
                <table class="table table-bordered text-center align-middle">
                    <thead class="table-dark">
                        <tr><th>Alternatif</th><?php foreach($kriteria_nama as $n) echo "<th>$n</th>"; ?></tr>
                    </thead>
                    <tbody>
                        <?php foreach($alt_nama as $i => $n): ?>
                        <tr>
                            <td class="text-start fw-bold"><?= $n ?></td>
                            <?php foreach($alt_nilai[$i] as $val) echo "<td>$val</td>"; ?>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="table-warning border-top border-3">
                            <td class="text-end fw-bold text-dark fst-italic align-middle">Pembagi (Divisor) <br> <small class="fw-normal">Rumus: √(x² + ...)</small></td>
                            <?php foreach($pembagi as $idx => $p): ?>
                            <td class="position-relative">
                                <div class="text-muted small fst-italic mb-1 border-bottom pb-1" style="font-size: 0.75rem; white-space: nowrap;"><?= $pembagi_rumus[$idx] ?></div>
                                <div class="fw-bold text-danger fs-5"><?= round($p, 4) ?></div>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="alert alert-warning small mt-3"><i class="bi bi-info-circle-fill me-2"></i> <strong>Info:</strong> Jika data alternatif Anda banyak, rumus di baris kuning hanya menampilkan 3 angka pertama agar tabel tetap rapi.</div>
            <button class="btn btn-dinus btn-sm float-end" onclick="document.querySelector('#topsisTab button[data-bs-target=\'#tab2\']').click()">Lanjut ke normalisasi >></button>
        </div>

        <div class="tab-pane fade" id="tab2">
            <h5 class="text-primary fw-bold"><i class="bi bi-grid-3x3"></i> Matriks Normalisasi (R)</h5>
            <div class="card border-info mb-3" style="background-color: #f0f8ff;">
                <div class="card-body">
                    
                    <p class="small text-secondary mb-0">Matriks normalisasi didapatkan dengan membagi tiap nilai pada suatu kriteria dengan pembagi yang didapatkan dari rumus sebelumnya (lihat pada tab data awal). 
                        <br> Nilai normalisasi Rumusnya = <strong>Nilai Asli</strong> ÷ <strong>Pembagi</strong>.</p>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered text-center align-middle">
                    <thead class="table-secondary">
                        <tr><th>Alternatif</th><?php foreach($kriteria_nama as $n) echo "<th>$n</th>"; ?></tr>
                    </thead>
                    <tbody>
                        <?php foreach($alt_nama as $i => $n): ?>
                        <tr>
                            <td class="text-start fw-bold"><?= $n ?></td>
                            <?php foreach($matriks_R[$i] as $j => $val): ?>
                            <td>
                                <div class="text-muted fst-italic border-bottom pb-1 mb-1" style="font-size: 0.7rem;"><?= $alt_nilai[$i][$j] ?> ÷ <?= round($pembagi[$j], 4) ?></div>
                                <span class="fw-bold text-dark"><?= round($val, 4) ?></span>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <button class="btn btn-dinus btn-sm float-end mt-3" onclick="document.querySelector('#topsisTab button[data-bs-target=\'#tab3\']').click()">Lanjut ke Matriks Terbobot >></button>
        </div>

        <div class="tab-pane fade" id="tab3">
            <h5 class="text-primary fw-bold"><i class="bi bi-bullseye"></i> Matriks Terbobot & Solusi Ideal</h5>
            
            <div class="card bg-light border-0 mb-3">
                <div class="card-body">
                    <p class="small text-secondary mb-0">
                        Untuk mendapatkan matriks ternormalisasi, setiap nilai normalisasi (Tab 2) dikalikan dengan Bobot Prioritas kriteria.
                        <br>
                        <em>Rumus = R × Bobot = Y</em>
                        <br> Setiap kriteria memiliki 2 (dua) nilai yaitu : ideal positif (A+) dan ideal negatif (A-).  
                        <br> PAda kriteria dengan atribut benefit, (A+) merupakan nilai tertinggi dari tiap kriteria. sedangkan (A-) merupakan nilai terendah dari tiap kriteria.
                        <br> PAda kriteria dengan atribut cost, (A+) merupakan nilai terendah dari tiap kriteria. sedangkan (A-) merupakan nilai tertinggi dari tiap kriteria.
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered text-center align-middle">
                    <thead class="table-secondary">
                        <tr><th>Alternatif</th><?php foreach($kriteria_nama as $idx=>$n) echo "<th>$n <br>

                        <small class='text-muted fw-normal'>W=".round($bobot_norm[$idx],2)."</small></th>"; ?></tr>
                    </thead>
                    <tbody>
                        <?php foreach($alt_nama as $i => $n): ?>
                        <tr>
                            <td class="text-start fw-bold"><?= $n ?></td>
                            <?php foreach($matriks_Y[$i] as $j => $val): ?>
                            <td>
                                <div class="text-muted fst-italic border-bottom pb-1 mb-1" style="font-size: 0.7rem;">
                                    <?= round($matriks_R[$i][$j], 4) ?> × <?= round($bobot_norm[$j], 2) ?>
                                </div>
                                <span class="fw-bold text-dark"><?= round($val, 4) ?></span>

                            </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="border-top border-3">
                        <tr class="table-success fw-bold">
                            <td class="text-start"><i class="bi bi-plus-circle"></i> A+ (Ideal positif)</td>
                            <?php foreach($A_plus as $ap) echo "<td class='text-success'>".round($ap, 4)."</td>"; ?>
                        </tr>
                        <tr class="table-danger fw-bold">
                            <td class="text-start"><i class="bi bi-dash-circle"></i> A- (Ideal negatif)</td>
                            <?php foreach($A_min as $am) echo "<td class='text-danger'>".round($am, 4)."</td>"; ?>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <button class="btn btn-dinus btn-sm float-end mt-3" onclick="document.querySelector('#topsisTab button[data-bs-target=\'#tab4\']').click()">Hitung Jarak & Hasil >></button>
        </div>

        <div class="tab-pane fade" id="tab4">
            <h5 class="text-primary fw-bold"><i class="bi bi-rulers"></i> Mengukur Jarak & Skor Akhir</h5>
            
            <div class="card border-info mb-4" style="background-color: #f0f8ff;">
                <div class="card-body">
                    <h6 class="fw-bold text-dark">Logika Perhitungan Jarak</h6>
                    <p class="small text-secondary mb-0">
                        Jarak dihitung menggunakan rumus Pythagoras (Akar dari total selisih kuadrat).
                        <br>
                        <em>Perhatikan rumus kecil di dalam sel.</em>
                        <br>Jarak ke solusi ideal positif  = Akar kuadrat dari Jumlah dari (Nilai A+ - Nilai Y)². 
                        <br>Jarak ke solusi ideal negatif  = Akar kuadrat dari Jumlah dari (Nilai Y - Nilai A-)². 
                        <br>Nilai preferensi  = (D-) ÷ ( (D+) + (D-) ) 
                    </p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered align-middle text-center table-v">
                    <thead class="table-secondary">
                        <tr>
                            <th>Alternatif</th>
                            <th>Jarak ke Solusi Ideal Positif (D+)</th>
                            <th>Jarak ke Solusi Ideal Negatif (D-)</th>
                            <th class="bg-primary text-white" style="width: 300px;">Nilai Preferensi (V)</th>
                            <th class="bg-dark text-white" style="width: 100px;">Ranking</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($nilai_V as $i => $row): ?>
                        <tr>
                            <td class="text-start fw-bold"><?= $row['nama'] ?></td>
                            
                            <td class="position-relative">
                                <div class="text-muted fst-italic border-bottom pb-1 mb-1" 
                                     style="font-size:0.65rem; white-space: normal; min-width: 150px;">
                                    <?= $rumus_jarak_plus[$i] ?>
                                </div>
                                <div class="fw-bold text-success"><?= round($row['d_plus'], 4) ?></div>
                            </td>
                            
                            <td class="position-relative">
                                <div class="text-muted fst-italic border-bottom pb-1 mb-1" 
                                     style="font-size:0.65rem; white-space: normal; min-width: 150px;">
                                    <?= $rumus_jarak_min[$i] ?>
                                </div>
                                <div class="fw-bold text-danger"><?= round($row['d_min'], 4) ?></div>
                            </td>
                            
                            <td class="bg-light text-primary position-relative p-2">
                                <div class="text-muted fst-italic" style="font-size: 0.7rem;">
                                    D<sup class="text-danger">-</sup> ÷ Total
                                </div>
                                <div class="text-secondary border-bottom border-secondary mb-1 pb-1" style="font-size: 0.8rem;">
                                    <span class="text-danger fw-bold"><?= round($row['d_min'], 4) ?></span> 
                                    ÷ 
                                    ( <span class="text-danger"><?= round($row['d_min'], 4) ?></span> + <span class="text-success"><?= round($row['d_plus'], 4) ?></span> )
                                </div>
                                <div class="fw-bold fs-4 text-primary">
                                    <?= round($row['v_val'], 5) ?>
                                </div>
                            </td>
                            
                            <td class="fw-bold fs-5 text-dark">#<?= $row['rank'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <button class="btn btn-dinus btn-sm float-end mt-3" onclick="document.querySelector('#topsisTab button[data-bs-target=\'#tab5\']').click()">Lihat Rekomendasi >></button>
        </div>

        <div class="tab-pane fade" id="tab5">
            <div class="text-center mb-4">
                <h4 class="fw-bold text-primary">Hasil Akhir (Metode TOPSIS)</h4>
                <p class="text-muted">Kandidat dengan nilai tertinggi adalah yang paling mendekati kriteria idaman:</p>
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
                            $sorted = $nilai_V; 
                            usort($sorted, fn($a,$b)=>$b['v_val']<=>$a['v_val']);
                            foreach($sorted as $r): ?>
                            <tr class="<?=($r['rank']==1)?'table-warning border-warning border-2':''?>">
                                <td class="fw-bold text-center fs-4"><span class="badge bg-<?=($r['rank']==1)?'warning text-dark':'light text-dark border'?> rounded-circle p-3"><?= $r['rank'] ?></span></td>
                                <td><span class="fw-bold fs-5"><?= $r['nama'] ?></span><?php if($r['rank']==1): ?><br><small class="text-success fw-bold"><i class="bi bi-check-circle-fill"></i> Sangat Direkomendasikan</small><?php endif; ?></td>
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
                    <p class="card-text text-muted mb-4"> Rekomendasi di atas merupakan hasil perhitungan Sistem Pendukung Keputusan menggunakan <strong>metode TOPSIS </strong>, sedangkan keputusan akhir tetap berada di tangan Anda selaku pemegang keputusan. Apakah hasilnya sesuai prediksi Anda? Inilah esensi dari Sistem Pendukung Keputusan yang mencoba memberikan rekomendasi berdasarkan metode dan perhitungan objektif berdasarkan kriteria serta alternatif yang ada.  Apakah hasil rekomendasi akan sama atau berubah jika dilakukan menggunakan metode lain? Coba bandingkan !</p>
                    <div class="d-flex justify-content-center gap-3 flex-wrap">
                        <a href="proses_saw.php" class="btn btn-outline-success px-4 py-2">
                            <strong>Coba Metode SAW</strong><br><small>penjumlahan terbobot</small>
                        </a>
                        <a href="proses_wp.php" class="btn btn-outline-danger px-4 py-2">
                            <strong>Coba Metode WP</strong><br><small>Weighted Product</small>
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