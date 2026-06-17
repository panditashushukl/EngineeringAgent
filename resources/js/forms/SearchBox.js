import { Input }
from "./Input";

export function SearchBox(
    placeholder = "Search..."
) {

    return `
        <div class="search-box">

            ${Input({

                name: "search",

                placeholder
            })}

        </div>
    `;
}