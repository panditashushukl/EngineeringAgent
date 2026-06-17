import { AppLayout }
from "../layouts/AppLayout";

import { RepositoryTable }
from "../components/tables/RepositoryTable";

export function RepositoriesPage()
{
    return AppLayout({

        title: "Repositories",

        content: `

            <div class="card">

                ${RepositoryTable([])}

            </div>

        `
    });
}