<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Main Layout</title>
</head>
<body>
<h1>Topup Logger</h1>

<div>
    <form>
        <input type="button" value="Go back!" onclick="window.history.back()">
    </form>
    <hr />
</div>

@yield('content')

</body>
</html>
