/* ============================================================
   WASLA — Lightweight SVG charts (no dependencies)
   ============================================================ */
(function () {
  "use strict";

  const DATA = window.WASLA_DATA;
  const NS = "http://www.w3.org/2000/svg";

  const palette = {
    energy: "#00A875",
    water: "#00A3D7"
  };
  // Theme-dependent colours resolve from CSS custom properties at paint time.
  const TEXT_STYLE = "fill: var(--chart-text)";
  const POINT_STYLE = "fill: var(--color-surface)";

  function smoothPath(points) {
    if (!points.length) return "";
    if (points.length === 1) return `M${points[0][0]} ${points[0][1]}`;
    let d = `M${points[0][0].toFixed(2)} ${points[0][1].toFixed(2)}`;
    for (let i = 0; i < points.length - 1; i++) {
      const p0 = points[i - 1] || points[i];
      const p1 = points[i];
      const p2 = points[i + 1];
      const p3 = points[i + 2] || p2;
      const cp1x = p1[0] + (p2[0] - p0[0]) / 6;
      const cp1y = p1[1] + (p2[1] - p0[1]) / 6;
      const cp2x = p2[0] - (p3[0] - p1[0]) / 6;
      const cp2y = p2[1] - (p3[1] - p1[1]) / 6;
      d += ` C${cp1x.toFixed(2)} ${cp1y.toFixed(2)}, ${cp2x.toFixed(2)} ${cp2y.toFixed(2)}, ${p2[0].toFixed(2)} ${p2[1].toFixed(2)}`;
    }
    return d;
  }

  function el(name, attrs) {
    const node = document.createElementNS(NS, name);
    if (attrs) {
      Object.keys(attrs).forEach((key) => node.setAttribute(key, attrs[key]));
    }
    return node;
  }

  /* KPI sparklines ----------------------------------------- */

  function renderSparklines() {
    document.querySelectorAll("[data-spark]").forEach((host, index) => {
      const key = host.getAttribute("data-spark");
      const series = DATA && DATA.network[key] ? DATA.network[key].series : null;
      if (!series) return;

      const width = 106;
      const height = 52;
      const pad = 3;
      const min = Math.min(...series);
      const max = Math.max(...series);
      const span = max - min || 1;
      const points = series.map((value, i) => {
        const x = pad + (i / (series.length - 1)) * (width - pad * 2);
        const y = height - pad - ((value - min) / span) * (height - pad * 2);
        return [x, y];
      });

      const linePath = smoothPath(points);
      const areaPath = `${linePath} L${width - pad} ${height} L${pad} ${height} Z`;
      const color = key === "water" ? palette.water : palette.energy;
      const gradientId = `spark-gradient-${key}-${index}`;

      const svg = el("svg", {
        viewBox: `0 0 ${width} ${height}`,
        preserveAspectRatio: "none",
        role: "img",
        "aria-label": key === "water" ? "Tendance de la consommation d'eau" : "Tendance de la consommation d'électricité"
      });

      const defs = el("defs");
      const gradient = el("linearGradient", {
        id: gradientId,
        x1: "0",
        y1: "0",
        x2: "0",
        y2: "1"
      });
      gradient.appendChild(el("stop", { offset: "0%", "stop-color": color, "stop-opacity": "0.22" }));
      gradient.appendChild(el("stop", { offset: "100%", "stop-color": color, "stop-opacity": "0" }));
      defs.appendChild(gradient);
      svg.appendChild(defs);

      svg.appendChild(el("path", { d: areaPath, fill: `url(#${gradientId})`, class: "chart-anim-fade" }));
      svg.appendChild(
        el("path", {
          d: linePath,
          fill: "none",
          stroke: color,
          "stroke-width": "2",
          "stroke-linecap": "round",
          "stroke-linejoin": "round",
          pathLength: "1",
          class: "chart-anim-line"
        })
      );
      const last = points[points.length - 1];
      svg.appendChild(el("circle", { cx: last[0], cy: last[1], r: "2.4", style: POINT_STYLE, stroke: color, "stroke-width": "2", class: "chart-anim-pop" }));

      host.innerHTML = "";
      host.appendChild(svg);
    });
  }

  /* Analytics line chart ----------------------------------- */

  function drawLineChart(periodKey) {
    const host = document.querySelector("[data-line-chart]");
    if (!host || !DATA) return;
    const period = DATA.analytics.periods[periodKey] || DATA.analytics.periods["7j"];
    const width = 640;
    const height = 268;
    const pad = { l: 34, r: 14, t: 16, b: 30 };
    const plotW = width - pad.l - pad.r;
    const plotH = height - pad.t - pad.b;
    const labels = period.labels;
    const x = (i) => pad.l + (labels.length === 1 ? plotW / 2 : (i / (labels.length - 1)) * plotW);
    const y = (v) => pad.t + (1 - v / 100) * plotH;

    const svg = el("svg", {
      viewBox: `0 0 ${width} ${height}`,
      class: "chart-svg",
      role: "img",
      "aria-label": `Évolution de la consommation — ${period.label}`
    });

    const defs = el("defs");
    ["energy", "water"].forEach((tone) => {
      const color = tone === "water" ? palette.water : palette.energy;
      const gradient = el("linearGradient", { id: `area-${tone}`, x1: "0", y1: "0", x2: "0", y2: "1" });
      gradient.appendChild(el("stop", { offset: "0%", "stop-color": color, "stop-opacity": "0.18" }));
      gradient.appendChild(el("stop", { offset: "100%", "stop-color": color, "stop-opacity": "0" }));
      defs.appendChild(gradient);
    });
    svg.appendChild(defs);

    [0, 25, 50, 75, 100].forEach((tick) => {
      const gy = y(tick);
      svg.appendChild(
        el("line", {
          x1: pad.l,
          y1: gy,
          x2: width - pad.r,
          y2: gy,
          style: "stroke: var(--chart-grid)",
          "stroke-width": "1",
          "stroke-dasharray": tick === 0 ? "0" : "3 4"
        })
      );
      const label = el("text", {
        x: pad.l - 8,
        y: gy + 3,
        "text-anchor": "end",
        style: TEXT_STYLE,
        "font-family": "Inter, sans-serif",
        "font-size": "8.5",
        "font-weight": "500"
      });
      label.textContent = `${tick}%`;
      svg.appendChild(label);
    });

    const series = [
      { key: "electricity", values: period.electricity, color: palette.energy, tone: "energy" },
      { key: "water", values: period.water, color: palette.water, tone: "water" }
    ];

    series.forEach((item) => {
      const points = item.values.map((value, i) => [x(i), y(value)]);
      const line = smoothPath(points);
      const area = `${line} L${x(points.length - 1)} ${y(0)} L${x(0)} ${y(0)} Z`;

      svg.appendChild(el("path", { d: area, fill: `url(#area-${item.tone})`, class: "chart-anim-fade" }));
      svg.appendChild(
        el("path", {
          d: line,
          fill: "none",
          stroke: item.color,
          "stroke-width": "2.3",
          "stroke-linecap": "round",
          "stroke-linejoin": "round",
          pathLength: "1",
          class: "chart-anim-line"
        })
      );

      points.forEach((point, i) => {
        const circle = el("circle", {
          cx: point[0],
          cy: point[1],
          r: "2.5",
          style: `${POINT_STYLE}; animation-delay: ${320 + i * 40}ms`,
          stroke: item.color,
          "stroke-width": "1.8",
          class: "chart-anim-pop"
        });
        const title = el("title");
        title.textContent = `${item.key === "water" ? "Eau" : "Électricité"} — ${labels[i]} : ${item.values[i]}%`;
        circle.appendChild(title);
        svg.appendChild(circle);
      });
    });

    labels.forEach((label, i) => {
      const text = el("text", {
        x: x(i),
        y: height - 8,
        "text-anchor": "middle",
        style: TEXT_STYLE,
        "font-family": "Inter, sans-serif",
        "font-size": labels.length > 8 ? "8" : "9",
        "font-weight": "500"
      });
      text.textContent = label;
      svg.appendChild(text);
    });

    host.innerHTML = "";
    host.appendChild(svg);
  }

  /* Analytics grouped horizontal bars ---------------------- */

  function drawBarChart() {
    const host = document.querySelector("[data-bar-chart]");
    if (!host || !DATA) return;
    const rows = DATA.analytics.byGovernorate;
    const width = 420;
    const height = 292;
    const pad = { l: 72, r: 38, t: 12, b: 12 };
    const rowH = (height - pad.t - pad.b) / rows.length;
    const barMax = width - pad.l - pad.r - 18;

    const svg = el("svg", {
      viewBox: `0 0 ${width} ${height}`,
      class: "chart-svg",
      role: "img",
      "aria-label": "Consommation relative par gouvernorat"
    });

    rows.forEach((row, index) => {
      const top = pad.t + index * rowH;
      const label = el("text", {
        x: 0,
        y: top + rowH / 2 + 3,
        style: TEXT_STYLE,
        "font-family": "Inter, sans-serif",
        "font-size": "9",
        "font-weight": "600"
      });
      label.textContent = row.name;
      svg.appendChild(label);

      const barY1 = top + rowH / 2 - 9;
      const barY2 = top + rowH / 2 + 2;
      const barH = 6;

      const energyWidth = Math.max(2, (row.electricity / 100) * barMax);
      const waterWidth = Math.max(2, (row.water / 100) * barMax);

      const energy = el("rect", {
        x: pad.l,
        y: barY1,
        width: energyWidth,
        height: barH,
        rx: "1",
        fill: palette.energy,
        class: "chart-anim-bar",
        style: `animation-delay: ${index * 60}ms`
      });
      const energyTitle = el("title");
      energyTitle.textContent = `${row.name} — Électricité : ${row.electricity}%`;
      energy.appendChild(energyTitle);
      svg.appendChild(energy);

      const water = el("rect", {
        x: pad.l,
        y: barY2,
        width: waterWidth,
        height: barH,
        rx: "1",
        fill: palette.water,
        class: "chart-anim-bar",
        style: `animation-delay: ${index * 60 + 40}ms`
      });
      const waterTitle = el("title");
      waterTitle.textContent = `${row.name} — Eau : ${row.water}%`;
      water.appendChild(waterTitle);
      svg.appendChild(water);

      const valueEnergy = el("text", {
        x: pad.l + energyWidth + 5,
        y: barY1 + barH - 1,
        style: TEXT_STYLE,
        class: "chart-anim-fade",
        "font-family": "Inter, sans-serif",
        "font-size": "8",
        "font-weight": "600"
      });
      valueEnergy.textContent = `${row.electricity}%`;
      svg.appendChild(valueEnergy);

      const valueWater = el("text", {
        x: pad.l + waterWidth + 5,
        y: barY2 + barH - 1,
        style: TEXT_STYLE,
        class: "chart-anim-fade",
        "font-family": "Inter, sans-serif",
        "font-size": "8",
        "font-weight": "600"
      });
      valueWater.textContent = `${row.water}%`;
      svg.appendChild(valueWater);

      if (index < rows.length - 1) {
        svg.appendChild(
          el("line", {
            x1: 0,
            y1: top + rowH,
            x2: width,
            y2: top + rowH,
            style: "stroke: var(--chart-separator)",
            "stroke-width": "1"
          })
        );
      }
    });

    host.innerHTML = "";
    host.appendChild(svg);
  }

  /* Period switching --------------------------------------- */

  function setupPeriods() {
    const tabs = document.querySelectorAll("[data-period]");
    if (!tabs.length) return;
    tabs.forEach((tab) => {
      tab.addEventListener("click", () => {
        tabs.forEach((item) => {
          item.classList.remove("is-active");
          item.setAttribute("aria-selected", "false");
        });
        tab.classList.add("is-active");
        tab.setAttribute("aria-selected", "true");
        const period = tab.getAttribute("data-period");
        const label = document.querySelector("[data-period-label]");
        if (label && DATA.analytics.periods[period]) {
          label.textContent = DATA.analytics.periods[period].label;
        }
        drawLineChart(period);
      });
    });
  }

  document.addEventListener("DOMContentLoaded", () => {
    renderSparklines();
    const activePeriod = document.querySelector("[data-period].is-active");
    drawLineChart(activePeriod ? activePeriod.getAttribute("data-period") : "7j");
    drawBarChart();
    setupPeriods();
  });
})();
