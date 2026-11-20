/* ============================================================
   WASLA — Tunisia map + hero network
   ============================================================ */
(function () {
  "use strict";

  const DATA = window.WASLA_DATA;
  const GEO = window.WASLA_TUNISIA;
  const NS = "http://www.w3.org/2000/svg";
  const icon = window.WASLA_icon;

  const state = {
    scale: 1,
    x: 0,
    y: 0,
    dragging: false,
    pointerId: null,
    startX: 0,
    startY: 0,
    originX: 0,
    originY: 0,
    selected: null
  };

  let canvas = null;
  let svg = null;
  let panGroup = null;
  let tooltip = null;
  let markers = [];
  let mode = "home";

  function el(name, attrs) {
    const node = document.createElementNS(NS, name);
    if (attrs) Object.keys(attrs).forEach((key) => node.setAttribute(key, attrs[key]));
    return node;
  }

  function project(lon, lat) {
    const p = GEO.projection;
    return [
      p.offsetX + (lon - p.minLon) * p.cosLat * p.scale,
      p.offsetY + (p.maxLat - lat) * p.scale
    ];
  }

  function statusClass(status) {
    return status === "watch" ? "watch" : status === "outage" ? "outage" : status === "nodata" ? "nodata" : "normal";
  }

  /* Hero network ------------------------------------------- */

  function renderHeroNetwork() {
    const host = document.querySelector("[data-hero-network]");
    if (!host || !GEO) return;

    const svgNode = el("svg", {
      viewBox: GEO.viewBox,
      preserveAspectRatio: "xMidYMid meet",
      role: "img",
      "aria-label": "Réseau tunisien connecté"
    });

    const glow = el("g");
    glow.appendChild(el("path", { class: "tn-glow", d: GEO.outlinePath }));
    svgNode.appendChild(glow);
    svgNode.appendChild(el("path", { class: "tn-fill", d: GEO.outlinePath }));
    svgNode.appendChild(el("path", { class: "tn-outline", d: GEO.outlinePath }));

    [255, 300].forEach((radius) => {
      svgNode.appendChild(el("circle", { class: "orbit", cx: "200", cy: "310", r: String(radius) }));
    });

    const points = DATA.governorates.map((zone) => ({
      zone,
      point: project(zone.lon, zone.lat)
    }));

    const lines = el("g");
    const seen = new Set();
    points.forEach((item, i) => {
      let nearest = null;
      let nearestDistance = Infinity;
      points.forEach((other, j) => {
        if (i === j) return;
        const dx = item.point[0] - other.point[0];
        const dy = item.point[1] - other.point[1];
        const distance = Math.sqrt(dx * dx + dy * dy);
        if (distance < nearestDistance) {
          nearestDistance = distance;
          nearest = j;
        }
      });
      if (nearest !== null && nearestDistance < 125) {
        const key = [i, nearest].sort((a, b) => a - b).join("-");
        if (!seen.has(key)) {
          seen.add(key);
          const other = points[nearest];
          lines.appendChild(
            el("line", {
              class: "mesh-line",
              x1: item.point[0],
              y1: item.point[1],
              x2: other.point[0],
              y2: other.point[1]
            })
          );
        }
      }
    });
    svgNode.appendChild(lines);

    const nodes = el("g");
    points.forEach((item, index) => {
      const node = el("circle", {
        class: `mesh-node${index % 5 === 0 ? " mesh-node--green" : ""}`,
        cx: item.point[0],
        cy: item.point[1],
        r: index % 5 === 0 ? "2.2" : "1.5"
      });
      nodes.appendChild(node);
    });
    svgNode.appendChild(nodes);

    host.innerHTML = "";
    host.appendChild(svgNode);
  }

  /* Map ---------------------------------------------------- */

  function buildMap() {
    canvas = document.querySelector("[data-map-canvas]");
    if (!canvas || !GEO) return;

    mode = canvas.getAttribute("data-map-mode") || "home";
    const card = canvas.closest("[data-map-card]");
    tooltip = canvas.querySelector("[data-map-tooltip]");

    const svgNode = el("svg", {
      class: "map-svg",
      viewBox: GEO.viewBox,
      preserveAspectRatio: "xMidYMid meet",
      role: "img",
      "aria-label": "Carte des réseaux d'électricité et d'eau en Tunisie"
    });

    const defs = el("defs");
    const landGradient = el("linearGradient", { id: "landGradient", x1: "0", y1: "0", x2: "1", y2: "1" });
    landGradient.appendChild(el("stop", { offset: "0%", style: "stop-color: var(--map-land-1)" }));
    landGradient.appendChild(el("stop", { offset: "55%", style: "stop-color: var(--map-land-2)" }));
    landGradient.appendChild(el("stop", { offset: "100%", style: "stop-color: var(--map-land-3)" }));
    defs.appendChild(landGradient);

    const shadow = el("filter", { id: "landShadow", x: "-20%", y: "-20%", width: "140%", height: "140%" });
    const drop = el("feDropShadow", {
      dx: "0",
      dy: "4",
      stdDeviation: "4",
      "flood-color": "#003B5C",
      "flood-opacity": "0.22"
    });
    shadow.appendChild(drop);
    defs.appendChild(shadow);
    svgNode.appendChild(defs);

    panGroup = el("g", { class: "map-pan-zoom" });

    panGroup.appendChild(
      el("path", {
        class: "map-land",
        d: GEO.outlinePath,
        fill: "url(#landGradient)",
        filter: "url(#landShadow)"
      })
    );

    const regions = el("g", { class: "map-regions" });
    GEO.regions.forEach((region) => {
      const path = el("path", { class: "map-region", d: region.path });
      const title = el("title");
      title.textContent = region.name;
      path.appendChild(title);
      regions.appendChild(path);
    });
    panGroup.appendChild(regions);

    panGroup.appendChild(el("path", { class: "map-coast", d: GEO.outlinePath }));

    // Decorative labels around the map.
    const labels = el("g", { "aria-hidden": "true" });
    const sea = el("text", {
      x: "300",
      y: "72",
      class: "map-label",
      "font-family": "Inter, sans-serif",
      "font-size": "9",
      "font-weight": "600",
      "letter-spacing": "0.12em",
      "text-anchor": "middle"
    });
    sea.textContent = "MÉDITERRANÉE";
    labels.appendChild(sea);

    const algeria = el("text", {
      x: "24",
      y: "310",
      class: "map-label map-label--soft",
      "font-family": "Inter, sans-serif",
      "font-size": "8",
      "font-weight": "600",
      "letter-spacing": "0.1em",
      transform: "rotate(-90 24 310)",
      "text-anchor": "middle"
    });
    algeria.textContent = "ALGÉRIE";
    labels.appendChild(algeria);

    const libya = el("text", {
      x: "374",
      y: "404",
      class: "map-label map-label--soft",
      "font-family": "Inter, sans-serif",
      "font-size": "8",
      "font-weight": "600",
      "letter-spacing": "0.1em",
      "text-anchor": "middle"
    });
    libya.textContent = "LIBYE";
    labels.appendChild(libya);
    panGroup.appendChild(labels);

    // Mesh lines between nearby governorates.
    const points = DATA.governorates.map((zone) => ({ zone, point: project(zone.lon, zone.lat) }));
    const network = el("g", { class: "map-network" });
    const seen = new Set();
    points.forEach((item, i) => {
      let nearest = null;
      let nearestDistance = Infinity;
      points.forEach((other, j) => {
        if (i === j) return;
        const dx = item.point[0] - other.point[0];
        const dy = item.point[1] - other.point[1];
        const distance = Math.sqrt(dx * dx + dy * dy);
        if (distance < nearestDistance) {
          nearestDistance = distance;
          nearest = j;
        }
      });
      if (nearest !== null && nearestDistance < 90) {
        const key = [i, nearest].sort((a, b) => a - b).join("-");
        if (!seen.has(key)) {
          seen.add(key);
          const other = points[nearest];
          network.appendChild(
            el("line", {
              x1: item.point[0],
              y1: item.point[1],
              x2: other.point[0],
              y2: other.point[1]
            })
          );
        }
      }
    });
    panGroup.appendChild(network);

    // Markers.
    const markerGroup = el("g");
    markers = DATA.governorates.map((zone) => {
      const point = project(zone.lon, zone.lat);
      const group = el("g", {
        class: `map-marker map-marker--${statusClass(zone.status)}`,
        transform: `translate(${point[0]} ${point[1]})`,
        tabindex: "0",
        role: "button",
        "data-zone": zone.name,
        "aria-label": `${zone.name} — ${DATA.statuses[zone.status].label}`
      });
      group.appendChild(el("circle", { class: "map-marker__halo", r: "8" }));
      group.appendChild(el("circle", { class: "map-marker__ring", r: "5.8" }));
      group.appendChild(el("circle", { class: "map-marker__dot", r: "3.4" }));

      const title = el("title");
      title.textContent = `${zone.name} — ${DATA.statuses[zone.status].label}`;
      group.appendChild(title);

      group.addEventListener("mouseenter", () => showTooltip(group, zone));
      group.addEventListener("focus", () => showTooltip(group, zone));
      group.addEventListener("mouseleave", hideTooltip);
      group.addEventListener("blur", hideTooltip);
      group.addEventListener("click", (event) => {
        event.stopPropagation();
        selectZone(zone, group);
      });
      group.addEventListener("keydown", (event) => {
        if (event.key === "Enter" || event.key === " ") {
          event.preventDefault();
          selectZone(zone, group);
        }
      });

      markerGroup.appendChild(group);
      return { zone, group, point };
    });
    panGroup.appendChild(markerGroup);

    svgNode.appendChild(panGroup);
    canvas.insertBefore(svgNode, canvas.firstChild);
    svg = svgNode;

    setupMapControls(card);
    setupPanZoom(canvas);
    applyTransform();
  }

  function showTooltip(marker, zone) {
    if (!tooltip) return;
    const canvasRect = canvas.getBoundingClientRect();
    const markerRect = marker.getBoundingClientRect();
    const status = DATA.statuses[zone.status];
    tooltip.innerHTML = `
      <strong>${zone.name}</strong>
      <span><span class="map-tooltip__dot" style="background:${status.color}"></span>${status.label}</span>
      <span>${zone.zones} zones · ${zone.updated}</span>`;
    tooltip.style.left = `${markerRect.left + markerRect.width / 2 - canvasRect.left}px`;
    tooltip.style.top = `${markerRect.top - canvasRect.top}px`;
    tooltip.hidden = false;
  }

  function hideTooltip() {
    if (tooltip && !state.selected) tooltip.hidden = true;
  }

  function selectZone(zone, group) {
    if (state.selected) state.selected.classList.remove("is-selected");
    state.selected = group;
    group.classList.add("is-selected");
    showTooltip(group, zone);
    document.dispatchEvent(new CustomEvent("wasla:zone-select", { detail: { zone } }));
    if (mode !== "page" && window.WASLA_toast) {
      window.WASLA_toast(`${zone.name} — ${DATA.statuses[zone.status].label}`);
    }
  }

  function applyTransform() {
    if (!panGroup) return;
    // A CSS transform (in SVG user units) lets zoom steps animate via the stylesheet.
    panGroup.style.transform = `translate(${state.x}px, ${state.y}px) scale(${state.scale})`;
  }

  function clampPan() {
    const minX = 400 * (1 - state.scale);
    const minY = 620 * (1 - state.scale);
    state.x = Math.min(0, Math.max(minX, state.x));
    state.y = Math.min(0, Math.max(minY, state.y));
  }

  function zoomBy(factor) {
    const nextScale = Math.min(3.4, Math.max(1, state.scale * factor));
    if (nextScale === state.scale) return;
    const cx = 200;
    const cy = 310;
    const k = nextScale / state.scale;
    state.x = cx - (cx - state.x) * k;
    state.y = cy - (cy - state.y) * k;
    state.scale = nextScale;
    clampPan();
    applyTransform();
    hideTooltip();
  }

  function resetView() {
    state.scale = 1;
    state.x = 0;
    state.y = 0;
    applyTransform();
    hideTooltip();
  }

  function setupMapControls(card) {
    canvas.querySelectorAll("[data-map-zoom]").forEach((button) => {
      button.addEventListener("click", (event) => {
        event.stopPropagation();
        zoomBy(button.getAttribute("data-map-zoom") === "in" ? 1.25 : 0.8);
      });
    });

    const reset = canvas.querySelector("[data-map-reset]");
    if (reset) reset.addEventListener("click", (event) => { event.stopPropagation(); resetView(); });

    const fullscreen = canvas.querySelector("[data-map-fullscreen]");
    if (fullscreen && card) {
      fullscreen.addEventListener("click", (event) => {
        event.stopPropagation();
        if (document.fullscreenElement) {
          document.exitFullscreen();
        } else if (card.requestFullscreen) {
          card.requestFullscreen();
        } else {
          card.classList.toggle("map-card--expanded");
        }
      });
      document.addEventListener("fullscreenchange", () => {
        const isFull = document.fullscreenElement === card;
        fullscreen.innerHTML = icon(isFull ? "minimize" : "maximize");
        fullscreen.setAttribute("aria-label", isFull ? "Quitter le plein écran" : "Afficher la carte en plein écran");
      });
    }
  }

  function setupPanZoom(target) {
    target.addEventListener("pointerdown", (event) => {
      if (event.target.closest(".map-marker") || event.target.closest(".map-control")) return;
      state.dragging = true;
      state.pointerId = event.pointerId;
      state.startX = event.clientX;
      state.startY = event.clientY;
      state.originX = state.x;
      state.originY = state.y;
      target.setPointerCapture(event.pointerId);
      if (svg) svg.classList.add("is-dragging");
      hideTooltip();
    });

    target.addEventListener("pointermove", (event) => {
      if (!state.dragging || event.pointerId !== state.pointerId) return;
      const rect = target.getBoundingClientRect();
      const vbPerPx = 620 / rect.height;
      state.x = state.originX + (event.clientX - state.startX) * vbPerPx;
      state.y = state.originY + (event.clientY - state.startY) * vbPerPx;
      clampPan();
      applyTransform();
    });

    function endDrag(event) {
      if (!state.dragging || (event.pointerId !== undefined && event.pointerId !== state.pointerId)) return;
      state.dragging = false;
      state.pointerId = null;
      if (svg) svg.classList.remove("is-dragging");
      if (target.hasPointerCapture && event.pointerId !== undefined) {
        try { target.releasePointerCapture(event.pointerId); } catch (error) { /* noop */ }
      }
    }

    target.addEventListener("pointerup", endDrag);
    target.addEventListener("pointercancel", endDrag);
    target.addEventListener("pointerleave", endDrag);

    target.addEventListener(
      "wheel",
      (event) => {
        if (!event.ctrlKey && Math.abs(event.deltaY) < 2) return;
        event.preventDefault();
        zoomBy(event.deltaY < 0 ? 1.12 : 0.89);
      },
      { passive: false }
    );

    target.addEventListener("click", (event) => {
      if (event.target === target || event.target === svg) {
        if (state.selected) {
          state.selected.classList.remove("is-selected");
          state.selected = null;
        }
        hideTooltip();
      }
    });
  }

  /* Public filter API -------------------------------------- */

  function applyFilters(filters) {
    const status = filters.status || "all";
    const query = (filters.query || "").trim().toLowerCase();
    const t = window.WASLA_t || ((value) => value);
    markers.forEach(({ zone, group }) => {
      const matchesStatus = status === "all" || zone.status === status;
      const matchesQuery = !query || zone.name.toLowerCase().includes(query) || t(zone.name).toLowerCase().includes(query);
      group.classList.toggle("is-hidden", !(matchesStatus && matchesQuery));
    });
  }

  function selectZoneByName(name) {
    const item = markers.find((marker) => marker.zone.name === name);
    if (item) selectZone(item.zone, item.group);
  }

  window.WASLA_MAP = {
    applyFilters,
    selectZone: selectZoneByName,
    reset: resetView
  };

  document.addEventListener("DOMContentLoaded", () => {
    renderHeroNetwork();
    buildMap();
  });
})();
