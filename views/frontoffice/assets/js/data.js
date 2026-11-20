/* ============================================================
   WASLA — Demonstration data
   All figures are illustrative and intended for the prototype.
   ============================================================ */
(function () {
  "use strict";

  const statuses = {
    normal: { label: "Normale", short: "Normale", className: "normal", color: "#00A875" },
    watch: { label: "En vigilance", short: "Vigilance", className: "watch", color: "#F5A800" },
    outage: { label: "Coupure", short: "Coupure", className: "outage", color: "#D92D20" },
    nodata: { label: "Sans données", short: "Sans données", className: "nodata", color: "#8A9AAA" }
  };

  const governorates = [
    { name: "Tunis", lon: 10.1815, lat: 36.8065, status: "normal", zones: 14, updated: "il y a 2 min" },
    { name: "Ariana", lon: 10.1934, lat: 36.8663, status: "normal", zones: 8, updated: "il y a 3 min" },
    { name: "Ben Arous", lon: 10.2189, lat: 36.7539, status: "normal", zones: 9, updated: "il y a 4 min" },
    { name: "Manouba", lon: 10.0863, lat: 36.8101, status: "normal", zones: 7, updated: "il y a 6 min" },
    { name: "Nabeul", lon: 10.7357, lat: 36.4513, status: "normal", zones: 11, updated: "il y a 5 min" },
    { name: "Bizerte", lon: 9.8739, lat: 37.2744, status: "normal", zones: 10, updated: "il y a 7 min" },
    { name: "Béja", lon: 9.1817, lat: 36.7256, status: "normal", zones: 8, updated: "il y a 9 min" },
    { name: "Jendouba", lon: 8.7802, lat: 36.5011, status: "normal", zones: 7, updated: "il y a 11 min" },
    { name: "El Kef", lon: 8.7049, lat: 36.1742, status: "normal", zones: 6, updated: "il y a 13 min" },
    { name: "Siliana", lon: 9.3708, lat: 36.0849, status: "normal", zones: 6, updated: "il y a 14 min" },
    { name: "Zaghouan", lon: 10.1429, lat: 36.4029, status: "normal", zones: 5, updated: "il y a 15 min" },
    { name: "Kairouan", lon: 10.0963, lat: 35.6781, status: "normal", zones: 9, updated: "il y a 17 min" },
    { name: "Kasserine", lon: 8.8365, lat: 35.1676, status: "watch", zones: 7, updated: "il y a 4 min" },
    { name: "Sidi Bouzid", lon: 9.4849, lat: 35.0382, status: "outage", zones: 6, updated: "il y a 8 min" },
    { name: "Sousse", lon: 10.6084, lat: 35.8256, status: "normal", zones: 12, updated: "il y a 2 min" },
    { name: "Monastir", lon: 10.8113, lat: 35.7643, status: "normal", zones: 8, updated: "il y a 3 min" },
    { name: "Mahdia", lon: 10.5029, lat: 35.5047, status: "normal", zones: 7, updated: "il y a 6 min" },
    { name: "Sfax", lon: 10.7603, lat: 34.7406, status: "normal", zones: 15, updated: "il y a 2 min" },
    { name: "Gafsa", lon: 8.7842, lat: 34.425, status: "watch", zones: 6, updated: "il y a 12 min" },
    { name: "Tozeur", lon: 8.1335, lat: 33.9197, status: "nodata", zones: 4, updated: "il y a 42 min" },
    { name: "Kébili", lon: 8.969, lat: 33.7044, status: "watch", zones: 5, updated: "il y a 10 min" },
    { name: "Gabès", lon: 10.0982, lat: 33.8815, status: "normal", zones: 8, updated: "il y a 5 min" },
    { name: "Médenine", lon: 10.5055, lat: 33.3549, status: "normal", zones: 7, updated: "il y a 7 min" },
    { name: "Tataouine", lon: 10.4518, lat: 32.9211, status: "normal", zones: 5, updated: "il y a 16 min" }
  ];

  const notifications = [
    {
      type: "warning",
      title: "Vigilance électricité à Kasserine",
      text: "La consommation approche du seuil critique dans 2 zones.",
      time: "il y a 12 min"
    },
    {
      type: "danger",
      title: "Coupure signalée à Sidi Bouzid",
      text: "Une intervention est en cours sur le réseau local.",
      time: "il y a 34 min"
    },
    {
      type: "success",
      title: "Réseau eau stable à Sfax",
      text: "Aucun incident majeur détecté aujourd'hui.",
      time: "il y a 1 h"
    }
  ];

  const features = [
    { icon: "zap", tone: "energy", text: "Suivre la consommation d'électricité" },
    { icon: "droplet", tone: "water", text: "Surveiller la consommation d'eau" },
    { icon: "map-pin", tone: "map", text: "Consulter la carte en temps réel" },
    { icon: "bell", tone: "alert", text: "Recevoir des alertes personnalisées" },
    { icon: "leaf", tone: "leaf", text: "Agir pour une Tunisie durable" }
  ];

  const services = [
    {
      id: "alertes",
      icon: "bell",
      tone: "green",
      title: "Alertes personnalisées",
      description:
        "Choisissez vos gouvernorats et vos seuils pour recevoir une notification avant chaque coupure ou pic de consommation.",
      points: ["Notifications push et e-mail", "Seuils personnalisables", "Historique des alertes"],
      link: "index.php?action=alertes"
    },
    {
      id: "factures",
      icon: "file-text",
      tone: "navy",
      title: "Factures & IA",
      description:
        "Retrouvez vos factures d'eau et d'électricité, suivez vos paiements et laissez l'IA détecter les anomalies de consommation.",
      points: ["Lecture assistée par IA", "Suivi des paiements", "Conseils d'économie"],
      link: "index.php?action=factures"
    },
    {
      id: "fournisseurs",
      icon: "wrench",
      tone: "amber",
      title: "Fournisseurs",
      description:
        "Consultez l'annuaire des fournisseurs agréés et trouvez un technicien proche de chez vous pour vos interventions.",
      points: ["Annuaire vérifié", "Recherche par gouvernorat", "Demande d'intervention"],
      link: "index.php?action=fournisseurs"
    },
    {
      id: "refuge",
      icon: "home",
      tone: "red",
      title: "Zones refuge",
      description:
        "Localisez les espaces climatisés et les points d'eau potable ouverts au public pendant les épisodes de coupure.",
      points: ["Points d'accueil cartographiés", "Horaires en temps réel", "Itinéraire accessible"],
      link: "index.php?action=refuges"
    },
    {
      id: "relais",
      icon: "snowflake",
      tone: "navy",
      title: "Relais de voisinage",
      description:
        "Partagez un réfrigérateur, une batterie ou un groupe électrogène avec vos voisins grâce au réseau solidaire WASLA.",
      points: ["Entraide de proximité", "Réservation simple", "Réseau de confiance"],
      link: "index.php?action=relais"
    },
    {
      id: "sante",
      icon: "heart-pulse",
      tone: "green",
      title: "Priorité santé",
      description:
        "Signalez les besoins vitaux (dialyse, oxygène, chaîne du froid) pour être priorisé lors des rétablissements de réseau.",
      points: ["Priorisation des urgences", "Contact direct", "Confidentialité des données"],
      link: "index.php?action=sante"
    }
  ];

  const searchIndex = [
    { title: "Accueil", description: "Vue d'ensemble des réseaux", url: "index.php?action=accueil", icon: "home" },
    { title: "Carte des réseaux", description: "Statut en temps réel par gouvernorat", url: "index.php?action=carte", icon: "map" },
    { title: "Analyses", description: "Consommation, tendances et coupures", url: "index.php?action=analyses", icon: "chart-line" },
    { title: "Services", description: "Alertes, factures, fournisseurs et entraide", url: "index.php?action=services", icon: "layers" },
    { title: "À propos", description: "Mission et engagements de WASLA", url: "index.php?action=a-propos", icon: "info" },
    { title: "Connexion", description: "Accéder à votre espace citoyen", url: "index.php?action=connexion", icon: "user" },
    { title: "Alertes personnalisées", description: "Recevoir les alertes de votre zone", url: "index.php?action=alertes", icon: "bell" },
    { title: "Factures & IA", description: "Suivre et analyser vos factures", url: "index.php?action=factures", icon: "file-text" },
    { title: "Fournisseurs", description: "Trouver un fournisseur agréé", url: "index.php?action=fournisseurs", icon: "wrench" },
    { title: "Zones refuge", description: "Points d'accueil pendant une coupure", url: "index.php?action=refuges", icon: "home" },
    { title: "Relais de voisinage", description: "Partager une ressource avec ses voisins", url: "index.php?action=relais", icon: "snowflake" },
    { title: "Priorité santé", description: "Signaler un besoin vital", url: "index.php?action=sante", icon: "heart-pulse" }
  ];

  const analytics = {
    periods: {
      "7j": {
        label: "7 derniers jours",
        labels: ["Lun", "Mar", "Mer", "Jeu", "Ven", "Sam", "Dim"],
        electricity: [72, 76, 74, 79, 81, 78, 83],
        water: [64, 66, 65, 69, 71, 70, 73]
      },
      "30j": {
        label: "30 derniers jours",
        labels: ["Sem. 1", "Sem. 2", "Sem. 3", "Sem. 4"],
        electricity: [74, 78, 76, 81],
        water: [66, 68, 67, 71]
      },
      "12m": {
        label: "12 derniers mois",
        labels: ["Jan", "Fév", "Mars", "Avr", "Mai", "Juin", "Juil", "Août", "Sep", "Oct", "Nov", "Déc"],
        electricity: [68, 70, 72, 75, 79, 84, 88, 90, 86, 81, 77, 74],
        water: [62, 64, 66, 69, 73, 78, 82, 84, 80, 75, 70, 67]
      }
    },
    byGovernorate: [
      { name: "Tunis", electricity: 94, water: 88 },
      { name: "Sfax", electricity: 87, water: 92 },
      { name: "Sousse", electricity: 78, water: 74 },
      { name: "Nabeul", electricity: 69, water: 71 },
      { name: "Bizerte", electricity: 61, water: 58 },
      { name: "Gabès", electricity: 54, water: 63 }
    ],
    outages: [
      { zone: "Sidi Bouzid — Zone 3", type: "Électricité", status: "outage", duration: "1 h 24", updated: "il y a 8 min" },
      { zone: "Kasserine — Zone 1", type: "Électricité", status: "watch", duration: "45 min", updated: "il y a 4 min" },
      { zone: "Kébili — Zone 2", type: "Eau", status: "watch", duration: "38 min", updated: "il y a 10 min" },
      { zone: "Tozeur — Zone 4", type: "Eau", status: "nodata", duration: "—", updated: "il y a 42 min" },
      { zone: "Sfax — Zone 7", type: "Électricité", status: "normal", duration: "12 min", updated: "il y a 2 min" }
    ]
  };

  const network = {
    electricity: {
      value: 98,
      status: "normal",
      label: "Réseau stable",
      series: [58, 62, 60, 66, 64, 70, 72, 69, 75, 78, 76, 82, 84, 81, 88, 91, 89, 95, 98]
    },
    water: {
      value: 95,
      status: "normal",
      label: "Réseau stable",
      series: [54, 58, 56, 61, 59, 64, 66, 63, 69, 72, 70, 76, 78, 75, 82, 85, 83, 90, 95]
    }
  };

  window.WASLA_DATA = {
    statuses,
    governorates,
    notifications,
    features,
    services,
    searchIndex,
    analytics,
    network
  };
})();
