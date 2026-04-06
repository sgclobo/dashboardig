// assets/js/dashboard.js

(function () {
  "use strict";

  /* ── Sidebar toggle ──────────────────────────────────── */
  const sidebar = document.getElementById("sidebar");
  const overlay = document.getElementById("sidebar-overlay");
  const toggleBt = document.getElementById("sidebar-toggle");

  function isMobile() {
    return window.innerWidth < 768;
  }

  function openSidebar() {
    sidebar.classList.remove("collapsed");
    if (isMobile()) overlay.classList.add("active");
    localStorage.setItem("sidebar_open", "1");
  }

  function closeSidebar() {
    sidebar.classList.add("collapsed");
    overlay.classList.remove("active");
    localStorage.setItem("sidebar_open", "0");
  }

  function toggleSidebar() {
    sidebar.classList.contains("collapsed") ? openSidebar() : closeSidebar();
  }

  if (isMobile()) {
    closeSidebar();
  } else {
    if (localStorage.getItem("sidebar_open") === "0") closeSidebar();
  }

  toggleBt && toggleBt.addEventListener("click", toggleSidebar);
  overlay && overlay.addEventListener("click", closeSidebar);
  window.addEventListener("resize", () => {
    if (!isMobile()) overlay.classList.remove("active");
  });

  /* ── Section switching ───────────────────────────────── */
  const navItems = document.querySelectorAll(".nav-item[data-section]");
  const panels = document.querySelectorAll(".section-panel");
  const topTitle = document.getElementById("topbar-title");

  function activateSection(slug) {
    navItems.forEach((el) =>
      el.classList.toggle("active", el.dataset.section === slug),
    );
    panels.forEach((p) =>
      p.classList.toggle("active", p.id === "section-" + slug),
    );

    const item = document.querySelector(
      '.nav-item[data-section="' + slug + '"]',
    );
    if (item && topTitle) topTitle.textContent = item.dataset.label || slug;

    history.replaceState(null, "", "#" + slug);
    if (isMobile()) closeSidebar();
    loadSection(slug);
  }

  navItems.forEach((item) => {
    item.addEventListener("click", () => activateSection(item.dataset.section));
  });

  const initSlug =
    location.hash.slice(1) ||
    (navItems[0] ? navItems[0].dataset.section : "overview");
  activateSection(initSlug);

  /* ── Data loading ────────────────────────────────────── */
  const loaded = {};

  function loadSection(slug) {
    if (loaded[slug]) return;
    const panel = document.getElementById("section-" + slug);
    if (!panel) return;

    const source = panel.dataset.source;
    if (!source) {
      loaded[slug] = true;
      return;
    }

    const container = panel.querySelector(".stats-grid");
    if (container) {
      container.innerHTML =
        '<div class="stat-card" style="opacity:.4"><div class="stat-label">Loading…</div><div class="stat-value">—</div></div>';
    }

    fetch("api/section.php?source=" + encodeURIComponent(source))
      .then(function (r) {
        if (!r.ok) throw new Error("HTTP " + r.status);
        return r.json();
      })
      .then(function (data) {
        renderSection(panel, data);
      })
      .catch(function (err) {
        if (container) {
          container.innerHTML =
            '<div class="alert error">⚠ Could not load data: ' +
            err.message +
            "</div>";
        }
      })
      .finally(function () {
        loaded[slug] = true;
      });
  }

  function renderSection(panel, data) {
    const grid = panel.querySelector(".stats-grid");
    if (!grid) return;

    if (data.error) {
      grid.innerHTML = '<div class="alert error">⚠ ' + data.error + "</div>";
      return;
    }

    const cards = Object.entries(data)
      .filter(function (entry) {
        return !entry[0].startsWith("_");
      })
      .map(function (entry) {
        const k = entry[0],
          v = entry[1];
        const label = k.replace(/_/g, " ");
        const value = typeof v === "number" ? v.toLocaleString() : v;
        return (
          '<div class="stat-card"><div class="stat-label">' +
          label +
          '</div><div class="stat-value">' +
          value +
          "</div></div>"
        );
      })
      .join("");

    grid.innerHTML =
      cards || '<p style="color:var(--muted)">No data returned.</p>';

    const badge = panel.querySelector(".badge-status");
    if (badge) {
      badge.className = "badge-status live";
      badge.textContent = "Live";
    }
  }

  /* ── Refresh button ──────────────────────────────────── */
  const btnRefresh = document.getElementById("btn-refresh");
  if (btnRefresh) {
    btnRefresh.addEventListener("click", function () {
      const slug = location.hash.slice(1) || initSlug;
      delete loaded[slug];
      loadSection(slug);
    });
  }

  /* ── Live clock ──────────────────────────────────────── */
  function updateClock() {
    const el = document.getElementById("topbar-clock");
    if (!el) return;
    const now = new Date();
    const days = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
    const months = [
      "Jan",
      "Feb",
      "Mar",
      "Apr",
      "May",
      "Jun",
      "Jul",
      "Aug",
      "Sep",
      "Oct",
      "Nov",
      "Dec",
    ];
    const pad = function (n) {
      return String(n).padStart(2, "0");
    };
    el.textContent =
      days[now.getDay()] +
      ", " +
      pad(now.getDate()) +
      " " +
      months[now.getMonth()] +
      " " +
      now.getFullYear() +
      " · " +
      pad(now.getHours()) +
      ":" +
      pad(now.getMinutes());
  }
  setInterval(updateClock, 30000);
})();
