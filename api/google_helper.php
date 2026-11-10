<?php
function g_config() {
    static $c;
    return $c ??= require __DIR__ . "/../config/google.php";
}

function g_http($url, $method = "GET", $body = null, $token = null, $json = true) {
    $headers = [];
    if ($token) $headers[] = "Authorization: Bearer $token";
    if ($json && $body !== null) {
        $headers[] = "Content-Type: application/json";
        $body = json_encode($body);
    }
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_TIMEOUT        => 15,
    ]);
    if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$code, json_decode((string)$res, true)];
}

function g_access_token($conn, $uid) {
    $stmt = $conn->prepare("SELECT google_refresh_token FROM users WHERE id = ?");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row || !$row["google_refresh_token"]) return null;

    $c = g_config();
    [$code, $res] = g_http("https://oauth2.googleapis.com/token", "POST", http_build_query([
        "client_id"     => $c["client_id"],
        "client_secret" => $c["client_secret"],
        "refresh_token" => $row["google_refresh_token"],
        "grant_type"    => "refresh_token",
    ]), null, false);

    if (($res["error"] ?? "") === "invalid_grant") {   // access was revoked or expired
        $clr = $conn->prepare("UPDATE users SET google_refresh_token = NULL WHERE id = ?");
        $clr->bind_param("i", $uid);
        $clr->execute();
        return null;
    }
    return $res["access_token"] ?? null;
}

function g_event_body($t) {
    $tz = g_config()["timezone"];
    $body = [
        "summary"     => $t["title"],
        "description" => trim(($t["desc"] ?? "") . "\n(Due It Now)"),
        "reminders"   => ["useDefault" => false, "overrides" => [
            ["method" => "popup", "minutes" => 60],
            ["method" => "popup", "minutes" => 1440],
        ]],
    ];
    if (!empty($t["time"])) {
        $s = new DateTime($t["due"] . " " . substr($t["time"], 0, 5), new DateTimeZone($tz));
        $e = (clone $s)->modify("+1 hour");
        $body["start"] = ["dateTime" => $s->format("Y-m-d\TH:i:s"), "timeZone" => $tz];
        $body["end"]   = ["dateTime" => $e->format("Y-m-d\TH:i:s"), "timeZone" => $tz];
    } else {
        $s = new DateTime($t["due"]);
        $e = (clone $s)->modify("+1 day");
        $body["start"] = ["date" => $s->format("Y-m-d")];
        $body["end"]   = ["date" => $e->format("Y-m-d")];
    }
    return $body;
}

const G_EVENTS = "https://www.googleapis.com/calendar/v3/calendars/primary/events";

function g_event_id($conn, $uid, $taskId) {
    $stmt = $conn->prepare("SELECT gcal_event_id FROM tasks WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $taskId, $uid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row["gcal_event_id"] ?? null;
}

function g_set_event_id($conn, $uid, $taskId, $eventId) {
    $stmt = $conn->prepare("UPDATE tasks SET gcal_event_id = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("sii", $eventId, $taskId, $uid);
    $stmt->execute();
}

// Create/update the Google event for a task. Removes it if the task is done or has no date.
// Never throws: a Google problem must not stop the task from saving.
function g_sync_task($conn, $uid, $taskId, $t, $status) {
    try {
        $token = g_access_token($conn, $uid);
        if (!$token) return;
        $eventId = g_event_id($conn, $uid, $taskId);

        if ($status === "completed" || empty($t["due"])) {
            if ($eventId) {
                g_http(G_EVENTS . "/" . $eventId, "DELETE", null, $token);
                g_set_event_id($conn, $uid, $taskId, null);
            }
            return;
        }

        $body = g_event_body($t);
        if ($eventId) {
            [$code] = g_http(G_EVENTS . "/" . $eventId, "PATCH", $body, $token);
            if ($code === 404 || $code === 410) $eventId = null;   // deleted in Google, recreate
            else return;
        }
        [$code, $res] = g_http(G_EVENTS, "POST", $body, $token);
        if (!empty($res["id"])) g_set_event_id($conn, $uid, $taskId, $res["id"]);
    } catch (Throwable $e) {
        // ignore
    }
}

function g_sync_delete($conn, $uid, $taskId) {
    try {
        $eventId = g_event_id($conn, $uid, $taskId);
        if (!$eventId) return;
        $token = g_access_token($conn, $uid);
        if ($token) g_http(G_EVENTS . "/" . $eventId, "DELETE", null, $token);
    } catch (Throwable $e) {
        // ignore
    }
}