document.addEventListener('DOMContentLoaded', () => {
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    setupDrawer(prefersReducedMotion);
    setupSortControls();
    setupLightbox(prefersReducedMotion);
    setupCardAnimations(prefersReducedMotion);
    setupPhotoSorter();
});

function setupDrawer(prefersReducedMotion) {
    const drawer = document.querySelector('[data-drawer]');
    const toggle = document.querySelector('[data-menu-toggle]');
    const close = document.querySelector('[data-menu-close]');
    if (!drawer || !toggle || !close) {
        return;
    }

    let lastFocused = null;

    const openDrawer = () => {
        lastFocused = document.activeElement;
        drawer.classList.add('drawer-open');
        drawer.setAttribute('aria-hidden', 'false');
        document.body.classList.add('drawer-visible');
        close.focus();
    };

    const closeDrawer = () => {
        drawer.classList.remove('drawer-open');
        drawer.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('drawer-visible');
        if (lastFocused) {
            lastFocused.focus();
        }
    };

    toggle.addEventListener('click', () => {
        if (drawer.classList.contains('drawer-open')) {
            closeDrawer();
        } else {
            openDrawer();
        }
    });

    close.addEventListener('click', closeDrawer);

    drawer.addEventListener('click', (event) => {
        if (event.target === drawer) {
            closeDrawer();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && drawer.classList.contains('drawer-open')) {
            closeDrawer();
        }
    });

    if (prefersReducedMotion) {
        drawer.classList.add('drawer-no-motion');
    }
}

function setupSortControls() {
    const selects = document.querySelectorAll('[data-sort-select]');
    if (!selects.length) {
        return;
    }

    selects.forEach((select) => {
        select.addEventListener('change', () => {
            const params = new URLSearchParams(window.location.search);
            params.set('sort', select.value);
            const currentCategory = window.catalogState?.currentCategory;
            if (currentCategory) {
                params.set('category', currentCategory);
            }
            window.location.search = params.toString();
        });
    });
}

function setupLightbox(prefersReducedMotion) {
    const modal = document.querySelector('[data-lightbox-modal]');
    if (!modal) {
        return;
    }
    const image = modal.querySelector('[data-lightbox-image]');
    const closeBtn = modal.querySelector('[data-lightbox-close]');
    const prevBtn = modal.querySelector('[data-lightbox-prev]');
    const nextBtn = modal.querySelector('[data-lightbox-next]');
    const triggers = document.querySelectorAll('[data-lightbox]');
    let currentIndex = 0;
    let lastFocus = null;
    const photos = window.lightboxPhotos || [];

    const open = (index) => {
        if (!photos.length) {
            return;
        }
        currentIndex = index;
        image.src = photos[currentIndex];
        modal.hidden = false;
        modal.classList.add('lightbox-open');
        lastFocus = document.activeElement;
        closeBtn.focus();
    };

    const close = () => {
        modal.classList.remove('lightbox-open');
        modal.hidden = true;
        image.src = '';
        if (lastFocus) {
            lastFocus.focus();
        }
    };

    const showNext = (direction) => {
        if (!photos.length) {
            return;
        }
        currentIndex = (currentIndex + direction + photos.length) % photos.length;
        image.src = photos[currentIndex];
    };

    triggers.forEach((trigger) => {
        trigger.addEventListener('click', (event) => {
            event.preventDefault();
            const index = Number(trigger.dataset.index || 0);
            open(index);
        });
    });

    closeBtn?.addEventListener('click', close);
    prevBtn?.addEventListener('click', () => showNext(-1));
    nextBtn?.addEventListener('click', () => showNext(1));

    document.addEventListener('keydown', (event) => {
        if (modal.hidden) {
            return;
        }
        if (event.key === 'Escape') {
            close();
        }
        if (event.key === 'ArrowLeft') {
            showNext(-1);
        }
        if (event.key === 'ArrowRight') {
            showNext(1);
        }
    });

    if (prefersReducedMotion) {
        modal.classList.add('lightbox-no-motion');
    }
}

function setupCardAnimations(prefersReducedMotion) {
    if (prefersReducedMotion) {
        return;
    }
    const cards = document.querySelectorAll('[data-animate]');
    if (!cards.length) {
        return;
    }

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-in');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.2 });

    cards.forEach((card) => observer.observe(card));
}

function setupPhotoSorter() {
    const forms = document.querySelectorAll('[data-photo-form] .photo-sorter');
    forms.forEach((container) => {
        let dragged = null;

        container.addEventListener('dragstart', (event) => {
            dragged = event.target.closest('.photo-slot');
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', '');
            dragged?.classList.add('dragging');
        });

        container.addEventListener('dragover', (event) => {
            event.preventDefault();
            const target = event.target.closest('.photo-slot');
            if (!dragged || !target || dragged === target) {
                return;
            }
            const bounding = target.getBoundingClientRect();
            const offset = event.clientY - bounding.top;
            const shouldInsertAfter = offset > bounding.height / 2;
            if (shouldInsertAfter) {
                target.after(dragged);
            } else {
                target.before(dragged);
            }
        });

        container.addEventListener('dragend', () => {
            dragged?.classList.remove('dragging');
            dragged = null;
        });
    });
}
