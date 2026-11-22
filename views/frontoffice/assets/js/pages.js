/* ============================================================
   WASLA — Page-specific behaviour
   ============================================================ */
(function () {
  "use strict";

  const DATA = window.WASLA_DATA;
  const icon = window.WASLA_icon;
  const toast = window.WASLA_toast || function () {};
  const t = window.WASLA_t || ((value) => value);

  function matchesName(name, query) {
    return !query || name.toLowerCase().includes(query) || t(name).toLowerCase().includes(query);
  }

  function setLoading(button, loading, label) {
    if (!button) return;
    button.disabled = loading;
    button.classList.toggle("is-loading", loading);
    button.setAttribute("aria-busy", String(loading));
    if (label) button.textContent = label;
  }

  function hydrate(root) {
    if (window.WASLA_hydrateIcons) window.WASLA_hydrateIcons(root || document);
  }

  function statusBadge(status) {
    const meta = DATA.statuses[status] || DATA.statuses.nodata;
    return `<span class="status-badge status-badge--${meta.className}">${meta.short}</span>`;
  }

  /* Carte --------------------------------------------------- */

  function setupCarte() {
    const list = document.querySelector("[data-zone-list]");
    if (!list || !DATA) return;

    const governorates = DATA.governorates;
    const counts = { total: governorates.length, normal: 0, watch: 0, outage: 0, nodata: 0 };
    governorates.forEach((zone) => {
      if (counts[zone.status] !== undefined) counts[zone.status]++;
    });

    document.querySelectorAll("[data-summary]").forEach((el) => {
      const key = el.getAttribute("data-summary");
      if (counts[key] !== undefined) el.textContent = counts[key];
    });
    document.querySelectorAll("[data-count]").forEach((el) => {
      const key = el.getAttribute("data-count");
      if (counts[key] !== undefined) el.textContent = counts[key];
    });

    let activeStatus = "all";
    let query = "";
    let selected = null;

    function filtered() {
      return governorates.filter((zone) => {
        const matchesStatus = activeStatus === "all" || zone.status === activeStatus;
        return matchesStatus && matchesName(zone.name, query);
      });
    }

    function renderList() {
      const rows = filtered();
      const empty = `<div class="empty-state">${icon("search")}<strong>Aucune zone trouvée</strong><p>Modifiez la recherche ou réinitialisez les filtres.</p></div>`;
      list.innerHTML = rows.length ? rows
        .map(
          (zone) => `
        <button class="zone-item${selected === zone.name ? " is-selected" : ""}" type="button" data-zone-item="${zone.name}">
          <span>
            <span class="zone-item__name">${zone.name}</span>
            <span class="zone-item__meta">${zone.zones} zones · ${zone.updated}</span>
          </span>
          <span class="zone-item__right">
            ${statusBadge(zone.status)}
            ${icon("chevron-right")}
          </span>
        </button>`
        )
        .join("") : empty;

      list.querySelectorAll("[data-zone-item]").forEach((button) => {
        button.addEventListener("click", () => {
          const name = button.getAttribute("data-zone-item");
          selected = name;
          highlight(name);
          if (window.WASLA_MAP) window.WASLA_MAP.selectZone(name);
        });
      });

      const visible = document.querySelector("[data-visible-count]");
      if (visible) visible.textContent = rows.length;
      const label = document.querySelector("[data-zone-count-label]");
      if (label) label.textContent = `${rows.length} zone${rows.length > 1 ? "s" : ""}`;

      if (window.WASLA_MAP) window.WASLA_MAP.applyFilters({ status: activeStatus, query });
    }

    function highlight(name) {
      list.querySelectorAll("[data-zone-item]").forEach((button) => {
        button.classList.toggle("is-selected", button.getAttribute("data-zone-item") === name);
      });
    }

    document.querySelectorAll("[data-status-filter]").forEach((chip) => {
      chip.addEventListener("click", () => {
        document.querySelectorAll("[data-status-filter]").forEach((item) => item.classList.remove("is-active"));
        chip.classList.add("is-active");
        activeStatus = chip.getAttribute("data-status-filter");
        renderList();
      });
    });

    const search = document.querySelector("[data-zone-search]");
    if (search) {
      search.addEventListener("input", () => {
        query = search.value.trim().toLowerCase();
        renderList();
      });
    }

    const reset = document.querySelector("[data-reset-filters]");
    if (reset) {
      reset.addEventListener("click", () => {
        activeStatus = "all";
        query = "";
        if (search) search.value = "";
        document.querySelectorAll("[data-status-filter]").forEach((item) => {
          item.classList.toggle("is-active", item.getAttribute("data-status-filter") === "all");
        });
        renderList();
      });
    }

    document.addEventListener("wasla:zone-select", (event) => {
      const name = event.detail && event.detail.zone ? event.detail.zone.name : null;
      if (!name) return;
      selected = name;
      highlight(name);
      const button = list.querySelector(`[data-zone-item="${name}"]`);
      if (button) button.scrollIntoView({ block: "nearest" });
    });

    renderList();
    setupReportModal();
  }

  function setupReportModal() {
    const modal = document.querySelector("[data-report-modal]");
    if (!modal || !DATA) return;

    const form = modal.querySelector("[data-report-form]");
    const zoneSelect = modal.querySelector("#report-zone");
    const address = modal.querySelector("#report-address");

    if (zoneSelect) {
      zoneSelect.innerHTML =
        '<option value="">Sélectionner un gouvernorat…</option>' +
        DATA.governorates.map((zone) => `<option value="${zone.name}">${zone.name}</option>`).join("");
    }

    let lastFocus = null;

    function open() {
      lastFocus = document.activeElement;
      modal.hidden = false;
      document.body.classList.add("no-scroll");
      requestAnimationFrame(() => {
        if (zoneSelect) zoneSelect.focus();
      });
    }

    function close() {
      modal.hidden = true;
      document.body.classList.remove("no-scroll");
      if (lastFocus && typeof lastFocus.focus === "function") lastFocus.focus();
    }

    document.querySelectorAll("[data-open-report]").forEach((button) => {
      button.addEventListener("click", open);
    });
    modal.querySelectorAll("[data-close-report]").forEach((button) => {
      button.addEventListener("click", close);
    });
    modal.addEventListener("click", (event) => {
      if (event.target === modal) close();
    });
    document.addEventListener("keydown", (event) => {
      if (event.key === "Escape" && !modal.hidden) close();
      if (event.key !== "Tab" || modal.hidden) return;
      const focusables = Array.from(
        modal.querySelectorAll("a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled])")
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

    if (form) {
      form.addEventListener("submit", (event) => {
        event.preventDefault();
        const zoneOk = zoneSelect && zoneSelect.value;
        const addressOk = address && address.value.trim().length >= 3;
        if (zoneSelect) zoneSelect.classList.toggle("is-invalid", !zoneOk);
        if (address) address.classList.toggle("is-invalid", !addressOk);
        if (!zoneOk || !addressOk) {
          toast("Veuillez renseigner le gouvernorat et une adresse valide.");
          return;
        }
        const submit = form.querySelector('[type="submit"]');
        setLoading(submit, true, "Envoi…");
        window.setTimeout(() => {
          setLoading(submit, false, "Envoyer le signalement");
          close();
          form.reset();
          toast("Merci ! Votre signalement a bien été enregistré (démonstration).");
        }, 700);
      });
    }
  }

  /* Analyses ------------------------------------------------ */

  function setupAnalyses() {
    if (!DATA) return;

    const tbody = document.querySelector("[data-outage-table]");
    const zoneSelect = document.querySelector("[data-analysis-zone]");

    function renderTable(rows) {
      if (!tbody) return;
      if (!rows.length) {
        tbody.innerHTML = `<tr class="data-table__empty"><td colspan="5">Aucun incident récent pour ce gouvernorat.</td></tr>`;
        return;
      }
      tbody.innerHTML = rows
        .map(
          (row) => `
        <tr>
          <td><strong>${row.zone}</strong></td>
          <td>${row.type}</td>
          <td>${statusBadge(row.status)}</td>
          <td>${row.duration}</td>
          <td>${row.updated}</td>
        </tr>`
        )
        .join("");
    }

    if (zoneSelect) {
      zoneSelect.innerHTML =
        '<option value="">Tous les gouvernorats</option>' +
        DATA.governorates.map((zone) => `<option value="${zone.name}">${zone.name}</option>`).join("");
      zoneSelect.addEventListener("change", () => {
        const value = zoneSelect.value;
        const rows = DATA.analytics.outages.filter((row) => !value || row.zone.includes(value));
        renderTable(rows);
      });
    }

    renderTable(DATA.analytics.outages);
  }

  /* Services ------------------------------------------------ */

  function setupServices() {
    const grid = document.querySelector("[data-services-grid]");
    if (!grid || !DATA) return;

    const masques = (document.body.dataset.servicesMasques || "").split(" ").filter(Boolean);
    grid.innerHTML = DATA.services.filter((service) => !masques.some((cle) => service.link === "index.php?action=" + cle))
      .map(
        (service) => `
      <article class="service-card" id="${service.id}">
        <span class="service-card__icon service-card__icon--${service.tone}" data-icon="${service.icon}"></span>
        <h3>${service.title}</h3>
        <p>${service.description}</p>
        <ul>${service.points
          .map((point) => `<li><span data-icon="check"></span>${point}</li>`)
          .join("")}</ul>
        <a class="text-link" href="${service.link}">Découvrir ${icon("arrow-right")}</a>
      </article>`
      )
      .join("");

    hydrate(grid);
  }

  /* Init ---------------------------------------------------- */

  document.addEventListener("DOMContentLoaded", () => {
    setupCarte();
    setupAnalyses();
    setupServices();
    hydrate(document);
  });
})();
