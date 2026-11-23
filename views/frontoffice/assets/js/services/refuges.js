/* ============================================================
   WASLA — Service 04 · Zones refuge (Module 3)
   Carte interactive + panneau de résultats triés par distance,
   filtres d'équipements, fiche détaillée et itinéraire.

   Les refuges viennent de la BASE DE DONNÉES : la vue PHP
   (pages/refuges/zones-refuge.php) les encode en JSON dans
   l'attribut data-refuges de <main>. Aucune donnée de démonstration.
   La réservation se fait sur une page PHP (formulaire POST validé
   côté navigateur ET côté serveur) : ce script ne fait qu'y mener.
   ============================================================ */
(function () {
  "use strict";

  const S = window.WASLA_SVC;
  const root = document.querySelector('[data-service="refuges"]');
  if (!S || !root) return;

  const $ = (sel) => document.querySelector(sel);

  /** Services de la colonne SET refuge_zone.services (+ accessible_pmr). */
  const FEAT = {
    climatisation: { label: "Climatisation", icon: "snowflake" },
    eau_potable: { label: "Eau potable", icon: "droplet" },
    recharge: { label: "Recharge électrique", icon: "battery-charging" },
    wifi: { label: "Wi-Fi", icon: "globe" },
    sanitaires: { label: "Sanitaires", icon: "sparkles" },
    espace_enfants: { label: "Espace enfants", icon: "users" },
    pmr: { label: "Accessible PMR", icon: "accessibility" }
  };

  const STATUS = {
    open: { label: "Ouvert", badge: "normal", marker: "normal" },
    full: { label: "Complet", badge: "watch", marker: "watch" },
    closed: { label: "Fermé", badge: "nodata", marker: "nodata" }
  };

  /** Noms de la table zone → noms de la carte (assets/js/data.js). */
  const GOV_ALIAS = { "Le Kef": "El Kef", "La Manouba": "Manouba" };

  function readJson(attr, fallback) {
    try {
      return JSON.parse(root.getAttribute(attr) || "") || fallback;
    } catch (e) {
      return fallback;
    }
  }

  const config = {
    canReserve: root.dataset.canReserve === "1",
    reserveUrl: root.dataset.reserveUrl || "",
    loginUrl: root.dataset.loginUrl || "",
    myReservationsUrl: root.dataset.myReservationsUrl || ""
  };

  const state = {
    refuges: [],
    mine: readJson("data-my-reservations", {}),
    origin: { lon: 10.1815, lat: 36.8065, label: "Tunis" },
    query: "",
    features: [],
    onlyAvailable: false,
    maxKm: 0,
    selected: "",
    map: null
  };

  /* ---------- Données ---------- */

  /** Coordonnées : celles de la base, sinon une position approximative dans le gouvernorat. */
  function normalize(r) {
    const gov = GOV_ALIAS[r.gov] || r.gov;
    let pos = r.lat != null && r.lon != null ? { lat: Number(r.lat), lon: Number(r.lon) } : S.coords(gov, r.address);
    if (!pos) pos = { lat: 36.8065, lon: 10.1815 };
    return { ...r, lat: pos.lat, lon: pos.lon, approx: r.lat == null || r.lon == null, features: r.features || [] };
  }

  function enrich(refuge) {
    return { ...refuge, distance: S.distanceKm(state.origin, refuge) };
  }

  function visible() {
    const q = state.query;
    return state.refuges
      .map(enrich)
      .filter((r) => !q || `${r.name} ${r.type} ${r.gov} ${r.address}`.toLowerCase().includes(q))
      .filter((r) => state.features.every((f) => r.features.includes(f)))
      .filter((r) => !state.onlyAvailable || (r.status === "open" && r.available > 0))
      .filter((r) => !state.maxKm || r.distance <= state.maxKm)
      .sort((a, b) => a.distance - b.distance);
  }

  const byId = (id) => {
    const r = state.refuges.find((x) => x.id === id);
    return r ? enrich(r) : null;
  };

  const reservedHere = (id) => Number(state.mine[id] || 0);

  /* ---------- Rendu ---------- */

  function renderStats() {
    const all = state.refuges.map(enrich);
    const open = all.filter((r) => r.status === "open");
    const set = (key, value) => {
      const el = document.querySelector(`[data-rstat="${key}"]`);
      if (el) el.textContent = value;
    };
    set("open", open.length);
    set("places", S.fmt.number(open.reduce((sum, r) => sum + r.available, 0)));
    set("clim", open.filter((r) => r.features.includes("climatisation")).length);
    const nearest = open.sort((a, b) => a.distance - b.distance)[0];
    set("nearest", nearest ? S.formatDistance(nearest.distance) : "—");
    $("[data-rstat-label]").textContent = nearest ? `Plus proche : ${nearest.name}` : "Aucun lieu ouvert";
  }

  function renderChips() {
    const holder = $("[data-feature-chips]");
    holder.innerHTML = Object.keys(FEAT)
      .map((key) => {
        const on = state.features.includes(key);
        return `<button class="chip${on ? " is-active" : ""}" type="button" data-feature="${key}" aria-pressed="${on}"><span data-icon="${FEAT[key].icon}"></span>${FEAT[key].label}</button>`;
      })
      .join("");
    S.hydrate(holder);
  }

  function capacityClass(r) {
    const ratio = r.capacity ? r.available / r.capacity : 0;
    return r.status !== "open" ? " svc-progress--outage" : ratio < 0.2 ? " svc-progress--watch" : "";
  }

  function featureIcons(r) {
    return r.features
      .filter((f) => FEAT[f])
      .map((f) => `<span class="svc-feature" title="${FEAT[f].label}"><span data-icon="${FEAT[f].icon}"></span><span class="sr-only">${FEAT[f].label}</span></span>`)
      .join("");
  }

  function refugeItem(r) {
    const st = STATUS[r.status];
    const fill = r.status === "closed" || !r.capacity ? 0 : (r.available / r.capacity) * 100;
    return `<button class="svc-refuge${r.id === state.selected ? " is-selected" : ""}" type="button" data-refuge="${r.id}" aria-pressed="${r.id === state.selected}">
      <span class="svc-refuge__top">
        <span><span class="svc-refuge__name">${S.esc(r.name)}</span><span class="svc-refuge__type" style="display:block;">${S.esc(r.type)} · ${S.esc(r.gov)}</span></span>
        <span class="svc-refuge__distance">${S.formatDistance(r.distance)}</span>
      </span>
      <span class="svc-places"><span class="status-badge status-badge--${st.badge}">${st.label}</span><span class="svc-progress${capacityClass(r)}"><span style="width:${fill}%"></span></span><span>${r.status === "closed" ? "—" : `${r.available}/${r.capacity} places`}</span></span>
      <span class="svc-features">${featureIcons(r)}</span>
    </button>`;
  }

  function renderList() {
    const list = visible();
    const holder = $("[data-refuge-list]");
    $("[data-refuge-caption]").textContent = `${list.length} lieu${list.length > 1 ? "x" : ""} · depuis ${state.origin.label}`;
    const emptyText = state.refuges.length
      ? "Retirez un équipement, augmentez la distance ou décochez « Places disponibles »."
      : "Aucun refuge n'a encore été validé. Revenez bientôt.";
    holder.innerHTML = list.length
      ? list.map(refugeItem).join("")
      : S.emptyState({ icon: "home", title: "Aucun lieu trouvé", text: emptyText, action: state.refuges.length ? '<button class="btn btn--outline btn--sm" type="button" data-refuge-reset>Réinitialiser les filtres</button>' : "" });
    S.hydrate(holder);
    $(".svc-map__empty").hidden = list.length > 0;
    if (state.map) {
      state.map.setMarkers(
        list
          .map((r) => ({ id: r.id, lon: r.lon, lat: r.lat, kind: STATUS[r.status].marker, label: r.name, sub: `${STATUS[r.status].label} · ${S.formatDistance(r.distance)}` }))
          .concat([{ id: "me", lon: state.origin.lon, lat: state.origin.lat, kind: "me", label: "Votre position de référence", sub: state.origin.label }])
      );
      if (state.selected) state.map.select(state.selected);
    }
  }

  /** Bouton principal de la fiche : réserver (page PHP), se connecter, ou état du lieu. */
  function reserveAction(r) {
    if (r.status !== "open") {
      return `<button class="btn btn--primary btn--sm" type="button" disabled><span data-icon="check"></span>${r.status === "closed" ? "Fermé" : "Complet"}</button>`;
    }
    if (reservedHere(r.id)) {
      return `<a class="btn btn--outline btn--sm" href="${S.esc(config.myReservationsUrl)}"><span data-icon="calendar"></span>Voir ma réservation</a>`;
    }
    if (config.canReserve) {
      return `<a class="btn btn--primary btn--sm" href="${S.esc(config.reserveUrl + encodeURIComponent(r.id))}"><span data-icon="check"></span>Réserver une place</a>`;
    }
    if (config.loginUrl) {
      return `<a class="btn btn--primary btn--sm" href="${S.esc(config.loginUrl)}"><span data-icon="user"></span>Se connecter pour réserver</a>`;
    }
    return "";
  }

  function renderDetail() {
    const card = $("[data-refuge-detail]");
    const r = byId(state.selected);
    if (!r) {
      card.hidden = true;
      return;
    }
    const st = STATUS[r.status];
    const mapsUrl = `https://www.google.com/maps/dir/?api=1&destination=${r.lat},${r.lon}`;
    const mine = reservedHere(r.id);
    const fill = r.status === "closed" || !r.capacity ? 0 : ((r.capacity - r.available) / r.capacity) * 100;
    card.hidden = false;
    card.innerHTML = `
      <div class="card__header">
        <div>
          <h2 class="card__title">${S.esc(r.name)}</h2>
          <p class="svc-card-sub">${S.esc(r.type)} · ${S.esc(r.gov)}</p>
        </div>
        <span class="status-badge status-badge--${st.badge}">${st.label}</span>
      </div>
      <div class="card__body">
        <div class="svc-split" style="grid-template-columns:1.1fr 1fr;">
          <dl class="svc-dl">
            <div class="svc-dl--full"><dt>Adresse</dt><dd>${S.esc(r.address)}, ${S.esc(r.gov)}</dd></div>
            <div><dt>Distance</dt><dd>${S.formatDistance(r.distance)} depuis ${S.esc(state.origin.label)}${r.approx ? ' <span class="field__hint">(position approximative)</span>' : ""}</dd></div>
            <div><dt>Horaires</dt><dd>${S.esc(r.hours)}</dd></div>
            <div><dt>Capacité d'accueil</dt><dd>${r.capacity} personnes</dd></div>
            <div><dt>Places disponibles</dt><dd>${r.status === "closed" ? "Fermé actuellement" : `${r.available} place${r.available > 1 ? "s" : ""}`}</dd></div>
            <div class="svc-dl--full"><dt>Remplissage</dt><dd><span class="svc-progress${capacityClass(r)}" style="display:block;margin-top:6px;"><span style="width:${fill}%"></span></span></dd></div>
            ${r.phone ? `<div><dt>Contact</dt><dd>${S.esc(r.phone)}</dd></div>` : ""}
          </dl>
          <div>
            <h3 class="svc-section-title" style="font-size:13.5px;">Services proposés</h3>
            <ul class="svc-service-list">${Object.keys(FEAT).map((f) => `<li class="${r.features.includes(f) ? "" : "is-missing"}"><span data-icon="${FEAT[f].icon}"></span>${FEAT[f].label}</li>`).join("")}</ul>
            ${mine ? `<p class="svc-notice" style="margin:14px 0 0;"><span data-icon="circle-check"></span><span>Vous avez une réservation en cours ici (${mine} personne${mine > 1 ? "s" : ""}).</span></p>` : ""}
          </div>
        </div>
      </div>
      <div class="card__footer svc-actions" style="justify-content:flex-end;">
        <button class="btn btn--outline btn--sm" type="button" data-refuge-op="locate"><span data-icon="map"></span>Voir sur la carte</button>
        <a class="btn btn--outline btn--sm" href="${mapsUrl}" target="_blank" rel="noopener noreferrer"><span data-icon="navigation"></span>Itinéraire</a>
        ${reserveAction(r)}
      </div>`;
    S.hydrate(card);
  }

  function renderAll() {
    renderStats();
    renderList();
    renderDetail();
  }

  function select(id, fromMap) {
    state.selected = id;
    renderList();
    renderDetail();
    if (state.map) state.map.select(id, true);
    const item = document.querySelector(`[data-refuge="${id}"]`);
    if (item && fromMap) item.scrollIntoView({ block: "nearest" });
    if (window.innerWidth < 980) $("[data-refuge-detail]").scrollIntoView({ behavior: "smooth", block: "start" });
  }

  /* ---------- Position ---------- */

  function setOriginFromGov(name) {
    const g = S.governorate(name);
    if (!g) return;
    state.origin = { lon: g.lon, lat: g.lat, label: g.name };
    $("[data-position-label]").textContent = `Position de référence : ${g.name}`;
    renderAll();
  }

  $("[data-locate-me]").addEventListener("click", (event) => {
    const button = event.currentTarget;
    if (!navigator.geolocation) {
      S.toast("La géolocalisation n'est pas disponible : choisissez votre gouvernorat dans la liste.");
      return;
    }
    S.setBusy(button, true);
    navigator.geolocation.getCurrentPosition(
      (pos) => {
        S.setBusy(button, false);
        const { latitude: lat, longitude: lon } = pos.coords;
        if (lat < 30 || lat > 37.7 || lon < 7.4 || lon > 11.8) {
          S.toast("Votre position semble hors de Tunisie : la position de référence est conservée.");
          return;
        }
        state.origin = { lon, lat, label: "votre position" };
        const origin = $("[data-refuge-origin]");
        if (!origin.querySelector('[value="__gps"]')) origin.insertAdjacentHTML("afterbegin", '<option value="__gps">Ma position</option>');
        origin.value = "__gps";
        $("[data-position-label]").textContent = "Position : localisation de l'appareil";
        S.toast("Position mise à jour : les lieux sont triés depuis votre emplacement.");
        renderAll();
      },
      () => {
        S.setBusy(button, false);
        S.toast("Localisation refusée ou indisponible : choisissez votre gouvernorat dans la liste.");
      },
      { timeout: 8000, maximumAge: 300000 }
    );
  });

  $("[data-refuge-origin]").addEventListener("change", (event) => {
    if (event.target.value !== "__gps") setOriginFromGov(event.target.value);
  });

  /* ---------- Filtres ---------- */

  $("[data-feature-chips]").addEventListener("click", (event) => {
    const chip = event.target.closest("[data-feature]");
    if (!chip) return;
    const key = chip.dataset.feature;
    state.features = state.features.includes(key) ? state.features.filter((f) => f !== key) : state.features.concat(key);
    renderChips();
    renderList();
  });

  let timer = null;
  $("[data-refuge-search]").addEventListener("input", (event) => {
    clearTimeout(timer);
    timer = setTimeout(() => {
      state.query = event.target.value.trim().toLowerCase();
      renderList();
    }, 160);
  });
  $("[data-only-available]").addEventListener("change", (event) => {
    state.onlyAvailable = event.target.checked;
    renderList();
  });
  $("[data-refuge-distance]").addEventListener("change", (event) => {
    state.maxKm = Number(event.target.value) || 0;
    renderList();
  });

  document.addEventListener("click", (event) => {
    if (event.target.closest("[data-refuge-reset]")) {
      Object.assign(state, { query: "", features: [], onlyAvailable: false, maxKm: 0 });
      $("[data-refuge-search]").value = "";
      $("[data-only-available]").checked = false;
      $("[data-refuge-distance]").value = "";
      renderChips();
      renderList();
      if (state.map) state.map.reset();
      return;
    }
    const item = event.target.closest("[data-refuge]");
    if (item) select(item.dataset.refuge);
    const op = event.target.closest('[data-refuge-op="locate"]');
    if (op && state.map) {
      state.map.select(state.selected, true);
      $("[data-refuge-map]").scrollIntoView({ behavior: "smooth", block: "center" });
    }
  });

  /* ---------- Démarrage ---------- */

  document.addEventListener("DOMContentLoaded", () => {
    S.fillGovernorates($("[data-refuge-origin]"), "", "Tunis");
    renderChips();
    state.map = S.createMap($("[data-refuge-map]"), { label: "Carte des zones refuge", onSelect: (id) => (id !== "me" ? select(id, true) : null) });
    state.refuges = readJson("data-refuges", []).map(normalize);
    renderAll();
  });
})();
