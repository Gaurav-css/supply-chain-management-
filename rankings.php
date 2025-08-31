<?php
$conn = new mysqli("localhost", "root", "", "user_management");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

function fetchTechnicalAttributes($conn) {
    $sql = "SELECT * FROM technical_attributes";
    $result = $conn->query($sql);
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[$row['attribute_name']] = $row['attribute_value'];
    }
    return $data;
}

function fetchNonTechnicalAttributes($conn) {
    $sql = "SELECT * FROM non_technical_attributes";
    $result = $conn->query($sql);
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    return $data;
}

function fetchVendorResponses($conn) {
    $sql = "SELECT * FROM vendor_attribute_responses";
    $result = $conn->query($sql);
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    return $data;
}

function fetchBidAmounts($conn) {
    $sql = "SELECT vendor_id, attribute_value AS bid_amount FROM vendor_attribute_responses WHERE attribute_name = 'final_bid_amount'";
    $result = $conn->query($sql);
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[$row['vendor_id']] = $row['bid_amount'];
    }
    return $data;
}

function normalizeMatrix($matrix) {
    $normMatrix = [];
    foreach ($matrix as $column => $values) {
        $sumSquares = 0;
        foreach ($values as $value) {
            if (is_numeric($value)) {
                $sumSquares += pow($value, 2);
            }
        }
        $norm = sqrt($sumSquares);
        if ($norm == 0) {
            $normMatrix[$column] = array_fill(0, count($values), 0.00000);
        } else {
            foreach ($values as $key => $value) {
                $normMatrix[$column][$key] = is_numeric($value) ? number_format($value / $norm, 5) : 0.00000;
            }
        }
    }
    return $normMatrix;
}

function calculateTOPSIS($matrix, $weights, $attributeValues) {
    $normMatrix = normalizeMatrix($matrix);

    $weightedMatrix = [];
    foreach ($normMatrix as $column => $values) {
        foreach ($values as $key => $value) {
            $weightedMatrix[$column][$key] = $value * $weights[$column];
        }
    }

    $idealBest = [];
    $idealWorst = [];
    foreach ($weightedMatrix as $column => $values) {
        if ($attributeValues[$column] === 'High') {
            $idealBest[$column] = max($values);
            $idealWorst[$column] = min($values);
        } else {
            $idealBest[$column] = min($values);
            $idealWorst[$column] = max($values);
        }
    }

    $separationBest = [];
    $separationWorst = [];
    foreach ($weightedMatrix as $column => $values) {
        foreach ($values as $i => $value) {
            if (!isset($separationBest[$i])) $separationBest[$i] = 0;
            if (!isset($separationWorst[$i])) $separationWorst[$i] = 0;
            $separationBest[$i] += pow($value - $idealBest[$column], 2);
            $separationWorst[$i] += pow($value - $idealWorst[$column], 2);
        }
    }
    $separationBest = array_map('sqrt', $separationBest);
    $separationWorst = array_map('sqrt', $separationWorst);

    $scores = [];
    foreach ($separationBest as $i => $best) {
        if ($best == 0 && $separationWorst[$i] == 0) {
            $scores[$i] = 0.00000; 
        } else {
            $scores[$i] = $separationWorst[$i] / ($separationWorst[$i] + $best);
        }
    }

    arsort($scores);
    return $scores;
}

function combineRankings($technicalScores, $topsisScores, $technicalWeight, $nonTechnicalWeight) {
    $combinedScores = [];
    foreach ($technicalScores as $vendor => $techScore) {
        if (isset($topsisScores[$vendor])) {
            $combinedScores[$vendor] = ($techScore * $technicalWeight) + ($topsisScores[$vendor] * $nonTechnicalWeight);
        } else {
            $combinedScores[$vendor] = $techScore * $technicalWeight; 
        }
    }
    arsort($combinedScores);
    return $combinedScores;
}

function rankVendors($conn) {
    $technicalAttrs = fetchTechnicalAttributes($conn);
    $nonTechnicalAttrs = fetchNonTechnicalAttributes($conn);
    $vendorResponses = fetchVendorResponses($conn);
    $bidAmounts = fetchBidAmounts($conn);

    $technicalScores = [];
    foreach ($vendorResponses as $response) {
        foreach ($technicalAttrs as $attr => $attrValue) {
            if ($attr === $response['attribute_name']) {
                $technicalScores[$response['vendor_id']] = ($technicalScores[$response['vendor_id']] ?? 0)
                    + ($response['attribute_value'] == $attrValue ? 1 : 0);
            }
        }
    }

    $matrix = [];
    $weights = [];
    $attributeValues = [];
    foreach ($nonTechnicalAttrs as $attr) {
        if ($attr['attribute_name'] !== 'final_bid_amount') { 
            $matrix[$attr['attribute_name']] = [];
            foreach ($vendorResponses as $response) {
                if ($response['attribute_name'] === $attr['attribute_name']) {
                    $matrix[$attr['attribute_name']][] = (float) $response['attribute_value'];
                }
            }
            $weights[$attr['attribute_name']] = $attr['attribute_weight'] ?? 1; 
            $attributeValues[$attr['attribute_name']] = $attr['attribute_value'] ?? 'High'; 
        }
    }

    $topsisScores = calculateTOPSIS($matrix, $weights, $attributeValues);

    $finalRankings = combineRankings($technicalScores, $topsisScores, 0.5, 0.5); 
    
    $top2Vendors = array_slice($finalRankings, 0, 2, true);

    $lowestBidVendor = null;
    $lowestBidAmount = PHP_INT_MAX;
    foreach ($top2Vendors as $vendor => $score) {
        if (isset($bidAmounts[$vendor]) && $bidAmounts[$vendor] < $lowestBidAmount) {
            $lowestBidVendor = $vendor;
            $lowestBidAmount = $bidAmounts[$vendor];
        }
    }

    return ['rankings' => $finalRankings, 'lowestBidVendor' => $lowestBidVendor];
}

$result = rankVendors($conn);

echo "<h2>Vendor Rankings</h2>";
echo "<table border='1'><tr><th>Rank</th><th>Vendor ID</th><th>Score</th></tr>";
$rank = 1;
foreach ($result['rankings'] as $vendor => $score) {
    echo "<tr><td>$rank</td><td>Vendor $vendor</td><td>" . number_format($score, 5) . "</td></tr>";
    $rank++;
}
echo "</table>";

if ($result['lowestBidVendor'] !== null) {
    echo "<h2>Vendor with the Lowest Bid among Top 2</h2>";
    echo "Vendor ID: " . $result['lowestBidVendor'];
} else {
    echo "<h2>No valid bids found for the top 2 vendors.</h2>";
}

$conn->close();
?>
