/* ============================================================
   WASLA — Module 4 (Assistance) : petits comportements des formulaires
   - [data-open-on-load]       rouvre la fenêtre si PHP a renvoyé des erreurs
   - [data-post-sens]          bouton qui ouvre la publication avec le bon type
   - [data-pick-fournisseur]   bouton qui présélectionne le professionnel
   - [data-sens-form]          affiche « urgence » pour une demande, « disponibilité » pour une offre
   Les fenêtres s'ouvrent avec data-svc-open (services/core.js) ;
   la validation reste dans validation.js (data-validate / data-rules) et en PHP.
   ============================================================ */
(function () {
  "use strict";

  const S = window.WASLA_SVC || {};

  function syncSens(form) {
    const checked = form.querySelector('input[name="sens"]:checked');
    const sens = checked ? checked.value : "demande";
    form.querySelectorAll("[data-when-sens]").forEach((block) => {
      block.hidden = block.getAttribute("data-when-sens") !== sens;
    });
  }

  document.addEventListener("click", (event) => {
    const sensButton = event.target.closest("[data-post-sens]");
    if (sensButton) {
      const form = document.querySelector("#post-modal form");
      const radio = form && form.querySelector(`input[name="sens"][value="${sensButton.getAttribute("data-post-sens")}"]`);
      if (radio) {
        radio.checked = true;
        syncSens(form);
      }
    }
    const pick = event.target.closest("[data-pick-fournisseur]");
    if (pick) {
      const select = document.getElementById("req-pro");
      if (select) select.value = pick.getAttribute("data-pick-fournisseur");
    }
  });

  document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll("form[data-sens-form]").forEach((form) => {
      syncSens(form);
      form.addEventListener("change", (event) => {
        if (event.target.name === "sens") syncSens(form);
      });
    });

    const toOpen = document.querySelector("[data-open-on-load]");
    if (toOpen && S.openModal) S.openModal(toOpen);

    // Une réponse renvoyée avec des erreurs : on fait défiler jusqu'à elle
    const openReply = document.querySelector("details.asst-reply-box[open]");
    if (openReply) openReply.scrollIntoView({ block: "center" });
  });
})();
