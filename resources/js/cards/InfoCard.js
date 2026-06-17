export function InfoCard({
    title,
    content
}) {
    return `
        <div class="info-card">
            <h3>${title}</h3>

            <div>
                ${content}
            </div>
        </div>
    `;
}