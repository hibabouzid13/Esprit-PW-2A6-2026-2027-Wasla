/* =====================================================================
   WASLA — contrôle de saisie côté navigateur (Front Office ET Back Office)
   ---------------------------------------------------------------------
   Consigne du projet : AUCUNE validation HTML5 (pas de required, type="email",
   min, max, pattern…). Tout est vérifié ici en JavaScript, puis de nouveau
   en PHP par Controller::validate() avec les MÊMES noms de règles.

   Utilisation dans une vue :

     <form method="post" action="…" data-validate>
       <div class="field">
         <label for="f-email">Adresse e-mail</label>
         <input class="input" id="f-email" name="email" type="text"
                data-rules="required|email">
         <p class="field__error" data-error-for="email"></p>
       </div>
     </form>

   Règles : required, email, name, phone, password, min:N, max:N, match:champ,
            in:a,b,c, int, decimal, positive, date, after:champ, accepted
   Message personnalisé (facultatif) : data-message-required="Indiquez le montant."
   ===================================================================== */
(function () {
  "use strict";

  const MESSAGES = {
    required: "Ce champ est obligatoire.",
    email: "Veuillez saisir une adresse e-mail valide.",
    name: "Lettres, espaces, apostrophes et tirets uniquement (2 à 50 caractères).",
    phone: "Numéro tunisien à 8 chiffres attendu (ex. 22 123 456).",
    password: "Au moins 8 caractères, dont une lettre et un chiffre.",
    espace: "Le mot de passe ne doit pas commencer ni finir par un espace.",
    min: (n) => `Au moins ${n} caractères.`,
    max: (n) => `Au plus ${n} caractères.`,
    match: "Les deux valeurs ne correspondent pas.",
    in: "Valeur non autorisée.",
    int: "Nombre entier attendu.",
    decimal: "Nombre attendu (ex. 45,500).",
    positive: "La valeur doit être supérieure à 0.",
    date: "Date invalide (format AAAA-MM-JJ).",
    after: "Cette date doit être postérieure à la précédente.",
    accepted: "Veuillez cocher cette case."
  };

  /* Exactement le même motif que Controller::EMAIL (PHP). */
  const EMAIL = /^(?=[^@]{1,64}@)[A-Za-z0-9!#$%&'*+\/=?^_`{|}~-]+(\.[A-Za-z0-9!#$%&'*+\/=?^_`{|}~-]+)*@([A-Za-z0-9]([A-Za-z0-9-]{0,61}[A-Za-z0-9])?\.)+[A-Za-z]{2,63}$/;

  function isDate(value) {
    if (!/^\d{4}-\d{2}-\d{2}$/.test(value)) return false;
    const [y, m, d] = value.split("-").map(Number);
    const date = new Date(y, m - 1, d);
    return date.getFullYear() === y && date.getMonth() === m - 1 && date.getDate() === d;
  }

  /** Valeur d'un champ (gère cases à cocher et boutons radio). */
  function valueOf(form, field) {
    if (field.type === "checkbox") return field.checked ? "1" : "";
    if (field.type === "radio") {
      const checked = form.querySelector(`[name="${field.name}"]:checked`);
      return checked ? checked.value : "";
    }
    return (field.value || "").trim();
  }

  /** Retourne le message d'erreur du champ, ou "" s'il est valide. */
  function check(form, field) {
    const value = valueOf(form, field);
    const rules = (field.dataset.rules || "").split("|").filter(Boolean);

    for (const rule of rules) {
      const sep = rule.indexOf(":");
      const name = sep === -1 ? rule : rule.slice(0, sep);
      const param = sep === -1 ? "" : rule.slice(sep + 1);

      if (value === "" && name !== "required" && name !== "accepted") continue;

      let ok = true;
      let regle = name;   // message à afficher
      switch (name) {
        case "required": ok = value !== ""; break;
        case "email": ok = EMAIL.test(value) && value.length <= 150; break;
        case "name": ok = /^\p{L}[\p{L}' -]{1,49}$/u.test(value); break;
        case "phone": ok = /^(\+216)?[2-9]\d{7}$/.test(value.replace(/[\s.-]/g, "")); break;
        case "password":
          ok = value.length >= 8 && /[A-Za-z]/.test(value) && /\d/.test(value);
          if (ok && (/^\s/.test(field.value) || /\s$/.test(field.value))) { ok = false; regle = "espace"; }
          break;
        case "min": ok = value.length >= Number(param); break;
        case "max": ok = value.length <= Number(param); break;
        case "match": {
          const other = form.elements[param];
          ok = !other || field.value === other.value;   // valeurs brutes, comme en PHP
          break;
        }
        case "in": ok = param.split(",").includes(value); break;
        case "int": ok = /^-?\d+$/.test(value); break;
        case "decimal": ok = /^-?\d+([.,]\d{1,3})?$/.test(value); break;
        case "positive": ok = parseFloat(value.replace(",", ".")) > 0; break;
        case "date": ok = isDate(value); break;
        case "after": {
          const other = form.elements[param];
          const otherValue = other ? (other.value || "").trim() : "";
          ok = otherValue === "" || new Date(value) > new Date(otherValue);
          break;
        }
        case "accepted": ok = value === "1"; break;
        default: ok = true;
      }

      if (!ok) {
        const custom = field.dataset["message" + regle.charAt(0).toUpperCase() + regle.slice(1)];
        const base = MESSAGES[regle];
        return custom || (typeof base === "function" ? base(param) : base);
      }
    }
    return "";
  }

  /** Affiche ou efface l'erreur (style Front Office .field ou Back Office .admin-field). */
  function show(form, field, message) {
    field.classList.toggle("is-invalid", Boolean(message));
    field.setAttribute("aria-invalid", message ? "true" : "false");

    const out = form.querySelector(`[data-error-for="${field.name}"]`);
    if (out) {
      out.textContent = message;
      out.classList.toggle("is-visible", Boolean(message));
    }
    const adminWrap = field.closest(".admin-field");
    if (adminWrap) {
      adminWrap.classList.toggle("has-error", Boolean(message));
      const adminOut = adminWrap.querySelector(".admin-field__error");
      if (adminOut && !out) adminOut.textContent = message;
    }
  }

  function toast(message) {
    if (window.WASLA_toast) window.WASLA_toast(message);
    else if (window.WASLA_adminToast) window.WASLA_adminToast(message);
  }

  function setup(form) {
    const fields = Array.from(form.querySelectorAll("[data-rules]"));

    fields.forEach((field) => {
      const live = field.type === "checkbox" || field.type === "radio" || field.tagName === "SELECT" ? "change" : "blur";
      field.addEventListener(live, () => show(form, field, check(form, field)));
      // Dès que l'utilisateur corrige, on retire l'erreur
      field.addEventListener("input", () => {
        if (field.classList.contains("is-invalid") && !check(form, field)) show(form, field, "");
      });
    });

    form.addEventListener("submit", (event) => {
      let first = null;
      const seen = new Set();
      fields.forEach((field) => {
        if (field.type === "radio") {
          if (seen.has(field.name)) return;
          seen.add(field.name);
        }
        const message = check(form, field);
        show(form, field, message);
        if (message && !first) first = field;
      });

      if (first) {
        event.preventDefault();
        first.focus();
        toast("Veuillez corriger les champs indiqués.");
        return;
      }
      // Formulaire valide : il part vers le serveur, qui revérifie tout en PHP.
      const submit = form.querySelector('[type="submit"]');
      if (submit && submit.dataset.loading) {
        submit.disabled = true;
        submit.textContent = submit.dataset.loading;
      }
    });
  }

  /** Bouton « œil » pour afficher / masquer un mot de passe. */
  function setupPasswordToggles() {
    document.querySelectorAll("[data-toggle-password]").forEach((button) => {
      const input = button.parentElement.querySelector("input");
      if (!input) return;
      button.addEventListener("click", () => {
        const showing = input.type === "text";
        input.type = showing ? "password" : "text";
        if (window.WASLA_icon) button.innerHTML = window.WASLA_icon(showing ? "eye" : "eye-off");
        button.setAttribute("aria-label", showing ? "Afficher le mot de passe" : "Masquer le mot de passe");
      });
    });
  }

  /** Demande une confirmation avant une action dangereuse : <form data-confirm="Supprimer ?"> */
  function setupConfirms() {
    document.querySelectorAll("form[data-confirm]").forEach((form) => {
      form.addEventListener("submit", (event) => {
        if (!window.confirm(form.dataset.confirm)) event.preventDefault();
      });
    });
  }

  document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll("form[data-validate]").forEach(setup);
    setupPasswordToggles();
    setupConfirms();
  });

  window.WASLA_validate = { check, show };
})();
