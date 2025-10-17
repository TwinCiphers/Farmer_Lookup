document.getElementById('subscribeBtn').addEventListener('click', function () {
  const email = document.getElementById('email').value;
  const message = document.getElementById('message');

  if (email === "") {
    message.style.color = "red";
    message.textContent = "Please enter your email!";
  } else if (!email.includes("@")) {
    message.style.color = "red";
    message.textContent = "Invalid email format!";
  } else {
    message.style.color = "#27ae60";
    message.textContent = "Thank you for subscribing!";
  }
});
