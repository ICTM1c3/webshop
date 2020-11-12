window.onload = temperatureUpdater;
var testTemp = 5;

function temperatureUpdater() {
    updateTemperature();
    window.setInterval(updateTemperature, 5000);
}

function updateTemperature() {
    let temperatureValue = document.getElementById("TemperatureValue");
    let fetchedValue = 100;
    
    temperatureValue.innerHTML = fetchedValue;
}
