@once
    <div class="modal fade" id="filePreviewModal" tabindex="-1" aria-labelledby="filePreviewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Preview Dokumen</div>
                        <h5 class="modal-title" id="filePreviewModalLabel">Dokumen</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body p-0">
                    <iframe
                        data-file-preview-frame
                        title="Preview dokumen"
                        class="w-100 border-0"
                        style="height: min(76vh, 760px); background: #f6f8fb;"
                    ></iframe>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modalElement = document.getElementById('filePreviewModal');
            const frame = modalElement?.querySelector('[data-file-preview-frame]');
            const title = document.getElementById('filePreviewModalLabel');
            const modal = modalElement ? new bootstrap.Modal(modalElement) : null;

            document.querySelectorAll('[data-file-preview-url]').forEach(function (button) {
                button.addEventListener('click', function () {
                    if (!modal || !frame) return;

                    if (title) {
                        title.textContent = button.dataset.filePreviewTitle || 'Dokumen';
                    }

                    frame.src = button.dataset.filePreviewUrl || '';
                    modal.show();
                });
            });

            modalElement?.addEventListener('hidden.bs.modal', function () {
                if (frame) frame.src = 'about:blank';
            });
        });
    </script>
@endonce
