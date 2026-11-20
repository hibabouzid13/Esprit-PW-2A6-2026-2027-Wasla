/* ============================================================
   WASLA — Preferences bootstrap (theme, language, motion)
   Loaded synchronously in <head> so the first paint is correct.
   ============================================================ */
(function () {
  "use strict";

  const root = document.documentElement;
  const THEME_KEY = "wasla-theme";
  const LANG_KEY = "wasla-lang";
  const LANGS = ["fr", "en", "ar"];
  const THEME_COLORS = { light: "#003B5C", dark: "#071723" };

  function read(key) {
    try {
      return localStorage.getItem(key);
    } catch (error) {
      return null;
    }
  }

  function write(key, value) {
    try {
      localStorage.setItem(key, value);
    } catch (error) {
      /* storage unavailable: preference lasts for this page only */
    }
  }

  const darkQuery = window.matchMedia ? window.matchMedia("(prefers-color-scheme: dark)") : null;
  const motionQuery = window.matchMedia ? window.matchMedia("(prefers-reduced-motion: reduce)") : null;

  function storedTheme() {
    const value = read(THEME_KEY);
    return value === "dark" || value === "light" ? value : null;
  }

  function resolvedTheme() {
    return storedTheme() || (darkQuery && darkQuery.matches ? "dark" : "light");
  }

  function applyTheme(theme) {
    root.setAttribute("data-theme", theme);
    root.style.colorScheme = theme;
    const meta = document.querySelector('meta[name="theme-color"]');
    if (meta) meta.setAttribute("content", THEME_COLORS[theme]);
  }

  function setTheme(theme, options) {
    const animate = !(options && options.animate === false) && !reducedMotion();
    if (animate) {
      root.classList.add("theme-transition");
      window.clearTimeout(setTheme.timer);
      setTheme.timer = window.setTimeout(() => root.classList.remove("theme-transition"), 420);
    }
    write(THEME_KEY, theme);
    applyTheme(theme);
    document.dispatchEvent(new CustomEvent("wasla:themechange", { detail: { theme } }));
  }

  function storedLang() {
    const value = read(LANG_KEY);
    return LANGS.indexOf(value) !== -1 ? value : "fr";
  }

  function applyLangAttributes(lang) {
    root.setAttribute("lang", lang);
    root.setAttribute("dir", lang === "ar" ? "rtl" : "ltr");
  }

  function reducedMotion() {
    return !!(motionQuery && motionQuery.matches);
  }

  /* Initial state -------------------------------------------- */

  applyTheme(resolvedTheme());

  const lang = storedLang();
  applyLangAttributes(lang);
  // Hide the French source text until i18n.js has translated it.
  if (lang !== "fr") {
    root.classList.add("i18n-pending");
    window.setTimeout(() => root.classList.remove("i18n-pending"), 2500);
  }

  if (!reducedMotion() && "IntersectionObserver" in window) {
    root.classList.add("reveal-on");
    window.setTimeout(() => {
      if (!window.WASLA_MOTION) root.classList.remove("reveal-on");
    }, 4000);
  }

  // Entrance animation only when the browser did not run a cross-document view transition.
  if (!("onpagereveal" in window)) {
    root.classList.add("page-enter");
  } else {
    window.addEventListener("pagereveal", (event) => {
      if (!event.viewTransition) root.classList.add("page-enter");
    });
  }

  /* Live sync ------------------------------------------------ */

  if (darkQuery && darkQuery.addEventListener) {
    darkQuery.addEventListener("change", () => {
      if (!storedTheme()) applyTheme(resolvedTheme());
    });
  }

  window.addEventListener("storage", (event) => {
    if (event.key === THEME_KEY) applyTheme(resolvedTheme());
    if (event.key === LANG_KEY && window.WASLA_I18N) window.WASLA_I18N.setLang(storedLang(), { persist: false });
  });

  window.WASLA_PREFS = {
    langs: LANGS,
    getTheme: resolvedTheme,
    setTheme,
    toggleTheme() {
      setTheme(resolvedTheme() === "dark" ? "light" : "dark");
    },
    getLang: storedLang,
    saveLang(value) {
      write(LANG_KEY, value);
    },
    applyLangAttributes,
    reducedMotion
  };
})();
