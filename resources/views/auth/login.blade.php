<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingreso | IoT Monitoring</title>

    <style>
        :root {
            --bg: #07111f;
            --panel: #0d1729;
            --border: rgba(120, 160, 220, .22);
            --cyan: #22d3ee;
            --purple: #a855f7;
            --green: #22c55e;
            --text: #f8fafc;
            --muted: #9fb3d1;
            --danger: #fb7185;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background:
                radial-gradient(circle at 15% 15%, rgba(34, 211, 238, .18), transparent 28%),
                radial-gradient(circle at 85% 10%, rgba(168, 85, 247, .20), transparent 30%),
                linear-gradient(135deg, #06101d, #0b1024 55%, #140f2e);
            color: var(--text);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 28px;
        }

        .login-shell {
            width: min(1080px, 100%);
            display: grid;
            grid-template-columns: 1.1fr .9fr;
            gap: 24px;
            align-items: stretch;
        }

        .hero-card,
        .login-card {
            border: 1px solid var(--border);
            background: rgba(13, 23, 41, .82);
            box-shadow: 0 24px 80px rgba(0, 0, 0, .35);
            backdrop-filter: blur(16px);
            border-radius: 28px;
            overflow: hidden;
        }

        .hero-card {
            padding: 42px;
            position: relative;
            min-height: 520px;
        }

        .hero-card::after {
            content: "";
            position: absolute;
            inset: auto -80px -90px auto;
            width: 280px;
            height: 280px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(34, 211, 238, .35), rgba(168, 85, 247, .35));
            filter: blur(8px);
            opacity: .8;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 1px solid rgba(34, 211, 238, .35);
            background: rgba(34, 211, 238, .10);
            color: #a5f3fc;
            padding: 8px 14px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .dot {
            width: 9px;
            height: 9px;
            border-radius: 50%;
            background: var(--green);
            box-shadow: 0 0 18px rgba(34, 197, 94, .9);
        }

        h1 {
            margin: 26px 0 14px;
            font-size: clamp(42px, 5vw, 72px);
            line-height: .92;
            letter-spacing: -0.06em;
        }

        .gradient {
            background: linear-gradient(90deg, #67e8f9, #a78bfa, #f0abfc);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .hero-text {
            max-width: 620px;
            color: #b9c8df;
            font-size: 17px;
            line-height: 1.65;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
            margin-top: 34px;
            position: relative;
            z-index: 1;
        }

        .stat {
            border: 1px solid rgba(120, 160, 220, .18);
            background: rgba(6, 15, 30, .55);
            border-radius: 20px;
            padding: 18px;
        }

        .stat strong {
            display: block;
            font-size: 28px;
            margin-bottom: 4px;
        }

        .stat span {
            font-size: 12px;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .08em;
        }

        .login-card {
            padding: 36px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-icon {
            width: 54px;
            height: 54px;
            display: grid;
            place-items: center;
            border-radius: 18px;
            background: linear-gradient(135deg, #22d3ee, #a855f7);
            margin-bottom: 20px;
            box-shadow: 0 18px 40px rgba(34, 211, 238, .20);
        }

        .login-title {
            font-size: 28px;
            font-weight: 900;
            margin: 0;
            letter-spacing: -0.04em;
        }

        .login-subtitle {
            margin: 8px 0 26px;
            color: var(--muted);
            line-height: 1.5;
        }

        .field { margin-bottom: 16px; }

        label {
            display: block;
            margin-bottom: 8px;
            color: #bcd2ef;
            font-size: 13px;
            font-weight: 800;
        }

        input[type="email"],
        input[type="password"] {
            width: 100%;
            height: 52px;
            border-radius: 16px;
            border: 1px solid rgba(120, 160, 220, .24);
            background: rgba(6, 15, 30, .72);
            color: var(--text);
            padding: 0 16px;
            outline: none;
            font-weight: 700;
        }

        input:focus {
            border-color: rgba(34, 211, 238, .85);
            box-shadow: 0 0 0 4px rgba(34, 211, 238, .12);
        }

        .remember {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--muted);
            font-size: 14px;
            margin: 4px 0 22px;
        }

        .remember input {
            width: 16px;
            height: 16px;
            accent-color: var(--cyan);
        }

        .btn-login {
            width: 100%;
            height: 54px;
            border: 1px solid rgba(34, 211, 238, .65);
            background: linear-gradient(135deg, rgba(34, 211, 238, .28), rgba(168, 85, 247, .26));
            color: white;
            font-weight: 900;
            border-radius: 18px;
            cursor: pointer;
            box-shadow: 0 16px 38px rgba(34, 211, 238, .12);
            transition: .2s ease;
        }

        .btn-login:hover {
            transform: translateY(-1px);
            border-color: rgba(34, 211, 238, 1);
        }

        .error {
            margin-bottom: 16px;
            border: 1px solid rgba(251, 113, 133, .35);
            background: rgba(251, 113, 133, .10);
            color: #fecdd3;
            border-radius: 16px;
            padding: 12px 14px;
            font-size: 14px;
            line-height: 1.45;
        }

        .footer-note {
            margin-top: 20px;
            color: #7d91af;
            font-size: 12px;
            text-align: center;
        }

        @media (max-width: 900px) {
            .login-shell { grid-template-columns: 1fr; }
            .hero-card { min-height: auto; }
        }
    </style>
</head>

<body>
    <main class="login-shell">
        <section class="hero-card">
            <div class="badge">
                <span class="dot"></span>
                IoT Monitoring Platform
            </div>

            <h1>
                Control térmico<br>
                <span class="gradient">inteligente</span>
            </h1>

            <p class="hero-text">
                Accedé al panel de monitoreo para visualizar cámaras frigoríficas,
                analizar tendencias, gestionar sensores y administrar alertas operativas.
            </p>

            <div class="stats">
                <div class="stat">
                    <strong>24/7</strong>
                    <span>Monitoreo</span>
                </div>
                <div class="stat">
                    <strong>IoT</strong>
                    <span>Sensores</span>
                </div>
                <div class="stat">
                    <strong>QA</strong>
                    <span>Calidad</span>
                </div>
            </div>
        </section>

        <section class="login-card">
            <div class="login-icon">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none">
                    <path d="M12 2L20 6V12C20 17.2 16.6 21.4 12 22C7.4 21.4 4 17.2 4 12V6L12 2Z" stroke="white" stroke-width="2"/>
                    <path d="M9 12L11 14L15.5 9.5" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>

            <h2 class="login-title">Ingresar al sistema</h2>
            <p class="login-subtitle">
                Usá tus credenciales para acceder al dashboard operativo.
            </p>

            @if ($errors->any())
                <div class="error">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login.post') }}">
                @csrf

                <div class="field">
                    <label for="email">Email</label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email', 'admin@iot.local') }}"
                        placeholder="admin@iot.local"
                        autocomplete="email"
                        required
                        autofocus
                    >
                </div>

                <div class="field">
                    <label for="password">Contraseña</label>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        placeholder="••••••••"
                        autocomplete="current-password"
                        required
                    >
                </div>

                <label class="remember">
                    <input type="checkbox" name="remember">
                    Mantener sesión iniciada
                </label>

                <button class="btn-login" type="submit">
                    Entrar al dashboard
                </button>
            </form>

            <div class="footer-note">
                Acceso restringido a personal autorizado.
            </div>
        </section>
    </main>
</body>
</html>
