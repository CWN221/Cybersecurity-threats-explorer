<?php
# Inside docker it uses set 'FLASK_HOST' in .env, on host machine 'localhost'
$flask_host = getenv('FLASK_HOST') ?: 'localhost';
$flask_port = getenv('FLASK_PORT') ?: '5000';
$json = @file_get_contents("http://$flask_host:$flask_port/threats");
$types = [];
sort($types);
$threats = [];

if ($json !== false) {
    $obj = json_decode($json, true);
    if (!isset($obj["threats"]) || !is_array($obj["threats"])) {
        echo "<div class='alert alert-danger'>Invalid response from threat service</div>";
    }

    if ($obj && isset($obj["threats"])) {
        $threats = $obj["threats"];

        foreach ($threats as &$threat) {
            $threat = [
                'id' => $threat['Id'] ?? null,
                'name' => $threat['Name'] ?? 'Unknown Threat',
                'type' => $threat['Type'] ?? 'Unknown',
                'severity' => $threat['Severity'] ?? 'Low',
                'description' => $threat['Description'] ?? '',
                'impact' => $threat['Impact'] ?? '',
                'mitigation' => $threat['Mitigation'] ?? '',
                'category' => $threat['Category'] ?? '',
                'attack_vector' => $threat['Attack_vector'] ?? '',
                'source' => $threat['Source'] ?? '',
                'reference_url' => $threat['Reference_URL'] ?? ''
            ];
            $types[] = $threat["type"];
        }
        $types = array_unique($types);
    }
} else {
    echo "<div class='alert alert-danger'>Threat service unavailable</div>";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Cybersecurity Threats</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <style>
        .card {
            border-radius: 12px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        }
    </style>
</head>

<body class="bg-dark text-white text-center">
    <div class="container mt-5">
        <!-- Dashboard header -->
        <div class="dashboard-header d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="mb-1">Cybersecurity Threats Explorer</h1>
                <p class="text-secondary mb-0">Monitor and analyze cybersecurity threats in real time</p>
            </div>
            <button id="toggleBtn" class="btn btn-secondary" onclick="toggleMode()">Toggle Dark Mode</button>
        </div>

        <!-- Statistics row -->
        <div class="row text-center mb-4">

            <div class="col-md-3">
                <div class="card bg-dark text-light p-3 border-danger">
                    <h5>Critical</h5>
                    <p id="criticalCount">0</p>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card bg-dark text-light p-3 border-warning">
                    <h5>High</h5>
                    <p id="highCount">0</p>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card bg-dark text-light p-3 border-info">
                    <h5>Medium</h5>
                    <p id="mediumCount">0</p>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card bg-dark text-light p-3 border-secondary">
                    <h5>Low</h5>
                    <p id="lowCount">0</p>
                </div>
            </div>

        </div>

        <!-- Search + Filter + Chart -->
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <!-- Search -->
                <input class="form-control mb-2" list="<?php $threats ?>" id="searchFunction" placeholder="Type to search...">

                <!-- Filter -->
                <div class="filter-box">
                    <p class="mb-2">Filter by Threat Type</p>
                    <select id="typeFilter" class="form-select">
                        <option selected value="">All</option>
                        <?php foreach ($types as $type): ?>
                            <option value="<?= strtolower($type) ?>"><?= htmlspecialchars($type) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Chart -->
            <div class="col-md-8">
                <div class="chart-box mb-4" style="max-width:400px;margin:auto;">
                    <canvas id="severityChart" class="mb-4"></canvas>
                </div>
            </div>
        </div>


        <!-- Cards -->
        <div class="row" id="cardsContainer">
            <?php
            if (empty($threats)) {
                echo "<div class='alert alert-danger'>Threat service unavailable</div>";
            } else {
                foreach ($threats as $threat) {
                    // Match color to use in Threat severity (uses bootstrap colors)
                    $color = match ($threat["severity"] ?? "Low") {
                        "Critical" => "danger",
                        "High" => "warning",
                        "Medium" => "info",
                        "Low" => "secondary",
                        default => "success"
                    };
                    echo "
                                <div class='col-md-4 card-wrapper'>
                                    <div class='card bg-dark text-light mb-3 p-3 border-$color' data-type='" . strtolower($threat["type"] ?? "") . "'>
                                        <h5 class='card-title'>" . htmlspecialchars($threat["name"] ?? "Unknown Threat") . "</h5>
                                        <p class='card-text'>Type: " . htmlspecialchars($threat["type"] ?? "Unknown") . "</p>
                                        <p class='card-text'>Severity: <span class='text-$color'>" . htmlspecialchars($threat["severity"] ?? "Low") . "</span></p>

                                        <!-- Button trigger modal -->
                                        <button type='button'
                                            class='btn btn-primary view-btn'
                                            data-bs-toggle='modal'
                                            data-bs-target='#threatModal'
                                            data-name='" . htmlspecialchars($threat["name"] ?? "") . "'
                                            data-description='" . htmlspecialchars($threat["description"] ?? "") . "'
                                            data-category='" . htmlspecialchars($threat["category"] ?? "") . "'
                                            data-impact='" . htmlspecialchars($threat["impact"] ?? "") . "'
                                            data-vector='" . htmlspecialchars($threat["attack_vector"] ?? "") . "'
                                            data-mitigation='" . htmlspecialchars($threat["mitigation"] ?? "") . "'
                                            data-source='" . htmlspecialchars($threat["source"] ?? "") . "'
                                            data-ref='" . htmlspecialchars($threat["reference_url"] ?? "") . "'
                                        >
                                        View Details
                                        </button>

                                    </div>
                                </div>
                            ";
                }
            }
            ?>
        </div>
    </div>

    <!-- Modal -->
    <div class='modal fade' id='threatModal' tabindex='-1' aria-labelledby='threatModalLabel' aria-hidden='true'>
    <div class='modal-dialog modal-lg modal-dialog-centered'>
        <div class='modal-content bg-light text-dark'>
        <div class='modal-header'>
            <h5 class="modal-title" id="threatModalLabel"></h5>
            <button type='button' class='btn-close btn-close-dark' data-bs-dismiss='modal' aria-label='Close'></button>
        </div>
        <div class='modal-body'>
            <!-- Description -->
            <div class="mb-3">
            <h6>Description</h6>
            <p id="modalDescription" class="text-dark"></p>
            </div>

            <!-- Key Details -->
            <div class="row row-cols-1 row-cols-md-2 g-3">
            <div class="col">
                <div class="p-2 border rounded bg-secondary bg-opacity-10">
                <strong>Category:</strong> <span id="modalCategory"></span>
                </div>
            </div>
            <div class="col">
                <div class="p-2 border rounded bg-secondary bg-opacity-10">
                <strong>Impact:</strong> <span id="modalImpact"></span>
                </div>
            </div>
            <div class="col">
                <div class="p-2 border rounded bg-secondary bg-opacity-10">
                <strong>Attack Vector:</strong> <span id="modalVector"></span>
                </div>
            </div>
            <div class="col">
                <div class="p-2 border rounded bg-secondary bg-opacity-10">
                <strong>Mitigation:</strong> <span id="modalMitigation"></span>
                </div>
            </div>
            <div class="col">
                <div class="p-2 border rounded bg-secondary bg-opacity-10">
                <strong>Source:</strong> <span id="modalSource"></span>
                </div>
            </div>
            <div class="col">
                <div class="p-2 border rounded bg-secondary bg-opacity-10">
                <strong>Reference:</strong> 
                <a id="modalRef" href="#" target="_blank" class="text-info text-decoration-underline">View</a>
                </div>
            </div>
            </div>
        </div>
        <div class='modal-footer'>
            <button type='button' class='btn btn-outline-dark' data-bs-dismiss='modal'>Close</button>
        </div>
        </div>
    </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <script>
        // Dark mode toggle
        function toggleMode() {
            var toggle = document.getElementById("toggleBtn");
            document.body.classList.toggle("bg-dark");
            document.body.classList.toggle("text-white");

            document.querySelectorAll(".card").forEach(card => {
                card.classList.toggle("bg-dark");
                card.classList.toggle("text-light");
            })
        }


        // Search function
        const searchInput = document.getElementById("searchFunction");
        searchInput.addEventListener("input", (e) => {
            const term = e.target.value.toLowerCase();

            document.querySelectorAll("#cardsContainer .card-wrapper").forEach(wrapper => {
                const text = wrapper.textContent.toLowerCase();
                wrapper.classList.toggle("d-none", !text.includes(term));
            });
        });


        // Filter function
        const filterType = document.getElementById("typeFilter");
        filterType.addEventListener("change", () => {
            const selected = filterType.value.toLowerCase();
            // show all the cards with the selected value
            document.querySelectorAll("#cardsContainer .card").forEach(card => {
                if (!selected || card.dataset.type === selected) {
                    card.parentElement.classList.remove("d-none");
                } else {
                    card.parentElement.classList.add("d-none");
                }
            })
        })


        // Chart
        const ctx = document.getElementById('severityChart').getContext('2d');
        const severityCounts = {
            "Critical": 0,
            "High": 0,
            "Medium": 0,
            "Low": 0
        };

        document.querySelectorAll("#cardsContainer .card").forEach(card => {
            const sev = card.querySelector("span").textContent.trim();
            if (severityCounts[sev] !== undefined) severityCounts[sev]++;
        });

        // Statistics row
        document.getElementById("criticalCount").textContent = severityCounts["Critical"];
        document.getElementById("highCount").textContent = severityCounts["High"];
        document.getElementById("mediumCount").textContent = severityCounts["Medium"];
        document.getElementById("lowCount").textContent = severityCounts["Low"];

        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: Object.keys(severityCounts),
                datasets: [{
                    data: Object.values(severityCounts),
                    backgroundColor: ['#dc3545', '#fd7e14', '#0dcaf0', '#6c757d']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: "white"
                        }
                    }
                },
                animation: false
            }
        });


        // Modal function
        document.querySelectorAll('.view-btn').forEach(btn => {
            btn.addEventListener('click', e => {
                const b = e.currentTarget;
                document.getElementById("threatModalLabel").textContent = b.dataset.name;
                document.getElementById("modalDescription").textContent = b.dataset.description;
                document.getElementById("modalCategory").textContent = b.dataset.category;
                document.getElementById("modalImpact").textContent = b.dataset.impact;
                document.getElementById("modalVector").textContent = b.dataset.vector;
                document.getElementById("modalMitigation").textContent = b.dataset.mitigation;
                document.getElementById("modalSource").textContent = b.dataset.source;
                document.getElementById("modalRef").href = b.dataset.ref;
                document.getElementById("modalRef").textContent = b.dataset.ref;
            });
        });
    </script>
</body>

</html>