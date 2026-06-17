import { DataTable }
from "./DataTable";

export function RepositoryTable(
    repositories
) {

    return DataTable({

        columns: [

            "Repository",
            "Commits",
            "PRs",
            "Tasks",
            "Deployments"
        ],

        rows: repositories.map(
            repository => [

                repository.name,

                repository.commits,

                repository.pull_requests,

                repository.tasks,

                repository.deployments
            ]
        )
    });
}