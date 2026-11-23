/* ============================================================
   WASLA — Internationalisation (FR source, EN, AR + RTL)
   French is the source language written in the HTML and data.
   Every visible string is looked up here and swapped in place,
   including content rendered later by other scripts.
   ============================================================ */
(function () {
  "use strict";

  const PREFS = window.WASLA_PREFS;
  const LANGS = ["fr", "en", "ar"];

  /* Dictionary: "French source": ["English", "Arabic"] ---------- */

  const D = {
    /* Shell, header, navigation */
    "Aller au contenu": ["Skip to content", "انتقل إلى المحتوى"],
    "Retour en haut": ["Back to top", "العودة إلى الأعلى"],
    "Connecter. Anticiper.": ["Connect. Anticipate.", "نربط. نستبق."],
    "Protéger.": ["Protect.", "نحمي."],
    "Connecter. Anticiper. Protéger.": ["Connect. Anticipate. Protect.", "نربط. نستبق. نحمي."],
    "Navigation principale": ["Main navigation", "التنقل الرئيسي"],
    "Accueil": ["Home", "الرئيسية"],
    "Carte": ["Map", "الخريطة"],
    "Analyses": ["Analytics", "التحليلات"],
    "Services": ["Services", "الخدمات"],
    "À propos": ["About", "من نحن"],
    "Services citoyens": ["Citizen services", "خدمات المواطنين"],
    "Alertes personnalisées": ["Personalised alerts", "تنبيهات مخصّصة"],
    "Notifications ciblées": ["Targeted notifications", "إشعارات موجّهة"],
    "Factures & IA": ["Bills & AI", "الفواتير والذكاء الاصطناعي"],
    "Suivi et analyse": ["Tracking and analysis", "متابعة وتحليل"],
    "Fournisseurs": ["Providers", "المزوّدون"],
    "Techniciens agréés": ["Certified technicians", "فنيون معتمدون"],
    "Zones refuge": ["Shelter zones", "مناطق الإيواء"],
    "Points d'accueil": ["Reception points", "نقاط الاستقبال"],
    "Relais de voisinage": ["Neighbourhood relay", "شبكة الجيران"],
    "Entraide locale": ["Local mutual aid", "تضامن محلي"],
    "Priorité santé": ["Health priority", "أولوية صحية"],
    "Besoins vitaux": ["Vital needs", "احتياجات حيوية"],
    "Rechercher (Ctrl+K)": ["Search (Ctrl+K)", "بحث (Ctrl+K)"],
    "Notifications, 3 non lues": ["Notifications, 3 unread", "الإشعارات، 3 غير مقروءة"],
    "Notifications, aucune non lue": ["Notifications, none unread", "الإشعارات، لا توجد إشعارات غير مقروءة"],
    "Connexion": ["Sign in", "تسجيل الدخول"],
    "Ouvrir le menu": ["Open menu", "فتح القائمة"],
    "Fermer le menu": ["Close menu", "إغلاق القائمة"],
    "Menu principal": ["Main menu", "القائمة الرئيسية"],
    "Navigation mobile": ["Mobile navigation", "تنقل الجوال"],
    "Rechercher": ["Search", "بحث"],
    "Changer de langue": ["Change language", "تغيير اللغة"],
    "Langue": ["Language", "اللغة"],
    "Thème": ["Theme", "المظهر"],
    "Préférences": ["Preferences", "التفضيلات"],
    "Mode sombre": ["Dark mode", "الوضع الداكن"],
    "Activer le mode sombre": ["Switch to dark mode", "تفعيل الوضع الداكن"],
    "Activer le mode clair": ["Switch to light mode", "تفعيل الوضع الفاتح"],
    "Chargement…": ["Loading…", "جارٍ التحميل…"],
    "Fil d'Ariane": ["Breadcrumb", "مسار التنقل"],

    /* Notifications */
    "Notifications": ["Notifications", "الإشعارات"],
    "Tout marquer comme lu": ["Mark all as read", "تعليم الكل كمقروء"],
    "Voir toutes les alertes": ["View all alerts", "عرض كل التنبيهات"],
    "Toutes les notifications ont été marquées comme lues.": ["All notifications have been marked as read.", "تم تعليم جميع الإشعارات كمقروءة."],
    "Vigilance électricité à Kasserine": ["Electricity watch in Kasserine", "يقظة كهربائية في القصرين"],
    "La consommation approche du seuil critique dans 2 zones.": ["Consumption is nearing the critical threshold in 2 areas.", "يقترب الاستهلاك من العتبة الحرجة في منطقتين."],
    "Coupure signalée à Sidi Bouzid": ["Outage reported in Sidi Bouzid", "انقطاع مُبلَّغ عنه في سيدي بوزيد"],
    "Une intervention est en cours sur le réseau local.": ["Repair work is under way on the local network.", "تدخّل جارٍ على الشبكة المحلية."],
    "Réseau eau stable à Sfax": ["Stable water network in Sfax", "شبكة الماء مستقرة في صفاقس"],
    "Aucun incident majeur détecté aujourd'hui.": ["No major incident detected today.", "لم يُرصد أي حادث كبير اليوم."],

    /* Search */
    "Rechercher dans WASLA": ["Search WASLA", "ابحث في WASLA"],
    "Rechercher une page, un service, une zone…": ["Search for a page, service or area…", "ابحث عن صفحة أو خدمة أو منطقة…"],
    "Fermer la recherche": ["Close search", "إغلاق البحث"],
    "pour naviguer": ["to navigate", "للتنقل"],
    "Entrée": ["Enter", "إدخال"],
    "pour ouvrir": ["to open", "للفتح"],
    "Échap": ["Esc", "Esc"],
    "pour fermer": ["to close", "للإغلاق"],
    "Essayez « carte », « alertes » ou « factures ».": ["Try “map”, “alerts” or “bills”.", "جرّب « خريطة » أو « تنبيهات » أو « فواتير »."],
    "Vue d'ensemble des réseaux": ["Network overview", "نظرة عامة على الشبكات"],
    "Carte des réseaux": ["Network map", "خريطة الشبكات"],
    "Statut en temps réel par gouvernorat": ["Real-time status by governorate", "الحالة في الوقت الفعلي حسب الولاية"],
    "Consommation, tendances et coupures": ["Consumption, trends and outages", "الاستهلاك والاتجاهات والانقطاعات"],
    "Alertes, factures, fournisseurs et entraide": ["Alerts, bills, providers and mutual aid", "التنبيهات والفواتير والمزوّدون والتضامن"],
    "Mission et engagements de WASLA": ["WASLA's mission and commitments", "مهمة WASLA والتزاماتها"],
    "Accéder à votre espace citoyen": ["Access your citizen space", "الدخول إلى فضائك المواطني"],
    "Recevoir les alertes de votre zone": ["Get alerts for your area", "تلقَّ تنبيهات منطقتك"],
    "Suivre et analyser vos factures": ["Track and analyse your bills", "تابع فواتيرك وحلّلها"],
    "Trouver un fournisseur agréé": ["Find a certified provider", "ابحث عن مزوّد معتمد"],
    "Points d'accueil pendant une coupure": ["Reception points during an outage", "نقاط الاستقبال أثناء الانقطاع"],
    "Partager une ressource avec ses voisins": ["Share a resource with your neighbours", "شارك موردًا مع جيرانك"],
    "Signaler un besoin vital": ["Report a vital need", "أبلغ عن حاجة حيوية"],

    /* Footer */
    "Une plateforme intelligente pour surveiller, anticiper et gérer les coupures d'eau et d'électricité en Tunisie.": [
      "A smart platform to monitor, anticipate and manage water and electricity outages in Tunisia.",
      "منصة ذكية لرصد انقطاعات الماء والكهرباء في تونس واستباقها وإدارتها."
    ],
    "Plateforme": ["Platform", "المنصة"],
    "Notre mission": ["Our mission", "مهمتنا"],
    "Engagements": ["Commitments", "الالتزامات"],
    "Contact": ["Contact", "اتصل بنا"],
    "Mentions légales": ["Legal notice", "الإشعار القانوني"],
    "Confidentialité": ["Privacy", "الخصوصية"],
    "Accessibilité": ["Accessibility", "إمكانية الوصول"],

    /* Page meta */
    "WASLA est une plateforme intelligente pour surveiller, anticiper et gérer les coupures d'eau et d'électricité en Tunisie.": [
      "WASLA is a smart platform to monitor, anticipate and manage water and electricity outages in Tunisia.",
      "WASLA منصة ذكية لرصد انقطاعات الماء والكهرباء في تونس واستباقها وإدارتها."
    ],
    "Visualisez l'état de l'électricité et de l'eau dans les 24 gouvernorats de Tunisie, filtrez par statut et signalez une coupure avec WASLA.": [
      "View the status of electricity and water in Tunisia's 24 governorates, filter by status and report an outage with WASLA.",
      "اطّلع على حالة الكهرباء والماء في ولايات تونس الأربع والعشرين، وصفِّ حسب الحالة وأبلغ عن انقطاع مع WASLA."
    ],
    "Suivez l'évolution de la consommation d'électricité et d'eau, identifiez les zones à risque et consultez les derniers incidents en Tunisie.": [
      "Follow electricity and water consumption trends, identify at-risk areas and review the latest incidents in Tunisia.",
      "تابع تطور استهلاك الكهرباء والماء، وحدّد المناطق المعرّضة للخطر، واطّلع على آخر الحوادث في تونس."
    ],
    "Alertes personnalisées, factures, fournisseurs, zones refuge, relais de voisinage et priorité santé : découvrez les services WASLA.": [
      "Personalised alerts, bills, providers, shelter zones, neighbourhood relay and health priority: discover WASLA's services.",
      "تنبيهات مخصّصة، فواتير، مزوّدون، مناطق إيواء، شبكة الجيران وأولوية صحية: اكتشف خدمات WASLA."
    ],
    "Découvrez la mission, les valeurs et les engagements de WASLA pour une Tunisie plus résiliente face aux coupures d'eau et d'électricité.": [
      "Discover WASLA's mission, values and commitments for a Tunisia more resilient to water and electricity outages.",
      "اكتشف مهمة WASLA وقيمها والتزاماتها من أجل تونس أكثر صمودًا أمام انقطاعات الماء والكهرباء."
    ],
    "Connectez-vous à votre espace citoyen WASLA pour suivre vos factures, recevoir des alertes personnalisées et accéder aux services prioritaires.": [
      "Sign in to your WASLA citizen space to track your bills, receive personalised alerts and access priority services.",
      "سجّل الدخول إلى فضائك المواطني في WASLA لمتابعة فواتيرك وتلقي تنبيهات مخصّصة والوصول إلى الخدمات ذات الأولوية."
    ],

    /* Home */
    "Éoliennes sur la côte du Cap Bon en Tunisie, face à la Méditerranée": [
      "Wind turbines on the Cap Bon coast in Tunisia, facing the Mediterranean",
      "توربينات رياح على ساحل الوطن القبلي في تونس، مطلّة على البحر الأبيض المتوسط"
    ],
    "Une Tunisie plus résiliente": ["A more resilient Tunisia", "تونس أكثر صمودًا"],
    "Découvrir la plateforme": ["Explore the platform", "اكتشف المنصة"],
    "Faire défiler vers le contenu": ["Scroll to content", "التمرير إلى المحتوى"],
    "SCROLL": ["SCROLL", "مرّر"],
    "Fonctionnalités clés": ["Key features", "الميزات الرئيسية"],
    "Suivre la consommation d'électricité": ["Track electricity consumption", "متابعة استهلاك الكهرباء"],
    "Surveiller la consommation d'eau": ["Monitor water consumption", "مراقبة استهلاك الماء"],
    "Consulter la carte en temps réel": ["View the real-time map", "الاطلاع على الخريطة في الوقت الفعلي"],
    "Recevoir des alertes personnalisées": ["Receive personalised alerts", "تلقي تنبيهات مخصّصة"],
    "Agir pour une Tunisie durable": ["Act for a sustainable Tunisia", "العمل من أجل تونس مستدامة"],
    "État actuel des réseaux": ["Current network status", "الحالة الراهنة للشبكات"],
    "Mis à jour à l'instant": ["Updated just now", "تم التحديث للتو"],
    "Données de démonstration": ["Demo data", "بيانات تجريبية"],
    "Électricité": ["Electricity", "الكهرباء"],
    "Eau": ["Water", "الماء"],
    "Réseau stable": ["Stable network", "شبكة مستقرة"],
    "Voir les détails": ["View details", "عرض التفاصيل"],
    "Tunisie": ["Tunisia", "تونس"],
    "Temps réel": ["Real time", "الوقت الفعلي"],
    "Afficher la carte en plein écran": ["Show map in full screen", "عرض الخريطة بملء الشاشة"],
    "Quitter le plein écran": ["Exit full screen", "الخروج من ملء الشاشة"],
    "Légende des statuts": ["Status legend", "مفتاح الحالات"],
    "Statut du réseau": ["Network status", "حالة الشبكة"],
    "Normale": ["Normal", "عادية"],
    "En vigilance": ["Under watch", "تحت المراقبة"],
    "Vigilance": ["Watch", "يقظة"],
    "Coupure": ["Outage", "انقطاع"],
    "Sans données": ["No data", "لا بيانات"],
    "Zoomer": ["Zoom in", "تكبير"],
    "Dézoomer": ["Zoom out", "تصغير"],
    "Recentrer la carte": ["Recenter map", "إعادة توسيط الخريطة"],
    "Lignes électriques haute tension au coucher du soleil": ["High-voltage power lines at sunset", "خطوط كهرباء ذات جهد عالٍ عند الغروب"],
    "Alerte importante": ["Important alert", "تنبيه مهم"],
    "La consommation électrique dans certaines zones approche du": ["Electricity consumption in some areas is approaching the", "يقترب استهلاك الكهرباء في بعض المناطق من"],
    "seuil critique": ["critical threshold", "العتبة الحرجة"],
    ". Pensez à réduire votre consommation.": [". Please consider reducing your consumption.", ". يُرجى التفكير في خفض استهلاككم."],
    "Voir la carte": ["View map", "عرض الخريطة"],
    "Nos engagements": ["Our commitments", "التزاماتنا"],
    "Pour une gestion durable et intelligente des ressources en Tunisie.": ["For sustainable, smart resource management in Tunisia.", "من أجل إدارة مستدامة وذكية للموارد في تونس."],
    "+1M": ["1M+", "+1 مليون"],
    "Utilisateurs": ["Users", "مستخدم"],
    "Gouvernorats": ["Governorates", "ولاية"],
    "Engagement citoyen": ["Civic engagement", "مشاركة المواطنين"],
    "Panneaux solaires dans une centrale photovoltaïque": ["Solar panels at a photovoltaic plant", "ألواح شمسية في محطة كهروضوئية"],
    "Ensemble pour une Tunisie durable": ["Together for a sustainable Tunisia", "معًا من أجل تونس مستدامة"],
    "Découvrir nos engagements": ["Discover our commitments", "اكتشف التزاماتنا"],
    "Réseau tunisien connecté": ["Connected Tunisian network", "الشبكة التونسية المترابطة"],
    "Carte des réseaux d'électricité et d'eau en Tunisie": ["Map of electricity and water networks in Tunisia", "خريطة شبكات الكهرباء والماء في تونس"],
    "MÉDITERRANÉE": ["MEDITERRANEAN", "البحر المتوسط"],
    "ALGÉRIE": ["ALGERIA", "الجزائر"],
    "LIBYE": ["LIBYA", "ليبيا"],
    "Tendance de la consommation d'eau": ["Water consumption trend", "اتجاه استهلاك الماء"],
    "Tendance de la consommation d'électricité": ["Electricity consumption trend", "اتجاه استهلاك الكهرباء"],

    /* Governorates */
    "Tunis": ["Tunis", "تونس"],
    "Ariana": ["Ariana", "أريانة"],
    "Ben Arous": ["Ben Arous", "بن عروس"],
    "Manouba": ["Manouba", "منوبة"],
    "Nabeul": ["Nabeul", "نابل"],
    "Bizerte": ["Bizerte", "بنزرت"],
    "Béja": ["Beja", "باجة"],
    "Jendouba": ["Jendouba", "جندوبة"],
    "El Kef": ["El Kef", "الكاف"],
    "Siliana": ["Siliana", "سليانة"],
    "Zaghouan": ["Zaghouan", "زغوان"],
    "Kairouan": ["Kairouan", "القيروان"],
    "Kasserine": ["Kasserine", "القصرين"],
    "Sidi Bouzid": ["Sidi Bouzid", "سيدي بوزيد"],
    "Sousse": ["Sousse", "سوسة"],
    "Monastir": ["Monastir", "المنستير"],
    "Mahdia": ["Mahdia", "المهدية"],
    "Sfax": ["Sfax", "صفاقس"],
    "Gafsa": ["Gafsa", "قفصة"],
    "Tozeur": ["Tozeur", "توزر"],
    "Kébili": ["Kebili", "قبلي"],
    "Gabès": ["Gabes", "قابس"],
    "Médenine": ["Medenine", "مدنين"],
    "Tataouine": ["Tataouine", "تطاوين"],

    /* Services (data + page) */
    "Choisissez vos gouvernorats et vos seuils pour recevoir une notification avant chaque coupure ou pic de consommation.": [
      "Choose your governorates and thresholds to be notified before every outage or consumption peak.",
      "اختر ولاياتك وعتباتك لتتلقى إشعارًا قبل كل انقطاع أو ذروة استهلاك."
    ],
    "Notifications push et e-mail": ["Push and email notifications", "إشعارات فورية وعبر البريد الإلكتروني"],
    "Seuils personnalisables": ["Customisable thresholds", "عتبات قابلة للتخصيص"],
    "Historique des alertes": ["Alert history", "سجل التنبيهات"],
    "Retrouvez vos factures d'eau et d'électricité, suivez vos paiements et laissez l'IA détecter les anomalies de consommation.": [
      "Find your water and electricity bills, track your payments and let AI detect consumption anomalies.",
      "اعثر على فواتير الماء والكهرباء، وتابع مدفوعاتك، ودع الذكاء الاصطناعي يكتشف حالات الاستهلاك غير الطبيعية."
    ],
    "Lecture assistée par IA": ["AI-assisted reading", "قراءة بمساعدة الذكاء الاصطناعي"],
    "Suivi des paiements": ["Payment tracking", "متابعة المدفوعات"],
    "Conseils d'économie": ["Saving tips", "نصائح للتوفير"],
    "Consultez l'annuaire des fournisseurs agréés et trouvez un technicien proche de chez vous pour vos interventions.": [
      "Browse the directory of certified providers and find a technician near you for repairs.",
      "تصفّح دليل المزوّدين المعتمدين واعثر على فني قريب منك لتدخلاتك."
    ],
    "Annuaire vérifié": ["Verified directory", "دليل موثّق"],
    "Recherche par gouvernorat": ["Search by governorate", "البحث حسب الولاية"],
    "Demande d'intervention": ["Service request", "طلب تدخّل"],
    "Localisez les espaces climatisés et les points d'eau potable ouverts au public pendant les épisodes de coupure.": [
      "Locate air-conditioned spaces and drinking-water points open to the public during outages.",
      "حدّد أماكن الفضاءات المكيّفة ونقاط الماء الصالح للشرب المفتوحة للعموم أثناء فترات الانقطاع."
    ],
    "Points d'accueil cartographiés": ["Mapped reception points", "نقاط استقبال على الخريطة"],
    "Horaires en temps réel": ["Real-time opening hours", "مواعيد في الوقت الفعلي"],
    "Itinéraire accessible": ["Accessible route", "مسار ميسّر"],
    "Partagez un réfrigérateur, une batterie ou un groupe électrogène avec vos voisins grâce au réseau solidaire WASLA.": [
      "Share a fridge, a battery or a generator with your neighbours through the WASLA solidarity network.",
      "شارك ثلاجة أو بطارية أو مولّدًا كهربائيًا مع جيرانك عبر شبكة WASLA التضامنية."
    ],
    "Entraide de proximité": ["Neighbourhood mutual aid", "تضامن الجوار"],
    "Réservation simple": ["Simple booking", "حجز بسيط"],
    "Réseau de confiance": ["Trusted network", "شبكة موثوقة"],
    "Signalez les besoins vitaux (dialyse, oxygène, chaîne du froid) pour être priorisé lors des rétablissements de réseau.": [
      "Report vital needs (dialysis, oxygen, cold chain) to be prioritised when the network is restored.",
      "أبلغ عن الاحتياجات الحيوية (غسيل الكلى، الأكسجين، سلسلة التبريد) لتحظى بالأولوية عند إعادة الشبكة."
    ],
    "Priorisation des urgences": ["Emergency prioritisation", "إعطاء الأولوية للحالات العاجلة"],
    "Contact direct": ["Direct contact", "اتصال مباشر"],
    "Confidentialité des données": ["Data privacy", "سرية البيانات"],
    "Découvrir": ["Learn more", "اكتشف"],
    "Des services pour mieux vivre les coupures": ["Services to better cope with outages", "خدمات لتجاوز الانقطاعات بشكل أفضل"],
    "Alertes, factures, fournisseurs, zones refuge et entraide : WASLA accompagne les citoyens avant, pendant et après chaque interruption.": [
      "Alerts, bills, providers, shelter zones and mutual aid: WASLA supports citizens before, during and after every interruption.",
      "تنبيهات وفواتير ومزوّدون ومناطق إيواء وتضامن: ترافق WASLA المواطنين قبل كل انقطاع وأثناءه وبعده."
    ],
    "Créer mon espace": ["Create my space", "إنشاء فضائي"],
    "S'INFORMER": ["STAY INFORMED", "الاطّلاع"],
    "ANTICIPER": ["ANTICIPATE", "الاستباق"],
    "AGIR": ["ACT", "التحرّك"],
    "Consultez l'état du réseau": ["Check the network status", "اطّلع على حالة الشبكة"],
    "La carte et les analyses vous donnent une vision claire de la situation dans chaque gouvernorat.": [
      "The map and analytics give you a clear view of the situation in every governorate.",
      "تمنحك الخريطة والتحليلات رؤية واضحة للوضع في كل ولاية."
    ],
    "Recevez les bonnes alertes": ["Get the right alerts", "تلقَّ التنبيهات المناسبة"],
    "Choisissez vos zones et vos seuils pour être prévenu avant une coupure ou un pic de consommation.": [
      "Choose your areas and thresholds to be warned before an outage or a consumption peak.",
      "اختر مناطقك وعتباتك لتُنبَّه قبل أي انقطاع أو ذروة استهلاك."
    ],
    "Activez les solutions locales": ["Activate local solutions", "فعّل الحلول المحلية"],
    "Zones refuge, relais de voisinage et priorité santé : des réponses concrètes pour chaque situation.": [
      "Shelter zones, neighbourhood relay and health priority: concrete answers for every situation.",
      "مناطق الإيواء وشبكة الجيران والأولوية الصحية: حلول ملموسة لكل وضعية."
    ],
    "Un service ne figure pas encore ici ?": ["Is a service missing?", "هل هناك خدمة غير موجودة هنا؟"],
    "WASLA évolue avec les besoins des citoyens. Proposez un nouveau service ou signalez un besoin prioritaire.": [
      "WASLA evolves with citizens' needs. Suggest a new service or report a priority need.",
      "تتطوّر WASLA مع احتياجات المواطنين. اقترح خدمة جديدة أو أبلغ عن حاجة ذات أولوية."
    ],
    "Proposer un service": ["Suggest a service", "اقترح خدمة"],
    "Se connecter": ["Sign in", "تسجيل الدخول"],

    /* Map page */
    "Réseau national": ["National network", "الشبكة الوطنية"],
    "Visualisez l'état de l'électricité et de l'eau dans les 24 gouvernorats. Filtrez par statut, recherchez une zone et ouvrez la carte en plein écran.": [
      "View the status of electricity and water in all 24 governorates. Filter by status, search for an area and open the map in full screen.",
      "اطّلع على حالة الكهرباء والماء في الولايات الأربع والعشرين. صفِّ حسب الحالة، وابحث عن منطقة، وافتح الخريطة بملء الشاشة."
    ],
    "Signaler une coupure": ["Report an outage", "الإبلاغ عن انقطاع"],
    "Gouvernorats suivis": ["Governorates monitored", "ولاية متابَعة"],
    "Réseaux normaux": ["Normal networks", "شبكة عادية"],
    "Zones en vigilance": ["Areas under watch", "منطقة تحت المراقبة"],
    "Coupure en cours": ["Ongoing outage", "انقطاع جارٍ"],
    "Filtrer": ["Filter", "تصفية"],
    "Tous": ["All", "الكل"],
    "Rechercher un gouvernorat": ["Search for a governorate", "ابحث عن ولاية"],
    "Rechercher un gouvernorat…": ["Search for a governorate…", "ابحث عن ولاية…"],
    "Zones suivies": ["Monitored areas", "المناطق المتابَعة"],
    "zones affichées": ["areas shown", "منطقة معروضة"],
    "Réinitialiser": ["Reset", "إعادة التعيين"],
    "Aucune zone trouvée": ["No area found", "لم يتم العثور على أي منطقة"],
    "Modifiez la recherche ou réinitialisez les filtres.": ["Change your search or reset the filters.", "عدّل البحث أو أعد تعيين عوامل التصفية."],
    "Aucun incident récent pour ce gouvernorat.": ["No recent incident for this governorate.", "لا توجد حوادث حديثة في هذه الولاية."],
    "Citoyen": ["Citizen", "مواطن"],
    "Une coupure non signalée ? Aidez la communauté en partageant l'information en quelques secondes.": [
      "An unreported outage? Help the community by sharing the information in a few seconds.",
      "انقطاع لم يُبلَّغ عنه؟ ساعد المجتمع بمشاركة المعلومة في بضع ثوانٍ."
    ],
    "Signaler maintenant": ["Report now", "أبلغ الآن"],
    "Votre signalement sera visible par la communauté.": ["Your report will be visible to the community.", "سيكون بلاغك مرئيًا للمجتمع."],
    "Fermer": ["Close", "إغلاق"],
    "Gouvernorat": ["Governorate", "الولاية"],
    "Type de réseau": ["Network type", "نوع الشبكة"],
    "Adresse ou zone": ["Address or area", "العنوان أو المنطقة"],
    "Ex. Cité El Ons, rue 12": ["E.g. Cité El Ons, street 12", "مثال: حي الأنس، نهج 12"],
    "Description": ["Description", "الوصف"],
    "(facultatif)": ["(optional)", "(اختياري)"],
    "Décrivez la situation…": ["Describe the situation…", "صِف الوضع…"],
    "Annuler": ["Cancel", "إلغاء"],
    "Envoyer le signalement": ["Send report", "إرسال البلاغ"],
    "Envoi…": ["Sending…", "جارٍ الإرسال…"],
    "Sélectionner un gouvernorat…": ["Select a governorate…", "اختر ولاية…"],
    "Veuillez renseigner le gouvernorat et une adresse valide.": ["Please enter the governorate and a valid address.", "يُرجى تحديد الولاية وإدخال عنوان صالح."],
    "Merci ! Votre signalement a bien été enregistré (démonstration).": ["Thank you! Your report has been recorded (demo).", "شكرًا! تم تسجيل بلاغك (تجريبي)."],

    /* Analytics */
    "Observatoire": ["Observatory", "المرصد"],
    "Analyses & tendances": ["Analytics & trends", "التحليلات والاتجاهات"],
    "Suivez l'évolution de la consommation, identifiez les zones à risque et comparez les réseaux d'électricité et d'eau.": [
      "Follow consumption trends, identify at-risk areas and compare electricity and water networks.",
      "تابع تطوّر الاستهلاك، وحدّد المناطق المعرّضة للخطر، وقارن بين شبكتي الكهرباء والماء."
    ],
    "Le rapport PDF sera disponible prochainement.": ["The PDF report will be available soon.", "سيكون تقرير PDF متاحًا قريبًا."],
    "Télécharger le rapport": ["Download report", "تنزيل التقرير"],
    "Période d'analyse": ["Analysis period", "فترة التحليل"],
    "7 jours": ["7 days", "7 أيام"],
    "30 jours": ["30 days", "30 يومًا"],
    "12 mois": ["12 months", "12 شهرًا"],
    "Filtrer par gouvernorat": ["Filter by governorate", "التصفية حسب الولاية"],
    "Export CSV de démonstration.": ["Demo CSV export.", "تصدير CSV تجريبي."],
    "Exporter": ["Export", "تصدير"],
    "Électricité consommée": ["Electricity consumed", "الكهرباء المستهلكة"],
    "Eau consommée": ["Water consumed", "الماء المستهلك"],
    "Coupures signalées": ["Reported outages", "الانقطاعات المبلَّغ عنها"],
    "Stabilité moyenne": ["Average stability", "متوسط الاستقرار"],
    "1,24": ["1.24", "1,24"],
    "96,4": ["96.4", "96,4"],
    "Évolution de la consommation": ["Consumption trend", "تطوّر الاستهلاك"],
    "Indice de charge —": ["Load index —", "مؤشر الحمل —"],
    "7 derniers jours": ["Last 7 days", "آخر 7 أيام"],
    "30 derniers jours": ["Last 30 days", "آخر 30 يومًا"],
    "12 derniers mois": ["Last 12 months", "آخر 12 شهرًا"],
    "Données consolidées": ["Consolidated data", "بيانات موحّدة"],
    "Consommation par gouvernorat": ["Consumption by governorate", "الاستهلاك حسب الولاية"],
    "Top 6": ["Top 6", "أعلى 6"],
    "indice relatif": ["relative index", "مؤشر نسبي"],
    "Consommation relative par gouvernorat": ["Relative consumption by governorate", "الاستهلاك النسبي حسب الولاية"],
    "Derniers incidents": ["Latest incidents", "آخر الحوادث"],
    "Zone": ["Area", "المنطقة"],
    "Réseau": ["Network", "الشبكة"],
    "Statut": ["Status", "الحالة"],
    "Durée": ["Duration", "المدة"],
    "Dernière mise à jour": ["Last update", "آخر تحديث"],
    "Tous les gouvernorats": ["All governorates", "كل الولايات"],
    "Lun": ["Mon", "الإثنين"],
    "Mar": ["Tue", "الثلاثاء"],
    "Mer": ["Wed", "الأربعاء"],
    "Jeu": ["Thu", "الخميس"],
    "Ven": ["Fri", "الجمعة"],
    "Sam": ["Sat", "السبت"],
    "Dim": ["Sun", "الأحد"],
    "Jan": ["Jan", "جانفي"],
    "Fév": ["Feb", "فيفري"],
    "Mars": ["Mar", "مارس"],
    "Avr": ["Apr", "أفريل"],
    "Mai": ["May", "ماي"],
    "Juin": ["Jun", "جوان"],
    "Juil": ["Jul", "جويلية"],
    "Août": ["Aug", "أوت"],
    "Sep": ["Sep", "سبتمبر"],
    "Oct": ["Oct", "أكتوبر"],
    "Nov": ["Nov", "نوفمبر"],
    "Déc": ["Dec", "ديسمبر"],

    /* About */
    "Connecter les territoires, anticiper les crises, protéger les citoyens.": [
      "Connecting territories, anticipating crises, protecting citizens.",
      "ربط الجهات، استباق الأزمات، حماية المواطنين."
    ],
    "WASLA est née d'un constat simple : l'information sur les coupures d'eau et d'électricité doit être accessible, fiable et utile à tous.": [
      "WASLA was born from a simple observation: information about water and electricity outages must be accessible, reliable and useful to everyone.",
      "وُلدت WASLA من ملاحظة بسيطة: يجب أن تكون المعلومة حول انقطاعات الماء والكهرباء متاحة وموثوقة ومفيدة للجميع."
    ],
    "Rejoindre WASLA": ["Join WASLA", "انضم إلى WASLA"],
    "Une plateforme citoyenne, utile et transparente.": ["A useful, transparent civic platform.", "منصة مواطنية، مفيدة وشفافة."],
    "En Tunisie, les coupures d'eau et d'électricité affectent le quotidien de millions de foyers. WASLA rassemble les données disponibles, les rend lisibles et aide chacun à anticiper.": [
      "In Tunisia, water and electricity outages affect the daily lives of millions of households. WASLA brings together the available data, makes it readable and helps everyone anticipate.",
      "في تونس، تؤثّر انقطاعات الماء والكهرباء في الحياة اليومية لملايين الأسر. تجمع WASLA البيانات المتاحة، وتجعلها مفهومة، وتساعد الجميع على الاستباق."
    ],
    "Notre approche est simple : connecter les réseaux et les citoyens, anticiper les tensions grâce à l'analyse, et protéger les plus vulnérables en priorisant les besoins essentiels.": [
      "Our approach is simple: connect networks and citizens, anticipate pressure through analysis, and protect the most vulnerable by prioritising essential needs.",
      "مقاربتنا بسيطة: ربط الشبكات بالمواطنين، واستباق الضغوط بفضل التحليل، وحماية الفئات الأكثر هشاشة بإعطاء الأولوية للاحتياجات الأساسية."
    ],
    "WASLA est un prototype de démonstration. Les données affichées sont illustratives et servent à présenter l'expérience et les services de la plateforme.": [
      "WASLA is a demo prototype. The data shown is illustrative and serves to present the platform's experience and services.",
      "WASLA نموذج تجريبي. البيانات المعروضة توضيحية وتهدف إلى تقديم تجربة المنصة وخدماتها."
    ],
    "Découvrir les services": ["Discover the services", "اكتشف الخدمات"],
    "Explorer la carte": ["Explore the map", "استكشف الخريطة"],
    "Une énergie locale, propre et partagée pour les générations futures.": ["Local, clean and shared energy for future generations.", "طاقة محلية ونظيفة ومشتركة للأجيال القادمة."],
    "ENGAGEMENT": ["COMMITMENT", "التزام"],
    "Transparence": ["Transparency", "الشفافية"],
    "Des données claires et accessibles, pour des décisions éclairées.": ["Clear, accessible data for informed decisions.", "بيانات واضحة ومتاحة من أجل قرارات مستنيرة."],
    "Solidarité": ["Solidarity", "التضامن"],
    "Un réseau d'entraide qui relie voisins, fournisseurs et institutions.": ["A mutual-aid network linking neighbours, providers and institutions.", "شبكة تضامن تربط الجيران والمزوّدين والمؤسسات."],
    "Durabilité": ["Sustainability", "الاستدامة"],
    "Une consommation plus responsable et des ressources préservées.": ["More responsible consumption and preserved resources.", "استهلاك أكثر مسؤولية وموارد محفوظة."],
    "Protection": ["Protection", "الحماية"],
    "Les besoins vitaux prioritaires, surtout pour les plus vulnérables.": ["Vital needs first, especially for the most vulnerable.", "الأولوية للاحتياجات الحيوية، خاصة للفئات الأكثر هشاشة."],
    "Utilisateurs visés": ["Target users", "مستخدم مستهدف"],
    "Gouvernorats couverts": ["Governorates covered", "ولاية مشمولة"],
    "Prêt à agir pour une Tunisie plus résiliente ?": ["Ready to act for a more resilient Tunisia?", "مستعد للعمل من أجل تونس أكثر صمودًا؟"],
    "Créez votre espace citoyen et recevez des alertes adaptées à votre zone.": ["Create your citizen space and receive alerts tailored to your area.", "أنشئ فضاءك المواطني وتلقَّ تنبيهات ملائمة لمنطقتك."],
    "Nous contacter": ["Contact us", "اتصل بنا"],
    "Mentions légales —": ["Legal notice —", "الإشعار القانوني —"],
    "WASLA est un prototype de démonstration. Les données, chiffres et statuts affichés sont fictifs. Les photographies sont utilisées à des fins d'illustration.": [
      "WASLA is a demo prototype. The data, figures and statuses shown are fictitious. Photographs are used for illustration purposes.",
      "WASLA نموذج تجريبي. البيانات والأرقام والحالات المعروضة وهمية. تُستخدم الصور لأغراض توضيحية."
    ],
    "Confidentialité —": ["Privacy —", "الخصوصية —"],
    "Aucune donnée personnelle n'est collectée dans cette démonstration. Les formulaires sont simulés.": [
      "No personal data is collected in this demo. Forms are simulated.",
      "لا تُجمع أي بيانات شخصية في هذا العرض التجريبي. الاستمارات محاكاة."
    ],
    "Accessibilité —": ["Accessibility —", "إمكانية الوصول —"],
    "L'interface vise le niveau AA : contraste renforcé, navigation clavier et respect de la préférence de réduction des animations.": [
      "The interface targets level AA: enhanced contrast, keyboard navigation and respect for the reduced-motion preference.",
      "تستهدف الواجهة المستوى AA: تباين معزّز، وتنقّل عبر لوحة المفاتيح، واحترام تفضيل تقليل الحركة."
    ],

    /* Sign in */
    "Votre réseau,": ["Your network,", "شبكتكم،"],
    "en confiance.": ["with confidence.", "بكل ثقة."],
    "Connectez-vous pour suivre vos factures, recevoir des alertes personnalisées et accéder aux services prioritaires.": [
      "Sign in to track your bills, receive personalised alerts and access priority services.",
      "سجّل الدخول لمتابعة فواتيرك وتلقي تنبيهات مخصّصة والوصول إلى الخدمات ذات الأولوية."
    ],
    "Citoyens": ["Citizens", "مواطن"],
    "Espace citoyen": ["Citizen space", "الفضاء المواطني"],
    "Accédez à votre tableau de bord personnel et à vos alertes.": ["Access your personal dashboard and your alerts.", "ادخل إلى لوحة القيادة الشخصية وتنبيهاتك."],
    "Adresse e-mail": ["Email address", "البريد الإلكتروني"],
    "vous@exemple.tn": ["you@example.tn", "you@example.tn"],
    "Veuillez saisir une adresse e-mail valide.": ["Please enter a valid email address.", "يُرجى إدخال بريد إلكتروني صالح."],
    "Mot de passe": ["Password", "كلمة المرور"],
    "Votre mot de passe": ["Your password", "كلمة المرور الخاصة بك"],
    "Afficher le mot de passe": ["Show password", "إظهار كلمة المرور"],
    "Masquer le mot de passe": ["Hide password", "إخفاء كلمة المرور"],
    "Le mot de passe doit contenir au moins 6 caractères.": ["Password must be at least 6 characters long.", "يجب أن تحتوي كلمة المرور على 6 أحرف على الأقل."],
    "Se souvenir de moi": ["Remember me", "تذكّرني"],
    "Mot de passe oublié ?": ["Forgot password?", "نسيت كلمة المرور؟"],
    "La réinitialisation du mot de passe sera disponible prochainement.": ["Password reset will be available soon.", "ستكون إعادة تعيين كلمة المرور متاحة قريبًا."],
    "Pas encore de compte ?": ["No account yet?", "ليس لديك حساب بعد؟"],
    "Créer un compte": ["Create an account", "إنشاء حساب"],
    "L'inscription sera disponible prochainement.": ["Registration will be available soon.", "سيكون التسجيل متاحًا قريبًا."],
    "Démonstration :": ["Demo:", "تجريبي:"],
    "utilisez n'importe quelle adresse e-mail valide et un mot de passe de 6 caractères minimum. Aucune donnée n'est envoyée.": [
      "use any valid email address and a password of at least 6 characters. No data is sent.",
      "استخدم أي بريد إلكتروني صالح وكلمة مرور من 6 أحرف على الأقل. لا تُرسل أي بيانات."
    ],
    "Veuillez corriger les champs indiqués.": ["Please correct the highlighted fields.", "يُرجى تصحيح الحقول المشار إليها."],
    "Connexion…": ["Signing in…", "جارٍ تسجيل الدخول…"],
    "Créer mon compte": ["Create my account", "إنشاء حسابي"],
    "Création du compte…": ["Creating account…", "جارٍ إنشاء الحساب…"],
    "Déjà inscrit ?": ["Already registered?", "لديك حساب؟"],
    "Se déconnecter": ["Sign out", "تسجيل الخروج"],
    "Mon espace": ["My account", "حسابي"],
    "Administration": ["Administration", "الإدارة"],
    "Accès rapides": ["Quick access", "وصول سريع"],
    "Prénom": ["First name", "الاسم"],
    "Nom": ["Last name", "اللقب"],
    "Téléphone": ["Phone", "الهاتف"],
    "Confirmation": ["Confirm password", "تأكيد كلمة المرور"],

    /* Authentification, compte, erreurs et contrôle de saisie (branche main) */
    "Espace WASLA": ["WASLA account", "فضاء وصلة"],
    "Accédez à votre espace personnel et à vos alertes.": ["Access your personal space and your alerts.", "ادخل إلى فضائك الشخصي وتنبيهاتك."],
    "Connectez-vous à votre espace WASLA.": ["Sign in to your WASLA account.", "سجّل الدخول إلى فضائك في وصلة."],
    "Rejoignez": ["Join", "انضمّ إلى"],
    "WASLA.": ["WASLA.", "وصلة."],
    "Créez votre compte en une minute pour recevoir les alertes de votre gouvernorat, suivre vos factures et accéder aux services prioritaires.": ["Create your account in a minute to receive alerts for your governorate, track your bills and access priority services.", "أنشئ حسابك في دقيقة لتتلقّى تنبيهات ولايتك، وتتابع فواتيرك، وتصل إلى الخدمات ذات الأولوية."],
    "Renseignez vos informations, puis connectez-vous.": ["Fill in your details, then sign in.", "أدخل معلوماتك، ثم سجّل الدخول."],
    "8 caractères min., une lettre et un chiffre.": ["At least 8 characters, one letter and one digit.", "8 أحرف على الأقل، حرف ورقم."],
    "J'accepte les conditions d'utilisation et la politique de confidentialité de WASLA.": ["I accept WASLA's terms of use and privacy policy.", "أوافق على شروط استخدام وصلة وسياسة الخصوصية."],
    "Créez votre compte citoyen WASLA.": ["Create your WASLA citizen account.", "أنشئ حسابك كمواطن في وصلة."],
    "Votre espace personnel WASLA.": ["Your personal WASLA space.", "فضاؤك الشخصي في وصلة."],
    "Retrouvez ici l'état du réseau dans votre gouvernorat, vos analyses et les services WASLA.": ["Find here the network status in your governorate, your analytics and WASLA services.", "تجد هنا حالة الشبكة في ولايتك وتحليلاتك وخدمات وصلة."],
    "Dernière connexion": ["Last sign-in", "آخر تسجيل دخول"],
    "Analyses & consommation": ["Analytics & consumption", "التحليلات والاستهلاك"],
    "Suivez les tendances de consommation et comparez-les à la moyenne de votre zone.": ["Follow consumption trends and compare them with your area's average.", "تابع اتجاهات الاستهلاك وقارنها بمعدّل منطقتك."],
    "Soyez prévenu des interruptions planifiées et des incidents dans votre zone.": ["Be notified of planned outages and incidents in your area.", "كن على علم بالانقطاعات المبرمجة والحوادث في منطقتك."],
    "Factures": ["Bills", "الفواتير"],
    "Analysez vos factures STEG et SONEDE et recevez des recommandations.": ["Analyse your STEG and SONEDE bills and get recommendations.", "حلّل فواتير الستاغ والصوناد واحصل على توصيات."],
    "Trouvez un lieu pour accéder à l'eau ou à l'électricité pendant une coupure.": ["Find a place with water or electricity during an outage.", "اعثر على مكان للحصول على الماء أو الكهرباء أثناء الانقطاع."],
    "Assistance d'urgence": ["Emergency assistance", "المساعدة العاجلة"],
    "Demandez de l'aide à un bénévole, un fournisseur ou un propriétaire solaire.": ["Ask a volunteer, a supplier or a solar owner for help.", "اطلب المساعدة من متطوّع أو مزوّد أو مالك ألواح شمسية."],
    "Erreur 403": ["Error 403", "خطأ 403"],
    "Erreur 404": ["Error 404", "خطأ 404"],
    "Accès refusé": ["Access denied", "الدخول مرفوض"],
    "Accès refusé — WASLA": ["Access denied — WASLA", "الدخول مرفوض — وصلة"],
    "Page introuvable": ["Page not found", "الصفحة غير موجودة"],
    "Page introuvable — WASLA": ["Page not found — WASLA", "الصفحة غير موجودة — وصلة"],
    "Vous n'avez pas les droits nécessaires pour accéder à cette page.": ["You don't have permission to access this page.", "ليست لديك الصلاحيات اللازمة للوصول إلى هذه الصفحة."],
    "La page demandée n'existe pas ou n'est pas encore disponible.": ["The requested page doesn't exist or isn't available yet.", "الصفحة المطلوبة غير موجودة أو غير متاحة بعد."],
    "Retour à l'accueil": ["Back to home", "العودة إلى الرئيسية"],
    "Retour à mon espace": ["Back to my account", "العودة إلى حسابي"],
    "WASLA : surveiller, anticiper et gérer les coupures d'eau et d'électricité en Tunisie.": ["WASLA: monitor, anticipate and manage water and power outages in Tunisia.", "وصلة: مراقبة انقطاعات الماء والكهرباء في تونس واستباقها وإدارتها."],
    "Ce champ est obligatoire.": ["This field is required.", "هذا الحقل إجباري."],
    "Veuillez saisir votre adresse e-mail.": ["Please enter your e-mail address.", "يرجى إدخال بريدك الإلكتروني."],
    "Veuillez saisir votre mot de passe.": ["Please enter your password.", "يرجى إدخال كلمة المرور."],
    "Lettres, espaces, apostrophes et tirets uniquement (2 à 50 caractères).": ["Letters, spaces, apostrophes and hyphens only (2 to 50 characters).", "أحرف ومسافات وفواصل علوية وشرطات فقط (من 2 إلى 50 حرفًا)."],
    "Numéro tunisien à 8 chiffres attendu (ex. 22 123 456).": ["8-digit Tunisian number expected (e.g. 22 123 456).", "يُنتظر رقم تونسي من 8 أرقام (مثال: 22 123 456)."],
    "Au moins 8 caractères, dont une lettre et un chiffre.": ["At least 8 characters, including one letter and one digit.", "8 أحرف على الأقل، منها حرف ورقم."],
    "Les deux valeurs ne correspondent pas.": ["The two values don't match.", "القيمتان غير متطابقتين."],
    "Les mots de passe ne correspondent pas.": ["Passwords don't match.", "كلمتا المرور غير متطابقتين."],
    "Valeur non autorisée.": ["Value not allowed.", "قيمة غير مسموح بها."],
    "Nombre entier attendu.": ["Whole number expected.", "يُنتظر عدد صحيح."],
    "Nombre attendu (ex. 45,500).": ["Number expected (e.g. 45.500).", "يُنتظر رقم (مثال: 45,500)."],
    "La valeur doit être supérieure à 0.": ["The value must be greater than 0.", "يجب أن تكون القيمة أكبر من 0."],
    "Date invalide (format AAAA-MM-JJ).": ["Invalid date (YYYY-MM-DD).", "تاريخ غير صالح (الصيغة سنة-شهر-يوم)."],
    "Veuillez saisir un nombre.": ["Please enter a number.", "يرجى إدخال رقم."],
    "Veuillez saisir un nombre entier.": ["Please enter a whole number.", "يرجى إدخال عدد صحيح."],
    "Cette date doit être postérieure à la précédente.": ["This date must be after the previous one.", "يجب أن يكون هذا التاريخ بعد التاريخ السابق."],
    "Entreprise": ["Business", "مؤسسة"],
    "Fournisseur": ["Supplier", "مزوّد"],
    "Nom commercial": ["Trade name", "الاسم التجاري"],
    "Matricule fiscal": ["Tax ID", "المعرّف الجبائي"],
    "Secteur": ["Sector", "القطاع"],
    "Spécialité": ["Specialty", "الاختصاص"],
    "Compte vérifié": ["Verified account", "حساب موثّق"],
    "Oui": ["Yes", "نعم"],
    "En attente de validation par l'administrateur": ["Awaiting administrator approval", "في انتظار مصادقة المشرف"],
    "Électricien": ["Electrician", "كهربائي"],
    "Plombier": ["Plumber", "سبّاك"],
    "Technicien de maintenance": ["Maintenance technician", "تقني صيانة"],
    "Installateur solaire": ["Solar installer", "مركّب ألواح شمسية"],
    "Spécialiste hydraulique": ["Hydraulics specialist", "مختص في المائيات"],
    "Veuillez cocher cette case.": ["Please tick this box.", "يرجى تحديد هذه الخانة."],
    "Veuillez choisir votre gouvernorat.": ["Please choose your governorate.", "يرجى اختيار ولايتك."],
    "Veuillez accepter les conditions d'utilisation.": ["Please accept the terms of use.", "يرجى قبول شروط الاستخدام."],
    "Adresse e-mail ou mot de passe incorrect.": ["Incorrect e-mail address or password.", "البريد الإلكتروني أو كلمة المرور غير صحيحة."],
    "Un compte existe déjà avec cette adresse e-mail.": ["An account already exists with this e-mail address.", "يوجد حساب مسجّل بهذا البريد الإلكتروني."],
    "Votre compte a été créé. Vous pouvez maintenant vous connecter.": ["Your account has been created. You can now sign in.", "تمّ إنشاء حسابك. يمكنك الآن تسجيل الدخول."],
    "Vous êtes déconnecté. À bientôt !": ["You are signed out. See you soon!", "تمّ تسجيل خروجك. إلى اللقاء!"],
    "Veuillez vous connecter pour accéder à cette page.": ["Please sign in to access this page.", "يرجى تسجيل الدخول للوصول إلى هذه الصفحة."],
    "Jeton de sécurité invalide ou expiré. Veuillez réessayer.": ["Invalid or expired security token. Please try again.", "رمز الأمان غير صالح أو منتهي الصلاحية. يرجى إعادة المحاولة."],
    "Votre session a expiré après 30 minutes d'inactivité. Veuillez vous reconnecter.": ["Your session expired after 30 minutes of inactivity. Please sign in again.", "انتهت جلستك بعد 30 دقيقة من عدم النشاط. يرجى تسجيل الدخول من جديد."],
    "Votre compte est suspendu. Contactez l'administration de WASLA.": ["Your account is suspended. Please contact WASLA administration.", "حسابك معلّق. يرجى الاتصال بإدارة وصلة."],
    "La Manouba": ["La Manouba", "منوبة"],
    "Le Kef": ["Le Kef", "الكاف"],
    "Choisir…": ["Choose…", "اختر…"],
    "Membre depuis": ["Member since", "عضو منذ"],
    "Connexion de démonstration réussie. Bienvenue sur WASLA !": ["Demo sign-in successful. Welcome to WASLA!", "تم تسجيل الدخول التجريبي بنجاح. مرحبًا بك في WASLA!"]
  };

  /* Strings that stay identical in every language */
  const KEEP = new Set(["WASLA", "GWh", "k m³", "Ctrl+K", "PDF", "CSV"]);
  const NEUTRAL = /^[\s\d%+−\-–—.,:;!?·()«»"'/×↑↓©]*$/;

  function arabicCount(n, one, two, few, many) {
    if (n === 1) return one;
    if (n === 2) return two;
    if (n >= 3 && n <= 10) return `${n} ${few}`;
    return `${n} ${many}`;
  }

  const PATTERNS = [
    [/^Valeur minimale : ([\d.,]+)\.$/, (m, l) => (l === "en" ? `Minimum value: ${m[1]}.` : `القيمة الدنيا: ${m[1]}.`)],
    [/^Valeur maximale : ([\d.,]+)\.$/, (m, l) => (l === "en" ? `Maximum value: ${m[1]}.` : `القيمة القصوى: ${m[1]}.`)],
    [/^Bienvenue, ([\s\S]+) !$/, (m, l) => (l === "en" ? `Welcome, ${m[1]}!` : `مرحبًا، ${m[1]}!`)],
    [/^Bonjour, ([\s\S]+)$/, (m, l) => (l === "en" ? `Hello, ${m[1]}` : `أهلًا، ${m[1]}`)],
    [/^Trop de tentatives\. Réessayez dans (\d+) secondes\.$/, (m, l) => (l === "en" ? `Too many attempts. Try again in ${m[1]} seconds.` : `محاولات كثيرة. أعد المحاولة بعد ${m[1]} ثانية.`)],
    [/^Au moins (\d+) caractères\.$/, (m, l) => (l === "en" ? `At least ${m[1]} characters.` : `${m[1]} أحرف على الأقل.`)],
    [/^Au plus (\d+) caractères\.$/, (m, l) => (l === "en" ? `At most ${m[1]} characters.` : `${m[1]} حرفًا على الأكثر.`)],
    [/^Consultez l'état de l'électricité et de l'eau à ([\s\S]+) et signalez une coupure\.$/, (m, l) => (l === "en" ? `Check electricity and water status in ${m[1]} and report an outage.` : `اطّلع على حالة الكهرباء والماء في ${m[1]} وبلّغ عن انقطاع.`)],
    [/^il y a (\d+) min$/, (m, l) => (l === "en" ? `${m[1]} min ago` : `منذ ${m[1]} دقيقة`)],
    [/^il y a (\d+) h$/, (m, l) => (l === "en" ? `${m[1]} h ago` : `منذ ${m[1]} ساعة`)],
    [/^(\d+) zones?$/, (m, l) => {
      const n = Number(m[1]);
      if (l === "en") return `${n} ${n === 1 ? "area" : "areas"}`;
      return arabicCount(n, "منطقة واحدة", "منطقتان", "مناطق", "منطقة");
    }],
    [/^Zone (\d+)$/, (m, l) => (l === "en" ? `Area ${m[1]}` : `المنطقة ${m[1]}`)],
    [/^(\d+) h (\d+)$/, (m, l) => (l === "en" ? `${m[1]} h ${m[2]} min` : `${m[1]} س ${m[2]} د`)],
    [/^(\d+) min$/, (m, l) => (l === "en" ? `${m[1]} min` : `${m[1]} دقيقة`)],
    [/^Sem\. (\d+)$/, (m, l) => (l === "en" ? `Wk ${m[1]}` : `الأسبوع ${m[1]}`)],
    [/^Aucun résultat pour « ([\s\S]*) »\.$/, (m, l) => (l === "en" ? `No results for “${m[1]}”.` : `لا توجد نتائج لـ « ${m[1]} ».`)],
    [/^(.+?) vs période précédente$/, (m, l) => {
      if (l === "en") {
        const value = m[1].replace(/(\d),(\d)/g, "$1.$2").replace(/\s%/g, "%").replace(/\bpoint\b/, "pt");
        return `${value} vs previous period`;
      }
      return `${m[1].replace(/\bpoint\b/, "نقطة")} مقارنة بالفترة السابقة`;
    }]
  ];

  const SEPARATORS = [" — ", " · ", " : "];

  /* Core lookup ---------------------------------------------- */

  function translate(text, lang) {
    const key = text.replace(/\s+/g, " ").trim();
    if (!key) return text;
    const entry = D[key];
    if (entry) return entry[lang === "en" ? 0 : 1];
    if (NEUTRAL.test(key) || KEEP.has(key)) return key;
    for (let i = 0; i < PATTERNS.length; i++) {
      const match = key.match(PATTERNS[i][0]);
      if (match) return PATTERNS[i][1](match, lang);
    }
    for (let i = 0; i < SEPARATORS.length; i++) {
      const sep = SEPARATORS[i];
      if (key.indexOf(sep) === -1) continue;
      const parts = key.split(sep).map((part) => translate(part, lang));
      if (parts.every((part) => part !== null)) return parts.join(sep);
    }
    return null;
  }

  const missing = new Set();
  let current = PREFS ? PREFS.getLang() : "fr";

  function localize(source, lang) {
    if (lang === "fr") return source;
    const match = /^(\s*)([\s\S]*?)(\s*)$/.exec(source);
    if (!match[2]) return source;
    const out = translate(match[2], lang);
    if (out === null) {
      missing.add(match[2]);
      return source;
    }
    return match[1] + out + match[3];
  }

  function t(source) {
    return localize(String(source), current);
  }

  /* DOM translation ------------------------------------------ */

  const ATTRS = ["aria-label", "placeholder", "title", "alt"];
  const SKIP_SELECTOR = "script, style, noscript, textarea, [data-no-i18n], [translate='no']";
  // Form fields keep user-typed content, but their placeholder/label attributes are translated.
  const ATTR_SKIP_SELECTOR = "[data-no-i18n], [translate='no']";
  const textRecords = new WeakMap();
  const attrRecords = new WeakMap();

  function isSkipped(element) {
    return !element || !!element.closest(SKIP_SELECTOR);
  }

  function isAttrSkipped(element) {
    return !element || !!element.closest(ATTR_SKIP_SELECTOR);
  }

  function translateTextNode(node) {
    const value = node.data;
    let record = textRecords.get(node);
    if (!record || value !== record.out) {
      record = { src: value, out: value };
      textRecords.set(node, record);
    }
    const out = localize(record.src, current);
    record.out = out;
    if (value !== out) node.data = out;
  }

  function translateAttr(element, name) {
    const value = element.getAttribute(name);
    if (value === null) return;
    let records = attrRecords.get(element);
    if (!records) {
      records = {};
      attrRecords.set(element, records);
    }
    let record = records[name];
    if (!record || value !== record.out) {
      record = { src: value, out: value };
      records[name] = record;
    }
    const out = localize(record.src, current);
    record.out = out;
    if (value !== out) element.setAttribute(name, out);
  }

  function translateElementAttrs(element) {
    for (let i = 0; i < ATTRS.length; i++) {
      if (element.hasAttribute(ATTRS[i])) translateAttr(element, ATTRS[i]);
    }
    if (element.tagName === "META" && element.getAttribute("name") === "description") {
      translateAttr(element, "content");
    }
  }

  function walk(root) {
    if (root.nodeType === 1) {
      if (isAttrSkipped(root)) return;
      translateElementAttrs(root);
      if (isSkipped(root)) return;
    }
    const walker = document.createTreeWalker(root, NodeFilter.SHOW_ELEMENT | NodeFilter.SHOW_TEXT, {
      acceptNode(node) {
        if (node.nodeType === 3) return NodeFilter.FILTER_ACCEPT;
        return node.matches(SKIP_SELECTOR) ? NodeFilter.FILTER_REJECT : NodeFilter.FILTER_SKIP;
      }
    });
    let node = walker.nextNode();
    while (node) {
      if (node.nodeType === 3) {
        if (node.data.trim()) translateTextNode(node);
      }
      node = walker.nextNode();
    }
    if (root.nodeType === 1 || root.nodeType === 9) {
      const elements = (root.nodeType === 9 ? root.documentElement : root).querySelectorAll("[aria-label], [placeholder], [title], [alt], meta[name='description']");
      elements.forEach((element) => {
        if (!isAttrSkipped(element)) translateElementAttrs(element);
      });
    }
  }

  const observer = new MutationObserver((records) => {
    for (let i = 0; i < records.length; i++) {
      const record = records[i];
      if (record.type === "childList") {
        record.addedNodes.forEach((node) => {
          if (node.nodeType === 3) {
            if (node.data.trim() && !isSkipped(node.parentElement)) translateTextNode(node);
          } else if (node.nodeType === 1) {
            walk(node);
          }
        });
      } else if (record.type === "characterData") {
        const node = record.target;
        if (node.data.trim() && !isSkipped(node.parentElement)) translateTextNode(node);
      } else if (record.type === "attributes") {
        if (!isAttrSkipped(record.target)) translateAttr(record.target, record.attributeName);
      }
    }
  });

  function applyAll() {
    walk(document);
  }

  function setLang(lang, options) {
    if (LANGS.indexOf(lang) === -1) return;
    const opts = options || {};
    if (opts.persist !== false && PREFS) PREFS.saveLang(lang);
    if (lang === current) return;

    const run = () => {
      current = lang;
      if (PREFS) PREFS.applyLangAttributes(lang);
      applyAll();
      document.dispatchEvent(new CustomEvent("wasla:langchange", { detail: { lang } }));
    };

    const canAnimate = document.startViewTransition && !(PREFS && PREFS.reducedMotion()) && opts.animate !== false;
    if (canAnimate) document.startViewTransition(run);
    else run();
  }

  window.WASLA_t = t;
  window.WASLA_I18N = {
    t,
    translate,
    setLang,
    getLang: () => current,
    missing
  };

  /* Boot: translate the parsed document, then follow changes. */
  applyAll();
  observer.observe(document.documentElement, {
    subtree: true,
    childList: true,
    characterData: true,
    attributes: true,
    attributeFilter: ATTRS
  });
  document.documentElement.classList.remove("i18n-pending");
})();
