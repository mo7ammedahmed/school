<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Schools\Models\School;
use App\Domain\Content\Models\News;
use App\Domain\Content\Models\Event;
use App\Domain\Content\Models\Faq;
use App\Domain\Content\Models\StaffProfile;
use App\Domain\Content\Models\ContactLead;
use App\Domain\Content\Models\ContentPage;
use App\Domain\Admissions\Models\AdmissionPeriod;
use App\Domain\Admissions\Models\AdmissionApplication;
use App\Domain\Admissions\Models\AdmissionApplicationEvent;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds public website content and the admissions pipeline.
 * All news/events/faculty are clearly fictional demo content for Al Noor School.
 */
class ContentSeeder extends Seeder
{
    public function run(): void
    {
        $school = School::where('slug', 'al-noor-school')->firstOrFail();

        $this->seedNews($school);
        $this->seedEvents($school);
        $this->seedFaqs($school);
        $this->seedStaffProfiles($school);
        $this->seedContentPages($school);
        $this->seedContactLeads($school);
        $this->seedAdmissions($school);
    }

    private function seedNews(School $school): void
    {
        $posts = [
            [
                'title' => 'Science Fair 2026: Innovation on Display',
                'excerpt' => 'Students from grades 4–12 presented research projects spanning robotics, renewable energy and environmental science.',
                'content' => "Our annual Science Fair welcomed families and community judges to explore more than 60 student-led projects.\n\nThis year's highlights included a solar-powered water filtration prototype from the Grade 9 team and a bilingual robotics demonstration from our elementary club.\n\nWinners will represent Al Noor School at the regional science showcase later this semester.",
            ],
            [
                'title' => 'New Library Wing Opens with 10,000 New Titles',
                'excerpt' => 'The expanded library now features a dedicated Arabic literature collection and digital research stations.',
                'content' => "The new library wing officially opened its doors this week.\n\nThe collection has grown to include 10,000 additional titles, with a dedicated Arabic literature room and a youth media lab.\n\nStudents can now borrow devices for on-campus research, and librarians are hosting weekly reading circles in both Arabic and English.",
            ],
            [
                'title' => 'Athletics Team Wins City Championship',
                'excerpt' => 'Our senior football team brought home the city championship trophy after an undefeated season.',
                'content' => "After an undefeated season, our senior football team lifted the city championship trophy.\n\nCoach Mariam Al-Sayed credited the win to 'a year of disciplined training and real teamwork'.\n\nTryouts for the new season open next month for grades 7–12.",
            ],
        ];

        foreach ($posts as $i => $post) {
            News::firstOrCreate(
                ['school_id' => $school->id, 'title' => $post['title']],
                [
                    'slug' => Str::slug($post['title']),
                    'excerpt' => $post['excerpt'],
                    'content' => $post['content'],
                    'is_published' => true,
                    'published_at' => now()->subDays(($i + 1) * 7),
                ]
            );
        }
    }

    private function seedEvents(School $school): void
    {
        $events = [
            [
                'title' => 'Open House & Campus Tours',
                'description' => 'Prospective families are invited to tour the campus, meet faculty and learn about our academic programs.',
                'start' => now()->addDays(14)->setTime(9, 0),
                'end' => now()->addDays(14)->setTime(13, 0),
                'location' => 'Main Hall',
                'type' => 'admissions',
            ],
            [
                'title' => 'Parent-Teacher Conferences',
                'description' => 'Semester one parent-teacher conferences. Booking links are shared with guardians via the portal.',
                'start' => now()->addDays(21)->setTime(16, 0),
                'end' => now()->addDays(21)->setTime(19, 0),
                'location' => 'Classroom Blocks',
                'type' => 'academic',
            ],
            [
                'title' => 'Annual Arts Showcase',
                'description' => 'An evening of student art, music and theatre performances celebrating creative learning.',
                'start' => now()->addDays(35)->setTime(18, 0),
                'end' => now()->addDays(35)->setTime(21, 0),
                'location' => 'Auditorium',
                'type' => 'activity',
            ],
        ];

        foreach ($events as $event) {
            Event::firstOrCreate(
                ['school_id' => $school->id, 'title' => $event['title']],
                [
                    'slug' => Str::slug($event['title']),
                    'description' => $event['description'],
                    'start_date' => $event['start']->format('Y-m-d H:i:s'),
                    'end_date' => $event['end']->format('Y-m-d H:i:s'),
                    'location' => $event['location'],
                    'event_type' => $event['type'],
                    'is_published' => true,
                ]
            );
        }
    }

    private function seedFaqs(School $school): void
    {
        $faqs = [
            ['category' => 'Admissions', 'question' => 'How do I apply for admission?', 'answer' => 'Complete the online application form on this website. You will need previous academic records, a birth certificate, and passport-sized photographs.'],
            ['category' => 'Admissions', 'question' => 'Is there an application deadline?', 'answer' => 'We accept applications year-round, but priority is given to applications received before the start of each academic year.'],
            ['category' => 'Fees', 'question' => 'What payment methods are accepted?', 'answer' => 'We accept online card payments, bank transfers, and in-person payments at the finance office. Monthly and annual plans are available.'],
            ['category' => 'Academics', 'question' => 'What curriculum does Al Noor follow?', 'answer' => 'Our program combines the Saudi national curriculum with international best practices, taught bilingually in Arabic and English.'],
            ['category' => 'General', 'question' => 'What are the school hours?', 'answer' => 'School runs 8:00 AM to 3:00 PM, Sunday through Thursday. After-school activities run until 5:00 PM.'],
        ];

        foreach ($faqs as $i => $faq) {
            Faq::firstOrCreate(
                ['school_id' => $school->id, 'question' => $faq['question']],
                $faq + ['school_id' => $school->id, 'sort_order' => $i, 'is_published' => true]
            );
        }
    }

    private function seedStaffProfiles(School $school): void
    {
        $profiles = [
            ['first_name' => 'Sarah', 'last_name' => 'Johnson', 'position' => 'Head of Mathematics', 'department' => 'Mathematics', 'bio' => 'Sarah leads our mathematics department with a decade of classroom experience and a passion for making abstract concepts tangible.', 'featured' => true],
            ['first_name' => 'Mariam', 'last_name' => 'Al-Sayed', 'position' => 'Athletics Director', 'department' => 'Physical Education', 'bio' => 'Mariam coaches our championship athletics program and champions student wellbeing through sport.', 'featured' => true],
            ['first_name' => 'Omar', 'last_name' => 'Haddad', 'position' => 'Arabic Language Lead', 'department' => 'Arabic', 'bio' => 'Omar brings classical Arabic literature to life and leads our bilingual reading initiative.', 'featured' => true],
            ['first_name' => 'Elena', 'last_name' => 'Petrova', 'position' => 'Science Coordinator', 'department' => 'Science', 'bio' => 'Elena coordinates our laboratory program and mentors the award-winning science fair teams.', 'featured' => true],
            ['first_name' => 'David', 'last_name' => 'Okonkwo', 'position' => 'English Language Lead', 'department' => 'English', 'bio' => 'David leads our English program with a focus on debate, writing and confident communication.', 'featured' => false],
        ];

        foreach ($profiles as $i => $profile) {
            StaffProfile::firstOrCreate(
                ['school_id' => $school->id, 'first_name' => $profile['first_name'], 'last_name' => $profile['last_name']],
                [
                    'position' => $profile['position'],
                    'department' => $profile['department'],
                    'bio' => $profile['bio'],
                    'sort_order' => $i,
                    'is_featured' => $profile['featured'],
                ]
            );
        }
    }

    private function seedContentPages(School $school): void
    {
        $pages = [
            [
                'slug' => 'about',
                'title' => 'About Al Noor School',
                'content' => "Founded to serve the families of Riyadh, Al Noor School pairs academic excellence with character education.\n\nOur bilingual program, dedicated faculty and modern campus create an environment where every student is known, challenged and supported.",
            ],
            [
                'slug' => 'privacy',
                'title' => 'Privacy Policy',
                'content' => "Al Noor School handles student and family data in accordance with the Saudi Personal Data Protection Law (PDPL).\n\nData is collected only for educational and administrative purposes, stored securely, and shared only as required by law or with explicit consent.",
            ],
        ];

        foreach ($pages as $page) {
            ContentPage::firstOrCreate(
                ['school_id' => $school->id, 'slug' => $page['slug']],
                [
                    'title' => $page['title'],
                    'content' => $page['content'],
                    'is_published' => true,
                    'published_at' => now(),
                ]
            );
        }
    }

    private function seedContactLeads(School $school): void
    {
        $leads = [
            ['first_name' => 'Noura', 'last_name' => 'Al-Qahtani', 'email' => 'noura@example.com', 'phone' => '+966501234567', 'subject' => 'Admissions inquiry — Grade 3', 'message' => 'I would like to know about seat availability in Grade 3 for the next academic year.'],
            ['first_name' => 'Khalid', 'last_name' => 'Al-Otaibi', 'email' => 'khalid@example.com', 'phone' => '+966507654321', 'subject' => 'Campus tour', 'message' => 'Can we schedule a campus tour this week?'],
        ];

        foreach ($leads as $lead) {
            ContactLead::firstOrCreate(
                ['school_id' => $school->id, 'email' => $lead['email'], 'subject' => $lead['subject']],
                $lead + ['school_id' => $school->id, 'status' => 'new']
            );
        }
    }

    private function seedAdmissions(School $school): void
    {
        $period = AdmissionPeriod::firstOrCreate(
            ['school_id' => $school->id, 'name' => now()->format('Y').'-'.now()->addYear()->format('y').' Admissions'],
            [
                'start_date' => now()->startOfYear()->format('Y-m-d'),
                'end_date' => now()->endOfYear()->format('Y-m-d'),
                'is_active' => true,
                'description' => 'Main admissions window for the current academic year.',
            ]
        );

        $applications = [
            [
                'guardian' => ['Layla', 'Al-Mansouri', 'layla.mansouri@example.com', '+966551110001'],
                'student' => ['Yousef', 'Al-Mansouri', '2017-04-12', 'male', 'Saudi', 'Grade 1'],
                'previous' => ['Little Stars Kindergarten', 'KG2', '2025'],
                'status' => 'submitted',
            ],
            [
                'guardian' => ['Faisal', 'Al-Harbi', 'faisal.harbi@example.com', '+966551110002'],
                'student' => ['Dana', 'Al-Harbi', '2016-09-03', 'female', 'Saudi', 'Grade 2'],
                'previous' => ['Riyadh International School', 'Grade 1', '2025'],
                'status' => 'under_review',
            ],
            [
                'guardian' => ['Huda', 'Al-Zahrani', 'huda.zahrani@example.com', '+966551110003'],
                'student' => ['Omar', 'Al-Zahrani', '2015-01-25', 'male', 'Saudi', 'Grade 3'],
                'previous' => ['Al Faisal Academy', 'Grade 2', '2025'],
                'status' => 'approved',
            ],
            [
                'guardian' => ['Nasser', 'Al-Dosari', 'nasser.dosari@example.com', '+966551110004'],
                'student' => ['Reem', 'Al-Dosari', '2018-06-30', 'female', 'Saudi', 'Grade 1'],
                'previous' => ['Sunnyside Kindergarten', 'KG2', '2026'],
                'status' => 'rejected',
            ],
            [
                'guardian' => ['Amal', 'Al-Ghamdi', 'amal.ghamdi@example.com', '+966551110005'],
                'student' => ['Salem', 'Al-Ghamdi', '2017-11-08', 'male', 'Saudi', 'Grade 1'],
                'previous' => null,
                'status' => 'submitted',
            ],
        ];

        $seq = AdmissionApplication::withTrashed()->where('school_id', $school->id)->count();

        foreach ($applications as $app) {
            $seq++;
            $reference = 'ANS-ADM-'.now()->format('Y').'-'.str_pad((string) $seq, 5, '0', STR_PAD_LEFT);

            $application = AdmissionApplication::firstOrCreate(
                ['school_id' => $school->id, 'reference' => $reference],
                [
                    'admission_period_id' => $period->id,
                    'status' => $app['status'],
                    'guardian_first_name' => $app['guardian'][0],
                    'guardian_last_name' => $app['guardian'][1],
                    'guardian_email' => $app['guardian'][2],
                    'guardian_phone' => $app['guardian'][3],
                    'guardian_relationship' => 'Mother',
                    'student_first_name' => $app['student'][0],
                    'student_last_name' => $app['student'][1],
                    'student_date_of_birth' => $app['student'][2],
                    'student_gender' => $app['student'][3],
                    'student_nationality' => $app['student'][4],
                    'grade_applying' => $app['student'][5],
                    'previous_school_name' => $app['previous'][0] ?? null,
                    'previous_school_last_grade' => $app['previous'][1] ?? null,
                    'previous_school_year_completed' => $app['previous'][2] ?? null,
                    'documents' => [
                        ['name' => 'Birth Certificate', 'path' => 'admissions/demo/birth-certificate.pdf'],
                        ['name' => 'Passport Photo', 'path' => 'admissions/demo/passport-photo.jpg'],
                    ],
                    'submitted_at' => now()->subDays($seq),
                ]
            );

            AdmissionApplicationEvent::firstOrCreate(
                [
                    'admission_application_id' => $application->id,
                    'event_type' => 'submitted',
                ],
                [
                    'notes' => 'Application submitted through the public website (demo data).',
                ]
            );

            if (in_array($app['status'], ['approved', 'rejected'], true)) {
                AdmissionApplicationEvent::firstOrCreate(
                    [
                        'admission_application_id' => $application->id,
                        'event_type' => $app['status'],
                    ],
                    [
                        'notes' => $app['status'] === 'approved'
                            ? 'Meets admission criteria. Approved for enrollment.'
                            : 'Unable to offer a place for the applied grade this cycle.',
                    ]
                );
            }
        }
    }
}
