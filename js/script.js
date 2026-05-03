// script.js - JavaScript for Platform-Based Appointment System

// USER LOGIN (MySQL)
if (document.getElementById('loginForm')) {
    document.getElementById('loginForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const username = document.getElementById('username').value;
        const password = document.getElementById('password').value;

        fetch('login.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `username=${encodeURIComponent(username)}&password=${encodeURIComponent(password)}`
        })
        .then(res => res.text())
        .then(data => {
            if (data === "admin") {
                window.location.href = 'admin-index.html';
            } else if (data === "customer") {
                window.location.href = 'index.html';
            } else {
                document.getElementById('errorMessage').style.display = 'block';
            }
        });
    });
}


// SERVE NEXT (ADMIN)
const serveBtn = document.getElementById('serveNextBtn');

if (serveBtn) {
    serveBtn.addEventListener('click', function () {

        fetch('serve_next.php')
        .then(res => res.text())
        .then(data => {

            if (data === "success") {
                showAlert("Next customer is now being served");

                // refresh tables
                if (typeof loadAllAppointments === "function") {
                    loadAllAppointments();
                }

                if (typeof loadQueue === "function") {
                    loadQueue();
                }

            } else if (data === "no_more_queue") {
                showAlert("No more customers on appointments");
            } else {
                console.log("Response:", data);
            }

        })
        .catch(err => console.error("Error:", err));
    });
}






//AUTO-REFRESH QUEUE AND APPOINTMENTS EVERY 5 SECONDS
setInterval(() => {
    if (document.getElementById('queueTable')) {
        loadQueue();
    }
    if (document.getElementById('queuePosition')) {
        loadMyQueue();
    }
}, 5000); // every 5 seconds



// Load appointments
function loadAppointments() {
    fetch('get_appointments.php')
    .then(res => res.json())
    .then(data => {
        console.log("USER DATA:", data);

        const table = document.getElementById('userAppointmentsTable');
        table.innerHTML = '';

        data.forEach(app => {
            const row = `<tr>
                <td>${app.appointment_date}</td>
                <td>${app.appointment_time}</td>
                <td>${app.service_name}</td>
                <td>${app.status}</td>
            </tr>`;
            table.innerHTML += row;
        });
    })
    .catch(err => console.error("ERROR:", err));
}



//Load all appointments for admin
function loadAllAppointments() {
    fetch('get_all_appointments.php')
    .then(res => res.json())
    .then(data => {
        const table = document.getElementById('adminAppointmentsTable');
        table.innerHTML = '';

        data.forEach(app => {
            const row = `<tr>
                <td>${app.full_name}</td>
                <td>${app.service_name}</td>
                <td>${app.appointment_date}</td>
                <td>${app.appointment_time}</td>
                <td>${app.status}</td>
            </tr>`;
            table.innerHTML += row;
        });
    });
}



//Load doctors
function loadDoctors() {
    fetch('get_services.php')
    .then(res => res.json())
    .then(data => {
        const select = document.getElementById('doctor');

        // CLEAR first (IMPORTANT)
        select.innerHTML = '<option value="">Choose a doctor</option>';

        data.forEach(service => {
            const option = document.createElement('option');
            option.value = service.service_id;
            option.textContent = service.service_name;
            select.appendChild(option);
        });
    });
}


// Load admin queue
function loadAdminQueue() {
    fetch('get_queue.php')
    .then(res => res.json())
    .then(data => {

        const table = document.getElementById('adminQueueTable');
        table.innerHTML = '';

        data.forEach((item, index) => {

            const row = `
                <tr style="${item.status === 'serving' ? 'background-color: #d4edda;' : ''}">
                    <td>${item.queue_number}</td>
                    <td>${item.full_name}</td>
                    <td>${item.appointment_time}</td>
                    <td>${item.status}</td>
                </tr>
            `;

            table.innerHTML += row;
        });
    })
    .catch(err => console.error("Queue Error:", err));
}


// Load queue
function loadQueue() {
    fetch('get_queue.php')
    .then(res => res.json())
    .then(data => {
        const table = document.getElementById('queueTable');
        table.innerHTML = '';

        data.forEach(item => {
            const row = `<tr style="${item.status === 'serving' ? 'background-color: #d4edda;' : ''}">
                <td>${item.queue_number}</td>
                <td>${item.full_name}</td>
                <td>${item.appointment_time || '-'}</td>
                <td>${item.status}</td>
            </tr>`;
            table.innerHTML += row;
        });
    });
}



function loadMyQueue() {
    fetch('get_my_queue.php')
    .then(res => res.json())
    .then(data => {
        if (data) {
            document.getElementById('queuePosition').innerText = data.position;
            document.getElementById('waitTime').innerText = data.wait_time + " minutes";
        }
    });
}


// Notificaton pop-up
function showAlert(message) {
    const alertBox = document.getElementById('customAlert');
    const alertText = document.getElementById('alertMessage');

    alertText.textContent = message;
    alertBox.classList.add('show');

    setTimeout(() => {
        alertBox.classList.remove('show');
    }, 2000); // disappears after 2 seconds
}


// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {

    // LOGIN handled separately (ok)

    // SERVE NEXT
    const serveBtn = document.getElementById('serveNextBtn');
    if (serveBtn) {
        serveBtn.addEventListener('click', function () {
            fetch('serve_next.php')
            .then(res => res.text())
            .then(data => {
                if (data === "success") {
                    showAlert("Next customer is now being served");
                    loadAllAppointments?.();
                    loadQueue?.();
                } else if (data === "no_more_queue") {
                    showAlert("No more customers on appointments");
                } else {
    				console.log("Unexpected response:", data);
				}
            });
        });
    }

    // COOKIE
    const cookieNotice = document.getElementById("cookieNotice");
    const acceptBtn = document.getElementById("acceptCookies");

    if (cookieNotice && acceptBtn) {
        if (!localStorage.getItem("cookiesAccepted")) {
            cookieNotice.style.display = "block";
        }

        acceptBtn.addEventListener("click", function () {
            localStorage.setItem("cookiesAccepted", "true");
            cookieNotice.style.display = "none";
        });
    }

    // LOADERS
    if (document.getElementById('userAppointmentsTable')) {
        loadAppointments();
    }

    if (document.getElementById('adminAppointmentsTable')) {
        loadAllAppointments();
        setInterval(loadAllAppointments, 3000);
    }

    if (document.getElementById('queueTable')) {
        loadQueue();
    }

    if (document.getElementById('adminQueueTable')) {
        loadAdminQueue();
        setInterval(loadAdminQueue, 3000);
    }

    if (document.getElementById('queuePosition')) {
        loadMyQueue();
    }

    if (document.getElementById('doctor')) {
        loadDoctors();
    }

});