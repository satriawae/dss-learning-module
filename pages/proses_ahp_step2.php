<?php
session_start();
include '../includes/header.php';
include '../includes/navbar.php';

// --- 1. VALIDASI & HANDSHAKE ---
if (!isset($_POST['hitung_ahp']) && !isset($_SESSION['alt_nilai'])) { 
    header("Location: ahp_input_matrix.php"); exit(); 
}

// Ambil Data Session
$kriteria_nama  = $_SESSION['kriteria_nama'];
$kriteria_sifat = $_SESSION['kriteria_sifat'];
$alt_nama       = $_SESSION['alt_nama'];
$alt_nilai      = $_SESSION['alt_nilai'];
$n = count($kriteria_nama);

// ==========================================
// BAGIAN A: LOGIKA MATEMATIKA (BACKEND)
// ==========================================

// 1. Bangun Matriks Perbandingan (Input User)
$matriks = [];
for($i=0; $i<$n; $i++) {
    for($j=0; $j<$n; $j++) { $matriks[$i][$j] = 1; }
}

if(isset($_POST['hitung_ahp'])){
    for ($i = 0; $i < $n; $i++) {
        for ($j = $i + 1; $j < $n; $j++) {
            $input_name = "pair_" . $i . "_" . $j;
            $raw_val = $_POST[$input_name] ?? "1"; 
            if($raw_val != "1") {
                $parts = explode('_', $raw_val);
                $nilai = floatval($parts[0]);
                $arah  = $parts[1];
                if($arah == 'left') { $matriks[$i][$j] = $nilai; $matriks[$j][$i] = 1 / $nilai; } 
                else { $matriks[$i][$j] = 1 / $nilai; $matriks[$j][$i] = $nilai; }
            }
        }
    }
}

// 2. Hitung Total Kolom
$jumlah_kolom = array_fill(0, $n, 0);
for($j=0; $j<$n; $j++) {
    for($i=0; $i<$n; $i++) { $jumlah_kolom[$j] += $matriks[$i][$j]; }
}

// 3. Normalisasi Matriks & Hitung Bobot
$matriks_norm = [];
$bobot_kriteria = [];
for($i=0; $i<$n; $i++) {
    $sum_baris = 0;
    for($j=0; $j<$n; $j++) {
        $matriks_norm[$i][$j] = $matriks[$i][$j] / $jumlah_kolom[$j];
        $sum_baris += $matriks_norm[$i][$j];
    }
    $bobot_kriteria[$i] = $sum_baris / $n;
}

// 4. Hitung CR (Consistency Ratio)
$lambda_max = 0;
$lambda_rumus = []; 
for($j=0; $j<$n; $j++) { 
    $val = $jumlah_kolom[$j] * $bobot_kriteria[$j];
    $lambda_max += $val;
    $lambda_rumus[] = "(".round($jumlah_kolom[$j],2)." x ".round($bobot_kriteria[$j],3).")";
}
$CI = ($lambda_max - $n) / ($n - 1);
$RI_list = [0, 0, 0.58, 0.90, 1.12, 1.24, 1.32, 1.41, 1.45, 1.49];
$RI = $RI_list[$n-1] ?? 1.49;
$CR = ($RI == 0) ? 0 : $CI / $RI;

// 5. Normalisasi Alternatif (Absolute Measurement)
$prioritas_alt = [];
$total_per_kriteria_alt = [];

// Hitung Pembagi (Total Kolom Alternatif)
for($j=0; $j<$n; $j++) {
    $sum = 0;
    $is_cost = ($kriteria_sifat[$j] == 'cost');
    foreach($alt_nilai as $row) {
        $val = floatval($row[$j]);
        if($val == 0) $val = 0.000001; 
        $sum += $is_cost ? (1/$val) : $val;
    }
    $total_per_kriteria_alt[$j] = $sum;
}

// Hitung Nilai Ternormalisasi
foreach($alt_nama as $i => $nama) {
    for($j=0; $j<$n; $j++) {
        $val = floatval($alt_nilai[$i][$j]);
        if($val == 0) $val = 0.000001;
        $is_cost = ($kriteria_sifat[$j] == 'cost');
        $denom = $total_per_kriteria_alt[$j];
        $prioritas_alt[$i][$j] = $is_cost ? ((1/$val)/$denom) : ($val/$denom);
    }
}

// 6. Ranking Akhir
$nilai_akhir_unsorted = [];
foreach($alt_nama as $i => $nama) {
    $skor = 0;
    for($j=0; $j<$n; $j++) {
        $skor += $prioritas_alt[$i][$j] * $bobot_kriteria[$j];
    }
    $nilai_akhir_unsorted[$i] = $skor;
}

// Sorting & Final Array
$nilai_akhir_sorted = [];
foreach($alt_nama as $i => $nama) {
    $nilai_akhir_sorted[] = [
        'id_asli' => $i,
        'nama' => $nama,
        'nilai' => $nilai_akhir_unsorted[$i],
        'rank' => 0
    ];
}
usort($nilai_akhir_sorted, function($a, $b) { return $b['nilai'] <=> $a['nilai']; });
foreach($nilai_akhir_sorted as $k => $v) { $nilai_akhir_sorted[$k]['rank'] = $k + 1; }

function getRank($id, $sorted_array) {
    foreach($sorted_array as $item) {
        if($item['id_asli'] == $id) return $item['rank'];
    }
    return 0;
}
?>

<style>
    .calc-cell { padding: 8px; vertical-align: middle; position: relative; }
    .formula-small { display: block; font-size: 0.65rem; color: #6c757d; margin-bottom: 2px; font-family: 'Courier New', monospace; white-space: nowrap; }
    .result-big { display: block; font-size: 1.1rem; font-weight: bold; color: #0d6efd; }
    .result-big-final { font-size: 1.25rem; font-weight: 800; color: #198754; }
    .nav-tabs .nav-link.active { background-color: #e7f1ff; border-color: #dee2e6 #dee2e6 #fff; color: #0d6efd; }
    .badge-rank { font-size: 1.2rem; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; border-radius: 50%; }
</style>

<div class="container mt-4 mb-5">
    <div class="text-center mb-4">
        <h2 class="fw-bold text-primary">Modul Pembelajaran AHP</h2>
        <p class="text-muted">Analytic Hierarchy Process - Bedah Perhitungan</p>
    </div>

    <ul class="nav nav-tabs nav-fill" id="myTab" role="tablist">
        <li class="nav-item"><button class="nav-link active fw-bold" data-bs-toggle="tab" data-bs-target="#tab1">1. Data & Konsistensi</button></li>
        <li class="nav-item"><button class="nav-link fw-bold" data-bs-toggle="tab" data-bs-target="#tab2">2. Normalisasi Bobot</button></li>
        <li class="nav-item"><button class="nav-link fw-bold" data-bs-toggle="tab" data-bs-target="#tab3">3. Perhitungan Skor</button></li>
        <li class="nav-item"><button class="nav-link fw-bold text-success" data-bs-toggle="tab" data-bs-target="#tab4">4. Hasil Rekomendasi</button></li>
    </ul>

    <div class="tab-content p-4 border border-top-0 bg-white shadow-sm">

        <div class="tab-pane fade show active" id="tab1">
            <div class="alert alert-info small">
                <i class="bi bi-info-circle-fill"></i> <strong>Langkah Awal:</strong> Berikut adalah matriks perbandingan kriteria dan data alternatif yang tersimpan. Di bawah juga ditampilkan perhitungan validitas (CR).
            </div>

            <h6 class="fw-bold text-dark mt-3">A. Matriks Perbandingan Berpasangan (Input User)</h6>
            <div class="table-responsive">
                <table class="table table-bordered text-center align-middle">
                    <thead class="table-secondary">
                        <tr><th>Kriteria</th><?php foreach($kriteria_nama as $k) echo "<th>$k</th>"; ?></tr>
                    </thead>
                    <tbody>
                        <?php for($i=0; $i<$n; $i++): ?>
                        <tr>
                            <td class="fw-bold text-start bg-light"><?= $kriteria_nama[$i] ?></td>
                            <?php for($j=0; $j<$n; $j++): ?>
                                <td class="fw-bold text-primary"><?= round($matriks[$i][$j], 3) ?></td>
                            <?php endfor; ?>
                        </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>

            <h6 class="fw-bold text-dark mt-4">B. Data Mentah Alternatif</h6>
            <div class="table-responsive">
                <table class="table table-bordered table-striped text-center align-middle small">
                    <thead class="table-dark">
                        <tr><th>Alternatif</th><?php foreach($kriteria_nama as $idx => $k) echo "<th>$k (".ucfirst($kriteria_sifat[$idx]).")</th>"; ?></tr>
                    </thead>
                    <tbody>
                        <?php foreach($alt_nama as $i => $nama): ?>
                        <tr>
                            <td class="text-start fw-bold"><?= $nama ?></td>
                            <?php foreach($kriteria_nama as $j => $k) echo "<td>".number_format($alt_nilai[$i][$j],0,',','.')."</td>"; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="card bg-light border-0 mt-4 shadow-sm">
                <div class="card-header bg-warning text-dark fw-bold">
                    <i class="bi bi-calculator"></i> Bedah Perhitungan Consistency Ratio (CR)
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-7">
                            <p class="small mb-1 text-muted"><strong>1. Rumus Lambda Max ($\lambda_{max}$):</strong> $\sum (\text{Total Kolom} \times \text{Bobot Prioritas})$</p>
                            <div class="bg-white p-2 border rounded small font-monospace text-muted mb-3">
                                <?= implode(' + ', $lambda_rumus) ?> <br>
                                = <strong><?= round($lambda_max, 5) ?></strong>
                            </div>

                            <p class="small mb-1 text-muted"><strong>2. Consistency Index (CI):</strong> $(\lambda_{max} - n) / (n - 1)$</p>
                            <div class="bg-white p-2 border rounded small font-monospace text-muted mb-3">
                                (<?= round($lambda_max, 4) ?> - <?= $n ?>) / (<?= $n ?> - 1) <br>
                                = <strong><?= round($CI, 5) ?></strong>
                            </div>

                            <p class="small mb-1 text-muted"><strong>3. Random Index (RI):</strong> Nilai ketetapan Prof. Saaty berdasarkan jumlah kriteria (n=<?= $n ?>).</p>
                            <div class="table-responsive mb-3">
                                <table class="table table-bordered table-sm text-center small mb-0">
                                    <thead class="table-secondary">
                                        <tr>
                                            <th>n</th> <td>1</td><td>2</td><td>3</td><td>4</td><td>5</td><td>6</td><td>7</td>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <th>RI</th> <td>0</td><td>0</td><td>0.58</td><td>0.90</td><td>1.12</td><td>1.24</td><td>1.32</td>
                                        </tr>
                                    </tbody>
                                </table>
                                <div class="bg-warning bg-opacity-10 border border-warning p-1 mt-1 text-center small">
                                    Karena kriteria Anda ada <strong><?= $n ?></strong>, maka nilai <strong>RI = <?= $RI ?></strong>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-5 text-center d-flex flex-column justify-content-center">
                            <div class="border rounded p-3 bg-white h-100 shadow-sm">
                                <h6 class="text-secondary fw-bold">HASIL AKHIR (CR)</h6>
                                <p class="small text-muted fst-italic mb-2">Rumus: CI / RI</p>
                                
                                <h3 class="fw-bold text-primary mb-0">
                                    <?= round($CI, 4) ?> / <?= $RI ?>
                                </h3>
                                <i class="bi bi-arrow-down fs-4 text-muted"></i>
                                <h1 class="display-4 fw-bold <?= ($CR<=0.1)?'text-success':'text-danger' ?>">
                                    <?= round($CR, 4) ?>
                                </h1>
                                
                                <span class="badge rounded-pill bg-<?= ($CR<=0.1)?'success':'danger' ?> fs-6 px-3 py-2">
                                    <?= ($CR<=0.1)?'KONSISTEN (Valid)':'TIDAK KONSISTEN' ?>
                                </span>
                                
                                <div class="alert alert-<?= ($CR<=0.1)?'success':'danger' ?> small mt-3 mb-0 text-start">
                                    <?php if($CR <= 0.1): ?>
                                        <i class="bi bi-check-circle-fill"></i> Nilai CR ≤ 0.1. Matriks perbandingan Anda konsisten secara logika.
                                    <?php else: ?>
                                        <i class="bi bi-exclamation-triangle-fill"></i> Nilai CR > 0.1. Logika perbandingan Anda bertentangan. Mohon input ulang.
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="text-end mt-3">
                <button class="btn btn-primary btn-sm" onclick="document.querySelector('[data-bs-target=\'#tab2\']').click()">Lanjut Normalisasi <i class="bi bi-arrow-right"></i></button>
            </div>
        </div>

        <div class="tab-pane fade" id="tab2">
            <div class="alert alert-warning small">
                <i class="bi bi-lightbulb-fill"></i> <strong>Langkah 2: Normalisasi Matriks.</strong> <br>
                Agar pembacaan lebih mudah, proses ini dibagi menjadi dua tabel: <br>
                1. <strong>Tabel A:</strong> Menjumlahkan setiap kolom matriks. (Angka total ini akan jadi pembagi).<br>
                2. <strong>Tabel B:</strong> Membagi nilai sel dengan total kolom, lalu menghitung rata-rata baris (Bobot).
            </div>

            <h6 class="fw-bold text-dark mt-3">A. Penjumlahan Kolom (Mencari Pembagi)</h6>
            <div class="table-responsive mb-4">
                <table class="table table-bordered text-center align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Kriteria</th>
                            <?php foreach($kriteria_nama as $k) echo "<th>$k</th>"; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php for($i=0; $i<$n; $i++): ?>
                        <tr>
                            <td class="fw-bold text-start bg-light"><?= $kriteria_nama[$i] ?></td>
                            <?php for($j=0; $j<$n; $j++): ?>
                                <td class="text-primary fw-bold"><?= round($matriks[$i][$j], 3) ?></td>
                            <?php endfor; ?>
                        </tr>
                        <?php endfor; ?>
                        
                        <tr class="table-warning border-top border-2 border-warning">
                            <td class="fw-bold text-start text-danger">Total Kolom (Σ)</td>
                            <?php foreach($jumlah_kolom as $jk): ?>
                                <td class="fw-bold text-danger fs-5"><?= round($jk, 3) ?></td>
                            <?php endforeach; ?>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="text-center mb-4">
                <i class="bi bi-arrow-down-circle-fill text-muted fs-3"></i>
                <p class="small text-muted fst-italic">Setiap nilai di Tabel A dibagi dengan Total Kolomnya masing-masing</p>
            </div>

            <h6 class="fw-bold text-dark">B. Hasil Normalisasi & Bobot Prioritas</h6>
            <div class="table-responsive">
                <table class="table table-bordered text-center align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Kriteria</th>
                            <?php foreach($kriteria_nama as $k) echo "<th>$k (Norm)</th>"; ?>
                            <th class="table-primary" width="20%">Bobot (Rata-rata)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php for($i=0; $i<$n; $i++): 
                            $rumus_rata = [];
                        ?>
                        <tr>
                            <td class="fw-bold text-start bg-light"><?= $kriteria_nama[$i] ?></td>
                            <?php for($j=0; $j<$n; $j++): 
                                $rumus_rata[] = round($matriks_norm[$i][$j], 3);
                            ?>
                                <td class="calc-cell">
                                    <span class="formula-small"><?= round($matriks[$i][$j],2) ?> / <?= round($jumlah_kolom[$j],2) ?></span>
                                    <span class="result-big"><?= round($matriks_norm[$i][$j], 3) ?></span>
                                </td>
                            <?php endfor; ?>
                            
                            <td class="table-primary calc-cell text-start ps-3">
                                <span class="formula-small">(<?= implode('+', $rumus_rata) ?>) / <?= $n ?></span>
                                <span class="result-big-final text-dark text-center"><?= round($bobot_kriteria[$i], 4) ?></span>
                            </td>
                        </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>

            <div class="text-end mt-3">
                <button class="btn btn-primary btn-sm" onclick="document.querySelector('[data-bs-target=\'#tab3\']').click()">Lanjut Perhitungan Skor <i class="bi bi-arrow-right"></i></button>
            </div>
        </div>

        <div class="tab-pane fade" id="tab3">
            <div class="alert alert-success small">
                <i class="bi bi-calculator-fill"></i> <strong>Langkah 3: Perhitungan Skor Akhir.</strong> <br>
                Proses ini terdiri dari dua tahap:<br>
                1. <strong>Normalisasi Data Alternatif:</strong> Mengubah satuan Rupiah/GB menjadi skor prioritas (0-1).<br>
                2. <strong>Perkalian Bobot:</strong> Mengalikan skor prioritas tersebut dengan Bobot Kriteria (dari Tab 2).
            </div>

            <h6 class="fw-bold text-dark mt-3">A. Normalisasi Data Alternatif (Vektor Prioritas)</h6>
            <div class="table-responsive mb-4">
                <table class="table table-bordered text-center align-middle">
                    <thead class="table-secondary">
                        <tr>
                            <th>Alternatif</th>
                            <?php foreach($kriteria_nama as $idx => $k): 
                                // Cek Cost/Benefit untuk label
                                $bg = ($kriteria_sifat[$idx]=='cost') ? 'danger' : 'success';
                                $lbl = ucfirst($kriteria_sifat[$idx]);
                            ?>
                                <th>
                                    <?= $k ?> <span class="badge bg-<?= $bg ?>"><?= $lbl ?></span><br>
                                    <small class="text-muted fw-normal">Total: <?= round($total_per_kriteria_alt[$idx], 4) ?></small>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($alt_nama as $i => $nama): ?>
                        <tr>
                            <td class="fw-bold text-start"><?= $nama ?></td>
                            <?php foreach($kriteria_nama as $j => $k): 
                                // Ambil data untuk rumus
                                $val_asli = $alt_nilai[$i][$j];
                                $total_col = $total_per_kriteria_alt[$j];
                                $is_cost = ($kriteria_sifat[$j] == 'cost');
                                $hasil_norm = round($prioritas_alt[$i][$j], 4);
                            ?>
                                <td class="calc-cell">
                                    <?php if($is_cost): ?>
                                        <span class="formula-small">(1/<?= $val_asli ?>) / <?= round($total_col, 2) ?></span>
                                    <?php else: ?>
                                        <span class="formula-small"><?= $val_asli ?> / <?= round($total_col, 2) ?></span>
                                    <?php endif; ?>
                                    <span class="result-big"><?= $hasil_norm ?></span>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="text-center mb-4">
                <i class="bi bi-arrow-down-circle-fill text-muted fs-3"></i>
                <p class="small text-muted fst-italic">Hasil Normalisasi di atas dikalikan dengan Bobot Kriteria</p>
            </div>

            <h6 class="fw-bold text-dark">B. Ranking & Skor Akhir (Weighted Sum)</h6>
            <div class="table-responsive">
                <table class="table table-bordered table-hover text-center align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th rowspan="2" style="vertical-align: middle;">Alternatif</th>
                            <?php foreach($kriteria_nama as $k) echo "<th>$k</th>"; ?>
                            <th rowspan="2" class="bg-success" style="vertical-align: middle;">Total Skor</th>
                            <th rowspan="2" class="bg-warning text-dark" style="vertical-align: middle;">Rank</th>
                        </tr>
                        <tr>
                            <?php foreach($bobot_kriteria as $b) echo "<th class='text-warning small'>Bobot: ".round($b,3)."</th>"; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($alt_nama as $i => $nama): 
                            $current_score = $nilai_akhir_unsorted[$i];
                            $current_rank = getRank($i, $nilai_akhir_sorted);
                        ?>
                        <tr>
                            <td class="fw-bold text-start"><?= $nama ?></td>
                            <?php foreach($kriteria_nama as $j => $k): 
                                $norm_val = round($prioritas_alt[$i][$j], 3);
                                $weight = round($bobot_kriteria[$j], 3);
                                $res = round($norm_val * $weight, 4);
                            ?>
                                <td class="calc-cell">
                                    <span class="formula-small">(<?= $norm_val ?> x <?= $weight ?>)</span>
                                    <span class="result-big"><?= $res ?></span>
                                </td>
                            <?php endforeach; ?>
                            
                            <td class="calc-cell table-success">
                                <span class="formula-small">Σ(Row)</span>
                                <span class="result-big-final text-success"><?= round($current_score, 4) ?></span>
                            </td>

                            <td class="table-warning fw-bold fs-5">
                                #<?= $current_rank ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="text-end mt-3">
                <button class="btn btn-primary btn-sm" onclick="document.querySelector('[data-bs-target=\'#tab4\']').click()">Lihat Hasil Rekomendasi <i class="bi bi-arrow-right"></i></button>
            </div>
        </div>

        <div class="tab-pane fade" id="tab4">
            <h4 class="fw-bold text-success mb-3 text-center"><i class="bi bi-trophy-fill"></i> Peringkat & Rekomendasi AHP</h4>
            
            <div class="table-responsive">
                <table class="table table-hover align-middle border shadow-sm">
                    <thead class="bg-success text-white">
                        <tr>
                            <th class="text-center" width="10%">Rank</th>
                            <th width="40%">Nama Alternatif</th>
                            <th width="30%" class="text-center">Skor Akhir</th>
                            <th width="20%" class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($nilai_akhir_sorted as $row): ?>
                        <tr class="<?= ($row['rank']==1)?'table-warning border-warning border-2':'' ?>">
                            <td class="text-center">
                                <div class="badge-rank <?= ($row['rank']==1)?'bg-warning text-dark shadow':'bg-secondary text-white' ?> mx-auto">
                                    <?= $row['rank'] ?>
                                </div>
                            </td>
                            <td>
                                <span class="fw-bold fs-5"><?= $row['nama'] ?></span>
                                <?php if($row['rank']==1): ?>
                                    <br><small class="text-success fw-bold"><i class="bi bi-check-circle-fill"></i> Sangat Direkomendasikan</small>
                                <?php endif; ?>
                            </td>
                            <td class="text-center fw-bold fs-4 text-primary"><?= round($row['nilai'], 5) ?></td>
                            <td class="text-center">
                                <?php if($row['rank']==1): ?>
                                    <span class="badge bg-success">Best Choice</span>
                                <?php else: ?>
                                    <span class="badge bg-light text-muted border">Alternatif</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="card mt-5 border-0 shadow-sm bg-light">
                <div class="card-body text-center p-4">
                    <h5 class="card-title text-secondary">Eksperimen Selesai!</h5>
                    <p class="card-text text-muted mb-4"> Rekomendasi di atas merupakan hasil perhitungan Sistem Pendukung Keputusan menggunakan <strong>metode AHP </strong>, sedangkan keputusan akhir tetap berada di tangan Anda selaku pemegang keputusan. Apakah hasilnya sesuai prediksi Anda? Inilah esensi dari Sistem Pendukung Keputusan yang mencoba memberikan rekomendasi berdasarkan metode dan perhitungan objektif berdasarkan kriteria serta alternatif yang ada.  Apakah hasil rekomendasi akan sama atau berubah jika dilakukan menggunakan metode lain? Coba bandingkan !</p>
                    
                    <div class="d-flex justify-content-center gap-3 flex-wrap">
                        <a href="proses_wp.php" class="btn btn-outline-success px-4 py-2">
                            <strong>Coba Metode WP</strong><br><small>Weighted Product</small>
                        </a>
                        <a href="proses_topsis.php" class="btn btn-outline-danger px-4 py-2">
                            <strong>Coba Metode TOPSIS</strong><br><small> Solusi jarak terbaik dan terburuk</small>
                        </a>
                        <a href="proses_saw.php" class="btn btn-outline-primary px-4 py-2">
                            <strong>Coba Metode SAW</strong><br><small>penjumlahan terbobot</small>
                        </a>
                    </div>
                    <hr class="my-4 w-50 mx-auto">
                    <a href="../index.php" class="btn btn-secondary px-5">Kembali ke Dashboard</a>
                </div>
            </div>
        </div>

    </div>
</div>

<?php include '../includes/footer.php'; ?>