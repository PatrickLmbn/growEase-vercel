// Global variables
let channel;
const usedPins = new Set(); // Track used moisture sensor pins
const plantDatabase = new Map(); // Pin -> plant data mapping

// Request available pins when the page loads
function requestAvailablePins() {
  const message = {
    name: "pump_control",
    data: {
      command: "get_pins",
    },
  };

  channel.publish("message", message, (err) => {
    if (err) {
      console.error("Failed to request available pins:", err);
      // Show error in select element
      const sensorPinSelect = document.getElementById("sensorPin");
      sensorPinSelect.innerHTML =
        '<option value="">Error loading pins</option>';
    } else {
      console.log("📌 Available pins request sent successfully");
    }
  });
}

// Function to restart the device
function restartDevice() {
  Swal.fire({
    title: "Restart Device",
    text: "Are you sure you want to restart the device?",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#3085d6",
    cancelButtonColor: "#d33",
    confirmButtonText: "Yes, restart it!",
  }).then((result) => {
    if (result.isConfirmed) {
      const message = {
        name: "device_control",
        data: {
          command: "restart_device",
        },
      };

      channel.publish("message", message, (err) => {
        if (err) {
          console.error("Failed to send restart command to ESP32:", err);
          showAlert("Error!", "Failed to restart device.", "error");
        } else {
          console.log(
            "📢 Restart command sent successfully to ESP32:",
            message
          );
          showAlert("Success!", "Restart command sent to device.", "success");
        }
      });
    }
  });
}

// Function to reset the device
function resetDevice() {
  Swal.fire({
    title: "Reset Device",
    text: "Are you sure you want to reset the device? This will clear all device settings.",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#d33",
    cancelButtonColor: "#3085d6",
    confirmButtonText: "Yes, reset it!",
  }).then((result) => {
    if (result.isConfirmed) {
      const message = {
        name: "device_control",
        data: {
          command: "reset_device",
        },
      };

      channel.publish("message", message, (err) => {
        if (err) {
          console.error("Failed to send reset command to ESP32:", err);
          showAlert("Error!", "Failed to reset device.", "error");
        } else {
          console.log("📢 Reset command sent successfully to ESP32:", message);
          showAlert("Success!", "Reset command sent to device.", "success");
        }
      });
    }
  });
}

// Function to toggle add plant modal
function toggleAddPlantModal() {
  const modal = document.getElementById("addPlantModal");
  if (modal.classList.contains("hidden")) {
    modal.classList.remove("hidden");
    modal.classList.add("flex");
    document.body.style.overflow = "hidden";

    // Request available pins from ESP32
    if (channel) {
      requestAvailablePins(); // Use the new function
    } else {
      console.error("Channel not initialized");
      showError("Connection error. Please refresh the page.");
    }
  } else {
    modal.classList.add("hidden");
    modal.classList.remove("flex");
    document.body.style.overflow = "auto";
  }
}

// Function to delete a plant
function deletePlant(pin) {
  // Show warning notification
  Swal.fire({
    title: "<strong>Are you sure?</strong>",
    icon: "warning",
    html: `
      <div class="text-center">
        <i class="text-5xl text-yellow-500 bi bi-exclamation-triangle mb-4"></i>
        <p class="text-gray-600">This plant will be permanently deleted.</p>
        <p class="text-gray-600">You won't be able to recover this data!</p>
      </div>
    `,
    showCancelButton: true,
    focusConfirm: false,
    confirmButtonText: '<i class="bi bi-trash-fill mr-2"></i>Yes, delete it!',
    confirmButtonColor: "#EF4444", // red-500
    cancelButtonText: '<i class="bi bi-x-lg mr-2"></i>Cancel',
    cancelButtonColor: "#6B7280", // gray-500
    background: "rgba(255, 255, 255, 0.9)",
    backdrop: `rgba(0,0,0,0.4)`,
    allowOutsideClick: false,
    customClass: {
      popup: "rounded-xl shadow-2xl",
      confirmButton: "px-4 py-2 text-sm font-medium rounded-lg",
      cancelButton: "px-4 py-2 text-sm font-medium rounded-lg",
    },
  }).then((result) => {
    if (result.isConfirmed) {
      // Send delete request
      fetch(`../api/delete_plant.php?pin=${pin}`, {
        method: "DELETE",
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            // Remove plant card from UI
            const plantCard = document.getElementById(`plantCard${pin}`);
            if (plantCard) {
              plantCard.remove();
            }

            // Remove from our local cache
            plantDatabase.delete(pin.toString());

            // Remove from used pins
            usedPins.delete(pin.toString());

            // Send message to ESP32 to stop monitoring this pin
            console.log("📤 Sending delete command to ESP32 for pin:", pin);

            channel.publish("message", {
              name: "delete_plant",
              data: {
                pin: parseInt(pin),
                command: "stop_monitoring",
              },
            });

            // Show success notification
            Swal.fire({
              icon: "success",
              title: "Deleted!",
              text: "The plant has been deleted successfully.",
              showConfirmButton: false,
              timer: 1500,
              background: "rgba(255, 255, 255, 0.9)",
              customClass: {
                popup: "rounded-xl shadow-2xl",
              },
            });
          } else {
            // Show error notification
            Swal.fire({
              icon: "error",
              title: "Oops...",
              text: data.message || "Failed to delete plant",
              confirmButtonColor: "#3B82F6",
              background: "rgba(255, 255, 255, 0.9)",
              customClass: {
                popup: "rounded-xl shadow-2xl",
                confirmButton: "px-4 py-2 text-sm font-medium rounded-lg",
              },
            });
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          Swal.fire({
            icon: "error",
            title: "Oops...",
            text: "Something went wrong!",
            confirmButtonColor: "#3B82F6",
            background: "rgba(255, 255, 255, 0.9)",
            customClass: {
              popup: "rounded-xl shadow-2xl",
              confirmButton: "px-4 py-2 text-sm font-medium rounded-lg",
            },
          });
        });
    }
  });
}

// Function to edit plant
function editPlant(pin) {
  // Get plant data from database
  fetch(`../api/get_plant.php?pin=${pin}`)
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        // Show edit modal with plant data
        console.log("Editing plant with pin:", pin);
      } else {
        showError(data.message || "Failed to get plant data");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      showError("Failed to get plant data");
    });
}

// Function to fetch and send plant data to ESP32
async function sendPlantsToESP32() {
  try {
    console.log("📤 Fetching plants from database and sending to ESP32");

    // 1. Fetch all plants from database
    const response = await fetch("../api/get_plants.php");
    const result = await response.json();

    if (!result.success) {
      throw new Error(result.message || "Failed to fetch plants");
    }

    // 2. For each plant in database, send to ESP32
    result.plants.forEach((plant) => {
      // Store each plant in our map using pin as key
      plantDatabase.set(plant.moisture_pin.toString(), plant);

      channel.publish(
        "plant_config", // Channel name
        {
          pin: parseInt(plant.moisture_pin), // GPIO pin number
          name: plant.name, // Plant name
        },
        (err) => {
          if (err) {
            console.error("❌ Failed to send plant to ESP32:", err);
          } else {
            console.log(
              "✅ Sent plant to ESP32:",
              plant.name,
              plant.moisture_pin
            );
          }
        }
      );
      // 3. Track used pins locally
      usedPins.add(plant.moisture_pin.toString());
    });
  } catch (error) {
    console.error("❌ Error sending plants to ESP32:", error);
  }
}

// Function to update plant card with checkbox state preservation
function updatePlantCard(pin, moisturePercentage, name, type) {
  const container = document.getElementById("plantCardsContainer");

  // Save checkbox state before updating
  const existingCheckbox = document.querySelector(`#plantCheckbox${pin}`);
  const wasChecked = existingCheckbox ? existingCheckbox.checked : false;

  // Status colors logic based on moisture percentage
  let statusClass, statusText, barColor;
  if (moisturePercentage > 80) {
    statusClass = "bg-yellow-100 text-yellow-800";
    statusText = "Overwatered";
    barColor = "bg-yellow-500";
  } else if (moisturePercentage >= 40) {
    statusClass = "bg-green-100 text-green-800";
    statusText = "Healthy";
    barColor = "bg-green-500";
  } else {
    statusClass = "bg-red-100 text-red-800";
    statusText = "Critical";
    barColor = "bg-red-500";
  }

  const plantCardHTML = `
    <div id="plantCard${pin}" class="bg-white border border-green-100 p-4 rounded-lg shadow-md">
      <div class="flex justify-between items-start mb-2">
        <div class="flex gap-2 items-center">
          <input type="checkbox" 
                 id="plantCheckbox${pin}" 
                 class="rounded text-green-600 plant-card-checkbox"
                 onchange="updateWaterSelectedButton()"
                 ${wasChecked ? "checked" : ""}>
          <div>
            <h3 class="text-gray-800 text-lg font-semibold">${
              name || `Plant ${pin}`
            }</h3>
            ${type ? `<p class="text-gray-600 text-sm">Type: ${type}</p>` : ""}
          </div>
        </div>
        <span id="status${pin}" class="rounded-full text-sm ${statusClass} px-3 py-1">
          ${statusText}
          
        </span>
        
      </div>
      
      <div class="flex gap-4 items-center mb-2">
        <div class="flex-1 bg-gray-200 h-2 rounded-full">
          <div id="moistureBar${pin}" 
               class="h-2 rounded-full ${barColor} duration-500 transition-all"
               style="width: ${moisturePercentage}%">
          </div>
        </div>
        <span id="moisturePercent${pin}" class="text-gray-600 text-sm min-w-[48px]">
          ${moisturePercentage}%
        </span>
      </div>

      <p class="text-gray-600 text-sm">Pin: GPIO${pin}</p>
      
      <!-- Add Water Button -->
      <div class="flex justify-between items-center mt-4">
        <button onclick="waterPlant(${pin})" 
                class="flex bg-blue-500 rounded-md text-white gap-2 hover:bg-blue-600 items-center px-4 py-2 transition-colors">
          <i class="bi bi-droplet-fill"></i>
          Water this plant
        </button>
        <button onclick="confirmDeletePlantCard(${pin})" 
                  class="text-red-500 hover:text-red-600 transition-colors">
            <i class="bi bi-trash-fill"></i>
        </button>
      </div>
      
    </div>
  `;

  // Update the card
  const existingCard = document.getElementById(`plantCard${pin}`);
  if (!existingCard) {
    container.insertAdjacentHTML("beforeend", plantCardHTML);
  } else {
    existingCard.outerHTML = plantCardHTML;
  }
}

// Helper function to format dates
function formatDate(dateString) {
  if (!dateString) return "";
  const date = new Date(dateString);
  if (isNaN(date.getTime())) return "";
  return date.toLocaleString();
}

// Updated loadPlantsFromDatabase function to populate our cache
async function loadPlantsFromDatabase() {
  try {
    const response = await fetch("../api/get_plants.php");
    const data = await response.json();

    if (data.success) {
      data.plants.forEach((plant) => {
        // Store each plant in our map using pin as key
        plantDatabase.set(plant.moisture_pin.toString(), plant);

        // Initialize the plant card with data from database
        updatePlantCard(
          plant.moisture_pin,
          plant.moisture_level || 0, // Use existing moisture level from DB if available
          plant.name,
          plant.type || ""
        );

        // Track used pins
        usedPins.add(plant.moisture_pin.toString());
      });
    }
  } catch (error) {
    console.error("Failed to load plants:", error);
  }
}

// Function to handle watering - just turn on pump
function waterPlant(pin) {
  const message = {
    name: "pump_control",
    data: {
      command: pin,
    },
  };

  // Use the global channel variable
  channel.publish("message", message, (err) => {
    if (err) {
      console.error("Failed to publish message:", err);
    } else {
      console.log("🚰 Message published successfully:", message);
    }
  });

  // Show watering notification
  Swal.fire({
    title: "Watering Plants",
    text: "Pump activated...",
    icon: "info",
    timer: 5000,
    timerProgressBar: true,
    showConfirmButton: false,
  });
}

// Function to update water selected button state
function updateWaterSelectedButton() {
  const selectedPlants = document.querySelectorAll(
    ".plant-card-checkbox:checked"
  );
  const waterSelectedButton = document.getElementById("waterSelectedButton");

  // Enable button if any plants are selected
  waterSelectedButton.disabled = selectedPlants.length === 0;

  // Update button text based on selection
  if (selectedPlants.length > 0) {
    waterSelectedButton.innerHTML = `
      <i class="bi bi-droplet-fill"></i>
      Water ${selectedPlants.length} Selected Plant${
      selectedPlants.length > 1 ? "s" : ""
    }
    `;
  } else {
    waterSelectedButton.innerHTML = `
      <i class="bi bi-droplet-fill"></i>
      Water Selected Plants
    `;
  }
}

// Function to water selected plants
function waterSelectedPlants() {
  const selectedPlants = document.querySelectorAll(
    ".plant-card-checkbox:checked"
  );

  if (selectedPlants.length > 0) {
    console.log(`🚰 Watering ${selectedPlants.length} selected plants`);

    // Send command to ESP32 with proper structure
    const message = {
      name: "pump_control",
      data: {
        command: "water_all",
      },
    };

    // Send to ESP32
    channel.publish("message", message, (err) => {
      if (err) {
        console.error("Failed to publish message:", err);
      } else {
        console.log("🚰 Message published successfully:", message);
      }
    });

    // Show watering notification
    Swal.fire({
      title: "Watering Plants",
      text: `Watering ${selectedPlants.length} selected plants...`,
      icon: "info",
      timer: 5000,
      timerProgressBar: true,
      showConfirmButton: false,
    });
  }

  // Unselect all checkboxes after watering
  document.querySelectorAll(".plant-card-checkbox").forEach((checkbox) => {
    checkbox.checked = false;
  });
}

// Function to update the pins dropdown
function updatePinDropdown(pins) {
  // Get the select element
  const sensorPinSelect = document.getElementById("sensorPin");

  // Clear existing options
  sensorPinSelect.innerHTML = "";

  // Add a placeholder option
  const placeholderOption = document.createElement("option");
  placeholderOption.value = "";
  placeholderOption.textContent = "Select a sensor pin";
  sensorPinSelect.appendChild(placeholderOption);

  // Filter out pins that are already in use
  const availablePins = pins.filter((pin) => !usedPins.has(pin.toString()));

  // Add options for each available pin
  availablePins.forEach((pin) => {
    const option = document.createElement("option");
    option.value = pin;
    option.textContent = `GPIO ${pin}`;
    option.title = `Analog-capable GPIO pin ${pin} for moisture sensor`;
    sensorPinSelect.appendChild(option);
  });

  // If no pins are available, show a message
  if (availablePins.length === 0) {
    const noOption = document.createElement("option");
    noOption.value = "";
    noOption.textContent = "No sensor pins available";
    noOption.disabled = true;
    sensorPinSelect.appendChild(noOption);
  }

  // Remove the loading message
  sensorPinSelect.classList.remove("cursor-help");

  // Enable the select element
  sensorPinSelect.disabled = false;
}

// Function to show error messages
function showError(message) {
  const errorDiv = document.getElementById("formError");
  errorDiv.textContent = message;
  errorDiv.classList.remove("hidden");
  setTimeout(() => {
    errorDiv.classList.add("hidden");
  }, 3000);
}

// Function to show success messages
function showSuccess(message) {
  Swal.fire({
    title: "Success!",
    text: message,
    icon: "success",
    timer: 2000,
    showConfirmButton: false,
    background: "rgba(255, 255, 255, 0.9)",
    customClass: {
      popup: "rounded-lg shadow-xl",
    },
  });
}

document.addEventListener("DOMContentLoaded", function () {
  try {
    // Initialize Ably and channel
    const ably = new Ably.Realtime(
      "Pv6ihg.dTVV5g:WvuHeDy0GRFUyeczjP7yn5iVbNygMVBhq0p20dr1jho"
    );
    channel = ably.channels.get("esp32");

    // Connection status
    ably.connection.on("connected", () => {
      console.log("✅ Connected to Ably");

      // Load plants from database first
      loadPlantsFromDatabase().then(() => {
        // Then send to ESP32 after database is loaded
        sendPlantsToESP32();
        requestAvailablePins();
      });
    });

    ably.connection.on("failed", () =>
      console.error("❌ Failed to connect to Ably")
    );

    // Listen for messages from ESP32
    channel.subscribe((message) => {
      console.log("📥 Message Received:", message);

      if (!message) return;

      switch (message.name) {
        case "available_pins":
          // Handle available pins from ESP32
          const pins = message.data;
          if (Array.isArray(pins)) {
            updatePinDropdown(pins);
            console.log("📌 Available pins:", pins);
          }
          break;

        case "moisture_data":
          // Handle moisture data from ESP32
          const moistureData = message.data;
          if (!Array.isArray(moistureData)) {
            console.error("❌ Invalid moisture data:", moistureData);
            return;
          }

          // Update each plant's moisture level
          moistureData.forEach((data) => {
            // Get the pin and moisture from ESP32 data
            const pin = data.pin;
            const moisturePercentage = data.moisturePercentage;

            if (pin === undefined || moisturePercentage === undefined) {
              console.error("❌ Missing pin or moisture data:", data);
              return;
            }

            console.log(`🌱 Pin ${pin}: ${moisturePercentage}% moisture`);

            // Get plant data from our database cache
            const plant = plantDatabase.get(pin.toString());

            if (plant) {
              // Update the plant card with combined data
              updatePlantCard(
                pin,
                moisturePercentage, // Real-time data from ESP32
                plant.name, // Metadata from database
                plant.type || "" // Metadata from database
              );
            }
          });
          break;

        case "pump_status":
          // Handle pump status updates
          const { pin: pumpPin, status } = message.data;
          console.log(`🚰 Pump status for pin ${pumpPin}:`, status);
          break;

        case "user_credentials":
          // Handle user credentials from ESP32
          console.log("📥 Received user credentials from ESP32:", message.data);

          // Send credentials to database immediately
          fetch("../api/save_credentials.php", {
            method: "POST",
            headers: {
              "Content-Type": "application/json",
            },
            body: JSON.stringify(message.data),
          })
            .then((response) => response.json())
            .then((data) => {
              if (data.success) {
                console.log("✅ Credentials saved to database:", {
                  email: message.data.email,
                  savedAt: new Date().toISOString(),
                });
              } else {
                console.error("❌ Failed to save credentials:", data.message);
              }
            })
            .catch((error) => {
              console.error("❌ Error saving credentials:", error);
            });
          break;

        default:
          console.log("📥 Received message:", message.name, message.data);
      }
    });

    // Listen for ESP32 responses
    channel.subscribe("pump_status", (message) => {
      console.log("📥 ESP32 Response:", message.data);

      if (message.data === "Watering is Done!") {
        Swal.fire({
          title: "Complete",
          text: "✅ Watering complete!",
          icon: "success",
          timer: 2000,
          showConfirmButton: false,
        });
      }
    });
  } catch (error) {
    console.error("❌ Error initializing Ably:", error);
  }

  // Handle form submission for adding plants
  document
    .getElementById("addPlantForm")
    .addEventListener("submit", function (e) {
      e.preventDefault();

      const formData = new FormData(this);
      const plantData = {
        name: formData.get("plantName"),
        type: formData.get("plantType") || null,
        moisture_pin: formData.get("sensorPin"),
      };

      console.log("📝 Saving plant data:", plantData);

      // Send to database
      fetch("../api/add_plant.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(plantData),
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            console.log("✅ Saved to database:", data.plant);

            // Add to our local cache
            plantDatabase.set(data.plant.moisture_pin.toString(), data.plant);

            // Track this pin as used
            usedPins.add(data.plant.moisture_pin.toString());

            // Send to ESP32 via Ably
            const esp32Config = {
              pin: parseInt(plantData.moisture_pin),
              name: plantData.name,
            };

            console.log("📡 Sending to ESP32:", esp32Config);
            channel.publish("plant_config", esp32Config);

            // Update UI with all data
            updatePlantCard(
              data.plant.moisture_pin,
              data.plant.moisture_level || 0,
              data.plant.name,
              data.plant.type
            );

            // Close modal and show success
            toggleAddPlantModal();
            this.reset();
            showSuccess("Plant added successfully!");
          } else {
            showError(data.message || "Failed to add plant");
          }
        })
        .catch((error) => {
          console.error("❌ Error:", error);
          showError("Failed to add plant");
        });
    });

  // Subscribe to available pins updates from ESP32
  channel.subscribe("available_pins", (message) => {
    console.log("Available pins received:", message.data);

    // Update the pin dropdown with available pins
    updatePinDropdown(message.data);
  });

  // Function to handle pin selection
  function selectPin() {
    const selectedPin = document.getElementById("sensorPin").value;
    if (selectedPin) {
      const message = {
        name: "send_selected_pin",
        data: {
          command: parseInt(selectedPin),
        },
      };

      channel.publish("message", message, (err) => {
        if (err) {
          console.error("Failed to select pin:", err);
        } else {
          console.log(`📌 Pin ${selectedPin} selected successfully`);
        }
      });
    }
  }

  // Add an event listener to the select element
  document.getElementById("sensorPin").addEventListener("change", selectPin);

  // Add click event listener for the Add Plant button and close buttons
  const addPlantBtn = document.getElementById("addPlantBtn");
  const modalCloseX = document.getElementById("modalCloseX");
  const modalCancelBtn = document.getElementById("modalCancelBtn");

  if (addPlantBtn) {
    addPlantBtn.addEventListener("click", toggleAddPlantModal);
  }

  if (modalCloseX) {
    modalCloseX.addEventListener("click", toggleAddPlantModal);
  }

  if (modalCancelBtn) {
    modalCancelBtn.addEventListener("click", toggleAddPlantModal);
  }

  // Close modal when clicking outside
  const addPlantModal = document.getElementById("addPlantModal");
  if (addPlantModal) {
    addPlantModal.addEventListener("click", function (e) {
      if (e.target === this) {
        toggleAddPlantModal();
      }
    });
  }

  // Close modal on escape key
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape" && !addPlantModal.classList.contains("hidden")) {
      toggleAddPlantModal();
    }
  });

  // Handle watering mode selection
  document
    .getElementById("wateringMode")
    .addEventListener("change", function (e) {
      const scheduleDropdown = document.getElementById("wateringSchedule");
      if (e.target.value === "auto") {
        scheduleDropdown.classList.remove("hidden");
        document.getElementById("saveSchedule").classList.remove("hidden");
        document.getElementById("waterSelectedButton").classList.add("hidden");
      } else {
        scheduleDropdown.classList.add("hidden");
        document.getElementById("saveSchedule").classList.add("hidden");
        document
          .getElementById("waterSelectedButton")
          .classList.remove("hidden");
      }
    });

  // Handle select all checkbox
  document.getElementById("selectAll").addEventListener("change", function (e) {
    const plantCards = document.querySelectorAll(".plant-card-checkbox");
    plantCards.forEach((checkbox) => {
      checkbox.checked = e.target.checked;
    });
    updateWaterSelectedButton();
  });

  // Update the water selected button click handler
  document
    .getElementById("waterSelectedButton")
    .addEventListener("click", waterSelectedPlants);

  // Settings dropdown toggle
  document
    .getElementById("settingsButton")
    .addEventListener("click", function (e) {
      e.stopPropagation();
      const dropdown = document.getElementById("settingsDropdown");
      dropdown.classList.toggle("hidden");
    });

  // Close dropdown when clicking outside
  document.addEventListener("click", function () {
    document.getElementById("settingsDropdown").classList.add("hidden");
  });

  // User settings modal functions
  function showUserSettings() {
    const modal = document.getElementById("userSettingsModal");
    if (modal) {
      modal.classList.remove("hidden");
      modal.classList.add("flex");
      document.getElementById("settingsDropdown").classList.add("hidden");
      document.body.style.overflow = "hidden"; // Prevent scrolling when modal is open
    }
  }

  function closeUserSettings() {
    const modal = document.getElementById("userSettingsModal");
    if (modal) {
      modal.classList.add("hidden");
      modal.classList.remove("flex");
      document.body.style.overflow = "auto"; // Restore scrolling
    }
  }

  // Close modal when clicking outside
  document.addEventListener("click", function (e) {
    const modal = document.getElementById("userSettingsModal");
    if (modal && e.target === modal) {
      closeUserSettings();
    }
  });

  // Close modal on escape key
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") {
      closeUserSettings();
    }
  });

  // Handle user settings form submission
  document
    .getElementById("userSettingsForm")
    .addEventListener("submit", function (e) {
      e.preventDefault();

      const formData = {
        username: document.getElementById("username").value,
        gender: document.getElementById("gender").value,
        oldPassword: document.getElementById("oldPassword").value,
        newPassword: document.getElementById("newPassword").value,
      };

      fetch("../api/update_user_settings.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(formData),
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            Swal.fire({
              title: "Success!",
              text: "Settings updated successfully",
              icon: "success",
              timer: 2000,
              showConfirmButton: false,
            });
            closeUserSettings();
          } else {
            Swal.fire({
              title: "Error!",
              text: data.message || "Failed to update settings",
              icon: "error",
            });
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          Swal.fire({
            title: "Error!",
            text: "An error occurred. Please try again.",
            icon: "error",
          });
        });
    });

  // Make sure settings button has the right event handler
  document
    .querySelector('#settingsDropdown [data-action="settings"]')
    ?.addEventListener("click", showUserSettings);
});

// Function to confirm logout
function confirmLogout(event) {
  event.preventDefault();

  Swal.fire({
    title: "Are you sure?",
    text: "You will be logged out of your account",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#3085d6",
    cancelButtonColor: "#d33",
    confirmButtonText: "Yes, log me out!",
  }).then((result) => {
    if (result.isConfirmed) {
      window.location.href = "../api/logout.php";
    }
  });
}

function deletePlantCard(pin) {
  fetch(`../api/delete_plant.php?pin=${pin}`, {
    method: "DELETE",
  })
    .then((response) => {
      if (!response.ok) {
        throw new Error("Network response was not ok");
      }
      return response.json();
    })
    .then((data) => {
      if (data.success) {
        // Remove plant card from UI
        const plantCard = document.getElementById(`plantCard${pin}`);
        if (plantCard) {
          plantCard.remove();
        }

        // Remove from our local cache
        plantDatabase.delete(pin.toString());

        // Remove from used pins
        usedPins.delete(pin.toString());

        // Send message to ESP32 to stop monitoring this pin
        const message = {
          name: "delete_plant",
          data: {
            pin: parseInt(pin),
            command: "stop_monitoring",
          },
        };

        channel.publish("message", message, (err) => {
          if (err) {
            console.error("Failed to publish message:", err);
            showAlert("Error!", "Failed to send command to Ably.", "error");
          } else {
            console.log("🚰 Message published successfully:", message);
            requestAvailablePins();
            showAlert(
              "Deleted!",
              "Plant deleted successfully and command sent to Ably.",
              "success"
            );
          }
        });
      } else {
        showAlert("Error!", data.message, "error");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      showAlert(
        "Error!",
        "An error occurred while deleting the plant.",
        "error"
      );
    });
}

function confirmDeletePlantCard(pin) {
  Swal.fire({
    title: "Are you sure?",
    text: "You won't be able to revert this!",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#d33",
    cancelButtonColor: "#3085d6",
    confirmButtonText: "Yes, delete it!",
    cancelButtonText: "Cancel",
  }).then((result) => {
    if (result.isConfirmed) {
      deletePlantCard(pin);
    }
  });
}

function showAlert(title, text, icon, timer = 3000) {
  Swal.fire({
    title,
    text,
    icon,
    timer,
    timerProgressBar: true,
    showConfirmButton: false,
  });
}

function saveWateringSchedule() {
  // Get all selected plant checkboxes
  const selectedPlants = document.querySelectorAll(
    ".plant-card-checkbox:checked"
  );
  if (selectedPlants.length === 0) {
    showAlert("Error!", "Please select at least one plant.", "error");
    return;
  }

  const wateringMode = document.getElementById("wateringMode").value;
  const wateringSchedule = document.getElementById("wateringSchedule").value;

  // Show confirmation dialog
  Swal.fire({
    title: "Update Watering Schedule",
    text: `Are you sure you want to ${
      wateringMode === "manual"
        ? "set manual watering"
        : `set automatic watering for ${wateringSchedule}`
    } for ${selectedPlants.length} selected plant(s)?`,
    icon: "question",
    showCancelButton: true,
    confirmButtonColor: "#3085d6",
    cancelButtonColor: "#d33",
    confirmButtonText: "Yes, update all!",
  }).then((result) => {
    if (result.isConfirmed) {
      // Prepare schedule value based on mode
      const scheduleValue =
        wateringMode === "manual" ? "manual" : wateringSchedule;

      // Create array of promises for each plant update
      const updatePromises = Array.from(selectedPlants).map((checkbox) => {
        const pin = checkbox.id.replace("plantCheckbox", "");

        return fetch("../api/save_watering_schedule.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify({
            moisture_pin: parseInt(pin),
            schedule: scheduleValue,
          }),
        }).then(async (response) => {
          const data = await response.json();
          if (!response.ok) {
            throw new Error(
              `Failed to update schedule for pin ${pin}: ${data.message}`
            );
          }
          return { pin, data };
        });
      });

      // Execute all update requests
      Promise.all(updatePromises)
        .then((results) => {
          // Update local plant data and UI for all successful updates
          results.forEach(({ pin, data }) => {
            if (data.success) {
              const plant = plantDatabase.get(pin);
              if (plant) {
                plant.schedule = scheduleValue;
                plantDatabase.set(pin, plant);
                // Update the plant card directly
                updatePlantCard(
                  pin,
                  plant.moisture_level || 0,
                  plant.name,
                  plant.type || ""
                );
                // Trigger sending schedule to ESP32
                sendScheduleToESP32(pin);
              }
            }
          });

          // Unselect all plant card checkboxes and the select all button
          document
            .querySelectorAll(".plant-card-checkbox")
            .forEach((checkbox) => {
              checkbox.checked = false;
            });
          document.getElementById("selectAll").checked = false;

          showAlert(
            "Success!",
            `Watering schedule updated successfully for ${results.length} plant(s).`,
            "success"
          );
        })
        .catch((error) => {
          console.error("Error updating schedules:", error);
          showAlert(
            "Error!",
            error.message || "An error occurred while updating schedules.",
            "error"
          );
        });
    }
  });
}

// Function to send schedule to ESP32 via Ably
function sendScheduleToESP32(pin) {
  // First fetch the schedule from database
  fetch(`../api/get_schedule.php?pin=${pin}`)
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        // Create message for ESP32
        const message = {
          name: "schedule_update",
          data: {
            pin: pin,
            schedule: data.schedule,
          },
        };

        // Publish message to Ably channel
        channel.publish("message", message, (err) => {
          if (err) {
            console.error("Failed to send schedule to ESP32:", err);
            showAlert("Error!", "Failed to send schedule to device.", "error");
          } else {
            console.log("📅 Schedule sent successfully to ESP32:", message);
          }
        });

        // Show success alert once after attempting to send the schedule
        if (!window.scheduleAlertShown) {
          showAlert(
            "Success!",
            `The schedule for the selected plant has been sent to the device.`,
            "success"
          );
          window.scheduleAlertShown = true;
        }
      } else {
        throw new Error(data.message || "Failed to fetch schedule");
      }
    })
    .catch((error) => {
      console.error("Error sending schedule:", error);
      showAlert(
        "Error!",
        error.message || "Failed to send schedule to device.",
        "error"
      );
    });
}
