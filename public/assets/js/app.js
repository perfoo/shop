document.addEventListener('DOMContentLoaded', () => {
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    setupDrawer(prefersReducedMotion);
    setupSortControls();
    setupCardSliders(prefersReducedMotion);
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

function setupCardSliders(prefersReducedMotion) {
    const sliders = document.querySelectorAll('[data-slider]');
    if (!sliders.length) {
        return;
    }

    sliders.forEach((slider) => {
        const rawData = slider.dataset.sliderImages;
        if (!rawData) {
            return;
        }

        let images;
        try {
            images = JSON.parse(rawData);
        } catch (error) {
            console.error('Neispravan format slika za slider.', error);
            return;
        }

        if (!Array.isArray(images) || images.length === 0) {
            return;
        }

        let index = 0;
        const mainImage = slider.querySelector('[data-slider-main]');
        const prevButton = slider.querySelector('[data-slider-prev]');
        const nextButton = slider.querySelector('[data-slider-next]');
        const dotsContainer = slider.querySelector('[data-slider-dots]');

        if (!mainImage) {
            return;
        }

        const update = () => {
            const current = images[index];
            if (!current) {
                return;
            }
            const nextSrc = current.thumb || current.image;
            if (nextSrc) {
                mainImage.src = nextSrc;
            }
            mainImage.alt = `Fotografija ${index + 1}`;
            if (dotsContainer) {
                dotsContainer.querySelectorAll('.slider-dot').forEach((dot) => {
                    dot.classList.toggle('is-active', Number(dot.dataset.index) === index);
                });
            }
        };

        const go = (direction) => {
            index = (index + direction + images.length) % images.length;
            update();
        };

        if (dotsContainer && dotsContainer.children.length === 0 && images.length > 1) {
            images.forEach((_, dotIndex) => {
                const dot = document.createElement('button');
                dot.type = 'button';
                dot.className = 'slider-dot' + (dotIndex === 0 ? ' is-active' : '');
                dot.dataset.index = String(dotIndex);
                dot.setAttribute('aria-label', `Prikaži fotografiju ${dotIndex + 1}`);
                dot.addEventListener('click', () => {
                    index = dotIndex;
                    update();
                });
                dotsContainer.appendChild(dot);
            });
        }

        prevButton?.addEventListener('click', () => go(-1));
        nextButton?.addEventListener('click', () => go(1));

        slider.addEventListener('keydown', (event) => {
            if (event.key === 'ArrowLeft') {
                event.preventDefault();
                go(-1);
            }
            if (event.key === 'ArrowRight') {
                event.preventDefault();
                go(1);
            }
        });

        if (prefersReducedMotion) {
            slider.classList.add('slider-no-motion');
        }

        update();
    });
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
