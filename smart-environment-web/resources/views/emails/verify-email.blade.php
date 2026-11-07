<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Email</title>
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
            color: #4F46E5;
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
            background-color: #4F46E5;
            color: white !important;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            margin: 20px 0;
        }
        .button:hover {
            background-color: #4338CA;
        }
        .link {
            word-break: break-all;
            color: #4F46E5;
            font-size: 14px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Smart Environment</h1>
        </div>
        <div class="content">
            <h2>Verifikasi Email Anda</h2>
            <p>Terima kasih telah mendaftar di Smart Environment!</p>
            <p>Untuk melanjutkan, silakan verifikasi alamat email Anda dengan mengklik tombol di bawah ini:</p>
            
            <div style="text-align: center;">
                <a href="{{ $url }}" class="button">Verifikasi Email</a>
            </div>
            
            <p>Atau copy dan paste link berikut ke browser Anda:</p>
            <p class="link">{{ $url }}</p>
            
            <p><strong>Link ini akan kadaluarsa dalam 24 jam.</strong></p>
            
            <hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;">
            
            <p style="font-size: 14px; color: #666;">
                Jika Anda tidak mendaftar di Smart Environment, abaikan email ini.
            </p>
        </div>
        <div class="footer">
            <p>© {{ date('Y') }} Smart Environment. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
