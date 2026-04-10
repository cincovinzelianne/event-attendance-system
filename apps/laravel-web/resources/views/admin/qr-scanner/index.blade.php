<x-admin-layout title="QR Scanner" subtitle="Use your camera for real-time QR capture, then submit attendance instantly.">
    <div class="grid max-w-6xl gap-6 lg:grid-cols-[1.1fr_0.9fr]">
        <section class="rounded-3xl border border-white/10 bg-blue-950/80 p-6 shadow-2xl shadow-black/30 backdrop-blur-xl">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-lg font-semibold text-white">Live camera scanner</h2>
                <div class="flex flex-wrap gap-2">
                    <button id="start-scan" type="button" class="rounded-xl bg-yellow-500 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-yellow-400">Start camera</button>
                    <button id="stop-scan" type="button" class="rounded-xl border border-white/20 px-4 py-2 text-sm font-medium text-slate-200 transition hover:bg-white/10" disabled>Stop camera</button>
                    <label for="qr-image" class="cursor-pointer rounded-xl border border-white/20 px-4 py-2 text-sm font-medium text-slate-200 transition hover:bg-white/10">Scan image</label>
                    <input id="qr-image" type="file" accept="image/*" class="hidden">
                </div>
            </div>

            <p class="mt-3 text-sm text-slate-300">
                Point your camera at a student QR code. Once detected, the token field is filled automatically.
            </p>

            <div class="mt-4 overflow-hidden rounded-2xl border border-white/10 bg-blue-950/80 p-3">
                <div id="qr-reader" class="min-h-[320px] w-full"></div>
                <div id="qr-status" class="mt-3 rounded-lg bg-white/5 px-3 py-2 text-xs text-slate-300">
                    Camera is idle. Click "Start camera" to begin scanning.
                </div>
            </div>
        </section>

        <section class="rounded-3xl border border-white/10 bg-blue-950/80 p-6 shadow-2xl shadow-black/30 backdrop-blur-xl">
            <h2 class="text-lg font-semibold text-white">Attendance check-in</h2>
            <p class="mt-2 text-sm text-slate-300">Review the captured token and submit it for event attendance.</p>

            <form id="scan-form" method="POST" action="{{ route('admin.qr-scanner.store') }}" class="mt-5 space-y-4">
                @csrf

                <div>
                    <label for="event_id" class="block text-sm font-medium text-slate-200">Event</label>
                    <select id="event_id" name="event_id" required class="mt-1 block w-full rounded-xl border-white/10 bg-white/5 text-white shadow-sm focus:border-yellow-300 focus:ring-yellow-300">
                        <option value="">-- Select Event --</option>
                        @foreach ($events as $event)
                            <option value="{{ $event->id }}" @selected(old('event_id') == $event->id)>
                                {{ $event->title }} ({{ $event->starts_at?->format('Y-m-d H:i') }})
                            </option>
                        @endforeach
                    </select>
                    @error('event_id')<p class="mt-1 text-sm text-yellow-200">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="token" class="block text-sm font-medium text-slate-200">Scanned QR Token</label>
                    <textarea id="token" name="token" rows="6" required class="mt-1 block w-full rounded-xl border-white/10 bg-white/5 text-xs text-white shadow-sm focus:border-yellow-300 focus:ring-yellow-300">{{ old('token') }}</textarea>
                    @error('token')<p class="mt-1 text-sm text-yellow-200">{{ $message }}</p>@enderror
                </div>

                <label class="inline-flex items-center gap-2 text-sm text-slate-300">
                    <input id="auto-submit" type="checkbox" class="rounded border-white/20 bg-white/5 text-yellow-300 focus:ring-yellow-300">
                    Auto-submit after successful scan
                </label>

                <div class="flex flex-wrap items-center gap-3">
                    <button type="submit" class="rounded-xl bg-gradient-to-r from-blue-700 to-yellow-500 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:-translate-y-0.5">Process Scan</button>
                    <button id="clear-token" type="button" class="rounded-xl border border-white/20 px-4 py-2 text-sm font-medium text-slate-200 transition hover:bg-white/10">Clear token</button>
                </div>
            </form>
        </section>
    </div>

    <script src="https://unpkg.com/html5-qrcode" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const tokenField = document.getElementById('token');
            const eventField = document.getElementById('event_id');
            const statusField = document.getElementById('qr-status');
            const form = document.getElementById('scan-form');
            const startBtn = document.getElementById('start-scan');
            const stopBtn = document.getElementById('stop-scan');
            const imageInput = document.getElementById('qr-image');
            const clearBtn = document.getElementById('clear-token');
            const autoSubmit = document.getElementById('auto-submit');

            let qrScanner = null;
            let isRunning = false;

            const setStatus = (message, tone) => {
                const tones = {
                    idle: 'bg-white/5 text-slate-300',
                    active: 'bg-blue-700/25 text-yellow-200',
                    success: 'bg-blue-700/25 text-blue-100',
                    error: 'bg-yellow-500/20 text-yellow-100',
                };

                statusField.className = 'mt-3 rounded-lg px-3 py-2 text-xs ' + (tones[tone] || tones.idle);
                statusField.textContent = message;
            };

            const handleDecode = async (decodedText) => {
                tokenField.value = decodedText.trim();
                setStatus('QR token captured successfully.', 'success');

                if (isRunning) {
                    await stopScanner();
                }

                if (autoSubmit.checked) {
                    if (!eventField.value) {
                        setStatus('Select an event before auto-submit can continue.', 'error');
                        return;
                    }

                    form.submit();
                }
            };

            const startScanner = async () => {
                if (isRunning) {
                    return;
                }

                if (typeof Html5Qrcode === 'undefined') {
                    setStatus('QR scanner library failed to load. Please refresh.', 'error');
                    return;
                }

                try {
                    qrScanner = new Html5Qrcode('qr-reader');
                    await qrScanner.start(
                        { facingMode: 'environment' },
                        {
                            fps: 10,
                            qrbox: { width: 260, height: 260 },
                            aspectRatio: 1.0,
                        },
                        (decodedText) => {
                            handleDecode(decodedText);
                        },
                        () => {
                            // Ignore per-frame decode failures.
                        }
                    );

                    isRunning = true;
                    startBtn.disabled = true;
                    stopBtn.disabled = false;
                    setStatus('Camera active. Align student QR code inside the frame.', 'active');
                } catch (error) {
                    setStatus('Unable to start camera scanner. Check camera permissions.', 'error');
                }
            };

            const stopScanner = async () => {
                if (!qrScanner || !isRunning) {
                    return;
                }

                try {
                    await qrScanner.stop();
                    await qrScanner.clear();
                } catch (error) {
                    // No-op.
                }

                isRunning = false;
                startBtn.disabled = false;
                stopBtn.disabled = true;
                setStatus('Camera stopped. You can start scanning again.', 'idle');
            };

            startBtn.addEventListener('click', function () {
                startScanner();
            });

            stopBtn.addEventListener('click', function () {
                stopScanner();
            });

            clearBtn.addEventListener('click', function () {
                tokenField.value = '';
                setStatus('Token field cleared.', 'idle');
            });

            imageInput.addEventListener('change', async function (event) {
                const file = event.target.files && event.target.files[0];

                if (!file) {
                    return;
                }

                if (typeof Html5Qrcode === 'undefined') {
                    setStatus('QR scanner library failed to load. Please refresh.', 'error');
                    return;
                }

                try {
                    if (isRunning) {
                        await stopScanner();
                    }

                    const fileScanner = new Html5Qrcode('qr-reader');
                    const decodedText = await fileScanner.scanFile(file, true);
                    await fileScanner.clear();
                    await handleDecode(decodedText);
                } catch (error) {
                    setStatus('No readable QR code found in the selected image.', 'error');
                } finally {
                    imageInput.value = '';
                }
            });

            window.addEventListener('beforeunload', function () {
                if (qrScanner && isRunning) {
                    qrScanner.stop();
                }
            });
        });
    </script>
</x-admin-layout>
