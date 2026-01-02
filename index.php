<?php
require_once 'setting/connect.php';
require 'layout/header.php';
?>

    <div class="row">
        <div class="col-md-8 mb-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="">
                            <h5 class="card-title mb-0">LinkTree</h5>
                            <p class="card-text">Buat Link Tree dengan AI</p>
                        </div>
                        <div class="badge badge-outline-success badge-dashed">
                            Tersisa: <span id="limitCount">0</span>x
                        </div>
                    </div>

                    <form id="formGenerate" action="">
                        <div class="mb-3">
                            <label for="deskripsi" class="form-label">Deskripsi</label>
                            <textarea name="deskripsi" id="deskripsi" class="form-control" rows="5" maxlength="1000" placeholder="Deskripsikan Link Tree dan desain yang Anda inginkan..." required></textarea>
                            <small class="text-muted"><span id="charCount">0</span>/1000 karakter</small>
                        </div>
                        <div class="mb-3">
                            <label for="custom_url" class="form-label">Custom URL</label>
                            <div class="input-group">
                                <div class="input-group-text"><?= $_SERVER['HTTP_HOST'] ?>/lt/</div>
                                <input type="text" id="custom_url" class="form-control" name="custom_url" pattern="[a-zA-Z0-9_\-]+" minlength="3" maxlength="50" placeholder="min: 3 | maks: 50 karakter" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="url_logo" class="form-label">URL Logo <span class="text-muted">(Opsional)</span></label>
                            <input type="url" id="url_logo" class="form-control" name="url_logo" placeholder="https://example.com/logo.png">
                        </div>
                        <div class="mb-3">
                            <label for="url" class="form-label">Link</label>
                            <div id="urlsContainer">
                                <div class="url-input-group mb-2">
                                    <input type="url" class="form-control" name="urls[]" placeholder="https://example.com" required>
                                </div>
                            </div>
                            <a href="#" id="btnAddUrl" class="text-sm text-success gap-2 d-inline-flex align-items-center my-2">
                                <i data-lucide="plus" class="w-4 h-4"></i> Tambah URL
                            </a>
                        </div>

                        <div class="d-flex flex-column flex-sm-row gap-2">
                            <button type="submit" id="btnGenerate" class="btn btn-primary flex-sm-grow-1 inline-flex items-center justify-center gap-2">
                                <i data-lucide="blocks" class="w-4 h-4"></i>
                                <span>Buat LinkTree</span>
                            </button>
                            <button type="button" id="btnSave" class="btn btn-soft-success w-100 w-sm-auto" style="display: none;">
                                <span>Simpan</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-3">
            <div class="card">
                <div class="card-header p-2">
                    <h4 class="card-title text-center mb-0">Preview</h4>
                </div>
                <div class="card-body">
                    <div id="preview" style="min-height: 200px; border: 1px dashed var(--border); border-radius: 8px; padding: 16px; text-align: center; color: var(--text-secondary);">
                        <p>Preview akan muncul di sini setelah generate</p>
                    </div>

                    <div id="urlLT" style="display: none;" class="mt-3">
                        <div class="mb-0">
                            <label for="url_linktree" class="form-label">URL LinkTree</label>
                            <input type="text" id="url_linktree" class="form-control" readonly>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="limitModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-body p-5">
                    <div class="text-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mx-auto mb-3 text-warning">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" x2="12" y1="8" y2="12"></line>
                            <line x1="12" x2="12.01" y1="16" y2="16"></line>
                        </svg>
                        <p class="mb-0" id="limitMessage"></p>

                        <div class="mt-3">
                            <button type="button" class="btn btn-soft-primary" data-bs-dismiss="modal">Oke</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const csrfToken = '<?= $csrf_token ?>';
        let generatedHtml = '';
        let currentDeskripsi = '';
        let currentUrls = [];
        let retryCount = 0;
        const MAX_RETRIES = 1;

        window.addEventListener('DOMContentLoaded', async function() {
            if (window.location.hash === '#notfound') {
                showToast({
                    title: 'Error',
                    message: 'LinkTree tidak ditemukan',
                    type: 'danger'
                });
                history.replaceState(null, null, ' ');
            }
            await fetchCurrentLimit();
        });

        async function fetchCurrentLimit() {
            try {
                const response = await fetch('generate.php?get_limit=1');
                const result = await response.json();
                if (result.success) {
                    updateLimitBadge(result.remaining);
                }
            } catch (error) {
                updateLimitBadge(3);
            }
        }

        async function checkLimit() {
            try {
                const response = await fetch('generate.php?get_limit=1');
                const result = await response.json();
                if (result.success) {
                    updateLimitBadge(result.remaining);
                    if (result.remaining <= 0) {
                        return {
                            canGenerate: false,
                            message: 'Anda telah mencapai batas 3x generate per hari. Silakan coba lagi besok.'
                        };
                    }
                    return {
                        canGenerate: true
                    };
                }
            } catch (error) {
                return {
                    canGenerate: true
                };
            }
            return {
                canGenerate: true
            };
        }

        document.getElementById('deskripsi').addEventListener('input', function() {
            document.getElementById('charCount').textContent = this.value.length;
        });

        document.getElementById('btnAddUrl').addEventListener('click', function(e) {
            e.preventDefault();
            const urlsContainer = document.getElementById('urlsContainer');
            const newUrlGroup = document.createElement('div');
            newUrlGroup.className = 'url-input-group mb-2 d-flex gap-2';
            newUrlGroup.innerHTML = `
                            <input type="url" class="form-control" name="urls[]" placeholder="https://example.com" required>
                            <button type="button" class="btn btn-soft-danger btn-sm" onclick="removeUrlField(this)">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"></path><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path></svg>
                            </button>
                        `;
            urlsContainer.appendChild(newUrlGroup);
        });

        window.removeUrlField = function(btn) {
            const urlGroups = document.querySelectorAll('.url-input-group');
            if (urlGroups.length > 1) {
                btn.closest('.url-input-group').remove();
            } else {
                showToast({
                    title: 'Peringatan',
                    message: 'Minimal harus ada 1 URL',
                    type: 'warning'
                });
            }
        };

        document.getElementById('formGenerate').addEventListener('submit', async function(e) {
            e.preventDefault();
            retryCount = 0;
            await generateLinkTree();
        });

        async function generateLinkTree() {
            const deskripsi = document.getElementById('deskripsi').value.trim();
            const customUrl = document.getElementById('custom_url').value.trim();
            const urlLogo = document.getElementById('url_logo').value.trim();
            const urlInputs = document.querySelectorAll('input[name="urls[]"]');
            const urls = Array.from(urlInputs).map(input => input.value.trim()).filter(url => url !== '');

            if (!deskripsi || !customUrl || urls.length === 0) {
                showToast({
                    title: 'Peringatan',
                    message: 'Mohon lengkapi semua field',
                    type: 'warning'
                });
                return;
            }

            const limitCheck = await checkLimit();
            if (!limitCheck.canGenerate) {
                showRateLimitModal(limitCheck.message);
                return;
            }

            const btnGenerate = document.getElementById('btnGenerate');
            const btnSave = document.getElementById('btnSave');
            const originalBtnHtml = btnGenerate.innerHTML;

            btnGenerate.disabled = true;
            btnSave.disabled = true;
            btnGenerate.classList.add('btn-loading');
            btnGenerate.textContent = 'Proses...';

            showLoadingProgress();

            const formData = new FormData();
            formData.append('csrf_token', csrfToken);
            formData.append('deskripsi', deskripsi);
            formData.append('custom_url', customUrl);
            formData.append('url_logo', urlLogo);
            urls.forEach(url => formData.append('urls[]', url));

            try {
                const response = await fetch('generate.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.limit_reached) {
                    await waitForTailBoot();
                    showRateLimitModal('generate', result.message);
                    hideLoadingProgress(false);
                    updateLimitBadge(result.remaining || 0);
                    return;
                }

                if (result.success) {
                    generatedHtml = result.html;
                    currentDeskripsi = deskripsi;
                    currentUrls = urls;

                    hideLoadingProgress(true, result.html);

                    document.getElementById('url_linktree').value = window.location.origin + '/lt/' + customUrl;
                    document.getElementById('urlLT').style.display = 'block';
                    document.getElementById('btnSave').style.display = 'block';

                    updateLimitBadge(result.remaining);
                    showToast({
                        title: 'Berhasil',
                        message: 'Generate berhasil!',
                        type: 'success'
                    });
                } else {
                    hideLoadingProgress(false);

                    if (retryCount < MAX_RETRIES) {
                        retryCount++;
                        showToast({
                            title: 'Info',
                            message: 'Generate gagal, mencoba lagi...',
                            type: 'warning'
                        });
                        setTimeout(() => generateLinkTree(), 2000);
                        return;
                    }

                    showToast({
                        title: 'Gagal',
                        message: result.error,
                        type: 'danger'
                    });
                }
            } catch (error) {
                hideLoadingProgress(false);
                showToast({
                    title: 'Error',
                    message: error.message,
                    type: 'danger'
                });
            } finally {
                btnGenerate.disabled = false;
                btnSave.disabled = false;
                btnGenerate.classList.remove('btn-loading');
                btnGenerate.innerHTML = originalBtnHtml;
            }
        }

        function showLoadingProgress() {
            const previewDiv = document.getElementById('preview');
            previewDiv.innerHTML = `
                            <div class="text-start">
                                <div class="mb-4">
                                    <div class="spinner-border text-primary mb-3" role="status"></div>
                                </div>
                                <div class="timeline">
                                    <div class="timeline-item" id="step1">
                                        <div class="timeline-marker"></div>
                                        <div class="timeline-content">
                                            <p class="text-sm mb-0">Memproses permintaan...</p>
                                        </div>
                                    </div>
                                    <div class="timeline-item" id="step2">
                                        <div class="timeline-marker"></div>
                                        <div class="timeline-content">
                                            <p class="text-sm mb-0">Mengirim ke AI...</p>
                                        </div>
                                    </div>
                                    <div class="timeline-item" id="step3">
                                        <div class="timeline-marker"></div>
                                        <div class="timeline-content">
                                            <p class="text-sm mb-0">Menunggu response...</p>
                                        </div>
                                    </div>
                                    <div class="timeline-item" id="step4">
                                        <div class="timeline-marker"></div>
                                        <div class="timeline-content">
                                            <p class="text-sm mb-0">Memproses hasil...</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <style>
                                .timeline { position: relative; padding-left: 20px; }
                                .timeline-item { position: relative; padding-bottom: 20px; }
                                .timeline-item:before { content: ''; position: absolute; left: -15px; top: 6px; bottom: -14px; width: 2px; background: var(--border); }
                                .timeline-item:last-child:before { display: none; }
                                .timeline-marker { position: absolute; left: -20px; top: 3px; width: 12px; height: 12px; border-radius: 50%; background: var(--bg-secondary); border: 2px solid var(--border); transition: all 0.3s; }
                                .timeline-item.active .timeline-marker { background: var(--primary); border-color: var(--primary); }
                                .timeline-item.completed .timeline-marker { background: var(--success); border-color: var(--success); }
                                .timeline-content { padding-left: 10px; color: var(--text-secondary); transition: color 0.3s; }
                                .timeline-item.active .timeline-content { color: var(--primary); font-weight: 500; }
                                .timeline-item.completed .timeline-content { color: var(--success); }
                            </style>
                        `;

            const steps = ['step1', 'step2', 'step3', 'step4'];
            let currentStepIndex = 0;

            function activateStep() {
                if (currentStepIndex < steps.length) {
                    const step = document.getElementById(steps[currentStepIndex]);
                    if (step) {
                        step.classList.add('active');
                        if (currentStepIndex > 0) {
                            document.getElementById(steps[currentStepIndex - 1]).classList.remove('active');
                            document.getElementById(steps[currentStepIndex - 1]).classList.add('completed');
                        }
                    }
                    currentStepIndex++;
                    setTimeout(activateStep, 1500);
                }
            }

            activateStep();
        }

        function hideLoadingProgress(success, html = null) {
            const previewDiv = document.getElementById('preview');

            if (success && html) {
                previewDiv.innerHTML = '<iframe style="width: 100%; height: 500px; border: 1px solid var(--border); border-radius: 8px;"></iframe>';
                const iframe = previewDiv.querySelector('iframe');
                iframe.contentDocument.open();
                iframe.contentDocument.write(html);
                iframe.contentDocument.close();
            } else if (!success) {
                previewDiv.innerHTML = `
                                <div class="text-center py-5">
                                    <i data-lucide="x-circle" class="w-16 h-16 mx-auto mb-3 text-danger"></i>
                                    <p class="text-muted mb-0">Gagal membuat LinkTree</p>
                                </div>
                            `;
                setTimeout(() => lucide.createIcons(), 50);
            }
        }



        function resetToGenerateMode() {
            generatedHtml = '';
            currentDeskripsi = '';
            currentUrls = [];
            retryCount = 0;

            const cardTitle = document.querySelector('.col-md-8 .card-title');
            cardTitle.textContent = 'LinkTree';

            const cardText = document.querySelector('.col-md-8 .card-text');
            cardText.textContent = 'Buat Link Tree dengan AI';

            const btnGenerate = document.getElementById('btnGenerate');
            btnGenerate.className = 'btn btn-primary';
            btnGenerate.innerHTML = '<i data-lucide="blocks" class="w-4 h-4"></i> <span>Buat LinkTree</span>';

            document.getElementById('deskripsi').value = '';
            document.getElementById('custom_url').value = '';
            document.getElementById('url_logo').value = '';
            document.getElementById('charCount').textContent = '0';

            const urlsContainer = document.getElementById('urlsContainer');
            urlsContainer.innerHTML = `
                        <div class="url-input-group mb-2">
                            <input type="url" class="form-control" name="urls[]" placeholder="https://example.com" required>
                        </div>
                    `;

            document.getElementById('btnAddUrl').style.display = 'inline-flex';

            const previewDiv = document.getElementById('preview');
            previewDiv.style = 'min-height: 200px; border: 1px dashed var(--border); border-radius: 8px; padding: 16px; text-align: center; color: var(--text-secondary);';
            previewDiv.innerHTML = '<p>Preview akan muncul di sini setelah generate</p>';

            document.getElementById('urlLT').style.display = 'none';
            document.getElementById('url_linktree').value = '';
            document.getElementById('btnSave').style.display = 'none';
        }

        document.getElementById('btnSave').addEventListener('click', async function() {
            if (!generatedHtml) {
                showToast({
                    title: 'Peringatan',
                    message: 'Belum ada LinkTree yang di-generate',
                    type: 'warning'
                });
                return;
            }

            const customUrl = document.getElementById('custom_url').value.trim();
            const urlLogo = document.getElementById('url_logo').value.trim();

            if (!currentDeskripsi || !customUrl || currentUrls.length === 0) {
                showToast({
                    title: 'Peringatan',
                    message: 'Data tidak lengkap',
                    type: 'warning'
                });
                return;
            }

            const btnGenerate = document.getElementById('btnGenerate');
            const btnSave = document.getElementById('btnSave');
            const originalText = btnSave.textContent;

            btnSave.disabled = true;
            btnGenerate.disabled = true;
            btnSave.classList.add('btn-loading');
            btnSave.textContent = 'Menyimpan...';

            const formData = new FormData();
            formData.append('csrf_token', csrfToken);
            formData.append('deskripsi', currentDeskripsi);
            formData.append('custom_url', customUrl);
            formData.append('url_logo', urlLogo);
            formData.append('html_content', generatedHtml);
            currentUrls.forEach(url => formData.append('urls[]', url));

            try {
                const response = await fetch('save.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    await navigator.clipboard.writeText(result.url);
                    showToast({
                        title: 'Berhasil',
                        message: 'LinkTree berhasil disimpan dan URL disalin!',
                        type: 'success'
                    });

                    setTimeout(() => {
                        window.open(result.url, '_blank');
                        resetToGenerateMode();
                    }, 1500);
                } else {
                    showToast({
                        title: 'Gagal',
                        message: result.error,
                        type: 'danger'
                    });
                }
            } catch (error) {
                showToast({
                    title: 'Error',
                    message: error.message,
                    type: 'danger'
                });
            } finally {
                btnSave.disabled = false;
                btnGenerate.disabled = false;
                btnSave.classList.remove('btn-loading');
                btnSave.textContent = originalText;
            }
        });

        function updateLimitBadge(remaining) {
            const badge = document.querySelector('.badge-outline-success') || document.querySelector('.badge-outline-danger') || document.querySelector('.badge-outline-warning');
            const limitCount = document.getElementById('limitCount');

            if (limitCount) {
                limitCount.textContent = remaining;
            }

            if (badge) {
                if (remaining === 0) {
                    badge.className = 'badge badge-outline-danger badge-dashed';
                } else if (remaining === 1) {
                    badge.className = 'badge badge-outline-warning badge-dashed';
                } else {
                    badge.className = 'badge badge-outline-success badge-dashed';
                }
            }
        }

        function showRateLimitModal(message) {
            const limitMessage = document.getElementById('limitMessage');
            if (limitMessage) {
                limitMessage.textContent = message;
            }
            if (window.showModal) {
                showModal('#limitModal');
            }
        }
    </script>

<?php
require 'layout/footer.php';
?>