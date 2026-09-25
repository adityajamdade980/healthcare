/*
document
  .getElementById("calculateBtn")
  .addEventListener("click", calculateHealthScore);

function calculateHealthScore() {
  // Get input values
  const height = parseFloat(document.getElementById("height").value) / 100; // Convert to meters
  const weight = parseFloat(document.getElementById("weight").value);
  const exercise = parseFloat(document.getElementById("exercise").value);
  const sleep = parseFloat(document.getElementById("sleep").value);

  // Validate inputs
  if ([height, weight, exercise, sleep].some(isNaN)) {
    alert("Please fill in all fields with valid numbers");
    return;
  }

  // Calculate BMI
  const bmi = weight / (height * height);

  // Calculate individual scores
  const bmiScore = Math.max(0, 50 - Math.abs(22 - bmi) * 4); // Ideal BMI is 22
  const exerciseScore = Math.min(30, exercise * 2.5); // Max 30 points for exercise
  const sleepScore = Math.max(0, 20 - Math.abs(7 - sleep) * 4); // Ideal 7 hours sleep

  // Total health score
  const totalScore = Math.min(
    100,
    Math.round(bmiScore + exerciseScore + sleepScore)
  );

  // Display result
  const resultDiv = document.getElementById("result");
  resultDiv.style.display = "block";
  resultDiv.className =
    "result " +
    (totalScore >= 80 ? "good" : totalScore >= 50 ? "average" : "poor");

  resultDiv.innerHTML = `
        <h3>Your Health Score: ${totalScore}/100</h3>
        <p>BMI: ${bmi.toFixed(1)}</p>
        <p>Exercise: ${exercise} hrs/week</p>
        <p>Sleep: ${sleep} hrs/night</p>
    `;
}
*/
function calculateHealthScore() {
  // Get input values
  const height = parseFloat(document.getElementById("height").value) / 100; // Convert to meters
  const weight = parseFloat(document.getElementById("weight").value);
  const exercise = parseFloat(document.getElementById("exercise").value);
  const sleep = parseFloat(document.getElementById("sleep").value);

  // Calculate BMI
  const bmi = weight / (height * height);

  // Calculate individual scores
  const bmiScore = Math.max(0, 50 - Math.abs(22 - bmi) * 4); // Ideal BMI is 22
  const exerciseScore = Math.min(30, exercise * 2.5); // Max 30 points for exercise
  const sleepScore = Math.max(0, 20 - Math.abs(7 - sleep) * 4); // Ideal 7 hours sleep

  // Total health score
  const totalScore = Math.min(
    100,
    Math.round(bmiScore + exerciseScore + sleepScore)
  );

  // Display result
  const resultDiv = document.getElementById("result");
  resultDiv.style.display = "block";
  resultDiv.className =
    "result " +
    (totalScore >= 80 ? "good" : totalScore >= 50 ? "average" : "poor");

  resultDiv.innerHTML = `
        <h3>Your Health Score: ${totalScore}/100</h3>
        <p>BMI: ${bmi.toFixed(1)}</p>
        <p>Exercise: ${exercise} hrs/week</p>
        <p>Sleep: ${sleep} hrs/night</p>
    `;

  // Show BMI Guide
  document.getElementById("bmi-guide").style.display = "block";
}
