/* ============================================================
   WASLA — Shared layout behaviour
   ============================================================ */
(function () {
  "use strict";

  const DATA = window.WASLA_DATA;
  const icon = window.WASLA_icon;
  const PREFS = window.WASLA_PREFS;
  const t = window.WASLA_t || ((value) => value);

  // Pages du menu « Services » : le bouton Services reste actif sur chacune d'elles.
  const SERVICE_PAGES = ["services", "alertes", "factures", "fournisseurs", "refuges", "relais", "sante"];

  const navItems = [
    { label: "Accueil", href: "index.php?action=accueil", page: "accueil" },
    { label: "Carte", href: "index.php?action=carte", page: "carte" },
    { label: "Analyses", href: "index.php?action=analyses", page: "analyses" },
    {
      label: "Services",
      href: "index.php?action=services",
      page: "services",
      children: [
        { label: "Alertes personnalisées", href: "index.php?action=alertes", icon: "bell" },
        { label: "Factures & IA", href: "index.php?action=factures", icon: "file-text" },
        { label: "Fournisseurs", href: "index.php?action=fournisseurs", icon: "wrench" },
        { label: "Zones refuge", href: "index.php?action=refuges", icon: "home" },
        { label: "Relais de voisinage", href: "index.php?action=relais", icon: "snowflake" },
        { label: "Priorité santé", href: "index.php?action=sante", icon: "heart-pulse" }
      ]
    },
    { label: "À propos", href: "index.php?action=a-propos", page: "a-propos" }
  ];

  // Page courante : fournie par PHP dans <body data-page="…">, sinon lue dans l'URL (?page=…)
  // data-page est toujours rendu par PHP ; une valeur vide (pages d'erreur) = aucun lien actif.
  const currentPage = "page" in document.body.dataset
    ? document.body.dataset.page
    : (new URLSearchParams(location.search).get("action") || "accueil");

  /* Toast -------------------------------------------------- */

  let toastTimer = null;

  function toast(message) {
    let el = document.querySelector(".toast");
    if (!el) {
      el = document.createElement("div");
      el.className = "toast";
      el.setAttribute("role", "status");
      el.setAttribute("aria-live", "polite");
      document.body.appendChild(el);
    }
    el.textContent = message;
    el.classList.add("is-visible");
    window.clearTimeout(toastTimer);
    toastTimer = window.setTimeout(() => el.classList.remove("is-visible"), 3200);
  }

  window.WASLA_toast = toast;

  /* Active navigation -------------------------------------- */

  function setActiveNav() {
    document.querySelectorAll("[data-nav]").forEach((link) => {
      const page = link.getAttribute("data-nav");
      const isActive = page === currentPage || (page === "services" && SERVICE_PAGES.includes(currentPage));
      link.classList.toggle("is-active", isActive);
      if (isActive) link.setAttribute("aria-current", "page");
      else link.removeAttribute("aria-current");
    });
  }

  /* Mobile menu -------------------------------------------- */

  /* Services cachés pour ce rôle (SERVICE_ROLES, config/helpers.php) */
  function lienVisible(href) {
    const masques = (document.body.dataset.servicesMasques || "").split(" ").filter(Boolean);
    return !masques.some((service) => href === "index.php?action=" + service);
  }

  function buildMobileMenu() {
    const root = document.querySelector("[data-mobile-menu-root]");
    if (!root) return;

    const links = navItems
      .map((item) => {
        if (item.children) {
          return `
            <button type="button" data-mobile-sub-toggle aria-expanded="false" aria-controls="mobile-services">
              ${item.label}
              <span data-mobile-chevron>${icon("chevron-down")}</span>
            </button>
            <div class="mobile-menu__sub" id="mobile-services" hidden>
              ${item.children
                .filter((child) => lienVisible(child.href))
                .map(
                  (child) =>
                    `<a href="${child.href}" data-close-menu><span data-icon="${child.icon}"></span>${child.label}</a>`
                )
                .join("")}
            </div>`;
        }
        return `<a href="${item.href}" data-nav="${item.page}" data-close-menu>${item.label}</a>`;
      })
      .join("");

    root.innerHTML = `
      <div class="menu-backdrop" data-close-menu hidden></div>
      <div class="mobile-menu" id="mobile-menu" role="dialog" aria-modal="true" aria-label="Menu principal" hidden>
        <div class="mobile-menu__head">
          <a class="brand" href="index.php?action=accueil" aria-label="WASLA — Accueil">
            <span class="brand__mark">${logoMark()}</span>
            <span class="brand__text"><span class="brand__name">WASLA</span></span>
          </a>
          <button class="icon-btn" type="button" data-close-menu aria-label="Fermer le menu">${icon("x")}</button>
        </div>
        <nav class="mobile-menu__nav" aria-label="Navigation mobile">${links}</nav>
        <div class="mobile-menu__prefs">
          <p class="mobile-menu__prefs-title">Préférences</p>
          <div class="mobile-pref">
            <span id="pref-lang-label">Langue</span>
            <div class="segmented" role="group" aria-labelledby="pref-lang-label">
              <button type="button" data-set-lang="fr" aria-pressed="false" lang="fr" aria-label="Français" data-no-i18n>FR</button>
              <button type="button" data-set-lang="en" aria-pressed="false" lang="en" aria-label="English" data-no-i18n>EN</button>
              <button type="button" data-set-lang="ar" aria-pressed="false" lang="ar" aria-label="العربية" data-no-i18n>AR</button>
            </div>
          </div>
          <div class="mobile-pref">
            <span id="pref-theme-label">Mode sombre</span>
            <button class="switch" type="button" role="switch" aria-checked="false" aria-labelledby="pref-theme-label" data-theme-toggle></button>
          </div>
        </div>
        <div class="mobile-menu__actions">
          ${accountActions()}
          <button class="btn btn--outline" type="button" data-open-search>${icon("search")} Rechercher</button>
        </div>
      </div>`;
  }

  /* Boutons du compte : « Connexion » ou, si connecté, « Mon espace » (+ « Administration »). */
  function accountActions() {
    const body = document.body;
    const name = body.dataset.userName;
    if (!name) {
      return `<a class="btn btn--secondary" href="index.php?action=connexion" data-close-menu>${icon("user")} Connexion</a>`;
    }
    const safe = name.replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" })[c]);
    const admin = body.hasAttribute("data-user-admin")
      ? `<a class="btn btn--primary" href="index.php?action=dashboard" data-close-menu>${icon("settings")} Administration</a>`
      : "";
    return `${admin}<a class="btn btn--secondary" href="index.php?action=mon-compte" data-close-menu>${icon("user")} <span data-no-i18n>${safe}</span></a>`;
  }

  function logoMark() {
    return `<svg viewBox="0 0 64 64" fill="none" aria-hidden="true" class="brand__logo">
      <path d="M32 3.5C32 3.5 10.5 25.5 10.5 40.5C10.5 51.8 20.1 61.5 32 61.5C43.9 61.5 53.5 51.8 53.5 40.5C53.5 25.5 32 3.5 32 3.5Z" fill="#fff" stroke="#00A3D7" stroke-width="4.5" stroke-linejoin="round"/>
      <path d="M32 12.5C32 12.5 18.5 29.5 18.5 39.5C18.5 47 24.5 53 32 53C39.5 53 45.5 47 45.5 39.5C45.5 29.5 32 12.5 32 12.5Z" fill="#003B5C"/>
      <path d="M36.2 19.5C36.2 19.5 27 31 27 39.2C27 44.2 30.8 48 35.8 48C41 48 45.2 43.8 45.2 38.6C45.2 30.5 40.4 24.5 36.2 19.5Z" fill="#73D13D"/>
      <path d="M32.2 21.5L24.6 39.2H29.4L27.2 49.2L36.8 31.8H31.9L34.2 21.5H32.2Z" fill="#EAF4FB"/>
    </svg>`;
  }

  function setupMobileMenu() {
    const root = document.querySelector("[data-mobile-menu-root]");
    if (!root) return;

    const menu = root.querySelector(".mobile-menu");
    const backdrop = root.querySelector(".menu-backdrop");
    const toggle = document.querySelector("[data-open-menu]");
    let lastFocus = null;
    let closeTimer = null;

    function openMenu() {
      window.clearTimeout(closeTimer);
      lastFocus = document.activeElement;
      backdrop.hidden = false;
      menu.hidden = false;
      document.body.classList.add("no-scroll");
      if (toggle) toggle.setAttribute("aria-expanded", "true");
      requestAnimationFrame(() => menu.classList.add("is-open"));
      const first = menu.querySelector("a, button");
      if (first) first.focus();
    }

    function closeMenu() {
      menu.classList.remove("is-open");
      backdrop.hidden = true;
      document.body.classList.remove("no-scroll");
      if (toggle) toggle.setAttribute("aria-expanded", "false");
      closeTimer = window.setTimeout(() => {
        menu.hidden = true;
      }, 240);
      if (lastFocus && typeof lastFocus.focus === "function") lastFocus.focus();
    }

    if (toggle) toggle.addEventListener("click", openMenu);
    root.querySelectorAll("[data-close-menu]").forEach((el) => {
      el.addEventListener("click", (event) => {
        if (el.classList.contains("menu-backdrop") && event.target !== el) return;
        closeMenu();
      });
    });

    const subToggle = root.querySelector("[data-mobile-sub-toggle]");
    const sub = root.querySelector("#mobile-services");
    if (subToggle && sub) {
      subToggle.addEventListener("click", () => {
        const open = subToggle.getAttribute("aria-expanded") === "true";
        subToggle.setAttribute("aria-expanded", String(!open));
        sub.hidden = open;
      });
    }

    document.addEventListener("keydown", (event) => {
      if (event.key === "Escape" && !menu.hidden) closeMenu();
      if (event.key !== "Tab" || menu.hidden) return;
      const focusables = menu.querySelectorAll("a[href], button:not([disabled])");
      if (!focusables.length) return;
      const first = focusables[0];
      const last = focusables[focusables.length - 1];
      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
      }
    });

    window.WASLA_closeMenu = closeMenu;
  }

  /* Dropdowns ---------------------------------------------- */

  function setupDropdowns() {
    const toggles = document.querySelectorAll("[data-dropdown-toggle]");

    toggles.forEach((toggle) => {
      const panelId = toggle.getAttribute("aria-controls");
      const panel = panelId ? document.getElementById(panelId) : null;
      if (!panel) return;

      toggle.addEventListener("click", (event) => {
        event.stopPropagation();
        const open = toggle.getAttribute("aria-expanded") === "true";
        closeAllDropdowns();
        if (!open) {
          toggle.setAttribute("aria-expanded", "true");
          panel.hidden = false;
        }
      });
    });

    function closeAllDropdowns() {
      toggles.forEach((toggle) => {
        const panelId = toggle.getAttribute("aria-controls");
        const panel = panelId ? document.getElementById(panelId) : null;
        toggle.setAttribute("aria-expanded", "false");
        if (panel) panel.hidden = true;
      });
    }

    document.addEventListener("click", (event) => {
      if (!event.target.closest(".nav-item") && !event.target.closest(".notification-wrap")) {
        closeAllDropdowns();
        closeNotifications();
      }
    });

    document.addEventListener("keydown", (event) => {
      if (event.key === "Escape") {
        closeAllDropdowns();
        closeNotifications();
      }
    });

    window.WASLA_closeDropdowns = closeAllDropdowns;
  }

  /* Notifications ------------------------------------------ */

  let notificationPanel = null;

  function buildNotifications() {
    const wrap = document.querySelector(".notification-wrap");
    if (!wrap || !DATA) return;

    wrap.insertAdjacentHTML(
      "beforeend",
      `<div class="notification-panel" data-notification-panel role="dialog" aria-label="Notifications" hidden>
        <div class="notification-panel__head">
          <strong>Notifications <span data-notification-count>${DATA.notifications.length}</span></strong>
          <button type="button" data-mark-read>Tout marquer comme lu</button>
        </div>
        <div class="notification-list">
          ${DATA.notifications
            .map(
              (item) => `
            <div class="notification-item notification-item--${item.type}">
              <span class="notification-item__dot" aria-hidden="true"></span>
              <div>
                <p><strong>${item.title}</strong></p>
                <p>${item.text}</p>
                <time>${item.time}</time>
              </div>
            </div>`
            )
            .join("")}
        </div>
        <div class="notification-panel__foot"><a href="index.php?action=carte">Voir toutes les alertes</a></div>
      </div>`
    );

    notificationPanel = wrap.querySelector("[data-notification-panel]");
    const button = wrap.querySelector("[data-open-notifications]");
    if (button) {
      button.addEventListener("click", (event) => {
        event.stopPropagation();
        const open = !notificationPanel.hidden;
        closeNotifications();
        if (!open) {
          notificationPanel.hidden = false;
          button.setAttribute("aria-expanded", "true");
        }
      });
    }

    const markRead = notificationPanel.querySelector("[data-mark-read]");
    if (markRead) {
      markRead.addEventListener("click", () => {
        const badge = wrap.querySelector(".icon-btn__badge");
        if (badge) badge.remove();
        const count = notificationPanel.querySelector("[data-notification-count]");
        if (count) count.textContent = "0";
        if (button) button.setAttribute("aria-label", "Notifications, aucune non lue");
        notificationPanel.querySelectorAll(".notification-item").forEach((item) => {
          item.style.opacity = "0.62";
        });
        toast("Toutes les notifications ont été marquées comme lues.");
      });
    }
  }

  function closeNotifications() {
    if (!notificationPanel) return;
    notificationPanel.hidden = true;
    const button = document.querySelector("[data-open-notifications]");
    if (button) button.setAttribute("aria-expanded", "false");
  }

  window.WASLA_closeNotifications = closeNotifications;

  /* Search ------------------------------------------------- */

  let searchModal = null;
  let searchInput = null;
  let searchResults = null;
  let searchLastFocus = null;

  function buildSearch() {
    const root = document.querySelector("[data-search-root]");
    if (!root) return;

    root.innerHTML = `
      <div class="modal" data-search-modal role="dialog" aria-modal="true" aria-labelledby="search-title" hidden>
        <div class="modal__panel">
          <div class="modal__search">
            ${icon("search")}
            <label class="sr-only" id="search-title" for="wasla-search">Rechercher dans WASLA</label>
            <input id="wasla-search" type="search" data-search-input placeholder="Rechercher une page, un service, une zone…" autocomplete="off">
            <button class="modal__close" type="button" data-close-search aria-label="Fermer la recherche">${icon("x")}</button>
          </div>
          <div class="modal__results" data-search-results></div>
          <div class="modal__hint">
            <span><kbd>↑</kbd> <kbd>↓</kbd> pour naviguer</span>
            <span><kbd>Entrée</kbd> pour ouvrir</span>
            <span><kbd>Échap</kbd> pour fermer</span>
          </div>
        </div>
      </div>`;

    searchModal = root.querySelector("[data-search-modal]");
    searchInput = root.querySelector("[data-search-input]");
    searchResults = root.querySelector("[data-search-results]");

    document.querySelectorAll("[data-open-search]").forEach((button) => {
      button.addEventListener("click", openSearch);
    });

    searchModal.querySelector("[data-close-search]").addEventListener("click", closeSearch);
    searchModal.addEventListener("click", (event) => {
      if (event.target === searchModal) closeSearch();
    });

    searchInput.addEventListener("input", () => renderSearch(searchInput.value));
    searchInput.addEventListener("keydown", (event) => {
      const links = Array.from(searchResults.querySelectorAll("a"));
      if (!links.length) return;
      const currentIndex = links.indexOf(document.activeElement);
      if (event.key === "ArrowDown") {
        event.preventDefault();
        links[Math.min(currentIndex + 1, links.length - 1)].focus();
      } else if (event.key === "ArrowUp") {
        event.preventDefault();
        links[Math.max(currentIndex - 1, 0)].focus();
      } else if (event.key === "Enter" && currentIndex === -1) {
        event.preventDefault();
        links[0].click();
      }
    });

    document.addEventListener("keydown", (event) => {
      if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === "k") {
        event.preventDefault();
        openSearch();
      }
      if (event.key === "Escape" && !searchModal.hidden) closeSearch();
      if (event.key !== "Tab" || searchModal.hidden) return;
      const focusables = Array.from(
        searchModal.querySelectorAll("a[href], button:not([disabled]), input:not([disabled])")
      ).filter((element) => element.offsetParent !== null);
      if (!focusables.length) return;
      const first = focusables[0];
      const last = focusables[focusables.length - 1];
      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
      }
    });

    renderSearch("");
  }

  function openSearch() {
    if (!searchModal) return;
    searchLastFocus = document.activeElement;
    searchModal.hidden = false;
    document.body.classList.add("no-scroll");
    renderSearch("");
    requestAnimationFrame(() => searchInput.focus());
  }

  function closeSearch() {
    if (!searchModal) return;
    searchModal.hidden = true;
    document.body.classList.remove("no-scroll");
    if (searchLastFocus && typeof searchLastFocus.focus === "function") searchLastFocus.focus();
  }

  function renderSearch(query) {
    if (!searchResults || !DATA) return;
    const q = query.trim().toLowerCase();
    const results = DATA.searchIndex.filter((item) => lienVisible(item.url)).filter((item) => {
      if (!q) return true;
      const haystack = [item.title, item.description, t(item.title), t(item.description)].join(" ");
      return haystack.toLowerCase().includes(q);
    });

    if (!results.length) {
      searchResults.innerHTML = `<div class="modal__empty">Aucun résultat pour « ${escapeHtml(query)} ».<br>Essayez « carte », « alertes » ou « factures ».</div>`;
      return;
    }

    searchResults.innerHTML = results
      .map(
        (item) => `
        <a class="search-result" href="${item.url}">
          <span class="search-result__icon">${icon(item.icon)}</span>
          <span><strong>${item.title}</strong><small>${item.description}</small></span>
        </a>`
      )
      .join("");
  }

  function escapeHtml(value) {
    return String(value).replace(/[&<>"']/g, (char) => {
      return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[char];
    });
  }

  /* Footer year / icons / links ---------------------------- */

  function setupMisc() {
    document.querySelectorAll("[data-year]").forEach((el) => {
      el.textContent = new Date().getFullYear();
    });

    document.querySelectorAll("[data-demo-action]").forEach((el) => {
      el.addEventListener("click", (event) => {
        event.preventDefault();
        toast(el.getAttribute("data-demo-action") || "Action de démonstration.");
      });
    });
  }

  /* Spinner & Back to top ---------------------------------- */

  function hideSpinner() {
    const spinner = document.getElementById("spinner");
    if (spinner) spinner.classList.remove("is-visible");
  }

  function setupBackToTop() {
    const button = document.querySelector("[data-back-to-top]");
    if (!button) return;

    const toggle = () => {
      button.hidden = window.scrollY < 300;
    };
    toggle();
    window.addEventListener("scroll", toggle, { passive: true });

    button.addEventListener("click", (event) => {
      event.preventDefault();
      const reduce = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
      window.scrollTo({ top: 0, behavior: reduce ? "auto" : "smooth" });
    });
  }

  /* Theme & language controls ------------------------------ */

  function syncPreferenceControls() {
    if (!PREFS) return;
    const lang = window.WASLA_I18N ? window.WASLA_I18N.getLang() : PREFS.getLang();
    const dark = PREFS.getTheme() === "dark";

    document.querySelectorAll("[data-lang-code]").forEach((el) => {
      el.textContent = lang.toUpperCase();
    });
    document.querySelectorAll("[data-set-lang]").forEach((button) => {
      const active = button.getAttribute("data-set-lang") === lang;
      button.setAttribute(button.getAttribute("role") === "menuitemradio" ? "aria-checked" : "aria-pressed", String(active));
    });
    document.querySelectorAll("[data-theme-toggle]").forEach((button) => {
      if (button.getAttribute("role") === "switch") {
        button.setAttribute("aria-checked", String(dark));
      } else {
        button.setAttribute("aria-pressed", String(dark));
        button.setAttribute("aria-label", dark ? "Activer le mode clair" : "Activer le mode sombre");
      }
    });
  }

  function setupPreferenceControls() {
    document.addEventListener("click", (event) => {
      const langButton = event.target.closest("[data-set-lang]");
      if (langButton && window.WASLA_I18N) {
        if (window.WASLA_closeDropdowns) window.WASLA_closeDropdowns();
        const toggle = document.querySelector(".lang-switch__btn");
        if (langButton.closest(".lang-menu") && toggle) toggle.focus();
        window.WASLA_I18N.setLang(langButton.getAttribute("data-set-lang"));
        return;
      }
      if (event.target.closest("[data-theme-toggle]") && PREFS) {
        PREFS.toggleTheme();
      }
    });

    const menu = document.getElementById("lang-menu");
    if (menu) {
      menu.addEventListener("keydown", (event) => {
        if (event.key !== "ArrowDown" && event.key !== "ArrowUp") return;
        event.preventDefault();
        const items = Array.from(menu.querySelectorAll("[data-set-lang]"));
        const index = items.indexOf(document.activeElement);
        const next = event.key === "ArrowDown" ? (index + 1) % items.length : (index - 1 + items.length) % items.length;
        items[next].focus();
      });
      const toggle = document.querySelector(".lang-switch__btn");
      if (toggle) {
        toggle.addEventListener("click", () => {
          if (toggle.getAttribute("aria-expanded") !== "true") return;
          const current = menu.querySelector('[aria-checked="true"]') || menu.querySelector("[data-set-lang]");
          requestAnimationFrame(() => current.focus());
        });
      }
    }

    document.addEventListener("wasla:themechange", syncPreferenceControls);
    document.addEventListener("wasla:langchange", syncPreferenceControls);
    syncPreferenceControls();
  }

  /* Init --------------------------------------------------- */

  document.addEventListener("DOMContentLoaded", () => {
    if (window.WASLA_hydrateIcons) window.WASLA_hydrateIcons(document);
    buildMobileMenu();
    buildNotifications();
    buildSearch();
    setActiveNav();
    setupMobileMenu();
    setupDropdowns();
    setupPreferenceControls();
    setupMisc();
    setupBackToTop();
    if (window.WASLA_hydrateIcons) window.WASLA_hydrateIcons(document);
    hideSpinner();
  });
})();
