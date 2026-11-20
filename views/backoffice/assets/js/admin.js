/* ============================================================
   WASLA — Back Office : comportements de l'interface
   - icônes, menu latéral (groupes + mobile), sparklines
   - recherche et filtres dans les tableaux
   - contrôle de saisie des formulaires (JavaScript, sans HTML5)
   ============================================================ */
(function () {
  "use strict";

  /* Toast ---------------------------------------------------- */

  let toastTimer = null;
  function toast(message) {
    let el = document.querySelector(".admin-toast");
    if (!el) {
      el = document.createElement("div");
      el.className = "admin-toast";
      el.setAttribute("role", "status");
      el.setAttribute("aria-live", "polite");
      document.body.appendChild(el);
    }
    el.textContent = message;
    el.classList.add("is-visible");
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => el.classList.remove("is-visible"), 3000);
  }

  /* Menu latéral --------------------------------------------- */

  function setupSidebar() {
    document.querySelectorAll("[data-nav-group]").forEach((group) => {
      const toggle = group.querySelector(".admin-nav__toggle");
      if (!toggle) return;
      toggle.addEventListener("click", () => {
        const open = group.classList.toggle("is-open");
        toggle.setAttribute("aria-expanded", String(open));
      });
    });

    // Pages pas encore créées : le lien est désactivé
    document.querySelectorAll('[aria-disabled="true"]').forEach((link) => {
      link.addEventListener("click", (event) => event.preventDefault());
    });

    const shell = document.querySelector(".admin");
    const openBtn = document.querySelector("[data-sidebar-open]");
    if (!shell || !openBtn) return;

    function close() {
      shell.classList.remove("is-sidebar-open");
      openBtn.setAttribute("aria-expanded", "false");
      const backdrop = document.querySelector(".admin-backdrop");
      if (backdrop) backdrop.remove();
    }

    openBtn.addEventListener("click", () => {
      shell.classList.add("is-sidebar-open");
      openBtn.setAttribute("aria-expanded", "true");
      const backdrop = document.createElement("div");
      backdrop.className = "admin-backdrop";
      backdrop.addEventListener("click", close);
      shell.appendChild(backdrop);
    });

    document.addEventListener("keydown", (event) => {
      if (event.key === "Escape") close();
    });
  }

  /* Sparklines (data-admin-spark="12,15,…" data-color="#00A875") */

  function renderSparks() {
    document.querySelectorAll("[data-admin-spark]").forEach((host, index) => {
      const values = host.getAttribute("data-admin-spark").split(",").map(Number);
      const color = host.getAttribute("data-color") || "#005A9C";
      const w = 120;
      const h = 48;
      const pad = 3;
      const min = Math.min(...values);
      const max = Math.max(...values);
      const span = max - min || 1;
      const pts = values.map((v, i) => [
        pad + (i / (values.length - 1)) * (w - pad * 2),
        h - pad - ((v - min) / span) * (h - pad * 2)
      ]);
      const line = pts.map((p, i) => (i ? "L" : "M") + p[0].toFixed(1) + " " + p[1].toFixed(1)).join(" ");
      const id = "admin-spark-" + index;
      host.innerHTML =
        `<svg viewBox="0 0 ${w} ${h}" preserveAspectRatio="none" aria-hidden="true" style="width:100%;height:100%">` +
        `<defs><linearGradient id="${id}" x1="0" y1="0" x2="0" y2="1">` +
        `<stop offset="0%" stop-color="${color}" stop-opacity="0.25"/><stop offset="100%" stop-color="${color}" stop-opacity="0"/>` +
        `</linearGradient></defs>` +
        `<path d="${line} L${w - pad} ${h} L${pad} ${h} Z" fill="url(#${id})"/>` +
        `<path d="${line}" fill="none" stroke="${color}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>` +
        `</svg>`;
    });
  }

  /* Tableaux : recherche + filtres --------------------------- */

  function setupTables() {
    document.querySelectorAll("[data-admin-table]").forEach((table) => {
      const card = table.closest(".admin-card");
      const search = card.querySelector("[data-table-search]");
      const filters = card.querySelectorAll("[data-table-filter]");
      const count = card.querySelector("[data-table-count]");
      const empty = card.querySelector("[data-table-empty]");
      const rows = Array.from(table.tBodies[0].rows);

      function apply() {
        const q = search ? search.value.trim().toLowerCase() : "";
        let visible = 0;
        rows.forEach((row) => {
          let ok = !q || row.textContent.toLowerCase().includes(q);
          filters.forEach((select) => {
            const key = select.getAttribute("data-table-filter");
            if (select.value && row.dataset[key] !== select.value) ok = false;
          });
          row.hidden = !ok;
          if (ok) visible++;
        });
        if (count) count.textContent = String(visible);
        if (empty) empty.hidden = visible !== 0;
      }

      if (search) search.addEventListener("input", apply);
      filters.forEach((select) => select.addEventListener("change", apply));

      table.addEventListener("click", (event) => {
        const btn = event.target.closest("[data-action]");
        if (!btn) return;
        const row = btn.closest("tr");
        const action = btn.getAttribute("data-action");
        if (action === "delete") {
          if (window.confirm("Supprimer cet élément ? Cette action est définitive.")) {
            row.remove();
            rows.splice(rows.indexOf(row), 1);
            apply();
            toast("Élément supprimé (démonstration).");
          }
        } else if (action === "close") {
          toast("Interruption clôturée (démonstration).");
        }
      });

      apply();
    });
  }

  /* Contrôle de saisie : voir frontoffice/assets/js/validation.js (commun FO / BO) */

  /* Démarrage ------------------------------------------------ */

  /* Icônes supplémentaires du Back Office (Lucide, licence ISC) */
  function registerIcons() {
    if (!window.WASLA_ICONS) return;
    const wrap = (paths) =>
      `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${paths}</svg>`;
    Object.assign(window.WASLA_ICONS, {
      pencil: wrap('<path d="M21.17 6.81a1 1 0 0 0-3.99-3.99L3.84 16.17a2 2 0 0 0-.5.83l-1.32 4.35a.5.5 0 0 0 .62.62l4.35-1.32a2 2 0 0 0 .83-.5z"/><path d="m15 5 4 4"/>'),
      trash: wrap('<path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/>'),
      save: wrap('<path d="M15.2 3a2 2 0 0 1 1.4.6l3.8 3.8a2 2 0 0 1 .6 1.4V19a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z"/><path d="M17 21v-7a1 1 0 0 0-1-1H8a1 1 0 0 0-1 1v7"/><path d="M7 3v4a1 1 0 0 0 1 1h7"/>')
    });
  }

  document.addEventListener("DOMContentLoaded", () => {
    registerIcons();
    if (window.WASLA_hydrateIcons) window.WASLA_hydrateIcons();
    setupSidebar();
    renderSparks();
    setupTables();
    const search = document.querySelector("[data-admin-search]");
    if (search) search.addEventListener("submit", (event) => event.preventDefault());
    const preloader = document.getElementById("preloader");
    if (preloader) preloader.classList.remove("is-visible");
  });

  window.WASLA_adminToast = toast;
})();
