/* ============================================================
   WASLA — Service 02 · Factures & IA (Module 2, Hiba)
   ------------------------------------------------------------
   - Les factures viennent de la BASE DE DONNÉES : la vue les
     place dans l'attribut data-bills (JSON) de <main>. Aucune
     facture de démonstration, rien n'est stocké dans le navigateur.
   - En-tête (Wasel et sa bulle), indicateurs, graphiques, liste,
     constats, analyse et assistant sont calculés à partir de ces factures.
   - Ajout : la fenêtre « Ajouter une facture » (parcours guidé) est
     vérifiée par validation.js + factures-crud.js (mêmes règles que le
     CRUD), puis envoyée en POST (jeton CSRF) à facture-ajouter-json,
     qui refait tous les contrôles en PHP.
   - Suppression : formulaire POST + jeton CSRF vers facture-supprimer.
   - L'« IA » (analyse, Wasel) est simulée par des règles explicites ;
     la conversation n'est pas enregistrée.
   - Aucun attribut style : hauteurs et largeurs calculées posées en JavaScript.
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

  /* Nombres français : l'espace fine insécable (U+202F) d'Intl n'existe pas dans la police Inter du site
     (les milliers se collaient : « 1434,775 »). On affiche une espace insécable classique, comme en PHP. */
  const nbsp = (text) => String(text).replace(/\u202f/g, "\u00a0");
  const F = {
    number: (n, digits) => nbsp(S.fmt.number(n, digits)),
    money: (n) => nbsp(S.fmt.money(n)),
    percent: (n) => nbsp(S.fmt.percent(n))
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
  const prenom = root.dataset.prenom || "";

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

  /* ---------- Petits morceaux d'affichage ---------- */

  /* Pastille logo STEG / SONEDE (symboles du sprite partials/sprite.php). */
  function logo(type, size, decorative) {
    const key = type === "eau" ? "sonede" : "steg";
    const a11y = decorative ? 'aria-hidden="true"' : `role="img" aria-label="${type === "eau" ? "SONEDE" : "STEG"}"`;
    return `<span class="bill-logo bill-logo--${key} bill-logo--${size}"><svg ${a11y} focusable="false"><use href="#bill-logo-${key}"/></svg></span>`;
  }

  function statusChip(bill) {
    if (bill.status === "payee") return '<span class="bill-chip bill-chip--paid">Payée</span>';
    if (isOverdue(bill)) return '<span class="bill-chip bill-chip--late">En retard</span>';
    return '<span class="bill-chip bill-chip--due">À payer</span>';
  }

  /* Références lisibles : abonnement par 3 chiffres, talon par 4 (comme en PHP). */
  const group = (value, size) => String(value || "").replace(new RegExp(`(\\d{${size}})(?=\\d)`, "g"), "$1 ");
  const period = (bill) => `${S.fmt.date(bill.start)} → ${S.fmt.date(bill.end)}`;
  const periodShort = (bill) => `${S.fmt.month(bill.start)} → ${S.fmt.month(bill.end)} ${bill.end.getFullYear()}`;
  const qty = (bill) => `${F.number(bill.quantity, bill.quantity % 1 ? 2 : 0)} ${unit(bill.type)}`;
  const deltaHtml = (value) => (value == null ? '<span class="bill-muted">—</span>' : `<span class="${value > 0 ? "bill-delta-bad" : "bill-delta-good"}">${F.percent(value)}</span>`);
  const plural = (n, word) => `${n} ${word}${n > 1 ? "s" : ""}`;

  /* Hauteurs et largeurs calculées : posées en JavaScript (pas d'attribut style dans le HTML). */
  function applySizes(holder) {
    holder.querySelectorAll("[data-h]").forEach((el) => { el.style.height = `${Math.max(0, Math.min(100, Number(el.dataset.h)))}%`; });
    if (window.WASLA_factures) window.WASLA_factures.applyProgress(holder);
  }

  /* ---------- En-tête : chiffres, bulle de Wasel, cartes flottantes ---------- */

  function renderHero() {
    const bills = sorted();
    const due = bills.filter((b) => b.status === "a_payer");
    const late = due.filter(isOverdue);
    const next = due.filter((b) => b.due).sort((a, b) => a.due - b.due)[0];
    const name = prenom ? ` ${S.esc(prenom)}` : "";
    const bubble = $("[data-wasel-bubble]");
    const text = $("[data-wasel-text]");
    const cta = $("[data-wasel-cta]");

    if (!bills.length) {
      text.innerHTML = `Bonjour${name} 👋 Je suis <b>Wasel</b>. Ajoutez votre première facture ${logo("electricite", "xs")} ou ${logo("eau", "xs")} : je l'analyserai pour vous.`;
      cta.textContent = "Ajouter une facture";
      cta.dataset.mode = "add";
      return;
    }

    $("[data-hero-trust]").hidden = false;
    $('[data-trust="count"]').textContent = String(bills.length);
    $('[data-trust="due"]').textContent = F.money(due.reduce((s, b) => s + b.amount, 0));
    $('[data-trust="next"]').textContent = next ? S.fmt.date(next.due) : "Aucune";

    // Message de Wasel : la plus forte hausse récente, sinon les retards, sinon tout va bien
    const rises = ["electricite", "eau"].map(lastOf).filter(Boolean)
      .map((b) => ({ b, v: variation(b) })).filter((x) => x.v && x.v.quantity >= 15)
      .sort((x, y) => y.v.quantity - x.v.quantity);
    if (rises.length) {
      const { b, v } = rises[0];
      text.innerHTML = `Bonjour${name} 👋 J'ai lu vos <b>${plural(bills.length, "facture")}</b>. Votre dernière facture ${S.esc(b.provider)} (${periodShort(b)}) a bondi de <b class="bill-delta-bad">${F.percent(v.quantity)}</b> par jour : on regarde pourquoi ensemble ?`;
      cta.textContent = "Oui, explique-moi";
      cta.dataset.mode = b.type === "eau" ? "explain-eau" : "explain";
    } else if (late.length) {
      text.innerHTML = `Bonjour${name} 👋 J'ai lu vos <b>${plural(bills.length, "facture")}</b>. Attention : <b class="bill-delta-bad">${plural(late.length, "facture")} en retard</b> de paiement. Je vous fais le point ?`;
      cta.textContent = "Faire le point";
      cta.dataset.mode = "pay";
    } else {
      text.innerHTML = `Bonjour${name} 👋 J'ai lu vos <b>${plural(bills.length, "facture")}</b> : rien d'inhabituel. Une question sur votre consommation ?`;
      cta.textContent = "Poser une question";
      cta.dataset.mode = "ask";
    }
    bubble.hidden = false;

    // Carte « dernière facture »
    const last = bills[0];
    const fl = $("[data-float-last]");
    fl.innerHTML = `<p class="bill-float__head"><span>${logo(last.type, "sm", true)}${S.esc(last.provider)}</span>${statusChip(last)}</p>
      <p class="bill-float__value">${F.money(last.amount)}</p><p class="bill-float__sub">Dernière facture · ${periodShort(last)}</p>`;
    fl.hidden = false;

    // Carte « consommation » : électricité en priorité, avec les 5 dernières factures du fournisseur
    const c = lastOf("electricite") || lastOf("eau");
    const v = variation(c);
    const serie = state.bills.filter((b) => b.type === c.type).sort((a, b) => a.end - b.end).slice(-5);
    const max = Math.max(...serie.map(perDay)) || 1;
    const fc = $("[data-float-conso]");
    fc.innerHTML = `<p class="bill-float__head"><span>${logo(c.type, "sm", true)}${typeLabel[c.type]}</span>${v ? deltaHtml(v.quantity) : ""}</p>
      <p class="bill-float__value">${qty(c)}</p>
      <div class="bill-mini-bars" role="img" aria-label="Consommation par jour de vos ${serie.length} dernières factures ${S.esc(c.provider)}">${serie.map((b, i) => `<span data-h="${(perDay(b) / max) * 100}"${i === serie.length - 1 ? ' class="is-hi"' : ""}></span>`).join("")}</div>`;
    fc.hidden = false;
    $("[data-core-lines]").hidden = false;
    applySizes(fc);
  }

  /* ---------- Vue générale ---------- */

  function lastOf(type) {
    return sorted().find((b) => b.type === type) || null;
  }

  function kpi(cls, ico, label, value, small, foot) {
    return `<article class="bill-kpi bill-glass${cls}">
      <p class="bill-kpi__top">${ico}${S.esc(label)}</p>
      <p class="bill-kpi__value">${value}${small ? `<small>${S.esc(small)}</small>` : ""}</p>
      ${foot}
    </article>`;
  }

  function consumptionKpi(type) {
    const bill = lastOf(type);
    const label = type === "eau" ? "Eau · dernière facture" : "Électricité · dernière facture";
    const cls = type === "eau" ? " bill-kpi--sonede" : " bill-kpi--steg";
    if (!bill) return kpi(cls, logo(type, "md"), label, "—", "", `<p class="bill-kpi__foot">Aucune facture ${type === "eau" ? "SONEDE" : "STEG"}</p>`);
    const v = variation(bill);
    return kpi(cls, logo(type, "md"), label, F.number(bill.quantity, bill.quantity % 1 ? 2 : 0), unit(type),
      `<p class="bill-kpi__foot">${v ? `${deltaHtml(v.quantity)} par jour vs précédente` : periodShort(bill)}</p>`);
  }

  function renderOverview() {
    const yearAgo = new Date(todayStart().getTime() - 365 * DAY);
    const recent = state.bills.filter((b) => b.end >= yearAgo);
    const spend = recent.reduce((sum, b) => sum + b.amount, 0);
    const due = state.bills.filter((b) => b.status === "a_payer");
    const dueTotal = due.reduce((sum, b) => sum + b.amount, 0);
    const paid = state.bills.length - due.length;
    const next = due.filter((b) => b.due).sort((a, b) => a.due - b.due)[0];
    const holder = $("[data-overview-stats]");
    holder.innerHTML =
      consumptionKpi("electricite") +
      consumptionKpi("eau") +
      kpi("", '<span class="bill-ico bill-ico--blue" data-icon="wallet"></span>', "Dépenses sur 12 mois", F.number(spend, 3), "TND",
        `<p class="bill-kpi__foot">${plural(recent.length, "facture")} terminée${recent.length > 1 ? "s" : ""} depuis un an</p>`) +
      kpi(" bill-kpi--amber", '<span class="bill-ico bill-ico--amber" data-icon="clock"></span>', "Reste à payer", F.number(dueTotal, 3), "TND",
        `<div class="bill-progress" role="img" aria-label="${paid} facture${paid > 1 ? "s" : ""} payée${paid > 1 ? "s" : ""} sur ${state.bills.length}"><span data-progress="${state.bills.length ? (paid / state.bills.length) * 100 : 0}"></span></div>
         <p class="bill-kpi__foot">${paid} sur ${state.bills.length} payée${paid > 1 ? "s" : ""}${next ? ` · échéance le ${S.fmt.date(next.due)}` : ""}</p>`);
    S.hydrate(holder);
    applySizes(holder);
    renderTrend();
    renderSpend();
    renderRecent();
    renderInsights();
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
   * Un mois sans facture vaut null (barre vide, pas un zéro inventé).
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

  /* Barres arrondies, mois le plus haut mis en avant avec sa valeur. */
  function renderTrend() {
    const type = state.trend;
    const holder = $("[data-trend-chart]");
    const legend = $("[data-trend-legend]");
    $("[data-trend-title]").textContent = type === "eau" ? "Consommation d'eau" : "Consommation d'électricité";
    const data = monthlySeries(type);
    if (!data) {
      holder.innerHTML = `<div class="bill-empty">${logo(type, "xl", true)}<h3>Aucune facture ${type === "eau" ? "SONEDE" : "STEG"}</h3><p>Ajoutez une facture de ce fournisseur pour voir l'évolution de votre consommation.</p></div>`;
      legend.textContent = "";
      return;
    }
    const { months, values } = data;
    const known = values.filter((v) => v != null);
    const max = niceMax(Math.max(...known) * 1.1);
    const peak = values.indexOf(Math.max(...known));
    const avg = known.reduce((a, b) => a + b, 0) / known.length;
    const u = unit(type);
    const axis = [4, 3, 2, 1, 0].map((k) => `<span>${F.number((max / 4) * k)}</span>`).join("");
    const bars = values.map((v, i) => {
      const month = S.fmt.month(months[i]);
      if (v == null) return `<li class="bill-bar is-empty" aria-label="${S.fmt.monthLong(months[i])} : aucune facture"><span class="bill-bar__label" aria-hidden="true">${month}</span></li>`;
      const label = `${S.fmt.monthLong(months[i])} : ${F.number(v)} ${u}`;
      return `<li class="bill-bar${i === peak ? " is-hi" : ""}" data-h="${(v / max) * 100}" aria-label="${label}">
        ${i === peak ? `<span class="bill-bar__tip" aria-hidden="true">${month} · ${F.number(v)} ${u}</span>` : ""}
        <span class="bill-bar__label" aria-hidden="true">${month}</span></li>`;
    }).join("");
    holder.innerHTML = `<div class="bill-bars bill-bars--${type === "eau" ? "sonede" : "steg"}">
      <div class="bill-bars__axis" aria-hidden="true">${axis}</div>
      <ul class="bill-bars__plot" aria-label="Consommation mensuelle ${type === "eau" ? "d'eau" : "d'électricité"} (${u}) calculée à partir de vos factures">${bars}</ul>
    </div>`;
    applySizes(holder);
    legend.textContent = `Moyenne des mois facturés : ${F.number(avg, 1)} ${u} · pic en ${S.fmt.monthLong(months[peak])}`;
  }

  /* Jauge : dépenses des 12 derniers mois, réparties entre STEG et SONEDE. */
  function renderSpend() {
    const holder = $("[data-spend-chart]");
    const yearAgo = new Date(todayStart().getTime() - 365 * DAY);
    const recent = state.bills.filter((b) => b.end >= yearAgo);
    const sum = (type) => recent.filter((b) => b.type === type).reduce((s, b) => s + b.amount, 0);
    const steg = sum("electricite");
    const sonede = sum("eau");
    const total = steg + sonede;
    if (!total) {
      holder.innerHTML = '<div class="bill-empty"><h3>Aucune dépense sur 12 mois</h3><p>Les factures terminées depuis moins d\'un an apparaîtront ici.</p></div>';
      return;
    }
    const arc = 377;                       // 3/4 du cercle (rayon 80 → circonférence 502,65)
    const gap = steg && sonede ? 6 : 0;
    const lenSteg = Math.max(0, (arc * steg) / total - gap / 2);
    const lenSonede = Math.max(0, (arc * sonede) / total - gap / 2);
    const svg = el("svg", { viewBox: "0 0 200 200", "aria-hidden": "true", focusable: "false" });
    svg.appendChild(el("circle", { class: "bill-gauge__track", cx: 100, cy: 100, r: 80, "stroke-dasharray": `${arc} 503` }));
    if (lenSteg) svg.appendChild(el("circle", { class: "bill-gauge__steg", cx: 100, cy: 100, r: 80, "stroke-dasharray": `${lenSteg} 503` }));
    if (lenSonede) svg.appendChild(el("circle", { class: "bill-gauge__sonede", cx: 100, cy: 100, r: 80, "stroke-dasharray": `${lenSonede} 503`, "stroke-dashoffset": String(-(lenSteg + gap)) }));
    holder.innerHTML = `<div class="bill-gauge" role="img" aria-label="Dépenses sur 12 mois : ${F.money(total)}, dont ${F.money(steg)} pour la STEG et ${F.money(sonede)} pour la SONEDE">
        <div class="bill-gauge__center"><strong>${F.number(total, 0)}</strong><span>TND dépensés</span></div>
      </div>
      <ul class="bill-legend">
        <li>${logo("electricite", "sm", true)}Électricité (STEG)<b>${F.money(steg)}</b></li>
        <li>${logo("eau", "sm", true)}Eau (SONEDE)<b>${F.money(sonede)}</b></li>
      </ul>`;
    holder.querySelector(".bill-gauge").prepend(svg);
  }

  /* Ligne de facture (carte). compact = vue générale (Voir / Analyser) ; sinon + Supprimer. */
  function billRow(bill, compact) {
    const v = variation(bill);
    const label = `la facture ${bill.provider} du ${S.fmt.date(bill.start)} au ${S.fmt.date(bill.end)}`;
    return `<li class="bill-row bill-glass" data-id="${S.esc(bill.id)}">
      ${logo(bill.type, "lg", true)}
      <div><p class="bill-row__title" data-no-i18n>${S.esc(bill.provider)} · ${periodShort(bill)}</p><p class="bill-row__sub" data-no-i18n>Abonnement ${S.esc(group(bill.refAbo, 3))} · ${qty(bill)}</p></div>
      <div class="bill-row__col"><span class="bill-row__label">Montant</span><span class="bill-row__amount">${F.money(bill.amount)}</span></div>
      <div class="bill-row__col bill-row__col--opt bill-row__col--hide-sm"><span class="bill-row__label">Variation*</span>${v ? deltaHtml(v.quantity) : '<span class="bill-muted">—</span>'}</div>
      <div class="bill-row__col bill-row__col--status"><span class="bill-row__label">Statut</span>${statusChip(bill)}</div>
      <div class="bill-row__actions">
        <button class="bill-icon-btn" type="button" data-bill-action="analyze" aria-label="Analyser ${S.esc(label)}" title="Analyser"><span data-icon="sparkles"></span></button>
        ${compact ? "" : `<button class="bill-icon-btn bill-icon-btn--danger" type="button" data-bill-action="delete" aria-label="Supprimer ${S.esc(label)}" title="Supprimer"><span data-icon="trash"></span></button>`}
        <button class="bill-icon-btn" type="button" data-bill-action="view" aria-label="Voir ${S.esc(label)}" title="Voir"><span data-icon="arrow-right"></span></button>
      </div>
    </li>`;
  }

  function renderRecent() {
    const list = $("[data-recent-rows]");
    list.innerHTML = sorted().slice(0, 4).map((b) => billRow(b, true)).join("");
    S.hydrate(list);
  }

  /* Constats de Wasel : hausse inhabituelle, bonne nouvelle, échéances. */
  function renderInsights() {
    const out = [];
    const latest = ["electricite", "eau"].map(lastOf).filter(Boolean).map((b) => ({ b, v: variation(b) })).filter((x) => x.v);
    const rise = latest.filter((x) => x.v.quantity >= 15).sort((a, b) => b.v.quantity - a.v.quantity)[0];
    const drop = latest.filter((x) => x.v.quantity <= -5).sort((a, b) => a.v.quantity - b.v.quantity)[0];
    if (rise) out.push(["alert", "triangle-alert", "Hausse inhabituelle", `Votre dernière facture ${S.esc(rise.b.provider)} (${periodShort(rise.b)}) consomme <b>${F.percent(rise.v.quantity)}</b> par jour par rapport à la précédente.`]);
    if (drop) out.push(["good", "leaf", "Bonne nouvelle", `Votre dernière facture ${S.esc(drop.b.provider)} baisse de <b>${F.number(Math.abs(drop.v.quantity), 1)} %</b> par jour. Continuez !`]);
    const due = state.bills.filter((b) => b.status === "a_payer");
    const late = due.filter(isOverdue);
    const next = due.filter((b) => b.due && !isOverdue(b)).sort((a, b) => a.due - b.due)[0];
    if (due.length) out.push(["due", "clock", "À ne pas oublier", `${plural(due.length, "facture")} à payer${late.length ? `, dont <b>${late.length} en retard</b>` : ""}.${next ? ` Prochaine date limite : <b>${S.fmt.date(next.due)}</b>.` : ""}`]);
    if (!out.length) out.push(["good", "circle-check", "Tout est en ordre", "Aucune hausse inhabituelle et aucune facture en attente de paiement."]);
    const holder = $("[data-insights]");
    holder.innerHTML = out.map(([kind, icon, title, text]) => `<article class="bill-insight bill-insight--${kind} bill-glass"><h3><span data-icon="${icon}"></span>${title}</h3><p>${text}</p></article>`).join("");
    S.hydrate(holder);
  }

  /* ---------- Mes factures (onglet) ---------- */

  function renderBills() {
    const rows = sorted().filter((b) => !state.filter || b.type === state.filter);
    $("[data-bill-count]").textContent = state.bills.length;
    $("[data-bill-caption]").textContent = `${plural(rows.length, "facture")} · * variation de la consommation moyenne par jour vs facture précédente`;
    const list = $("[data-bill-rows]");
    list.innerHTML = rows.length
      ? rows.map((b) => billRow(b, false)).join("")
      : '<li class="bill-empty bill-glass"><h3>Aucune facture pour ce fournisseur</h3><p>Choisissez « Tous les fournisseurs » ou ajoutez une facture.</p></li>';
    S.hydrate(list);
    fillAnalysisSelect();
  }

  function openBillDetail(id) {
    const bill = billById(id);
    if (!bill) return;
    const br = breakdown(bill);
    const v = variation(bill);
    $("#bill-detail-title").textContent = `Facture ${bill.provider} · ${periodShort(bill)}`;
    $("[data-bill-detail-sub]").textContent = `${typeLabel[bill.type]} · abonnement ${group(bill.refAbo, 3)}`;
    $("[data-bill-detail-body]").innerHTML = `
      <div class="bill-ticket__top">${logo(bill.type, "lg")}<div><p class="bill-row__title">${typeLabel[bill.type]} · ${S.esc(bill.provider)}</p><p class="bill-row__sub" data-no-i18n>Abonnement ${S.esc(group(bill.refAbo, 3))}</p></div>${statusChip(bill)}</div>
      <p class="bill-ticket__amount">${F.number(bill.amount, 3)} <small>TND</small></p>
      <dl class="bill-facts">
        <div class="bill-fact"><dt>Période</dt><dd>${period(bill)}</dd></div>
        <div class="bill-fact"><dt>Durée</dt><dd>${days(bill)} jours</dd></div>
        <div class="bill-fact"><dt>Consommation</dt><dd>${qty(bill)}</dd></div>
        <div class="bill-fact"><dt>Talon</dt><dd data-no-i18n${bill.refPaie ? "" : ' class="bill-muted"'}>${bill.refPaie ? S.esc(group(bill.refPaie, 4)) : "Non renseigné"}</dd></div>
        <div class="bill-fact"><dt>Date limite</dt><dd${bill.due ? "" : ' class="bill-muted"'}>${bill.due ? S.fmt.date(bill.due) : "Non renseignée"}</dd></div>
        <div class="bill-fact"><dt>Variation*</dt><dd>${v ? deltaHtml(v.quantity) : '<span class="bill-muted">—</span>'}</dd></div>
      </dl>
      <h3 class="bill-step__lead">Décomposition estimée (tarifs de référence indicatifs)</h3>
      <table class="bill-breakdown">
        <tbody>
          <tr><td>Consommation : ${qty(bill)} × ${F.number(TARIFS[bill.type].price, 3)} TND</td><td>${F.money(br.energy)}</td></tr>
          <tr><td>Redevance fixe (abonnement, compteur)</td><td>${F.money(br.fixed)}</td></tr>
          <tr><td>${S.esc(br.extraLabel)}</td><td>${F.money(br.extra)}</td></tr>
          <tr><td>TVA (${Math.round(TARIFS[bill.type].taxRate * 100)} %)</td><td>${F.money(br.tax)}</td></tr>
          <tr><td><strong>Total estimé</strong></td><td>${F.money(br.total)}</td></tr>
        </tbody>
      </table>
      ${Math.abs(br.total - bill.amount) > 1 ? `<p class="field__hint">L'écart avec le montant facturé (${F.money(bill.amount)}) vient des tranches tarifaires réelles, non reproduites ici.</p>` : ""}`;
    $("[data-bill-detail-foot]").innerHTML = `
      <button class="bill-btn bill-btn--danger bill-btn--sm" type="button" data-bill-detail="delete"><span data-icon="trash"></span>Supprimer</button>
      <a class="bill-btn bill-btn--glass bill-btn--sm" href="${S.esc(bill.urlModifier)}"><span data-icon="pencil"></span>Modifier</a>
      <button class="bill-btn bill-btn--white bill-btn--sm" type="button" data-bill-detail="analyze"><span data-icon="sparkles"></span>Analyser cette facture</button>`;
    S.hydrate($("#bill-detail"));
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
        text: `Facture ${bill.provider} du ${S.fmt.date(bill.start)} au ${S.fmt.date(bill.end)} · ${F.money(bill.amount)}. Cette action est définitive.`,
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

  /* Bulle de Wasel : le bouton dépend du message (ajouter, expliquer une hausse, faire le point, question). */
  $("[data-wasel-cta]").addEventListener("click", (event) => {
    const mode = event.currentTarget.dataset.mode || "ask";
    if (mode === "add") return S.openModal($("#bill-modal"));
    state.tabs.show("assistant");
    if (mode === "explain") ask("Pourquoi ma facture a-t-elle augmenté ?");
    else if (mode === "explain-eau") ask("Pourquoi ma facture d'eau a-t-elle augmenté ?");
    else if (mode === "pay") ask("Quelles factures dois-je payer ?");
    $("[data-tab-panel=\"assistant\"]").scrollIntoView({ behavior: "smooth", block: "start" });
    $("#chat-input").focus({ preventScroll: true });
  });
  $("[data-wasel-later]").addEventListener("click", () => {
    $("[data-wasel-bubble]").hidden = true;
  });

  /* ---------- Ajouter une facture (enregistrement en base) ---------- */

  const billForm = $("#bill-modal [data-bill-crud-form]");

  function resetBillForm() {
    billForm.reset();
    S.clearErrors(billForm);
    billForm.querySelectorAll(".has-error, .is-invalid").forEach((node) => node.classList.remove("has-error", "is-invalid"));
    // Remet l'unité, les exemples et les aides « sans fournisseur » (factures-crud.js écoute « change »)
    billForm.querySelectorAll('[name="type_ressource"]').forEach((f) => f.dispatchEvent(new Event("change")));
    S.clearErrors(billForm);
    if (window.WASLA_factures) window.WASLA_factures.resetWizard(billForm);
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
    // Parcours guidé : afficher l'étape du premier champ en erreur (par l'API du parcours, qui garde l'étape courante à jour)
    if (first && window.WASLA_factures) window.WASLA_factures.revealField(billForm, first);
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
      .map((b) => `<option value="${S.esc(b.id)}"${b.id === state.analysisId ? " selected" : ""}>${S.esc(`${b.provider} · ${periodShort(b)} · ${F.money(b.amount)}`)}</option>`)
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
    const saving = F.number(bill.amount * 0.1, 3);
    return bill.type === "electricite"
      ? ["Régler la climatisation sur 25–26 °C plutôt que 20 °C : jusqu'à 20 % d'économie sur la clim.", "Programmer le chauffe-eau la nuit et le régler sur 55 °C.", "Débrancher les appareils en veille (box TV, chargeurs, consoles).", `Objectif −10 % : environ ${saving} TND économisés sur une facture comparable.`]
      : ["Installer des mousseurs sur les robinets : jusqu'à 50 % de débit en moins.", "Vérifier l'absence de fuite : relevez le compteur le soir puis le matin sans utiliser d'eau.", "Privilégier les douches courtes et remplir le lave-linge.", `Objectif −10 % : environ ${saving} TND économisés sur une facture comparable.`];
  }

  function renderAnalysis() {
    const holder = $("[data-analysis]");
    const bill = billById(state.analysisId);
    if (!bill) {
      holder.innerHTML = '<article class="bill-empty bill-glass"><h3>Aucune facture à analyser</h3><p>Ajoutez une facture pour obtenir une analyse.</p></article>';
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
      ? `Votre consommation ${label} a ${v.quantity >= 0 ? "augmenté" : "baissé"} de <strong>${F.number(Math.abs(v.quantity), 0)} %</strong> par rapport à la facture précédente (${F.number(perDay(bill), dec)} ${u}/jour contre ${F.number(perDay(v.prev), dec)} ${u}/jour). ${v.quantity >= 0 ? "Voici les variations observées et quelques recommandations pour réduire votre consommation." : "Continuez ainsi : voici quelques pistes pour aller plus loin."}`
      : "C'est votre première facture de ce fournisseur : l'analyse comparative sera disponible dès la prochaine facture.";

    const maxQ = Math.max(bill.quantity, v ? v.prev.quantity : 0) || 1;
    const bar = (text, b, cls) => `
      <div class="bill-compare__row">
        <span>${S.esc(text)} <b>${qty(b)} · ${F.money(b.amount)}</b></span>
        <div class="bill-progress${cls}"><span data-progress="${(b.quantity / maxQ) * 100}"></span></div>
      </div>`;

    holder.innerHTML = `
      <div class="svc-split">
        <article class="card">
          <div class="card__body">
            <div class="bill-ticket__top">${logo(bill.type, "md")}<p class="svc-source">Données de votre facture</p></div>
            <dl class="svc-dl">
              <div><dt>Fournisseur</dt><dd>${S.esc(bill.provider)} (${typeLabel[bill.type]})</dd></div>
              <div><dt>Abonnement</dt><dd data-no-i18n>${S.esc(group(bill.refAbo, 3))}</dd></div>
              <div><dt>Période</dt><dd>${period(bill)}</dd></div>
              <div><dt>Durée</dt><dd>${days(bill)} jours</dd></div>
              <div><dt>Consommation</dt><dd>${qty(bill)}</dd></div>
              <div><dt>Montant</dt><dd>${F.money(bill.amount)}</dd></div>
            </dl>
            <div class="svc-block">
              <h3>Comparaison avec la facture précédente</h3>
              ${v ? `<div class="bill-compare">${bar("Cette facture", bill, bill.type === "eau" ? "" : " bill-progress--paid")}${bar(`Précédente (${periodShort(v.prev)})`, v.prev, " bill-progress--prev")}</div>
              <p class="field__hint">Montant : ${deltaHtml(v.amount)} · consommation moyenne par jour : ${deltaHtml(v.quantity)}</p>` : "<p>Pas de facture précédente du même fournisseur.</p>"}
            </div>
            <div class="svc-block">
              <h3>Comprendre votre facture</h3>
              <ul class="svc-bullets">
                <li><span data-icon="check"></span>Avec les tarifs de référence, la consommation représente environ ${energyShare} % du montant (${F.money(br.energy)}).</li>
                <li><span data-icon="check"></span>La partie fixe (abonnement, compteur, redevances) est due même sans consommation.</li>
                <li><span data-icon="check"></span>La TVA s'applique sur l'ensemble (${Math.round(TARIFS[bill.type].taxRate * 100)} % dans les tarifs de référence).</li>
              </ul>
            </div>
          </div>
        </article>

        <article class="card svc-ai-card">
          <div class="card__body">
            <p class="svc-source svc-source--ai"><span data-icon="sparkles"></span>Interprétation de Wasel — hypothèses (analyse simulée)</p>
            <p class="svc-highlight">${headline}</p>
            <div class="svc-block">
              <h3>Variations inhabituelles</h3>
              ${v ? unusual
                ? `<p class="bill-notice bill-glass"><span data-icon="triangle-alert"></span><span>Écart de ${F.percent(v.quantity)} sur la consommation journalière : au-delà du seuil de ±15 % utilisé pour signaler une variation inhabituelle.</span></p>`
                : `<p class="bill-notice bill-glass"><span data-icon="circle-check"></span><span>Variation de ${F.percent(v.quantity)} : dans la plage habituelle (±15 %).</span></p>`
                : "<p>Comparaison impossible sans facture précédente.</p>"}
            </div>
            <div class="svc-block">
              <h3>Causes possibles</h3>
              <ul class="svc-bullets">${causesFor(bill, v).map(([level, text]) => `<li><span data-icon="info"></span><span><span class="bill-chip ${level === "Probable" ? "bill-chip--due" : "bill-chip--paid"}">${level}</span> ${S.esc(text)}</span></li>`).join("")}</ul>
            </div>
            <div class="svc-block">
              <h3>Conseils pour économiser</h3>
              <ul class="svc-bullets">${tipsFor(bill).map((tip) => `<li><span data-icon="leaf"></span>${S.esc(tip)}</li>`).join("")}</ul>
            </div>
            <p class="field__hint">Ces explications sont produites par des règles simples à partir de vos factures ; elles peuvent être inexactes.</p>
          </div>
        </article>
      </div>`;
    applySizes(holder);
    S.hydrate(holder);
  }

  /* ---------- Wasel, l'assistant (simulé, conversation non enregistrée) ---------- */

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
      if (!v) return `<p>Votre dernière facture ${focus.provider} (${period(focus)}) s'élève à <strong>${F.money(focus.amount)}</strong>. Je n'ai pas de facture précédente du même fournisseur pour comparer.</p>`;
      const causes = causesFor(focus, v).map(([, t]) => `<li>${S.esc(t)}</li>`).join("");
      return `<p>Sur votre dernière facture ${focus.provider} (${period(focus)}), la consommation moyenne est de <strong>${F.number(perDay(focus), dec)} ${u}/jour</strong> contre ${F.number(perDay(v.prev), dec)} ${u}/jour la fois précédente, soit <strong>${F.percent(v.quantity)}</strong>. Le montant varie de ${F.percent(v.amount)}.</p><p>Hypothèses les plus plausibles :</p><ul>${causes}</ul>`;
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
      return `<p>Votre facture ${focus.provider} du ${period(focus)} se décompose ainsi (estimation avec les tarifs de référence) :</p><ul><li>Consommation : ${qty(focus)} → ${F.money(br.energy)}</li><li>Redevance fixe : ${F.money(br.fixed)}</li><li>${S.esc(br.extraLabel)} : ${F.money(br.extra)}</li><li>TVA : ${F.money(br.tax)}</li></ul><p>Le montant facturé est de <strong>${F.money(focus.amount)}</strong>${focus.due ? `, à régler avant le ${S.fmt.date(focus.due)}` : ""}.</p>`;
    }
    if (/fuite/.test(q)) {
      if (!water) return "<p>Vous n'avez pas encore de facture d'eau enregistrée.</p>";
      const wv = variation(water);
      return `<p>Votre dernière facture d'eau indique ${F.number(perDay(water), 2)} m³/jour${wv ? ` (${F.percent(wv.quantity)} vs précédente)` : ""}. ${wv && wv.quantity > 20 ? "C'est une hausse marquée : une fuite est possible." : "Rien n'indique clairement une fuite."}</p><p>Pour vérifier : notez l'index du compteur le soir, n'utilisez pas d'eau pendant la nuit et relevez-le le matin. S'il a bougé, il y a une fuite.</p>`;
    }
    if (/payer|échéance|echeance|retard|impayé|point/.test(q)) {
      const due = sorted().filter((b) => b.status === "a_payer");
      if (!due.length) return "<p>Toutes vos factures enregistrées sont marquées comme payées.</p>";
      return `<p>Vous avez ${plural(due.length, "facture")} à payer :</p><ul>${due.map((b) => `<li>${b.provider} · ${period(b)} : <strong>${F.money(b.amount)}</strong>${b.due ? ` avant le ${S.fmt.date(b.due)}` : ""}${isOverdue(b) ? " (en retard)" : ""}</li>`).join("")}</ul><p>Le paiement se fait auprès de la STEG ou de la SONEDE : WASLA ne gère pas les paiements.</p>`;
    }
    if (/bonjour|salut|aide|merci/.test(q)) return "<p>Bonjour ! Je peux expliquer une facture, comparer deux périodes, identifier les appareils qui consomment le plus ou proposer des économies. Choisissez une question ci-dessous ou écrivez la vôtre.</p>";
    return "<p>Je n'ai pas bien compris. Je sais répondre aux questions sur la hausse d'une facture, les économies possibles, la consommation des appareils, la lecture d'une facture, les fuites d'eau et les échéances.</p>";
  }

  const avatar = '<svg class="bill-msg__avatar" aria-hidden="true" focusable="false"><use href="#bill-wasel"/></svg>';

  function messageHtml(msg) {
    return `<div class="bill-msg bill-msg--${msg.role}">
      ${msg.role === "bot" ? avatar : ""}
      <div class="bill-msg__bubble">${msg.role === "user" ? `<p>${S.esc(msg.text)}</p>` : msg.html}<span class="bill-msg__time">${S.fmt.time(msg.date)}${msg.role === "bot" ? " · réponse simulée" : ""}</span></div>
    </div>`;
  }

  function renderChat() {
    const intro = { role: "bot", date: new Date().toISOString(), html: `<p>Bonjour${prenom ? ` ${S.esc(prenom)}` : ""} ! Je suis <b>Wasel</b>, votre assistant pour vos factures ${logo("electricite", "xs")} et ${logo("eau", "xs")}. Posez-moi une question : je réponds à partir des factures enregistrées dans votre compte.</p>` };
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
    typing.className = "bill-msg bill-msg--bot";
    typing.innerHTML = `${avatar}<div class="bill-msg__bubble"><span class="bill-typing" role="status" aria-label="Wasel rédige une réponse"><span></span><span></span><span></span></span></div>`;
    log.appendChild(typing);
    log.scrollTop = log.scrollHeight;
    setTimeout(() => {
      state.chat.push({ role: "bot", html: answer(text), date: new Date().toISOString() });
      state.chat = state.chat.slice(-40);
      renderChat();
    }, 600);
  }

  $("[data-chat-suggestions]").innerHTML = suggestions.map((s) => `<button type="button" data-ask>${S.esc(s)}</button>`).join("");
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
    const ok = await S.confirm({ title: "Effacer la conversation ?", text: "Les messages échangés avec Wasel seront effacés.", confirmLabel: "Effacer", danger: true });
    if (!ok) return;
    state.chat = [];
    renderChat();
  });

  /* ---------- Démarrage ---------- */

  document.addEventListener("DOMContentLoaded", () => {
    const empty = state.bills.length === 0;
    renderHero();
    // Aucune facture : un seul état vide clair (avec « Ajouter une facture ») à la place des onglets
    $("[data-bills-empty]").hidden = !empty;
    $("[data-bills-tabs]").hidden = empty;
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
    // Facture demandée par la page « Voir » (?facture=…) : seulement si elle fait partie des factures du citoyen
    const params = new URLSearchParams(window.location.search);
    const asked = params.get("facture");
    if (asked && billById(asked)) state.analysisId = String(asked);
    fillAnalysisSelect();
    renderAnalysis();
    // Onglet demandé dans l'adresse (?onglet=bills après un ajout ou une suppression) : liste blanche
    const onglet = params.get("onglet");
    if (["overview", "bills", "analysis", "assistant"].includes(onglet)) state.tabs.show(onglet);
  });
})();
