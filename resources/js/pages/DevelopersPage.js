import { AppLayout }
from "../layouts/AppLayout";

import { DeveloperTable }
from "../components/tables/DeveloperTable";

export function DevelopersPage()
{
    return AppLayout({

        title: "Developers",

        content: `

            <div class="card">

                ${DeveloperTable([])}

            </div>

        `
    });
}