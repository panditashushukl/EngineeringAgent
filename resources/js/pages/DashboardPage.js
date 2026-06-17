import { AppLayout }
from "../layouts/AppLayout";

import { MetricCard }
from "../components/cards/MetricCard";

export async function DashboardPage()
{
    return AppLayout({

        title: "Dashboard",

        content: `

            <div class="metrics-grid">

                ${MetricCard({
                    title: "Repositories",
                    value: 42
                })}

                ${MetricCard({
                    title: "Developers",
                    value: 18
                })}

                ${MetricCard({
                    title: "Commits",
                    value: 1264
                })}

                ${MetricCard({
                    title: "Average Score",
                    value: 89
                })}

            </div>

            <div class="dashboard-charts">

                <canvas
                    id="activity-chart">
                </canvas>

            </div>

        `
    });
}