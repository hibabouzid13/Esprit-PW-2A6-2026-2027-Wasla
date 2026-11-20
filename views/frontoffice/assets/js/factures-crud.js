/* =====================================================================
   WASLA — Module 2 (Hiba) : CRUD des factures (Front Office et Back Office)
   ---------------------------------------------------------------------
   - Ajoute les icônes utilisées par les pages du CRUD.
   - Selon la ressource : unité (kWh / m³), exemple et aide de la référence
     (STG-… pour la STEG, SND-… pour la SONEDE).
   - Ajoute les contrôles propres aux factures (référence, nombres avec ou sans
     unité, règles des dates par fournisseur), en plus des règles data-rules de
     validation.js. Les MÊMES contrôles sont refaits en PHP dans
     FactureController::check() ; le chevauchement de périodes n'est vérifié
     qu'en PHP (il faut consulter la base).
   Aucune validation HTML5.
   ===================================================================== */
(function () {
  "use strict";

  /* Les MÊMES règles que FactureController::check() (PHP). */
  const PREFIXES = { electricite: "STG", eau: "SND" };
  const EXEMPLES = {
    electricite: { ref: "STG-20260918", qte: "455 ou 455 kWh" },
    eau: { ref: "SND-20260930", qte: "22.5 ou 22.5 m³" }
  };
  const DUREES = {
    electricite: { minJours: 15, maxMois: 12, nom: "STEG", note: "régularisation" },
    eau: { minJours: 15, maxMois: 6, nom: "SONEDE", note: "plafond réglementaire" }
  };
  const DATE_MIN = "2016-01-01";
  const ECHEANCE_MAX_JOURS = 90;
  const MONTANT_MAX = 99999.999;
  const QUANTITE_MAX = 999999.99;
  const ISO = /^\d{4}-\d{2}-\d{2}$/;

  const pad = (n) => String(n).padStart(2, "0");
  const iso = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
  const fr = (s) => s.split("-").reverse().join("/");
  const parse = (s) => { const [y, m, d] = s.split("-").map(Number); return new Date(y, m - 1, d); };
  const today = () => iso(new Date());
  /* Même calcul que PHP « +N months » / « +N days » (débordement de fin de mois compris). */
  const addMonths = (s, n) => { const d = parse(s); return iso(new Date(d.getFullYear(), d.getMonth() + n, d.getDate())); };
  const addDays = (s, n) => { const d = parse(s); return iso(new Date(d.getFullYear(), d.getMonth(), d.getDate() + n)); };
  const daysBetween = (a, b) => {
    const [x, y] = [parse(a), parse(b)];
    return Math.round((Date.UTC(y.getFullYear(), y.getMonth(), y.getDate()) - Date.UTC(x.getFullYear(), x.getMonth(), x.getDate())) / 86400000);
  };

  function referenceError(ref, type) {
    if (!/^(STG|SND)-\d{1,20}$/.test(ref)) {
      if (type === "electricite") return "Format attendu : STG- suivi de chiffres uniquement (ex. STG-20260918).";
      if (type === "eau") return "Format attendu : SND- suivi de chiffres uniquement (ex. SND-20260930).";
      return "Format attendu : STG-chiffres (STEG) ou SND-chiffres (SONEDE).";
    }
    if (type && ref.slice(0, 3) !== PREFIXES[type]) {
      return type === "electricite"
        ? "Une facture STEG commence par STG- (SND- est réservé aux factures SONEDE)."
        : "Une facture SONEDE commence par SND- (STG- est réservé aux factures STEG).";
    }
    return "";
  }

  /* « 22.5 », « 22,5 m³ », « 500TND »… → { n, unit } ; null si ce n'est pas un réel (+ unité). */
  function parseNombre(value, units, decimals) {
    const re = new RegExp(`^(\\d{1,9}(?:[.,]\\d{1,${decimals}})?)\\s*(${units})?$`, "i");
    const m = value.trim().match(re);
    return m ? { n: parseFloat(m[1].replace(",", ".")), unit: (m[2] || "").toLowerCase() } : null;
  }

  function quantiteError(value, type) {
    const exemple = type === "eau" ? EXEMPLES.eau.qte : EXEMPLES.electricite.qte;
    const lu = parseNombre(value, "kwh|m³|m3", 2);
    if (!lu) return `Saisissez un nombre réel (2 décimales au plus), avec ou sans unité : ex. ${exemple}.`;
    if (lu.unit && type === "eau" && lu.unit === "kwh") return "Une facture d'eau se mesure en m³, pas en kWh (ex. 22.5 m³).";
    if (lu.unit && type === "electricite" && lu.unit !== "kwh") return "Une facture d'électricité se mesure en kWh, pas en m³ (ex. 455 kWh).";
    if (lu.n <= 0) return "La consommation doit être supérieure à 0.";
    if (lu.n > QUANTITE_MAX) return "Consommation trop élevée (999 999,99 au maximum).";
    return "";
  }

  function montantError(value) {
    const lu = parseNombre(value, "tnd", 3);
    if (!lu) return "Saisissez un nombre réel (3 décimales au plus), avec ou sans TND : ex. 500 ou 500 TND.";
    if (lu.n <= 0) return "Le montant doit être supérieur à 0.";
    if (lu.n > MONTANT_MAX) return "Montant trop élevé (99 999,999 TND au maximum).";
    return "";
  }

  function dateErrors(debut, fin, echeance, type, auj) {
    const errors = {};
    [["periode_debut", debut, "Le début"], ["periode_fin", fin, "La fin"]].forEach(([champ, d, nom]) => {
      if (!ISO.test(d)) return;
      if (d < DATE_MIN) errors[champ] = `Date trop ancienne : les factures antérieures au ${fr(DATE_MIN)} ne sont pas acceptées.`;
      else if (d > auj) errors[champ] = `${nom} de la période ne peut pas être dans le futur.`;
    });

    if (ISO.test(debut) && ISO.test(fin) && !errors.periode_debut && !errors.periode_fin) {
      if (debut >= fin) {
        errors.periode_fin = "La fin de la période doit être strictement après le début.";
      } else if (type) {
        const r = DUREES[type];
        const jours = daysBetween(debut, fin);
        const limite = addMonths(debut, r.maxMois);
        if (jours < r.minJours) {
          errors.periode_fin = `Une période ${r.nom} dure au moins ${r.minJours} jours (ici ${jours} jour${jours > 1 ? "s" : ""}).`;
        } else if (fin > limite) {
          errors.periode_fin = `Une période ${r.nom} dure au plus ${r.maxMois} mois (${r.note}) : la fin doit être au plus le ${fr(limite)}.`;
        }
      }
    }

    if (echeance && ISO.test(echeance) && ISO.test(fin) && !errors.periode_fin) {
      const max = addDays(fin, ECHEANCE_MAX_JOURS);
      if (echeance < fin) {
        errors.date_echeance = "La date limite de paiement doit être le jour de la fin de la période ou après.";
      } else if (echeance > max) {
        errors.date_echeance = `La date limite de paiement tombe au plus ${ECHEANCE_MAX_JOURS} jours après la fin de la période (au plus tard le ${fr(max)}).`;
      }
    }
    return errors;
  }

  /** Erreurs propres aux factures : { champ: message }. */
  function businessErrors(form) {
    const v = (name) => {
      const el = form.querySelector(`[name="${name}"]`);
      return el ? String(el.value).trim() : "";
    };
    const type = PREFIXES[v("type_ressource")] ? v("type_ressource") : "";
    const errors = {};
    if (v("reference")) {
      const m = referenceError(v("reference").toUpperCase(), type);
      if (m) errors.reference = m;
    }
    if (v("quantite")) {
      const m = quantiteError(v("quantite"), type);
      if (m) errors.quantite = m;
    }
    if (v("montant")) {
      const m = montantError(v("montant"));
      if (m) errors.montant = m;
    }
    return Object.assign(errors, dateErrors(v("periode_debut"), v("periode_fin"), v("date_echeance"), type, today()));
  }

  /* Unité, placeholder et aide de la référence selon la ressource choisie. */
  function setupType(form) {
    // Par le NOM : dans le formulaire STEG/SONEDE, un champ caché porte name="type_ressource"
    // et un champ en lecture seule porte id="type_ressource" (form.elements renverrait les deux).
    const field = form.querySelector('[name="type_ressource"]');
    if (!field) return;
    const unit = form.querySelector("[data-bill-unit]");
    const ref = form.querySelector('[name="reference"]');
    const refHint = form.querySelector("[data-bill-ref-hint]");
    const qte = form.querySelector('[name="quantite"]');
    const update = () => {
      const type = PREFIXES[field.value] ? field.value : "";
      if (unit) unit.textContent = type === "eau" ? "m³" : "kWh";
      if (ref) ref.placeholder = type ? `ex. ${EXEMPLES[type].ref}` : "Choisissez d'abord la ressource";
      if (refHint) {
        refHint.textContent = type
          ? `Format : ${PREFIXES[type]}- suivi de chiffres (ex. ${EXEMPLES[type].ref}).`
          : "STG-chiffres pour la STEG, SND-chiffres pour la SONEDE.";
      }
      if (qte) qte.placeholder = `ex. ${type === "eau" ? EXEMPLES.eau.qte : EXEMPLES.electricite.qte}`;
    };
    field.addEventListener("change", update);
    update();
  }

  function setupBusinessChecks(form) {
    const api = window.WASLA_validate;
    if (!api) return;
    const submit = form.querySelector('[type="submit"]');
    const label = submit ? submit.textContent : "";
    const field = (name) => form.querySelector(`[name="${name}"]:not([type="hidden"])`) || form.querySelector(`[name="${name}"]`);
    const champs = ["reference", "quantite", "montant", "periode_debut", "periode_fin", "date_echeance"];

    /* Affiche les erreurs « facture » sur les champs que validation.js n'a pas déjà signalés. */
    const apply = (names) => {
      const errors = businessErrors(form);
      let first = null;
      names.forEach((name) => {
        const el = field(name);
        if (!el || api.check(form, el)) return;   // erreur de base déjà affichée par validation.js
        api.show(form, el, errors[name] || "");
        if (errors[name] && !first) first = el;
      });
      return first;
    };

    // Contrôle en direct quand on quitte un champ (et sur les champs liés : dates, ressource).
    // « blur » aussi : validation.js revérifie le champ à ce moment-là et effacerait sinon l'erreur.
    champs.concat(["type_ressource"]).forEach((name) => {
      const el = field(name);
      if (!el) return;
      const run = () => {
        const remplis = (liste) => liste.filter((c) => field(c) && field(c).value);
        if (name === "type_ressource") apply(remplis(champs));
        else if (name.startsWith("periode") || name === "date_echeance") apply(remplis(["periode_debut", "periode_fin", "date_echeance"]));
        else if (el.value) apply([name]);
      };
      el.addEventListener("change", run);
      el.addEventListener("blur", run);
    });

    form.addEventListener("submit", (event) => {
      const dejaBloque = event.defaultPrevented;   // validation.js a déjà trouvé une erreur de base
      const first = apply(champs);
      if (!first) return;
      event.preventDefault();
      if (dejaBloque) return;                      // il a déjà placé le curseur et affiché le message
      first.focus();
      if (window.WASLA_toast) window.WASLA_toast("Veuillez corriger les champs indiqués.");
      else if (window.WASLA_adminToast) window.WASLA_adminToast("Veuillez corriger les champs indiqués.");
      if (submit) {                                // validation.js avait mis le bouton en « Enregistrement… »
        submit.disabled = false;
        submit.textContent = label;
      }
    });
  }

  /* Icônes Lucide (licence ISC) utilisées par les pages du CRUD, si elles manquent */
  const SVG = (paths) =>
    `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${paths}</svg>`;
  const ICONS = {
    pencil: SVG('<path d="M21.17 6.81a1 1 0 0 0-3.99-3.99L3.84 16.17a2 2 0 0 0-.5.83l-1.32 4.35a.5.5 0 0 0 .62.62l4.35-1.32a2 2 0 0 0 .83-.5z"/><path d="m15 5 4 4"/>'),
    trash: SVG('<path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/>'),
    wallet: SVG('<path d="M19 7V4a1 1 0 0 0-1-1H5a2 2 0 0 0 0 4h15a1 1 0 0 1 1 1v4h-3a2 2 0 0 0 0 4h3a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1"/><path d="M3 5v14a2 2 0 0 0 2 2h15a1 1 0 0 0 1-1v-4"/>'),
    banknote: SVG('<rect width="20" height="12" x="2" y="6" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/>'),
    eye: SVG('<path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/>'),
    save: SVG('<path d="M15.2 3a2 2 0 0 1 1.4.6l3.8 3.8a2 2 0 0 1 .6 1.4V19a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z"/><path d="M17 21v-7a1 1 0 0 0-1-1H8a1 1 0 0 0-1 1v7"/><path d="M7 3v4a1 1 0 0 0 1 1h7"/>')
  };
  function addIcons() {
    window.WASLA_ICONS = window.WASLA_ICONS || {};
    Object.keys(ICONS).forEach((key) => {
      if (!window.WASLA_ICONS[key]) window.WASLA_ICONS[key] = ICONS[key];
    });
    if (window.WASLA_hydrateIcons) window.WASLA_hydrateIcons(document);
  }
  document.addEventListener("DOMContentLoaded", addIcons);

  // « load » arrive après DOMContentLoaded : ces contrôles s'exécutent APRÈS ceux de validation.js
  window.addEventListener("load", () => {
    document.querySelectorAll("form[data-bill-crud-form]").forEach((form) => {
      setupType(form);
      setupBusinessChecks(form);
    });
  });
})();
