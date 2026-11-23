/* ============================================================
   WASLA — Service 03 · Fournisseurs
   Annuaire filtrable, profils détaillés, demandes d'intervention
   (formulaire validé), suivi et évaluation des professionnels.
   ============================================================ */
(function () {
  "use strict";

  const S = window.WASLA_SVC;
  if (!S || !document.querySelector('[data-service="fournisseurs"]')) return;

  const D = S.SDATA;
  const SPEC = D.specialties;
  const AV = D.availability;
  const $ = (sel) => document.querySelector(sel);

  const requestsRepo = S.repo("pro-requests", D.providerRequests);
  const state = {
    requests: [],
    extraReviews: S.store.get("pro-reviews", {}),
    specialty: "",
    gov: "",
    availability: "",
    verified: false,
    query: "",
    sort: "rating"
  };

  const problemTypes = {
    electricien: ["Panne après une coupure", "Disjoncteur qui saute", "Prise ou circuit défectueux", "Mise aux normes", "Autre"],
    plombier: ["Fuite d'eau", "Absence ou faible pression d'eau", "Chauffe-eau", "Citerne ou réservoir", "Autre"],
    maintenance: ["Groupe électrogène", "Onduleur", "Climatiseur", "Entretien préventif", "Autre"],
    solaire: ["Étude d'installation", "Kit de secours avec batterie", "Panne de l'installation", "Autre"],
    hydraulique: ["Pompe ou surpresseur", "Stockage d'eau", "Réseau d'immeuble", "Autre"]
  };
  const flow = D.requestStatuses;
  const statusLabel = D.requestStatusLabels;
  const statusBadge = { envoyee: "nodata", acceptee: "watch", planifiee: "watch", terminee: "normal", annulee: "outage" };

  /* ---------- Données dérivées ---------- */

  function reviewsOf(pro) {
    return (state.extraReviews[pro.id] || []).concat(pro.reviews);
  }

  // Note moyenne recalculée en intégrant les avis ajoutés dans ce navigateur.
  function ratingOf(pro) {
    const extra = state.extraReviews[pro.id] || [];
    if (!extra.length) return { rating: pro.rating, count: pro.reviewsCount };
    const total = pro.rating * pro.reviewsCount + extra.reduce((sum, r) => sum + r.rating, 0);
    const count = pro.reviewsCount + extra.length;
    return { rating: Math.round((total / count) * 10) / 10, count };
  }

  const proById = (id) => D.providers.find((p) => p.id === id);

  function filtered() {
    const q = state.query;
    return D.providers
      .filter((p) => !state.specialty || p.specialty === state.specialty)
      .filter((p) => !state.gov || p.gov === state.gov || p.zones.includes(state.gov))
      .filter((p) => !state.availability || (state.availability === "now" ? p.available === "now" : p.available !== "busy"))
      .filter((p) => !state.verified || p.verified)
      .filter((p) => {
        if (!q) return true;
        const hay = `${p.name} ${SPEC[p.specialty].label} ${p.skills.join(" ")} ${p.gov} ${p.area} ${p.zones.join(" ")}`.toLowerCase();
        return hay.includes(q);
      })
      .sort((a, b) => {
        if (state.sort === "name") return a.name.localeCompare(b.name, "fr");
        if (state.sort === "reviews") return ratingOf(b).count - ratingOf(a).count;
        return ratingOf(b).rating - ratingOf(a).rating || ratingOf(b).count - ratingOf(a).count;
      });
  }

  /* ---------- Rendu ---------- */

  function renderTiles() {
    const holder = $("[data-specialty-tiles]");
    holder.innerHTML = Object.keys(SPEC)
      .map((key) => {
        const count = D.providers.filter((p) => p.specialty === key).length;
        const active = state.specialty === key;
        return `<button class="summary-card svc-tile${active ? " is-active" : ""}" type="button" data-specialty="${key}" aria-pressed="${active}">
          <span class="summary-card__icon" data-icon="${SPEC[key].icon}"></span>
          <span><span class="summary-card__value">${count}</span><span class="summary-card__label">${SPEC[key].plural}</span></span>
        </button>`;
      })
      .join("");
    S.hydrate(holder);
  }

  function proCard(pro) {
    const r = ratingOf(pro);
    const av = AV[pro.available];
    return `<article class="card svc-pro" data-id="${pro.id}">
      <div class="svc-pro__head">
        <span class="svc-avatar svc-avatar--${SPEC[pro.specialty].avatar}" aria-hidden="true">${S.initials(pro.name)}</span>
        <div>
          <h3 class="svc-pro__name">${S.esc(pro.name)}</h3>
          <p class="svc-pro__spec">${SPEC[pro.specialty].label} · ${pro.years} ans d'expérience</p>
        </div>
      </div>
      <div class="svc-pro__rating">${S.stars(r.rating)}<strong>${S.fmt.number(r.rating, 1)}</strong><span>(${r.count} avis)</span></div>
      <div class="svc-meta"><span><span data-icon="map-pin"></span>${S.esc(pro.area)}, ${S.esc(pro.gov)}</span><span><span data-icon="map"></span>${pro.zones.length} gouvernorat${pro.zones.length > 1 ? "s" : ""} couvert${pro.zones.length > 1 ? "s" : ""}</span></div>
      <div class="svc-pro__badges">
        <span class="status-badge status-badge--${av.badge}">${av.label}</span>
        ${pro.verified ? '<span class="svc-tag svc-tag--green"><span data-icon="shield-check"></span>Vérifié</span>' : '<span class="svc-tag">Vérification en cours</span>'}
        <span class="svc-tag svc-tag--demo">Démo</span>
      </div>
      <div class="svc-pro__actions">
        <button class="btn btn--outline btn--sm" type="button" data-pro-action="profile">Voir le profil</button>
        <button class="btn btn--secondary btn--sm" type="button" data-pro-action="request">Contacter</button>
      </div>
    </article>`;
  }

  function renderGrid() {
    const list = filtered();
    const grid = $("[data-pro-grid]");
    const parts = [];
    if (state.specialty) parts.push(SPEC[state.specialty].plural.toLowerCase());
    if (state.gov) parts.push(`intervenant à ${state.gov}`);
    $("[data-pro-caption]").textContent = `${list.length} professionnel${list.length > 1 ? "s" : ""}${parts.length ? ` · ${parts.join(" · ")}` : ""}`;
    grid.innerHTML = list.length
      ? list.map(proCard).join("")
      : `<div class="card" style="grid-column:1/-1;">${S.emptyState({ icon: "search", title: "Aucun professionnel trouvé", text: "Élargissez la zone, retirez un filtre ou essayez un autre mot-clé.", action: '<button class="btn btn--outline btn--sm" type="button" data-pro-reset>Réinitialiser les filtres</button>' })}</div>`;
    S.hydrate(grid);
  }

  function requestItem(req) {
    const pro = proById(req.providerId);
    const idx = flow.indexOf(req.status);
    return `<button class="svc-item" type="button" data-request-id="${req.id}" style="width:100%;text-align:start;border-left:0;border-right:0;border-top:0;background:transparent;">
      <span class="svc-avatar svc-avatar--sm svc-avatar--${pro ? SPEC[pro.specialty].avatar : "blue"}" aria-hidden="true">${pro ? S.initials(pro.name) : "?"}</span>
      <span class="svc-item__body">
        <span class="svc-item__top"><span class="svc-item__title" style="font-size:13px;">${S.esc(req.problem)}</span><span class="status-badge status-badge--${statusBadge[req.status]}">${statusLabel[req.status]}</span></span>
        <span class="svc-meta" style="margin-top:4px;"><span>${pro ? S.esc(pro.name) : "Professionnel"}</span><span><span data-icon="calendar"></span>${S.fmt.date(req.slot)}</span>${req.demo ? '<span class="svc-tag svc-tag--demo">Exemple</span>' : ""}</span>
        ${req.status !== "annulee" ? `<span class="svc-steps-inline" style="margin-top:8px;">${flow.map((s, i) => `<span class="svc-step-pill${i < idx ? " is-done" : i === idx ? " is-current" : ""}">${statusLabel[s]}</span>`).join("")}</span>` : ""}
        ${req.status === "terminee" && !req.rating ? '<span class="svc-tag svc-tag--blue" style="margin-top:8px;"><span data-icon="star"></span>À évaluer</span>' : ""}
      </span>
    </button>`;
  }

  function renderRequests() {
    const holder = $("[data-request-list]");
    const list = state.requests.slice().sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt));
    $("[data-request-count]").textContent = `${list.filter((r) => r.status !== "terminee" && r.status !== "annulee").length} en cours`;
    holder.innerHTML = list.length
      ? list.map(requestItem).join("")
      : S.emptyState({ icon: "wrench", title: "Aucune demande", text: "Vos demandes d'intervention apparaîtront ici." });
    S.hydrate(holder);
  }

  /* ---------- Profil ---------- */

  function maskPhone(phone) {
    return phone.replace(/(\+216 \d{2}) \d{3} (\d)\d{2}/, "$1 ••• $2••");
  }

  function openProfile(id) {
    const pro = proById(id);
    if (!pro) return;
    const r = ratingOf(pro);
    const reviews = reviewsOf(pro);
    $("#pro-modal-title").textContent = pro.name;
    $("[data-pro-modal-sub]").textContent = `${SPEC[pro.specialty].label} · ${pro.area}, ${pro.gov}`;
    $("[data-pro-modal-body]").innerHTML = `
      <div class="svc-block">
        <div class="svc-profile-head">
          <span class="svc-avatar svc-avatar--lg svc-avatar--${SPEC[pro.specialty].avatar}" aria-hidden="true">${S.initials(pro.name)}</span>
          <div>
            <h3>${S.esc(pro.name)}</h3>
            <div class="svc-pro__rating" style="margin-top:4px;">${S.stars(r.rating)}<strong>${S.fmt.number(r.rating, 1)}</strong><span>${r.count} avis · ${pro.years} ans d'expérience</span></div>
            <div class="svc-pro__badges" style="margin-top:8px;">
              <span class="status-badge status-badge--${AV[pro.available].badge}">${AV[pro.available].label}</span>
              ${pro.verified ? '<span class="svc-tag svc-tag--green"><span data-icon="shield-check"></span>Identité et qualifications vérifiées (démo)</span>' : '<span class="svc-tag">Vérification en cours</span>'}
            </div>
          </div>
        </div>
      </div>
      <div class="svc-block"><h3>Présentation</h3><p>${S.esc(pro.presentation)}</p></div>
      <div class="svc-block"><h3>Compétences</h3><div class="svc-chips">${pro.skills.map((s) => `<span class="svc-tag svc-tag--blue">${S.esc(s)}</span>`).join("")}</div></div>
      <div class="svc-block svc-split" style="grid-template-columns:1fr 1fr;">
        <div><h3>Expériences</h3><ul class="svc-bullets">${pro.experiences.map((e) => `<li><span data-icon="briefcase"></span>${S.esc(e)}</li>`).join("")}</ul></div>
        <div><h3>Certifications</h3><ul class="svc-bullets">${pro.certifications.map((c) => `<li><span data-icon="shield-check"></span>${S.esc(c)}</li>`).join("")}</ul></div>
      </div>
      <div class="svc-block"><h3>Zone d'intervention</h3><div class="svc-chips">${pro.zones.map((z) => `<span class="svc-tag"><span data-icon="map-pin"></span>${S.esc(z)}</span>`).join("")}</div></div>
      <div class="svc-block">
        <h3>Tarifs indicatifs</h3>
        <table class="data-table"><tbody>${pro.tariffs.map((t) => `<tr><td>${S.esc(t.label)}</td><td style="text-align:end"><strong>${S.esc(t.price)}</strong></td></tr>`).join("")}</tbody></table>
        <p class="field__hint" style="margin-top:6px;">Tarifs donnés à titre indicatif : le devis final est établi par le professionnel.</p>
      </div>
      <div class="svc-block">
        <h3>Avis des clients (${reviews.length})</h3>
        ${reviews.map((rv) => `<div class="svc-review"><div class="svc-review__head"><span>${S.esc(rv.author)}${rv.local ? ' <span class="svc-tag svc-tag--blue">Votre avis</span>' : ""}</span>${S.stars(rv.rating)}</div><p>${S.esc(rv.text)}</p><span class="field__hint">${S.fmt.relative(rv.date)}</span></div>`).join("")}
      </div>
      <div class="svc-block">
        <h3>Coordonnées</h3>
        <dl class="svc-dl">
          <div><dt>Téléphone</dt><dd><span data-phone>${maskPhone(pro.phone)}</span> <button class="text-link" type="button" data-reveal-phone>Afficher</button></dd></div>
          <div><dt>E-mail</dt><dd>${S.esc(pro.email)}</dd></div>
        </dl>
        <p class="field__hint" style="margin-top:6px;">Coordonnées fictives (démonstration).</p>
      </div>`;
    $("[data-pro-modal-foot]").innerHTML = `
      <span class="svc-modal__foot-note">Profil de démonstration</span>
      <button class="btn btn--outline btn--sm" type="button" data-svc-close>Fermer</button>
      <button class="btn btn--primary btn--sm" type="button" data-request-from="${pro.id}"><span data-icon="send"></span>Demander une intervention</button>`;
    const body = $("[data-pro-modal-body]");
    body.querySelector("[data-reveal-phone]").addEventListener("click", (event) => {
      body.querySelector("[data-phone]").textContent = pro.phone;
      event.currentTarget.remove();
    });
    S.openModal($("#pro-modal"));
  }

  /* ---------- Demande d'intervention ---------- */

  const form = $("[data-request-form]");

  function fillProblems() {
    const pro = proById(form.providerId.value);
    const types = pro ? problemTypes[pro.specialty] : ["Choisissez d'abord un professionnel"];
    form.problem.innerHTML = (pro ? '<option value="">Choisir…</option>' : "") + types.map((t) => `<option${pro ? "" : ' value=""'}>${S.esc(t)}</option>`).join("");
    $("[data-busy-note]").hidden = !pro || pro.available !== "busy";
  }

  function openRequest(proId) {
    form.reset();
    S.clearErrors(form);
    form.providerId.innerHTML = '<option value="">Choisir un professionnel…</option>' +
      Object.keys(SPEC).map((key) => `<optgroup label="${SPEC[key].plural}">${D.providers.filter((p) => p.specialty === key).map((p) => `<option value="${p.id}">${S.esc(p.name)} — ${S.esc(p.gov)}</option>`).join("")}</optgroup>`).join("");
    form.providerId.value = proId || "";
    fillProblems();
    const pro = proById(proId);
    S.fillGovernorates(form.gov, "Choisir…", pro ? pro.gov : state.gov);
    const tomorrow = new Date(Date.now() + 86400000);
    form.date.value = tomorrow.toISOString().slice(0, 10);
    $("[data-desc-count]").textContent = "0 / 600 caractères";
    S.openModal($("#request-modal"));
    requestAnimationFrame(() => (proId ? form.problem : form.providerId).focus());
  }

  form.providerId.addEventListener("change", fillProblems);
  form.description.addEventListener("input", () => {
    $("[data-desc-count]").textContent = `${form.description.value.length} / 600 caractères`;
  });

  form.addEventListener("submit", async (event) => {
    event.preventDefault();
    const ok = S.validate(form, (v) => {
      const pro = proById(v.providerId);
      if (pro && v.gov && !pro.zones.includes(v.gov)) return { gov: `${pro.name} n'intervient pas à ${v.gov} (zones : ${pro.zones.join(", ")}).` };
      return {};
    });
    if (!ok) return;
    const v = S.values(form);
    const submit = form.querySelector('[type="submit"]');
    S.setBusy(submit, true);
    const request = {
      id: S.uid("req"),
      providerId: v.providerId,
      problem: v.problem,
      urgency: v.urgency,
      description: v.description,
      gov: v.gov,
      area: v.area,
      slot: new Date(`${v.date}T${v.slot === "matin" ? "09" : v.slot === "soir" ? "18" : "14"}:00:00`).toISOString(),
      slotLabel: form.slot.options[form.slot.selectedIndex].text,
      phone: v.phone,
      status: "envoyee",
      rating: null,
      createdAt: new Date().toISOString(),
      history: [{ status: "envoyee", date: new Date().toISOString() }]
    };
    state.requests.push(request);
    try {
      await requestsRepo.save(state.requests);
      S.toast("Demande enregistrée (démonstration) : aucun message n'a été réellement envoyé.");
    } catch (error) {
      S.toast("Impossible d'enregistrer la demande dans ce navigateur.");
    }
    S.setBusy(submit, false);
    S.closeModal($("#request-modal"));
    renderRequests();
    openRequestDetail(request.id);
  });

  /* ---------- Suivi et évaluation ---------- */

  async function saveRequests(message) {
    try {
      await requestsRepo.save(state.requests);
      if (message) S.toast(message);
    } catch (error) {
      S.toast("Impossible d'enregistrer dans ce navigateur.");
    }
    renderRequests();
  }

  function openRequestDetail(id) {
    const req = state.requests.find((r) => r.id === id);
    if (!req) return;
    const pro = proById(req.providerId);
    const idx = flow.indexOf(req.status);
    const descriptions = {
      envoyee: "Votre demande attend la réponse du professionnel.",
      acceptee: "Le professionnel a accepté et va vous proposer un créneau.",
      planifiee: `Intervention prévue le ${S.fmt.date(req.slot)}${req.slotLabel ? ` (${req.slotLabel.toLowerCase()})` : ""}.`,
      terminee: "L'intervention est terminée."
    };
    $("#request-detail-title").textContent = req.problem;
    $("[data-request-detail-sub]").textContent = `${pro ? pro.name : "Professionnel"} · demande du ${S.fmt.date(req.createdAt)}`;
    $("[data-request-detail-body]").innerHTML = `
      ${req.demo ? '<p class="svc-notice" style="margin-top:0;"><span data-icon="info"></span><span>Demande d\'exemple fournie avec la démonstration.</span></p>' : ""}
      <div class="svc-block">
        ${req.status === "annulee"
          ? '<p class="svc-notice svc-notice--danger" style="margin:0;"><span data-icon="circle-alert"></span><span>Demande annulée.</span></p>'
          : `<ul class="svc-timeline">${flow.map((s, i) => `<li class="${i < idx || (i === idx && s === "terminee") ? "is-done" : i === idx ? "is-current" : ""}"><span class="svc-timeline__dot">${i < idx || (i === idx && s === "terminee") ? S.icon("check") : ""}</span><div><strong>${statusLabel[s]}</strong><p>${i <= idx ? descriptions[s] : "À venir"}</p></div></li>`).join("")}</ul>`}
      </div>
      <div class="svc-block">
        <dl class="svc-dl">
          <div><dt>Urgence</dt><dd>${req.urgency === "urgente" ? "Urgente (24 h)" : "Normale"}</dd></div>
          <div><dt>Lieu</dt><dd>${S.esc(req.area)}, ${S.esc(req.gov)}</dd></div>
          <div class="svc-dl--full"><dt>Description</dt><dd style="font-weight:500;">${S.esc(req.description)}</dd></div>
        </dl>
      </div>
      ${req.status === "terminee" ? `<div class="svc-block" data-rating-block>${req.rating
        ? `<h3>Votre évaluation</h3><div class="svc-review__head"><span>${S.stars(req.rating.value)}</span></div><p>${S.esc(req.rating.comment)}</p>`
        : `<h3>Évaluer ${pro ? S.esc(pro.name) : "le professionnel"}</h3>
          <form class="form-grid" data-rating-form novalidate>
            <div class="field"><label>Note *</label><div class="svc-rating-input" role="radiogroup" aria-label="Note sur 5">${[1, 2, 3, 4, 5].map((n) => `<button type="button" role="radio" aria-checked="false" aria-label="${n} sur 5" data-star="${n}">${S.icon("star")}</button>`).join("")}</div><input type="hidden" name="stars" data-required="Choisissez une note."><p class="field__error" id="rate-stars-error" data-error-for="stars"></p></div>
            <div class="field"><label for="rate-comment">Commentaire *</label><textarea class="textarea" id="rate-comment" name="comment" style="min-height:90px;" data-required="Ajoutez un court commentaire." data-min="10" data-max="400"></textarea><p class="field__error" id="rate-comment-error" data-error-for="comment"></p></div>
            <button class="btn btn--primary btn--sm" type="submit" style="justify-self:start;"><span data-icon="star"></span>Publier mon avis</button>
          </form>`}</div>` : ""}`;
    const foot = $("[data-request-detail-foot]");
    foot.innerHTML = `
      ${req.status !== "terminee" && req.status !== "annulee" ? `<button class="btn btn--ghost btn--sm" type="button" data-req-op="advance" title="Fait progresser le statut pour tester le suivi">Simuler l'étape suivante (démo)</button>` : ""}
      ${req.status !== "terminee" && req.status !== "annulee" ? '<button class="btn btn--outline btn--sm" type="button" data-req-op="cancel">Annuler la demande</button>' : ""}
      <button class="btn btn--secondary btn--sm" type="button" data-svc-close>Fermer</button>`;
    const modal = $("#request-detail");
    modal.dataset.id = id;
    S.hydrate(modal);

    const ratingForm = modal.querySelector("[data-rating-form]");
    if (ratingForm) {
      ratingForm.addEventListener("click", (event) => {
        const star = event.target.closest("[data-star]");
        if (!star) return;
        const n = Number(star.dataset.star);
        ratingForm.stars.value = n;
        ratingForm.querySelectorAll("[data-star]").forEach((b) => {
          b.classList.toggle("is-on", Number(b.dataset.star) <= n);
          b.setAttribute("aria-checked", String(Number(b.dataset.star) === n));
        });
        S.fieldError(ratingForm, "stars", "");
      });
      ratingForm.addEventListener("submit", async (event) => {
        event.preventDefault();
        if (!S.validate(ratingForm)) return;
        const v = S.values(ratingForm);
        req.rating = { value: Number(v.stars), comment: v.comment };
        state.extraReviews[req.providerId] = (state.extraReviews[req.providerId] || []).concat({ author: S.user.firstName || "Vous", rating: Number(v.stars), text: v.comment, date: new Date().toISOString(), local: true });
        S.store.set("pro-reviews", state.extraReviews);
        await saveRequests("Merci ! Votre avis est publié (visible dans ce navigateur).");
        renderGrid();
        openRequestDetail(req.id);
      });
    }
    S.openModal(modal);
  }

  $("#request-detail").addEventListener("click", async (event) => {
    const op = event.target.closest("[data-req-op]");
    if (!op) return;
    const id = $("#request-detail").dataset.id;
    const req = state.requests.find((r) => r.id === id);
    if (!req) return;
    if (op.dataset.reqOp === "advance") {
      req.status = flow[Math.min(flow.indexOf(req.status) + 1, flow.length - 1)];
      await saveRequests(`Statut simulé : ${statusLabel[req.status].toLowerCase()}.`);
      openRequestDetail(id);
    } else if (op.dataset.reqOp === "cancel") {
      const ok = await S.confirm({ title: "Annuler cette demande ?", text: "Le professionnel ne pourra plus y donner suite.", confirmLabel: "Annuler la demande", danger: true });
      if (!ok) return;
      req.status = "annulee";
      await saveRequests("Demande annulée.");
      openRequestDetail(id);
    }
  });

  /* ---------- Événements ---------- */

  $("[data-specialty-tiles]").addEventListener("click", (event) => {
    const tile = event.target.closest("[data-specialty]");
    if (!tile) return;
    state.specialty = state.specialty === tile.dataset.specialty ? "" : tile.dataset.specialty;
    renderTiles();
    renderGrid();
  });

  let timer = null;
  $("[data-pro-search]").addEventListener("input", (event) => {
    clearTimeout(timer);
    timer = setTimeout(() => {
      state.query = event.target.value.trim().toLowerCase();
      renderGrid();
    }, 160);
  });
  $("[data-pro-gov]").addEventListener("change", (event) => {
    state.gov = event.target.value;
    renderGrid();
  });
  $("[data-pro-availability]").addEventListener("change", (event) => {
    state.availability = event.target.value;
    renderGrid();
  });
  $("[data-pro-verified]").addEventListener("change", (event) => {
    state.verified = event.target.checked;
    renderGrid();
  });
  $("[data-pro-sort]").addEventListener("change", (event) => {
    state.sort = event.target.value;
    renderGrid();
  });

  document.addEventListener("click", (event) => {
    if (event.target.closest("[data-pro-reset]")) {
      Object.assign(state, { specialty: "", gov: "", availability: "", verified: false, query: "" });
      $("[data-pro-search]").value = "";
      $("[data-pro-gov]").value = "";
      $("[data-pro-availability]").value = "";
      $("[data-pro-verified]").checked = false;
      renderTiles();
      renderGrid();
      return;
    }
    const action = event.target.closest("[data-pro-action]");
    if (action) {
      const id = action.closest("[data-id]").dataset.id;
      if (action.dataset.proAction === "profile") openProfile(id);
      else openRequest(id);
      return;
    }
    const from = event.target.closest("[data-request-from]");
    if (from) {
      S.closeModal($("#pro-modal"));
      openRequest(from.dataset.requestFrom);
      return;
    }
    if (event.target.closest("[data-new-request]")) {
      openRequest("");
      return;
    }
    const item = event.target.closest("[data-request-id]");
    if (item) openRequestDetail(item.dataset.requestId);
  });

  /* ---------- Démarrage ---------- */

  document.addEventListener("DOMContentLoaded", async () => {
    S.fillGovernorates($("[data-pro-gov]"), "Toute la Tunisie");
    renderTiles();
    $("[data-pro-grid]").innerHTML = `<div class="card" style="grid-column:1/-1;">${S.skeleton(8)}</div>`;
    $("[data-request-list]").innerHTML = S.skeleton(4);
    state.requests = await requestsRepo.all();
    renderGrid();
    renderRequests();
  });
})();
