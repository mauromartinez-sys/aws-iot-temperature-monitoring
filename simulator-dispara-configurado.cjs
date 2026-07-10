const { DynamoDBClient } = require("@aws-sdk/client-dynamodb");
const { DynamoDBDocumentClient, PutCommand } = require("@aws-sdk/lib-dynamodb");

const client = new DynamoDBClient({ region: "us-east-1" });
const dynamodb = DynamoDBDocumentClient.from(client);

const tableName = "iot_temperature_readings_dev";

const INTERVAL_SECONDS = 60;

/*
|--------------------------------------------------------------------------
| Simulador controlado: DISPARAR alarma de rango
|--------------------------------------------------------------------------
|
| Para regla:
| Temperatura mínima: 8
| Temperatura máxima: 11
| Puerta: solo si está cerrada
|
| Este simulador manda:
| temperature = 12.5
| door_open = false
|
| Resultado esperado:
| Lambda crea/actualiza alarma con breach_type = above_max
|
*/

const sensors = [
  {
    sensor_id: "sensor_demo_003",
    client_id: "cliente_demo",
    site_id: "planta_demo",
    camera_id: "camara_3",
    baseTemp: 2.2,
    battery: 87,
    profile: "normal",
    testMode: "temperature_range_above_max",
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
      doorOpenRemainingCycles: 0,
      anomalyRemainingCycles: 0,
      missedReadingsRemainingCycles: 0,
    };
  }

  return state[sensor.sensor_id];
}

function buildReading(sensor, overrides = {}) {
  const s = initSensorState(sensor);

  return {
    sensor_id: sensor.sensor_id,
    timestamp: new Date().toISOString(),
    client_id: sensor.client_id,
    site_id: sensor.site_id,
    camera_id: sensor.camera_id,
    temperature: overrides.temperature ?? +s.currentTemp.toFixed(2),
    humidity: overrides.humidity ?? 72,
    door_open: overrides.door_open ?? false,
    battery: overrides.battery ?? s.battery,
    signal_rssi: overrides.signal_rssi ?? randomBetween(-115, -72, 0),
    signal_snr: overrides.signal_snr ?? randomBetween(-8, 12, 1),
    status: overrides.status ?? "normal",
    source: overrides.source ?? "simulator",
  };
}

function generateTestReading(sensor) {
  switch (sensor.testMode) {
    case "temperature_range_above_max":
      return buildReading(sensor, {
        temperature: 12.5,
        humidity: 75,
        door_open: false,
        battery: sensor.battery,
        signal_rssi: -90,
        signal_snr: 6.1,
        status: "temperature_alarm_above_max",
        source: "simulator_test_temperature_range_above_max",
      });

    default:
      return null;
  }
}

function generateReading(sensor) {
  const s = initSensorState(sensor);

  const testReading = generateTestReading(sensor);

  if (testReading) {
    return testReading;
  }

  if (sensor.profile === "unstable" && Math.random() < 0.03) {
    s.missedReadingsRemainingCycles = randomBetween(2, 5, 0);
  }

  if (s.missedReadingsRemainingCycles > 0) {
    s.missedReadingsRemainingCycles--;
    console.log(`Sin lectura simulada para ${sensor.sensor_id}`);
    return null;
  }

  if (s.doorOpenRemainingCycles === 0) {
    const doorChance = sensor.profile === "door_activity" ? 0.18 : 0.06;

    if (Math.random() < doorChance) {
      s.doorOpenRemainingCycles = randomBetween(2, 8, 0);
    }
  }

  const door_open = s.doorOpenRemainingCycles > 0;

  if (door_open) {
    s.doorOpenRemainingCycles--;
  }

  if (s.anomalyRemainingCycles === 0) {
    const anomalyChance = sensor.profile === "unstable" ? 0.08 : 0.02;

    if (Math.random() < anomalyChance) {
      s.anomalyRemainingCycles = randomBetween(5, 20, 0);
    }
  }

  const hasAnomaly = s.anomalyRemainingCycles > 0;

  if (hasAnomaly) {
    s.anomalyRemainingCycles--;
  }

  let targetTemp = sensor.baseTemp;

  if (door_open) {
    targetTemp += randomBetween(2.5, 6.5);
  }

  if (hasAnomaly) {
    targetTemp += randomBetween(5, 11);
  }

  const variation = randomBetween(-0.35, 0.35);
  s.currentTemp = s.currentTemp + (targetTemp - s.currentTemp) * 0.25 + variation;
  s.currentTemp = clamp(s.currentTemp, -2, 18);

  let humidity = 68 + randomBetween(-6, 8, 0);

  if (door_open) {
    humidity += randomBetween(4, 12, 0);
  }

  humidity = clamp(humidity, 45, 95);

  if (Math.random() < 0.10) {
    s.battery -= 1;
  }

  s.battery = clamp(s.battery, 40, 100);

  return {
    sensor_id: sensor.sensor_id,
    timestamp: new Date().toISOString(),
    client_id: sensor.client_id,
    site_id: sensor.site_id,
    camera_id: sensor.camera_id,
    temperature: +s.currentTemp.toFixed(2),
    humidity,
    door_open,
    battery: s.battery,
    signal_rssi: randomBetween(-115, -72, 0),
    signal_snr: randomBetween(-8, 12, 1),
    status: hasAnomaly ? "temperature_alarm" : door_open ? "door_open" : "normal",
    source: "simulator",
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
  console.log("Simulador iniciado - DISPARAR alarma de rango...");
  console.log(`Intervalo: ${INTERVAL_SECONDS} segundos`);
  console.log(`Tabla: ${tableName}`);
  console.log("Sensor/cámara de prueba: sensor_demo_003 / camara_3");
  console.log("Modo de prueba: temperature_range_above_max");

  while (true) {
    for (const sensor of sensors) {
      const item = generateReading(sensor);

      if (item) {
        await insertReading(item);
      }
    }

    console.log(`Esperando ${INTERVAL_SECONDS} segundos...\n`);
    await new Promise((resolve) =>
      setTimeout(resolve, INTERVAL_SECONDS * 1000)
    );
  }
}

runSimulator().catch((error) => {
  console.error("Error:", error);
});