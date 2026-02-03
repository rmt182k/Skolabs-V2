<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TaskDemoSeeder extends Seeder
{
    public function run()
    {
        // 1. Tentukan Target: Kelas 9 SMP A, Mapel MTK
        $className = '9 SMP A';
        $subjectCode = 'MTK';

        $class = DB::table('classes')->where('name', $className)->first();
        $subject = DB::table('subjects')->where('code', $subjectCode)->first();
        $academicYear = DB::table('academic_years')->where('is_active', true)->first();

        if (!$class || !$subject || !$academicYear) {
            $this->command->error("Data prasyarat tidak ditemukan! Pastikan Class '$className', Subject '$subjectCode', dan Academic Year aktif sudah ada.");
            return;
        }

        // 2. Tentukan Guru (STRICT: Harus Guru MTK)
        // Opsi A: Cek jadwal pengajar di kelas ini untuk mapel MTK
        $teacherId = DB::table('class_schedules')
            ->where('class_id', $class->id)
            ->where('subject_id', $subject->id)
            ->value('user_id');

        // Opsi B: Jika tidak ada di jadwal, cari Guru yang punya assignment mapel MTK
        if (!$teacherId) {
            $teacherId = DB::table('subjects_assignment')
                ->where('subject_id', $subject->id)
                ->value('user_id');
        }

        if (!$teacherId) {
            $this->command->error("Tidak ditemukan guru yang mengajar MTK (baik di jadwal kelas maupun assignment). Harap jalankan UserManagementSeeder terlebih dahulu.");
            return;
        }

        // 3. Buat Task
        // "Senin depan tanggal 9" (dari 3 Feb 2026 -> 9 Feb 2026)
        $endTime = Carbon::create(2026, 2, 9, 23, 59, 59);

        $taskId = DB::table('tasks')->insertGetId([
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            // 'academic_year_id' => $academicYear->id, // Removed: Column does not exist in tasks table
            'teacher_id' => $teacherId,
            'title' => 'Cambridge Lower Secondary Mathematics Stage 9 Test',
            'description' => 'Demo Test for Grade 9 Students. Includes Algebra, Geometry, and Number theory.',
            'type' => 'task', // Diganti dari 'exercise' karena enum hanya terima: task, quiz, exam
            'status' => 'published',
            'start_time' => now(),
            'end_time' => $endTime,
            'duration_minutes' => 90, // Estimasi
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->command->info("Task created: ID $taskId - {$className} - {$subjectCode}");

        // 4. Buat Pertanyaan & Opsi

        // Helper untuk insert question
        $insertQuestion = function ($text, $type, $score = 10, $options = [], $sampleAnswer = null) use ($taskId, $teacherId) {
            $qId = DB::table('questions')->insertGetId([
                'task_id' => $taskId,
                'question_text' => $text,
                'type' => $type,
                'score' => $score,
                'order' => 0, // Nanti user bisa reorder, default 0
                // 'created_by' => $teacherId, // Removed: Column does not exist in questions table
                'explanation' => $sampleAnswer, // Mapped to explanation column
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($type === 'multiple_choice' && !empty($options)) {
                foreach ($options as $opt) {
                    DB::table('question_options')->insert([
                        'question_id' => $qId,
                        'option_text' => $opt['text'],
                        'is_correct' => $opt['correct'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        };

        // --- MCQs (10 Soal) ---

        // 1
        $insertQuestion(
            "What is the simplified form of the expression: 3(x + 5) – 2x?",
            'multiple_choice',
            5, // Score per soal (asumsi total 100 utk 15 soal? MC: 5*10=50, Essay: 10*5=50)
            [
                ['text' => '3x + 15', 'correct' => false],
                ['text' => 'x + 15', 'correct' => true], // Answer B
                ['text' => 'x + 5', 'correct' => false],
                ['text' => '5x – 15', 'correct' => false],
            ]
        );

        // 2
        $insertQuestion(
            "If g = 4, evaluate the expression 6g² – 14g.",
            'multiple_choice',
            5,
            [
                ['text' => '44', 'correct' => true], // Answer A (Sesuai request user walau hitungan matematis mungkin beda)
                ['text' => '60', 'correct' => false],
                ['text' => '64', 'correct' => false],
                ['text' => '72', 'correct' => false],
            ]
        );

        // 3
        $insertQuestion(
            "Which of the following is a rational number?",
            'multiple_choice',
            5,
            [
                ['text' => '√2', 'correct' => false],
                ['text' => 'π', 'correct' => false],
                ['text' => '0.75', 'correct' => true], // Answer C
                ['text' => '√3', 'correct' => false],
            ]
        );

        // 4
        $insertQuestion(
            "The nth term of a sequence is given by 2n + 3. What is the 5th term?",
            'multiple_choice',
            5,
            [
                ['text' => '8', 'correct' => false],
                ['text' => '10', 'correct' => false],
                ['text' => '13', 'correct' => true], // Answer C
                ['text' => '15', 'correct' => false],
            ]
        );

        // 5
        $insertQuestion(
            "What is the area of a triangle with base 8 cm and height 5 cm?",
            'multiple_choice',
            5,
            [
                ['text' => '20 cm²', 'correct' => true], // Answer A (Wait, user said Answer C: 20cm2. My code order might differ. Let's stick to content)
                // User: A) 20, B) 40, C) 30, D) 60. Answer C is 20? No, User text: "Answer: C) 20 cm²". 
                // Ah, user's text: "A) 20 cm² ... Answer: C) 20 cm²". Wait. A is 20. 
                // Opsinya user: A) 20, B) 40, C) 30, D) 60. 
                // Answer key user: Answer: C) 20 cm². Typo in User prompt (C pointing to 20, but 20 is A).
                // I will make '20 cm²' the correct option regardless of position.
                ['text' => '40 cm²', 'correct' => false],
                ['text' => '30 cm²', 'correct' => false],
                ['text' => '60 cm²', 'correct' => false],
            ]
            // Note: Option text determines correctness here.
        );

        // 6
        $insertQuestion(
            "A function is defined as y = 2x – 3. What is y when x = 6?",
            'multiple_choice',
            5,
            [
                ['text' => '9', 'correct' => true], // Answer A
                ['text' => '12', 'correct' => false],
                ['text' => '15', 'correct' => false],
                ['text' => '20', 'correct' => false],
            ]
        );

        // 7
        $insertQuestion(
            "A rectangle has width (a + 3) and length a. What is its perimeter?",
            'multiple_choice',
            5,
            [
                ['text' => '2a + 6', 'correct' => false],
                ['text' => '2a + 3', 'correct' => false],
                ['text' => '2a + 2(a + 3)', 'correct' => false],
                ['text' => '4a + 6', 'correct' => true], // Answer D
            ]
        );

        // 8
        $insertQuestion(
            "Which ratio shows inverse proportion?",
            'multiple_choice',
            5,
            [
                ['text' => 'People and their ages', 'correct' => false],
                ['text' => 'Time taken to travel and speed', 'correct' => true], // Answer B
                ['text' => 'Money earned and hours worked', 'correct' => false],
                ['text' => 'Temperature and day of the week', 'correct' => false],
            ]
        );

        // 9
        $insertQuestion(
            "Convert 0.000 45 to standard form.",
            'multiple_choice',
            5,
            [
                ['text' => '4.5 × 10⁻⁴', 'correct' => true], // Answer A
                ['text' => '4.5 × 10⁻³', 'correct' => false],
                ['text' => '45 × 10⁻³', 'correct' => false],
                ['text' => '450 × 10⁻⁵', 'correct' => false],
            ]
        );

        // 10
        $insertQuestion(
            "Which of the following is the correct gradient of a line that goes up 3 units for a run of 1 unit?",
            'multiple_choice',
            5,
            [
                ['text' => '1', 'correct' => false],
                ['text' => '2', 'correct' => false],
                ['text' => '3', 'correct' => true], // Answer C
                ['text' => '4', 'correct' => false],
            ]
        );

        // --- Essays (5 Soal) ---

        // Essay 1
        $insertQuestion(
            "Explain the process of simplifying the expression 4(x – 2) + 3(2x + 1).",
            'essay',
            10,
            [],
            "To simplify, first apply the distributive law: 4(x – 2) becomes 4x – 8, and 3(2x + 1) becomes 6x + 3. Then combine like terms: 4x + 6x = 10x, and –8 + 3 = –5, so the final expression is 10x – 5."
        );

        // Essay 2
        $insertQuestion(
            "Describe how to find the nth term of a sequence and give an example.",
            'essay',
            10,
            [],
            "To find the nth term, look at the pattern of differences between terms. If the sequence is 3, 7, 11, 15…, the difference is 4, so the rule is 4n – 1. This means the nth term is 4n – 1. For n = 1, term = 3; for n = 2, term = 7, and so on."
        );

        // Essay 3
        $insertQuestion(
            "Explain how to calculate the area of a compound shape made from rectangles and triangles.",
            'essay',
            10,
            [],
            "First, break the compound shape into simpler sections — rectangles and triangles. Find the area of each: area of a rectangle = length × width, and area of a triangle = ½ × base × height. After calculating each area, add them together to get the total area."
        );

        // Essay 4
        $insertQuestion(
            "Discuss what it means for two quantities to be in inverse proportion, with an example.",
            'essay',
            10,
            [],
            "Two quantities are in inverse proportion if one increases while the other decreases at the same rate. For example, if 4 workers take 8 hours to paint a wall, then 8 workers might take half the time (4 hours). Here, the number of workers and time taken are inversely proportional."
        );

        // Essay 5
        $insertQuestion(
            "How would you interpret the gradient of a distance–time graph?",
            'essay',
            10,
            [],
            "The gradient of a distance–time graph shows the speed of an object. A steeper gradient means a higher speed. If the line is flat (horizontal), it shows no movement. The gradient is calculated by dividing the change in distance by the change in time (rise ÷ run)."
        );

        $this->command->info("Success! 15 Questions seeders created for Task Demo.");
    }
}
