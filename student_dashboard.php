<?php
require "config.php";
require_role("student");
$message = "";
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["quiz_id"])) {
    $quiz_id = (int)$_POST["quiz_id"];
    $stmt = $conn->prepare("SELECT id, correct_option FROM questions WHERE quiz_id=?");
    $stmt->bind_param("i", $quiz_id); $stmt->execute(); $res = $stmt->get_result();
    $score=0; $total=0;
    while ($q=$res->fetch_assoc()) {
        $total++;
        $answer = strtoupper($_POST["answer"][$q["id"]] ?? "");
        if ($answer === $q["correct_option"]) $score++;
    }
    if ($total > 0) {
        $save = $conn->prepare("INSERT INTO results (quiz_id, student_id, score, total) VALUES (?, ?, ?, ?)");
        $save->bind_param("iiii", $quiz_id, $_SESSION["user_id"], $score, $total);
        $save->execute();
        $message = "Your score: $score / $total";
    } else { $message = "This quiz has no questions yet."; }
}
$quizzes = $conn->query("SELECT q.id, q.title, q.description, u.name AS teacher FROM quizzes q JOIN users u ON u.id=q.teacher_id ORDER BY q.id DESC");
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Student Dashboard</title><link rel="stylesheet" href="style.css"></head>
<body><header><strong>Quiz Generator</strong><nav><span><?= htmlspecialchars($_SESSION["name"]) ?></span> · <a href="logout.php">Logout</a></nav></header>
<main class="wrap"><h1>Student Dashboard</h1><?php if ($message): ?><p class="notice"><?= htmlspecialchars($message) ?></p><?php endif; ?>
<?php if ($quizzes && $quizzes->num_rows): while ($quiz=$quizzes->fetch_assoc()): ?>
<section class="panel"><h2><?= htmlspecialchars($quiz["title"]) ?></h2><p><?= htmlspecialchars($quiz["description"] ?? "") ?></p><p class="muted small">Teacher: <?= htmlspecialchars($quiz["teacher"]) ?></p>
<?php $stmt=$conn->prepare("SELECT id, question_text, option_a, option_b, option_c, option_d FROM questions WHERE quiz_id=?"); $stmt->bind_param("i",$quiz["id"]); $stmt->execute(); $qs=$stmt->get_result(); ?>
<?php if ($qs->num_rows): ?><form method="post"><input type="hidden" name="quiz_id" value="<?= (int)$quiz["id"] ?>">
<?php while ($q=$qs->fetch_assoc()): ?><fieldset><legend><?= htmlspecialchars($q["question_text"]) ?></legend>
<?php foreach (["A"=>"option_a","B"=>"option_b","C"=>"option_c","D"=>"option_d"] as $letter=>$field): ?><label class="option"><input type="radio" name="answer[<?= (int)$q["id"] ?>]" value="<?= $letter ?>" required> <?= $letter ?>. <?= htmlspecialchars($q[$field]) ?></label><?php endforeach; ?>
</fieldset><?php endwhile; ?><button type="submit">Submit answers</button></form><?php else: ?><p class="muted">No questions available.</p><?php endif; ?></section>
<?php endwhile; else: ?><section class="panel"><p>No quizzes are available yet.</p></section><?php endif; ?>
</main></body></html>
