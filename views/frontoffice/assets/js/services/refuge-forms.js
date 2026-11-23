/*
 * services/refuge-forms.js — Module 3 (Louay)
 * Règles de saisie propres au module, vérifiées dans le navigateur EN PLUS de validation.js
 * (fichier commun, non modifié). Les mêmes règles sont revérifiées en PHP et les messages
 * viennent des constantes MESSAGES des contrôleurs (attributs data-message-*).
 *
 *   data-extra-rules="matricule"         7 chiffres + 1 lettre, éventuellement /A/M/000 (espaces ignorés)
 *   data-extra-rules="max-value:1000"    nombre ≤ 1000
 *   data-extra-rules="min-value:12"      nombre ≥ 12 (ex. places déjà réservées)
 *   <div data-check-group="services[]" data-error-name="services" data-message-required="…">
 *                                        au moins une case cochée dans le groupe
 *   <form data-confirm-apres="Supprimer ?">
 *                                        confirmation demandée SEULEMENT si le formulaire est valide
 *                                        (data-confirm de validation.js s'ouvre avant la vérification)
 *
 * Chargé avant validation.js : on s'installe APRÈS lui (setTimeout) pour que nos écouteurs
 * passent après les siens et ne remplacent pas ses messages.
 */
(function () {
  "use strict";

  const MATRICULE = /^\d{7}[A-Z](\/[A-Z]\/[A-Z]\/\d{3})?$/;

  /** Message de la règle : attribut data-message-<règle> écrit par PHP (même texte qu'en PHP). */
  function messageFor(el, rule, fallback) {
    const key = "message" + rule.split("-").map((p) => p.charAt(0).toUpperCase() + p.slice(1)).join("");
    return el.dataset[key] || fallback;
  }

  /** Erreur des règles supplémentaires d'un champ, ou "" s'il est valide. */
  function checkExtra(field) {
    const value = (field.value || "").trim();
    if (value === "") return "";               // champ vide : géré par « required » dans validation.js
    for (const rule of (field.dataset.extraRules || "").split("|").filter(Boolean)) {
      const [name, param] = rule.split(":");
      let ok = true;
      if (name === "matricule") ok = MATRICULE.test(value.replace(/\s+/g, "").toUpperCase());
      if (name === "max-value") ok = !/^-?\d+$/.test(value) || Number(value) <= Number(param);
      if (name === "min-value") ok = !/^-?\d+$/.test(value) || Number(value) >= Number(param);
      if (!ok) return messageFor(field, name, "Valeur non autorisée.");
    }
    return "";
  }

  function showExtra(form, field, message) {
    if (window.WASLA_validate) window.WASLA_validate.show(form, field, message);
  }

  /** Erreur affichée par validation.js (ses règles passent en premier). */
  function hasBaseError(form, field) {
    return window.WASLA_validate ? window.WASLA_validate.check(form, field) !== "" : false;
  }

  function checkGroup(group) {
    const boxes = group.querySelectorAll(`input[name="${group.dataset.checkGroup}"]`);
    return Array.from(boxes).some((b) => b.checked) ? "" : messageFor(group, "required", "Ce champ est obligatoire.");
  }

  function showGroup(form, group, message) {
    const out = form.querySelector(`[data-error-for="${group.dataset.errorName}"]`);
    if (!out) return;
    out.textContent = message;
    out.classList.toggle("is-visible", Boolean(message));
  }

  function setup(form) {
    const fields = Array.from(form.querySelectorAll("[data-extra-rules]"));
    const groups = Array.from(form.querySelectorAll("[data-check-group]"));
    const confirmApres = form.dataset.confirmApres || "";
    if (fields.length === 0 && groups.length === 0 && confirmApres === "") return;

    fields.forEach((field) => {
      const run = () => { if (!hasBaseError(form, field)) showExtra(form, field, checkExtra(field)); };
      field.addEventListener("blur", run);
      field.addEventListener("input", () => { if (field.classList.contains("is-invalid")) run(); });
    });
    groups.forEach((group) => group.addEventListener("change", () => showGroup(form, group, checkGroup(group))));

    form.addEventListener("submit", (event) => {
      const already = event.defaultPrevented;   // validation.js a déjà trouvé une erreur
      let first = null;
      fields.forEach((field) => {
        if (hasBaseError(form, field)) return;   // validation.js affiche déjà son message
        const message = checkExtra(field);
        showExtra(form, field, message);
        if (message && !first) first = field;
      });
      groups.forEach((group) => {
        const message = checkGroup(group);
        showGroup(form, group, message);
        if (message && !first) first = group.querySelector("input");
      });
      if (!first) {
        // Formulaire valide (ni validation.js ni nos règles n'ont bloqué) : on confirme maintenant
        if (!already && confirmApres !== "" && !window.confirm(confirmApres)) {
          event.preventDefault();
          restoreSubmit();
        }
        return;
      }

      event.preventDefault();
      restoreSubmit();
      if (!already) {
        first.focus();
        const toast = window.WASLA_toast || window.WASLA_adminToast;
        if (toast) toast("Veuillez corriger les champs indiqués.");
      }
    });

    // validation.js a peut-être déjà désactivé le bouton (« Enregistrement… ») : on le rétablit
    function restoreSubmit() {
      if (submit && submit.disabled && submit.dataset.label) {
        submit.disabled = false;
        submit.textContent = submit.dataset.label;
      }
    }

    // Texte d'origine du bouton, pour le rétablir si l'envoi est bloqué
    const submit = form.querySelector('[type="submit"]');
    if (submit) submit.dataset.label = submit.textContent;
  }

  document.addEventListener("DOMContentLoaded", () => {
    // validation.js installe ses écouteurs au même événement, mais il est chargé après ce fichier
    setTimeout(() => document.querySelectorAll("form[data-validate]").forEach(setup), 0);
  });
})();
