// Toggle forms
document.getElementById("toggleRegister").addEventListener("click", () => {
    document.getElementById("authForm").style.display = "none";
    document.getElementById("registerForm").style.display = "block";
    document.getElementById("message").textContent = "";
});
document.getElementById("toggleLogin").addEventListener("click", () => {
    document.getElementById("authForm").style.display = "block";
    document.getElementById("registerForm").style.display = "none";
    document.getElementById("message").textContent = "";
});

// Login form
document.getElementById("authForm").addEventListener("submit", async e => {
    e.preventDefault();
    const email = document.getElementById("email").value.trim();
    const password = document.getElementById("password").value.trim();
    const messageEl = document.getElementById("message");

    try {
        const res = await fetch("php/login.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ email, password })
        });
        const result = await res.json();

        messageEl.textContent = result.message;
        messageEl.style.color = result.success ? "green" : "red";

        if (result.success) {
            window.location.href = "main.php"; // session will handle login
        }
    } catch (err) {
        console.error(err);
        messageEl.textContent = "Login error";
        messageEl.style.color = "red";
    }
});

// Register form
document.getElementById("registerForm").addEventListener("submit", async e => {
    e.preventDefault();
    const data = {
        fname: document.getElementById("fname").value.trim(),
        lname: document.getElementById("lname").value.trim(),
        dob: document.getElementById("dob").value,
        address: document.getElementById("address").value.trim(),
        email: document.getElementById("regEmail").value.trim(),
        password: document.getElementById("regPassword").value
    };
    const messageEl = document.getElementById("message");

    try {
        const res = await fetch("php/register.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(data)
        });
        const result = await res.json();
        messageEl.textContent = result.message;
        messageEl.style.color = result.success ? "green" : "red";

        if (result.success) {
            setTimeout(() => {
                document.getElementById("authForm").style.display = "block";
                document.getElementById("registerForm").style.display = "none";
                messageEl.textContent = "";
            }, 1500);
        }
    } catch (err) {
        console.error(err);
        messageEl.textContent = "Registration error";
        messageEl.style.color = "red";
    }
});
