import Chart from "chart.js/auto";

export function DoughnutChart(
    canvasId,
    labels,
    data
) {

    return new Chart(
        document.getElementById(
            canvasId
        ),
        {
            type: "doughnut",

            data: {
                labels,

                datasets: [
                    {
                        data
                    }
                ]
            }
        }
    );
}