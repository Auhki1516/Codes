<?php
include('../config/constants.php'); 

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "inventory";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fuel Inventory</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; }
        table { width: 80%; border-collapse: collapse; margin: 20px auto; background-color: white; }
        th, td { border: 1px solid black; padding: 10px; text-align: center; }
        th { background-color: #004aad; color: white; }
        form { background: white; padding: 20px; width: 60%; margin: auto; }
        input, select, button { padding: 8px; margin: 5px; }
        button { background-color: #004aad; color: white; cursor: pointer; }
        button:hover { background-color: #003080; }
        .total-row { font-weight: bold; background-color: #f0f0f0; }
    </style>
</head>
<body>

    <form id="inventoryForm">
        <input type="date" id="date" required min="">

        <select id="pumpSelect" required>
            <option value="" disabled selected>Select Pump</option>
            <option value="Pump 1">Pump 1</option>
            <option value="Pump 2">Pump 2</option>
            <option value="Pump 3">Pump 3</option>
            <option value="Pump 4">Pump 4</option>
        </select>

        <select id="productSelect" multiple required>
            <option value="Diesel">Diesel</option>
            <option value="Premium">Premium</option>
            <option value="Unleaded">Unleaded</option>
        </select>

        <button type="button" onclick="addEntry()">Add to Table</button>
        <button type="button" id="saveButton">Save to Database</button>
    </form>

    <table id="inventoryTable">
        <thead>
            <tr>
                <th>Date</th>
                <th>Pump</th>
                <th>Product</th>
                <th>Meter Opening</th>
                <th>Meter Closing</th>
                <th>Electronic Opening</th>
                <th>Electronic Closing</th>
                <th>Liters Sold</th>
                <th>Amount (PHP)</th>
                <th>Lubs</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody></tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="7">Total</td>
                <td id="totalLiters">0</td>
                <td id="totalAmount">0</td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            let today = new Date().toISOString().split("T")[0];
            document.getElementById("date").setAttribute("min", today);
        });

        function addEntry() {
            let date = document.getElementById("date").value;
            let pump = document.getElementById("pumpSelect").value;
            let productOptions = document.getElementById("productSelect").selectedOptions;

            if (!date || !pump || productOptions.length === 0) {
                alert("Please fill in all fields!");
                return;
            }

            let tbody = document.querySelector("#inventoryTable tbody");

            Array.from(productOptions).forEach(productOption => {
                let product = productOption.value;

                let newRow = document.createElement("tr");
                newRow.innerHTML = `
                    <td>${date}</td>
                    <td>${pump}</td>
                    <td>${product}</td>
                    <td><input type="number" class="meter-opening" oninput="updateTotals()"></td>
                    <td><input type="number" class="meter-closing" oninput="updateTotals()"></td>
                    <td><input type="number" class="electronic-opening"></td>
                    <td><input type="number" class="electronic-closing"></td>
                    <td><input type="number" class="liters-sold" oninput="updateTotals()"></td>
                    <td><input type="number" class="amount-php" oninput="updateTotals()"></td>
                    <td><input type="number" class="lubs"></td>
                    <td><button onclick="removeEntry(this)">Remove</button></td>
                `;

                tbody.appendChild(newRow);
            });

            updateTotals();
        }

        function removeEntry(button) {
            let row = button.parentElement.parentElement;
            row.remove();
            updateTotals();
        }

        function updateTotals() {
            let totalLiters = 0;
            let totalAmount = 0;

            document.querySelectorAll(".liters-sold").forEach(input => {
                totalLiters += parseFloat(input.value) || 0;
            });

            document.querySelectorAll(".amount-php").forEach(input => {
                totalAmount += parseFloat(input.value) || 0;
            });

            document.getElementById("totalLiters").textContent = totalLiters.toFixed(2);
            document.getElementById("totalAmount").textContent = totalAmount.toFixed(2);
        }

        document.getElementById("saveButton").addEventListener("click", function () {
            let rows = document.querySelectorAll("#inventoryTable tbody tr");
            let data = [];

            rows.forEach(row => {
                let cells = row.querySelectorAll("td input, td");
                data.push({
                    date: cells[0].textContent,
                    pump: cells[1].textContent,
                    product: cells[2].textContent,
                    meter_opening: cells[3].value || 0,
                    meter_closing: cells[4].value || 0,
                    electronic_opening: cells[5].value || 0,
                    electronic_closing: cells[6].value || 0,
                    liters_sold: cells[7].value || 0,
                    amount_php: cells[8].value || 0,
                    lubs: cells[9].value || 0
                });
            });

            fetch("save_inventory.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "data=" + encodeURIComponent(JSON.stringify(data))
            })
            .then(response => response.text())
            .then(result => {
                alert(result);

                // Disable all inputs in the table after saving
                document.querySelectorAll("#inventoryTable input").forEach(input => {
                    input.disabled = true;
                });

                // Disable the "Save to Database" button
                document.getElementById("saveButton").disabled = true;
            })
            .catch(error => console.error("Error:", error));
        });
    </script>

</body>
</html>