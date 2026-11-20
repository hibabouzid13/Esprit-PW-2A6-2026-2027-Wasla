/* ============================================================
   WASLA — Données de démonstration des six services citoyens
   ------------------------------------------------------------
   TOUTES les données de ce fichier sont FICTIVES : personnes,
   professionnels, établissements, numéros, adresses, montants
   et disponibilités servent uniquement au prototype.
   Elles sont lues exclusivement via WASLA_SVC.repo() (core.js) :
   pour brancher le futur backend, il suffira de remplacer les
   « seed » ci-dessous par des appels à l'API PHP.
   ============================================================ */
(function () {
  "use strict";

  const now = Date.now();
  const ago = (minutes) => new Date(now - minutes * 60000).toISOString();
  const inDays = (days) => new Date(now + days * 86400000).toISOString();

  /* ---------- Libellés partagés ---------- */

  const alertCategories = {
    eau: { label: "Coupure d'eau", icon: "droplet", tone: "blue" },
    electricite: { label: "Coupure d'électricité", icon: "zap", tone: "green" },
    distribution: { label: "Perturbation de distribution", icon: "activity", tone: "amber" },
    chaleur: { label: "Fortes chaleurs", icon: "thermometer", tone: "red" },
    penurie: { label: "Risque de pénurie", icon: "triangle-alert", tone: "amber" },
    incident: { label: "Incident local", icon: "circle-alert", tone: "gray" }
  };

  const severities = {
    critique: { label: "Critique", badge: "outage", rank: 4 },
    elevee: { label: "Élevée", badge: "watch", rank: 3 },
    moderee: { label: "Modérée", badge: "nodata", rank: 2 },
    info: { label: "Information", badge: "normal", rank: 1 }
  };

  const serviceLabels = {
    eau: "Eau (SONEDE)",
    electricite: "Électricité (STEG)",
    mixte: "Eau et électricité",
    autre: "Tous services"
  };

  /* ---------- 01 · Alertes personnalisées ---------- */

  const alerts = [
    {
      id: "al-1", category: "electricite", severity: "critique", service: "electricite", active: true,
      title: "Coupure d'électricité en cours à Sidi Bouzid",
      text: "Incident sur le poste de transformation de Cité Ennour. Les équipes STEG sont sur place ; rétablissement estimé vers 18h30. Pensez à débrancher les appareils sensibles.",
      gov: "Sidi Bouzid", area: "Cité Ennour", date: ago(38)
    },
    {
      id: "al-2", category: "eau", severity: "elevee", service: "eau", active: true,
      title: "Coupure d'eau programmée à El Menzah 6",
      text: "Travaux de raccordement sur la conduite principale. L'eau sera coupée demain de 08h00 à 14h00. Prévoyez une réserve d'eau potable.",
      gov: "Tunis", area: "El Menzah 6", date: ago(95)
    },
    {
      id: "al-3", category: "chaleur", severity: "elevee", service: "autre", active: true,
      title: "Vigilance fortes chaleurs : 41 °C attendus",
      text: "Températures élevées prévues cet après-midi dans le centre-ouest. Limitez l'usage simultané des climatiseurs entre 13h et 17h et hydratez-vous régulièrement.",
      gov: "Kasserine", area: "Centre-ville", date: ago(160)
    },
    {
      id: "al-4", category: "distribution", severity: "moderee", service: "eau", active: true,
      title: "Baisse de pression de l'eau à Ennasr 2",
      text: "Une baisse de pression est constatée aux étages supérieurs suite à une forte demande. La situation devrait revenir à la normale dans la soirée.",
      gov: "Ariana", area: "Ennasr 2", date: ago(240)
    },
    {
      id: "al-5", category: "penurie", severity: "elevee", service: "eau", active: true,
      title: "Risque de pénurie d'eau : rationnement possible",
      text: "Le niveau du réservoir de Gafsa-Nord est bas. Des coupures tournantes nocturnes pourraient être organisées cette semaine. Merci de limiter les usages non essentiels.",
      gov: "Gafsa", area: "Gafsa-Nord", date: ago(420)
    },
    {
      id: "al-6", category: "incident", severity: "moderee", service: "electricite", active: true,
      title: "Câble endommagé signalé avenue de la Liberté",
      text: "Un câble basse tension a été endommagé par des travaux. Le périmètre est sécurisé, des micro-coupures sont possibles dans le quartier.",
      gov: "Tunis", area: "Lafayette", date: ago(610)
    },
    {
      id: "al-7", category: "electricite", severity: "info", service: "electricite", active: false,
      title: "Électricité rétablie à Ennasr 1",
      text: "La coupure signalée ce matin est terminée. Le réseau fonctionne normalement. Merci pour votre patience.",
      gov: "Ariana", area: "Ennasr 1", date: ago(900)
    },
    {
      id: "al-8", category: "eau", severity: "critique", service: "eau", active: true,
      title: "Coupure d'eau imprévue à Kébili Sud",
      text: "Casse sur une canalisation principale. Des citernes sont mises à disposition devant la municipalité de 10h à 18h.",
      gov: "Kébili", area: "Kébili Sud", date: ago(1300)
    },
    {
      id: "al-9", category: "distribution", severity: "info", service: "electricite", active: false,
      title: "Maintenance préventive terminée à Sousse",
      text: "Les travaux de maintenance du réseau moyenne tension se sont terminés plus tôt que prévu.",
      gov: "Sousse", area: "Sahloul", date: ago(1800)
    },
    {
      id: "al-10", category: "chaleur", severity: "moderee", service: "autre", active: false,
      title: "Épisode de chaleur terminé à Tunis",
      text: "Les températures redeviennent normales pour la saison. Les zones refuge climatisées reprennent leurs horaires habituels.",
      gov: "Tunis", area: "Tunis Centre", date: ago(2900)
    },
    {
      id: "al-11", category: "electricite", severity: "elevee", service: "electricite", active: true,
      title: "Coupures tournantes possibles à Gafsa",
      text: "En raison d'une forte demande, des délestages d'une heure pourraient avoir lieu entre 19h et 22h. Le planning sera communiqué par quartier.",
      gov: "Gafsa", area: "Cité Ouvrière", date: ago(3600)
    },
    {
      id: "al-12", category: "incident", severity: "info", service: "mixte", active: false,
      title: "Exercice de sécurité au poste de Ben Arous",
      text: "Un exercice de sécurité a eu lieu au poste de distribution. Aucune coupure n'a été constatée.",
      gov: "Ben Arous", area: "Mégrine", date: ago(5200)
    }
  ];

  const defaultPlaces = [
    { id: "pl-1", kind: "domicile", label: "Domicile", gov: "Tunis", area: "El Menzah 6", enabled: true },
    { id: "pl-2", kind: "travail", label: "Travail", gov: "Ariana", area: "Ennasr 2", enabled: true },
    { id: "pl-3", kind: "proche", label: "Chez ma mère", gov: "Sidi Bouzid", area: "Cité Ennour", enabled: true }
  ];

  const defaultAlertPrefs = {
    water: true,
    electricity: true,
    categories: ["eau", "electricite", "distribution", "chaleur", "penurie", "incident"],
    minSeverity: "info",
    channels: { site: true, email: false, sms: false },
    frequency: "immediate",
    onlyMyPlaces: true
  };

  /* ---------- 02 · Factures & IA ---------- */

  // Relevés mensuels du compteur (12 derniers mois, du plus ancien au plus récent).
  // Le dernier relevé correspond au dernier mois complet (le mois en cours n'est pas encore relevé).
  const monthlyElectricity = [240, 268, 290, 272, 232, 206, 218, 300, 372, 395, 318, 262]; // kWh
  const monthlyWater = [13, 12, 12, 12, 13, 14, 16, 19, 22, 23, 18, 15]; // m³

  const monthStart = (offset) => {
    const d = new Date();
    d.setDate(1);
    d.setHours(12, 0, 0, 0);
    d.setMonth(d.getMonth() - offset);
    return d;
  };

  const months = monthlyElectricity.map((_, i) => monthStart(12 - i).toISOString());

  // Tarifs indicatifs (simplifiés) — ne correspondent pas à une grille officielle.
  const tariffs = {
    electricite: { unit: "kWh", price: 0.196, fixed: 4.6, taxRate: 0.13, extraLabel: "Contribution audiovisuelle et municipale", extra: 1.5 },
    eau: { unit: "m³", price: 0.82, fixed: 3.9, taxRate: 0.07, extraLabel: "Redevance assainissement (ONAS)", extra: 2.4 }
  };

  function billAmount(type, quantity) {
    const t = tariffs[type];
    const base = quantity * t.price + t.fixed + t.extra;
    return Math.round(base * (1 + t.taxRate) * 1000) / 1000;
  }

  function makeBill(type, startIndex, length, number, status) {
    const series = type === "electricite" ? monthlyElectricity : monthlyWater;
    const quantity = series.slice(startIndex, startIndex + length).reduce((a, b) => a + b, 0);
    const start = new Date(months[startIndex]);
    const end = new Date(months[startIndex + length - 1]);
    end.setMonth(end.getMonth() + 1);
    end.setDate(0);
    const due = new Date(end);
    due.setDate(due.getDate() + 20);
    return {
      id: `bill-${type}-${number}`,
      provider: type === "electricite" ? "STEG" : "SONEDE",
      type,
      reference: (type === "electricite" ? "STG-" : "SND-") + String(482301 + number * 37),
      periodStart: start.toISOString(),
      periodEnd: end.toISOString(),
      quantity,
      amount: billAmount(type, quantity),
      dueDate: due.toISOString(),
      status,
      source: "demo"
    };
  }

  // STEG : facture bimestrielle · SONEDE : facture trimestrielle.
  // La dernière facture STEG (2 derniers mois) n'est volontairement pas incluse :
  // c'est celle que l'on peut « importer » pour tester la lecture simulée.
  const bills = [
    makeBill("electricite", 0, 2, 1, "payee"),
    makeBill("electricite", 2, 2, 2, "payee"),
    makeBill("electricite", 4, 2, 3, "payee"),
    makeBill("electricite", 6, 2, 4, "payee"),
    makeBill("electricite", 8, 2, 5, "payee"),
    makeBill("eau", 0, 3, 1, "payee"),
    makeBill("eau", 3, 3, 2, "payee"),
    makeBill("eau", 6, 3, 3, "payee"),
    makeBill("eau", 9, 3, 4, "a-payer")
  ];

  /* ---------- 03 · Fournisseurs ---------- */

  const specialties = {
    electricien: { label: "Électricien", plural: "Électriciens", icon: "zap", avatar: "green" },
    plombier: { label: "Plombier", plural: "Plombiers", icon: "droplet", avatar: "water" },
    maintenance: { label: "Technicien de maintenance", plural: "Techniciens de maintenance", icon: "wrench", avatar: "blue" },
    solaire: { label: "Installateur solaire", plural: "Installateurs solaires", icon: "solar-panel", avatar: "amber" },
    hydraulique: { label: "Spécialiste hydraulique", plural: "Spécialistes hydrauliques", icon: "activity", avatar: "navy" }
  };

  const availability = {
    now: { label: "Disponible aujourd'hui", badge: "normal" },
    week: { label: "Disponible cette semaine", badge: "watch" },
    busy: { label: "Complet ce mois-ci", badge: "nodata" }
  };

  const review = (author, rating, text, daysAgo) => ({ author, rating, text, date: ago(daysAgo * 1440) });

  const providers = [
    {
      id: "pro-1", name: "Karim Ben Salah", specialty: "electricien", gov: "Tunis", area: "El Menzah", rating: 4.8, reviewsCount: 64,
      available: "now", verified: true, years: 12,
      presentation: "Électricien agréé, spécialisé dans la remise en service après coupure, la mise aux normes des tableaux et la protection des appareils contre les surtensions.",
      skills: ["Tableaux électriques", "Mise à la terre", "Parafoudres", "Dépannage urgent"],
      experiences: ["Chef d'équipe dans une entreprise d'électricité générale (8 ans)", "Artisan indépendant depuis 2019"],
      certifications: ["Habilitation électrique BR (démo)", "Agrément de démonstration WASLA"],
      zones: ["Tunis", "Ariana", "Manouba"],
      tariffs: [{ label: "Déplacement + diagnostic", price: "30 TND" }, { label: "Remplacement de disjoncteur", price: "45 – 70 TND" }, { label: "Mise aux normes d'un tableau", price: "à partir de 350 TND" }],
      reviews: [review("Sonia M.", 5, "Intervention rapide après la coupure, tableau remis en état en une heure. Très professionnel.", 6), review("Hatem R.", 5, "Explications claires et devis respecté.", 21), review("Ines K.", 4, "Bon travail, un peu de retard à l'arrivée.", 44)],
      phone: "+216 20 000 101", email: "karim.bensalah@exemple.tn"
    },
    {
      id: "pro-2", name: "Leila Trabelsi", specialty: "plombier", gov: "Ariana", area: "Ennasr", rating: 4.9, reviewsCount: 41,
      available: "now", verified: true, years: 9,
      presentation: "Plombière diplômée : recherche de fuites, réservoirs et surpresseurs, raccordement des citernes d'eau potable.",
      skills: ["Détection de fuites", "Surpresseurs", "Citernes", "Chauffe-eau"],
      experiences: ["Technicienne dans un bureau d'études hydrauliques (4 ans)", "Plombière indépendante depuis 2020"],
      certifications: ["Brevet de technicien supérieur en plomberie (démo)"],
      zones: ["Ariana", "Tunis", "Bizerte"],
      tariffs: [{ label: "Déplacement + diagnostic", price: "25 TND" }, { label: "Réparation de fuite simple", price: "40 – 80 TND" }, { label: "Installation d'un surpresseur", price: "à partir de 180 TND" }],
      reviews: [review("Mourad B.", 5, "A trouvé une fuite que deux autres n'avaient pas vue. Je recommande.", 3), review("Asma H.", 5, "Ponctuelle et soigneuse.", 30)],
      phone: "+216 20 000 102", email: "leila.trabelsi@exemple.tn"
    },
    {
      id: "pro-3", name: "Sami Gharbi", specialty: "solaire", gov: "Sousse", area: "Sahloul", rating: 4.7, reviewsCount: 38,
      available: "week", verified: true, years: 7,
      presentation: "Installateur photovoltaïque : kits autonomes avec batteries pour garder l'essentiel allumé pendant les coupures, et installations raccordées au réseau.",
      skills: ["Photovoltaïque", "Batteries lithium", "Onduleurs hybrides", "Dimensionnement"],
      experiences: ["Installateur dans une société solaire (5 ans)", "Plus de 120 installations résidentielles"],
      certifications: ["Formation photovoltaïque (démo)", "Partenaire de démonstration WASLA"],
      zones: ["Sousse", "Monastir", "Mahdia"],
      tariffs: [{ label: "Étude et dimensionnement", price: "gratuit" }, { label: "Kit de secours 1 kWh", price: "à partir de 1 900 TND" }, { label: "Installation 3 kWc", price: "sur devis" }],
      reviews: [review("Nabil S.", 5, "Notre frigo et la box internet tiennent toute la coupure.", 12), review("Rim A.", 4, "Bonne installation, délai un peu long.", 60)],
      phone: "+216 20 000 103", email: "sami.gharbi@exemple.tn"
    },
    {
      id: "pro-4", name: "Hichem Jaziri", specialty: "maintenance", gov: "Sfax", area: "Sfax Ville", rating: 4.5, reviewsCount: 27,
      available: "now", verified: true, years: 15,
      presentation: "Maintenance des groupes électrogènes, onduleurs et équipements électriques des commerces et immeubles.",
      skills: ["Groupes électrogènes", "Onduleurs", "Contrats d'entretien", "Dépannage 7j/7"],
      experiences: ["Technicien de maintenance industrielle (10 ans)"],
      certifications: ["Certificat de technicien de maintenance (démo)"],
      zones: ["Sfax", "Gabès"],
      tariffs: [{ label: "Visite d'entretien", price: "60 TND" }, { label: "Révision de groupe électrogène", price: "120 – 250 TND" }],
      reviews: [review("Fethi D.", 5, "Groupe électrogène de l'immeuble réparé le jour même.", 9), review("Olfa Z.", 4, "Sérieux et disponible.", 50)],
      phone: "+216 20 000 104", email: "hichem.jaziri@exemple.tn"
    },
    {
      id: "pro-5", name: "Amel Kefi", specialty: "hydraulique", gov: "Tunis", area: "La Marsa", rating: 4.6, reviewsCount: 19,
      available: "week", verified: true, years: 11,
      presentation: "Ingénieure en hydraulique : réseaux d'eau d'immeubles, stockage, pompes et systèmes de récupération des eaux de pluie.",
      skills: ["Pompes", "Bâches de stockage", "Réseaux d'immeubles", "Eaux de pluie"],
      experiences: ["Ingénieure dans une entreprise de travaux hydrauliques (7 ans)"],
      certifications: ["Diplôme d'ingénieure en hydraulique (démo)"],
      zones: ["Tunis", "Ariana", "Ben Arous"],
      tariffs: [{ label: "Audit d'un réseau d'immeuble", price: "à partir de 150 TND" }, { label: "Installation de bâche + pompe", price: "sur devis" }],
      reviews: [review("Syndic Résidence Yasmine", 5, "Audit très complet, recommandations utiles.", 25)],
      phone: "+216 20 000 105", email: "amel.kefi@exemple.tn"
    },
    {
      id: "pro-6", name: "Walid Mansour", specialty: "electricien", gov: "Sousse", area: "Khezama", rating: 4.3, reviewsCount: 22,
      available: "busy", verified: false, years: 5,
      presentation: "Électricien bâtiment : installations neuves, rénovation et éclairage.",
      skills: ["Rénovation", "Éclairage LED", "Prises et circuits"],
      experiences: ["Électricien en entreprise de bâtiment (5 ans)"],
      certifications: ["Vérification WASLA en cours"],
      zones: ["Sousse", "Monastir"],
      tariffs: [{ label: "Déplacement + diagnostic", price: "25 TND" }, { label: "Ajout d'une prise", price: "35 TND" }],
      reviews: [review("Yassine M.", 4, "Travail correct.", 40)],
      phone: "+216 20 000 106", email: "walid.mansour@exemple.tn"
    },
    {
      id: "pro-7", name: "Nour Ayari", specialty: "solaire", gov: "Tunis", area: "Les Berges du Lac", rating: 4.9, reviewsCount: 53,
      available: "now", verified: true, years: 8,
      presentation: "Spécialiste des solutions de secours solaires pour les foyers où vivent des personnes dépendantes d'un appareil médical.",
      skills: ["Kits de secours", "Batteries", "Appareils médicaux", "Suivi à distance"],
      experiences: ["Responsable technique dans une start-up d'énergie (4 ans)", "Installatrice indépendante"],
      certifications: ["Formation photovoltaïque (démo)", "Partenaire de démonstration WASLA"],
      zones: ["Tunis", "Ariana", "Ben Arous", "Nabeul"],
      tariffs: [{ label: "Visite technique", price: "gratuit" }, { label: "Kit de secours 2 kWh", price: "à partir de 3 200 TND" }],
      reviews: [review("Famille Ch.", 5, "Le concentrateur d'oxygène de mon père reste alimenté pendant les coupures. Merci !", 4), review("Karim L.", 5, "Excellent suivi.", 33)],
      phone: "+216 20 000 107", email: "nour.ayari@exemple.tn"
    },
    {
      id: "pro-8", name: "Fathi Hammami", specialty: "plombier", gov: "Sfax", area: "Sakiet Ezzit", rating: 4.4, reviewsCount: 31,
      available: "week", verified: true, years: 18,
      presentation: "Plombier expérimenté : dépannage, rénovation de salles d'eau, installation de citernes et de filtres.",
      skills: ["Dépannage", "Filtres à eau", "Citernes", "Rénovation"],
      experiences: ["Artisan plombier depuis 2008"],
      certifications: ["Agrément de démonstration WASLA"],
      zones: ["Sfax"],
      tariffs: [{ label: "Déplacement", price: "20 TND" }, { label: "Pose d'un filtre", price: "60 – 120 TND" }],
      reviews: [review("Hela B.", 4, "Efficace.", 15), review("Moez K.", 5, "Très bon rapport qualité/prix.", 70)],
      phone: "+216 20 000 108", email: "fathi.hammami@exemple.tn"
    },
    {
      id: "pro-9", name: "Yosra Mejri", specialty: "maintenance", gov: "Ariana", area: "Raoued", rating: 4.7, reviewsCount: 16,
      available: "now", verified: true, years: 6,
      presentation: "Maintenance des climatiseurs, chauffe-eau et petits équipements électriques domestiques.",
      skills: ["Climatisation", "Chauffe-eau", "Électroménager"],
      experiences: ["Technicienne frigoriste (6 ans)"],
      certifications: ["Attestation de manipulation des fluides frigorigènes (démo)"],
      zones: ["Ariana", "Tunis"],
      tariffs: [{ label: "Entretien d'un climatiseur", price: "50 TND" }, { label: "Recharge de gaz", price: "90 – 140 TND" }],
      reviews: [review("Lotfi G.", 5, "Climatiseur réparé avant la vague de chaleur.", 8)],
      phone: "+216 20 000 109", email: "yosra.mejri@exemple.tn"
    },
    {
      id: "pro-10", name: "Mehdi Bouzid", specialty: "hydraulique", gov: "Gafsa", area: "Gafsa Ville", rating: 4.2, reviewsCount: 11,
      available: "now", verified: false, years: 4,
      presentation: "Installation de pompes, de citernes et de systèmes d'économie d'eau dans les zones touchées par le stress hydrique.",
      skills: ["Pompes immergées", "Citernes", "Économiseurs d'eau"],
      experiences: ["Technicien hydraulique (4 ans)"],
      certifications: ["Vérification WASLA en cours"],
      zones: ["Gafsa", "Sidi Bouzid", "Kasserine"],
      tariffs: [{ label: "Déplacement + diagnostic", price: "25 TND" }, { label: "Pose de citerne 1 000 L", price: "à partir de 90 TND" }],
      reviews: [review("Abdelaziz T.", 4, "Disponible et arrangeant.", 19)],
      phone: "+216 20 000 110", email: "mehdi.bouzid@exemple.tn"
    },
    {
      id: "pro-11", name: "Rania Chaabane", specialty: "electricien", gov: "Nabeul", area: "Hammamet", rating: 4.6, reviewsCount: 29,
      available: "week", verified: true, years: 10,
      presentation: "Électricienne pour particuliers et maisons d'hôtes : diagnostics, sécurité électrique et délestage intelligent.",
      skills: ["Diagnostic", "Délestage", "Sécurité électrique"],
      experiences: ["Électricienne en hôtellerie (6 ans)"],
      certifications: ["Habilitation électrique (démo)"],
      zones: ["Nabeul", "Zaghouan"],
      tariffs: [{ label: "Diagnostic complet", price: "70 TND" }, { label: "Installation d'un délesteur", price: "à partir de 220 TND" }],
      reviews: [review("Maison d'hôtes D.", 5, "Plus aucune coupure du disjoncteur en été.", 28)],
      phone: "+216 20 000 111", email: "rania.chaabane@exemple.tn"
    },
    {
      id: "pro-12", name: "Anis Kouki", specialty: "solaire", gov: "Kasserine", area: "Kasserine Nord", rating: 4.5, reviewsCount: 14,
      available: "busy", verified: true, years: 6,
      presentation: "Installations solaires pour le pompage agricole et l'autonomie des habitations rurales.",
      skills: ["Pompage solaire", "Sites isolés", "Batteries"],
      experiences: ["Installateur en milieu rural (6 ans)"],
      certifications: ["Formation photovoltaïque (démo)"],
      zones: ["Kasserine", "Sidi Bouzid", "Siliana"],
      tariffs: [{ label: "Étude de pompage", price: "50 TND" }, { label: "Pompage solaire complet", price: "sur devis" }],
      reviews: [review("Agriculteur S.", 5, "Pompe solaire fiable depuis deux étés.", 90)],
      phone: "+216 20 000 112", email: "anis.kouki@exemple.tn"
    }
  ];

  const requestStatuses = ["envoyee", "acceptee", "planifiee", "terminee"];
  const requestStatusLabels = { envoyee: "Envoyée", acceptee: "Acceptée", planifiee: "Planifiée", terminee: "Terminée", annulee: "Annulée" };

  const providerRequests = [
    {
      id: "req-demo-1", providerId: "pro-2", problem: "Fuite d'eau", urgency: "normale",
      description: "Fuite sous l'évier de la cuisine depuis la remise en eau après la coupure.",
      gov: "Ariana", area: "Ennasr", slot: inDays(-12), status: "terminee", rating: null, createdAt: ago(15 * 1440), demo: true
    },
    {
      id: "req-demo-2", providerId: "pro-1", problem: "Disjoncteur qui saute", urgency: "urgente",
      description: "Le disjoncteur principal saute dès que le climatiseur démarre depuis la dernière coupure.",
      gov: "Tunis", area: "El Menzah", slot: inDays(2), status: "planifiee", rating: null, createdAt: ago(2 * 1440), demo: true
    }
  ];

  /* ---------- 04 · Zones refuge ---------- */

  const refugeFeatures = {
    eau: { label: "Eau potable", icon: "droplet" },
    electricite: { label: "Recharge électrique", icon: "battery-charging" },
    clim: { label: "Climatisation", icon: "snowflake" },
    pmr: { label: "Accessible PMR", icon: "accessibility" },
    essentiels: { label: "Première nécessité", icon: "package" }
  };

  const refugeStatuses = {
    open: { label: "Ouvert", badge: "normal", marker: "normal" },
    full: { label: "Complet", badge: "watch", marker: "watch" },
    closed: { label: "Fermé", badge: "nodata", marker: "nodata" }
  };

  const refuges = [
    { id: "rf-1", name: "Maison des jeunes d'El Menzah", type: "Maison des jeunes", gov: "Tunis", address: "Rue des Jasmins, El Menzah 6", lon: 10.165, lat: 36.84, hours: "08:00 – 22:00", capacity: 80, available: 34, status: "open", features: ["eau", "electricite", "clim", "pmr", "essentiels"], services: ["Salle climatisée", "Fontaine d'eau potable", "Prises de recharge", "Trousse de premiers secours", "Coin enfants"], phone: "+216 70 000 201" },
    { id: "rf-2", name: "Espace partenaire Ennasr Business Center", type: "Entreprise partenaire", gov: "Ariana", address: "Avenue Hédi Nouira, Ennasr 2", lon: 10.172, lat: 36.858, hours: "09:00 – 19:00", capacity: 45, available: 12, status: "open", features: ["eau", "electricite", "clim", "pmr"], services: ["Hall climatisé", "Bornes de recharge", "Eau fraîche", "Wi-Fi"], phone: "+216 70 000 202" },
    { id: "rf-3", name: "Bibliothèque municipale de Lafayette", type: "Bibliothèque", gov: "Tunis", address: "Rue de Palestine, Lafayette", lon: 10.18, lat: 36.818, hours: "09:00 – 18:00", capacity: 60, available: 0, status: "full", features: ["eau", "clim", "pmr"], services: ["Salle de lecture climatisée", "Fontaine d'eau potable", "Accès PMR"], phone: "+216 70 000 203" },
    { id: "rf-4", name: "Complexe sportif de Mégrine", type: "Complexe sportif", gov: "Ben Arous", address: "Avenue de France, Mégrine", lon: 10.236, lat: 36.77, hours: "24h/24 pendant les crises", capacity: 150, available: 96, status: "open", features: ["eau", "electricite", "pmr", "essentiels"], services: ["Grand gymnase ventilé", "Citerne d'eau potable", "Groupe électrogène", "Lits de camp", "Kits d'hygiène"], phone: "+216 70 000 204" },
    { id: "rf-5", name: "Centre culturel de Sidi Bouzid", type: "Centre culturel", gov: "Sidi Bouzid", address: "Avenue de la République, Cité Ennour", lon: 9.49, lat: 35.045, hours: "08:00 – 23:00", capacity: 90, available: 21, status: "open", features: ["eau", "electricite", "clim", "essentiels"], services: ["Salle climatisée sur groupe électrogène", "Recharge des téléphones", "Eau potable", "Collations"], phone: "+216 70 000 205" },
    { id: "rf-6", name: "Hôtel partenaire Sidi Bouzid Centre", type: "Entreprise partenaire", gov: "Sidi Bouzid", address: "Rue Farhat Hached", lon: 9.478, lat: 35.03, hours: "24h/24", capacity: 30, available: 4, status: "open", features: ["eau", "electricite", "clim", "pmr"], services: ["Salon climatisé", "Recharge électrique", "Eau potable", "Priorité aux personnes âgées"], phone: "+216 70 000 206" },
    { id: "rf-7", name: "Municipalité de Kasserine — salle polyvalente", type: "Municipalité", gov: "Kasserine", address: "Place de l'Indépendance", lon: 8.84, lat: 35.172, hours: "08:00 – 20:00", capacity: 70, available: 38, status: "open", features: ["eau", "clim", "pmr", "essentiels"], services: ["Salle climatisée", "Distribution d'eau", "Kits de première nécessité"], phone: "+216 70 000 207" },
    { id: "rf-8", name: "Maison des jeunes de Gafsa", type: "Maison des jeunes", gov: "Gafsa", address: "Rue Ibn Khaldoun, Gafsa Ville", lon: 8.79, lat: 34.43, hours: "09:00 – 21:00", capacity: 55, available: 0, status: "full", features: ["eau", "electricite", "clim"], services: ["Salle climatisée", "Recharge électrique", "Eau potable"], phone: "+216 70 000 208" },
    { id: "rf-9", name: "Centre commercial Sahloul — espace refuge", type: "Entreprise partenaire", gov: "Sousse", address: "Route de Sahloul", lon: 10.59, lat: 35.84, hours: "10:00 – 22:00", capacity: 200, available: 140, status: "open", features: ["eau", "electricite", "clim", "pmr"], services: ["Galerie climatisée", "Bornes de recharge", "Fontaines d'eau", "Accès PMR"], phone: "+216 70 000 209" },
    { id: "rf-10", name: "Bibliothèque régionale de Sfax", type: "Bibliothèque", gov: "Sfax", address: "Avenue Habib Bourguiba", lon: 10.755, lat: 34.735, hours: "08:30 – 17:30", capacity: 65, available: 27, status: "open", features: ["eau", "clim", "pmr"], services: ["Salle climatisée", "Eau potable", "Accès PMR"], phone: "+216 70 000 210" },
    { id: "rf-11", name: "École primaire Ibn Sina — Kébili", type: "Établissement scolaire", gov: "Kébili", address: "Rue Ibn Sina, Kébili Sud", lon: 8.97, lat: 33.7, hours: "Fermé hors période de crise", capacity: 50, available: 50, status: "closed", features: ["eau", "essentiels"], services: ["Point d'eau potable (citerne)", "Kits de première nécessité"], phone: "+216 70 000 211" },
    { id: "rf-12", name: "Centre culturel de Hammamet", type: "Centre culturel", gov: "Nabeul", address: "Avenue des Nations Unies, Hammamet", lon: 10.61, lat: 36.4, hours: "09:00 – 20:00", capacity: 75, available: 41, status: "open", features: ["eau", "clim", "pmr"], services: ["Salle climatisée", "Eau potable", "Accès PMR"], phone: "+216 70 000 212" },
    { id: "rf-13", name: "Complexe sportif de Bizerte", type: "Complexe sportif", gov: "Bizerte", address: "Boulevard Hassen Nouri", lon: 9.865, lat: 37.27, hours: "08:00 – 22:00", capacity: 120, available: 88, status: "open", features: ["eau", "electricite", "essentiels"], services: ["Gymnase ventilé", "Groupe électrogène", "Citerne d'eau", "Lits de camp"], phone: "+216 70 000 213" },
    { id: "rf-14", name: "Espace partenaire Monastir Marina", type: "Entreprise partenaire", gov: "Monastir", address: "Port de plaisance", lon: 10.83, lat: 35.775, hours: "10:00 – 21:00", capacity: 40, available: 0, status: "closed", features: ["eau", "electricite", "clim"], services: ["Espace climatisé", "Recharge électrique", "Eau fraîche"], phone: "+216 70 000 214" }
  ];

  /* ---------- 05 · Relais de voisinage ---------- */

  const relaisCategories = {
    eau: { label: "Eau potable", icon: "droplet" },
    recharge: { label: "Recharge de téléphones", icon: "smartphone" },
    electricite: { label: "Électricité", icon: "zap" },
    generateur: { label: "Générateur électrique", icon: "plug" },
    hebergement: { label: "Hébergement temporaire", icon: "bed" },
    transport: { label: "Transport de ressources", icon: "truck" },
    aines: { label: "Aide aux personnes âgées", icon: "heart" },
    autre: { label: "Autres besoins essentiels", icon: "package" }
  };

  const urgencies = {
    haute: { label: "Urgent", badge: "outage", rank: 3 },
    moyenne: { label: "Modéré", badge: "watch", rank: 2 },
    faible: { label: "Peu urgent", badge: "normal", rank: 1 }
  };

  const relaisPosts = [
    { id: "rp-1", type: "offer", category: "eau", author: "Leila K.", gov: "Tunis", area: "El Menzah 5", title: "Bidons d'eau potable à partager", text: "J'ai 6 bidons de 5 L d'eau potable en réserve. Je peux en donner 2 ou 3 aux familles du quartier pendant la coupure de demain.", availability: "Aujourd'hui et demain", urgency: "faible", status: "open", date: ago(55), replies: [] },
    { id: "rp-2", type: "request", category: "aines", author: "Mohamed A.", gov: "Tunis", area: "El Menzah 6", title: "Monter des packs d'eau à une voisine âgée", text: "Ma voisine de 82 ans vit au 4e étage sans ascenseur. Quelqu'un pourrait-il l'aider à monter des packs d'eau demain matin ?", urgency: "haute", status: "open", date: ago(80), replies: [{ author: "Youssef T.", text: "Je peux passer vers 9h, je suis à deux rues.", date: ago(40) }] },
    { id: "rp-3", type: "offer", category: "recharge", author: "Café Le Relais (partenaire)", gov: "Ariana", area: "Ennasr 2", title: "Recharge de téléphones gratuite", text: "Notre café a un groupe électrogène : recharge gratuite des téléphones et batteries externes pendant les coupures, de 8h à 20h.", availability: "Pendant toutes les coupures", urgency: "faible", status: "open", date: ago(130), replies: [] },
    { id: "rp-4", type: "request", category: "generateur", author: "Rim S.", gov: "Sidi Bouzid", area: "Cité Ennour", title: "Besoin d'un générateur pour un réfrigérateur de médicaments", text: "Mon père est diabétique et son insuline doit rester au frais. Cherche un voisin pouvant brancher un petit réfrigérateur quelques heures.", urgency: "haute", status: "open", date: ago(35), replies: [] },
    { id: "rp-5", type: "offer", category: "generateur", author: "Hamza B.", gov: "Sidi Bouzid", area: "Cité Ennour", title: "Groupe électrogène disponible le soir", text: "J'ai un groupe électrogène de 3 kW. Je peux alimenter un réfrigérateur ou recharger des appareils médicaux pour un voisin.", availability: "Tous les soirs de 18h à 23h", urgency: "faible", status: "open", date: ago(70), replies: [] },
    { id: "rp-6", type: "request", category: "hebergement", author: "Famille Gh.", gov: "Gafsa", area: "Cité Ouvrière", title: "Hébergement d'une nuit pour une famille", text: "Coupure d'eau prolongée et bébé de 6 mois. Nous cherchons un hébergement pour une nuit, idéalement dans un quartier non touché.", urgency: "moyenne", status: "open", date: ago(300), replies: [] },
    { id: "rp-7", type: "offer", category: "transport", author: "Aymen R.", gov: "Ariana", area: "Ennasr 1", title: "Transport de citernes et de packs d'eau", text: "J'ai une camionnette. Je peux transporter des packs d'eau ou des citernes pour les voisins qui n'ont pas de véhicule.", availability: "Week-end", urgency: "faible", status: "open", date: ago(500), replies: [] },
    { id: "rp-8", type: "request", category: "eau", author: "Nadia M.", gov: "Kébili", area: "Kébili Sud", title: "Eau potable pour trois foyers", text: "Notre rue n'a plus d'eau depuis hier. Nous cherchons de l'eau potable pour trois foyers, dont deux personnes âgées.", urgency: "haute", status: "open", date: ago(650), replies: [] },
    { id: "rp-9", type: "offer", category: "hebergement", author: "Salma H.", gov: "Gafsa", area: "Gafsa Ville", title: "Chambre disponible pour une famille", text: "Notre quartier n'est pas touché par la coupure. Nous pouvons accueillir une famille pour une nuit.", availability: "Cette semaine", urgency: "faible", status: "open", date: ago(720), replies: [] },
    { id: "rp-10", type: "request", category: "recharge", author: "Karim D.", gov: "Tunis", area: "Lafayette", title: "Recharge d'un fauteuil roulant électrique", text: "Besoin de recharger la batterie d'un fauteuil roulant électrique pendant la coupure de cet après-midi.", urgency: "moyenne", status: "resolved", date: ago(1500), replies: [{ author: "Pharmacie du Centre (partenaire)", text: "Venez à la pharmacie, nous avons une prise sur onduleur.", date: ago(1450) }] },
    { id: "rp-11", type: "offer", category: "aines", author: "Association Jeunes Solidaires", gov: "Kasserine", area: "Centre-ville", title: "Visites et courses pour les personnes âgées", text: "Nos bénévoles font les courses essentielles et rendent visite aux personnes âgées isolées pendant la vague de chaleur.", availability: "Tous les jours de 9h à 18h", urgency: "faible", status: "open", date: ago(900), replies: [] },
    { id: "rp-12", type: "request", category: "eau", author: "Vous", gov: "Tunis", area: "El Menzah 6", title: "Eau potable pendant la coupure de demain", text: "La coupure d'eau programmée tombe au moment où je reçois ma famille. Je cherche 2 bidons d'eau potable près d'El Menzah.", urgency: "moyenne", status: "open", date: ago(25), replies: [], mine: true }
  ];

  /* ---------- 06 · Priorité santé ---------- */

  const healthNeeds = {
    dialyse: { label: "Dialyse à domicile", icon: "activity", resource: "both" },
    oxygene: { label: "Oxygénothérapie / concentrateur", icon: "wind", resource: "electricite" },
    respirateur: { label: "Respirateur ou appareil vital électrique", icon: "heart-pulse", resource: "electricite" },
    froid: { label: "Médicaments à conserver au froid", icon: "pill", resource: "electricite" },
    mobilite: { label: "Mobilité réduite (étage, ascenseur)", icon: "accessibility", resource: "both" },
    autre: { label: "Autre besoin vital", icon: "heart", resource: "both" }
  };

  const healthPriorities = {
    critique: { label: "Critique", badge: "outage", rank: 3, hint: "Risque vital en moins de quelques heures sans eau ou électricité." },
    elevee: { label: "Élevée", badge: "watch", rank: 2, hint: "Situation difficile au-delà d'une demi-journée de coupure." },
    moderee: { label: "Modérée", badge: "normal", rank: 1, hint: "Besoin d'attention mais pas de risque immédiat." }
  };

  const healthStatuses = {
    attente: { label: "En attente de vérification", badge: "nodata" },
    verifiee: { label: "Vérifiée", badge: "watch" },
    encours: { label: "Prise en charge en cours", badge: "watch" },
    resolue: { label: "Résolue", badge: "normal" }
  };

  // Statistiques nationales de démonstration (s'ajoutent aux demandes saisies dans ce navigateur).
  const healthStats = { registered: 1284, attente: 37, encours: 52, resolue: 1195 };

  // Dossiers fictifs visibles uniquement dans l'espace de coordination (comptes autorisés).
  const healthCases = [
    { id: "hc-1", name: "Mohamed T.", age: 67, need: "oxygene", resource: "electricite", priority: "critique", gov: "Sidi Bouzid", area: "Cité Ennour", status: "encours", autonomy: "3 h de batterie", team: "Équipe STEG Sidi Bouzid", notes: [] },
    { id: "hc-2", name: "Fatma B.", age: 74, need: "dialyse", resource: "both", priority: "critique", gov: "Sidi Bouzid", area: "Centre-ville", status: "attente", autonomy: "Séance prévue ce soir", team: "", notes: [] },
    { id: "hc-3", name: "Ali H.", age: 58, need: "froid", resource: "electricite", priority: "elevee", gov: "Sidi Bouzid", area: "Cité Ennour", status: "verifiee", autonomy: "Insuline — 6 h", team: "", notes: [] },
    { id: "hc-4", name: "Saida M.", age: 81, need: "mobilite", resource: "eau", priority: "elevee", gov: "Kasserine", area: "Centre-ville", status: "attente", autonomy: "4e étage sans ascenseur", team: "", notes: [] },
    { id: "hc-5", name: "Hedi K.", age: 70, need: "respirateur", resource: "electricite", priority: "critique", gov: "Gafsa", area: "Cité Ouvrière", status: "verifiee", autonomy: "2 h de batterie", team: "", notes: [] },
    { id: "hc-6", name: "Amina R.", age: 9, need: "froid", resource: "electricite", priority: "elevee", gov: "Gafsa", area: "Gafsa-Nord", status: "attente", autonomy: "Médicaments — 8 h", team: "", notes: [] },
    { id: "hc-7", name: "Brahim S.", age: 63, need: "dialyse", resource: "both", priority: "critique", gov: "Kébili", area: "Kébili Sud", status: "encours", autonomy: "Séance demain matin", team: "Protection civile Kébili", notes: [] },
    { id: "hc-8", name: "Zohra A.", age: 88, need: "mobilite", resource: "both", priority: "moderee", gov: "Tunis", area: "El Menzah 6", status: "attente", autonomy: "Vit seule", team: "", notes: [] }
  ];

  // Coupures en cours (cohérentes avec la carte des réseaux : data.js).
  const activeOutages = [
    { id: "out-1", gov: "Sidi Bouzid", resource: "electricite", label: "Coupure d'électricité — Sidi Bouzid", since: ago(38) },
    { id: "out-2", gov: "Kébili", resource: "eau", label: "Coupure d'eau — Kébili Sud", since: ago(1300) },
    { id: "out-3", gov: "Gafsa", resource: "electricite", label: "Délestages possibles — Gafsa", since: ago(3600) },
    { id: "out-4", gov: "Kasserine", resource: "eau", label: "Vigilance eau — Kasserine", since: ago(160) }
  ];

  const teams = ["Équipe STEG d'intervention", "Équipe SONEDE d'intervention", "Protection civile", "Croix-Rouge (bénévoles)", "Centre de santé de base"];

  window.WASLA_SERVICES_DATA = {
    version: 1,
    alertCategories, severities, serviceLabels, alerts, defaultPlaces, defaultAlertPrefs,
    months, monthlyElectricity, monthlyWater, tariffs, bills, billAmount,
    specialties, availability, providers, providerRequests, requestStatuses, requestStatusLabels,
    refugeFeatures, refugeStatuses, refuges,
    relaisCategories, urgencies, relaisPosts,
    healthNeeds, healthPriorities, healthStatuses, healthStats, healthCases, activeOutages, teams
  };
})();
