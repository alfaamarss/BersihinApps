<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $success ? 'Email Terverifikasi' : 'Verifikasi Gagal' }} - Smart Environment</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, {{ $success ? '#11998e 0%, #38ef7d' : '#ee0979 0%, #ff6a00' }} 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 500px;
            width: 100%;
            padding: 50px 40px;
            text-align: center;
        }
        .icon {
            width: 100px;
            height: 100px;
            margin: 0 auto 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 50px;
            background: {{ $success ? 'linear-gradient(135deg, #11998e 0%, #38ef7d 100%)' : 'linear-gradient(135deg, #ee0979 0%, #ff6a00 100%)' }};
        }
        .icon-inner {
            color: white;
        }
        h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 15px;
            font-weight: 700;
        }
        .message {
            color: #666;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        @if($success && isset($user))
        .user-info {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .user-info p {
            margin: 8px 0;
            color: #555;
        }
        .user-info strong {
            color: #333;
        }
        @endif
        .btn {
            display: inline-block;
            padding: 14px 40px;
            background: {{ $success ? 'linear-gradient(135deg, #11998e 0%, #38ef7d 100%)' : 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' }};
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            transition: transform 0.2s;
            border: none;
            cursor: pointer;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e1e8ed;
        }
        .footer-text {
            color: #999;
            font-size: 14px;
        }
        .checkmark {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: block;
            stroke-width: 2;
            stroke: #fff;
            stroke-miterlimit: 10;
            margin: 10% auto;
            box-shadow: inset 0px 0px 0px #7ac142;
            animation: fill .4s ease-in-out .4s forwards, scale .3s ease-in-out .9s both;
        }
        .checkmark__circle {
            stroke-dasharray: 166;
            stroke-dashoffset: 166;
            stroke-width: 2;
            stroke-miterlimit: 10;
            stroke: #7ac142;
            fill: none;
            animation: stroke 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards;
        }
        .checkmark__check {
            transform-origin: 50% 50%;
            stroke-dasharray: 48;
            stroke-dashoffset: 48;
            animation: stroke 0.3s cubic-bezier(0.65, 0, 0.45, 1) 0.8s forwards;
        }
        @keyframes stroke {
            100% {
                stroke-dashoffset: 0;
            }
        }
        @keyframes scale {
            0%, 100% {
                transform: none;
            }
            50% {
                transform: scale3d(1.1, 1.1, 1);
            }
        }
        @keyframes fill {
            100% {
                box-shadow: inset 0px 0px 0px 30px #7ac142;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        @if($success)
            <div class="icon">
                <svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                    <circle class="checkmark__circle" cx="26" cy="26" r="25" fill="none"/>
                    <path class="checkmark__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
                </svg>
            </div>
            <h1>Email Terverifikasi! ✓</h1>
        @else
            <div class="icon">
                <span class="icon-inner">✕</span>
            </div>
            <h1>Verifikasi Gagal</h1>
        @endif

        <p class="message">{{ $message }}</p>

        @if($success && isset($user))
            <div class="user-info">
                <p><strong>Nama:</strong> {{ $user->name }}</p>
                <p><strong>Email:</strong> {{ $user->email }}</p>
                <p><strong>Terverifikasi pada:</strong> {{ $user->email_verified_at->format('d F Y, H:i') }}</p>
            </div>
        @endif

        @if($success)
            <button onclick="closeWindow()" class="btn">
                Tutup Halaman Ini
            </button>
        @else
            <a href="{{ config('app.url') }}" class="btn">
                Kembali ke Beranda
            </a>
        @endif

        <script>
            function closeWindow() {
                // Try to close the window/tab
                window.close();
                
                // If window.close() doesn't work (security restrictions), show a message
                setTimeout(function() {
                    alert('Silakan tutup tab ini secara manual.');
                }, 300);
            }
        </script>

        <div class="footer">
            <p class="footer-text">🌱 Smart Environment - Aplikasi Pengelolaan Sampah</p>
        </div>
    </div>
</body>
</html>
