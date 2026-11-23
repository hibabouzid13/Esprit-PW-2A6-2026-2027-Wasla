/* ============================================================
   WASLA — Service 06 · Priorité santé
   Enregistrement (champs conditionnels, priorité suggérée),
   suivi des demandes (modification, aggravation, évolution),
   indicateurs et espace de coordination réservé aux comptes
   autorisés. Les données de santé ne sont jamais publiques.
   ============================================================ */
(function () {
  "use strict";

  const S = window.WASLA_SVC;
  if (!S || !document.querySelector('[data-service="sante"]')) return;

  const D = S.SDATA;
  const NEEDS = D.healthNeeds;
  const PRIO = D.healthPriorities;
  const STAT = D.healthStatuses;
  const $ = (sel) => document.querySelector(sel);

  const recordsRepo = S.repo("health-records", []);
  const casesRepo = S.repo("health-cases", D.healthCases);
  const flow = ["attente", "verifiee", "encours", "resolue"];
  const flowLabels = { attente: "Reçue", verifiee: "Vérifiée", encours: "Prise en charge", resolue: "Résolue" };
  const resourceLabel = { eau: "Eau", electricite: "Électricité", both: "Eau et électricité" };

  const state = { records: [], cases: [], editing: "", outage: D.activeOutages[0].id, tabs: null };

  const maskPhone = (phone) => String(phone || "").replace(/\d(?=(?:\D*\d){2})/g, "•");
  const shortName = (first, last) => `${first} ${String(last || "").charAt(0)}.`;
  const age = (year) => (year ? new Date().getFullYear() - Number(year) : "—");

  /* ---------- Indicateurs ---------- */

  function renderStats() {
    const count = (status) => state.records.filter((r) => r.status === status || (status === "encours" && r.status === "verifiee")).length;
    const base = D.healthStats;
    const set = (key, value) => {
      const el = document.querySelector(`[data-hstat="${key}"]`);
      if (el) el.textContent = S.fmt.number(value);
    };
    set("registered", base.registered + state.records.length);
    set("attente", base.attente + count("attente"));
    set("encours", base.encours + count("encours"));
    set("resolue", base.resolue + count("resolue"));
    $("[data-my-count]").textContent = state.records.length;
  }

  /* ---------- Formulaire ---------- */

  const form = $("[data-health-form]");

  form.need.innerHTML = '<option value="">Choisir…</option>' + Object.keys(NEEDS).map((k) => `<option value="${k}">${NEEDS[k].label}</option>`).join("");

  function syncConditionals() {
    const need = form.need.value;
    form.querySelectorAll("[data-when-need]").forEach((block) => {
      block.hidden = !block.dataset.whenNeed.split(" ").includes(need);
    });
    const forWhom = (form.querySelector('[name="forWhom"]:checked') || {}).value;
    form.querySelector("[data-when-for]").hidden = forWhom !== "proche";
    suggestPriority();
  }

  // Suggestion de priorité à partir du besoin déclaré (la décision finale revient à la coordination).
  function suggestion() {
    const need = form.need.value;
    if (!need) return null;
    const autonomy = form.autonomy.value === "" ? null : Number(form.autonomy.value);
    if (need === "dialyse") return ["critique", "la dialyse ne peut pas être reportée sans risque."];
    if (need === "oxygene" || need === "respirateur") {
      if (autonomy !== null && autonomy < 4) return ["critique", `autonomie de ${autonomy} h seulement.`];
      return ["elevee", "appareil électrique avec batterie."];
    }
    if (need === "froid") return ["elevee", "le traitement doit rester au froid."];
    if (need === "mobilite") return [Number(form.floor.value) >= 3 ? "elevee" : "moderee", "difficultés d'accès sans ascenseur."];
    return ["moderee", "à préciser avec l'équipe de coordination."];
  }

  function suggestPriority() {
    const hint = $("[data-priority-hint]");
    const s = suggestion();
    if (!s) {
      hint.textContent = "La priorité sera vérifiée par l'équipe de coordination.";
      return;
    }
    hint.innerHTML = `Suggestion : <strong>${PRIO[s[0]].label}</strong> — ${S.esc(s[1])} ${S.esc(PRIO[s[0]].hint)}`;
    if (!form.querySelector('[name="priority"]:checked')) {
      const radio = form.querySelector(`[name="priority"][value="${s[0]}"]`);
      if (radio) radio.checked = true;
    }
    const resource = form.querySelector('[name="resource"]:checked');
    if (!resource && NEEDS[form.need.value]) {
      const r = form.querySelector(`[name="resource"][value="${NEEDS[form.need.value].resource}"]`);
      if (r) r.checked = true;
    }
  }

  form.addEventListener("change", (event) => {
    if (["need", "forWhom", "autonomy", "floor"].includes(event.target.name)) syncConditionals();
  });
  form.addEventListener("reset", () => {
    setTimeout(() => {
      S.clearErrors(form);
      syncConditionals();
    }, 0);
  });

  function setEditing(id) {
    state.editing = id;
    $("[data-cancel-edit]").hidden = !id;
    $("[data-form-title]").textContent = id ? "Modifier la demande" : "Enregistrer une personne prioritaire";
    $("[data-submit-label]").textContent = id ? "Enregistrer les modifications" : "Enregistrer la demande";
  }

  function fillForm(record) {
    form.reset();
    setTimeout(() => {
      Object.keys(record.data).forEach((name) => {
        const controls = form.querySelectorAll(`[name="${name}"]`);
        controls.forEach((control) => {
          if (control.type === "radio") control.checked = control.value === record.data[name];
          else if (control.type === "checkbox") control.checked = Boolean(record.data[name]);
          else control.value = record.data[name];
        });
      });
      form.consent.checked = true;
      syncConditionals();
    }, 0);
  }

  $("[data-cancel-edit]").addEventListener("click", () => {
    setEditing("");
    form.reset();
  });

  form.addEventListener("submit", async (event) => {
    event.preventDefault();
    const ok = S.validate(form, (v) => {
      const errors = {};
      const year = Number(v.birthYear);
      if (v.birthYear && (year < 1915 || year > new Date().getFullYear())) errors.birthYear = "Année de naissance invalide.";
      if (v.phone && v.caregiverPhone && v.phone.replace(/\D/g, "") === v.caregiverPhone.replace(/\D/g, "")) errors.caregiverPhone = "Indiquez un numéro différent de celui de la personne concernée.";
      return errors;
    });
    if (!ok) return;
    const v = S.values(form);
    delete v.consent;
    delete v.csrf_token;
    const submit = form.querySelector('[type="submit"]');
    S.setBusy(submit, true);
    const editingRecord = state.editing ? state.records.find((r) => r.id === state.editing) : null;
    // Envoi au serveur : l'enregistrement devient une demande d'assistance traitée au Back Office
    const sent = await sendToServer(editingRecord ? editingRecord.serverId : 0);
    if (!sent) {
      S.setBusy(submit, false);
      return;
    }
    const now = new Date().toISOString();
    if (editingRecord) {
      editingRecord.data = v;
      editingRecord.serverId = sent.id;
      editingRecord.history.push({ kind: "edit", label: "Informations mises à jour", date: now });
      await saveRecords("Demande mise à jour et transmise à l'équipe d'assistance.");
    } else {
      state.records.unshift({ id: S.uid("hr"), serverId: sent.id, data: v, status: "attente", createdAt: now, history: [{ kind: "created", label: "Demande reçue", date: now }] });
      await saveRecords("Demande enregistrée et transmise à l'équipe d'assistance.");
    }
    S.setBusy(submit, false);
    setEditing("");
    form.reset();
    state.tabs.show("mine");
    $("[data-my-records]").scrollIntoView({ behavior: "smooth", block: "start" });
  });

  /** POST du formulaire vers index.php?action=sante. @return {Promise<{id:number}|null>} */
  async function sendToServer(serverId) {
    const body = new FormData(form);
    if (serverId) body.set("id_demande", String(serverId));
    let data = null;
    try {
      const response = await fetch(form.dataset.endpoint, {
        method: "POST",
        body,
        credentials: "same-origin",
        headers: { Accept: "application/json" }
      });
      data = await response.json().catch(() => null);
    } catch (error) {
      data = null;
    }
    if (data && data.ok) return { id: Number(data.id) || 0 };
    if (!data) {
      S.toast("Envoi impossible (session expirée ou serveur indisponible). Rechargez la page puis réessayez.");
      return null;
    }
    if (data.login) {
      S.toast(data.message);
      setTimeout(() => { window.location.href = data.login; }, 1800);
      return null;
    }
    const errors = data.errors || {};
    Object.keys(errors).forEach((name) => S.fieldError && S.fieldError(form, name, errors[name]));
    const first = Object.keys(errors)[0];
    if (first) {
      const target = form.querySelector(`[name="${first}"]`);
      if (target) target.focus();
    }
    S.toast(data.message || "La demande n'a pas pu être enregistrée.");
    return null;
  }

  async function saveRecords(message) {
    try {
      await recordsRepo.save(state.records);
      if (message) S.toast(message);
    } catch (error) {
      S.toast("Impossible d'enregistrer dans ce navigateur.");
    }
    renderStats();
    renderRecords();
    renderCoord();
  }

  /* ---------- Mes demandes ---------- */

  function needDetail(data) {
    if (data.need === "dialyse") return `${data.sessions || "?"} séance(s) par semaine${data.center ? ` · ${data.center}` : ""}`;
    if (data.need === "oxygene" || data.need === "respirateur") return `Autonomie : ${data.autonomy || 0} h${data.device ? ` · ${data.device}` : ""}`;
    if (data.need === "froid") return `${data.medication || ""}${data.coldHours ? ` · ${data.coldHours} h sans froid` : ""}`;
    if (data.need === "mobilite") return `Étage ${data.floor || 0} · ascenseur : ${data.lift || "non"}`;
    return data.otherNeed || "";
  }

  function timeline(record) {
    return `<ul class="svc-timeline">${record.history
      .slice()
      .reverse()
      .map((h) => `<li class="${h.kind === "alert" ? "is-alert" : "is-done"}"><span class="svc-timeline__dot">${S.icon(h.kind === "alert" ? "triangle-alert" : "check")}</span><div><strong>${S.esc(h.label)}</strong><span>${S.fmt.dateTime(h.date)}</span>${h.text ? `<p>${S.esc(h.text)}</p>` : ""}</div></li>`)
      .join("")}</ul>`;
  }

  function recordCard(record) {
    const d = record.data;
    const need = NEEDS[d.need];
    const prio = PRIO[d.priority];
    const idx = flow.indexOf(record.status);
    return `<article class="card" data-record="${record.id}">
      <div class="card__header">
        <div style="display:flex;align-items:center;gap:12px;">
          <span class="svc-icon" data-icon="${need.icon}"></span>
          <div>
            <h3 class="card__title" style="font-size:16px;">${S.esc(d.firstName)} ${S.esc(d.lastName)} · ${age(d.birthYear)} ans</h3>
            <p class="svc-card-sub">${need.label} · demande du ${S.fmt.date(record.createdAt)}</p>
          </div>
        </div>
        <div class="svc-actions">
          <span class="status-badge status-badge--${prio.badge}">Priorité ${prio.label.toLowerCase()}</span>
          <span class="status-badge status-badge--${STAT[record.status].badge}">${STAT[record.status].label}</span>
        </div>
      </div>
      <div class="card__body">
        <div class="svc-steps-inline" style="margin-bottom:16px;">${flow.map((s, i) => `<span class="svc-step-pill${i < idx || (i === idx && s === "resolue") ? " is-done" : i === idx ? " is-current" : ""}">${flowLabels[s]}</span>`).join("")}</div>
        <dl class="svc-dl">
          <div><dt>Ressource indispensable</dt><dd>${resourceLabel[d.resource]}</dd></div>
          <div><dt>Détail du besoin</dt><dd>${S.esc(needDetail(d)) || "—"}</dd></div>
          <div><dt>Localisation</dt><dd>${S.esc(d.area)}, ${S.esc(d.gov)} <span class="field__hint">(adresse réservée aux équipes)</span></dd></div>
          <div><dt>Téléphone</dt><dd class="svc-masked">${maskPhone(d.phone)}</dd></div>
          <div><dt>Aidant</dt><dd>${S.esc(d.caregiverName)} · <span class="svc-masked">${maskPhone(d.caregiverPhone)}</span></dd></div>
          <div><dt>Déclarant</dt><dd>${d.forWhom === "moi" ? "La personne elle-même" : `Un proche (${S.esc(d.relation || "—")})`}</dd></div>
        </dl>
        <details style="margin-top:16px;">
          <summary class="text-link" style="cursor:pointer;">Évolution de la prise en charge (${record.history.length})</summary>
          <div style="margin-top:12px;">${timeline(record)}</div>
        </details>
      </div>
      <div class="card__footer svc-actions" style="justify-content:space-between;">
        <button class="btn btn--ghost btn--sm" type="button" data-rec-op="advance"${record.status === "resolue" ? " disabled" : ""}>Simuler l'étape suivante (démo)</button>
        <div class="svc-actions">
          <button class="svc-icon-btn svc-icon-btn--danger" type="button" data-rec-op="delete"><span data-icon="trash"></span>Retirer</button>
          <button class="svc-icon-btn" type="button" data-rec-op="edit"><span data-icon="pencil"></span>Modifier</button>
          <button class="btn btn--secondary btn--sm" type="button" data-rec-op="worse"${record.status === "resolue" ? " disabled" : ""}><span data-icon="triangle-alert"></span>Signaler une aggravation</button>
        </div>
      </div>
    </article>`;
  }

  function renderRecords() {
    const holder = $("[data-my-records]");
    holder.innerHTML = state.records.length
      ? state.records.map(recordCard).join("")
      : `<article class="card">${S.emptyState({ icon: "heart-pulse", title: "Aucune demande enregistrée", text: "Enregistrez une personne prioritaire pour suivre ici l'évolution de sa prise en charge.", action: '<button class="btn btn--primary btn--sm" type="button" data-health-tab="form">Enregistrer une personne</button>' })}</article>`;
    S.hydrate(holder);
  }

  const worseForm = $("[data-worse-form]");
  let worseTarget = null;

  $("[data-my-records]").addEventListener("click", async (event) => {
    const op = event.target.closest("[data-rec-op]");
    if (!op) return;
    const record = state.records.find((r) => r.id === op.closest("[data-record]").dataset.record);
    if (!record) return;
    const now = new Date().toISOString();
    if (op.dataset.recOp === "edit") {
      setEditing(record.id);
      fillForm(record);
      state.tabs.show("form");
      $("[data-health-form]").scrollIntoView({ behavior: "smooth", block: "start" });
    } else if (op.dataset.recOp === "worse") {
      worseTarget = record;
      worseForm.reset();
      S.clearErrors(worseForm);
      $("[data-worse-sub]").textContent = `${record.data.firstName} ${record.data.lastName} · ${NEEDS[record.data.need].label}`;
      S.openModal($("#worse-modal"));
    } else if (op.dataset.recOp === "advance") {
      const next = flow[Math.min(flow.indexOf(record.status) + 1, flow.length - 1)];
      record.status = next;
      const labels = { verifiee: "Demande vérifiée par l'équipe de coordination", encours: "Prise en charge : une équipe est assignée", resolue: "Situation résolue" };
      record.history.push({ kind: "status", label: labels[next], text: "Étape simulée pour la démonstration.", date: now });
      await saveRecords(`Statut simulé : ${STAT[next].label.toLowerCase()}.`);
    } else if (op.dataset.recOp === "delete") {
      const ok = await S.confirm({ title: "Retirer cette demande ?", text: "Les informations seront supprimées de ce navigateur. La personne ne sera plus signalée comme prioritaire.", confirmLabel: "Retirer", danger: true });
      if (!ok) return;
      state.records = state.records.filter((r) => r.id !== record.id);
      if (state.editing === record.id) setEditing("");
      await saveRecords("Demande retirée.");
    }
  });

  worseForm.addEventListener("submit", async (event) => {
    event.preventDefault();
    if (!worseTarget || !S.validate(worseForm)) return;
    const v = S.values(worseForm);
    worseTarget.data.priority = "critique";
    if (v.autonomy !== "") worseTarget.data.autonomy = v.autonomy;
    if (worseTarget.status === "resolue") worseTarget.status = "encours";
    worseTarget.history.push({ kind: "alert", label: "Aggravation signalée — priorité passée à critique", text: v.details + (v.autonomy !== "" ? ` (autonomie restante : ${v.autonomy} h)` : ""), date: new Date().toISOString() });
    S.closeModal($("#worse-modal"));
    await saveRecords("Aggravation enregistrée (démonstration : aucune équipe n'est réellement prévenue). En cas de danger, appelez le 190.");
  });

  /* ---------- Coordination (comptes autorisés) ---------- */

  function affectedCases() {
    const outage = D.activeOutages.find((o) => o.id === state.outage);
    const fromRecords = state.records.map((r) => ({
      id: r.id,
      name: shortName(r.data.firstName, r.data.lastName),
      age: age(r.data.birthYear),
      need: r.data.need,
      resource: r.data.resource,
      priority: r.data.priority,
      gov: r.data.gov,
      area: r.data.area,
      status: r.status,
      autonomy: needDetail(r.data),
      team: r.team || "",
      local: true
    }));
    return state.cases
      .concat(fromRecords)
      .filter((c) => c.gov === outage.gov && (c.resource === "both" || c.resource === outage.resource))
      .sort((a, b) => PRIO[b.priority].rank - PRIO[a.priority].rank || flow.indexOf(a.status) - flow.indexOf(b.status));
  }

  function renderCoord() {
    const holder = $("[data-coord]");
    if (!S.user.authorized) {
      holder.innerHTML = `<article class="card"><div class="svc-lock">${S.icon("lock")}<strong>Espace réservé aux équipes habilitées</strong><p>La coordination des interventions est accessible uniquement aux comptes administrateur et aux sous-admins STEG / SONEDE, afin de protéger les données de santé.</p><a class="btn btn--secondary btn--sm" href="index.php?action=connexion">Se connecter avec un compte autorisé</a></div></article>`;
      return;
    }
    const outage = D.activeOutages.find((o) => o.id === state.outage);
    const list = affectedCases();
    const critical = list.filter((c) => c.priority === "critique" && c.status !== "resolue").length;
    holder.innerHTML = `
      <div class="svc-toolbar">
        <div class="svc-toolbar__group">
          <label class="filter-bar__label" for="coord-outage">Coupure</label>
          <select class="select" id="coord-outage" data-coord-outage>${D.activeOutages.map((o) => `<option value="${o.id}"${o.id === state.outage ? " selected" : ""}>${S.esc(o.label)}</option>`).join("")}</select>
        </div>
        <span class="svc-tag svc-tag--navy"><span data-icon="shield-check"></span>Accès autorisé · ${S.esc(S.user.firstName || "équipe")}</span>
      </div>
      <div class="summary-grid">
        <div class="summary-card"><span class="summary-card__icon" data-icon="users"></span><div><p class="summary-card__value">${list.length}</p><p class="summary-card__label">Personnes concernées</p></div></div>
        <div class="summary-card"><span class="summary-card__icon summary-card__icon--red" data-icon="circle-alert"></span><div><p class="summary-card__value">${critical}</p><p class="summary-card__label">Situations critiques ouvertes</p></div></div>
        <div class="summary-card"><span class="summary-card__icon summary-card__icon--amber" data-icon="activity"></span><div><p class="summary-card__value">${list.filter((c) => c.status === "encours").length}</p><p class="summary-card__label">Prises en charge en cours</p></div></div>
        <div class="summary-card"><span class="summary-card__icon summary-card__icon--green" data-icon="circle-check"></span><div><p class="summary-card__value">${list.filter((c) => c.status === "resolue").length}</p><p class="summary-card__label">Résolues</p></div></div>
      </div>
      <article class="card">
        <div class="card__header">
          <div>
            <h2 class="card__title">${S.esc(outage.label)}</h2>
            <p class="svc-card-sub">Depuis ${S.fmt.relative(outage.since)} · triées par priorité · dossiers fictifs de démonstration</p>
          </div>
        </div>
        <div class="table-wrap">
          <table class="data-table svc-table">
            <thead><tr><th>Personne</th><th>Besoin</th><th>Autonomie / détail</th><th>Priorité</th><th>Statut</th><th>Équipe</th><th><span class="sr-only">Actions</span></th></tr></thead>
            <tbody>${list.length ? list.map((c) => `
              <tr data-case="${c.id}" data-local="${c.local ? "1" : ""}">
                <td data-label="Personne"><strong>${S.esc(c.name)}</strong><br><span class="text-muted" style="font-size:11px;">${c.age} ans · ${S.esc(c.area)}</span></td>
                <td data-label="Besoin">${NEEDS[c.need].label}<br><span class="text-muted" style="font-size:11px;">${resourceLabel[c.resource]}</span></td>
                <td data-label="Autonomie">${S.esc(c.autonomy)}</td>
                <td data-label="Priorité"><span class="status-badge status-badge--${PRIO[c.priority].badge}">${PRIO[c.priority].label}</span></td>
                <td data-label="Statut"><span class="status-badge status-badge--${STAT[c.status].badge}">${flowLabels[c.status]}</span></td>
                <td data-label="Équipe">
                  <label class="sr-only" for="team-${c.id}">Équipe assignée</label>
                  <select class="select" id="team-${c.id}" data-team style="min-height:34px;padding-block:5px;font-size:12px;min-width:170px;">
                    <option value="">Non assignée</option>${D.teams.map((t) => `<option${t === c.team ? " selected" : ""}>${S.esc(t)}</option>`).join("")}${c.team && !D.teams.includes(c.team) ? `<option selected>${S.esc(c.team)}</option>` : ""}
                  </select>
                </td>
                <td class="svc-table__actions"><div class="svc-actions" style="justify-content:flex-end;">
                  ${c.status !== "resolue" ? '<button class="svc-icon-btn" type="button" data-case-op="resolve"><span data-icon="circle-check"></span>Résolu</button>' : '<span class="svc-tag svc-tag--green">Clôturé</span>'}
                </div></td>
              </tr>`).join("") : `<tr><td colspan="7">${S.emptyState({ icon: "circle-check", title: "Aucune personne prioritaire concernée", text: "Aucun dossier enregistré dans la zone touchée par cette coupure." })}</td></tr>`}
            </tbody>
          </table>
        </div>
      </article>`;
    S.hydrate(holder);
  }

  async function updateCase(id, local, change) {
    if (local) {
      const record = state.records.find((r) => r.id === id);
      if (!record) return;
      change(record);
      await saveRecords();
    } else {
      const c = state.cases.find((x) => x.id === id);
      if (!c) return;
      change(c);
      try {
        await casesRepo.save(state.cases);
      } catch (error) {
        S.toast("Impossible d'enregistrer dans ce navigateur.");
      }
      renderCoord();
    }
  }

  $("[data-coord]").addEventListener("change", async (event) => {
    if (event.target.matches("[data-coord-outage]")) {
      state.outage = event.target.value;
      renderCoord();
      return;
    }
    if (event.target.matches("[data-team]")) {
      const row = event.target.closest("[data-case]");
      const team = event.target.value;
      await updateCase(row.dataset.case, Boolean(row.dataset.local), (item) => {
        item.team = team;
        if (team && item.status !== "resolue") item.status = "encours";
        if (item.history) item.history.push({ kind: "status", label: team ? `Équipe assignée : ${team}` : "Équipe retirée", date: new Date().toISOString() });
      });
      S.toast(team ? `Équipe assignée : ${team} (démonstration).` : "Assignation retirée.");
    }
  });

  $("[data-coord]").addEventListener("click", async (event) => {
    const op = event.target.closest("[data-case-op]");
    if (!op) return;
    const row = op.closest("[data-case]");
    await updateCase(row.dataset.case, Boolean(row.dataset.local), (item) => {
      item.status = "resolue";
      if (item.history) item.history.push({ kind: "status", label: "Situation résolue par l'équipe de coordination", date: new Date().toISOString() });
    });
    S.toast("Prise en charge clôturée (démonstration).");
  });

  /* ---------- Démarrage ---------- */

  document.addEventListener("click", (event) => {
    const tab = event.target.closest("[data-health-tab]");
    if (!tab) return;
    state.tabs.show(tab.dataset.healthTab);
    document.querySelector('[data-svc-tabs="health"]').scrollIntoView({ behavior: "smooth", block: "start" });
  });

  document.addEventListener("DOMContentLoaded", async () => {
    S.fillGovernorates(form.gov, "Choisir…");
    state.tabs = S.initTabs("health");
    syncConditionals();
    $("[data-my-records]").innerHTML = `<article class="card">${S.skeleton(6)}</article>`;
    [state.records, state.cases] = await Promise.all([recordsRepo.all(), casesRepo.all()]);
    renderStats();
    renderRecords();
    renderCoord();
  });
})();
