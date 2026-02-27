document.addEventListener("DOMContentLoaded", function () {
    const fileInputAdv = document.getElementById("fileInputAdv");
    const fileListAdv = document.getElementById("fileListAdv");
    const uploadBtnAdv = document.getElementById("uploadBtnAdv");
    const fileCounterAdv = document.getElementById("fileCounterAdv");
    const fileUploadAreaAdv = document.getElementById("fileUploadAreaAdv");
    const validationBadgeAdv = document.getElementById("validationBadgeAdv");
    const validationAlertAdv = document.getElementById("validationAlertAdv");
    const loadingOverlay = document.getElementById("loadingOverlay");
    const typeSelectAdv = document.getElementById("typeSelectAdv");

    let selectedFilesAdv = [];
    let companiesData = [];

    const ADVANCE_UPLOAD_ENDPOINT = "/projess/api/advance-upload";
    const CHUNK_SIZE = 20; // PHP max_file_uploads; chunked uploads allow more files total

    const GROUND_TRUTH_FILES = ["KL", "Nopes", "SP", "WO"];

    const EXPECTED_DOCUMENTS = {
        groundTruth: ["KL", "SP", "WO", "Nopes"],
        singleReview: ["PO", "GR", "PR", "NPK", "SPB", "BAST", "BAUT", "BARD", "P7", "SKM", "P8"]
    };

    function isGroundTruthFile(filename) {
        let upperName = filename.toUpperCase().replace('.PDF', '');
        upperName = upperName.replace(/^\d+\.?\s*/, '').trim();

        return GROUND_TRUTH_FILES.some(gtType => {
            const gtUpper = gtType.toUpperCase();

            // Special handling untuk Nopes (bisa detect "NOTA PESANAN" juga)
            if (gtType === "Nopes") {
                if (upperName.includes("NOPES") || upperName.includes("NOTA PESANAN")) {
                    return true;
                }
            }

            if (gtType.includes(' ')) {
                return upperName.includes(gtUpper);
            }

            // Support suffix _1 dan _2 untuk Perpanjangan
            const regex = new RegExp(`^${gtUpper}(?:_[12])?(?:[\\s\\-_\\.]|$)`, 'i');
            return regex.test(upperName);
        });
    }

    /**
     * Extract Ground Truth type dari filename
     */
    function extractGroundTruthType(filename) {
        let upperName = filename.toUpperCase().replace('.PDF', '');
        upperName = upperName.replace(/^\d+\.?\s*/, '').trim();

        if (upperName.includes("NOPES") || upperName.includes("NOTA PESANAN")) {
            return "Nopes";
        }

        for (const gtType of GROUND_TRUTH_FILES) {
            if (gtType === "Nopes") continue; 

            const gtUpper = gtType.toUpperCase();
            const regex = new RegExp(`^${gtUpper}(?:_[12])?(?:[\\s\\-_\\.]|$)`, 'i');
            if (regex.test(upperName)) {
                return gtType;
            }
        }

        return null;
    }

    function hasPerpanjanganSuffix(filename) {
        let upperName = filename.toUpperCase().replace('.PDF', '');
        upperName = upperName.replace(/^\d+\.?\s*/, '').trim();

        return /_[12](?:[\\s\\-_\\.]|$)/.test(upperName);
    }

    function getSuffixNumber(filename) {
        let upperName = filename.toUpperCase().replace('.PDF', '');
        upperName = upperName.replace(/^\d+\.?\s*/, '').trim();

        const match = upperName.match(/_([12])(?:[\\s\\-_\\.]|$)/);
        return match ? match[1] : null;
    }

    function checkDocumentPresence() {
        const present = { groundTruth: [], singleReview: [] };

        selectedFilesAdv.forEach(file => {
            let upperName = file.name.toUpperCase().replace('.PDF', '');
            const cleanName = upperName.replace(/^\d+\.?\s*/, '').trim();

            if (isGroundTruthFile(file.name)) {
                const gtType = extractGroundTruthType(file.name);
                if (gtType) {
                    present.groundTruth.push({
                        type: gtType,
                        filename: file.name,
                        hasSuffix: hasPerpanjanganSuffix(file.name),
                        suffix: getSuffixNumber(file.name)
                    });
                }
            } else {
                EXPECTED_DOCUMENTS.singleReview.forEach(doc => {
                    const regex = new RegExp(`\\b${doc}\\b`, 'i');
                    if (regex.test(cleanName)) {
                        if (!present.singleReview.includes(doc)) {
                            present.singleReview.push(doc);
                        }
                    }
                });
            }
        });

        return present;
    }

    function autoDetectType() {
        const present = checkDocumentPresence();
        const gtCount = present.groundTruth.length;

        if (gtCount === 0) {
            return null;
        }

        if (gtCount === 1) {
            if (!present.groundTruth[0].hasSuffix) {
                return "Non-Perpanjangan";
            }
            return null; 
        }

        if (gtCount === 2) {
            const gt1 = present.groundTruth[0];
            const gt2 = present.groundTruth[1];

            // Check apakah jenis sama
            if (gt1.type === gt2.type) {
                // Check apakah ada suffix _1 dan _2
                const suffixes = [gt1.suffix, gt2.suffix].sort();
                if (suffixes[0] === '1' && suffixes[1] === '2') {
                    return "Perpanjangan";
                }
            }
            return null; // Invalid: 2 GT tapi tidak memenuhi syarat
        }

        return null; // Invalid: lebih dari 2 GT
    }

    /**
     * Update type select berdasarkan auto-detect
     */
    function updateTypeSelect() {
        const detectedType = autoDetectType();

        if (detectedType && typeSelectAdv.value === "") {
            typeSelectAdv.value = detectedType;
        }
    }

    /**
     * Validate apakah files sesuai dengan type yang dipilih
     */
    function validateTypeCompatibility(selectedType) {
        const present = checkDocumentPresence();
        const gtCount = present.groundTruth.length;

        if (!selectedType) return true; // Belum pilih type, skip validasi

        if (selectedType === "Non-Perpanjangan") {
            // Harus tepat 1 GT tanpa suffix
            if (gtCount !== 1) return false;
            if (present.groundTruth[0].hasSuffix) return false;
            return true;
        }

        if (selectedType === "Perpanjangan") {
            // Harus tepat 2 GT, jenis sama, dengan suffix _1 dan _2
            if (gtCount !== 2) return false;

            const gt1 = present.groundTruth[0];
            const gt2 = present.groundTruth[1];

            if (gt1.type !== gt2.type) return false;

            const suffixes = [gt1.suffix, gt2.suffix].sort();
            if (suffixes[0] !== '1' || suffixes[1] !== '2') return false;

            return true;
        }

        return false;
    }

    function getMissingDocuments() {
        const present = checkDocumentPresence();
        const primaryGT = present.groundTruth.length > 0 ? present.groundTruth[0].type : null;

        // ✅ P8 hanya muncul jika GT = KL
        let expectedDocs = EXPECTED_DOCUMENTS.singleReview;
        if (primaryGT !== "KL") {
            expectedDocs = expectedDocs.filter(doc => doc !== "P8");
        }

        return {
            singleReview: expectedDocs.filter(
                (doc) => !present.singleReview.includes(doc)
            )
        };
    }

    function formatMissingDocumentsMessage(missing) {
        const messages = [];
        if (missing.singleReview.length > 0) {
            messages.push(
                `<strong>Dokumen yang belum diupload:</strong> ${missing.singleReview.join(", ")}`
            );
        }
        return messages;
    }

    function validateFiles() {
        const errors = [];
        const warnings = [];
        const present = checkDocumentPresence();
        const missing = getMissingDocuments();
        const gtCount = present.groundTruth.length;
        const selectedType = typeSelectAdv.value;

        // ✅ VALIDASI GROUND TRUTH
        if (gtCount === 0) {
            errors.push(`<strong>Ground Truth WAJIB ada!</strong><br>Pilih salah satu: ${EXPECTED_DOCUMENTS.groundTruth.join(", ")}`);
        } else if (gtCount > 2) {
            errors.push(`<strong>Maksimal 2 Ground Truth!</strong><br>Ditemukan: ${gtCount} file`);
        } else if (gtCount === 1) {
            const gt = present.groundTruth[0];

            // Jika ada suffix tapi cuma 1 GT
            if (gt.hasSuffix) {
                errors.push(`<strong>File "${gt.filename}" memiliki suffix _${gt.suffix}!</strong><br>Perpanjangan wajib 2 GT dengan suffix _1 dan _2`);
            }
        } else if (gtCount === 2) {
            const gt1 = present.groundTruth[0];
            const gt2 = present.groundTruth[1];

            // Check jenis sama
            if (gt1.type !== gt2.type) {
                errors.push(`<strong>2 Ground Truth harus jenis sama!</strong><br>Ditemukan: ${gt1.type} dan ${gt2.type}`);
            } else {
                // Check suffix _1 dan _2
                const suffixes = [gt1.suffix, gt2.suffix].sort();
                if (!gt1.suffix || !gt2.suffix) {
                    errors.push(`<strong>Perpanjangan wajib pakai suffix _1 dan _2!</strong><br>Contoh: ${gt1.type}_1.pdf dan ${gt1.type}_2.pdf`);
                } else if (suffixes[0] !== '1' || suffixes[1] !== '2') {
                    errors.push(`<strong>Suffix harus _1 dan _2!</strong><br>Ditemukan suffix: _${gt1.suffix} dan _${gt2.suffix}`);
                }
            }
        }

        // ✅ VALIDASI TYPE COMPATIBILITY
        if (selectedType && gtCount > 0 && errors.length === 0) {
            if (!validateTypeCompatibility(selectedType)) {
                if (selectedType === "Non-Perpanjangan") {
                    errors.push(`<strong>Type "Non-Perpanjangan" tidak sesuai!</strong><br>Harus 1 GT tanpa suffix _1 atau _2`);
                } else if (selectedType === "Perpanjangan") {
                    errors.push(`<strong>Type "Perpanjangan" tidak sesuai!</strong><br>Harus 2 GT dengan jenis sama dan suffix _1 dan _2`);
                }
            }
        }

        // ✅ VALIDASI TYPE WAJIB DIPILIH
        if (!selectedType && gtCount > 0 && errors.length === 0) {
            errors.push(`<strong>Type wajib dipilih!</strong><br>Pilih "Perpanjangan" atau "Non-Perpanjangan"`);
        }

        // ✅ WARNING untuk dokumen belum lengkap
        if (gtCount > 0 && errors.length === 0) {
            const missingMessages = formatMissingDocumentsMessage(missing);
            if (missingMessages.length > 0) {
                warnings.push(...missingMessages);
            }
        }

        const canUpload = errors.length === 0 && gtCount > 0 && selectedType;
        const gtSummary = present.groundTruth.map(gt => gt.filename).join(", ");

        return {
            valid: errors.length === 0,
            canUpload: canUpload,
            errors,
            warnings,
            hasGroundTruth: gtCount > 0,
            groundTruthSummary: gtSummary,
            groundTruthCount: gtCount
        };
    }

    function updateValidationUI() {
        const validation = validateFiles();
        const count = selectedFilesAdv.length;
        fileCounterAdv.textContent = `${count} file${count !== 1 ? "s" : ""}`;

        if (count === 0) {
            fileCounterAdv.className = "badge bg-secondary";
            validationBadgeAdv.style.display = "none";
            validationAlertAdv.style.display = "none";
            uploadBtnAdv.disabled = true;
            return;
        }

        validationBadgeAdv.style.display = "inline-block";

        if (!validation.canUpload) {
            fileCounterAdv.className = "badge bg-danger";
            validationBadgeAdv.className = "badge badge-validation-error";
            validationBadgeAdv.innerHTML =
                '<i class="bi bi-x-circle me-1"></i>Invalid';
            showValidationAlert("danger", "Upload Tidak Diizinkan", validation.errors);
            uploadBtnAdv.disabled = true;
            return;
        }

        if (validation.warnings.length > 0) {
            fileCounterAdv.className = "badge bg-warning text-dark";
            validationBadgeAdv.className = "badge badge-validation-warning";
            validationBadgeAdv.innerHTML =
                '<i class="bi bi-exclamation-triangle me-1"></i>Incomplete';
            showValidationAlert(
                "warning",
                `Ground Truth: ${validation.groundTruthSummary} ✓ - Dokumen Belum Lengkap`,
                validation.warnings
            );
            uploadBtnAdv.disabled = false;
        } else {
            fileCounterAdv.className = "badge bg-success";
            validationBadgeAdv.className = "badge badge-validation-success";
            validationBadgeAdv.innerHTML =
                '<i class="bi bi-check-circle me-1"></i>Complete';
            validationAlertAdv.style.display = "none";
            uploadBtnAdv.disabled = false;
        }
    }

    function showValidationAlert(type, title, messages) {
        validationAlertAdv.className = `alert alert-${type} alert-dismissible fade show mb-3`;
        validationAlertAdv.style.display = "block";
        const icon =
            type === "danger"
                ? "bi-x-circle-fill"
                : type === "warning"
                    ? "bi-exclamation-triangle-fill"
                    : "bi-check-circle-fill";
        const alertList = messages.map((msg) => `<li>${msg}</li>`).join("");
        validationAlertAdv.querySelector("i").className = `bi ${icon} flex-shrink-0 me-2`;
        validationAlertAdv.querySelector(".alert-title").textContent = title;
        validationAlertAdv.querySelector(".alert-list").innerHTML = alertList;
    }

    function isDuplicateFile(newFile) {
        return selectedFilesAdv.some(
            (existingFile) =>
                existingFile.name === newFile.name &&
                existingFile.size === newFile.size
        );
    }

    /**
     * Start rename mode - show input field
     */
    function startRenameMode(index, displaySpan, editInput, renameBtn) {
        // Guard against null elements
        if (!displaySpan || !editInput || !renameBtn) {
            console.warn('startRenameMode: Missing required DOM elements', {
                displaySpan: !!displaySpan,
                editInput: !!editInput,
                renameBtn: !!renameBtn
            });
            return;
        }

        displaySpan.classList.add('d-none');
        editInput.classList.remove('d-none');
        renameBtn.classList.add('d-none');

        editInput.focus();
        editInput.select();

        // Ensure parentNode exists before replacing
        if (!editInput.parentNode) {
            console.warn('startRenameMode: editInput has no parentNode');
            return;
        }

        const newEditInput = editInput.cloneNode(true);
        editInput.parentNode.replaceChild(newEditInput, editInput);

        newEditInput.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                saveRename(index, newEditInput, displaySpan, renameBtn);
            }
        });

        newEditInput.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                e.preventDefault();
                cancelRename(displaySpan, newEditInput, renameBtn);
            }
        });

        newEditInput.addEventListener('blur', function (e) {
            setTimeout(() => {
                saveRename(index, newEditInput, displaySpan, renameBtn);
            }, 200);
        });
    }

    /**
     * Save renamed file
     */
    function saveRename(index, editInput, displaySpan, renameBtn) {
        const newName = editInput.value.trim();
        const oldFile = selectedFilesAdv[index];
        const oldFileName = oldFile.name.replace('.pdf', '');

        if (newName === oldFileName) {
            cancelRename(displaySpan, editInput, renameBtn);
            return;
        }

        if (!newName) {
            showNotification('Nama file tidak boleh kosong.', 'danger');
            editInput.value = oldFileName;
            cancelRename(displaySpan, editInput, renameBtn);
            return;
        }

        if (!/^[a-zA-Z0-9\s\-_\.]+$/.test(newName)) {
            showNotification('Nama file hanya boleh mengandung huruf, angka, spasi, dash, underscore, dan titik.', 'danger');
            editInput.value = oldFileName;
            cancelRename(displaySpan, editInput, renameBtn);
            return;
        }

        const newFileName = `${newName}.pdf`;
        const isDuplicate = selectedFilesAdv.some((f, idx) =>
            idx !== index && f.name === newFileName
        );

        if (isDuplicate) {
            showNotification(`File dengan nama "${newFileName}" sudah ada.`, 'danger');
            editInput.value = oldFileName;
            cancelRename(displaySpan, editInput, renameBtn);
            return;
        }

        const newFile = new File([oldFile], newFileName, { type: oldFile.type });
        selectedFilesAdv[index] = newFile;

        displayFilesAdv();
        showNotification(`File berhasil direname menjadi "${newFileName}"`, 'success');
    }

    /**
     * Cancel rename - restore display mode
     */
    function cancelRename(displaySpan, editInput, renameBtn) {
        editInput.classList.add('d-none');
        displaySpan.classList.remove('d-none');
        renameBtn.classList.remove('d-none');
    }

    function displayFilesAdv() {
        updateTypeSelect();
        updateValidationUI();

        if (selectedFilesAdv.length > 0) {
            const listGroup = document.createElement("div");
            listGroup.className = "list-group list-group-flush";

            selectedFilesAdv.forEach((file, index) => {
                const listItem = document.createElement("div");
                listItem.className =
                    "list-group-item d-flex justify-content-between align-items-center px-3 py-2";

                const fileName = file.name.replace('.pdf', '');

                const isGT = isGroundTruthFile(file.name);
                const gtBadge = isGT
                    ? '<span class="badge bg-primary ms-2" style="font-size: 0.7rem;">Ground Truth</span>'
                    : "";

                listItem.innerHTML = `
                    <div class="d-flex align-items-center flex-grow-1" style="min-width: 0;">
                        <i class="bi bi-file-earmark-pdf text-danger me-2 fs-5"></i>
                        <div class="d-flex align-items-center flex-grow-1" style="min-width: 0;">
                            <div class="fw-medium d-flex align-items-center flex-grow-1" style="min-width: 0;">
                                <span class="file-name-display" data-index="${index}" title="${file.name}">
                                    ${file.name}
                                </span>
                                <input type="text" class="form-control form-control-sm file-name-edit d-none" 
                                       data-index="${index}" 
                                       value="${fileName}" 
                                       style="max-width: 250px;">
                                <button class="btn btn-sm btn-link p-0 ms-2 rename-btn" data-index="${index}" title="Rename file">
                                    <i class="bi bi-pencil-square text-primary"></i>
                                </button>
                                ${gtBadge}
                            </div>
                            <small class="text-muted file-size-info">${(file.size / 1024 / 1024).toFixed(2)} MB</small>
                        </div>
                    </div>
                    <button class="btn btn-sm btn-danger remove-btn" data-index="${index}" title="Hapus file">
                        <i class="bi bi-trash me-1"></i>Hapus
                    </button>
                `;

                listItem.querySelector(".remove-btn").addEventListener("click", function (e) {
                    e.stopPropagation();
                    selectedFilesAdv.splice(index, 1);
                    displayFilesAdv();
                });

                const renameBtn = listItem.querySelector(".rename-btn");
                const displaySpan = listItem.querySelector(".file-name-display");
                const editInput = listItem.querySelector(".file-name-edit");

                renameBtn.addEventListener("click", function (e) {
                    e.stopPropagation();
                    startRenameMode(index, displaySpan, editInput, renameBtn);
                });

                displaySpan.addEventListener("dblclick", function (e) {
                    e.stopPropagation();
                    startRenameMode(index, displaySpan, editInput, renameBtn);
                });

                listGroup.appendChild(listItem);
            });

            fileListAdv.innerHTML = "";
            fileListAdv.appendChild(listGroup);
        } else {
            fileListAdv.innerHTML = `
                <div class="text-center py-4">
                    <i class="bi bi-files fs-1 text-muted mb-2"></i>
                    <p class="text-muted mb-1">Belum ada file yang dipilih</p>
                    <small class="text-muted">Klik area ini atau paste file PDF (Ctrl+V)</small>
                </div>
            `;
        }
    }

    const TICKET_STATUS_POLL_INTERVAL_MS = 2500;
    const TICKET_STATUS_MAX_POLLS = 480;

    function showLoading() {
        if (document.activeElement && typeof document.activeElement.blur === 'function') {
            document.activeElement.blur();
        }
        loadingOverlay.classList.add("show");
        document.body.style.overflow = "hidden";
        const loadingText = loadingOverlay.querySelector('.loading-text');
        const loadingSubtext = loadingOverlay.querySelector('.text-muted');
        const spinner = loadingOverlay.querySelector('.loading-spinner');
        if (loadingText) loadingText.innerHTML = 'Ekstraksi Informasi<span class="loading-dots"></span>';
        if (loadingSubtext) loadingSubtext.textContent = 'Mohon tunggu, proses ekstraksi informasi sedang berlangsung';
        if (spinner) spinner.style.display = 'block';
    }

    function updateLoadingSubtext(text) {
        const el = loadingOverlay.querySelector('.text-muted');
        if (el) el.textContent = text;
    }

    function pollTicketStatusThenRedirect(ticketNumber) {
        const loadingText = loadingOverlay.querySelector('.loading-text');
        const loadingSubtext = loadingOverlay.querySelector('.text-muted');
        if (loadingText) loadingText.innerHTML = 'Memproses ekstraksi<span class="loading-dots"></span>';
        if (loadingSubtext) loadingSubtext.textContent = 'Ekstraksi sedang diproses, mohon tunggu...';
        let pollCount = 0;
        const pollInterval = setInterval(() => {
            pollCount++;
            fetch(`/projess/api/ticket-status/${encodeURIComponent(ticketNumber)}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then((r) => r.json())
                .then((data) => {
                    if (data.status === 'completed' && data.processed === true) {
                        clearInterval(pollInterval);
                        showSuccessOverlay(ticketNumber);
                    }
                })
                .catch(() => {});
            if (pollCount >= TICKET_STATUS_MAX_POLLS) {
                clearInterval(pollInterval);
                hideLoading();
                showErrorModal('Proses ekstraksi memakan waktu terlalu lama. Silakan cek tiket di halaman history atau coba lagi.');
            }
        }, TICKET_STATUS_POLL_INTERVAL_MS);
    }

    function hideLoading() {
        loadingOverlay.classList.remove("show");
        document.body.style.overflow = "";
    }

    function showSuccessOverlay(ticketNumber) {
        const loadingText = loadingOverlay.querySelector('.loading-text');
        const loadingSubtext = loadingOverlay.querySelector('.text-muted');
        const spinner = loadingOverlay.querySelector('.loading-spinner');

        if (spinner) {
            spinner.style.display = 'none';
        }

        if (loadingText) {
            loadingText.innerHTML = '<i class="bi bi-check-circle-fill text-success me-2"></i>Ekstraksi Berhasil!';
            loadingText.style.color = '#198754';
        }

        if (loadingSubtext) {
            loadingSubtext.innerHTML = 'Mengalihkan ke halaman validasi<span class="loading-dots"></span>';
        }

        setTimeout(() => {
            window.location.href = `/projess/validate-ground-truth/${ticketNumber}`;
        }, 1500);
    }

    function closeAllModals() {
        const modals = document.querySelectorAll(".modal.show");
        modals.forEach((modal) => {
            const instance = bootstrap.Modal.getInstance(modal);
            if (instance) instance.hide();
        });
        const backdrops = document.querySelectorAll(".modal-backdrop");
        backdrops.forEach((backdrop) => backdrop.remove());
        document.body.classList.remove("modal-open");
        document.body.style.removeProperty("overflow");
        document.body.style.removeProperty("padding-right");
    }

    function showErrorModal(message) {
        hideLoading();
        closeAllModals();

        setTimeout(() => {
            const errorMessageElement = document.getElementById("errorMessage");
            if (errorMessageElement) {
                errorMessageElement.textContent = "Terjadi kesalahan saat memproses dokumen. Silakan coba lagi atau hubungi tim support jika masalah berlanjut.";
            }

            selectedFilesAdv = [];
            displayFilesAdv();

            const ticketInput = document.getElementById("ticketNumberAdv");
            const companySelect = document.getElementById("companySelectAdv");

            if (ticketInput) ticketInput.value = "";
            if (companySelect) companySelect.value = "";
            if (fileInputAdv) fileInputAdv.value = "";
            if (typeSelectAdv) typeSelectAdv.value = "";

            const errorModal = new bootstrap.Modal(document.getElementById("errorModal"));
            errorModal.show();

            console.error("Upload error details:", message);
            console.log('Form auto-reset completed');
        }, 100);
    }

    function showNotification(message, type = "success") {
        const notification = document.createElement("div");
        notification.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3`;
        notification.style.zIndex = "9999";
        notification.style.minWidth = "300px";

        const icon = type === "success" ? "check-circle-fill" : "exclamation-triangle-fill";

        notification.innerHTML = `
            <i class="bi bi-${icon} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(notification);

        setTimeout(() => {
            notification.remove();
        }, 3000);
    }

    // ====== EVENT LISTENER: TYPE SELECT CHANGE ======
    if (typeSelectAdv) {
        typeSelectAdv.addEventListener("change", function () {
            updateValidationUI();
        });
    }

    // ====== EVENT LISTENER: FILE INPUT (KLIK) ======
    if (fileInputAdv) {
        fileInputAdv.addEventListener("change", function (e) {
            const files = Array.from(e.target.files);

            files.forEach(file => {
                if (file.type === "application/pdf") {
                    if (!isDuplicateFile(file)) {
                        selectedFilesAdv.push(file);
                    } else {
                        showNotification(`File "${file.name}" sudah ada dalam daftar.`, "warning");
                    }
                } else {
                    showNotification(`File "${file.name}" bukan PDF.`, "warning");
                }
            });

            displayFilesAdv();
            fileInputAdv.value = "";
        });
    }

    // ====== EVENT LISTENER: SELECT FILES BUTTON ======
    const selectFilesBtn = document.getElementById("selectFilesBtn");
    if (selectFilesBtn) {
        selectFilesBtn.addEventListener("click", function (e) {
            e.stopPropagation();
            fileInputAdv.click();
        });
    }

    // ====== EVENT LISTENER: CLICK UPLOAD AREA ======
    if (fileUploadAreaAdv) {
        fileUploadAreaAdv.addEventListener("click", function (e) {
            if (e.target.closest("button") || e.target.closest(".remove-btn")) {
                return;
            }
            fileInputAdv.click();
        });

        fileUploadAreaAdv.addEventListener("paste", function (e) {
            e.preventDefault();

            const items = e.clipboardData.items;
            const pastedFiles = [];

            for (let i = 0; i < items.length; i++) {
                const item = items[i];

                if (item.kind === "file") {
                    const file = item.getAsFile();

                    if (file && file.type === "application/pdf") {
                        if (!isDuplicateFile(file)) {
                            pastedFiles.push(file);
                        } else {
                            showNotification(`File "${file.name}" sudah ada.`, "warning");
                        }
                    } else if (file) {
                        showNotification(`File "${file.name}" bukan PDF.`, "warning");
                    }
                }
            }

            if (pastedFiles.length > 0) {
                selectedFilesAdv.push(...pastedFiles);
                displayFilesAdv();
                showNotification(`${pastedFiles.length} file berhasil di-paste!`, "success");
            }
        });

        fileUploadAreaAdv.addEventListener("dragover", function (e) {
            e.preventDefault();
            fileUploadAreaAdv.classList.add("border-primary", "bg-light");
        });

        fileUploadAreaAdv.addEventListener("dragleave", function (e) {
            e.preventDefault();
            fileUploadAreaAdv.classList.remove("border-primary", "bg-light");
        });

        fileUploadAreaAdv.addEventListener("drop", function (e) {
            e.preventDefault();
            fileUploadAreaAdv.classList.remove("border-primary", "bg-light");

            const droppedFiles = Array.from(e.dataTransfer.files);
            let addedCount = 0;

            droppedFiles.forEach(file => {
                if (file.type === "application/pdf") {
                    if (!isDuplicateFile(file)) {
                        selectedFilesAdv.push(file);
                        addedCount++;
                    }
                } else {
                    showNotification(`File "${file.name}" bukan PDF.`, "warning");
                }
            });

            if (addedCount > 0) {
                displayFilesAdv();
                showNotification(`${addedCount} file berhasil ditambahkan!`, "success");
            }
        });

        fileUploadAreaAdv.setAttribute("tabindex", "0");

        fileUploadAreaAdv.addEventListener("focus", function () {
            this.style.outline = "2px solid #0d6efd";
            this.style.outlineOffset = "2px";
        });

        fileUploadAreaAdv.addEventListener("blur", function () {
            this.style.outline = "none";
        });
    }

    // ====== UPLOAD BUTTON ======
    if (uploadBtnAdv) {
        uploadBtnAdv.addEventListener("click", function () {
            const ticketNumber = document.getElementById("ticketNumberAdv").value.trim();
            const companySelect = document.getElementById("companySelectAdv");
            const companyName = companySelect.value;
            const ticketType = typeSelectAdv.value;

            if (!ticketNumber) return alert("Nomor tiket wajib diisi.");
            if (!companyName) return alert("Silakan pilih mitra.");
            if (!ticketType) return alert("Silakan pilih type tiket.");
            if (!selectedFilesAdv.length) return alert("Silakan pilih file terlebih dahulu.");

            const selectedOption = companySelect.options[companySelect.selectedIndex];
            const companyId = selectedOption.dataset.companyId;
            if (!companyId) return alert("Company ID tidak ditemukan.");

            const validation = validateFiles();
            if (!validation.canUpload) {
                if (validation.errors.length > 0) {
                    const errorMsg = validation.errors
                        .map((e) => e.replace(/<\/?[^>]+(>|$)/g, ""))
                        .join("\n\n");
                    return alert(`❌ Upload tidak dapat dilakukan!\n\n${errorMsg}`);
                }
                return;
            }

            if (validation.warnings.length > 0) {
                const warningMsg = validation.warnings
                    .map((w) => w.replace(/<\/?[^>]+(>|$)/g, ""))
                    .join("\n");
                const proceed = confirm(
                    `⚠️ Perhatian:\n\nGround Truth: ${validation.groundTruthSummary}\nType: ${ticketType}\n\n${warningMsg}\n\nLanjutkan upload?`
                );
                if (!proceed) return;
            }

            if (document.activeElement && typeof document.activeElement.blur === 'function') {
                document.activeElement.blur();
            }
            closeAllModals();
            setTimeout(() => showLoading(), 300);

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "";
            const chunks = [];
            for (let i = 0; i < selectedFilesAdv.length; i += CHUNK_SIZE) {
                chunks.push(selectedFilesAdv.slice(i, i + CHUNK_SIZE));
            }
            const totalChunks = chunks.length;

            function parseUploadResponse(response, fallbackTicket) {
                if (response.status === 401 || response.status === 419) {
                    hideLoading();
                    alert('Session Anda telah expired. Halaman akan di-refresh untuk mendapatkan session baru.');
                    setTimeout(() => window.location.reload(), 1000);
                    throw new Error('Session expired');
                }
                if (response.status === 413) throw new Error('File terlalu besar. Maksimal ukuran file yang diizinkan oleh server telah terlampaui.');
                if (response.status === 504 || response.status === 502) throw new Error('Server timeout. Proses upload memakan waktu terlalu lama. Silakan coba lagi atau hubungi administrator.');
                if (response.status === 500) {
                    return response.text().then((text) => {
                        console.error('Server error response:', text.substring(0, 500));
                        throw new Error('Internal server error (500). Silakan periksa log server untuk detail.');
                    });
                }
                if (!response.ok) {
                    return response.text().then((text) => {
                        try {
                            const err = JSON.parse(text);
                            throw new Error(err.error || "HTTP error: " + response.status);
                        } catch (e) {
                            if (e.message && e.message.startsWith("HTTP error")) throw e;
                            throw new Error("HTTP error: " + response.status);
                        }
                    });
                }
                if (response.status === 202) {
                    return response.text().then((text) => {
                        try {
                            const data = JSON.parse(text);
                            return data.ticket ? data : { success: true, ticket: fallbackTicket, status: "processing" };
                        } catch (e) {
                            const start = text.indexOf("{");
                            const end = text.lastIndexOf("}") + 1;
                            if (start !== -1 && end > start) {
                                try {
                                    const data = JSON.parse(text.slice(start, end));
                                    if (data.ticket) return data;
                                } catch (e2) {}
                            }
                            return { success: true, ticket: fallbackTicket, status: "processing" };
                        }
                    });
                }
                return response.json();
            }

            function sendChunk(chunkIndex, files) {
                const formData = new FormData();
                files.forEach((f) => formData.append("files[]", f));
                formData.append("ticket", ticketNumber);
                formData.append("company_id", companyId);
                formData.append("nama_mitra", companyName);
                formData.append("type", ticketType);
                if (totalChunks > 1) {
                    formData.append("chunk_index", chunkIndex);
                    formData.append("total_chunks", totalChunks);
                }
                return fetch(ADVANCE_UPLOAD_ENDPOINT, {
                    method: "POST",
                    body: formData,
                    headers: { "X-CSRF-TOKEN": csrfToken, "Accept": "application/json", "X-Requested-With": "XMLHttpRequest" },
                }).then((response) => parseUploadResponse(response, ticketNumber));
            }

            (async function runChunkedUpload() {
                try {
                    let lastData = null;
                    for (let i = 0; i < chunks.length; i++) {
                        if (totalChunks > 1) {
                            updateLoadingSubtext("Mengunggah chunk " + (i + 1) + "/" + totalChunks + " dari " + selectedFilesAdv.length + " file...");
                        }
                        lastData = await sendChunk(i, chunks[i]);
                    }
                    console.log("Upload success:", lastData);
                    selectedFilesAdv = [];
                    displayFilesAdv();
                    document.getElementById("ticketNumberAdv").value = "";
                    document.getElementById("companySelectAdv").value = "";
                    typeSelectAdv.value = "";
                    pollTicketStatusThenRedirect(lastData.ticket || ticketNumber);
                } catch (error) {
                    console.error("Upload error:", error);
                    hideLoading();
                    showErrorModal("Terjadi kesalahan: " + error.message);
                }
            })();
        });
    }

    // ====== LOAD COMPANIES ======
    fetch("/projess/api/companies")
        .then((response) => response.json())
        .then((data) => {
            const companySelect = document.getElementById("companySelectAdv");
            companySelect.innerHTML = '<option value="">Pilih Mitra</option>';
            companiesData = data;
            data.forEach((company) => {
                const option = document.createElement("option");
                option.value = company.nama_perusahaan;
                option.dataset.companyId = company.id;
                option.textContent = company.nama_perusahaan;
                companySelect.appendChild(option);
            });
        })
        .catch((error) => console.error("Error loading companies:", error));

    displayFilesAdv();
});