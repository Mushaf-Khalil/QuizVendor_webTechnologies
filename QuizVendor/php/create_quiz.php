<?php
// create_quiz.php

// Make sure db.php uses the correct $dbname = 'quizz_app'; (with double 'z' as you specified)
require 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Only POST requests are allowed']);
    exit;
}

// --- Input Handling ---
// In a real application, creator_user_id would come from the logged-in user's session.
// For now, we'll expect it from the POST data.
// Ensure your frontend sends 'creator_user_id' and optionally 'quiz_description'
$creator_user_id = isset($_POST['creator_user_id']) ? intval($_POST['creator_user_id']) : 0;
$quiz_title = isset($_POST['quiz_title']) ? trim($_POST['quiz_title']) : '';
$quiz_description = isset($_POST['quiz_description']) ? trim($_POST['quiz_description']) : null; // Optional, from new schema
$quiz_prompt_from_user = isset($_POST['quiz_prompt']) ? trim($_POST['quiz_prompt']) : '';

// Basic validation
if (!$creator_user_id || empty($quiz_title) || empty($quiz_prompt_from_user)) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Missing required fields: creator_user_id, quiz_title, or quiz_prompt.']);
    exit;
}

// IMPORTANT: Store your API key securely (e.g., environment variable, config file outside web root)
// DO NOT hardcode it directly in production code if possible.
$openai_api_key = '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'; // Replace with your actual key

// --- OpenAI API Call ---
$prompt_instruction = "You are a quiz generator. Based on the user request: '$quiz_prompt_from_user', generate a quiz.";
$json_format_instruction = "Format your response as a single, valid JSON object. Example:
{
  \"questions\": [
    {
      \"question_text\": \"Sample MCQ?\",
      \"question_type\": \"multiple_choice\",
      \"choices\": [
        {\"choice_text\": \"Opt A\", \"is_correct\": true},
        {\"choice_text\": \"Opt B\", \"is_correct\": false}
      ]
    },
    {
      \"question_text\": \"Sample short answer question?\",
      \"question_type\": \"short_answer\",
      \"correct_answer_text\": \"The exact correct answer.\"
    },
    {
      \"question_text\": \"Is this statement true?\",
      \"question_type\": \"true_false\",
      \"choices\": [
        {\"choice_text\": \"True\", \"is_correct\": true},
        {\"choice_text\": \"False\", \"is_correct\": false}
      ]
    }
  ]
}
Ensure JSON is well-formed. Number and types of questions should match user request. For multiple_choice and true_false, clearly mark one choice with \"is_correct\": true. Provide appropriate choices for true_false questions.";

$full_openai_prompt = $prompt_instruction . "\n\n" . $json_format_instruction;

$url = "https://api.openai.com/v1/chat/completions";
$data = [
    'model' => 'gpt-3.5-turbo',
    'messages' => [
        ['role' => 'system', 'content' => 'You are a helpful AI assistant that generates quizzes in JSON format, including multiple_choice, short_answer, and true_false types.'],
        ['role' => 'user', 'content' => $full_openai_prompt]
    ],
    'max_tokens' => 2500,
    'temperature' => 0.6,
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $openai_api_key,
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
curl_setopt($ch, CURLOPT_TIMEOUT, 120);

$response_body = curl_exec($ch);
$curl_error_num = curl_errno($ch);
$curl_error_msg = curl_error($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($curl_error_num > 0) {
    http_response_code(503); // Service Unavailable
    echo json_encode(['error' => 'OpenAI API request failed: ' . $curl_error_msg]);
    exit;
}

if ($http_code !== 200) {
    http_response_code($http_code);
    echo json_encode(['error' => 'OpenAI API returned HTTP status ' . $http_code, 'details' => json_decode($response_body, true)]);
    exit;
}

$responseData = json_decode($response_body, true);
$ai_output_json_string = $responseData['choices'][0]['message']['content'] ?? null;

if (!$ai_output_json_string) {
    http_response_code(500);
    echo json_encode(['error' => 'Invalid OpenAI API response structure (no content).', 'raw_response' => $responseData]);
    exit;
}

// Attempt to clean up JSON if it's wrapped in markdown
$ai_output_json_string = trim($ai_output_json_string);
if (preg_match('/```json\s*([\s\S]*?)\s*```/', $ai_output_json_string, $matches)) {
    $ai_output_json_string = $matches[1];
} elseif (strpos($ai_output_json_string, '{') !== 0 && strpos($ai_output_json_string, '[') !== 0) {
    $firstBrace = strpos($ai_output_json_string, '{');
    $firstBracket = strpos($ai_output_json_string, '[');
    if ($firstBrace !== false && ($firstBracket === false || $firstBrace < $firstBracket)) {
        $ai_output_json_string = substr($ai_output_json_string, $firstBrace);
    } elseif ($firstBracket !== false) {
        $ai_output_json_string = substr($ai_output_json_string, $firstBracket);
    }
}

$quizContent = json_decode($ai_output_json_string, true);

if ($quizContent === null || !isset($quizContent['questions']) || !is_array($quizContent['questions'])) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to parse AI output JSON or missing "questions" array.',
        'raw_ai_output_for_debugging' => $ai_output_json_string
    ]);
    exit;
}

// --- Database Operations ---
$conn->begin_transaction();
$generated_quiz_id = 0; // This will store quizzes.quiz_id

try {
    // Insert into quizzes table (uses creator_user_id, quiz_id)
    $stmtQuiz = $conn->prepare("INSERT INTO quizzes (creator_user_id, title, description) VALUES (?, ?, ?)");
    if (!$stmtQuiz) throw new Exception("Prepare failed (quizzes): " . $conn->error);
    $stmtQuiz->bind_param('iss', $creator_user_id, $quiz_title, $quiz_description);
    if (!$stmtQuiz->execute()) throw new Exception("Execute failed (quizzes): " . $stmtQuiz->error);
    $generated_quiz_id = $stmtQuiz->insert_id;
    if ($generated_quiz_id === 0) throw new Exception("Failed to get insert_id for quiz.");
    $stmtQuiz->close();

    // Prepare statements for questions and choices (using question_id, choice_id)
    $stmtQ = $conn->prepare("INSERT INTO questions (quiz_id, question_text, question_type, correct_short_answer, created_at) VALUES (?, ?, ?, ?, NOW())");
    if (!$stmtQ) throw new Exception("Prepare failed (questions): " . $conn->error);
    $stmtC = $conn->prepare("INSERT INTO choices (question_id, choice_text, is_correct, created_at) VALUES (?, ?, ?, NOW())");
    if (!$stmtC) throw new Exception("Prepare failed (choices): " . $conn->error);

    foreach ($quizContent['questions'] as $question_data_from_ai) {
        $question_text = $question_data_from_ai['question_text'] ?? 'N/A';
        $question_type = $question_data_from_ai['question_type'] ?? 'multiple_choice';
        $correct_short_answer = null;

        if ($question_type === 'short_answer') {
            $correct_short_answer = $question_data_from_ai['correct_answer_text'] ?? null;
        }

        $stmtQ->bind_param('isss', $generated_quiz_id, $question_text, $question_type, $correct_short_answer);
        if (!$stmtQ->execute()) throw new Exception("Execute failed (questions insert): " . $stmtQ->error . " | Text: " . $question_text);
        $generated_question_id = $stmtQ->insert_id; // This is questions.question_id
        if ($generated_question_id === 0) throw new Exception("Failed to get insert_id for question.");

        if (($question_type === 'multiple_choice' || $question_type === 'true_false') && isset($question_data_from_ai['choices']) && is_array($question_data_from_ai['choices'])) {
            foreach ($question_data_from_ai['choices'] as $choice_data_from_ai) {
                $choice_text = $choice_data_from_ai['choice_text'] ?? 'N/A';
                $is_correct_from_ai = (isset($choice_data_from_ai['is_correct']) && $choice_data_from_ai['is_correct'] === true) ? 1 : 0;

                $stmtC->bind_param('isi', $generated_question_id, $choice_text, $is_correct_from_ai);
                if (!$stmtC->execute()) throw new Exception("Execute failed (choices insert): " . $stmtC->error . " | Text: " . $choice_text);
            }
        }
    }
    $stmtQ->close();
    $stmtC->close();
    $conn->commit();

    // --- Fetch Quiz Structure for Client (without correct answers for quiz-taking) ---
    $quizDataForClient = [
        'quiz_id' => $generated_quiz_id, // quizzes.quiz_id
        'title' => $quiz_title,
        'description' => $quiz_description,
        'questions' => []
    ];

    // Fetch using questions.question_id
    $stmtFetchQuestions = $conn->prepare("SELECT question_id, question_text, question_type FROM questions WHERE quiz_id = ? ORDER BY question_id");
    if (!$stmtFetchQuestions) throw new Exception("Prepare failed (fetch questions): " . $conn->error);
    $stmtFetchQuestions->bind_param('i', $generated_quiz_id);
    $stmtFetchQuestions->execute();
    $resultQuestions = $stmtFetchQuestions->get_result();

    while ($questionRow = $resultQuestions->fetch_assoc()) {
        $clientQuestionData = [
            'question_id' => $questionRow['question_id'], // questions.question_id
            'question_text' => $questionRow['question_text'],
            'question_type' => $questionRow['question_type'],
            'choices' => []
        ];

        if ($questionRow['question_type'] === 'multiple_choice' || $questionRow['question_type'] === 'true_false') {
            // Fetch using choices.choice_id
            $stmtFetchChoices = $conn->prepare("SELECT choice_id, choice_text FROM choices WHERE question_id = ? ORDER BY choice_id");
            if (!$stmtFetchChoices) throw new Exception("Prepare failed (fetch choices): " . $conn->error);
            $stmtFetchChoices->bind_param('i', $questionRow['question_id']);
            $stmtFetchChoices->execute();
            $resultChoices = $stmtFetchChoices->get_result();
            while ($choiceRow = $resultChoices->fetch_assoc()) {
                $clientQuestionData['choices'][] = [
                    'choice_id' => $choiceRow['choice_id'], // choices.choice_id
                    'choice_text' => $choiceRow['choice_text']
                ];
            }
            $stmtFetchChoices->close();
        }
        $quizDataForClient['questions'][] = $clientQuestionData;
    }
    $stmtFetchQuestions->close();

    echo json_encode([
        'success' => true,
        'message' => 'Quiz created successfully!',
        'quiz_data' => $quizDataForClient
    ]);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    error_log("Error in create_quiz.php: " . $e->getMessage() . " | Raw AI Output (if available): " . ($ai_output_json_string ?? 'N/A'));
    echo json_encode(['error' => 'An error occurred while creating the quiz. Please check server logs.']);
    // For development debugging, you might expose more:
    // echo json_encode(['error' => 'Database operation failed: ' . $e->getMessage(), 'debug_ai_output' => $ai_output_json_string]);
}
?>