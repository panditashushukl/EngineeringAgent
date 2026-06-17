import Chart from "chart.js/auto";

export function LineChart(
    canvasId,
    labels,
    data
) {

    return new Chart(
        document.getElementById(
            canvasId
        ),
        {
            type: "line",

            data: {
                labels,

                datasets: [
                    {
                        label: "Activity",
                        data
                    }
                ]
            }
        }
    );
}