<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

require_role('customer');
$conn = getDB();

$services = mysqli_fetch_all(
    mysqli_query($conn, "SELECT * FROM services ORDER BY service_name ASC"),
    MYSQLI_ASSOC
);

render_header('Book Appointment', 'customer');
render_flash();
?>
    <h2>Book a new appointment</h2>

    <form id="appointmentForm" action="save-appointment.php" method="POST">
        <label for="doctor">Doctor:</label>
        <select id="doctor" name="doctor" required>
            <option value="">Choose a doctor</option>
            <?php foreach ($services as $service) { ?>
                <option value="<?= (int) $service['service_id'] ?>">
                    <?= h($service['service_name']) ?> (<?= h(money($service['fee'])) ?>)
                </option>
            <?php } ?>
        </select>

        <label for="date">Date:</label>
        <input type="date" id="date" name="date" min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>" required>

        <label>Available time slots:</label>
        <div id="slotList" class="grid muted">Select a doctor and date to see open slots.</div>
        <input type="hidden" id="time" name="time" required>

        <label for="reason">Reason for visit:</label>
        <input type="text" id="reason" name="reason" placeholder="e.g. persistent cough">

        <button type="submit">Book appointment</button>
    </form>

    <script>
    const doctorSelect = document.getElementById('doctor');
    const dateInput = document.getElementById('date');
    const slotList = document.getElementById('slotList');
    const timeInput = document.getElementById('time');

    function loadSlots() {
        timeInput.value = '';

        if (!doctorSelect.value || !dateInput.value) {
            slotList.textContent = 'Select a doctor and date to see open slots.';
            return;
        }

        fetch(`get_slots.php?service_id=${doctorSelect.value}&date=${dateInput.value}`)
            .then(res => res.json())
            .then(slots => {
                slotList.innerHTML = '';

                const open = slots.filter(slot => slot.available);
                if (!open.length) {
                    slotList.textContent = 'No open slots for this day.';
                    return;
                }

                open.forEach(slot => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.textContent = slot.label;
                    button.addEventListener('click', () => {
                        timeInput.value = slot.time;
                        slotList.querySelectorAll('button').forEach(b => b.style.outline = '');
                        button.style.outline = '3px solid #0b5ed7';
                    });
                    slotList.appendChild(button);
                });
            });
    }

    doctorSelect.addEventListener('change', loadSlots);
    dateInput.addEventListener('change', loadSlots);

    document.getElementById('appointmentForm').addEventListener('submit', function (e) {
        if (!timeInput.value) {
            e.preventDefault();
            alert('Please pick a time slot.');
        }
    });
    </script>
<?php
render_footer();
?>
