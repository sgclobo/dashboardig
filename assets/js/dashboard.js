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

    fetch("/api/section.php?source=" + encodeURIComponent(source))
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
    renderExtras(panel, data);
  }

  function renderExtras(panel, data) {
    const slug = panel.dataset.source;
    const container = document.getElementById(slug + "-extras");
    if (!container) return;
    container.innerHTML = "";

    const esc = (s) =>
      String(s ?? "").replace(
        /[&<>"]/g,
        (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;" }[c])
      );

    // ── Recent records table ──────────────────────────────
    if (data._recent && data._recent.length) {
      let thead = "",
        rows = "";

      if (slug === "inspections") {
        thead = "<tr><th>Date</th><th>Municipality</th><th>Type</th></tr>";
        rows = data._recent
          .map(
            (r) =>
              `<tr><td>${esc(r.report_date)}</td><td>${esc(r.munisipiu)}</td><td>${esc(r.inspection_type)}</td></tr>`
          )
          .join("");
      } else if (slug === "fines") {
        thead =
          '<tr><th>Date</th><th>Payer</th><th>Business</th><th class="extras-num">Amount</th></tr>';
        rows = data._recent
          .map(
            (r) =>
              `<tr><td>${esc(r.payment_date)}</td><td>${esc(r.payer_name)}</td><td>${esc(r.business_name)}</td><td class="extras-num">$${(+r.total_value).toLocaleString("en", { minimumFractionDigits: 2 })}</td></tr>`
          )
          .join("");
      }

      if (thead) {
        container.innerHTML += `
          <div class="extras-block">
            <h3 class="extras-title">Recent 10</h3>
            <div class="extras-table-wrap">
              <table class="extras-table">
                <thead>${thead}</thead>
                <tbody>${rows}</tbody>
              </table>
            </div>
          </div>`;
      }
    }

    // ── Monthly bar charts (fines only) ──────────────────
    if (data._monthly_2025 || data._monthly_2026) {
      const MONTHS = ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"];
      const COLORS = ["#c9a84c","#4a90d9","#4caf82","#e05555","#8b5cf6","#e0a030","#5dade2","#ff6b35","#8b2c9b","#2e5266","#4caf82","#c9a84c"];

      const buildChart = (monthly, label) => {
        const map = {};
        (monthly || []).forEach((r) => { map[+r.m] = r; });
        const bars = MONTHS.map((name, i) => ({
          name,
          total: +(map[i + 1]?.t ?? 0),
          count: +(map[i + 1]?.c ?? 0),
        }));
        const maxVal = Math.max(1, ...bars.map((b) => b.total));
        return `
          <div class="extras-chart-wrap">
            <h4 class="extras-subtitle">${label}</h4>
            <div class="extras-chart">
              ${bars
                .map(
                  (b, i) => `
                <div class="extras-bar-col">
                  <div class="extras-bar-val">${b.count || ""}</div>
                  <div class="extras-bar"
                    style="height:${Math.round((b.total / maxVal) * 100)}%;background:${COLORS[i]}"
                    title="${b.name}: $${b.total.toLocaleString()} (${b.count} fines)"></div>
                  <div class="extras-bar-lbl">${b.name}</div>
                </div>`
                )
                .join("")}
            </div>
          </div>`;
      };

      container.innerHTML += `
        <div class="extras-block extras-charts-row">
          ${buildChart(data._monthly_2025, "Monthly Fines 2025")}
          ${buildChart(data._monthly_2026, "Monthly Fines 2026")}
        </div>`;
    }  }

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
      window.location.href = "/logout.php";
    });
})();
