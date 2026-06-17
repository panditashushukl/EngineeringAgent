import { AppLayout }
from "../layouts/AppLayout";

import { InsightCard }
from "../components/cards/InsightCard";

export function InsightsPage()
{
    return AppLayout({

        title: "AI Insights",

        content: `

            <div class="insights-grid">

                ${InsightCard({

                    summary:
                        "Team productivity increased 15% this month."

                })}

                ${InsightCard({

                    summary:
                        "Repository API has review bottlenecks."

                })}

            </div>

        `
    });
}