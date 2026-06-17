export function Input({
    name,
    value = "",
    placeholder = ""
}) {

    return `
        <input
            type="text"
            name="${name}"
            value="${value}"
            placeholder="${placeholder}"
            class="input"
        />
    `;
}