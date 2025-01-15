<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Success</title>
</head>

<body>
    <div>
        <h1>Payment Success Redirect after 2 Seconds.</h1>
    </div>
    <script>
        const redirectTimeout = setTimeout(() => {window.location.replace("http://localhost:3000/success");}, 2000);

        function myStopFunction() {
            clearTimeout(redirectTimeout);
        }
    </script>
</body>

</html>