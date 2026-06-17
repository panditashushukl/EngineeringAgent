export function AppLayout({
    title,
    content
}) {

    return `
        <div class="app-layout">

            <aside class="sidebar">

                <div class="sidebar-logo">
                    Engineering Agent
                </div>

                <nav>

                    <a href="/dashboard">
                        Dashboard
                    </a>

                    <a href="/integrations">
                        Integrations
                    </a>

                    <a href="/repositories">
                        Repositories
                    </a>

                    <a href="/developers">
                        Developers
                    </a>

                    <a href="/leaderboard">
                        Leaderboard
                    </a>

                    <a href="/insights">
                        AI Insights
                    </a>

                    <a href="/reports">
                        Reports
                    </a>

                    <a href="/settings">
                        Settings
                    </a>

                </nav>

            </aside>

            <main class="main-content">

                <header class="topbar">

                    <div>
                        <h1>${title}</h1>
                    </div>

                    <div>
                        <input
                            placeholder="Search..."
                        />
                    </div>

                </header>

                <section class="page-content">

                    ${content}

                </section>

            </main>

        </div>
    `;
}