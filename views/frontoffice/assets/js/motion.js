/* ============================================================
   WASLA — Motion behaviour (reveal, count-up, page transitions)
   ============================================================ */
(function () {
  "use strict";

  const root = document.documentElement;
  const PREFS = window.WASLA_PREFS;
  const reducedMotion = () => (PREFS ? PREFS.reducedMotion() : false);

  // Must match the hidden-state selector list in motion.css.
  const REVEAL_SELECTOR = [
    ".feature-item", ".dashboard-heading", ".kpi-card", ".map-card", ".alert-card",
    ".engagements__intro", ".engagement-stat", ".engagements__visual",
    ".page-hero__row", ".summary-card", ".filter-bar", ".map-page-side > .card",
    ".analysis-toolbar", ".stat-card", ".chart-card", ".page-section .container > .card",
    ".service-card", ".step", ".cta-band", ".about-copy", ".about-visual", ".value-card",
    ".coverage-band", "#mentions", ".login-brand", ".login-form-wrap", ".footer-main > *"
  ].join(", ");

  const COUNT_SELECTOR = ".kpi-card__value, .summary-card__value, .engagement-stat__value, .coverage-band__value, .stat-card__value";

  window.WASLA_MOTION = true;

  /* Count-up for integer figures ----------------------------- */

  function countUp(container) {
    if (reducedMotion()) return;
    container.querySelectorAll(COUNT_SELECTOR).forEach((el) => {
      const node = el.firstChild;
      if (!node || node.nodeType !== 3) return;
      const match = /^(\D*)(\d+)(\D*)$/.exec(node.data.trim());
      if (!match) return;
      const target = Number(match[2]);
      if (target < 10) return;
      const start = performance.now();
      const duration = 900;
      function frame(now) {
        const progress = Math.min(1, (now - start) / duration);
        const eased = 1 - Math.pow(1 - progress, 3);
        node.data = match[1] + Math.round(target * eased) + match[3];
        if (progress < 1) requestAnimationFrame(frame);
      }
      requestAnimationFrame(frame);
    });
  }

  /* Scroll reveal -------------------------------------------- */

  function setupReveal() {
    if (!root.classList.contains("reveal-on")) return;

    const observer = new IntersectionObserver(
      (entries) => {
        let batchIndex = 0;
        entries.forEach((entry) => {
          if (!entry.isIntersecting) return;
          const el = entry.target;
          el.style.setProperty("--reveal-delay", `${Math.min(batchIndex, 6) * 70}ms`);
          batchIndex++;
          el.classList.add("is-revealed");
          observer.unobserve(el);
          countUp(el);
        });
      },
      { rootMargin: "0px 0px -6% 0px", threshold: 0.06 }
    );

    function bind(el) {
      if (el.classList.contains("is-revealed") || el.hasAttribute("data-reveal-bound")) return;
      el.setAttribute("data-reveal-bound", "");
      observer.observe(el);
    }

    function scan(scope) {
      if (scope.matches && scope.matches(REVEAL_SELECTOR)) bind(scope);
      if (scope.querySelectorAll) scope.querySelectorAll(REVEAL_SELECTOR).forEach(bind);
    }

    function revealAll() {
      document.querySelectorAll(REVEAL_SELECTOR).forEach((el) => el.classList.add("is-revealed"));
    }

    scan(document);
    new MutationObserver((records) => {
      records.forEach((record) => {
        record.addedNodes.forEach((node) => {
          if (node.nodeType === 1) scan(node);
        });
      });
    }).observe(document.body, { childList: true, subtree: true });

    window.addEventListener("beforeprint", revealAll);
  }

  /* Page transition fallback --------------------------------- */
  // Browsers with cross-document View Transitions animate via CSS (@view-transition).

  function setupPageTransitions() {
    const nativeTransitions = "onpagereveal" in window && CSS.supports && CSS.supports("view-transition-name", "none");
    if (nativeTransitions) return;

    document.addEventListener("click", (event) => {
      if (reducedMotion() || event.defaultPrevented || event.button !== 0) return;
      if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
      const link = event.target.closest("a[href]");
      if (!link || link.hasAttribute("download")) return;
      if (link.target && link.target !== "_self") return;

      const url = new URL(link.href, location.href);
      if (url.protocol !== location.protocol || url.origin !== location.origin) return;
      if (url.pathname === location.pathname && url.search === location.search) return;

      event.preventDefault();
      root.classList.add("is-leaving");
      window.setTimeout(() => {
        location.href = url.href;
      }, 170);
    });

    // Restored from the back/forward cache: undo the exit state.
    window.addEventListener("pageshow", () => root.classList.remove("is-leaving"));
  }

  /* Header elevation ----------------------------------------- */

  function setupHeader() {
    const header = document.querySelector(".site-header");
    if (!header) return;
    let ticking = false;
    function update() {
      header.classList.toggle("is-scrolled", window.scrollY > 8);
      ticking = false;
    }
    window.addEventListener(
      "scroll",
      () => {
        if (!ticking) {
          ticking = true;
          requestAnimationFrame(update);
        }
      },
      { passive: true }
    );
    update();
  }

  document.addEventListener("DOMContentLoaded", () => {
    setupReveal();
    setupPageTransitions();
    setupHeader();
  });
})();
