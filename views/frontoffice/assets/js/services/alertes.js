/* ============================================================
   WASLA — Service 01 · Alertes personnalisées
   Centre de notifications : filtres, lecture / archivage,
   lieux suivis, préférences et carte des zones concernées.
   ============================================================ */
(function () {
  "use strict";

  const S = window.WASLA_SVC;
  if (!S || !document.querySelector('[data-service="alertes"]')) return;

  const D = S.SDATA;
  const CATS = D.alertCategories;
  const SEV = D.severities;

  /*
   * Notifications réelles (Module 1) : la page contient un bloc JSON
   *   <script type="application/json" id="wasla-live-alerts">…</script>
   * produit par InterruptionController::getAlertFeed() à partir des interruptions du Back Office.
   * La liste est relue à CHAQUE chargement ; seuls les états « lue / archivée » de l'utilisateur
   * sont gardés dans le navigateur (clé alert-flags). Sans ce bloc : données de démonstration.
   */
  const liveAlerts = (() => {
    const el = document.getElementById("wasla-live-alerts");
    if (!el) return null;
    try {
      const rows = JSON.parse(el.textContent);
      return Array.isArray(rows) ? rows : null;
    } catch (error) {
      return null;
    }
  })();

  // Noms de la base (« Le Kef », « La Manouba ») → noms utilisés par la carte (« El Kef », « Manouba »)
  const govKey = (name) =>
    String(name).normalize("NFD").replace(/[̀-ͯ]/g, "").toLowerCase().replace(/^(le|la|el)\s+/, "").trim();
  const govNames = new Map(((window.WASLA_DATA || {}).governorates || []).map((g) => [govKey(g.name), g.name]));
  const canonicalGov = (name) => govNames.get(govKey(name)) || name;

  const alertsRepo = liveAlerts
    ? {
        async all() {
          const flags = S.store.get("alert-flags", {});
          return liveAlerts.map((a) => {
            const f = flags[a.id] || {};
            return {
              ...a,
              gov: canonicalGov(a.gov),
              read: typeof f.read === "boolean" ? f.read : !a.active, // terminées = déjà lues par défaut
              archived: !!f.archived
            };
          });
        },
        async save(items) {
          const flags = {};
          items.forEach((a) => {
            flags[a.id] = { read: !!a.read, archived: !!a.archived };
          });
          if (!S.store.set("alert-flags", flags)) throw new Error("storage");
          return items;
        }
      }
    : S.repo("alerts", () => D.alerts.map((a, i) => ({ ...a, read: i > 5, archived: false })));
  const placesRepo = S.repo("alert-places", D.defaultPlaces);

  const state = {
    alerts: [],
    places: [],
    prefs: S.store.get("alert-prefs", D.defaultAlertPrefs),
    tab: "all",
    category: "",
    severity: "",
    query: "",
    placeGov: "",
    map: null
  };

  const $ = (sel) => document.querySelector(sel);
  const list = $("[data-alert-list]");
  const placeList = $("[data-place-list]");
  const placeIcons = { domicile: "home", travail: "briefcase", proche: "heart", autre: "map-pin" };
  const toneClass = (tone) => (tone === "blue" ? "" : ` svc-icon--${tone}`);
  const markerKind = (severity) => (severity === "critique" ? "outage" : severity === "elevee" ? "watch" : "normal");

  /* ---------- Règles de filtrage ---------- */

  function placeGovs() {
    return state.places.filter((p) => p.enabled).map((p) => p.gov);
  }

  // Ce que l'utilisateur a choisi de recevoir (préférences).
  function matchesPrefs(alert) {
    const p = state.prefs;
    if (!p.water && alert.service === "eau") return false;
    if (!p.electricity && alert.service === "electricite") return false;
    if (!p.categories.includes(alert.category)) return false;
    if (SEV[alert.severity].rank < SEV[p.minSeverity].rank) return false;
    if (p.onlyMyPlaces && !placeGovs().includes(alert.gov)) return false;
    return true;
  }

  function inTab(alert, tab) {
    if (tab === "unread") return !alert.archived && !alert.read;
    if (tab === "history") return alert.archived || !alert.active;
    return !alert.archived;
  }

  function matchesFilters(alert) {
    if (state.category && alert.category !== state.category) return false;
    if (state.severity && SEV[alert.severity].rank < SEV[state.severity].rank) return false;
    if (state.placeGov && alert.gov !== state.placeGov) return false;
    if (state.query) {
      const hay = `${alert.title} ${alert.text} ${alert.gov} ${alert.area} ${CATS[alert.category].label}`.toLowerCase();
      if (!hay.includes(state.query)) return false;
    }
    return true;
  }

  const received = () => state.alerts.filter(matchesPrefs);

  /* ---------- Rendu ---------- */

  function renderStats() {
    const mine = received();
    const set = (key, value) => {
      const el = document.querySelector(`[data-stat="${key}"]`);
      if (el) el.textContent = value;
    };
    set("unread", mine.filter((a) => !a.read && !a.archived).length);
    set("critical", mine.filter((a) => a.active && !a.archived && a.severity === "critique").length);
    set("active", state.alerts.filter((a) => a.active && !a.archived && placeGovs().includes(a.gov)).length);
    set("places", state.places.filter((p) => p.enabled).length);
    ["all", "unread", "history"].forEach((tab) => {
      const el = document.querySelector(`[data-count="${tab}"]`);
      if (el) el.textContent = mine.filter((a) => inTab(a, tab)).length;
    });
  }

  function renderChips() {
    const holder = $("[data-category-chips]");
    const mine = received().filter((a) => inTab(a, state.tab));
    const chip = (key, label, iconName, count) =>
      `<button class="chip${state.category === key ? " is-active" : ""}" type="button" data-category="${key}" aria-pressed="${state.category === key}">${iconName ? `<span data-icon="${iconName}"></span>` : ""}${S.esc(label)} <span>${count}</span></button>`;
    holder.innerHTML =
      chip("", "Toutes", "", mine.length) +
      Object.keys(CATS).map((key) => chip(key, CATS[key].label, CATS[key].icon, mine.filter((a) => a.category === key).length)).join("");
    S.hydrate(holder);
  }

  function alertItem(alert) {
    const cat = CATS[alert.category];
    const sev = SEV[alert.severity];
    const isMine = placeGovs().includes(alert.gov);
    return `
      <article class="svc-item${!alert.read && !alert.archived ? " is-unread" : ""}" data-id="${alert.id}">
        <span class="svc-icon${toneClass(cat.tone)}" data-icon="${cat.icon}"></span>
        <div class="svc-item__body">
          <div class="svc-item__top">
            <h3 class="svc-item__title">${S.esc(alert.title)}</h3>
          </div>
          <p class="svc-item__text">${S.esc(alert.text)}</p>
          <div class="svc-meta">
            <span class="status-badge status-badge--${sev.badge}">${sev.label}</span>
            <span><span data-icon="map-pin"></span>${S.esc(alert.area)}, ${S.esc(alert.gov)}</span>
            <span><span data-icon="clock"></span><time datetime="${alert.date}" title="${S.fmt.dateTime(alert.date)}">${S.fmt.relative(alert.date)}</time></span>
            <span class="svc-tag">${S.esc(D.serviceLabels[alert.service])}</span>
            ${!alert.active ? '<span class="svc-tag svc-tag--green">Terminée</span>' : ""}
            ${isMine ? '<span class="svc-tag svc-tag--blue">Vos lieux</span>' : ""}
          </div>
          <div class="svc-item__actions">
            <button class="svc-icon-btn" type="button" data-action="detail"><span data-icon="eye"></span>Détails</button>
            <button class="svc-icon-btn" type="button" data-action="toggle-read">${alert.read ? '<span data-icon="mail"></span>Marquer non lue' : '<span data-icon="check"></span>Marquer comme lue'}</button>
            <button class="svc-icon-btn" type="button" data-action="archive">${alert.archived ? '<span data-icon="refresh"></span>Restaurer' : '<span data-icon="archive"></span>Archiver'}</button>
            <button class="svc-icon-btn" type="button" data-action="locate"><span data-icon="map"></span>Sur la carte</button>
          </div>
        </div>
      </article>`;
  }

  function renderList() {
    const visible = received().filter((a) => inTab(a, state.tab) && matchesFilters(a)).sort((a, b) => new Date(b.date) - new Date(a.date));
    const caption = $("[data-list-caption]");
    caption.textContent = `${visible.length} alerte${visible.length > 1 ? "s" : ""} · triées de la plus récente à la plus ancienne`;

    const filters = [];
    if (state.category) filters.push(CATS[state.category].label);
    if (state.severity) filters.push(`gravité ≥ ${SEV[state.severity].label.toLowerCase()}`);
    if (state.placeGov) filters.push(`lieu : ${state.placeGov}`);
    if (state.query) filters.push(`« ${state.query} »`);
    $("[data-active-filters]").textContent = filters.length ? `Filtres : ${filters.join(" · ")}` : "Aucun filtre actif";

    if (!visible.length) {
      const reason = state.tab === "unread" ? "Vous avez tout lu. Les nouvelles alertes apparaîtront ici." : filters.length ? "Aucune alerte ne correspond à ces filtres." : "Aucune alerte ne correspond à vos préférences et à vos lieux suivis.";
      list.innerHTML = S.emptyState({
        icon: state.tab === "unread" ? "circle-check" : "bell",
        title: state.tab === "unread" ? "Aucune notification non lue" : "Aucune alerte",
        text: reason,
        action: filters.length ? '<button class="btn btn--outline btn--sm" type="button" data-reset-filters>Réinitialiser les filtres</button>' : ""
      });
    } else {
      list.innerHTML = visible.map(alertItem).join("");
    }
    S.hydrate(list);
  }

  function renderPlaces() {
    if (!state.places.length) {
      placeList.innerHTML = S.emptyState({ icon: "map-pin", title: "Aucun lieu suivi", text: "Ajoutez votre domicile, votre travail ou le domicile d'un proche." });
      return;
    }
    placeList.innerHTML = state.places
      .map((place) => {
        const count = state.alerts.filter((a) => a.active && !a.archived && a.gov === place.gov).length;
        return `
        <div class="svc-place${place.enabled ? "" : " is-off"}${state.placeGov === place.gov ? " is-selected" : ""}" data-id="${place.id}">
          <span class="svc-icon svc-icon--sm" data-icon="${placeIcons[place.kind] || "map-pin"}"></span>
          <div class="svc-place__body">
            <button class="text-link" type="button" data-place-filter title="Afficher les alertes de ce lieu" style="font-size:13px;">${S.esc(place.label)}</button>
            <p class="svc-place__sub">${S.esc(place.area)}, ${S.esc(place.gov)} · ${count ? `<strong style="color:var(--color-outage)">${count} alerte${count > 1 ? "s" : ""} active${count > 1 ? "s" : ""}</strong>` : "aucune alerte active"}</p>
          </div>
          <button class="switch" type="button" role="switch" aria-checked="${place.enabled}" aria-label="Alertes pour ${S.esc(place.label)}" data-place-toggle></button>
          <button class="svc-icon-btn svc-icon-btn--danger" type="button" aria-label="Ne plus suivre ${S.esc(place.label)}" data-place-delete><span data-icon="trash"></span></button>
        </div>`;
      })
      .join("");
    S.hydrate(placeList);
  }

  function renderMap() {
    if (!state.map) return;
    const markers = received()
      .filter((a) => a.active && !a.archived)
      .map((a) => {
        const c = S.coords(a.gov, a.area);
        return c && { id: a.id, lon: c.lon, lat: c.lat, kind: markerKind(a.severity), label: a.title, sub: `${a.area}, ${a.gov} · ${SEV[a.severity].label}` };
      })
      .concat(
        state.places.filter((p) => p.enabled).map((p) => {
          const c = S.coords(p.gov, p.area);
          return c && { id: p.id, lon: c.lon + 0.035, lat: c.lat + 0.03, kind: "place", label: p.label, sub: `${p.area}, ${p.gov}` };
        })
      );
    state.map.setMarkers(markers.filter(Boolean));
  }

  function renderAll() {
    renderStats();
    renderChips();
    renderList();
    renderPlaces();
    renderMap();
  }

  /* ---------- Préférences ---------- */

  const prefForm = $("[data-pref-form]");

  function fillPrefs() {
    const p = state.prefs;
    prefForm.querySelectorAll("[data-pref-switch]").forEach((sw) => sw.setAttribute("aria-checked", String(Boolean(p[sw.dataset.prefSwitch]))));
    $("[data-pref-categories]").innerHTML = Object.keys(CATS)
      .map((key) => `<label class="checkbox"><input type="checkbox" name="categories" value="${key}"${p.categories.includes(key) ? " checked" : ""}> ${S.esc(CATS[key].label)}</label>`)
      .join("");
    prefForm.minSeverity.value = p.minSeverity;
    prefForm.querySelectorAll('[name="channels"]').forEach((box) => {
      box.checked = Boolean(p.channels[box.value]);
    });
    prefForm.querySelectorAll('[name="frequency"]').forEach((radio) => {
      radio.checked = radio.value === p.frequency;
    });
  }

  prefForm.addEventListener("click", (event) => {
    const sw = event.target.closest("[data-pref-switch]");
    if (sw) sw.setAttribute("aria-checked", sw.getAttribute("aria-checked") === "true" ? "false" : "true");
  });

  prefForm.addEventListener("submit", (event) => {
    event.preventDefault();
    const v = S.values(prefForm);
    const ok = S.validate(prefForm, (vals) => {
      const errors = {};
      if (!vals.categories.length) errors.categories = "Choisissez au moins une catégorie.";
      if (!vals.channels.length) errors.channels = "Choisissez au moins un canal.";
      return errors;
    });
    if (!ok) return;
    const read = (key) => prefForm.querySelector(`[data-pref-switch="${key}"]`).getAttribute("aria-checked") === "true";
    state.prefs = {
      water: read("water"),
      electricity: read("electricity"),
      onlyMyPlaces: read("onlyMyPlaces"),
      categories: v.categories,
      minSeverity: v.minSeverity,
      channels: { site: v.channels.includes("site"), email: v.channels.includes("email"), sms: v.channels.includes("sms") },
      frequency: v.frequency || "immediate"
    };
    S.store.set("alert-prefs", state.prefs);
    if (!state.prefs.water && !state.prefs.electricity) S.toast("Préférences enregistrées : alertes eau et électricité désactivées.");
    else S.toast("Préférences enregistrées dans ce navigateur.");
    renderAll();
  });

  /* ---------- Actions sur les alertes ---------- */

  async function persist() {
    try {
      await alertsRepo.save(state.alerts);
    } catch (error) {
      S.toast("Impossible d'enregistrer : stockage du navigateur indisponible.");
    }
  }

  function findAlert(id) {
    return state.alerts.find((a) => a.id === id);
  }

  const advice = {
    eau: ["Constituez une réserve d'eau potable avant l'heure annoncée.", "Fermez les robinets pour éviter un dégât des eaux au retour de l'eau.", "Les points d'eau ouverts sont indiqués dans les zones refuge."],
    electricite: ["Débranchez les appareils sensibles (ordinateur, télévision, box).", "Gardez le réfrigérateur et le congélateur fermés.", "Si un proche dépend d'un appareil médical, inscrivez-le au service Priorité santé."],
    distribution: ["Limitez les usages simultanés pendant la perturbation.", "Signalez une aggravation sur la carte des réseaux."],
    chaleur: ["Buvez régulièrement et évitez de sortir entre 12h et 16h.", "Prenez des nouvelles des personnes âgées de votre entourage.", "Des espaces climatisés sont ouverts dans les zones refuge."],
    penurie: ["Réservez l'eau du réseau aux usages essentiels.", "Réparez sans attendre les fuites (chasse d'eau, robinets)."],
    incident: ["Ne vous approchez pas d'un câble au sol ou d'une canalisation endommagée.", "Signalez tout danger sur la carte des réseaux."]
  };

  const adviceLinks = {
    eau: { href: "index.php?action=refuges", label: "Voir les points d'eau" },
    electricite: { href: "index.php?action=sante", label: "Priorité santé" },
    chaleur: { href: "index.php?action=refuges", label: "Espaces climatisés" },
    distribution: { href: "index.php?action=carte", label: "Carte des réseaux" },
    incident: { href: "index.php?action=carte", label: "Carte des réseaux" },
    penurie: { href: "index.php?action=factures", label: "Suivre ma consommation" }
  };

  function openDetail(id) {
    const alert = findAlert(id);
    if (!alert) return;
    if (!alert.read) {
      alert.read = true;
      persist();
      renderAll();
    }
    const cat = CATS[alert.category];
    const sev = SEV[alert.severity];
    const link = adviceLinks[alert.category];
    $("#alert-modal-title").textContent = alert.title;
    $("[data-alert-modal-sub]").textContent = `${cat.label} · reçue ${S.fmt.relative(alert.date)}`;
    $("[data-alert-modal-body]").innerHTML = `
      <div class="svc-block">
        <p class="svc-highlight" style="font-weight:500;font-size:13.5px;">${S.esc(alert.text)}</p>
      </div>
      <div class="svc-block">
        <dl class="svc-dl">
          <div><dt>Gravité</dt><dd><span class="status-badge status-badge--${sev.badge}">${sev.label}</span></dd></div>
          <div><dt>Statut</dt><dd>${alert.active ? "En cours" : "Terminée"} · ${alert.read ? "lue" : "non lue"}${alert.archived ? " · archivée" : ""}</dd></div>
          <div><dt>Service concerné</dt><dd>${S.esc(D.serviceLabels[alert.service])}</dd></div>
          <div><dt>Catégorie</dt><dd>${S.esc(cat.label)}</dd></div>
          <div><dt>Localisation</dt><dd>${S.esc(alert.area)}, ${S.esc(alert.gov)}</dd></div>
          <div><dt>Date et heure</dt><dd>${S.fmt.dateTime(alert.date)}</dd></div>
        </dl>
      </div>
      <div class="svc-block">
        <h3>Que faire ?</h3>
        <ul class="svc-bullets">${advice[alert.category].map((tip) => `<li><span data-icon="check"></span>${S.esc(tip)}</li>`).join("")}</ul>
        ${link ? `<a class="text-link" style="margin-top:12px;" href="${link.href}">${link.label} <span data-icon="arrow-right"></span></a>` : ""}
      </div>`;
    $("[data-alert-modal-foot]").innerHTML = `
      <span class="svc-modal__foot-note">Source : données de démonstration</span>
      <button class="btn btn--outline btn--sm" type="button" data-modal-action="toggle-read" data-id="${alert.id}">${alert.read ? "Marquer non lue" : "Marquer comme lue"}</button>
      <button class="btn btn--outline btn--sm" type="button" data-modal-action="archive" data-id="${alert.id}">${alert.archived ? "Restaurer" : "Archiver"}</button>
      <button class="btn btn--secondary btn--sm" type="button" data-modal-action="locate" data-id="${alert.id}"><span data-icon="map"></span>Voir sur la carte</button>`;
    S.openModal($("#alert-modal"));
  }

  function locate(id) {
    const alert = findAlert(id);
    if (!alert || !state.map) return;
    if (alert.archived || !alert.active || !matchesPrefs(alert)) {
      S.toast("Cette alerte n'est plus active : elle n'apparaît pas sur la carte.");
      return;
    }
    state.map.select(id, true);
    const card = $("[data-alert-map]");
    card.scrollIntoView({ behavior: "smooth", block: "center" });
  }

  function act(action, id) {
    const alert = findAlert(id);
    if (!alert) return;
    if (action === "detail") return openDetail(id);
    if (action === "locate") return locate(id);
    if (action === "toggle-read") {
      alert.read = !alert.read;
      S.toast(alert.read ? "Alerte marquée comme lue." : "Alerte marquée comme non lue.");
    }
    if (action === "archive") {
      alert.archived = !alert.archived;
      if (alert.archived) alert.read = true;
      S.toast(alert.archived ? "Alerte archivée : retrouvez-la dans l'historique." : "Alerte restaurée.");
    }
    persist();
    renderAll();
  }

  list.addEventListener("click", (event) => {
    const button = event.target.closest("[data-action]");
    if (!button) return;
    act(button.dataset.action, button.closest("[data-id]").dataset.id);
  });

  $("#alert-modal").addEventListener("click", (event) => {
    const button = event.target.closest("[data-modal-action]");
    if (!button) return;
    const { modalAction, id } = button.dataset;
    S.closeModal($("#alert-modal"));
    act(modalAction, id);
  });

  $("[data-mark-all]").addEventListener("click", () => {
    const targets = received().filter((a) => !a.read && !a.archived);
    if (!targets.length) {
      S.toast("Toutes vos notifications sont déjà lues.");
      return;
    }
    targets.forEach((a) => {
      a.read = true;
    });
    persist();
    renderAll();
    S.toast(`${targets.length} notification${targets.length > 1 ? "s" : ""} marquée${targets.length > 1 ? "s" : ""} comme lue${targets.length > 1 ? "s" : ""}.`);
  });

  /* ---------- Filtres ---------- */

  $("[data-category-chips]").addEventListener("click", (event) => {
    const chip = event.target.closest("[data-category]");
    if (!chip) return;
    state.category = chip.dataset.category;
    renderChips();
    renderList();
  });

  $("[data-severity-filter]").addEventListener("change", (event) => {
    state.severity = event.target.value;
    renderList();
  });

  let searchTimer = null;
  $("[data-alert-search]").addEventListener("input", (event) => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
      state.query = event.target.value.trim().toLowerCase();
      renderList();
    }, 160);
  });

  function resetFilters() {
    state.category = "";
    state.severity = "";
    state.query = "";
    state.placeGov = "";
    $("[data-severity-filter]").value = "";
    $("[data-alert-search]").value = "";
    renderAll();
  }

  document.addEventListener("click", (event) => {
    if (event.target.closest("[data-reset-filters]")) resetFilters();
  });

  /* ---------- Lieux suivis ---------- */

  async function savePlaces() {
    try {
      await placesRepo.save(state.places);
    } catch (error) {
      S.toast("Impossible d'enregistrer vos lieux dans ce navigateur.");
    }
  }

  placeList.addEventListener("click", async (event) => {
    const row = event.target.closest("[data-id]");
    if (!row) return;
    const place = state.places.find((p) => p.id === row.dataset.id);
    if (!place) return;
    if (event.target.closest("[data-place-toggle]")) {
      place.enabled = !place.enabled;
      await savePlaces();
      S.toast(place.enabled ? `Alertes activées pour « ${place.label} ».` : `Alertes en pause pour « ${place.label} ».`);
      renderAll();
    } else if (event.target.closest("[data-place-delete]")) {
      const ok = await S.confirm({ title: "Ne plus suivre ce lieu ?", text: `Vous ne recevrez plus d'alertes pour « ${place.label} » (${place.area}, ${place.gov}).`, confirmLabel: "Ne plus suivre", danger: true });
      if (!ok) return;
      state.places = state.places.filter((p) => p.id !== place.id);
      if (state.placeGov === place.gov) state.placeGov = "";
      await savePlaces();
      S.toast("Lieu supprimé.");
      renderAll();
    } else if (event.target.closest("[data-place-filter]")) {
      state.placeGov = state.placeGov === place.gov ? "" : place.gov;
      renderAll();
      list.scrollIntoView({ behavior: "smooth", block: "start" });
    }
  });

  const placeForm = $("[data-place-form]");
  S.fillGovernorates(placeForm.gov);

  placeForm.addEventListener("submit", async (event) => {
    event.preventDefault();
    const ok = S.validate(placeForm, (v) => {
      const errors = {};
      if (state.places.length >= 6) errors.label = "Vous suivez déjà 6 lieux : supprimez-en un pour en ajouter un nouveau.";
      if (state.places.some((p) => p.label.toLowerCase() === v.label.toLowerCase())) errors.label = "Vous suivez déjà un lieu portant ce nom.";
      return errors;
    });
    if (!ok) return;
    const v = S.values(placeForm);
    const submit = placeForm.querySelector('[type="submit"]');
    S.setBusy(submit, true);
    state.places.push({ id: S.uid("pl"), kind: v.kind, label: v.label, gov: v.gov, area: v.area, enabled: true });
    await savePlaces();
    S.setBusy(submit, false);
    placeForm.reset();
    S.clearErrors(placeForm);
    S.closeModal($("#place-modal"));
    S.toast(`« ${v.label} » est maintenant suivi.`);
    renderAll();
  });

  /* ---------- Démarrage ---------- */

  document.addEventListener("DOMContentLoaded", async () => {
    list.innerHTML = S.skeleton(9);
    placeList.innerHTML = S.skeleton(4);
    S.initTabs("alert-view", (tab) => {
      state.tab = tab;
      if (state.alerts.length) {
        renderChips();
        renderList();
      }
    });
    fillPrefs();
    state.map = S.createMap($("[data-alert-map]"), { label: "Carte des alertes et des lieux suivis", onSelect: (id) => (findAlert(id) ? openDetail(id) : null) });
    [state.alerts, state.places] = await Promise.all([alertsRepo.all(), placesRepo.all()]);
    renderAll();
    if (state.map) state.map.fit();
  });
})();
