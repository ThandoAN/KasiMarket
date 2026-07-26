document.addEventListener('DOMContentLoaded', () => {

    const dropZone  = document.getElementById('photoDropZone');
    const fileInput = document.getElementById('productImage');
    const preview   = document.getElementById('photoPreview');
    const dropText  = document.getElementById('dropText');

    if (dropZone && fileInput) {
        dropZone.addEventListener('click', () => fileInput.click());

        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('drag-over');
        });

        dropZone.addEventListener('dragleave', () => {
            dropZone.classList.remove('drag-over');
        });

        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('drag-over');
            const file = e.dataTransfer.files[0];
            if (file && file.type.startsWith('image/')) {
                fileInput.files = e.dataTransfer.files;
                showPreview(file);
            }
        });

        fileInput.addEventListener('change', () => {
            const file = fileInput.files[0];
            if (file) showPreview(file);
        });

        function showPreview(file) {
            const reader = new FileReader();
            reader.onload = (e) => {
                if (dropText)  dropText.style.display  = 'none';
                if (preview) {
                    preview.src   = e.target.result;
                    preview.style.display = 'block';
                }
            };
            reader.readAsDataURL(file);
        }
    }

    const passInput  = document.getElementById('password');
    const passBar    = document.getElementById('passStrengthBar');
    const passLabel  = document.getElementById('passStrengthLabel');

    if (passInput && passBar) {
        passInput.addEventListener('input', () => {
            const val = passInput.value;
            let score = 0;
            if (val.length >= 8)               score++;
            if (/[A-Z]/.test(val))             score++;
            if (/[0-9]/.test(val))             score++;
            if (/[^A-Za-z0-9]/.test(val))      score++;

            const levels  = ['', 'Weak', 'Fair', 'Good', 'Strong'];
            const colors  = ['', '#ff3d3d', '#FFD600', '#80c8ff', '#00C853'];
            const widths  = ['0%', '25%', '50%', '75%', '100%'];

            passBar.style.width      = widths[score]  || '0%';
            passBar.style.background = colors[score]  || 'transparent';
            if (passLabel) {
                passLabel.textContent = val.length > 0 ? (levels[score] || '') : '';
                passLabel.style.color = colors[score] || '';
            }
        });
    }

    const flashMsgs = document.querySelectorAll('.km-alert[data-auto-dismiss]');
    flashMsgs.forEach(el => {
        setTimeout(() => {
            el.style.transition = 'opacity 0.6s ease';
            el.style.opacity    = '0';
            setTimeout(() => el.remove(), 600);
        }, 4500);
    });

    const urlParams = new URLSearchParams(window.location.search);
    const activeCat = urlParams.get('cat') || '';
    document.querySelectorAll('.km-cat-pill').forEach(pill => {
        if (decodeURIComponent(pill.dataset.cat || '') === activeCat) {
            pill.classList.add('active');
        }
    });

    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', (e) => {
            if (!confirm(el.dataset.confirm)) {
                e.preventDefault();
            }
        });
    });

    const navbar = document.querySelector('.km-navbar');
    if (navbar) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 60) {
                navbar.style.padding = '6px 0';
            } else {
                navbar.style.padding = '12px 0';
            }
        }, { passive: true });
    }

});