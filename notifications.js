document.addEventListener("DOMContentLoaded", function() {
  document.getElementById('submitBtn').addEventListener('click', submitResponse);
  fetchNotifications();
});

async function fetchNotifications() {
  try {
    const response = await fetch("notifications.php");
    if (!response.ok) {
      throw new Error("Network response was not ok");
    }
    const data = await response.json();
    console.log("Fetched notifications:", data);

    const container = document.getElementById("notifications-container");
    container.innerHTML = "";

    if (data.status === "success" && data.data.notifications.length > 0) {
      for (const buyer of data.data.notifications) {
        for (const product of buyer.products) {
          const card = createNotificationCard(buyer.buyer_id, product);
          container.appendChild(card);
        }
      }
    } else {
      container.innerHTML = "<p>No notifications.</p>";
    }
  } catch (error) {
    console.error("Error fetching notifications:", error);
    document.getElementById("notifications-container").innerHTML = "<p>Error loading notifications.</p>";
  }
}

async function checkVendorResponse(productId) {
  try {
    const response = await fetch(`check_response.php?productId=${productId}`);
    if (!response.ok) {
      throw new Error("Network response was not ok");
    }
    const data = await response.json();
    return data.hasSubmitted;
  } catch (error) {
    console.error("Error checking vendor response:", error);
    return false;
  }
}

function createNotificationCard(buyerId, product) {
  const card = document.createElement("div");
  card.className = "card p-3 col-md-6";
  card.setAttribute("data-product-id", product.product_id);
  card.setAttribute("data-buyer-id", buyerId);
  card.innerHTML = `
    <h3>Product ID: ${product.product_id}</h3>
    <p>Product Name: ${product.product_name}</p>
    <button class="btn btn-primary" onclick='openResponseModal("${buyerId}", "${product.product_id}")'>Respond</button>
  `;
  return card;
}

async function openResponseModal(buyerId, productId) {
  try {
    console.log("Opening modal for buyer ID:", buyerId, "and product ID:", productId);
    const response = await fetch(`notifications.php?buyer_id=${encodeURIComponent(buyerId)}&product_id=${encodeURIComponent(productId)}`);
    const data = await response.json();
    console.log("Fetched data:", data);
    populateModalWithData(data, buyerId, productId);

    const modal = new bootstrap.Modal(document.getElementById("responseModal"));
    modal.show();

    document.getElementById("buyerId").value = buyerId;
    document.getElementById("productId").value = productId;
    console.log(`Set buyerId: ${buyerId}, productId: ${productId}`);
  } catch (error) {
    console.error("Error in openResponseModal:", error);
  }
}

function populateModalWithData(data, buyerId, productId) {
  console.log("Data received in populateModalWithData:", data);

  const requirementList = document.getElementById("requirement-list");
  requirementList.innerHTML = "";

  if (data.status === "success" && typeof data.data === "object") {
    const buyerData = data.data.notifications.find((buyer) => buyer.buyer_id === buyerId);

    if (buyerData) {
      appendRequirements(buyerData, requirementList, productId);
      appendAttributes(buyerData, requirementList, productId, "technical_attributes", "(Technical)");
      appendAttributes(buyerData, requirementList, productId, "non_technical_attributes", "(Non-Technical)");
    }

    requirementList.innerHTML += `
      <div class="mb-2">
        <strong>Final Bid Amount</strong>:
        <input type="text" class="form-control" placeholder="Enter bid amount" name="final_bid_amount" required>
      </div>
    `;
    document.getElementById("productId").value = productId;
  } else {
    console.error("Invalid data format:", data);
  }
}

function appendRequirements(buyerData, container, productId) {
  console.log("Appending requirements for product ID:", productId);

  if (buyerData.attributes && buyerData.attributes[productId] && Array.isArray(buyerData.attributes[productId])) {
    for (const req of buyerData.attributes[productId]) {
      const requirementItem = document.createElement("div");
      requirementItem.className = "mb-2";
      requirementItem.innerHTML = `
        <strong>${req}</strong>:
        <input type="text" class="form-control requirement-value" placeholder="Enter value" name="${req}">
      `;
      container.appendChild(requirementItem);
    }
  } else {
    container.innerHTML += "<p>No requirements found.</p>";
  }
}

function appendAttributes(buyerData, container, productId, attributeType, label) {
  console.log(`Appending ${label} attributes for product ID:`, productId);

  if (buyerData[attributeType] && Array.isArray(buyerData[attributeType][productId])) {
    for (const attr of buyerData[attributeType][productId]) {
      const attributeItem = document.createElement("div");
      attributeItem.className = "mb-2";
      attributeItem.innerHTML = `
        <strong>${attr.attribute_name} ${label}</strong>:
        <input type="text" class="form-control attribute-value" placeholder="Enter value" name="${attr.attribute_name}">
      `;
      container.appendChild(attributeItem);
    }
  } else {
    container.innerHTML += `<p>No ${label.toLowerCase().replace(/[\(\)]/g, "")} attributes found.</p>`;
  }
}

function collectFormValues(selector) {
  const inputs = document.querySelectorAll(selector);
  const values = {};
  inputs.forEach(input => {
    values[input.name] = input.value;
  });
  return values;
}

async function submitResponse() {
  const productIdElement = document.getElementById("productId");
  const buyerIdElement = document.getElementById("buyerId");

  if (!productIdElement || !buyerIdElement) {
    console.error("Error: Modal input elements not found");
    return;
  }

  const productId = productIdElement.value;
  const buyerId = buyerIdElement.value;
  const attributeValues = collectFormValues(".attribute-value");
  const finalBidAmount = document.querySelector('input[name="final_bid_amount"]').value;

  if (!finalBidAmount) {
    alert("Final Bid Amount cannot be null");
    return;
  }

  console.log("Submitting response for product ID:", productId, "and buyer ID:", buyerId);
  console.log("Collected data:", {
    productId,
    attributeValues,
    finalBidAmount,
    buyer_id: buyerId,
  });

  try {
    const response = await fetch("./submit_response.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        productId,
        attributeValues,
        final_bid_amount: finalBidAmount,
        buyer_id: buyerId,
      }),
    });
    const responseText = await response.text();
    console.log("Raw response:", responseText);

    try {
      const data = JSON.parse(responseText);

      if (data.status === "success") {
        alert("Response submitted successfully!");
        const modal = bootstrap.Modal.getInstance(document.getElementById("responseModal"));
        modal.hide();
      } else {
        alert("Failed to submit response: " + data.message);
      }
    } catch (e) {
      console.error("Error parsing JSON response:", e);
      console.log("Response text was:", responseText);
      alert("An error occurred while processing your request.");
    }
  } catch (error) {
    console.error("Error submitting response:", error);
    const entriesContainer = document.getElementById("entries-container");
    if (entriesContainer) {
      entriesContainer.innerHTML = '<p class="no-entries">Error submitting response.</p>';
    }
  }
}
