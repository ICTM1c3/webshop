window.onload = temperatureUpdater; // Start the temperatureupdater function once upon load of the page

function temperatureUpdater() {
    updateTemperature();
    window.setInterval(updateTemperature, 5000); // automatically execute the update function every 5 seconds
}

function updateTemperature() {
    let temperatureValue = document.getElementById("TemperatureValue");
    
    let fetchedValue = 100;
    
    temperatureValue.innerHTML = fetchedValue;
}
