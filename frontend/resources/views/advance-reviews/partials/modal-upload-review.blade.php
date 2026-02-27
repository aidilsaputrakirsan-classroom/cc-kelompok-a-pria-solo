<div class="modal fade" id="uploadModalAdvanced" tabindex="-1" aria-labelledby="uploadModalAdvancedLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="uploadModalAdvancedLabel">
                    <i class="bi bi-clipboard-check me-2"></i>Advanced Review - Upload Documents
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form enctype="multipart/form-data">
                    <div class="row">
                        <!-- Kolom Kiri - Input Form dengan Scrollbar -->
                        <div class="col-md-5 left-column-adv">
                            <div class="input-scroll-container-adv">
                                <!-- Nomor Tiket -->
                                <div class="mb-4">
                                    <label for="ticketNumberAdv" class="form-label fw-semibold">
                                        Nomor Tiket <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="ticketNumberAdv"
                                        placeholder="Masukkan nomor tiket" required>
                                    <div class="form-text">Nomor tiket wajib diisi untuk identifikasi dokumen.</div>
                                </div>

                                <!-- Nama Mitra -->
                                <div class="mb-4">
                                    <label for="companySelectAdv" class="form-label fw-semibold">
                                        Nama Mitra <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select" id="companySelectAdv" required>
                                        <option value="">Pilih Mitra</option>
                                    </select>
                                    <div class="form-text">Pilih Mitra yang terkait dengan dokumen.</div>
                                </div>

                                <!-- Type Tiket -->
                                <div class="mb-4">
                                    <label for="typeSelectAdv" class="form-label fw-semibold">
                                        Type <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select" id="typeSelectAdv" required>
                                        <option value="">Pilih Type</option>
                                        <option value="Perpanjangan">Perpanjangan</option>
                                        <option value="Non-Perpanjangan">Non-Perpanjangan</option>
                                    </select>
                                    <div class="form-text">
                                        <strong>Perpanjangan:</strong> 2 GT dengan jenis sama + suffix _1 dan _2<br>
                                        <strong>Non-Perpanjangan:</strong> 1 GT tanpa suffix
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Kolom Kanan - Upload Documents -->
                        <div class="col-md-7 right-column-adv">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0 fw-semibold">Upload Documents</h6>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-secondary" id="fileCounterAdv">0 file</span>
                                    <span class="badge" id="validationBadgeAdv" style="display: none;">
                                        <i class="bi bi-check-circle me-1"></i>Valid
                                    </span>
                                </div>
                            </div>

                            <!-- Validation Alert -->
                            <div id="validationAlertAdv" style="display: none;"
                                class="alert alert-dismissible fade show mb-3" role="alert">
                                <div class="d-flex align-items-start">
                                    <i class="bi flex-shrink-0 me-2"></i>
                                    <div class="flex-grow-1">
                                        <strong class="alert-title"></strong>
                                        <ul class="mb-0 mt-1 ps-3 alert-list"></ul>
                                    </div>
                                </div>
                                <button type="button" class="btn-close"
                                    onclick="document.getElementById('validationAlertAdv').style.display='none'"></button>
                            </div>

                            <!-- File Upload Section -->
                            <div class="mb-3">
                                <div class="file-upload-section-adv" id="fileUploadAreaAdv">
                                    <input class="form-control d-none" type="file" id="fileInputAdv" multiple
                                        accept=".pdf">
                                    <div class="upload-content-adv">
                                        <i class="bi bi-cloud-upload upload-icon-adv mb-2"></i>
                                        <p class="mb-2">Klik untuk memilih file atau seret file ke sini</p>
                                        <small class="text-muted">Hanya mendukung file PDF (bisa pilih multiple
                                            files)</small>
                                    </div>
                                    <button type="button" class="btn btn-outline-primary btn-sm mt-2"
                                        id="selectFilesBtn">
                                        <i class="bi bi-folder2-open me-1"></i>Pilih Multiple PDF Files
                                    </button>
                                </div>
                            </div>

                            <!-- File List with Scroll -->
                            <div id="fileListAdv" class="file-list-container-adv">
                                <div class="text-center py-4">
                                    <i class="bi bi-files fs-1 text-muted mb-2"></i>
                                    <p class="text-muted mb-1">Belum ada file yang dipilih</p>
                                    <small class="text-muted">File yang dipilih akan muncul di sini</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1"></i>Batal
                </button>
                <button type="button" class="btn btn-primary" id="uploadBtnAdv">
                    <i class="bi bi-cloud-upload me-1"></i>Unggah File
                </button>
            </div>
        </div>
    </div>
</div>
