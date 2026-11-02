

document.addEventListener('DOMContentLoaded', function () {
    const mobileBtn = document.getElementById('mobileMenuBtn');
    const navMenu = document.getElementById('navMenu');

    if (mobileBtn && navMenu) {
        mobileBtn.addEventListener('click', function () {
            navMenu.classList.toggle('nav-open');
            mobileBtn.classList.toggle('active');
        });
    }

    const toast = document.querySelector('.flash-toast');
    if (toast) {
        setTimeout(function () {
            toast.style.opacity = '0';
            toast.style.transition = 'opacity 0.5s ease';
            setTimeout(() => toast.remove(), 500);
        }, 5000);
    }

    const confirmButtons = document.querySelectorAll('[data-confirm]');
    confirmButtons.forEach(button => {
        button.addEventListener('click', function (e) {
            const message = this.getAttribute('data-confirm') || 'Are you sure you want to proceed?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });

    const tabButtons = document.querySelectorAll('.dashboard-tab-btn');
    const tabPanes = document.querySelectorAll('.dashboard-tab-pane');

    if (tabButtons.length > 0 && tabPanes.length > 0) {
        const hash = window.location.hash.substring(1);
        if (hash) {
            const activeBtn = document.querySelector(`.dashboard-tab-btn[data-tab="${hash}"]`);
            const activePane = document.getElementById(`tab-${hash}`);
            if (activeBtn && activePane) {
                tabButtons.forEach(b => b.classList.remove('active'));
                tabPanes.forEach(p => p.classList.remove('active'));
                activeBtn.classList.add('active');
                activePane.classList.add('active');
            }
        }

        tabButtons.forEach(btn => {
            btn.addEventListener('click', function () {
                const targetTab = this.getAttribute('data-tab');
                tabButtons.forEach(b => b.classList.remove('active'));
                tabPanes.forEach(p => p.classList.remove('active'));

                this.classList.add('active');
                const pane = document.getElementById(`tab-${targetTab}`);
                if (pane) {
                    pane.classList.add('active');
                }
                window.location.hash = targetTab;
            });
        });
    }
});

