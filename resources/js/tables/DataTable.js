export function DataTable({
    columns,
    rows
}) {
    return `
        <table class="data-table">

            <thead>
                <tr>
                    ${columns
                        .map(
                            c => `<th>${c}</th>`
                        )
                        .join("")}
                </tr>
            </thead>

            <tbody>

                ${rows
                    .map(
                        row => `
                            <tr>
                                ${row
                                    .map(
                                        cell =>
                                            `<td>${cell}</td>`
                                    )
                                    .join("")}
                            </tr>
                        `
                    )
                    .join("")}

            </tbody>

        </table>
    `;
}