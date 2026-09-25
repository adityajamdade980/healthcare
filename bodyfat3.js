/*
// Show or hide the hip measurement based on gender selection
document.getElementById("gender").addEventListener("change", function () {
  const hipGroup = document.getElementById("hip-group");
  if (this.value === "female") {
    hipGroup.style.display = "block";
  } else {
    hipGroup.style.display = "none";
  }
});

function calculateBodyFat() {
  const gender = document.getElementById("gender").value;
  const age = parseFloat(document.getElementById("age").value);
  const height = parseFloat(document.getElementById("height").value);
  const neck = parseFloat(document.getElementById("neck").value);
  const waist = parseFloat(document.getElementById("waist").value);
  let hip = null;
  if (gender === "female") {
    hip = parseFloat(document.getElementById("hip").value);
  }

  if (
    gender &&
    age &&
    height &&
    neck &&
    waist &&
    (gender === "male" || (gender === "female" && hip))
  ) {
    let bodyFat = 0;

    if (gender === "male") {
      // Male body fat formula
      bodyFat =
        495 /
          (1.0324 -
            0.19077 * Math.log10(waist - neck) +
            0.15456 * Math.log10(height)) -
        450;
    } else {
      // Female body fat formula
      bodyFat =
        495 /
          (1.29579 -
            0.35004 * Math.log10(waist + hip - neck) +
            0.221 * Math.log10(height)) -
        450;
    }

    bodyFat = bodyFat.toFixed(2);

    // Display the result
    const resultDiv = document.getElementById("body-fat-result");
    resultDiv.innerText = `${bodyFat}%`;

    // Show result and guide
    document.getElementById("result-box").style.display = "block";
    document.getElementById("guide-box").style.display = "block";
  } else {
    alert("Please fill in all required fields.");
  }
}
*/

/*
function toggleHipInput() {
  const gender = document.getElementById("gender").value;
  const hipGroup = document.getElementById("hip-group");
  if (gender === "female") {
    hipGroup.style.display = "block";
  } else {
    hipGroup.style.display = "none";
  }
}

function calculateBodyFat() {
  // Retrieve input values
  const gender = document.getElementById("gender").value;
  const age = parseFloat(document.getElementById("age").value);
  const height = parseFloat(document.getElementById("height").value);
  const neck = parseFloat(document.getElementById("neck").value);
  const waist = parseFloat(document.getElementById("waist").value);
  let hip = null;
  if (gender === "female") {
    hip = parseFloat(document.getElementById("hip").value);
  }

  // Input validation
  const inputsValid =
    gender &&
    !isNaN(age) &&
    !isNaN(height) &&
    !isNaN(neck) &&
    !isNaN(waist) &&
    (gender === "male" || (gender === "female" && !isNaN(hip)));

  if (!inputsValid) {
    displayError("Please fill in all fields with valid numbers.");
    return;
  }

  // Ensure measurements are positive numbers
  if (
    age <= 0 ||
    height <= 0 ||
    neck <= 0 ||
    waist <= 0 ||
    (gender === "female" && hip <= 0)
  ) {
    displayError("All measurements must be positive numbers.");
    return;
  }

  // Calculations
  let bodyFat = 0;
  if (gender === "male") {
    // Male body fat formula
    const A1 = 1.0324;
    const B1 = 0.19077;
    const C1 = 0.15456;
    const logWaistNeck = Math.log10(waist - neck);
    const logHeight = Math.log10(height);
    bodyFat = 495 / (A1 - B1 * logWaistNeck + C1 * logHeight) - 450;
  } else {
    // Female body fat formula
    const A2 = 1.29579;
    const B2 = 0.35004;
    const C2 = 0.221;
    const logWaistHipNeck = Math.log10(waist + hip - neck);
    const logHeight = Math.log10(height);
    bodyFat = 495 / (A2 - B2 * logWaistHipNeck + C2 * logHeight) - 450;
  }

  bodyFat = bodyFat.toFixed(2);

  // Display result
  document.getElementById("body-fat-result").innerText = `${bodyFat}%`;
  document.getElementById("result").style.display = "block";
  document.getElementById("interpretation").style.display = "block";
  resultDiv.className =
    "result " +
    (totalScore >= 40 ? "good" : totalScore >= 20 ? "average" : "poor");

  // Remove any existing error messages
  removeError();
}

function displayError(message) {
  let errorDiv = document.getElementById("error-message");
  if (!errorDiv) {
    errorDiv = document.createElement("div");
    errorDiv.id = "error-message";
    errorDiv.className = "error-message";
    const calculator = document.querySelector(".calculator");
    calculator.appendChild(errorDiv);
  }
  errorDiv.innerText = message;
}

function removeError() {
  const errorDiv = document.getElementById("error-message");
  if (errorDiv) {
    errorDiv.remove();
  }
}
*/

function toggleHipInput() {
  const gender = document.getElementById("gender").value;
  const hipGroup = document.getElementById("hip-group");
  if (gender === "female") {
    hipGroup.style.display = "block";
  } else {
    hipGroup.style.display = "none";
  }
}

function calculateBodyFat() {
  // Retrieve input values
  const gender = document.getElementById("gender").value;
  const age = parseFloat(document.getElementById("age").value);
  const height = parseFloat(document.getElementById("height").value);
  const neck = parseFloat(document.getElementById("neck").value);
  const waist = parseFloat(document.getElementById("waist").value);
  let hip = null;
  if (gender === "female") {
    hip = parseFloat(document.getElementById("hip").value);
  }

  // Input validation
  const inputsValid =
    gender &&
    !isNaN(age) &&
    !isNaN(height) &&
    !isNaN(neck) &&
    !isNaN(waist) &&
    (gender === "male" || (gender === "female" && !isNaN(hip)));

  if (!inputsValid) {
    displayError("Please fill in all fields with valid numbers.");
    return;
  }

  // Ensure measurements are positive numbers
  if (
    age <= 0 ||
    height <= 0 ||
    neck <= 0 ||
    waist <= 0 ||
    (gender === "female" && hip <= 0)
  ) {
    displayError("All measurements must be positive numbers.");
    return;
  }

  // Calculations
  let bodyFat = 0;
  if (gender === "male") {
    // Male body fat formula
    const A1 = 1.0324;
    const B1 = 0.19077;
    const C1 = 0.15456;
    const logWaistNeck = Math.log10(waist - neck);
    const logHeight = Math.log10(height);
    bodyFat = 495 / (A1 - B1 * logWaistNeck + C1 * logHeight) - 450;
  } else {
    // Female body fat formula
    const A2 = 1.29579;
    const B2 = 0.35004;
    const C2 = 0.221;
    const logWaistHipNeck = Math.log10(waist + hip - neck);
    const logHeight = Math.log10(height);
    bodyFat = 495 / (A2 - B2 * logWaistHipNeck + C2 * logHeight) - 450;
  }

  bodyFat = bodyFat.toFixed(2);

  // Display result
  const resultDiv = document.getElementById("result");
  document.getElementById(
    "body-fat-result"
  ).innerText = `Body Fat: ${bodyFat}%`;
  resultDiv.style.display = "block";
  document.getElementById("interpretation").style.display = "block";
  /*
  // Class assignment based on body fat percentage
  const totalScore = parseFloat(bodyFat); // Assuming bodyFat as the score for this example
  resultDiv.className =
    "result " +
    (totalScore >= 40 ? "good" : totalScore >= 20 ? "average" : "poor");
*/

  const totalScore = parseFloat(bodyFat);

  if (gender === "male") {
    resultDiv.className =
      "result " +
      (totalScore >= 25
        ? "obese"
        : totalScore >= 18
        ? "average"
        : totalScore >= 14
        ? "fitness"
        : totalScore >= 6
        ? "athletes"
        : "essential");
  } else if (gender === "female") {
    resultDiv.className =
      "result " +
      (totalScore >= 32
        ? "obese"
        : totalScore >= 25
        ? "average"
        : totalScore >= 21
        ? "fitness"
        : totalScore >= 14
        ? "athletes"
        : "essential");
  }

  // Remove any existing error messages
  removeError();
}

function displayError(message) {
  let errorDiv = document.getElementById("error-message");
  if (!errorDiv) {
    errorDiv = document.createElement("div");
    errorDiv.id = "error-message";
    errorDiv.className = "error-message";
    const calculator = document.querySelector(".calculator");
    calculator.appendChild(errorDiv);
  }
  errorDiv.innerText = message;
}

function removeError() {
  const errorDiv = document.getElementById("error-message");
  if (errorDiv) {
    errorDiv.remove();
  }
}
