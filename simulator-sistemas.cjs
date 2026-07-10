const { DynamoDBClient } = require("@aws-sdk/client-dynamodb");
const { DynamoDBDocumentClient, PutCommand } = require("@aws-sdk/lib-dynamodb");

const client = new DynamoDBClient({ region: "us-east-1" });
const dynamodb = DynamoDBDocumentClient.from(client);

const tableName = "iot_temperature_readings_dev";
const INTERVAL_SECONDS = 60;

const sensors = [
  {
    sensor_id: "sensor_demo_001",
    client_id: "cliente_demo",
    site_id: "planta",
    camera_id: "camera_1",
    display_name: "Verduleria",
    baseTemp: 23.0,
    battery: 96,
  },
];

const state = {};

function randomBetween(min, max, decimals = 2) {
  return +(Math.random() * (max - min) + min).toFixed(decimals);
}

function clamp(value, min, max) {
  return Math.max(min, Math.min(max, value));
}

function initSensorState(sensor) {
  if (!state[sensor.sensor_id]) {
    state[sensor.sensor_id] = {
      currentTemp: sensor.baseTemp,
      battery: sensor.battery,
    };
  }
  return state[sensor.sensor_id];
}

function generateReading(sensor) {
  const s = initSensorState(sensor);

  // Oficina en invierno con aire prendido/calefacción: estable entre 22 y 24°C
  const targetTemp = randomBetween(22.2, 23.8);
  const variation = randomBetween(-0.18, 0.18);

  s.currentTemp = s.currentTemp + (targetTemp - s.currentTemp) * 0.35 + variation;
  s.currentTemp = clamp(s.currentTemp, 21.8, 24.2);

  if (Math.random() < 0.04) {
    s.battery -= 1;
  }

  s.battery = clamp(s.battery, 80, 100);

  return {
    sensor_id: sensor.sensor_id,
    timestamp: new Date().toISOString(),
    client_id: sensor.client_id,
    site_id: sensor.site_id,
    camera_id: sensor.camera_id,
    display_name: sensor.display_name,

    temperature: +s.currentTemp.toFixed(2),
    humidity: randomBetween(38, 48, 0),
    door_open: false,
    battery: s.battery,

    signal_rssi: randomBetween(-78, -58, 0),
    signal_snr: randomBetween(7.5, 12.5, 1),

    status: "normal",
    source: "simulator_office_systems",
  };
}

async function insertReading(item) {
  await dynamodb.send(
    new PutCommand({
      TableName: tableName,
      Item: item,
    })
  );

  console.log("Insertado:", item);
}

async function runSimulator() {
  console.log("Simulador iniciado - Oficina Sistemas / sensor_demo_001");
  console.log(`Intervalo: ${INTERVAL_SECONDS} segundos`);
  console.log(`Tabla: ${tableName}`);

  while (true) {
    for (const sensor of sensors) {
      const item = generateReading(sensor);
      await insertReading(item);
    }

    console.log(`Esperando ${INTERVAL_SECONDS} segundos...\n`);
    await new Promise((resolve) => setTimeout(resolve, INTERVAL_SECONDS * 1000));
  }
}

runSimulator().catch((error) => {
  console.error("Error:", error);
});