<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .container {
            background-color: #f9f9f9;
            border-radius: 10px;
            padding: 30px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #DC2626;
            margin: 0;
        }
        .content {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background-color: #DC2626;
            color: white !important;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            margin: 20px 0;
        }
        .button:hover {
            background-color: #B91C1C;
        }
        .link {
            word-break: break-all;
            color: #DC2626;
            font-size: 14px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 12px;
            color: #666;
        }
        .warning {
            background-color: #FEF3C7;
            border-left: 4px solid #F59E0B;
            padding: 15px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Smart Environment</h1>
        </div>
        <div class="content">
            <h2>Reset Password</h2>
            <p>Kami menerima permintaan untuk mereset password akun Anda.</p>
            <p>Silakan klik tombol di bawah ini untuk mereset password:</p>
            
            <div style="text-align: center;">
                <a href="{{ $url }}" class="button">Reset Password</a>
            </div>
            
            <p>Atau copy dan paste link berikut ke browser Anda:</p>
            <p class="link">{{ $url }}</p>
            
            <div class="warning">
                <strong>⚠️ Penting:</strong>
                <ul style="margin: 10px 0; padding-left: 20px;">
                    <li>Link ini akan kadaluarsa dalam <strong>1 jam</strong></li>
                    <!-- <li>Token: <code>{{ $token }}</code></li> -->
                </ul>
            </div>
            
            <hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;">
            
            <p style="font-size: 14px; color: #666;">
                Jika Anda tidak meminta reset password, abaikan email ini. Password Anda tidak akan berubah.
            </p>
        </div>
        <div class="footer">
            <p>© {{ date('Y') }} Smart Environment. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
