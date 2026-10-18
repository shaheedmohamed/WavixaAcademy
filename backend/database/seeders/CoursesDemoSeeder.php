<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Project;
use App\Models\Quiz;
use App\Models\VideoAsset;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

/**
 * Demo content: 20 published courses, each with 3 modules, video lessons
 * (reusing a known-good HLS asset on disk), a per-module quiz, and a
 * per-module activity (project). Idempotent — skips existing slugs.
 *
 * Run: php artisan db:seed --class=CoursesDemoSeeder
 */
class CoursesDemoSeeder extends Seeder
{
    private const THUMBS = [
        '/storage/seed-thumbnails/html-css-js.jpg',
        '/storage/seed-thumbnails/dev-project.jpg',
        '/storage/seed-thumbnails/tools-workflow.jpg',
        '/storage/seed-thumbnails/course-plan.jpg',
        '/storage/seed-thumbnails/html-css-js-2.jpg',
    ];

    private const SOURCE_HLS = 'videos/5';

    private array $courses = [
        ['slug' => 'html-css-foundations', 'title' => 'أساسيات HTML وCSS من الصفر', 'level' => 'beginner', 'price' => 0, 'topic' => 'HTML وCSS'],
        ['slug' => 'javascript-zero-to-hero', 'title' => 'JavaScript من الصفر للاحتراف', 'level' => 'beginner', 'price' => 299, 'topic' => 'JavaScript'],
        ['slug' => 'git-github-for-developers', 'title' => 'Git وGitHub للمطورين', 'level' => 'beginner', 'price' => 149, 'topic' => 'Git وGitHub'],
        ['slug' => 'build-your-first-portfolio', 'title' => 'بناء أول موقع شخصي ليك', 'level' => 'beginner', 'price' => 0, 'topic' => 'موقعك الشخصي'],
        ['slug' => 'react-fundamentals-projects', 'title' => 'React من الأساسيات للمشاريع', 'level' => 'intermediate', 'price' => 399, 'topic' => 'React'],
        ['slug' => 'vuejs-practical', 'title' => 'Vue.js عملي خطوة بخطوة', 'level' => 'intermediate', 'price' => 349, 'topic' => 'Vue.js'],
        ['slug' => 'php-laravel-apps', 'title' => 'PHP وLaravel لبناء تطبيقات حقيقية', 'level' => 'intermediate', 'price' => 449, 'topic' => 'Laravel'],
        ['slug' => 'nodejs-express-apis', 'title' => 'Node.js وبناء APIs بـ Express', 'level' => 'intermediate', 'price' => 349, 'topic' => 'Node.js'],
        ['slug' => 'python-for-beginners', 'title' => 'Python للمبتدئين تمامًا', 'level' => 'beginner', 'price' => 249, 'topic' => 'Python'],
        ['slug' => 'databases-sql', 'title' => 'قواعد البيانات وSQL عمليًا', 'level' => 'beginner', 'price' => 199, 'topic' => 'SQL وقواعد البيانات'],
        ['slug' => 'typescript-real-projects', 'title' => 'TypeScript في المشاريع الحقيقية', 'level' => 'intermediate', 'price' => 299, 'topic' => 'TypeScript'],
        ['slug' => 'ui-ux-practical', 'title' => 'تصميم واجهات UI/UX عملي', 'level' => 'beginner', 'price' => 249, 'topic' => 'UI/UX'],
        ['slug' => 'tailwindcss-fast-ui', 'title' => 'Tailwind CSS وبناء واجهات سريعة', 'level' => 'beginner', 'price' => 199, 'topic' => 'Tailwind CSS'],
        ['slug' => 'rest-apis-design-deploy', 'title' => 'REST APIs من التصميم للنشر', 'level' => 'intermediate', 'price' => 349, 'topic' => 'REST APIs'],
        ['slug' => 'docker-for-developers', 'title' => 'Docker للمطورين', 'level' => 'advanced', 'price' => 399, 'topic' => 'Docker'],
        ['slug' => 'web-security-basics', 'title' => 'أساسيات الأمان في تطبيقات الويب', 'level' => 'intermediate', 'price' => 299, 'topic' => 'أمان الويب'],
        ['slug' => 'data-structures-problem-solving', 'title' => 'هياكل البيانات وحل المشكلات', 'level' => 'intermediate', 'price' => 349, 'topic' => 'هياكل البيانات'],
        ['slug' => 'nextjs-fullstack', 'title' => 'Next.js وبناء تطبيقات كاملة', 'level' => 'advanced', 'price' => 449, 'topic' => 'Next.js'],
        ['slug' => 'freelancing-portfolio', 'title' => 'الفريلانس وبناء معرض أعمالك', 'level' => 'all_levels', 'price' => 199, 'topic' => 'العمل الحر'],
        ['slug' => 'final-project-idea-to-deploy', 'title' => 'المشروع النهائي: من الفكرة للنشر', 'level' => 'advanced', 'price' => 499, 'topic' => 'مشروع التخرج'],
    ];

    private array $modulesTemplate = [
        [
            'title' => 'الأساسيات والتحضير',
            'description' => 'تهيئة بيئة العمل وفهم المفاهيم الأساسية قبل البناء.',
            'lessons' => ['مقدمة الكورس وخارطة الطريق', 'تجهيز أدوات العمل خطوة بخطوة', 'أول تطبيق عملي سريع'],
        ],
        [
            'title' => 'التطبيق العملي خطوة بخطوة',
            'description' => 'نبني المكونات الأساسية للمشروع مع شرح كل خطوة.',
            'lessons' => ['بناء الجزء الأول من المشروع', 'بناء الجزء الثاني وربط المكونات', 'مراجعة الكود وتصحيح الأخطاء الشائعة'],
        ],
        [
            'title' => 'المشروع النهائي والنشر',
            'description' => 'نكمل المشروع كاملًا وننشره بشكل حقيقي.',
            'lessons' => ['تنفيذ المشروع النهائي', 'النشر والمشاركة على الإنترنت', 'الخلاصة وخطوتك التالية'],
        ],
    ];

    public function run(): void
    {
        $sourceDir = storage_path('app/private/'.self::SOURCE_HLS);
        $hasVideo = is_dir($sourceDir) && is_file($sourceDir.'/index.m3u8');
        if (! $hasVideo) {
            $this->command->warn('لا يوجد فيديو HLS جاهز في '.self::SOURCE_HLS.' — ستُنشأ الدروس بدون فيديو.');
        }

        foreach ($this->courses as $i => $data) {
            if (Course::where('slug', $data['slug'])->exists()) {
                continue;
            }

            $course = Course::create([
                'instructor_id' => 1,
                'title' => $data['title'],
                'slug' => $data['slug'],
                'short_description' => "كورس عملي في {$data['topic']} — تتعلم بالبناء من أول درس حتى مشروع منشور.",
                'description' => "كورس {$data['title']} مصمم ليأخذك من الأساسيات إلى بناء مشروع حقيقي في {$data['topic']}. كل وحدة تنتهي باختبار قصير ونشاط تطبيقي تُسلّمه للمراجعة.",
                'thumbnail' => self::THUMBS[$i % count(self::THUMBS)],
                'cover' => self::THUMBS[$i % count(self::THUMBS)],
                'price' => $data['price'],
                'currency' => 'EGP',
                'status' => 'published',
                'published_at' => now(),
                'level' => $data['level'],
                'language' => 'ar',
                'is_featured' => $i < 6,
                'sort_order' => $i + 1,
            ]);

            foreach ($this->modulesTemplate as $m => $modTpl) {
                $module = $course->modules()->create([
                    'title' => $modTpl['title'],
                    'description' => $modTpl['description'],
                    'sort_order' => $m + 1,
                    'status' => 'published',
                ]);

                $lastLessonId = null;
                foreach ($modTpl['lessons'] as $l => $lessonTitle) {
                    $lesson = $module->lessons()->create([
                        'title' => "{$lessonTitle} — {$data['topic']}",
                        'slug' => $data['slug']."-m{$m}-l{$l}",
                        'description' => "في هذا الدرس نغطي: {$lessonTitle}.",
                        'duration_seconds' => 240 + (($i * 7 + $m * 3 + $l) % 10) * 60,
                        'sort_order' => $l + 1,
                        'is_preview' => $m === 0 && $l === 0,
                        'is_published' => true,
                        'status' => 'published',
                    ]);
                    $lastLessonId = $lesson->id;

                    if ($hasVideo) {
                        $lesson->video_asset_id = $this->cloneVideoAsset($lesson->id);
                        $lesson->save();
                    }
                }

                $this->seedQuiz($course, $module, $m, $data['topic']);
                $this->seedActivity($course, $module, $m, $lastLessonId, $i);
            }

            $course->update(['duration_minutes' => $course->modules->sum(fn ($mod) => (int) ceil($mod->lessons->sum('duration_seconds') / 60))]);
        }

        $this->command->info('تم إنشاء الكورسات التجريبية.');
    }

    /**
     * Clone the ready HLS asset (asset #5) into a new asset directory so
     * each seeded lesson gets a real playable video.
     */
    private function cloneVideoAsset(int $lessonId): int
    {
        $source = VideoAsset::find(5);

        $asset = VideoAsset::create([
            'lesson_id' => $lessonId,
            'provider' => 'local',
            'source_filename' => 'demo-video.mp4',
            'status' => 'ready',
            'duration_seconds' => $source?->duration_seconds ?? 10,
            'file_size_bytes' => $source?->file_size_bytes,
            'imported_by' => 1,
        ]);

        $asset->update([
            'playback_id' => (string) $asset->id,
            'meta' => ['hls_path' => "videos/{$asset->id}"],
        ]);

        File::copyDirectory(
            storage_path('app/private/'.self::SOURCE_HLS),
            storage_path("app/private/videos/{$asset->id}")
        );

        return $asset->id;
    }

    private function seedQuiz(Course $course, $module, int $m, string $topic): void
    {
        $quiz = Quiz::create([
            'course_id' => $course->id,
            'module_id' => $module->id,
            'title' => "اختبار الوحدة ".($m + 1)." — {$topic}",
            'description' => "اختبار قصير يقيس فهمك لمحتوى الوحدة قبل الانتقال.",
            'passing_score' => 70,
            'max_attempts' => 3,
            'status' => 'published',
        ]);

        $questions = [
            "ما الهدف الأساسي من محتوى هذه الوحدة في {$topic}؟",
            "أي مما يلي يُعد ممارسة صحيحة عند العمل على {$topic}؟",
            "ما الخطوة الأولى عند مواجهة مشكلة أثناء التطبيق؟",
        ];
        $options = [
            ['فهم المفهوم وتطبيقه عمليًا', 'الحفظ النظري فقط', 'تخطي التمارين', 'مشاهدة الفيديو دون تطبيق'],
            ['كتابة الكود بنفسك ومراجعته', 'نسخ الكود دون فهم', 'تجاهل الأخطاء', 'الانتقال السريع للدرس التالي'],
            ['قراءة رسالة الخطأ والبحث عنها', 'حذف المشروع كاملًا', 'تجاهل المشكلة', 'إغلاق المحرر'],
        ];

        foreach ($questions as $q => $question) {
            $qModel = $quiz->questions()->create([
                'question' => $question,
                'type' => 'multiple_choice',
                'points' => 1,
                'sort_order' => $q + 1,
            ]);
            foreach ($options[$q] as $o => $text) {
                $qModel->options()->create([
                    'text' => $text,
                    'is_correct' => $o === 0,
                    'sort_order' => $o + 1,
                ]);
            }
        }
    }

    private function seedActivity(Course $course, $module, int $m, ?int $afterLessonId, int $courseIdx): void
    {
        $types = ['link', 'image', null];

        Project::create([
            'course_id' => $course->id,
            'module_id' => $module->id,
            'after_lesson_id' => $afterLessonId,
            'title' => "نشاط الوحدة ".($m + 1).": طبّق ما تعلمته",
            'description' => 'طبّق ما تعلمته في هذه الوحدة على مثال صغير من عندك، ثم سلّم النتيجة هنا: ارفع صورة أو فيديو لعملك، أو شارك رابطًا.',
            'instructions' => "المطلوب:\n1. نفّذ التطبيق المطلوب في الوحدة بنفسك.\n2. ارفع صورة/فيديو للنتيجة أو ضع رابطها.\n3. اكتب رسالة قصيرة توضح ما فعلته وأين واجهت صعوبة.",
            'attachment_type' => $types[($courseIdx + $m) % count($types)],
            'attachment_url' => $types[($courseIdx + $m) % count($types)] === 'link'
                ? 'https://developer.mozilla.org/ar/'
                : ($types[($courseIdx + $m) % count($types)] === 'image' ? self::THUMBS[$courseIdx % count(self::THUMBS)] : null),
            'status' => 'published',
            'sort_order' => $m + 1,
        ]);
    }
}
