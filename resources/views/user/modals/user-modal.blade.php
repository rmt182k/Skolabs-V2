<div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="userModalLabel">Modal Title</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="userForm">
                <input type="hidden" id="user_id" name="user_id">
                <div class="modal-body">
                    <ul class="nav nav-tabs" id="userTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="info-tab" data-bs-toggle="tab"
                                data-bs-target="#info-tab-pane" type="button" role="tab">Info Akun</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="details-tab" data-bs-toggle="tab"
                                data-bs-target="#details-tab-pane" type="button" role="tab">Detail
                                Pengguna</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="role-tab" data-bs-toggle="tab" data-bs-target="#role-tab-pane"
                                type="button" role="tab">Assign Role & Status</button>
                        </li>
                    </ul>
                    <div class="tab-content" id="userTabContent">
                        <div class="tab-pane fade show active p-3" id="info-tab-pane" role="tabpanel">
                            <div class="mb-3">
                                <label for="name" class="form-label">Nama Lengkap</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Alamat Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password">
                                <small class="form-text text-muted" id="passwordHelp">Wajib diisi untuk pengguna baru.
                                    Kosongkan jika tidak ingin mengubah password.</small>
                            </div>
                        </div>

                        <div class="tab-pane fade p-3" id="details-tab-pane" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="identity_number" class="form-label">NISN / NIP</label>
                                    <input type="text" class="form-control" id="identity_number"
                                        name="identity_number">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="phone_number" class="form-label">Nomor Telepon</label>
                                    <input type="text" class="form-control" id="phone_number" name="phone_number">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="date_of_birth" class="form-label">Tanggal Lahir</label>
                                    <input type="date" class="form-control" id="date_of_birth" name="date_of_birth">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="gender" class="form-label">Jenis Kelamin</label>
                                    <select class="form-select" id="gender" name="gender">
                                        <option value="">Pilih...</option>
                                        <option value="male">Laki-laki</option>
                                        <option value="female">Perempuan</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="address" class="form-label">Alamat</label>
                                <textarea class="form-control" id="address" name="address" rows="3"></textarea>
                            </div>
                        </div>

                        <div class="tab-pane fade p-3" id="role-tab-pane" role="tabpanel">
                            <div class="mb-3">
                                <label for="role" class="form-label">Assign Role</label>
                                <select class="form-select" id="role" name="role_id" required>
                                    <option value="">Pilih Role...</option>
                                    {{-- Opsi diisi oleh JavaScript --}}
                                </select>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="is_active"
                                    name="is_active" value="1" checked>
                                <label class="form-check-label" for="is_active">Akun Aktif</label>
                            </div>
                        </div>
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
