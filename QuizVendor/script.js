
let current = 0;
let images = document.querySelectorAll("#carousel img");
let interval;

if (images.length > 0) {
  interval = setInterval(showNextImage, 3000);

  function showNextImage() {
    images[current].classList.remove("active");
    current = (current + 1) % images.length;
    images[current].classList.add("active");
  }

  window.pauseSlider = () => clearInterval(interval);
  window.playSlider = () => {
    clearInterval(interval);
    interval = setInterval(showNextImage, 3000);
  };
}


function toggleForm(formType) {
  const login = document.getElementById('loginForm');
  const signup = document.getElementById('signupForm');

  if (formType === 'login') {
    login.style.display = 'block';
    signup.style.display = 'none';
  } else {
    signup.style.display = 'block';
    login.style.display = 'none';
  }
}

function validateLogin() {
  alert("Login successful!");
  return false;
}
function validateSignup() {
  alert("Signup successful!");
  return false;
}