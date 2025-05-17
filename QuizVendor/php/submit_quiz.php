<?php
// php/submit_quiz.php
session_start(); // Start session to potentially get taker_user_id if already logged in
require 'db.php'; // Ensure db.php is in the parent directory (e.g., Project/)

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Only POST requests are allowed']);
    exit;
}

// Read JSON input from the request body
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, TRUE); // TRUE for associative array

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Invalid JSON payload: ' . json_last_error_msg()]);
    exit;
}

// --- Input Validation ---
$quiz_id = isset($input['quiz_id']) ? intval($input['quiz_id']) : 0;
// IMPORTANT: In a real application, get taker_user_id from the session after login
$taker_user_id = $_SESSION['user_id'] ?? 0;
// For now, we take it from the input, as per your current JS
$taker_user_id = isset($input['taker_user_id']) ? intval($input['taker_user_id']) : 0;
$user_submitted_answers = isset($input['answers']) && is_array($input['answers']) ? $input['answers'] : [];

if (!$quiz_id || !$taker_user_id || empty($user_submitted_answers)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing quiz_id, taker_user_id, or answers.']);
    exit;
}

// --- Database Operations ---
$conn->begin_transaction();
$attempt_id = 0;
$total_questions_in_submission = count($user_submitted_answers);
$total_correct_user_answers = 0;
$graded_answers_details = [];

try {
    // 1. Create a quiz attempt record in 'quiz_attempts'
    $stmtAttempt = $conn->prepare("INSERT INTO quiz_attempts (quiz_id, taker_user_id, total_questions_answered) VALUES (?, ?, ?)");
    if (!$stmtAttempt) throw new Exception("Prepare failed (quiz_attempts): " . $conn->error);
    $stmtAttempt->bind_param("iii", $quiz_id, $taker_user_id, $total_questions_in_submission);
    if (!$stmtAttempt->execute()) throw new Exception("Execute failed (quiz_attempts): " . $stmtAttempt->error);
    $attempt_id = $stmtAttempt->insert_id;
    if ($attempt_id === 0) throw new Exception("Failed to get insert_id for quiz_attempt.");
    $stmtAttempt->close();

    // Prepare statements for fetching question details and choice correctness, and saving user answers
    $stmtSaveUserAnswer = $conn->prepare("INSERT INTO user_answers (attempt_id, question_id, selected_choice_id, short_answer_text, is_correct) VALUES (?, ?, ?, ?, ?)");
    if (!$stmtSaveUserAnswer) throw new Exception("Prepare failed (user_answers): " . $conn->error);

    $stmtGetQuestionInfo = $conn->prepare("SELECT question_type, correct_short_answer FROM questions WHERE question_id = ?");
    if (!$stmtGetQuestionInfo) throw new Exception("Prepare failed (get question info): " . $conn->error);

    $stmtGetChoiceInfo = $conn->prepare("SELECT is_correct, choice_text FROM choices WHERE choice_id = ? AND question_id = ?");
    if (!$stmtGetChoiceInfo) throw new Exception("Prepare failed (get choice info): " . $conn->error);
    
    $stmtGetCorrectChoiceText = $conn->prepare("SELECT choice_text FROM choices WHERE question_id = ? AND is_correct = 1 LIMIT 1");
    if (!$stmtGetCorrectChoiceText) throw new Exception("Prepare failed (get correct choice text): " . $conn->error);


    // 2. Process and grade each answer
    foreach ($user_submitted_answers as $user_answer) {
        $question_id = isset($user_answer['question_id']) ? intval($user_answer['question_id']) : 0;
        if ($question_id === 0) continue; // Skip if question_id is invalid

        $submitted_choice_id = isset($user_answer['selected_choice_id']) ? intval($user_answer['selected_choice_id']) : null;
        $submitted_short_answer_text = isset($user_answer['short_answer_text']) ? trim($user_answer['short_answer_text']) : null;
        $is_this_answer_correct_bool = false; // Boolean for logic
        $correct_answer_display_text = null; // For showing the user the correct answer if they were wrong

        // Fetch question type and correct short answer (if applicable)
        $stmtGetQuestionInfo->bind_param("i", $question_id);
        $stmtGetQuestionInfo->execute();
        $questionResult = $stmtGetQuestionInfo->get_result();
        $questionDetails = $questionResult->fetch_assoc();

        if (!$questionDetails) {
            // Log this server-side, question might have been deleted or ID is wrong
            error_log("Question not found in DB: question_id = " . $question_id . " for attempt_id = " . $attempt_id);
            $graded_answers_details[] = ['question_id' => $question_id, 'is_correct' => false, 'user_answer' => $user_answer, 'correct_answer_display' => 'Question details not found.'];
            // Save a record indicating an issue or skip
            $is_correct_int_for_db = 0;
            $stmtSaveUserAnswer->bind_param("iiisi", $attempt_id, $question_id, $submitted_choice_id, $submitted_short_answer_text, $is_correct_int_for_db);
            $stmtSaveUserAnswer->execute();
            continue;
        }
        $question_type = $questionDetails['question_type'];

        // Grade the answer
        if ($question_type === 'multiple_choice' || $question_type === 'true_false') {
            if ($submitted_choice_id !== null) {
                $stmtGetChoiceInfo->bind_param("ii", $submitted_choice_id, $question_id);
                $stmtGetChoiceInfo->execute();
                $choiceResult = $stmtGetChoiceInfo->get_result();
                $choiceDetails = $choiceResult->fetch_assoc();

                if ($choiceDetails && $choiceDetails['is_correct'] == 1) {
                    $is_this_answer_correct_bool = true;
                } else {
                    // Fetch the text of the actual correct choice to display to the user
                    $stmtGetCorrectChoiceText->bind_param("i", $question_id);
                    $stmtGetCorrectChoiceText->execute();
                    $correctChoiceTextResult = $stmtGetCorrectChoiceText->get_result()->fetch_assoc();
                    if ($correctChoiceTextResult) {
                        $correct_answer_display_text = $correctChoiceTextResult['choice_text'];
                    }
                }
            } else {
                 // User did not select an answer for an MCQ/TF question
                 // Fetch the text of the actual correct choice to display to the user
                 $stmtGetCorrectChoiceText->bind_param("i", $question_id);
                 $stmtGetCorrectChoiceText->execute();
                 $correctChoiceTextResult = $stmtGetCorrectChoiceText->get_result()->fetch_assoc();
                 if ($correctChoiceTextResult) {
                     $correct_answer_display_text = $correctChoiceTextResult['choice_text'];
                 }
            }
        } elseif ($question_type === 'short_answer') {
            $db_correct_short_answer = $questionDetails['correct_short_answer'];
            $correct_answer_display_text = $db_correct_short_answer; 
            if ($submitted_short_answer_text !== null && $db_correct_short_answer !== null) {
                // Simple case-insensitive comparison, after trimming whitespace
                if (strtolower(trim($submitted_short_answer_text)) === strtolower(trim($db_correct_short_answer))) {
                    $is_this_answer_correct_bool = true;
                }
            }
        }

        if ($is_this_answer_correct_bool) {
            $total_correct_user_answers++;
        }

        // Save user's answer with its correctness (0 or 1 for boolean in DB)
        $is_correct_int_for_db = $is_this_answer_correct_bool ? 1 : 0;
        $stmtSaveUserAnswer->bind_param("iiisi", $attempt_id, $question_id, $submitted_choice_id, $submitted_short_answer_text, $is_correct_int_for_db);
        if (!$stmtSaveUserAnswer->execute()) throw new Exception("Execute failed (user_answers insert): " . $stmtSaveUserAnswer->error);
        
        $graded_answers_details[] = [
            'question_id' => $question_id,
            'is_correct' => $is_this_answer_correct_bool,
            'user_answer' => $user_answer, // Contains what user submitted
            'correct_answer_display' => $is_this_answer_correct_bool ? null : $correct_answer_display_text
        ];
    }

    // Close prepared statements used in loop
    $stmtSaveUserAnswer->close();
    $stmtGetQuestionInfo->close();
    $stmtGetChoiceInfo->close();
    $stmtGetCorrectChoiceText->close();

    // 3. Calculate final score and update 'quiz_attempts' table
    $score_percentage = ($total_questions_in_submission > 0) ? ($total_correct_user_answers / $total_questions_in_submission) * 100 : 0;
    $score_percentage_rounded = round($score_percentage, 2);

    $stmtUpdateAttempt = $conn->prepare("UPDATE quiz_attempts SET score = ?, total_correct_answers = ? WHERE attempt_id = ?");
    if (!$stmtUpdateAttempt) throw new Exception("Prepare failed (update quiz_attempts): " . $conn->error);
    $stmtUpdateAttempt->bind_param("dii", $score_percentage_rounded, $total_correct_user_answers, $attempt_id);
    if (!$stmtUpdateAttempt->execute()) throw new Exception("Execute failed (update quiz_attempts): " . $stmtUpdateAttempt->error);
    $stmtUpdateAttempt->close();

    $conn->commit(); // Commit transaction if all went well

    // --- Send results back to client ---
    echo json_encode([
        'success' => true,
        'message' => 'Quiz submitted and graded successfully.',
        'results' => [
            'attempt_id' => $attempt_id,
            'quiz_id' => $quiz_id,
            'taker_user_id' => $taker_user_id,
            'score_percentage' => $score_percentage_rounded,
            'total_questions_answered' => $total_questions_in_submission,
            'total_correct_answers' => $total_correct_user_answers,
            'graded_answers' => $graded_answers_details // Detailed breakdown
        ]
    ]);

} catch (Exception $e) {
    $conn->rollback(); // Rollback transaction on error
    http_response_code(500); // Internal Server Error
    // Log detailed error on server for administrators
    error_log("Error in submit_quiz.php: " . $e->getMessage() . " | Input: " . $inputJSON);
    echo json_encode(['error' => 'An error occurred while submitting your quiz. Please try again.']);
    // For development, you might want to see the specific error:
    // echo json_encode(['error' => 'Submission failed: ' . $e->getMessage()]);
}

$conn->close();
?>