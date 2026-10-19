<?php
header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

// Ensure user is an authenticated student
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'student') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized: Student access required.']);
    exit();
}

if (!isset($_SESSION['student_id_number'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Student record not found in active session.']);
    exit();
}

$student = Student::findByStudentNumber($pdo, $_SESSION['student_id_number']);
if (!$student) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Student profile not located in database.']);
    exit();
}

// Fetch results & enrollment
$activeEnrollment = $student->getActiveEnrollment($pdo);
$moduleResults = $student->getModuleResults($pdo);

$totalAverageSum = 0;
$resultCount = 0;
foreach ($moduleResults as $res) {
    if ($res['average'] !== null) {
        $totalAverageSum += (float)$res['average'];
        $resultCount++;
    }
}
$overallAverage = $resultCount > 0 ? round($totalAverageSum / $resultCount, 2) : null;

$studentContext = [
    'name' => $student->getFullName(),
    'student_number' => $student->getStudentNumber() ?: $student->getStudentIdNumber(),
    'course' => is_array($activeEnrollment) ? ($activeEnrollment['course_name'] ?? $student->getCourseOfStudy()) : $student->getCourseOfStudy(),
    'overall_average' => $overallAverage,
    'results' => $moduleResults
];

// Read input (supports JSON or POST)
$rawInput = file_get_contents('php://input');
$inputData = json_decode($rawInput, true) ?: $_POST;

$action = $inputData['action'] ?? 'analyze';

$aiService = new OpenRouterService();

try {
    if ($action === 'analyze') {
        $result = $aiService->analyzeStudentState($studentContext);
        echo json_encode($result);
        exit();
    } elseif ($action === 'chat') {
        $message = trim($inputData['message'] ?? '');
        if (empty($message)) {
            echo json_encode(['success' => false, 'error' => 'Please enter a question or topic.']);
            exit();
        }
        $history = is_array($inputData['history'] ?? null) ? $inputData['history'] : [];
        $result = $aiService->chatWithStudent($studentContext, $history, $message);
        echo json_encode($result);
        exit();
    } elseif ($action === 'insights') {
        $result = $aiService->getQuickInsights($studentContext);
        echo json_encode($result);
        exit();
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid action specified.']);
        exit();
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'AI processing error: ' . $e->getMessage()]);
    exit();
}
