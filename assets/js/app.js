document.addEventListener('DOMContentLoaded', () => {
    // 1. Mobile Admin Sidebar Drawer Toggle
    const drawerOpenBtn = document.getElementById('mobile-drawer-open');
    const drawerCloseBtn = document.getElementById('mobile-drawer-close');
    const drawerBackdrop = document.getElementById('mobile-drawer-backdrop');
    const adminSidebar = document.getElementById('admin-sidebar');

    function closeDrawer() {
        if (adminSidebar) adminSidebar.classList.remove('open');
        if (drawerBackdrop) drawerBackdrop.classList.remove('open');
    }

    if (drawerOpenBtn && adminSidebar) {
        drawerOpenBtn.addEventListener('click', () => {
            adminSidebar.classList.add('open');
            if (drawerBackdrop) drawerBackdrop.classList.add('open');
        });
    }
    if (drawerCloseBtn) {
        drawerCloseBtn.addEventListener('click', closeDrawer);
    }
    if (drawerBackdrop) {
        drawerBackdrop.addEventListener('click', closeDrawer);
    }

    // 2. Live Preview for Book Form (Add / Edit Book)
    const bookTitleInput = document.querySelector('input[name="title"]');
    const previewCoverTitle = document.getElementById('preview-cover-title');
    if (bookTitleInput && previewCoverTitle) {
        bookTitleInput.addEventListener('input', () => {
            const val = bookTitleInput.value.trim();
            previewCoverTitle.textContent = val !== '' ? val.toUpperCase() : 'JUDUL BUKU BARU';
        });
    }

    // 3. File Input Filename & Cover Image Preview for Dropzones
    document.querySelectorAll('.upload-dropzone input[type="file"]').forEach((fileInput) => {
        fileInput.addEventListener('change', () => {
            const dropzone = fileInput.closest('.upload-dropzone');
            const titleEl = dropzone
                ? (dropzone.querySelector('.upload-title') || dropzone.querySelector('.upload-dropzone-title'))
                : null;

            if (fileInput.files && fileInput.files.length > 0) {
                const file = fileInput.files[0];
                if (titleEl) {
                    titleEl.textContent = file.name;
                }
                if (fileInput.name === 'cover_file' && file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        const previewBox = document.getElementById('book-cover-preview-box');
                        if (previewBox) {
                            previewBox.style.padding = '0';
                            previewBox.innerHTML = `<img src="${e.target.result}" alt="Pratinjau Sampul" style="width:100%;height:100%;object-fit:cover;display:block;position:relative;z-index:3;">`;
                        }
                    };
                    reader.readAsDataURL(file);
                }
            }
        });
    });

    // 4. Live Preview for Category Form (Add / Edit Category)
    const categoryNameInput = document.querySelector('input[name="category_name"]');
    const categoryDescInput = document.querySelector('textarea[name="description"]');
    const previewCatInitial = document.getElementById('preview-category-initial');
    const previewCatName = document.getElementById('preview-category-name');
    const previewCatDesc = document.getElementById('preview-category-desc');

    if (categoryNameInput && previewCatName && previewCatInitial) {
        categoryNameInput.addEventListener('input', () => {
            const val = categoryNameInput.value.trim();
            previewCatName.textContent = val !== '' ? val : 'Nama Kategori';
            previewCatInitial.textContent = val !== '' ? val.charAt(0).toUpperCase() : 'K';
        });
    }

    if (categoryDescInput && previewCatDesc) {
        categoryDescInput.addEventListener('input', () => {
            const val = categoryDescInput.value.trim();
            previewCatDesc.textContent = val !== ''
                ? val
                : 'Jelaskan jenis buku yang termasuk dalam kategori ini.';
        });
    }

    // 5. PDF Reader Toolbar Controls (Zoom Stepper, Select & Fit Width)
    const zoomSelect = document.getElementById('reader-zoom-select');
    const zoomOutBtn = document.getElementById('reader-zoom-out');
    const zoomInBtn = document.getElementById('reader-zoom-in');
    const fitBtn = document.getElementById('reader-fit-btn');
    const readerWorkspace = document.getElementById('reader-workspace');
    const readerIframe = document.getElementById('reader-iframe');

    const zoomLevels = [85, 100, 110, 125, 150];

    function applyZoom(pct) {
        if (!readerIframe) return;
        const baseHeight = window.innerWidth <= 768 ? 540 : 760;
        readerIframe.style.height = `${Math.round(baseHeight * (pct / 100))}px`;
        if (zoomSelect) {
            zoomSelect.value = String(pct);
        }
    }

    if (zoomSelect && readerIframe) {
        zoomSelect.addEventListener('change', () => {
            const pct = parseFloat(zoomSelect.value) || 100;
            const normalized = pct <= 2 ? Math.round(pct * 100) : Math.round(pct);
            applyZoom(normalized);
        });
    }

    if (zoomOutBtn && zoomSelect) {
        zoomOutBtn.addEventListener('click', () => {
            const current = parseInt(zoomSelect.value, 10) || 100;
            const idx = zoomLevels.indexOf(current);
            const next = idx > 0 ? zoomLevels[idx - 1] : zoomLevels[0];
            applyZoom(next);
        });
    }

    if (zoomInBtn && zoomSelect) {
        zoomInBtn.addEventListener('click', () => {
            const current = parseInt(zoomSelect.value, 10) || 100;
            const idx = zoomLevels.indexOf(current);
            const next = (idx >= 0 && idx < zoomLevels.length - 1)
                ? zoomLevels[idx + 1]
                : zoomLevels[zoomLevels.length - 1];
            applyZoom(next);
        });
    }

    if (fitBtn && readerWorkspace) {
        fitBtn.addEventListener('click', () => {
            const isExpanded = readerWorkspace.classList.toggle('expanded');
            const sidePanels = readerWorkspace.querySelectorAll('.reader-side-card');
            sidePanels.forEach((p) => {
                p.style.display = isExpanded ? 'none' : '';
            });
            fitBtn.textContent = isExpanded ? 'Tampilkan panel' : 'Lebar halaman';
        });
    }
});
