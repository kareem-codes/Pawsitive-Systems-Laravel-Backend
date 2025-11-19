<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} - API Server</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
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
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 60px 40px;
            max-width: 600px;
            width: 100%;
            text-align: center;
        }

        h1 {
            font-size: 2.5rem;
            color: #333;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .subtitle {
            font-size: 1.2rem;
            color: #666;
            margin-bottom: 40px;
        }

        .links {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            justify-content: center;
            margin-top: 40px;
        }

        .btn {
            display: inline-block;
            padding: 15px 40px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            min-width: 200px;
        }

        .btn-owner {
            background: #667eea;
            color: white;
        }

        .btn-owner:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-admin {
            background: #764ba2;
            color: white;
        }

        .btn-admin:hover {
            background: #643a8c;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(118, 75, 162, 0.4);
        }

        .api-info {
            margin-top: 50px;
            padding-top: 30px;
            border-top: 2px solid #f0f0f0;
        }

        .api-info p {
            color: #888;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }

        code {
            background: #f5f5f5;
            padding: 5px 15px;
            border-radius: 5px;
            color: #667eea;
            font-size: 0.85rem;
        }

        @media (max-width: 600px) {
            h1 {
                font-size: 2rem;
            }

            .container {
                padding: 40px 20px;
            }

            .btn {
                min-width: 100%;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>🐾 {{ config('app.name') }}</h1>
        <p class="subtitle">Veterinary Clinic Management API</p>

        <div class="links">
            <a href="http://pawsitive-owners.kareem-codes.com/" class="btn btn-owner">
                Owner Dashboard
            </a>
            <a href="https://pawsitive-dashboard.kareem-codes.com/" class="btn btn-admin">
                Admin Dashboard
            </a>
        </div>
    </div>
</body>

</html>