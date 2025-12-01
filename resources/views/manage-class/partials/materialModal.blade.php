<div class="modal fade" id="materialModal" tabindex="-1" aria-labelledby="materialModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="materialModalLabel">Tambah Bahan Ajar</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="materialModalForm" enctype="multipart/form-data">
                <input type="hidden" id="material_id" name="material_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="material_subject_id" class="form-label">Mata Pelajaran</label>
                        <select class="form-select" id="material_subject_id" name="subject_id" required
                            style="width: 100%;">
                            {{-- Diisi oleh Select2 --}}
                        </select>
                        <div class="invalid-feedback" id="subject_id-error"></div>
                    </div>
                    <div class="mb-3">
                        <label for="title" class="form-label">Judul Materi</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                        <div class="invalid-feedback" id="title-error"></div>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Deskripsi (Opsional)</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        <div class="invalid-feedback" id="description-error"></div>
                    </div>
                    <hr>
                    <p class="mb-2 fw-bold small text-muted">Lampiran (Opsional)</p>
                    <div class="mb-3">
                        <label for="file_input" class="form-label">Upload File</label>
                        <input class="form-control" type="file" id="file_input" name="file_input">
                        <div class="form-text" id="current-file-info"></div>
                        <div class="invalid-feedback" id="file_input-error"></div>
                    </div>
                    <div class="mb-3 form-check" id="remove-file-group" style="display: none;">
                        <input type="checkbox" class="form-check-input" id="remove_current_file"
                            name="remove_current_file" value="1">
                        <label class="form-check-label" for="remove_current_file">Hapus file saat ini</label>
                        <div class="form-text">Centang untuk menghapus file yang sudah ter-upload.</div>
                    </div>
                    <div class="mb-3">
                        <label for="link_url" class="form-label">URL / Link Materi</label>
                        <input type="url" class="form-control" id="link_url" name="link_url"
                            placeholder="https://www.example.com/materi.pdf">
                        <div class="invalid-feedback" id="link_url-error"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="materialSubmitBtn">Simpan Materi</button>
                </div>
            </form>
        </div>
    </div>
</div>
