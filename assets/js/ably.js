const ably = new Ably.Realtime(
  "Pv6ihg.dTVV5g:WvuHeDy0GRFUyeczjP7yn5iVbNygMVBhq0p20dr1jho"
);
const channel = ably.channels.get("esp32");

// Object to store plant cards
const plantCards = {};

// Function to create or update a plant card
function updatePlantCard(plantID, plantName, moisturePercentage, lastWatered) {
  let plantCard = plantCards[plantID];

  // If the plant card doesn't exist, create it
  if (!plantCard) {
    const container = document.getElementById("plantCardsContainer");

    // Create a new plant card
    const plantCardHTML = `
        <div id="plantCard${plantID}" class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
          <div class="flex items-center justify-between mb-2">
            <input type="checkbox" class="rounded text-green-600" disabled>
            <span id="status${plantID}" class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm">
              Healthy
            </span>
          </div>
          <h3 class="text-lg font-medium text-gray-900 mb-4">${plantName}</h3>
          <div class="flex items-center gap-3 mb-2">
            <div class="flex-grow bg-gray-200 rounded-full h-2">
              <div id="moistureBar${plantID}" class="bg-green-500 h-2 rounded-full" style="width: ${moisturePercentage}%;"></div>
            </div>
            <span id="moisturePercent${plantID}" class="text-sm text-gray-600 min-w-[40px]">${moisturePercentage}%</span>
          </div>
          <p id="lastWatered${plantID}" class="text-sm text-gray-600">Last watered: ${lastWatered}</p>
        </div>
      `;

    // Add the plant card to the container
    container.insertAdjacentHTML("beforeend", plantCardHTML);

    // Store the plant card elements in the object
    plantCard = {
      status: document.getElementById(`status${plantID}`),
      moistureBar: document.getElementById(`moistureBar${plantID}`),
      moisturePercent: document.getElementById(`moisturePercent${plantID}`),
      lastWatered: document.getElementById(`lastWatered${plantID}`),
    };
    plantCards[plantID] = plantCard;
  }

  // Update the progress bar and moisture percentage
  plantCard.moistureBar.style.width = moisturePercentage + "%";
  plantCard.moisturePercent.textContent = moisturePercentage + "%";

  // Update the status badge based on moisture level
  if (moisturePercentage < 30) {
    plantCard.status.textContent = "Dry";
    plantCard.status.className =
      "px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm";
  } else if (moisturePercentage >= 30 && moisturePercentage <= 70) {
    plantCard.status.textContent = "Healthy";
    plantCard.status.className =
      "px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm";
  } else {
    plantCard.status.textContent = "Overwatered";
    plantCard.status.className =
      "px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-sm";
  }

  // Update the last watered time
  plantCard.lastWatered.textContent = `Last watered: ${lastWatered}`;
}

// Subscribe to the channel
channel.subscribe(function (message) {
  console.log("📥 Received Message from ESP32:", message);

  try {
    if (message.name === "moisture") {
      const data = message.data; // ESP32 already sends JSON, no need to parse
      const plantID = data.plantID;
      const moisturePercentage = data.moisture;
      const lastWatered = data.lastWatered;

      updatePlantCard(plantID, "Basil", moisturePercentage, lastWatered);
    }
  } catch (error) {
    console.error("❌ Error processing message:", error);
  }
});
