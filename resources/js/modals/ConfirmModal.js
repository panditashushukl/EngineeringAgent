import { Modal }
from "./Modal";

export function ConfirmModal({
    title,
    message
}) {

    return Modal({

        title,

        content: `
            <p>${message}</p>
        `,

        footer: `
            <button id="confirm-btn">
                Confirm
            </button>

            <button id="cancel-btn">
                Cancel
            </button>
        `
    });
}