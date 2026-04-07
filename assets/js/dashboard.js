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

  // Restore state on load
  if (isMobile()) {
    closeSidebar();
  } else {
    const saved = localStorage.getItem("sidebar_open");
    if (saved === "0") closeSidebar(); // default open on desktop
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
    // Update nav
    navItems.forEach((el) =>
      el.classList.toggle("active", el.dataset.section === slug),
    );

    // Update panels
    panels.forEach((p) =>
      p.classList.toggle("active", p.id === "section-" + slug),
    );

    // Update topbar title
    const item = document.querySelector(`.nav-item[data-section="${slug}"]`);
    if (item && topTitle) topTitle.textContent = item.dataset.label || slug;

    // Update URL hash (no full reload)
    history.replaceState(null, "", "#" + slug);

    // On mobile, close sidebar after navigation
    if (isMobile()) closeSidebar();

    // Lazy-load data for the section
    loadSection(slug);
  }

  navItems.forEach((item) => {
    item.addEventListener("click", () => activateSection(item.dataset.section));
  });

  // Load section from hash on page load
  const initSlug =
    location.hash.slice(1) ||
    (navItems[0] ? navItems[0].dataset.section : "home");
  activateSection(initSlug);

  /* ── Data loading via fetch API ──────────────────────── */
  const loaded = {}; // cache which sections we've already fetched

  function loadSection(slug) {
    if (loaded[slug]) return;
    const panel = document.getElementById("section-" + slug);
    if (!panel) return;

    // Only fetch if the panel has a data-source attribute
    const source = panel.dataset.source;
    if (!source) {
      loaded[slug] = true;
      return;
    }

    // Show loading state
    const container = panel.querySelector(".stats-grid");
    if (container) {
      container.innerHTML =
        '<div class="stat-card" style="opacity:.4"><div class="stat-label">Loading…</div></div>';
    }

    fetch("/section.php?source=" + encodeURIComponent(source))
      .then((r) => (r.ok ? r.json() : Promise.reject(r.status)))
      .then((data) => renderSection(panel, data))
      .catch((err) => {
        if (container) {
          container.innerHTML = `<div class="alert error">⚠ Could not load data (${err}). Is the subdomain API reachable?</div>`;
        }
      })
      .finally(() => {
        loaded[slug] = true;
      });
  }

  function renderSection(panel, data) {
    const grid = panel.querySelector(".stats-grid");
    if (!grid) return;

    if (data.error) {
      grid.innerHTML = `<div class="alert error">⚠ ${data.error}</div>`;
      return;
    }

    // Generic renderer: turn each key/value pair into a stat card
    const cards = Object.entries(data)
      .filter(([k]) => !k.startsWith("_")) // skip meta keys
      .map(([k, v]) => {
        const label = k.replace(/_/g, " ");
        const value = typeof v === "number" ? v.toLocaleString() : v;
        return `
                <div class="stat-card">
                    <div class="stat-label">${label}</div>
                    <div class="stat-value">${value}</div>
                </div>`;
      })
      .join("");

    grid.innerHTML =
      cards || '<p style="color:var(--muted)">No data returned.</p>';

    // Update status badge
    const badge = panel.querySelector(".badge-status");
    if (badge) {
      badge.className = "badge-status live";
      badge.textContent = "Live";
    }
  }

  /* ── Manual refresh ──────────────────────────────────── */
  document.getElementById("btn-refresh") &&
    document.getElementById("btn-refresh").addEventListener("click", () => {
      const slug = location.hash.slice(1) || initSlug;
      delete loaded[slug];
      loadSection(slug);
    });

  /* ── Logout ──────────────────────────────────────────── */
  document.getElementById("btn-logout") &&
    document.getElementById("btn-logout").addEventListener("click", () => {
      if (confirm("Sign out?")) window.location.href = "/logout.php";
    });
})();
