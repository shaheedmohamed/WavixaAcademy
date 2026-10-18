<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Lesson;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminCourseController extends Controller
{
    public function __construct(private AuditLogger $audit) {}

    public function index(Request $request): JsonResponse
    {
        $query = Course::with('instructor:id,name')
            ->withCount(['modules', 'enrollments'])
            ->latest();

        if ($search = $request->string('q')->trim()->toString()) {
            $query->where('title', 'like', "%{$search}%");
        }
        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        return response()->json($query->paginate(20));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validateCourse($request);
        $course = Course::create($data);
        $this->audit->log('course.created', $course);

        return response()->json(['course' => $course], 201);
    }

    public function show(Course $course): JsonResponse
    {
        $course->load([
            'instructor:id,name',
            'modules.lessons.videoAsset:id,lesson_id,status,playback_id,duration_seconds,error_message',
            'modules.lessons.resources',
            'cohorts',
            'projects',
            'quizzes.questions.options',
        ]);

        return response()->json(['course' => $course]);
    }

    public function update(Request $request, Course $course): JsonResponse
    {
        $data = $this->validateCourse($request, $course->id);
        $course->update($data);
        $this->audit->log('course.updated', $course);

        return response()->json(['course' => $course]);
    }

    public function destroy(Course $course): JsonResponse
    {
        $this->audit->log('course.deleted', $course);
        $course->delete();

        return response()->json(['ok' => true]);
    }

    // ---------- Modules ----------

    public function storeModule(Request $request, Course $course): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'description' => ['nullable', 'string', 'max:2000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', Rule::in(['draft', 'published'])],
        ]);

        $module = $course->modules()->create($data + ['sort_order' => ($course->modules()->max('sort_order') ?? 0) + 1]);

        return response()->json(['module' => $module], 201);
    }

    public function updateModule(Request $request, CourseModule $module): JsonResponse
    {
        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:190'],
            'description' => ['nullable', 'string', 'max:2000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', Rule::in(['draft', 'published'])],
        ]);

        $module->update($data);

        return response()->json(['module' => $module]);
    }

    public function destroyModule(CourseModule $module): JsonResponse
    {
        $module->delete();

        return response()->json(['ok' => true]);
    }

    // ---------- Lessons ----------

    public function storeLesson(Request $request, CourseModule $module): JsonResponse
    {
        $data = $this->validateLesson($request);
        $lesson = $module->lessons()->create($data + [
            'sort_order' => ($module->lessons()->max('sort_order') ?? 0) + 1,
        ]);

        return response()->json(['lesson' => $lesson], 201);
    }

    public function updateLesson(Request $request, Lesson $lesson): JsonResponse
    {
        $lesson->update($this->validateLesson($request, true));

        return response()->json(['lesson' => $lesson]);
    }

    public function destroyLesson(Lesson $lesson): JsonResponse
    {
        $lesson->delete();

        return response()->json(['ok' => true]);
    }

    // ---------- Cohorts ----------

    public function storeCohort(Request $request, Course $course): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', Rule::in(['open', 'closed'])],
        ]);

        $cohort = $course->cohorts()->create($data);

        return response()->json(['cohort' => $cohort], 201);
    }

    public function instructors(): JsonResponse
    {
        $instructors = User::role('instructor')->get(['id', 'name']);

        return response()->json(['instructors' => $instructors]);
    }

    private function validateCourse(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'slug' => ['nullable', 'string', 'max:190', Rule::unique('courses', 'slug')->ignore($ignoreId)],
            'short_description' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'thumbnail' => ['nullable', 'string', 'max:500'],
            'cover' => ['nullable', 'string', 'max:500'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:8'],
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
            'published_at' => ['nullable', 'date'],
            'duration_minutes' => ['nullable', 'integer', 'min:0'],
            'level' => ['required', Rule::in(['beginner', 'intermediate', 'advanced', 'all_levels'])],
            'language' => ['nullable', 'string', 'max:8'],
            'instructor_id' => ['nullable', 'exists:users,id'],
            'is_featured' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'certificate_enabled' => ['nullable', 'boolean'],
            'certificate_title' => ['nullable', 'string', 'max:190'],
            'certificate_language' => ['nullable', 'string', 'in:ar,en,both'],
        ]);
    }

    private function validateLesson(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'title' => [$required, 'string', 'max:190'],
            'slug' => ['nullable', 'string', 'max:190'],
            'description' => ['nullable', 'string', 'max:5000'],
            'duration_seconds' => ['nullable', 'integer', 'min:0'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_preview' => ['nullable', 'boolean'],
            'is_published' => ['nullable', 'boolean'],
            'status' => ['nullable', Rule::in(['draft', 'published'])],
        ]);
    }
}
