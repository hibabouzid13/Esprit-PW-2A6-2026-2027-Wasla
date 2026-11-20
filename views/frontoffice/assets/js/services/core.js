/* ============================================================
   WASLA — Outils partagés des six services citoyens
   - accès aux données (repo) : démonstration + localStorage,
     prévu pour être remplacé par des appels à l'API PHP
   - fenêtres modales, onglets, validation des formulaires
   - états vides / chargement, formats (dates, montants)
   - carte de Tunisie interactive (marqueurs, zoom, sélection)
   Expose window.WASLA_SVC.
   ============================================================ */
(function () {
  "use strict";

  const DATA = window.WASLA_DATA || {};
  const SDATA = window.WASLA_SERVICES_DATA || {};

  /* ---------- Icônes supplémentaires (Lucide, licence ISC) ---------- */

  (function registerIcons() {
    if (!window.WASLA_ICONS) return;
    const wrap = (paths) =>
      `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${paths}</svg>`;
    const add = {
      star: wrap('<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>'),
      phone: wrap('<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>'),
      send: wrap('<path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/>'),
      "message-circle": wrap('<path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/>'),
      flag: wrap('<path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" x2="4" y1="22" y2="15"/>'),
      upload: wrap('<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" x2="12" y1="3" y2="15"/>'),
      archive: wrap('<rect width="20" height="5" x="2" y="3" rx="1"/><path d="M4 8v11a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8"/><path d="M10 12h4"/>'),
      navigation: wrap('<polygon points="3 11 22 2 13 21 11 13 3 11"/>'),
      thermometer: wrap('<path d="M14 4v10.54a4 4 0 1 1-4 0V4a2 2 0 0 1 4 0Z"/>'),
      accessibility: wrap('<circle cx="16" cy="4" r="1"/><path d="m18 19 1-7-6 1"/><path d="m5 8 3-3 5.5 3-2.36 3.5"/><path d="M4.24 14.5a5 5 0 0 0 6.88 6"/><path d="M13.76 17.5a5 5 0 0 0-6.88-6"/>'),
      bot: wrap('<path d="M12 8V4H8"/><rect width="16" height="12" x="4" y="8" rx="2"/><path d="M2 14h2"/><path d="M20 14h2"/><path d="M15 13v2"/><path d="M9 13v2"/>'),
      package: wrap('<path d="M16.5 9.4 7.55 4.24"/><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.29 7 12 12 20.71 7"/><line x1="12" x2="12" y1="22" y2="12"/>'),
      smartphone: wrap('<rect width="14" height="20" x="5" y="2" rx="2" ry="2"/><path d="M12 18h.01"/>'),
      truck: wrap('<path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/>'),
      bed: wrap('<path d="M2 4v16"/><path d="M2 8h18a2 2 0 0 1 2 2v10"/><path d="M2 17h20"/><path d="M6 8v9"/>'),
      plug: wrap('<path d="M12 22v-5"/><path d="M9 8V2"/><path d="M15 8V2"/><path d="M18 8v5a4 4 0 0 1-4 4h-4a4 4 0 0 1-4-4V8Z"/>'),
      heart: wrap('<path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>'),
      pill: wrap('<path d="m10.5 20.5 10-10a4.95 4.95 0 1 0-7-7l-10 10a4.95 4.95 0 1 0 7 7Z"/><path d="m8.5 8.5 7 7"/>'),
      wind: wrap('<path d="M17.7 7.7a2.5 2.5 0 1 1 1.8 4.3H2"/><path d="M9.6 4.6A2 2 0 1 1 11 8H2"/><path d="M12.6 19.4A2 2 0 1 0 14 16H2"/>'),
      briefcase: wrap('<path d="M16 20V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/><rect width="20" height="14" x="2" y="6" rx="2"/>'),
      pencil: wrap('<path d="M21.17 6.81a1 1 0 0 0-3.99-3.99L3.84 16.17a2 2 0 0 0-.5.83l-1.32 4.35a.5.5 0 0 0 .62.62l4.35-1.32a2 2 0 0 0 .83-.5z"/><path d="m15 5 4 4"/>'),
      trash: wrap('<path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/>'),
      "mail-open": wrap('<path d="M21.2 8.4c.5.38.8.97.8 1.6v10a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V10a2 2 0 0 1 .8-1.6l8-6a2 2 0 0 1 2.4 0l8 6Z"/><path d="m22 10-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 10"/>'),
      history: wrap('<path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l4 2"/>'),
      "user-plus": wrap('<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" x2="19" y1="8" y2="14"/><line x1="22" x2="16" y1="11" y2="11"/>')
    };
    Object.keys(add).forEach((key) => {
      if (!window.WASLA_ICONS[key]) window.WASLA_ICONS[key] = add[key];
    });
  })();

  const icon = (name) => (window.WASLA_icon ? window.WASLA_icon(name) : "");
  const hydrate = (root) => {
    if (window.WASLA_hydrateIcons) window.WASLA_hydrateIcons(root);
  };

  /* ---------- Formats ---------- */

  function esc(value) {
    return String(value == null ? "" : value)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#39;");
  }

  const dateFmt = new Intl.DateTimeFormat("fr-FR", { day: "numeric", month: "short", year: "numeric" });
  const dateTimeFmt = new Intl.DateTimeFormat("fr-FR", { day: "numeric", month: "short", hour: "2-digit", minute: "2-digit" });
  const monthFmt = new Intl.DateTimeFormat("fr-FR", { month: "short" });
  const monthLongFmt = new Intl.DateTimeFormat("fr-FR", { month: "long", year: "numeric" });
  const timeFmt = new Intl.DateTimeFormat("fr-FR", { hour: "2-digit", minute: "2-digit" });

  const fmt = {
    date: (iso) => dateFmt.format(new Date(iso)),
    dateTime: (iso) => dateTimeFmt.format(new Date(iso)),
    time: (iso) => timeFmt.format(new Date(iso)),
    month: (iso) => monthFmt.format(new Date(iso)).replace(".", ""),
    monthLong: (iso) => monthLongFmt.format(new Date(iso)),
    number: (n, digits = 0) => Number(n).toLocaleString("fr-FR", { minimumFractionDigits: digits, maximumFractionDigits: digits }),
    money: (n) => Number(n).toLocaleString("fr-FR", { minimumFractionDigits: 3, maximumFractionDigits: 3 }) + " TND",
    percent: (n) => (n > 0 ? "+" : n < 0 ? "−" : "") + Math.abs(n).toLocaleString("fr-FR", { maximumFractionDigits: 1 }) + " %",
    relative(iso) {
      const diff = Math.round((Date.now() - new Date(iso).getTime()) / 60000);
      if (diff < 0) {
        const ahead = -diff;
        if (ahead < 60) return `dans ${ahead} min`;
        if (ahead < 1440) return `dans ${Math.round(ahead / 60)} h`;
        return `dans ${Math.round(ahead / 1440)} j`;
      }
      if (diff < 1) return "à l'instant";
      if (diff < 60) return `il y a ${diff} min`;
      if (diff < 1440) return `il y a ${Math.round(diff / 60)} h`;
      if (diff < 2880) return "hier";
      return `il y a ${Math.round(diff / 1440)} j`;
    }
  };

  function initials(name) {
    return String(name || "?")
      .replace(/\(.*\)/, "")
      .trim()
      .split(/\s+/)
      .slice(0, 2)
      .map((part) => part.charAt(0).toUpperCase())
      .join("");
  }

  function stars(rating) {
    let html = '<span class="svc-stars" aria-hidden="true">';
    for (let i = 1; i <= 5; i += 1) {
      html += `<span class="${rating >= i - 0.25 ? "" : "is-empty"}">${icon("star")}</span>`;
    }
    return html + "</span>";
  }

  function uid(prefix) {
    return `${prefix}-${Date.now().toString(36)}${Math.random().toString(36).slice(2, 6)}`;
  }

  /* ---------- Stockage local ---------- */

  const NS = "wasla:svc:v" + (SDATA.version || 1) + ":";

  const store = {
    get(key, fallback) {
      try {
        const raw = window.localStorage.getItem(NS + key);
        return raw ? JSON.parse(raw) : fallback;
      } catch (error) {
        return fallback;
      }
    },
    set(key, value) {
      try {
        window.localStorage.setItem(NS + key, JSON.stringify(value));
        return true;
      } catch (error) {
        return false;
      }
    },
    remove(key) {
      try {
        window.localStorage.removeItem(NS + key);
      } catch (error) {
        /* stockage indisponible : rien à faire */
      }
    }
  };

  const clone = (value) => JSON.parse(JSON.stringify(value));
  const wait = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

  /**
   * Collection de données d'un service.
   * Aujourd'hui : données de démonstration + modifications enregistrées dans localStorage.
   * Demain : remplacer all() par fetch('index.php?action=api-…') et save() par un POST,
   * sans toucher au reste du code des pages.
   */
  function repo(key, seed) {
    return {
      async all() {
        await wait(320);
        const saved = store.get(key, null);
        return saved !== null ? saved : clone(typeof seed === "function" ? seed() : seed);
      },
      async save(items) {
        await wait(160);
        if (!store.set(key, items)) throw new Error("storage");
        return items;
      },
      reset() {
        store.remove(key);
      }
    };
  }

  /* ---------- Retours visuels ---------- */

  function toast(message) {
    if (window.WASLA_toast) window.WASLA_toast(message);
  }

  function emptyState({ icon: iconName = "search", title, text, action }) {
    return `<div class="empty-state">${icon(iconName)}<strong>${esc(title)}</strong>${text ? `<p>${esc(text)}</p>` : ""}${action || ""}</div>`;
  }

  function skeleton(lines = 6) {
    return `<div class="svc-skeleton" aria-hidden="true">${"<span></span>".repeat(lines)}</div><p class="sr-only" role="status">Chargement…</p>`;
  }

  function setBusy(button, busy, label) {
    if (!button) return;
    button.classList.toggle("is-loading", busy);
    button.disabled = busy;
    if (label) {
      if (busy) {
        button.dataset.label = button.innerHTML;
        button.textContent = label;
      } else if (button.dataset.label) {
        button.innerHTML = button.dataset.label;
        delete button.dataset.label;
      }
    }
  }

  /* ---------- Fenêtres modales ---------- */

  const openModals = [];

  function focusables(root) {
    return Array.from(
      root.querySelectorAll("a[href], button:not([disabled]), input:not([disabled]):not([type=hidden]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex='-1'])")
    ).filter((el) => el.offsetParent !== null);
  }

  function openModal(modal) {
    if (!modal || !modal.hidden) return;
    modal._lastFocus = document.activeElement;
    modal.hidden = false;
    openModals.push(modal);
    document.body.classList.add("no-scroll");
    hydrate(modal);
    requestAnimationFrame(() => {
      const target = modal.querySelector("[data-autofocus]") || focusables(modal.querySelector(".modal__panel") || modal)[1] || focusables(modal)[0];
      if (target) target.focus();
    });
  }

  function closeModal(modal) {
    if (!modal || modal.hidden) return;
    modal.hidden = true;
    const index = openModals.indexOf(modal);
    if (index > -1) openModals.splice(index, 1);
    if (!openModals.length) document.body.classList.remove("no-scroll");
    if (modal._lastFocus && typeof modal._lastFocus.focus === "function") modal._lastFocus.focus();
    modal.dispatchEvent(new CustomEvent("svc:close"));
  }

  document.addEventListener("click", (event) => {
    const scroller = event.target.closest("[data-scroll-to]");
    if (scroller) {
      const target = document.getElementById(scroller.getAttribute("data-scroll-to"));
      if (target) {
        target.scrollIntoView({ behavior: "smooth", block: "start" });
        const focusTarget = target.querySelector("input, select, textarea, button");
        if (focusTarget) setTimeout(() => focusTarget.focus({ preventScroll: true }), 400);
      }
      return;
    }
    const opener = event.target.closest("[data-svc-open]");
    if (opener) {
      event.preventDefault();
      openModal(document.getElementById(opener.getAttribute("data-svc-open")));
      return;
    }
    const closer = event.target.closest("[data-svc-close]");
    if (closer) {
      event.preventDefault();
      closeModal(closer.closest(".modal"));
      return;
    }
    if (event.target.classList && event.target.classList.contains("svc-modal")) closeModal(event.target);
  });

  document.addEventListener("keydown", (event) => {
    const modal = openModals[openModals.length - 1];
    if (!modal) return;
    if (event.key === "Escape") {
      closeModal(modal);
      return;
    }
    if (event.key !== "Tab") return;
    const items = focusables(modal);
    if (!items.length) return;
    const first = items[0];
    const last = items[items.length - 1];
    if (event.shiftKey && document.activeElement === first) {
      event.preventDefault();
      last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
      event.preventDefault();
      first.focus();
    }
  });

  /** Boîte de confirmation (remplace window.confirm, même charte que les autres modales). */
  function confirmDialog({ title, text, confirmLabel = "Confirmer", danger = false }) {
    let modal = document.getElementById("svc-confirm");
    if (!modal) {
      modal = document.createElement("div");
      modal.id = "svc-confirm";
      modal.className = "modal svc-modal";
      modal.setAttribute("role", "alertdialog");
      modal.setAttribute("aria-modal", "true");
      modal.setAttribute("aria-labelledby", "svc-confirm-title");
      modal.hidden = true;
      modal.innerHTML = `
        <div class="modal__panel modal__panel--narrow">
          <div class="modal__head"><div><h2 id="svc-confirm-title"></h2></div>
            <button class="modal__close" type="button" data-svc-close aria-label="Fermer">${icon("x")}</button></div>
          <div class="svc-modal__body"><p class="text-muted" data-confirm-text style="font-size:13px;line-height:1.6"></p></div>
          <div class="svc-modal__foot">
            <button class="btn btn--outline btn--sm" type="button" data-svc-close>Annuler</button>
            <button class="btn btn--secondary btn--sm" type="button" data-confirm-ok></button>
          </div>
        </div>`;
      document.body.appendChild(modal);
    }
    modal.querySelector("#svc-confirm-title").textContent = title;
    modal.querySelector("[data-confirm-text]").textContent = text || "";
    const ok = modal.querySelector("[data-confirm-ok]");
    ok.textContent = confirmLabel;
    ok.className = `btn btn--sm ${danger ? "btn--secondary" : "btn--primary"}`;
    return new Promise((resolve) => {
      let answered = false;
      const onOk = () => {
        answered = true;
        closeModal(modal);
      };
      const onClose = () => {
        ok.removeEventListener("click", onOk);
        modal.removeEventListener("svc:close", onClose);
        resolve(answered);
      };
      ok.addEventListener("click", onOk);
      modal.addEventListener("svc:close", onClose);
      openModal(modal);
      requestAnimationFrame(() => ok.focus());
    });
  }

  /* ---------- Onglets ---------- */

  /**
   * <div class="tabs" data-svc-tabs="nom"> <button class="tab" data-tab="a">…</button> … </div>
   * <section data-tab-panel="a" data-tabs-for="nom">…</section>
   */
  function initTabs(name, onChange) {
    const bar = document.querySelector(`[data-svc-tabs="${name}"]`);
    if (!bar) return { show() {} };
    const buttons = Array.from(bar.querySelectorAll("[data-tab]"));
    const panels = Array.from(document.querySelectorAll(`[data-tabs-for="${name}"]`));
    bar.setAttribute("role", "tablist");

    function show(key, focus) {
      buttons.forEach((button) => {
        const active = button.getAttribute("data-tab") === key;
        button.classList.toggle("is-active", active);
        button.setAttribute("aria-selected", active ? "true" : "false");
        button.tabIndex = active ? 0 : -1;
        if (active && focus) button.focus();
      });
      panels.forEach((panel) => {
        panel.hidden = panel.getAttribute("data-tab-panel") !== key;
      });
      if (onChange) onChange(key);
    }

    buttons.forEach((button, index) => {
      button.setAttribute("role", "tab");
      button.addEventListener("click", () => show(button.getAttribute("data-tab")));
      button.addEventListener("keydown", (event) => {
        if (event.key !== "ArrowRight" && event.key !== "ArrowLeft") return;
        const next = buttons[(index + (event.key === "ArrowRight" ? 1 : buttons.length - 1)) % buttons.length];
        show(next.getAttribute("data-tab"), true);
      });
    });
    panels.forEach((panel) => panel.setAttribute("role", "tabpanel"));

    const initial = buttons.find((button) => button.classList.contains("is-active")) || buttons[0];
    if (initial) show(initial.getAttribute("data-tab"));
    return { show };
  }

  /* ---------- Formulaires ---------- */

  function fieldError(form, name, message) {
    const holder = form.querySelector(`[data-error-for="${name}"]`);
    const controls = form.querySelectorAll(`[name="${name}"]`);
    controls.forEach((control) => {
      if (control.type === "radio" || control.type === "checkbox") {
        const group = control.closest(".svc-radio-row");
        if (group) group.classList.toggle("is-invalid", Boolean(message));
      } else {
        control.classList.toggle("is-invalid", Boolean(message));
      }
      if (message) control.setAttribute("aria-invalid", "true");
      else control.removeAttribute("aria-invalid");
      if (holder && holder.id) control.setAttribute("aria-describedby", holder.id);
    });
    if (holder) {
      holder.textContent = message || "";
      holder.classList.toggle("is-visible", Boolean(message));
    }
  }

  function clearErrors(form) {
    form.querySelectorAll("[data-error-for]").forEach((holder) => {
      holder.textContent = "";
      holder.classList.remove("is-visible");
    });
    form.querySelectorAll(".is-invalid").forEach((el) => el.classList.remove("is-invalid"));
    form.querySelectorAll("[aria-invalid]").forEach((el) => el.removeAttribute("aria-invalid"));
  }

  function values(form) {
    const out = {};
    Array.from(form.elements).forEach((el) => {
      if (!el.name || el.disabled) return;
      if (el.type === "radio") {
        if (el.checked) out[el.name] = el.value;
        else if (!(el.name in out)) out[el.name] = "";
      } else if (el.type === "checkbox") {
        if (form.querySelectorAll(`[name="${el.name}"]`).length > 1) {
          out[el.name] = out[el.name] || [];
          if (el.checked) out[el.name].push(el.value);
        } else {
          out[el.name] = el.checked;
        }
      } else if (el.type === "file") {
        out[el.name] = el.files && el.files[0] ? el.files[0] : null;
      } else {
        out[el.name] = el.value.trim();
      }
    });
    return out;
  }

  const PATTERNS = {
    phone: { re: /^(\+216\s?)?[2-9]\d(\s?\d{3}){2}$/, message: "Numéro tunisien à 8 chiffres attendu (ex. 22 123 456)." },
    email: { re: /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/, message: "Adresse e-mail invalide." },
    name: { re: /^[\p{L}][\p{L}' .-]{1,79}$/u, message: "Lettres uniquement (2 caractères minimum)." }
  };

  /**
   * Valide un formulaire selon des attributs data-* posés sur les champs :
   * data-required="message", data-min="20", data-max="500", data-pattern="phone|email|name",
   * data-not-past (date), data-max-mb="5", data-accept="pdf,jpg,png", data-no-phone (texte public),
   * data-num-min="1" / data-num-max="99999" (+ data-integer) pour les nombres saisis dans un champ texte.
   * Aucune validation HTML5 (required, type="number", min, max, pattern, maxlength) : consigne du projet.
   * Les champs cachés (hidden ou dans un bloc hidden) sont ignorés.
   */
  function validate(form, extra) {
    clearErrors(form);
    const errors = {};
    const seen = new Set();
    const isHidden = (el) => Boolean(el.closest("[hidden]"));

    Array.from(form.elements).forEach((el) => {
      if (!el.name || el.disabled || seen.has(el.name) || isHidden(el)) return;
      const value = el.type === "file" ? el.files && el.files[0] : el.type === "radio" ? (form.querySelector(`[name="${el.name}"]:checked`) || {}).value || "" : el.type === "checkbox" ? el.checked : el.value.trim();
      seen.add(el.name);
      const required = el.getAttribute("data-required");
      const empty = value === "" || value === false || value == null;
      if (required && empty) {
        errors[el.name] = required;
        return;
      }
      if (empty) return;
      const min = Number(el.getAttribute("data-min"));
      if (min && String(value).length < min) errors[el.name] = `${min} caractères minimum (${String(value).length} actuellement).`;
      const max = Number(el.getAttribute("data-max"));
      if (max && String(value).length > max) errors[el.name] = `${max} caractères maximum.`;
      const pattern = PATTERNS[el.getAttribute("data-pattern")];
      if (pattern && !pattern.re.test(String(value))) errors[el.name] = pattern.message;
      if (el.hasAttribute("data-not-past")) {
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        if (new Date(value) < today) errors[el.name] = "La date ne peut pas être dans le passé.";
      }
      if (el.hasAttribute("data-no-phone") && /(\+?216)?[\s.-]?\d{2}[\s.-]?\d{3}[\s.-]?\d{3}/.test(String(value))) {
        errors[el.name] = "Pour votre sécurité, n'indiquez pas de numéro de téléphone : utilisez le bouton « Contacter ».";
      }
      // Nombres : champ texte avec data-num-min / data-num-max (pas de type="number" ni min/max HTML5)
      if (el.hasAttribute("data-num-min") || el.hasAttribute("data-num-max")) {
        const raw = String(value).replace(",", ".");
        const n = Number(raw);
        const lo = el.getAttribute("data-num-min");
        const hi = el.getAttribute("data-num-max");
        if (raw === "" || Number.isNaN(n)) errors[el.name] = "Veuillez saisir un nombre.";
        else if (el.hasAttribute("data-integer") && !Number.isInteger(n)) errors[el.name] = "Veuillez saisir un nombre entier.";
        else if (lo !== null && n < Number(lo)) errors[el.name] = `Valeur minimale : ${lo}.`;
        else if (hi !== null && n > Number(hi)) errors[el.name] = `Valeur maximale : ${hi}.`;
      }
      if (el.type === "file" && value) {
        const maxMb = Number(el.getAttribute("data-max-mb") || 0);
        const accept = (el.getAttribute("data-accept") || "").split(",").filter(Boolean);
        const ext = value.name.split(".").pop().toLowerCase();
        if (accept.length && !accept.includes(ext)) errors[el.name] = `Format non pris en charge. Formats acceptés : ${accept.join(", ").toUpperCase()}.`;
        else if (maxMb && value.size > maxMb * 1048576) errors[el.name] = `Fichier trop volumineux (${maxMb} Mo maximum).`;
      }
    });

    if (extra) Object.assign(errors, extra(values(form)) || {});
    Object.keys(errors).forEach((name) => fieldError(form, name, errors[name]));
    const firstName = Object.keys(errors)[0];
    if (firstName) {
      const target = form.querySelector(`[name="${firstName}"]`);
      if (target) target.focus();
    }
    return Object.keys(errors).length === 0;
  }

  // Dès que l'utilisateur corrige un champ en erreur, son message disparaît.
  function clearOnEdit(event) {
    const el = event.target;
    if (!el.name || !el.form || el.getAttribute("aria-invalid") !== "true") return;
    fieldError(el.form, el.name, "");
  }
  document.addEventListener("input", clearOnEdit);
  document.addEventListener("change", clearOnEdit);

  /** Remplit un <select> avec les 24 gouvernorats. */
  function fillGovernorates(select, placeholder = "Choisir un gouvernorat…", selected = "") {
    if (!select) return;
    const names = (DATA.governorates || []).map((g) => g.name).sort((a, b) => a.localeCompare(b, "fr"));
    select.innerHTML = (placeholder ? `<option value="">${esc(placeholder)}</option>` : "") +
      names.map((name) => `<option value="${esc(name)}"${name === selected ? " selected" : ""}>${esc(name)}</option>`).join("");
  }

  /* ---------- Géographie ---------- */

  function governorate(name) {
    return (DATA.governorates || []).find((g) => g.name === name) || null;
  }

  // Décalage déterministe autour du chef-lieu pour placer un quartier (approximation volontaire).
  function hashOffset(text) {
    let h = 0;
    for (let i = 0; i < text.length; i += 1) h = (h * 31 + text.charCodeAt(i)) | 0;
    const a = ((h & 0xff) / 255 - 0.5) * 0.08;
    const b = (((h >> 8) & 0xff) / 255 - 0.5) * 0.08;
    return [a, b];
  }

  function coords(gov, area) {
    const g = governorate(gov);
    if (!g) return null;
    if (!area) return { lon: g.lon, lat: g.lat };
    const [dx, dy] = hashOffset(gov + area);
    return { lon: g.lon + dx, lat: g.lat + dy };
  }

  function distanceKm(a, b) {
    if (!a || !b) return null;
    const rad = Math.PI / 180;
    const dLat = (b.lat - a.lat) * rad;
    const dLon = (b.lon - a.lon) * rad;
    const h = Math.sin(dLat / 2) ** 2 + Math.cos(a.lat * rad) * Math.cos(b.lat * rad) * Math.sin(dLon / 2) ** 2;
    return 6371 * 2 * Math.asin(Math.sqrt(h));
  }

  function formatDistance(km) {
    if (km == null) return "—";
    return km < 1 ? `${Math.round(km * 1000)} m` : `${fmt.number(km, km < 10 ? 1 : 0)} km`;
  }

  /* ---------- Carte de Tunisie ---------- */

  const SVG_NS = "http://www.w3.org/2000/svg";

  function svgEl(tag, attrs) {
    const node = document.createElementNS(SVG_NS, tag);
    Object.keys(attrs || {}).forEach((key) => node.setAttribute(key, attrs[key]));
    return node;
  }

  /**
   * Carte légère réutilisant la géométrie et les styles de la carte des réseaux
   * (classes map-land, map-region, map-marker--normal|watch|outage|nodata + place|me).
   * markers : [{ id, lon, lat, kind, label, sub }]
   */
  function createMap(canvas, options = {}) {
    const GEO = window.WASLA_TUNISIA;
    if (!canvas || !GEO) return null;
    const [, , W, H] = GEO.viewBox.split(" ").map(Number);
    const p = GEO.projection;
    const project = (lon, lat) => [p.offsetX + (lon - p.minLon) * p.cosLat * p.scale, p.offsetY + (p.maxLat - lat) * p.scale];

    const svg = svgEl("svg", { class: "map-svg", viewBox: GEO.viewBox, preserveAspectRatio: "xMidYMid meet", role: "img", "aria-label": options.label || "Carte de Tunisie" });
    const group = svgEl("g", { class: "map-pan-zoom" });
    group.appendChild(svgEl("path", { class: "map-land", d: GEO.outlinePath }));
    GEO.regions.forEach((region) => {
      const path = svgEl("path", { class: "map-region", d: region.path });
      const title = svgEl("title");
      title.textContent = region.name;
      path.appendChild(title);
      group.appendChild(path);
    });
    const layer = svgEl("g", { class: "map-markers" });
    group.appendChild(layer);
    svg.appendChild(group);
    canvas.insertBefore(svg, canvas.firstChild);

    let tooltip = canvas.querySelector("[data-map-tooltip]");
    if (!tooltip) {
      tooltip = document.createElement("div");
      tooltip.className = "map-tooltip";
      tooltip.hidden = true;
      canvas.appendChild(tooltip);
    }

    const state = { scale: 1, x: 0, y: 0, selected: null, markers: [] };

    function apply() {
      const minX = W - W * state.scale;
      const minY = H - H * state.scale;
      state.x = Math.min(0, Math.max(minX, state.x));
      state.y = Math.min(0, Math.max(minY, state.y));
      group.setAttribute("transform", `translate(${state.x} ${state.y}) scale(${state.scale})`);
      layer.querySelectorAll(".map-marker").forEach((marker) => {
        marker.setAttribute("transform", `translate(${marker.dataset.x} ${marker.dataset.y}) scale(${1 / Math.sqrt(state.scale)})`);
      });
    }

    function zoomTo(scale, cx = W / 2, cy = H / 2) {
      state.scale = Math.max(1, Math.min(5, scale));
      state.x = W / 2 - cx * state.scale;
      state.y = H / 2 - cy * state.scale;
      apply();
    }

    function showTip(marker) {
      const data = state.markers.find((m) => m.id === marker.dataset.id);
      if (!data) return;
      const box = marker.getBoundingClientRect();
      const host = canvas.getBoundingClientRect();
      tooltip.innerHTML = `<strong>${esc(data.label)}</strong>${data.sub ? `<span>${esc(data.sub)}</span>` : ""}`;
      tooltip.style.left = `${box.left - host.left + box.width / 2}px`;
      tooltip.style.top = `${box.top - host.top}px`;
      tooltip.hidden = false;
    }

    function hideTip() {
      tooltip.hidden = true;
    }

    function setMarkers(list) {
      state.markers = list.filter((m) => m && Number.isFinite(m.lon) && Number.isFinite(m.lat));
      layer.innerHTML = "";
      state.markers.forEach((m) => {
        const [x, y] = project(m.lon, m.lat);
        const g = svgEl("g", { class: `map-marker map-marker--${m.kind || "normal"}`, tabindex: "0", role: "button", "aria-label": m.label + (m.sub ? `, ${m.sub}` : "") });
        g.dataset.id = m.id;
        g.dataset.x = x;
        g.dataset.y = y;
        g.appendChild(svgEl("circle", { class: "map-marker__halo", r: "9" }));
        g.appendChild(svgEl("circle", { class: "map-marker__ring", r: "6" }));
        g.appendChild(svgEl("circle", { class: "map-marker__dot", r: "3.6" }));
        if (m.id === state.selected) g.classList.add("is-selected");
        g.addEventListener("mouseenter", () => showTip(g));
        g.addEventListener("mouseleave", hideTip);
        g.addEventListener("focus", () => showTip(g));
        g.addEventListener("blur", hideTip);
        g.addEventListener("click", () => {
          select(m.id);
          if (options.onSelect) options.onSelect(m.id);
        });
        g.addEventListener("keydown", (event) => {
          if (event.key === "Enter" || event.key === " ") {
            event.preventDefault();
            g.dispatchEvent(new Event("click"));
          }
        });
        layer.appendChild(g);
      });
      apply();
      const empty = canvas.querySelector(".svc-map__empty");
      if (empty) empty.hidden = state.markers.length > 0;
    }

    function select(id, focus) {
      state.selected = id;
      layer.querySelectorAll(".map-marker").forEach((marker) => marker.classList.toggle("is-selected", marker.dataset.id === id));
      const data = state.markers.find((m) => m.id === id);
      if (data && focus) {
        const [x, y] = project(data.lon, data.lat);
        zoomTo(Math.max(state.scale, 2.4), x, y);
      }
    }

    function fit(list) {
      const pts = (list || state.markers).map((m) => project(m.lon, m.lat));
      if (!pts.length) return zoomTo(1);
      const xs = pts.map((pt) => pt[0]);
      const ys = pts.map((pt) => pt[1]);
      const spanX = Math.max(...xs) - Math.min(...xs) + 60;
      const spanY = Math.max(...ys) - Math.min(...ys) + 60;
      const scale = Math.min(W / spanX, H / spanY, 3.2);
      zoomTo(scale, (Math.max(...xs) + Math.min(...xs)) / 2, (Math.max(...ys) + Math.min(...ys)) / 2);
    }

    // Contrôles de zoom (mêmes boutons que la carte des réseaux)
    const controls = document.createElement("div");
    controls.className = "map-card__zoom";
    controls.innerHTML = `
      <button class="map-control" type="button" data-z="in" aria-label="Zoomer">+</button>
      <button class="map-control" type="button" data-z="out" aria-label="Dézoomer">−</button>
      <button class="map-control" type="button" data-z="reset" aria-label="Recentrer la carte">${icon("locate")}</button>`;
    canvas.appendChild(controls);
    controls.addEventListener("click", (event) => {
      const button = event.target.closest("[data-z]");
      if (!button) return;
      const center = [(W / 2 - state.x) / state.scale, (H / 2 - state.y) / state.scale];
      if (button.dataset.z === "in") zoomTo(state.scale * 1.5, center[0], center[1]);
      else if (button.dataset.z === "out") zoomTo(state.scale / 1.5, center[0], center[1]);
      else zoomTo(1);
    });

    // Déplacement à la souris / au doigt
    let drag = null;
    svg.addEventListener("pointerdown", (event) => {
      if (event.target.closest(".map-marker") || state.scale === 1) return;
      const rect = svg.getBoundingClientRect();
      drag = { x: event.clientX, y: event.clientY, sx: state.x, sy: state.y, ratio: Math.max(W / rect.width, H / rect.height) };
      svg.classList.add("is-dragging");
      svg.setPointerCapture(event.pointerId);
    });
    svg.addEventListener("pointermove", (event) => {
      if (!drag) return;
      state.x = drag.sx + (event.clientX - drag.x) * drag.ratio;
      state.y = drag.sy + (event.clientY - drag.y) * drag.ratio;
      apply();
    });
    const endDrag = () => {
      drag = null;
      svg.classList.remove("is-dragging");
    };
    svg.addEventListener("pointerup", endDrag);
    svg.addEventListener("pointercancel", endDrag);

    apply();
    return { setMarkers, select, fit, zoomTo, reset: () => zoomTo(1) };
  }

  // Les fenêtres modales sont déplacées sous <body> : <main> est animé à l'arrivée sur la page
  // (motion.css), ce qui crée un contexte d'empilement qui les ferait passer sous l'en-tête.
  document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll("main .svc-modal").forEach((modal) => document.body.appendChild(modal));
  });

  /* ---------- Utilisateur ---------- */

  const body = document.body;
  const user = {
    loggedIn: body.hasAttribute("data-user-name"),
    firstName: body.getAttribute("data-user-name") || "",
    authorized: body.hasAttribute("data-user-admin")
  };

  window.WASLA_SVC = {
    DATA,
    SDATA,
    icon,
    hydrate,
    esc,
    fmt,
    initials,
    stars,
    uid,
    store,
    repo,
    toast,
    emptyState,
    skeleton,
    setBusy,
    openModal,
    closeModal,
    confirm: confirmDialog,
    initTabs,
    validate,
    values,
    clearErrors,
    fieldError,
    fillGovernorates,
    governorate,
    coords,
    distanceKm,
    formatDistance,
    createMap,
    user
  };
})();
