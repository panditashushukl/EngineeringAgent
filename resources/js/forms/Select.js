export function Select({
    name,
    options = []
}) {

    return `
        <select
            name="${name}"
            class="select"
        >

            ${options
                .map(
                    option =>
                        `<option value="${option.value}">
                            ${option.label}
                        </option>`
                )
                .join("")}

        </select>
    `;
}