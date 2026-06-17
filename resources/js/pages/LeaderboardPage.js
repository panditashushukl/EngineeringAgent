import { AppLayout }
from "../layouts/AppLayout";

export function LeaderboardPage()
{
    return AppLayout({

        title: "Leaderboard",

        content: `

            <div class="card">

                <table>

                    <thead>

                        <tr>

                            <th>Rank</th>

                            <th>Name</th>

                            <th>Score</th>

                        </tr>

                    </thead>

                    <tbody>

                        <tr>

                            <td>1</td>

                            <td>Developer A</td>

                            <td>98</td>

                        </tr>

                    </tbody>

                </table>

            </div>

        `
    });
}