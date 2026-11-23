/* ============================================================
   WASLA — Service 05 · Relais de voisinage
   Tableau communautaire (demandes / offres), publication avec
   champs conditionnels, réponses, contact, signalement,
   résolution et rapprochement automatique offres ↔ demandes.
   Aucune adresse exacte n'est jamais affichée.
   ============================================================ */
(function () {
  "use strict";

  const S = window.WASLA_SVC;
  if (!S || !document.querySelector('[data-service="relais"]')) return;

  const D = S.SDATA;
  const CAT = D.relaisCategories;
  const URG = D.urgencies;
  const $ = (sel) => document.querySelector(sel);

  const postsRepo = S.repo("relais-posts", D.relaisPosts);
  const state = {
    posts: [],
    flagged: S.store.get("relais-flagged", []),
    home: S.store.get("relais-home", { gov: "Tunis", area: "El Menzah 6" }),
    tab: "all",
    categories: [],
    urgency: "",
    showResolved: false,
    nearOnly: false,
    query: "",
    sort: "recent",
    focusRequest: ""
  };

  const NEAR_KM = 15;
  const homeCoords = () => S.coords(state.home.gov, state.home.area) || S.coords("Tunis");
  const postCoords = (p) => S.coords(p.gov, p.area);
  const distance = (p) => S.distanceKm(homeCoords(), postCoords(p));
  // Les positions sont volontairement approximatives (quartier) : pas de précision au mètre.
  const approx = (km) => (km < 1 ? "moins de 1 km" : `≈ ${S.formatDistance(km)}`);

  /* ---------- Rapprochement offres ↔ demandes ---------- */

  /**
   * Score sur 100 : même type de ressource (obligatoire), proximité (50 pts sur 0–50 km),
   * même gouvernorat (+15) et même quartier (+10), urgence de la demande (+0 à +15),
   * disponibilité de l'offre (offre récente ou disponibilité immédiate : +10).
   */
  function matchScore(request, offer) {
    if (request.category !== offer.category) return null;
    const d = S.distanceKm(postCoords(request), postCoords(offer));
    let score = Math.max(0, 1 - d / 50) * 50;
    const reasons = [`${CAT[offer.category].label.toLowerCase()}`];
    if (request.gov === offer.gov) {
      score += 15;
      reasons.push("même gouvernorat");
    }
    if (request.area && offer.area && request.area.split(" ")[0].toLowerCase() === offer.area.split(" ")[0].toLowerCase()) {
      score += 10;
      reasons.push("quartier voisin");
    }
    score += { haute: 15, moyenne: 8, faible: 0 }[request.urgency] || 0;
    const recent = Date.now() - new Date(offer.date).getTime() < 86400000;
    if (recent || /aujourd|tous les|toutes les|pendant/i.test(offer.availability || "")) {
      score += 10;
      reasons.push("disponible rapidement");
    }
    return { score: Math.min(100, Math.round(score)), distance: d, reasons };
  }

  function matchesFor(request) {
    return state.posts
      .filter((p) => p.type === "offer" && p.status === "open" && !p.mine && !state.flagged.includes(p.id))
      .map((offer) => ({ offer, m: matchScore(request, offer) }))
      .filter((x) => x.m && x.m.distance <= 60)
      .sort((a, b) => b.m.score - a.m.score);
  }

  /* ---------- Filtres ---------- */

  function inTab(p) {
    if (state.tab === "mine") return p.mine;
    if (state.tab === "request" || state.tab === "offer") return p.type === state.tab;
    return true;
  }

  function visible() {
    const q = state.query;
    const list = state.posts
      .filter((p) => !state.flagged.includes(p.id))
      .filter(inTab)
      .filter((p) => state.showResolved || state.tab === "mine" || p.status !== "resolved")
      .filter((p) => !state.categories.length || state.categories.includes(p.category))
      .filter((p) => !state.urgency || (p.type === "request" && p.urgency === state.urgency))
      .filter((p) => !state.nearOnly || distance(p) <= NEAR_KM)
      .filter((p) => !q || `${p.title} ${p.text} ${p.area} ${p.gov} ${CAT[p.category].label}`.toLowerCase().includes(q));
    const sorters = {
      recent: (a, b) => new Date(b.date) - new Date(a.date),
      near: (a, b) => distance(a) - distance(b),
      urgent: (a, b) => (URG[b.urgency] ? URG[b.urgency].rank : 0) - (URG[a.urgency] ? URG[a.urgency].rank : 0) || new Date(b.date) - new Date(a.date)
    };
    return list.sort(sorters[state.sort]);
  }

  /* ---------- Rendu ---------- */

  function renderCounts() {
    const base = state.posts.filter((p) => !state.flagged.includes(p.id) && p.status !== "resolved");
    const set = (key, value) => {
      const el = document.querySelector(`[data-feed-count="${key}"]`);
      if (el) el.textContent = value;
    };
    set("all", base.length);
    set("request", base.filter((p) => p.type === "request").length);
    set("offer", base.filter((p) => p.type === "offer").length);
    set("mine", state.posts.filter((p) => p.mine).length);
  }

  function renderFilters() {
    $("[data-post-categories]").innerHTML = Object.keys(CAT)
      .map((key) => {
        const on = state.categories.includes(key);
        return `<button class="chip${on ? " is-active" : ""}" type="button" data-cat="${key}" aria-pressed="${on}"><span data-icon="${CAT[key].icon}"></span>${CAT[key].label}</button>`;
      })
      .join("");
    $("[data-post-urgency]").innerHTML = Object.keys(URG)
      .map((key) => `<button class="chip${state.urgency === key ? " is-active" : ""}" type="button" data-urg="${key}" aria-pressed="${state.urgency === key}"><span class="chip__dot chip__dot--${URG[key].badge}"></span>${URG[key].label}</button>`)
      .join("");
    S.hydrate($("[data-post-categories]"));
  }

  function postCard(p) {
    const cat = CAT[p.category];
    const d = distance(p);
    const isRequest = p.type === "request";
    const matchCount = isRequest && p.status === "open" ? matchesFor(p).length : 0;
    const lastReply = p.replies[p.replies.length - 1];
    return `<article class="card svc-post svc-post--${isRequest ? "request" : "offer"}${p.status === "resolved" ? " is-resolved" : ""}${state.focusRequest === p.id ? " is-selected" : ""}" data-post="${p.id}">
      <div class="svc-post__head">
        <span class="svc-avatar svc-avatar--sm${isRequest ? " svc-avatar--amber" : " svc-avatar--green"}" aria-hidden="true">${p.mine ? "V" : S.initials(p.author)}</span>
        <div class="svc-post__author">
          <strong>${p.mine ? `Vous${S.user.firstName ? ` (${S.esc(S.user.firstName)})` : ""}` : S.esc(p.author)}</strong>
          <span><time datetime="${p.date}" title="${S.fmt.dateTime(p.date)}">${S.fmt.relative(p.date)}</time></span>
        </div>
        ${isRequest ? '<span class="status-badge status-badge--watch">Demande d\'aide</span>' : '<span class="status-badge status-badge--normal">Offre d\'aide</span>'}
      </div>
      <h3 class="svc-post__title">${S.esc(p.title)}</h3>
      <p class="svc-post__text">${S.esc(p.text)}</p>
      <div class="svc-meta">
        <span class="svc-tag svc-tag--blue"><span data-icon="${cat.icon}"></span>${cat.label}</span>
        <span><span data-icon="map-pin"></span>${S.esc(p.area)}, ${S.esc(p.gov)} · zone approximative</span>
        ${p.mine ? "" : `<span><span data-icon="navigation"></span>${approx(d)} de chez vous</span>`}
        ${isRequest ? `<span class="status-badge status-badge--${URG[p.urgency].badge}">${URG[p.urgency].label}</span>` : `<span><span data-icon="clock"></span>${S.esc(p.availability || "Disponibilité à convenir")}</span>`}
        ${p.status === "resolved" ? '<span class="svc-tag svc-tag--green"><span data-icon="circle-check"></span>Résolue</span>' : '<span class="svc-tag">Ouverte</span>'}
        ${matchCount ? `<span class="svc-tag svc-tag--blue"><span data-icon="sparkles"></span>${matchCount} offre${matchCount > 1 ? "s" : ""} correspondante${matchCount > 1 ? "s" : ""}</span>` : ""}
      </div>
      ${lastReply ? `<div class="svc-reply" style="margin-top:12px;"><strong>${S.esc(lastReply.author)}</strong> · ${S.fmt.relative(lastReply.date)}<br>${S.esc(lastReply.text)}${p.replies.length > 1 ? `<br><button class="text-link" type="button" data-post-op="reply" style="margin-top:4px;">Voir les ${p.replies.length} réponses</button>` : ""}</div>` : ""}
      <div class="svc-post__foot">
        <div class="svc-actions">
          ${p.status === "open" ? `<button class="svc-icon-btn" type="button" data-post-op="reply"><span data-icon="message-circle"></span>Répondre${p.replies.length ? ` (${p.replies.length})` : ""}</button>` : ""}
          ${!p.mine && p.status === "open" ? '<button class="svc-icon-btn" type="button" data-post-op="contact"><span data-icon="mail"></span>Contacter</button>' : ""}
          ${p.mine && isRequest && p.status === "open" ? '<button class="svc-icon-btn" type="button" data-post-op="matches"><span data-icon="sparkles"></span>Trouver une offre</button>' : ""}
        </div>
        <div class="svc-actions">
          ${p.mine ? `<button class="svc-icon-btn" type="button" data-post-op="resolve"><span data-icon="${p.status === "open" ? "circle-check" : "refresh"}"></span>${p.status === "open" ? "Marquer résolue" : "Rouvrir"}</button>
             <button class="svc-icon-btn svc-icon-btn--danger" type="button" data-post-op="delete" aria-label="Supprimer la publication"><span data-icon="trash"></span></button>`
            : '<button class="svc-icon-btn svc-icon-btn--danger" type="button" data-post-op="report"><span data-icon="flag"></span>Signaler</button>'}
        </div>
      </div>
    </article>`;
  }

  function renderFeed() {
    const list = visible();
    const feed = $("[data-feed]");
    if (!list.length) {
      const mine = state.tab === "mine";
      feed.innerHTML = `<article class="card">${S.emptyState({
        icon: mine ? "user" : "handshake",
        title: mine ? "Vous n'avez encore rien publié" : "Aucune publication",
        text: mine ? "Publiez une demande ou une offre : elle apparaîtra ici." : "Aucune publication ne correspond à vos filtres pour le moment.",
        action: mine ? '<button class="btn btn--primary btn--sm" type="button" data-new-post="request">Demander de l\'aide</button>' : '<button class="btn btn--outline btn--sm" type="button" data-post-reset>Réinitialiser les filtres</button>'
      })}</article>`;
    } else {
      feed.innerHTML = list.map(postCard).join("");
    }
    S.hydrate(feed);
  }

  function renderMatches() {
    const holder = $("[data-match-list]");
    const requests = state.posts.filter((p) => p.mine && p.type === "request" && p.status === "open");
    if (!requests.length) {
      holder.innerHTML = S.emptyState({ icon: "sparkles", title: "Aucune demande en cours", text: "Publiez une demande : WASLA vous proposera les offres les plus proches." });
      return;
    }
    holder.innerHTML = requests
      .map((req) => {
        const top = matchesFor(req).slice(0, 3);
        return `<div class="svc-match${state.focusRequest === req.id ? " is-selected" : ""}" style="${state.focusRequest === req.id ? "background:var(--color-blue-tint);" : ""}">
          <p class="svc-source" style="margin:0;">Votre demande : ${S.esc(req.title)}</p>
          ${top.length
            ? top.map(({ offer, m }) => `
              <div style="display:grid;gap:6px;padding-top:8px;">
                <p class="svc-match__pair"><span data-icon="handshake"></span>${S.esc(offer.author)} · ${S.esc(offer.area)}</p>
                <p class="text-muted" style="font-size:11.5px;">${S.esc(offer.title)}</p>
                <div class="svc-score"><span>${m.score}/100</span><span class="svc-progress${m.score < 50 ? " svc-progress--watch" : ""}"><span style="width:${m.score}%"></span></span><span>${approx(m.distance)}</span></div>
                <p class="field__hint">${m.reasons.join(" · ")}</p>
                <button class="btn btn--outline btn--sm" type="button" data-contact-offer="${offer.id}" style="justify-self:start;"><span data-icon="mail"></span>Contacter</button>
              </div>`).join("")
            : '<p class="field__hint" style="margin-top:6px;">Aucune offre correspondante à moins de 60 km pour l\'instant. Nous vous la suggérerons dès qu\'elle sera publiée.</p>'}
        </div>`;
      })
      .join("");
    S.hydrate(holder);
  }

  function renderAll() {
    renderCounts();
    renderFilters();
    renderFeed();
    renderMatches();
  }

  /* ---------- Publication ---------- */

  const postForm = $("[data-post-form]");

  function syncConditional() {
    const type = (postForm.querySelector('[name="type"]:checked') || {}).value;
    postForm.querySelectorAll("[data-when]").forEach((block) => {
      block.hidden = block.dataset.when !== type;
    });
  }

  function openPost(type) {
    postForm.reset();
    S.clearErrors(postForm);
    postForm.category.innerHTML = '<option value="">Choisir…</option>' + Object.keys(CAT).map((k) => `<option value="${k}">${CAT[k].label}</option>`).join("");
    S.fillGovernorates(postForm.gov, "Choisir…", state.home.gov);
    postForm.area.value = state.home.area;
    if (type) postForm.querySelector(`[name="type"][value="${type}"]`).checked = true;
    syncConditional();
    $("#post-modal-title").textContent = type === "offer" ? "Proposer mon aide" : type === "request" ? "Demander de l'aide" : "Nouvelle publication";
    S.openModal($("#post-modal"));
    requestAnimationFrame(() => postForm.category.focus());
  }

  postForm.addEventListener("change", (event) => {
    if (event.target.name === "type") syncConditional();
  });

  async function savePosts(message) {
    try {
      await postsRepo.save(state.posts);
      if (message) S.toast(message);
    } catch (error) {
      S.toast("Impossible d'enregistrer dans ce navigateur.");
    }
    renderAll();
  }

  postForm.addEventListener("submit", async (event) => {
    event.preventDefault();
    if (!S.validate(postForm)) return;
    const v = S.values(postForm);
    const submit = postForm.querySelector('[type="submit"]');
    S.setBusy(submit, true);
    const post = {
      id: S.uid("rp"),
      type: v.type,
      category: v.category,
      author: "Vous",
      gov: v.gov,
      area: v.area,
      title: v.title,
      text: v.text,
      urgency: v.type === "request" ? v.urgency : "faible",
      availability: v.type === "offer" ? v.availability : "",
      status: "open",
      date: new Date().toISOString(),
      replies: [],
      mine: true
    };
    state.posts.unshift(post);
    S.setBusy(submit, false);
    S.closeModal($("#post-modal"));
    state.tab = "mine";
    tabs.show("mine");
    await savePosts(v.type === "request" ? "Demande publiée (visible dans ce navigateur, démonstration)." : "Offre publiée (visible dans ce navigateur, démonstration).");
    if (post.type === "request") {
      const n = matchesFor(post).length;
      S.toast(n ? `${n} offre${n > 1 ? "s" : ""} proche${n > 1 ? "s" : ""} correspond${n > 1 ? "ent" : ""} à votre demande.` : "Demande publiée. Aucune offre correspondante pour l'instant.");
    }
  });

  /* ---------- Réponse / contact / signalement ---------- */

  const replyForm = $("[data-reply-form]");
  const reportForm = $("[data-report-post-form]");
  let replyTarget = null;
  let replyMode = "reply";
  let reportTarget = null;

  function openReply(post, mode) {
    replyTarget = post;
    replyMode = mode;
    replyForm.reset();
    S.clearErrors(replyForm);
    $("#reply-title").textContent = mode === "contact" ? `Contacter ${post.author}` : "Répondre publiquement";
    $("[data-reply-sub]").textContent = post.title;
    $("[data-reply-label]").textContent = mode === "contact" ? "Message privé *" : "Votre réponse *";
    $("[data-reply-hint]").textContent = mode === "contact"
      ? "Démonstration : le message est enregistré dans ce navigateur, il n'est pas réellement envoyé. Ne partagez pas votre adresse exacte."
      : "Votre réponse sera visible sous la publication. Ne partagez pas d'adresse exacte ni de numéro.";
    $("[data-reply-submit]").textContent = mode === "contact" ? "Envoyer le message" : "Publier la réponse";
    $("[data-reply-thread]").innerHTML = mode === "reply" && post.replies.length
      ? post.replies.map((r) => `<div class="svc-reply"><strong>${S.esc(r.author)}</strong> · ${S.fmt.relative(r.date)}<br>${S.esc(r.text)}</div>`).join("")
      : "";
    S.openModal($("#reply-modal"));
  }

  replyForm.addEventListener("submit", async (event) => {
    event.preventDefault();
    if (!replyTarget || !S.validate(replyForm)) return;
    const v = S.values(replyForm);
    if (replyMode === "reply") {
      replyTarget.replies.push({ author: S.user.firstName ? `${S.user.firstName} (vous)` : "Vous", text: v.message, date: new Date().toISOString() });
      S.closeModal($("#reply-modal"));
      await savePosts("Réponse publiée sous la publication.");
    } else {
      const sent = S.store.get("relais-messages", []);
      sent.push({ postId: replyTarget.id, text: v.message, date: new Date().toISOString() });
      S.store.set("relais-messages", sent);
      S.closeModal($("#reply-modal"));
      S.toast("Message enregistré (démonstration) : il n'a pas été réellement envoyé.");
    }
  });

  reportForm.addEventListener("submit", (event) => {
    event.preventDefault();
    if (!reportTarget || !S.validate(reportForm)) return;
    state.flagged.push(reportTarget.id);
    S.store.set("relais-flagged", state.flagged);
    S.closeModal($("#report-modal"));
    S.toast("Merci : la publication est masquée et sera examinée par un modérateur.");
    renderAll();
  });

  async function postOp(op, post) {
    if (op === "reply") return openReply(post, "reply");
    if (op === "contact") return openReply(post, "contact");
    if (op === "report") {
      reportTarget = post;
      reportForm.reset();
      S.clearErrors(reportForm);
      return S.openModal($("#report-modal"));
    }
    if (op === "matches") {
      state.focusRequest = post.id;
      renderAll();
      const card = $("[data-match-list]");
      card.scrollIntoView({ behavior: "smooth", block: "center" });
      return;
    }
    if (op === "resolve") {
      post.status = post.status === "open" ? "resolved" : "open";
      return savePosts(post.status === "resolved" ? "Merci ! Publication marquée comme résolue." : "Publication rouverte.");
    }
    if (op === "delete") {
      const ok = await S.confirm({ title: "Supprimer cette publication ?", text: post.title, confirmLabel: "Supprimer", danger: true });
      if (!ok) return;
      state.posts = state.posts.filter((p) => p.id !== post.id);
      return savePosts("Publication supprimée.");
    }
  }

  /* ---------- Événements ---------- */

  const tabs = S.initTabs("feed", (tab) => {
    state.tab = tab;
    if (state.posts.length) renderFeed();
  });

  document.addEventListener("click", (event) => {
    const newPost = event.target.closest("[data-new-post]");
    if (newPost) return openPost(newPost.dataset.newPost);
    const op = event.target.closest("[data-post-op]");
    if (op) {
      const post = state.posts.find((p) => p.id === op.closest("[data-post]").dataset.post);
      if (post) postOp(op.dataset.postOp, post);
      return;
    }
    const contact = event.target.closest("[data-contact-offer]");
    if (contact) {
      const offer = state.posts.find((p) => p.id === contact.dataset.contactOffer);
      if (offer) openReply(offer, "contact");
      return;
    }
    const cat = event.target.closest("[data-cat]");
    if (cat) {
      const key = cat.dataset.cat;
      state.categories = state.categories.includes(key) ? state.categories.filter((c) => c !== key) : state.categories.concat(key);
      renderFilters();
      renderFeed();
      return;
    }
    const urg = event.target.closest("[data-urg]");
    if (urg) {
      state.urgency = state.urgency === urg.dataset.urg ? "" : urg.dataset.urg;
      renderFilters();
      renderFeed();
      return;
    }
    if (event.target.closest("[data-post-reset]")) {
      Object.assign(state, { categories: [], urgency: "", showResolved: false, nearOnly: false, query: "", focusRequest: "" });
      $("[data-post-search]").value = "";
      $("[data-show-resolved]").checked = false;
      $("[data-near-only]").checked = false;
      renderAll();
    }
  });

  let timer = null;
  $("[data-post-search]").addEventListener("input", (event) => {
    clearTimeout(timer);
    timer = setTimeout(() => {
      state.query = event.target.value.trim().toLowerCase();
      renderFeed();
    }, 160);
  });
  $("[data-post-sort]").addEventListener("change", (event) => {
    state.sort = event.target.value;
    renderFeed();
  });
  $("[data-show-resolved]").addEventListener("change", (event) => {
    state.showResolved = event.target.checked;
    renderFeed();
  });
  $("[data-near-only]").addEventListener("change", (event) => {
    state.nearOnly = event.target.checked;
    renderFeed();
  });

  function saveHome() {
    state.home = { gov: $("[data-my-gov]").value || "Tunis", area: $("[data-my-area]").value.trim() };
    S.store.set("relais-home", state.home);
    renderFeed();
  }
  $("[data-my-gov]").addEventListener("change", saveHome);
  $("[data-my-area]").addEventListener("change", saveHome);

  /* ---------- Démarrage ---------- */

  document.addEventListener("DOMContentLoaded", async () => {
    S.fillGovernorates($("[data-my-gov]"), "", state.home.gov);
    $("[data-my-area]").value = state.home.area;
    renderFilters();
    $("[data-feed]").innerHTML = `<article class="card">${S.skeleton(10)}</article>`;
    state.posts = await postsRepo.all();
    renderAll();
  });
})();
