/* =====================================================================
   WASLA — Module 2 (Hiba) : comptes citoyens (Front Office et Back Office)
   ---------------------------------------------------------------------
   Complète validation.js avec les règles de CitoyenController :
   - e-mail au même format qu'en PHP (motif reçu dans data-motif) ;
   - mot de passe sans espace au début ni à la fin, confirmation identique au caractère près ;
   - mot de passe actuel demandé si l'adresse e-mail change ;
   - confirmation de la suppression, seulement quand le formulaire est valide.
   Les messages viennent de PHP (attributs data-*). Aucune validation HTML5.
   ===================================================================== */
(function () {
  "use strict";

  /** Message propre au module pour un champ, ou "" s'il est valide. */
  function erreurPropre(form, champ) {
    const brut = champ.value;
    const valeur = brut.trim();
    if (champ.dataset.motif && valeur && !new RegExp(champ.dataset.motif).test(valeur)) return champ.dataset.messageMotif;
    if (champ.dataset.messageEspace && brut && (/^\s/.test(brut) || /\s$/.test(brut))) return champ.dataset.messageEspace;
    if (champ.name === "password_confirm" && brut !== form.querySelector('[name="password"]').value) return champ.dataset.messageMatch;
    if (champ.dataset.siEmailChange !== undefined && brut === "" && emailChange(form)) return champ.dataset.messageRequired;
    return "";
  }

  function emailChange(form) {
    const email = form.querySelector('[name="email"][data-actuel]');
    return Boolean(email) && email.value.trim().toLowerCase() !== email.dataset.actuel.toLowerCase();
  }

  function setup(form) {
    const api = window.WASLA_validate;
    if (!api) return;
    const champs = Array.from(form.querySelectorAll("[data-motif], [data-message-espace], [name='password_confirm'], [data-si-email-change]"));
    const submit = form.querySelector('[type="submit"]');
    // innerHTML (et non textContent) : garde l'icône du bouton quand on le remet en état
    const libelle = submit ? submit.innerHTML : "";

    const controler = (champ) => {
      if (api.check(form, champ)) return true;        // erreur de base déjà affichée par validation.js
      const message = erreurPropre(form, champ);
      api.show(form, champ, message);
      return Boolean(message);
    };
    champs.forEach((champ) => {
      champ.addEventListener("blur", () => { if (champ.value) controler(champ); });
      champ.addEventListener("input", () => {
        if (champ.classList.contains("is-invalid") && !api.check(form, champ) && !erreurPropre(form, champ)) api.show(form, champ, "");
      });
    });

    // Mot de passe demandé seulement si l'e-mail change (sans JavaScript, le champ reste visible)
    const zone = form.querySelector("[data-zone-email-change]");
    if (zone) {
      const afficher = () => { zone.hidden = !emailChange(form) && !zone.querySelector(".is-invalid"); };
      form.querySelector('[name="email"]').addEventListener("input", afficher);
      afficher();
    }

    form.addEventListener("submit", (event) => {
      const dejaBloque = event.defaultPrevented;      // validation.js a trouvé une erreur de base
      const fautifs = champs.filter(controler);
      if (fautifs.length) {
        event.preventDefault();
        if (dejaBloque) return;                       // il a déjà placé le curseur et affiché le message
        fautifs[0].focus();
        const toast = window.WASLA_toast || window.WASLA_adminToast;
        if (toast) toast("Veuillez corriger les champs indiqués.");
      } else if (!dejaBloque && form.dataset.confirmApres && !window.confirm(form.dataset.confirmApres)) {
        event.preventDefault();
      }
      if (!dejaBloque && event.defaultPrevented && submit) {   // validation.js l'avait mis en « Enregistrement… »
        submit.disabled = false;
        submit.innerHTML = libelle;
      }
    });
  }

  // « load » arrive après DOMContentLoaded : ces contrôles passent APRÈS ceux de validation.js
  window.addEventListener("load", () => {
    document.querySelectorAll("form[data-citoyen-form]").forEach(setup);
  });
})();
