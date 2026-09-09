<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Academics\Models\AcademicYear;
use App\Domain\Academics\Models\GradeLevel;
use App\Domain\Academics\Models\GradingCategory;
use App\Domain\Academics\Models\GradingScale;
use App\Domain\Academics\Models\Offering;
use App\Domain\Academics\Models\Section;
use App\Domain\Academics\Models\Semester;
use App\Domain\Academics\Models\Subject;
use App\Domain\Assessment\Models\Assessment;
use App\Domain\Assessment\Models\AssessmentScore;
use App\Domain\Assessment\Models\Exam;
use App\Domain\Assessment\Models\ExamResult;
use App\Domain\Assessment\Models\ReportCard;
use App\Domain\Attendance\Models\AttendanceRecord;
use App\Domain\Attendance\Models\AttendanceSession;
use App\Domain\Communication\Models\Announcement;
use App\Domain\Communication\Models\Conversation;
use App\Domain\Communication\Models\Message;
use App\Domain\Communication\Models\Notification;
use App\Domain\Compliance\Models\AuditLog;
use App\Domain\Documents\Models\Document;
use App\Domain\Documents\Models\DocumentCategory;
use App\Domain\Learning\Models\Assignment;
use App\Domain\Learning\Models\Material;
use App\Domain\Learning\Models\Quiz;
use App\Domain\Learning\Models\QuizAttempt;
use App\Domain\Learning\Models\Submission;
use App\Domain\People\Models\Student;
use App\Domain\People\Models\TeacherProfile;
use App\Domain\Scheduling\Models\Room;
use App\Domain\Scheduling\Models\TimetableEntry;
use App\Domain\Schools\Models\School;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeds the academic operating layer: offerings, timetable, attendance,
 * assessments, exams, report cards and the learning domain. All data is
 * deterministic demo data for Al Noor School.
 */
class OperationsSeeder extends Seeder
{
    private const DAYS = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday'];

    private const PERIODS = [
        ['08:00', '08:45'],
        ['08:50', '09:35'],
        ['09:40', '10:25'],
        ['10:45', '11:30'],
        ['11:35', '12:20'],
        ['13:00', '13:45'],
    ];

    public function run(): void
    {
        $school = School::where('slug', 'al-noor-school')->firstOrFail();
        $year = AcademicYear::where('school_id', $school->id)->where('is_current', true)->firstOrFail();
        $semester = Semester::where('academic_year_id', $year->id)->where('is_current', true)->first();
        $gradeLevels = GradeLevel::where('school_id', $school->id)->orderBy('level')->get();
        $sections = Section::where('school_id', $school->id)->where('academic_year_id', $year->id)->get();
        $subjects = Subject::where('school_id', $school->id)->get();
        $teachers = TeacherProfile::where('school_id', $school->id)->get();
        $students = Student::where('school_id', $school->id)->get();

        if ($teachers->isEmpty()) {
            // PeopleSeeder must have run; nothing to attach offerings to.
            return;
        }

        $this->seedRooms($school);
        $this->seedGrading($school);
        $this->seedOfferings($school, $year, $sections, $subjects, $teachers);
        $this->seedTimetable($school, $year, $semester, $teachers);
        $this->seedAttendance($school, $year, $semester, $sections, $teachers, $students);
        $this->seedAssessments($school, $year, $semester, $sections, $students);
        $this->seedExams($school, $year, $semester, $sections, $students);
        $this->seedReportCards($school, $year, $semester, $students);
        $this->seedLearning($school, $sections, $students);
        $this->seedCommunication($school);
        $this->seedDocuments($school);
        $this->seedAuditLogs($school);
        $this->seedNotifications($school);
    }

    private function seedRooms(School $school): void
    {
        $rooms = [
            ['name_ar' => 'قاعة ١٠١', 'name_en' => 'Room 101', 'code' => 'R101', 'room_type' => 'classroom', 'capacity' => 30, 'description' => 'Main building, ground floor'],
            ['name_ar' => 'قاعة ١٠٢', 'name_en' => 'Room 102', 'code' => 'R102', 'room_type' => 'classroom', 'capacity' => 30, 'description' => 'Main building, ground floor'],
            ['name_ar' => 'قاعة ٢٠١', 'name_en' => 'Room 201', 'code' => 'R201', 'room_type' => 'classroom', 'capacity' => 28, 'description' => 'Main building, first floor'],
            ['name_ar' => 'قاعة ٢٠٢', 'name_en' => 'Room 202', 'code' => 'R202', 'room_type' => 'classroom', 'capacity' => 28, 'description' => 'Main building, first floor'],
            ['name_ar' => 'مختبر العلوم ١', 'name_en' => 'Science Lab 1', 'code' => 'LAB1', 'room_type' => 'laboratory', 'capacity' => 24, 'description' => 'Science block laboratory'],
            ['name_ar' => 'مختبر الحاسب', 'name_en' => 'Computer Lab', 'code' => 'LAB2', 'room_type' => 'laboratory', 'capacity' => 24, 'description' => 'Computer laboratory'],
            ['name_ar' => 'القاعة الرئيسية', 'name_en' => 'Main Hall', 'code' => 'HALL-A', 'room_type' => 'hall', 'capacity' => 120, 'description' => 'Assembly and events hall'],
            ['name_ar' => 'الصالة الرياضية', 'name_en' => 'Sports Hall', 'code' => 'HALL-B', 'room_type' => 'hall', 'capacity' => 80, 'description' => 'Indoor sports hall'],
        ];

        foreach ($rooms as $room) {
            Room::firstOrCreate(
                ['school_id' => $school->id, 'code' => $room['code']],
                $room + ['school_id' => $school->id]
            );
        }
    }

    private function seedGrading(School $school): void
    {
        $scale = GradingScale::firstOrCreate(
            ['school_id' => $school->id, 'name' => 'Al Noor Standard Scale'],
            [
                'description' => 'Standard percentage-based grading scale',
                'scale' => json_encode([
                    ['grade' => 'A+', 'min' => 95, 'max' => 100],
                    ['grade' => 'A', 'min' => 90, 'max' => 94.99],
                    ['grade' => 'B+', 'min' => 85, 'max' => 89.99],
                    ['grade' => 'B', 'min' => 80, 'max' => 84.99],
                    ['grade' => 'C+', 'min' => 75, 'max' => 79.99],
                    ['grade' => 'C', 'min' => 70, 'max' => 74.99],
                    ['grade' => 'D', 'min' => 60, 'max' => 69.99],
                    ['grade' => 'F', 'min' => 0, 'max' => 59.99],
                ]),
                'is_default' => true,
            ]
        );

        foreach ([
            ['name' => 'Classwork', 'code' => 'CW', 'weight' => 30],
            ['name' => 'Homework', 'code' => 'HW', 'weight' => 20],
            ['name' => 'Quizzes', 'code' => 'QZ', 'weight' => 20],
            ['name' => 'Exams', 'code' => 'EX', 'weight' => 30],
        ] as $cat) {
            GradingCategory::firstOrCreate(
                ['school_id' => $school->id, 'code' => $cat['code']],
                [
                    'name' => $cat['name'],
                    'weight' => $cat['weight'],
                    'description' => $cat['name'].' contribution to final grade',
                ]
            );
        }
    }

    private function seedOfferings(School $school, AcademicYear $year, $sections, $subjects, $teachers): void
    {
        foreach ($sections as $index => $section) {
            foreach ($subjects as $sIndex => $subject) {
                $teacher = $teachers[($index + $sIndex) % $teachers->count()];

                Offering::firstOrCreate(
                    [
                        'school_id' => $school->id,
                        'academic_year_id' => $year->id,
                        'subject_id' => $subject->id,
                        'section_id' => $section->id,
                    ],
                    [
                        'teacher_id' => $teacher->id,
                        'name' => $subject->name.' — '.$section->name,
                        'description' => $subject->name.' offering for '.$section->name,
                    ]
                );
            }
        }
    }

    private function seedTimetable(School $school, AcademicYear $year, ?Semester $semester, $teachers): void
    {
        $rooms = Room::where('school_id', $school->id)->get();
        $offerings = Offering::where('school_id', $school->id)
            ->where('academic_year_id', $year->id)
            ->with(['section', 'subject', 'teacher'])
            ->get();

        $slotIndex = 0;
        foreach ($offerings as $offering) {
            $slot = self::PERIODS[$slotIndex % count(self::PERIODS)];
            $day = self::DAYS[intdiv($slotIndex, count(self::PERIODS)) % count(self::DAYS)];
            $slotIndex++;

            TimetableEntry::firstOrCreate(
                [
                    'school_id' => $school->id,
                    'academic_year_id' => $year->id,
                    'offering_id' => $offering->id,
                    'day_of_week' => $day,
                    'start_time' => $slot[0],
                ],
                [
                    'semester_id' => $semester?->id,
                    'section_id' => $offering->section_id,
                    'teacher_id' => $offering->teacher_id,
                    'room_id' => $rooms->isNotEmpty() ? $rooms[$slotIndex % $rooms->count()]->id : null,
                    'end_time' => $slot[1],
                    'is_published' => true,
                ]
            );
        }
    }

    private function seedAttendance(School $school, AcademicYear $year, ?Semester $semester, $sections, $teachers, $students): void
    {
        $userIds = User::pluck('id')->toArray();
        $recorderId = $userIds[0] ?? null;

        foreach ($sections as $section) {
            $sectionOfferings = Offering::where('section_id', $section->id)->get();
            if ($sectionOfferings->isEmpty()) {
                continue;
            }

            $enrolled = $section->students()->get();
            if ($enrolled->isEmpty()) {
                continue;
            }

            // Two weeks of attendance history (weekdays only).
            for ($d = 1; $d <= 10; $d++) {
                $date = now()->subDays($d);
                if ($date->isWeekend()) {
                    continue;
                }

                $offering = $sectionOfferings[$d % $sectionOfferings->count()];
                $teacher = $teachers->where('id', $offering->teacher_id)->first() ?? $teachers->first();

                $session = AttendanceSession::firstOrCreate(
                    [
                        'school_id' => $school->id,
                        'section_id' => $section->id,
                        'session_date' => $date->format('Y-m-d'),
                        'offering_id' => $offering->id,
                    ],
                    [
                        'academic_year_id' => $year->id,
                        'semester_id' => $semester?->id,
                        'teacher_id' => $teacher->id,
                        'start_time' => '08:00',
                        'end_time' => '08:45',
                        'status' => 'closed',
                        'is_finalized' => true,
                    ]
                );

                foreach ($enrolled as $i => $student) {
                    // Deterministic pseudo-random statuses: mostly present.
                    $roll = ($student->id * 7 + $d * 13) % 20;
                    $status = match (true) {
                        $roll === 0 => 'absent',
                        $roll === 1 => 'late',
                        $roll === 2 => 'excused',
                        default => 'present',
                    };

                    AttendanceRecord::firstOrCreate(
                        [
                            'attendance_session_id' => $session->id,
                            'student_id' => $student->id,
                        ],
                        [
                            'school_id' => $school->id,
                            'status' => $status,
                            'notes' => $status === 'excused' ? 'Excused absence on file' : null,
                            'recorded_by' => $recorderId,
                        ]
                    );
                }
            }
        }
    }

    private function seedAssessments(School $school, AcademicYear $year, ?Semester $semester, $sections, $students): void
    {
        $categories = GradingCategory::where('school_id', $school->id)->get();
        if ($categories->isEmpty()) {
            return;
        }

        $offerings = Offering::where('school_id', $school->id)
            ->where('academic_year_id', $year->id)
            ->with('section')
            ->get();

        foreach ($offerings->take(20) as $oIndex => $offering) {
            $category = $categories[$oIndex % $categories->count()];

            $assessment = Assessment::firstOrCreate(
                [
                    'school_id' => $school->id,
                    'offering_id' => $offering->id,
                    'name' => 'Quiz '.(($oIndex % 3) + 1).' — '.$offering->name,
                ],
                [
                    'academic_year_id' => $year->id,
                    'semester_id' => $semester?->id,
                    'grading_category_id' => $category->id,
                    'description' => 'Regular class assessment',
                    'due_date' => now()->subDays(($oIndex % 14) + 1)->format('Y-m-d'),
                    'max_score' => 20,
                    'weight' => 10,
                    'is_published' => true,
                ]
            );

            // Scores for enrolled students
            $enrolled = $offering->section?->students()->get() ?? collect();
            foreach ($enrolled as $student) {
                $score = 12 + (($student->id * 5 + $oIndex * 3) % 9); // 12..20
                AssessmentScore::firstOrCreate(
                    ['assessment_id' => $assessment->id, 'student_id' => $student->id],
                    [
                        'school_id' => $school->id,
                        'score' => $score,
                        'feedback' => $score >= 18 ? 'Excellent work.' : null,
                    ]
                );
            }
        }
    }

    private function seedExams(School $school, AcademicYear $year, ?Semester $semester, $sections, $students): void
    {
        $rooms = Room::where('school_id', $school->id)->get();
        $offerings = Offering::where('school_id', $school->id)
            ->where('academic_year_id', $year->id)
            ->with('section')
            ->get();

        foreach ($offerings->take(12) as $oIndex => $offering) {
            $exam = Exam::firstOrCreate(
                [
                    'school_id' => $school->id,
                    'offering_id' => $offering->id,
                    'name' => 'Midterm — '.$offering->name,
                ],
                [
                    'academic_year_id' => $year->id,
                    'semester_id' => $semester?->id,
                    'description' => 'Midterm examination',
                    'exam_date' => now()->addDays(($oIndex % 20) + 2)->format('Y-m-d'),
                    'start_time' => '09:00',
                    'end_time' => '11:00',
                    'room_id' => $rooms->isNotEmpty() ? $rooms[$oIndex % $rooms->count()]->id : null,
                    'max_score' => 100,
                    'is_published' => $oIndex < 8,
                ]
            );

            if (! $exam->is_published) {
                continue; // results only for published exams
            }

            $enrolled = $offering->section?->students()->get() ?? collect();
            foreach ($enrolled as $student) {
                $score = 55 + (($student->id * 11 + $oIndex * 7) % 45); // 55..99
                ExamResult::firstOrCreate(
                    ['exam_id' => $exam->id, 'student_id' => $student->id],
                    [
                        'school_id' => $school->id,
                        'score' => $score,
                        'notes' => $score < 60 ? 'Borderline performance — follow up.' : null,
                    ]
                );
            }
        }
    }

    private function seedReportCards(School $school, AcademicYear $year, ?Semester $semester, $students): void
    {
        foreach ($students->take(10) as $student) {
            $gpa = round(2.0 + (($student->id * 37) % 20) / 10, 2); // 2.00..3.90

            ReportCard::firstOrCreate(
                [
                    'school_id' => $school->id,
                    'academic_year_id' => $year->id,
                    'semester_id' => $semester?->id,
                    'student_id' => $student->id,
                ],
                [
                    'gpa' => $gpa,
                    'grades' => json_encode([
                        ['subject' => 'Mathematics', 'score' => 82 + ($student->id % 15)],
                        ['subject' => 'Science', 'score' => 78 + ($student->id % 18)],
                        ['subject' => 'English', 'score' => 80 + ($student->id % 12)],
                        ['subject' => 'Arabic', 'score' => 85 + ($student->id % 10)],
                    ]),
                    'attendance_summary' => json_encode([
                        'present' => 40 + ($student->id % 5),
                        'absent' => $student->id % 3,
                        'late' => ($student->id + 1) % 3,
                    ]),
                    'comments' => $gpa >= 3.0
                        ? 'A strong semester. Keep up the excellent effort.'
                        : 'A steady semester with room to grow in core subjects.',
                    'published_at' => now()->subDays(3),
                ]
            );
        }
    }

    private function seedLearning(School $school, $sections, $students): void
    {
        $offerings = Offering::where('school_id', $school->id)->with('section')->get();
        $userIds = User::pluck('id')->toArray();

        foreach ($offerings->take(10) as $oIndex => $offering) {
            // Material
            Material::firstOrCreate(
                [
                    'school_id' => $school->id,
                    'offering_id' => $offering->id,
                    'title' => 'Week 1 Study Notes — '.$offering->name,
                ],
                [
                    'description' => 'Introductory study notes for the first week.',
                    'is_published' => true,
                ]
            );

            // Assignment
            $assignment = Assignment::firstOrCreate(
                [
                    'school_id' => $school->id,
                    'offering_id' => $offering->id,
                    'title' => 'Homework 1 — '.$offering->name,
                ],
                [
                    'description' => 'Practice problems from chapter 1.',
                    'instructions' => 'Answer all questions. Show your work.',
                    'due_date' => now()->addDays(($oIndex % 10) + 2)->format('Y-m-d'),
                    'max_score' => 10,
                    'is_published' => true,
                ]
            );

            // Quiz
            $quiz = Quiz::firstOrCreate(
                [
                    'school_id' => $school->id,
                    'offering_id' => $offering->id,
                    'title' => 'Quick Check 1 — '.$offering->name,
                ],
                [
                    'description' => 'Short formative quiz.',
                    'questions' => json_encode([
                        ['question' => 'What is 7 x 8?', 'options' => ['54', '56', '58', '48'], 'answer' => 1],
                        ['question' => 'What is the capital of Saudi Arabia?', 'options' => ['Jeddah', 'Riyadh', 'Dammam', 'Makkah'], 'answer' => 1],
                    ]),
                    'time_limit_minutes' => 15,
                    'max_score' => 10,
                    'is_published' => true,
                ]
            );

            // Submissions for the first few students
            $enrolled = $offering->section?->students()->get() ?? collect();
            foreach ($enrolled->take(3) as $sIndex => $student) {
                $score = 6 + (($student->id + $oIndex) % 5);
                Submission::firstOrCreate(
                    ['assignment_id' => $assignment->id, 'student_id' => $student->id],
                    [
                        'school_id' => $school->id,
                        'content' => 'Completed homework submission.',
                        'score' => $sIndex === 2 ? null : $score,
                        'feedback' => $sIndex === 2 ? null : 'Good effort.',
                        'graded_by' => $sIndex === 2 ? null : ($userIds[0] ?? null),
                        'graded_at' => $sIndex === 2 ? null : now(),
                        'submitted_at' => now()->subDays(1),
                    ]
                );

                QuizAttempt::firstOrCreate(
                    ['quiz_id' => $quiz->id, 'student_id' => $student->id],
                    [
                        'school_id' => $school->id,
                        'answers' => json_encode([1, 1]),
                        'score' => $sIndex === 1 ? null : 10,
                        'started_at' => now()->subDays(2),
                        'completed_at' => $sIndex === 1 ? null : now()->subDays(2)->addMinutes(12),
                        'status' => $sIndex === 1 ? 'in_progress' : 'completed',
                    ]
                );
            }
        }
    }

    private function seedCommunication(School $school): void
    {
        $admin = User::where('email', 'admin@alnoor.school')->first();
        $teacher = User::where('email', 'teacher@alnoor.school')->first();

        Announcement::firstOrCreate(
            ['school_id' => $school->id, 'title' => 'Welcome to the New Academic Year'],
            [
                'body' => 'Classes begin Sunday. Homeroom lists are posted in the portal, and the first assembly is at 8:00 AM in the Main Hall.',
                'audience' => 'all',
                'target_audience' => json_encode(['all']),
                'start_date' => now()->startOfWeek()->format('Y-m-d'),
                'end_date' => now()->addDays(30)->format('Y-m-d'),
                'is_published' => true,
                'published_by' => $admin?->id,
                'published_at' => now()->subDays(2),
            ]
        );

        Announcement::firstOrCreate(
            ['school_id' => $school->id, 'title' => 'Parent-Teacher Conference Sign-ups Open'],
            [
                'body' => 'Guardians can now book conference slots through the guardian portal. Slots fill quickly — book early.',
                'audience' => 'guardians',
                'target_audience' => json_encode(['guardian']),
                'start_date' => now()->format('Y-m-d'),
                'end_date' => now()->addDays(14)->format('Y-m-d'),
                'is_published' => true,
                'published_by' => $admin?->id,
                'published_at' => now()->subDay(),
            ]
        );

        if ($admin && $teacher) {
            $conversation = Conversation::firstOrCreate(
                ['school_id' => $school->id, 'subject' => 'Grade 1A — Curriculum Planning'],
                ['type' => 'group']
            );

            Message::firstOrCreate(
                [
                    'school_id' => $school->id,
                    'conversation_id' => $conversation->id,
                    'sender_id' => $admin->id,
                    'body' => 'Please submit your semester supply lists by Thursday.',
                ]
            );
            Message::firstOrCreate(
                [
                    'school_id' => $school->id,
                    'conversation_id' => $conversation->id,
                    'sender_id' => $teacher->id,
                    'body' => 'Will do. The math list is almost ready.',
                    'read_at' => now(),
                ]
            );
        }
    }

    private function seedDocuments(School $school): void
    {
        $admin = User::where('email', 'admin@alnoor.school')->first();
        $uploader = $admin?->id ?? User::query()->value('id') ?? 1;

        $categories = [
            ['name' => 'Policies', 'code' => 'POL', 'description' => 'Official school policies'],
            ['name' => 'Academic Records', 'code' => 'ACAD', 'description' => 'Transcripts and academic documents'],
            ['name' => 'Admissions', 'code' => 'ADM', 'description' => 'Admissions forms and checklists'],
        ];

        $cats = [];
        foreach ($categories as $cat) {
            $cats[$cat['code']] = DocumentCategory::firstOrCreate(
                ['school_id' => $school->id, 'name' => $cat['name']],
                $cat + ['school_id' => $school->id]
            );
        }

        $documents = [
            ['title' => 'Student Handbook 2026', 'cat' => 'POL', 'file' => 'documents/demo/student-handbook.pdf', 'type' => 'pdf', 'size' => 1_240_000, 'class' => 'public'],
            ['title' => 'Admissions Checklist', 'cat' => 'ADM', 'file' => 'documents/demo/admissions-checklist.pdf', 'type' => 'pdf', 'size' => 320_000, 'class' => 'public'],
            ['title' => 'Grading Policy', 'cat' => 'POL', 'file' => 'documents/demo/grading-policy.pdf', 'type' => 'pdf', 'size' => 210_000, 'class' => 'internal'],
        ];

        foreach ($documents as $doc) {
            Document::firstOrCreate(
                ['school_id' => $school->id, 'title' => $doc['title']],
                [
                    'document_category_id' => $cats[$doc['cat']]->id,
                    'file_path' => $doc['file'],
                    'file_type' => $doc['type'],
                    'file_size' => $doc['size'],
                    'classification' => $doc['class'],
                    'uploaded_by' => $uploader,
                ]
            );
        }
    }

    private function seedAuditLogs(School $school): void
    {
        $admin = User::where('email', 'admin@alnoor.school')->first();
        $student = Student::where('school_id', $school->id)->first();

        if ($student) {
            AuditLog::firstOrCreate(
                [
                    'school_id' => $school->id,
                    'user_id' => $admin?->id,
                    'action' => 'student_created',
                    'entity_type' => Student::class,
                    'entity_id' => $student->id,
                ],
                [
                    'new_values' => json_encode(['status' => 'active']),
                    'ip_address' => '127.0.0.1',
                    'user_agent' => 'seeder',
                ]
            );
        }
    }

    private function seedNotifications(School $school): void
    {
        $users = User::whereHas('memberships', fn ($q) => $q->where('school_id', $school->id))->get();

        foreach ($users as $index => $user) {
            Notification::firstOrCreate(
                [
                    'school_id' => $school->id,
                    'user_id' => $user->id,
                    'title' => 'Welcome to Aether School OS',
                ],
                [
                    'type' => 'info',
                    'body' => 'Your Al Noor School account is ready. Explore your dashboard to get started.',
                    'action_url' => '/dashboard',
                    'read_at' => $index % 2 === 0 ? now() : null,
                ]
            );
        }
    }
}
