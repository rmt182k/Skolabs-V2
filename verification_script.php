<?php

use App\Http\Controllers\TaskSubmissionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

// Mock Auth
$teacher = DB::table('users')->where('role_id', 2)->first(); // Assuming role 2 is teacher
if (!$teacher) {
    // create dummy teacher if not exists
    $teacherId = DB::table('users')->insertGetId([
        'name' => 'Test Teacher',
        'email' => 'teacher@test.com',
        'password' => bcrypt('password'),
        'role_id' => 2,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    Auth::loginUsingId($teacherId);
} else {
    Auth::loginUsingId($teacher->id);
}

// Create dummy data
$taskId = DB::table('tasks')->insertGetId([
    'title' => 'Test Task Timer',
    'class_id' => 1, // assume class 1 exists
    'created_at' => now(),
    'updated_at' => now(),
]);

$studentId = DB::table('users')->where('role_id', 3)->value('id'); // Assuming role 3 is student
if (!$studentId) {
    $studentId = DB::table('users')->insertGetId([
        'name' => 'Test Student',
        'email' => 'student@test.com',
        'password' => bcrypt('password'),
        'role_id' => 3,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

$submissionId = DB::table('task_submissions')->insertGetId([
    'task_id' => $taskId,
    'student_id' => $studentId,
    'status' => 'pending_review',
    'submitted_at' => now(),
    'created_at' => now(),
    'updated_at' => now(),
]);

echo "Created submission ID: $submissionId with status pending_review\n";

// Test 1: Simulate opening the grading page (Start Timer)
$controller = new TaskSubmissionController();
$controller->getSubmissionDetails($submissionId);

$submission = DB::table('task_submissions')->where('id', $submissionId)->first();
if ($submission->grading_started_at) {
    echo "PASS: grading_started_at set to {$submission->grading_started_at}\n";
} else {
    echo "FAIL: grading_started_at is NULL\n";
}

// Wait 2 seconds
sleep(2);

// Test 2: Simulate saving grades (End Timer)
$request = new Request();
$request->merge([
    'teacher_feedback' => 'Good job',
    'grades' => [] // empty grades for simplicity
]);
// Need 'grades' to be array
$request->setJson(new \Symfony\Component\HttpFoundation\ParameterBag([
    'teacher_feedback' => 'Good job',
    'grades' => []
]));


$questionId = DB::table('questions')->insertGetId([
    'task_id' => $taskId,
    'question_text' => 'Test Q',
    'type' => 'essay',
    'score' => 10,
    'order' => 1
]);

$answerId = DB::table('task_submission_answers')->insertGetId([
    'task_submission_id' => $submissionId,
    'question_id' => $questionId,
    'answer_text' => 'Test Answer',
    'created_at' => now(),
    'updated_at' => now()
]);

$request = Request::create('/api/submissions/' . $submissionId . '/grade', 'POST', [], [], [], [], json_encode([
    'teacher_feedback' => 'Good job',
    'grades' => [
        [
            'answer_id' => $answerId,
            'score_awarded' => 10,
            'teacher_comment' => 'Nice'
        ]
    ]
]));


$controller->saveGrades($request, $submissionId);

$submission = DB::table('task_submissions')->where('id', $submissionId)->first();
echo "Grading Duration: {$submission->grading_duration_seconds} seconds\n";

if ($submission->grading_duration_seconds >= 2) {
    echo "PASS: Duration recorded correctly (> 2 seconds)\n";
} else {
    echo "FAIL: Duration too short or 0.\n";
}

// Cleanup
// DB::table('task_submissions')->where('id', $submissionId)->delete();
// DB::table('tasks')->where('id', $taskId)->delete();
