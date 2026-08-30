/**
 * FAMS Homepage — interaction script
 * Handles: sticky navbar state, scroll-reveal animations,
 * animated statistic counters, back-to-top button,
 * and basic contact form feedback.
 */
document.addEventListener('DOMContentLoaded', function () {

    /* ---------- Sticky navbar style on scroll ---------- */
    const navbar = document.getElementById('famsNavbar');
    const backToTop = document.getElementById('backToTop');

    function handleScrollState() {
        const scrolled = window.scrollY > 40;
        if (navbar) navbar.classList.toggle('is-scrolled', scrolled);
        if (backToTop) backToTop.classList.toggle('is-visible', window.scrollY > 500);
    }
    handleScrollState();
    window.addEventListener('scroll', handleScrollState, { passive: true });

    if (backToTop) {
        backToTop.addEventListener('click', function () {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    /* ---------- Collapse mobile navbar after link click ---------- */
    document.querySelectorAll('.fams-navbar .nav-link').forEach(function (link) {
        link.addEventListener('click', function () {
            const collapseEl = document.getElementById('famsNavContent');
            if (collapseEl && collapseEl.classList.contains('show')) {
                const bsCollapse = bootstrap.Collapse.getOrCreateInstance(collapseEl);
                bsCollapse.hide();
            }
        });
    });

    /* ---------- Scroll-reveal ---------- */
    const revealEls = document.querySelectorAll('.reveal');
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.15 });
        revealEls.forEach(function (el) { observer.observe(el); });
    } else {
        revealEls.forEach(function (el) { el.classList.add('is-visible'); });
    }

    /* ---------- Animated statistic counters ---------- */
    const counters = document.querySelectorAll('[data-counter]');
    const animateCounter = function (el) {
        const target = parseInt(el.getAttribute('data-counter'), 10) || 0;
        const duration = 1600;
        const start = performance.now();
        const suffix = el.getAttribute('data-suffix') || '';

        function step(now) {
            const progress = Math.min((now - start) / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3);
            const value = Math.floor(eased * target);
            el.textContent = value.toLocaleString() + suffix;
            if (progress < 1) {
                requestAnimationFrame(step);
            } else {
                el.textContent = target.toLocaleString() + suffix;
            }
        }
        requestAnimationFrame(step);
    };

    if (counters.length) {
        if ('IntersectionObserver' in window) {
            const counterObserver = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        animateCounter(entry.target);
                        counterObserver.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.4 });
            counters.forEach(function (el) { counterObserver.observe(el); });
        } else {
            counters.forEach(animateCounter);
        }
    }

    /* ---------- Contact form (front-end feedback only) ---------- */
    const contactForm = document.getElementById('famsContactForm');
    if (contactForm) {
        contactForm.addEventListener('submit', function (e) {
            e.preventDefault();
            if (!contactForm.checkValidity()) {
                e.stopPropagation();
                contactForm.classList.add('was-validated');
                return;
            }
            const feedback = document.getElementById('contactFormFeedback');
            if (feedback) {
                feedback.classList.remove('d-none');
            }
            contactForm.reset();
            contactForm.classList.remove('was-validated');
        });
    }

});