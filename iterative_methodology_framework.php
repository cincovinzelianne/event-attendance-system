<?php
$projectTitle = 'Iterative Methodology Framework';
$systemName = 'QR Code Event Attendance Monitoring System';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($projectTitle . ' - ' . $systemName); ?></title>
    <style>
        :root {
            --ink: #17324d;
            --muted: #5f7285;
            --line: #b7c8d8;
            --page: #eef4f8;
            --card: #ffffff;
            --accent: #ef7d00;
            --accent-soft: #fff0df;
            --blue-soft: #dff1fb;
            --blue-deep: #7fc0e6;
            --green-soft: #dff6ea;
            --green-deep: #4eaf79;
            --shadow: 0 18px 45px rgba(20, 42, 64, 0.12);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            color: var(--ink);
            background:
                radial-gradient(circle at top left, rgba(127, 192, 230, 0.22), transparent 28%),
                radial-gradient(circle at top right, rgba(239, 125, 0, 0.12), transparent 24%),
                linear-gradient(180deg, #f6fbff 0%, var(--page) 100%);
            padding: 28px 18px 40px;
        }

        .sheet {
            max-width: 1120px;
            margin: 0 auto;
            background: var(--card);
            border: 1px solid rgba(23, 50, 77, 0.08);
            border-radius: 28px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .hero {
            padding: 34px 36px 20px;
            text-align: center;
            background: linear-gradient(180deg, #ffffff 0%, #f8fbfd 100%);
            border-bottom: 1px solid rgba(23, 50, 77, 0.08);
        }

        .hero h1 {
            margin: 0;
            font-size: 1.2rem;
            font-weight: 800;
            letter-spacing: 0.03em;
            text-transform: uppercase;
        }

        .hero h2 {
            margin: 8px auto 0;
            font-size: 1.9rem;
            line-height: 1.25;
            max-width: 820px;
        }

        .hero p {
            margin: 12px auto 0;
            max-width: 860px;
            color: var(--muted);
            font-size: 0.98rem;
        }

        .content {
            padding: 22px;
        }

        .top-grid {
            display: grid;
            grid-template-columns: 190px 1fr;
            gap: 14px;
            align-items: stretch;
        }

        .row-label {
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 20px;
            border: 1px solid var(--line);
            background: linear-gradient(180deg, #f6fbff 0%, #edf6fb 100%);
            font-weight: 800;
            font-size: 1.05rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            min-height: 100%;
            padding: 18px;
            text-align: center;
        }

        .requirements-board {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        .board-card,
        .phase-card {
            border: 1px solid var(--line);
            border-radius: 20px;
            background: #fff;
            overflow: hidden;
        }

        .board-title {
            padding: 12px 16px;
            font-weight: 800;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #6a3600;
            background: var(--accent-soft);
            border-bottom: 1px solid rgba(239, 125, 0, 0.2);
        }

        .board-body {
            padding: 16px 18px 18px;
            background: var(--blue-soft);
        }

        .board-body ul,
        .phase-body ul {
            margin: 0;
            padding-left: 18px;
        }

        .board-body li,
        .phase-body li {
            margin: 8px 0;
            line-height: 1.45;
        }

        .phase-stack {
            margin-top: 14px;
            display: grid;
            gap: 14px;
        }

        .phase-card {
            display: grid;
            grid-template-columns: 190px 1fr;
        }

        .phase-name {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 18px 14px;
            text-align: center;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-right: 1px solid var(--line);
            background: linear-gradient(180deg, #f7fbff 0%, #ecf5fb 100%);
        }

        .phase-body {
            padding: 16px 18px;
            background: #fff;
        }

        .phase-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
        }

        .sub-card {
            border: 1px solid rgba(23, 50, 77, 0.12);
            border-radius: 16px;
            background: linear-gradient(180deg, #fdfefe 0%, #f4fafe 100%);
            padding: 14px;
        }

        .sub-card h3 {
            margin: 0 0 10px;
            font-size: 0.98rem;
            color: #134766;
        }

        .full-width-note {
            margin-top: 12px;
            border: 1px solid rgba(78, 175, 121, 0.24);
            border-radius: 16px;
            background: var(--green-soft);
            padding: 14px 16px;
            line-height: 1.5;
        }

        .validation {
            margin-top: 14px;
            border: 1px solid rgba(23, 50, 77, 0.14);
            border-radius: 20px;
            background: linear-gradient(180deg, #eef8ff 0%, #dff1fb 100%);
            padding: 18px 20px;
        }

        .validation h3 {
            margin: 0 0 10px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            font-size: 1rem;
        }

        .footer-note {
            margin-top: 8px;
            color: var(--muted);
            font-size: 0.92rem;
        }

        @media (max-width: 980px) {
            .top-grid,
            .phase-card,
            .phase-grid,
            .requirements-board {
                grid-template-columns: 1fr;
            }

            .phase-name {
                border-right: none;
                border-bottom: 1px solid var(--line);
            }
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
            }

            .sheet {
                box-shadow: none;
                border-radius: 0;
                border: none;
            }
        }
    </style>
</head>
<body>
    <main class="sheet">
        <section class="hero">
            <h1>Iterative System Development Framework</h1>
            <h2><?php echo htmlspecialchars($systemName); ?></h2>
            <p>
                This framework presents the iterative methodology used in developing the QR-based event attendance
                system for student registration, QR generation, event setup, attendance monitoring, notifications,
                reports, and administrative tracking.
            </p>
        </section>

        <section class="content">
            <div class="top-grid">
                <div class="row-label">Requirements</div>
                <div class="requirements-board">
                    <article class="board-card">
                        <div class="board-title">Functional Requirements</div>
                        <div class="board-body">
                            <ul>
                                <li>Student registration, sign in, profile completion, and secure sign out.</li>
                                <li>QR code generation, QR lock and unlock controls, and scan validation.</li>
                                <li>Admin event creation with date, time, venue, course, department, year level, and section filters.</li>
                                <li>Real-time attendance recording with scan timestamps and status tagging.</li>
                                <li>Student notifications, attendance history, dashboards, and PDF export of attendance sheets.</li>
                            </ul>
                        </div>
                    </article>
                    <article class="board-card">
                        <div class="board-title">Non-Functional Requirements</div>
                        <div class="board-body">
                            <ul>
                                <li>Responsive interface for desktop and mobile access.</li>
                                <li>Reliable PHP and MySQL data handling with role-based access for admins and students.</li>
                                <li>Fast QR validation and attendance loading during live events.</li>
                                <li>Data accuracy, duplicate-notification prevention, and secure session handling.</li>
                                <li>Maintainable modular structure for future expansion and reporting improvements.</li>
                            </ul>
                        </div>
                    </article>
                </div>
            </div>

            <div class="phase-stack">
                <article class="phase-card">
                    <div class="phase-name">Analysis</div>
                    <div class="phase-body">
                        <div class="phase-grid">
                            <section class="sub-card">
                                <h3>User and Process Study</h3>
                                <ul>
                                    <li>Identify student and administrator workflows.</li>
                                    <li>Review manual attendance pain points such as slow checking and missing records.</li>
                                    <li>Define event restrictions by department, course, year level, and section.</li>
                                </ul>
                            </section>
                            <section class="sub-card">
                                <h3>Data and Access Review</h3>
                                <ul>
                                    <li>Examine students, attendance, courses, events, and notifications data needs.</li>
                                    <li>Map role permissions for admin management and student self-service access.</li>
                                    <li>Identify required logs for attendance status and scan history.</li>
                                </ul>
                            </section>
                            <section class="sub-card">
                                <h3>Interface Planning</h3>
                                <ul>
                                    <li>Plan student pages for dashboard, QR display, analytics, and notifications.</li>
                                    <li>Plan admin pages for events, students, attendance monitoring, and scanner controls.</li>
                                    <li>Prepare filter-driven navigation for departments and sections.</li>
                                </ul>
                            </section>
                        </div>
                    </div>
                </article>

                <article class="phase-card">
                    <div class="phase-name">Design</div>
                    <div class="phase-body">
                        <div class="phase-grid">
                            <section class="sub-card">
                                <h3>Database Design</h3>
                                <ul>
                                    <li>Structure tables for students, admins, events, attendance, sessions, and notifications.</li>
                                    <li>Support course-department mapping and event targeting logic.</li>
                                    <li>Preserve scan timestamps and attendance status for reports.</li>
                                </ul>
                            </section>
                            <section class="sub-card">
                                <h3>System Architecture</h3>
                                <ul>
                                    <li>Use a modular PHP application with shared auth, notification, and QR utilities.</li>
                                    <li>Separate admin and student workflows while using one central database.</li>
                                    <li>Design for iterative updates without disrupting core attendance operations.</li>
                                </ul>
                            </section>
                            <section class="sub-card">
                                <h3>Prototype and UX</h3>
                                <ul>
                                    <li>Design cards, tables, filters, and attendance sheets for quick event operation.</li>
                                    <li>Shape the QR scanner flow for immediate attendance feedback.</li>
                                    <li>Create summary dashboards for attendance and student grouping.</li>
                                </ul>
                            </section>
                        </div>
                    </div>
                </article>

                <article class="phase-card">
                    <div class="phase-name">Implementation</div>
                    <div class="phase-body">
                        <ul>
                            <li>Develop student modules for registration, login, profile completion, QR code view, analytics, and notifications.</li>
                            <li>Develop admin modules for course management, student browsing, event creation, QR scanner activation, and attendance monitoring.</li>
                            <li>Implement dynamic filtering by college, course, year level, and section for attendance visibility.</li>
                            <li>Integrate attendance recording, notification generation, PDF export, and QR lock or unlock behavior.</li>
                            <li>Apply iterative fixes based on real testing, including duplicate prevention, schema-safe updates, and dashboard corrections.</li>
                        </ul>
                    </div>
                </article>

                <article class="phase-card">
                    <div class="phase-name">Testing</div>
                    <div class="phase-body">
                        <ul>
                            <li>Run functionality testing on sign in, profile completion, QR generation, event selection, and attendance saving.</li>
                            <li>Validate filtering accuracy for college, course, section, and year level views.</li>
                            <li>Test student and admin notifications, duplicate scan handling, and QR activation flows.</li>
                            <li>Perform syntax and database checks after each iterative fix to reduce runtime errors.</li>
                            <li>Confirm responsive layout behavior for cards, tables, and filter panels across screen sizes.</li>
                        </ul>
                    </div>
                </article>

                <article class="phase-card">
                    <div class="phase-name">Deployment</div>
                    <div class="phase-body">
                        <ul>
                            <li>Deploy the system in a local XAMPP environment for staged development and validation.</li>
                            <li>Configure database tables, seed student records, and synchronize course-department mappings.</li>
                            <li>Verify that live event operations such as scanner use, attendance saving, and reporting remain stable.</li>
                            <li>Prepare the system for institutional rollout once user acceptance and data checks are complete.</li>
                        </ul>
                    </div>
                </article>

                <article class="phase-card">
                    <div class="phase-name">Review</div>
                    <div class="phase-body">
                        <ul>
                            <li>Collect feedback from student and admin usage during event attendance operations.</li>
                            <li>Identify bugs, usability gaps, performance issues, and reporting mismatches.</li>
                            <li>Revise modules in short cycles, then retest affected pages and data flows.</li>
                            <li>Repeat the cycle until the attendance workflow becomes accurate, fast, and easy to manage.</li>
                        </ul>
                        <div class="full-width-note">
                            The methodology is iterative because each phase feeds the next cycle. Findings from testing and
                            review are returned to analysis and design, allowing the QR attendance system to improve through
                            repeated refinement instead of a one-time linear build.
                        </div>
                    </div>
                </article>
            </div>

            <section class="validation">
                <h3>Validation</h3>
                <p>
                    The QR Code Event Attendance Monitoring System should undergo validation based on usability,
                    functional correctness, performance efficiency, reliability, security, and maintainability.
                    Validation ensures that attendance scans are recorded accurately, student access is controlled
                    properly, notifications are sent correctly, and administrative monitoring remains dependable
                    before final institutional use.
                </p>
                <p class="footer-note">
                    Prepared as a separate framework file for documentation, presentation, or printing.
                </p>
            </section>
        </section>
    </main>
</body>
</html>
