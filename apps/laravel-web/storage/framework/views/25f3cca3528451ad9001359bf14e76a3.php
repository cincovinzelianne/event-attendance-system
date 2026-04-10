<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'title' => 'Admin Page',
    'subtitle' => null,
]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter(([
    'title' => 'Admin Page',
    'subtitle' => null,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

        <title><?php echo e(config('app.name', 'Event Attendance System')); ?> | <?php echo e($title); ?></title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700|fraunces:500,700" rel="stylesheet" />
        <style>
            .theme-admin-light {
                background: linear-gradient(180deg, #f3f4ff 0%, #eceffd 100%);
                color: #2e3a60;
            }

            .theme-admin-light .bg-blue-950,
            .theme-admin-light .bg-blue-950\/85,
            .theme-admin-light .bg-blue-950\/80,
            .theme-admin-light .bg-blue-950\/75 {
                background-color: #ffffff !important;
            }

            .theme-admin-light .bg-white\/5 {
                background-color: #f7f8ff !important;
            }

            .theme-admin-light .bg-blue-700\/25 {
                background-color: rgba(79, 125, 247, 0.14) !important;
            }

            .theme-admin-light .bg-blue-700\/90 {
                background-color: rgba(79, 125, 247, 0.95) !important;
                color: #ffffff !important;
            }

            .theme-admin-light .bg-blue-600\/30 {
                background-color: rgba(79, 125, 247, 0.14) !important;
            }

            .theme-admin-light .bg-yellow-500\/20,
            .theme-admin-light .bg-yellow-500\/25,
            .theme-admin-light .bg-yellow-500\/90,
            .theme-admin-light .bg-yellow-500,
            .theme-admin-light .bg-yellow-400\/20 {
                background-color: rgba(125, 78, 234, 0.14) !important;
            }

            .theme-admin-light .text-white,
            .theme-admin-light .text-slate-100,
            .theme-admin-light .text-slate-200,
            .theme-admin-light .text-slate-300,
            .theme-admin-light .text-slate-400 {
                color: #5f6f96 !important;
            }

            .theme-admin-light h1,
            .theme-admin-light h2,
            .theme-admin-light h3,
            .theme-admin-light .font-semibold,
            .theme-admin-light .font-medium {
                color: #2d3a63;
            }

            .theme-admin-light .text-yellow-300,
            .theme-admin-light .text-yellow-200,
            .theme-admin-light .text-yellow-200\/80,
            .theme-admin-light .text-yellow-200\/70,
            .theme-admin-light .text-yellow-100,
            .theme-admin-light .text-yellow-100\/85,
            .theme-admin-light .text-blue-100,
            .theme-admin-light .text-blue-200 {
                color: #4f7df7 !important;
            }

            .theme-admin-light .text-slate-950 {
                color: #ffffff !important;
            }

            .theme-admin-light .border-white\/10,
            .theme-admin-light .border-white\/15,
            .theme-admin-light .border-white\/20,
            .theme-admin-light .border-yellow-300\/30,
            .theme-admin-light .border-yellow-300\/50,
            .theme-admin-light .border-blue-300\/30 {
                border-color: #dce3f5 !important;
            }

            .theme-admin-light .hover\:bg-white\/10:hover {
                background-color: #eef3ff !important;
            }

                    <nav class="px-4 py-5">
            .theme-admin-light .hover\:bg-yellow-500:hover,
            .theme-admin-light .hover\:bg-blue-700:hover,
            .theme-admin-light .hover\:bg-blue-700\/25:hover {
                background-color: #3f72f0 !important;
                color: #ffffff !important;
            }

            .theme-admin-light .hover\:border-yellow-300\/50:hover {
                border-color: #9eb5fb !important;
            }

            .theme-admin-light .from-blue-700 {
                --tw-gradient-from: #5b83f6 var(--tw-gradient-from-position) !important;
                --tw-gradient-to: rgb(91 131 246 / 0) var(--tw-gradient-to-position) !important;
                --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to) !important;
            }

            .theme-admin-light .to-yellow-500 {
                --tw-gradient-to: #8f58ee var(--tw-gradient-to-position) !important;
            }

            .theme-admin-light .shadow-black\/30 {
                --tw-shadow-color: rgba(79, 125, 247, 0.12) !important;
            }

            .theme-admin-light aside {
                background: linear-gradient(180deg, #0f1f53 0%, #0b1742 52%, #0a1334 100%) !important;
                border-color: rgba(148, 163, 184, 0.25) !important;
                box-shadow: inset -1px 0 0 rgba(255, 255, 255, 0.06);
            }

            .theme-admin-light aside .border-b,
            .theme-admin-light aside .border-t {
                border-color: rgba(148, 163, 184, 0.22) !important;
            }

            .theme-admin-light aside .bg-white\/5 {
                background-color: rgba(255, 255, 255, 0.06) !important;
            }

            .theme-admin-light aside .text-white {
                color: #f8fbff !important;
            }

            .theme-admin-light aside .text-slate-400 {
                color: #a5b6dc !important;
            }

            .theme-admin-light aside .text-slate-300,
            .theme-admin-light aside .text-slate-200,
            .theme-admin-light aside .text-slate-100 {
                color: #d6e0f8 !important;
            }

            .theme-admin-light aside .text-yellow-200,
            .theme-admin-light aside .text-yellow-200\/80,
                    <div class="px-4 pb-4 pt-2">
            .theme-admin-light aside .text-yellow-100 {
                color: #f7d778 !important;
            }

            .theme-admin-light aside .bg-blue-700\/25 {
                background: linear-gradient(90deg, rgba(74, 117, 238, 0.28), rgba(97, 133, 236, 0.16)) !important;
                border: 1px solid rgba(140, 170, 255, 0.28);
                box-shadow: 0 10px 22px rgba(8, 18, 48, 0.35);
            }

            .theme-admin-light aside .hover\:bg-white\/5:hover {
                background-color: rgba(255, 255, 255, 0.1) !important;
                color: #ffffff !important;
            }

            .theme-admin-light aside .rounded-2xl.border {
                border-color: rgba(148, 163, 184, 0.24) !important;
                background: linear-gradient(180deg, rgba(255, 255, 255, 0.08), rgba(255, 255, 255, 0.04)) !important;
            }

            .sidebar-scroll {
                scrollbar-width: thin;
                scrollbar-color: rgba(148, 163, 184, 0.45) transparent;
            }

            .sidebar-scroll::-webkit-scrollbar {
                width: 8px;
            }

            .sidebar-scroll::-webkit-scrollbar-track {
                background: transparent;
            }

            .sidebar-scroll::-webkit-scrollbar-thumb {
                background: rgba(148, 163, 184, 0.4);
                border-radius: 9999px;
            }

            .sidebar-shell {
                background: linear-gradient(180deg, #101f57 0%, #0c1845 55%, #0a1438 100%);
            }

            .nav-section-title {
                font-size: 11px;
                letter-spacing: 0.2em;
                text-transform: uppercase;
                color: #8fa5d6;
                font-weight: 700;
                padding: 0 14px;
            }

            .nav-item {
                display: flex;
                align-items: center;
                gap: 0;
                border-radius: 12px;
                padding: 10px 14px;
                font-size: 15px;
                line-height: 1.25;
                transition: all 150ms ease;
                position: relative;
            }

            .nav-item-active {
                background: linear-gradient(90deg, rgba(74, 117, 238, 0.3), rgba(97, 133, 236, 0.14));
                border: 1px solid rgba(140, 170, 255, 0.32);
                color: #f7d778;
                box-shadow: 0 8px 18px rgba(8, 18, 48, 0.24);
                font-weight: 600;
            }

            .nav-item-active::before {
                content: '';
                position: absolute;
                left: 8px;
                top: 8px;
                bottom: 8px;
                width: 3px;
                border-radius: 9999px;
                background: linear-gradient(180deg, #f7d778, #e3b84c);
            }

            .nav-item-inactive {
                color: #d6e0f8;
            }

            .nav-item-inactive:hover {
                background-color: rgba(255, 255, 255, 0.1);
                color: #ffffff;
            }

        </style>
        <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
    </head>
    <body class="theme-admin-light min-h-screen bg-blue-950 font-sans text-slate-100 antialiased">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_15%_4%,_rgba(91,131,246,0.16),_transparent_30%),radial-gradient(circle_at_85%_0%,_rgba(143,88,238,0.14),_transparent_24%),linear-gradient(180deg,#f4f5ff_0%,#eceffd_100%)]"></div>

        <div class="relative min-h-screen lg:flex">
            <aside class="sidebar-shell border-b border-white/10 bg-blue-950/85 backdrop-blur-xl lg:fixed lg:inset-y-0 lg:left-0 lg:w-80 lg:shrink-0 lg:border-b-0 lg:border-r lg:overflow-hidden">
                <div class="flex h-full flex-col">
                    <div class="border-b border-white/10 px-6 py-6">
                        <a href="<?php echo e(route('admin.dashboard')); ?>" class="inline-flex items-center gap-3">
                            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-yellow-400/20 text-sm font-semibold tracking-[0.2em] text-yellow-200">
                                EA
                            </div>
                            <div>
                                <p class="text-xs uppercase tracking-[0.3em] text-yellow-200/80">Admin Panel</p>
                                <p class="text-sm font-semibold text-white">Event Attendance System</p>
                            </div>
                        </a>
                    </div>

                    <nav class="flex-1 min-h-0 px-4 py-5">
                        <p class="px-3 text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">Navigation</p>

                        <?php
                            $navSections = [
                                [
                                    'title' => null,
                                    'items' => [
                                        ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'pattern' => 'admin.dashboard'],
                                        ['label' => 'Events', 'route' => 'admin.events.index', 'pattern' => 'admin.events.*'],
                                        ['label' => 'Attendance', 'route' => 'admin.attendance.index', 'pattern' => 'admin.attendance.*'],
                                        ['label' => 'QR Scanner', 'route' => 'admin.qr-scanner.index', 'pattern' => 'admin.qr-scanner.*'],
                                        ['label' => 'Notifications', 'route' => 'admin.notifications.index', 'pattern' => 'admin.notifications.*'],
                                    ],
                                ],
                                [
                                    'title' => 'Administration',
                                    'items' => [
                                        ['label' => 'Users', 'route' => 'admin.users.index', 'pattern' => 'admin.users.*'],
                                        ['label' => 'Settings', 'route' => 'admin.settings.index', 'pattern' => 'admin.settings.*'],
                                        ['label' => 'Permissions', 'route' => 'admin.permissions.index', 'pattern' => 'admin.permissions.*'],
                                        ['label' => 'Audit Logs', 'route' => 'admin.audit-logs.index', 'pattern' => 'admin.audit-logs.*'],
                                    ],
                                ],
                                [
                                    'title' => 'Insights & Outputs',
                                    'items' => [
                                        ['label' => 'Certificates', 'route' => 'admin.certificates.index', 'pattern' => 'admin.certificates.*'],
                                        ['label' => 'Evaluations', 'route' => 'admin.evaluations.index', 'pattern' => 'admin.evaluations.*'],
                                        ['label' => 'Analytics', 'route' => 'admin.analytics.index', 'pattern' => 'admin.analytics.*'],
                                        ['label' => 'Diagnostics', 'route' => 'admin.diagnostics.index', 'pattern' => 'admin.diagnostics.*'],
                                    ],
                                ],
                            ];
                        ?>

                        <div class="mt-5 space-y-8">
                            <?php $__currentLoopData = $navSections; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $section): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <section>
                                    <?php if(! empty($section['title'])): ?>
                                        <p class="nav-section-title"><?php echo e($section['title']); ?></p>
                                    <?php endif; ?>
                                    <div class="<?php echo e(! empty($section['title']) ? 'mt-2.5' : 'mt-1'); ?> space-y-1.5">
                                        <?php $__currentLoopData = $section['items']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <?php
                                                $isActive = request()->routeIs($item['pattern']);
                                            ?>
                                            <a href="<?php echo e(route($item['route'])); ?>" class="nav-item <?php echo e($isActive ? 'nav-item-active' : 'nav-item-inactive'); ?>">
                                                <span><?php echo e($item['label']); ?></span>
                                            </a>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </div>
                                </section>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </nav>

                    <div class="px-4 pb-4 pt-2">
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-[10px] uppercase tracking-[0.2em] text-slate-400">Workspace</p>
                                    <p class="mt-1 text-sm font-semibold text-white">Admin Session Active</p>
                                    <p class="mt-1 text-[11px] text-slate-400">Live oversight and controls</p>
                                </div>
                                <div class="rounded-2xl border border-white/10 bg-blue-700/25 px-3 py-2 text-center">
                                    <p class="text-[10px] uppercase tracking-[0.18em] text-yellow-200">Role</p>
                                    <p class="text-xs font-semibold text-white">Admin</p>
                                </div>
                            </div>
                            <div class="mt-4 border-t border-white/10 pt-4">
                                <p class="text-xs uppercase tracking-[0.25em] text-slate-400">Signed in as</p>
                                <p class="mt-2 text-sm font-semibold text-white"><?php echo e(auth()->user()->name); ?></p>
                                <p class="mt-1 text-xs text-slate-400"><?php echo e(auth()->user()->email); ?></p>
                                <div class="mt-4 flex items-center gap-2">
                                    <a href="<?php echo e(route('landing')); ?>" class="rounded-lg border border-white/15 px-3 py-2 text-xs font-medium text-slate-200 transition hover:bg-white/10">Landing</a>
                                    <form method="POST" action="<?php echo e(route('logout')); ?>">
                                        <?php echo csrf_field(); ?>
                                        <button type="submit" class="rounded-lg bg-yellow-500/90 px-3 py-2 text-xs font-semibold text-white transition hover:bg-yellow-500">
                                            Logout
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </aside>

            <main class="p-5 sm:p-8 lg:ml-80 lg:flex-1 lg:p-10">
                <header class="mb-8 flex flex-wrap items-end justify-between gap-5">
                    <div>
                        <p class="text-xs uppercase tracking-[0.3em] text-yellow-200/70">Control Center</p>
                        <h1 class="mt-2 font-['Fraunces'] text-4xl leading-tight text-white sm:text-5xl"><?php echo e($title); ?></h1>
                        <?php if($subtitle): ?>
                            <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-300"><?php echo e($subtitle); ?></p>
                        <?php endif; ?>
                    </div>
                    <?php if(isset($actions)): ?>
                        <div class="flex flex-wrap gap-3">
                            <?php echo e($actions); ?>

                        </div>
                    <?php endif; ?>
                </header>

                <?php if(session('status')): ?>
                    <div class="mb-6 rounded-xl border border-blue-300/30 bg-blue-700/25 px-4 py-3 text-sm text-blue-100">
                        <?php echo e(session('status')); ?>

                    </div>
                <?php endif; ?>

                <?php echo e($slot); ?>

            </main>
        </div>
    </body>
</html>
<?php /**PATH C:\Projects\event-attendance-system\apps\laravel-web\resources\views/components/admin-layout.blade.php ENDPATH**/ ?>