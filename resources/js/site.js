(() => {
  'use strict';

  const root = document.documentElement;

  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  const finePointer = window.matchMedia('(hover: hover) and (pointer: fine)');
  const desktopMotion = window.matchMedia('(min-width: 64.01rem)');
  const header = document.querySelector('[data-site-header]');

  if (header) {
    let scheduled = false;
    const updateHeader = () => {
      header.classList.toggle('is-scrolled', window.scrollY > 12);
      scheduled = false;
    };
    window.addEventListener('scroll', () => {
      if (!scheduled) {
        scheduled = true;
        window.requestAnimationFrame(updateHeader);
      }
    }, { passive: true });
  }

  const mobileMenu = document.querySelector('[data-mobile-menu]');
  if (mobileMenu) {
    mobileMenu.addEventListener('click', (event) => {
      if (event.target.closest('a')) mobileMenu.removeAttribute('open');
    });
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && mobileMenu.open) {
        mobileMenu.removeAttribute('open');
        mobileMenu.querySelector('summary')?.focus();
      }
    });
    const closeOnDesktop = (event) => {
      if (event.matches) mobileMenu.removeAttribute('open');
    };
    desktopMotion.addEventListener?.('change', closeOnDesktop);
  }

  const reveals = [...document.querySelectorAll('[data-reveal]')];
  if (!reducedMotion.matches && finePointer.matches && desktopMotion.matches && 'IntersectionObserver' in window && reveals.length) {
    root.classList.add('motion-ready');
    const revealObserver = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        entry.target.classList.add('is-visible');
        revealObserver.unobserve(entry.target);
      });
    }, { rootMargin: '0px 0px -6% 0px', threshold: 0.06 });
    reveals.forEach((element) => revealObserver.observe(element));
  }

  const panelSteps = [...document.querySelectorAll('[data-panel-step]')];
  if (panelSteps.length && !reducedMotion.matches && finePointer.matches && desktopMotion.matches && 'IntersectionObserver' in window) {
    const panelObserver = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        panelSteps.forEach((panel) => panel.classList.toggle('is-current', panel === entry.target));
      });
    }, { rootMargin: '-35% 0px -45% 0px', threshold: 0 });
    panelSteps.forEach((panel) => panelObserver.observe(panel));
  }

  const heroSystem = document.querySelector('[data-hero-system]');
  if (heroSystem && finePointer.matches && desktopMotion.matches && !reducedMotion.matches) {
    let frame = 0;
    heroSystem.addEventListener('pointermove', (event) => {
      const bounds = heroSystem.getBoundingClientRect();
      const x = ((event.clientX - bounds.left) / bounds.width - 0.5) * 7;
      const y = ((event.clientY - bounds.top) / bounds.height - 0.5) * 7;
      window.cancelAnimationFrame(frame);
      frame = window.requestAnimationFrame(() => {
        heroSystem.style.setProperty('--hero-x', `${x.toFixed(2)}px`);
        heroSystem.style.setProperty('--hero-y', `${y.toFixed(2)}px`);
      });
    }, { passive: true });
    heroSystem.addEventListener('pointerleave', () => {
      heroSystem.style.removeProperty('--hero-x');
      heroSystem.style.removeProperty('--hero-y');
    });
  }

  document.querySelector('[data-error-summary]')?.focus();

  const domainInputs = [...document.querySelectorAll('input[name="services_researched[]"]')];
  domainInputs.forEach((input) => {
    input.addEventListener('change', () => {
      const selected = domainInputs.filter((candidate) => candidate.checked);
      const limitReached = selected.length >= 3;
      domainInputs.forEach((candidate) => {
        candidate.disabled = limitReached && !candidate.checked;
      });
    });
  });

  document.querySelectorAll('[data-form]').forEach((form) => {
    form.addEventListener('submit', () => {
      const submit = form.querySelector('button[type="submit"]');
      if (!submit) return;
      form.setAttribute('aria-busy', 'true');
      submit.disabled = true;
      const original = submit.textContent;
      submit.textContent = 'Envoi en cours…';
      window.setTimeout(() => {
        form.removeAttribute('aria-busy');
        submit.disabled = false;
        submit.textContent = original;
      }, 10000);
    });
  });
})();
