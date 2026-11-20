/* =====================================================================
   WASLA — Module 2 (Hiba) : CRUD des factures (Front Office et Back Office)
   ---------------------------------------------------------------------
   - Ajoute les icônes utilisées par les pages du CRUD.
   - Selon le fournisseur : unité (kWh / m³), exemples et aides des deux
     références (abonnement et paiement / talon).
   - Références : espaces retirés et chiffres seulement pendant la frappe,
     longueur limitée en JavaScript (pas d'attribut maxlength : HTML5 interdit).
   - Ajoute les contrôles propres aux factures (références, nombres avec ou sans
     unité, règles des dates par fournisseur), en plus des règles data-rules de
     validation.js. Les MÊMES contrôles sont refaits en PHP dans
     FactureController::check() ; le chevauchement de périodes n'est vérifié
     qu'en PHP (il faut consulter la base).
   Aucune validation HTML5.
   ===================================================================== */
(function () {
  "use strict";

  /* Les MÊMES règles que FactureController::check() (PHP). */
  /* Références : [longueur min, longueur max] selon le fournisseur (FactureController::REFERENCES). */
  const REFERENCES = {
    electricite: { abonnement: [9, 9], paiement: [16, 16] },
    eau: { abonnement: [8, 9], paiement: [12, 16] }
  };
  const REFERENCE_MAX = { reference_abonnement: 9, reference_paiement: 16 };
  const REFERENCE_MESSAGES = {
    electricite: {
      reference_abonnement: "La référence d'abonnement STEG doit contenir exactement 9 chiffres.",
      reference_paiement: "La référence de paiement STEG (talon) doit contenir exactement 16 chiffres."
    },
    eau: {
      reference_abonnement: "La référence d'abonnement SONEDE (district + police) doit contenir entre 8 et 9 chiffres.",
      reference_paiement: "La référence de paiement SONEDE (talon) doit contenir entre 12 et 16 chiffres."
    }
  };
  const ABONNEMENT_VIDE = "Indiquez la référence d'abonnement (en haut de la facture).";
  const EXEMPLES = {
    electricite: {
      abonnement: "Ex : 937974800", paiement: "Ex : 0000049937974800 (facultatif)", qte: "455 ou 455 kWh",
      aideAbonnement: "9 chiffres, en haut de la facture STEG.",
      aidePaiement: "16 chiffres, sur le talon en bas de la facture."
    },
    eau: {
      abonnement: "Ex : 123456789", paiement: "Ex : 00123456789012 (facultatif)", qte: "22.5 ou 22.5 m³",
      aideAbonnement: "8 ou 9 chiffres (code district + numéro de police), en haut de la facture SONEDE.",
      aidePaiement: "12 à 16 chiffres, sur le talon en bas de la facture."
    }
  };
  const SANS_FOURNISSEUR = "Choisissez d'abord le fournisseur";
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

  /** Espaces retirés : « 93797 480 0 » → « 937974800 » (comme FactureController::normaliserReferences). */
  const sansEspaces = (value) => String(value).replace(/\s+/g, "");

  /** Même logique que FactureController::referencesErrors() : { champ: message }. */
  function referencesErrors(abonnement, paiement, type) {
    const errors = {};
    if (abonnement === "") errors.reference_abonnement = ABONNEMENT_VIDE;
    [["reference_abonnement", abonnement, "abonnement"], ["reference_paiement", paiement, "paiement"]].forEach(([champ, valeur, cle]) => {
      if (valeur === "" || errors[champ]) return;
      if (!type) {
        if (!new RegExp(`^\\d{1,${REFERENCE_MAX[champ]}}$`).test(valeur)) {
          errors[champ] = `Chiffres uniquement (${REFERENCE_MAX[champ]} au plus).`;
        }
        return;
      }
      const [min, max] = REFERENCES[type][cle];
      if (!new RegExp(`^\\d{${min},${max}}$`).test(valeur)) errors[champ] = REFERENCE_MESSAGES[type][champ];
    });
    return errors;
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
    const type = REFERENCES[v("type_ressource")] ? v("type_ressource") : "";
    const errors = referencesErrors(sansEspaces(v("reference_abonnement")), sansEspaces(v("reference_paiement")), type);
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

  /* Unité, placeholders et aides des références selon le fournisseur choisi. */
  function setupType(form) {
    // Par le NOM : dans le formulaire STEG/SONEDE, un champ caché porte name="type_ressource"
    // et un champ en lecture seule porte id="type_ressource" (form.elements renverrait les deux).
    const field = form.querySelector('[name="type_ressource"]');
    if (!field) return;
    const unit = form.querySelector("[data-bill-unit]");
    const abo = form.querySelector('[name="reference_abonnement"]');
    const paie = form.querySelector('[name="reference_paiement"]');
    const aboHint = form.querySelector("[data-bill-abo-hint]");
    const paieHint = form.querySelector("[data-bill-paie-hint]");
    const qte = form.querySelector('[name="quantite"]');
    const update = () => {
      const type = REFERENCES[field.value] ? field.value : "";
      const ex = type ? EXEMPLES[type] : null;
      if (unit) unit.textContent = type === "eau" ? "m³" : "kWh";
      if (abo) abo.placeholder = ex ? ex.abonnement : SANS_FOURNISSEUR;
      if (paie) paie.placeholder = ex ? ex.paiement : SANS_FOURNISSEUR;
      if (aboHint) aboHint.textContent = ex ? ex.aideAbonnement : "Choisissez le fournisseur pour voir le format attendu.";
      if (paieHint) paieHint.textContent = ex ? ex.aidePaiement : "Facultatif : talon en bas de la facture.";
      if (qte) qte.placeholder = `ex. ${type === "eau" ? EXEMPLES.eau.qte : EXEMPLES.electricite.qte}`;
    };
    field.addEventListener("change", update);
    update();
  }

  /* Pendant la frappe (ou un collage) : espaces et caractères non numériques retirés, longueur limitée. */
  function setupReferenceInputs(form) {
    Object.keys(REFERENCE_MAX).forEach((name) => {
      const el = form.querySelector(`[name="${name}"]`);
      if (!el) return;
      const filtrer = () => {
        const propre = el.value.replace(/\D+/g, "").slice(0, REFERENCE_MAX[name]);
        if (propre !== el.value) el.value = propre;
      };
      el.addEventListener("input", filtrer);
      filtrer();
    });
  }

  function setupBusinessChecks(form) {
    const api = window.WASLA_validate;
    if (!api) return;
    const submit = form.querySelector('[type="submit"]');
    // innerHTML (et non textContent) : garde l'icône du bouton quand on le remet en état
    const label = submit ? submit.innerHTML : "";
    const field = (name) => form.querySelector(`[name="${name}"]:not([type="hidden"])`) || form.querySelector(`[name="${name}"]`);
    const champs = ["reference_abonnement", "reference_paiement", "quantite", "montant", "periode_debut", "periode_fin", "date_echeance"];

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
        submit.innerHTML = label;
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
      setupReferenceInputs(form);
      setupBusinessChecks(form);
    });
  });

  // Réutilisé par la fenêtre « Ajouter une facture » de Factures & IA
  window.WASLA_factures = { businessErrors };
})();
