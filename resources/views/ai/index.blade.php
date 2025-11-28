@extends('layouts.app')

@section('title', 'Pengaturan Agen AI')

@push('styles')
    <style>
        .settings-card {
            border-left: 4px solid #0d6efd;
            transition: all .2s;
        }

        .settings-card.disabled {
            border-left-color: #6c757d;
            background-color: #f8f9fa;
        }

        .settings-card.disabled * {
            opacity: 0.7;
            color: #6c757d !important;
        }

        .prompt-template {
            font-family: 'Courier New', monospace;
            font-size: 0.875rem;
            background-color: #f8f9fa;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        @include('layouts.components.breadcrumb')
        <h1 class="h3 mb-4">Pengaturan Agen AI</h1>

        <div class="row">
            <div class="col-lg-12">
                <form id="aiSettingsForm">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Penugasan & Prompt Agen AI</h5>
                        </div>
                        <div class="card-body p-4">
                            <p class="text-muted mb-4">
                                Pilih model AI dan sesuaikan <strong>prompt template</strong> untuk setiap tugas otomatis.
                            </p>

                            <button type="button" id="addNewTask" class="btn btn-primary mb-3">
                                <i class="fas fa-plus me-2"></i>Tambah Tugas AI
                            </button>

                            <div id="settings-container">
                                <div class="text-center p-5">
                                    <div class="spinner-border text-primary"></div>
                                    <p class="mt-2">Memuat pengaturan...</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mb-5">
                        <button type="button" id="saveSettingsBtn" class="btn btn-success btn-lg">
                            <i class="fas fa-save me-2"></i>Simpan Pengaturan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            const API = {
                GET: '/api/ai-settings',
                BULK: '/api/ai-settings/bulk',
                STORE: '/api/ai-settings/store',
                DELETE: (key) => `/api/ai-settings/${key}`
            };

            const $container = $('#settings-container');
            let settings = [];
            let models = [];

            // === FETCH DATA ===
            function loadData() {
                $.get(API.GET).done(res => {
                    if (res.success) {
                        settings = res.data.settings;
                        models = res.data.models;
                        renderAll();
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                }).fail(() => {
                    Swal.fire('Error', 'Gagal terhubung ke server.', 'error');
                });
            }

            // === RENDER SEMUA CARD ===
            function renderAll() {
                $container.empty();
                if (!settings.length) {
                    $container.html(
                        '<p class="text-center text-muted">Belum ada tugas AI. Klik "Tambah Tugas AI" untuk memulai.</p>'
                        );
                    return;
                }
                settings.forEach((s, i) => renderCard(s, i > 0));
            }

            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            function renderCard(s, addHr = false) {
                const opts = '<option value="">-- Tidak Ditugaskan --</option>' +
                    models.map(m =>
                        `<option value="${m.id}" ${m.id == s.ai_model_id ? 'selected' : ''}>${m.model_name}</option>`
                        ).join('');

                const disabled = !s.is_enabled ? 'disabled' : '';
                const checked = s.is_enabled ? 'checked' : '';
                const inputDis = !s.is_enabled ? 'disabled' : '';

                const html = `
            ${addHr ? '<hr class="my-4">' : ''}
            <div class="card shadow-sm mb-3 settings-card ${disabled}" data-task-key="${s.task_key}">
                <div class="card-body p-4">
                    <div class="row">
                        <div class="col-md-8 d-flex align-items-start">
                            <div class="form-check form-switch form-check-lg me-3 mt-1">
                                <input class="form-check-input toggle-switch" type="checkbox" ${checked}>
                            </div>
                            <div class="flex-grow-1">
                                <h5 class="card-title mb-1">${s.task_name}</h5>
                                <p class="card-text small text-muted mb-3">${s.description || ''}</p>
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Prompt Template:</label>
                                    <textarea class="form-control prompt-template" rows="7"
                                              name="settings[${s.task_key}][prompt_template]" ${inputDis}>${escapeHtml(s.prompt_template || '')}</textarea>
                                    <div class="form-text">Gunakan <code>@{{ nama }}</code> untuk placeholder.</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Model AI:</label>
                                <select class="form-select model-select" name="settings[${s.task_key}][ai_model_id]" ${inputDis}>
                                    ${opts}
                                </select>
                            </div>
                            <div class="text-end mt-3">
                                <button type="button" class="btn btn-sm btn-outline-danger btn-delete">
                                    Hapus
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>`;
                $container.append(html);
            }

            // === EVENTS ===
            $container.on('change', '.toggle-switch', function() {
                const $card = $(this).closest('.settings-card');
                const on = this.checked;
                $card.toggleClass('disabled', !on);
                $card.find('select, textarea').prop('disabled', !on);
            });

            $('#addNewTask').on('click', function() {
                Swal.fire({
                    title: 'Tambah Tugas AI Baru',
                    html: `
                <input id="name" class="swal2-input" placeholder="Nama Tugas">
                <input id="key" class="swal2-input" placeholder="Kunci unik (contoh: GRADE_QUIZ)" style="text-transform:uppercase">
                <textarea id="desc" class="swal2-textarea" placeholder="Deskripsi..."></textarea>
                <textarea id="prompt" class="swal2-textarea" placeholder="Prompt template..." style="height:140px"></textarea>
            `,
                    showCancelButton: true,
                    preConfirm: () => {
                        const name = $('#name').val().exclusively().trim();
                        const key = $('#key').val().trim().toUpperCase().replace(/[^A-Z0-9_]/g,
                            '_');
                        const desc = $('#desc').val().trim();
                        const prompt = $('#prompt').val().trim();
                        if (!name || !key || !prompt) return Swal.showValidationMessage(
                            'Wajib isi semua field');
                        return {
                            name,
                            key,
                            desc,
                            prompt
                        };
                    }
                }).then(r => {
                    if (r.isConfirmed) {
                        $.post(API.STORE, r.value).done(res => {
                            if (res.success) {
                                settings.push(res.data);
                                renderCard(res.data, true);
                                Swal.fire('Sukses!', res.message, 'success');
                            } else {
                                Swal.fire('Gagal', res.message, 'error');
                            }
                        });
                    }
                });
            });

            $container.on('click', '.btn-delete', function() {
                const $card = $(this).closest('.settings-card');
                const key = $card.data('task-key');
                Swal.fire({
                    title: 'Hapus?',
                    icon: 'warning',
                    showCancelButton: true
                }).then(r => {
                    if (r.isConfirmed) {
                        $.ajax({
                            url: API.DELETE(key),
                            method: 'DELETE'
                        }).done(res => {
                            if (res.success) {
                                $card.prev('hr').remove();
                                $card.remove();
                                settings = settings.filter(s => s.task_key !== key);
                                if (!settings.length) renderAll();
                                Swal.fire('Dihapus!', res.message, 'success');
                            }
                        });
                    }
                });
            });

            $('#saveSettingsBtn').on('click', function() {
                const $btn = $(this);
                $btn.prop('disabled', true).html('Menyimpan...');

                const payload = {
                    settings: {}
                };
                $('.settings-card').each(function() {
                    const $c = $(this);
                    const key = $c.data('task-key');
                    payload.settings[key] = {
                        is_enabled: $c.find('.toggle-switch').is(':checked') ? '1' : '0',
                        ai_model_id: $c.find('.model-select').val(),
                        prompt_template: $c.find('.prompt-template').val()
                    };
                });

                $.post(API.BULK, payload).done(res => {
                    Swal.fire('Sukses!', res.message, 'success');
                }).fail(() => {
                    Swal.fire('Gagal', 'Terjadi kesalahan saat menyimpan.', 'error');
                }).always(() => {
                    $btn.prop('disabled', false).html('Simpan Pengaturan');
                });
            });

            loadData();
        });
    </script>
@endpush
