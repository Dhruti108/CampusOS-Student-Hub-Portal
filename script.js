window.onload = function() {
    var welcome = document.getElementById("welcome");

    if (welcome) {
        welcome.style.display = "block";
    }
};

function login() {
    var username = document.getElementById("username").value;
    var password = document.getElementById("password").value;

    if (username == "dhruti_108" && password == "dcs108@") {
        document.getElementById("popupMessage").innerHTML = "Login Successful!";
        document.getElementById("popup").style.display = "flex";
    } else {
        document.getElementById("popupMessage").innerHTML = "Wrong username or password!";
        document.getElementById("popup").style.display = "flex";
    }
}

function closePopup() {
    document.getElementById("popup").style.display = "none";
}

var registerForm = document.getElementById("registerForm");

if (registerForm) {
    registerForm.onsubmit = function(event) {
        var firstName = document.getElementById("firstname").value.trim();
        var lastName = document.getElementById("lastname").value.trim();
        var email = document.getElementById("email").value.trim();
        var enrollment = document.getElementById("enrollment").value.trim();
        var password = document.getElementById("password").value;
        var confirmPassword = document.getElementById("confirm_password").value;

        var namePattern = /^[A-Za-z ]+$/;
        var emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        var enrollmentPattern = /^[A-Za-z0-9]+$/;
        var passwordPattern = /^(?=.*[A-Za-z])(?=.*[0-9]).{6,}$/;

        if (!namePattern.test(firstName) || !namePattern.test(lastName)) {
            alert("Enter a valid first and last name.");
            event.preventDefault();
            return;
        }

        if (!emailPattern.test(email)) {
            alert("Enter a valid email address.");
            event.preventDefault();
            return;
        }

        if (!enrollmentPattern.test(enrollment)) {
            alert("Enter a valid enrollment ID using letters and numbers only.");
            event.preventDefault();
            return;
        }

        if (!passwordPattern.test(password)) {
            alert("Password must contain letters and numbers and be at least 6 characters.");
            event.preventDefault();
            return;
        }

        if (password !== confirmPassword) {
            alert("Passwords do not match.");
            event.preventDefault();
            return;
        }
    };
}

function changeTheme() {
    document.body.classList.toggle("dark");
}
