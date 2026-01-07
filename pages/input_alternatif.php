<?php
session_start();

// Simpan data kriteria ke sesi
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $_SESSION['metode']         = $_POST['metode'];
    $_SESSION['kriteria_nama']  = $_POST['kriteria_nama'];
    $_SESSION['kriteria_sifat'] = $_POST['kriteria_sifat']; 
    $_SESSION['bobot']          = $_POST['bobot'];          
}

if (!isset($_SESSION['kriteria_nama'])) { header("Location: input_kriteria.php"); exit(); }

$metode   = $_SESSION['metode'];
$kriteria = $_SESSION['kriteria_nama'];

// Arahkan ke file proses yang sesuai
$action_url = "proses_saw.php"; 
if($metode == 'WP') $action_url = "proses_wp.php";
if($metode == 'TOPSIS') $action_url = "proses_topsis.php";
if($metode == 'AHP') $action_url = "ahp_input_matrix.php";

include '../includes/header.php';
include '../includes/navbar.php';
?>

<div class="container mt-4 mb-5">
    <div class="card shadow">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0 fw-bold text-success">Langkah 2: Data Kandidat / Alternatif</h5>
                <small style="padding-right: 10pt;" class="text-muted">Pada contoh kasus hendak membeli HP, Anda dihadapkan pada beberapa merk dan tipe yang ada. Contoh : IPhone, Samsung, Oppo, Vivo dll. Ini adalah kandidat/alternatif yang hendak dipilih. Silahkan masukkan alternatif yang hendak Anda pilih.</small>
            </div>
            <span class="badge bg-success fs-6" >Metode: <?= $metode ?></span>
        </div>
        <div class="card-body p-4">
            
            <form action="<?= $action_url ?>" method="POST">
                <?php foreach($_SESSION['kriteria_nama'] as $k): ?><input type="hidden" name="kriteria_nama[]" value="<?= $k ?>"><?php endforeach; ?>
                <?php foreach($_SESSION['kriteria_sifat'] as $s): ?><input type="hidden" name="kriteria_sifat[]" value="<?= $s ?>"><?php endforeach; ?>
                <?php foreach($_SESSION['bobot'] as $b): ?><input type="hidden" name="bobot[]" value="<?= $b ?>"><?php endforeach; ?>

                <div class="mb-4">
                    <label class="form-label fw-bold">Jumlah Kandidat/Alternatif?</label>
                    <div class="input-group">
                        <input type="number" id="jml_alt" class="form-control" placeholder="Contoh: 3" min="2" max="20">
                        <button type="button" class="btn btn-dark" onclick="generateAlternatif()">Buat Tabel Input</button>
                    </div>
                </div>

                <div id="form_alt_area"></div>

                <div id="btn_submit_area" class="text-end mt-4 d-none">
                    <button type="submit" name="simpan_data" class="btn btn-primary px-5 py-2 fw-bold shadow">
                        <i class="bi bi-calculator-fill"></i> Proses Hitung Sekarang
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

<script>
var kriteriaData = <?php echo json_encode($kriteria); ?>;

function generateAlternatif() {
    let jml = document.getElementById('jml_alt').value;
    let container = document.getElementById('form_alt_area');
    let btnArea = document.getElementById('btn_submit_area');
    
    if(jml > 1) {
        btnArea.classList.remove('d-none');
        let html = `
        <div class="alert alert-info small"><i class="bi bi-info-circle-fill"></i> Masukkan angka riil dan biarkan komputer yang menghitung. Contoh: harga 5 Juta = 5000000.</div>
        <div class="table-responsive">
        <table class="table table-bordered align-middle text-center bg-white">
            <thead class="table-dark">
                <tr><th style="width: 50px">No</th><th style="width: 25%">Nama Kandidat/Alternatif</th>`;
        
        kriteriaData.forEach((k, index) => {
            html += `<th>${k}</th>`;
        });
        html += `</tr></thead><tbody>`;
        
        for(let i=0; i<jml; i++) {
            html += `<tr><td class="fw-bold bg-light">${i+1}</td>
                <td><input type="text" name="alt_nama[]" class="form-control fw-bold" placeholder="Nama Kandidat ${i+1}" required></td>`;
            kriteriaData.forEach((k, indexK) => {
                html += `<td><input type="number" step="0.01" name="alt_nilai[${i}][${indexK}]" class="form-control text-center" placeholder="0" required></td>`;
            });
            html += `</tr>`;
        }
        html += `</tbody></table></div>`;
        container.innerHTML = html;
    }
}
</script>

<?php include '../includes/footer.php'; ?>