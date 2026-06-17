import Chart from "chart.js/auto";

export function BarChart(
    canvasId,
    labels,
    data
) {

    return new Chart(
        document.getElementById(
            canvasId
        ),
        {
            type: "bar",

            data: {
                labels,

                datasets: [
                    {
                        label: "Count",
                        data
                    }
                ]
            }
        }
    );
}