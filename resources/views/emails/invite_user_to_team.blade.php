<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Invitation Email Notification</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .container {
            width: 100%;
            padding: 20px;
            background-color: #fff;
            margin: 20px auto;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .footer {
            text-align: center;
            font-size: 12px;
            color: #777;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="content">
            <strong>
                Congrats! Your account has just been created now, so you can login and start exploring our services.
            </strong> <br />

            <strong> Your Email:  {{ $email }} </strong>
                </br>
            <strong> Your Password:   {{ $password }} </strong>

            <form action="https://app.customercloudhq.com/login">
                <input type="submit" value="Login to Dashboard" />
            </form>

            
        </div>
        <div class="footer">
            <b>Regards,</b> <br />
            <p>&copy; {{ date('Y') }} Customer Cloud. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
