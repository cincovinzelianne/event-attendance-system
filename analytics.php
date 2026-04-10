<?php
require_once 'includes/auth.php';
require_once 'config/database.php';

$auth = new Auth();
$currentUser = $auth->getCurrentUser();

if (!$currentUser) {
    header('Location: signin.php');
    exit;
}

function h($v){ return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }

$profileIncomplete = false;
$profileCompleted = !empty($currentUser['profile_completed']) || (!empty($currentUser['course']) && !empty($currentUser['year_level']));
$hasCourse = !empty($currentUser['course']);
$hasYearLevel = !empty($currentUser['year_level']);
if (!$hasCourse || !$hasYearLevel || !$profileCompleted) {
    $profileIncomplete = true;
}

$message = '';
$messageType = '';
$studentEvents = [];
$submittedFeedback = [];

try {
    $database = new Database();
    $db = $database->getConnection();

    $db->exec("
        CREATE TABLE IF NOT EXISTS feedback_submissions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            event_id INT NULL,
            feedback_type VARCHAR(50) NOT NULL,
            rating INT NOT NULL,
            subject VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_feedback_student (student_id),
            INDEX idx_feedback_event (event_id)
        )
    ");

    $eventStmt = $db->prepare("
        SELECT id, event_name, event_date
        FROM events
        WHERE is_active = 1
        ORDER BY event_date DESC, id DESC
        LIMIT 20
    ");
    $eventStmt->execute();
    $studentEvents = $eventStmt->fetchAll(PDO::FETCH_ASSOC);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $feedbackType = trim($_POST['feedback_type'] ?? '');
        $rating = (int)($_POST['rating'] ?? 0);
        $subject = trim($_POST['subject'] ?? '');
        $feedbackMessage = trim($_POST['message'] ?? '');
        $eventIdRaw = trim($_POST['event_id'] ?? '');
        $eventId = ($eventIdRaw !== '') ? (int)$eventIdRaw : null;

        if ($feedbackType === '' || $rating < 1 || $rating > 5 || $subject === '' || $feedbackMessage === '') {
            $message = 'Please complete the feedback form before submitting.';
            $messageType = 'error';
        } else {
            $insert = $db->prepare("
                INSERT INTO feedback_submissions (student_id, event_id, feedback_type, rating, subject, message)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $insert->execute([
                (int)$currentUser['id'],
                $eventId,
                $feedbackType,
                $rating,
                $subject,
                $feedbackMessage
            ]);

            $message = 'Thank you. Your feedback has been submitted successfully.';
            $messageType = 'success';
            $_POST = [];
        }
    }

    $historyStmt = $db->prepare("
        SELECT f.subject, f.feedback_type, f.rating, f.created_at, e.event_name
        FROM feedback_submissions f
        LEFT JOIN events e ON e.id = f.event_id
        WHERE f.student_id = ?
        ORDER BY f.created_at DESC
        LIMIT 5
    ");
    $historyStmt->execute([(int)$currentUser['id']]);
    $submittedFeedback = $historyStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $message = 'Unable to load the feedback form right now.';
    $messageType = 'error';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback Form - Event Attendance</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            margin: 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f5f7fb;
            color: #0f172a;
            min-height: 100vh;
        }

        .feedback-container {
            max-width: 980px;
            margin: 0 auto;
            padding: 18px 16px 16px;
        }

        .page-topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 18px;
        }

        .back-link {
            display: inline-block;
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }

        .signout-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            border-radius: 10px;
            text-decoration: none;
            color: #b91c1c;
            font-weight: 600;
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .feedback-hero,
        .feedback-card,
        .history-card {
            background: white;
            border-radius: 18px;
            box-shadow: 0 14px 32px rgba(15, 23, 42, 0.10);
        }

        .feedback-hero {
            padding: 22px 18px;
            margin-bottom: 18px;
        }

        .feedback-title {
            font-size: 1.7rem;
            font-weight: 700;
            margin: 0 0 6px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .feedback-subtitle {
            color: #718096;
            font-size: 0.95rem;
            margin: 0;
        }

        .feedback-layout {
            display: grid;
            grid-template-columns: minmax(0, 1.4fr) minmax(280px, 0.8fr);
            gap: 18px;
        }

        .feedback-card,
        .history-card {
            padding: 20px 18px;
        }

        .section-title {
            margin: 0 0 16px;
            font-size: 1.05rem;
            font-weight: 700;
            color: #2d3748;
        }

        .feedback-message {
            padding: 12px 14px;
            border-radius: 12px;
            margin-bottom: 16px;
            font-size: 14px;
            font-weight: 600;
        }

        .feedback-message.success {
            background: #e6fffa;
            color: #047857;
            border: 1px solid #a7f3d0;
        }

        .feedback-message.error {
            background: #fff1f2;
            color: #be123c;
            border: 1px solid #fecdd3;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        .form-group label {
            font-size: 14px;
            font-weight: 600;
            color: #334155;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            border: 1px solid #dbe3f3;
            border-radius: 12px;
            padding: 12px 14px;
            font-size: 14px;
            font-family: inherit;
            background: #fbfcff;
            color: #0f172a;
        }

        .form-group textarea {
            min-height: 130px;
            resize: vertical;
        }

        .rating-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .rating-option {
            position: relative;
        }

        .rating-option input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .rating-option span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 46px;
            padding: 11px 12px;
            border-radius: 12px;
            border: 1px solid #dbe3f3;
            background: #fbfcff;
            color: #667eea;
            font-weight: 700;
            cursor: pointer;
        }

        .rating-option input:checked + span {
            background: linear-gradient(135deg, #667eea, #7c3aed);
            color: white;
            border-color: transparent;
            box-shadow: 0 12px 24px rgba(102, 126, 234, 0.22);
        }

        .submit-btn {
            border: none;
            border-radius: 14px;
            background: linear-gradient(135deg, #667eea, #7c3aed);
            color: white;
            padding: 13px 18px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .helper-card {
            padding: 14px 14px;
            border-radius: 14px;
            background: linear-gradient(135deg, rgba(102,126,234,0.08), rgba(124,58,237,0.08));
            color: #475569;
            font-size: 14px;
            line-height: 1.55;
            margin-bottom: 14px;
        }

        .history-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .history-item {
            border: 1px solid #e6ebf7;
            border-radius: 14px;
            padding: 14px;
            background: #fbfcff;
        }

        .history-item h4 {
            margin: 0 0 4px;
            font-size: 14px;
            color: #2d3748;
        }

        .history-meta {
            font-size: 12px;
            color: #7c8aa5;
            line-height: 1.5;
        }

        .history-empty {
            font-size: 14px;
            color: #7c8aa5;
        }

        @media (max-width: 768px) {
            .feedback-layout,
            .form-grid {
                grid-template-columns: 1fr;
            }

            .feedback-container {
                padding: 16px 14px 14px;
            }
        }
    </style>
</head>
<body>
    <div class="feedback-container">
        <div class="page-topbar">
            <a href="dashboard.php" class="back-link">&larr; Back to Dashboard</a>
            <a href="signout.php" class="signout-link"><i class="fas fa-right-from-bracket"></i> Sign out</a>
        </div>

        <div class="feedback-hero">
            <h1 class="feedback-title">Feedback Form</h1>
            <p class="feedback-subtitle">Share your experience, suggestions, and event feedback with the school team.</p>
        </div>

        <div class="feedback-layout">
            <section class="feedback-card">
                <h2 class="section-title">Submit Feedback</h2>

                <?php if ($message): ?>
                    <div class="feedback-message <?php echo h($messageType); ?>"><?php echo h($message); ?></div>
                <?php endif; ?>

                <?php if ($profileIncomplete): ?>
                    <div class="helper-card">
                        Complete your profile first so your feedback can be linked correctly to your student record.
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="feedback_type">Feedback Type</label>
                            <select id="feedback_type" name="feedback_type" required>
                                <option value="">Select type</option>
                                <option value="General" <?php echo (($_POST['feedback_type'] ?? '') === 'General') ? 'selected' : ''; ?>>General</option>
                                <option value="Event Experience" <?php echo (($_POST['feedback_type'] ?? '') === 'Event Experience') ? 'selected' : ''; ?>>Event Experience</option>
                                <option value="System Issue" <?php echo (($_POST['feedback_type'] ?? '') === 'System Issue') ? 'selected' : ''; ?>>System Issue</option>
                                <option value="Suggestion" <?php echo (($_POST['feedback_type'] ?? '') === 'Suggestion') ? 'selected' : ''; ?>>Suggestion</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="event_id">Related Event</label>
                            <select id="event_id" name="event_id">
                                <option value="">No specific event</option>
                                <?php foreach ($studentEvents as $event): ?>
                                    <option value="<?php echo (int)$event['id']; ?>" <?php echo (($_POST['event_id'] ?? '') == $event['id']) ? 'selected' : ''; ?>>
                                        <?php echo h($event['event_name'] . ' - ' . date('M d, Y', strtotime($event['event_date']))); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group full">
                            <label>Rating</label>
                            <div class="rating-group">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <label class="rating-option">
                                        <input type="radio" name="rating" value="<?php echo $i; ?>" <?php echo (($_POST['rating'] ?? '') == (string)$i) ? 'checked' : ''; ?> required>
                                        <span><?php echo $i; ?> <i class="fas fa-star"></i></span>
                                    </label>
                                <?php endfor; ?>
                            </div>
                        </div>

                        <div class="form-group full">
                            <label for="subject">Subject</label>
                            <input type="text" id="subject" name="subject" maxlength="255" required value="<?php echo h($_POST['subject'] ?? ''); ?>" placeholder="Enter a short subject">
                        </div>

                        <div class="form-group full">
                            <label for="message">Message</label>
                            <textarea id="message" name="message" required placeholder="Write your feedback here..."><?php echo h($_POST['message'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <button type="submit" class="submit-btn">
                        <i class="fas fa-paper-plane"></i>
                        Submit Feedback
                    </button>
                </form>
            </section>

            <aside class="history-card">
                <h2 class="section-title">Recent Submissions</h2>

                <div class="helper-card">
                    Use this form to share system concerns, event experience, or suggestions for improvement.
                </div>

                <?php if (empty($submittedFeedback)): ?>
                    <div class="history-empty">You have not submitted any feedback yet.</div>
                <?php else: ?>
                    <div class="history-list">
                        <?php foreach ($submittedFeedback as $item): ?>
                            <div class="history-item">
                                <h4><?php echo h($item['subject']); ?></h4>
                                <div class="history-meta">
                                    Type: <?php echo h($item['feedback_type']); ?><br>
                                    Rating: <?php echo (int)$item['rating']; ?>/5<br>
                                    <?php if (!empty($item['event_name'])): ?>Event: <?php echo h($item['event_name']); ?><br><?php endif; ?>
                                    Submitted: <?php echo h(date('M d, Y g:i A', strtotime($item['created_at']))); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </aside>
        </div>
    </div>

    <?php include 'includes/bottom_navbar.php'; ?>
</body>
</html>
