import { AppLayout }
from "../layouts/AppLayout";

export function IntegrationsPage()
{
    return AppLayout({

        title: "Integrations",

        content: `

            <div class="card">

                <h2>
                    GitLab
                </h2>

                <p>
                    Connected
                </p>

                <button id="sync-btn">
                    Sync Now
                </button>

            </div>

            <div class="card">

                <h2>
                    GitHub
                </h2>

                <p>
                    Coming Soon
                </p>

            </div>

        `
    });
}