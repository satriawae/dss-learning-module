<footer class="bg-light text-center py-4 mt-auto border-top">
        <div class="container">
            <small class="text-muted fw-bold">
                &copy; <?= date('Y') ?> DSS Learning Modul. <span class="fw-normal">Developed for Educational Purpose.</span>
            </small>
        </div>
    </footer>

    <div class="modal fade" id="helpModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title fw-bold"><i class="bi bi-lightbulb-fill"></i> Panduan Belajar SPK</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row">
                        <div class="col-md-6 border-end">
                            <h6 class="text-primary fw-bold">Konsep Dasar</h6>
                            <p class="small text-muted text-justify">
                                SPK (Sistem Pendukung Keputusan) merupakan Sistem berbasis komputer interaktif, yang membantu para pengambil keputusan untuk menggunakan data dan berbagai model untuk memecahkan masalah-masalah Semi terstruktur maupun tidak terstruktur. SPK menggunakan teknik menghitung ranking terbaik dari berbagai pilihan (alternatif) berdasarkan kriteria yang ditentukan.
                            </p>
                            <h6 class="text-primary fw-bold mt-3">Langkah Simulasi:</h6>
                            <ol class="small text-muted ps-3">
                                <li>Tentukan <strong>Kriteria</strong> & Bobotnya.</li>
                                <li>Isi data <strong>Kandidat</strong>.</li>
                                <li>Sistem akan menampilkan <strong>Perhitungan & Ranking</strong>.</li>
                            </ol>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-success fw-bold">Kamus Metode:</h6>
                            <ul class="list-unstyled small">
                                <li class="mb-2"><strong>SAW:</strong> Penjumlahan terbobot. Paling dasar.</li>
                                <li class="mb-2"><strong>WP:</strong> Perkalian terbobot. Lebih ketat menyeleksi.</li>
                                <li class="mb-2"><strong>AHP:</strong> Fokus pada struktur hirarki & konsistensi logika.</li>
                                <li class="mb-2"><strong>TOPSIS:</strong> Mencari jarak terdekat ke solusi ideal.</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-primary btn-sm" data-bs-dismiss="modal">Siap Belajar!</button>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>