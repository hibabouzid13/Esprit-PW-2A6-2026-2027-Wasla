/* ============================================================
   WASLA — Service 02 · Factures & IA
   Vue générale (graphiques), gestion des factures (import
   simulé, saisie manuelle), analyse automatique et assistant.
   L'« IA » est simulée par des règles explicites : chaque
   résultat distingue les données de la facture des hypothèses.
   ============================================================ */
(function () {
  "use strict";

  const S = window.WASLA_SVC;
  if (!S || !document.querySelector('[data-service="factures"]')) return;

  const D = S.SDATA;
  const T = D.tariffs;
  const NS = "http://www.w3.org/2000/svg";
  const $ = (sel) => document.querySelector(sel);

  const billsRepo = S.repo("bills", D.bills);
  const state = { bills: [], trend: "electricite", filter: "", analysisId: "", chat: S.store.get("chat-history", []), tabs: null };

  const typeLabel = { electricite: "Électricité", eau: "Eau" };
  const unit = (type) => T[type].unit;
  const sourceLabel = { demo: "Démo", manual: "Saisie", import: "Importée" };

  /* ---------- Calculs ---------- */

  const byDateDesc = (a, b) => new Date(b.periodEnd) - new Date(a.periodEnd);
  const sorted = () => state.bills.slice().sort(byDateDesc);

  function previousOf(bill) {
    return state.bills
      .filter((b) => b.type === bill.type && b.id !== bill.id && new Date(b.periodEnd) < new Date(bill.periodEnd))
      .sort(byDateDesc)[0] || null;
  }

  const days = (bill) => Math.max(1, Math.round((new Date(bill.periodEnd) - new Date(bill.periodStart)) / 86400000) + 1);
  const perDay = (bill) => bill.quantity / days(bill);
  const pct = (a, b) => (b ? ((a - b) / b) * 100 : 0);

  function variation(bill) {
    const prev = previousOf(bill);
    if (!prev) return null;
    // Comparaison à durée égale : consommation moyenne par jour.
    return { prev, quantity: pct(perDay(bill), perDay(prev)), amount: pct(bill.amount, prev.amount) };
  }

  function breakdown(bill) {
    const t = T[bill.type];
    const energy = bill.quantity * t.price;
    const subtotal = energy + t.fixed + t.extra;
    const tax = subtotal * t.taxRate;
    return { energy, fixed: t.fixed, extra: t.extra, extraLabel: t.extraLabel, tax, total: subtotal + tax };
  }

  function billMonths(bill) {
    const out = [];
    const d = new Date(bill.periodStart);
    d.setDate(1);
    const end = new Date(bill.periodEnd);
    while (d <= end && out.length < 12) {
      out.push(d.getMonth());
      d.setMonth(d.getMonth() + 1);
    }
    return out;
  }

  const isOverdue = (bill) => bill.status === "a-payer" && bill.dueDate && new Date(bill.dueDate) < new Date();

  function statusBadge(bill) {
    if (bill.status === "payee") return '<span class="status-badge status-badge--normal">Payée</span>';
    if (isOverdue(bill)) return '<span class="status-badge status-badge--outage">En retard</span>';
    return '<span class="status-badge status-badge--watch">À payer</span>';
  }

  const period = (bill) => `${S.fmt.month(bill.periodStart)} – ${S.fmt.month(bill.periodEnd)} ${new Date(bill.periodEnd).getFullYear()}`;
  const deltaHtml = (value) => (value == null ? "—" : `<span class="${value > 0 ? "svc-delta-up" : "svc-delta-down"}">${S.fmt.percent(value)}</span>`);

  /* ---------- Vue générale ---------- */

  function monthEstimate(i) {
    const e = D.monthlyElectricity[i];
    const w = D.monthlyWater[i];
    return (e * T.electricite.price + (T.electricite.fixed + T.electricite.extra) / 2) * (1 + T.electricite.taxRate) +
      (w * T.eau.price + (T.eau.fixed + T.eau.extra) / 3) * (1 + T.eau.taxRate);
  }

  function statCard(iconName, label, value, unitText, delta, deltaText) {
    const cls = delta == null ? "" : delta > 1 ? " stat-card__delta--down" : delta < -1 ? " stat-card__delta--up" : " stat-card__delta--watch";
    const trend = delta == null ? "" : delta > 1 ? "trending-up" : delta < -1 ? "trending-down" : "activity";
    return `<article class="stat-card">
      <p class="stat-card__label"><span data-icon="${iconName}"></span> ${label}</p>
      <p class="stat-card__value">${value}${unitText ? ` <span>${unitText}</span>` : ""}</p>
      <p class="stat-card__delta${cls}">${trend ? `<span data-icon="${trend}"></span>` : ""} ${deltaText}</p>
    </article>`;
  }

  function renderOverview() {
    const last = D.monthlyElectricity.length - 1;
    const e = D.monthlyElectricity;
    const w = D.monthlyWater;
    const dE = pct(e[last], e[last - 1]);
    const dW = pct(w[last], w[last - 1]);
    const spend = monthEstimate(last);
    const dS = pct(spend, monthEstimate(last - 1));
    const due = state.bills.filter((b) => b.status === "a-payer");
    const dueTotal = due.reduce((sum, b) => sum + b.amount, 0);
    const next = due.slice().sort((a, b) => new Date(a.dueDate) - new Date(b.dueDate))[0];
    $("[data-overview-stats]").innerHTML =
      statCard("zap", `Électricité · ${S.fmt.monthLong(D.months[last])}`, S.fmt.number(e[last]), "kWh", dE, `${S.fmt.percent(dE)} vs mois précédent`) +
      statCard("droplet", `Eau · ${S.fmt.monthLong(D.months[last])}`, S.fmt.number(w[last]), "m³", dW, `${S.fmt.percent(dW)} vs mois précédent`) +
      statCard("file-text", "Dépense estimée du mois", S.fmt.number(spend, 2), "TND", dS, `${S.fmt.percent(dS)} vs mois précédent`) +
      statCard("clock", "Factures à payer", String(due.length), due.length ? `· ${S.fmt.money(dueTotal)}` : "", null, next ? `Prochaine échéance : ${S.fmt.date(next.dueDate)}` : "Aucune facture en attente");
    S.hydrate($("[data-overview-stats]"));
    renderTrend();
    renderSpend();
    renderRecent();
  }

  function el(tag, attrs, text) {
    const node = document.createElementNS(NS, tag);
    Object.keys(attrs || {}).forEach((key) => node.setAttribute(key, attrs[key]));
    if (text != null) node.textContent = text;
    return node;
  }

  function niceMax(value) {
    const pow = Math.pow(10, Math.floor(Math.log10(value)));
    return Math.ceil(value / pow) * pow;
  }

  function renderTrend() {
    const type = state.trend;
    const series = type === "electricite" ? D.monthlyElectricity : D.monthlyWater;
    const W = 640, H = 250, L = 42, R = 14, Tp = 14, B = 30;
    const max = niceMax(Math.max(...series) * 1.1);
    const x = (i) => L + (i * (W - L - R)) / (series.length - 1);
    const y = (v) => Tp + (1 - v / max) * (H - Tp - B);
    const avg = series.reduce((a, b) => a + b, 0) / series.length;
    const svg = el("svg", { class: "chart-svg", viewBox: `0 0 ${W} ${H}`, role: "img", "aria-label": `Consommation mensuelle ${type === "eau" ? "d'eau" : "d'électricité"} sur 12 mois` });
    const defs = el("defs");
    const grad = el("linearGradient", { id: "svcArea", x1: "0", y1: "0", x2: "0", y2: "1" });
    const color = type === "eau" ? "var(--color-water-blue)" : "var(--color-emerald)";
    grad.appendChild(el("stop", { offset: "0%", style: `stop-color:${color};stop-opacity:.22` }));
    grad.appendChild(el("stop", { offset: "100%", style: `stop-color:${color};stop-opacity:0` }));
    defs.appendChild(grad);
    svg.appendChild(defs);
    for (let i = 0; i <= 4; i += 1) {
      const v = (max / 4) * i;
      svg.appendChild(el("line", { class: "chart-grid-line", x1: L, x2: W - R, y1: y(v), y2: y(v) }));
      svg.appendChild(el("text", { class: "chart-axis-label", x: L - 8, y: y(v) + 3, "text-anchor": "end" }, S.fmt.number(v)));
    }
    const pts = series.map((v, i) => [x(i), y(v)]);
    const line = pts.map((pt, i) => `${i ? "L" : "M"}${pt[0].toFixed(1)} ${pt[1].toFixed(1)}`).join(" ");
    svg.appendChild(el("path", { d: `${line} L${x(series.length - 1)} ${y(0)} L${x(0)} ${y(0)} Z`, fill: "url(#svcArea)" }));
    svg.appendChild(el("line", { x1: L, x2: W - R, y1: y(avg), y2: y(avg), stroke: "var(--color-border-strong)", "stroke-dasharray": "5 5", "stroke-width": "1.2" }));
    svg.appendChild(el("path", { class: `chart-line chart-line--${type === "eau" ? "water" : "energy"}`, d: line }));
    series.forEach((v, i) => {
      const point = el("circle", { class: `chart-point chart-point--${type === "eau" ? "water" : "energy"}`, cx: pts[i][0], cy: pts[i][1], r: i === series.length - 1 ? 5 : 3.6, tabindex: "0" });
      const prev = series[i - 1];
      point.appendChild(el("title", null, `${S.fmt.monthLong(D.months[i])} : ${S.fmt.number(v)} ${unit(type)}${prev ? ` (${S.fmt.percent(pct(v, prev))} vs mois précédent)` : ""}`));
      svg.appendChild(point);
      svg.appendChild(el("text", { class: "chart-axis-label", x: pts[i][0], y: H - 10, "text-anchor": "middle" }, S.fmt.month(D.months[i])));
    });
    const lastV = series[series.length - 1];
    svg.appendChild(el("text", { x: pts[pts.length - 1][0] - 6, y: pts[pts.length - 1][1] - 11, "text-anchor": "end", fill: "var(--color-heading)", "font-size": "11", "font-weight": "700" }, `${S.fmt.number(lastV)} ${unit(type)}`));
    const holder = $("[data-trend-chart]");
    holder.innerHTML = "";
    holder.appendChild(svg);
    $("[data-trend-title]").textContent = type === "eau" ? "Consommation d'eau" : "Consommation d'électricité";
    $("[data-trend-legend]").innerHTML = `
      <span class="chart-legend__item"><span class="chart-legend__swatch${type === "eau" ? " chart-legend__swatch--water" : ""}"></span>Consommation mensuelle (${unit(type)})</span>
      <span class="chart-legend__item"><span class="chart-legend__swatch chart-legend__swatch--muted"></span>Moyenne sur 12 mois : ${S.fmt.number(avg, 1)} ${unit(type)}</span>`;
  }

  function renderSpend() {
    const recent = sorted().slice(0, 6).reverse();
    const holder = $("[data-spend-chart]");
    if (!recent.length) {
      holder.innerHTML = S.emptyState({ icon: "file-text", title: "Aucune facture", text: "Ajoutez une facture pour voir vos dépenses." });
      return;
    }
    const W = 360, H = 250, L = 10, B = 40, Tp = 22;
    const max = niceMax(Math.max(...recent.map((b) => b.amount)) * 1.15);
    const slot = (W - L * 2) / recent.length;
    const bw = Math.min(34, slot * 0.56);
    const svg = el("svg", { class: "chart-svg", viewBox: `0 0 ${W} ${H}`, role: "img", "aria-label": "Montant des six dernières factures" });
    svg.appendChild(el("line", { class: "chart-axis-line", x1: L, x2: W - L, y1: H - B, y2: H - B }));
    recent.forEach((bill, i) => {
      const h = (bill.amount / max) * (H - B - Tp);
      const cx = L + slot * i + slot / 2;
      const rect = el("rect", { class: `chart-bar chart-bar--${bill.type === "eau" ? "water" : "energy"}`, x: cx - bw / 2, y: H - B - h, width: bw, height: h, rx: 2 });
      rect.appendChild(el("title", null, `${bill.provider} · ${period(bill)} : ${S.fmt.money(bill.amount)}`));
      svg.appendChild(rect);
      svg.appendChild(el("text", { x: cx, y: H - B - h - 6, "text-anchor": "middle", fill: "var(--color-heading)", "font-size": "10", "font-weight": "700" }, S.fmt.number(bill.amount, 0)));
      svg.appendChild(el("text", { class: "chart-axis-label", x: cx, y: H - B + 14, "text-anchor": "middle" }, S.fmt.month(bill.periodEnd)));
      svg.appendChild(el("text", { class: "chart-axis-label", x: cx, y: H - B + 26, "text-anchor": "middle" }, bill.provider));
    });
    holder.innerHTML = "";
    holder.appendChild(svg);
  }

  const tableHead = '<thead><tr><th>Fournisseur</th><th>Période</th><th>Consommation</th><th>Montant TTC</th><th>Variation*</th><th>Statut</th><th><span class="sr-only">Actions</span></th></tr></thead>';

  function billRow(bill, compact) {
    const v = variation(bill);
    return `<tr data-id="${bill.id}">
      <td data-label="Fournisseur"><strong>${bill.provider}</strong><br><span class="text-muted" style="font-size:11px;">${typeLabel[bill.type]} · ${S.esc(bill.reference)}</span></td>
      <td data-label="Période">${period(bill)}</td>
      <td data-label="Consommation">${S.fmt.number(bill.quantity)} ${unit(bill.type)}</td>
      <td data-label="Montant"><strong>${S.fmt.money(bill.amount)}</strong></td>
      <td data-label="Variation">${v ? deltaHtml(v.quantity) : '<span class="text-muted">—</span>'}</td>
      <td data-label="Statut">${statusBadge(bill)}${compact ? "" : ` <span class="svc-tag" style="margin-top:4px;">${sourceLabel[bill.source] || "Démo"}</span>`}</td>
      <td class="svc-table__actions"><div class="svc-actions" style="justify-content:flex-end;">
        <button class="svc-icon-btn" type="button" data-bill-action="view"><span data-icon="eye"></span>Voir</button>
        <button class="svc-icon-btn" type="button" data-bill-action="analyze"><span data-icon="sparkles"></span>Analyser</button>
        ${compact ? "" : bill.status === "a-payer" ? '<button class="svc-icon-btn" type="button" data-bill-action="pay"><span data-icon="check"></span>Payée</button>' : ""}
        ${compact || bill.source === "demo" ? "" : '<button class="svc-icon-btn svc-icon-btn--danger" type="button" data-bill-action="delete" aria-label="Supprimer cette facture"><span data-icon="trash"></span></button>'}
      </div></td>
    </tr>`;
  }

  function renderRecent() {
    const table = $("[data-recent-table]");
    const rows = sorted().slice(0, 5);
    table.innerHTML = rows.length ? `${tableHead}<tbody>${rows.map((b) => billRow(b, true)).join("")}</tbody>` : `<tbody><tr><td>${S.emptyState({ icon: "file-text", title: "Aucune facture enregistrée" })}</td></tr></tbody>`;
    S.hydrate(table);
  }

  /* ---------- Mes factures ---------- */

  function renderBills() {
    const rows = sorted().filter((b) => !state.filter || b.type === state.filter);
    $("[data-bill-count]").textContent = state.bills.length;
    $("[data-bill-caption]").textContent = `${rows.length} facture${rows.length > 1 ? "s" : ""} · * variation de la consommation moyenne par jour vs facture précédente`;
    const table = $("[data-bill-table]");
    table.innerHTML = rows.length
      ? `${tableHead}<tbody>${rows.map((b) => billRow(b, false)).join("")}</tbody>`
      : `<tbody><tr><td colspan="7">${S.emptyState({ icon: "file-text", title: "Aucune facture", text: "Importez un PDF ou une photo de facture, ou saisissez-la manuellement.", action: '<button class="btn btn--primary btn--sm" type="button" data-svc-open="bill-modal">Ajouter une facture</button>' })}</td></tr></tbody>`;
    S.hydrate(table);
    fillAnalysisSelect();
  }

  async function saveBills(message) {
    try {
      await billsRepo.save(state.bills);
      if (message) S.toast(message);
    } catch (error) {
      S.toast("Impossible d'enregistrer : stockage du navigateur indisponible.");
    }
    renderAllData();
  }

  function renderAllData() {
    renderOverview();
    renderBills();
    renderAnalysis();
  }

  function billById(id) {
    return state.bills.find((b) => b.id === id);
  }

  function openBillDetail(id) {
    const bill = billById(id);
    if (!bill) return;
    const br = breakdown(bill);
    $("#bill-detail-title").textContent = `Facture ${bill.provider} · ${period(bill)}`;
    $("[data-bill-detail-sub]").textContent = `${typeLabel[bill.type]} · référence ${bill.reference}`;
    $("[data-bill-detail-body]").innerHTML = `
      <div class="svc-block">
        <p class="svc-source"><span data-icon="file-text"></span>Informations de la facture${bill.source === "import" ? " (extraites puis vérifiées par vous)" : bill.source === "manual" ? " (saisies par vous)" : " (données de démonstration)"}</p>
        <dl class="svc-dl">
          <div><dt>Période</dt><dd>${S.fmt.date(bill.periodStart)} → ${S.fmt.date(bill.periodEnd)} (${days(bill)} jours)</dd></div>
          <div><dt>Consommation</dt><dd>${S.fmt.number(bill.quantity)} ${unit(bill.type)}</dd></div>
          <div><dt>Montant TTC</dt><dd>${S.fmt.money(bill.amount)}</dd></div>
          <div><dt>Date limite</dt><dd>${bill.dueDate ? S.fmt.date(bill.dueDate) : "—"}</dd></div>
          <div><dt>Statut</dt><dd>${statusBadge(bill)}</dd></div>
          <div><dt>Document</dt><dd>${bill.fileName ? S.esc(bill.fileName) : "—"}</dd></div>
        </dl>
      </div>
      <div class="svc-block">
        <p class="svc-source svc-source--ai"><span data-icon="sparkles"></span>Décomposition estimée (tarifs indicatifs)</p>
        <table class="data-table">
          <tbody>
            <tr><td>Consommation : ${S.fmt.number(bill.quantity)} ${unit(bill.type)} × ${S.fmt.number(T[bill.type].price, 3)} TND</td><td style="text-align:end">${S.fmt.money(br.energy)}</td></tr>
            <tr><td>Redevance fixe (abonnement, compteur)</td><td style="text-align:end">${S.fmt.money(br.fixed)}</td></tr>
            <tr><td>${S.esc(br.extraLabel)}</td><td style="text-align:end">${S.fmt.money(br.extra)}</td></tr>
            <tr><td>TVA (${Math.round(T[bill.type].taxRate * 100)} %)</td><td style="text-align:end">${S.fmt.money(br.tax)}</td></tr>
            <tr><td><strong>Total estimé</strong></td><td style="text-align:end"><strong>${S.fmt.money(br.total)}</strong></td></tr>
          </tbody>
        </table>
        ${Math.abs(br.total - bill.amount) > 1 ? `<p class="field__hint" style="margin-top:8px;">L'écart avec le montant facturé (${S.fmt.money(bill.amount)}) s'explique par les tranches tarifaires réelles, non reproduites dans ce prototype.</p>` : ""}
      </div>`;
    $("[data-bill-detail-foot]").innerHTML = `
      ${bill.source !== "demo" ? '<button class="btn btn--outline btn--sm" type="button" data-bill-detail="delete">Supprimer</button>' : ""}
      ${bill.status === "a-payer" ? '<button class="btn btn--outline btn--sm" type="button" data-bill-detail="pay">Marquer comme payée</button>' : ""}
      <button class="btn btn--secondary btn--sm" type="button" data-bill-detail="analyze"><span data-icon="sparkles"></span>Analyser cette facture</button>`;
    $("#bill-detail").dataset.id = id;
    S.openModal($("#bill-detail"));
  }

  async function billAction(action, id) {
    const bill = billById(id);
    if (!bill) return;
    if (action === "view") return openBillDetail(id);
    if (action === "analyze") {
      state.analysisId = id;
      state.tabs.show("analysis");
      renderAnalysis();
      $("[data-analysis-bill]").focus();
      return;
    }
    if (action === "pay") {
      bill.status = "payee";
      return saveBills("Facture marquée comme payée (suivi personnel, aucun paiement effectué).");
    }
    if (action === "delete") {
      const ok = await S.confirm({ title: "Supprimer cette facture ?", text: `${bill.provider} · ${period(bill)} · ${S.fmt.money(bill.amount)}`, confirmLabel: "Supprimer", danger: true });
      if (!ok) return;
      state.bills = state.bills.filter((b) => b.id !== id);
      if (state.analysisId === id) state.analysisId = "";
      return saveBills("Facture supprimée.");
    }
  }

  document.addEventListener("click", (event) => {
    const button = event.target.closest("[data-bill-action]");
    if (button) billAction(button.dataset.billAction, button.closest("[data-id]").dataset.id);
    const detail = event.target.closest("[data-bill-detail]");
    if (detail) {
      const id = $("#bill-detail").dataset.id;
      S.closeModal($("#bill-detail"));
      billAction(detail.dataset.billDetail, id);
    }
    const goto = event.target.closest("[data-goto-tab]");
    if (goto) {
      const target = goto.dataset.gotoTab;
      if (target === "import") {
        state.tabs.show("bills");
        $("#import").scrollIntoView({ behavior: "smooth", block: "center" });
        setTimeout(() => $("[data-dropzone]").focus({ preventScroll: true }), 350);
      } else {
        state.tabs.show(target);
        if (target === "assistant") $("#chat-input").focus();
      }
    }
  });

  $("[data-bill-filter]").addEventListener("change", (event) => {
    state.filter = event.target.value;
    renderBills();
  });

  $("[data-trend-switch]").addEventListener("click", (event) => {
    const button = event.target.closest("[data-trend]");
    if (!button) return;
    state.trend = button.dataset.trend;
    $("[data-trend-switch]").querySelectorAll("button").forEach((b) => b.setAttribute("aria-pressed", String(b === button)));
    renderTrend();
  });

  /* ---------- Saisie / import ---------- */

  const billForm = $("[data-bill-form]");
  const toDateInput = (iso) => new Date(iso).toISOString().slice(0, 10);

  function resetBillForm() {
    billForm.reset();
    S.clearErrors(billForm);
    billForm.source.value = "manual";
    delete billForm.dataset.fileName;
    $("[data-extract-note]").hidden = true;
    $("#bill-modal-title").textContent = "Ajouter une facture";
    $("[data-bill-modal-sub]").textContent = "Saisie manuelle des informations principales.";
  }

  billForm.type.addEventListener("change", () => {
    billForm.querySelector("[data-unit]").textContent = billForm.type.value ? `(${unit(billForm.type.value)})` : "(kWh ou m³)";
  });

  $("#bill-modal").addEventListener("svc:close", () => setTimeout(resetBillForm, 0));

  billForm.addEventListener("submit", async (event) => {
    event.preventDefault();
    const ok = S.validate(billForm, (v) => {
      const errors = {};
      if (v.periodStart && v.periodEnd) {
        const start = new Date(v.periodStart);
        const end = new Date(v.periodEnd);
        if (end <= start) errors.periodEnd = "La fin de période doit être après le début.";
        else if ((end - start) / 86400000 > 125) errors.periodEnd = "Une facture couvre au maximum 4 mois.";
        if (start > new Date()) errors.periodStart = "La période ne peut pas commencer dans le futur.";
        if (v.dueDate && new Date(v.dueDate) < end) errors.dueDate = "L'échéance doit suivre la fin de période.";
      }
      if (v.reference && state.bills.some((b) => b.reference.toLowerCase() === v.reference.toLowerCase())) errors.reference = "Une facture avec cette référence existe déjà.";
      if (v.type && v.quantity && v.periodStart && v.periodEnd) {
        const dayCount = Math.max(1, (new Date(v.periodEnd) - new Date(v.periodStart)) / 86400000);
        const daily = Number(v.quantity) / dayCount;
        if ((v.type === "electricite" && daily > 120) || (v.type === "eau" && daily > 6)) errors.quantity = "Cette consommation paraît très élevée pour un foyer : vérifiez la valeur.";
      }
      return errors;
    });
    if (!ok) return;
    const v = S.values(billForm);
    const submit = billForm.querySelector('[type="submit"]');
    S.setBusy(submit, true);
    state.bills.push({
      id: S.uid("bill"),
      provider: v.type === "electricite" ? "STEG" : "SONEDE",
      type: v.type,
      reference: v.reference,
      periodStart: new Date(v.periodStart + "T12:00:00").toISOString(),
      periodEnd: new Date(v.periodEnd + "T12:00:00").toISOString(),
      quantity: Number(v.quantity),
      amount: Math.round(Number(v.amount) * 1000) / 1000,
      dueDate: v.dueDate ? new Date(v.dueDate + "T12:00:00").toISOString() : "",
      status: v.status || "a-payer",
      source: v.source,
      fileName: billForm.dataset.fileName || ""
    });
    await saveBills(v.source === "import" ? "Facture importée et enregistrée dans ce navigateur." : "Facture enregistrée dans ce navigateur.");
    S.setBusy(submit, false);
    S.closeModal($("#bill-modal"));
    state.tabs.show("bills");
  });

  const importForm = $("[data-import-form]");
  const fileInput = $("#bill-file");
  const dropzone = $("[data-dropzone]");

  function showFileName() {
    const file = fileInput.files[0];
    $("[data-file-name]").textContent = file ? `Fichier choisi : ${file.name} (${S.fmt.number(file.size / 1024, 0)} Ko)` : "";
  }

  fileInput.addEventListener("change", showFileName);
  dropzone.addEventListener("keydown", (event) => {
    if (event.key === "Enter" || event.key === " ") {
      event.preventDefault();
      fileInput.click();
    }
  });
  ["dragenter", "dragover"].forEach((type) =>
    dropzone.addEventListener(type, (event) => {
      event.preventDefault();
      dropzone.classList.add("is-dragover");
    })
  );
  ["dragleave", "drop"].forEach((type) =>
    dropzone.addEventListener(type, (event) => {
      event.preventDefault();
      dropzone.classList.remove("is-dragover");
    })
  );
  dropzone.addEventListener("drop", (event) => {
    if (event.dataTransfer && event.dataTransfer.files.length) {
      fileInput.files = event.dataTransfer.files;
      showFileName();
    }
  });

  importForm.addEventListener("submit", async (event) => {
    event.preventDefault();
    if (!S.validate(importForm)) return;
    const v = S.values(importForm);
    const submit = importForm.querySelector('[type="submit"]');
    S.setBusy(submit, true, "Lecture du document…");
    await new Promise((resolve) => setTimeout(resolve, 1400));
    S.setBusy(submit, false);

    // Lecture SIMULÉE : on propose des valeurs plausibles à partir de la facture précédente.
    const last = sorted().find((b) => b.type === v.type);
    const length = v.type === "electricite" ? 2 : 3;
    const start = last ? new Date(last.periodEnd) : new Date();
    start.setDate(start.getDate() + 1);
    let end = new Date(start);
    end.setMonth(end.getMonth() + length);
    end.setDate(end.getDate() - 1);
    // Une facture ne couvre jamais le mois en cours : on s'arrête à la fin du mois dernier.
    const lastMonthEnd = new Date();
    lastMonthEnd.setDate(0);
    if (end > lastMonthEnd) end = lastMonthEnd;
    if (start >= end) start.setTime(new Date(end.getFullYear(), end.getMonth() - length + 1, 1, 12).getTime());
    const quantity = Math.round((last ? last.quantity : v.type === "electricite" ? 500 : 45) * (0.92 + Math.random() * 0.22));
    resetBillForm();
    billForm.type.value = v.type;
    billForm.type.dispatchEvent(new Event("change"));
    billForm.reference.value = (v.type === "electricite" ? "STG-" : "SND-") + String(Math.floor(482000 + Math.random() * 9000));
    billForm.periodStart.value = toDateInput(start);
    billForm.periodEnd.value = toDateInput(end);
    billForm.quantity.value = quantity;
    billForm.amount.value = D.billAmount(v.type, quantity).toFixed(3);
    const due = new Date(end);
    due.setDate(due.getDate() + 20);
    billForm.dueDate.value = toDateInput(due);
    billForm.source.value = "import";
    billForm.dataset.fileName = v.file.name;
    $("[data-extract-note]").hidden = false;
    $("#bill-modal-title").textContent = "Vérifier les informations extraites";
    $("[data-bill-modal-sub]").textContent = `Document : ${v.file.name}`;
    importForm.reset();
    showFileName();
    S.openModal($("#bill-modal"));
  });

  /* ---------- Analyse automatique ---------- */

  function fillAnalysisSelect() {
    const select = $("[data-analysis-bill]");
    const bills = sorted();
    if (!state.analysisId || !billById(state.analysisId)) {
      const firstElec = bills.find((b) => b.type === "electricite");
      state.analysisId = (firstElec || bills[0] || {}).id || "";
    }
    select.innerHTML = bills.map((b) => `<option value="${b.id}"${b.id === state.analysisId ? " selected" : ""}>${b.provider} · ${period(b)} · ${S.fmt.money(b.amount)}</option>`).join("");
  }

  $("[data-analysis-bill]").addEventListener("change", (event) => {
    state.analysisId = event.target.value;
    renderAnalysis();
  });

  function causesFor(bill, v) {
    const months = billMonths(bill);
    const summer = months.some((m) => m >= 5 && m <= 8);
    const winter = months.some((m) => m === 11 || m <= 1);
    const up = v && v.quantity > 0;
    const causes = [];
    if (bill.type === "electricite") {
      if (summer) causes.push(["Probable", "Climatisation : un climatiseur de 1,2 kW utilisé 6 h par jour consomme environ 216 kWh par mois."]);
      if (winter) causes.push(["Probable", "Chauffage d'appoint ou chauffe-eau électrique plus sollicité en hiver (environ 4 kWh par jour pour un chauffe-eau)."]);
      if (up) causes.push(["Possible", "Nouvel appareil ou appareil vieillissant (réfrigérateur dont le joint fuit, congélateur givré)."]);
      if (up) causes.push(["Possible", "Plus de personnes présentes au foyer ou télétravail pendant la période."]);
    } else {
      if (summer) causes.push(["Probable", "Usage estival : douches plus fréquentes, arrosage, remplissage de citernes."]);
      if (up) causes.push(["Possible", "Fuite invisible : une chasse d'eau qui coule peut perdre jusqu'à 25 m³ par mois."]);
      if (up) causes.push(["Possible", "Remplissage d'une réserve après une coupure d'eau."]);
    }
    if (!causes.length) causes.push(["Information", "Aucun facteur saisonnier marqué sur cette période."]);
    return causes;
  }

  function tipsFor(bill) {
    const saving = (bill.amount * 0.1).toFixed(3).replace(".", ",");
    return bill.type === "electricite"
      ? [`Régler la climatisation sur 25–26 °C plutôt que 20 °C : jusqu'à 20 % d'économie sur la clim.`, "Programmer le chauffe-eau la nuit et le régler sur 55 °C.", "Débrancher les appareils en veille (box TV, chargeurs, consoles).", `Objectif −10 % : environ ${saving} TND économisés sur une facture comparable.`]
      : [`Installer des mousseurs sur les robinets : jusqu'à 50 % de débit en moins.`, "Vérifier l'absence de fuite : relevez le compteur le soir puis le matin sans utiliser d'eau.", "Privilégier les douches courtes et remplir le lave-linge.", `Objectif −10 % : environ ${saving} TND économisés sur une facture comparable.`];
  }

  function renderAnalysis() {
    const holder = $("[data-analysis]");
    const bill = billById(state.analysisId);
    if (!bill) {
      holder.innerHTML = `<article class="card">${S.emptyState({ icon: "sparkles", title: "Aucune facture à analyser", text: "Ajoutez une facture pour obtenir une analyse." })}</article>`;
      return;
    }
    const v = variation(bill);
    const br = breakdown(bill);
    const u = unit(bill.type);
    const dec = bill.type === "eau" ? 2 : 1;
    const energyShare = Math.round((br.energy / br.total) * 100);
    const unusual = v && Math.abs(v.quantity) >= 15;
    const label = bill.type === "electricite" ? "électrique" : "d'eau";
    const headline = v
      ? `Votre consommation ${label} a ${v.quantity >= 0 ? "augmenté" : "baissé"} de <strong>${S.fmt.number(Math.abs(v.quantity), 0)} %</strong> par rapport à la facture précédente (${S.fmt.number(perDay(bill), dec)} ${u}/jour contre ${S.fmt.number(perDay(v.prev), dec)} ${u}/jour). ${v.quantity >= 0 ? "Voici les variations observées et quelques recommandations pour réduire votre consommation." : "Continuez ainsi : voici quelques pistes pour aller plus loin."}`
      : "C'est la première facture de ce type : l'analyse comparative sera disponible dès la prochaine facture.";

    const maxQ = Math.max(bill.quantity, v ? v.prev.quantity : 0);
    const bar = (label2, b, cls) => `
      <div style="display:grid;gap:5px;">
        <div class="svc-places"><span style="min-width:140px;color:var(--color-heading);font-weight:700;">${label2}</span><span>${S.fmt.number(b.quantity)} ${u} · ${S.fmt.money(b.amount)}</span></div>
        <div class="svc-progress${cls}"><span style="width:${(b.quantity / maxQ) * 100}%"></span></div>
      </div>`;

    holder.innerHTML = `
      <div class="svc-split">
        <article class="card">
          <div class="card__body">
            <p class="svc-source"><span data-icon="file-text"></span>Données extraites de la facture</p>
            <dl class="svc-dl">
              <div><dt>Fournisseur</dt><dd>${bill.provider} (${typeLabel[bill.type]})</dd></div>
              <div><dt>Référence</dt><dd>${S.esc(bill.reference)}</dd></div>
              <div><dt>Période</dt><dd>${S.fmt.date(bill.periodStart)} → ${S.fmt.date(bill.periodEnd)}</dd></div>
              <div><dt>Durée</dt><dd>${days(bill)} jours</dd></div>
              <div><dt>Consommation</dt><dd>${S.fmt.number(bill.quantity)} ${u}</dd></div>
              <div><dt>Montant TTC</dt><dd>${S.fmt.money(bill.amount)}</dd></div>
            </dl>
            <div class="svc-block">
              <h3>Comparaison avec la facture précédente</h3>
              ${v ? `<div style="display:grid;gap:12px;">${bar("Cette facture", bill, bill.type === "eau" ? "" : "")}${bar(`Précédente (${period(v.prev)})`, v.prev, " svc-progress--watch")}</div>
              <p class="field__hint" style="margin-top:10px;">Montant : ${deltaHtml(v.amount)} · consommation moyenne par jour : ${deltaHtml(v.quantity)}</p>` : '<p>Pas de facture précédente du même fournisseur.</p>'}
            </div>
            <div class="svc-block">
              <h3>Comprendre votre facture</h3>
              <ul class="svc-bullets">
                <li><span data-icon="check"></span>La consommation représente environ ${energyShare} % du montant (${S.fmt.money(br.energy)}).</li>
                <li><span data-icon="check"></span>La partie fixe (abonnement, compteur, redevances) est due même sans consommation.</li>
                <li><span data-icon="check"></span>La TVA s'applique sur l'ensemble (${Math.round(T[bill.type].taxRate * 100)} % dans ce prototype).</li>
              </ul>
            </div>
          </div>
        </article>

        <article class="card svc-ai-card">
          <div class="card__body">
            <p class="svc-source svc-source--ai"><span data-icon="sparkles"></span>Interprétation automatique — hypothèses (analyse simulée)</p>
            <p class="svc-highlight">${headline}</p>
            <div class="svc-block">
              <h3>Variations inhabituelles</h3>
              ${v ? unusual
                ? `<p class="svc-notice svc-notice--warning" style="margin:0;"><span data-icon="triangle-alert"></span><span>Écart de ${S.fmt.percent(v.quantity)} sur la consommation journalière : au-delà du seuil de ±15 % utilisé pour signaler une variation inhabituelle.</span></p>`
                : `<p class="svc-notice" style="margin:0;"><span data-icon="circle-check"></span><span>Variation de ${S.fmt.percent(v.quantity)} : dans la plage habituelle (±15 %).</span></p>`
                : "<p>Comparaison impossible sans facture précédente.</p>"}
            </div>
            <div class="svc-block">
              <h3>Causes possibles</h3>
              <ul class="svc-bullets">${causesFor(bill, v).map(([level, text]) => `<li><span data-icon="info"></span><span><span class="svc-tag${level === "Probable" ? " svc-tag--blue" : ""}" style="margin-inline-end:6px;">${level}</span>${S.esc(text)}</span></li>`).join("")}</ul>
            </div>
            <div class="svc-block">
              <h3>Conseils personnalisés pour économiser</h3>
              <ul class="svc-bullets">${tipsFor(bill).map((tip) => `<li><span data-icon="leaf"></span>${S.esc(tip)}</li>`).join("")}</ul>
            </div>
            <p class="field__hint" style="margin-top:16px;">Ces explications sont produites par des règles simples à partir de vos données ; elles peuvent être inexactes.</p>
          </div>
        </article>
      </div>`;
    S.hydrate(holder);
  }

  /* ---------- Assistant ---------- */

  const log = $("[data-chat-log]");
  const suggestions = [
    "Pourquoi ma facture a-t-elle augmenté ?",
    "Comment réduire ma consommation ?",
    "Quels appareils peuvent expliquer cette augmentation ?",
    "Comment interpréter cette facture ?",
    "Ai-je une fuite d'eau ?",
    "Quelles factures dois-je payer ?"
  ];

  function latest(type) {
    return sorted().find((b) => !type || b.type === type) || null;
  }

  function answer(question) {
    const q = question.toLowerCase();
    const elec = latest("electricite");
    const water = latest("eau");
    const focus = /eau|fuite|sonede|m3|m³/.test(q) ? water : elec || water;
    if (!focus) return "<p>Je n'ai encore aucune facture à analyser. Ajoutez une facture dans l'onglet « Mes factures » et je pourrai vous répondre.</p>";
    const v = variation(focus);
    const u = unit(focus.type);

    if (/augment|hausse|plus cher|élev|pourquoi/.test(q)) {
      if (!v) return `<p>Votre dernière facture ${focus.provider} (${period(focus)}) s'élève à <strong>${S.fmt.money(focus.amount)}</strong>. Je n'ai pas de facture précédente pour comparer.</p>`;
      const causes = causesFor(focus, v).map(([, t]) => `<li>${S.esc(t)}</li>`).join("");
      return `<p>Sur votre dernière facture ${focus.provider} (${period(focus)}), la consommation moyenne est de <strong>${S.fmt.number(perDay(focus), focus.type === "eau" ? 2 : 1)} ${u}/jour</strong> contre ${S.fmt.number(perDay(v.prev), focus.type === "eau" ? 2 : 1)} ${u}/jour la fois précédente, soit <strong>${S.fmt.percent(v.quantity)}</strong>. Le montant varie de ${S.fmt.percent(v.amount)}.</p><p>Hypothèses les plus plausibles :</p><ul>${causes}</ul>`;
    }
    if (/rédui|redui|économ|econom|baisser|diminu/.test(q)) {
      const tips = tipsFor(focus).map((t) => `<li>${S.esc(t)}</li>`).join("");
      return `<p>Voici des actions adaptées à votre facture ${focus.provider} :</p><ul>${tips}</ul>`;
    }
    if (/appareil|climatis|clim|frigo|réfrig|chauffe|machine|télé/.test(q)) {
      const extra = v && v.quantity > 0 ? Math.round((perDay(focus) - perDay(v.prev)) * 30) : 0;
      return `<p>Consommations mensuelles typiques (estimations) :</p><ul><li>Climatiseur 1,2 kW, 6 h/jour : ~216 kWh</li><li>Chauffe-eau électrique 2 kW, 2 h/jour : ~120 kWh</li><li>Réfrigérateur-congélateur : 30 à 45 kWh</li><li>Lave-linge (4 cycles/semaine) : ~20 kWh</li><li>Télévision et box (4 h/jour) : ~15 kWh</li></ul>${extra > 0 ? `<p>Votre hausse correspond à environ <strong>${extra} kWh de plus par mois</strong> : ${extra > 150 ? "un climatiseur ou un chauffe-eau utilisé plus souvent est l'explication la plus probable." : "cela peut venir d'un appareil de taille moyenne ou d'une présence accrue au foyer."}</p>` : ""}`;
    }
    if (/interpr|lire|comprend|ligne|tva|redevance|détail|detail/.test(q)) {
      const br = breakdown(focus);
      return `<p>Votre facture ${focus.provider} de ${period(focus)} se décompose ainsi (estimation) :</p><ul><li>Consommation : ${S.fmt.number(focus.quantity)} ${u} → ${S.fmt.money(br.energy)}</li><li>Redevance fixe : ${S.fmt.money(br.fixed)}</li><li>${S.esc(br.extraLabel)} : ${S.fmt.money(br.extra)}</li><li>TVA : ${S.fmt.money(br.tax)}</li></ul><p>Le montant facturé est de <strong>${S.fmt.money(focus.amount)}</strong>, à régler avant le ${focus.dueDate ? S.fmt.date(focus.dueDate) : "—"}.</p>`;
    }
    if (/fuite/.test(q)) {
      if (!water) return "<p>Je n'ai pas de facture d'eau enregistrée.</p>";
      const wv = variation(water);
      return `<p>Votre dernière facture d'eau indique ${S.fmt.number(perDay(water), 2)} m³/jour${wv ? ` (${S.fmt.percent(wv.quantity)} vs précédente)` : ""}. ${wv && wv.quantity > 20 ? "C'est une hausse marquée : une fuite est possible." : "Rien n'indique clairement une fuite."}</p><p>Pour vérifier : notez l'index du compteur le soir, n'utilisez pas d'eau pendant la nuit et relevez-le le matin. S'il a bougé, il y a une fuite.</p>`;
    }
    if (/payer|échéance|echeance|retard|impayé/.test(q)) {
      const due = state.bills.filter((b) => b.status === "a-payer");
      if (!due.length) return "<p>Toutes vos factures enregistrées sont marquées comme payées.</p>";
      return `<p>Vous avez ${due.length} facture${due.length > 1 ? "s" : ""} à payer :</p><ul>${due.map((b) => `<li>${b.provider} · ${period(b)} : <strong>${S.fmt.money(b.amount)}</strong> avant le ${b.dueDate ? S.fmt.date(b.dueDate) : "—"}${isOverdue(b) ? " (en retard)" : ""}</li>`).join("")}</ul><p>Le paiement se fait auprès de STEG ou SONEDE : WASLA ne gère pas les paiements.</p>`;
    }
    if (/bonjour|salut|aide|merci/.test(q)) return "<p>Bonjour ! Je peux expliquer une facture, comparer deux périodes, identifier les appareils qui consomment le plus ou proposer des économies. Choisissez une question ci-dessous ou écrivez la vôtre.</p>";
    return "<p>Je n'ai pas bien compris. Je sais répondre aux questions sur la hausse d'une facture, les économies possibles, la consommation des appareils, la lecture d'une facture, les fuites d'eau et les échéances.</p>";
  }

  function messageHtml(msg) {
    return `<div class="svc-msg svc-msg--${msg.role}">
      ${msg.role === "bot" ? '<span class="svc-icon svc-icon--sm svc-icon--navy" data-icon="bot"></span>' : ""}
      <div class="svc-msg__bubble">${msg.role === "user" ? `<p>${S.esc(msg.text)}</p>` : msg.html}<span class="svc-msg__time">${S.fmt.time(msg.date)}${msg.role === "bot" ? " · réponse simulée" : ""}</span></div>
    </div>`;
  }

  function renderChat() {
    const intro = { role: "bot", date: new Date().toISOString(), html: "<p>Bonjour, je suis l'assistant de démonstration de WASLA. Posez-moi une question sur vos factures : je réponds à partir des factures enregistrées dans ce navigateur.</p>" };
    log.innerHTML = [intro].concat(state.chat).map(messageHtml).join("");
    S.hydrate(log);
    log.scrollTop = log.scrollHeight;
  }

  function ask(question) {
    const text = question.trim();
    if (!text) return;
    state.chat.push({ role: "user", text, date: new Date().toISOString() });
    renderChat();
    const typing = document.createElement("div");
    typing.className = "svc-msg svc-msg--bot";
    typing.innerHTML = `<span class="svc-icon svc-icon--sm svc-icon--navy">${S.icon("bot")}</span><div class="svc-msg__bubble"><span class="svc-typing" aria-label="L'assistant rédige une réponse"><span></span><span></span><span></span></span></div>`;
    log.appendChild(typing);
    log.scrollTop = log.scrollHeight;
    setTimeout(() => {
      state.chat.push({ role: "bot", html: answer(text), date: new Date().toISOString() });
      state.chat = state.chat.slice(-40);
      S.store.set("chat-history", state.chat);
      renderChat();
    }, 750);
  }

  $("[data-chat-suggestions]").innerHTML = suggestions.map((s) => `<button class="chip" type="button" data-ask>${S.esc(s)}</button>`).join("");
  $("[data-chat-suggestions]").addEventListener("click", (event) => {
    const chip = event.target.closest("[data-ask]");
    if (chip) ask(chip.textContent);
  });
  $("[data-chat-form]").addEventListener("submit", (event) => {
    event.preventDefault();
    const input = $("#chat-input");
    if (!input.value.trim()) {
      input.focus();
      S.toast("Écrivez une question ou choisissez une suggestion.");
      return;
    }
    ask(input.value);
    input.value = "";
  });
  $("[data-chat-clear]").addEventListener("click", async () => {
    if (!state.chat.length) return S.toast("L'historique est déjà vide.");
    const ok = await S.confirm({ title: "Effacer la conversation ?", text: "L'historique des échanges enregistré dans ce navigateur sera supprimé.", confirmLabel: "Effacer", danger: true });
    if (!ok) return;
    state.chat = [];
    S.store.remove("chat-history");
    renderChat();
  });

  /* ---------- Démarrage ---------- */

  document.addEventListener("DOMContentLoaded", async () => {
    $("[data-overview-stats]").innerHTML = S.skeleton(4);
    $("[data-trend-chart]").innerHTML = S.skeleton(8);
    state.tabs = S.initTabs("bills", (tab) => {
      if (tab === "assistant") setTimeout(() => (log.scrollTop = log.scrollHeight), 0);
    });
    renderChat();
    state.bills = await billsRepo.all();
    renderAllData();
  });
})();
