<?php
session_start();

if (!isset($_SESSION['kriteria_nama'])) { header("Location: input_kriteria.php"); exit(); }
$kriteria = $_SESSION['kriteria_nama'];
$jumlah_kriteria = count($kriteria);

include '../includes/header.php';
include '../includes/navbar.php';
?>

<div class="container mb-5">
    <div class="alert alert-primary border-start border-5 border-primary shadow-sm mt-4">
        <h5 class="fw-bold"><i class="bi bi-diagram-3-fill"></i> Tentukan prioritas Antar Kriteria yang akan digunakan pada metode AHP</h5>
        <p class="mb-0">Pada metode AHP, diperlukan perbandingan berpasangan (pairwise comparison) antar elemen. HAl ini untuk menentukan bobot prioritas antar kriteria terhadap riteria lain. Diperlukan konsistensi penentuan antar bobot agar perhitungan AHP bisa dilakukan. contoh ekstrem : A > B, B > C, namun A < C, hal tersebut menunjukkan ketidak konsistenan penentuan bobot. Tingkat konsistensi disebut dengan Consistency Ratio (CR). 
            <br>Geser slider ke arah kriteria yang menurut Anda lebih penting.</p>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <form action="proses_ahp_step2.php" method="POST">
                <div class="table-responsive">
                    <table class="table table-borderless align-middle">
                        <thead class="text-center table-light border-bottom">
                            <tr><th class="w-25">Kiri</th><th class="w-50">Nilai Perbandingan</th><th class="w-25">Kanan</th></tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 0;
                            for ($i = 0; $i < $jumlah_kriteria; $i++) {
                                for ($j = $i + 1; $j < $jumlah_kriteria; $j++) {
                                    $k1 = $kriteria[$i]; $k2 = $kriteria[$j];
                                    $name = "pair_" . $i . "_" . $j;
                                    ?>
                                    <tr class="border-bottom">
                                        <td class="text-center fw-bold text-primary fs-5"><?= $k1 ?></td>
                                        <td>
                                            <div class="d-flex justify-content-between text-muted small px-2"><span>Mutlak Kiri</span><span>Sama</span><span>Mutlak Kanan</span></div>
                                            <div class="range-wrap text-center py-2">
                                                <input type="range" class="form-range" min="-9" max="9" step="1" value="0" oninput="updateLabel(this, 'label_<?= $no ?>', 'val_<?= $name ?>')">
                                                <input type="hidden" name="<?= $name ?>" id="val_<?= $name ?>" value="1">
                                            </div>
                                            <div class="text-center fw-bold" id="label_<?= $no ?>"><span class="badge bg-secondary">Sama Penting (1)</span></div>
                                        </td>
                                        <td class="text-center fw-bold text-primary fs-5"><?= $k2 ?></td>
                                    </tr>
                                    <?php $no++; } } ?>
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-end mt-4">
                    <button type="submit" name="hitung_ahp" class="btn btn-primary px-5 fw-bold shadow-sm">Proses <i class="bi bi-arrow-right-circle-fill ms-2"></i></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function updateLabel(el, labelId, hiddenInputId) {
    let val = parseInt(el.value);
    let label = document.getElementById(labelId);
    let hidden = document.getElementById(hiddenInputId);
    let absVal = Math.abs(val) + (Math.abs(val) >= 2 ? 0 : 1);
    if(Math.abs(val)===1) absVal=2;

    if (val === 0) {
        label.innerHTML = "<span class='badge bg-secondary'>Sama Penting (1)</span>";
        hidden.value = "1";
    } else if (val < 0) {
        label.innerHTML = "<span class='badge bg-primary'><i class='bi bi-arrow-left'></i> Kiri Lebih Penting (" + absVal + ")</span>";
        hidden.value = absVal + "_left";
    } else {
        label.innerHTML = "<span class='badge bg-primary'>Kanan Lebih Penting (" + absVal + ") <i class='bi bi-arrow-right'></i></span>";
        hidden.value = absVal + "_right";
    }
}
</script>
<?php include '../includes/footer.php'; ?>