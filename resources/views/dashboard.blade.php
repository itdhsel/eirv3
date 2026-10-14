
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>eIRv3</title>

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            color: #243247;
        }

        header {
            background: #183153;
            padding: 22px 35px;
            color: white;
        }

        .container {
            max-width: 1100px;
            margin: 50px auto;
            padding: 0 20px;
        }

        .card {
            background: white;
            border-radius: 10px;
            padding: 35px;
            box-shadow: 0 2px 10px #00000012;
        }

        h1 { margin-top: 0; }

        .status {
            background: #e8f5ec;
            padding: 12px 16px;
            border-radius: 6px;
            color: #226b3a;
            display: inline-block;
        }

        footer {
            margin-top: 35px;
            text-align: center;
            color: #777;
            font-size: 13px;
        }
    </style>
</head>

<body>

<header>
    <h2>eIRv3</h2>
</header>

<div class="container">
    <div class="card">
        <h1>Welcome to eIRv3</h1>

        <p>Application Setup Dashboard</p>

        <div class="status">
            Laravel application is running
        </div>

        <hr>

        <h3>System Information</h3>

        <p>Application: eIRv3</p>
        <p>Status: Initial Setup</p>
        <p>Environment: {{ app()->environment() }}</p>

        <p>
            The application modules and user
            authentication will be configured next.
        </p>
    </div>

    <footer>
        &copy; {{ date('Y') }} eIRv3
    </footer>
</div>

</body>
</html>
