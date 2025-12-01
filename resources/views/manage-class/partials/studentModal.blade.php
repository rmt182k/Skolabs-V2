<div class="modal fade" id="studentModal" tabindex="-1" aria-labelledby="studentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="studentModalLabel">Tambah Siswa ke Kelas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="studentModalForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="student_user_id" class="form-label">Cari Siswa (bisa pilih lebih dari satu)</label>
                        <select class="form-select" id="student_user_id" name="user_ids[]" required style="width: 100%;"
                            multiple>
                        </select>
                        <div class="invalid-feedback" id="user_id-error"></div>
                        <div class="form-text">Anda bisa memilih lebih dari satu siswa dari daftar pencarian.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Tambahkan ke Kelas</button>
                </div>
            </form>
        </div>
    </div>
</div>
