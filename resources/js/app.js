import Chart from "chart.js/auto";
import "../css/app.css";

const root = document.getElementById("engineering-agent-app");
const data = window.__ENGINEERING_AGENT__ ?? {};
const config = window.__ENGINEERING_AGENT_CONFIG__ ?? {};

let activeChart = null;
let developerApiKey = localStorage.getItem("developer_api_key") || "YOUR_API_KEY";
let activeTokens = [];
let justGeneratedToken = null;
let activeEndpointId = "get_dashboard_overview";
let lastResponseData = null;
let lastResponseStatus = null;
let lastResponseTime = null;
let isSendingRequest = false;

const routes = {
    "/": "dashboard",
    "/dashboard": "dashboard",
    "/integrations": "integrations",
    "/repositories": "repositories",
    "/developers": "developers",
    "/leaderboard": "leaderboard",
    "/insights": "insights",
    "/reports": "reports",
    "/settings": "settings",
    "/developer-mode": "developer_mode",
};

const navItems = [
    ["dashboard", "Dashboard", "/dashboard", dashboardIcon()],
    ["integrations", "Integrations", "/integrations", integrationsIcon()],
    ["repositories", "Repositories", "/repositories", repositoryIcon()],
    ["developers", "Developers", "/developers", developersIcon()],
    ["leaderboard", "Leaderboard", "/leaderboard", leaderboardIcon()],
    ["insights", "AI Insights", "/insights", insightIcon()],
    ["reports", "Reports", "/reports", reportIcon()],
    ["settings", "Settings", "/settings", settingsIcon()],
    ["developer_mode", "Developer Mode", "/developer-mode", developerModeIcon()],
];

function pageKey() {
    return routes[window.location.pathname] ?? "dashboard";
}

function appShell(content, title, eyebrow = "Engineering intelligence") {
    const current = pageKey();

    return `
        <div class="app-shell">
            <aside class="sidebar">
                <a class="brand" href="/dashboard" data-link>
                    <span class="brand-mark">EA</span>
                    <span>
                        <strong>${escapeHtml(config.appName || "Engineering Agent")}</strong>
                        <small>Delivery cockpit</small>
                    </span>
                </a>

                <nav class="nav-list">
                    ${navItems.map(([key, label, href, icon]) => `
                        <a class="nav-item ${current === key ? "is-active" : ""}" href="${href}" data-link>
                            ${icon}
                            <span>${label}</span>
                        </a>
                    `).join("")}
                </nav>

                <div class="sidebar-panel">
                    <span class="status-dot"></span>
                    <div>
                        <strong>Local workspace</strong>
                        <small>${formatNumber(data.overview?.commits ?? 0)} commits indexed</small>
                    </div>
                </div>

                <div class="user-profile-menu">
                    <button class="user-profile-btn" id="userProfileBtn" type="button" aria-haspopup="true" aria-expanded="false">
                        <div class="user-avatar">
                            ${escapeHtml(initialsFor(data.user?.name || "User"))}
                        </div>
                        <div class="user-info">
                            <span class="user-name">${escapeHtml(data.user?.name || "Developer")}</span>
                            <span class="user-email">${escapeHtml(data.user?.email || "dev@workspace.local")}</span>
                        </div>
                        <div class="user-menu-chevron">
                            <svg viewBox="0 0 24 24" width="16" height="16"><path d="M12 15l-5-5h10z" fill="currentColor"/></svg>
                        </div>
                    </button>
                    <div class="user-dropdown-menu" id="userDropdownMenu">
                        <button class="dropdown-item" id="btnLogout" type="button">
                            ${logoutIcon()}
                            <span>Sign out</span>
                        </button>
                        <button class="dropdown-item danger" id="btnLogoutAll" type="button">
                            ${logoutAllIcon()}
                            <span>Sign out all devices</span>
                        </button>
                    </div>
                </div>
            </aside>

            <div class="workspace">
                <header class="topbar">
                    <div>
                        <p class="eyebrow">${eyebrow}</p>
                        <h1>${title}</h1>
                    </div>
                    <div class="topbar-actions">
                        <label class="global-search">
                            ${searchIcon()}
                            <input type="search" placeholder="Search developers, repos..." data-search>
                        </label>
                        <button class="icon-button" type="button" title="Refresh page" data-refresh>
                            ${refreshIcon()}
                        </button>
                    </div>
                </header>

                <main class="page-content">
                    ${content}
                </main>
            </div>
        </div>
    `;
}

function render() {
    if (!root) {
        return;
    }
    const key = pageKey();
    const pages = {
        dashboard: dashboardPage,
        integrations: integrationsPage,
        repositories: repositoriesPage,
        developers: developersPage,
        leaderboard: leaderboardPage,
        insights: insightsPage,
        reports: reportsPage,
        settings: settingsPage,
        developer_mode: developerModePage,
    };

    destroyChart();
    root.innerHTML = pages[key]();
    bindShellEvents();

    if (key === "dashboard") {
        renderActivityChart();
    }
}

function dashboardPage() {
    const overview = data.overview ?? {};
    const topDevelopers = normalizedLeaderboard().slice(0, 5);
    const repositories = normalizedRepositories().slice(0, 5);

    return appShell(`
        <section class="hero-panel">
            <div>
                <p class="eyebrow">Live overview</p>
                <h2>Understand team delivery, quality, and momentum from one clean board.</h2>
            </div>
            <div class="hero-score">
                <span>${formatNumber(overview.average_score ?? 0)}</span>
                <small>Average developer score</small>
            </div>
        </section>

        <section class="metrics-grid">
            ${metricCard("Repositories", overview.repositories, repositoryIcon(), "Tracked source repositories")}
            ${metricCard("Developers", overview.developers, developersIcon(), "Contributors in the system")}
            ${metricCard("Commits", overview.commits, commitIcon(), "Total code activity")}
            ${metricCard("Pull Requests", overview.pull_requests, pullRequestIcon(), "Opened and merged work")}
        </section>

        <section class="dashboard-grid">
            <article class="panel chart-panel">
                <div class="panel-heading">
                    <div>
                        <p class="eyebrow">Last 7 days</p>
                        <h2>Commit activity</h2>
                    </div>
                </div>
                <div class="chart-wrap">
                    <canvas id="activity-chart"></canvas>
                </div>
            </article>

            <article class="panel">
                <div class="panel-heading">
                    <div>
                        <p class="eyebrow">Performance</p>
                        <h2>Top engineers</h2>
                    </div>
                    <a href="/leaderboard" data-link>View all</a>
                </div>
                ${topDevelopers.length ? compactList(topDevelopers.map((item, index) => ({
        title: item.name,
        meta: `${item.commits} commits · ${item.reviews} reviews`,
        value: `${index + 1}`,
        score: item.score,
    }))) : emptyState("No developer metrics yet", "Run metric calculation after syncing repositories.")}
            </article>
        </section>

        <section class="panel">
            <div class="panel-heading">
                <div>
                    <p class="eyebrow">Repositories</p>
                    <h2>Recently synced codebases</h2>
                </div>
                <a href="/repositories" data-link>Open repositories</a>
            </div>
            ${repositoryTable(repositories)}
        </section>
    `, "Dashboard");
}

function integrationsPage() {
    const integrations = normalizedIntegrations();
    const connectedProviders = integrations.map(i => (i.provider || '').toLowerCase());

    const providers = [
        ["gitlab", "GitLab", "GL", "/oauth/gitlab/connect", "linear-gradient(135deg, #fc6d26, #e24329)"],
        ["github", "GitHub", "GH", "/oauth/github/connect", "linear-gradient(135deg, #24292e, #4a4a4a)"],
        ["bitbucket", "Bitbucket", "BB", "/oauth/bitbucket/connect", "linear-gradient(135deg, #0052cc, #2684ff)"],
    ];

    return appShell(`
        <section class="provider-grid">
            ${providers.map(([key, name, initials, href, bg]) => {
                const isConnected = connectedProviders.includes(key);
                return `
                    <article class="provider-card" style="position: relative;">
                        <div class="provider-icon" style="background: ${bg};">${initials}</div>
                        <div>
                            <h2>${name}</h2>
                            <p>${isConnected ? 'Connected account' : 'OAuth connection ready'}</p>
                        </div>
                        ${isConnected 
                            ? `<span style="padding: 0.35rem 0.75rem; border-radius: 8px; font-weight: 800; font-size: 0.82rem; background: #dcfce7; color: #166534; border: 1px solid #bbf7d0;">Connected</span>`
                            : `<a class="button" href="${href}">Connect</a>`
                        }
                    </article>
                `;
            }).join("")}
        </section>

        <section class="panel">
            <div class="panel-heading">
                <div>
                    <p class="eyebrow">Active integrations</p>
                    <h2>Connected accounts</h2>
                </div>
            </div>
            ${integrations.length ? integrationList(integrations) : emptyState("No integrations connected", "Connect a source control provider to begin syncing repositories.")}
        </section>
    `, "Integrations", "Source control");
}

function repositoriesPage() {
    return appShell(`
        <section class="panel">
            <div class="panel-heading">
                <div>
                    <p class="eyebrow">Codebase health</p>
                    <h2>Repositories</h2>
                </div>
                <span class="count-pill">${normalizedRepositories().length} shown</span>
            </div>
            ${repositoryTable(normalizedRepositories())}
        </section>
    `, "Repositories");
}

function developersPage() {
    const developers = normalizedDevelopers();

    return appShell(`
        <section class="developer-grid">
            ${developers.length ? developers.map(developerCard).join("") : emptyState("No developers indexed", "Sync repositories to populate contributor profiles.")}
        </section>
    `, "Developers", "Team intelligence");
}

function leaderboardPage() {
    const leaderboard = normalizedLeaderboard();

    return appShell(`
        <section class="panel">
            <div class="panel-heading">
                <div>
                    <p class="eyebrow">Scoreboard</p>
                    <h2>Leaderboard</h2>
                </div>
            </div>
            ${leaderboard.length ? leaderboardTable(leaderboard) : emptyState("No scores calculated", "Run metric calculation to rank developers.")}
        </section>
    `, "Leaderboard");
}

function insightsPage() {
    const insights = normalizedInsights();

    return appShell(`
        <div style="display: flex; justify-content: flex-end; margin-bottom: 1.5rem;">
            <button type="button" class="btn btn-primary" id="btnGenerateAllInsights" style="display: flex; align-items: center; gap: 0.5rem;">
                ${insightIcon()}
                <span>Regenerate All Insights</span>
            </button>
        </div>
        <section class="insight-grid">
            ${insights.length ? insights.map(insightCard).join("") : emptyState("No AI insights yet", "Generate insights after developer metrics are available.")}
        </section>
    `, "AI Insights", "Performance narratives");
}

function reportsPage() {
    const overview = data.overview ?? {};

    return appShell(`
        <section class="metrics-grid">
            ${metricCard("Reviews", overview.reviews, reviewIcon(), "Completed code reviews")}
            ${metricCard("Tasks", overview.tasks, taskIcon(), "Tracked delivery tasks")}
            ${metricCard("Deployments", overview.deployments, deploymentIcon(), "Release events")}
            ${metricCard("Pull Requests", overview.pull_requests, pullRequestIcon(), "Collaboration throughput")}
        </section>
        <section class="panel">
            <div class="panel-heading" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
                <div>
                    <p class="eyebrow">Cockpit intelligence</p>
                    <h2>AI Workflow & Velocity Report</h2>
                </div>
                <button type="button" class="btn btn-primary" id="btnGenerateReport">
                    ${insightIcon()}
                    <span>Generate AI Report</span>
                </button>
            </div>
            
            <div id="reportContainer" class="report-box">
                <div class="loading-state">
                    <svg class="animate-spin" viewBox="0 0 24 24" style="width:2rem;height:2rem;margin: 0 auto 1rem;"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    <p>Fetching latest report...</p>
                </div>
            </div>
        </section>
    `, "Reports");
}

function settingsPage() {
    return appShell(`
        <div id="settingsContainer">
            <div class="loading-state">
                <svg class="animate-spin" viewBox="0 0 24 24" style="width:2rem;height:2rem;margin: 0 auto 1rem;"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                <p>Loading configurations...</p>
            </div>
        </div>
    `, "Settings");
}

function metricCard(title, value, icon, subtitle) {
    return `
        <article class="metric-card">
            <div class="metric-icon">${icon}</div>
            <div>
                <p>${title}</p>
                <strong>${formatNumber(value ?? 0)}</strong>
                <small>${subtitle}</small>
            </div>
        </article>
    `;
}

function repositoryTable(repositories) {
    if (!repositories.length) {
        return emptyState("No repositories synced", "Connect GitLab, GitHub, or Bitbucket to bring repositories into the dashboard.");
    }

    return `
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Repository</th>
                        <th>Provider</th>
                        <th>Commits</th>
                        <th>PRs</th>
                        <th>Tasks</th>
                        <th>Deployments</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    ${repositories.map((repo) => `
                        <tr>
                            <td>
                                <strong>${escapeHtml(repo.name)}</strong>
                                <span>${escapeHtml(repo.owner || "Unknown owner")}</span>
                            </td>
                            <td><span class="tag">${escapeHtml(repo.provider || "source")}</span></td>
                            <td>${formatNumber(repo.commits)}</td>
                            <td>${formatNumber(repo.pull_requests)}</td>
                            <td>${formatNumber(repo.tasks)}</td>
                            <td>${formatNumber(repo.deployments)}</td>
                            <td>
                                <button type="button" class="btn btn-secondary btn-sync-repo" data-sync-repo="${repo.id}" style="padding: 0.25rem 0.5rem; font-size: 0.75rem; height: auto; display: flex; align-items: center; gap: 0.2rem; min-width: 90px; justify-content: center;">
                                    ${refreshIcon()}
                                    <span>Sync Now</span>
                                </button>
                            </td>
                        </tr>
                    `).join("")}
                </tbody>
            </table>
        </div>
    `;
}

function leaderboardTable(rows) {
    return `
        <div class="table-wrap">
            <table class="data-table leaderboard-table">
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Developer</th>
                        <th>Commits</th>
                        <th>PRs</th>
                        <th>Reviews</th>
                        <th>Score</th>
                    </tr>
                </thead>
                <tbody>
                    ${rows.map((developer, index) => `
                        <tr>
                            <td><span class="rank">${index + 1}</span></td>
                            <td>
                                <strong>${escapeHtml(developer.name)}</strong>
                                <span>${escapeHtml(developer.username)}</span>
                            </td>
                            <td>${formatNumber(developer.commits)}</td>
                            <td>${formatNumber(developer.prs)}</td>
                            <td>${formatNumber(developer.reviews)}</td>
                            <td><strong>${formatNumber(developer.score)}</strong></td>
                        </tr>
                    `).join("")}
                </tbody>
            </table>
        </div>
    `;
}

function developerCard(developer) {
    const initials = initialsFor(developer.name);

    return `
        <article class="developer-card">
            <div class="avatar">${developer.avatar ? `<img src="${escapeHtml(developer.avatar)}" alt="">` : initials}</div>
            <div class="developer-card-main">
                <div>
                    <h2>${escapeHtml(developer.name)}</h2>
                    <p>${escapeHtml(developer.username)}</p>
                </div>
                <strong>${formatNumber(developer.score)}</strong>
            </div>
            <div class="developer-stats">
                <span><strong>${formatNumber(developer.commits)}</strong>Commits</span>
                <span><strong>${formatNumber(developer.prs)}</strong>PRs</span>
                <span><strong>${formatNumber(developer.reviews)}</strong>Reviews</span>
            </div>
        </article>
    `;
}

function getRecommendationHtml(item) {
    if (!item) return "";
    if (typeof item === "string") {
        return escapeHtml(item);
    }
    if (typeof item === "object") {
        if (item.action && (item.reason || item.description)) {
            const action = escapeHtml(item.action);
            const extra = escapeHtml(item.reason || item.description);
            return `<strong>${action}</strong>: ${extra}`;
        }
        if (item.action) return escapeHtml(item.action);
        if (item.text) return escapeHtml(item.text);
        if (item.recommendation) return escapeHtml(item.recommendation);
        if (item.description) return escapeHtml(item.description);
        // Fallback: join values
        return escapeHtml(Object.values(item).join(" - "));
    }
    return escapeHtml(String(item));
}

function insightCard(insight) {
    return `
        <article class="insight-card" style="display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div class="panel-heading" style="display:flex; justify-content:space-between; align-items:flex-start;">
                    <div>
                        <p class="eyebrow">${escapeHtml(insight.developer)}</p>
                        <h2>AI insight</h2>
                    </div>
                </div>
                <p style="margin-top: 1rem;">${escapeHtml(insight.summary || "No summary available.")}</p>
                ${insight.recommendations?.length ? `
                    <ul style="margin-top: 1rem;">
                        ${insight.recommendations.slice(0, 3).map((item) => `<li>${getRecommendationHtml(item)}</li>`).join("")}
                    </ul>
                ` : ""}
            </div>
            ${insight.developerId ? `
                <div style="margin-top: 1.5rem; display: flex; justify-content: flex-end;">
                    <button type="button" class="btn btn-secondary btn-regenerate-insight" data-regenerate-insight="${insight.developerId}" style="padding: 0.25rem 0.5rem; font-size: 0.75rem; height: auto; display: flex; align-items: center; gap: 0.2rem;">
                        ${refreshIcon()}
                        <span>Regenerate</span>
                    </button>
                </div>
            ` : ""}
        </article>
    `;
}

function compactList(items) {
    return `
        <div class="compact-list">
            ${items.map((item) => `
                <div class="compact-row">
                    <span class="rank">${item.value}</span>
                    <div>
                        <strong>${escapeHtml(item.title)}</strong>
                        <small>${escapeHtml(item.meta)}</small>
                    </div>
                    <b>${formatNumber(item.score)}</b>
                </div>
            `).join("")}
        </div>
    `;
}

function integrationList(items) {
    return `
        <div class="compact-list">
            ${items.map((item) => {
                const provider = (item.provider || '').toLowerCase();
                const displayProvider = provider === 'github' ? 'GitHub' : (provider === 'gitlab' ? 'GitLab' : (provider === 'bitbucket' ? 'Bitbucket' : 'Provider'));
                
                let initials = "SC";
                let bg = "linear-gradient(135deg, var(--blue), var(--teal))";
                if (provider === "github") {
                    initials = "GH";
                    bg = "linear-gradient(135deg, #24292e, #4a4a4a)";
                } else if (provider === "gitlab") {
                    initials = "GL";
                    bg = "linear-gradient(135deg, #fc6d26, #e24329)";
                } else if (provider === "bitbucket") {
                    initials = "BB";
                    bg = "linear-gradient(135deg, #0052cc, #2684ff)";
                }

                let displayTime = "recently";
                if (item.created_at) {
                    try {
                        const date = new Date(item.created_at);
                        displayTime = date.toLocaleDateString(undefined, { 
                            year: 'numeric', 
                            month: 'short', 
                            day: 'numeric'
                        });
                    } catch (e) {}
                }

                return `
                    <div class="compact-row">
                        <span class="rank" style="background: ${bg}; color: #ffffff; border-radius: 8px;">${initials}</span>
                        <div>
                            <strong>${escapeHtml(displayProvider)}</strong>
                            <small>Connected on ${escapeHtml(displayTime)}</small>
                        </div>
                        <button type="button" class="btn btn-secondary btn-sync-integration" data-sync-integration="${item.id}" style="padding: 0.25rem 0.75rem; font-size: 0.8rem; height: auto; display: flex; align-items: center; gap: 0.25rem;">
                            ${refreshIcon()}
                            <span>Sync</span>
                        </button>
                    </div>
                `;
            }).join("")}
        </div>
    `;
}

function settingBlock(title, text) {
    return `
        <article class="panel setting-block">
            <h2>${title}</h2>
            <p>${text}</p>
        </article>
    `;
}

function emptyState(title, text) {
    return `
        <div class="empty-state">
            <div class="empty-icon">${insightIcon()}</div>
            <strong>${title}</strong>
            <p>${text}</p>
        </div>
    `;
}

function bindShellEvents() {
    document.querySelectorAll("[data-link]").forEach((link) => {
        link.addEventListener("click", (event) => {
            const url = new URL(link.href);

            if (url.origin !== window.location.origin) {
                return;
            }

            event.preventDefault();
            history.pushState({}, "", url.pathname);
            render();
        });
    });

    document.querySelector("[data-refresh]")?.addEventListener("click", () => {
        window.location.reload();
    });

    document.querySelector("[data-search]")?.addEventListener("input", (event) => {
        const term = event.target.value.trim().toLowerCase();

        document.querySelectorAll(".data-table tbody tr, .developer-card, .insight-card").forEach((node) => {
            node.hidden = term && !node.textContent.toLowerCase().includes(term);
        });
    });

    const profileBtn = document.getElementById("userProfileBtn");
    const dropdownMenu = document.getElementById("userDropdownMenu");

    if (profileBtn && dropdownMenu) {
        profileBtn.addEventListener("click", (e) => {
            e.stopPropagation();
            dropdownMenu.classList.toggle("show");
            const expanded = dropdownMenu.classList.contains("show");
            profileBtn.setAttribute("aria-expanded", expanded ? "true" : "false");
        });

        // Close dropdown when clicking outside
        document.addEventListener("click", () => {
            if (dropdownMenu.classList.contains("show")) {
                dropdownMenu.classList.remove("show");
                profileBtn.setAttribute("aria-expanded", "false");
            }
        });

        // Prevent closing when clicking inside the dropdown menu (except on logout buttons)
        dropdownMenu.addEventListener("click", (e) => {
            e.stopPropagation();
        });
    }

    const btnLogout = document.getElementById("btnLogout");
    if (btnLogout) {
        btnLogout.addEventListener("click", () => {
            btnLogout.disabled = true;
            btnLogout.innerHTML = `
                <svg class="animate-spin" viewBox="0 0 24 24" style="width:1.1rem;height:1.1rem;display:inline-block;vertical-align:middle;margin-right:0.5rem;"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                <span>Signing out...</span>
            `;
            
            fetch("/logout", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json",
                    "X-CSRF-TOKEN": config.csrfToken
                }
            })
            .then(response => {
                if (response.ok) {
                    window.location.href = "/login";
                } else {
                    showToast("Logout failed. Please try again.", "error");
                    setTimeout(() => window.location.reload(), 1000);
                }
            })
            .catch(() => {
                showToast("Logout failed. Please check your connection.", "error");
                setTimeout(() => window.location.reload(), 1000);
            });
        });
    }

    const btnLogoutAll = document.getElementById("btnLogoutAll");
    if (btnLogoutAll) {
        btnLogoutAll.addEventListener("click", async () => {
            const confirmed = await showConfirm({
                title: "Sign Out All Devices",
                message: "Are you sure you want to sign out from all devices? This will invalidate all active sessions and API tokens.",
                confirmText: "Sign Out All",
                cancelText: "Cancel",
                danger: true
            });
            if (!confirmed) {
                return;
            }
            
            btnLogoutAll.disabled = true;
            btnLogoutAll.innerHTML = `
                <svg class="animate-spin" viewBox="0 0 24 24" style="width:1.1rem;height:1.1rem;display:inline-block;vertical-align:middle;margin-right:0.5rem;"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                <span>Signing out all...</span>
            `;
            
            fetch("/logout-all-devices", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json",
                    "X-CSRF-TOKEN": config.csrfToken
                }
            })
            .then(response => {
                if (response.ok) {
                    window.location.href = "/login";
                } else {
                    showToast("Logout failed. Please try again.", "error");
                    setTimeout(() => window.location.reload(), 1000);
                }
            })
            .catch(() => {
                showToast("Logout failed. Please check your connection.", "error");
                setTimeout(() => window.location.reload(), 1000);
            });
        });
    }

    // ---------------------------------------------------
    // Reports Page Binding
    // ---------------------------------------------------
    const reportContainer = document.getElementById("reportContainer");
    const btnGenerateReport = document.getElementById("btnGenerateReport");

    if (reportContainer && pageKey() === "reports") {
        const fetchLatestReport = () => {
            fetch('/api/workflow-report')
                .then(r => r.json())
                .then(res => {
                    if (res.success && res.report) {
                        displayReport(res.report.report_text);
                    } else {
                        renderReportEmptyState();
                    }
                })
                .catch(err => {
                    reportContainer.innerHTML = `<p class="alert-warning">Failed to load the latest workflow report.</p>`;
                });
        };

        const escapeHtml = (text) => {
            return (text || '')
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        };

        const renderValue = (val) => {
            if (!val) return '';
            if (typeof val === 'string') {
                return escapeHtml(val);
            }
            if (Array.isArray(val)) {
                return `<ul style="margin: 0.25rem 0; padding-left: 1.25rem; list-style-type: disc;">${val.map(item => `<li style="margin-bottom: 0.25rem; color: var(--text);">${escapeHtml(String(item))}</li>`).join('')}</ul>`;
            }
            if (typeof val === 'object') {
                let metricsHtml = '';
                let descHtml = '';

                Object.entries(val).forEach(([key, value]) => {
                    const formattedKey = key.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
                    if (key.toLowerCase() === 'description') {
                        descHtml = `<p style="margin-top: 0.75rem; color: var(--text); font-size: 0.88rem; line-height: 1.5; border-top: 1px dashed var(--line); padding-top: 0.5rem;">${renderValue(value)}</p>`;
                    } else {
                        metricsHtml += `
                            <div style="margin-bottom: 0.35rem; font-size: 0.88rem;">
                                <strong style="color: var(--text);">${escapeHtml(formattedKey)}:</strong> 
                                <span style="color: var(--muted);">${renderValue(value)}</span>
                            </div>
                        `;
                    }
                });

                return metricsHtml + descHtml;
            }
            return escapeHtml(String(val));
        };

        const renderReportJson = (dataObj) => {
            const recommendations = dataObj.recommendations || [];
            const recsHtml = recommendations.map(rec => `
                <li>
                    <strong>💡</strong>
                    <span>${typeof rec === 'string' ? escapeHtml(rec) : renderValue(rec)}</span>
                </li>
            `).join('');

            reportContainer.innerHTML = `
                <div class="report-dashboard">
                    <div class="report-section main-summary">
                        <h3>Executive Summary</h3>
                        <p>${renderValue(dataObj.executive_summary || dataObj['Executive Summary'])}</p>
                    </div>
                    
                    <div class="report-metrics-grid">
                        <div class="report-card velocity">
                            <div class="card-icon">⚡</div>
                            <h4>Velocity & Throughput</h4>
                            <p>${renderValue(dataObj.velocity || dataObj['Velocity'])}</p>
                        </div>
                        
                        <div class="report-card collaboration">
                            <div class="card-icon">👥</div>
                            <h4>Collaboration & Quality</h4>
                            <p>${renderValue(dataObj.collaboration || dataObj['Collaboration'])}</p>
                        </div>
                        
                        <div class="report-card delivery">
                            <div class="card-icon">🚀</div>
                            <h4>Delivery & Cadence</h4>
                            <p>${renderValue(dataObj.delivery || dataObj['Delivery'])}</p>
                        </div>
                    </div>
                    
                    <div class="report-section recommendations">
                        <h3>Actionable Recommendations</h3>
                        <ul>
                            ${recsHtml}
                        </ul>
                    </div>
                </div>
            `;
        };

        const renderReportMarkdown = (md) => {
            let html = md || "";
            // simple escapes
            html = html.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
            
            // headings
            html = html.replace(/^### (.*$)/gim, '<h3>$1</h3>');
            html = html.replace(/^## (.*$)/gim, '<h2>$1</h2>');
            html = html.replace(/^# (.*$)/gim, '<h1>$1</h1>');
            
            // bold
            html = html.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
            
            // list items
            html = html.replace(/^\s*[-*]\s+(.*$)/gim, '<li>$1</li>');
            
            // Wrap list items
            html = html.replace(/(<li>.*<\/li>)/gim, '<ul>$1</ul>');
            html = html.replace(/<\/ul>\s*<ul>/g, '');
            
            // format newlines/paragraphs
            html = html.split('\n\n').map(p => {
                const trimmed = p.trim();
                if (trimmed.startsWith('<h') || trimmed.startsWith('<ul') || trimmed.startsWith('<li')) {
                    return p;
                }
                return `<p>${p.replace(/\n/g, '<br>')}</p>`;
            }).join('\n');

            reportContainer.innerHTML = html;
        };

        const displayReport = (rawText) => {
            try {
                const parsed = JSON.parse(rawText);
                if (parsed && typeof parsed === 'object') {
                    renderReportJson(parsed);
                    return;
                }
            } catch (e) {
                // Not valid JSON, fall back to markdown
            }
            renderReportMarkdown(rawText);
        };

        const renderReportEmptyState = () => {
            reportContainer.innerHTML = `
                <div class="empty-state" style="text-align: center; padding: 2rem 0;">
                    <div style="font-size: 2.5rem; margin-bottom: 1rem;">📊</div>
                    <strong>No AI Workflow Report generated</strong>
                    <p style="color: #93c5fd; font-size: 0.88rem; max-width: 400px; margin: 0.5rem auto 0;">Click "Generate AI Report" above to prompt the Engineering Agent to write a detailed analysis of team velocity, code reviews, and delivery patterns.</p>
                </div>
            `;
        };

        fetchLatestReport();

        if (btnGenerateReport) {
            btnGenerateReport.addEventListener("click", () => {
                btnGenerateReport.disabled = true;
                btnGenerateReport.innerHTML = `
                    <svg class="animate-spin" viewBox="0 0 24 24" style="width:1.1rem;height:1.1rem;display:inline-block;vertical-align:middle;margin-right:0.5rem;"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    <span>Analyzing...</span>
                `;
                reportContainer.innerHTML = `
                    <div class="loading-state">
                        <svg class="animate-spin" viewBox="0 0 24 24" style="width:2rem;height:2rem;margin: 0 auto 1rem;"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        <p>The Engineering Agent is compiling commits, reviews, and tasks. Initiating local LLM analysis (this may take 10-20 seconds)...</p>
                    </div>
                `;

                fetch('/api/workflow-report/generate', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': config.csrfToken
                    }
                })
                .then(r => r.json())
                .then(res => {
                    btnGenerateReport.disabled = false;
                    btnGenerateReport.innerHTML = `
                        <svg viewBox="0 0 20 20" fill="currentColor" style="width:1.1rem;height:1.1rem;display:inline-block;vertical-align:middle;margin-right:0.5rem;"><path fill-rule="evenodd" d="M4 2a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V4a2 2 0 00-2-2H4zm9 4a1 1 0 10-2 0v3a1 1 0 002 0V6zm-3 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>
                        <span>Generate AI Report</span>
                    `;
                    if (res.success && res.report) {
                        if (res.warning) {
                            showToast(res.warning, "warning");
                        }
                        displayReport(res.report.report_text);
                    } else {
                        showToast("Failed to generate report.", "error");
                        fetchLatestReport();
                    }
                })
                .catch(err => {
                    btnGenerateReport.disabled = false;
                    btnGenerateReport.innerHTML = `
                        <svg viewBox="0 0 20 20" fill="currentColor" style="width:1.1rem;height:1.1rem;display:inline-block;vertical-align:middle;margin-right:0.5rem;"><path fill-rule="evenodd" d="M4 2a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V4a2 2 0 00-2-2H4zm9 4a1 1 0 10-2 0v3a1 1 0 002 0V6zm-3 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>
                        <span>Generate AI Report</span>
                    `;
                    showToast("An error occurred during report generation. Please try again.", "error");
                    fetchLatestReport();
                });
            });
        }
    }

    // ---------------------------------------------------
    // Settings Page Binding
    // ---------------------------------------------------
    const settingsContainer = document.getElementById("settingsContainer");

    if (settingsContainer && pageKey() === "settings") {
        fetch('/api/settings')
            .then(r => r.json())
            .then(res => {
                if (res.success && res.settings) {
                    renderSettingsForm(res.settings);
                } else {
                    settingsContainer.innerHTML = `<p class="alert-warning">Failed to load configuration settings.</p>`;
                }
            })
            .catch(err => {
                settingsContainer.innerHTML = `<p class="alert-warning">Error loading settings. Please check your connection.</p>`;
            });

        const renderSettingsForm = (settings) => {
            const provider = settings.ai_provider || 'gemini';
            settingsContainer.innerHTML = `
                <form id="settingsForm" class="settings-form">
                    <div class="settings-grid">
                        <section class="panel">
                            <div class="panel-heading">
                                <h2>AI Service Integration</h2>
                            </div>
                            <div class="form-group">
                                <label for="settingAiProvider">AI Provider</label>
                                <select id="settingAiProvider" class="form-control">
                                    <option value="gemini" ${provider === 'gemini' ? 'selected' : ''}>Google Gemini API</option>
                                    <option value="ollama" ${provider === 'ollama' ? 'selected' : ''}>Ollama Local AI</option>
                                </select>
                                <small>Select the AI provider to generate developer insights and velocity reports.</small>
                            </div>
                            
                            <div id="geminiSection" style="display: ${provider === 'gemini' ? 'block' : 'none'};">
                                <div class="form-group">
                                    <label for="settingGeminiModel">Gemini Model</label>
                                    <input type="text" id="settingGeminiModel" class="form-control" value="${settings.gemini_model || 'gemini-2.5-pro'}" placeholder="gemini-2.5-pro">
                                    <small>Google Gemini model target (e.g. gemini-2.5-pro, gemini-2.5-flash).</small>
                                </div>
                                <div class="form-group">
                                    <label>Gemini API Key</label>
                                    <input type="text" class="form-control" disabled value="Configured via environment variable (GEMINI_API_KEY)" style="background: var(--bg-hover); opacity: 0.8; font-style: italic;">
                                </div>
                            </div>
                            
                            <div id="ollamaSection" style="display: ${provider === 'ollama' ? 'block' : 'none'};">
                                <div class="form-group">
                                    <label for="settingOllamaUrl">Ollama Base URL</label>
                                    <input type="text" id="settingOllamaUrl" class="form-control" value="${settings.ollama_base_url || ''}" placeholder="http://localhost:11434">
                                    <small>Local HTTP endpoint of the Ollama server.</small>
                                </div>
                                <div class="form-group">
                                    <label for="settingOllamaModel">Model Name</label>
                                    <input type="text" id="settingOllamaModel" class="form-control" value="${settings.ollama_model || ''}" placeholder="llama3.1:8b">
                                    <small>The LLM model target loaded into local Ollama.</small>
                                </div>
                            </div>
                        </section>
                        
                        <section class="panel">
                            <div class="panel-heading">
                                <h2>Developer Metric Weights</h2>
                            </div>
                            <div class="form-group">
                                <div class="slider-header">
                                    <label>Task Completion Weight</label>
                                    <strong id="weightTaskVal">${Math.round(settings.weight_task_completion * 100)}%</strong>
                                </div>
                                <input type="range" id="weightTask" class="slider-control" min="0" max="100" step="5" value="${Math.round(settings.weight_task_completion * 100)}">
                            </div>
                            <div class="form-group">
                                <div class="slider-header">
                                    <label>Code Review Weight</label>
                                    <strong id="weightReviewsVal">${Math.round(settings.weight_reviews * 100)}%</strong>
                                </div>
                                <input type="range" id="weightReviews" class="slider-control" min="0" max="100" step="5" value="${Math.round(settings.weight_reviews * 100)}">
                            </div>
                            <div class="form-group">
                                <div class="slider-header">
                                    <label>Delivery Speed Weight</label>
                                    <strong id="weightDeliveryVal">${Math.round(settings.weight_delivery * 100)}%</strong>
                                </div>
                                <input type="range" id="weightDelivery" class="slider-control" min="0" max="100" step="5" value="${Math.round(settings.weight_delivery * 100)}">
                            </div>
                            <div class="form-group">
                                <div class="slider-header">
                                    <label>Active Days (Quality) Weight</label>
                                    <strong id="weightQualityVal">${Math.round(settings.weight_code_quality * 100)}%</strong>
                                </div>
                                <input type="range" id="weightQuality" class="slider-control" min="0" max="100" step="5" value="${Math.round(settings.weight_code_quality * 100)}">
                            </div>
                            
                            <div class="weight-total-container" id="weightTotalContainer">
                                <span>Total Weight sum:</span>
                                <strong id="weightTotalVal">100%</strong>
                            </div>
                            
                            <div id="weightWarning" class="alert-warning" style="display:none;">
                                Warning: Total metric weights must sum to exactly 100%.
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" id="btnSaveSettings" class="btn btn-primary">Save configuration</button>
                            </div>
                        </section>
                        
                        <section class="panel full-width">
                            <div class="panel-heading">
                                <h2>Recalculate Workspace Scores</h2>
                            </div>
                            <div class="recalc-block">
                                <p>Modifying weights will not automatically update historical developer scores. Run a workspace-wide scoring recalculation to rebuild the developer leaderboard instantly.</p>
                                <button type="button" id="btnRecalculate" class="btn btn-secondary">
                                    <span>Recalculate Scores</span>
                                </button>
                            </div>
                        </section>
                    </div>
                </form>
            `;

            // Toggle provider sections dynamically
            const aiProviderSelect = document.getElementById("settingAiProvider");
            const geminiSection = document.getElementById("geminiSection");
            const ollamaSection = document.getElementById("ollamaSection");

            aiProviderSelect.addEventListener("change", (e) => {
                if (e.target.value === "gemini") {
                    geminiSection.style.display = "block";
                    ollamaSection.style.display = "none";
                } else {
                    geminiSection.style.display = "none";
                    ollamaSection.style.display = "block";
                }
            });

            // Bind interactive slider live changes
            const wTask = document.getElementById("weightTask");
            const wReviews = document.getElementById("weightReviews");
            const wDelivery = document.getElementById("weightDelivery");
            const wQuality = document.getElementById("weightQuality");

            const wTaskVal = document.getElementById("weightTaskVal");
            const wReviewsVal = document.getElementById("weightReviewsVal");
            const wDeliveryVal = document.getElementById("weightDeliveryVal");
            const wQualityVal = document.getElementById("weightQualityVal");

            const weightTotalVal = document.getElementById("weightTotalVal");
            const weightTotalContainer = document.getElementById("weightTotalContainer");
            const weightWarning = document.getElementById("weightWarning");
            const btnSaveSettings = document.getElementById("btnSaveSettings");

            const updateSum = () => {
                const val1 = parseInt(wTask.value);
                const val2 = parseInt(wReviews.value);
                const val3 = parseInt(wDelivery.value);
                const val4 = parseInt(wQuality.value);

                wTaskVal.textContent = val1 + "%";
                wReviewsVal.textContent = val2 + "%";
                wDeliveryVal.textContent = val3 + "%";
                wQualityVal.textContent = val4 + "%";

                const sum = val1 + val2 + val3 + val4;
                weightTotalVal.textContent = sum + "%";

                if (sum !== 100) {
                    weightTotalContainer.classList.add("warning");
                    weightWarning.style.display = "block";
                    btnSaveSettings.disabled = true;
                } else {
                    weightTotalContainer.classList.remove("warning");
                    weightWarning.style.display = "none";
                    btnSaveSettings.disabled = false;
                }
            };

            [wTask, wReviews, wDelivery, wQuality].forEach(input => {
                input.addEventListener("input", updateSum);
            });

            updateSum();

            // Form submission
            const settingsForm = document.getElementById("settingsForm");
            settingsForm.addEventListener("submit", (e) => {
                e.preventDefault();
                btnSaveSettings.disabled = true;
                btnSaveSettings.textContent = "Saving...";

                fetch('/api/settings', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': config.csrfToken
                    },
                    body: JSON.stringify({
                        ai_provider: aiProviderSelect.value,
                        gemini_model: document.getElementById("settingGeminiModel").value,
                        ollama_base_url: document.getElementById("settingOllamaUrl").value,
                        ollama_model: document.getElementById("settingOllamaModel").value,
                        weight_task_completion: parseInt(wTask.value) / 100,
                        weight_reviews: parseInt(wReviews.value) / 100,
                        weight_delivery: parseInt(wDelivery.value) / 100,
                        weight_code_quality: parseInt(wQuality.value) / 100,
                    })
                })
                .then(r => r.json())
                .then(res => {
                    btnSaveSettings.disabled = false;
                    btnSaveSettings.textContent = "Save configuration";
                    if (res.success) {
                        showToast("Settings updated successfully!", "success");
                    } else {
                        showToast(res.message || "Failed to update settings.", "error");
                    }
                })
                .catch(() => {
                    btnSaveSettings.disabled = false;
                    btnSaveSettings.textContent = "Save configuration";
                    showToast("An error occurred. Settings were not saved.", "error");
                });
            });

            // Recalculation Action
            const btnRecalculate = document.getElementById("btnRecalculate");
            if (btnRecalculate) {
                btnRecalculate.addEventListener("click", async () => {
                    const confirmed = await showConfirm({
                        title: "Recalculate Metrics",
                        message: "Are you sure you want to recalculate all developer metrics? This runs intensive database operations.",
                        confirmText: "Recalculate",
                        cancelText: "Cancel",
                        danger: true
                    });
                    if (!confirmed) {
                        return;
                    }

                    btnRecalculate.disabled = true;
                    btnRecalculate.innerHTML = `
                        <svg class="animate-spin" viewBox="0 0 24 24" style="width:1.1rem;height:1.1rem;display:inline-block;vertical-align:middle;margin-right:0.5rem;"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        <span>Recalculating...</span>
                    `;

                    fetch('/api/settings/recalculate', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': config.csrfToken
                        }
                    })
                    .then(r => r.json())
                    .then(res => {
                        btnRecalculate.disabled = false;
                        btnRecalculate.innerHTML = `<span>Recalculate Scores</span>`;
                        if (res.success) {
                            showToast(res.message, "success");
                            // Refresh page state
                            setTimeout(() => {
                                window.location.reload();
                            }, 1000);
                        } else {
                            showToast("Recalculation failed.", "error");
                        }
                    })
                    .catch(() => {
                        btnRecalculate.disabled = false;
                        btnRecalculate.innerHTML = `<span>Recalculate Scores</span>`;
                        showToast("An error occurred during recalculation.", "error");
                    });
                });
            }
        };
    }

    // ---------------------------------------------------
    // Custom Actions (Sync & Insights)
    // ---------------------------------------------------

    // 1. Integration Sync
    document.querySelectorAll(".btn-sync-integration").forEach(btn => {
        btn.addEventListener("click", () => {
            const id = btn.getAttribute("data-sync-integration");
            if (!id) return;

            btn.disabled = true;
            const originalHtml = btn.innerHTML;
            btn.innerHTML = `${spinnerSvg("0.9rem", "0.9rem")}<span>Syncing...</span>`;

            fetch(`/api/integrations/${id}/sync`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': config.csrfToken
                }
            })
            .then(r => r.json())
            .then(res => {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
                if (res.success) {
                    showToast(res.message || "Repository sync started in background!", "success");
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showToast(res.message || "Failed to start sync.", "error");
                }
            })
            .catch(() => {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
                showToast("An error occurred during sync request.", "error");
            });
        });
    });

    // 2. Repository Sync
    document.querySelectorAll(".btn-sync-repo").forEach(btn => {
        btn.addEventListener("click", () => {
            const id = btn.getAttribute("data-sync-repo");
            if (!id) return;

            btn.disabled = true;
            const originalHtml = btn.innerHTML;
            btn.innerHTML = `${spinnerSvg("0.8rem", "0.8rem")}<span>Syncing...</span>`;

            fetch(`/api/repositories/${id}/sync`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': config.csrfToken
                }
            })
            .then(r => r.json())
            .then(res => {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
                if (res.success) {
                    showToast(res.message || "Repository sync queued successfully!", "success");
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showToast(res.message || "Failed to sync repository.", "error");
                }
            })
            .catch(() => {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
                showToast("An error occurred during repository sync.", "error");
            });
        });
    });

    // 3. Generate All Insights
    const btnGenerateAllInsights = document.getElementById("btnGenerateAllInsights");
    if (btnGenerateAllInsights) {
        btnGenerateAllInsights.addEventListener("click", () => {
            btnGenerateAllInsights.disabled = true;
            const originalHtml = btnGenerateAllInsights.innerHTML;
            btnGenerateAllInsights.innerHTML = `${spinnerSvg("1.1rem", "1.1rem")}<span>Queuing...</span>`;

            fetch('/api/insights/generate-all', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': config.csrfToken
                }
            })
            .then(r => r.json())
            .then(res => {
                btnGenerateAllInsights.disabled = false;
                btnGenerateAllInsights.innerHTML = originalHtml;
                if (res.success) {
                    showToast(res.message || "Bulk developer insights generation started!", "success");
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showToast(res.message || "Failed to start bulk insight generation.", "error");
                }
            })
            .catch(() => {
                btnGenerateAllInsights.disabled = false;
                btnGenerateAllInsights.innerHTML = originalHtml;
                showToast("An error occurred.", "error");
            });
        });
    }

    // 4. Regenerate Individual Insight
    document.querySelectorAll(".btn-regenerate-insight").forEach(btn => {
        btn.addEventListener("click", () => {
            const devId = btn.getAttribute("data-regenerate-insight");
            if (!devId) return;

            btn.disabled = true;
            const originalHtml = btn.innerHTML;
            btn.innerHTML = `${spinnerSvg("0.8rem", "0.8rem")}<span>Generating...</span>`;

            fetch(`/api/developers/${devId}/generate-insights`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': config.csrfToken
                }
            })
            .then(r => r.json())
            .then(res => {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
                if (res.success) {
                    showToast(res.message || "Insight generation queued successfully!", "success");
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showToast(res.message || "Failed to generate insight.", "error");
                }
            })
            .catch(() => {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
                showToast("An error occurred during insight generation.", "error");
            });
        });
    });

    // ---------------------------------------------------
    // Developer Mode Page Binding
    // ---------------------------------------------------
    if (pageKey() === "developer_mode") {
        const apiKeyBoxContainer = document.getElementById("apiKeyBoxContainer");
        const requestTesterContainer = document.getElementById("requestTesterContainer");
        const endpointListContainer = document.getElementById("endpointListContainer");
        const endpointSearchInput = document.getElementById("endpointSearchInput");
        const apiKeyOptionInput = document.getElementById("apiKeyOptionInput");
        const queueManagerContainer = document.getElementById("queueManagerContainer");

        if (apiKeyOptionInput) {
            apiKeyOptionInput.value = developerApiKey;
            apiKeyOptionInput.addEventListener("input", (e) => {
                developerApiKey = e.target.value;
                localStorage.setItem("developer_api_key", developerApiKey);
                updateRequestTester();
            });
        }

        if (apiKeyBoxContainer) {
            apiKeyBoxContainer.addEventListener("click", async (e) => {
                // Generate Key
                const btnGen = e.target.closest("#btnGenerateKey");
                if (btnGen) {
                    btnGen.disabled = true;
                    const originalHtml = btnGen.innerHTML;
                    btnGen.innerHTML = `${spinnerSvg("0.9rem", "0.9rem")} Generating...`;

                    fetch('/api/developer/tokens', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': config.csrfToken
                        }
                    })
                    .then(r => r.json())
                    .then(res => {
                        if (pageKey() !== "developer_mode") return;
                        if (res.success) {
                            justGeneratedToken = res.token;
                            developerApiKey = res.token;
                            localStorage.setItem("developer_api_key", developerApiKey);
                            if (apiKeyOptionInput) {
                                apiKeyOptionInput.value = developerApiKey;
                            }
                            
                            try {
                                const currentSaved = JSON.parse(localStorage.getItem('developer_api_keys') || '{}');
                                currentSaved[res.token_id] = res.token;
                                localStorage.setItem('developer_api_keys', JSON.stringify(currentSaved));
                            } catch (ex) {
                                // ignore
                            }

                            showToast("API key generated successfully!", "success");
                            fetchTokens();
                            updateRequestTester();
                        } else {
                            showToast("Failed to generate token.", "error");
                            btnGen.disabled = false;
                            btnGen.innerHTML = originalHtml;
                        }
                    })
                    .catch(() => {
                        if (pageKey() !== "developer_mode") return;
                        showToast("Error generating API key.", "error");
                        btnGen.disabled = false;
                        btnGen.innerHTML = originalHtml;
                    });
                    return;
                }

                // Dismiss Warning
                const btnDismiss = e.target.closest("#btnDismissWarning");
                if (btnDismiss) {
                    justGeneratedToken = null;
                    updateApiKeyBox();
                    return;
                }

                // Toggle visibility
                const btnToggle = e.target.closest(".btn-toggle-visibility");
                if (btnToggle) {
                    const tokenId = btnToggle.getAttribute("data-id");
                    const maskedEl = document.getElementById(`masked-${tokenId}`);
                    const rawEl = document.getElementById(`raw-${tokenId}`);
                    
                    if (!rawEl || !rawEl.textContent) {
                        showToast("For security, this API key can only be viewed immediately after generation on the browser it was created.", "warning");
                        return;
                    }

                    if (rawEl.style.display === "none") {
                        rawEl.style.display = "inline";
                        maskedEl.style.display = "none";
                        btnToggle.innerHTML = `
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                <line x1="1" y1="1" x2="23" y2="23"></line>
                            </svg>
                        `;
                    } else {
                        rawEl.style.display = "none";
                        maskedEl.style.display = "inline";
                        btnToggle.innerHTML = `
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        `;
                    }
                    return;
                }

                // Copy token
                const btnCopy = e.target.closest(".btn-copy-token");
                if (btnCopy) {
                    const tokenId = btnCopy.getAttribute("data-id");
                    const rawEl = document.getElementById(`raw-${tokenId}`);
                    
                    if (!rawEl || !rawEl.textContent) {
                        showToast("This API key cannot be copied because it was generated in a different session or browser.", "warning");
                        return;
                    }

                    navigator.clipboard.writeText(rawEl.textContent)
                        .then(() => {
                            showToast("API key copied to clipboard!", "success");
                        })
                        .catch(() => {
                            showToast("Failed to copy API key.", "error");
                        });
                    return;
                }

                // Delete token
                const btnDelete = e.target.closest(".btn-delete-token");
                if (btnDelete) {
                    const tokenId = btnDelete.getAttribute("data-id");
                    const confirmed = await showConfirm({
                        title: "Revoke API Key",
                        message: "Are you sure you want to revoke this API key? Any applications currently using this key will immediately lose access.",
                        confirmText: "Revoke",
                        cancelText: "Cancel",
                        danger: true
                    });
                    if (!confirmed) {
                        return;
                    }
                    
                    fetch(`/api/developer/tokens/${tokenId}`, {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': config.csrfToken
                        }
                    })
                    .then(r => r.json())
                    .then(res => {
                        if (pageKey() !== "developer_mode") return;
                        if (res.success) {
                            showToast("API Key revoked successfully.", "success");
                            
                            try {
                                const currentSaved = JSON.parse(localStorage.getItem('developer_api_keys') || '{}');
                                delete currentSaved[tokenId];
                                localStorage.setItem('developer_api_keys', JSON.stringify(currentSaved));
                            } catch (ex) {
                                // ignore
                            }
                        } else {
                            showToast("Failed to revoke key.", "error");
                        }
                        fetchTokens();
                    })
                    .catch(() => {
                        if (pageKey() !== "developer_mode") return;
                        showToast("Error revoking key.", "error");
                        fetchTokens();
                    });
                    return;
                }
            });
        }

        let queueStatus = { pending_jobs: 0, failed_jobs: 0, connection: 'database' };
        const fetchQueueStatus = () => {
            if (pageKey() !== "developer_mode") return;
            fetch('/api/engineering-agent/queue/status', {
                headers: {
                    'Accept': 'application/json',
                    'Authorization': developerApiKey && developerApiKey !== "YOUR_API_KEY" ? 'Bearer ' + developerApiKey : ''
                }
            })
            .then(r => r.json())
            .then(res => {
                if (pageKey() !== "developer_mode") return;
                if (res.success) {
                    queueStatus = res;
                    updateQueueManager();
                }
            })
            .catch(() => {
                if (pageKey() !== "developer_mode") return;
                if (queueManagerContainer) {
                    queueManagerContainer.innerHTML = `<p class="alert-warning">Failed to load queue status.</p>`;
                }
            });
        };

        const updateQueueManager = () => {
            if (pageKey() !== "developer_mode") return;
            if (!queueManagerContainer) return;

            queueManagerContainer.innerHTML = `
                <div style="display: flex; gap: 1.5rem; margin-bottom: 1rem; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 10rem; padding: 0.75rem 1rem; background: var(--panel-soft); border: 1px solid var(--line); border-radius: 8px;">
                        <span style="display: block; color: var(--muted); font-size: 0.85rem; margin-bottom: 0.25rem;">Pending Jobs</span>
                        <strong style="font-size: 1.5rem; color: ${queueStatus.pending_jobs > 0 ? 'var(--blue)' : 'var(--text)'};">${queueStatus.pending_jobs}</strong>
                    </div>
                    <div style="flex: 1; min-width: 10rem; padding: 0.75rem 1rem; background: var(--panel-soft); border: 1px solid var(--line); border-radius: 8px;">
                        <span style="display: block; color: var(--muted); font-size: 0.85rem; margin-bottom: 0.25rem;">Failed Jobs</span>
                        <strong style="font-size: 1.5rem; color: ${queueStatus.failed_jobs > 0 ? '#ef4444' : 'var(--text)'};">${queueStatus.failed_jobs}</strong>
                    </div>
                    <div style="flex: 1; min-width: 10rem; padding: 0.75rem 1rem; background: var(--panel-soft); border: 1px solid var(--line); border-radius: 8px;">
                        <span style="display: block; color: var(--muted); font-size: 0.85rem; margin-bottom: 0.25rem;">Queue Connection</span>
                        <strong style="font-size: 1.25rem; color: var(--text); text-transform: uppercase;">${escapeHtml(queueStatus.connection)}</strong>
                    </div>
                </div>
                <div class="key-actions">
                    <button class="btn btn-primary" id="btnExecuteQueue" type="button" ${queueStatus.pending_jobs === 0 ? "disabled" : ""}>
                        <span>Execute Queue</span>
                    </button>
                    <button class="btn btn-secondary" id="btnRefreshQueue" type="button">
                        <span>Refresh Status</span>
                    </button>
                    <button class="btn btn-secondary danger" id="btnClearQueue" type="button" style="color: #ef4444; border-color: #fca5a5;">
                        <span>Clear Queue</span>
                    </button>
                </div>
            `;

            // Bind actions
            document.getElementById("btnExecuteQueue")?.addEventListener("click", () => {
                const btn = document.getElementById("btnExecuteQueue");
                btn.disabled = true;
                btn.innerHTML = `${spinnerSvg("0.9rem", "0.9rem")} Executing...`;
                
                fetch('/api/engineering-agent/queue/work', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': config.csrfToken
                    }
                })
                .then(r => r.json())
                .then(res => {
                    if (pageKey() !== "developer_mode") return;
                    if (res.success) {
                        showToast(res.message, "success");
                    } else {
                        showToast(res.message || "Failed to trigger queue work.", "error");
                    }
                    setTimeout(fetchQueueStatus, 1000);
                })
                .catch(() => {
                    if (pageKey() !== "developer_mode") return;
                    showToast("Error triggering queue execution.", "error");
                    fetchQueueStatus();
                });
            });

            document.getElementById("btnRefreshQueue")?.addEventListener("click", () => {
                const btn = document.getElementById("btnRefreshQueue");
                btn.disabled = true;
                btn.innerHTML = `${spinnerSvg("0.9rem", "0.9rem")} Refreshing...`;
                fetchQueueStatus();
            });

            document.getElementById("btnClearQueue")?.addEventListener("click", async () => {
                const confirmed = await showConfirm({
                    title: "Clear Pending Jobs",
                    message: "Are you sure you want to clear all pending jobs from the queue?",
                    confirmText: "Clear",
                    cancelText: "Cancel",
                    danger: true
                });
                if (!confirmed) {
                    return;
                }
                const btn = document.getElementById("btnClearQueue");
                btn.disabled = true;
                btn.innerHTML = `${spinnerSvg("0.9rem", "0.9rem")} Clearing...`;

                fetch('/api/engineering-agent/queue/clear', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': config.csrfToken
                    }
                })
                .then(r => r.json())
                .then(res => {
                    if (pageKey() !== "developer_mode") return;
                    if (res.success) {
                        showToast(res.message, "success");
                    } else {
                        showToast(res.message || "Failed to clear queue.", "error");
                    }
                    fetchQueueStatus();
                })
                .catch(() => {
                    if (pageKey() !== "developer_mode") return;
                    showToast("Error clearing queue.", "error");
                    fetchQueueStatus();
                });
            });
        };

        const fetchTokens = () => {
            fetch('/api/developer/tokens')
                .then(r => r.json())
                .then(res => {
                    if (pageKey() !== "developer_mode") return;
                    if (res.success) {
                        activeTokens = res.tokens;
                    }
                    updateApiKeyBox();
                })
                .catch(() => {
                    if (pageKey() !== "developer_mode") return;
                    if (apiKeyBoxContainer) {
                        apiKeyBoxContainer.innerHTML = `<p class="alert-warning">Failed to load API keys.</p>`;
                    }
                });
        };

        const updateApiKeyBox = () => {
            if (pageKey() !== "developer_mode") return;
            if (!apiKeyBoxContainer) return;

            let savedKeys = {};
            try {
                savedKeys = JSON.parse(localStorage.getItem('developer_api_keys') || '{}');
            } catch (e) {
                savedKeys = {};
            }

            apiKeyBoxContainer.innerHTML = `
                <div class="token-manager">
                    ${justGeneratedToken ? `
                        <div class="key-warning-banner" style="margin-bottom: 1rem; position: relative;">
                            <strong>🎉 API Key Generated</strong>
                            <p>Your new API key has been generated and added to the list below. Make sure to copy it now.</p>
                            <button type="button" id="btnDismissWarning" style="position: absolute; top: 0.5rem; right: 0.5rem; background: transparent; border: none; font-size: 1.1rem; cursor: pointer; color: #b45309;">&times;</button>
                        </div>
                    ` : ''}

                    <!-- Purple Header Banner -->
                    <div class="token-banner">
                        <div class="token-banner-info">
                            <h2 class="token-banner-title">Access Tokens</h2>
                            <div class="token-banner-desc">
                                <svg class="info-icon" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="12" y1="16" x2="12" y2="12"></line>
                                    <line x1="12" y1="8" x2="12.01" y2="8"></line>
                                </svg>
                                <span>Create & manage your api keys</span>
                            </div>
                        </div>
                        <button class="btn btn-generate-api" id="btnGenerateKey" type="button">Generate API key</button>
                    </div>

                    <!-- Tokens Table/List Card -->
                    <div class="token-list-card">
                        <table class="token-table">
                            <thead>
                                <tr>
                                    <th class="col-token">Token</th>
                                    <th class="col-created">Created On</th>
                                    <th class="col-actions"></th>
                                </tr>
                            </thead>
                            <tbody>
                                ${activeTokens.length === 0 ? `
                                    <tr>
                                        <td colspan="3" style="text-align: center; color: var(--muted); padding: 2rem 0;">
                                            No active tokens found. Click "Generate API key" to create one.
                                        </td>
                                    </tr>
                                ` : activeTokens.map(token => {
                                    const rawVal = savedKeys[token.id] || '';
                                    return `
                                        <tr data-token-id="${token.id}">
                                            <td class="col-token">
                                                <div class="token-value-wrapper">
                                                    <span class="token-masked" id="masked-${token.id}">*****************************************</span>
                                                    <span class="token-raw" id="raw-${token.id}" style="display: none; font-family: monospace;">${escapeHtml(rawVal)}</span>
                                                    <button class="btn-icon btn-toggle-visibility" data-id="${token.id}" title="Toggle Visibility">
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                            <circle cx="12" cy="12" r="3"></circle>
                                                        </svg>
                                                    </button>
                                                    <button class="btn-icon btn-copy-token" data-id="${token.id}" title="Copy Token">
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                            <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                                            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                                                        </svg>
                                                    </button>
                                                </div>
                                            </td>
                                            <td class="col-created">
                                                <span class="date-pill">${formatDate(token.created_at)}</span>
                                            </td>
                                            <td class="col-actions">
                                                <button class="btn-icon btn-delete-token" data-id="${token.id}" title="Delete Token">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <polyline points="3 6 5 6 21 6"></polyline>
                                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                    </svg>
                                                </button>
                                            </td>
                                        </tr>
                                    `;
                                }).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
            `;

        };

        const updateEndpointList = () => {
            if (pageKey() !== "developer_mode") return;
            if (!endpointListContainer) return;
            const searchVal = endpointSearchInput ? endpointSearchInput.value.trim().toLowerCase() : "";

            const filtered = apiEndpoints.filter(ep => 
                ep.name.toLowerCase().includes(searchVal) ||
                ep.path.toLowerCase().includes(searchVal) ||
                ep.desc.toLowerCase().includes(searchVal) ||
                ep.method.toLowerCase().includes(searchVal)
            );

            if (!filtered.length) {
                endpointListContainer.innerHTML = `<p style="color:var(--muted); font-size:0.88rem; text-align:center; padding:1rem 0;">No matching endpoints.</p>`;
                return;
            }

            endpointListContainer.innerHTML = filtered.map(ep => `
                <div class="endpoint-item ${ep.id === activeEndpointId ? "is-active" : ""}" data-endpoint-id="${ep.id}">
                    <div class="endpoint-header-row">
                        <span class="endpoint-badge ${ep.method.toLowerCase()}">${ep.method}</span>
                        <span class="endpoint-path">${escapeHtml(ep.path)}</span>
                    </div>
                    <div class="endpoint-name">${escapeHtml(ep.name)}</div>
                    <div class="endpoint-desc">${escapeHtml(ep.desc)}</div>
                </div>
            `).join("");

            endpointListContainer.querySelectorAll(".endpoint-item").forEach(item => {
                item.addEventListener("click", () => {
                    activeEndpointId = item.getAttribute("data-endpoint-id");
                    lastResponseData = null;
                    lastResponseStatus = null;
                    lastResponseTime = null;
                    updateEndpointList();
                    updateRequestTester();
                });
            });
        };

        const updateRequestTester = () => {
            if (pageKey() !== "developer_mode") return;
            if (!requestTesterContainer) return;

            const ep = apiEndpoints.find(e => e.id === activeEndpointId);
            if (!ep) {
                requestTesterContainer.innerHTML = `<p style="color:var(--muted);">Select an endpoint from the sidebar to test.</p>`;
                return;
            }

            // Gather values of parameter inputs if they already exist in the DOM, so they are not lost
            const paramValues = {};
            requestTesterContainer.querySelectorAll(".param-input").forEach(inp => {
                paramValues[inp.getAttribute("data-param-key")] = inp.value;
            });

            // If empty, set defaults
            ep.params.forEach(p => {
                if (paramValues[p.key] === undefined) {
                    paramValues[p.key] = p.placeholder;
                }
            });

            const curlCmd = buildCurlCommand(ep, paramValues, developerApiKey);

            requestTesterContainer.innerHTML = `
                <div style="margin-bottom: 1rem;">
                    <span class="endpoint-badge ${ep.method.toLowerCase()}" style="font-size:0.85rem; padding: 0.25rem 0.6rem;">${ep.method}</span>
                    <strong style="margin-left:0.5rem; font-size:1rem; vertical-align:middle; color:var(--text);">${escapeHtml(ep.name)}</strong>
                    <p style="color:var(--muted); font-size:0.85rem; margin: 0.5rem 0 0;">${escapeHtml(ep.desc)}</p>
                </div>

                ${ep.params.length ? `
                    <div style="margin-bottom: 1rem;">
                        <h4 style="font-size:0.88rem; font-weight:600; margin-bottom:0.5rem; color:var(--text);">Path Parameters</h4>
                        <table class="param-input-table">
                            <tbody>
                                ${ep.params.map(p => `
                                    <tr>
                                        <td>${escapeHtml(p.label)}</td>
                                        <td>
                                            <input type="text" class="form-control param-input" data-param-key="${p.key}" placeholder="${escapeHtml(p.placeholder)}" value="${escapeHtml(paramValues[p.key])}">
                                        </td>
                                    </tr>
                                `).join("")}
                            </tbody>
                        </table>
                    </div>
                ` : ""}

                <div style="margin-bottom: 1rem;">
                    <h4 style="font-size:0.88rem; font-weight:600; margin-bottom:0.5rem; color:var(--text);">cURL Command</h4>
                    <div class="code-block-wrapper">
                        <pre class="curl-code-pre" id="curlCodeBlock">${escapeHtml(curlCmd)}</pre>
                        <button class="btn-copy-code" id="btnCopyCurl" type="button">
                            <svg viewBox="0 0 24 24" width="14" height="14" style="width:14px; height:14px; margin-right:2px;"><path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z" fill="currentColor"/></svg>
                            <span>Copy</span>
                        </button>
                    </div>
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <button class="btn btn-primary" id="btnSendTestRequest" type="button" ${isSendingRequest ? "disabled" : ""}>
                        ${isSendingRequest ? `${spinnerSvg("0.9rem", "0.9rem")} Sending...` : "<span>Send Request</span>"}
                    </button>
                </div>

                ${(lastResponseStatus !== null) ? `
                    <div>
                        <h4 style="font-size:0.88rem; font-weight:600; margin-bottom:0.5rem; color:var(--text);">Response Console</h4>
                        <div class="response-status-row" style="margin-bottom: 0.5rem;">
                            <span>HTTP Status: <span class="response-status-badge ${lastResponseStatus >= 200 && lastResponseStatus < 300 ? 'success' : 'error'}">${lastResponseStatus}</span></span>
                            ${lastResponseTime ? `<span style="color:var(--muted); font-size:0.82rem;">Time: ${lastResponseTime}ms</span>` : ""}
                        </div>
                        <pre class="response-pre">${escapeHtml(JSON.stringify(lastResponseData, null, 4))}</pre>
                    </div>
                ` : ""}
            `;

            // Bind param inputs
            requestTesterContainer.querySelectorAll(".param-input").forEach(inp => {
                inp.addEventListener("input", () => {
                    const currentValues = {};
                    requestTesterContainer.querySelectorAll(".param-input").forEach(i => {
                        currentValues[i.getAttribute("data-param-key")] = i.value;
                    });
                    const updatedCurl = buildCurlCommand(ep, currentValues, developerApiKey);
                    const curlBlock = document.getElementById("curlCodeBlock");
                    if (curlBlock) {
                        curlBlock.textContent = updatedCurl;
                    }
                });
            });

            // Bind copy cURL
            document.getElementById("btnCopyCurl")?.addEventListener("click", () => {
                const curlBlock = document.getElementById("curlCodeBlock");
                if (curlBlock) {
                    navigator.clipboard.writeText(curlBlock.textContent);
                    showToast("cURL command copied to clipboard!", "success");
                }
            });

            // Bind send request
            document.getElementById("btnSendTestRequest")?.addEventListener("click", () => {
                if (isSendingRequest) return;

                isSendingRequest = true;
                updateRequestTester();

                const currentValues = {};
                requestTesterContainer.querySelectorAll(".param-input").forEach(i => {
                    currentValues[i.getAttribute("data-param-key")] = i.value;
                });

                let url = window.location.origin + ep.path;
                ep.params.forEach(p => {
                    const val = currentValues[p.key] || p.placeholder;
                    url = url.replace(`{${p.key}}`, val);
                });

                const headers = {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                };

                if (developerApiKey && developerApiKey !== "YOUR_API_KEY") {
                    headers['Authorization'] = 'Bearer ' + developerApiKey;
                }

                const startTime = performance.now();

                fetch(url, {
                    method: ep.method,
                    headers: headers
                })
                .then(res => {
                    lastResponseStatus = res.status;
                    lastResponseTime = Math.round(performance.now() - startTime);
                    return res.json().catch(() => ({ message: "Response was not valid JSON." }));
                })
                .then(data => {
                    if (pageKey() !== "developer_mode") return;
                    lastResponseData = data;
                    isSendingRequest = false;
                    updateRequestTester();
                })
                .catch(err => {
                    if (pageKey() !== "developer_mode") return;
                    lastResponseData = { error: err.message || "Network request failed." };
                    lastResponseStatus = 0;
                    lastResponseTime = Math.round(performance.now() - startTime);
                    isSendingRequest = false;
                    updateRequestTester();
                });
            });
        };

        // Initialize lists and containers
        fetchTokens();
        updateEndpointList();
        updateRequestTester();
        fetchQueueStatus();

        const queueInterval = setInterval(fetchQueueStatus, 300000);

        endpointSearchInput?.addEventListener("input", updateEndpointList);

        window.addEventListener("popstate", () => clearInterval(queueInterval), { once: true });
    }
}

function renderActivityChart() {
    const canvas = document.getElementById("activity-chart");

    if (!canvas) {
        return;
    }

    const labels = data.activity?.labels?.length ? data.activity.labels : ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"];
    const commits = data.activity?.commits?.length ? data.activity.commits : [0, 0, 0, 0, 0, 0, 0];

    activeChart = new Chart(canvas, {
        type: "line",
        data: {
            labels,
            datasets: [{
                label: "Commits",
                data: commits,
                borderColor: "#2563eb",
                backgroundColor: "rgba(37, 99, 235, 0.12)",
                borderWidth: 3,
                pointBackgroundColor: "#0f766e",
                pointRadius: 4,
                fill: true,
                tension: 0.35,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false,
                },
            },
            scales: {
                x: {
                    grid: {
                        display: false,
                    },
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0,
                    },
                },
            },
        },
    });
}

function destroyChart() {
    if (activeChart) {
        activeChart.destroy();
        activeChart = null;
    }
}

function normalizedRepositories() {
    return asArray(data.repositories).map((repo) => ({
        id: repo.id,
        name: repo.name ?? "Untitled repository",
        owner: repo.owner ?? "",
        provider: repo.provider ?? "",
        commits: repo.commits_count ?? repo.commits ?? 0,
        pull_requests: repo.pull_requests_count ?? repo.pull_requests ?? 0,
        tasks: repo.tasks_count ?? repo.tasks ?? 0,
        deployments: repo.deployments_count ?? repo.deployments ?? 0,
    }));
}

function normalizedDevelopers() {
    return asArray(data.developers).map((developer) => {
        const metric = asArray(developer.metrics)[0] ?? {};

        return {
            name: developer.name ?? "Unknown developer",
            username: developer.username || developer.email || "No username",
            avatar: developer.avatar,
            commits: developer.commits_count ?? metric.commits ?? 0,
            prs: developer.pull_requests_count ?? metric.prs_created ?? 0,
            reviews: developer.reviews_count ?? metric.reviews_done ?? 0,
            tasks: developer.tasks_count ?? 0,
            score: metric.developer_score ?? 0,
        };
    });
}

function normalizedLeaderboard() {
    const metricRows = asArray(data.leaderboard);

    if (metricRows.length) {
        return metricRows.map((row) => ({
            name: row.developer?.name ?? "Unknown developer",
            username: row.developer?.username ?? row.developer?.email ?? "No username",
            commits: row.commits ?? 0,
            prs: row.prs_created ?? 0,
            reviews: row.reviews_done ?? 0,
            score: row.developer_score ?? 0,
        }));
    }

    return normalizedDevelopers().sort((a, b) => Number(b.score) - Number(a.score));
}

function normalizedIntegrations() {
    return asArray(data.integrations);
}

function normalizedInsights() {
    return asArray(data.insights).map((insight) => ({
        developer: insight.developer?.name ?? "Team",
        developerId: insight.developer_id,
        summary: insight.summary,
        recommendations: asArray(insight.recommendations),
    }));
}

function asArray(value) {
    return Array.isArray(value) ? value : [];
}

function formatNumber(value) {
    const number = Number(value ?? 0);  

    return Number.isInteger(number)
        ? number.toLocaleString()
        : number.toLocaleString(undefined, { maximumFractionDigits: 2 });
}

function initialsFor(value) {
    return String(value || "EA")
        .split(" ")
        .map((part) => part[0])
        .join("")
        .slice(0, 2)
        .toUpperCase();
}

function escapeHtml(value) {
    return String(value ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
}

function formatDate(dateString) {
    const d = new Date(dateString);
    const day = String(d.getDate()).padStart(2, '0');
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const month = months[d.getMonth()];
    const year = String(d.getFullYear()).slice(-2);
    const hours = String(d.getHours()).padStart(2, '0');
    const minutes = String(d.getMinutes()).padStart(2, '0');
    return `${day}-${month}-${year} ${hours}:${minutes}`;
}

function showToast(message, type = 'info', duration = 4000) {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast-notification ${type}`;

    let iconSvg = '';
    if (type === 'success') {
        iconSvg = `<svg viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><polyline points="22 4 12 14.01 9 11.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>`;
    } else if (type === 'error') {
        iconSvg = `<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/><line x1="15" y1="9" x2="9" y2="15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><line x1="9" y1="9" x2="15" y2="15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>`;
    } else if (type === 'warning') {
        iconSvg = `<svg viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><line x1="12" y1="9" x2="12" y2="13" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><line x1="12" y1="17" x2="12.01" y2="17" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>`;
    } else {
        iconSvg = `<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/><line x1="12" y1="16" x2="12" y2="12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><line x1="12" y1="8" x2="12.01" y2="8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>`;
    }

    toast.innerHTML = `
        <div class="toast-icon">${iconSvg}</div>
        <div class="toast-message">${escapeHtml(message)}</div>
        <button type="button" class="toast-close">
            <svg viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
    `;

    container.appendChild(toast);

    const dismissToast = () => {
        if (toast.classList.contains('toast-exit')) return;
        toast.classList.add('toast-exit');
        toast.addEventListener('animationend', () => {
            toast.remove();
            if (container.children.length === 0) {
                container.remove();
            }
        });
    };

    toast.querySelector('.toast-close').addEventListener('click', dismissToast);

    setTimeout(dismissToast, duration);
}

function showConfirm({ title, message, confirmText = 'Confirm', cancelText = 'Cancel', danger = false }) {
    return new Promise((resolve) => {
        const backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop';

        let iconSvg = '';
        if (danger) {
            iconSvg = `<svg viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>`;
        } else {
            iconSvg = `<svg viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>`;
        }

        backdrop.innerHTML = `
            <div class="modal-container">
                <div class="modal-content-wrapper">
                    <div class="modal-icon-wrapper ${danger ? 'danger' : 'primary'}">
                        ${iconSvg}
                    </div>
                    <div class="modal-text-content">
                        <h3 class="modal-title">${escapeHtml(title)}</h3>
                        <p class="modal-message">${escapeHtml(message)}</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="modal-btn cancel-btn">${escapeHtml(cancelText)}</button>
                    <button type="button" class="modal-btn ${danger ? 'confirm-btn-danger' : 'confirm-btn-primary'} confirm-btn">${escapeHtml(confirmText)}</button>
                </div>
            </div>
        `;

        document.body.appendChild(backdrop);

        const closeModal = (result) => {
            backdrop.remove();
            resolve(result);
        };

        backdrop.querySelector('.cancel-btn').addEventListener('click', () => closeModal(false));
        backdrop.querySelector('.confirm-btn').addEventListener('click', () => closeModal(true));
        backdrop.addEventListener('click', (e) => {
            if (e.target === backdrop) {
                closeModal(false);
            }
        });
    });
}

function svg(path) {
    return `<svg viewBox="0 0 24 24" aria-hidden="true">${path}</svg>`;
}

function dashboardIcon() { return svg('<path d="M4 13h7V4H4v9Zm0 7h7v-5H4v5Zm9 0h7v-9h-7v9Zm0-16v5h7V4h-7Z"/>'); }
function integrationsIcon() { return svg('<path d="M7 7h4v2H7v6h4v2H5V7h2Zm6 0h6v10h-6v-2h4V9h-4V7Zm-5 4h8v2H8v-2Z"/>'); }
function repositoryIcon() { return svg('<path d="M5 4h10l4 4v12H5V4Zm9 1.5V9h3.5L14 5.5ZM7 12v2h10v-2H7Zm0 4v2h7v-2H7Z"/>'); }
function developersIcon() { return svg('<path d="M8 11a4 4 0 1 1 0-8 4 4 0 0 1 0 8Zm8.5 0a3.5 3.5 0 1 1 0-7 3.5 3.5 0 0 1 0 7ZM2 20a6 6 0 0 1 12 0H2Zm12.5 0a7.4 7.4 0 0 0-2.1-5.2A5 5 0 0 1 22 20h-7.5Z"/>'); }
function leaderboardIcon() { return svg('<path d="M4 13h4v7H4v-7Zm6-9h4v16h-4V4Zm6 5h4v11h-4V9Z"/>'); }
function insightIcon() { return svg('<path d="M12 2a7 7 0 0 0-4 12.7V18h8v-3.3A7 7 0 0 0 12 2Zm-3 18h6v2H9v-2Zm2-13h2v5h-2V7Zm0 6h2v2h-2v-2Z"/>'); }
function reportIcon() { return svg('<path d="M5 3h14v18H5V3Zm3 4v2h8V7H8Zm0 4v2h8v-2H8Zm0 4v2h5v-2H8Z"/>'); }
function settingsIcon() { return svg('<path d="M10.5 2h3l.4 2.2 1.9.8 1.8-1.3 2.1 2.1-1.3 1.8.8 1.9 2.2.4v3l-2.2.4-.8 1.9 1.3 1.8-2.1 2.1-1.8-1.3-1.9.8-.4 2.2h-3l-.4-2.2-1.9-.8-1.8 1.3-2.1-2.1 1.3-1.8-.8-1.9-2.2-.4v-3l2.2-.4.8-1.9-1.3-1.8 2.1-2.1L8.2 5l1.9-.8.4-2.2ZM12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8Z"/>'); }
function searchIcon() { return svg('<path d="M10 4a6 6 0 1 0 3.7 10.7l4.3 4.3 1.4-1.4-4.3-4.3A6 6 0 0 0 10 4Zm0 2a4 4 0 1 1 0 8 4 4 0 0 1 0-8Z"/>'); }
function refreshIcon() { return svg('<path d="M17.7 6.3A8 8 0 0 0 4.1 10H2a10 10 0 0 1 17.1-5.1L21 3v6h-6l2.7-2.7ZM6.3 17.7A8 8 0 0 0 19.9 14H22A10 10 0 0 1 4.9 19.1L3 21v-6h6l-2.7 2.7Z"/>'); }
function commitIcon() { return svg('<path d="M7 10.3A3 3 0 0 1 9.8 12h4.4a3 3 0 1 1 0 2H9.8A3 3 0 1 1 7 10.3Z"/>'); }
function pullRequestIcon() { return svg('<path d="M7 5a3 3 0 1 0 1 5.8v6.4a3 3 0 1 0 2 0V6H8.8A3 3 0 0 0 7 5Zm10 0a3 3 0 0 0-1 5.8V13a3 3 0 0 1-3 3h-1v2h1a5 5 0 0 0 5-5v-2.2A3 3 0 0 0 17 5Z"/>'); }
function reviewIcon() { return svg('<path d="M4 4h16v11H8l-4 4V4Zm4 4v2h8V8H8Zm0 4v2h5v-2H8Z"/>'); }
function taskIcon() { return svg('<path d="M4 5h16v14H4V5Zm4 4 2 2 4-4 1.4 1.4L10 13.8 6.6 10.4 8 9Zm0 6h8v2H8v-2Z"/>'); }
function deploymentIcon() { return svg('<path d="M12 2 4 6v6c0 5 3.4 8.7 8 10 4.6-1.3 8-5 8-10V6l-8-4Zm-1 13.5-3-3 1.4-1.4 1.6 1.6 3.6-3.6 1.4 1.4-5 5Z"/>'); }
function logoutIcon() { return svg('<path d="M16 17v-3H9v-4h7V7l5 5-5 5M14 2a2 2 0 0 1 2 2v2h-2V4H4v16h10v-2h2v2a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h10Z"/>'); }
function logoutAllIcon() { return svg('<path d="M4 6h16v6h2V4a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h7v-2H4V6Zm17 6h-6a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2Zm0 8h-6v-6h6v6ZM9 12l4-3v2h4v2h-4v2l-4-3Z"/>'); }
function developerModeIcon() { return svg('<path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm-8 12H4v-2h8v2zm8-4l-4 4V8l4 4z"/>'); }

const apiEndpoints = [
    {
        id: "get_dashboard_overview",
        name: "Dashboard Overview",
        method: "GET",
        path: "/api/engineering-agent/dashboard/overview",
        desc: "Retrieve summary metrics including total repositories, commits, pull requests, and average developer score.",
        params: []
    },
    {
        id: "get_dashboard_leaderboard",
        name: "Dashboard Leaderboard",
        method: "GET",
        path: "/api/engineering-agent/dashboard/leaderboard",
        desc: "Retrieve the developer leaderboard ranked by score.",
        params: []
    },
    {
        id: "get_repositories",
        name: "List Repositories",
        method: "GET",
        path: "/api/engineering-agent/repositories",
        desc: "List all source control repositories indexed in the workspace.",
        params: []
    },
    {
        id: "get_developers",
        name: "List Developers",
        method: "GET",
        path: "/api/engineering-agent/developers",
        desc: "List all developer contributor profiles.",
        params: []
    },
    {
        id: "get_integrations",
        name: "List Integrations",
        method: "GET",
        path: "/api/engineering-agent/integrations",
        desc: "List connected source control provider integrations.",
        params: []
    },
    {
        id: "post_recalculate_metrics",
        name: "Recalculate Metrics",
        method: "POST",
        path: "/api/engineering-agent/metrics/calculate",
        desc: "Trigger workspace-wide calculation of developer metrics.",
        params: []
    },
    {
        id: "post_sync_repository",
        name: "Sync Repository",
        method: "POST",
        path: "/api/engineering-agent/repositories/{repository_id}/sync",
        desc: "Queue a background sync job for a specific repository.",
        params: [
            { key: "repository_id", label: "Repository ID", placeholder: "1", type: "path" }
        ]
    },
    {
        id: "post_sync_integration",
        name: "Sync Integration",
        method: "POST",
        path: "/api/engineering-agent/integrations/{integration_id}/sync",
        desc: "Queue a background sync job for all repositories under an integration.",
        params: [
            { key: "integration_id", label: "Integration ID", placeholder: "1", type: "path" }
        ]
    },
    {
        id: "post_generate_insights",
        name: "Generate Insights",
        method: "POST",
        path: "/api/engineering-agent/insights/{developer_id}/generate",
        desc: "Prompt local AI to generate insights for a specific developer.",
        params: [
            { key: "developer_id", label: "Developer ID", placeholder: "1", type: "path" }
        ]
    }
];

function buildCurlCommand(ep, paramValues, apiKey) {
    let url = window.location.origin + ep.path;
    ep.params.forEach(p => {
        const val = paramValues[p.key] || p.placeholder;
        url = url.replace(`{${p.key}}`, val);
    });

    let cmd = `curl -X ${ep.method} "${url}" \\\n`;
    cmd += `  -H "Accept: application/json" \\\n`;
    cmd += `  -H "Authorization: Bearer ${apiKey || "YOUR_API_KEY"}"`;

    if (ep.method === "POST") {
        cmd += ` \\\n  -H "Content-Type: application/json"`;
    }

    return cmd;
}

function developerModePage() {
    return appShell(`
        <div class="developer-mode-grid">
            <div class="developer-mode-main">
                <!-- API Key Manager Card -->
                <section class="panel">
                    <div class="panel-heading">
                        <div>
                            <p class="eyebrow">Authentication</p>
                            <h2>Developer API Key</h2>
                        </div>
                    </div>
                    <div class="api-key-box" id="apiKeyBoxContainer">
                        <div class="loading-state">
                            ${spinnerSvg("1.5rem", "1.5rem")}
                            <p>Loading API key details...</p>
                        </div>
                    </div>
                </section>

                <!-- Request Tester Card -->
                <section class="panel">
                    <div class="panel-heading">
                        <div>
                            <p class="eyebrow">API Console</p>
                            <h2>Request Tester</h2>
                        </div>
                    </div>
                    
                    <!-- cURL Option Row -->
                    <div class="api-key-config-row">
                        <label for="apiKeyOptionInput">
                            API Key Option
                            <span>This value will be substituted into the cURL command below.</span>
                        </label>
                        <input type="text" id="apiKeyOptionInput" class="api-key-config-input" placeholder="YOUR_API_KEY">
                    </div>

                    <div class="tester-box" id="requestTesterContainer">
                        <!-- Loaded dynamically -->
                    </div>
                </section>
            </div>

            <!-- Right Sidebar: Endpoint Explorer -->
            <aside class="developer-mode-sidebar panel">
                <div class="panel-heading">
                    <div>
                        <p class="eyebrow">Explorer</p>
                        <h2>API Endpoints</h2>
                    </div>
                </div>
                <div class="endpoint-search-box">
                    <input type="search" class="form-control" placeholder="Filter endpoints..." id="endpointSearchInput">
                </div>
                <div class="endpoint-list" id="endpointListContainer">
                    <!-- Rendered dynamically -->
                </div>
            </aside>
        </div>
    `, "Developer Mode", "API Integration");
}

function spinnerSvg(width = "1rem", height = "1rem") {
    return `<svg class="animate-spin" viewBox="0 0 24 24" style="width:${width};height:${height};display:inline-block;vertical-align:middle;margin-right:0.25rem;"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>`;
}

window.addEventListener("popstate", render);
render();

