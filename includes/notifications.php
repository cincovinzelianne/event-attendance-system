<?php
/**
 * Student-side in-app notifications when attendance is clocked successfully.
 * Writes to student_notifications (see student_notifications.sql).
 * Attendance notifications are keyed by action (time_in / time_out) and deduped
 * so a single scan action does not create multiple visible notifications.
 */

/**
 * Queue a notification after a successful time in / time out (or second session).
 *
 * @param PDO         $db
 * @param int         $studentPkId students.id (primary key), matches attendance.student_id
 * @param int         $eventId     matches attendance.event_id
 * @param string      $field       one of time_in, time_out, time_in_2, time_out_2
 * @param string|null $recordedAt  datetime string that was stored, or null for "now"
 */
function notifyStudentAttendanceAction(PDO $db, $studentPkId, $eventId, $field, $recordedAt = null) {
    static $hasTable = null;
    if ($hasTable === null) {
        try {
            $hasTable = (bool) $db->query("SHOW TABLES LIKE 'student_notifications'")->fetch(PDO::FETCH_NUM);
        } catch (Exception $e) {
            $hasTable = false;
        }
    }
    if (!$hasTable) {
        return;
    }

    $allowed = ['time_in', 'time_out', 'time_in_2', 'time_out_2'];
    if (!in_array($field, $allowed, true)) {
        return;
    }

    $studentPkId = (int) $studentPkId;
    $eventId = (int) $eventId;
    if ($studentPkId < 1 || $eventId < 1) {
        return;
    }

    if ($recordedAt === null || $recordedAt === '') {
        $recordedAt = date('Y-m-d H:i:s');
    }
    $ts = strtotime($recordedAt);
    $displayTime = $ts ? date('g:i A', $ts) : $recordedAt;

    $eventName = 'your event';
    try {
        $ev = $db->prepare('SELECT event_name FROM events WHERE id = ?');
        $ev->execute([$eventId]);
        $row = $ev->fetch(PDO::FETCH_ASSOC);
        if ($row && !empty($row['event_name'])) {
            $eventName = $row['event_name'];
        }
    } catch (Exception $e) {
        // keep default label
    }

    switch ($field) {
        case 'time_in':
            $title = 'Time in recorded';
            $body = sprintf('You timed in at %s for "%s".', $displayTime, $eventName);
            break;
        case 'time_out':
            $title = 'Time out recorded';
            $body = sprintf('You timed out at %s for "%s".', $displayTime, $eventName);
            break;
        case 'time_in_2':
            $title = 'Second time in recorded';
            $body = sprintf('Second session: time in at %s for "%s".', $displayTime, $eventName);
            break;
        case 'time_out_2':
            $title = 'Second time out recorded';
            $body = sprintf('Second session: time out at %s for "%s".', $displayTime, $eventName);
            break;
    }

    try {
        // Prevent duplicate notifications for the same scan action from stacking.
        // This covers repeated AJAX submissions or rapid retries from the scanner UI.
        $dedupe = $db->prepare(
            'SELECT id
             FROM student_notifications
             WHERE student_id = ?
               AND event_id = ?
               AND action_key = ?
               AND title = ?
               AND body = ?
               AND created_at >= DATE_SUB(NOW(), INTERVAL 2 MINUTE)
             ORDER BY id DESC
             LIMIT 1'
        );
        $dedupe->execute([$studentPkId, $eventId, $field, $title, $body]);
        $existingId = $dedupe->fetchColumn();

        if ($existingId) {
            return;
        }

        $ins = $db->prepare(
            'INSERT INTO student_notifications (student_id, event_id, action_key, title, body) VALUES (?, ?, ?, ?, ?)'
        );
        $ins->execute([$studentPkId, $eventId, $field, $title, $body]);
    } catch (Exception $e) {
        // Do not break attendance recording if notification insert fails
    }
}
