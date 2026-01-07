<?php
session_start();
$metode = $_GET['metode'] ?? 'SAW';

// Panggil Header & Navbar Baru
include '../includes/header.php';
include '../includes/navbar.php';
?>

<div class="container mt-4 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card border-primary mb-5 shadow-sm" style="background-color: #f0f7ff;">
                <div class="card-header bg-primary text-white fw-bold">
                    <i class="bi bi-book-half me-2"></i> Studi Kasus Referensi
                </div>
                <div class="card-body">
                    <p class="card-text small">
                        Contoh: Anda ingin membeli HP baru. Anda bingung memilih antara <strong>Samsung, iPhone, dan Xiaomi</strong>. 
                        Kriterianya:
                    </p>
                    <ul class="small mb-0">
                        <li><strong>Harga (Cost):</strong> Semakin murah semakin bagus.</li>
                        <li><strong>RAM (Benefit):</strong> Semakin besar kapasitasnya semakin bagus.</li>
                        <li><strong>Kamera (Benefit):</strong> Semakin besar resolusi semakin bagus.</li>
                    </ul>
                </div>
            </div>

            <div class="card shadow">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold text-primary">Langkah 1: Tentukan Kriteria</h5>
                    <small class="text-muted">Metode terpilih: <span class="badge bg-dark"><?= $metode ?></span></small>
                </div>
                <div class="card-body p-4">
                    <form action="input_alternatif.php" method="POST">
                        <input type="hidden" name="metode" value="<?= $metode ?>">
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold">Masukkan Jumlah Kriteria</label>
                            <div class="input-group">
                                <input type="number" id="jml_kriteria" class="form-control" placeholder="Contoh: 3" min="2" max="10">
                                <button type="button" class="btn btn-dark" onclick="generateKriteria()">Buat Form</button>
                            </div>
                        </div>

                        <div id="form_area"></div>

                        <div id="btn_area" class="text-end mt-4 d-none">
                            <button type="submit" class="btn btn-success px-4 fw-bold">
                                Lanjut ke Input Kandidat / Alternatif <i class="bi bi-arrow-right"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function generateKriteria() {
    let jml = document.getElementById('jml_kriteria').value;
    let container = document.getElementById('form_area');
    let btnArea = document.getElementById('btn_area');
    
    if(jml > 1) {
        btnArea.classList.remove('d-none');
        let html = `
            <h6 class="fw-bold text-secondary mt-4 mb-3">Isi Detail Kriteria:</h6>
            <div class="table-responsive">
            <table class="table table-bordered align-middle text-center">
                <thead class="table-secondary">
                    <tr>
                        <th style="width:10%">Kode</th>
                        <th style="width:35%">Nama Kriteria (Syarat)</th>
                        <th style="width:30%">Sifat (Cost/Benefit)</th>
                        <th style="width:25%">Bobot (Angka Bebas)</th>
                    </tr>
                </thead>
                <tbody>`;
        
        for(let i=1; i<=jml; i++) {
            html += `
            <tr>
                <td class="fw-bold bg-light">C${i}</td>
                <td><input type="text" name="kriteria_nama[]" class="form-control" placeholder="Contoh: Harga/RAM/Kamera" required></td>
                <td>
                    <select name="kriteria_sifat[]" class="form-select">
                        <option value="benefit">Benefit (Makin Tinggi Bagus)</option>
                        <option value="cost">Cost (Makin Rendah Bagus)</option>
                    </select>
                </td>
                <td><input type="number" step="0.01" name="bobot[]" class="form-control text-center" placeholder="Contoh: 1-100" required></td>
            </tr>`;
        }
        html += `</tbody></table></div>`;
        container.innerHTML = html;
    }
}
</script>

<?php include '../includes/footer.php'; ?>