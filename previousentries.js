document.addEventListener("DOMContentLoaded", function() {
    fetchPreviousEntries();
});

async function fetchPreviousEntries() {
    try {
        const response = await fetch("previousentries.php");
        if (!response.ok) {
            throw new Error("Network response was not ok");
        }
        const data = await response.json();
        console.log("Fetched previous entries:", data);

        const container = document.getElementById("entries-container");
        container.innerHTML = "";

        if (data.status === "success" && data.data.previous_entries.length > 0) {
            const groupedEntries = groupEntriesByProductId(data.data.previous_entries);
            groupedEntries.forEach((entryGroup) => {
                const card = createEntryCard(entryGroup);
                container.appendChild(card);
            });
        } else {
            container.innerHTML = "<p class='no-entries'>No previous entries.</p>";
        }
    } catch (error) {
        console.error("Error fetching previous entries:", error);
        document.getElementById("entries-container").innerHTML = "<p class='no-entries'>Error loading entries.</p>";
    }
}

function groupEntriesByProductId(entries) {
    const groupedEntries = {};
    entries.forEach(entry => {
        if (!groupedEntries[entry.product_id]) {
            groupedEntries[entry.product_id] = [];
        }
        groupedEntries[entry.product_id].push(entry);
    });
    return Object.values(groupedEntries);
}

function createEntryCard(entryGroup) {
    const card = document.createElement("div");
    card.className = "card p-3 col-md-12 mb-3";
    card.innerHTML = `
        <h3>Product ID: ${entryGroup[0].product_id}</h3>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Attribute Name</th>
                    <th>Attribute Value</th>
                </tr>
            </thead>
            <tbody>
                ${entryGroup.map(entry => `
                <tr>
                    <td>${entry.attribute_name}</td>
                    <td>${entry.attribute_value}</td>
                </tr>`).join('')}
            </tbody>
        </table>
    `;
    return card;
}
