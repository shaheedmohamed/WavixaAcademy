import { createRouter, createWebHistory } from 'vue-router';
import { auth, fetchMe, isInstructor } from '../stores/auth';

const routes = [
  {
    path: '/',
    component: () => import('../layouts/PublicLayout.vue'),
    children: [
      { path: '', name: 'home', component: () => import('../views/public/HomeView.vue') },
      { path: 'courses', name: 'courses', component: () => import('../views/public/CoursesView.vue') },
      { path: 'courses/:slug', name: 'course', component: () => import('../views/public/CourseDetailsView.vue') },
      { path: 'events', name: 'events', component: () => import('../views/public/EventsView.vue') },
      { path: 'events/:slug', name: 'event', component: () => import('../views/public/EventDetailsView.vue') },
      { path: 'instructors', name: 'instructors', component: () => import('../views/public/InstructorsView.vue') },
      { path: 'about', name: 'about', component: () => import('../views/public/AboutView.vue') },
      { path: 'faq', name: 'faq', component: () => import('../views/public/FaqView.vue') },
      { path: 'contact', name: 'contact', component: () => import('../views/public/ContactView.vue') },
      { path: 'terms', name: 'terms', component: () => import('../views/public/TermsView.vue') },
      { path: 'privacy', name: 'privacy', component: () => import('../views/public/PrivacyView.vue') },
      { path: 'certificates/:token', name: 'certificate-verify', component: () => import('../views/public/CertificateVerifyView.vue') },
    ],
  },
  {
    path: '/',
    component: () => import('../layouts/AuthLayout.vue'),
    meta: { guest: true },
    children: [
      { path: 'login', name: 'login', component: () => import('../views/auth/LoginView.vue') },
      { path: 'register', name: 'register', component: () => import('../views/auth/RegisterView.vue') },
      { path: 'forgot-password', name: 'forgot', component: () => import('../views/auth/ForgotPasswordView.vue') },
      { path: 'reset-password', name: 'reset-password', component: () => import('../views/auth/ResetPasswordView.vue') },
    ],
  },
  {
    path: '/dashboard',
    component: () => import('../layouts/DashboardLayout.vue'),
    meta: { auth: true },
    children: [
      { path: '', name: 'dashboard', component: () => import('../views/dashboard/DashboardHome.vue') },
      { path: 'courses', name: 'my-courses', component: () => import('../views/dashboard/MyCoursesView.vue') },
      { path: 'courses/:slug', name: 'my-course', component: () => import('../views/dashboard/CourseView.vue') },
      { path: 'courses/:slug/lessons/:lessonId', name: 'lesson-player', component: () => import('../views/dashboard/LessonPlayerView.vue') },
      { path: 'quizzes/:id', name: 'quiz', component: () => import('../views/dashboard/QuizView.vue') },
      { path: 'events', name: 'my-events', component: () => import('../views/dashboard/MyEventsView.vue') },
      { path: 'certificates', name: 'my-certificates', component: () => import('../views/dashboard/CertificatesView.vue') },
      { path: 'notifications', name: 'notifications', component: () => import('../views/dashboard/NotificationsView.vue') },
      { path: 'profile', name: 'profile', component: () => import('../views/dashboard/ProfileView.vue') },
    ],
  },
  {
    path: '/admin',
    component: () => import('../layouts/AdminLayout.vue'),
    meta: { auth: true, admin: true },
    children: [
      { path: '', name: 'admin', component: () => import('../views/admin/AdminDashboard.vue') },
      { path: 'students', name: 'admin-students', component: () => import('../views/admin/StudentsView.vue') },
      { path: 'students/:id', name: 'admin-student', component: () => import('../views/admin/StudentDetailView.vue') },
      { path: 'instructors', name: 'admin-instructors', component: () => import('../views/admin/InstructorsView.vue') },
      { path: 'instructors/create', name: 'admin-instructor-create', component: () => import('../views/admin/AddInstructorView.vue') },
      { path: 'instructors/:id', name: 'admin-instructor-edit', component: () => import('../views/admin/InstructorEditView.vue') },
      { path: 'courses', name: 'admin-courses', component: () => import('../views/admin/AdminCoursesView.vue') },
      { path: 'courses/:id', name: 'admin-course-edit', component: () => import('../views/admin/AdminCourseEditView.vue') },
      { path: 'video-imports', name: 'admin-video-imports', component: () => import('../views/admin/VideoImportsView.vue') },
      { path: 'enrollments', name: 'admin-enrollments', component: () => import('../views/admin/EnrollmentsView.vue') },
      { path: 'submissions', name: 'admin-submissions', component: () => import('../views/admin/SubmissionsView.vue') },
      { path: 'quizzes', name: 'admin-quizzes', component: () => import('../views/admin/QuizzesView.vue') },
      { path: 'quizzes/:id', name: 'admin-quiz-edit', component: () => import('../views/admin/QuizEditView.vue') },
      { path: 'events', name: 'admin-events', component: () => import('../views/admin/AdminEventsView.vue') },
      { path: 'events/:id/registrations', name: 'admin-event-registrations', component: () => import('../views/admin/EventRegistrationsView.vue') },
      { path: 'checkin', name: 'admin-checkin', component: () => import('../views/admin/CheckinView.vue') },
      { path: 'certificates', name: 'admin-certificates', component: () => import('../views/admin/AdminCertificatesView.vue') },
      { path: 'announcements', name: 'admin-announcements', component: () => import('../views/admin/AnnouncementsView.vue') },
      { path: 'faqs', name: 'admin-faqs', component: () => import('../views/admin/FaqsView.vue') },
      { path: 'security', name: 'admin-security', component: () => import('../views/admin/SecurityView.vue') },
      { path: 'audit-logs', name: 'admin-audit', component: () => import('../views/admin/AuditLogsView.vue') },
      { path: 'settings', name: 'admin-settings', component: () => import('../views/admin/AdminSettingsView.vue') },
    ],
  },
  {
    path: '/instructor',
    component: () => import('../layouts/InstructorLayout.vue'),
    meta: { auth: true, instructor: true },
    children: [
      { path: '', name: 'instructor', component: () => import('../views/instructor/InstructorDashboard.vue') },
      { path: 'courses', name: 'instructor-courses', component: () => import('../views/instructor/InstructorCoursesView.vue') },
      { path: 'courses/:id', name: 'instructor-course-edit', component: () => import('../views/instructor/InstructorCourseEditView.vue') },
      { path: 'students', name: 'instructor-students', component: () => import('../views/instructor/InstructorStudentsView.vue') },
      { path: 'quizzes', name: 'instructor-quizzes', component: () => import('../views/instructor/InstructorQuizzesView.vue') },
      { path: 'quizzes/:id', name: 'instructor-quiz-edit', component: () => import('../views/instructor/InstructorQuizEditView.vue') },
      { path: 'quiz-attempts', name: 'instructor-quiz-attempts', component: () => import('../views/instructor/InstructorQuizAttemptsView.vue') },
    ],
  },
  { path: '/:pathMatch(.*)*', name: 'not-found', component: () => import('../views/public/NotFoundView.vue') },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior(to, from, saved) {
    return saved || { top: 0 };
  },
});

router.beforeEach(async (to) => {
  if (!auth.ready) {
    try { await fetchMe(); } catch { /* guest */ }
  }

  if (to.meta.auth && !auth.user) {
    return { name: 'login', query: { redirect: to.fullPath } };
  }
  if (to.meta.admin && !auth.user?.is_admin) {
    return { name: 'dashboard' };
  }
  if (to.meta.instructor && !isInstructor.value) {
    return { name: 'dashboard' };
  }
  if (to.meta.guest && auth.user) {
    if (auth.user.is_admin) return { name: 'admin' };
    if (isInstructor.value) return { name: 'instructor' };
    return { name: 'dashboard' };
  }
});

export default router;
