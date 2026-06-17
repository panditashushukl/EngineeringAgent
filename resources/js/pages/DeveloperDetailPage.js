import { AppLayout }
from "../layouts/AppLayout";

import { MetricCard }
from "../components/cards/MetricCard";

export function DeveloperDetailPage(
    developer
)
{
    return AppLayout({

        title:
            developer?.name ??
            "Developer",

        content: `

            <div class="developer-header">

                <img
                    src="${
                        developer?.avatar ??
                        ''
                    }"
                />

                <div>

                    <h2>
                        ${
                            developer?.name ??
                            ''
                        }
                    </h2>

                    <p>
                        Overall Score:
                        ${
                            developer?.score ??
                            0
                        }
                    </p>

                </div>

            </div>

            <div class="metrics-grid">

                ${MetricCard({
                    title: "Commits",
                    value:
                        developer?.commits ??
                        0
                })}

                ${MetricCard({
                    title: "PRs",
                    value:
                        developer?.prs ??
                        0
                })}

                ${MetricCard({
                    title: "Reviews",
                    value:
                        developer?.reviews ??
                        0
                })}

                ${MetricCard({
                    title: "Tasks",
                    value:
                        developer?.tasks ??
                        0
                })}

            </div>

            <div class="card">

                <h2>
                    AI Insight
                </h2>

                <p>
                    ${
                        developer?.insight ??
                        'No insights generated'
                    }
                </p>

            </div>

        `
    });
}