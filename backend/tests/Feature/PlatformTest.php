<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\User;
use App\Models\VideoAsset;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    // ── Public API ──────────────────────────────────────────────

    public function test_public_settings_endpoint_returns_platform_name(): void
    {
        $this->getJson('/api/settings/public')
            ->assertOk()
            ->assertJsonStructure(['platform_name']);
    }

    public function test_courses_list_only_shows_published(): void
    {
        Course::create(['title' => 'Draft Course', 'status' => 'draft']);
        Course::create(['title' => 'Live Course', 'status' => 'published', 'published_at' => now()]);

        $response = $this->getJson('/api/courses')->assertOk();

        $titles = collect($response->json('data'))->pluck('title');
        $this->assertContains('Live Course', $titles);
        $this->assertNotContains('Draft Course', $titles);
    }

    public function test_certificate_verify_returns_404_for_invalid_token(): void
    {
        $this->getJson('/api/certificates/verify/not-a-real-token')->assertNotFound();
    }

    // ── Auth ────────────────────────────────────────────────────

    public function test_student_can_register_and_login(): void
    {
        $this->postJson('/api/auth/register', [
            'name' => 'Test Student',
            'email' => 'student@test.dev',
            'phone' => '01012345678',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ])->assertCreated();

        $this->assertDatabaseHas('users', ['email' => 'student@test.dev']);
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/auth/me')->assertUnauthorized();
    }

    // ── Authorization ───────────────────────────────────────────

    public function test_student_cannot_access_admin_routes(): void
    {
        $student = User::factory()->create();
        $student->assignRole('student');

        $this->actingAs($student)
            ->getJson('/api/admin/stats')
            ->assertForbidden();
    }

    public function test_admin_can_access_admin_routes(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $this->actingAs($admin)
            ->getJson('/api/admin/stats')
            ->assertOk();
    }

    public function test_guest_cannot_access_student_routes(): void
    {
        $this->getJson('/api/me/courses')->assertUnauthorized();
    }

    // ── Video security (CourseShield) ───────────────────────────

    public function test_video_session_denied_without_enrollment(): void
    {
        $student = User::factory()->create();
        $student->assignRole('student');

        $lesson = $this->lessonWithVideo();

        $this->actingAs($student)
            ->postJson('/api/video/session', [
                'lesson_id' => $lesson->id,
                'device_fingerprint' => 'fp-test-123',
            ])->assertForbidden();
    }

    public function test_lesson_payload_never_exposes_drive_file_id(): void
    {
        $student = User::factory()->create();
        $student->assignRole('student');
        $lesson = $this->lessonWithVideo();

        Enrollment::create([
            'user_id' => $student->id,
            'course_id' => $lesson->module->course_id,
            'status' => 'active',
            'enrolled_at' => now(),
        ]);

        $response = $this->actingAs($student)
            ->getJson("/api/courses/{$lesson->module->course->slug}/lessons/{$lesson->id}");

        $body = $response->getContent();
        $this->assertStringNotContainsString('google_file', $body);
        $this->assertStringNotContainsString('drive.google.com', $body);
        $this->assertStringNotContainsString($lesson->videoAsset->google_file_id, $body);
    }

    public function test_stream_endpoint_rejects_unsigned_requests(): void
    {
        $lesson = $this->lessonWithVideo();

        $this->get("/api/video/stream/{$lesson->videoAsset->id}/1/badsig/index.m3u8")
            ->assertForbidden();
    }

    private function lessonWithVideo(): Lesson
    {
        $course = Course::create(['title' => 'Secured Course', 'status' => 'published', 'published_at' => now()]);
        $module = CourseModule::create(['course_id' => $course->id, 'title' => 'Module 1', 'sort_order' => 1]);
        $lesson = Lesson::create([
            'module_id' => $module->id,
            'title' => 'Lesson 1',
            'type' => 'video',
            'sort_order' => 1,
            'is_published' => true,
            'status' => 'published',
        ]);
        $asset = VideoAsset::create([
            'lesson_id' => $lesson->id,
            'provider' => 'mux',
            'google_file_id' => 'SECRET-DRIVE-FILE-ID-123',
            'source_filename' => 'source.mp4',
            'status' => 'ready',
            'playback_id' => 'playback-xyz',
        ]);
        $lesson->update(['video_asset_id' => $asset->id]);

        return $lesson->fresh();
    }
}
