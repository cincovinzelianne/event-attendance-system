<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate {{ $certificate->certificate_no }}</title>
    <style>
        body {
            margin: 0;
            font-family: Georgia, serif;
            background: #f9fafb;
            color: #111827;
        }
        .wrap {
            max-width: 900px;
            margin: 32px auto;
            border: 10px solid #1e3a8a;
            background: #ffffff;
            padding: 48px;
            text-align: center;
        }
        .small {
            color: #4b5563;
            font-size: 14px;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }
        h1 {
            margin: 16px 0 8px;
            font-size: 44px;
            color: #0f172a;
        }
        h2 {
            margin: 10px 0;
            font-size: 34px;
            color: #1d4ed8;
        }
        p {
            font-size: 18px;
            line-height: 1.6;
        }
        .meta {
            margin-top: 30px;
            font-size: 14px;
            color: #374151;
        }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="small">Certificate of Participation</div>
        <h1>{{ config('app.name', 'Event Attendance System') }}</h1>
        <p>This certifies that</p>
        <h2>{{ $certificate->user?->name }}</h2>
        <p>has participated in the event</p>
        <h2>{{ $certificate->event?->title }}</h2>
        <p>held on {{ $certificate->event?->starts_at?->format('F d, Y') }}.</p>

        <div class="meta">
            <div>Certificate No: {{ $certificate->certificate_no }}</div>
            <div>Issued At: {{ $certificate->issued_at?->format('Y-m-d H:i:s') }}</div>
        </div>
    </div>
</body>
</html>
