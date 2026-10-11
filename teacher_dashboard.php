<?php
require "config.php";
require_role("teacher");
$message = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = trim($_POST["title"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $questions = $_POST["question"] ?? [];
    $a = $_POST["option_a"] ?? []; $b = $_POST["option_b"] ?? [];
    $c = $_POST["option_c"] ?? []; $d = $_POST["option_d"] ?? [];
    $correct = $_POST["correct"] ?? [];
    if ($title === "") {
        $message = "Please enter a quiz title.";
    } else {
        $stmt = $conn->prepare("INSERT INTO quizzes (title, description, teacher_id) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $title, $description, $_SESSION["user_id"]);
        if ($stmt->execute()) {
            $quiz_id = $stmt->insert_id;
            $qstmt = $conn->prepare("INSERT INTO questions (quiz_id, question_text, option_a, option_b, option_c, option_d, correct_option) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $saved = 0;
            foreach ($questions as $i => $q) {
                $q = trim($q);
                $opts = [trim($a[$i] ?? ""), trim($b[$i] ?? ""), trim($c[$i] ?? ""), trim($d[$i] ?? "")];
                $key = strtoupper($correct[$i] ?? "");
                if ($q !== "" && !in_array("", $opts, true) && in_array($key, ["A","B","C","D"], true)) {
                    $qstmt->bind_param("issssss", $quiz_id, $q, $opts[0], $opts[1], $opts[2], $opts[3], $key);
                    $qstmt->execute(); $saved++;
                }
            }
            $message = "Quiz saved with " . $saved . " question(s).";
        } else { $message = "Could not save quiz."; }
    }
}
$quizzes = $conn->query("SELECT q.id, q.title, q.created_at, COUNT(ques.id) AS question_count FROM quizzes q LEFT JOIN questions ques ON ques.quiz_id=q.id WHERE q.teacher_id=" . (int)$_SESSION["user_id"] . " GROUP BY q.id ORDER BY q.id DESC");
$results = $conn->query("SELECT r.score, r.total, r.submitted_at, u.name AS student, q.title FROM results r JOIN users u ON u.id=r.student_id JOIN quizzes q ON q.id=r.quiz_id WHERE q.teacher_id=" . (int)$_SESSION["user_id"] . " ORDER BY r.id DESC LIMIT 20");
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Teacher Dashboard</title><link rel="stylesheet" href="style.css"></head>
<body><header><strong>Quiz Generator</strong><nav><span><?= htmlspecialchars($_SESSION["name"]) ?></span> · <a href="logout.php">Logout</a></nav></header>
<main class="wrap"><h1>Teacher Dashboard</h1>
<?php if ($message): ?><p class="notice"><?= htmlspecialchars($message) ?></p><?php endif; ?>
<section class="panel"><h2>Create a quiz</h2><form method="post">
<label>Quiz title</label><input name="title" required>
<label>Description</label><textarea name="description" rows="2"></textarea>
<div class="question-box"><h3>Question 1</h3>
<label>Question</label><input name="question[]" required>
<div class="grid">
<div><label>Option A</label><input name="option_a[]" required></div><div><label>Option B</label><input name="option_b[]" required></div>
<div><label>Option C</label><input name="option_c[]" required></div><div><label>Option D</label><input name="option_d[]" required></div>
</div><label>Correct option</label><select name="correct[]"><option>A</option><option>B</option><option>C</option><option>D</option></select>
</div><p class="muted small">This starter version accepts one question per quiz. More questions can be added in a later version.</p><button type="submit">Save Quiz</button>
</form></section>
<section class="panel"><h2>Your quizzes</h2><?php if ($quizzes && $quizzes->num_rows): ?><div class="table-wrap"><table><tr><th>Title</th><th>Questions</th><th>Created</th></tr><?php while ($q=$quizzes->fetch_assoc()): ?><tr><td><?= htmlspecialchars($q["title"]) ?></td><td><?= (int)$q["question_count"] ?></td><td><?= htmlspecialchars($q["created_at"]) ?></td></tr><?php endwhile; ?></table></div><?php else: ?><p class="muted">No quizzes created yet.</p><?php endif; ?></section>
<section class="panel"><h2>Recent student results</h2><?php if ($results && $results->num_rows): ?><div class="table-wrap"><table><tr><th>Student</th><th>Quiz</th><th>Score</th><th>Date</th></tr><?php while ($r=$results->fetch_assoc()): ?><tr><td><?= htmlspecialchars($r["student"]) ?></td><td><?= htmlspecialchars($r["title"]) ?></td><td><?= (int)$r["score"] ?>/<?= (int)$r["total"] ?></td><td><?= htmlspecialchars($r["submitted_at"]) ?></td></tr><?php endwhile; ?></table></div><?php else: ?><p class="muted">No results yet.</p><?php endif; ?></section>
</main></body></html>
