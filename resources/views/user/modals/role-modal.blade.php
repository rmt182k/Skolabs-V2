<!-- Add/Edit Role Modal -->
<div class="modal fade" id="roleModal" tabindex="-1" aria-labelledby="roleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="roleModalLabel">Tambah Role Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="roleForm">
                <input type="hidden" id="role_id" name="role_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="role_name" class="form-label">Nama Role</label>
                        <input type="text" class="form-control" id="role_name" name="name" required>
                    </div>
                    <hr>
                    <label class="form-label">Assign Permissions</label>
                    <div id="permissions-list" class="row">
                        {{-- Checkbox permissions akan di-load oleh JavaScript --}}
                        <p class="text-muted">Memuat permissions...</p>
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
