<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSV Import Only - Attendance</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            max-width: 600px;
            width: 100%;
            padding: 40px;
            text-align: center;
        }
        h1 {
            color: #333;
            margin-bottom: 20px;
            font-size: 2rem;
        }
        .icon {
            font-size: 80px;
            margin-bottom: 20px;
        }
        p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 15px;
        }
        .info-box {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin: 20px 0;
            text-align: left;
        }
        .info-box h3 {
            color: #667eea;
            margin-bottom: 10px;
        }
        .btn {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 12px 30px;
            border-radius: 25px;
            text-decoration: none;
            margin-top: 20px;
            transition: all 0.3s;
        }
        .btn:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">📊</div>
        <h1>Attendance System</h1>
        <p><strong>CSV Import Only</strong></p>
        
        <div class="info-box">
            <h3>How It Works:</h3>
            <p>• HR users can import attendance records via CSV files</p>
            <p>• Files are processed and stored in the database</p>
            <p>• Re-importing the same file updates existing records</p>
            <p>• All historical data is preserved</p>
        </div>

        <div class="info-box">
            <h3>Removed Features:</h3>
            <p>• Fingerprint scanning (removed)</p>
            <p>• QR code attendance (removed)</p>
        </div>

        <p>Please log in through the main system to access attendance management.</p>
        
        <a href="../index.php" class="btn">Go to Login</a>
    </div>
</body>
</html>
