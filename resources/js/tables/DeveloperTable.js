import { DataTable }
from "./DataTable";

export function DeveloperTable(
    developers
) {
    return DataTable({

        columns: [

            "Name",
            "Score",
            "Commits",
            "PRs",
            "Reviews"
        ],

        rows: developers.map(
            developer => [

                developer.name,

                developer.score,

                developer.commits,

                developer.prs,

                developer.reviews
            ]
        )
    });
}