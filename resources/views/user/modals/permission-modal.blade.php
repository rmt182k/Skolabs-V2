<!-- Add/Edit Permission Modal -->
<div class="modal fade" id="permissionModal" tabindex="-1" aria-labelledby="permissionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="permissionModalLabel">Tambah Permission Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="permissionForm">
                <input type="hidden" id="permission_id" name="permission_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="permission_name" class="form-label">Nama Permission</label>
                        <input type="text" class="form-control" id="permission_name" name="name" required placeholder="Contoh: create-user">
                         <small class="form-text text-muted">Gunakan format `aksi-modul` (contoh: edit-post, delete-user).</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
