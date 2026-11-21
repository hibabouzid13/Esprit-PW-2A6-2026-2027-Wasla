/* ============================================================
   WASLA — Service 02 · Factures & IA (Module 2, Hiba)
   ------------------------------------------------------------
   - Les factures viennent de la BASE DE DONNÉES : la vue les
     place dans l'attribut data-bills (JSON) de <main>. Aucune
     facture de démonstration, rien n'est stocké dans le navigateur.
   - Vue générale (statistiques et graphiques), liste, analyse et
     assistant sont calculés à partir de ces factures.
   - Ajout : la fenêtre « Ajouter une facture » est vérifiée par
     validation.js + factures-crud.js (mêmes règles que le CRUD),
     puis envoyée en POST (jeton CSRF) à facture-ajouter-json,
     qui refait tous les contrôles en PHP.
   - Suppression : formulaire POST + jeton CSRF vers facture-supprimer.
   - L'« IA » (analyse, assistant) est simulée par des règles
     explicites ; la conversation n'est pas enregistrée.
   ============================================================ */
(function () {
  "use strict";

  const S = window.WASLA_SVC;
  const root = document.querySelector('[data-service="factures"]');
  if (!S || !root) return;

  const NS = "http://www.w3.org/2000/svg";
  const $ = (sel) => document.querySelector(sel);
  const DAY = 86400000;

  /* Tarifs de RÉFÉRENCE indicatifs (simplifiés, pas une grille officielle) :
     ils servent uniquement à décomposer le montant d'une facture réelle. */
  const TARIFS = {
    electricite: { unit: "kWh", price: 0.196, fixed: 4.6, taxRate: 0.13, extraLabel: "Contribution audiovisuelle et municipale", extra: 1.5 },
    eau: { unit: "m³", price: 0.82, fixed: 3.9, taxRate: 0.07, extraLabel: "Redevance assainissement (ONAS)", extra: 2.4 }
  };

  const typeLabel = { electricite: "Électricité", eau: "Eau" };
  const unit = (type) => TARIFS[type].unit;

  /* « 2026-07-01 » → date locale à midi (évite les décalages de fuseau). */
  const toDate = (iso) => {
    const [y, m, d] = String(iso).split("-").map(Number);
    return new Date(y, m - 1, d, 12);
  };

  function readBills() {
    let raw = [];
    try {
      raw = JSON.parse(root.dataset.bills || "[]");
    } catch (error) {
      raw = [];
    }
    return (Array.isArray(raw) ? raw : [])
      .filter((b) => TARIFS[b.type])
      .map((b) => ({
        ...b,
        id: String(b.id),
        quantity: Number(b.quantity),
        amount: Number(b.amount),
        start: toDate(b.periodStart),
        end: toDate(b.periodEnd),
        due: b.dueDate ? toDate(b.dueDate) : null
      }));
  }

  const state = { bills: readBills(), trend: "electricite", filter: "", analysisId: "", chat: [], tabs: null };

  /* ---------- Calculs ---------- */

  const byDateDesc = (a, b) => b.end - a.end;
  const sorted = () => state.bills.slice().sort(byDateDesc);
  const billById = (id) => state.bills.find((b) => b.id === String(id));

  function previousOf(bill) {
    return state.bills
      .filter((b) => b.type === bill.type && b.id !== bill.id && b.end < bill.end)
      .sort(byDateDesc)[0] || null;
  }

  /* Durée en jours : même calcul que la page « Voir » (PHP DateTime::diff). */
  const days = (bill) => Math.max(1, Math.round((bill.end - bill.start) / DAY));
  const perDay = (bill) => bill.quantity / days(bill);
  const pct = (a, b) => (b ? ((a - b) / b) * 100 : 0);

  function variation(bill) {
    const prev = previousOf(bill);
    if (!prev) return null;
    // Comparaison à durée égale : consommation moyenne par jour.
    return { prev, quantity: pct(perDay(bill), perDay(prev)), amount: pct(bill.amount, prev.amount) };
  }

  function breakdown(bill) {
    const t = TARIFS[bill.type];
    const energy = bill.quantity * t.price;
    const subtotal = energy + t.fixed + t.extra;
    const tax = subtotal * t.taxRate;
    return { energy, fixed: t.fixed, extra: t.extra, extraLabel: t.extraLabel, tax, total: subtotal + tax };
  }

  function billMonths(bill) {
    const out = [];
    const d = new Date(bill.start.getFullYear(), bill.start.getMonth(), 1, 12);
    while (d <= bill.end && out.length < 12) {
      out.push(d.getMonth());
      d.setMonth(d.getMonth() + 1);
    }
    return out;
  }

  const todayStart = () => {
    const d = new Date();
    return new Date(d.getFullYear(), d.getMonth(), d.getDate());
  };
  const isOverdue = (bill) => bill.status === "a_payer" && bill.due && bill.due < todayStart();

  function statusBadge(bill) {
    if (bill.status === "payee") return '<span class="status-badge status-badge--normal">Payée</span>';
    if (isOverdue(bill)) return '<span class="status-badge status-badge--outage">En retard</span>';
    return '<span class="status-badge status-badge--watch">À payer</span>';
  }

  /* Références lisibles : abonnement par 3 chiffres, talon par 4 (comme en PHP). */
  const group = (value, size) => String(value || "").replace(new RegExp(`(\\d{${size}})(?=\\d)`, "g"), "$1 ");
  const period = (bill) => `${S.fmt.date(bill.start)} → ${S.fmt.date(bill.end)}`;
  const periodShort = (bill) => `${S.fmt.month(bill.start)} – ${S.fmt.month(bill.end)} ${bill.end.getFullYear()}`;
  const deltaHtml = (value) => (value == null ? "—" : `<span class="${value > 0 ? "svc-delta-up" : "svc-delta-down"}">${S.fmt.percent(value)}</span>`);

  /* Largeurs des barres de progression : posées en JavaScript (pas d'attribut style). */
  function applyWidths(holder) {
    holder.querySelectorAll("[data-width]").forEach((el) => {
      el.style.width = `${Math.max(0, Math.min(100, Number(el.dataset.width)))}%`;
    });
  }

  /* ---------- Vue générale ---------- */

  function statCard(iconName, label, value, unitText, delta, deltaText) {
    const cls = delta == null ? "" : delta > 1 ? " stat-card__delta--down" : delta < -1 ? " stat-card__delta--up" : " stat-card__delta--watch";
    const trend = delta == null ? "" : delta > 1 ? "trending-up" : delta < -1 ? "trending-down" : "activity";
    return `<article class="stat-card">
      <p class="stat-card__label"><span data-icon="${iconName}"></span> ${S.esc(label)}</p>
      <p class="stat-card__value">${S.esc(value)}${unitText ? `<span>&nbsp;${S.esc(unitText)}</span>` : ""}</p>
      <p class="stat-card__delta${cls}">${trend ? `<span data-icon="${trend}"></span>` : ""} ${S.esc(deltaText)}</p>
    </article>`;
  }

  function lastOf(type) {
    return sorted().find((b) => b.type === type) || null;
  }

  function consumptionCard(type) {
    const bill = lastOf(type);
    const name = type === "eau" ? "Eau · dernière facture" : "Électricité · dernière facture";
    const iconName = type === "eau" ? "droplet" : "zap";
    if (!bill) return statCard(iconName, name, "—", "", null, `Aucune facture ${type === "eau" ? "SONEDE" : "STEG"}`);
    const v = variation(bill);
    return statCard(iconName, name, S.fmt.number(bill.quantity, bill.quantity % 1 ? 2 : 0), unit(type), v ? v.quantity : null,
      v ? `${S.fmt.percent(v.quantity)} par jour vs facture précédente` : periodShort(bill));
  }

  function renderOverview() {
    const yearAgo = new Date(todayStart().getTime() - 365 * DAY);
    const recent = state.bills.filter((b) => b.end >= yearAgo);
    const spend = recent.reduce((sum, b) => sum + b.amount, 0);
    const due = state.bills.filter((b) => b.status === "a_payer");
    const dueTotal = due.reduce((sum, b) => sum + b.amount, 0);
    const next = due.filter((b) => b.due).sort((a, b) => a.due - b.due)[0];
    $("[data-overview-stats]").innerHTML =
      consumptionCard("electricite") +
      consumptionCard("eau") +
      statCard("file-text", "Dépenses sur 12 mois", S.fmt.number(spend, 3), "TND", null, `${recent.length} facture${recent.length > 1 ? "s" : ""} terminée${recent.length > 1 ? "s" : ""} depuis un an`) +
      statCard("clock", "Factures à payer", String(due.length), due.length ? `· ${S.fmt.money(dueTotal)}` : "", null,
        next ? `Prochaine échéance : ${S.fmt.date(next.due)}` : due.length ? "Date limite non renseignée" : "Aucune facture en attente");
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
    if (value <= 0) return 1;
    const pow = Math.pow(10, Math.floor(Math.log10(value)));
    return Math.ceil(value / pow) * pow;
  }

  /**
   * Consommation mensuelle calculée à partir des factures d'un fournisseur :
   * chaque facture est répartie sur les mois qu'elle couvre, au prorata des jours.
   * Fenêtre : les 12 mois qui se terminent par le mois de la dernière facture.
   * Un mois sans facture vaut null (trou dans la courbe, pas un zéro inventé).
   */
  function monthlySeries(type) {
    const bills = state.bills.filter((b) => b.type === type);
    if (!bills.length) return null;
    const last = bills.reduce((max, b) => (b.end > max ? b.end : max), bills[0].end);
    const months = [];
    for (let i = 11; i >= 0; i -= 1) months.push(new Date(last.getFullYear(), last.getMonth() - i, 1, 12));
    const values = months.map(() => null);
    bills.forEach((b) => {
      const total = days(b);
      months.forEach((m, i) => {
        const monthEnd = new Date(m.getFullYear(), m.getMonth() + 1, 1, 12);
        const from = Math.max(b.start, m);
        const to = Math.min(b.end, monthEnd);
        const overlap = Math.round((to - from) / DAY);
        if (overlap > 0) values[i] = (values[i] || 0) + (b.quantity * overlap) / total;
      });
    });
    return { months, values };
  }

  function renderTrend() {
    const type = state.trend;
    const holder = $("[data-trend-chart]");
    const legend = $("[data-trend-legend]");
    $("[data-trend-title]").textContent = type === "eau" ? "Consommation d'eau" : "Consommation d'électricité";
    const data = monthlySeries(type);
    if (!data) {
      holder.innerHTML = S.emptyState({
        icon: type === "eau" ? "droplet" : "zap",
        title: `Aucune facture ${type === "eau" ? "SONEDE" : "STEG"}`,
        text: "Ajoutez une facture de ce fournisseur pour voir l'évolution de votre consommation."
      });
      legend.innerHTML = "";
      return;
    }
    const { months, values } = data;
    const known = values.filter((v) => v != null);
    const W = 640, H = 250, L = 42, R = 14, Tp = 18, B = 30;
    const max = niceMax(Math.max(...known) * 1.1);
    const x = (i) => L + (i * (W - L - R)) / (months.length - 1);
    const y = (v) => Tp + (1 - v / max) * (H - Tp - B);
    const avg = known.reduce((a, b) => a + b, 0) / known.length;
    const kind = type === "eau" ? "water" : "energy";
    const svg = el("svg", { class: "chart-svg", viewBox: `0 0 ${W} ${H}`, role: "img", "aria-label": `Consommation mensuelle ${type === "eau" ? "d'eau" : "d'électricité"} calculée à partir de vos factures` });
    for (let i = 0; i <= 4; i += 1) {
      const v = (max / 4) * i;
      svg.appendChild(el("line", { class: "chart-grid-line", x1: L, x2: W - R, y1: y(v), y2: y(v) }));
      svg.appendChild(el("text", { class: "chart-axis-label", x: L - 8, y: y(v) + 3, "text-anchor": "end" }, S.fmt.number(v)));
    }
    svg.appendChild(el("line", { x1: L, x2: W - R, y1: y(avg), y2: y(avg), stroke: "var(--color-border-strong)", "stroke-dasharray": "5 5", "stroke-width": "1.2" }));
    // Ligne coupée sur les mois sans facture
    let d = "";
    values.forEach((v, i) => {
      if (v == null) return;
      d += `${i === 0 || values[i - 1] == null ? "M" : "L"}${x(i).toFixed(1)} ${y(v).toFixed(1)} `;
    });
    svg.appendChild(el("path", { class: `chart-line chart-line--${kind}`, d: d.trim() }));
    const lastIndex = values.reduce((acc, v, i) => (v != null ? i : acc), -1);
    values.forEach((v, i) => {
      svg.appendChild(el("text", { class: "chart-axis-label", x: x(i), y: H - 10, "text-anchor": "middle" }, S.fmt.month(months[i])));
      if (v == null) return;
      const point = el("circle", { class: `chart-point chart-point--${kind}`, cx: x(i), cy: y(v), r: i === lastIndex ? 5 : 3.6, tabindex: "0" });
      const prev = values[i - 1];
      point.appendChild(el("title", null, `${S.fmt.monthLong(months[i])} : ${S.fmt.number(v)} ${unit(type)}${prev ? ` (${S.fmt.percent(pct(v, prev))} vs mois précédent)` : ""}`));
      svg.appendChild(point);
    });
    holder.innerHTML = "";
    holder.appendChild(svg);
    legend.innerHTML = `
      <span class="chart-legend__item"><span class="chart-legend__swatch${type === "eau" ? " chart-legend__swatch--water" : ""}"></span>Consommation mensuelle (${unit(type)})</span>
      <span class="chart-legend__item"><span class="chart-legend__swatch chart-legend__swatch--muted"></span>Moyenne des mois facturés : ${S.fmt.number(avg, 1)} ${unit(type)}</span>`;
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
    const svg = el("svg", { class: "chart-svg", viewBox: `0 0 ${W} ${H}`, role: "img", "aria-label": "Montant de vos dernières factures" });
    svg.appendChild(el("line", { class: "chart-axis-line", x1: L, x2: W - L, y1: H - B, y2: H - B }));
    recent.forEach((bill, i) => {
      const h = (bill.amount / max) * (H - B - Tp);
      const cx = L + slot * i + slot / 2;
      const rect = el("rect", { class: `chart-bar chart-bar--${bill.type === "eau" ? "water" : "energy"}`, x: cx - bw / 2, y: H - B - h, width: bw, height: h, rx: 2 });
      rect.appendChild(el("title", null, `${bill.provider} · ${period(bill)} : ${S.fmt.money(bill.amount)}`));
      svg.appendChild(rect);
      svg.appendChild(el("text", { x: cx, y: H - B - h - 6, "text-anchor": "middle", fill: "var(--color-heading)", "font-size": "10", "font-weight": "700" }, S.fmt.number(bill.amount, 0)));
      svg.appendChild(el("text", { class: "chart-axis-label", x: cx, y: H - B + 14, "text-anchor": "middle" }, S.fmt.month(bill.end)));
      svg.appendChild(el("text", { class: "chart-axis-label", x: cx, y: H - B + 26, "text-anchor": "middle" }, bill.provider));
    });
    holder.innerHTML = "";
    holder.appendChild(svg);
  }

  const tableHead = '<thead><tr><th scope="col">Fournisseur</th><th scope="col">Période</th><th scope="col">Consommation</th><th scope="col">Montant</th><th scope="col">Variation*</th><th scope="col">Statut</th><th scope="col"><span class="sr-only">Actions</span></th></tr></thead>';

  function billRow(bill, compact) {
    const v = variation(bill);
    const label = `la facture ${bill.provider} du ${S.fmt.date(bill.start)} au ${S.fmt.date(bill.end)}`;
    return `<tr data-id="${S.esc(bill.id)}">
      <td data-label="Fournisseur"><strong>${S.esc(bill.provider)}</strong><span class="bill-row-sub" data-no-i18n>${typeLabel[bill.type]} · abonnement ${S.esc(group(bill.refAbo, 3))}</span></td>
      <td data-label="Période">${period(bill)}</td>
      <td data-label="Consommation">${S.fmt.number(bill.quantity, bill.quantity % 1 ? 2 : 0)} ${unit(bill.type)}</td>
      <td data-label="Montant"><strong>${S.fmt.money(bill.amount)}</strong></td>
      <td data-label="Variation">${v ? deltaHtml(v.quantity) : '<span class="text-muted">—</span>'}</td>
      <td data-label="Statut">${statusBadge(bill)}</td>
      <td class="svc-table__actions"><div class="svc-actions bill-row-actions">
        <button class="svc-icon-btn" type="button" data-bill-action="view" aria-label="Voir ${S.esc(label)}"><span data-icon="eye"></span>Voir</button>
        <button class="svc-icon-btn" type="button" data-bill-action="analyze" aria-label="Analyser ${S.esc(label)}"><span data-icon="sparkles"></span>Analyser</button>
        ${compact ? "" : `<button class="svc-icon-btn svc-icon-btn--danger" type="button" data-bill-action="delete" aria-label="Supprimer ${S.esc(label)}" title="Supprimer"><span data-icon="trash"></span></button>`}
      </div></td>
    </tr>`;
  }

  function renderRecent() {
    const table = $("[data-recent-table]");
    const rows = sorted().slice(0, 5);
    table.innerHTML = `${tableHead}<tbody>${rows.map((b) => billRow(b, true)).join("")}</tbody>`;
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
      : `<tbody><tr><td colspan="7">${S.emptyState({ icon: "file-text", title: "Aucune facture pour ce fournisseur", text: "Choisissez « Tous les fournisseurs » ou ajoutez une facture." })}</td></tr></tbody>`;
    S.hydrate(table);
    fillAnalysisSelect();
  }

  function openBillDetail(id) {
    const bill = billById(id);
    if (!bill) return;
    const br = breakdown(bill);
    $("#bill-detail-title").textContent = `Facture ${bill.provider} · ${periodShort(bill)}`;
    $("[data-bill-detail-sub]").textContent = `${typeLabel[bill.type]} · abonnement ${group(bill.refAbo, 3)}`;
    $("[data-bill-detail-body]").innerHTML = `
      <div class="svc-block">
        <p class="svc-source"><span data-icon="file-text"></span>Informations enregistrées dans votre compte</p>
        <dl class="svc-dl">
          <div><dt>Référence d'abonnement</dt><dd data-no-i18n>${S.esc(group(bill.refAbo, 3))}</dd></div>
          <div><dt>Référence de paiement</dt><dd data-no-i18n>${bill.refPaie ? S.esc(group(bill.refPaie, 4)) : "Non renseignée"}</dd></div>
          <div><dt>Période</dt><dd>${period(bill)} (${days(bill)} jours)</dd></div>
          <div><dt>Consommation</dt><dd>${S.fmt.number(bill.quantity, bill.quantity % 1 ? 2 : 0)} ${unit(bill.type)}</dd></div>
          <div><dt>Montant</dt><dd>${S.fmt.money(bill.amount)}</dd></div>
          <div><dt>Date limite</dt><dd>${bill.due ? S.fmt.date(bill.due) : "Non renseignée"}</dd></div>
          <div><dt>Statut</dt><dd>${statusBadge(bill)}</dd></div>
        </dl>
      </div>
      <div class="svc-block">
        <p class="svc-source svc-source--ai"><span data-icon="sparkles"></span>Décomposition estimée (tarifs de référence indicatifs)</p>
        <table class="data-table">
          <tbody>
            <tr><td>Consommation : ${S.fmt.number(bill.quantity, bill.quantity % 1 ? 2 : 0)} ${unit(bill.type)} × ${S.fmt.number(TARIFS[bill.type].price, 3)} TND</td><td class="bill-num">${S.fmt.money(br.energy)}</td></tr>
            <tr><td>Redevance fixe (abonnement, compteur)</td><td class="bill-num">${S.fmt.money(br.fixed)}</td></tr>
            <tr><td>${S.esc(br.extraLabel)}</td><td class="bill-num">${S.fmt.money(br.extra)}</td></tr>
            <tr><td>TVA (${Math.round(TARIFS[bill.type].taxRate * 100)} %)</td><td class="bill-num">${S.fmt.money(br.tax)}</td></tr>
            <tr><td><strong>Total estimé</strong></td><td class="bill-num"><strong>${S.fmt.money(br.total)}</strong></td></tr>
          </tbody>
        </table>
        ${Math.abs(br.total - bill.amount) > 1 ? `<p class="field__hint bill-hint">L'écart avec le montant facturé (${S.fmt.money(bill.amount)}) vient des tranches tarifaires réelles, non reproduites ici.</p>` : ""}
      </div>`;
    $("[data-bill-detail-foot]").innerHTML = `
      <button class="btn btn--outline btn--sm bill-btn-danger" type="button" data-bill-detail="delete"><span data-icon="trash"></span>Supprimer</button>
      <a class="btn btn--outline btn--sm" href="${S.esc(bill.urlModifier)}"><span data-icon="pencil"></span>Modifier</a>
      <button class="btn btn--secondary btn--sm" type="button" data-bill-detail="analyze"><span data-icon="sparkles"></span>Analyser cette facture</button>`;
    $("#bill-detail").dataset.id = id;
    S.openModal($("#bill-detail"));
  }

  async function billAction(action, id) {
    const bill = billById(id);
    if (!bill) return;
    if (action === "view") return openBillDetail(id);
    if (action === "analyze") {
      state.analysisId = bill.id;
      state.tabs.show("analysis");
      fillAnalysisSelect();
      renderAnalysis();
      $("[data-analysis-bill]").focus();
      return;
    }
    if (action === "delete") {
      const ok = await S.confirm({
        title: "Supprimer cette facture ?",
        text: `Facture ${bill.provider} du ${S.fmt.date(bill.start)} au ${S.fmt.date(bill.end)} · ${S.fmt.money(bill.amount)}. Cette action est définitive.`,
        confirmLabel: "Supprimer",
        danger: true
      });
      if (!ok) return;
      // Suppression en base : formulaire POST avec jeton CSRF (revérifié par index.php et le contrôleur)
      const form = $("[data-bill-delete-form]");
      form.querySelector('[name="id_facture"]').value = bill.id;
      form.submit();
    }
  }

  document.addEventListener("click", (event) => {
    const button = event.target.closest("[data-bill-action]");
    if (button) {
      billAction(button.dataset.billAction, button.closest("[data-id]").dataset.id);
      return;
    }
    const detail = event.target.closest("[data-bill-detail]");
    if (detail) {
      const id = $("#bill-detail").dataset.id;
      S.closeModal($("#bill-detail"));
      billAction(detail.dataset.billDetail, id);
      return;
    }
    const goto = event.target.closest("[data-goto-tab]");
    if (goto) {
      state.tabs.show(goto.dataset.gotoTab);
      if (goto.dataset.gotoTab === "assistant") $("#chat-input").focus();
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

  /* ---------- Ajouter une facture (enregistrement en base) ---------- */

  const billForm = $("[data-bill-crud-form]");

  function resetBillForm() {
    billForm.reset();
    S.clearErrors(billForm);
    billForm.querySelectorAll(".has-error").forEach((node) => node.classList.remove("has-error"));
    // Remet l'unité, les exemples et les aides « sans fournisseur » (factures-crud.js écoute « change »)
    billForm.querySelector('[name="type_ressource"]').dispatchEvent(new Event("change"));
    S.clearErrors(billForm);
  }

  $("#bill-modal").addEventListener("svc:close", () => setTimeout(resetBillForm, 0));

  function showServerErrors(errors) {
    const api = window.WASLA_validate;
    let first = null;
    Object.keys(errors).forEach((name) => {
      const field = billForm.querySelector(`[name="${name}"]:not([type="hidden"])`);
      if (field && api) {
        api.show(billForm, field, errors[name]);
        if (!first) first = field;
      }
    });
    if (errors.form) S.toast(errors.form);
    if (first) first.focus();
  }

  // « load » : ce gestionnaire s'exécute APRÈS ceux de validation.js et de factures-crud.js,
  // qui bloquent l'envoi (event.defaultPrevented) si un champ est invalide.
  window.addEventListener("load", () => {
    billForm.addEventListener("submit", async (event) => {
      if (event.defaultPrevented) return;
      event.preventDefault();
      const submit = billForm.querySelector('[type="submit"]');
      S.setBusy(submit, true, "Enregistrement…");
      try {
        const response = await fetch(billForm.dataset.jsonAction, {
          method: "POST",
          body: new FormData(billForm),
          credentials: "same-origin",
          headers: { Accept: "application/json" }
        });
        const isJson = (response.headers.get("Content-Type") || "").includes("application/json");
        if (!isJson) throw new Error("session");
        const data = await response.json();
        if (data.ok) {
          window.location.assign(data.redirect);
          return;
        }
        showServerErrors(data.errors || {});
        S.toast("Veuillez corriger les champs indiqués.");
      } catch (error) {
        S.toast("Enregistrement impossible : votre session a peut-être expiré. Rechargez la page puis réessayez.");
      }
      S.setBusy(submit, false);
    });
  });

  /* ---------- Analyse automatique ---------- */

  function fillAnalysisSelect() {
    const select = $("[data-analysis-bill]");
    const bills = sorted();
    if (!state.analysisId || !billById(state.analysisId)) state.analysisId = (bills[0] || {}).id || "";
    select.innerHTML = bills
      .map((b) => `<option value="${S.esc(b.id)}"${b.id === state.analysisId ? " selected" : ""}>${S.esc(`${b.provider} · ${periodShort(b)} · ${S.fmt.money(b.amount)}`)}</option>`)
      .join("");
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
    const saving = S.fmt.number(bill.amount * 0.1, 3);
    return bill.type === "electricite"
      ? ["Régler la climatisation sur 25–26 °C plutôt que 20 °C : jusqu'à 20 % d'économie sur la clim.", "Programmer le chauffe-eau la nuit et le régler sur 55 °C.", "Débrancher les appareils en veille (box TV, chargeurs, consoles).", `Objectif −10 % : environ ${saving} TND économisés sur une facture comparable.`]
      : ["Installer des mousseurs sur les robinets : jusqu'à 50 % de débit en moins.", "Vérifier l'absence de fuite : relevez le compteur le soir puis le matin sans utiliser d'eau.", "Privilégier les douches courtes et remplir le lave-linge.", `Objectif −10 % : environ ${saving} TND économisés sur une facture comparable.`];
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
      : "C'est votre première facture de ce fournisseur : l'analyse comparative sera disponible dès la prochaine facture.";

    const maxQ = Math.max(bill.quantity, v ? v.prev.quantity : 0) || 1;
    const bar = (text, b, cls) => `
      <div class="bill-compare__row">
        <div class="svc-places"><span class="bill-compare__label">${S.esc(text)}</span><span>${S.fmt.number(b.quantity, b.quantity % 1 ? 2 : 0)} ${u} · ${S.fmt.money(b.amount)}</span></div>
        <div class="svc-progress${cls}"><span data-width="${(b.quantity / maxQ) * 100}"></span></div>
      </div>`;

    holder.innerHTML = `
      <div class="svc-split">
        <article class="card">
          <div class="card__body">
            <p class="svc-source"><span data-icon="file-text"></span>Données de votre facture</p>
            <dl class="svc-dl">
              <div><dt>Fournisseur</dt><dd>${S.esc(bill.provider)} (${typeLabel[bill.type]})</dd></div>
              <div><dt>Abonnement</dt><dd data-no-i18n>${S.esc(group(bill.refAbo, 3))}</dd></div>
              <div><dt>Période</dt><dd>${period(bill)}</dd></div>
              <div><dt>Durée</dt><dd>${days(bill)} jours</dd></div>
              <div><dt>Consommation</dt><dd>${S.fmt.number(bill.quantity, bill.quantity % 1 ? 2 : 0)} ${u}</dd></div>
              <div><dt>Montant</dt><dd>${S.fmt.money(bill.amount)}</dd></div>
            </dl>
            <div class="svc-block">
              <h3>Comparaison avec la facture précédente</h3>
              ${v ? `<div class="bill-compare">${bar("Cette facture", bill, "")}${bar(`Précédente (${periodShort(v.prev)})`, v.prev, " svc-progress--watch")}</div>
              <p class="field__hint bill-hint">Montant : ${deltaHtml(v.amount)} · consommation moyenne par jour : ${deltaHtml(v.quantity)}</p>` : "<p>Pas de facture précédente du même fournisseur.</p>"}
            </div>
            <div class="svc-block">
              <h3>Comprendre votre facture</h3>
              <ul class="svc-bullets">
                <li><span data-icon="check"></span>Avec les tarifs de référence, la consommation représente environ ${energyShare} % du montant (${S.fmt.money(br.energy)}).</li>
                <li><span data-icon="check"></span>La partie fixe (abonnement, compteur, redevances) est due même sans consommation.</li>
                <li><span data-icon="check"></span>La TVA s'applique sur l'ensemble (${Math.round(TARIFS[bill.type].taxRate * 100)} % dans les tarifs de référence).</li>
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
                ? `<p class="svc-notice svc-notice--warning bill-flat"><span data-icon="triangle-alert"></span><span>Écart de ${S.fmt.percent(v.quantity)} sur la consommation journalière : au-delà du seuil de ±15 % utilisé pour signaler une variation inhabituelle.</span></p>`
                : `<p class="svc-notice bill-flat"><span data-icon="circle-check"></span><span>Variation de ${S.fmt.percent(v.quantity)} : dans la plage habituelle (±15 %).</span></p>`
                : "<p>Comparaison impossible sans facture précédente.</p>"}
            </div>
            <div class="svc-block">
              <h3>Causes possibles</h3>
              <ul class="svc-bullets">${causesFor(bill, v).map(([level, text]) => `<li><span data-icon="info"></span><span><span class="svc-tag bill-tag-gap${level === "Probable" ? " svc-tag--blue" : ""}">${level}</span>${S.esc(text)}</span></li>`).join("")}</ul>
            </div>
            <div class="svc-block">
              <h3>Conseils pour économiser</h3>
              <ul class="svc-bullets">${tipsFor(bill).map((tip) => `<li><span data-icon="leaf"></span>${S.esc(tip)}</li>`).join("")}</ul>
            </div>
            <p class="field__hint bill-aside-hint">Ces explications sont produites par des règles simples à partir de vos factures ; elles peuvent être inexactes.</p>
          </div>
        </article>
      </div>`;
    applyWidths(holder);
    S.hydrate(holder);
  }

  /* ---------- Assistant (simulé, conversation non enregistrée) ---------- */

  const log = $("[data-chat-log]");
  const suggestions = [
    "Pourquoi ma facture a-t-elle augmenté ?",
    "Comment réduire ma consommation ?",
    "Quels appareils peuvent expliquer cette augmentation ?",
    "Comment interpréter cette facture ?",
    "Ai-je une fuite d'eau ?",
    "Quelles factures dois-je payer ?"
  ];

  function answer(question) {
    const q = question.toLowerCase();
    const elec = lastOf("electricite");
    const water = lastOf("eau");
    const focus = /eau|fuite|sonede|m3|m³/.test(q) ? water || elec : elec || water;
    if (!focus) return "<p>Vous n'avez encore aucune facture enregistrée. Ajoutez une facture avec le bouton « Ajouter une facture » et je pourrai vous répondre.</p>";
    const v = variation(focus);
    const u = unit(focus.type);
    const dec = focus.type === "eau" ? 2 : 1;

    if (/augment|hausse|plus cher|élev|pourquoi/.test(q)) {
      if (!v) return `<p>Votre dernière facture ${focus.provider} (${period(focus)}) s'élève à <strong>${S.fmt.money(focus.amount)}</strong>. Je n'ai pas de facture précédente du même fournisseur pour comparer.</p>`;
      const causes = causesFor(focus, v).map(([, t]) => `<li>${S.esc(t)}</li>`).join("");
      return `<p>Sur votre dernière facture ${focus.provider} (${period(focus)}), la consommation moyenne est de <strong>${S.fmt.number(perDay(focus), dec)} ${u}/jour</strong> contre ${S.fmt.number(perDay(v.prev), dec)} ${u}/jour la fois précédente, soit <strong>${S.fmt.percent(v.quantity)}</strong>. Le montant varie de ${S.fmt.percent(v.amount)}.</p><p>Hypothèses les plus plausibles :</p><ul>${causes}</ul>`;
    }
    if (/rédui|redui|économ|econom|baisser|diminu/.test(q)) {
      return `<p>Voici des actions adaptées à votre facture ${focus.provider} :</p><ul>${tipsFor(focus).map((t) => `<li>${S.esc(t)}</li>`).join("")}</ul>`;
    }
    if (/appareil|climatis|clim|frigo|réfrig|chauffe|machine|télé/.test(q)) {
      const extra = v && v.quantity > 0 && focus.type === "electricite" ? Math.round((perDay(focus) - perDay(v.prev)) * 30) : 0;
      return `<p>Consommations mensuelles typiques (estimations) :</p><ul><li>Climatiseur 1,2 kW, 6 h/jour : ~216 kWh</li><li>Chauffe-eau électrique 2 kW, 2 h/jour : ~120 kWh</li><li>Réfrigérateur-congélateur : 30 à 45 kWh</li><li>Lave-linge (4 cycles/semaine) : ~20 kWh</li><li>Télévision et box (4 h/jour) : ~15 kWh</li></ul>${extra > 0 ? `<p>Votre hausse correspond à environ <strong>${extra} kWh de plus par mois</strong> : ${extra > 150 ? "un climatiseur ou un chauffe-eau utilisé plus souvent est l'explication la plus probable." : "cela peut venir d'un appareil de taille moyenne ou d'une présence accrue au foyer."}</p>` : ""}`;
    }
    if (/interpr|lire|comprend|ligne|tva|redevance|détail|detail/.test(q)) {
      const br = breakdown(focus);
      return `<p>Votre facture ${focus.provider} du ${period(focus)} se décompose ainsi (estimation avec les tarifs de référence) :</p><ul><li>Consommation : ${S.fmt.number(focus.quantity, focus.quantity % 1 ? 2 : 0)} ${u} → ${S.fmt.money(br.energy)}</li><li>Redevance fixe : ${S.fmt.money(br.fixed)}</li><li>${S.esc(br.extraLabel)} : ${S.fmt.money(br.extra)}</li><li>TVA : ${S.fmt.money(br.tax)}</li></ul><p>Le montant facturé est de <strong>${S.fmt.money(focus.amount)}</strong>${focus.due ? `, à régler avant le ${S.fmt.date(focus.due)}` : ""}.</p>`;
    }
    if (/fuite/.test(q)) {
      if (!water) return "<p>Vous n'avez pas encore de facture d'eau enregistrée.</p>";
      const wv = variation(water);
      return `<p>Votre dernière facture d'eau indique ${S.fmt.number(perDay(water), 2)} m³/jour${wv ? ` (${S.fmt.percent(wv.quantity)} vs précédente)` : ""}. ${wv && wv.quantity > 20 ? "C'est une hausse marquée : une fuite est possible." : "Rien n'indique clairement une fuite."}</p><p>Pour vérifier : notez l'index du compteur le soir, n'utilisez pas d'eau pendant la nuit et relevez-le le matin. S'il a bougé, il y a une fuite.</p>`;
    }
    if (/payer|échéance|echeance|retard|impayé/.test(q)) {
      const due = sorted().filter((b) => b.status === "a_payer");
      if (!due.length) return "<p>Toutes vos factures enregistrées sont marquées comme payées.</p>";
      return `<p>Vous avez ${due.length} facture${due.length > 1 ? "s" : ""} à payer :</p><ul>${due.map((b) => `<li>${b.provider} · ${period(b)} : <strong>${S.fmt.money(b.amount)}</strong>${b.due ? ` avant le ${S.fmt.date(b.due)}` : ""}${isOverdue(b) ? " (en retard)" : ""}</li>`).join("")}</ul><p>Le paiement se fait auprès de la STEG ou de la SONEDE : WASLA ne gère pas les paiements.</p>`;
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
    const intro = { role: "bot", date: new Date().toISOString(), html: "<p>Bonjour, je suis l'assistant de démonstration de WASLA. Posez-moi une question : je réponds à partir des factures enregistrées dans votre compte.</p>" };
    log.innerHTML = [intro].concat(state.chat).map(messageHtml).join("");
    S.hydrate(log);
    log.scrollTop = log.scrollHeight;
  }

  function ask(question) {
    const text = question.trim().slice(0, 300);
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
      renderChat();
    }, 600);
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
    if (!state.chat.length) return S.toast("La conversation est déjà vide.");
    const ok = await S.confirm({ title: "Effacer la conversation ?", text: "Les messages échangés avec l'assistant seront effacés.", confirmLabel: "Effacer", danger: true });
    if (!ok) return;
    state.chat = [];
    renderChat();
  });

  /* ---------- Démarrage ---------- */

  document.addEventListener("DOMContentLoaded", () => {
    const empty = state.bills.length === 0;
    // Aucune facture : un seul état vide clair (avec « Ajouter une facture ») à la place des onglets
    $("[data-bills-empty]").hidden = !empty;
    document.querySelector(".analysis-toolbar").hidden = empty;
    state.tabs = S.initTabs("bills", (tab) => {
      if (tab === "assistant") setTimeout(() => (log.scrollTop = log.scrollHeight), 0);
    });
    if (empty) {
      document.querySelectorAll('[data-tabs-for="bills"]').forEach((panel) => (panel.hidden = true));
      return;
    }
    renderChat();
    renderOverview();
    renderBills();
    renderAnalysis();
    // Onglet demandé dans l'adresse (?onglet=bills après un ajout ou une suppression) : liste blanche
    const onglet = new URLSearchParams(window.location.search).get("onglet");
    if (["overview", "bills", "analysis", "assistant"].includes(onglet)) state.tabs.show(onglet);
  });
})();
