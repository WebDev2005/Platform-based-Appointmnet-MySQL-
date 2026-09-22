#!/usr/bin/env bash
# End-to-end workflow check against a running server (default http://127.0.0.1:8080).
# Usage: bash smoke.sh
set -euo pipefail

BASE="${BASE:-http://127.0.0.1:8080}"
TMP=$(mktemp -d)
PASS="password123"
STAMP=$(date +%s)
PATIENT_EMAIL="smoke+$STAMP@clinic.test"

login() { # login <cookie-file> <email>
    curl -s -c "$1" -d "username=$2&password=$PASS" "$BASE/login.php"
}

expect() { # expect <needle> <haystack> <label>
    if ! grep -q "$2" <<<"$3"; then
        echo "FAIL: $1 (expected '$2')"
        exit 1
    fi
    echo "ok: $1"
}

echo "== patient self-registration"
curl -s -c "$TMP/patient" -o /dev/null \
    -d "full_name=Smoke Tester&email=$PATIENT_EMAIL&password=$PASS&phone=0917-000-0000" \
    "$BASE/register.php"

SERVICE_ID=$(curl -s -b "$TMP/patient" "$BASE/get_services.php" | python3 -c 'import sys,json;print(json.load(sys.stdin)[0]["service_id"])')
echo "ok: registered, booking with service $SERVICE_ID"

echo "== available slots"
DATE=$(date -d "+1 day" +%F)
SLOT=$(curl -s -b "$TMP/patient" "$BASE/get_slots.php?service_id=$SERVICE_ID&date=$DATE" \
    | python3 -c 'import sys,json;print([s["time"] for s in json.load(sys.stdin) if s["available"]][0])')
echo "ok: first open slot $DATE $SLOT"

echo "== booking"
curl -s -b "$TMP/patient" -o /dev/null -d "doctor=$SERVICE_ID&date=$DATE&time=$SLOT&reason=Smoke+test" "$BASE/save-appointment.php"
DASH=$(curl -s -b "$TMP/patient" "$BASE/dashboard.php")
expect "appointment appears on patient dashboard" "Smoke Tester" "$DASH"
expect "status is booked" "Booked" "$DASH"

echo "== staff confirms arrival"
login "$TMP/staff" "staff@clinic.test" > /dev/null
FRONT=$(curl -s -b "$TMP/staff" "$BASE/staff-dashboard.php?date=$DATE")
APPT=$(grep -o 'name="appointment_id" value="[0-9]*"' <<<"$FRONT" | tail -1 | grep -o '[0-9]\+')
curl -s -b "$TMP/staff" -o /dev/null -d "appointment_id=$APPT&return_to=staff-dashboard.php?date=$DATE" "$BASE/confirm_arrival.php"
expect "queue number issued" "Queue number" "$(curl -s -b "$TMP/staff" "$BASE/staff-dashboard.php?date=$DATE")"

echo "== vitals"
curl -s -b "$TMP/staff" -o /dev/null \
    -d "appointment_id=$APPT&temperature=37.8&blood_pressure=120/80&pulse=88&respiratory=18&weight_kg=60&height_cm=165&notes=stable" \
    "$BASE/vitals.php"

echo "== consultation, prescription, checkout"
login "$TMP/doctor" "doctor@clinic.test" > /dev/null
CHART=$(curl -s -b "$TMP/doctor" "$BASE/consultation.php?appointment_id=$APPT")
expect "vitals visible to physician" "120/80" "$CHART"
curl -s -b "$TMP/doctor" -o /dev/null \
    -d "appointment_id=$APPT&action=save_notes&chief_complaint=Cough&diagnosis=Acute+bronchitis&notes=Rest&follow_up_date=$(date -d '+7 days' +%F)" \
    "$BASE/consultation.php"
curl -s -b "$TMP/doctor" -o /dev/null \
    -d "appointment_id=$APPT&action=add_prescription&drug_name=Amoxicillin&dosage=500+mg&frequency=3x+a+day&duration=7+days" \
    "$BASE/consultation.php"
curl -s -b "$TMP/doctor" -o /dev/null -d "appointment_id=$APPT&action=complete" "$BASE/consultation.php"

echo "== billing"
login "$TMP/admin" "admin@clinic.test" > /dev/null
BILLING=$(curl -s -b "$TMP/admin" "$BASE/admin-billing.php")
expect "invoice created on checkout" "Smoke Tester" "$BILLING"
INVOICE=$(grep -o 'name="invoice_id" value="[0-9]*"' <<<"$BILLING" | tail -1 | grep -o '[0-9]\+')
curl -s -b "$TMP/admin" -o /dev/null -d "action=mark_paid&invoice_id=$INVOICE&payment_method=cash" "$BASE/admin-billing.php"
expect "invoice paid" "Paid" "$(curl -s -b "$TMP/admin" "$BASE/admin-billing.php")"

echo "== follow-ups, archive, reports"
expect "follow-up tracked" "Smoke Tester" "$(curl -s -b "$TMP/admin" "$BASE/admin-followups.php")"
expect "closed visit archivable" "Acute bronchitis" "$(curl -s -b "$TMP/admin" "$BASE/archive.php?show=open")"
expect "report renders" "Completion rate" "$(curl -s -b "$TMP/admin" "$BASE/admin-reports.php")"
expect "csv export" "Clinic summary report" "$(curl -s -b "$TMP/admin" "$BASE/report_export.php")"

echo "== patient record"
expect "patient sees diagnosis" "Acute bronchitis" "$(curl -s -b "$TMP/patient" "$BASE/my-records.php")"
expect "patient sees prescription" "Amoxicillin" "$(curl -s -b "$TMP/patient" "$BASE/my-records.php")"

rm -rf "$TMP"
echo "SMOKE TEST OK"
