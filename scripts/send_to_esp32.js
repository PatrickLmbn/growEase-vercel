const Ably = require("ably");

// Initialize Ably
const ably = new Ably.Realtime(
  "Pv6ihg.dTVV5g:WvuHeDy0GRFUyeczjP7yn5iVbNygMVBhq0p20dr1jho"
);
const channel = ably.channels.get("esp32");

// Just forward turn_on command to ESP32
channel.subscribe("pump_command", (message) => {
  console.log("🚰 Turning on pump");
  channel.publish("pump_control", {
    command: "turn_on",
  });
});

// Handle pump status from ESP32
channel.subscribe("pump_status", (message) => {
  const { pin, status } = message.data;
  console.log("📥 Pump status from ESP32:", { pin, status });

  // Forward status to frontend
  channel.publish("pump_update", {
    pin: parseInt(pin),
    status: status,
  });
});

// Add this handler
// channel.subscribe("get_available_pins", (message) => {
//   if (message.data.type === "request") {
//     // Forward request to ESP32
//     channel.publish("get_pins", {
//       request: true,
//     });
//     console.log("📤 Forwarding pin request to ESP32");
//   }
// });

console.log("🚀 Pump control ready!");
