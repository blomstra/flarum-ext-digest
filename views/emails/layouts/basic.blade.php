<!doctype html>
<html>
<head>
    <style>
        body {
            color: #111;
            font-family: system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",Ubuntu,Cantarell,Oxygen,Roboto,Helvetica,Arial,sans-serif;
            font-size: 13px;
            line-height: 1.5;
            margin: 0;
            padding: 0;
        }

        a {
            color: #426799;
            text-decoration: underline;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 10px 20px;
        }

        .Hero {
            margin-top: -1px;
            background: #e8ecf3;
            text-align: center;
            color: #333;
        }

        .Hero .container {
            padding-top: 25px;
            padding-bottom: 10px;
        }

        .Post {
            border-bottom: 1px solid #e8ecf3;
            padding: 5px 0;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    @yield('content')
</body>
</html>
