export function Modal({
    title,
    content,
    footer = ""
}) {

    return `
        <div class="modal-overlay">

            <div class="modal">

                <div class="modal-header">

                    <h2>${title}</h2>

                </div>

                <div class="modal-body">

                    ${content}

                </div>

                <div class="modal-footer">

                    ${footer}

                </div>

            </div>

        </div>
    `;
}