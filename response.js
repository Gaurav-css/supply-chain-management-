document.addEventListener("DOMContentLoaded", function() {
    fetchResponses();
});

async function fetchResponses() {
    try {
        const response = await fetch("fetch_responses.php");
        if (!response.ok) {
            throw new Error("Network response was not ok");
        }
        const data = await response.json();
        console.log("Fetched responses:", data);

        const container = document.getElementById("responses-container");
        if (!container) {
            console.error("Element with ID 'responses-container' not found.");
            return;
        }
        container.innerHTML = "";

        if (data.status === "success" && data.data.responses.length > 0) {
            const table = createResponseTable(data.data.responses);
            container.appendChild(table);
        } else {
            container.innerHTML = "<p>No responses found.</p>";
        }
    } catch (error) {
        console.error("Error fetching responses:", error);
        const container = document.getElementById("responses-container");
        if (container) {
            container.innerHTML = "<p>Error loading responses.</p>";
        }
    }
}

function createResponseTable(responses) {
    const groupedResponses = groupResponsesByVendorAndProduct(responses);
    const table = document.createElement("table");
    table.className = "table table-striped";

    const thead = document.createElement("thead");
    const headerRow = document.createElement("tr");

    
    const vendorHeader = document.createElement("th");
    vendorHeader.textContent = "Vendor";
    headerRow.appendChild(vendorHeader);

    
    const attributeNames = Array.from(new Set(responses.map(response => response.attribute_name)));
    attributeNames.forEach(attribute => {
        const th = document.createElement("th");
        th.textContent = attribute;
        headerRow.appendChild(th);
    });

    thead.appendChild(headerRow);
    table.appendChild(thead);

    const tbody = document.createElement("tbody");

    
    for (const vendor in groupedResponses) {
        groupedResponses[vendor].forEach(product => {
            const row = document.createElement("tr");

            
            const vendorCell = document.createElement("td");
            vendorCell.textContent = vendor;
            row.appendChild(vendorCell);

           
            attributeNames.forEach(attribute => {
                const td = document.createElement("td");
                td.textContent = product.attributes[attribute] || "";
                row.appendChild(td);
            });

            tbody.appendChild(row);
        });
    }

    table.appendChild(tbody);
    return table;
}

function groupResponsesByVendorAndProduct(responses) {
    const grouped = {};
    responses.forEach(response => {
        const vendorName = "Vendor";  
        if (!grouped[vendorName]) {
            grouped[vendorName] = [];
        }
        const product = grouped[vendorName].find(product => product.product_id === response.product_id);
        if (product) {
            product.attributes[response.attribute_name] = response.attribute_value;
        } else {
            grouped[vendorName].push({
                product_id: response.product_id,
                attributes: {
                    [response.attribute_name]: response.attribute_value
                }
            });
        }
    });
    return grouped;
}
